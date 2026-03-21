# PowerShell script to update Postman collection

$collection = Get-Content postman_collection.json -Raw | ConvertFrom-Json

# Find Authentication folder
$authFolder = $collection.item | Where-Object { $_.name -eq "Authentication" }

# Add Password Reset endpoints after Refresh Token
$passwordResetItems = @(
    @{
        name = "Forgot Password"
        request = @{
            method = "POST"
            header = @(
                @{ key = "Accept"; value = "application/json" }
                @{ key = "Content-Type"; value = "application/json" }
            )
            body = @{
                mode = "raw"
                raw = '{"email": "user@example.com"}'
            }
            url = @{
                raw = "{{baseUrl}}/api/v1/auth/password/forgot"
                host = @("{{baseUrl}}")
                path = @("api", "v1", "auth", "password", "forgot")
            }
        }
        response = @()
    }
    @{
        name = "Verify Password Reset OTP"
        request = @{
            method = "POST"
            header = @(
                @{ key = "Accept"; value = "application/json" }
                @{ key = "Content-Type"; value = "application/json" }
            )
            body = @{
                mode = "raw"
                raw = '{"email": "user@example.com", "code": "123456"}'
            }
            url = @{
                raw = "{{baseUrl}}/api/v1/auth/password/verify-otp"
                host = @("{{baseUrl}}")
                path = @("api", "v1", "auth", "password", "verify-otp")
            }
        }
        response = @()
        event = @(
            @{
                listen = "test"
                script = @{
                    exec = @(
                        "if (pm.response.code === 200) {",
                        "    var jsonData = pm.response.json();",
                        "    if (jsonData.data && jsonData.data.reset_token) {",
                        "        pm.collectionVariables.set('reset_token', jsonData.data.reset_token);",
                        "    }",
                        "}"
                    )
                    type = "text/javascript"
                }
            }
        )
    }
    @{
        name = "Reset Password"
        request = @{
            method = "POST"
            header = @(
                @{ key = "Accept"; value = "application/json" }
                @{ key = "Content-Type"; value = "application/json" }
            )
            body = @{
                mode = "raw"
                raw = '{"reset_token": "{{reset_token}}", "password": "NewPassword123!", "password_confirmation": "NewPassword123!"}'
            }
            url = @{
                raw = "{{baseUrl}}/api/v1/auth/password/reset"
                host = @("{{baseUrl}}")
                path = @("api", "v1", "auth", "password", "reset")
            }
        }
        response = @()
    }
    @{
        name = "Resend Password Reset OTP"
        request = @{
            method = "POST"
            header = @(
                @{ key = "Accept"; value = "application/json" }
                @{ key = "Content-Type"; value = "application/json" }
            )
            body = @{
                mode = "raw"
                raw = '{"email": "user@example.com"}'
            }
            url = @{
                raw = "{{baseUrl}}/api/v1/auth/password/resend-otp"
                host = @("{{baseUrl}}")
                path = @("api", "v1", "auth", "password", "resend-otp")
            }
        }
        response = @()
    }
)

# Insert password reset items after Refresh Token (index 4)
$newAuthItems = @()
$newAuthItems += $authFolder.item[0..4]  # Register, Login, Logout, Get Current User, Refresh Token
$newAuthItems += $passwordResetItems
$newAuthItems += $authFolder.item[5..($authFolder.item.Count - 1)]  # Rest of items

$authFolder.item = $newAuthItems

# Find Stores folder and update endpoints
$storesFolder = $collection.item | Where-Object { $_.name -eq "Stores" }

# Update Create Store endpoint
$createStore = $storesFolder.item | Where-Object { $_.name -eq "Create Store" }
if ($createStore) {
    $createStore.request.url.raw = "{{baseUrl}}/api/v1/stores"
    $createStore.request.url.path = @("api", "v1", "stores")
}

# Update Get My Stores endpoint
$myStores = $storesFolder.item | Where-Object { $_.name -eq "Get My Stores" }
if ($myStores) {
    $myStores.name = "Get My Stores (Index)"
    $myStores.request.url.raw = "{{baseUrl}}/api/v1/stores"
    $myStores.request.url.path = @("api", "v1", "stores")
}

# Add reset_token variable
$resetTokenVar = @{
    key = "reset_token"
    value = ""
    type = "string"
}

if (-not ($collection.variable | Where-Object { $_.key -eq "reset_token" })) {
    $collection.variable += $resetTokenVar
}

# Save updated collection
$collection | ConvertTo-Json -Depth 100 | Set-Content postman_collection.json

Write-Host "Postman collection updated successfully!" -ForegroundColor Green
Write-Host "Added:" -ForegroundColor Cyan
Write-Host "  - Forgot Password endpoint" -ForegroundColor White
Write-Host "  - Verify Password Reset OTP endpoint" -ForegroundColor White
Write-Host "  - Reset Password endpoint" -ForegroundColor White
Write-Host "  - Resend Password Reset OTP endpoint" -ForegroundColor White
Write-Host "  - Updated Store endpoints to new routes" -ForegroundColor White
