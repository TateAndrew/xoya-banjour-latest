<?php

namespace App\Services;

use Telnyx\Telnyx;
use Telnyx\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

class MessagingProfileService
{
    public function __construct()
    {
        // Initialize Telnyx with API key from environment
        Telnyx::setApiKey(env('TELNYX_API_KEY', 'KEY0198CD286DBF50F67B7833E70136D955_qwcmtnBzxZqKBNqx3flTIR'));
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
        } catch (\Exception $e) {
            Log::error('Messaging Profile Creation Error: ' . $e->getMessage());
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
        } catch (\Exception $e) {
            Log::error('Messaging Profile Update Error: ' . $e->getMessage());
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
        } catch (\Exception $e) {
            Log::error('Messaging Profile Retrieve Error: ' . $e->getMessage());
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
            // Alternative approach: Make HTTP DELETE request to Telnyx API
            $response = $this->makeAPICall('DELETE', "/v2/messaging_profiles/{$profileId}");
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Messaging profile deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Failed to delete messaging profile'
                ];
            }
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Messaging Profile Delete Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Messaging Profile Delete Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Make a direct API call to Telnyx
     */
    private function makeAPICall(string $method, string $endpoint, array $data = []): array
    {
        try {
            $apiKey = env('TELNYX_API_KEY', 'KEY0198CD286DBF50F67B7833E70136D955_qwcmtnBzxZqKBNqx3flTIR');
            $url = 'https://api.telnyx.com' . $endpoint;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ]);
            
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'data' => json_decode($response, true)
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'API request failed with status: ' . $httpCode,
                    'response' => $response
                ];
            }
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            Log::error('Messaging Profile Phone Numbers Error: ' . $e->getMessage());
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
    public function assignPhoneNumberToProfile(string $profileId, string $phoneNumberId): array
    {
        try {
            $endpoint = "/v2/phone_numbers/{$phoneNumberId}/messaging";
            $data = [
                'messaging_profile_id' => $profileId
            ];

            $response = $this->makeAPICall('PATCH', $endpoint, $data);
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'data' => $response['data'],
                    'message' => 'Phone number assigned to messaging profile successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Failed to assign phone number to messaging profile'
                ];
            }
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Phone Number Assignment Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Phone Number Assignment Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Unassign a phone number from a messaging profile
     */
    public function unassignPhoneNumberFromProfile(string $phoneNumberId): array
    {
        try {
            $endpoint = "/v2/phone_numbers/{$phoneNumberId}/messaging";
            $data = [
                'messaging_profile_id' => null
            ];

            $response = $this->makeAPICall('PATCH', $endpoint, $data);
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'data' => $response['data'],
                    'message' => 'Phone number unassigned from messaging profile successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Failed to unassign phone number from messaging profile'
                ];
            }
        } catch (ApiErrorException $e) {
            Log::error('Telnyx Phone Number Unassignment Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Phone Number Unassignment Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
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
        } catch (\Exception $e) {
            Log::error('Messaging Profile List Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }
}
