# Internal User-to-User Messaging Fix

## Problem Description

When two users in the system send messages to each other (both numbers purchased in the portal), the conversation was only showing for the sender, not the recipient.

### Example Scenario:
- **User A** owns: `+1-203-720-6619`
- **User B** owns: `+1-607-569-8372`
- User A sends message to User B
- ❌ Conversation shows in User A's messenger
- ❌ Conversation does NOT show in User B's messenger

## Root Cause

The original query only fetched conversations where `sender_number` matched the user's phone numbers:

```php
Conversation::whereIn('sender_number', $userPhoneNumbers->pluck('phone_number'))
```

### How Conversations Are Structured:
When User A sends to User B:
- `contact_id` → Contact with phone_e164 = `+1-607-569-8372` (User B)
- `sender_number` → `+1-203-720-6619` (User A)

This conversation would only show for User A, not User B.

## Solution

Modified the query to fetch conversations where the user is EITHER:
1. **The sender** (initiated the conversation)
2. **The recipient** (someone sent to their number)

### Updated Query:
```php
Conversation::where(function($query) use ($userPhoneNumbersList) {
    // Conversations you initiated
    $query->whereIn('sender_number', $userPhoneNumbersList)
        // OR conversations sent to you (contact is your number)
        ->orWhereHas('contact', function($q) use ($userPhoneNumbersList) {
            $q->whereIn('phone_e164', $userPhoneNumbersList);
        });
})
```

## How It Works Now

### Case 1: External Contact (Normal)
```
User owns: +1-203-720-6619
Contact: +1-555-123-4567 (external number)

Conversation:
- sender_number: +1-203-720-6619
- contact.phone_e164: +1-555-123-4567

Direction Logic:
- Outbound message → from you
- Inbound message → from contact
```

### Case 2: Internal Contact (User-to-User)
```
User A owns: +1-203-720-6619
User B owns: +1-607-569-8372

When User A sends to User B:
Conversation:
- sender_number: +1-203-720-6619 (User A)
- contact.phone_e164: +1-607-569-8372 (User B)

User A's View:
- Shows conversation (sender_number matches)
- Outbound message → from you
- Inbound message → from User B

User B's View:
- Shows conversation (contact.phone_e164 matches)
- Direction reversed!
- Inbound message → from User A (but marked as outbound in DB)
- Outbound message → from you (but marked as inbound in DB)
```

## Direction Reversal Logic

For internal conversations, the message direction is reversed:

```php
if ($contactIsYourNumber) {
    // Contact is actually your number - reverse the logic
    $message->is_from_user = $message->direction === Message::DIRECTION_INBOUND;
    $message->is_from_contact = $message->direction === Message::DIRECTION_OUTBOUND;
    $message->sender_name = $message->is_from_contact 
        ? $conversation->sender_number 
        : 'You';
} else {
    // Normal external contact
    $message->is_from_user = $message->direction === Message::DIRECTION_OUTBOUND;
    $message->is_from_contact = $message->direction === Message::DIRECTION_INBOUND;
    $message->sender_name = $message->is_from_user 
        ? 'You' 
        : $conversation->contact->name;
}
```

## Example: User A → User B Message Flow

### Step 1: User A sends message
```
POST /messenger/send
{
  contact_id: 2 (User B's number as contact),
  content: "Hello User B!",
  from_phone_number_id: 1 (User A's number)
}
```

### Step 2: Conversation Created
```
Conversation:
- id: 1
- contact_id: 2 (User B)
- sender_number: +1-203-720-6619 (User A)
- last_message_at: now()

Message:
- conversation_id: 1
- direction: 'outbound'
- content: "Hello User B!"
```

### Step 3: User A's View
```
Query: WHERE sender_number = '+1-203-720-6619'
Result: ✅ Shows conversation

Messages:
- "Hello User B!" → direction: outbound → is_from_user: true
```

