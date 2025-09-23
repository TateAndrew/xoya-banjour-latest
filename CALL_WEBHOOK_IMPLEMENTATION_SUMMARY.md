# Call Webhook Implementation Summary

## Overview

The webhook system now creates a **main call record** in the `calls` table when a call is initiated, and stores all subsequent webhook events in the **detail table** (`call_legs`). This provides a clean master-detail relationship for call tracking.

## Flow

### 1. Call Initiated (`call.initiated`)
- ✅ Creates main **Call** record in `calls` table
- ✅ Creates **CallSession** record linked to the call
- ✅ Creates **CallLeg** record for the initiate event

### 2. Subsequent Events (`call.answered`, `call.bridged`, `call.hangup`, etc.)
- ✅ Finds existing CallSession by `call_session_id`
- ✅ Creates **CallLeg** record for each event (detail table)
- ✅ Updates main **Call** record status and timestamps
- ✅ Updates **CallSession** record

## Database Structure

### Main Table: `calls`
```sql
- id (primary key)
- user_id
- phone_number_id
- from_number, to_number
- call_type, status
- telnyx_call_id
- answered_at, ended_at, duration
- metadata
```

### Master Table: `call_sessions`
```sql
- id (primary key)
- call_id (foreign key to calls)
- call_session_id (Telnyx session ID)
- from_number, to_number
- direction, status
- start_time, end_time, duration
- custom_headers, metadata
```

### Detail Table: `call_legs`
```sql
- id (primary key)
- call_session_id (foreign key to call_sessions)
- call_leg_id (Telnyx leg ID)
- event_type, event_id
- occurred_at
- state, hangup_cause
- call_quality_stats
- custom_headers, metadata
```

## Relationships

```
Call (1) -> (Many) CallSession (1) -> (Many) CallLeg
```

- **Call**: Main call record created on initiate
- **CallSession**: Session-level tracking
- **CallLeg**: Individual event records

## Webhook Processing

### Event Types Handled
- `call.initiated` → Creates call + session + leg
- `call.answered` → Updates call status + creates leg
- `call.bridged` → Updates call status + creates leg
- `call.hangup` → Updates call status + creates leg
- `call.failed` → Updates call status + creates leg

### Key Features
- **Deduplication**: Prevents duplicate event processing
- **Status Tracking**: Updates call status based on events
- **Duration Calculation**: Calculates call duration on hangup
- **Quality Metrics**: Stores call quality statistics
- **Error Handling**: Comprehensive logging and error handling

## Usage Examples

### Get Call with All Events
```php
$call = Call::with(['callSessions.callLegs'])->find($callId);
$allEvents = $call->callLegs; // All events across all sessions
```

### Get Call History
```php
$calls = Call::getUserCallHistory($userId, 50);
```

### Get Event Timeline for a Call
```php
$call = Call::find($callId);
$events = $call->callSessions->first()->getEventHistory();
```

## Webhook Endpoint

- **URL**: `/webhook/call`
- **Method**: POST
- **CSRF**: Exempted
- **Rate Limit**: 100 requests/minute

## Benefits

1. **Clean Data Structure**: Main call record + detailed event tracking
2. **Complete History**: Every webhook event is preserved
3. **Performance**: Optimized queries with proper indexing
4. **Flexibility**: Easy to add new event types
5. **Analytics**: Rich data for call analytics and reporting

## Testing

You can test with the sample webhook data from `call_webhook.md`:

1. Send `call.initiated` → Creates call record
2. Send `call.answered` → Updates call + creates leg
3. Send `call.bridged` → Updates call + creates leg
4. Send `call.hangup` → Updates call + creates leg

Each event will be stored in the detail table while maintaining the main call record.
