Perfect 👍 Now we have both:

* The **correct field spec** from the body docs, and
* The **correct JSON structure** with example values.

Here’s a clean **README.md** you can use directly in Cursor as context for your **Messaging Profile CRUD with validation**:

---

# Messaging Profile (Telnyx)

## Overview

A **Messaging Profile** configures how SMS/MMS traffic is handled within Telnyx.
This object supports destinations, webhooks, number pooling, URL shorteners, spending limits, and more.

This module defines:

* **Validation rules** for each field
* **CRUD operations** against Telnyx API
* **Example JSON structure** for reference

---

## Validation Rules

### Required

* **`name`** → `string|required`

  * User-friendly profile name.

* **`whitelisted_destinations`** → `array|required`

  * Each element must be a valid ISO 3166-1 alpha-2 country code (`^[A-Z]{2}$`)
  * Or use `["*"]` to allow all destinations.

---

### Optional / Defaults

* **`enabled`** → `boolean|default:true`

* **`webhook_url`** → `url|nullable`

* **`webhook_failover_url`** → `url|nullable`

* **`webhook_api_version`** → `string|in:1,2,2010-04-01|default:2`

---

### `number_pool_settings` (object, nullable)

Validation inside object:

* `toll_free_weight` → `numeric|min:0|required`
* `long_code_weight` → `numeric|min:0|required`
* `skip_unhealthy` → `boolean|required`
* `sticky_sender` → `boolean`
* `geomatch` → `boolean`

Set `null` to disable.

---

### `url_shortener_settings` (object, nullable)

Validation inside object:

* `domain` → `string|required`
* `prefix` → `string|nullable`
* `replace_blacklist_only` → `boolean`
* `send_webhooks` → `boolean`

Set `null` to disable.

---

### Additional Optional

* **`alpha_sender`** → `string|nullable|regex:/^[A-Za-z0-9 ]{1,11}$/`

* **`daily_spend_limit`** → `string|regex:/^[0-9]+(?:\.[0-9]+)?$/`

* **`daily_spend_limit_enabled`** → `boolean`

* **`mms_fall_back_to_sms`** → `boolean`

* **`mms_transcoding`** → `boolean`

---

## Example JSON Response

```json
{
  "data": {
    "record_type": "messaging_profile",
    "id": "400198eb-b746-43f8-9a6e-e54a4a8ad29f",
    "organization_id": "83612ed7-9361-485c-bea9-29186c09df43",
    "name": "My name",
    "webhook_url": "https://www.example.com/hooks",
    "webhook_failover_url": "https://backup.example.com/hooks",
    "enabled": true,
    "whitelisted_destinations": ["US"],
    "webhook_api_version": "2",
    "created_at": "2025-08-27T13:28:33.598Z",
    "updated_at": "2025-08-27T13:28:33.598Z",
    "number_pool_settings": {
      "toll_free_weight": 10.0,
      "long_code_weight": 1.0,
      "skip_unhealthy": true,
      "sticky_sender": false,
      "geomatch": false,
      "tf_weight": 10.0,
      "lc_weight": 1.0
    },
    "url_shortener_settings": {
      "domain": "",
      "prefix": "",
      "prevent_additional_segments": false,
      "replace_blacklist_only": true,
      "send_webhooks": false
    },
    "v1_secret": "g92ZfOK9XlI5Ee6HkSvfraSd",
    "health_webhook_url": null,
    "mms_fall_back_to_sms": false,
    "mms_transcoding": false,
    "daily_spend_limit": "0.00",
    "daily_spend_limit_enabled": false
  }
}
```

---

## Notes

* **Minimum required fields**: `name`, `whitelisted_destinations`.
* Set nested objects (`number_pool_settings`, `url_shortener_settings`) to `null` to disable features.
* Always validate before sending data to Telnyx API.
* Webhook URLs should use **HTTPS**.

---

👉 This README gives you both **validation rules** and a **realistic JSON body** to guide development.

