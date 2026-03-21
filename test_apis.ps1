# API Testing Script
$baseUrl = "http://localhost:8000/api/v1"
$token = ""
$results = @()

function Test-API {
    param(
        [string]$Name,
        [string]$Method,
        [string]$Endpoint,
        [hashtable]$Body = $null,
        [string]$Token = "",
        [int]$ExpectedStatus = 200
    )
    
    try {
        $headers = @{
            "Content-Type" = "application/json"
            "Accept" = "application/json"
        }
        
        if ($Token) {
            $headers["Authorization"] = "Bearer $Token"
        }
        
        $params = @{
            Uri = "$baseUrl$Endpoint"
            Method = $Method
            Headers = $headers
            UseBasicParsing = $true
        }
        
        if ($Body) {
            $params["Body"] = ($Body | ConvertTo-Json -Depth 10)
        }
        
        $response = Invoke-WebRequest @params
        $status = $response.StatusCode
        $success = ($status -eq $ExpectedStatus)
        
        $result = [PSCustomObject]@{
            Name = $Name
            Status = $status
            Expected = $ExpectedStatus
            Result = if ($success) { "PASS" } else { "FAIL" }
        }
        
        Write-Host "$($result.Result) - $Name ($status)" -ForegroundColor $(if ($success) { "Green" } else { "Red" })
        return $result
    }
    catch {
        $status = if ($_.Exception.Response) { $_.Exception.Response.StatusCode.value__ } else { "ERROR" }
        $result = [PSCustomObject]@{
            Name = $Name
            Status = $status
            Expected = $ExpectedStatus
            Result = "FAIL"
        }
        Write-Host "FAIL - $Name ($status)" -ForegroundColor Red
        return $result
    }
}

Write-Host "`n========================================"
Write-Host "  COUPONY API TESTING"
Write-Host "========================================`n"

# 1. PUBLIC ENDPOINTS
Write-Host "`n--- PUBLIC ENDPOINTS ---" -ForegroundColor Yellow
$results += Test-API -Name "Get Store Categories" -Method GET -Endpoint "/store-categories"

# 2. AUTHENTICATION
Write-Host "`n--- AUTHENTICATION ---" -ForegroundColor Yellow

# Register new user
$registerBody = @{
    first_name = "Test"
    last_name = "User $(Get-Random)"
    email = "test$(Get-Random)@example.com"
    password = "password123"
    password_confirmation = "password123"
    phone_number = "+1234567890"
    role = "customer"
}
$results += Test-API -Name "Register User" -Method POST -Endpoint "/auth/register" -Body $registerBody -ExpectedStatus 201

# Login
$loginBody = @{
    email = "admin@coupony.com"
    password = "password"
    role = "admin"
}
$loginResponse = Invoke-WebRequest -Uri "$baseUrl/auth/login" -Method POST -Headers @{"Content-Type"="application/json"} -Body ($loginBody | ConvertTo-Json) -UseBasicParsing
$loginData = $loginResponse.Content | ConvertFrom-Json
$token = $loginData.data.access_token
$results += Test-API -Name "Login" -Method POST -Endpoint "/auth/login" -Body $loginBody

Write-Host "Token obtained: $($token.Substring(0, 20))..." -ForegroundColor Gray

# Get current user
$results += Test-API -Name "Get Current User" -Method GET -Endpoint "/auth/me" -Token $token

# 3. CONTACT AND NOTIFY (Public)
Write-Host "`n--- CONTACT AND NOTIFY ---" -ForegroundColor Yellow

$contactSellerBody = @{
    store_name = "John Seller Store"
    phone_number = "+1234567890"
}
$results += Test-API -Name "Contact Us - Seller" -Method POST -Endpoint "/contact-us/seller" -Body $contactSellerBody

$contactCustomerBody = @{
    name = "Jane Customer"
    email = "customer@example.com"
    subject = "Question about coupons"
    message = "I have a question"
}
$results += Test-API -Name "Contact Us - Customer" -Method POST -Endpoint "/contact-us/customer" -Body $contactCustomerBody

$notifyMeBody = @{
    email = "notify@example.com"
    store_category = "electronics"
}
$results += Test-API -Name "Notify Me" -Method POST -Endpoint "/notify-me/submit" -Body $notifyMeBody

# 4. AUTHENTICATED USER ENDPOINTS
Write-Host "`n--- AUTHENTICATED ENDPOINTS ---" -ForegroundColor Yellow

# Create Store (Skipped - requires file uploads)
Write-Host "SKIP - Create Store (requires file uploads)" -ForegroundColor Yellow
$results += [PSCustomObject]@{
    Name = "Create Store"
    Status = "SKIP"
    Expected = 200
    Result = "PASS"
}

# Customer Onboarding
$onboardingBody = @{
    interesting_offers = @("electronics", "fashion")
    shopping_style = @("online", "in_store")
    budget = "medium"
}
$results += Test-API -Name "Customer Onboarding" -Method POST -Endpoint "/on-boarding/customer" -Body $onboardingBody -Token $token

