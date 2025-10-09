# Call Recordings Implementation

This document describes the complete call recording functionality implemented in the Xoya-Banjour application.

## Overview

The call recording system integrates with Telnyx's Call Control API to fetch and manage call recordings in real-time. **Recordings are fetched directly from Telnyx API** rather than storing them in the database, ensuring you always have the latest data. The database is only used to optionally cache webhook events for reference.

## Features

- ✅ **Real-time fetching** from Telnyx API (no manual sync required)
- ✅ List all call recordings with filters
- ✅ Retrieve individual recording details directly from Telnyx
- ✅ Download recordings in MP3 or WAV format
- ✅ Play recordings directly in browser
- ✅ Delete recordings from Telnyx
- ✅ Automatic association with local call data
- ✅ User-based access control
- ✅ Beautiful Vue.js interface
- ✅ Optional webhook capture for event logging

## How It Works

### 1. **Direct API Fetching**
When you visit `/recordings`, the application:
1. Calls Telnyx API to fetch current recordings
2. Matches recordings with local call data (if available)
3. Filters recordings by user permissions
4. Displays results in real-time

### 2. **No Database Dependency**
- Recordings are **NOT** stored in the database by default
- All data is fetched live from Telnyx API
- This ensures you always see the most current state
- Webhooks optionally store events for logging purposes

### 3. **Smart Call Association**
- Uses `call_session_id` to match Telnyx recordings with local calls
- Enriches recording data with call details (from/to numbers, direction)
- Applies user-based filtering automatically

## API Endpoints

All recording endpoints require authentication and fetch data directly from Telnyx API.

### List Recordings (Fetches from Telnyx API)
```
GET /api/recordings
```

**This endpoint fetches recordings directly from Telnyx API in real-time.**

Query Parameters:
- `status` - Filter by recording status (completed, processing, deleted)
- `call_session_id` - Filter by call session ID
- `date_from` - Filter by creation date (from)
- `date_to` - Filter by creation date (to)
- `per_page` - Number of results per page (default: 25)
- `page` - Page number (default: 1)

Response:
```json
{
    "success": true,
    "recordings": {
        "data": [
            {
                "id": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
                "call_session_id": "84a97d76-e40f-11ed-9074-02420a0daa69",
                "call_control_id": "v3:e-31OnvjEM...",
                "duration_millis": 60000,
                "status": "completed",
                "channels": "dual",
                "download_urls": {
                    "mp3": "https://...",
                    "wav": "https://..."
                },
                "created_at": "2025-10-07T13:00:00.000Z",
                "call": {
                    "id": 123,
                    "from_number": "+1234567890",
                    "to_number": "+0987654321",
                    "direction": "outbound",
                    "user_id": 1
                }
            }
        ],
        "meta": {
            "total_pages": 3,
            "total_results": 55,
            "page_number": 1,
            "page_size": 25
        }
    }
}
```

### Get Recording Details (Fetches from Telnyx API)
```
GET /api/recordings/{telnyxRecordingId}
```

**Note:** Use the Telnyx recording ID, not the database ID.

Response:
```json
{
    "success": true,
    "recording": {
        "id": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
        "call_session_id": "...",
        "duration_millis": 60000,
        "download_urls": {
            "mp3": "https://...",
            "wav": "https://..."
        },
        ...
    }
}
```

### Delete Recording (Deletes from Telnyx API)
```
DELETE /api/recordings/{telnyxRecordingId}
```

**Note:** This deletes the recording from Telnyx. Use the Telnyx recording ID.

Response:
```json
{
    "success": true,
    "message": "Recording deleted successfully"
}
```

### Download Recording (Fetches from Telnyx API)
```
GET /api/recordings/{telnyxRecordingId}/download?format=mp3
```

**Note:** Use the Telnyx recording ID.

Query Parameters:
- `format` - Download format (mp3 or wav, default: mp3)

Response:
```json
{
    "success": true,
    "download_url": "https://api.telnyx.com/v2/..."
}
```

### Get Recordings by Call (Fetches from Telnyx API)
```
GET /api/calls/{callId}/recordings
```