Do you want me to also add a **CRUD endpoints section** (Create, Update, Retrieve, Delete) like I did earlier, with validation tied to each action?



Here’s a **step-by-step README.md** outline (with clear instructions, no code) for your **“Assign Number to Messaging Profile”** functionality and showing the **profile with assigned numbers**.

---

# Messaging Profiles – Assign Numbers

This module allows you to **assign phone numbers to a Telnyx Messaging Profile** and then display them on the **Messaging Profile show page**.

We are using:

* **Laravel 11** (Backend)
* **Inertia.js + Vue/React** (Frontend)
* **Telnyx PHP SDK** (`telnyx/telnyx-php`)
* **Toastr** (for validation/error messages)

---

## 1. Setup

1. Make sure your `.env` has the **Telnyx API Key**:

   ```env
   TELNYX_API_KEY=your_api_key_here
   ```
2. Ensure you have installed the SDK:

   ```bash
   composer require telnyx/telnyx-php
   ```
3. Add `Telnyx\Telnyx::setApiKey(env('TELNYX_API_KEY'));` in your service provider or before API calls.

---

## 2. Assign Number to Messaging Profile

### API Reference

Telnyx API: [Assign Numbers to Messaging Profile](https://developers.telnyx.com/api/messaging/list-profile-phone-numbers#request)

**Endpoint:**

```
GET /v2/messaging_profiles/{id}/phone_numbers
```

* **Path Parameter:**

  * `id` → UUID of the messaging profile
* **Query Parameters (Optional):**

  * `page[number]` → page number
  * `page[size]` → records per page

---

## 3. Laravel Flow

### Controller

* Create a method `show($id)` inside your `MessagingProfileController`.
* Inside this method:

  1. Fetch the messaging profile by `id` from **your database**.
  2. Call the **Telnyx API** (via SDK or CURL) to get the list of phone numbers assigned.
  3. Return both profile details and phone numbers to your **Inertia page**.

---

## 4. Frontend (Inertia Page)

* Create a `MessagingProfile/Show.vue` (or `.jsx` if React).
* Display:

  * **Profile Information** (from DB → name, created\_at, etc.)
  * **Assigned Numbers Table** (from Telnyx API → number, status, etc.)
* Add **Action Button “Assign Number”**:

  * Opens a modal or dropdown with available Telnyx numbers.
  * On select → call backend route to **attach number to profile**.
  * Use **toastr notifications** for success/error.

---

## 5. Assign Number Action

* Create route: `POST /messaging-profiles/{id}/assign-number`
* Backend will:

  1. Validate request (`number_id` required).
  2. Call Telnyx API to **assign phone number to profile**.
  3. Save assignment in local DB (optional, for quick lookup).
  4. Return success → show toastr notification.

---

## 6. Show Page Example Layout

**Messaging Profile: Zoom-Style UI**

* Left sidebar → List of Messaging Profiles
* Main area → Selected profile details
* Section → Assigned Numbers (with pagination)
* Button → **Assign New Number**

---

## 7. Validation

* **Backend Validation:**

  * `name` → required, string
  * `whitelisted_destinations.*` → required, must match `^[A-Z]{2}$`
  * `number_id` (when assigning) → required, must exist in Telnyx

* **Frontend:**

  * Display **toastr** errors for validation failures
  * Success toastr for assignment

---

## 8. Toastr Notifications

* On success → `"Number assigned successfully"`
* On error → show API error message returned from Telnyx

---

## 9. Testing

1. Create a messaging profile.
2. Open the show page.
3. Assign a number from available Telnyx numbers.
4. Confirm number appears in the assigned numbers list.
5. Refresh page → numbers must persist from Telnyx API.

---

✅ With this flow, your **Messaging Profile Show Page** will always reflect the **live assigned numbers** from Telnyx.

---

Do you want me to also **add a section in the README about unassigning/removing a number** from a profile, or you only want the assign & show part?
