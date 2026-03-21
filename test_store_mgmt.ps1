$baseUrl = "http://localhost:8000/api/v1"
$token = ""

Write-Host "`nSTORE MANAGEMENT API TESTING`n" -ForegroundColor Cyan

# Login
Write-Host "1. Login as Admin..." -ForegroundColor Yellow
$loginBody = '{"email":"admin@coupony.com","password":"password","role":"admin"}'
$loginResp = Invoke-WebRequest -Uri "$baseUrl/auth/login" -Method POST -Headers @{"Content-Type"="application/json"} -Body $loginBody -UseBasicParsing
$token = ($loginResp.Content | ConvertFrom-Json).data.access_token
Write-Host "   Success! Token: $($token.Substring(0,20))...`n" -ForegroundColor Green

# Get Statistics
Write-Host "2. Get Store Statistics..." -ForegroundColor Yellow
$statsResp = Invoke-WebRequest -Uri "$baseUrl/admin/stores/statistics" -Headers @{"Authorization"="Bearer $token"} -UseBasicParsing
$stats = $statsResp.Content | ConvertFrom-Json
Write-Host "   Total: $($stats.data.total), Pending: $($stats.data.pending), Active: $($stats.data.active)`n" -ForegroundColor Green

# List Pending Stores
Write-Host "3. List Pending Stores..." -ForegroundColor Yellow
$pendingResp = Invoke-WebRequest -Uri "$baseUrl/admin/stores/pending" -Headers @{"Authorization"="Bearer $token"} -UseBasicParsing
$pending = $pendingResp.Content | ConvertFrom-Json
Write-Host "   Found $($pending.data.Count) pending store(s)`n" -ForegroundColor Green

if ($pending.data.Count -gt 0) {
    $storeId = $pending.data[0].id
    $storeName = $pending.data[0].name
    Write-Host "   Testing with Store: $storeName (ID: $storeId)`n" -ForegroundColor Gray

    # Get Store Details
    Write-Host "4. Get Store Details..." -ForegroundColor Yellow
    $detailsResp = Invoke-WebRequest -Uri "$baseUrl/admin/stores/$storeId" -Headers @{"Authorization"="Bearer $token"} -UseBasicParsing
    $details = $detailsResp.Content | ConvertFrom-Json
    Write-Host "   Name: $($details.data.name), Status: $($details.data.status)`n" -ForegroundColor Green

    # Get Verification Documents
    Write-Host "5. Get Verification Documents..." -ForegroundColor Yellow
    $verifResp = Invoke-WebRequest -Uri "$baseUrl/admin/stores/$storeId/verifications" -Headers @{"Authorization"="Bearer $token"} -UseBasicParsing
    $verifs = $verifResp.Content | ConvertFrom-Json
    Write-Host "   Found $($verifs.data.Count) document(s)" -ForegroundColor Green
    
    foreach ($doc in $verifs.data) {
        Write-Host "     - $($doc.document_type): $($doc.status)" -ForegroundColor Gray
    }
    Write-Host ""

    # Approve a document
    if ($verifs.data.Count -gt 0) {
        $docId = $verifs.data[0].id
        Write-Host "6. Approve Document ($($verifs.data[0].document_type))..." -ForegroundColor Yellow
        $approveDocBody = '{"notes":"Approved by test"}'
        try {
            $approveDocResp = Invoke-WebRequest -Uri "$baseUrl/admin/stores/$storeId/verifications/$docId/approve" -Method POST -Headers @{"Authorization"="Bearer $token";"Content-Type"="application/json"} -Body $approveDocBody -UseBasicParsing
            Write-Host "   Document approved!`n" -ForegroundColor Green
        } catch {
            Write-Host "   Error: $($_.Exception.Message)`n" -ForegroundColor Red
        }
    }

    # Approve Store
    Write-Host "7. Approve Store..." -ForegroundColor Yellow
    $approveStoreBody = '{"notes":"Approved by test"}'
    try {
        $approveStoreResp = Invoke-WebRequest -Uri "$baseUrl/admin/stores/$storeId/approve" -Method POST -Headers @{"Authorization"="Bearer $token";"Content-Type"="application/json"} -Body $approveStoreBody -UseBasicParsing
        Write-Host "   Store approved!`n" -ForegroundColor Green
    } catch {
        Write-Host "   Error: $($_.Exception.Message)`n" -ForegroundColor Red
    }
}

# Final Stats
Write-Host "8. Final Statistics..." -ForegroundColor Yellow
$finalStatsResp = Invoke-WebRequest -Uri "$baseUrl/admin/stores/statistics" -Headers @{"Authorization"="Bearer $token"} -UseBasicParsing
$finalStats = $finalStatsResp.Content | ConvertFrom-Json
Write-Host "   Total: $($finalStats.data.total), Pending: $($finalStats.data.pending), Active: $($finalStats.data.active)`n" -ForegroundColor Green

Write-Host "Testing Complete!`n" -ForegroundColor Cyan
