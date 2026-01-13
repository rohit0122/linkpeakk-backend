# LinkPeak Subscription API Documentation

This document details the REST API endpoints for the LinkPeak Subscription System. Use this guide to integrate the frontend/UI framework with the backend subscription logic.

## Base URL
`http://your-domain.com/api/v1`

## Authentication
All endpoints (except Webhooks) require a Bearer Token (Sanctum).
Header: `Authorization: Bearer <token>`

---

## Authentication
All endpoints (except Authentication & Webhooks) require a Bearer Token (Sanctum).
Header: `Authorization: Bearer <token>`

## 1. Authentication Endpoints

### 1.1 Register
**Endpoint:** `POST /auth/register`
**Access:** Public

Registers a new user and returns an access token.

#### Request Body
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `name` | String | Yes | Full name of the user. Max 255 chars. |
| `email` | String | Yes | Valid email address. Must be unique. |
| `password` | String | Yes | Min 8 characters. |
| `password_confirmation` | String | Yes | Must match `password`. |

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

#### Response (201 Created)
```json
{
    "success": true,
    "message": "User registered successfully.",
    "data": {
        "user": {
            "name": "John Doe",
            "email": "john@example.com",
            "updated_at": "2026-01-09T10:00:00.000000Z",
            "created_at": "2026-01-09T10:00:00.000000Z",
            "id": 1
        },
        "token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz123456",
        "token_type": "Bearer"
    }
}
```

### 1.2 Login
**Endpoint:** `POST /auth/login`
**Access:** Public

Authenticates a user and returns a new access token.

#### Request Body
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `email` | String | Yes | Registered email address. |
| `password` | String | Yes | User password. |

