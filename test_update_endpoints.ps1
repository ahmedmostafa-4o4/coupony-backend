# Test Store Update Endpoints
$baseUrl = "http://127.0.0.1:8000/api/v1"

Write-Host "=== Testing Store Update Endpoints ===" -ForegroundColor Cyan
Write-Host ""

# Login as seller1
Write-Host "1. Login as seller1@example.com..." -ForegroundColor Yellow
try {
    $loginResponse = Invoke-RestMethod -Uri "$baseUrl/auth/login" -Method Post -Body (@{
        email = "seller1@example.com"
        password = "password"
    } | ConvertTo-Json) -ContentType "application/json"
} catch {
    Write-Host "   Login failed: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "   Make sure the server is running and database is seeded" -ForegroundColor Yellow
    exit
}

$token = $loginResponse.data.access_token
Write-Host "   Token: $($token.Substring(0,20))..." -ForegroundColor Green
Write-Host ""

# Get user's stores
Write-Host "2. Get my stores..." -ForegroundColor Yellow
$headers = @{
    "Authorization" = "Bearer $token"
    "Accept" = "application/json"
}
$myStores = Invoke-RestMethod -Uri "$baseUrl/stores/my-stores" -Method Get -Headers $headers
Write-Host "   Found $($myStores.data.Count) store(s)" -ForegroundColor Green
$storeId = $myStores.data[0].id
Write-Host "   Store ID: $storeId" -ForegroundColor Green
Write-Host "   Store Status: $($myStores.data[0].status)" -ForegroundColor Green
Write-Host ""

# Update store basic info
Write-Host "3. Update store basic information..." -ForegroundColor Yellow
$updateData = @{
    name = "Updated Store Name"
    description = "Updated description for testing"
    email = "updated@example.com"
    phone = "+201234567890"
    category_ids = @(1, 2)
} | ConvertTo-Json

try {
    $updateResponse = Invoke-RestMethod -Uri "$baseUrl/stores/$storeId" -Method Put -Headers $headers -Body $updateData -ContentType "application/json"
    Write-Host "   Store updated successfully!" -ForegroundColor Green
    Write-Host "   New name: $($updateResponse.data.store.name)" -ForegroundColor Green
} catch {
    Write-Host "   Error: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

# Test updating verification document
Write-Host "4. Update verification document..." -ForegroundColor Yellow
Write-Host "   Creating test document file..." -ForegroundColor Gray

# Create a test file
$testFile = "test_document.txt"
"This is a test verification document" | Out-File -FilePath $testFile -Encoding utf8

# Prepare multipart form data
$boundary = [System.Guid]::NewGuid().ToString()
$LF = "`r`n"

$bodyLines = (
    "--$boundary",
    "Content-Disposition: form-data; name=`"document_type`"$LF",
    "commercial_register",
    "--$boundary",
    "Content-Disposition: form-data; name=`"document`"; filename=`"test_document.txt`"",
    "Content-Type: text/plain$LF",
    (Get-Content $testFile -Raw),
    "--$boundary--$LF"
) -join $LF

try {
    $updateDocHeaders = @{
        "Authorization" = "Bearer $token"
        "Content-Type" = "multipart/form-data; boundary=$boundary"
    }
    
    $docResponse = Invoke-RestMethod -Uri "$baseUrl/stores/$storeId/verification-document" -Method Post -Headers $updateDocHeaders -Body $bodyLines
    Write-Host "   Document updated successfully!" -ForegroundColor Green
} catch {
    Write-Host "   Error: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "   Note: File upload via PowerShell can be tricky. Test with Postman for better results." -ForegroundColor Yellow
}

# Cleanup
Remove-Item $testFile -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "=== Test Complete ===" -ForegroundColor Cyan
