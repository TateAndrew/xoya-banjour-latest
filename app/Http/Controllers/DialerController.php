<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PhoneNumber;
use App\Models\User;
use App\Services\TelynxService;
use App\Services\TranscriptionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DialerController extends Controller
{
    protected $telnyxService;
    protected $transcriptionService;

    public function __construct(TelynxService $telnyxService, TranscriptionService $transcriptionService)
    {
        $this->telnyxService = $telnyxService;
        $this->transcriptionService = $transcriptionService;
    }

    /**
     * Show the main dialer interface
     */
    public function index()
    {
        $user = Auth::user();
        $phoneNumbers = $user->phoneNumbers()->where('status', 'purchased')->get();
        $sipCredentials = $this->getSipCredentials($user);
        
        // Get recent calls for the user (last 10 calls)
        $recentCalls = \App\Models\Call::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'from_number' => $call->from_number,
                    'to_number' => $call->to_number,
                    'direction' => $call->direction,
                    'status' => $call->status,
                    'duration' => $call->duration_formatted ?? $call->duration,
                    'created_at' => $call->created_at,
                    'time_ago' => $call->created_at->diffForHumans(),
                ];
            });
        
        return Inertia::render('Dialer/Index', [
            'phoneNumbers' => $phoneNumbers,
            'user' => $user,
            'sipCredentials' => $sipCredentials,
            'recentCalls' => $recentCalls,
        ]);
    }

    /**
     * Initiate a call
     */
    public function initiateCall(Request $request)
    {
        $request->validate([
            'from_number' => 'required|string',
            'to_number' => 'required|string',
        ]);
        // dd($request->all());
        try {
            $user = Auth::user();
            
            // Get SIP credentials for the user
            $sipCredentials = $this->getSipCredentials($user);
            // Create call session
            $callData = [
                'from' => $request->from_number,
                'to' => $request->to_number,
                'call_type' => 'voice',
                'sip_trunk_id' => $sipCredentials['sip_trunk_id'] ?? 'default',
                'user_id' => $user->id,
                'status' => 'initiating',
                'metadata' => [
                    'call_type' => 'voice',
                ]
            ];
           
            // Store call in database
            $call = \App\Models\Call::create([
                'user_id' => $user->id,
                'from_number' => $request->from_number,
                'to_number' => $request->to_number,
                'call_type' => 'voice',
                'status' => 'initiating',
                'sip_trunk_id' => $sipCredentials['sip_trunk_id'] ?? 'default',
            ]);
            
            // Initialize Telnyx call
            $telnyxCall = $this->telnyxService->createCall($callData);

            return response()->json([
                'success' => true,
                'call_id' => $call->id,
                'telnyx_call_id' => $telnyxCall['data']['id'] ?? null,
                'sip_credentials' => $sipCredentials,
                'webrtc_credentials' => [
                    'username' => $sipCredentials['sip_username'],
                    'password' => $sipCredentials['sip_password'],
                    'domain' => $sipCredentials['sip_domain'],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Call initiation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Join a conference call
     */
    public function joinConference(Request $request)
    {
        $request->validate([
            'conference_id' => 'required|string',
            'phone_number' => 'required|string',
        ]);

        try {
            $user = Auth::user();
            
            // Find existing conference or create new one
            $conference = \App\Models\Conference::firstOrCreate([
                'conference_id' => $request->conference_id,
            ], [
                'host_id' => $user->id,
                'status' => 'active',
                'created_at' => now(),
            ]);

            // Add participant to conference
            $participant = $conference->participants()->create([
                'user_id' => $user->id,
                'phone_number' => $request->phone_number,
                'joined_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'conference_id' => $conference->conference_id,
                'participant_id' => $participant->id,
                'sip_credentials' => $this->getSipCredentials($user),
            ]);

        } catch (\Exception $e) {
            Log::error('Conference join error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * End a call
     */
    public function endCall(Request $request)
    {
        $request->validate([
            'call_id' => 'required|exists:calls,id',
        ]);

        try {
            $call = \App\Models\Call::find($request->call_id);
            $call->update(['status' => 'ended', 'ended_at' => now()]);

            // End call in Telnyx
            if ($call->telnyx_call_id) {
                $this->telnyxService->endCall($call->telnyx_call_id);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Call end error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get call history
     */
    public function callHistory()
    {
        $user = Auth::user();
        $calls = $user->calls()->with('phoneNumber')->orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('Dialer/History', [
            'calls' => $calls,
        ]);
    }

    /**
     * Get SIP credentials for the user
     */
    private function getSipCredentials($user)
    {
        // TODO: Replace with your actual Telnyx credentials
        // Please provide your real Telnyx credentials and connection ID
        $credentials = [
            'sip_username' => env('TELNYX_SIP_USERNAME', 'TateAndrew1122'),
            'sip_password' => env('TELNYX_SIP_PASSWORD', 'Toxic2225411'),
            'sip_domain' => env('TELNYX_SIP_DOMAIN', 'sip.telnyx.com'),
            'sip_port' => env('TELNYX_SIP_PORT', 5060),
            'connection_id' => env('TELNYX_CONNECTION_ID', '2751034134250915524'),
            'api_key' => env('TELNYX_API_KEY'),
            'webrtc_credentials' => [
                'username' => env('TELNYX_WEBRTC_USERNAME', 'webrtc_' . $user->id),
                'password' => env('TELNYX_WEBRTC_PASSWORD', 'webrtc_pass_' . $user->id),
            ]
        ];
        
        // Log the credentials for debugging (remove in production)
        \Log::info('Telnyx Credentials being used:', array_merge($credentials, ['sip_password' => '***']));
        
        return $credentials;
    }

    /**
     * Generate a unique conference ID
     */
    private function generateConferenceId()
    {
        return 'conf_' . uniqid() . '_' . time();
    }

    /**
     * Start transcription for a call
     */
    public function startTranscription(Request $request)
    {
        $request->validate([
            'call_control_id' => 'required|string',
            'language' => 'sometimes|string|in:en,es,fr,de,it,pt,ru,ja,ko,zh',
            'transcription_engine' => 'sometimes|string|in:A,B'
        ]);

        try {
            $callControlId = $request->input('call_control_id');
            $language = $request->input('language', 'en');
            $transcriptionEngine = $request->input('transcription_engine', 'B');

            // Start transcription using TranscriptionService
            $clientState = base64_encode('transcription_started_' . time() . '_' . $callControlId);
            
            Log::info('Starting transcription with client_state', [
                'call_control_id' => $callControlId,
                'client_state_raw' => 'transcription_started_' . time() . '_' . $callControlId,
                'client_state_encoded' => $clientState,
                'language' => $language,
                'engine' => $transcriptionEngine
            ]);
            
            $transcript = $this->transcriptionService->startTranscription(
                $callControlId,
                $language,
                [
                    'transcription_engine' => $transcriptionEngine,
                    'client_state' => $clientState
                ]
            );

            if ($transcript) {
                Log::info('Transcription started successfully', [
                    'call_control_id' => $callControlId,
                    'transcript_id' => $transcript->id,
                    'language' => $language,
                    'engine' => $transcriptionEngine
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Transcription started successfully',
                    'transcript' => [
                        'id' => $transcript->id,
                        'status' => $transcript->status,
                        'language' => $transcript->language,
                        'started_at' => $transcript->started_at
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to start transcription'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error starting transcription: ' . $e->getMessage(), [
                'call_control_id' => $request->input('call_control_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start transcription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get call details by session ID
     */
    public function getCall(Request $request, $sessionId)
    {
        try {
            $call = \App\Models\Call::where('call_session_id', $sessionId)->first();
            
            if (!$call) {
                return response()->json([
                    'success' => false,
                    'message' => 'Call not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $call->id,
                    'call_session_id' => $call->call_session_id,
                    'call_control_id' => $call->call_control_id,
                    'from_number' => $call->from_number,
                    'to_number' => $call->to_number,
                    'status' => $call->status,
                    'direction' => $call->direction,
                    'start_time' => $call->start_time,
                    'answered_at' => $call->answered_at,
                    'ended_at' => $call->ended_at,
                    'duration' => $call->duration,
                    'metadata' => $call->metadata
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting call details: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get call details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop transcription for a call
     */
    public function stopTranscription(Request $request)
    {
        $request->validate([
            'call_control_id' => 'required|string'
        ]);

        try {
            $callControlId = $request->input('call_control_id');

            // Stop transcription using TranscriptionService
            $success = $this->transcriptionService->stopTranscription($callControlId);

            if ($success) {
                Log::info('Transcription stopped successfully', [
                    'call_control_id' => $callControlId
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Transcription stopped successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to stop transcription'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error stopping transcription: ' . $e->getMessage(), [
                'call_control_id' => $request->input('call_control_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to stop transcription: ' . $e->getMessage()
            ], 500);
        }
    }
}
