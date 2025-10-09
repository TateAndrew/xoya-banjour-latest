<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Services\TelnyxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TranscriptionController extends Controller
{
    protected $telnyxService;

    public function __construct(TelnyxService $telnyxService)
    {
        $this->telnyxService = $telnyxService;
    }

    /**
     * Display the transcriptions page.
     */
    public function index(Request $request)
    {
        return Inertia::render('Transcriptions/Index', [
            'filters' => $request->only(['status', 'date_from', 'date_to'])
        ]);
    }

    /**
     * List all transcriptions from Telnyx API.
     */
    public function list(Request $request)
    {
        try {
            $filters = [];
            
            if ($request->has('recording_id')) {
                $filters['recording_id'] = $request->recording_id;
            }
            
            if ($request->has('status')) {
                $filters['status'] = $request->status;
            }
            
            if ($request->has('date_from')) {
                $filters['created_at_gte'] = $request->date_from;
            }
            
            if ($request->has('date_to')) {
                $filters['created_at_lte'] = $request->date_to;
            }
            
            if ($request->has('page_size')) {
                $filters['page_size'] = $request->page_size;
            }
            
            if ($request->has('page_after')) {
                $filters['page_after'] = $request->page_after;
            }

            // Fetch transcriptions from Telnyx API
            $response = $this->telnyxService->listRecordingTranscriptions($filters);

            if (!$response) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to fetch transcriptions from Telnyx'
                ], 500);
            }

            // Enrich transcriptions with call data
            $enrichedTranscriptions = [];
            foreach ($response['data'] as $transcription) {
                $enrichedTranscription = $transcription;
                
                // Try to find associated recording and call
                if (isset($transcription['recording_id'])) {
                    $recordingResponse = $this->telnyxService->getRecording($transcription['recording_id']);
                    
                    if ($recordingResponse && isset($recordingResponse['data'])) {
                        $recording = $recordingResponse['data'];
                        
                        // Try to find call in database
                        if (isset($recording['call_session_id'])) {
                            $call = Call::where('call_session_id', $recording['call_session_id'])->first();
                            
                            if ($call) {
                                $enrichedTranscription['call'] = [
                                    'id' => $call->id,
                                    'from_number' => $call->from_number,
                                    'to_number' => $call->to_number,
                                    'direction' => $call->direction ?? 'outbound',
                                    'user_id' => $call->user_id,
                                ];
                                
                                $enrichedTranscription['from_number'] = $call->from_number;
                                $enrichedTranscription['to_number'] = $call->to_number;
                                $enrichedTranscription['direction'] = $call->direction ?? 'outbound';
                                
                                // Filter by user if not admin
                                if (!Auth::user()->hasRole('super-admin') && $call->user_id !== Auth::id()) {
                                    continue;
                                }
                            }
                        }
                    }
                }
                
                $enrichedTranscriptions[] = $enrichedTranscription;
            }

            return response()->json([
                'success' => true,
                'transcriptions' => [
                    'data' => $enrichedTranscriptions,
                    'meta' => $response['meta'] ?? null
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error listing transcriptions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to list transcriptions'
            ], 500);
        }
    }

    /**
     * Show a specific transcription.
     */
    public function show($transcriptionId)
    {
        try {
            // Fetch transcription from Telnyx API
            $response = $this->telnyxService->getRecordingTranscription($transcriptionId);

            if (!$response || !isset($response['data'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Transcription not found'
                ], 404);
            }

            $transcription = $response['data'];
            
            // Try to find associated recording and call
            if (isset($transcription['recording_id'])) {
                $recordingResponse = $this->telnyxService->getRecording($transcription['recording_id']);
                
                if ($recordingResponse && isset($recordingResponse['data'])) {
                    $recording = $recordingResponse['data'];
                    
                    if (isset($recording['call_session_id'])) {
                        $call = Call::where('call_session_id', $recording['call_session_id'])->first();
                        
                        if ($call) {
                            // Check authorization
                            if (!Auth::user()->hasRole('super-admin') && $call->user_id !== Auth::id()) {
                                return response()->json([
                                    'success' => false,
                                    'error' => 'Unauthorized'
                                ], 403);
                            }
                            
                            $transcription['call'] = [
                                'id' => $call->id,
                                'from_number' => $call->from_number,
                                'to_number' => $call->to_number,
                                'direction' => $call->direction ?? 'outbound',
                                'user_id' => $call->user_id,
                            ];
                            
                            $transcription['from_number'] = $call->from_number;
                            $transcription['to_number'] = $call->to_number;
                            $transcription['direction'] = $call->direction ?? 'outbound';
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'transcription' => $transcription
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching transcription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch transcription'
            ], 500);
        }
    }

    /**
     * Get transcription by recording ID.
     */
    public function getByRecording($recordingId)
    {
        try {
            $transcription = $this->telnyxService->getRecordingTranscriptionByRecordingId($recordingId);

            if (!$transcription) {
                return response()->json([
                    'success' => false,
                    'error' => 'Transcription not found or not yet available'
                ], 404);
            }

            // Try to find associated call
            $recordingResponse = $this->telnyxService->getRecording($recordingId);
            
            if ($recordingResponse && isset($recordingResponse['data'])) {
                $recording = $recordingResponse['data'];
                
                if (isset($recording['call_session_id'])) {
                    $call = Call::where('call_session_id', $recording['call_session_id'])->first();
                    
                    if ($call) {
                        $transcription['call'] = [
                            'id' => $call->id,
                            'from_number' => $call->from_number,
                            'to_number' => $call->to_number,
                            'direction' => $call->direction ?? 'outbound',
                            'user_id' => $call->user_id,
                        ];
                        
                        $transcription['from_number'] = $call->from_number;
                        $transcription['to_number'] = $call->to_number;
                        $transcription['direction'] = $call->direction ?? 'outbound';
                    }
                }
            }

            return response()->json([
                'success' => true,
                'transcription' => $transcription
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching transcription by recording: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch transcription'
            ], 500);
        }
    }
}
