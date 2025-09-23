<?php

namespace App\Services;

use App\Models\Call;
use App\Models\CallTranscript;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranscriptionService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.telnyx.api_key');
        $this->baseUrl = 'https://api.telnyx.com/v2';
        
        // Validate API key is configured
        if (!$this->apiKey) {
            throw new \InvalidArgumentException('Telnyx API key not configured. Please set TELNYX_API_KEY in your .env file.');
        }
        
        // Validate API key format (should start with KEY)
        if (!str_starts_with($this->apiKey, 'KEY')) {
            throw new \InvalidArgumentException('Invalid Telnyx API key format. API key should start with "KEY".');
        }
    }

    /**
     * Start transcription for a call using call_control_id via Call Control API
     */
    public function startTranscription(string $callControlId, string $language = 'en', array $options = []): ?CallTranscript
    {
        try {
            // Validate input parameters
            if (empty($callControlId)) {
                throw new \InvalidArgumentException('Call control ID is required');
            }
            
            // Find the call by call_control_id
            $call = Call::where('call_control_id', $callControlId)->first();
            
            if (!$call) {
                Log::error('Call not found for transcription', ['call_control_id' => $callControlId]);
                throw new \Exception("Call not found for call_control_id: {$callControlId}");
            }

            // Check if transcription already exists
            $existingTranscript = CallTranscript::where('call_control_id', $callControlId)->first();
            if ($existingTranscript) {
                Log::info('Transcription already exists for call', [
                    'call_control_id' => $callControlId,
                    'transcript_id' => $existingTranscript->id
                ]);
                return $existingTranscript;
            }

            // Prepare transcription start payload
            $payload = [
                'language' => $language,
                'transcription_engine' => $options['transcription_engine'] ?? 'A/B',
                'client_state' => $options['client_state'] ?? null,
                'command_id' => $options['command_id'] ?? uniqid('transcription_')
            ];

            // Start transcription via Telnyx Call Control API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/calls/' . $callControlId . '/actions/transcription_start', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                // Create transcript record
                $transcript = CallTranscript::create([
                    'call_id' => $call->id,
                    'call_control_id' => $callControlId,
                    'transcription_id' => $data['data']['command_id'] ?? $payload['command_id'],
                    'status' => 'processing', // Changed from 'started' to match webhook expectations
                    'language' => $language,
                    'started_at' => now(),
                    'metadata' => array_merge($data['data'] ?? [], $payload)
                ]);

                Log::info('Transcription started successfully via Call Control API', [
                    'call_control_id' => $callControlId,
                    'transcript_id' => $transcript->id,
                    'command_id' => $payload['command_id'],
                    'response' => $data
                ]);

                return $transcript;
            } else {
                $errorData = $response->json() ?: [];
                $errorMessage = $errorData['errors'][0]['detail'] ?? 'Unknown API error';
                
                Log::error('Failed to start transcription via Call Control API', [
                    'call_control_id' => $callControlId,
                    'error_data' => $errorData,
                    'error_message' => $errorMessage,
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);
                
                throw new \Exception("Transcription API failed: {$errorMessage} (HTTP {$response->status()})");
            }

        } catch (\Exception $e) {
            Log::error('Error starting transcription: ' . $e->getMessage(), [
                'call_control_id' => $callControlId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw the exception with more context
            throw new \Exception("Failed to start transcription for call {$callControlId}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Stop transcription for a call via Call Control API
     */
    public function stopTranscription(string $callControlId, array $options = []): bool
    {
        try {
            $transcript = CallTranscript::where('call_control_id', $callControlId)->first();
            
            if (!$transcript) {
                Log::error('Transcript not found for stopping', ['call_control_id' => $callControlId]);
                return false;
            }

            // Prepare transcription stop payload
            $payload = [
                'client_state' => $options['client_state'] ?? null,
                'command_id' => $options['command_id'] ?? uniqid('transcription_stop_')
            ];

            // Stop transcription via Telnyx Call Control API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/calls/' . $callControlId . '/actions/transcription_stop', $payload);

            if ($response->successful()) {
                $transcript->update([
                    'status' => 'processing',
                    'completed_at' => now(),
                    'metadata' => array_merge($transcript->metadata ?? [], $payload)
                ]);

                Log::info('Transcription stopped successfully via Call Control API', [
                    'call_control_id' => $callControlId,
                    'transcript_id' => $transcript->id,
                    'command_id' => $payload['command_id']
                ]);

                return true;
            } else {
                Log::error('Failed to stop transcription via Call Control API', [
                    'call_control_id' => $callControlId,
                    'response' => $response->body(),
                    'status' => $response->status()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Error stopping transcription: ' . $e->getMessage(), [
                'call_control_id' => $callControlId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get transcription status
     */
    public function getTranscriptionStatus(string $callControlId): ?array
    {
        try {
            $transcript = CallTranscript::where('call_control_id', $callControlId)->first();
            
            if (!$transcript) {
                return null;
            }

            if (!$transcript->transcription_id) {
                return [
                    'status' => $transcript->status,
                    'message' => 'No Telnyx transcription ID'
                ];
            }

            // Get status from Telnyx API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/transcriptions/' . $transcript->transcription_id);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? null;
            } else {
                return [
                    'status' => $transcript->status,
                    'message' => 'Failed to get status from Telnyx'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error getting transcription status: ' . $e->getMessage(), [
                'call_control_id' => $callControlId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Update transcript with completed data
     */
    public function updateTranscriptWithData(string $transcriptionId, array $transcriptData): bool
    {
        try {
            $transcript = CallTranscript::where('transcription_id', $transcriptionId)->first();
            
            if (!$transcript) {
                Log::error('Transcript not found for update', ['transcription_id' => $transcriptionId]);
                return false;
            }

            $transcript->update([
                'status' => 'completed',
                'transcript_text' => $transcriptData['text'] ?? null,
                'transcript_data' => $transcriptData,
                'completed_at' => now(),
                'duration' => $transcriptData['duration'] ?? null
            ]);

            Log::info('Transcript updated with completed data', [
                'transcription_id' => $transcriptionId,
                'transcript_id' => $transcript->id,
                'word_count' => str_word_count($transcriptData['text'] ?? '')
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error updating transcript: ' . $e->getMessage(), [
                'transcription_id' => $transcriptionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get transcript for a call
     */
    public function getTranscript(string $callControlId): ?CallTranscript
    {
        return CallTranscript::where('call_control_id', $callControlId)->first();
    }

    /**
     * Get all transcripts for a call
     */
    public function getAllTranscripts(string $callControlId): array
    {
        return CallTranscript::where('call_control_id', $callControlId)->get()->toArray();
    }

    /**
     * Test API connection and credentials
     */
    public function testApiConnection(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/calls', ['page[size]' => 1]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'API connection successful',
                    'status_code' => $response->status(),
                    'api_key_prefix' => substr($this->apiKey, 0, 10) . '...'
                ];
            } else {
                $errorData = $response->json() ?: [];
                return [
                    'success' => false,
                    'message' => 'API connection failed',
                    'error' => $errorData['errors'][0]['detail'] ?? 'Unknown error',
                    'status_code' => $response->status(),
                    'api_key_prefix' => substr($this->apiKey, 0, 10) . '...'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'API connection error',
                'error' => $e->getMessage(),
                'api_key_configured' => !empty($this->apiKey)
            ];
        }
    }
}
