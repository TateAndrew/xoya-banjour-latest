<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PhoneNumber;
use App\Services\TelynxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    protected $telynxService;

    public function __construct(TelynxService $telynxService)
    {
        $this->telynxService = $telynxService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with('roles')->orderBy('created_at', 'desc');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by role
        if ($request->filled('role') && $request->role !== 'all') {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $users = $query->paginate(10)->withQueryString();
        $roles = Role::all();
        $statuses = ['active', 'inactive', 'suspended'];

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'statuses' => $statuses,
            'filters' => $request->only(['search', 'status', 'role']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::all();
        $permissions = Permission::all();

        return Inertia::render('Users/Create', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('User creation request received', $request->all());
        
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive,suspended',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $user = User::create([
            'name' => $validated['name'] ?? '',
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);

        // Assign roles
        if (!empty($validated['roles'])) {
            $user->assignRole($validated['roles']);
        }

        // Assign permissions
        if (!empty($validated['permissions'])) {
            $user->givePermissionTo($validated['permissions']);
        }

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user->load(['roles', 'permissions', 'phoneNumbers']);
        
        // Get all Telnyx purchased numbers
        $telnyxNumbers = $this->telynxService->getAllPurchasedNumbers();
        
        // Get unassigned Telnyx numbers for assignment
        $unassignedTelnyxNumbers = $this->telynxService->getUnassignedNumbers();
        
        return Inertia::render('Users/Show', [
            'user' => $user,    
            'telnyxNumbers' => $telnyxNumbers['success'] ? $telnyxNumbers['data'] : [],
            'unassignedTelnyxNumbers' => $unassignedTelnyxNumbers['success'] ? $unassignedTelnyxNumbers['data'] : [],
            'telnyxError' => !$telnyxNumbers['success'] ? $telnyxNumbers['error'] : null,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $user->load(['roles', 'permissions']);
        $roles = Role::all();
        $permissions = Permission::all();

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        Log::info('User update request received', $request->all());
        
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive,suspended',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $user->update([
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $user->phone,
            'status' => $validated['status'] ?? $user->status,
        ]);

        // Update password if provided
        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        // Sync roles
        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        // Sync permissions
        if (isset($validated['permissions'])) {
            $user->syncPermissions($validated['permissions']);
        }

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Prevent deleting own account
        if ($user->id === Auth::user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Toggle user status
     */
    public function toggleStatus(User $user)
    {
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        return back()->with('success', "User status updated to {$newStatus}.");
    }

    /**
     * Assign a Telnyx phone number to user
     */
    public function assignPhoneNumber(Request $request, User $user)
    {
        $request->validate([
            'telnyx_phone_number_id' => 'required|string',
            'phone_number' => 'required|string',
        ]);
       try {
            // Check if this phone number is already assigned to another user in our system
            $existingAssignment = PhoneNumber::where('telynx_id', $request->telnyx_phone_number_id)
                ->where('user_id', '!=', $user->id)
                ->first();

            if ($existingAssignment) {
                return response()->json([
                    'success' => false,
                    'error' => 'This phone number is already assigned to another user.'
                ], 400);
            }

            // Check if user already has this number
            $userHasNumber = PhoneNumber::where('telynx_id', $request->telnyx_phone_number_id)
                ->where('user_id', $user->id)
                ->first();

            if ($userHasNumber) {
                return response()->json([
                    'success' => false,
                    'error' => 'This phone number is already assigned to this user.'
                ], 400);
            }

            // Get fresh details from Telnyx to ensure we have the most up-to-date information
            $telnyxDetails = $this->telynxService->getNumberDetails($request->telnyx_phone_number_id);
            
            $telnyxData = null;
            if ($telnyxDetails['success']) {
                $telnyxData = $telnyxDetails['data'];
            }

            // Prepare capabilities - use Telnyx data if available, otherwise fallback to request
            $capabilities = [];
            if ($telnyxData && isset($telnyxData->features)) {
                $capabilities = is_array($telnyxData->features) ? $telnyxData->features : [$telnyxData->features];
            } elseif ($request->features) {
                $capabilities = is_array($request->features) ? $request->features : [$request->features];
            } else {
                $capabilities = ['voice', 'sms']; // default capabilities
            }

            // Prepare expiry date
            $expiresAt = null;
            if ($telnyxData && isset($telnyxData->expires_at)) {
                $expiresAt = $telnyxData->expires_at;
            } elseif ($request->expires_at) {
                $expiresAt = $request->expires_at;
            }

            // Prepare monthly rate
            $monthlyRate = 0;
            if ($telnyxData && isset($telnyxData->monthly_cost)) {
                $monthlyRate = $telnyxData->monthly_cost;
            } elseif ($request->monthly_cost) {
                $monthlyRate = $request->monthly_cost;
            } elseif ($request->monthly_rate) {
                $monthlyRate = $request->monthly_rate;
            }

            // Prepare setup fee
            $setupFee = 0;
            if ($telnyxData && isset($telnyxData->upfront_cost)) {
                $setupFee = $telnyxData->upfront_cost;
            } elseif ($request->upfront_cost) {
                $setupFee = $request->upfront_cost;
            } elseif ($request->setup_fee) {
                $setupFee = $request->setup_fee;
            }

            // Create or update the phone number record in our database
            $phoneNumber = PhoneNumber::updateOrCreate(
                [
                    'telynx_id' => $request->telnyx_phone_number_id,
                ],
                [
                    'user_id' => $user->id,
                    'phone_number' => $request->phone_number,
                    'country_code' => $request->country_code ?? ($telnyxData->country_code ?? 'US'),
                    'area_code' => $request->area_code ?? ($telnyxData->area_code ?? null),
                    'city' => $request->city ?? ($telnyxData->city ?? null),
                    'state' => $request->state ?? ($telnyxData->state ?? null),
                    'carrier' => $telnyxData->carrier ?? null,
                    'number_type' => $telnyxData->phone_number_type ?? 'local',
                    'monthly_rate' => $monthlyRate,
                    'setup_fee' => $setupFee,
                    'status' => 'assigned',
                    'capabilities' => $capabilities,
                    'expires_at' => $expiresAt,
                    'purchased_at' => $telnyxData->purchased_at ?? now(),
                    'metadata' => [
                        'assigned_by' => Auth::id(),
                        'assigned_at' => now(),
                        'assignment_source' => 'admin_panel',
                        'telnyx_data' => $telnyxData ? [
                            'connection_id' => $telnyxData->connection_id ?? null,
                            'messaging_profile_id' => $telnyxData->messaging_profile_id ?? null,
                            'voice_profile_id' => $telnyxData->voice_profile_id ?? null,
                            'external_pin' => $telnyxData->external_pin ?? null,
                            'status' => $telnyxData->status ?? null,
                        ] : null,
                        'original_request_data' => $request->only([
                            'features', 'monthly_cost', 'upfront_cost', 'expires_at'
                        ])
                    ]
                ]
            );

            Log::info('Phone number assigned to user with complete Telnyx data', [
                'user_id' => $user->id,
                'phone_number' => $request->phone_number,
                'telnyx_id' => $request->telnyx_phone_number_id,
                'capabilities' => $capabilities,
                'monthly_rate' => $monthlyRate,
                'expires_at' => $expiresAt,
                'assigned_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Phone number assigned successfully with complete details',
                'phone_number' => $phoneNumber->fresh() // Get the fresh instance with all data
            ]);

        } catch (\Exception $e) {
            Log::error('Phone number assignment error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'telnyx_id' => $request->telnyx_phone_number_id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while assigning the phone number.'
            ], 500);
        }
    }

    /**
     * Unassign a phone number from user
     */
    public function unassignPhoneNumber(Request $request, User $user)
    {
        $request->validate([
            'phone_number_id' => 'required|integer|exists:phone_numbers,id',
        ]);

        try {
            $phoneNumber = PhoneNumber::where('id', $request->phone_number_id)
                ->where('user_id', $user->id)
                ->firstOrFail();
    
            // Update the phone number to remove user assignment
            $phoneNumber->delete();

            Log::info('Phone number unassigned from user', [
                'user_id' => $user->id,
                'phone_number' => $phoneNumber->phone_number,
                'unassigned_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Phone number unassigned successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Phone number unassignment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while unassigning the phone number.'
            ], 500);
        }
    }

    /**
     * Get all available Telnyx numbers for assignment
     */
    public function getAvailableTelnyxNumbers()
    {
        try {
            // Get all purchased numbers from Telnyx
            $telnyxNumbers = $this->telynxService->getAllPurchasedNumbers();         
            if (!$telnyxNumbers['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $telnyxNumbers['error'],
                    'data' => []
                ]);
            }

            // Get numbers that are already assigned in our database
            $assignedNumbers = PhoneNumber::whereNotNull('user_id')
                ->pluck('telynx_id')
                ->toArray();          
            // Filter out already assigned numbers
            $availableNumbers = array_filter($telnyxNumbers['data'], function($number) use ($assignedNumbers) {
                return !in_array($number['id'], $assignedNumbers);
            });

            return response()->json([
                'success' => true,
                'data' => array_values($availableNumbers),
                'total' => count($availableNumbers)
            ]);

        } catch (\Exception $e) {
            Log::error('Get available Telnyx numbers error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'trace' => $e->getTraceAsString(),
                'message' => $e->getMessage(),
                'error' => 'An error occurred while fetching available numbers.',
                'data' => []
            ], 500);
        }
    }

    /**
     * Refresh Telnyx numbers data
     */
    public function refreshTelnyxNumbers()
    {
        try {
            $telnyxNumbers = $this->telynxService->getAllPurchasedNumbers();
            
            return response()->json([
                'success' => $telnyxNumbers['success'],
                'data' => $telnyxNumbers['success'] ? $telnyxNumbers['data'] : [],
                'error' => !$telnyxNumbers['success'] ? $telnyxNumbers['error'] : null,
                'message' => $telnyxNumbers['success'] ? 'Telnyx numbers refreshed successfully' : 'Failed to refresh Telnyx numbers'
            ]);

        } catch (\Exception $e) {
            Log::error('Refresh Telnyx numbers error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while refreshing Telnyx numbers.',
                'data' => []
            ], 500);
        }
    }

    /**
     * Get detailed Telnyx information for a specific phone number
     */
    public function getTelnyxNumberDetails(Request $request)
    {
        $request->validate([
            'telnyx_id' => 'required|string',
        ]);

        try {
            // Get detailed information from Telnyx
            $telnyxDetails = $this->telynxService->getNumberDetails($request->telnyx_id);
            
            if (!$telnyxDetails['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $telnyxDetails['error']
                ]);
            }

            // Also get the local database record if it exists
            $localRecord = PhoneNumber::where('telnyx_id', $request->telnyx_id)->first();

            return response()->json([
                'success' => true,
                'telnyx_data' => $telnyxDetails['data'],
                'local_data' => $localRecord,
                'message' => 'Phone number details retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Get Telnyx number details error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while fetching phone number details.'
            ], 500);
        }
    }

    /**
     * Get phone number usage statistics and call history
     */
    public function getPhoneNumberUsage(Request $request, User $user)
    {
        $request->validate([
            'phone_number_id' => 'required|integer|exists:phone_numbers,id',
        ]);

        try {
            $phoneNumber = PhoneNumber::where('id', $request->phone_number_id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Get usage statistics (you can expand this based on your needs)
            $statistics = [
                'total_calls' => $user->calls()->where('from_number', $phoneNumber->phone_number)
                    ->orWhere('to_number', $phoneNumber->phone_number)->count(),
                'total_messages' => 0, // Implement if you have message tracking
                'last_used' => $user->calls()->where('from_number', $phoneNumber->phone_number)
                    ->orWhere('to_number', $phoneNumber->phone_number)
                    ->latest('created_at')->first()?->created_at,
            ];

            // Get recent call history
            $recentCalls = $user->calls()
                ->where(function($query) use ($phoneNumber) {
                    $query->where('from_number', $phoneNumber->phone_number)
                          ->orWhere('to_number', $phoneNumber->phone_number);
                })
                ->latest('created_at')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'phone_number' => $phoneNumber,
                'statistics' => $statistics,
                'recent_calls' => $recentCalls,
            ]);

        } catch (\Exception $e) {
            Log::error('Get phone number usage error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while fetching phone number usage.'
            ], 500);
        }
    }
}
