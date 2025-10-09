<?php

namespace App\Http\Controllers;

use App\Models\Recording;
use App\Models\Call;
use App\Services\TelnyxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class RecordingController extends Controller
{
    protected $telnyxService;

    public function __construct(TelnyxService $telnyxService)
    {
        $this->telnyxService = $telnyxService;
    }

    /**
     * Display a listing of recordings.
     */
    public function index(Request $request)
    {
        $query = Recording::with(['call', 'user'])
            ->orderBy('created_at', 'desc');

        // Filter by user if not admin
        if (!Auth::user()->hasRole('super-admin')) {
            $query->where('user_id', Auth::id());
        }

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id') && Auth::user()->hasRole('super-admin')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $recordings = $query->paginate(25);
        
        return Inertia::render('Recordings/Index', [
            'recordings' => $recordings,
            'filters' => $request->only(['status', 'user_id', 'date_from', 'date_to'])
        ]);
    }

    /**
     * Get all recordings as JSON - fetches directly from Telnyx API.
     */
    public function list(Request $request)
    {
        try {
            $pageNumber = $request->get('page', 1);
            $pageSize = $request->get('per_page', 25);
            
            // Build filters for Telnyx API
            $filters = [];
            
            if ($request->has('status')) {
                $filters['status'] = $request->status;
            }
            
            if ($request->has('call_session_id')) {
                $filters['call_session_id'] = $request->call_session_id;
            }
            
            if ($request->has('date_from')) {
                $filters['created_at_gte'] = $request->date_from;
            }
            
            if ($request->has('date_to')) {
                $filters['created_at_lte'] = $request->date_to;
            }

            // Fetch recordings from Telnyx API
            $response = $this->telnyxService->listRecordings($pageNumber, $pageSize, $filters);

            if (!$response) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to fetch recordings from Telnyx'
                ], 500);
            }

            // Enrich recordings with local call data
            $enrichedRecordings = [];
            foreach ($response['data'] as $recording) {
                $enrichedRecording = $recording;
                
                // Try to find associated call in database
                if (isset($recording['call_session_id'])) {
                    $call = Call::where('call_session_id', $recording['call_session_id'])->first();
                    if ($call) {
                        $enrichedRecording['call'] = [
                            'id' => $call->id,
                            'from_number' => $call->from_number,
                            'to_number' => $call->to_number,
                            'direction' => $call->direction ?? 'outbound',
                            'user_id' => $call->user_id,
                        ];
                        
                        // Add to recording root level for easier access
                        $enrichedRecording['from_number'] = $call->from_number;
                        $enrichedRecording['to_number'] = $call->to_number;
                        $enrichedRecording['direction'] = $call->direction ?? 'outbound';
                        
                        // Filter by user if not admin
                        if (!Auth::user()->hasRole('super-admin') && $call->user_id !== Auth::id()) {
                            continue; // Skip recordings not belonging to this user
                        }
                    }
                }
                
                // If no call found in database, check if Telnyx provides the info
                if (!isset($enrichedRecording['call']) && !isset($enrichedRecording['from_number'])) {
                    // Try to get from Telnyx recording metadata
                    $enrichedRecording['from_number'] = $recording['from'] ?? 'Unknown';
                    $enrichedRecording['to_number'] = $recording['to'] ?? 'Unknown';
                    $enrichedRecording['direction'] = 'unknown';
                }
                
                $enrichedRecordings[] = $enrichedRecording;
            }

            return response()->json([
                'success' => true,
                'recordings' => [
                    'data' => $enrichedRecordings,
                    'meta' => $response['meta'] ?? null
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error listing recordings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to list recordings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified recording - fetches directly from Telnyx API.
     */
    public function show($telnyxRecordingId)
    {
        try {
            // Fetch recording from Telnyx API
            $response = $this->telnyxService->getRecording($telnyxRecordingId);

            if (!$response || !isset($response['data'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Recording not found'
                ], 404);
            }

            $recording = $response['data'];
            
            // Try to find associated call and check authorization
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
                    
                    $recording['call'] = [
                        'id' => $call->id,
                        'from_number' => $call->from_number,
                        'to_number' => $call->to_number,
                        'direction' => $call->direction ?? 'outbound',
                        'user_id' => $call->user_id,
                    ];
                    
                    // Add to root level for easier access
                    $recording['from_number'] = $call->from_number;
                    $recording['to_number'] = $call->to_number;
                    $recording['direction'] = $call->direction ?? 'outbound';
                }
            }
            
            // If no call found, add fallback info
            if (!isset($recording['call'])) {
                $recording['from_number'] = $recording['from'] ?? 'Unknown';
                $recording['to_number'] = $recording['to'] ?? 'Unknown';
                $recording['direction'] = 'unknown';
            }

            return response()->json([
                'success' => true,
                'recording' => $recording
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching recording: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Recording not found'
            ], 404);
        }
    }

    /**
     * Sync recordings from Telnyx API.
     */
    public function syncFromTelnyx(Request $request)
    {
        try {
            $pageNumber = $request->get('page', 1);
            $pageSize = $request->get('page_size', 50);

            // Build filters
            $filters = [];
            if ($request->has('call_session_id')) {
                $filters['call_session_id'] = $request->call_session_id;
            }

            // Fetch from Telnyx
            $response = $this->telnyxService->listRecordings($pageNumber, $pageSize, $filters);
            if (!$response) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to fetch recordings from Telnyx'
                ], 500);
            }

            $synced = 0;
            foreach ($response['data'] as $recordingData) {
                $this->storeOrUpdateRecording($recordingData);
                $synced++;
            }

            return response()->json([
                'success' => true,
                'synced' => $synced,
                'meta' => $response['meta'] ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Error syncing recordings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to sync recordings'
            ], 500);
        }
    }

    /**
     * Fetch and sync a single recording from Telnyx.
     */
    public function fetchFromTelnyx($telnyxRecordingId)
    {
        try {
            $response = $this->telnyxService->getRecording($telnyxRecordingId);

            if (!$response || !isset($response['data'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Recording not found on Telnyx'
                ], 404);
            }

            $recording = $this->storeOrUpdateRecording($response['data']);

            return response()->json([
                'success' => true,
                'recording' => $recording
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching recording from Telnyx: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch recording'
            ], 500);
        }
    }

    /**
     * Delete a recording - deletes from Telnyx API.
     */
    public function destroy($telnyxRecordingId)
    {
        try {
            // First, fetch the recording to check authorization
            $response = $this->telnyxService->getRecording($telnyxRecordingId);
            
            if (!$response || !isset($response['data'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Recording not found'
                ], 404);
            }

            $recording = $response['data'];
            
            // Check authorization
            if (isset($recording['call_session_id'])) {
                $call = Call::where('call_session_id', $recording['call_session_id'])->first();
                if ($call && !Auth::user()->hasRole('super-admin') && $call->user_id !== Auth::id()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Unauthorized'
                    ], 403);
                }
            }

            // Delete from Telnyx
            $deleted = $this->telnyxService->deleteRecording($telnyxRecordingId);
            
            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to delete recording from Telnyx'
                ], 500);
            }

            // Also delete from database if it exists
            Recording::where('telnyx_recording_id', $telnyxRecordingId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Recording deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting recording: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete recording'
            ], 500);
        }
    }

    /**
     * Download a recording file - fetches from Telnyx API.
     */
    public function download($telnyxRecordingId, Request $request)
    {
        try {
            // Fetch recording from Telnyx API
            $response = $this->telnyxService->getRecording($telnyxRecordingId);

            if (!$response || !isset($response['data'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Recording not found'
                ], 404);
            }

            $recording = $response['data'];
            
            // Check authorization
            if (isset($recording['call_session_id'])) {
                $call = Call::where('call_session_id', $recording['call_session_id'])->first();
                if ($call && !Auth::user()->hasRole('super-admin') && $call->user_id !== Auth::id()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Unauthorized'
                    ], 403);
                }
            }

            $format = $request->get('format', 'mp3'); // mp3 or wav
            $downloadUrl = null;
            
            if (isset($recording['download_urls'])) {
                $downloadUrl = $format === 'wav' 
                    ? ($recording['download_urls']['wav'] ?? null)
                    : ($recording['download_urls']['mp3'] ?? null);
            }

            if (!$downloadUrl) {
                return response()->json([
                    'success' => false,
                    'error' => 'Download URL not available'
                ], 404);
            }

            // Return the download URL for client-side download
            return response()->json([
                'success' => true,
                'download_url' => $downloadUrl
            ]);
        } catch (\Exception $e) {
            Log::error('Error downloading recording: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to download recording'
            ], 500);
        }
    }

    /**
     * Get recordings for a specific call - fetches from Telnyx API.
     */
    public function getByCall($callId)
    {
        try {
            $call = Call::findOrFail($callId);

            // Check authorization
            if (!Auth::user()->hasRole('super-admin') && $call->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized'
                ], 403);
            }

            // Fetch recordings from Telnyx API using call_session_id
            $filters = [];
            if ($call->call_session_id) {
                $filters['call_session_id'] = $call->call_session_id;
            }
            
            $response = $this->telnyxService->listRecordings(1, 100, $filters);

            if (!$response) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to fetch recordings from Telnyx'
                ], 500);
            }

            // Enrich recordings with call data
            $enrichedRecordings = [];
            foreach ($response['data'] as $recording) {
                $recording['call'] = [
                    'id' => $call->id,
                    'from_number' => $call->from_number,
                    'to_number' => $call->to_number,
                    'direction' => $call->direction ?? 'outbound',
                    'user_id' => $call->user_id,
                ];
                
                // Add to root level for easier access
                $recording['from_number'] = $call->from_number;
                $recording['to_number'] = $call->to_number;
                $recording['direction'] = $call->direction ?? 'outbound';
                
                $enrichedRecordings[] = $recording;
            }

            return response()->json([
                'success' => true,
                'recordings' => $enrichedRecordings
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching call recordings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch recordings'
            ], 500);
        }
    }

    /**
     * Get transcription for a recording.
     */
    public function getTranscription($telnyxRecordingId)
    {
        try {
            // Fetch transcription from Telnyx API
            $transcription = $this->telnyxService->getRecordingTranscriptionByRecordingId($telnyxRecordingId);
            
            if (!$transcription) {
                return response()->json([
                    'success' => false,
                    'error' => 'Transcription not found or not yet available'
                ], 404);
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
     * List all transcriptions.
     */
    public function listTranscriptions(Request $request)
    {
        try {
            $filters = [];
            
            if ($request->has('recording_id')) {
                $filters['recording_id'] = $request->recording_id;
            }
            
            if ($request->has('status')) {
                $filters['status'] = $request->status;
            }
            
            if ($request->has('page_size')) {
                $filters['page_size'] = $request->page_size;
            }

            // Fetch transcriptions from Telnyx API
            $response = $this->telnyxService->listRecordingTranscriptions($filters);

            if (!$response) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to fetch transcriptions from Telnyx'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'transcriptions' => $response['data'] ?? [],
                'meta' => $response['meta'] ?? null
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
     * Store or update recording from Telnyx data.
     */
    private function storeOrUpdateRecording($data)
    {
        // Find associated call
        $call = null;
        if (isset($data['call_session_id'])) {
            $call = Call::where('call_session_id', $data['call_session_id'])->first();
        }

        $recordingData = [
            'telnyx_recording_id' => $data['id'],
            'call_id' => $call ? $call->id : null,
            'user_id' => $call ? $call->user_id : null,
            'call_control_id' => $data['call_control_id'] ?? null,
            'call_leg_id' => $data['call_leg_id'] ?? null,
            'call_session_id' => $data['call_session_id'] ?? null,
            'conference_id' => $data['conference_id'] ?? null,
            'channels' => $data['channels'] ?? 'dual',
            'source' => $data['source'] ?? null,
            'status' => $data['status'] ?? 'processing',
            'duration_millis' => $data['duration_millis'] ?? null,
            'download_url_mp3' => $data['download_urls']['mp3'] ?? null,
            'download_url_wav' => $data['download_urls']['wav'] ?? null,
            'recording_started_at' => isset($data['recording_started_at']) ? $data['recording_started_at'] : null,
            'recording_ended_at' => isset($data['recording_ended_at']) ? $data['recording_ended_at'] : null,
        ];

        return Recording::updateOrCreate(
            ['telnyx_recording_id' => $data['id']],
            $recordingData
        );
    }
}
