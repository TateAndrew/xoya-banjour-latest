<?php

namespace App\Services;

use Telnyx\Telnyx;
use Telnyx\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TelnyxService
{
    public function __construct()
    {
        Telnyx::setApiKey(config('services.telnyx.api_key'));
    }

    /**
     * Format phone number to E.164 format.
     */
    private function formatToE164($phoneNumber)
    {
        if (empty($phoneNumber)) {
            return null;
        }

        // Remove any non-numeric characters except leading +
        $number = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // If it already starts with +, return as is
        if (str_starts_with($number, '+')) {
            return $number;
        }
        
        // If it starts with a digit, add + prefix
        if (preg_match('/^[0-9]/', $number)) {
            return '+' . $number;
        }
        
        return $number;
    }

    /**
     * Send an SMS message via Telnyx.
     */
    public function sendSms($to, $content, $from = null, $messagingProfileId = null)
    {
        try {
            $fromNumber = $this->formatToE164($from ?: config('services.telnyx.phone_number'));
            $toNumber = $this->formatToE164($to);
            $profileId = $messagingProfileId ?: config('services.telnyx.messaging_profile_id');
            
            Log::info('Attempting to send SMS via Telnyx', [
                'to' => $toNumber,
                'to_original' => $to,
                'from' => $fromNumber,
                'from_original' => $from,
                'messaging_profile_id' => $profileId,
                'content_length' => strlen($content)
            ]);
            
            $message = Message::create([
                'from' => $fromNumber,
                'to' => $toNumber,
                'text' => $content,
                'messaging_profile_id' => $profileId
            ]);
            
            $response = $message->toArray();
            
            Log::info('SMS sent successfully via Telnyx', [
                'message_id' => $response['data']['id'] ?? 'unknown',
                'to' => $toNumber,
                'from' => $fromNumber,
                'status' => $response['data']['to'][0]['status'] ?? 'unknown'
            ]);
            
            return $response;
            
        } catch (\Telnyx\Exception\ApiErrorException $e) {
            Log::error('Telnyx API error while sending SMS', [
                'error_type' => 'ApiErrorException',
                'message' => $e->getMessage(),
                'to' => $toNumber ?? $to,
                'from' => $fromNumber ?? $from,
                'messaging_profile_id' => $messagingProfileId,
                'http_status' => $e->getHttpStatus(),
                'error_code' => method_exists($e, 'getTelnyxCode') ? $e->getTelnyxCode() : null,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
            
        } catch (\Telnyx\Exception\InvalidRequestException $e) {
            Log::error('Invalid request error while sending SMS', [
                'error_type' => 'InvalidRequestException',
                'message' => $e->getMessage(),
                'to' => $toNumber ?? $to,
                'from' => $fromNumber ?? $from,
                'messaging_profile_id' => $messagingProfileId,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
            
        } catch (\Telnyx\Exception\AuthenticationException $e) {
            Log::error('Authentication error while sending SMS', [
                'error_type' => 'AuthenticationException',
                'message' => $e->getMessage(),
                'to' => $toNumber ?? $to,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
            
        } catch (\Exception $e) {
            Log::error('Unexpected error while sending SMS', [
                'error_type' => get_class($e),
                'message' => $e->getMessage(),
                'to' => $toNumber ?? $to,
                'from' => $fromNumber ?? $from,
                'messaging_profile_id' => $messagingProfileId,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get message status from Telnyx.
     */
    public function getMessageStatus($messageId)
    {
        try {
            $message = Message::retrieve($messageId);
            return $message->toArray();
        } catch (\Exception $e) {
            Log::error('Telnyx message status error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate webhook signature.
     */
    public function validateWebhookSignature($payload, $signature, $timestamp)
    {
        try {
            $expectedSignature = hash_hmac(
                'sha256',
                $timestamp . '.' . $payload,
                config('services.telnyx.webhook_secret')
            );

            return hash_equals($expectedSignature, $signature);
        } catch (\Exception $e) {
            Log::error('Webhook signature validation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * List all call recordings with pagination.
     */
    public function listRecordings($pageNumber = 1, $pageSize = 25, $filters = [])
    {
        try {
            $url = 'https://api.telnyx.com/v2/recordings';
            
            $queryParams = [
                'page[number]' => $pageNumber,
                'page[size]' => $pageSize,
            ];

            // Add optional filters
            if (isset($filters['call_session_id'])) {
                $queryParams['filter[call_session_id]'] = $filters['call_session_id'];
            }
            if (isset($filters['conference_id'])) {
                $queryParams['filter[conference_id]'] = $filters['conference_id'];
            }
            if (isset($filters['status'])) {
                $queryParams['filter[status]'] = $filters['status'];
            }
            if (isset($filters['created_at_gte'])) {
                $queryParams['filter[created_at][gte]'] = $filters['created_at_gte'];
            }
            if (isset($filters['created_at_lte'])) {
                $queryParams['filter[created_at][lte]'] = $filters['created_at_lte'];
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->get($url, $queryParams);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Telnyx list recordings error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Telnyx list recordings error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieve a specific call recording.
     */
    public function getRecording($recordingId)
    {
        try {
            $url = "https://api.telnyx.com/v2/recordings/{$recordingId}";

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Telnyx get recording error', [
                'recording_id' => $recordingId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Telnyx get recording error: ' . $e->getMessage(), [
                'recording_id' => $recordingId
            ]);
            return null;
        }
    }

    /**
     * Delete a call recording.
     */
    public function deleteRecording($recordingId)
    {
        try {
            $url = "https://api.telnyx.com/v2/recordings/{$recordingId}";

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->delete($url);

            if ($response->successful()) {
                return true;
            }

            Log::error('Telnyx delete recording error', [
                'recording_id' => $recordingId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Telnyx delete recording error: ' . $e->getMessage(), [
                'recording_id' => $recordingId
            ]);
            return false;
        }
    }

    /**
     * Download recording file.
     */
    public function downloadRecording($downloadUrl)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->get($downloadUrl);

            if ($response->successful()) {
                return $response->body();
            }

            Log::error('Telnyx download recording error', [
                'url' => $downloadUrl,
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Telnyx download recording error: ' . $e->getMessage(), [
                'url' => $downloadUrl
            ]);
            return null;
        }
    }

    /**
     * List recording transcriptions from Telnyx.
     */
    public function listRecordingTranscriptions($filters = [])
    {
        try {
            $url = 'https://api.telnyx.com/v2/recording_transcriptions';
            
            $queryParams = [];

            // Add optional filters
            if (isset($filters['recording_id'])) {
                $queryParams['filter[recording_id]'] = $filters['recording_id'];
            }
            if (isset($filters['status'])) {
                $queryParams['filter[status]'] = $filters['status'];
            }
            if (isset($filters['created_at_gte'])) {
                $queryParams['filter[created_at][gte]'] = $filters['created_at_gte'];
            }
            if (isset($filters['created_at_lte'])) {
                $queryParams['filter[created_at][lte]'] = $filters['created_at_lte'];
            }
            if (isset($filters['page_size'])) {
                $queryParams['page[size]'] = $filters['page_size'];
            }
            if (isset($filters['page_after'])) {
                $queryParams['page[after]'] = $filters['page_after'];
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->get($url, $queryParams);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Telnyx list recording transcriptions error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Telnyx list recording transcriptions error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get a specific recording transcription from Telnyx.
     */
    public function getRecordingTranscription($transcriptionId)
    {
        try {
            $url = "https://api.telnyx.com/v2/recording_transcriptions/{$transcriptionId}";

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Telnyx get recording transcription error', [
                'transcription_id' => $transcriptionId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Telnyx get recording transcription error: ' . $e->getMessage(), [
                'transcription_id' => $transcriptionId
            ]);
            return null;
        }
    }

    /**
     * Get transcription for a specific recording.
     */
    public function getRecordingTranscriptionByRecordingId($recordingId)
    {
        try {
            $response = $this->listRecordingTranscriptions([
                'recording_id' => $recordingId
            ]);

            if ($response && isset($response['data']) && count($response['data']) > 0) {
                return $response['data'][0]; // Return first transcription
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Telnyx get recording transcription by recording ID error: ' . $e->getMessage(), [
                'recording_id' => $recordingId
            ]);
            return null;
        }
    }

    /**
     * Create an outbound voice profile.
     */
    public function createOutboundVoiceProfile($data)
    {
        try {
            $url = 'https://api.telnyx.com/v2/outbound_voice_profiles';

            $payload = [
                'name' => $data['name'],
            ];
           
            if (isset($data['concurrent_call_limit'])) {
                $payload['concurrent_call_limit'] = (int) $data['concurrent_call_limit'];
            }
            if (isset($data['enabled'])) {
                $payload['enabled'] = (bool) $data['enabled'];
            }
            if (isset($data['tags']) && !empty($data['tags'])) {
                $payload['tags'] = is_array($data['tags']) ? $data['tags'] : [$data['tags']];
            }
            if (isset($data['max_destination_rate'])) {
                $payload['max_destination_rate'] = (int) $data['max_destination_rate'];
            }
            if (isset($data['daily_spend_limit'])) {
                $payload['daily_spend_limit'] = (string) $data['daily_spend_limit'];
            }
            if (isset($data['daily_spend_limit_enabled'])) {
                // Convert string to boolean: 'enabled' -> true, 'disabled' -> false
                $payload['daily_spend_limit_enabled'] = $data['daily_spend_limit_enabled'] === 'enabled';
            }
            if (isset($data['call_recording'])) {
                $payload['call_recording'] = $data['call_recording'];
            }
            if (isset($data['billing_group_id'])) {
                $payload['billing_group_id'] = $data['billing_group_id'];
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->post($url, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Telnyx create outbound voice profile error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Telnyx create outbound voice profile error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all outbound voice profiles.
     */
    public function listOutboundVoiceProfiles($pageNumber = 1, $pageSize = 25, $filters = [])
    {
        try {
            $url = 'https://api.telnyx.com/v2/outbound_voice_profiles';
            
            $queryParams = [
                'page[number]' => $pageNumber,
                'page[size]' => $pageSize,
            ];

            // Add optional filters
            if (isset($filters['filter_name'])) {
                $queryParams['filter[name][contains]'] = $filters['filter_name'];
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->get($url, $queryParams);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Telnyx list outbound voice profiles error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Telnyx list outbound voice profiles error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieve a specific outbound voice profile.
     */
    public function getOutboundVoiceProfile($profileId)
    {
        try {
            $url = "https://api.telnyx.com/v2/outbound_voice_profiles/{$profileId}";

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Telnyx get outbound voice profile error', [
                'profile_id' => $profileId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Telnyx get outbound voice profile error: ' . $e->getMessage(), [
                'profile_id' => $profileId
            ]);
            return null;
        }
    }

    /**
     * Update an outbound voice profile.
     */
    public function updateOutboundVoiceProfile($profileId, $data)
    {
        try {
            $url = "https://api.telnyx.com/v2/outbound_voice_profiles/{$profileId}";

            $payload = [];

            // Add fields if provided
            if (isset($data['name'])) {
                $payload['name'] = $data['name'];
            }
            if (isset($data['traffic_type'])) {
                $payload['traffic_type'] = $data['traffic_type'] ? 'short_duration' : 'conversational';
            }
            if (isset($data['service_plan'])) {
                $payload['service_plan'] = $data['service_plan'];
            }
            if (isset($data['concurrent_call_limit'])) {
                $payload['concurrent_call_limit'] = (int) $data['concurrent_call_limit'];
            }
            if (isset($data['enabled'])) {
                $payload['enabled'] = (bool) $data['enabled'];
            }
            if (isset($data['tags'])) {
                $payload['tags'] = is_array($data['tags']) ? $data['tags'] : [$data['tags']];
            }
            if (isset($data['max_destination_rate'])) {
                $payload['max_destination_rate'] = (int) $data['max_destination_rate'];
            }
            if (isset($data['daily_spend_limit'])) {
                $payload['daily_spend_limit'] = (string) $data['daily_spend_limit'];
            }
            if (isset($data['daily_spend_limit_enabled'])) {
                // Convert string to boolean: 'enabled' -> true, 'disabled' -> false
                $payload['daily_spend_limit_enabled'] = $data['daily_spend_limit_enabled'] === 'enabled';
            }
            if (isset($data['call_recording'])) {
                $payload['call_recording'] = $data['call_recording'];
            }
            if (isset($data['billing_group_id'])) {
                $payload['billing_group_id'] = $data['billing_group_id'];
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->patch($url, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Telnyx update outbound voice profile error', [
                'profile_id' => $profileId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Telnyx update outbound voice profile error: ' . $e->getMessage(), [
                'profile_id' => $profileId
            ]);
            return null;
        }
    }

    /**
     * Delete an outbound voice profile.
     */
    public function deleteOutboundVoiceProfile($profileId)
    {
        try {
            $url = "https://api.telnyx.com/v2/outbound_voice_profiles/{$profileId}";

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->delete($url);

            if ($response->successful()) {
                return true;
            }

            Log::error('Telnyx delete outbound voice profile error', [
                'profile_id' => $profileId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Telnyx delete outbound voice profile error: ' . $e->getMessage(), [
                'profile_id' => $profileId
            ]);
            return false;
        }
    }

    /**
     * Get account balance from Telnyx.
     */
    public function getBalance()
    {
        try {
            $url = 'https://api.telnyx.com/v2/balance';

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Telnyx get balance error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Telnyx get balance error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * List invoices from Telnyx.
     */
    public function listInvoices($pageNumber = 1, $pageSize = 25, $filters = [])
    {
        try {
            $url = 'https://api.telnyx.com/v2/invoices';
            
            $queryParams = [
                'page[number]' => $pageNumber,
                'page[size]' => $pageSize,
            ];

            // Add optional filters
            if (isset($filters['status'])) {
                $queryParams['filter[status]'] = $filters['status'];
            }
            if (isset($filters['start_date'])) {
                $queryParams['filter[start_date]'] = $filters['start_date'];
            }
            if (isset($filters['end_date'])) {
                $queryParams['filter[end_date]'] = $filters['end_date'];
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->get($url, $queryParams);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Telnyx list invoices error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Telnyx list invoices error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get a specific invoice from Telnyx.
     */
    public function getInvoice($invoiceId)
    {
        try {
            $url = "https://api.telnyx.com/v2/invoices/{$invoiceId}";

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Telnyx get invoice error', [
                'invoice_id' => $invoiceId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Telnyx get invoice error: ' . $e->getMessage(), [
                'invoice_id' => $invoiceId
            ]);
            return null;
        }
    }

    /**
     * Download invoice PDF from Telnyx.
     */
    public function downloadInvoicePdf($invoiceId)
    {
        try {
            $url = "https://api.telnyx.com/v2/invoices/{$invoiceId}/pdf";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            Log::error('Telnyx download invoice PDF error', [
                'invoice_id' => $invoiceId,
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Telnyx download invoice PDF error: ' . $e->getMessage(), [
                'invoice_id' => $invoiceId
            ]);
            return null;
        }
    }

    /**
     * List billing groups from Telnyx.
     */
    public function listBillingGroups($pageNumber = 1, $pageSize = 25)
    {
        try {
            $url = 'https://api.telnyx.com/v2/billing_groups';
            
            $queryParams = [
                'page[number]' => $pageNumber,
                'page[size]' => $pageSize,
            ];

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->get($url, $queryParams);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Telnyx list billing groups error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Telnyx list billing groups error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get usage reports from Telnyx.
     */
    public function getUsageReports($filters = [])
    {
        try {
            $url = 'https://api.telnyx.com/v2/reports/cdr_usage_reports';
            
            $queryParams = [];

            // Add date filters
            if (isset($filters['start_date'])) {
                $queryParams['filter[start_date]'] = $filters['start_date'];
            }
            if (isset($filters['end_date'])) {
                $queryParams['filter[end_date]'] = $filters['end_date'];
            }
            if (isset($filters['product'])) {
                $queryParams['filter[product]'] = $filters['product'];
            }
            if (isset($filters['page_size'])) {
                $queryParams['page[size]'] = $filters['page_size'];
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->get($url, $queryParams);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Telnyx get usage reports error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Telnyx get usage reports error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get payment methods from Telnyx.
     */
    public function getPaymentMethods()
    {
        try {
            $url = 'https://api.telnyx.com/v2/payment_methods';

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.telnyx.api_key'),
            ])->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Telnyx get payment methods error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Telnyx get payment methods error: ' . $e->getMessage());
            return null;
        }
    }
}
