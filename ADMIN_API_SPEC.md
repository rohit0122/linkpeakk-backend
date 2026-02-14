# Admin API Specification (v1)

This document defines the endpoints for the Admin Dashboard, focusing on one-time payments and user management.

## 1. Dashboard Overview

- **Endpoint:** `GET /api/v1/admin/stats`
- **Purpose:** Fetch advanced system metrics, business intelligence, and chart data.

### Success Response

```json
{
  "success": true,
  "data": {
    "metrics": {
      "total_users": 1500,
      "paid_users": 450,
      "revenue_30d": 15000.00,
      "conversion_rate": 30.0,
      "total_views": 45000,
      "total_links": 3200,
      "expiring_soon": 15 // Users expiring in < 7 days
    },
    "revenue_breakdown": [
       { "currency": "USD", "total": "5500.00" },
       { "currency": "USD", "total": "200.00" }
    ],
    "top_pages": [
       { "id": 1, "slug": "pro-user", "title": "My LinkPeak", "views": 1200, "user": { "name": "..." } }
    ],
    "charts": {
      "revenue_growth": [...],
      "user_growth": [...],
      "plan_distribution": [...],
      "user_distribution": [...]
    }
  }
}
```

---

## 2. User & Content Management

### List Users (Advanced)

- **Endpoint:** `GET /api/v1/admin/users`
- **Query Params:**
  - `page`: Page number (default: 1)
  - `search`: Filter by name or email
  - `plan_id`: Filter by specific plan ID
  - `role`: Filter by role ('user', 'admin', 'agency')
- **Purpose:** Paginated list of users with multi-filter support.

#### Response Structure (Standard Pagination)

```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "user",
        "plan_id": 2,
        "plan_expires_at": "2024-12-31T23:59:59Z",
        "plan": { "id": 2, "name": "Pro" }
      }
    ],
    "first_page_url": "...",
    "from": 1,
    "last_page": 10,
    "last_page_url": "...",
    "links": [...],
    "next_page_url": "...",
    "path": "...",
    "per_page": 20,
    "prev_page_url": null,
    "to": 20,
    "total": 200
  }
}
```

### Get User Details

- **Endpoint:** `GET /api/v1/admin/users/{id}`
- **Purpose:** Full profile, BioPages, and Payment History.

#### Success Response

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "plan": { "id": 2, "name": "Pro" },
    "pending_plan": null,
    "bio_pages": [...],
    "payments": [
      {
        "id": 10,
        "amount": "499.00",
        "status": "captured",
        "plan": { "name": "Pro" }
      }
    ]
  }
}
```

### Suspend / Activate User

- **Endpoint:** `POST /api/v1/admin/user/suspend`
- **Body:**

```json
{
  "userId": 1,
  "suspend": true // true to deactivate, false to activate
}
```

#### Success Response

```json
{
  "success": true,
  "message": "User suspended successfully"
}
```

---

## 3. Subscription & Plan Control

### Manual Plan Override

- **Endpoint:** `POST /api/v1/admin/users/{id}/override-plan`
- **Body:**

```json
{
  "plan_id": 2,
  "expiry_date": "2025-12-31" // ISO 8601, Optional
}
```

#### Success Response

```json
{
  "success": true,
  "message": "User plan overridden to Pro until 2025-12-31 00:00:00",
  "data": {
    "id": 1,
    "plan_id": 2,
    "plan_expires_at": "2025-12-31T00:00:00Z"
  }
}
```

---

## 4. Monitoring & Transaction Logs

### List Payments (Filtered)

- **Endpoint:** `GET /api/v1/admin/payments`
- **Query Params:**
  - `page`: Page number
  - `status`: 'captured', 'created', 'failed'
  - `user_id`: Filter by specific user
- **Purpose:** Detailed transaction audit trail.

### Webhook Monitoring

- **Endpoint:** `GET /api/v1/admin/webhook-logs`
- **Query Params:**
  - `page`: Page number
  - `status`: 'processed', 'failed', 'pending'
- **Purpose:** Monitor Razorpay fulfillment stability and debug issues.

---

## 5. Plan Configuration

### List Plans

- **Endpoint:** `GET /api/v1/admin/plans`

### Create Plan

- **Endpoint:** `POST /api/v1/admin/plans`
- **Body:**

```json
{
  "name": "New Plan",
  "slug": "new-plan",
  "price": 299,
  "currency": "USD",
  "trial_days": 7,
  "is_active": true,
  "features": { "links": 10, "themes": ["standard"] }
}
```

### Update Plan

- **Endpoint:** `PUT /api/v1/admin/plans/{id}`
- **Body:** Same as Create (all fields optional)
- **Note:** `trial_days` controls the free trial duration for new registrations (0-365 days).

### Delete Plan

- **Endpoint:** `DELETE /api/v1/admin/plans/{id}`
- **Constraint:** Cannot delete if users are currently assigned.

---

## 6. Support & Community

### List Support Tickets

- **Endpoint:** `GET /api/v1/admin/tickets`
- **Purpose:** Manage user support requests.

### Update Ticket Status

- **Endpoint:** `PUT /api/v1/admin/tickets/{id}`
- **Body:** `{ "status": "resolved" }`

### Newsletter Subscribers

- **Endpoint:** `GET /api/v1/admin/newsletter/subscribers`
- **Purpose:** List and manage newsletter signups.