```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

#### Response (200 OK)
```json
{
    "success": true,
    "message": "Login successful.",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "...": "..."
        },
        "token": "2|AbCdEfGhIjKlMnOpQrStUvWxYz123456",
        "token_type": "Bearer"
    }
}
```

### 1.3 Logout
**Endpoint:** `POST /auth/logout`
**Access:** Authenticated (Bearer Token required)

Revokes the current access token.

#### Request Body
*(Empty)*

#### Response (200 OK)
```json
{
    "success": true,
    "message": "Logged out successfully.",
    "data": []
}
```

### 1.4 Get User Profile
**Endpoint:** `GET /user`
**Access:** Authenticated

Retrieves the currently authenticated user's details.

#### Response (200 OK)
```json
{
    "success": true,
    "message": "User retrieved successfully",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified_at": null,
        "created_at": "2026-01-09T10:00:00.000000Z",
        "updated_at": "2026-01-09T10:00:00.000000Z"
    }
}
```

---

## 2. Subscription System
### 2.1 Subscription Status
**Endpoint:** `GET /subscription/status`

Retrieves the current subscription state of the authenticated user.

### Response (Active Subscription)
```json
{
    "success": true,
    "message": "Subscription status retrieved successfully.",
    "data": {
        "plan": {
            "id": 2,
            "name": "PRO",
            "slug": "pro",
            "price": "9.00",
            "currency": "USD",
            "trial_days": 7
        },
        "subscription": {
            "id": 15,
            "status": "trialing",
            "trial_ends_at": "2026-01-16T10:00:00.000000Z",
            "ends_at": null,
            "cancelled_at": null
        },
        "is_active": true,
        "trial_ends_at": "2026-01-16T10:00:00.000000Z"
    }
}
```

### Response (No Subscription / Inactive)
```json
{
    "success": true,
    "message": "Subscription status retrieved successfully.",
    "data": {
        "plan": null,
        "subscription": null,
        "is_active": false,
        "trial_ends_at": null
    }
}
```

---

### 2.2 Select Plan (Start Trial)
**Endpoint:** `POST /subscription/select`

Starts a new subscription. If the plan has a trial (e.g., PRO, AGENCY), it enters `trialing` state for 7 days. Razorpay billing starts automatically after the trial ends.

**Constraint:** User must **not** have an existing active subscription. If they do, use the Cancel endpoint first or wait for expiry.

#### Request Body
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `plan_slug` | String | Yes | Slug of the plan to select (`pro`, `agency`, `free`). |

```json
{
    "plan_slug": "pro"
}
```

#### Response (Success - Trial Started)
```json
{
    "success": true,
    "message": "Plan selected successfully. Trial started.",
    "data": {
        "subscription": {
            "id": 16,
            "user_id": 5,
            "plan_id": 2,
            "status": "trialing",
            "razorpay_subscription_id": "sub_N7w123456789",
            "trial_ends_at": "2026-01-16T10:05:00.000000Z",
            "current_period_start": "2026-01-09T10:05:00.000000Z",
            "current_period_end": "2026-01-16T10:05:00.000000Z"
        },
        "razorpay_subscription_id": "sub_N7w123456789"
    }
}
```

#### Response (Error - Already Subscribed)
```json
{
    "success": false,
    "message": "User already has an active subscription.",
    "data": []
}
```

---

### 2.3 Cancel Subscription
**Endpoint:** `POST /subscription/cancel`

Cancels the user's current active subscription.
- **Behavior**: The cancellation is scheduled for the **end of the current billing cycle** (managed by Razorpay). The user generally retains access until `ends_at`.
- **Database Update**: Sets `cancelled_at` timestamp.

#### Request Body
*(Empty)*

#### Response
```json
{
    "success": true,
    "message": "Subscription cancelled successfully.",
    "data": {
        "subscription": {
            "id": 16,
            "status": "active",
            "cancelled_at": "2026-01-09T10:10:00.000000Z"
        }
    }
}
```

---

### 2.4 Webhooks
**Endpoint:** `POST /api/webhooks/razorpay`
**Public Endpoint** (No Auth Token required)

Validates `X-Razorpay-Signature` header using the `RAZORPAY_WEBHOOK_SECRET`.

#### Supported Events
| Event | Action Taken |
| :--- | :--- |
| `subscription.authenticated` | Activates subscription (marks as `active` in DB). |
| `subscription.charged` | Logs charge, can be used to update cycle dates. |
| `subscription.cancelled` | Marks subscription as cancelled if done via Razorpay Dashboard. |
| `subscription.halted` | Marks as `expired` or `payment_failed` (implementation dependent). |

---

## 3. Integration Flow for UI

### Step 1: Authentication
1.  **Register/Login**: Use `POST /auth/register` or `POST /auth/login` to get the `token`.
2.  **Store Token**: Save the `token` in `localStorage` or a secure cookie.
3.  **Attach Header**: For all subsequent calls, add `Authorization: Bearer <token>`.

### Step 2: Check Subscription State
1.  **Status**: Call `GET /subscription/status` on dashboard load.
    *   If `is_active: true`, show "Current Plan: [Name]" and "Cancel" button.
    *   If `is_active: false`, show Pricing Cards.

### Step 3: Select Plan / Start Trial
1.  **User Action**: User clicks "Start 7-Day Trial" on PRO plan.
2.  **API Call**: Call `POST /subscription/select` with `plan_slug: "pro"`.
3.  **Checkout**:
    *   This API creates the subscription on the backend.
    *   You *must* integrate Razorpay Checkout on frontend using the returned `razorpay_subscription_id`.
    *   *Note: Typically, after getting `sub_id`, Frontend opens Razorpay Checkout*.

### Step 4: Cancel
1.  **User Action**: User clicks "Cancel Subscription".
2.  **API Call**: Call `POST /subscription/cancel`.
3.  **Feedback**: Show "Cancellation Scheduled" message.

---

## 4. Error Responses
All API endpoints return standard HTTP codes.
- `200`: Success
- `201`: Created
- `400`: Bad Request (Validation or Logic Error)
- `401`: Unauthorized (Invalid Token)
- `403`: Forbidden
- `500`: Server Error

**Error Format:**
```json
{
    "success": false,
    "message": "Validation failed.",

---

## 5. Bio Links (User Page)
**Authentication Required**

### 5.1 List Links
**Endpoint:** `GET /links`

Returns all links for the authenticated user, ordered by `order`.

#### Response
```json
{
    "success": true,
    "message": "Links retrieved successfully.",
    "data": [
        {
            "id": 1,
            "title": "My Portfolio",
            "url": "https://portfolio.com",
            "is_active": 1,
            "order": 0
        }
    ]
}
```

### 5.2 Create Link
**Endpoint:** `POST /links`

> [!NOTE]
> This action is subject to Plan Limits (e.g., Free plan limited to 5 links). Returns 403 if limit reached.

#### Request Body
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `title` | String | Yes | Link text. |
| `url` | String | Yes | Valid URL. |

```json
{
    "title": "My Blog",
    "url": "https://blog.com"
}
```

### 5.3 Update Link
**Endpoint:** `PUT /links/{id}`

Update title, url, active status, or reorder.

#### Request Body
```json
{
    "title": "Updated Blog",
    "is_active": false,
    "order": 2
}
```

### 5.4 Delete Link
**Endpoint:** `DELETE /links/{id}`

---

## 6. Settings
**Authentication Required**

### 6.1 Update Profile
**Endpoint:** `PUT /settings/profile`

Updates user details including Bio and Avatar.

#### Request Body
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `name` | String | No | Update full name. |
| `bio` | String | No | User bio text (display on page). |
| `avatar_url` | String | No | URL to uploaded image. |

```json
{
    "bio": "Digital Creator | Tech Enthusiast",
    "avatar_url": "https://example.com/me.jpg"
}
```

### 6.2 Change Password
**Endpoint:** `PUT /settings/password`

#### Request Body
```json
{
    "current_password": "oldpassword",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
}
```

---

## 7. Support Tickets
**Authentication Required**

### 7.1 Create Ticket
**Endpoint:** `POST /tickets`

#### Request Body
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `subject` | String | Yes | Issue summary. |
| `message` | String | Yes | Detailed description. |
| `priority` | String | No | `low`, `medium`, `high`. Default: `medium`. |

```json
{
    "subject": "Billing Issue",
    "message": "I was charged twice.",
    "priority": "high"
}
```

### 7.2 List Tickets
**Endpoint:** `GET /tickets`

Returns all tickets submitted by the user.

---

---

## Database Schemas Reference

### Plans
| Column | Type | Description |
| :--- | :--- | :--- |
| `name` | String | "FREE", "PRO", "AGENCY" |
| `slug` | String | Unique id (e.g. "pro") |
| `price` | Decimal | Monthly cost |
| `trial_days` | Int | 7 |
| `razorpay_plan_id` | String | ID from Razorpay Dashboard |

### Subscriptions
| Column | Type | Description |
| :--- | :--- | :--- |
| `user_id` | FK | User owner |
| `status` | String | `trialing`, `active`, `cancelled`, `expired` |
| `razorpay_subscription_id` | String | Mapped to Razorpay |
| `trial_ends_at` | Timestamp | End of 7-day trial |