### Step 4: User B's View
```
Query: WHERE contact.phone_e164 = '+1-607-569-8372'
Result: ✅ Shows conversation (NOW WORKING!)

Messages:
- "Hello User B!" → direction: outbound (in DB)
                  → is_from_user: false (reversed for User B)
                  → is_from_contact: true (shows as from User A)
```

### Step 5: User B replies
```
POST /messenger/send
{
  contact_id: 1 (User A's number as contact),
  content: "Hi User A!",
  from_phone_number_id: 2 (User B's number)
}
```

Creates NEW conversation:
```
Conversation:
- id: 2
- contact_id: 1 (User A)
- sender_number: +1-607-569-8372 (User B)

Message:
- conversation_id: 2
- direction: 'outbound'
- content: "Hi User A!"
```

### Result:
- **User A** sees 2 conversations (one they initiated, one received)
- **User B** sees 2 conversations (one they initiated, one received)
- Both conversations show correctly with proper sender/receiver logic

## Display Name Logic

```php
if ($contactIsYourNumber) {
    // Show the sender's number as the contact name
    $conversation->display_name = $conversation->sender_number;
} else {
    // Show the contact's name or number
    $conversation->display_name = $conversation->contact->name 
        ?? $conversation->contact->phone_e164;
}
```

## Benefits

✅ **Both users see conversations** - No matter who initiates  
✅ **Correct message attribution** - Messages show from correct sender  
✅ **Works for external contacts** - Original functionality preserved  
✅ **Automatic detection** - System detects internal vs external contacts  
✅ **No duplicate logic needed** - Single query handles both cases  

## Testing

### Test Internal User Messaging:

1. **Create two users with phone numbers:**
```sql
User A: +1-203-720-6619
User B: +1-607-569-8372
```

2. **User A sends to User B:**
```bash
POST /messenger/send
{
  "contact_id": <User B contact ID>,
  "content": "Hello User B!",
  "from_phone_number_id": <User A number ID>
}
```

3. **Check User A's messenger:**
```
GET /messenger (as User A)
Expected: Shows conversation with User B
```

4. **Check User B's messenger:**
```
GET /messenger (as User B)
Expected: ✅ Now shows conversation with User A
```

5. **User B replies:**
```bash
POST /messenger/send
{
  "contact_id": <User A contact ID>,
  "content": "Hi User A!",
  "from_phone_number_id": <User B number ID>
}
```

6. **Both users see messages:**
```
User A sees:
- [Sent] Hello User B!
- [Received] Hi User A!

User B sees:
- [Received] Hello User B!
- [Sent] Hi User A!
```

## Response Structure

```json
{
  "conversations": [
    {
      "id": 1,
      "sender_number": "+1-203-720-6619",
      "contact": {
        "phone_e164": "+1-607-569-8372"
      },
      "contact_is_internal": true,
      "display_name": "+1-203-720-6619",
      "messages": [
        {
          "content": "Hello User B!",
          "direction": "outbound",
          "is_from_user": false,      // Reversed for User B
          "is_from_contact": true,    // Shows as from User A
          "sender_name": "+1-203-720-6619"
        }
      ]
    }
  ]
}
```

## Files Modified

- ✅ `app/Http/Controllers/SmsController.php`
  - Updated `index()` method to query conversations both ways
  - Added direction reversal logic for internal contacts
  - Added `contact_is_internal` flag
  - Added `display_name` for proper UI display

## Important Notes

1. **Two Separate Conversations**: When User A and User B message each other, it creates two conversation records (one for each direction). This is by design with the current schema.

2. **Direction Reversal**: For the recipient, message directions are reversed from the database values to show correct sender/receiver in the UI.

3. **Contact Detection**: System automatically detects if a contact's phone number belongs to another user in the system.

4. **Backward Compatible**: External contact conversations work exactly as before.

## Summary

The fix ensures that when two users in the system message each other:
- ✅ Both users see the conversation
- ✅ Messages show from the correct sender
- ✅ Direction logic is properly reversed for the recipient
- ✅ Display names show the other user's number

Now `+1-607-569-8372` will see conversations when `+1-203-720-6619` sends messages! 🎉

