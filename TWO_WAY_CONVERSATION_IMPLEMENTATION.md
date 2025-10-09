# Two-Way Conversation System Implementation

## Overview
This document explains how the messenger system handles two-way conversations, showing messages from both you (outbound) and your contacts (inbound).

## Database Structure

### Conversations Table
```
- id
- contact_id (who you're talking to)
- sender_number (your phone number)
- last_message_at
- unread_count
```

### Messages Table
```
- id
- conversation_id
- telnyx_message_id
- direction (inbound/outbound)
- content
- status (queued/sending/sent/delivered/failed)
- sent_at
- delivered_at
- failed_at
- error_message
```

### Direction Values
- **`inbound`** - Messages received from contact
- **`outbound`** - Messages sent by you to contact

## How It Works

### 1. Conversation Creation

**When you send a message:**
```php
Conversation::firstOrCreate([
    'contact_id' => $contact->id,
    'sender_number' => $yourPhoneNumber->phone_number
]);
```

**When you receive a message (webhook):**
```php
Conversation::firstOrCreate([
    'contact_id' => $contact->id,
    'sender_number' => $yourPhoneNumber // The number that received the message
]);
```

Both create the **same conversation** - ensuring all messages between you and a contact are in one place.

### 2. Message Direction

**Outbound Message (You → Contact):**
```php
$message = $conversation->messages()->create([
    'direction' => Message::DIRECTION_OUTBOUND,
    'content' => 'Hello!',
    'status' => Message::STATUS_QUEUED
]);
```

**Inbound Message (Contact → You):**
```php
$message = $conversation->messages()->create([
    'direction' => Message::DIRECTION_INBOUND,
    'content' => 'Hi there!',
    'status' => Message::STATUS_DELIVERED
]);
```

### 3. Displaying Conversations

The `index()` method now returns:

```php
[
    'conversations' => [
        {
            'id': 1,
            'contact': {
                'name': 'John Doe',
                'phone_e164': '+16075698372'
            },
            'sender_number': '+12037206619',
            'messages': [
                {
                    'content': 'Hello!',
                    'direction': 'outbound',
                    'is_from_user': true,
                    'is_from_contact': false,
                    'sender_name': 'You',
                    'status': 'delivered',
                    'created_at': '2025-10-08 10:00:00'
                },
                {
                    'content': 'Hi there!',
                    'direction': 'inbound',
                    'is_from_user': false,
                    'is_from_contact': true,
                    'sender_name': 'John Doe',
                    'status': 'delivered',
                    'created_at': '2025-10-08 10:01:00'
                }
            ]
        }
    ]
]
```

## Message Flow Diagrams

### Outbound Message Flow
```
1. You send message via SmsController
   ↓
2. Create conversation (if doesn't exist)
   ↓
3. Create message with direction='outbound'
   ↓
4. Send to Telnyx API
   ↓
5. Update status: queued → sending → sent → delivered
   ↓
6. Webhook updates message status
```

### Inbound Message Flow
```
1. Contact sends SMS to your number
   ↓
2. Telnyx receives message
   ↓
3. Telnyx sends webhook to your server
   ↓
4. WebhookController checks if number exists in database
   ↓
5. If YES:
   - Find/Create Contact
   - Find/Create Conversation
   - Create message with direction='inbound'
   - Update conversation metadata
   ↓
6. Frontend receives update
```

## Enhanced Features

### 1. Message Metadata
Each message now includes:
- `is_from_user` - Boolean indicating if you sent it
- `is_from_contact` - Boolean indicating if contact sent it
- `sender_name` - Display name ('You' or contact name)

### 2. Ordered Messages
Messages are ordered chronologically (`created_at ASC`) so conversations flow naturally:
```
[10:00] You: Hello!
[10:01] John: Hi there!
[10:02] You: How are you?
[10:03] John: Good, thanks!
```

### 3. Contact Display
- If contact has a name: Shows name
- If no name: Shows phone number (E.164 format)

## Frontend Integration

### Example Vue Component Usage

```vue
<template>
  <div v-for="conversation in conversations" :key="conversation.id">
    <h3>{{ conversation.contact.name || conversation.contact.phone_e164 }}</h3>
    
    <div class="messages">
      <div 
        v-for="message in conversation.messages" 
        :key="message.id"
        :class="{
          'message-outbound': message.is_from_user,
          'message-inbound': message.is_from_contact
        }"
      >
        <div class="sender">{{ message.sender_name }}</div>
        <div class="content">{{ message.content }}</div>
        <div class="time">{{ message.created_at }}</div>
        <div class="status">{{ message.status }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  conversations: Array
});
</script>
```

## API Endpoints

