<?php

namespace App\Http\Controllers;

use App\Models\VideoCall;
use App\Models\User;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VideoCallController extends Controller
{
    /**
     * Display the video calling interface.
     */
    public function index(): Response
    {
        $user = Auth::user();
        
        // Get user's recent and active calls
        $recentCalls = VideoCall::where('host_user_id', $user->id)
            ->orWhere('participant_user_id', $user->id)
            ->with(['host', 'participant', 'contact'])
            ->latest()
            ->take(20)
            ->get();

        // Get all users for starting new calls
        $users = User::where('id', '!=', $user->id)
            ->where('status', 'active')
            ->select('id', 'name', 'email')
            ->get();

        // Get contacts for external calls
        $contacts = Contact::select('id', 'name', 'phone_e164')
            ->orderBy('name')
            ->get();

        return Inertia::render('VideoCall/Index', [
            'recentCalls' => $recentCalls,
            'users' => $users,
            'contacts' => $contacts,
            'jitsiDomain' => config('services.jitsi.domain', 'meet.jit.si'),
        ]);
    }

    /**
     * Create a new video call room.
     */
    public function createRoom(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:one_to_one,group,conference',
            'participant_user_id' => 'nullable|exists:users,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'room_name' => 'nullable|string',
        ]);

        $user = Auth::user();
        
        // Generate unique room name if not provided
        $roomName = $validated['room_name'] ?? VideoCall::generateRoomName();

        $videoCall = VideoCall::create([
            'room_name' => $roomName,
            'host_user_id' => $user->id,
            'participant_user_id' => $validated['participant_user_id'] ?? null,
            'contact_id' => $validated['contact_id'] ?? null,
            'type' => $validated['type'],
            'status' => VideoCall::STATUS_SCHEDULED,
            'metadata' => [
                'created_by' => $user->name,
                'created_at_timestamp' => now()->timestamp,
            ],
        ]);

        return response()->json([
            'success' => true,
            'videoCall' => $videoCall->load(['host', 'participant', 'contact']),
            'message' => 'Video call room created successfully',
        ]);
    }

    /**
     * Join a video call room.
     */
    public function joinRoom(Request $request, string $roomName): Response
    {
        $user = Auth::user();
        
        $videoCall = VideoCall::where('room_name', $roomName)->first();

        if (!$videoCall) {
            abort(404, 'Video call room not found');
        }

        // Check if user can join
        if (!$videoCall->canUserJoin($user->id)) {
            abort(403, 'You are not authorized to join this call');
        }

        // Start the call if not already active
        if ($videoCall->status === VideoCall::STATUS_SCHEDULED) {
            $videoCall->start();
        }

        // Add user to participants list
        $videoCall->addParticipant([
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'type' => 'user',
        ]);

        return Inertia::render('VideoCall/Room', [
            'videoCall' => $videoCall->load(['host', 'participant', 'contact']),
            'roomName' => $roomName,
            'userName' => $user->name,
            'userEmail' => $user->email,
            'jitsiDomain' => config('services.jitsi.domain', 'meet.jit.si'),
            'jitsiConfig' => config('services.jitsi.config', []),
        ]);
    }

    /**
     * Start a quick call (instant one-to-one).
     */
    public function startQuickCall(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'participant_user_id' => 'nullable|exists:users,id',
            'contact_id' => 'nullable|exists:contacts,id',
        ]);
        if (!isset($validated['participant_user_id']) && !isset($validated['contact_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Please specify a participant or contact',
            ], 422);
        }

        $user = Auth::user();
        $roomName = VideoCall::generateRoomName();

        $videoCall = VideoCall::create([
            'room_name' => $roomName,
            'host_user_id' => $user->id,
            'participant_user_id' => $validated['participant_user_id'] ?? null,
            'contact_id' => $validated['contact_id'] ?? null,
            'type' => VideoCall::TYPE_ONE_TO_ONE,
            'status' => VideoCall::STATUS_ACTIVE,
            'started_at' => now(),
            'metadata' => [
                'quick_call' => true,
                'created_by' => $user->name,
            ],
        ]);

        // Add host to participants
        $videoCall->addParticipant([
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'type' => 'user',
            'role' => 'host',
        ]);

        return response()->json([
            'success' => true,
            'videoCall' => $videoCall->load(['host', 'participant', 'contact']),
            'roomName' => $roomName,
            'joinUrl' => route('video-call.join', ['roomName' => $roomName]),
        ]);
    }

    /**
     * Create a conference call.
     */
    public function createConference(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'participants' => 'nullable|array',
            'participants.*' => 'exists:users,id',
        ]);

        $user = Auth::user();
        $roomName = VideoCall::generateRoomName();

        $videoCall = VideoCall::create([
            'room_name' => $roomName,
            'host_user_id' => $user->id,
            'type' => VideoCall::TYPE_CONFERENCE,
            'status' => VideoCall::STATUS_SCHEDULED,
            'metadata' => [
                'conference_name' => $validated['name'],
                'invited_participants' => $validated['participants'] ?? [],
                'created_by' => $user->name,
            ],
        ]);

        return response()->json([
            'success' => true,
            'videoCall' => $videoCall->load('host'),
            'roomName' => $roomName,
            'joinUrl' => route('video-call.join', ['roomName' => $roomName]),
            'message' => 'Conference created successfully',
        ]);
    }

    /**
     * End a video call.
     */
    public function endCall(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $videoCall = VideoCall::findOrFail($id);

        // Only host can end the call
        if ($videoCall->host_user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the host can end this call',
            ], 403);
        }

        $videoCall->end();

        return response()->json([
            'success' => true,
            'videoCall' => $videoCall,
            'message' => 'Call ended successfully',
        ]);
    }

    /**
     * Update call status (participant joined, left, etc.).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:join,leave,start,end',
            'participant_data' => 'nullable|array',
        ]);

        $user = Auth::user();
        $videoCall = VideoCall::findOrFail($id);

        switch ($validated['action']) {
            case 'join':
                if ($validated['participant_data']) {
                    $videoCall->addParticipant($validated['participant_data']);
                }
                
                // Start call if it's scheduled
                if ($videoCall->status === VideoCall::STATUS_SCHEDULED) {
                    $videoCall->start();
                }
                break;

            case 'leave':
                if (isset($validated['participant_data']['id'])) {
                    $videoCall->removeParticipant($validated['participant_data']['id']);
                }
                break;

            case 'start':
                $videoCall->start();
                break;

            case 'end':
                // Only host can end the call
                if ($videoCall->host_user_id === $user->id) {
                    $videoCall->end();
                }
                break;
        }

        return response()->json([
            'success' => true,
            'videoCall' => $videoCall->fresh(),
        ]);
    }

    /**
     * Get call details.
     */
    public function getCallDetails(int $id): JsonResponse
    {
        $videoCall = VideoCall::with(['host', 'participant', 'contact'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'videoCall' => $videoCall,
        ]);
    }

    /**
     * Get user's call history.
     */
    public function history(Request $request): JsonResponse
    {
        $user = Auth::user();

        $calls = VideoCall::where('host_user_id', $user->id)
            ->orWhere('participant_user_id', $user->id)
            ->with(['host', 'participant', 'contact'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'calls' => $calls,
        ]);
    }

    /**
     * Delete a video call record.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        $videoCall = VideoCall::findOrFail($id);

        // Only host can delete
        if ($videoCall->host_user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the host can delete this call',
            ], 403);
        }

        $videoCall->delete();

        return response()->json([
            'success' => true,
            'message' => 'Call deleted successfully',
        ]);
    }
}
