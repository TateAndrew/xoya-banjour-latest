# Message.Finalized Webhook Implementation

## Overview
This document explains the implementation of the Telnyx `message.finalized` webhook handler that automatically creates conversations and saves messages.

## Features Implemented

### 1. **Webhook Handler** (`WebhookController.php`)

The webhook now handles multiple message events:
- ✅ `message.received` - Inbound messages
- ✅ `message.finalized` - Both inbound and outbound message completion
- ✅ `message.sent` - Message sent confirmation
- ✅ `message.delivered` - Message delivery confirmation
- ✅ `message.failed` - Message failure notification

### 2. **Message.Finalized Handler**

When Telnyx sends a `message.finalized` webhook, the system:

#### For Inbound Messages:
1. Checks if the "to" phone number exists in the `phone_numbers` table
2. If number **exists** in database:
   - Creates or finds the contact (based on "from" number)
   - Creates or finds the conversation between contact and your number
   - Saves the message to the database
   - Updates conversation's last message time and unread count
3. If number **doesn't exist**: Logs a warning and ignores the message

#### For Outbound Messages:
1. Finds the existing message by `telnyx_message_id`
2. Updates the message status based on delivery status:
   - `delivered` → STATUS_DELIVERED
   - `sent` → STATUS_SENT
   - `sending_failed` → STATUS_FAILED
   - `delivery_failed` → STATUS_FAILED
3. Updates sent/delivered timestamps

### 3. **Database Schema Updates**

Added new fields to `messages` table:
- `failed_at` - Timestamp when message failed
- `error_message` - Error details if message failed

### 4. **Example Webhook Payload**

```json
{
  "data": {
    "event_type": "message.finalized",
    "id": "9d7a67d9-9b72-4c09-b292-8409f9dedc23",
    "occurred_at": "2025-10-08T11:52:50.971+00:00",
    "payload": {
      "direction": "outbound",
      "from": {
        "phone_number": "+12037206619"
      },
      "to": [
        {
          "phone_number": "+16075698372",
          "status": "delivered"
        }
      ],
      "id": "403199c3-aabc-4ebe-b107-b91e2fca75e8",
      "text": "dadasda",
      "completed_at": "2025-10-08T11:52:50.971+00:00",
      "sent_at": "2025-10-08T11:52:50.892+00:00"
    }
  }
}
```

## How It Works

### Inbound Message Flow:
```
1. Customer sends SMS to your Telnyx number
   ↓
2. Telnyx sends message.finalized webhook
   ↓
3. System checks if "to" number exists in phone_numbers table
   ↓
4. If YES:
   - Find/Create Contact (from sender number)
   - Find/Create Conversation
   - Save Message
   - Update conversation metadata
   ↓
5. If NO: Log warning and skip
```

### Outbound Message Flow:
```
1. You send SMS via SmsController
   ↓
2. Message created with STATUS_QUEUED
   ↓
3. Telnyx sends message.sent webhook
   → Update to STATUS_SENT
   ↓
4. Telnyx sends message.finalized webhook
   → Update to STATUS_DELIVERED (if delivered)
   → Update timestamps
```

## Message Status Flow

```
QUEUED → SENDING → SENT → DELIVERED
   ↓         ↓       ↓         ↓
   └─────→ FAILED ←─┴────────┘
```

## Configuration

### Webhook URL Setup in Telnyx:
Configure your messaging profile webhook URL to point to:
```
https://yourdomain.com/api/webhooks/sms
```

### Events to Subscribe:
- message.received
- message.finalized
- message.sent
- message.delivered
- message.failed

## Error Handling

All webhook handlers include comprehensive error handling:
- Logs all incoming webhooks
- Try-catch blocks for each handler
- Detailed error logging with stack traces
- Graceful handling of missing data
- Returns appropriate HTTP responses

## Logging

The system logs:
- ✅ All incoming webhook payloads
- ✅ Message processing steps
- ✅ Contact/Conversation creation
- ✅ Status updates
- ✅ Errors with full context

Check logs at: `storage/logs/laravel.log`

## Benefits

1. **Automatic Conversation Management** - Conversations are created automatically when someone messages your number
2. **Real-time Status Updates** - Message statuses are updated in real-time as Telnyx sends webhooks
3. **Error Tracking** - Failed messages are tracked with error details
4. **Database Integrity** - Only processes messages for numbers in your database
5. **Duplicate Prevention** - Checks for existing messages to prevent duplicates
6. **Complete Audit Trail** - All webhook events are logged

## Testing

To test the webhook:

1. **Send a test message via Telnyx:**
   ```bash
   POST https://api.telnyx.com/v2/messages
   {
     "from": "+12037206619",
     "to": "+16075698372",
     "text": "Test message",
     "messaging_profile_id": "your-profile-id"
   }
   ```

2. **Check logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Verify database:**
   - Check `contacts` table for new contact
   - Check `conversations` table for new conversation
   - Check `messages` table for message record

## Phone Number Formatting

All phone numbers are automatically formatted to E.164 standard:
- Format: `+[country code][number]`
- Example: `+12025551234`
- Ensures Telnyx API compatibility

## Next Steps

To enable real-time updates in your frontend:
1. Uncomment the broadcast events in WebhookController
2. Set up Laravel Echo and Pusher/Reverb
3. Listen for events in your Vue components

## Files Modified

- ✅ `app/Http/Controllers/WebhookController.php` - Added message.finalized handler
- ✅ `app/Models/Message.php` - Added new fields
- ✅ `database/migrations/2025_10_08_115653_add_cost_and_error_fields_to_messages_table.php` - Database schema update

## API Endpoint

**Webhook Endpoint:**
- URL: `/api/webhooks/sms`
- Method: POST
- Handler: `WebhookController@handleSmsWebhook`

The endpoint is already configured in your routes.

