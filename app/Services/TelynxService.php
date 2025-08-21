<?php

namespace App\Services;

use Telnyx\Telnyx;
use Telnyx\PhoneNumber;
use Telnyx\PhoneNumberOrder;
use Telnyx\Exception\ApiException;
use Illuminate\Support\Facades\Log;

class TelynxService
{
    public function __construct()
    {
        // Initialize Telnyx with API key from environment
        Telnyx::setApiKey('KEY01981DE4FB0F5A066B6B7338FD74AAD1_jLcLOza8i560QqeovISomb');
        
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

            return [
                'success' => true,
                'data' => $phoneNumbers->data,
                'total' => count($phoneNumbers->data)
            ];
        } catch (ApiException $e) {
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
        } catch (ApiException $e) {
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
        } catch (ApiException $e) {
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
        } catch (ApiException $e) {
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
        } catch (ApiException $e) {
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
            $number = PhoneNumber::delete($phoneNumberId);

            return [
                'success' => true,
                'message' => 'Phone number deleted successfully'
            ];
        } catch (ApiException $e) {
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
            $countries = \Telnyx\Country::all();

            return [
                'success' => true,
                'data' => $countries->data
            ];
        } catch (ApiException $e) {
            Log::error('Telnyx Countries Error: ' . $e->getMessage());
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
        } catch (ApiException $e) {
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
        } catch (ApiException $e) {
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
        } catch (ApiException $e) {
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
        } catch (ApiException $e) {
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
        } catch (ApiException $e) {
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

            $participant = \Telnyx\Conference::join($conferenceId, $params);

            return [
                'success' => true,
                'data' => $participant
            ];
        } catch (ApiException $e) {
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
        } catch (ApiException $e) {
            Log::error('Telnyx SIP Credentials Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }
} 