# Postman Collection Updates

## Summary
Updated the Postman collection to reflect the refactored StoreController endpoints and new response structure.

---

## Changes Made

### 1. **Create Store Endpoint**
- **Old**: `POST /api/v1/store/create`
- **New**: `POST /api/v1/stores`
- **Route Name**: `stores.store`
- **Response Structure**: Now includes `success` boolean and standardized format

**Updated Request Body**:
```json
{
  "name": "My Store",
  "description": "Store description",
  "phone": "+1234567890",
  "tax_id": "TAX123456",
  "subscription_tier": "basic",
  "address_line1": "123 Main St",
  "address_line2": "Suite 100",
  "city": "New York",
  "latitude": "40.7128",
  "longitude": "-74.0060",
  "label": "Main Branch",
  "categories": [1, 2]
}
```

**File Uploads** (use form-data):
- `logo_url`: Image file (jpg, jpeg, png, max 2MB)
- `banner_url`: Image file (jpg, jpeg, png, max 5MB)
- `verification_docs.commercial_register`: PDF/Image (max 5MB)
- `verification_docs.tax_card`: PDF/Image (max 5MB)
- `verification_docs.id_card_front`: PDF/Image (max 5MB)
- `verification_docs.id_card_back`: PDF/Image (max 5MB)

**Expected Response** (201):
```json
{
  "success": true,
  "message": "Store created successfully. Pending approval.",
  "data": {
    "id": "uuid",
    "name": "My Store",
    "status": "pending",
    "logo_url": "http://domain.com/storage/stores/{storeId}/logo/file.jpg",
    "banner_url": "http://domain.com/storage/stores/{storeId}/banner/file.jpg",
    "categories": [...],
    "addresses": [...],
    "verifications": [...],
    "hours": [...]
  }
}
```

---

### 2. **Get My Stores Endpoint**
- **Old**: `GET /api/v1/stores/my-stores`
- **New**: `GET /api/v1/stores`
- **Route Name**: `stores.index`
- **Name Updated**: "Get My Stores (Index)"

**Expected Response** (200):
```json
{
  "success": true,
  "message": "Stores retrieved successfully.",
  "data": {
    "data": [
      {
        "id": "uuid",
        "name": "Store Name",
        "status": "pending",
        ...
      }
    ],
    "meta": {
      "total": 5
    }
  }
}
```

---

### 3. **Update Store Endpoint**
- **URL**: `PUT /api/v1/stores/{storeId}` (unchanged)
- **Route Name**: `stores.update`
- **Authorization**: Now uses StorePolicy

**Updated Request Body**:
```json
{
  "name": "Updated Store Name",
  "description": "Updated description",
  "email": "updated@example.com",
  "phone": "+1234567890",
  "tax_id": "TAX123456",
  "subscription_tier": "premium",
  "category_ids": [1, 2, 3],
  "address": {
    "address_line1": "456 New St",
    "address_line2": "Floor 2",
    "city": "Los Angeles",
    "state": "CA",
    "postal_code": "90001",
    "country": "USA",
    "latitude": "34.0522",
    "longitude": "-118.2437"
  }
}
```

**File Uploads** (use form-data):
- `logo`: Image file (jpg, jpeg, png, max 2MB)
- `banner`: Image file (jpg, jpeg, png, max 5MB)

**Expected Response** (200):
```json
{
  "success": true,
  "message": "Store updated successfully.",
  "data": {
    "id": "uuid",
    "name": "Updated Store Name",
    ...
  }
}
```

**Error Responses**:
- `403`: Not authorized (not owner or store is active)
- `400`: Cannot update approved store
- `500`: Server error

---

### 4. **Update Verification Document Endpoint**
- **URL**: `POST /api/v1/stores/{storeId}/verification-document` (unchanged)
- **Route Name**: `stores.updateVerificationDocument`
- **Authorization**: Now uses StorePolicy

**Request Body** (form-data):
```
document_type: commercial_register | tax_card | id_card_front | id_card_back
document: [file] (PDF/Image, max 5MB)
```

**Expected Response** (200):
```json
{
  "success": true,
  "message": "Verification document updated successfully. It will be reviewed by our team.",
  "data": {
    "id": "uuid",
    "store_id": "uuid",
    "document_type": "commercial_register",
    "document_path": "stores/{storeId}/verifications/commercial_register/file.pdf",
    "status": "pending",
    "created_at": "2026-03-06T...",
    "updated_at": "2026-03-06T..."
  }
}
```

**File Storage Path**:
- Old: `verification_documents/{file}`
- New: `stores/{storeId}/verifications/{documentType}/{file}`

---

## Response Structure Changes

### Old Response Format:
```json
{
  "message": "Success message",
  "data": {...}
}
```

### New Response Format:
```json
{
  "success": true,
  "message": "Success message",
  "data": {...}
}
```

### Error Response Format:
```json
{
  "success": false,
  "message": "Error message",
  "errors": {...} // Optional validation errors
}
```

---

## Authorization Changes

All store endpoints now use Laravel Policy (`StorePolicy`) instead of manual checks:

- **View Store**: Owner or Admin
- **Update Store**: Owner only, not for active stores
- **Update Verification Documents**: Owner only, not for active stores
- **Delete Store**: Owner or Admin

---

## File Storage Structure

All store files now organized by `storeId`:

```
storage/app/public/stores/{storeId}/
├── logo/
│   └── [logo files]
├── banner/
│   └── [banner files]
└── verifications/
    ├── commercial_register/
    ├── tax_card/
    ├── id_card_front/
    └── id_card_back/
```

**Public URLs**:
- Logo: `http://domain.com/storage/stores/{storeId}/logo/filename.jpg`
- Banner: `http://domain.com/storage/stores/{storeId}/banner/filename.jpg`
- Verification: `http://domain.com/storage/stores/{storeId}/verifications/{type}/filename.pdf`

---

## Testing Notes

### Environment Variables
Make sure to set in Postman:
- `baseUrl`: `http://127.0.0.1:8000` or your server URL
- `token`: Auto-set after login

### Test Sequence
1. **Login** as seller → Token auto-saved
2. **Get Store Categories** → Get category IDs
3. **Create Store** → Save store ID from response
4. **Get My Stores** → Verify store appears
5. **Update Store** → Test partial updates
6. **Update Verification Document** → Test document upload

### Common Issues
- **403 Forbidden**: Check if user owns the store
- **400 Bad Request**: Cannot update active stores
- **422 Validation Error**: Check required fields and file formats
- **500 Server Error**: Check logs for details

---

## Migration Checklist

- [x] Update Create Store endpoint URL
- [x] Update Get My Stores endpoint URL and name
- [x] Update response structure expectations
- [x] Update file upload field names
- [x] Update file storage paths
- [x] Add success boolean to responses
- [x] Update error response handling
- [x] Test all endpoints with new structure

---

## Backward Compatibility

### Breaking Changes
- ❌ Route URLs changed (old routes will 404)
- ❌ Response structure includes `success` field
- ❌ File storage paths changed
- ❌ Request field names changed (`logo_url` → `logo`, `banner_url` → `banner`)

### Migration Path
1. Update all API clients to use new endpoints
2. Update response parsing to handle `success` field
3. Update file upload field names
4. Test thoroughly before deploying

---

## Additional Resources

- See `REFACTORING_SUMMARY.md` for complete refactoring details
- See `StorePolicy.php` for authorization rules
- See `StoreResource.php` for response transformation
- See `CreateStoreRequest.php` and `UpdateStoreRequest.php` for validation rules
