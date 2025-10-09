<?php

namespace App\Http\Controllers;

use App\Models\OutboundVoiceProfile;
use App\Services\TelnyxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OutboundVoiceProfileController extends Controller
{
    protected $telnyxService;

    public function __construct(TelnyxService $telnyxService)
    {
        $this->telnyxService = $telnyxService;
    }

    /**
     * Display a listing of outbound voice profiles
     */
    public function index(): Response
    {
        $profiles = OutboundVoiceProfile::with(['user'])
            ->forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('OutboundVoiceProfiles/Index', [
            'profiles' => $profiles,
        ]);
    }

    /**
     * Show the form for creating a new outbound voice profile
     */
    public function create(): Response
    {
        return Inertia::render('OutboundVoiceProfiles/Create');
    }

    /**
     * Store a newly created outbound voice profile
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'concurrent_call_limit' => 'nullable|integer|min:1',
            'enabled' => 'sometimes|boolean',
            'tags' => 'nullable|string',
            'max_destination_rate' => 'nullable|integer|min:0',
            'daily_spend_limit' => 'nullable|integer|min:0',
            'daily_spend_limit_enabled' => 'sometimes|string|in:enabled,disabled',
            'call_recording_type' => 'nullable|string|in:all,none',
            'call_recording_channels' => 'nullable|string|in:single,dual',
            'call_recording_format' => 'nullable|string|in:wav,mp3',
            'billing_group_id' => 'nullable|string',
        ]);
        DB::beginTransaction();
        
        try {
            // Prepare data for Telnyx API
            $telnyxData = [
                'name' => $request->name,
                'traffic_type' => 'conversational',
                'service_plan' => 'global',
                'enabled' => $request->boolean('enabled', true),
            ];

            // Add optional fields
            if ($request->filled('concurrent_call_limit')) {
                $telnyxData['concurrent_call_limit'] = $request->concurrent_call_limit;
            }
            if ($request->filled('tags')) {
                $telnyxData['tags'] = $request->tags;
            }
            if ($request->filled('max_destination_rate')) {
                $telnyxData['max_destination_rate'] = $request->max_destination_rate;
            }
            if ($request->filled('daily_spend_limit')) {
                $telnyxData['daily_spend_limit'] = (string) $request->daily_spend_limit;
            }
            if ($request->filled('daily_spend_limit_enabled')) {
                $telnyxData['daily_spend_limit_enabled'] = $request->daily_spend_limit_enabled;
            }
            if ($request->filled('billing_group_id')) {
                $telnyxData['billing_group_id'] = $request->billing_group_id;
            }

            // Add call recording settings if provided
            if ($request->filled('call_recording_type')) {
                $callRecording = [
                    'type' => $request->call_recording_type,
                ];
                
                if ($request->filled('call_recording_channels')) {
                    $callRecording['channels'] = $request->call_recording_channels;
                }
                if ($request->filled('call_recording_format')) {
                    $callRecording['format'] = $request->call_recording_format;
                }

                $telnyxData['call_recording'] = $callRecording;
            }

            // Create profile in Telnyx
            $telnyxResult = $this->telnyxService->createOutboundVoiceProfile($telnyxData);
           if (!$telnyxResult || !isset($telnyxResult['data'])) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Failed to create outbound voice profile in Telnyx']);
            }

            $profileData = $telnyxResult['data'];

            // Store in local database
            $profile = OutboundVoiceProfile::create([
                'user_id' => Auth::id(),
                'telnyx_profile_id' => $profileData['id'],
                'name' => $request->name,
                'traffic_type' => false,
                'service_plan' => 'global',
                'concurrent_call_limit' => $request->concurrent_call_limit,
                'enabled' => $request->boolean('enabled', true),
                'tags' => $request->tags,
                'max_destination_rate' => $request->max_destination_rate,
                'daily_spend_limit' => $request->daily_spend_limit,
                'daily_spend_limit_enabled' => $request->daily_spend_limit_enabled ?? 'disabled',
                'call_recording_type' => $request->call_recording_type ?? 'all',
                'call_recording_caller_phone_numbers' => null,
                'call_recording_callee_phone_numbers' => null,
                'call_recording_channels' => $request->call_recording_channels,
                'call_recording_format' => $request->call_recording_format ?? 'mp3',
                'billing_group_id' => $request->billing_group_id,
                'metadata' => $profileData,
            ]);

            DB::commit();

            return redirect()->route('outbound-voice-profiles.index')
                ->with('success', 'Outbound voice profile created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Outbound Voice Profile Creation Error: ' . $e->getMessage());
            
            return back()->withInput()
                ->withErrors(['error' => 'Failed to create outbound voice profile: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified outbound voice profile
     */
    public function show(OutboundVoiceProfile $outboundVoiceProfile): Response
    {
        // Check if user owns this profile
        if ($outboundVoiceProfile->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('OutboundVoiceProfiles/Show', [
            'profile' => $outboundVoiceProfile,
        ]);
    }

    /**
     * Show the form for editing the specified outbound voice profile
     */
    public function edit(OutboundVoiceProfile $outboundVoiceProfile): Response
    {
        // Check if user owns this profile
        if ($outboundVoiceProfile->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('OutboundVoiceProfiles/Edit', [
            'profile' => $outboundVoiceProfile,
        ]);
    }

    /**
     * Update the specified outbound voice profile
     */
    public function update(Request $request, OutboundVoiceProfile $outboundVoiceProfile)
    {
        // Check if user owns this profile
        if ($outboundVoiceProfile->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'concurrent_call_limit' => 'nullable|integer|min:1',
            'enabled' => 'sometimes|boolean',
            'tags' => 'nullable|string',
            'max_destination_rate' => 'nullable|integer|min:0',
            'daily_spend_limit' => 'nullable|integer|min:0',
            'daily_spend_limit_enabled' => 'sometimes|string|in:enabled,disabled',
            'call_recording_type' => 'nullable|string|in:all,none',
            'call_recording_channels' => 'nullable|string|in:single,dual',
            'call_recording_format' => 'nullable|string|in:wav,mp3',
            'billing_group_id' => 'nullable|string',
        ]);

        DB::beginTransaction();
        
        try {
            // Prepare data for Telnyx API
            $telnyxData = [
                'name' => $request->name,
                'traffic_type' => 'conversational',
                'service_plan' => 'global',
                'enabled' => $request->boolean('enabled', true),
            ];

            // Add optional fields
            if ($request->filled('concurrent_call_limit')) {
                $telnyxData['concurrent_call_limit'] = $request->concurrent_call_limit;
            }
            if ($request->filled('tags')) {
                $telnyxData['tags'] = $request->tags;
            }
            if ($request->filled('max_destination_rate')) {
                $telnyxData['max_destination_rate'] = $request->max_destination_rate;
            }
            if ($request->filled('daily_spend_limit')) {
                $telnyxData['daily_spend_limit'] = (string) $request->daily_spend_limit;
            }
            if ($request->filled('daily_spend_limit_enabled')) {
                $telnyxData['daily_spend_limit_enabled'] = $request->daily_spend_limit_enabled;
            }
            if ($request->filled('billing_group_id')) {
                $telnyxData['billing_group_id'] = $request->billing_group_id;
            }

            // Add call recording settings if provided
            if ($request->filled('call_recording_type')) {
                $callRecording = [
                    'type' => $request->call_recording_type,
                ];
                
                if ($request->filled('call_recording_channels')) {
                    $callRecording['channels'] = $request->call_recording_channels;
                }
                if ($request->filled('call_recording_format')) {
                    $callRecording['format'] = $request->call_recording_format;
                }

                $telnyxData['call_recording'] = $callRecording;
            }

            // Update profile in Telnyx
            $telnyxResult = $this->telnyxService->updateOutboundVoiceProfile($outboundVoiceProfile->telnyx_profile_id, $telnyxData);

            if (!$telnyxResult || !isset($telnyxResult['data'])) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Failed to update outbound voice profile in Telnyx']);
            }

            $profileData = $telnyxResult['data'];

            // Update local database
            $outboundVoiceProfile->update([
                'name' => $request->name,
                'traffic_type' => false,
                'service_plan' => 'global',
                'concurrent_call_limit' => $request->concurrent_call_limit,
                'enabled' => $request->boolean('enabled', true),
                'tags' => $request->tags,
                'max_destination_rate' => $request->max_destination_rate,
                'daily_spend_limit' => $request->daily_spend_limit,
                'daily_spend_limit_enabled' => $request->daily_spend_limit_enabled ?? 'disabled',
                'call_recording_type' => $request->call_recording_type ?? 'all',
                'call_recording_caller_phone_numbers' => null,
                'call_recording_callee_phone_numbers' => null,
                'call_recording_channels' => $request->call_recording_channels,
                'call_recording_format' => $request->call_recording_format ?? 'mp3',
                'billing_group_id' => $request->billing_group_id,
                'metadata' => $profileData,
            ]);

            DB::commit();

            return redirect()->route('outbound-voice-profiles.index')
                ->with('success', 'Outbound voice profile updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Outbound Voice Profile Update Error: ' . $e->getMessage());
            
            return back()->withInput()
                ->withErrors(['error' => 'Failed to update outbound voice profile: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified outbound voice profile
     */
    public function destroy(OutboundVoiceProfile $outboundVoiceProfile)
    {
        // Check if user owns this profile
        if ($outboundVoiceProfile->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        DB::beginTransaction();
        
        try {
            // Delete profile from Telnyx
            $telnyxResult = $this->telnyxService->deleteOutboundVoiceProfile($outboundVoiceProfile->telnyx_profile_id);

            if (!$telnyxResult) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Failed to delete outbound voice profile from Telnyx']);
            }

            // Delete from local database
            $outboundVoiceProfile->delete();

            DB::commit();

            return redirect()->route('outbound-voice-profiles.index')
                ->with('success', 'Outbound voice profile deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Outbound Voice Profile Delete Error: ' . $e->getMessage());
            
            return back()->withErrors(['error' => 'Failed to delete outbound voice profile: ' . $e->getMessage()]);
        }
    }

    /**
     * Get outbound voice profiles as JSON for API consumption
     */
    public function getProfilesJson()
    {
        $profiles = OutboundVoiceProfile::forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($profiles);
    }

    /**
     * Sync profiles from Telnyx
     */
    public function syncFromTelnyx()
    {
        try {
            $telnyxResult = $this->telnyxService->listOutboundVoiceProfiles(1, 100);

            if (!$telnyxResult || !isset($telnyxResult['data'])) {
                return back()->withErrors(['error' => 'Failed to fetch profiles from Telnyx']);
            }

            $syncedCount = 0;
            foreach ($telnyxResult['data'] as $profileData) {
                // Check if profile already exists
                $existingProfile = OutboundVoiceProfile::where('telnyx_profile_id', $profileData['id'])
                    ->where('user_id', Auth::id())
                    ->first();

                if (!$existingProfile) {
                    OutboundVoiceProfile::create([
                        'user_id' => Auth::id(),
                        'telnyx_profile_id' => $profileData['id'],
                        'name' => $profileData['name'] ?? 'Unnamed Profile',
                        'traffic_type' => ($profileData['traffic_type'] ?? 'conversational') === 'short_duration',
                        'service_plan' => $profileData['service_plan'] ?? 'global',
                        'concurrent_call_limit' => $profileData['concurrent_call_limit'] ?? null,
                        'enabled' => $profileData['enabled'] ?? true,
                        'tags' => isset($profileData['tags']) && is_array($profileData['tags']) ? implode(',', $profileData['tags']) : null,
                        'max_destination_rate' => $profileData['max_destination_rate'] ?? null,
                        'daily_spend_limit' => $profileData['daily_spend_limit'] ?? null,
                        'daily_spend_limit_enabled' => $profileData['daily_spend_limit_enabled'] ?? 'disabled',
                        'billing_group_id' => $profileData['billing_group_id'] ?? null,
                        'metadata' => $profileData,
                    ]);
                    $syncedCount++;
                }
            }

            return back()->with('success', "Synced {$syncedCount} new profile(s) from Telnyx.");

        } catch (\Exception $e) {
            Log::error('Outbound Voice Profile Sync Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to sync profiles: ' . $e->getMessage()]);
        }
    }
}

