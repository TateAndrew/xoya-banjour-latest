<?php

namespace App\Services;

use Telnyx\Telnyx;
use Telnyx\Message;
use Illuminate\Support\Facades\Log;

class TelnyxService
{
    public function __construct()
    {
        Telnyx::setApiKey(config('services.telnyx.api_key'));
    }

    /**
     * Send an SMS message via Telnyx.
     */
    public function sendSms($to, $content, $from = null, $messagingProfileId = null)
    {
        try {
            $message = Message::create([
                'from' => $from ?: config('services.telnyx.phone_number'),
                'to' => $to,
                'text' => $content,
                'messaging_profile_id' => $messagingProfileId ?: config('services.telnyx.messaging_profile_id')
            ]);

            return $message->toArray();
        } catch (\Exception $e) {
            Log::error('Telnyx SMS send error: ' . $e->getMessage());
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
}
