# Real-time Transcription with Pusher Setup

This document explains how to set up real-time transcription broadcasting using Pusher for the XOYA Banjour application.

## Features Implemented

✅ **TranscriptionUpdated Event** - Broadcasts transcription updates in real-time
✅ **Laravel Echo Integration** - Frontend listens for real-time updates
✅ **WebhookController Integration** - Automatically broadcasts when transcription webhooks are received
✅ **Real-time UI Display** - Shows live transcription with confidence scores and status
✅ **Toast Notifications** - Alerts users when final transcriptions are completed

## Setup Instructions

### 1. Environment Configuration

Add the following environment variables to your `.env` file:

```env
# Broadcasting
BROADCAST_DRIVER=pusher

# Pusher Configuration
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_app_key
PUSHER_APP_SECRET=your_pusher_app_secret
PUSHER_APP_CLUSTER=your_pusher_cluster

# Frontend Pusher Configuration (for Vite)
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
VITE_PUSHER_HOST=
VITE_PUSHER_PORT=443
VITE_PUSHER_SCHEME=https
```

### 2. Pusher Account Setup

1. Sign up for a free Pusher account at https://pusher.com
2. Create a new app in the Pusher dashboard
3. Copy the app credentials (App ID, Key, Secret, Cluster) to your `.env` file

### 3. Install Dependencies

The required dependencies are already included:
- `pusher/pusher-php-server` (backend)
- `pusher-js` (frontend)
- `laravel-echo` (frontend)

### 4. Queue Configuration (Optional but Recommended)

For better performance, configure a queue driver to handle broadcasting:

```env
QUEUE_CONNECTION=database
```

Then run the queue worker:
```bash
php artisan queue:work
```

## How It Works

### Backend Flow

1. **Webhook Receives Transcription Data** - Telnyx sends transcription webhook to `/webhook/call`
2. **Event Processing** - `WebhookController::handleCallTranscriptionEvent()` processes the data
3. **Database Update** - Transcript is saved to `call_transcripts` table
4. **Broadcasting** - `TranscriptionUpdated` event is broadcast via Pusher
5. **Real-time Delivery** - Frontend receives the update instantly

### Frontend Flow

1. **Echo Listener** - Dialer component listens on `call-transcription` channel
2. **Data Update** - Reactive variables are updated with new transcription data
3. **UI Update** - Real-time transcription display shows latest text with confidence
4. **Transcript Log** - Transcription is added to call transcript with [FINAL]/[INTERIM] tags
5. **Notifications** - Toast notification for completed transcriptions

## Channels and Events

### Channels
- `call-transcription` - Global transcription updates
- `call-transcription.{call_control_id}` - Specific call transcription updates

### Event Name
- `transcription.updated` - Broadcast when transcription data is updated

### Event Data Structure
```json
{
  "call_control_id": "v2:7subYr8fLrXmaAXm8egeAMpoSJ72J3SGPUuome81-hQuaKRf9b7hKA",
  "transcript_id": 123,
  "call_id": 456,
  "transcript_text": "hello this is a test speech",
  "status": "completed",
  "language": "en",
  "is_final": true,
  "confidence": 0.977219,
  "latest_transcript": "hello this is a test speech",
  "timestamp": "2024-01-01T12:00:00.000Z",
  "transcript_data": {
    "segments": [...],
    "confidence": 0.977219,
    "is_final": true
  }
}
```

## UI Components

### Real-time Transcription Display
- Shows current transcription text with confidence percentage
- Status indicators: 🔄 Processing, ✅ Completed, ⏸️ Idle
- Beautiful blue-themed UI that appears when transcription is active

### Call Transcript Log
- Shows all transcription events with timestamps
- Marks interim vs final transcriptions: `[INTERIM]` / `[FINAL]`
- Includes confidence scores in parentheses

### Toast Notifications
- Success notifications for completed final transcriptions
- Configurable via toastr options in `bootstrap.js`

## Testing

### Test with Sample Webhook Data
```json
{
   "data": {
      "record_type": "event",
      "event_type": "call.transcription",
      "id": "0ccc7b54-4df3-4bca-a65a-3da1ecc777f0",
      "occurred_at": "2018-02-02T22:25:27.521992Z",
      "payload": {
           "call_control_id": "v2:7subYr8fLrXmaAXm8egeAMpoSJ72J3SGPUuome81-hQuaKRf9b7hKA",
           "call_leg_id": "5ca81340-5beb-11eb-ae45-02420a0f8b69",
           "call_session_id": "5ca81eee-5beb-11eb-ba6c-02420a0f8b69",
           "client_state": null,
           "connection_id": "1240401930086254526",
           "transcription_data": {
              "confidence": 0.977219,
              "is_final": true,
              "transcript": "hello this is a test speech"
           }
      }
   }
}
```

Send this to your webhook endpoint to see real-time updates in the Dialer interface.

## Troubleshooting

### Common Issues

1. **No real-time updates**: Check Pusher credentials and network connectivity
2. **Laravel Echo not defined**: Ensure `bootstrap.js` is properly imported
3. **Broadcasting not working**: Verify `BROADCAST_DRIVER=pusher` in `.env`
4. **Queue jobs stuck**: Run `php artisan queue:work` to process broadcasting jobs

### Debug Tools

- Browser Developer Console: Check for Echo connection and event logs
- Pusher Debug Console: Monitor events in real-time on Pusher dashboard
- Laravel Logs: Check `storage/logs/laravel.log` for transcription processing logs

## Performance Considerations

- Use Redis queue driver for high-volume transcription events
- Consider rate limiting for webhook endpoints
- Monitor Pusher usage to avoid exceeding free tier limits
- Implement client-side caching for large transcript data

## Security Notes

- Pusher channels are public by default - consider private channels for sensitive data
- Webhook endpoints should validate Telnyx signatures in production
- Store Pusher secrets securely and never commit to version control