**This fetches recordings from Telnyx API filtered by the call's session ID.**

Response:
```json
{
    "success": true,
    "recordings": [
        {
            "id": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
            ...
        }
    ]
}
```

### Sync to Database (Optional)
```
POST /api/recordings/sync
```

This endpoint syncs recordings from Telnyx to the database for caching purposes. **This is optional** as the list endpoint fetches directly from Telnyx.

Query Parameters:
- `page` - Page number (default: 1)
- `page_size` - Page size (default: 50)
- `call_session_id` - Optional filter by call session

Response:
```json
{
    "success": true,
    "synced": 15,
    "meta": {
        "total_pages": 3,
        "total_results": 55
    }
}
```

## Telnyx Service Methods

### List Recordings from Telnyx
```php
$telnyxService->listRecordings($pageNumber = 1, $pageSize = 25, $filters = [])
```

Filters:
- `call_session_id`
- `conference_id`
- `status`
- `created_at_gte`
- `created_at_lte`

### Get Single Recording from Telnyx
```php
$telnyxService->getRecording($recordingId)
```

### Delete Recording from Telnyx
```php
$telnyxService->deleteRecording($recordingId)
```

## Webhook Handler (Optional)

The webhook handler can optionally log recording events for debugging purposes.

### Supported Events
- `call.recording.saved`
- `recording.saved`

### Webhook Endpoint
```
POST /webhook/call
```

The webhook handler:
1. Receives recording data from Telnyx
2. Finds the associated call by `call_session_id`
3. Optionally stores recording metadata in database for logging
4. This is NOT required for the main functionality

### Example Webhook Payload
```json
{
    "data": {
        "event_type": "call.recording.saved",
        "payload": {
            "recording_id": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
            "call_session_id": "84a97d76-e40f-11ed-9074-02420a0daa69",
            "status": "completed",
            "duration_millis": 60000,
            "recording_urls": {
                "mp3": "https://...",
                "wav": "https://..."
            }
        }
    }
}
```

## Frontend Interface

### Accessing Recordings Page
Navigate to: `/recordings`

### Features
1. **View Recordings Table**
   - Date/Time of recording
   - Call details (from/to numbers, direction)
   - Duration
   - Status badges
   - Action buttons

2. **Filters**
   - Status filter (completed, processing, deleted)
   - Date range filter (from/to)
   - Search across recordings

3. **Actions**
   - **Play** - Opens audio player modal to listen to recording
   - **Download MP3** - Downloads recording in MP3 format
   - **Download WAV** - Downloads recording in WAV format  
   - **Delete** - Removes recording from Telnyx
   - **Refresh from Telnyx** - Fetches latest recordings from Telnyx API

4. **Audio Player**
   - Modal popup with HTML5 audio player
   - Displays recording details
   - Autoplay enabled
   - Full playback controls

## Usage Examples

### Frontend: Fetch Recordings from Telnyx
```javascript
const response = await axios.get('/api/recordings', {
    params: {
        status: 'completed',
        per_page: 25,
        page: 1
    }
});
const recordings = response.data.recordings.data;
// Recordings are fetched directly from Telnyx API
```

### Frontend: Delete Recording from Telnyx
```javascript
const telnyxRecordingId = "3fa85f64-5717-4562-b3fc-2c963f66afa6";
await axios.delete(`/api/recordings/${telnyxRecordingId}`);
// Deletes from Telnyx API
```

### Backend: Get Call Recordings
```javascript
const response = await axios.get(`/api/calls/${callId}/recordings`);
// Fetches from Telnyx API filtered by call session ID
```

## Configuration

### Enable Call Recording in Telnyx

1. **Configure Connection**
   - Go to your Telnyx Mission Control Portal
   - Navigate to Call Control Applications
   - Enable "Record" for your application

2. **Set Webhook URL (Optional)**
   ```
   https://your-domain.com/webhook/call
   ```

3. **Enable Recording Events (Optional)**
   - Enable `call.recording.saved` event
   - Enable `recording.saved` event

### Recording Options

