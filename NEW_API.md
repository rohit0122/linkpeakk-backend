# LinkPeakk API Documentation (v1)

Welcome to the LinkPeakk API documentation. All responses follow a standardized JSON format.

### Standard Response Format

```json
{
    "success": true,
    "message": "Action completed successfully",
    "data": { ... }
}
```

_Note: Errors return an appropriate 4xx/5xx status code with `success: false`._

### Response Formats

#### Dates

All timestamps (e.g., `created_at`, `updated_at`, `expiry_date`) are returned in a human-readable format:
`Jan 09, 2026, 05:28PM` (Month Day, Year, Hour:Minute:AM/PM)

#### Image URLs

All image fields (e.g., `avatar_url`, `profile_image`) return **Absolute URLs** pointing to the backend storage.

- **Example:** `http://localhost:8000/storage/avatars/abc123.webp`
- **Configuration:** Uses `LARAVEL_BACKEND_URL` from the environment.

---

## 1. Authentication APIs

**Base Path:** `/api/v1/auth`

### Register

`POST /register`

- **Fields:**
  - `name`: string (required)
  - `email`: string (required, unique)
  - `password`: string (required, min 8)
  - `password_confirmation`: string (required)
- **Response:** User object + `token`.

### Login

`POST /login`

- **Fields:**
  - `email`: string (required)
  - `password`: string (required)
