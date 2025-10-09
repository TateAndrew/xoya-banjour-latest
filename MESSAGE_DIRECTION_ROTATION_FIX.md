# Message Direction Rotation Fix

## Problem

When viewing a conversation where the contact's phone number matches one of your own numbers (internal user-to-user messaging), message directions showed backwards.

## What This Fixes

When User B opens a conversation and the "contact" in that conversation is actually User B's own number (meaning it's a conversation initiated by someone else TO User B), the message directions are now correctly reversed.

### Example Scenario:

**Database:**
```
Conversation found by query:
- sender_number: +1-203-720-6619 (User A)
- contact.phone_e164: +1-607-569-8372 (User B's number!)
- User B finds this via: WHERE contact.phone_e164 IN (user_numbers)
```

**Without Fix:**
```
Message: direction='inbound', content="Hello!"
Display: [User B] Hello!  ❌ WRONG - shows as from User B
```

**With Fix:**
```
Message: direction='inbound', content="Hello!"
Detect: contact is User B's own number → REVERSE directions
Display: [+1-203-720-6619] Hello!  ✅ CORRECT - shows from User A
```

## Implementation

### Step 1: Detect Internal Contact
```php
// Check if the contact in this conversation is the user's own number
$userPhoneNumbers = PhoneNumber::where('user_id', $user->id)
    ->pluck('phone_number')
    ->toArray();
    
$contactIsYourNumber = in_array($conversation->contact->phone_e164, $userPhoneNumbers);
```

### Step 2: Reverse Directions if Needed
```php
if ($contactIsYourNumber) {
    // Contact is your number - REVERSE
    $message->is_from_user = ($message->direction === 'inbound');
    $message->is_from_contact = ($message->direction === 'outbound');
    $message->sender_name = $message->is_from_contact 
        ? $conversation->sender_number 
        : 'You';
} else {
    // Normal contact - NORMAL
    $message->is_from_user = ($message->direction === 'outbound');
    $message->is_from_contact = ($message->direction === 'inbound');
    $message->sender_name = $message->is_from_user 
        ? 'You' 
        : $conversation->contact->name;
}
```

## Where Applied

### 1. `index()` Method
Main messenger page - all conversations loaded

### 2. `showConversation()` Method
Individual conversation view - messages for specific conversation

## Direction Logic Table

### Normal External Contact:
| DB Direction | is_from_user | is_from_contact | Display As |
|--------------|--------------|-----------------|------------|
| outbound     | true         | false           | You        |
| inbound      | false        | true            | Contact    |

### Internal Contact (Your Number):
| DB Direction | is_from_user | is_from_contact | Display As      |
|--------------|--------------|-----------------|-----------------|
| outbound     | **false**    | **true**        | Other User      |
| inbound      | **true**     | **false**       | You             |

Notice the directions are **reversed** for internal contacts!

## Why This is Needed

This handles edge cases where:
1. Conversations created by webhooks before reciprocal system
2. Conversations found via `contact.phone_e164` matching user's number
3. Legacy data where user-to-user conversations weren't split into reciprocal pairs
4. Any conversation where you are listed as the "contact" rather than the "sender"

## Visual Example

### Conversation Structure:
```
User B viewing conversation:
┌─────────────────────────────────┐
│ Conversation                    │
│ sender_number: +1-203-720-6619  │ ← User A
│ contact: +1-607-569-8372        │ ← User B (YOU!)
│                                 │
│ Message:                        │
│ direction: 'inbound'            │
│ content: "Hello from A!"        │
└─────────────────────────────────┘

Detection: contact (607) == your number (607) ✓
Action: REVERSE directions
Result: Show as from User A (203)
```

## Testing

### Test Case 1: Normal External Contact
```
Contact: +1-555-123-4567 (external)
Your number: +1-607-569-8372
contactIsYourNumber: false
Direction: Normal (no reversal)
```

### Test Case 2: Internal User Contact
```
Contact: +1-607-569-8372 (your number!)
Your number: +1-607-569-8372  
contactIsYourNumber: true
Direction: Reversed
```

## Files Modified

✅ `app/Http/Controllers/SmsController.php`
- `index()` - Added direction rotation logic
- `showConversation()` - Added direction rotation logic

## Benefits

✅ Correct message attribution in all scenarios  
✅ Handles user-to-user messaging properly  
✅ Backward compatible with existing data  
✅ Works with webhook-created conversations  
✅ Consistent display regardless of how conversation was created  

## Summary

When you view a conversation where the "contact" is actually your own phone number, the system now correctly identifies this and reverses the message directions so they display properly - messages from the other user show as from them, and messages from you show as from you.

This ensures user-to-user messaging within the portal always displays correctly! 🎉

