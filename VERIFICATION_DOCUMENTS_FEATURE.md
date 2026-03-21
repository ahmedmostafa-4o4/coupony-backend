# Verification Documents Approval/Rejection Feature

## Overview
Added functionality for admins to approve or reject individual store verification documents, providing granular control over the store verification process.

## Features

### 1. Document-Level Approval/Rejection
- Admins can now approve or reject each verification document separately
- Each document has its own status: `pending`, `approved`, or `rejected`
- Store is only marked as verified when ALL documents are approved

### 2. New API Endpoints

#### Get Verification Documents
```
GET /api/v1/admin/stores/{store}/verifications
```
Returns all verification documents for a specific store.

#### Approve Verification Document
```
POST /api/v1/admin/stores/{store}/verifications/{verification}/approve
```
Request body:
```json
{
  "notes": "Optional approval notes"
}
```

#### Reject Verification Document
```
POST /api/v1/admin/stores/{store}/verifications/{verification}/reject
```
Request body:
```json
{
  "reason": "Required rejection reason"
}
```

### 3. Automatic Notifications
- Store owners receive notifications when documents are approved/rejected
- Notifications include document type and reason (for rejections)
- Sent via email and in-app notifications

### 4. Store Verification Logic
- Store `is_verified` flag is set to `true` only when all documents are approved
- If any document is rejected, store is marked as not verified
- Admins can see verification status for each document

## Files Created

### Actions
- `app/Domain/Store/Actions/ApproveVerificationDocument.php`
- `app/Domain/Store/Actions/RejectVerificationDocument.php`

### Events
- `app/Domain/Store/Events/VerificationDocumentApproved.php`
- `app/Domain/Store/Events/VerificationDocumentRejected.php`

### Listeners
- `app/Domain/Store/Listeners/NotifyStoreOwnerOnDocumentApproval.php`
- `app/Domain/Store/Listeners/NotifyStoreOwnerOnDocumentRejection.php`

## Files Modified

### Controllers
- `app/Application/Http/Controllers/API/V1/Admin/StoreManagementController.php`
  - Added `verificationDocuments()` method
  - Added `approveDocument()` method
  - Added `rejectDocument()` method

### Routes
- `routes/api.php`
  - Added 3 new routes for verification document management

### Enums
- `app/Domain/Notification/Enums/NotificationTypes.php`
  - Added `STORE_DOCUMENT_APPROVED`
  - Added `STORE_DOCUMENT_REJECTED`

### Event Service Provider
- `app/Providers/EventServiceProvider.php`
  - Registered new event listeners

### Postman Collection
- `postman_collection.json`
  - Added 3 new requests for verification document management

## Usage Example

### 1. Get all verification documents for a store
```bash
GET /api/v1/admin/stores/{store-uuid}/verifications
Authorization: Bearer {admin-token}
```

Response:
```json
{
  "message": "Verification documents retrieved successfully.",
  "data": [
    {
      "id": "verification-uuid-1",
      "store_id": "store-uuid",
      "document_type": "commercial_register",
      "document_path": "/path/to/document.pdf",
      "status": "pending",
      "verified_by": null,
      "verified_at": null,
      "rejection_reason": null
    },
    {
      "id": "verification-uuid-2",
      "store_id": "store-uuid",
      "document_type": "tax_card",
      "document_path": "/path/to/tax_card.pdf",
      "status": "approved",
      "verified_by": "admin-uuid",
      "verified_at": "2026-03-06T10:30:00Z",
      "rejection_reason": null
    }
  ]
}
```

### 2. Approve a document
```bash
POST /api/v1/admin/stores/{store-uuid}/verifications/{verification-uuid}/approve
Authorization: Bearer {admin-token}
Content-Type: application/json

{
  "notes": "Document verified successfully"
}
```

### 3. Reject a document
```bash
POST /api/v1/admin/stores/{store-uuid}/verifications/{verification-uuid}/reject
Authorization: Bearer {admin-token}
Content-Type: application/json

{
  "reason": "Document is not clear or expired"
}
```

## Error Handling

All endpoints include comprehensive error handling:
- Validation errors (422)
- Authorization errors (401/403)
- Not found errors (404)
- Business logic errors (400)
- Server errors (500)

All errors return user-friendly messages without exposing internal details.

## Testing

To test the feature:
1. Create a store with verification documents
2. Login as admin
3. Get the store's verification documents
4. Approve or reject individual documents
5. Check that notifications are sent to store owner
6. Verify that store is marked as verified only when all documents are approved
