# One-Time Payment API Contract

This document outlines the **exact** API endpoints, request payloads, and response structures for the new One-Time Payment system (Link-Based Flow).

## 1. Get User & Plan Status

- **Endpoint:** `GET /api/v1/dashboard/init` (or `/api/v1/auth/me`)
- **Purpose:** Fetch current plan, expiry, and payment status to display UI.

### Response Structure

```json
{
  "success": true,
  "message": "Dashboard data retrieved successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "avatar_url": "...",
      "role": "user" // 'user', 'admin', 'agency'
    },
    // CRITICAL OBJECT FOR FRONTEND LOGIC
    "subscription": {
      "status": "active", // "active", "expired", "free", "trial"
      "formatted_status": "Active", // "Active", "Expired", "Free Plan", "Trialing"
      "plan_name": "Pro", // "Free", "Pro", "Agency"
      "expiry_date": "2024-12-31T23:59:59Z", // ISO 8601 or "Never"
      "is_trial": false, // True if user has plan but never paid (Registration Trial)
      "prefill": {
        "name": "John Doe",
        "email": "john@example.com"
      },
      "pending_plan": null // If a downgrade is scheduled (e.g. { "name": "Free", "slug": "free" })
    }
  }
}
```

---

## 2. Login (Primary Entry)

**Endpoint:** `POST /api/v1/auth/login`
**Purpose:** Authenticate user and **immediately** receive dashboard/subscription state (No need to call `/dashboard/init` separately).

### Response Structure

```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "token": "13|laravel_sanctum_token...", // Bearer Token
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "user"
    },
    // INCLUDES FULL SUBSCRIPTION OBJECT
    "subscription": {
        "status": "active",
        "formatted_status": "Active",
        "plan_name": "Pro",
        "expiry_date": "2024-12-31T23:59:59Z"
    },
    "bio_page": { ... } // Users bio page details
  }
}
```

---

## 4. List Available Plans

- **Endpoint:** `GET /api/v1/admin/plans` (or public equivalent if implemented)
- **Note:** Typically used to populate pricing tables dynamically.

### Structure (Standard Plan Object)

```json
[
  {
    "id": 1,
    "name": "Pro User",
    "slug": "pro",
    "price": 499,
    "currency": "INR",
    "features": [...]
  },
  {
    "id": 2,
    "name": "Agency",
    "slug": "agency",
    "price": 999,
    "currency": "INR",
    "features": [...]
  }
]
```

---

## 5. Create Payment Link (Primary Action)

- **Endpoint:** `POST /api/v1/payments/create-link`
- **Purpose:** Generate a Razorpay Payment Link for a specific plan.

### Request Body

```json
{
  "plan_id": 2 // ID of the plan user wants to buy
}
```

### Response (Success)

```json
{
  "payment_url": "https://razorpay.com/payment-link/plink_xyz123"
}
```

### Frontend Action (Step-by-Step)

1. Call `create-link` with `plan_id`.
2. Receive `payment_url`.
3. **Redirect** user's browser window to this URL.
4. User completes payment on Razorpay.
5. Razorpay redirects user back to: `YOUR_APP_URL/dashboard?payment=success`.
6. Frontend calls `/api/v1/dashboard/init` to refresh state (backend processes webhook async).

---

## 6. Payment Verification (Optional/Background)

- **Endpoint:** `POST /api/v1/payments/verify`
- **Purpose:** Verify signature if handling payment client-side (mostly for debug or custom flows). The primary fulfillment happens via **Webhooks**.

---

## Scenarios & Business Logic

### Scenario A: Free User Upgrades to Pro

1. **Action:** Call `create-link` with Pro Plan ID.
2. **Outcome:** User pays. Backend sets `plan_id` to Pro, `expiry` to `NOW + 30 Days`.

### Scenario B: Pro User Renews (Active)

1. **Context:** User has 5 days left.
2. **Action:** User pays for Pro again.
3. **Outcome:** Backend sets `expiry` to `Current Expiry + 30 Days`. (Result: 35 days total).

### Scenario C: Pro User Upgrades to Agency (Active)

1. **Context:** User has 10 days left on Pro.
2. **Action:** User pays for Agency.
3. **Outcome:** Backend sets `plan_id` to Agency. `expiry` becomes `Current Expiry + 30 Days`. (Immediate upgrade + 40 days total).

### Scenario D: Pro User Expires

1. **Context:** Plan expires naturally.
2. **Action:** Cron job runs daily.
3. **Outcome:** Backend moves user to `Free Plan`. `expiry` becomes `null`. `subscription.status` becomes "expired" -> "free".