- **Response:** Successfully authenticated user object, `token`, and the **Full Dashboard Initial Data** (see [Dashboard Init](#dashboard-init) section for payload details).
  - **Note:** This eliminates the need for a separate `/dashboard/init` call immediately after login.

### Verify Email

`POST /verify`

- **Fields:**
  - `token`: string (required, from email)

### Forgot Password

`POST /forgot-password`

- **Fields:**
  - `email`: string (required)

### Reset Password

`POST /reset-password`

- **Fields:**
  - `token`: string (required)
  - `email`: string (required)
  - `password`: string (required, min 8)
  - `password_confirmation`: string (required)

### Logout

`POST /logout` (Auth Required)

---

## 2. Public APIs

**Base Path:** `/api/v1/public`

### Fetch Bio Page

`GET /pages/{slug}`

- **Response:**
  - `page`: { id, title, bio, template, theme, profileImage, seo, socialLinks, views, branding, is_sensitive, show_branding, user, links }

### Submit Lead (Contact Form)

`POST /leads`

- **Fields:**
  - `bio_page_id`: integer (required)
  - `name`: string (optional)
  - `email`: string (required)
  - `message`: string (optional)
  - `metadata`: json (optional)

### QR Code Generation

`GET /pages/{id}/qrcode`

- **Response:** SVG Image (Content-Type: image/svg+xml)

### Newsletter Subscription

`POST /newsletter/subscribe`

- **Fields:** `email`: string (required)
- **Response:** Success message.

---

## 3. Tracking APIs

**Base Path:** `/api/v1/track`

### Track View

`POST /view`

- **Fields:** `pageId` (required)

### Track Click

`POST /click`

- **Fields:** `pageId` (required), `linkId` (required)

### Track Like

`POST /like`

- **Fields:** `pageId` (required)

---

## 4. User Dashboard APIs (Auth Required)

**Base Path:** `/api/v1`

### Dashboard Init

`GET /dashboard/init`

- **Response:** Aggregated data for the dashboard (User, active subscription, bio pages summary).
  - **Note:** Bio pages include `total_views` and `total_active_links`.

### Bio Page Management

- `GET /pages`: List all user bio pages.
- `POST /pages`: Create a page (`slug`, `title`, `bio`, `template`, `theme`).
- `PUT /pages/{id}`: Update page.
  - **New Feature:** Supports `profile_image_file` for automatic WebP optimization.
  - **Fields:** `title`, `bio`, `slug`, `template`, `theme`, `is_sensitive`, `show_branding`, `seo` (json), `social_links` (json), `branding` (json).
- `DELETE /pages/{id}`: Delete page.

### Links Management

- `GET /links?pageId=X`: List links for a page.
- `POST /links`: Create link (`pageId`, `title`, `url`, `icon`).
  - **Response:** Returns **array of all links** for the page (for easy UI sync).
- `PUT /links/{id}`: Update link (`title`, `url`, `icon`, `is_active`, `order`).
  - **Response:** Returns **array of all links** for the page.
- `PUT /links/bulk-reorder`: Reorder multiple links.
  - **Fields:** `links`: `[{id, order}, ...]`
- `DELETE /links/{id}`: Delete link.

#### **Bio Link Object Fields**

| Field           | Type    | Description              |
| :-------------- | :------ | :----------------------- |
| `id`            | int     | Unique ID                |
| `user_id`       | int     | Owner User ID            |
| `bio_page_id`   | int     | Parent Bio Page ID       |
| `title`         | string  | Link title               |
| `url`           | string  | Target URL               |
| `icon`          | string  | Emoji or icon identifier |
| `is_active`     | boolean | Toggle status            |
| `order`         | int     | Display order            |
| `clicks`        | int     | Total clicks             |
| `unique_clicks` | int     | Unique clicks            |
| `created_at`    | string  | `Jan 09, 2026, 03:39PM`  |
| `updated_at`    | string  | `Jan 11, 2026, 10:46AM`  |

### Analytics

- `GET /analytics?pageId=X`: Lifetime stats cards.
  - **Response:** `totalViews`, `uniqueViews`, `totalClicks`, `uniqueClicks`, `totalLikes`, `totalActiveLinks`, `avgCTR`.
- `GET /analytics/charts?pageId=X&range=7|15|30|90|180|9999`: Chart data.
  - **Range Values:** `7` (7D), `15` (15D), `30` (1M), `90` (3M), `180` (6M), `9999` (ALL)
  - **Access Control:** Free (7 only), Pro (up to 90), Agency (All).
  - **Response:**
    - `summaryChart`: Views vs Clicks over time (Daily/Weekly/Monthly).
    - `detailedLinksChart`: Per-link performance breakdown.

### Leads

- `GET /pages/{id}/leads`: Fetch captured leads for your bio page.

### Support Tickets

- `GET /tickets`: List your support tickets.
- `POST /tickets`: Create a new ticket.
  - **Fields:** `subject`, `message`, `category` (string, e.g., "Technical"), `priority` (low, medium, high - case insensitive).
- `GET /tickets/{id}`: View ticket details.
- `PUT /tickets/{id}`: Update ticket status (`status`: open/closed) or priority.
- `DELETE /tickets/{id}`: Delete a ticket.

#### **Ticket Object Fields**

| Field        | Type   | Description                                    |
| :----------- | :----- | :--------------------------------------------- |
| `id`         | int    | Unique ID                                      |
| `subject`    | string | Ticket title                                   |
| `message`    | string | Ticket description                             |
| `status`     | string | `open`, `pending`, `resolved`, `closed`        |
| `priority`   | string | `low`, `medium`, `high`                        |
| `category`   | string | Ticket category (e.g., "Technical", "Billing") |
| `created_at` | string | `Jan 11, 2026, 03:30PM`                        |
| `user`       | object | _(Admin only)_ `{ id, name, email }`           |

### AI Helpers

- `POST /ai/generate-link-title`: Generate AI-powered title suggestions for a URL.
  - **Request:** `{ "url": "https://github.com/username" }`
  - **Response:** `{ "brand": "GitHub", "suggestions": ["Title 1", "Title 2", "Title 3"] }`
  - **Fallback:** Uses Mistral AI → Gemini AI → Smart Mock (always returns results)
- `POST /ai/generate-seo`: Generate AI-powered SEO metadata (title, description, keywords) for a bio page.
  - **Request:** `{ "title": "My Portfolio", "bio": "Creative designer from NYC", "slug": "creative-jane" }`
  - **Response:** `{ "title": "...", "description": "...", "keywords": [...] }`
  - **Fallback:** Uses Mistral AI → Gemini AI → Smart Mock

### Settings

- `PUT /settings/profile`: Update name, email, bio.
  - **New Feature:** Supports `avatar_file` for automatic WebP optimization.
- `PUT /settings/password`: Change password (`current_password`, `new_password`).

---

## 5. Admin APIs (Auth Required - Admin Role)

**Base Path:** `/api/v1/admin`

### Global Stats

`GET /stats`

- **Response:** `totalUsers`, `totalViews`, `mrr`, `planDistribution`, `userGrowth`, `revenueGrowth`.

### User Management

`GET /users`

- **Response:** Paginated list of all users.

### User Suspension

`POST /user/suspend`

- **Fields:** `userId`, `suspend` (boolean).

### Support Ticket Management (Admin)

- `GET /admin/tickets`: List ALL support tickets from all users.
- `GET /admin/tickets/{id}`: View any ticket details (includes user info).
- `PUT /admin/tickets/{id}`: Update status, priority, or category.
- `DELETE /admin/tickets/{id}`: Delete any ticket.

### Subscription Plan Management (Admin)

- `GET /admin/plans`: List all subscription plans.
- `POST /admin/plans`: Create a new plan.
  - **Fields:** `name`, `slug` (optional), `razorpay_plan_id` (optional), `price`, `currency`, `billing_interval` (month/year), `trial_days`, `is_active`, `features` (json/array).
- `GET /admin/plans/{id}`: View specific plan details.
- `PUT /admin/plans/{id}`: Update plan details or features.
- `DELETE /admin/plans/{id}`: Delete a plan (prevented if plan has active users).

### Newsletter Management (Admin)

- `GET /admin/newsletter/subscribers`: List all newsletter subscribers.
- `DELETE /admin/newsletter/subscribers/{id}`: Remove a subscriber.

#### **Newsletter Subscriber Object Fields**

| Field        | Type   | Description             |
| :----------- | :----- | :---------------------- |
| `id`         | int    | Unique ID               |
| `email`      | string | Subscriber email        |
| `created_at` | string | `Jan 16, 2026, 03:30PM` |
| `updated_at` | string | `Jan 16, 2026, 03:30PM` |

### Subscription Management (Admin)

#### List All Subscriptions

`GET /admin/subscriptions`

- **Response:** Paginated list of all subscriptions with user and plan details.
- **Includes:** `user` (id, name, email), `plan` (name, price, billing_interval), subscription status, trial dates, period dates.

#### Sync Subscriptions with Razorpay

`POST /admin/subscriptions/sync`

- **Description:** Manually trigger synchronization of all local subscriptions with Razorpay.
- **Request Body:** None required (empty body).
- **Response:**
  ```json
  {
    "success": true,
    "message": "Subscriptions synchronized with Razorpay successfully",
    "data": {
      "total": 10,
      "synced": 9,
      "failed": 1,
      "errors": ["Sub ID sub_xxx: Subscription not found in Razorpay"]
    }
  }
  ```
- **Use Case:** Sync old data or missed webhook events.

---

## 6. Razorpay Webhook

**Endpoint:** `POST /api/v1/payment/callback`

- **Description:** Receives webhook events from Razorpay for subscription lifecycle management.
- **Authentication:** Verified using `X-Razorpay-Signature` header and webhook secret.
- **Supported Events:**
  - `subscription.authenticated` - Initial payment authorized
  - `subscription.activated` - Subscription activated
  - `subscription.charged` - Recurring payment successful
  - `subscription.cancelled` - Subscription cancelled
  - `subscription.halted` - Subscription halted due to payment failure
  - `subscription.expired` - Subscription expired
- **Idempotency:** Uses `event_id` to prevent duplicate processing.
- **Logging:** All events logged in `webhook_logs` table.

**Webhook URL Configuration:**
Set this URL in your Razorpay Dashboard:

```
https://your-domain.com/api/v1/payment/callback
```

**Environment Variables Required:**

- `RAZORPAY_KEY_ID`
- `RAZORPAY_KEY_SECRET`
- `RAZORPAY_WEBHOOK_SECRET`

---

## 7. Automated Trial Expiry System

The system automatically manages trial periods with the following features:

### Daily Scheduled Commands

These commands run automatically every day via Laravel scheduler:

#### 1. Check Trial Expiry

**Command:** `php artisan subscriptions:check-trial-expiry`

- Checks all active users for expired trials
- Suspends accounts with expired trials (no active paid subscription)
- Sends `TrialExpiredNotification` email
- Updates user status: `is_active = false`, sets `suspended_at` timestamp

#### 2. Send Trial Expiry Warnings

**Command:** `php artisan subscriptions:send-trial-warnings`

- Finds trials expiring in exactly 3 days
- Sends `TrialExpiringNotification` email with upgrade CTA
- Only sends to active users

### Email Notifications

#### Trial Expiring (3 days before)

- Subject: "Your Trial Expires Soon"
- Includes: Trial end date, plan name, upgrade link
- Sent to: Users with trials expiring in 3 days

#### Trial Expired (on expiry)

- Subject: "Your Trial Has Expired - Action Required"
- Includes: Account suspension notice, upgrade link, support contact
- Sent to: Users whose trials have expired and accounts are suspended

### Login-Time Check

When users log in, the system:

1. Checks if their trial has expired
2. Suspends the account if needed
3. Sends expiry notification email
4. Returns 403 error with suspension message

### Production Setup

Add this to your server's crontab to enable the scheduler:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 8. Subscription Plan Object Fields

| Field              | Type    | Description                                      |
| :----------------- | :------ | :----------------------------------------------- |
| `id`               | int     | Unique ID                                        |
| `name`             | string  | Plan display name (e.g., "PRO")                  |
| `slug`             | string  | URL-friendly identifier                          |
| `razorpay_plan_id` | string  | Razorpay dashboard plan ID                       |
| `price`            | decimal | Subscription price                               |
| `currency`         | string  | e.g., "USD", "INR"                               |
| `billing_interval` | string  | `month`, `year`                                  |
| `trial_days`       | int     | Number of free trial days                        |
| `is_active`        | boolean | Visibility status                                |
| `features`         | json    | Structured limits (links, pages, templates, etc) |
| `created_at`       | string  | `Jan 16, 2026, 03:30PM`                          |
