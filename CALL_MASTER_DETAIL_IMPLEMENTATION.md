# Call Master-Detail Table Implementation

## Overview

This implementation creates a master-detail table structure for call tracking based on Telnyx webhook data. The structure separates call session information (master) from individual call leg events (detail) for better data organization and tracking.

## Table Structure

### Master Table: `call_sessions`

The master table stores session-level information that persists across all events for a single call session.

**Key Fields:**
- `call_session_id` - Unique Telnyx session identifier
- `user_id` - Associated user
- `phone_number_id` - Associated phone number
- `from_number` / `to_number` - Call participants
- `direction` - incoming/outgoing
- `status` - Current call status
- `start_time` / `end_time` - Call duration
- `duration` - Calculated duration in seconds
- `cost` - Call cost
- `custom_headers` - SIP custom headers
- `metadata` - Additional payload data

### Detail Table: `call_legs`

The detail table stores individual events/legs for each call session.

**Key Fields:**
- `call_session_id` - Foreign key to call_sessions
- `call_leg_id` - Unique Telnyx leg identifier
- `event_type` - Type of event (call.initiated, call.answered, etc.)
- `event_id` - Unique Telnyx event identifier
- `occurred_at` - When the event occurred
- `state` - Current leg state
- `hangup_cause` - Reason for hangup
- `call_quality_stats` - Quality metrics
- `custom_headers` - Leg-specific headers

## Models

### CallSession Model

```php
// Key relationships
public function callLegs(): HasMany
public function user(): BelongsTo
public function phoneNumber(): BelongsTo

// Key methods
public function isActive(): bool
public function isEnded(): bool
public function getLatestEvent(): ?CallLeg
public function getEventHistory(): Collection
```

### CallLeg Model

```php
// Key relationships
public function callSession(): BelongsTo

// Key methods
public function isAnswered(): bool
public function isHangup(): bool
public function getQualityScore(): ?float
public function getDuration(): ?int
```

## Webhook Processing

The `WebhookController` handles incoming call webhooks and processes them into the master-detail structure:

1. **Event Deduplication** - Checks if event already exists using `event_id`
2. **Session Management** - Finds or creates call session based on `call_session_id`
3. **Leg Creation** - Creates call leg record for each event
4. **Session Updates** - Updates master session based on event type

### Supported Event Types

- `call.initiated` - Call started
- `call.answered` - Call answered
- `call.bridged` - Call connected
- `call.hangup` - Call ended
- `call.failed` - Call failed

## Usage Examples

### Get Call History
```php
$callHistory = CallSession::getUserCallHistory($userId, 50);
```

### Get Event Timeline
```php
$callSession = CallSession::findBySessionId($sessionId);
$events = $callSession->getEventHistory();
```

### Check Call Quality
```php
$latestEvent = $callSession->getLatestEvent();
$qualityScore = $latestEvent->getQualityScore();
```

## Migration Files

- `2025_09_11_130000_create_call_sessions_table.php`
- `2025_09_11_130001_create_call_legs_table.php`

## Webhook Endpoint

- **URL**: `/webhook/call`
- **Method**: POST
- **Rate Limit**: 100 requests per minute

## Benefits

1. **Better Data Organization** - Separates session data from event data
2. **Event History Tracking** - Complete timeline of call events
3. **Quality Metrics** - Detailed call quality statistics
4. **Scalability** - Efficient querying and indexing
5. **Flexibility** - Easy to add new event types and fields

## Database Indexes

### call_sessions
- `user_id, created_at`
- `status, created_at`
- `call_session_id`
- `from_number, to_number`

### call_legs
- `call_session_id, occurred_at`
- `event_type, occurred_at`
- `call_leg_id`
- `event_id`

This structure provides a robust foundation for call tracking and analytics while maintaining data integrity and performance.
