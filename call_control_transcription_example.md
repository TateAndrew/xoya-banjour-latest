# Call Control Transcription Implementation

## Updated Implementation

I've updated the transcription system to use the **Telnyx Call Control API** instead of the standalone transcription API, which matches your curl example.

## API Endpoints

### Start Transcription
```bash
curl -X POST /api/transcription/start \
  -H "Content-Type: application/json" \
  -d '{
    "call_control_id": "v3:fxMQD-u7B-EsOX5giZKrb5A3tHNnORCnaK5fcByTiS1iTW1F7-Etbg",
    "language": "en",
    "transcription_engine": "A/B",
    "client_state": "aGF2ZSBhIG5pY2UgZGF5ID1d",
    "command_id": "891510ac-f3e4-11e8-af5b-de00688a4901"
  }'
```

### Stop Transcription
```bash
curl -X POST /api/transcription/stop \
  -H "Content-Type: application/json" \
  -d '{
    "call_control_id": "v3:fxMQD-u7B-EsOX5giZKrb5A3tHNnORCnaK5fcByTiS1iTW1F7-Etbg",
    "client_state": "optional_state",
    "command_id": "stop_command_id"
  }'
```

## Webhook Handling

The system now properly handles the `call.transcription` webhook event that you showed in your example:

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

## Key Features

### 1. Real-time Transcription Updates
- Handles `call.transcription` webhook events
- Updates transcript text in real-time as speech is recognized
- Tracks confidence scores and finality status

### 2. Call Control API Integration
- Uses `/calls/{call_control_id}/actions/transcription_start` endpoint
- Uses `/calls/{call_control_id}/actions/transcription_stop` endpoint
- Supports all Call Control API parameters

### 3. Enhanced Data Storage
- Stores complete transcript text
- Tracks confidence scores
- Records finality status (`is_final`)
- Maintains timestamps for each update

## Database Updates

The `call_transcripts` table now stores:
- **Real-time updates**: Each webhook event updates the transcript
- **Confidence tracking**: Stores confidence scores from Telnyx
- **Finality status**: Tracks whether transcript is final or interim
- **Complete history**: Maintains full transcript with timestamps

## Usage Flow

1. **Start Call**: Call webhook creates call record
2. **Start Transcription**: Use `/api/transcription/start` with `call_control_id`
3. **Real-time Updates**: `call.transcription` webhooks update transcript
4. **Stop Transcription**: Use `/api/transcription/stop` when call ends
5. **Retrieve Transcript**: Use `/api/transcription/get` to get final transcript

## Example Response

When you call the start transcription API, you'll get:

```json
{
  "success": true,
  "message": "Transcription started successfully",
  "data": {
    "transcript_id": 123,
    "call_control_id": "v3:fxMQD-u7B-EsOX5giZKrb5A3tHNnORCnaK5fcByTiS1iTW1F7-Etbg",
    "status": "started",
    "language": "en",
    "command_id": "891510ac-f3e4-11e8-af5b-de00688a4901"
  }
}
```

## Webhook Configuration

Make sure your Telnyx webhook is configured to send `call.transcription` events to:
- **Primary**: `https://yourdomain.com/webhook/transcription`
- **Failover**: `https://yourdomain.com/webhook/transcription-failover`

The system will automatically process these events and update the transcript in real-time!
