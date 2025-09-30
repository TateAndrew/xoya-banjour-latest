<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Telnyx\Telnyx;
use App\Models\Call;

class TelnyxController extends Controller
{
    /**
     * Initialize Telnyx API
     */
    public function __construct()
    {
        Telnyx::setApiKey('KEY0198CD286DBF50F67B7833E70136D955_qwcmtnBzxZqKBNqx3flTIR');
    }

    /**
     * Test Telnyx connection and get SIP credentials
     */
    public function test()
    {
        try {
            // Test the connection by listing connections
            $connections = \Telnyx\Connection::all(['limit' => 10]);
            
            Log::info('Telnyx connection test successful', [
                'connections_count' => count($connections->data),
                'connections' => $connections->data
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Telnyx connection successful',
                'connections_count' => count($connections->data),
                'connections' => $connections->data
            ]);

        } catch (\Exception $e) {
            Log::error('Telnyx connection test failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test SIP connection using credentials
     */
    public function testSip()
    {
        try {
            // Test SIP credentials by attempting to list phone numbers
            $phoneNumbers = \Telnyx\PhoneNumber::all(['limit' => 5]);
            
            Log::info('SIP connection test successful', [
                'phone_numbers_count' => count($phoneNumbers->data)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'SIP connection test successful',
                'phone_numbers_count' => count($phoneNumbers->data)
            ]);

        } catch (\Exception $e) {
            Log::error('SIP connection test failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'SIP connection failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Make a simple call using SIP credentials
     */
    public function makeSimpleCall(Request $request)
    {
        try {
            $request->validate([
                'from' => 'required|string',
                'to' => 'required|string',
            ]);

            $user = Auth::user();
            $fromNumber = $request->from;
            $toNumber = $request->to;

            Log::info('Initiating simple SIP call', [
                'user_id' => $user->id,
                'from' => $fromNumber,
                'to' => $toNumber
            ]);

            // Create call using simple SIP credentials (NOM approach)
            $call = \Telnyx\Call::create([
                'from' => $fromNumber,
                'to' => $toNumber,
                'webhook_url' => url('/api/telnyx/webhook'),
                'webhook_url_method' => 'POST',
                'record' => false
            ]);

            Log::info('Simple SIP call created', [
                'call_id' => $call->id,
                'status' => $call->status
            ]);

            return response()->json([
                'success' => true,
                'call_id' => $call->id,
                'status' => $call->status,
                'message' => 'Simple SIP call initiated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Simple SIP call failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to initiate simple SIP call: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SIP credentials for a connection
     */
    public function getSipCredentials(Request $request)
    {
        try {
            $request->validate([
                'connection_id' => 'required|string',
            ]);

            $connectionId = $request->connection_id;

            // Get connection details
            $connection = \Telnyx\Connection::retrieve($connectionId);
            // dd($connection);
            Log::info('SIP credentials retrieved', [
                'connection_id' => $connectionId,
                'connection_name' => $connection->connection_name ?? 'Unknown'
            ]);
            
            return response()->json([
                'success' => true,
                'connection' => [
                    'id' => $connection->id,
                    'name' => $connection->connection_name ?? 'Unknown',
                    'record_type' => $connection->record_type ?? 'unknown',
                    'sip_uri' => $connection->sip_uri ?? null,
                    'webhook_url' => $connection->webhook_event_url ?? null,
                    'status' => $connection->active ?? 'false'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get SIP credentials: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get SIP credentials: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Make a call using Telnyx SIP trunking
     */
    public function makeCall(Request $request)
    {
        try {
            $request->validate([
                'from' => 'required|string',
                'to' => 'required|string',
            ]);

            $user = Auth::user();
            $fromNumber = $request->from;
            $toNumber = $request->to;

            Log::info('Initiating Telnyx call', [
                'user_id' => $user->id,
                'from' => $fromNumber,
                'to' => $toNumber
            ]);
            // dd( $request->all());
            // Create call using Telnyx API - Number Order Management (NOM) approach
            $call = \Telnyx\Call::create([
                'from' => $fromNumber,
                'to' => $toNumber,
                'connection_id' => $request->connection_id,
                'sip_auth_username' => 'TateAndrew1122',
                'sip_auth_password' => 'Toxic22211'
            ]);

            Log::info('Telnyx call created', [
                'call_id' => $call->id,
                'status' => $call->status
            ]);

            return response()->json([
                'success' => true,
                'call_id' => $call->id,
                'status' => $call->status,
                'message' => 'Call initiated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Call initiation failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to initiate call: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * End a call
     */
    public function endCall(Request $request)
    {
        try {
            $request->validate([
                'call_id' => 'required|string',
            ]);

            $callId = $request->call_id;

            Log::info('Ending Telnyx call', [
                'call_id' => $callId
            ]);

            // End call using Telnyx API
            $call = \Telnyx\Call::retrieve($callId);
            $call->hangup();

            Log::info('Telnyx call ended', [
                'call_id' => $callId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Call ended successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Call end failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to end call: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List available connections from database
     */
    public function listConnections()
    {
        try {
            $user = Auth::user();
            
            // Get SIP trunks from database for the current user
            $sipTrunks = \App\Models\SipTrunk::where('user_id', $user->id)
                ->where('status', 'active')
                ->with(['phoneNumbers' => function($query) {
                    $query->wherePivot('is_active', true);
                }])
                ->get();

            $connectionList = [];
            foreach ($sipTrunks as $sipTrunk) {
                $connectionList[] = [
                    'id' => $sipTrunk->id,
                    'name' => $sipTrunk->name,
                    'status' => $sipTrunk->status,
                    'sip_uri' => $sipTrunk->sip_uri,
                    'webhook_url' => $sipTrunk->webhook_url,
                    'telnyx_connection_id' => $sipTrunk->telnyx_connection_id,
                    'phone_numbers' => $sipTrunk->phoneNumbers->map(function($phoneNumber) {
                        return [
                            'id' => $phoneNumber->id,
                            'phone_number' => $phoneNumber->phone_number,
                            'assignment_type' => $phoneNumber->pivot->assignment_type,
                            'is_active' => $phoneNumber->pivot->is_active,
                            'assigned_at' => $phoneNumber->pivot->assigned_at,
                            'last_used_at' => $phoneNumber->pivot->last_used_at,
                            'settings' => $phoneNumber->pivot->settings,
                        ];
                    }),
                    'credentials' => $sipTrunk->credentials,
                    'settings' => $sipTrunk->settings,
                    'metadata' => $sipTrunk->metadata,
                ];
            }

            return response()->json([
                'success' => true,
                'connections' => $connectionList
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to list connections from database: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to list connections: ' . $e->getMessage()
            ], 500);
        }
    }

    public function listTelnyxConnections()
    {
        try {
            $connections = \Telnyx\Connection::all(['limit' => 50]);
            $connectionList = [];
            foreach ($connections->data as $connection) {
                $connectionList[] = [
                    'id' => $connection->id,
                    'name' => $connection->connection_name ?? 'Unnamed Connection',
                    'status' => $connection->active ?? 'unknown',
                    'sip_uri' => $connection->sip_uri ?? null,
                    'webhook_url' => $connection->webhook_event_url ?? null
                ];
            }

            return response()->json([
                'success' => true,
                'connections' => $connectionList
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to list connections from Telnyx: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to list connections: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Telnyx webhooks
     */
    public function webhook(Request $request)
    {
        dd($request->all());
        try {
            $payload = $request->all();
            
            Log::info('Telnyx webhook received', [
                'event_type' => $payload['event_type'] ?? 'unknown',
                'data' => $payload
            ]);

            // Handle different webhook events
            switch ($payload['event_type'] ?? '') {
                case 'call.initiated':
                    Log::info('Call initiated', $payload);
                    break;
                    
                case 'call.answered':
                    Log::info('Call answered', $payload);
                    break;
                    
                case 'call.hangup':
                    Log::info('Call ended', $payload);
                    break;
                    
                case 'call.recording.saved':
                    Log::info('Call recording saved', $payload);
                    break;
                    
                default:
                    Log::info('Unhandled webhook event', $payload);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Get user's call history
     */
    public function getCallHistory(Request $request)
    {
        try {
            $user = Auth::user();
            $limit = $request->get('limit', 50);
            
            $calls = Call::getUserCallHistory($user->id, $limit);
            
            return response()->json([
                'success' => true,
                'calls' => $calls
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get call history: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get call history: ' . $e->getMessage()
            ], 500);
        }
    }

        /**
     * Get available countries
     */
    public function getCountries()
    {
        try {
            $telnyxService = app(\App\Services\TelynxService::class);
            $result = $telnyxService->getAvailableCountries();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Failed to get countries: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to get countries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available area codes for a country
     */
    public function getAreaCodes($countryCode)
    {
        try {
            $telnyxService = app(\App\Services\TelynxService::class);
            $result = $telnyxService->getAvailableAreaCodes($countryCode);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Failed to get area codes: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to get area codes: ' . $e->getMessage()
            ], 500);
        }
    }
} 