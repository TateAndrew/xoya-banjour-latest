<?php

namespace App\Services;

use Telnyx\Telnyx;
use Telnyx\Message;
use Telnyx\PhoneNumber;
use Telnyx\PhoneNumberOrder;
use Telnyx\CredentialConnection;
use Telnyx\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

class TelynxService
{
    public function __construct()
    {
        // Initialize Telnyx with API key from environment
        Telnyx::setApiKey(config('services.telnyx.api_key'));
    }

    /** 
     * Search for available phone numbers
     */
    public function searchNumbers(array $filters = []): array
    {
        try {
            $params = [
                'country_code' => $filters['country_code'] ?? 'US',
                'features' => $filters['features'] ?? ['voice', 'sms'],
                'limit' => $filters['limit'] ?? 20,
            ];

            // Add area code if provided
            if (!empty($filters['area_code'])) {
                $params['area_code'] = $filters['area_code'];
            }

            // Use AvailablePhoneNumber to find available numbers
            $phoneNumbers = \Telnyx\AvailablePhoneNumber::all($params);

            // Process and format the results
            $formattedNumbers = [];
            foreach ($phoneNumbers->data as $number) {
                $formattedNumbers[] = [
                    'phone_number' => $number->phone_number,
                    'phone_number_type' => $number->phone_number_type ?? 'local',
                    'country_code' => $number->country_code,
                    'area_code' => $number->area_code ?? null,
                    'city' => $number->city ?? null,
                    'state' => $number->state ?? null,
                    'carrier' => $number->carrier ?? null,
                    'features' => $number->features ?? [],
                    'cost_information' => [
                        'monthly_cost' => $number->cost_information->monthly_cost ?? 0,
                        'upfront_cost' => $number->cost_information->upfront_cost ?? 0,
                        'currency' => $number->cost_information->currency ?? 'USD'
                    ],
                    'metadata' => $number->metadata ?? []
                ];
            }

            return [
                'success' => true,
                'data' => $formattedNumbers,
                'total' => count($formattedNumbers)
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Purchase a phone number
     */
    public function purchaseNumber(string $phoneNumber, array $options = []): array
    {
        try {
            $params = array_merge([
                'phone_number' => $phoneNumber,
                'connection_id' => $options['connection_id'] ?? null,
                'messaging_product_id' => $options['messaging_product_id'] ?? null,
                'voice_product_id' => $options['voice_product_id'] ?? null,
            ], $options);

            $purchasedNumber = PhoneNumber::create($params);

            return [
                'success' => true,
                'data' => $purchasedNumber,
                'phone_number' => $purchasedNumber->phone_number,
                'id' => $purchasedNumber->id
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Purchase Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a phone number order
     */
    public function createOrder(array $phoneNumbers, array $options = []): array
    {
        try {
            $params = array_merge([
                'phone_numbers' => $phoneNumbers,
                'connection_id' => $options['connection_id'] ?? null,
                'messaging_product_id' => $options['messaging_product_id'] ?? null,
                'voice_product_id' => $options['voice_product_id'] ?? null,
            ], $options);

            $order = PhoneNumberOrder::create($params);

            return [
                'success' => true,
                'data' => $order,
                'order_id' => $order->id,
                'status' => $order->status
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Order Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get phone number details
     */
    public function getNumberDetails(string $phoneNumberId): array
    {
        try {
            $number = PhoneNumber::retrieve($phoneNumberId);

            return [
                'success' => true,
                'data' => $number
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Retrieve Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update phone number settings
     */
    public function updateNumber(string $phoneNumberId, array $updates): array
    {
        try {
            $number = PhoneNumber::update($phoneNumberId, $updates);

            return [
                'success' => true,
                'data' => $number
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Update Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete/Release a phone number
     */
    public function deleteNumber(string $phoneNumberId): array
    {
        try {
            $number = PhoneNumber::retrieve($phoneNumberId);
            $number->delete();

            return [
                'success' => true,
                'message' => 'Phone number deleted successfully'
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Delete Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get available countries
     */
    public function getAvailableCountries(): array
    {
        try {
            // Use the AvailablePhoneNumber endpoint to get countries
            $phoneNumbers = \Telnyx\AvailablePhoneNumber::all([
                'country_code' => 'US',
                'limit' => 1
            ]);

            // Return a list of common countries since Telnyx doesn't have a direct countries endpoint
            $countries = [
                ['country_code' => 'US', 'name' => 'United States'],
                ['country_code' => 'CA', 'name' => 'Canada'],
                ['country_code' => 'GB', 'name' => 'United Kingdom'],
                ['country_code' => 'AU', 'name' => 'Australia'],
                ['country_code' => 'DE', 'name' => 'Germany'],
                ['country_code' => 'FR', 'name' => 'France'],
                ['country_code' => 'ES', 'name' => 'Spain'],
                ['country_code' => 'IT', 'name' => 'Italy'],
                ['country_code' => 'NL', 'name' => 'Netherlands'],
                ['country_code' => 'BE', 'name' => 'Belgium']
            ];

            return [
                'success' => true,
                'data' => $countries
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Countries Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Get available area codes for a country
     */
    public function getAvailableAreaCodes(string $countryCode = 'US'): array
    {
        try {
            // For US, return common area codes
            if ($countryCode === 'US') {
                $areaCodes = [
                    ['area_code' => '212', 'city' => 'New York', 'state' => 'NY'],
                    ['area_code' => '310', 'city' => 'Los Angeles', 'state' => 'CA'],
                    ['area_code' => '312', 'city' => 'Chicago', 'state' => 'IL'],
                    ['area_code' => '305', 'city' => 'Miami', 'state' => 'FL'],
                    ['area_code' => '713', 'city' => 'Houston', 'state' => 'TX'],
                    ['area_code' => '215', 'city' => 'Philadelphia', 'state' => 'PA'],
                    ['area_code' => '602', 'city' => 'Phoenix', 'state' => 'AZ'],
                    ['area_code' => '408', 'city' => 'San Jose', 'state' => 'CA'],
                    ['area_code' => '404', 'city' => 'Atlanta', 'state' => 'GA'],
                    ['area_code' => '206', 'city' => 'Seattle', 'state' => 'WA']
                ];
            } else {
                // For other countries, return empty array or generic area codes
                $areaCodes = [];
            }

            return [
                'success' => true,
                'data' => $areaCodes
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Area Codes Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Get phone number pricing
     */
    public function getPricing(string $countryCode = 'US'): array
    {
        try {
            $pricing = \Telnyx\PhoneNumber::all([
                'country_code' => $countryCode,
                'limit' => 1
            ]);

            return [
                'success' => true,
                'data' => $pricing->data
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Pricing Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Create a call
     */
    public function createCall(array $callData): array
    {
        try {
            $params = [
                'from' => $callData['from'],
                'to' => $callData['to'],
                'connection_id' => $callData['sip_trunk_id'],
                'webhook_url' => config('app.url') . '/webhook/telnyx',
                'webhook_api_version' => 'v2',
            ];

            // Add call type specific parameters
            if ($callData['call_type'] === 'video') {
                $params['media_encoding_name'] = 'VP8';
                $params['media_encoding_name'] = 'OPUS';
            }

            $call = \Telnyx\Call::create($params);
            return [
                'success' => true,
                'data' => $call,
                'call_id' => $call->id
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Call Creation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * End a call
     */
    public function endCall(string $callId): array
    {
        try {
            $call = \Telnyx\Call::update($callId, ['status' => 'hangup']);

            return [
                'success' => true,
                'data' => $call
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Call End Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get call details
     */
    public function getCallDetails(string $callId): array
    {
        try {
            $call = \Telnyx\Call::retrieve($callId);

            return [
                'success' => true,
                'data' => $call
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Call Details Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a conference
     */
    public function createConference(array $conferenceData): array
    {
        try {
            $params = [
                'name' => $conferenceData['title'] ?? 'Conference',
                'max_participants' => $conferenceData['max_participants'] ?? 10,
                'call_control_id' => $conferenceData['call_control_id'] ?? null,
            ];

            $conference = \Telnyx\Conference::create($params);

            return [
                'success' => true,
                'data' => $conference,
                'conference_id' => $conference->id
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Conference Creation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Join a conference
     */
    public function joinConference(string $conferenceId, array $participantData): array
    {
        try {
            $params = [
                'call_control_id' => $participantData['call_control_id'],
                'client_state' => $participantData['client_state'] ?? '',
            ];

            $conference = \Telnyx\Conference::retrieve($conferenceId);
            $participant = $conference->join($params);

            return [
                'success' => true,
                'data' => $participant
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Conference Join Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get SIP credentials
     */
    public function getSipCredentials(): array
    {
        try {
            $credentials = \Telnyx\CredentialConnection::all();

            return [
                'success' => true,
                'data' => $credentials->data
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx SIP Credentials Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Helper method to ensure boolean values are properly formatted
     */
    private function ensureBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['true', '1', 'yes', 'on']);
        }
        
        if (is_numeric($value)) {
            return (bool)$value;
        }
        
        return false;
    }

    /**
     * Create a SIP trunk connection using Telnyx CredentialConnection
     */
    public function createSipTrunk(array $data): array
    {
        try {
            $connectionData = [
                // "active" => true,
                "password" => $data['password'] ?? "my123secure456password789",
                "user_name" => $data['user_name'] ?? "myusername123", // Fixed: Telnyx expects "user_name"
                "anchorsite_override" => $data['anchorsite_override'] ?? "Latency",
                "connection_name" => $data['name'] ?? "SIP Trunk",
                "sip_uri_calling_preference" => $data['sip_uri_calling_preference'] ?? null, // Fixed: should be null by default
                // "default_on_hold_comfort_noise_enabled" => $data['default_on_hold_comfort_noise_enabled'] ?? false,
                "dtmf_type" => $data['dtmf_type'] ?? "RFC 2833",
                // "encode_contact_header_enabled" => $data['encode_contact_header_enabled'] ?? true,
                "encrypted_media" => $data['encrypted_media'] ?? "SRTP",
                // "onnet_t38_passthrough_enabled" => $data['onnet_t38_passthrough_enabled'] ?? true,
                // "ios_push_credential_id" => $data['ios_push_credential_id'] ?? null,
                // "android_push_credential_id" => $data['android_push_credential_id'] ?? null,
                "third_party_control_enabled" => $data['third_party_control_enabled'] ?? false, // Added missing field
                "noise_suppression" => $data['noise_suppression'] ?? "disabled", // Added missing field
                "tags" => $data['tags'] ?? [], // Added missing field
                "webhook_event_url" => $data['webhook_url'] ?? config('app.url') . '/api/telnyx/webhook',
                "webhook_event_failover_url" => $data['webhook_failover_url'] ?? null,
                "webhook_api_version" => $data['webhook_api_version'] ?? "1",
                "webhook_timeout_secs" => (int)($data['webhook_timeout_secs'] ?? 25),
                "rtcp_settings" => [
                    "port" => $data['rtcp_port'] ?? "rtcp-mux",
                    // "capture_enabled" => $data['rtcp_capture_enabled'] ?? true,
                    "report_frequency_seconds" => (int)($data['rtcp_report_frequency'] ?? 5), // Fixed: should be 5 by default
                ],
                "inbound" => [
                    "ani_number_format" => $data['inbound_ani_format'] ?? "+E.164",
                    "dnis_number_format" => $data['inbound_dnis_format'] ?? "+e164",
                    "codecs" => is_array($data['inbound_codecs'] ?? []) ? ($data['inbound_codecs'] ?? []) : [], // Fixed: should be empty array by default
                    "default_routing_method" => $data['inbound_routing_method'] ?? "sequential",
                    "channel_limit" => (int)($data['inbound_channel_limit'] ?? 10),
                    // "instant_ringback_enabled" => $this->ensureBoolean($data['inbound_instant_ringback'] ?? false), // Added missing field
                    // "generate_ringback_tone" => $this->ensureBoolean($data['inbound_ringback_tone'] ?? true),
                    // "isup_headers_enabled" => $data['inbound_isup_headers'] ?? true,
                    // "prack_enabled" => $data['inbound_prack'] ?? true,
                    // "sip_compact_headers_enabled" => $data['inbound_sip_compact_headers'] ?? true,
                    "simultaneous_ringing" => $data['inbound_simultaneous_ringing'] ?? "disabled", // Added missing field
                    "timeout_1xx_secs" => (int)($data['inbound_timeout_1xx'] ?? 10),
                    "timeout_2xx_secs" => (int)($data['inbound_timeout_2xx'] ?? 20),
                    "shaken_stir_enabled" => $data['inbound_shaken_stir'] ?? true,
                ],
                "outbound" => [
                    // "call_parking_enabled" => $data['outbound_call_parking'] ?? true,
                    "ani_override" => $data['outbound_ani_override'] ?? "", // Fixed: should be empty string by default
                    "ani_override_type" => $data['outbound_ani_override_type'] ?? "always", // Added missing field
                    "channel_limit" => (int)($data['outbound_channel_limit'] ?? 10),
                    // "instant_ringback_enabled" => $this->ensureBoolean($data['outbound_instant_ringback'] ?? false), // Fixed: should be false by default
                    // "generate_ringback_tone" => $this->ensureBoolean($data['outbound_ringback_tone'] ?? true),
                    "localization" => $data['outbound_localization'] ?? "US",
                    "t38_reinvite_source" => $data['outbound_t38_reinvite_source'] ?? "customer",
                    // "outbound_voice_profile_id" => $data['outbound_voice_profile_id'] ?? null,
                ],
            ];

            // Optional fields - only add if they have values
            if (!empty($data['ios_push_credential_id'])) {
                $connectionData["ios_push_credential_id"] = $data['ios_push_credential_id'];
            }
            if (!empty($data['android_push_credential_id'])) {
                $connectionData["android_push_credential_id"] = $data['android_push_credential_id'];
            }
            if (!empty($data['outbound_voice_profile_id'])) {
                $connectionData["outbound"]["outbound_voice_profile_id"] = $data['outbound_voice_profile_id'];
            }
            // dd("connectionData",json_encode($connectionData));
            // Create the credential connection
            $connection = CredentialConnection::create($connectionData);
            return [
                'success' => true,
                'connection_id' => $connection->id,
                'data' => $connection,
                'sip_uri' => $connection->sip_uri ?? null,
                'status' => $connection->status ?? 'active'
            ];
        } catch (ApiErrorException $e) {
            // Parse Telnyx API error response for details
            $body = $e->getHttpBody();
            $decoded = json_decode($body, true);

            Log::error('Telnyx SIP Trunk Creation API Error', [
                'message' => $e->getMessage(),
                'http_body' => $decoded,
            ]);
            
            return [
                'success' => false,
                'error' => $decoded['errors'] ?? $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('Telnyx SIP Trunk Creation Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }


    /**
     * Update a SIP trunk connection
     */
    public function updateSipTrunk(string $connectionId, array $data): array
    {
        try {
            $updateData = [];

            // Only include fields that are provided
            if (isset($data['name'])) {
                $updateData['connection_name'] = $data['name'];
            }
            if (isset($data['password'])) {
                $updateData['password'] = $data['password'];
            }
            if (isset($data['user_name'])) {
                $updateData['user_name'] = $data['user_name'];
            }
            if (isset($data['webhook_url'])) {
                $updateData['webhook_event_url'] = $data['webhook_url'];
            }
            if (isset($data['active'])) {
                $updateData['active'] = $data['active'];
            }

            $connection = CredentialConnection::update($connectionId, $updateData);

            return [
                'success' => true,
                'data' => $connection,
                'connection_id' => $connection->id
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx SIP Trunk Update Error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete a SIP trunk connection
     */
    public function deleteSipTrunk(string $connectionId): array
    {
        try {
            $connection = CredentialConnection::retrieve($connectionId);
            $connection->delete();

            return [
                'success' => true,
                'message' => 'SIP trunk connection deleted successfully'
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx SIP Trunk Deletion Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get SIP trunk connection details
     */
    public function getSipTrunkDetails(string $connectionId): array
    {
        try {
            $connection = CredentialConnection::retrieve($connectionId);

            return [
                'success' => true,
                'data' => $connection,
                'connection_id' => $connection->id,
                'status' => $connection->status ?? 'unknown',
                'connection_name' => $connection->connection_name ?? 'Unknown',
                'sip_uri' => $connection->sip_uri ?? null,
                'created_at' => $connection->created_at ?? null,
                'updated_at' => $connection->updated_at ?? null
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx SIP Trunk Details Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test SIP trunk connection
     */
    public function testSipTrunkConnection(string $connectionId): array
    {
        try {
            $connection = CredentialConnection::retrieve($connectionId);

            // Check if connection is active
            $isActive = $connection->active ?? false;
            $status = $connection->status ?? 'unknown';

            return [
                'success' => true,
                'is_active' => $isActive,
                'status' => $status,
                'connection_id' => $connection->id,
                'tested_at' => now()->toISOString(),
                'message' => $isActive ? 'Connection is active and responding' : 'Connection is not active'
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx SIP Trunk Test Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List all SIP trunk connections
     */
    public function listSipTrunks(): array
    {
        try {
            $connections = CredentialConnection::all(['limit' => 100]);

            $formattedConnections = [];
            foreach ($connections->data as $connection) {
                $formattedConnections[] = [
                    'id' => $connection->id,
                    'connection_name' => $connection->connection_name ?? 'Unnamed',
                    'status' => $connection->status ?? 'unknown',
                    'active' => $connection->active ?? false,
                    'sip_uri' => $connection->sip_uri ?? null,
                    'created_at' => $connection->created_at ?? null,
                    'updated_at' => $connection->updated_at ?? null
                ];
            }

            return [
                'success' => true,
                'data' => $formattedConnections,
                'total' => count($formattedConnections)
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx SIP Trunk List Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Create a new messaging profile
     */
    public function createMessagingProfile(array $data): array
    {
        try {
            $params = [
                'name' => $data['name'],
                'whitelisted_destinations' => $data['whitelisted_destinations'] ?? ['*'],
                'enabled' => $data['enabled'] ?? true,
                'webhook_url' => $data['webhook_url'] ?? null,
                'webhook_failover_url' => $data['webhook_failover_url'] ?? null,
                'webhook_api_version' => $data['webhook_api_version'] ?? '2',
            ];

            // Add optional fields if provided
            if (!empty($data['number_pool_settings'])) {
                $params['number_pool_settings'] = $data['number_pool_settings'];
            }
            if (!empty($data['url_shortener_settings'])) {
                $params['url_shortener_settings'] = $data['url_shortener_settings'];
            }
            if (!empty($data['alpha_sender'])) {
                $params['alpha_sender'] = $data['alpha_sender'];
            }
            if (!empty($data['daily_spend_limit'])) {
                $params['daily_spend_limit'] = $data['daily_spend_limit'];
                $params['daily_spend_limit_enabled'] = $data['daily_spend_limit_enabled'] ?? false;
            }
            if (isset($data['mms_fall_back_to_sms'])) {
                $params['mms_fall_back_to_sms'] = $data['mms_fall_back_to_sms'];
            }
            if (isset($data['mms_transcoding'])) {
                $params['mms_transcoding'] = $data['mms_transcoding'];
            }

            $profile = \Telnyx\MessagingProfile::create($params);

            return [
                'success' => true,
                'data' => $profile,
                'profile_id' => $profile->id
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Messaging Profile Creation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update a messaging profile
     */
    public function updateMessagingProfile(string $profileId, array $data): array
    {
        try {
            $updateData = [];

            // Only include fields that are provided
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            if (isset($data['whitelisted_destinations'])) {
                $updateData['whitelisted_destinations'] = $data['whitelisted_destinations'];
            }
            if (isset($data['enabled'])) {
                $updateData['enabled'] = $data['enabled'];
            }
            if (isset($data['webhook_url'])) {
                $updateData['webhook_url'] = $data['webhook_url'];
            }
            if (isset($data['webhook_failover_url'])) {
                $updateData['webhook_failover_url'] = $data['webhook_failover_url'];
            }
            if (isset($data['webhook_api_version'])) {
                $updateData['webhook_api_version'] = $data['webhook_api_version'];
            }
            if (isset($data['number_pool_settings'])) {
                $updateData['number_pool_settings'] = $data['number_pool_settings'];
            }
            if (isset($data['url_shortener_settings'])) {
                $updateData['url_shortener_settings'] = $data['url_shortener_settings'];
            }
            if (isset($data['alpha_sender'])) {
                $updateData['alpha_sender'] = $data['alpha_sender'];
            }
            if (isset($data['daily_spend_limit'])) {
                $updateData['daily_spend_limit'] = $data['daily_spend_limit'];
            }
            if (isset($data['daily_spend_limit_enabled'])) {
                $updateData['daily_spend_limit_enabled'] = $data['daily_spend_limit_enabled'];
            }
            if (isset($data['mms_fall_back_to_sms'])) {
                $updateData['mms_fall_back_to_sms'] = $data['mms_fall_back_to_sms'];
            }
            if (isset($data['mms_transcoding'])) {
                $updateData['mms_transcoding'] = $data['mms_transcoding'];
            }

            $profile = \Telnyx\MessagingProfile::update($profileId, $updateData);

            return [
                'success' => true,
                'data' => $profile,
                'profile_id' => $profile->id
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Messaging Profile Update Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get messaging profile details
     */
    public function getMessagingProfile(string $profileId): array
    {
        try {
            $profile = \Telnyx\MessagingProfile::retrieve($profileId);

            return [
                'success' => true,
                'data' => $profile
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Messaging Profile Retrieve Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete a messaging profile
     */
    public function deleteMessagingProfile(string $profileId): array
    {
        try {
            $profile = \Telnyx\MessagingProfile::retrieve($profileId);
            $profile->delete();

            return [
                'success' => true,
                'message' => 'Messaging profile deleted successfully'
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Messaging Profile Delete Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List phone numbers assigned to a messaging profile
     */
    public function getMessagingProfilePhoneNumbers(string $profileId, array $options = []): array
    {
        try {
            $params = [
                'messaging_profile_id' => $profileId,
                'page[number]' => $options['page'] ?? 1,
                'page[size]' => $options['limit'] ?? 20,
            ];

            $phoneNumbers = \Telnyx\PhoneNumber::all($params);

            return [
                'success' => true,
                'data' => $phoneNumbers->data,
                'total' => count($phoneNumbers->data)
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Messaging Profile Phone Numbers Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * List all messaging profiles
     */
    public function listMessagingProfiles(): array
    {
        try {
            $profiles = \Telnyx\MessagingProfile::all(['limit' => 100]);

            $formattedProfiles = [];
            foreach ($profiles->data as $profile) {
                $formattedProfiles[] = [
                    'id' => $profile->id,
                    'name' => $profile->name ?? 'Unnamed',
                    'enabled' => $profile->enabled ?? true,
                    'whitelisted_destinations' => $profile->whitelisted_destinations ?? [],
                    'webhook_url' => $profile->webhook_url ?? null,
                    'created_at' => $profile->created_at ?? null,
                    'updated_at' => $profile->updated_at ?? null
                ];
            }

            return [
                'success' => true,
                'data' => $formattedProfiles,
                'total' => count($formattedProfiles)
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Messaging Profile List Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Get all purchased phone numbers from Telnyx
     */
    public function getAllPurchasedNumbers(array $options = []): array
    {
        try {
            $params = [
                'page[size]' => $options['limit'] ?? 100,
                'page[number]' => $options['page'] ?? 1,
            ];

            // Add filter options if provided
            if (!empty($options['status'])) {
                $params['filter[status]'] = $options['status'];
            }
            if (!empty($options['country_code'])) {
                $params['filter[country_code]'] = $options['country_code'];
            }

            $phoneNumbers = PhoneNumber::all($params);

            $formattedNumbers = [];
            foreach ($phoneNumbers->data as $number) {
                $formattedNumbers[] = [
                    'id' => $number->id,
                    'phone_number' => $number->phone_number,
                    'phone_number_type' => $number->phone_number_type ?? 'local',
                    'country_code' => $number->country_code,
                    'area_code' => $number->area_code ?? null,
                    'city' => $number->city ?? null,
                    'state' => $number->state ?? null,
                    'carrier' => $number->carrier ?? null,
                    'features' => $number->features ?? [],
                    'status' => $number->status ?? 'purchased',
                    'messaging_profile_id' => $number->messaging_profile_id ?? null,
                    'voice_profile_id' => $number->voice_profile_id ?? null,
                    'connection_id' => $number->connection_id ?? null,
                    'created_at' => $number->created_at ?? null,
                    'updated_at' => $number->updated_at ?? null,
                    'monthly_cost' => $number->monthly_cost ?? 0,
                    'upfront_cost' => $number->upfront_cost ?? 0,
                    'external_pin' => $number->external_pin ?? null,
                    'purchased_at' => $number->purchased_at ?? null,
                ];
            }

            return [
                'success' => true,
                'data' => $formattedNumbers,
                'total' => count($formattedNumbers),
                'pagination' => [
                    'current_page' => $options['page'] ?? 1,
                    'per_page' => $options['limit'] ?? 100,
                    'has_next' => count($formattedNumbers) >= ($options['limit'] ?? 100)
                ]
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Get All Purchased Numbers Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Assign a phone number to a messaging profile
     */
    public function assignNumberToMessagingProfile(string $phoneNumberId, string $messagingProfileId): array
    {
        try {
            $number = PhoneNumber::update($phoneNumberId, [
                'messaging_profile_id' => $messagingProfileId
            ]);

            return [
                'success' => true,
                'data' => $number,
                'message' => 'Phone number assigned to messaging profile successfully'
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Assign Number to Messaging Profile Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get unassigned phone numbers (not assigned to any messaging profile)
     */
    public function getUnassignedNumbers(): array
    {
        try {
            $phoneNumbers = PhoneNumber::all([
                'page[size]' => 100,
                'filter[messaging_profile_id]' => 'null'
            ]);

            $formattedNumbers = [];
            foreach ($phoneNumbers->data as $number) {
                $formattedNumbers[] = [
                    'id' => $number->id,
                    'phone_number' => $number->phone_number,
                    'phone_number_type' => $number->phone_number_type ?? 'local',
                    'country_code' => $number->country_code,
                    'area_code' => $number->area_code ?? null,
                    'city' => $number->city ?? null,
                    'state' => $number->state ?? null,
                    'features' => $number->features ?? [],
                    'status' => $number->status ?? 'purchased',
                    'created_at' => $number->created_at ?? null,
                    'monthly_cost' => $number->monthly_cost ?? 0,
                ];
            }

            return [
                'success' => true,
                'data' => $formattedNumbers,
                'total' => count($formattedNumbers)
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Get Unassigned Numbers Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Assign a phone number to a SIP trunk connection
     */
    public function assignPhoneNumberToSipTrunk(string $phoneNumberId, string $connectionId): array
    {
        try {
            $number = PhoneNumber::update($phoneNumberId, [
                'connection_id' => $connectionId
            ]);

            return [
                'success' => true,
                'data' => $number,
                'message' => 'Phone number assigned to SIP trunk successfully'
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Assign Phone Number to SIP Trunk Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Unassign a phone number from a SIP trunk connection
     */
    public function unassignPhoneNumberFromSipTrunk(string $phoneNumberId): array
    {
        try {
            $number = PhoneNumber::update($phoneNumberId, [
                'connection_id' => null
            ]);

            return [
                'success' => true,
                'data' => $number,
                'message' => 'Phone number unassigned from SIP trunk successfully'
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Unassign Phone Number from SIP Trunk Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get phone numbers assigned to a specific SIP trunk connection
     */
    public function getSipTrunkPhoneNumbers(string $connectionId): array
    {
        try {
            $phoneNumbers = PhoneNumber::all([
                'page[size]' => 100,
                'filter[connection_id]' => $connectionId
            ]);

            $formattedNumbers = [];
            foreach ($phoneNumbers->data as $number) {
                $formattedNumbers[] = [
                    'id' => $number->id,
                    'phone_number' => $number->phone_number,
                    'phone_number_type' => $number->phone_number_type ?? 'local',
                    'country_code' => $number->country_code,
                    'area_code' => $number->area_code ?? null,
                    'city' => $number->city ?? null,
                    'state' => $number->state ?? null,
                    'features' => $number->features ?? [],
                    'status' => $number->status ?? 'purchased',
                    'connection_id' => $number->connection_id,
                    'created_at' => $number->created_at ?? null,
                    'monthly_cost' => $number->monthly_cost ?? 0,
                ];
            }

            return [
                'success' => true,
                'data' => $formattedNumbers,
                'total' => count($formattedNumbers)
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Get SIP Trunk Phone Numbers Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Get phone numbers that are not assigned to any SIP trunk
     */
    public function getUnassignedSipTrunkNumbers(): array
    {
        try {
            $phoneNumbers = PhoneNumber::all([
                'page[size]' => 100,
                'filter[connection_id]' => 'null'
            ]);

            $formattedNumbers = [];
            foreach ($phoneNumbers->data as $number) {
                $formattedNumbers[] = [
                    'id' => $number->id,
                    'phone_number' => $number->phone_number,
                    'phone_number_type' => $number->phone_number_type ?? 'local',
                    'country_code' => $number->country_code,
                    'area_code' => $number->area_code ?? null,
                    'city' => $number->city ?? null,
                    'state' => $number->state ?? null,
                    'features' => $number->features ?? [],
                    'status' => $number->status ?? 'purchased',
                    'created_at' => $number->created_at ?? null,
                    'monthly_cost' => $number->monthly_cost ?? 0,
                ];
            }

            return [
                'success' => true,
                'data' => $formattedNumbers,
                'total' => count($formattedNumbers)
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Get Unassigned SIP Trunk Numbers Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Get all outbound voice profiles from Telnyx
     */
    public function getOutboundVoiceProfiles(array $options = []): array
    {
        try {
            $params = [
                'page[size]' => $options['limit'] ?? 100,
                'page[number]' => $options['page'] ?? 1,
            ];

            // Add filter options if provided
            if (!empty($options['name'])) {
                $params['filter[name]'] = $options['name'];
            }
            if (!empty($options['traffic_type'])) {
                $params['filter[traffic_type]'] = $options['traffic_type'];
            }

            $voiceProfiles = \Telnyx\OutboundVoiceProfile::all($params);

            $formattedProfiles = [];
            foreach ($voiceProfiles->data as $profile) {
                $formattedProfiles[] = [
                    'id' => $profile->id,
                    'name' => $profile->name ?? 'Unnamed Profile',
                    'traffic_type' => $profile->traffic_type ?? 'conversational',
                    'service_plan' => $profile->service_plan ?? 'standard',
                    'enabled' => $profile->enabled ?? true,
                    'webhook_url' => $profile->webhook_url ?? null,
                    'webhook_failover_url' => $profile->webhook_failover_url ?? null,
                    'webhook_api_version' => $profile->webhook_api_version ?? '2',
                    'webhook_timeout_secs' => $profile->webhook_timeout_secs ?? 25,
                    'created_at' => $profile->created_at ?? null,
                    'updated_at' => $profile->updated_at ?? null,
                    'tags' => $profile->tags ?? [],
                    'metadata' => $profile->metadata ?? []
                ];
            }

            return [
                'success' => true,
                'data' => $formattedProfiles,
                'total' => count($formattedProfiles),
                'pagination' => [
                    'current_page' => $options['page'] ?? 1,
                    'per_page' => $options['limit'] ?? 100,
                    'has_next' => count($formattedProfiles) >= ($options['limit'] ?? 100)
                ]
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Get Outbound Voice Profiles Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Get specific outbound voice profile details
     */
    public function getOutboundVoiceProfile(string $profileId): array
    {
        try {
            $profile = \Telnyx\OutboundVoiceProfile::retrieve($profileId);

            return [
                'success' => true,
                'data' => [
                    'id' => $profile->id,
                    'name' => $profile->name ?? 'Unnamed Profile',
                    'traffic_type' => $profile->traffic_type ?? 'conversational',
                    'service_plan' => $profile->service_plan ?? 'standard',
                    'enabled' => $profile->enabled ?? true,
                    'webhook_url' => $profile->webhook_url ?? null,
                    'webhook_failover_url' => $profile->webhook_failover_url ?? null,
                    'webhook_api_version' => $profile->webhook_api_version ?? '2',
                    'webhook_timeout_secs' => $profile->webhook_timeout_secs ?? 25,
                    'created_at' => $profile->created_at ?? null,
                    'updated_at' => $profile->updated_at ?? null,
                    'tags' => $profile->tags ?? [],
                    'metadata' => $profile->metadata ?? []
                ]
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Get Outbound Voice Profile Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a new outbound voice profile
     */
    public function createOutboundVoiceProfile(array $data): array
    {
        try {
            $params = [
                'name' => $data['name'],
                'traffic_type' => $data['traffic_type'] ?? 'conversational',
                'service_plan' => $data['service_plan'] ?? 'standard',
                'enabled' => $data['enabled'] ?? true,
            ];

            // Add optional fields if provided
            if (!empty($data['webhook_url'])) {
                $params['webhook_url'] = $data['webhook_url'];
            }
            if (!empty($data['webhook_failover_url'])) {
                $params['webhook_failover_url'] = $data['webhook_failover_url'];
            }
            if (!empty($data['webhook_api_version'])) {
                $params['webhook_api_version'] = $data['webhook_api_version'];
            }
            if (!empty($data['webhook_timeout_secs'])) {
                $params['webhook_timeout_secs'] = $data['webhook_timeout_secs'];
            }
            if (!empty($data['tags'])) {
                $params['tags'] = $data['tags'];
            }

            $profile = \Telnyx\OutboundVoiceProfile::create($params);

            return [
                'success' => true,
                'data' => $profile,
                'profile_id' => $profile->id
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Create Outbound Voice Profile Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update an outbound voice profile
     */
    public function updateOutboundVoiceProfile(string $profileId, array $data): array
    {
        try {
            $updateData = [];

            // Only include fields that are provided
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            if (isset($data['traffic_type'])) {
                $updateData['traffic_type'] = $data['traffic_type'];
            }
            if (isset($data['service_plan'])) {
                $updateData['service_plan'] = $data['service_plan'];
            }
            if (isset($data['enabled'])) {
                $updateData['enabled'] = $data['enabled'];
            }
            if (isset($data['webhook_url'])) {
                $updateData['webhook_url'] = $data['webhook_url'];
            }
            if (isset($data['webhook_failover_url'])) {
                $updateData['webhook_failover_url'] = $data['webhook_failover_url'];
            }
            if (isset($data['webhook_api_version'])) {
                $updateData['webhook_api_version'] = $data['webhook_api_version'];
            }
            if (isset($data['webhook_timeout_secs'])) {
                $updateData['webhook_timeout_secs'] = $data['webhook_timeout_secs'];
            }
            if (isset($data['tags'])) {
                $updateData['tags'] = $data['tags'];
            }

            $profile = \Telnyx\OutboundVoiceProfile::update($profileId, $updateData);

            return [
                'success' => true,
                'data' => $profile,
                'profile_id' => $profile->id
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Update Outbound Voice Profile Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete an outbound voice profile
     */
    public function deleteOutboundVoiceProfile(string $profileId): array
    {
        try {
            $profile = \Telnyx\OutboundVoiceProfile::retrieve($profileId);
            $profile->delete();

            return [
                'success' => true,
                'message' => 'Outbound voice profile deleted successfully'
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Delete Outbound Voice Profile Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update phone number voice/recording settings
     * Uses the Telnyx API endpoint: PATCH /phone_numbers/:id/voice
     */
    public function updatePhoneNumberRecordingSettings(string $phoneNumberId, array $settings): array
    {
        try {
            $updateData = [];

            // Map our field names to Telnyx API field names
            if (isset($settings['inbound_call_recording_enabled'])) {
                $updateData['inbound_call_recording_enabled'] = (bool) $settings['inbound_call_recording_enabled'];
            }
            if (isset($settings['inbound_call_recording_format'])) {
                $updateData['inbound_call_recording_format'] = $settings['inbound_call_recording_format'];
            }
            if (isset($settings['inbound_call_recording_channels'])) {
                $updateData['inbound_call_recording_channels'] = $settings['inbound_call_recording_channels'];
            }

            // Update using the PhoneNumber class with the voice settings
            $number = PhoneNumber::update($phoneNumberId, $updateData);

            return [
                'success' => true,
                'data' => $number,
                'message' => 'Recording settings updated successfully'
            ];
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Update Phone Number Recording Settings Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
