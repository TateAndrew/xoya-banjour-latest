# Reciprocal Conversation Fix - Internal User Messaging

## Problem Statement

When **User A** sends a message to **User B** (both are users in the portal):
- ✅ User A sees the conversation in their messenger
- ❌ User B does NOT see the conversation in their messenger

### Example:
- **User A** owns: `+1-203-720-6619`
- **User B** owns: `+1-607-569-8372`
- User A sends: "Hello User B!"
- **Result**: Only User A sees the conversation

## Root Cause

The `sendMessage()` method only created ONE conversation:
```php
Conversation:
- contact_id: User B's contact
- sender_number: User A's number
- messages: [message from User A]
```

This conversation only appeared in User A's messenger query because:
```php
Conversation::whereIn('sender_number', $userANumbers) // ✅ Matches
Conversation::whereIn('sender_number', $userBNumbers) // ❌ Doesn't match
```

## Solution: Reciprocal Conversations

When User A sends to User B, we now create **TWO** conversations:

### Conversation 1 (User A's view):
```php
Conversation:
- id: 1
- contact_id: Contact(User B's number)
- sender_number: User A's number
- messages:
  - direction: 'outbound'
  - content: "Hello User B!"
```

### Conversation 2 (User B's view):
```php
Conversation:
- id: 2
- contact_id: Contact(User A's number)
- sender_number: User B's number
- messages:
  - direction: 'inbound'  ← Reversed!
  - content: "Hello User B!"
```

## How It Works

### Step 1: Detect Internal User
```php
$recipientPhoneNumber = PhoneNumber::where('phone_number', $contact->phone_e164)
    ->where('status', 'assigned')
    ->first();

if ($recipientPhoneNumber) {
    // Recipient is a user in the portal!
}
```

### Step 2: Create Sender Contact (for Recipient)
```php
// Create contact representing the sender (from recipient's perspective)
$senderContact = Contact::firstOrCreate(
    ['phone_e164' => $phoneNumber->phone_number],
    ['name' => null]
);
```

### Step 3: Create Reciprocal Conversation
```php
$reciprocalConversation = Conversation::firstOrCreate([
    'contact_id' => $senderContact->id,      // Sender as contact
    'sender_number' => $contact->phone_e164   // Recipient's number
]);
```

### Step 4: Create Reciprocal Message
```php
$reciprocalMessage = $reciprocalConversation->messages()->create([
    'telnyx_message_id' => $response['data']['id'], // Same ID
    'direction' => Message::DIRECTION_INBOUND,      // Inbound for recipient
    'content' => $request->content,                 // Same content
    'status' => Message::STATUS_DELIVERED
]);
```

### Step 5: Update Unread Count
```php
$reciprocalConversation->update([
    'last_message_at' => now(),
    'unread_count' => $reciprocalConversation->unread_count + 1 // Increment for recipient
]);
```

## Complete Flow Example

### User A sends to User B:

**User A** (`+1-203-720-6619`) sends "Hello!" to **User B** (`+1-607-569-8372`)

#### Created in Database:

