<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SipTrunkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'webhook_url' => 'nullable|url',
            'notes' => 'nullable|string|max:1000',
            
            // Basic credentials
            'password' => 'nullable|string|min:8|max:255',
            'user_name' => 'nullable|string|max:255',
            
            // Advanced settings
            'anchorsite_override' => 'nullable|in:Latency,ASN,Country',
            'sip_uri_calling_preference' => 'nullable|in:disabled,enabled',
            'default_on_hold_comfort_noise_enabled' => 'nullable|boolean',
            'dtmf_type' => 'nullable|in:RFC 2833,RFC 4733,INFO',
            'encode_contact_header_enabled' => 'nullable|boolean',
            'encrypted_media' => 'nullable|in:SRTP,SRTP_FEC,SRTP_FEC_OPTIONAL',
            'onnet_t38_passthrough_enabled' => 'nullable|boolean',
            'third_party_control_enabled' => 'nullable|boolean',
            'noise_suppression' => 'nullable|in:disabled,low,medium,high',
            
            // Webhook settings
            'webhook_failover_url' => 'nullable|url',
            'webhook_api_version' => 'nullable|string|in:1,2',
            'webhook_timeout_secs' => 'nullable|integer|min:1|max:300',
            
            // RTCP settings
            'rtcp_port' => 'nullable|string|in:rtcp-mux,rtcp-mux-only',
            'rtcp_capture_enabled' => 'nullable|boolean',
            'rtcp_report_frequency' => 'nullable|integer|min:1|max:60',
            
            // Inbound settings
            'inbound_ani_format' => 'nullable|string|in:+E.164,+E164,E.164,E164',
            'inbound_dnis_format' => 'nullable|string|in:+e164,+E.164,e164,E.164',
            'inbound_codecs' => 'nullable|array',
            'inbound_codecs.*' => 'string|in:G722,G711U,G711A,G729,OPUS,H.264,VP8,AMR-WB',
            'inbound_routing_method' => 'nullable|string|in:sequential,round-robin,weighted',
            'inbound_channel_limit' => 'nullable|integer|min:1|max:100',
            'inbound_instant_ringback' => 'nullable|boolean',
            'inbound_ringback_tone' => 'nullable|boolean',
            'inbound_isup_headers' => 'nullable|boolean',
            'inbound_prack' => 'nullable|boolean',
            'inbound_sip_compact_headers' => 'nullable|boolean',
            'inbound_simultaneous_ringing' => 'nullable|in:disabled,enabled',
            'inbound_timeout_1xx' => 'nullable|integer|min:1|max:60',
            'inbound_timeout_2xx' => 'nullable|integer|min:1|max:60',
            'inbound_shaken_stir' => 'nullable|boolean',
            
            // Outbound settings
            'outbound_call_parking' => 'nullable|boolean',
            'outbound_ani_override' => 'nullable|string|in:always,never,conditional',
            'outbound_ani_override_type' => 'nullable|string|in:always,never,conditional',
            'outbound_channel_limit' => 'nullable|integer|min:1|max:100',
            'outbound_instant_ringback' => 'nullable|boolean',
            'outbound_ringback_tone' => 'nullable|boolean',
            'outbound_localization' => 'nullable|string|in:US,CA,GB,AU,DE,FR,ES,IT,NL,BE',
            'outbound_codecs' => 'nullable|array',
            'outbound_codecs.*' => 'string|in:G722,G711U,G711A,G729,OPUS,H.264,VP8,AMR-WB',
            'outbound_t38_reinvite_source' => 'nullable|string|in:customer,telnyx',
            
            // Optional credentials
            'ios_push_credential_id' => 'nullable|string|uuid',
            'android_push_credential_id' => 'nullable|string|uuid',
            'outbound_voice_profile_id' => 'nullable|string',
            
            // Phone number assignments
            'phone_numbers' => 'nullable|array',
            'phone_numbers.*.phone_number_id' => 'required_with:phone_numbers|exists:phone_numbers,id',
                    'phone_numbers.*.assignment_type' => 'required_with:phone_numbers|in:primary,secondary,backup',
    ];

    return $rules;
}