When initiating a call with recording:
```php
$call = $telnyxService->createCall([
    'from' => '+1234567890',
    'to' => '+0987654321',
    'record' => 'record-from-answer',  // Start recording when answered
    'record_channels' => 'dual',        // Record both channels
    'record_format' => 'mp3'            // Format: mp3 or wav
]);
```

Recording options:
- `record` - When to start recording:
  - `record-from-answer` - Start when call is answered
  - `record-from-ringing` - Start when phone starts ringing
- `record_channels` - Channel configuration:
  - `dual` - Record both sides separately
  - `single` - Mix both sides into one channel
- `record_format` - File format: `mp3` or `wav`

## Security & Permissions

### Access Control
- Users can only access recordings for their own calls
- Super-admin role can access all recordings
- Authorization is checked against the associated call's user_id

### Authorization Checks
All recording endpoints:
1. Fetch recording from Telnyx API
2. Find associated call in database
3. Verify user owns the call OR has super-admin role
4. Return 403 Unauthorized if access denied

## Benefits of Direct API Approach

### ✅ Advantages
1. **Always Up-to-Date** - No sync delays, always shows current state
2. **No Storage Overhead** - Don't need to store large recording metadata
3. **Single Source of Truth** - Telnyx is the authoritative source
4. **Automatic Sync** - No manual sync button needed
5. **Simpler Code** - Less database logic, fewer edge cases

### ⚠️ Considerations
1. **API Rate Limits** - Subject to Telnyx API rate limits
2. **Network Dependency** - Requires active internet connection
3. **Latency** - Slightly slower than database queries

## Troubleshooting

### Recordings Not Appearing

1. **Check Telnyx API Key**
   ```php
   // Verify in .env
   TELNYX_API_KEY=your_key_here
   ```

2. **Check API Response**
   ```bash
   tail -f storage/logs/laravel.log | grep "recording"
   ```

3. **Verify Recording is Enabled**
   - Check Telnyx Mission Control
   - Ensure recording is enabled for your connection

### Download URLs Not Working

1. **Check recording status**
   - Recording must be in "completed" status
   - Processing recordings don't have download URLs yet

2. **Verify API access**
   - Ensure your API key has recording permissions

### Permission Errors

1. **Verify call ownership**
   - Recordings are filtered by call ownership
   - User must own the associated call

2. **Check role permissions**
   ```php
   if (auth()->user()->hasRole('super-admin')) {
       // Admin can access all recordings
   }
   ```

## Testing

### Manual Testing

1. **Make a recorded call**
   - Use the dialer interface
   - Enable recording when initiating the call

2. **View recordings**
   - Navigate to `/recordings` in browser
   - Click "Refresh from Telnyx" to fetch latest

3. **Test playback**
   - Click play button on a completed recording
   - Audio should play in modal

### API Testing with cURL

List recordings from Telnyx:
```bash
curl -X GET "http://localhost/api/recordings" \
     -H "Accept: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN"
```

Get specific recording:
```bash
curl -X GET "http://localhost/api/recordings/TELNYX_RECORDING_ID" \
     -H "Accept: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN"
```

Delete recording:
```bash
curl -X DELETE "http://localhost/api/recordings/TELNYX_RECORDING_ID" \
     -H "Accept: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN"
```

## Future Enhancements

Potential features to add:
- [ ] Caching layer for faster repeated access
- [ ] Recording transcription integration
- [ ] Recording analytics dashboard
- [ ] Bulk download functionality
- [ ] Recording sharing via secure links
- [ ] Advanced search and filtering
- [ ] Recording quality indicators
- [ ] Cost tracking per recording

## Resources

- [Telnyx Call Recording Documentation](https://developers.telnyx.com/docs/voice/call-recording)
- [Telnyx API Reference - Recordings](https://developers.telnyx.com/docs/api/v2/call-recording/list-recordings)
- [Laravel HTTP Client](https://laravel.com/docs/http-client)
- [Vue.js Documentation](https://vuejs.org/)
- [Inertia.js Documentation](https://inertiajs.com/)

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review Telnyx API logs in Mission Control
3. Check application logs: `storage/logs/laravel.log`
4. Verify API key permissions in Telnyx portal