### Get All Conversations
```
GET /messenger
Returns: All conversations with messages
```

### Get Specific Conversation
```
GET /messenger/conversation/{id}
Returns: Single conversation with all messages
```

### Send Message
```
POST /messenger/send
Body: {
  contact_id: 1,
  content: "Hello!",
  from_phone_number_id: 1
}
Returns: { success: true, message: {...} }
```

## Message Status Tracking

### Status Progression
```
QUEUED → SENDING → SENT → DELIVERED
   ↓         ↓       ↓         ↓
   └─────→ FAILED ←─┴────────┘
```

### Status Meanings
- **QUEUED** - Message created, waiting to send
- **SENDING** - Message being processed by Telnyx
- **SENT** - Message sent to carrier
- **DELIVERED** - Message delivered to recipient
- **FAILED** - Message failed to send/deliver

## Error Handling

### Failed Messages
When a message fails:
```php
{
    'status': 'failed',
    'failed_at': '2025-10-08 10:05:00',
    'error_message': '{"code": "invalid_phone_number", "detail": "..."}'
}
```

### Unknown Numbers
When webhook receives message for unknown number:
```
[WARNING] Received inbound SMS for unknown phone number
{
    'to': '+12037206619',
    'from': '+16075698372',
    'telnyx_id': '403199c3-aabc-4ebe-b107-b91e2fca75e8'
}
```

## Data Relationships

```
User
  ↓ has many
PhoneNumber (messaging_profile_id must be set)
  ↓ receives messages for
Conversation (identified by sender_number)
  ↓ has many
Messages (with direction: inbound/outbound)
  
Contact (identified by phone_e164)
  ↓ has many
Conversations (as contact_id)
```

## Query Examples

### Get all messages in a conversation
```php
$conversation->messages()
    ->orderBy('created_at', 'asc')
    ->get();
```

### Get unread conversations
```php
Conversation::where('unread_count', '>', 0)
    ->orderBy('last_message_at', 'desc')
    ->get();
```

### Get only outbound messages
```php
$conversation->messages()
    ->where('direction', Message::DIRECTION_OUTBOUND)
    ->get();
```

### Get only inbound messages
```php
$conversation->messages()
    ->where('direction', Message::DIRECTION_INBOUND)
    ->get();
```

### Get failed messages
```php
Message::where('status', Message::STATUS_FAILED)
    ->whereNotNull('error_message')
    ->get();
```

## Real-Time Updates

To enable real-time updates (optional):

1. **Uncomment broadcast events in WebhookController:**
```php
event(new \App\Events\MessageReceived($conversation, $message, $phoneNumber->user_id));
```

2. **Setup Laravel Echo in frontend:**
```javascript
Echo.private(`user.${userId}`)
    .listen('MessageReceived', (e) => {
        // Add new message to conversation
        console.log('New message:', e.message);
    });
```

3. **Configure broadcasting in `.env`:**
```
BROADCAST_DRIVER=reverb
```

## Testing

### Test Two-Way Conversation

1. **Send outbound message:**
```bash
POST /messenger/send
{
  "contact_id": 1,
  "content": "Test outbound message",
  "from_phone_number_id": 1
}
```

2. **Simulate inbound message (via Telnyx webhook):**
```bash
POST /api/webhooks/sms
{
  "data": {
    "event_type": "message.finalized",
    "payload": {
      "direction": "inbound",
      "from": {"phone_number": "+16075698372"},
      "to": [{"phone_number": "+12037206619"}],
      "text": "Test inbound message",
      "id": "unique-message-id"
    }
  }
}
```

3. **Check conversation:**
```bash
GET /messenger
```

Should return both messages in chronological order.

## Files Modified

- ✅ `app/Http/Controllers/SmsController.php`
  - Enhanced `index()` to load all messages with direction info
  - Enhanced `showConversation()` to include message metadata
  
- ✅ `app/Http/Controllers/WebhookController.php`
  - Handles inbound messages via webhooks
  - Creates conversations for inbound messages
  
- ✅ `app/Models/Message.php`
  - Added `failed_at` and `error_message` fields
  - Direction constants (INBOUND/OUTBOUND)
  
- ✅ `app/Models/Conversation.php`
  - Already has all necessary relationships

## Summary

The two-way conversation system now:
- ✅ Shows messages from both you and your contacts
- ✅ Properly identifies message direction (inbound/outbound)
- ✅ Provides sender metadata for easy display
- ✅ Orders messages chronologically
- ✅ Tracks message status in real-time
- ✅ Handles errors gracefully
- ✅ Only creates conversations for numbers in your database

Each conversation is a complete history of all messages exchanged between you and a contact! 🎉

