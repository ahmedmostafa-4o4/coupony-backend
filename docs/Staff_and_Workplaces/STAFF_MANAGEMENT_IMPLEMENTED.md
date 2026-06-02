# Backend Implementation Status — Staff Management Feature

This document describes the actual implemented backend behavior for the Staff Management feature, matching up with the questions from `BACKEND_REQUIRED (1).md`.

## 1. Implemented Endpoints

The following endpoints are fully functional and available in the API:

### Store Employees
- `GET /api/v1/stores/{store}/employees`: List all employees of a store.
- `GET /api/v1/stores/{store}/employees/{user}`: Get details of a specific employee.
- `PATCH /api/v1/stores/{store}/employees/{user}`: Update role/permissions/address of an employee.
- `DELETE /api/v1/stores/{store}/employees/{user}`: Remove an employee from a store.
- `GET /api/v1/store-employee-permissions`: List all available store permissions (grouped).

### Store Invitations (For Store Owners)
- `GET /api/v1/stores/{store}/invitations`: List all invitations for a store.
- `POST /api/v1/stores/{store}/invitations`: Send a new invitation to a user.
- `POST /api/v1/stores/{store}/invitations/{invitation}/resend`: Resend an invitation.
- `DELETE /api/v1/stores/{store}/invitations/{invitation}`: Cancel an invitation.

### Invitations (For Invitees)
- `GET /api/v1/me/invitations`: List all invitations sent to the currently authenticated user.
- `POST /api/v1/invitations/{invitation}/accept`: Accept a specific invitation.
- `POST /api/v1/invitations/{invitation}/decline`: Decline a specific invitation.

---

## 2. Responses & Schema Details

There are some minor differences between the initial frontend expectations and the current backend schema. The frontend models must be adjusted to parse the following actual responses.

### `GET /stores/{store}/employees`
**Differences from requirements:**
- `user.name` is returned as `user.full_name`.
- `user.phone` is returned as `user.phone_number`.
- `status` is **not returned**. Any employee returned in this array is considered active.
- `address.name` is not returned. You can use `address.label` or `address.first_name` from the `AddressResource`.
- `joined_at` is returned as `created_at` (matches fallback logic).

### `GET /stores/{store}/invitations`
**Differences from requirements:**
- `email` is not at the root. It is nested inside the `invitee` object (`invitee.email`).
- `permissions` is returned as a JSON array.

### `POST /stores/{store}/invitations`
- Supports `address_id: null` and `message: null` successfully.

### `GET /me/invitations`
- Includes the `store` object so the invitee can see store details like `store.name` and `store.logo`.

---

## 3. Clarifications to Frontend Questions

- **Permissions Validation:** Option B is implemented. The backend strictly validates `permissions` against a predefined list (`StorePermission` Enum). Unrecognized permissions will trigger a `422 Unprocessable Entity` error.
- **Accept/Decline Flow:** Option C is implemented. The user uses the mobile app to accept or decline the invitation from their pending invitations list via the `POST /invitations/{invitation}/accept` API.
- **Push Notification on Invite:** When the invite is first sent, an **Email** is dispatched. When "resend" is clicked, an **In-App** notification is sent. Direct `fcm` push messages are not sent unless handled by a separate Notification channel listener.
- **Auth Required for Email Link:** Yes. Because it uses Option C, the `/invitations/{id}/accept` endpoint operates under `auth:sanctum` middleware, so the user must be authenticated.
