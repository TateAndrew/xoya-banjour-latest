# Call Recording Settings for Phone Numbers

This guide explains how to use the call recording settings feature for purchased phone numbers.

## Overview

You can now configure inbound call recording settings for each purchased phone number. This feature allows you to:
- Enable or disable call recording
- Choose the recording format (WAV or MP3)
- Select the recording channels (single or dual)

## Database Schema

The following columns have been added to the `phone_numbers` table:

```sql
- inbound_call_recording_enabled (boolean, default: false)
- inbound_call_recording_format (string, default: 'wav')
- inbound_call_recording_channels (string, default: 'single')
```

## How to Access

### Via Web Interface

1. **Navigate to Phone Numbers Management**
   - Go to `/phone-numbers/manage`
   - You'll see all your purchased phone numbers

2. **Access Recording Settings**
   - Click on "Recording Settings" link in the footer of any phone number card
   - Or navigate directly to `/phone-numbers/{id}/recording-settings`

3. **Configure Settings**
   - **Recording Status**: Enable or Disable call recording
   - **Recording Format**: 
     - **WAV**: Uncompressed, higher quality, larger file size
     - **MP3**: Compressed, good quality, smaller file size
   - **Recording Channels**:
     - **Single**: Both parties mixed into one audio track
     - **Dual**: Each party recorded on separate channels for easier analysis

4. **Save Changes**
   - Click "Save Settings" to apply your changes
   - Settings are synced with Telnyx API and saved locally

## API Routes

### View Recording Settings Page
```
GET /phone-numbers/{phoneNumber}/recording-settings
```

### Update Recording Settings
```
PUT /phone-numbers/{phoneNumber}/recording-settings
```

**Request Body:**
```json
{
  "inbound_call_recording_enabled": true,
  "inbound_call_recording_format": "mp3",
  "inbound_call_recording_channels": "dual"
}
```

## Validation Rules

- `inbound_call_recording_enabled`: Required, must be boolean
- `inbound_call_recording_format`: Required, must be either "wav" or "mp3"
- `inbound_call_recording_channels`: Required, must be either "single" or "dual"

## Important Notes

1. **Legal Compliance**: Call recordings may be subject to legal requirements in your jurisdiction. Ensure you have proper consent before recording calls.

2. **Telnyx Integration**: Settings are automatically synced with Telnyx when you save changes. If the Telnyx API update fails, you'll see an error message.

3. **Immediate Effect**: Changes take effect immediately for new calls.

4. **Inbound Only**: These settings apply only to **inbound** calls to the phone number.

## Files Created/Modified

### New Files
- `database/migrations/2025_10_07_155816_add_recording_settings_to_phone_numbers_table.php`
- `resources/js/Pages/PhoneNumbers/EditRecordingSettings.vue`

### Modified Files
- `app/Models/PhoneNumber.php` - Added fillable fields and casts
- `app/Services/TelynxService.php` - Added `updatePhoneNumberRecordingSettings()` method
- `app/Http/Controllers/PhoneNumbersController.php` - Added `editRecordingSettings()` and `updateRecordingSettings()` methods
- `routes/web.php` - Added routes for recording settings
- `resources/js/Pages/PhoneNumbers/Manage.vue` - Added "Recording Settings" link

## Telnyx API Reference

This feature uses the Telnyx API endpoint:
```
PATCH /phone_numbers/:id/voice
```

Documentation: https://developers.telnyx.com/api/numbers/update-phone-number-voice-settings

## Future Enhancements

Consider adding:
- Outbound call recording settings
- Recording storage location configuration
- Automatic recording transcription settings
- Recording retention policies

