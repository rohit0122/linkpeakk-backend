# FRONTEND MIGRATION GUIDE: One-Time Payment System

This guide outlines the changes required in the frontend application to support the new **Razorpay One-Time Payment Links** system.

## 🚨 Critical Changes (Summary)

1.  **REMOVE Razorpay Modal**: `Razorpay(options).open()` is no longer used.
2.  **USE Redirect**: Payments now happen on a dedicated Razorpay page via a `payment_url`.
3.  **Login API**: The `POST /login` response now contains the **full subscription object**.
4.  **Status Logic**: The boolean `is_paid` has been **REMOVED**. Use `status` string instead.

---

## 1. New Payment Flow (Step-by-Step)

### Old Way (DEPRECATED ❌)

```javascript
// Don't do this anymore!
const options = {
    key: "rzp_test_...",
    amount: 50000,
    handler: function (response) { ... }
};
const rzp1 = new Razorpay(options);
rzp1.open();
```

### New Way (REQUIRED ✅)

1.  **Call API**: `POST /api/v1/payments/create-link`
    - Body: `{ "plan_id": 2 }`
2.  **Get URL**: Response contains `{ "payment_url": "https://razorpay.com/..." }`
3.  **Redirect**:
    ```javascript
    window.location.href = response.payment_url;
    ```
4.  **Return**: After payment, Razorpay redirects user back to your dashboard (e.g., `/dashboard?payment=success`).
5.  **Verify**: On the dashboard, call **Status API** to refresh state.

---

## 2. API Response Changes

### Subscription Object Structure

This object is returned in `POST /login`, `GET /dashboard/init`, and `GET /payments/status`.

**Key Change:** `is_paid` is GONE. Use `status` to determine access.

```javascript
/* OLD - DEPRECATED */
{
  "is_paid": true, // REMOVED
  "status": "active"
}

/* NEW - CORRECT */
{
  "status": "active",          // Possible values: "active", "trial", "expired", "free"
  "formatted_status": "Active", // "Active", "Trialing", "Expired", "Free Plan"
  "plan_name": "Pro",
  "expiry_date": "2024-12-31...",
  "is_trial": false            // Boolean helper (true if plan > free but no payment ever made)
}
```

### Logic Table

| UI State        | `status` Value | Condition                                                          |
| :-------------- | :------------- | :----------------------------------------------------------------- |
| **Active Plan** | `"active"`     | User has paid and plan is not expired.                             |
| **Trialing**    | `"trial"`      | User has selected a paid plan but never paid (Registration Trial). |
| **Expired**     | `"expired"`    | Plan expiry date has passed.                                       |
| **Free**        | `"free"`       | User is on the Free tier.                                          |

---

## 3. Login Optimization

The `POST /api/v1/auth/login` endpoint now returns the **exact same** `subscription` object as `/dashboard/init`.

**Frontend Action:**

- **Store** the `subscription` object immediately after login.
- **Do NOT** call `/dashboard/init` right after login just to get plan status. It's already there.

---

## 4. New Lightweight Endpoint

If you need to poll for status (e.g., after a payment redirect):

**GET** `/api/v1/payments/status`

**Response:**

```json
{
  "success": true,
  "data": {
    "subscription": {
      "status": "active",
      ...
    }
  }
}
```

Use this instead of reloading the entire dashboard data.