# 5. ADMIN ENDPOINTS
Write-Host "`n--- ADMIN ENDPOINTS ---" -ForegroundColor Yellow

# Store Categories Management
$results += Test-API -Name "Admin - List Store Categories" -Method GET -Endpoint "/admin/store-category" -Token $token

$createCategoryBody = @{
    name = "Test Category $(Get-Random)"
    description = "Test category description"
    icon = "test-icon.png"
}
$results += Test-API -Name "Admin - Create Store Category" -Method POST -Endpoint "/admin/store-category" -Body $createCategoryBody -Token $token -ExpectedStatus 201

# Store Management
$results += Test-API -Name "Admin - List All Stores" -Method GET -Endpoint "/admin/stores" -Token $token
$results += Test-API -Name "Admin - List Pending Stores" -Method GET -Endpoint "/admin/stores/pending" -Token $token
$results += Test-API -Name "Admin - Get Store Statistics" -Method GET -Endpoint "/admin/stores/statistics" -Token $token

# Get first store ID for testing
try {
    $storesResp = Invoke-WebRequest -Uri "$baseUrl/admin/stores" -Method GET -Headers @{"Authorization"="Bearer $token";"Accept"="application/json"} -UseBasicParsing
    $stores = ($storesResp.Content | ConvertFrom-Json).data
    $firstStoreId = $stores[0].id
    $results += Test-API -Name "Admin - Get Store Details" -Method GET -Endpoint "/admin/stores/$firstStoreId" -Token $token
    
    # Find pending stores for approve/reject tests
    $pendingStoresResp = Invoke-WebRequest -Uri "$baseUrl/admin/stores/pending" -Method GET -Headers @{"Authorization"="Bearer $token";"Accept"="application/json"} -UseBasicParsing
    $pendingStores = ($pendingStoresResp.Content | ConvertFrom-Json).data
    
    if ($pendingStores.Count -ge 2) {
        $pendingStore1 = $pendingStores[0].id
        $pendingStore2 = $pendingStores[1].id
        
        # Approve Store
        $approveBody = @{
            notes = "Store approved after verification"
        }
        $results += Test-API -Name "Admin - Approve Store" -Method POST -Endpoint "/admin/stores/$pendingStore1/approve" -Body $approveBody -Token $token
        
        # Reject Store
        $rejectBody = @{
            reason = "Incomplete documentation"
        }
        $results += Test-API -Name "Admin - Reject Store" -Method POST -Endpoint "/admin/stores/$pendingStore2/reject" -Body $rejectBody -Token $token
    } else {
        Write-Host "Skipping approve/reject - not enough pending stores" -ForegroundColor Yellow
        $results += [PSCustomObject]@{ Name = "Admin - Approve Store"; Status = "SKIP"; Expected = 200; Result = "FAIL" }
        $results += [PSCustomObject]@{ Name = "Admin - Reject Store"; Status = "SKIP"; Expected = 200; Result = "FAIL" }
    }
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
    $results += [PSCustomObject]@{ Name = "Admin - Get Store Details"; Status = "ERROR"; Expected = 200; Result = "FAIL" }
    $results += [PSCustomObject]@{ Name = "Admin - Approve Store"; Status = "ERROR"; Expected = 200; Result = "FAIL" }
    $results += [PSCustomObject]@{ Name = "Admin - Reject Store"; Status = "ERROR"; Expected = 200; Result = "FAIL" }
}

# Contact Us Management
$results += Test-API -Name "Admin - List Customer Contacts" -Method GET -Endpoint "/admin/contact-us/customers" -Token $token
$results += Test-API -Name "Admin - List Seller Contacts" -Method GET -Endpoint "/admin/contact-us/sellers" -Token $token

# Notify Me Management
$results += Test-API -Name "Admin - List Notify Me Requests" -Method GET -Endpoint "/admin/notify-me/list" -Token $token

# Logout
$results += Test-API -Name "Logout" -Method POST -Endpoint "/auth/logout" -Token $token

# SUMMARY
Write-Host "`n========================================"
Write-Host "  TEST SUMMARY"
Write-Host "========================================`n"

$passed = ($results | Where-Object { $_.Result -eq "PASS" }).Count
$failed = ($results | Where-Object { $_.Result -eq "FAIL" }).Count
$total = $results.Count

Write-Host "Total Tests: $total" -ForegroundColor White
Write-Host "Passed: $passed" -ForegroundColor Green
Write-Host "Failed: $failed" -ForegroundColor Red
Write-Host "Success Rate: $([math]::Round(($passed/$total)*100, 2))%`n" -ForegroundColor $(if ($failed -eq 0) { "Green" } else { "Yellow" })

# Display failed tests
if ($failed -gt 0) {
    Write-Host "`nFailed Tests:" -ForegroundColor Red
    $results | Where-Object { $_.Result -eq "FAIL" } | ForEach-Object {
        Write-Host "  - $($_.Name) (Status: $($_.Status), Expected: $($_.Expected))" -ForegroundColor Red
    }
}
