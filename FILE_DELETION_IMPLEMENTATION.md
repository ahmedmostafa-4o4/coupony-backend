# File Storage Structure

## Overview
All store files are now organized by storeId for better organization and easier management.

## Storage Structure
```
storage/app/public/stores/{storeId}/
├── logo/
│   └── [logo files]
├── banner/
│   └── [banner files]
└── verifications/
    ├── commercial_register/
    │   └── [document files]
    ├── tax_card/
    │   └── [document files]
    ├── id_card_front/
    │   └── [document files]
    └── id_card_back/
        └── [document files]
```

## Changes Made

### 1. StoreData DTO (`app/Domain/Store/DTOs/StoreData.php`)
- **fromRequest()**: Removed file upload logic (now handled in controller after store creation)
- **fromUpdateRequest()**: Updated to accept `$storeId` parameter and use it in file paths

### 2. StoreController (`app/Application/Http/Controllers/API/V1/StoreController.php`)
- **create()**: 
  - Creates store first to get storeId
  - Uploads files to `stores/{storeId}/logo` and `stores/{storeId}/banner`
  - Uploads verification docs to `stores/{storeId}/verifications/{docType}`
  - Updates store and verification records with file paths
  
- **update()**:
  - Passes storeId to `StoreData::fromUpdateRequest()`
  - Files uploaded to same structure as create
  
- **updateVerificationDocument()**:
  - Uploads to `stores/{storeId}/verifications/{docType}`
  - Old files automatically deleted by UpdateVerificationDocument action

### 3. UpdateStore Action (`app/Domain/Store/Actions/UpdateStore.php`)
- Deletes old logo from storage before updating
- Deletes old banner from storage before updating

### 4. UpdateVerificationDocument Action (`app/Domain/Store/Actions/UpdateVerificationDocument.php`)
- Deletes old verification document from storage before updating

## File Upload Flow

### Create Store:
1. User submits form with files
2. Store created in database (gets UUID)
3. Files uploaded to `stores/{storeId}/...`
4. Database updated with file paths

### Update Store Logo/Banner:
1. User submits update with new files
2. Files uploaded to `stores/{storeId}/logo` or `/banner`
3. UpdateStore action deletes old files
4. Database updated with new paths

### Update Verification Document:
1. User submits new document
2. File uploaded to `stores/{storeId}/verifications/{docType}`
3. UpdateVerificationDocument action deletes old file
4. Database updated with new path and status reset to 'pending'

## Safety Features
- Files organized by storeId (easy to find and manage)
- Old files deleted automatically on update
- Storage existence checked before deletion
- All operations use Laravel's Storage facade
- Database transactions ensure data integrity

## API Endpoints
- `POST /api/v1/store/create` - Create store with files
- `PUT /api/v1/stores/{store}` - Update store (handles logo/banner)
- `POST /api/v1/stores/{store}/verification-document` - Update verification document
- `GET /api/v1/stores/my-stores` - Get user's stores

## Notes
- Run `php artisan storage:link` to create public symlink
- Files accessible at `http://domain.com/storage/stores/{storeId}/...`
- Old file structure using userId/storeSlug is deprecated
- All new uploads use storeId-based structure
