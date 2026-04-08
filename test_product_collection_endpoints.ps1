$ErrorActionPreference = "Stop"

$baseUrl = "http://127.0.0.1:8000/api/v1"
$results = New-Object System.Collections.Generic.List[object]

function Add-Result {
    param(
        [string]$Name,
        [bool]$Passed,
        [int]$StatusCode = 0,
        [string]$Notes = ""
    )

    $results.Add([pscustomobject]@{
        name = $Name
        passed = $Passed
        status_code = $StatusCode
        notes = $Notes
    })
}

function Invoke-JsonRequest {
    param(
        [string]$Method,
        [string]$Uri,
        [hashtable]$Headers = @{},
        [object]$Body = $null
    )

    $params = @{
        Uri = $Uri
        Method = $Method
        Headers = $Headers
        UseBasicParsing = $true
        ErrorAction = "Stop"
    }

    if ($null -ne $Body) {
        $params.ContentType = "application/json"
        $params.Body = ($Body | ConvertTo-Json -Depth 20)
    }

    try {
        $response = Invoke-WebRequest @params
        return [pscustomobject]@{
            StatusCode = [int]$response.StatusCode
            Json = if ($response.Content) { $response.Content | ConvertFrom-Json } else { $null }
            Raw = $response.Content
        }
    } catch {
        $statusCode = 0
        $raw = ""

        if ($_.Exception.Response) {
            $statusCode = [int]$_.Exception.Response.StatusCode.value__
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $raw = $reader.ReadToEnd()
        } elseif ($_.ErrorDetails.Message) {
            $raw = $_.ErrorDetails.Message
        } else {
            $raw = $_.Exception.Message
        }

        $json = $null
        try {
            if ($raw) {
                $json = $raw | ConvertFrom-Json
            }
        } catch {
        }

        return [pscustomobject]@{
            StatusCode = $statusCode
            Json = $json
            Raw = $raw
        }
    }
}

function Invoke-MultipartCurl {
    param(
        [string]$Method,
        [string]$Uri,
        [hashtable]$Headers = @{},
        [string[]]$FormParts
    )

    $arguments = @("--silent", "--show-error", "--location", "--request", $Method, $Uri, "--write-out", "`nHTTPSTATUS:%{http_code}")

    foreach ($header in $Headers.GetEnumerator()) {
        $arguments += @("--header", "$($header.Key): $($header.Value)")
    }

    foreach ($part in $FormParts) {
        $arguments += @("--form", $part)
    }

    $raw = & curl.exe @arguments
    $statusCode = 0
    $body = $raw

    $statusMatch = [regex]::Match([string]$raw, "HTTPSTATUS:(\d{3})\s*$")
    if ($statusMatch.Success) {
        $statusCode = [int]$statusMatch.Groups[1].Value
        $body = ([string]$raw -replace "HTTPSTATUS:\d{3}\s*$", "").Trim()
    }

    $json = $null

    try {
        $json = $body | ConvertFrom-Json
    } catch {
    }

    return [pscustomobject]@{
        StatusCode = $statusCode
        Raw = $body
        Json = $json
    }
}

$tempDir = Join-Path $PWD "storage\app\testing-product-collection"
New-Item -ItemType Directory -Force -Path $tempDir | Out-Null

$pngBase64 = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7Z0uoAAAAASUVORK5CYII="
$image1Path = Join-Path $tempDir "product-1.png"
$image2Path = Join-Path $tempDir "product-2.png"
[IO.File]::WriteAllBytes($image1Path, [Convert]::FromBase64String($pngBase64))
[IO.File]::WriteAllBytes($image2Path, [Convert]::FromBase64String($pngBase64))

$unique = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
$createdCategoryId = $null
$storeId = $null
$productId = $null
$variantId = $null
$imageId = $null
$adminToken = $null

