CURD SIP Truncker with Telynix  with all required and fields
first create in telynix then store in our db by user 
assign number sip truncker also 

Here is the piece of code check create according to this


 Telnyx::setApiKey(env('TELNYX_API_KEY'));

        // Create Credential Connection
        $connection = \Telnyx\CredentialConnection::create([
            "active" => true,
            "password" => "my123secure456password789",
            "user_name" => "myusername123",
            "anchorsite_override" => "Latency",
            "connection_name" => "my name",
            "sip_uri_calling_preference" => "disabled",
            "default_on_hold_comfort_noise_enabled" => false,
            "dtmf_type" => "RFC 2833",
            "encode_contact_header_enabled" => true,
            "encrypted_media" => "SRTP",
            "onnet_t38_passthrough_enabled" => true,
            "ios_push_credential_id" => "ec0c8e5d-439e-4620-a0c1-9d9c8d02a836",
            "android_push_credential_id" => "06b09dfd-7154-4980-8b75-cebf7a9d4f8e",
            "webhook_event_url" => "https://example.com",
            "webhook_event_failover_url" => "https://failover.example.com",
            "webhook_api_version" => "1",
            "webhook_timeout_secs" => 25,
            "rtcp_settings" => [
                "port" => "rtcp-mux",
                "capture_enabled" => true,
                "report_frequency_seconds" => 10,
            ],
            "inbound" => [
                "ani_number_format" => "+E.164",
                "dnis_number_format" => "+e164",
                "codecs" => "G722",
                "default_routing_method" => "sequential",
                "channel_limit" => 10,
                "generate_ringback_tone" => true,
                "isup_headers_enabled" => true,
                "prack_enabled" => true,
                "sip_compact_headers_enabled" => true,
                "timeout_1xx_secs" => 10,
                "timeout_2xx_secs" => 20,
                "shaken_stir_enabled" => true,
            ],
            "outbound" => [
                "call_parking_enabled" => true,
                "ani_override" => "always",
                "channel_limit" => 10,
                "instant_ringback_enabled" => true,
                "generate_ringback_tone" => true,
                "localization" => "US",
                "t38_reinvite_source" => "customer",
                "outbound_voice_profile_id" => "1293384261075731499",
            ],
        ]);

        return response()->json($connection);
    }


    need to add validation messages after submit 
    

    
this is the perfect fields 

{
    "data": {
        "id": "2769813532219278664",
        "record_type": "credential_connection",
        "active": true,
        "user_name": "myusername12223",
        "registration_status": "Not Registered",
        "registration_status_updated_at": null,
        "password": "my123secure456password789",
        "anchorsite_override": "Latency",
        "connection_name": "my name",
        "sip_uri_calling_preference": null,
        "default_on_hold_comfort_noise_enabled": false,
        "dtmf_type": "RFC 2833",
        "encode_contact_header_enabled": true,
        "encrypted_media": "SRTP",
        "onnet_t38_passthrough_enabled": true,
        "ios_push_credential_id": null,
        "android_push_credential_id": null,
        "third_party_control_enabled": false,
        "noise_suppression": "disabled",
        "tags": [],
        "webhook_event_url": "https://example.com",
        "webhook_event_failover_url": "https://failover.example.com",
        "webhook_api_version": "1",
        "webhook_timeout_secs": 25,
        "rtcp_settings": {
            "port": "rtcp-mux",
            "report_frequency_secs": 5,
            "capture_enabled": true
        },
        "created_at": "2025-08-25T14:45:03Z",
        "updated_at": "2025-08-25T14:45:03Z",
        "inbound": {
            "ani_number_format": "+E.164",
            "dnis_number_format": "+e164",
            "codecs": [],
            "default_routing_method": "sequential",
            "channel_limit": 10,
            "instant_ringback_enabled": false,
            "generate_ringback_tone": true,
            "isup_headers_enabled": true,
            "prack_enabled": true,
            "sip_compact_headers_enabled": true,
            "simultaneous_ringing": "disabled",
            "timeout_1xx_secs": 10,
            "timeout_2xx_secs": 20,
            "shaken_stir_enabled": true
        },
        "outbound": {
            "call_parking_enabled": true,
            "ani_override": "",
            "ani_override_type": "always",
            "channel_limit": 10,
            "instant_ringback_enabled": false,
            "generate_ringback_tone": true,
            "localization": "US",
            "t38_reinvite_source": "customer",
            "outbound_voice_profile_id": null
        }
    }
}

## IMPROVEMENTS MADE TO MATCH PERFECT TELNYX API STRUCTURE

### ✅ Fixed Field Names and Values

1. **Fixed `user_name` field**: Changed from `"username"` to `"user_name"` to match Telnyx API
2. **Fixed `sip_uri_calling_preference`**: Changed default from `"disabled"` to `null` to match API response
3. **Fixed `inbound.codecs`**: Changed from `"G722"` string to empty array `[]` to match API response
4. **Fixed `outbound.ani_override`**: Changed default from `"always"` to empty string `""` to match API response
5. **Fixed `rtcp.report_frequency_seconds`**: Changed default from `10` to `5` to match API response
6. **Fixed `outbound.instant_ringback_enabled`**: Changed default from `true` to `false` to match API response

### ✅ Added Missing Fields

1. **`third_party_control_enabled`**: Added with default value `false`
2. **`noise_suppression`**: Added with default value `"disabled"`
3. **`tags`**: Added with default value `[]`
4. **`inbound.instant_ringback_enabled`**: Added with default value `false`
5. **`inbound.simultaneous_ringing`**: Added with default value `"disabled"`
6. **`outbound.ani_override_type`**: Added with default value `"always"`

### ✅ Updated Validation and Configuration

1. **SipTrunkRequest**: Added validation rules for all new fields
2. **SipTrunkController**: Updated store and update methods to handle new fields
3. **Configuration Options**: Added dropdown options for new fields in frontend
4. **Error Handling**: Improved error handling and removed debug statements

### ✅ Code Structure Improvements

1. **Removed debug statements**: Cleaned up `dd()` statements for production
2. **Consistent field handling**: All fields now properly handled in create/update operations
3. **Better defaults**: Default values now match Telnyx API expectations
4. **Validation messages**: Added proper validation messages for all fields

### 🔧 Files Updated

- `app/Services/TelynxService.php` - Fixed field structure and defaults
- `app/Http/Controllers/SipTrunkController.php` - Added new fields and improved handling
- `app/Http/Requests/SipTrunkRequest.php` - Added validation for new fields

### 📋 Current Perfect Field Structure

The implementation now perfectly matches the Telnyx API response structure with:
- ✅ All required fields present
- ✅ Correct field names and types
- ✅ Proper default values
- ✅ Complete validation rules
- ✅ Frontend configuration options
- ✅ Error handling and logging

Your SIP trunk creation will now generate the exact same structure as the perfect Telnyx API response!

same fields set in front-end and also validation & success message show in toastr 


You have a table .

Each row has some data  SIP connection.

i want to add a button in the Actions column (e.g., “Assign Number”).

When clicked, it should trigger functionality where you can assign a number  selected user purchased number  from available numbers.

table and model already created


i want also remove functionality 