/**
 * Get custom messages for validator errors.
 */
public function messages(): array
{
    return [
        'name.required' => 'SIP trunk name is required.',
            'webhook_url.url' => 'Webhook URL must be a valid URL.',
            'password.min' => 'Password must be at least 8 characters long.',
            'webhook_timeout_secs.min' => 'Webhook timeout must be at least 1 second.',
            'webhook_timeout_secs.max' => 'Webhook timeout cannot exceed 300 seconds.',
            'inbound_channel_limit.min' => 'Inbound channel limit must be at least 1.',
            'inbound_channel_limit.max' => 'Inbound channel limit cannot exceed 100.',
            'outbound_channel_limit.min' => 'Outbound channel limit must be at least 1.',
            'outbound_channel_limit.max' => 'Outbound channel limit cannot exceed 100.',
            'rtcp_report_frequency.min' => 'RTCP report frequency must be at least 1 second.',
            'rtcp_report_frequency.max' => 'RTCP report frequency cannot exceed 60 seconds.',
            'inbound_timeout_1xx.min' => '1xx timeout must be at least 1 second.',
            'inbound_timeout_1xx.max' => '1xx timeout cannot exceed 60 seconds.',
            'inbound_timeout_2xx.min' => '2xx timeout must be at least 1 second.',
            'inbound_timeout_2xx.max' => '2xx timeout cannot exceed 60 seconds.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
            return [
        'name' => 'SIP trunk name',
        'webhook_url' => 'webhook URL',
            'password' => 'password',
            'user_name' => 'username',
            'anchorsite_override' => 'anchor site override',
            'sip_uri_calling_preference' => 'SIP URI calling preference',
            'default_on_hold_comfort_noise_enabled' => 'comfort noise on hold',
            'dtmf_type' => 'DTMF type',
            'encode_contact_header_enabled' => 'contact header encoding',
            'encrypted_media' => 'encrypted media',
            'onnet_t38_passthrough_enabled' => 'T38 passthrough',
            'third_party_control_enabled' => 'third party control',
            'noise_suppression' => 'noise suppression',
            'webhook_failover_url' => 'webhook failover URL',
            'webhook_api_version' => 'webhook API version',
            'webhook_timeout_secs' => 'webhook timeout',
            'rtcp_port' => 'RTCP port',
            'rtcp_capture_enabled' => 'RTCP capture',
            'rtcp_report_frequency' => 'RTCP report frequency',
            'inbound_ani_format' => 'inbound ANI format',
            'inbound_dnis_format' => 'inbound DNIS format',
            'inbound_codecs' => 'inbound codecs',
            'inbound_routing_method' => 'inbound routing method',
            'inbound_channel_limit' => 'inbound channel limit',
            'inbound_instant_ringback' => 'inbound instant ringback',
            'inbound_ringback_tone' => 'inbound ringback tone',
            'inbound_isup_headers' => 'inbound ISUP headers',
            'inbound_prack' => 'inbound PRACK',
            'inbound_sip_compact_headers' => 'inbound SIP compact headers',
            'inbound_simultaneous_ringing' => 'inbound simultaneous ringing',
            'inbound_timeout_1xx' => '1xx timeout',
            'inbound_timeout_2xx' => '2xx timeout',
            'inbound_shaken_stir' => 'inbound SHAKEN/STIR',
            'outbound_call_parking' => 'outbound call parking',
            'outbound_ani_override' => 'outbound ANI override',
            'outbound_ani_override_type' => 'outbound ANI override type',
            'outbound_channel_limit' => 'outbound channel limit',
            'outbound_instant_ringback' => 'outbound instant ringback',
            'outbound_ringback_tone' => 'outbound ringback tone',
            'outbound_localization' => 'outbound localization',
            'outbound_codecs' => 'outbound codecs',
            'outbound_t38_reinvite_source' => 'outbound T38 reinvite source',
        ];
    }
}
