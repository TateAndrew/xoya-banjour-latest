<?php

namespace App\Http\Controllers;

use App\Models\MessagingProfile;
use App\Models\PhoneNumber;
use App\Services\MessagingProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MessagingProfileController extends Controller
{
    protected $messagingProfileService;

    public function __construct(MessagingProfileService $messagingProfileService)
    {
        $this->messagingProfileService = $messagingProfileService;
    }

    /**
     * Display a listing of messaging profiles
     */
    public function index(): Response
    {
        $messagingProfiles = MessagingProfile::with(['user'])
            ->forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('MessagingProfiles/Index', [
            'messagingProfiles' => $messagingProfiles,
        ]);
    }

    /**
     * Show the form for creating a new messaging profile
     */
    public function create(): Response
    {
        return Inertia::render('MessagingProfiles/Create', [
            'countries' => $this->getCountryOptions(),
        ]);
    }

    /**
     * Store a newly created messaging profile
     */
    public function store(Request $request)
    {
      
        $request->validate([
            // Required fields
            'name' => 'required|string',
            'whitelisted_destinations' => 'required|array|min:1',
            'whitelisted_destinations.*' => 'required|string',
            
            // Optional with defaults
            'enabled' => 'sometimes|boolean',
            'webhook_url' => 'nullable|url',
            'webhook_failover_url' => 'nullable|url',
            'webhook_api_version' => 'sometimes|string|in:1,2,2010-04-01',
            
            // Number Pool Settings (nullable object)
            'number_pool_settings' => 'nullable|array',
            'number_pool_settings.toll_free_weight' => 'required_with:number_pool_settings|numeric|min:0',
            'number_pool_settings.long_code_weight' => 'required_with:number_pool_settings|numeric|min:0',
            'number_pool_settings.skip_unhealthy' => 'required_with:number_pool_settings|boolean',
            'number_pool_settings.sticky_sender' => 'sometimes|boolean',
            'number_pool_settings.geomatch' => 'sometimes|boolean',
            
            // URL Shortener Settings (nullable object)
            'url_shortener_settings' => 'nullable|array',
            'url_shortener_settings.domain' => 'required_with:url_shortener_settings|string',
            'url_shortener_settings.prefix' => 'nullable|string',
            'url_shortener_settings.replace_blacklist_only' => 'sometimes|boolean',
            'url_shortener_settings.send_webhooks' => 'sometimes|boolean',
            
            // Additional optional fields
            'alpha_sender' => 'nullable|string',
            'daily_spend_limit' => 'nullable|string',
            'daily_spend_limit_enabled' => 'sometimes|boolean',
            'mms_fall_back_to_sms' => 'sometimes|boolean',
            'mms_transcoding' => 'sometimes|boolean',
        ]);
        DB::beginTransaction();
        
        try {
            // Create messaging profile in Telnyx
            $telnyxData = $request->only([
                'name',
                'whitelisted_destinations',
                'enabled',
                'webhook_url',
                'webhook_failover_url',
                'webhook_api_version',
                'number_pool_settings',
                'url_shortener_settings',
                'alpha_sender',
                'daily_spend_limit',
                'daily_spend_limit_enabled',
                'mms_fall_back_to_sms',
                'mms_transcoding',
            ]);
            $telnyxResult = $this->messagingProfileService->createMessagingProfile($telnyxData);
            if (!$telnyxResult['success']) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Failed to create messaging profile: ' . $telnyxResult['error']]);
            }

            // Store in local database
            $messagingProfile = MessagingProfile::create([
                'user_id' => Auth::id(),
                'telnyx_profile_id' => $telnyxResult['profile_id'],
                'name' => $request->name,
                'whitelisted_destinations' => $request->whitelisted_destinations,
                'enabled' => $request->boolean('enabled', true),
                'webhook_url' => $request->webhook_url,
                'webhook_failover_url' => $request->webhook_failover_url,
                'webhook_api_version' => $request->webhook_api_version ?? '2',
                'number_pool_settings' => $request->number_pool_settings,
                'url_shortener_settings' => $request->url_shortener_settings,
                'alpha_sender' => $request->alpha_sender,
                'daily_spend_limit' => $request->daily_spend_limit,
                'daily_spend_limit_enabled' => $request->boolean('daily_spend_limit_enabled', false),
                'mms_fall_back_to_sms' => $request->boolean('mms_fall_back_to_sms', false),
                'mms_transcoding' => $request->boolean('mms_transcoding', false),
                'metadata' => $telnyxResult['data'] ?? null,
            ]);

            DB::commit();

            return redirect()->route('messaging-profiles.index')
                ->with('success', 'Messaging profile created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Messaging Profile Creation Error: ' . $e->getMessage());
            
            return back()->withInput()
                ->withErrors(['error' => 'Failed to create messaging profile: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified messaging profile
     */
    public function show(MessagingProfile $messagingProfile): Response
    {
        // Check if user owns this profile
        if ($messagingProfile->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Get phone numbers assigned to this specific messaging profile
        $assignedPhoneNumbers = $messagingProfile->phoneNumbers()
            ->where('user_id', Auth::id())
            ->get();

        // Get available phone numbers (not assigned to any messaging profile)
        $availablePhoneNumbers = PhoneNumber::where('user_id', Auth::id())
            ->whereNull('messaging_profile_id')
            // ->where('status', 'purchased')
            ->get();
        
        return Inertia::render('MessagingProfiles/Show', [
            'messagingProfile' => $messagingProfile,
            'assignedPhoneNumbers' => $assignedPhoneNumbers,
            'availablePhoneNumbers' => $availablePhoneNumbers,
        ]);
    }

    /**
     * Show the form for editing the specified messaging profile
     */
    public function edit(MessagingProfile $messagingProfile): Response
    {
        // Check if user owns this profile
        if ($messagingProfile->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('MessagingProfiles/Edit', [
            'messagingProfile' => $messagingProfile,
            'countries' => $this->getCountryOptions(),
        ]);
    }

    /**
     * Update the specified messaging profile
     */
    public function update(Request $request, MessagingProfile $messagingProfile)
    {
        // Check if user owns this profile
        if ($messagingProfile->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'whitelisted_destinations' => 'required|array|min:1',
            'whitelisted_destinations.*' => 'required|string|size:2|regex:/^[A-Z]{2}$/',
            'enabled' => 'sometimes|boolean',
            'webhook_url' => 'nullable|url',
            'webhook_failover_url' => 'nullable|url',
            'webhook_api_version' => 'nullable|string|in:1,2,2010-04-01',
            'alpha_sender' => 'nullable|string|max:11|regex:/^[a-zA-Z0-9]+$/',
            'daily_spend_limit' => 'nullable|regex:/^[0-9]+(\.[0-9]+)?$/',
            'daily_spend_limit_enabled' => 'sometimes|boolean',
            'mms_fall_back_to_sms' => 'sometimes|boolean',
            'mms_transcoding' => 'sometimes|boolean',
        ]);

        DB::beginTransaction();
        
        try {
            // Update messaging profile in Telnyx
            $telnyxData = $request->only([
                'name',
                'whitelisted_destinations',
                'enabled',
                'webhook_url',
                'webhook_failover_url',
                'webhook_api_version',
                'alpha_sender',
                'daily_spend_limit',
                'daily_spend_limit_enabled',
                'mms_fall_back_to_sms',
                'mms_transcoding',
            ]);

            $telnyxResult = $this->messagingProfileService->updateMessagingProfile($messagingProfile->telnyx_profile_id, $telnyxData);

            if (!$telnyxResult['success']) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Failed to update messaging profile: ' . $telnyxResult['error']]);
            }

            // Update local database
            $messagingProfile->update([
                'name' => $request->name,
                'whitelisted_destinations' => $request->whitelisted_destinations,
                'enabled' => $request->boolean('enabled', true),
                'webhook_url' => $request->webhook_url,
                'webhook_failover_url' => $request->webhook_failover_url,
                'webhook_api_version' => $request->webhook_api_version ?? '2',
                'alpha_sender' => $request->alpha_sender,
                'daily_spend_limit' => $request->daily_spend_limit,
                'daily_spend_limit_enabled' => $request->boolean('daily_spend_limit_enabled', false),
                'mms_fall_back_to_sms' => $request->boolean('mms_fall_back_to_sms', false),
                'mms_transcoding' => $request->boolean('mms_transcoding', false),
                'metadata' => $telnyxResult['data'] ?? $messagingProfile->metadata,
            ]);

            DB::commit();

            return redirect()->route('messaging-profiles.index')
                ->with('success', 'Messaging profile updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Messaging Profile Update Error: ' . $e->getMessage());
            
            return back()->withInput()
                ->withErrors(['error' => 'Failed to update messaging profile: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified messaging profile
     */
    public function destroy(MessagingProfile $messagingProfile)
    {
        // Check if user owns this profile
        if ($messagingProfile->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        DB::beginTransaction();
        
        try {
            // Delete messaging profile from Telnyx
            $telnyxResult = $this->messagingProfileService->deleteMessagingProfile($messagingProfile->telnyx_profile_id);

            if (!$telnyxResult['success']) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Failed to delete messaging profile: ' . $telnyxResult['error']]);
            }

            // Delete from local database
            $messagingProfile->delete();

            DB::commit();

            return redirect()->route('messaging-profiles.index')
                ->with('success', 'Messaging profile deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Messaging Profile Delete Error: ' . $e->getMessage());
            
            return back()->withErrors(['error' => 'Failed to delete messaging profile: ' . $e->getMessage()]);
        }
    }

    /**
     * Get messaging profiles as JSON for API consumption
     */
    public function getMessagingProfilesJson()
    {
        $messagingProfiles = MessagingProfile::forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($messagingProfiles);
    }

    /**
     * Get phone numbers assigned to a messaging profile
     */
    public function getPhoneNumbers(MessagingProfile $messagingProfile)
    {
        // Check if user owns this profile
        if ($messagingProfile->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $result = $this->messagingProfileService->getMessagingProfilePhoneNumbers($messagingProfile->telnyx_profile_id);

        return response()->json($result);
    }

    /**
     * Assign a phone number to a messaging profile
     */
    public function assignPhoneNumber(Request $request, MessagingProfile $messagingProfile)
    {
        // Check if user owns this profile
        if ($messagingProfile->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
    
        $request->validate([
            'phone_number_id' => 'required|exists:phone_numbers,id',
        ]);

        $phoneNumber = PhoneNumber::where('id', $request->phone_number_id)
            ->where('user_id', Auth::id())
            ->whereNull('messaging_profile_id')
            ->first();

        if (!$phoneNumber) {
            return back()->withErrors(['error' => 'Phone number not found or already assigned.']);
        }

        DB::beginTransaction();
        
        try {
            // Assign phone number to messaging profile in Telnyx
            $telnyxResult = $this->messagingProfileService->assignPhoneNumberToProfile(
                $messagingProfile->telnyx_profile_id,
                $phoneNumber->telynx_id
            );
            if (!$telnyxResult['success']) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Failed to assign phone number in Telnyx: ' . $telnyxResult['error']]);
            }

            $phoneNumber->update([
                'messaging_profile_id' => $messagingProfile->id,
                'assigned_to_profile_at' => now(),
            ]);

            DB::commit();

            return back()->with('success', 'Phone number assigned successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Phone Number Assignment Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to assign phone number: ' . $e->getMessage()]);
        }
    }

    /**
     * Unassign a phone number from a messaging profile
     */
    public function unassignPhoneNumber(Request $request, MessagingProfile $messagingProfile)
    {
        // Check if user owns this profile
        if ($messagingProfile->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'phone_number_id' => 'required|exists:phone_numbers,id',
        ]);

        $phoneNumber = PhoneNumber::where('id', $request->phone_number_id)
            ->where('user_id', Auth::id())
            ->where('messaging_profile_id', $messagingProfile->id)
            ->first();

        if (!$phoneNumber) {
            return back()->withErrors(['error' => 'Phone number not found or not assigned to this profile.']);
        }

        DB::beginTransaction();
        
        try {
            $telnyxResult = $this->messagingProfileService->unassignPhoneNumberFromProfile(
                $phoneNumber->telynx_id
            );  
            if (!$telnyxResult['success']) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Failed to unassign phone number in Telnyx: ' . $telnyxResult['error']]);
            }

            // Update local database
            $phoneNumber->update([
                'messaging_profile_id' => null,
                'assigned_to_profile_at' => null,
            ]);
            DB::commit();
            return back()->with('success', 'Phone number unassigned successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Phone Number Unassignment Error: ' . $e->getMessage());
            
            return back()->withErrors(['error' => 'Failed to unassign phone number: ' . $e->getMessage()]);
        }
    }

    /**
     * Get country options for whitelisted destinations
     */
    private function getCountryOptions(): array
    {
        return [
            ['code' => '*', 'name' => 'All Countries'],
            ['code' => 'US', 'name' => 'United States'],
            ['code' => 'CA', 'name' => 'Canada'],
            ['code' => 'GB', 'name' => 'United Kingdom'],
            ['code' => 'AU', 'name' => 'Australia'],
            ['code' => 'DE', 'name' => 'Germany'],
            ['code' => 'FR', 'name' => 'France'],
            ['code' => 'ES', 'name' => 'Spain'],
            ['code' => 'IT', 'name' => 'Italy'],
            ['code' => 'NL', 'name' => 'Netherlands'],
            ['code' => 'BE', 'name' => 'Belgium'],
            ['code' => 'SE', 'name' => 'Sweden'],
            ['code' => 'NO', 'name' => 'Norway'],
            ['code' => 'DK', 'name' => 'Denmark'],
            ['code' => 'FI', 'name' => 'Finland'],
            ['code' => 'PL', 'name' => 'Poland'],
            ['code' => 'CZ', 'name' => 'Czech Republic'],
            ['code' => 'AT', 'name' => 'Austria'],
            ['code' => 'CH', 'name' => 'Switzerland'],
            ['code' => 'PT', 'name' => 'Portugal'],
            ['code' => 'IE', 'name' => 'Ireland'],
            ['code' => 'NZ', 'name' => 'New Zealand'],
            ['code' => 'JP', 'name' => 'Japan'],
            ['code' => 'KR', 'name' => 'South Korea'],
            ['code' => 'SG', 'name' => 'Singapore'],
            ['code' => 'HK', 'name' => 'Hong Kong'],
            ['code' => 'TW', 'name' => 'Taiwan'],
            ['code' => 'IN', 'name' => 'India'],
            ['code' => 'PK', 'name' => 'Pakistan'],
            ['code' => 'BD', 'name' => 'Bangladesh'],
            ['code' => 'LK', 'name' => 'Sri Lanka'],
            ['code' => 'MX', 'name' => 'Mexico'],
            ['code' => 'BR', 'name' => 'Brazil'],
            ['code' => 'AR', 'name' => 'Argentina'],
            ['code' => 'CL', 'name' => 'Chile'],
            ['code' => 'CO', 'name' => 'Colombia'],
            ['code' => 'PE', 'name' => 'Peru'],
            ['code' => 'VE', 'name' => 'Venezuela'],
            ['code' => 'ZA', 'name' => 'South Africa'],
            ['code' => 'EG', 'name' => 'Egypt'],
            ['code' => 'NG', 'name' => 'Nigeria'],
            ['code' => 'KE', 'name' => 'Kenya'],
            ['code' => 'GH', 'name' => 'Ghana'],
            ['code' => 'MA', 'name' => 'Morocco'],
            ['code' => 'TN', 'name' => 'Tunisia'],
        ];
    }
}