try {
    $sellerLoginAsCollection = Invoke-JsonRequest -Method "POST" -Uri "$baseUrl/auth/login" -Headers @{ Accept = "application/json" } -Body @{
        email = "seller1@example.com"
        password = "password"
    }
    Add-Result -Name "Collection Auth Helper - Seller Login" -Passed ($sellerLoginAsCollection.StatusCode -ge 200 -and $sellerLoginAsCollection.StatusCode -lt 300) -StatusCode $sellerLoginAsCollection.StatusCode -Notes "Collection request is missing required role."

    $adminLoginAsCollection = Invoke-JsonRequest -Method "POST" -Uri "$baseUrl/auth/login" -Headers @{ Accept = "application/json" } -Body @{
        email = "admin@coupony.com"
        password = "password"
    }
    Add-Result -Name "Collection Auth Helper - Admin Login" -Passed ($adminLoginAsCollection.StatusCode -ge 200 -and $adminLoginAsCollection.StatusCode -lt 300) -StatusCode $adminLoginAsCollection.StatusCode -Notes "Collection request is missing required role."

    $adminLogin = Invoke-JsonRequest -Method "POST" -Uri "$baseUrl/auth/login" -Headers @{ Accept = "application/json" } -Body @{
        email = "admin@coupony.com"
        password = "password"
        role = "admin"
    }

    if ($adminLogin.StatusCode -ne 200) {
        throw "Admin login failed: $($adminLogin.Raw)"
    }

    $adminToken = $adminLogin.Json.data.access_token
    $authHeaders = @{
        Accept = "application/json"
        Authorization = "Bearer $adminToken"
    }

    $adminCategories = Invoke-JsonRequest -Method "GET" -Uri "$baseUrl/admin/categories?active=" -Headers $authHeaders
    Add-Result -Name "List Admin Categories" -Passed ($adminCategories.StatusCode -eq 200) -StatusCode $adminCategories.StatusCode

    $createCategory = Invoke-JsonRequest -Method "POST" -Uri "$baseUrl/admin/categories" -Headers $authHeaders -Body @{
        name = "Collection Test Category $unique"
        slug = "collection-test-category-$unique"
        description = "Temporary category for endpoint verification"
        parent_id = $null
        sort_order = 1
        is_active = $true
    }
    $createdCategoryId = $createCategory.Json.data.id
    Add-Result -Name "Create Admin Category" -Passed ($createCategory.StatusCode -eq 201 -and $null -ne $createdCategoryId) -StatusCode $createCategory.StatusCode

    $updateCategory = Invoke-JsonRequest -Method "PUT" -Uri "$baseUrl/admin/categories/$createdCategoryId" -Headers $authHeaders -Body @{
        name = "Collection Test Category Updated $unique"
        description = "Updated during collection test"
        sort_order = 2
        is_active = $true
    }
    Add-Result -Name "Update Admin Category" -Passed ($updateCategory.StatusCode -eq 200) -StatusCode $updateCategory.StatusCode

    $storesResponse = Invoke-JsonRequest -Method "GET" -Uri "$baseUrl/admin/stores" -Headers $authHeaders
    if ($storesResponse.StatusCode -ne 200 -or $storesResponse.Json.data.Count -lt 1) {
        throw "Could not retrieve a store for product endpoint testing."
    }
    $storeId = $storesResponse.Json.data[0].id

    $createProduct = Invoke-MultipartCurl -Method "POST" -Uri "$baseUrl/stores/$storeId/products" -Headers @{ Accept = "application/json"; Authorization = "Bearer $adminToken" } -FormParts @(
        "title=Example Product $unique",
        "slug=example-product-$unique",
        "short_description=Short description",
        "description=Long description",
        "product_type=standard",
        "base_price=100.00",
        "compare_at_price=120.00",
        "currency=EGP",
        "sku=SKU-$unique",
        "status=draft",
        "is_featured=false",
        "category_ids[0]=$createdCategoryId",
        "images[0][file]=@$image1Path;type=image/png",
        "images[0][sort_order]=0",
        "images[0][is_primary]=true",
        "variants[0][title]=Red / XL",
        "variants[0][option_summary]=Color: Red, Size: XL",
        "variants[0][sku]=SKU-$unique-RED-XL",
        "variants[0][barcode]=123456789",
        "variants[0][price]=110.00",
        "variants[0][compare_at_price]=130.00",
        "variants[0][currency]=EGP",
        "variants[0][sort_order]=0",
        "variants[0][is_default]=true",
        "variants[0][is_active]=true",
        "variants[0][attributes][0][attribute_name]=color",
        "variants[0][attributes][0][attribute_value]=red",
        "variants[0][attributes][0][sort_order]=0",
        "variants[0][attributes][1][attribute_name]=size",
        "variants[0][attributes][1][attribute_value]=XL",
        "variants[0][attributes][1][sort_order]=1"
    )
    $productId = $createProduct.Json.data.id
    $createProductNotes = ""
    if ($null -eq $productId) {
        $createProductNotes = $createProduct.Raw
    }
    Add-Result -Name "Create Product (Multipart)" -Passed ($createProduct.StatusCode -eq 201 -and $null -ne $productId) -StatusCode $createProduct.StatusCode -Notes $createProductNotes

    if ($null -eq $productId) {
        $setupCreateProduct = Invoke-MultipartCurl -Method "POST" -Uri "$baseUrl/stores/$storeId/products" -Headers @{ Accept = "application/json"; Authorization = "Bearer $adminToken" } -FormParts @(
            "title=Setup Product $unique",
            "slug=setup-product-$unique",
            "short_description=Short description",
            "description=Long description",
            "product_type=standard",
            "base_price=100.00",
            "compare_at_price=120.00",
            "currency=EGP",
            "sku=SETUP-SKU-$unique",
            "status=draft",
            "is_featured=0",
            "category_ids[0]=$createdCategoryId",
            "images[0][file]=@$image1Path;type=image/png",
            "images[0][sort_order]=0",
            "images[0][is_primary]=1",
            "variants[0][title]=Red / XL",
            "variants[0][option_summary]=Color: Red, Size: XL",
            "variants[0][sku]=SETUP-SKU-$unique-RED-XL",
            "variants[0][barcode]=123456789",
            "variants[0][price]=110.00",
            "variants[0][compare_at_price]=130.00",
            "variants[0][currency]=EGP",
            "variants[0][sort_order]=0",
            "variants[0][is_default]=1",
            "variants[0][is_active]=1",
            "variants[0][attributes][0][attribute_name]=color",
            "variants[0][attributes][0][attribute_value]=red",
            "variants[0][attributes][0][sort_order]=0",
            "variants[0][attributes][1][attribute_name]=size",
            "variants[0][attributes][1][attribute_value]=XL",
            "variants[0][attributes][1][sort_order]=1"
        )

        $productId = $setupCreateProduct.Json.data.id

        if ($setupCreateProduct.StatusCode -ne 201 -or $null -eq $productId) {
            throw "Setup product creation failed: $($setupCreateProduct.Raw)"
        }
    }

    $listStoreProducts = Invoke-JsonRequest -Method "GET" -Uri "$baseUrl/stores/$storeId/products?status=&search=&is_featured=&per_page=15" -Headers $authHeaders
    Add-Result -Name "List Store Products" -Passed ($listStoreProducts.StatusCode -eq 200) -StatusCode $listStoreProducts.StatusCode

    $showStoreProduct = Invoke-JsonRequest -Method "GET" -Uri "$baseUrl/stores/$storeId/products/$productId" -Headers $authHeaders
    Add-Result -Name "Show Store Product" -Passed ($showStoreProduct.StatusCode -eq 200) -StatusCode $showStoreProduct.StatusCode

    $updateStoreProduct = Invoke-JsonRequest -Method "PUT" -Uri "$baseUrl/stores/$storeId/products/$productId" -Headers $authHeaders -Body @{
        title = "Updated Product $unique"
        slug = "updated-product-$unique"
        status = "active"
        is_featured = $true
        category_ids = @([int64]$createdCategoryId)
        variants = @(
            @{
                title = "Blue / L"
                option_summary = "Color: Blue, Size: L"
                sku = "SKU-$unique-BLUE-L"
                barcode = "987654321"
                price = 115
                compare_at_price = 140
                currency = "EGP"
                sort_order = 0
                is_default = $true
                is_active = $true
                attributes = @(
                    @{ attribute_name = "color"; attribute_value = "blue"; sort_order = 0 },
                    @{ attribute_name = "size"; attribute_value = "L"; sort_order = 1 }
                )
            }
        )
    }
    Add-Result -Name "Update Store Product (JSON)" -Passed ($updateStoreProduct.StatusCode -eq 200) -StatusCode $updateStoreProduct.StatusCode

    $updateStatus = Invoke-JsonRequest -Method "PATCH" -Uri "$baseUrl/stores/$storeId/products/$productId/status" -Headers $authHeaders -Body @{
        status = "active"
    }
    Add-Result -Name "Update Product Status" -Passed ($updateStatus.StatusCode -eq 200) -StatusCode $updateStatus.StatusCode

    $listVariants = Invoke-JsonRequest -Method "GET" -Uri "$baseUrl/stores/$storeId/products/$productId/variants" -Headers $authHeaders
    Add-Result -Name "List Product Variants" -Passed ($listVariants.StatusCode -eq 200) -StatusCode $listVariants.StatusCode

    $createVariant = Invoke-JsonRequest -Method "POST" -Uri "$baseUrl/stores/$storeId/products/$productId/variants" -Headers $authHeaders -Body @{
        title = "Blue / XL"
        option_summary = "Color: Blue, Size: XL"
        sku = "SKU-$unique-BLUE-XL"
        barcode = "987654322"
        price = 125
        compare_at_price = 150
        currency = "EGP"
        sort_order = 1
        is_default = $false
        is_active = $true
        attributes = @(
            @{ attribute_name = "color"; attribute_value = "blue"; sort_order = 0 },
            @{ attribute_name = "size"; attribute_value = "XL"; sort_order = 1 }
        )
    }
    $variantId = $createVariant.Json.data.id
    Add-Result -Name "Create Product Variant" -Passed ($createVariant.StatusCode -eq 201 -and $null -ne $variantId) -StatusCode $createVariant.StatusCode

    $showVariant = Invoke-JsonRequest -Method "GET" -Uri "$baseUrl/stores/$storeId/products/$productId/variants/$variantId" -Headers $authHeaders
    Add-Result -Name "Show Product Variant" -Passed ($showVariant.StatusCode -eq 200) -StatusCode $showVariant.StatusCode

    $updateVariant = Invoke-JsonRequest -Method "PUT" -Uri "$baseUrl/stores/$storeId/products/$productId/variants/$variantId" -Headers $authHeaders -Body @{
        title = "Blue / XXL"
        option_summary = "Color: Blue, Size: XXL"
        price = 135
        compare_at_price = 160
        is_default = $true
        is_active = $true
    }
    Add-Result -Name "Update Product Variant" -Passed ($updateVariant.StatusCode -eq 200) -StatusCode $updateVariant.StatusCode

    $replaceAttributes = Invoke-JsonRequest -Method "PUT" -Uri "$baseUrl/stores/$storeId/products/$productId/variants/$variantId/attributes" -Headers $authHeaders -Body @{
        attributes = @(
            @{ attribute_name = "color"; attribute_value = "navy"; sort_order = 0 },
            @{ attribute_name = "size"; attribute_value = "XXL"; sort_order = 1 }
        )
    }
    Add-Result -Name "Replace Variant Attributes" -Passed ($replaceAttributes.StatusCode -eq 200) -StatusCode $replaceAttributes.StatusCode

    $listImages = Invoke-JsonRequest -Method "GET" -Uri "$baseUrl/stores/$storeId/products/$productId/images" -Headers $authHeaders
    Add-Result -Name "List Product Images" -Passed ($listImages.StatusCode -eq 200) -StatusCode $listImages.StatusCode

    $uploadImages = Invoke-MultipartCurl -Method "POST" -Uri "$baseUrl/stores/$storeId/products/$productId/images" -Headers @{ Accept = "application/json"; Authorization = "Bearer $adminToken" } -FormParts @(
        "images[0][file]=@$image1Path;type=image/png",
        "images[0][sort_order]=0",
        "images[0][is_primary]=true",
        "images[1][file]=@$image2Path;type=image/png",
        "images[1][sort_order]=1",
        "images[1][is_primary]=false"
    )
    if ($uploadImages.Json -and $uploadImages.Json.data -and $uploadImages.Json.data.Count -gt 0) {
        $imageId = $uploadImages.Json.data[0].id
    }
    $uploadImagesNotes = ""
    if ($null -eq $imageId) {
        $uploadImagesNotes = $uploadImages.Raw
    }
    Add-Result -Name "Upload Product Images (Multipart)" -Passed ($uploadImages.StatusCode -eq 201 -and $null -ne $imageId) -StatusCode $uploadImages.StatusCode -Notes $uploadImagesNotes

    if ($null -eq $imageId) {
        $refreshedImages = Invoke-JsonRequest -Method "GET" -Uri "$baseUrl/stores/$storeId/products/$productId/images" -Headers $authHeaders
        if ($refreshedImages.StatusCode -eq 200 -and $refreshedImages.Json.data.Count -gt 0) {
            $imageId = $refreshedImages.Json.data[0].id
        }
    }

    $reorderImagesAsCollection = Invoke-JsonRequest -Method "PATCH" -Uri "$baseUrl/stores/$storeId/products/$productId/images/reorder" -Headers $authHeaders -Body @{
        images = @(
            @{
                id = [int]$imageId
                sort_order = 0
            }
        )
    }
    Add-Result -Name "Reorder Product Images" -Passed ($reorderImagesAsCollection.StatusCode -eq 200) -StatusCode $reorderImagesAsCollection.StatusCode -Notes "Collection body sends one image only; endpoint requires the full current image set."

    $setPrimaryImage = Invoke-JsonRequest -Method "PATCH" -Uri "$baseUrl/stores/$storeId/products/$productId/images/$imageId/primary" -Headers $authHeaders
    Add-Result -Name "Set Primary Product Image" -Passed ($setPrimaryImage.StatusCode -eq 200) -StatusCode $setPrimaryImage.StatusCode

    $publicList = Invoke-JsonRequest -Method "GET" -Uri "$baseUrl/products?category=$createdCategoryId&search=&featured=&per_page=15" -Headers @{ Accept = "application/json" }
    Add-Result -Name "List Public Products" -Passed ($publicList.StatusCode -eq 200) -StatusCode $publicList.StatusCode

    $publicShow = Invoke-JsonRequest -Method "GET" -Uri "$baseUrl/products/$productId" -Headers @{ Accept = "application/json" }
    Add-Result -Name "Show Public Product" -Passed ($publicShow.StatusCode -eq 200) -StatusCode $publicShow.StatusCode

    $publicCategories = Invoke-JsonRequest -Method "GET" -Uri "$baseUrl/categories" -Headers @{ Accept = "application/json" }
    Add-Result -Name "List Categories" -Passed ($publicCategories.StatusCode -eq 200) -StatusCode $publicCategories.StatusCode

    $categoryProducts = Invoke-JsonRequest -Method "GET" -Uri "$baseUrl/categories/$createdCategoryId/products?per_page=15" -Headers @{ Accept = "application/json" }
    Add-Result -Name "List Category Products" -Passed ($categoryProducts.StatusCode -eq 200) -StatusCode $categoryProducts.StatusCode

    $deleteImage = Invoke-JsonRequest -Method "DELETE" -Uri "$baseUrl/stores/$storeId/products/$productId/images/$imageId" -Headers $authHeaders
    Add-Result -Name "Delete Product Image" -Passed ($deleteImage.StatusCode -eq 200) -StatusCode $deleteImage.StatusCode

    $deleteVariant = Invoke-JsonRequest -Method "DELETE" -Uri "$baseUrl/stores/$storeId/products/$productId/variants/$variantId" -Headers $authHeaders
    Add-Result -Name "Delete Product Variant" -Passed ($deleteVariant.StatusCode -eq 200) -StatusCode $deleteVariant.StatusCode

    $deleteProduct = Invoke-JsonRequest -Method "DELETE" -Uri "$baseUrl/stores/$storeId/products/$productId" -Headers $authHeaders
    Add-Result -Name "Delete Store Product" -Passed ($deleteProduct.StatusCode -eq 200) -StatusCode $deleteProduct.StatusCode

    $deleteCategory = Invoke-JsonRequest -Method "DELETE" -Uri "$baseUrl/admin/categories/$createdCategoryId" -Headers $authHeaders
    Add-Result -Name "Delete Admin Category" -Passed ($deleteCategory.StatusCode -eq 200) -StatusCode $deleteCategory.StatusCode
}
finally {
    if (Test-Path $image1Path) { Remove-Item $image1Path -Force }
    if (Test-Path $image2Path) { Remove-Item $image2Path -Force }
}

$results | Format-Table -AutoSize
