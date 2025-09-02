<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming SMS webhooks from Telnyx.
     */
    public function handleSmsWebhook(Request $request)
    {
        Log::info('SMS Webhook received', $request->all());

        $data = $request->all();
        
        if ($data['event_type'] === 'message.received') {
            $this->handleInboundSms($data['data']['payload']);
        }

        return response()->json(['status' => 'success']);
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
}
