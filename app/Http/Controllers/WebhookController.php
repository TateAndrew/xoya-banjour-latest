<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\PhoneNumber;
use App\Models\Recording;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming SMS webhooks from Telnyx.
     */
    public function handleSmsWebhook(Request $request)
    {
        try {

            $data = $request->all();
            $eventType = $data['data']['event_type'] ?? $data['event_type'] ?? null;
            $payload = $data['data']['payload'] ?? [];

            Log::info('Processing SMS webhook', [
                'event_type' => $eventType,
                'message_id' => $payload['id'] ?? 'unknown'
            ]);

            switch ($eventType) {
                case 'message.received':
                    $this->handleInboundSms($payload);
                    break;
                
                case 'message.finalized':
                    $this->handleMessageFinalized($payload);
                    break;
                
                case 'message.sent':
                    $this->handleMessageSent($payload);
                    break;
                
                case 'message.delivered':
                    $this->handleMessageDelivered($payload);
                    break;
                
                case 'message.failed':
                    $this->handleMessageFailed($payload);
                    break;
                
                default:
                    Log::info('Unhandled SMS webhook event', [
                        'event_type' => $eventType
                    ]);
            }

            return response()->json(['status' => 'success']);
            
        } catch (\Exception $e) {
            Log::error('Error processing SMS webhook: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle delivery receipt webhooks from Telnyx.
     */
    public function handleDeliveryReceipt(Request $request)
    {
        Log::info('DLR Webhook received', $request->all());

        $data = $request->all();

        if ($data['event_type'] === 'message.delivered') {
            $this->processDeliveryReceipt($data['data']['payload']);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle incoming call webhooks from Telnyx.
     */
    public function handleCallWebhook(Request $request)
    {

        $data = $request->all();
        try {
            $eventType = $data['data']['event_type'] ?? '';
            $payload = $data['data']['payload'] ?? [];

            // Create call log entry for every webhook event
            $this->createCallLog($eventType, $payload, $data);

            // Update call status based on event type
            switch ($eventType) {
                case 'call.initiated':
                    $this->handleCallInitiated($payload);
                    break;
                case 'call.answered':
                    $this->handleCallAnswered($payload);
                    break;
                case 'call.bridged':
                    $this->handleCallBridged($payload);
                    break;
                case 'call.hangup':
                    $this->handleCallHangup($payload);
                    break;
                case 'call.failed':
                    $this->handleCallFailed($payload);
                    break;
                case 'call.transcription':
                    $this->handleCallTranscription($payload);
                    break;
                case 'call.recording.saved':
                case 'recording.saved':
                    $this->handleRecordingSaved($payload);
                    break;
                default:
                    Log::info('Call webhook event logged', ['event_type' => $eventType]);
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error processing call webhook: ' . $e->getMessage(), [
                'payload' => $data,
                'error' => $e->getMessage()
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Process inbound SMS message.
     */
    private function handleInboundSms($payload)
    {
        try {
            $from = $payload['from']['phone_number'];
            $to = $payload['to']['phone_number'];
            $content = $payload['text'];
            $telnyxId = $payload['id'];

            // Find the phone number that received this message
            $phoneNumber = PhoneNumber::where('phone_number', $to)
                ->where('status', 'assigned')
                ->whereNotNull('messaging_profile_id')
                ->first();

            if (!$phoneNumber) {
                Log::warning('Received SMS for unknown phone number', [
                    'to' => $to,
                    'from' => $from,
                    'telnyx_id' => $telnyxId
                ]);
                return;
            }

            // Find or create contact
            $contact = Contact::firstOrCreate(
                ['phone_e164' => $from],
                ['name' => 'Unknown Contact']
            );

            // Get or create conversation
            $conversation = Conversation::firstOrCreate([
                'contact_id' => $contact->id,
                'sender_number' => $to
            ]);

            // Create message
            $conversation->messages()->create([
                'telnyx_message_id' => $telnyxId,
                'direction' => Message::DIRECTION_INBOUND,
                'content' => $content,
                'status' => Message::STATUS_DELIVERED,
                'delivered_at' => now()
            ]);

            // Update conversation
            $conversation->update([
                'last_message_at' => now(),
                'unread_count' => $conversation->unread_count + 1
            ]);

            Log::info('Inbound SMS processed successfully', [
                'contact_id' => $contact->id,
                'conversation_id' => $conversation->id,
                'telnyx_id' => $telnyxId,
                'phone_number_id' => $phoneNumber->id,
                'user_id' => $phoneNumber->user_id
            ]);

            // Broadcast event for real-time updates (if you have broadcasting setup)
            // event(new \App\Events\MessageReceived($conversation, $message, $phoneNumber->user_id));

        } catch (\Exception $e) {
            Log::error('Error processing inbound SMS: ' . $e->getMessage(), [
                'payload' => $payload,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle message.finalized webhook - handles both inbound and outbound messages.
     */
    private function handleMessageFinalized($payload)
    {
        try {
            $direction = $payload['direction'] ?? 'outbound';
            $telnyxId = $payload['id'];
            $from = $payload['from']['phone_number'] ?? null;
            $to = $payload['to'][0]['phone_number'] ?? null;
            $content = $payload['text'] ?? '';
            $status = $payload['to'][0]['status'] ?? 'sent';
            $completedAt = $payload['completed_at'] ?? now();
            Log::info('Processing message.finalized', [
                'direction' => $direction,
                'from' => $from,
                'to' => $to,
                'status' => $status,
                'telnyx_id' => $telnyxId
            ]);

            if ($direction === 'inbound') {
                // Handle inbound message - only if "to" number exists in database
                $this->handleInboundMessageFinalized($payload);
            } else {
                // Handle outbound message - update existing message
                $message = Message::where('telnyx_message_id', $telnyxId)->first();

                if ($message) {
                    $statusMap = [
                        'delivered' => Message::STATUS_DELIVERED,
                        'sent' => Message::STATUS_SENT,
                        'sending_failed' => Message::STATUS_FAILED,
                        'delivery_failed' => Message::STATUS_FAILED,
                        'delivery_unconfirmed' => Message::STATUS_SENT,
                    ];

                    $message->update([
                        'status' => $statusMap[$status] ?? Message::STATUS_SENT,
                        'delivered_at' => $status === 'delivered' ? $completedAt : null,
                        'sent_at' => $payload['sent_at'] ?? now()
                    ]);

                    Log::info('Outbound message finalized', [
                        'message_id' => $message->id,
                        'telnyx_id' => $telnyxId,
                        'status' => $status,
                        'final_status' => $message->status
                    ]);

                    // Broadcast message status update
                    // event(new \App\Events\MessageSent($message));
                } 
                else {
                    Log::warning('Message.finalized received for unknown outbound message', [
                        'telnyx_id' => $telnyxId,
                        'from' => $from,
                        'to' => $to
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error processing message.finalized: ' . $e->getMessage(), [
                'payload' => $payload,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle inbound message from message.finalized webhook.
     */
    private function handleInboundMessageFinalized($payload)
    {
        try {
            $from = $payload['from']['phone_number'];
            $to = $payload['to'][0]['phone_number'] ?? null;
            $content = $payload['text'];
            $telnyxId = $payload['id'];
            $completedAt = $payload['completed_at'] ?? now();

            // Find the phone number that received this message
            $phoneNumber = PhoneNumber::where('phone_number', $to)
                ->where('status', 'assigned')
                ->whereNotNull('messaging_profile_id')
                ->first();

            if (!$phoneNumber) {
                Log::warning('Received inbound SMS for unknown phone number', [
                    'to' => $to,
                    'from' => $from,
                    'telnyx_id' => $telnyxId
                ]);
                return;
            }

            // Find or create contact
            $contact = Contact::firstOrCreate(
                ['phone_e164' => $from],
                ['name' => null] // Will show phone number as name
            );

            // Get or create conversation
            $conversation = Conversation::firstOrCreate(
                [
                    'contact_id' => $contact->id,
                    'sender_number' => $to // The number that received the message
                ],
                [
                    'last_message_at' => $completedAt,
                    'unread_count' => 0
                ]
            );

            // Check if message already exists
            $existingMessage = $conversation->messages()
                ->where('telnyx_message_id', $telnyxId)
                ->first();

            if (!$existingMessage) {
                // Create new message
                $message = $conversation->messages()->create([
                    'telnyx_message_id' => $telnyxId,
                    'direction' => Message::DIRECTION_INBOUND,
                    'content' => $content,
                    'status' => Message::STATUS_DELIVERED,
                    'delivered_at' => $completedAt,
                    'sent_at' => $payload['sent_at'] ?? $completedAt
                ]);

                // Update conversation
                $conversation->update([
                    'last_message_at' => $completedAt,
                    'unread_count' => $conversation->unread_count + 1
                ]);

                Log::info('Inbound message finalized and saved', [
                    'message_id' => $message->id,
                    'contact_id' => $contact->id,
                    'conversation_id' => $conversation->id,
                    'telnyx_id' => $telnyxId,
                    'phone_number_id' => $phoneNumber->id,
                    'user_id' => $phoneNumber->user_id
                ]);

                // Broadcast event for real-time updates
                // event(new \App\Events\MessageReceived($conversation, $message, $phoneNumber->user_id));
            } else {
                Log::info('Inbound message already exists', [
                    'message_id' => $existingMessage->id,
                    'telnyx_id' => $telnyxId
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error processing inbound message.finalized: ' . $e->getMessage(), [
                'payload' => $payload,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle message.sent webhook.
     */
    private function handleMessageSent($payload)
    {
        try {
            $telnyxId = $payload['id'];
            $message = Message::where('telnyx_message_id', $telnyxId)->first();

            if ($message) {
                $message->update([
                    'status' => Message::STATUS_SENT,
                    'sent_at' => $payload['sent_at'] ?? now()
                ]);

                Log::info('Message marked as sent', [
                    'message_id' => $message->id,
                    'telnyx_id' => $telnyxId
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing message.sent: ' . $e->getMessage(), [
                'payload' => $payload,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle message.delivered webhook.
     */
    private function handleMessageDelivered($payload)
    {
        try {
            $telnyxId = $payload['id'];
            $message = Message::where('telnyx_message_id', $telnyxId)->first();

            if ($message) {
                $message->update([
                    'status' => Message::STATUS_DELIVERED,
                    'delivered_at' => now()
                ]);

                Log::info('Message marked as delivered', [
                    'message_id' => $message->id,
                    'telnyx_id' => $telnyxId
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing message.delivered: ' . $e->getMessage(), [
                'payload' => $payload,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle message.failed webhook.
     */
    private function handleMessageFailed($payload)
    {
        try {
            $telnyxId = $payload['id'];
            $message = Message::where('telnyx_message_id', $telnyxId)->first();

            if ($message) {
                $errors = $payload['errors'] ?? [];
                $message->update([
                    'status' => Message::STATUS_FAILED,
                    'failed_at' => now(),
                    'error_message' => !empty($errors) ? json_encode($errors) : null
                ]);

                Log::info('Message marked as failed', [
                    'message_id' => $message->id,
                    'telnyx_id' => $telnyxId,
                    'errors' => $errors
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing message.failed: ' . $e->getMessage(), [
                'payload' => $payload,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle call transcription webhook
     */
    private function handleCallTranscription($payload)
    {
        Log::info('Call transcription received', $payload);
        
        try {
            // Find the call by call_session_id
            $call = \App\Models\Call::where('call_session_id', $payload['call_session_id'])->first();
            
            if (!$call) {
                Log::warning('Call not found for transcription', [
                    'call_session_id' => $payload['call_session_id'],
                    'call_control_id' => $payload['call_control_id']
                ]);
                return;
            }

            // Extract transcription data
            $transcriptionData = $payload['transcription_data'] ?? [];
            $transcript = $transcriptionData['transcript'] ?? '';
            $confidence = $transcriptionData['confidence'] ?? null;
            $isFinal = $transcriptionData['is_final'] ?? false;

            // Find or create CallTranscript record
            $callTranscript = \App\Models\CallTranscript::firstOrCreate(
                [
                    'call_id' => $call->id,
                    'call_control_id' => $payload['call_control_id']
                ],
                [
                    'status' => 'processing',
                    'language' => 'en', // Default language
                    'transcript_text' => '',
                    'transcript_data' => [],
                    'started_at' => now(),
                    'metadata' => $payload
                ]
            );

            // Update transcript with new data
            if ($isFinal) {
                // Final transcript - append to existing text
                $existingText = $callTranscript->transcript_text ?? '';
                $newText = $existingText ? $existingText . ' ' . $transcript : $transcript;
                
                $callTranscript->update([
                    'transcript_text' => $newText,
                    'transcript_data' => array_merge($callTranscript->transcript_data ?? [], [
                        'final_segments' => array_merge($callTranscript->transcript_data['final_segments'] ?? [], [
                            [
                                'text' => $transcript,
                                'confidence' => $confidence,
                                'timestamp' => now()->toISOString(),
                                'is_final' => true
                            ]
                        ])
                    ]),
                    'status' => 'processing' // Keep processing until call ends
                ]);
            } else {
                // Interim transcript - store in interim data
                $callTranscript->update([
                    'transcript_data' => array_merge($callTranscript->transcript_data ?? [], [
                        'interim_segments' => array_merge($callTranscript->transcript_data['interim_segments'] ?? [], [
                            [
                                'text' => $transcript,
                                'confidence' => $confidence,
                                'timestamp' => now()->toISOString(),
                                'is_final' => false
                            ]
                        ])
                    ])
                ]);
            }

            // Update call record with transcription metadata
            $call->update([
                'metadata' => array_merge($call->metadata ?? [], [
                    'transcription_active' => true,
                    'last_transcription' => [
                        'text' => $transcript,
                        'confidence' => $confidence,
                        'is_final' => $isFinal,
                        'timestamp' => now()->toISOString()
                    ]
                ])
            ]);

            Log::info('Transcription processed successfully', [
                'call_id' => $call->id,
                'transcript_id' => $callTranscript->id,
                'transcript_length' => strlen($transcript),
                'confidence' => $confidence,
                'is_final' => $isFinal
            ]);

            // Broadcast transcription update for real-time frontend updates
            event(new \App\Events\TranscriptionUpdated($callTranscript, [
                'transcript' => $transcript,
                'confidence' => $confidence,
                'is_final' => $isFinal
            ]));

        } catch (\Exception $e) {
            Log::error('Error processing transcription: ' . $e->getMessage(), [
                'payload' => $payload,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process delivery receipt.
     */
    private function processDeliveryReceipt($payload)
    {
        try {
            $telnyxId = $payload['id'];
            $status = $payload['status'];

            $message = Message::where('telnyx_message_id', $telnyxId)->first();

            if ($message) {
                $message->update([
                    'status' => $status,
                    'delivered_at' => $status === 'delivered' ? now() : null
                ]);

                Log::info('Delivery receipt processed', [
                    'message_id' => $message->id,
                    'telnyx_id' => $telnyxId,
                    'status' => $status
                ]);
            } else {
                Log::warning('Delivery receipt received for unknown message', [
                    'telnyx_id' => $telnyxId,
                    'status' => $status
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing delivery receipt: ' . $e->getMessage(), [
                'payload' => $payload,
                'error' => $e->getMessage()
            ]);
        }
    }
    /**
     * Get call information by ID
     */
    public function getCall(Request $request)
    {
        try {
            $callId = $request->input('call_id');

            if (!$callId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Call ID is required'
                ], 400);
            }

            // Find the call in database
            $call = \App\Models\Call::where("call_session_id", $callId)->first();

            if (!$call) {
                return response()->json([
                    'success' => false,
                    'error' => 'Call not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'call' => $call
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting call: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to get call: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create call log entry for every webhook event
     */
    private function createCallLog($eventType, $payload, $fullData)
    {
        try {
            // First, find or create the call record
            $call = null;
            if (isset($payload['call_session_id'])) {
                $call = \App\Models\Call::firstOrCreate(
                    ['call_session_id' => $payload['call_session_id']],
                    [
                        'user_id' => 1, // Default user - you might want to determine this differently
                        'from_number' => $payload['from'] ?? null,
                        'to_number' => $payload['to'] ?? null,
                        'status' => 'initiating',
                        'direction' => $payload['direction'] ?? 'outgoing',
                        'start_time' => $payload['start_time'] ?? now(),
                        'call_control_id' => $payload['call_control_id'] ?? null,
                        'call_leg_id' => $payload['call_leg_id'] ?? null,
                        'connection_id' => $payload['connection_id'] ?? null,
                        'calling_party_type' => $payload['calling_party_type'] ?? null,
                        'state' => $payload['state'] ?? null,
                        'from_sip_uri' => $payload['from_sip_uri'] ?? null,
                        'to_sip_uri' => $payload['to_sip_uri'] ?? null,
                        'custom_headers' => $payload['custom_headers'] ?? null,
                        'client_state' => $payload['client_state'] ?? null,
                        'metadata' => $payload // Save entire payload as JSON
                    ]
                );
            }

            // Create the call log entry
            \App\Models\CallLog::create([
                'call_id' => $call ? $call->id : null,
                'call_session_id' => $payload['call_session_id'] ?? null,
                'call_leg_id' => $payload['call_leg_id'] ?? null,
                'event_type' => $eventType,
                'event_id' => $fullData['data']['id'] ?? uniqid(), // Use Telnyx event ID or generate unique ID
                'call_control_id' => $payload['call_control_id'] ?? null,
                'connection_id' => $payload['connection_id'] ?? null,
                'direction' => $payload['direction'] ?? null,
                'calling_party_type' => $payload['calling_party_type'] ?? null,
                'state' => $payload['state'] ?? null,
                'from_number' => $payload['from'] ?? null,
                'to_number' => $payload['to'] ?? null,
                'from_sip_uri' => $payload['from_sip_uri'] ?? null,
                'to_sip_uri' => $payload['to_sip_uri'] ?? null,
                'start_time' => $payload['start_time'] ?? null,
                'end_time' => $payload['end_time'] ?? null,
                'hangup_cause' => $payload['hangup_cause'] ?? null,
                'hangup_source' => $payload['hangup_source'] ?? null,
                'sip_hangup_cause' => $payload['sip_hangup_cause'] ?? null,
                'call_quality_stats' => $payload['call_quality_stats'] ?? null,
                'custom_headers' => $payload['custom_headers'] ?? null,
                'client_state' => $payload['client_state'] ?? null,
                'raw_payload' => $payload,
                'occurred_at' => $fullData['data']['occurred_at'] ?? $payload['start_time'] ?? now()
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating call log: ' . $e->getMessage());
        }
    }

    /**
     * Handle call initiated webhook
     */
    private function handleCallInitiated($payload)
    {
        Log::info('Call initiated', $payload);

        // Create or update call record
        if (isset($payload['call_session_id'])) {
            $call = \App\Models\Call::firstOrCreate(
                ['call_session_id' => $payload['call_session_id']],
                [
                    'user_id' => 1, // Default user
                    'from_number' => $payload['from'] ?? null,
                    'to_number' => $payload['to'] ?? null,
                    'status' => 'initiating',
                    'direction' => $payload['direction'] ?? 'outgoing',
                    'start_time' => $payload['start_time'] ?? now(),
                    'call_control_id' => $payload['call_control_id'] ?? null,
                    'call_leg_id' => $payload['call_leg_id'] ?? null,
                    'connection_id' => $payload['connection_id'] ?? null,
                    'calling_party_type' => $payload['calling_party_type'] ?? null,
                    'state' => $payload['state'] ?? null,
                    'from_sip_uri' => $payload['from_sip_uri'] ?? null,
                    'to_sip_uri' => $payload['to_sip_uri'] ?? null,
                    'custom_headers' => $payload['custom_headers'] ?? null,
                    'client_state' => $payload['client_state'] ?? null,
                    'metadata' => $payload // Save entire payload as JSON
                ]
            );

            // Broadcast call status update
            event(new \App\Events\CallStatusUpdated($call, 'initiating', 'call.initiated'));
        }
    }

    /**
     * Handle call answered webhook
     */
    private function handleCallAnswered($payload)
    {
        Log::info('Call answered', $payload);

        // Update call status
        if (isset($payload['call_session_id'])) {
            $call = \App\Models\Call::where('call_session_id', $payload['call_session_id'])->first();
            if ($call) {
                $call->update([
                    'status' => 'answered',
                    'answered_at' => $payload['start_time'] ?? now(),
                    'call_control_id' => $payload['call_control_id'] ?? $call->call_control_id,
                    'call_leg_id' => $payload['call_leg_id'] ?? $call->call_leg_id,
                    'connection_id' => $payload['connection_id'] ?? $call->connection_id,
                    'calling_party_type' => $payload['calling_party_type'] ?? $call->calling_party_type,
                    'state' => $payload['state'] ?? $call->state,
                    'from_sip_uri' => $payload['from_sip_uri'] ?? $call->from_sip_uri,
                    'to_sip_uri' => $payload['to_sip_uri'] ?? $call->to_sip_uri,
                    'custom_headers' => $payload['custom_headers'] ?? $call->custom_headers,
                    'client_state' => $payload['client_state'] ?? $call->client_state,
                    'metadata' => array_merge($call->metadata ?? [], $payload) // Merge with existing metadata
                ]);

                // Broadcast call status update
                event(new \App\Events\CallStatusUpdated($call, 'answered', 'call.answered'));
            }
        }
    }

    /**
     * Handle call hangup webhook
     */
    private function handleCallHangup($payload)
    {
        Log::info('Call hangup', $payload);

        // Update call status and calculate duration
        if (isset($payload['call_session_id'])) {
            $call = \App\Models\Call::where('call_session_id', $payload['call_session_id'])->first();
            if ($call) {
                $startTime = $payload['start_time'] ?? $call->created_at;
                $endTime = $payload['end_time'] ?? now();
                $duration = strtotime($endTime) - strtotime($startTime);

                $call->update([
                    'status' => 'ended',
                    'ended_at' => $endTime,
                    'duration' => $duration,
                    'call_control_id' => $payload['call_control_id'] ?? $call->call_control_id,
                    'call_leg_id' => $payload['call_leg_id'] ?? $call->call_leg_id,
                    'connection_id' => $payload['connection_id'] ?? $call->connection_id,
                    'calling_party_type' => $payload['calling_party_type'] ?? $call->calling_party_type,
                    'state' => $payload['state'] ?? $call->state,
                    'from_sip_uri' => $payload['from_sip_uri'] ?? $call->from_sip_uri,
                    'to_sip_uri' => $payload['to_sip_uri'] ?? $call->to_sip_uri,
                    'custom_headers' => $payload['custom_headers'] ?? $call->custom_headers,
                    'client_state' => $payload['client_state'] ?? $call->client_state,
                    'hangup_cause' => $payload['hangup_cause'] ?? null,
                    'hangup_source' => $payload['hangup_source'] ?? null,
                    'sip_hangup_cause' => $payload['sip_hangup_cause'] ?? null,
                    'call_quality_stats' => $payload['call_quality_stats'] ?? null,
                    'metadata' => array_merge($call->metadata ?? [], $payload) // Merge with existing metadata
                ]);

                // Mark transcription as completed if it exists
                $callTranscript = \App\Models\CallTranscript::where('call_id', $call->id)->first();
                if ($callTranscript) {
                    $callTranscript->update([
                        'status' => 'completed',
                        'completed_at' => $endTime,
                        'duration' => $duration
                    ]);
                    
                    Log::info('Transcription marked as completed', [
                        'call_id' => $call->id,
                        'transcript_id' => $callTranscript->id,
                        'duration' => $duration
                    ]);
                }

                // Broadcast call status update
                event(new \App\Events\CallStatusUpdated($call, 'ended', 'call.hangup'));
            }
        }
    }

    /**
     * Handle call bridged webhook
     */
    private function handleCallBridged($payload)
    {
        Log::info('Call bridged', $payload);

        // Update call status to in_progress when bridged
        if (isset($payload['call_session_id'])) {
            $call = \App\Models\Call::where('call_session_id', $payload['call_session_id'])->first();
            if ($call) {
                $call->update([
                    'status' => 'in_progress',
                    'call_control_id' => $payload['call_control_id'] ?? $call->call_control_id,
                    'call_leg_id' => $payload['call_leg_id'] ?? $call->call_leg_id,
                    'connection_id' => $payload['connection_id'] ?? $call->connection_id,
                    'calling_party_type' => $payload['calling_party_type'] ?? $call->calling_party_type,
                    'state' => $payload['state'] ?? $call->state,
                    'from_sip_uri' => $payload['from_sip_uri'] ?? $call->from_sip_uri,
                    'to_sip_uri' => $payload['to_sip_uri'] ?? $call->to_sip_uri,
                    'custom_headers' => $payload['custom_headers'] ?? $call->custom_headers,
                    'client_state' => $payload['client_state'] ?? $call->client_state,
                    'metadata' => array_merge($call->metadata ?? [], $payload) // Merge with existing metadata
                ]);

                // Broadcast call status update
                event(new \App\Events\CallStatusUpdated($call, 'in_progress', 'call.bridged'));
            }
        }
    }

    /**
     * Handle call failed webhook
     */
    private function handleCallFailed($payload)
    {
        Log::info('Call failed', $payload);

        // Update call status to failed
        if (isset($payload['call_session_id'])) {
            $call = \App\Models\Call::where('call_session_id', $payload['call_session_id'])->first();
            if ($call) {
                $call->update([
                    'status' => 'failed',
                    'ended_at' => $payload['end_time'] ?? now(),
                    'call_control_id' => $payload['call_control_id'] ?? $call->call_control_id,
                    'call_leg_id' => $payload['call_leg_id'] ?? $call->call_leg_id,
                    'connection_id' => $payload['connection_id'] ?? $call->connection_id,
                    'calling_party_type' => $payload['calling_party_type'] ?? $call->calling_party_type,
                    'state' => $payload['state'] ?? $call->state,
                    'from_sip_uri' => $payload['from_sip_uri'] ?? $call->from_sip_uri,
                    'to_sip_uri' => $payload['to_sip_uri'] ?? $call->to_sip_uri,
                    'custom_headers' => $payload['custom_headers'] ?? $call->custom_headers,
                    'client_state' => $payload['client_state'] ?? $call->client_state,
                    'hangup_cause' => $payload['hangup_cause'] ?? null,
                    'hangup_source' => $payload['hangup_source'] ?? null,
                    'sip_hangup_cause' => $payload['sip_hangup_cause'] ?? null,
                    'call_quality_stats' => $payload['call_quality_stats'] ?? null,
                    'metadata' => array_merge($call->metadata ?? [], $payload) // Merge with existing metadata
                ]);

                // Broadcast call status update
                event(new \App\Events\CallStatusUpdated($call, 'failed', 'call.failed'));
            }
        }
    }

    /**
     * Handle recording saved webhook
     */
    private function handleRecordingSaved($payload)
    {
        Log::info('Recording saved webhook received', $payload);
        
        try {
            // Find associated call
            $call = null;
            if (isset($payload['call_session_id'])) {
                $call = Call::where('call_session_id', $payload['call_session_id'])->first();
            }

            // Extract recording data
            $recordingData = [
                'telnyx_recording_id' => $payload['recording_id'] ?? $payload['id'] ?? null,
                'call_id' => $call ? $call->id : null,
                'user_id' => $call ? $call->user_id : null,
                'call_control_id' => $payload['call_control_id'] ?? null,
                'call_leg_id' => $payload['call_leg_id'] ?? null,
                'call_session_id' => $payload['call_session_id'] ?? null,
                'conference_id' => $payload['conference_id'] ?? null,
                'channels' => $payload['channels'] ?? 'dual',
                'source' => $payload['source'] ?? null,
                'status' => $payload['status'] ?? 'completed',
                'duration_millis' => $payload['duration_millis'] ?? null,
                'download_url_mp3' => $payload['recording_urls']['mp3'] ?? $payload['download_urls']['mp3'] ?? null,
                'download_url_wav' => $payload['recording_urls']['wav'] ?? $payload['download_urls']['wav'] ?? null,
                'recording_started_at' => isset($payload['recording_started_at']) ? $payload['recording_started_at'] : null,
                'recording_ended_at' => isset($payload['recording_ended_at']) ? $payload['recording_ended_at'] : now(),
            ];

            // Create or update recording
            if (!empty($recordingData['telnyx_recording_id'])) {
                $recording = Recording::updateOrCreate(
                    ['telnyx_recording_id' => $recordingData['telnyx_recording_id']],
                    $recordingData
                );

                Log::info('Recording saved successfully', [
                    'recording_id' => $recording->id,
                    'telnyx_recording_id' => $recording->telnyx_recording_id,
                    'call_id' => $recording->call_id
                ]);
            } else {
                Log::warning('Recording webhook missing recording ID', $payload);
            }

        } catch (\Exception $e) {
            Log::error('Error processing recording saved webhook: ' . $e->getMessage(), [
                'payload' => $payload,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

}
