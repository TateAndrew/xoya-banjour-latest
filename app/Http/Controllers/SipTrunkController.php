<?php

namespace App\Http\Controllers;

use App\Models\SipTrunk;
use App\Models\PhoneNumber;
use App\Services\TelynxService;
use App\Http\Requests\SipTrunkRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SipTrunkController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    
    protected $telnyxService;

    public function __construct(TelynxService $telnyxService)
    {
        $this->telnyxService = $telnyxService;
        $this->middleware('auth');
    }

    /**
     * Display a listing of SIP trunks
     */
    public function index(): Response
    {
        $sipTrunks = SipTrunk::with(['user', 'phoneNumbers'])
            ->forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('SipTrunks/Index', [
            'sipTrunks' => $sipTrunks,
        ]);
    }

    /**
     * Show the form for creating a new SIP trunk
     */
    public function create(): Response
    {
        $phoneNumbers = PhoneNumber::where('user_id', Auth::id())
            ->whereDoesntHave('sipTrunks')
            ->get();

        // Get available outbound voice profiles from Telnyx
        $voiceProfiles = $this->getAvailableVoiceProfiles();

        return Inertia::render('SipTrunks/Create', [
            'phoneNumbers' => $phoneNumbers,
            'voiceProfiles' => $voiceProfiles,
            'configurationOptions' => $this->getConfigurationOptions(),
        ]);
    }

    /**
     * Store a newly created SIP trunk
     */
    public function store(SipTrunkRequest $request)
    {
         try {
            // Create SIP trunk in Telnyx first
            $telnyxResult = $this->createTelnyxConnection($request->validated());
    
            if (!$telnyxResult['success']) {
                return back()->withErrors(['telnyx' => 'Failed to create Telnyx connection: ' . $telnyxResult['error']]);
            }
            // Create SIP trunk in our database
            $sipTrunk = SipTrunk::create([
                'user_id' => Auth::id(),
                'name' => $request->name,
                'telnyx_connection_id' => $telnyxResult['connection_id'],
                'connection_type' => 'credential', // Always use credential connection for Telnyx
                'webhook_url' => $request->webhook_url,
                'notes' => $request->notes,
                'status' => 'pending',
                'credentials' => [
                    'password' => $request->password,
                    'user_name' => $request->user_name,
                ],
                'settings' => [
                    'anchorsite_override' => $request->anchorsite_override,
                    'sip_uri_calling_preference' => $request->sip_uri_calling_preference,
                    'default_on_hold_comfort_noise_enabled' => $request->default_on_hold_comfort_noise_enabled,
                    'dtmf_type' => $request->dtmf_type,
                    'encode_contact_header_enabled' => $request->encode_contact_header_enabled,
                    'encrypted_media' => $request->encrypted_media,
                    'onnet_t38_passthrough_enabled' => $request->onnet_t38_passthrough_enabled,
                    'third_party_control_enabled' => $request->third_party_control_enabled ?? false,
                    'noise_suppression' => $request->noise_suppression ?? 'disabled',
                    'webhook_failover_url' => $request->webhook_failover_url,
                    'webhook_api_version' => $request->webhook_api_version,
                    'webhook_timeout_secs' => $request->webhook_timeout_secs,
                    'rtcp_port' => $request->rtcp_port,
                    'rtcp_capture_enabled' => $request->rtcp_capture_enabled,
                    'rtcp_report_frequency' => $request->rtcp_report_frequency,
                    'inbound_ani_format' => $request->inbound_ani_format,
                    'inbound_dnis_format' => $request->inbound_dnis_format,
                    'inbound_codecs' => $request->inbound_codecs,
                    'inbound_routing_method' => $request->inbound_routing_method,
                    'inbound_channel_limit' => $request->inbound_channel_limit,
                    'inbound_instant_ringback' => $request->inbound_instant_ringback ?? false,
                    'inbound_ringback_tone' => $request->inbound_ringback_tone,
                    'inbound_isup_headers' => $request->inbound_isup_headers,
                    'inbound_prack' => $request->inbound_prack,
                    'inbound_sip_compact_headers' => $request->inbound_sip_compact_headers,
                    'inbound_simultaneous_ringing' => $request->inbound_simultaneous_ringing ?? 'disabled',
                    'inbound_timeout_1xx' => $request->inbound_timeout_1xx,
                    'inbound_timeout_2xx' => $request->inbound_timeout_2xx,
                    'inbound_shaken_stir' => $request->inbound_shaken_stir,
                    'outbound_call_parking' => $request->outbound_call_parking,
                    'outbound_ani_override' => $request->outbound_ani_override,
                    'outbound_ani_override_type' => $request->outbound_ani_override_type ?? 'always',
                    'outbound_channel_limit' => $request->outbound_channel_limit,
                    'outbound_instant_ringback' => $request->outbound_instant_ringback ?? false,
                    'outbound_ringback_tone' => $request->outbound_ringback_tone,
                    'outbound_localization' => $request->outbound_localization,
                    'outbound_t38_reinvite_source' => $request->outbound_t38_reinvite_source,
                    'ios_push_credential_id' => $request->ios_push_credential_id,
                    'android_push_credential_id' => $request->android_push_credential_id,
                    'outbound_voice_profile_id' => $request->outbound_voice_profile_id,
                ],
                'metadata' => $telnyxResult['metadata'] ?? [],
            ]);

            // Assign phone numbers if provided
            if ($request->has('phone_numbers')) {
                foreach ($request->phone_numbers as $phoneNumberData) {
                    $sipTrunk->phoneNumbers()->attach($phoneNumberData['phone_number_id'], [
                        'assignment_type' => $phoneNumberData['assignment_type'] ?? 'primary',
                        'is_active' => true,
                        'assigned_at' => now(),
                    ]);
                }
            }

            return redirect()->route('sip-trunks.show', $sipTrunk)
                ->with('success', 'SIP trunk created successfully');

        } catch (\Exception $e) {
            Log::error('SIP trunk creation failed: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Failed to create SIP trunk. Please try again.'])->withInput();
        }
    }

    /**
     * Display the specified SIP trunk
     */
    public function show(SipTrunk $sipTrunk): Response
    {
        $this->authorize('view', $sipTrunk);

        $sipTrunk->load(['user', 'phoneNumbers', 'calls']);

        // Get Telnyx connection details
        $telnyxDetails = $this->getTelnyxConnectionDetails($sipTrunk->telnyx_connection_id);

        // Get available phone numbers from Telnyx and sync with local database
        $availablePhoneNumbers = [];
        if ($sipTrunk->telnyx_connection_id) {
            // Get unassigned numbers from Telnyx
            $telnyxNumbers = $this->telnyxService->getUnassignedSipTrunkNumbers();
            
            if ($telnyxNumbers['success']) {
                // Filter to only show numbers that belong to the current user
                $userPhoneNumbers = PhoneNumber::where('user_id', Auth::id())->pluck('telynx_id')->toArray();
                
                $availablePhoneNumbers = collect($telnyxNumbers['data'])
                    ->filter(function ($number) use ($userPhoneNumbers) {
                        return in_array($number['id'], $userPhoneNumbers);
                    })
                    ->map(function ($number) {
                        // Get the local phone number record
                        $localNumber = PhoneNumber::where('telynx_id', $number['id'])->first();
                        return $localNumber ? $localNumber : null;
                    })
                    ->filter()
                    ->values()
                    ->toArray();
            }
        } else {
            // Fallback to local database if no Telnyx connection
            $availablePhoneNumbers = PhoneNumber::where('user_id', Auth::id())
                ->whereDoesntHave('sipTrunks', function ($query) use ($sipTrunk) {
                    $query->where('sip_trunk_id', $sipTrunk->id);
                })
                ->get()
                ->toArray();
        }

        return Inertia::render('SipTrunks/Show', [
            'sipTrunk' => $sipTrunk,
            'telnyxDetails' => $telnyxDetails,
            'availablePhoneNumbers' => $availablePhoneNumbers,
        ]);
    }

    /**
     * Show the form for editing the specified SIP trunk
     */
    public function edit(SipTrunk $sipTrunk): Response
    {
        $this->authorize('update', $sipTrunk);

        $sipTrunk->load(['phoneNumbers']);
        
        $availablePhoneNumbers = PhoneNumber::where('user_id', Auth::id())
            ->whereDoesntHave('sipTrunks', function ($query) use ($sipTrunk) {
                $query->where('sip_trunk_id', '!=', $sipTrunk->id);
            })
            ->get();

        // Get available outbound voice profiles from Telnyx
        $voiceProfiles = $this->getAvailableVoiceProfiles();
            
        return Inertia::render('SipTrunks/Edit', [
            'sipTrunk' => $sipTrunk,
            'availablePhoneNumbers' => $availablePhoneNumbers,
            'voiceProfiles' => $voiceProfiles,
            'configurationOptions' => $this->getConfigurationOptions(),
        ]);
    }

    /**
     * Update the specified SIP trunk
     */
    public function update(SipTrunkRequest $request, SipTrunk $sipTrunk)
    {
        $this->authorize('update', $sipTrunk);

        try {
            // Update Telnyx connection if needed
            if ($sipTrunk->telnyx_connection_id) {
                $telnyxResult = $this->updateTelnyxConnection($sipTrunk->telnyx_connection_id, $request->validated());
                
                if (!$telnyxResult['success']) {
                    return back()->withErrors(['telnyx' => 'Failed to update Telnyx connection: ' . $telnyxResult['error']]);
                }
            }

            // Update SIP trunk in our database
            $sipTrunk->update([
                'name' => $request->name,
                'webhook_url' => $request->webhook_url,
                'notes' => $request->notes,
                'credentials' => [
                    'password' => $request->password,
                    'user_name' => $request->user_name,
                ],
                'settings' => [
                    'anchorsite_override' => $request->anchorsite_override,
                    'sip_uri_calling_preference' => $request->sip_uri_calling_preference,
                    'default_on_hold_comfort_noise_enabled' => $request->default_on_hold_comfort_noise_enabled,
                    'dtmf_type' => $request->dtmf_type,
                    'encode_contact_header_enabled' => $request->encode_contact_header_enabled,
                    'encrypted_media' => $request->encrypted_media,
                    'onnet_t38_passthrough_enabled' => $request->onnet_t38_passthrough_enabled,
                    'third_party_control_enabled' => $request->third_party_control_enabled ?? false,
                    'noise_suppression' => $request->noise_suppression ?? 'disabled',
                    'webhook_failover_url' => $request->webhook_failover_url,
                    'webhook_api_version' => $request->webhook_api_version,
                    'webhook_timeout_secs' => $request->webhook_timeout_secs,
                    'rtcp_port' => $request->rtcp_port,
                    'rtcp_capture_enabled' => $request->rtcp_capture_enabled,
                    'rtcp_report_frequency' => $request->rtcp_report_frequency,
                    'inbound_ani_format' => $request->inbound_ani_format,
                    'inbound_dnis_format' => $request->inbound_dnis_format,
                    'inbound_codecs' => $request->inbound_codecs,
                    'inbound_routing_method' => $request->inbound_routing_method,
                    'inbound_channel_limit' => $request->inbound_channel_limit,
                    'inbound_instant_ringback' => $request->inbound_instant_ringback ?? false,
                    'inbound_ringback_tone' => $request->inbound_ringback_tone,
                    'inbound_isup_headers' => $request->inbound_isup_headers,
                    'inbound_prack' => $request->inbound_prack,
                    'inbound_sip_compact_headers' => $request->inbound_sip_compact_headers,
                    'inbound_simultaneous_ringing' => $request->inbound_simultaneous_ringing ?? 'disabled',
                    'inbound_timeout_1xx' => $request->inbound_timeout_1xx,
                    'inbound_timeout_2xx' => $request->inbound_timeout_2xx,
                    'inbound_shaken_stir' => $request->inbound_shaken_stir,
                    'outbound_call_parking' => $request->outbound_call_parking,
                    'outbound_ani_override' => $request->outbound_ani_override,
                    'outbound_ani_override_type' => $request->outbound_ani_override_type ?? 'always',
                    'outbound_channel_limit' => $request->outbound_channel_limit,
                    'outbound_instant_ringback' => $request->outbound_instant_ringback ?? false,
                    'outbound_ringback_tone' => $request->outbound_ringback_tone,
                    'outbound_localization' => $request->outbound_localization,
                    'outbound_t38_reinvite_source' => $request->outbound_t38_reinvite_source,
                    'ios_push_credential_id' => $request->ios_push_credential_id,
                    'android_push_credential_id' => $request->android_push_credential_id,
                    'outbound_voice_profile_id' => $request->outbound_voice_profile_id,
                ],
            ]);

            // Sync phone numbers
            if ($request->has('phone_numbers')) {
                $phoneNumberData = [];
                foreach ($request->phone_numbers as $phoneNumber) {
                    $phoneNumberData[$phoneNumber['phone_number_id']] = [
                        'assignment_type' => $phoneNumber['assignment_type'] ?? 'primary',
                        'is_active' => true,
                        'assigned_at' => now(),
                    ];
                }
                $sipTrunk->phoneNumbers()->sync($phoneNumberData);
            }

            return redirect()->route('sip-trunks.show', $sipTrunk)
                ->with('success', 'SIP trunk updated successfully');

        } catch (\Exception $e) {
            Log::error('SIP trunk update failed: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Failed to update SIP trunk. Please try again.'])->withInput();
        }
    }

    /**
     * Remove the specified SIP trunk
     */
    public function destroy(SipTrunk $sipTrunk)
    {
        $this->authorize('delete', $sipTrunk);

        try {
            // Delete from Telnyx if connection exists
            if ($sipTrunk->telnyx_connection_id) {
                $telnyxResult = $this->deleteTelnyxConnection($sipTrunk->telnyx_connection_id);
                
                if (!$telnyxResult['success']) {
                    Log::warning('Failed to delete Telnyx connection: ' . $telnyxResult['error']);
                }
            }

            // Delete from our database
            $sipTrunk->delete();

            return redirect()->route('sip-trunks.index')
                ->with('success', 'SIP trunk deleted successfully');

        } catch (\Exception $e) {
            Log::error('SIP trunk deletion failed: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Failed to delete SIP trunk. Please try again.']);
        }
    }

    /**
     * Activate the SIP trunk
     */
    public function activate(SipTrunk $sipTrunk)
    {
        $this->authorize('update', $sipTrunk);

        try {
            $sipTrunk->activate();
            return back()->with('success', 'SIP trunk activated successfully');
        } catch (\Exception $e) {
            Log::error('SIP trunk activation failed: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Failed to activate SIP trunk.']);
        }
    }

    /**
     * Deactivate the SIP trunk
     */
    public function deactivate(SipTrunk $sipTrunk)
    {
        $this->authorize('update', $sipTrunk);

        try {
            $sipTrunk->deactivate();
            return back()->with('success', 'SIP trunk deactivated successfully');
        } catch (\Exception $e) {
            Log::error('SIP trunk deactivation failed: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Failed to deactivate SIP trunk.']);
        }
    }

    /**
     * Test the SIP trunk connection
     */
    public function test(SipTrunk $sipTrunk)
    {
        $this->authorize('view', $sipTrunk);

        try {
            $testResult = $this->testTelnyxConnection($sipTrunk->telnyx_connection_id);
            
            if ($testResult['success']) {
                $sipTrunk->updateHealthCheck();
                return response()->json(['success' => true, 'message' => 'Connection test successful']);
            } else {
                return response()->json(['success' => false, 'message' => 'Connection test failed: ' . $testResult['error']]);
            }
        } catch (\Exception $e) {
            Log::error('SIP trunk test failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Test failed. Please try again.']);
        }
    }

    /**
     * Create Telnyx connection
     */
    private function createTelnyxConnection(array $data): array
    {
        try {
            $result = $this->telnyxService->createSipTrunk($data);
            if ($result['success']) {
                return [
                    'success' => true,
                    'connection_id' => $result['connection_id'],
                                    'metadata' => [
                    'created_at' => now()->toISOString(),
                    'connection_type' => 'credential',
                    'sip_uri' => $result['sip_uri'] ?? null,
                    'status' => $result['status'] ?? 'active'
                ]
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $result['error']
                ];
            }
        } catch (\Exception $e) {
            Log::error('Telnyx connection creation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update Telnyx connection
     */
    private function updateTelnyxConnection(string $connectionId, array $data): array
    {
        try {
            $result = $this->telnyxService->updateSipTrunk($connectionId, $data);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'connection_id' => $result['connection_id'],
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $result['error']
                ];
            }
        } catch (\Exception $e) {
            Log::error('Telnyx connection update failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete Telnyx connection
     */
    private function deleteTelnyxConnection(string $connectionId): array
    {
        try {
            $result = $this->telnyxService->deleteSipTrunk($connectionId);
            
            if ($result['success']) {
                return [
                    'success' => true,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $result['error']
                ];
            }
        } catch (\Exception $e) {
            Log::error('Telnyx connection deletion failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get Telnyx connection details
     */
    private function getTelnyxConnectionDetails(string $connectionId): array
    {
        try {
            $result = $this->telnyxService->getSipTrunkDetails($connectionId);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'connection_id' => $result['connection_id'],
                    'status' => $result['status'] ?? 'unknown',
                    'connection_name' => $result['connection_name'] ?? 'Unknown',
                    'sip_uri' => $result['sip_uri'] ?? null,
                    'created_at' => $result['created_at'] ?? null,
                    'updated_at' => $result['updated_at'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $result['error']
                ];
            }
        } catch (\Exception $e) {
            Log::error('Telnyx connection details failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Assign a phone number to this SIP trunk
     */
    public function assignPhoneNumber(SipTrunk $sipTrunk)
    {
        $this->authorize('update', $sipTrunk);
       
        $request = request();
        $phoneNumberId = $request->input('phone_number_id');
        $assignmentType = $request->input('assignment_type', 'primary');

        // Validate the phone number belongs to the user
        $phoneNumber = PhoneNumber::where('id', $phoneNumberId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$phoneNumber) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number not found or not accessible'
            ], 404);
        }

        // Check if phone number is already assigned to this trunk
        if ($sipTrunk->phoneNumbers()->where('phone_number_id', $phoneNumberId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number is already assigned to this SIP trunk'
            ], 400);
        }

        // Check if phone number is assigned to another trunk
        if ($phoneNumber->sipTrunks()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number is already assigned to another SIP trunk'
            ], 400);
        }

        try {
            // First, assign the phone number to the SIP trunk in Telnyx
            if ($sipTrunk->telnyx_connection_id && $phoneNumber->telynx_id) {
                $telnyxResult = $this->telnyxService->assignPhoneNumberToSipTrunk(
                    $phoneNumber->telynx_id, 
                    $sipTrunk->telnyx_connection_id
                );
                
                if (!$telnyxResult['success']) {
                    Log::error('Telnyx phone number assignment failed: ' . $telnyxResult['error']);
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to assign phone number in Telnyx: ' . $telnyxResult['error']
                    ], 500);
                }
            }

            // Then assign the phone number to the SIP trunk in our database
            $sipTrunk->phoneNumbers()->attach($phoneNumberId, [
                'assignment_type' => $assignmentType,
                'is_active' => true,
                'assigned_at' => now(),
            ]);

            // Update phone number status if needed
            $phoneNumber->update(['status' => 'assigned']);

            return response()->json([
                'success' => true,
                'message' => 'Phone number assigned successfully',
                'phone_number' => $phoneNumber->fresh(['sipTrunks'])
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to assign phone number to SIP trunk: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign phone number. Please try again.'
            ], 500);
        }
    }

    /**
     * Unassign a phone number from this SIP trunk
     */
    public function unassignPhoneNumber(SipTrunk $sipTrunk, PhoneNumber $phoneNumber)
    {
        $this->authorize('update', $sipTrunk);

        // Verify the phone number belongs to the user
        if ($phoneNumber->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number not accessible'
            ], 403);
        }

        try {
            // First, unassign the phone number from the SIP trunk in Telnyx
            if ($sipTrunk->telnyx_connection_id && $phoneNumber->telynx_id) {
                $telnyxResult = $this->telnyxService->unassignPhoneNumberFromSipTrunk($phoneNumber->telynx_id);
                
                if (!$telnyxResult['success']) {
                    Log::error('Telnyx phone number unassignment failed: ' . $telnyxResult['error']);
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to unassign phone number in Telnyx: ' . $telnyxResult['error']
                    ], 500);
                }
            }

            // Remove the phone number from the SIP trunk in our database
            $sipTrunk->phoneNumbers()->detach($phoneNumber->id);

            // Update phone number status back to available
            $phoneNumber->update(['status' => 'available']);

            return response()->json([
                'success' => true,
                'message' => 'Phone number unassigned successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to unassign phone number from SIP trunk: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to unassign phone number. Please try again.'
            ], 500);
        }
    }

    /**
     * Test Telnyx connection
     */
    private function testTelnyxConnection(string $connectionId): array
    {
        try {
            $result = $this->telnyxService->testSipTrunkConnection($connectionId);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'is_active' => $result['is_active'] ?? false,
                    'status' => $result['status'] ?? 'unknown',
                    'message' => $result['message'] ?? 'Connection test completed'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $result['error']
                ];
            }
        } catch (\Exception $e) {
            Log::error('Telnyx connection test failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get configuration options for the frontend
     */
    private function getConfigurationOptions(): array
    {
        return [
            'anchorsite_override' => [
                'Latency' => 'Latency',
                'Chicago, IL' => 'Chicago, IL',
                'Ashburn, VA' => 'Ashburn, VA',
                'San Jose, CA' => 'San Jose, CA',
                'Sydney' => 'Sydney',
                'Australia' => 'Australia',
                'Amsterdam' => 'Amsterdam',
            ],
            'sip_uri_calling_preference' => [
                'disabled' => 'Disabled',
                'unrestricted' => 'Unrestricted',
                'internal' => 'Internal'
            ],
            'dtmf_type' => [
                'RFC 2833' => 'RFC 2833',
                'Inband' => 'Inband',
                'SIP INFO' => 'SIP INFO'
            ],
            'encrypted_media' => [
                'Disabled' => 'Disabled',
                'SRTP' => 'SRTP',
             ],
            'noise_suppression' => [
                'disabled' => 'Disabled',
                'low' => 'Low suppression',
                'medium' => 'Medium suppression',
                'high' => 'High suppression'
            ],
            'rtcp_port' => [
                'rtcp-mux' => 'rtcp-mux',
                'rtp+1' => 'rtp+1'
            ],
            'inbound_ani_format' => [
                'E.164-national' => 'E.164-national',
                '+E.164' => '+E.164',
                'E.164' => 'E.164',
                '+E.164-national' => '+E.164-national'
            ],
            'inbound_dnis_format' => [
                '+e164' => '+E.164',
                'e164' => 'E.164',
                'national' => 'National',
                'sip_username' => 'Sip Username'
            ],
            'inbound_codecs' => [
                'G722' => 'G.722 (HD Voice)',
                'G711U' => 'G.711 μ-law (PCMU)',
                'G711A' => 'G.711 a-law (PCMA)',
                'G729' => 'G.729 (Compressed)',
                'OPUS' => 'OPUS (Modern Codec)',
                'H.264' => 'H.264 (Video Codec)',
            ],
            'inbound_routing_method' => [
                'sequential' => 'Sequential routing',
                'round-robin' => 'Round-robin routing',
                'weighted' => 'Weighted routing'
            ],
            'inbound_simultaneous_ringing' => [
                'disabled' => 'Disabled',
                'enabled' => 'Enabled'
            ],
            'outbound_ani_override' => [
                'always' => 'Always override',
            ],
            'outbound_ani_override_type' => [
                'always' => 'Always override',
            ],
            'outbound_localization' => [
                'US' => 'United States',
                'CA' => 'Canada',
                'GB' => 'United Kingdom',
                'AU' => 'Australia',
                'DE' => 'Germany',
                'FR' => 'France',
                'ES' => 'Spain',
                'IT' => 'Italy',
                'NL' => 'Netherlands',
                'BE' => 'Belgium'
            ],
            'outbound_t38_reinvite_source' => [
                'customer' => 'Customer endpoint',
                'telnyx' => 'Telnyx network'
            ],
        ];
    }

    /**
     * Get available outbound voice profiles from Telnyx
     */
    private function getAvailableVoiceProfiles(): array
    {
        try {
            $result = $this->telnyxService->getOutboundVoiceProfiles();
            
            if ($result['success']) {
                return $result['data'];
            } else {
                Log::warning('Failed to fetch voice profiles: ' . $result['error']);
                return [];
            }
        } catch (\Exception $e) {
            Log::error('Error fetching voice profiles: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get voice profile details
     */
    public function getVoiceProfile(string $profileId)
    {
        try {
            $result = $this->telnyxService->getOutboundVoiceProfile($profileId);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error fetching voice profile details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch voice profile details'
            ], 500);
        }
    }

    /**
     * List all available voice profiles
     */
    public function listVoiceProfiles()
    {
        try {
            $result = $this->telnyxService->getOutboundVoiceProfiles();
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                    'total' => $result['total']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error listing voice profiles: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to list voice profiles'
            ], 500);
        }
    }
}
