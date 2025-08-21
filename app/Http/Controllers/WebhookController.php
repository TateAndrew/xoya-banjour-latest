<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Call;
use App\Models\User;

class WebhookController extends Controller
{
    /**
     * Handle Telnyx webhooks
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        $eventType = $payload['data']['event_type'] ?? null;
        
        Log::info('Telnyx Webhook received', [
            'event_type' => $eventType,
            'payload' => $payload
        ]);

        switch ($eventType) {
            case 'call.initiated':
                return $this->handleCallInitiated($payload);
            
            case 'call.answered':
                return $this->handleCallAnswered($payload);
            
            case 'call.hangup':
                return $this->handleCallHangup($payload);
            
            case 'call.recording.saved':
                return $this->handleCallRecordingSaved($payload);
            
            default:
                Log::info('Unhandled webhook event type: ' . $eventType);
                return response()->json(['status' => 'ignored']);
        }
    }

    /**
     * Handle call initiated event
     */
    private function handleCallInitiated($payload)
    {
        $callData = $payload['data']['payload'];
        $telnyxCallId = $callData['id'] ?? null;
        
        if ($telnyxCallId) {
            // Update call status in database
            $call = Call::where('telnyx_call_id', $telnyxCallId)->first();
            if ($call) {
                $call->update([
                    'status' => 'initiating',
                    'metadata' => array_merge($call->metadata ?? [], [
                        'webhook_received' => now()->toISOString(),
                        'event_type' => 'call.initiated'
                    ])
                ]);
            }
        }

        return response()->json(['status' => 'processed']);
    }

    /**
     * Handle call answered event
     */
    private function handleCallAnswered($payload)
    {
        $callData = $payload['data']['payload'];
        $telnyxCallId = $callData['id'] ?? null;
        
        if ($telnyxCallId) {
            $call = Call::where('telnyx_call_id', $telnyxCallId)->first();
            if ($call) {
                $call->update([
                    'status' => 'answered',
                    'answered_at' => now(),
                    'metadata' => array_merge($call->metadata ?? [], [
                        'webhook_received' => now()->toISOString(),
                        'event_type' => 'call.answered'
                    ])
                ]);
            }
        }

        return response()->json(['status' => 'processed']);
    }

    /**
     * Handle call hangup event
     */
    private function handleCallHangup($payload)
    {
        $callData = $payload['data']['payload'];
        $telnyxCallId = $callData['id'] ?? null;
        
        if ($telnyxCallId) {
            $call = Call::where('telnyx_call_id', $telnyxCallId)->first();
            if ($call) {
                $call->update([
                    'status' => 'ended',
                    'ended_at' => now(),
                    'duration' => $callData['duration_sec'] ?? 0,
                    'cost' => $callData['cost'] ?? 0,
                    'metadata' => array_merge($call->metadata ?? [], [
                        'webhook_received' => now()->toISOString(),
                        'event_type' => 'call.hangup',
                        'hangup_cause' => $callData['hangup_cause'] ?? null
                    ])
                ]);
            }
        }

        return response()->json(['status' => 'processed']);
    }

    /**
     * Handle call recording saved event
     */
    private function handleCallRecordingSaved($payload)
    {
        $callData = $payload['data']['payload'];
        $telnyxCallId = $callData['call_control_id'] ?? null;
        
        if ($telnyxCallId) {
            $call = Call::where('telnyx_call_id', $telnyxCallId)->first();
            if ($call) {
                $call->update([
                    'recording_url' => $callData['recording_urls']['mp3'] ?? null,
                    'metadata' => array_merge($call->metadata ?? [], [
                        'webhook_received' => now()->toISOString(),
                        'event_type' => 'call.recording.saved',
                        'recording_urls' => $callData['recording_urls'] ?? []
                    ])
                ]);
            }
        }

        return response()->json(['status' => 'processed']);
    }
}