**Conversation 1 (User A's):**
```json
{
  "id": 1,
  "contact_id": 5,  // Contact with phone_e164: +1-607-569-8372
  "sender_number": "+1-203-720-6619",
  "last_message_at": "2025-10-08 12:00:00",
  "unread_count": 0,
  "messages": [
    {
      "id": 1,
      "direction": "outbound",
      "content": "Hello!",
      "status": "delivered"
    }
  ]
}
```

**Conversation 2 (User B's) - AUTO-CREATED:**
```json
{
  "id": 2,
  "contact_id": 6,  // Contact with phone_e164: +1-203-720-6619
  "sender_number": "+1-607-569-8372",
  "last_message_at": "2025-10-08 12:00:00",
  "unread_count": 1,  // Unread for User B
  "messages": [
    {
      "id": 2,
      "direction": "inbound",  // Inbound from User B's perspective
      "content": "Hello!",
      "status": "delivered"
    }
  ]
}
```

#### User A's Messenger View:
```
GET /messenger (as User A)

Response:
- Conversation with +1-607-569-8372
  - [You] Hello!
```

#### User B's Messenger View:
```
GET /messenger (as User B)

Response:
- Conversation with +1-203-720-6619  ✅ NOW SHOWS!
  - [+1-203-720-6619] Hello!  (1 unread)
```

## When User B Replies

When User B replies "Hi back!", the SAME logic applies:

**Created:**
1. Message in Conversation 2 (User B's outbound)
2. New reciprocal message in Conversation 1 (User A's inbound)

**Result:**
- Both users see both messages
- Messages stay synchronized
- Each user sees correct direction (sent/received)

## Phone Number Matching

The code handles phone number format variations:
```php
$recipientPhoneNumber = PhoneNumber::where('phone_number', $contact->phone_e164)
    ->orWhere('phone_number', 'LIKE', '%' . preg_replace('/[^0-9]/', '', substr($contact->phone_e164, -10)))
    ->where('status', 'assigned')
    ->first();
```

This matches:
- `+1-203-720-6619`
- `+12037206619`
- `12037206619`
- `2037206619` (last 10 digits)

## Logging

All reciprocal conversation creation is logged:
```
[INFO] Creating reciprocal conversation for internal user
{
    "sender": "+1-203-720-6619",
    "recipient": "+1-607-569-8372",
    "recipient_user_id": 2
}

[INFO] Reciprocal conversation created
{
    "conversation_id": 2,
    "message_id": 2
}
```

## Benefits

✅ **Both users see conversations immediately**  
✅ **No webhook dependency** - Works without Telnyx webhooks  
✅ **Correct message direction** - Each user sees proper sent/received  
✅ **Unread counts** - Recipient gets unread notification  
✅ **Message synchronization** - Same Telnyx message ID links both  
✅ **Backward compatible** - External contacts work as before  

## Testing

### Test Case 1: User A → User B (First Message)

**Setup:**
- User A: `+1-203-720-6619`
- User B: `+1-607-569-8372`
- Both have assigned phone numbers with messaging profiles

**Action:**
```bash
POST /messenger/send
{
  "contact_id": 5,  // User B's contact
  "content": "Hello User B!",
  "from_phone_number_id": 1  // User A's phone
}
```

**Expected Database:**
```sql
-- Check conversations
SELECT * FROM conversations;
-- Should show 2 conversations

-- Check messages
SELECT * FROM messages;
-- Should show 2 messages with same telnyx_message_id
```

**Expected User A View:**
```
GET /messenger (as User A)
- Conversation with +1-607-569-8372
  - [You] Hello User B!
```

**Expected User B View:**
```
GET /messenger (as User B)
- Conversation with +1-203-720-6619 ✅
  - [+1-203-720-6619] Hello User B! (unread)
```

### Test Case 2: User B Replies

**Action:**
```bash
POST /messenger/send
{
  "contact_id": 6,  // User A's contact
  "content": "Hi back!",
  "from_phone_number_id": 2  // User B's phone
}
```

**Expected Result:**
- User A sees: [You] Hello User B! / [+1-607-569-8372] Hi back!
- User B sees: [+1-203-720-6619] Hello User B! / [You] Hi back!

## Edge Cases Handled

### 1. External Contact
If recipient is NOT a user in portal:
- Only creates ONE conversation
- No reciprocal conversation
- Works as before

### 2. Duplicate Prevention
```php
Conversation::firstOrCreate([...])
```
Uses `firstOrCreate` to prevent duplicate conversations

### 3. Message ID Sync
Both messages share same `telnyx_message_id`:
```php
'telnyx_message_id' => $response['data']['id']
```
This allows tracking the same physical message from both perspectives

## Database Structure

### Contacts Table
```
- id: 5
  phone_e164: +1-607-569-8372  (User B)
  
- id: 6
  phone_e164: +1-203-720-6619  (User A)
```

### Conversations Table
```
- id: 1
  contact_id: 5 (User B)
  sender_number: +1-203-720-6619 (User A)
  
- id: 2
  contact_id: 6 (User A)
  sender_number: +1-607-569-8372 (User B)
```

### Messages Table
```
- id: 1
  conversation_id: 1
  telnyx_message_id: abc123
  direction: outbound
  content: "Hello!"
  
- id: 2
  conversation_id: 2
  telnyx_message_id: abc123  ← Same ID
  direction: inbound
  content: "Hello!"
```

## Code Changes

### File Modified
`app/Http/Controllers/SmsController.php`

### Changes Made
1. Added recipient detection logic
2. Added sender contact creation
3. Added reciprocal conversation creation
4. Added reciprocal message creation
5. Added unread count increment
6. Added detailed logging

### Lines Added
~40 lines of code between message send and response

## Comparison: Before vs After

### Before
```
User A sends to User B
↓
1 Conversation created (User A's view)
↓
User A sees conversation ✅
User B sees nothing ❌
```

### After
```
User A sends to User B
↓
Check if User B is internal
↓
2 Conversations created:
- Conversation 1 (User A's view)
- Conversation 2 (User B's view) ← NEW!
↓
User A sees conversation ✅
User B sees conversation ✅
```

## Summary

This fix ensures that when users within the portal message each other, **both users immediately see the conversation** in their messenger, with proper message direction and unread counts.

The solution creates reciprocal conversations automatically, treating each message from both sender and recipient perspectives, while maintaining backward compatibility with external contacts.

🎉 **Problem Solved: User B now sees conversations when User A messages them!**

