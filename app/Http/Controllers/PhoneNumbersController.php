<?php

namespace App\Http\Controllers;

use App\Models\PhoneNumber;
use App\Services\TelynxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PhoneNumbersController extends Controller
{
    protected $telnyxService;

    public function __construct(TelynxService $telnyxService)
    {
        $this->telnyxService = $telnyxService;
    }

    /**
     * Display a listing of phone numbers
     */
    public function index()
    {
        $userNumbers = PhoneNumber::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('PhoneNumbers/Index', [
            'userNumbers' => $userNumbers
        ]);
    }

    /**
     * Display the phone number purchase page
     */
    public function purchasePage()
    {
        return Inertia::render('PhoneNumbers/Purchase');
    }

    /**
     * Display the phone number management page
     */
    public function manage()
    {
        $userNumbers = PhoneNumber::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('PhoneNumbers/Manage', [
            'phoneNumbers' => $userNumbers
        ]);
    }

    /**
     * Get phone numbers as JSON for API consumption
     */
    public function getPhoneNumbersJson()
    {
        $phoneNumbers = PhoneNumber::where('user_id', Auth::id())
            ->with(['sipTrunks'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($phoneNumbers);
    }

    /**
     * Display the specified phone number
     */
    public function show(PhoneNumber $phoneNumber)
    {
        // Ensure user owns this phone number
        if ($phoneNumber->user_id !== Auth::id()) {
            abort(403);
        }

        return Inertia::render('PhoneNumbers/Show', [
            'phoneNumber' => $phoneNumber
        ]);
    }

    /**
     * Search for available phone numbers
     */
    public function search(Request $request)
    {
        $request->validate([
            'country_code' => 'required|string|size:2',
            'area_code' => 'nullable|string',
            'features' => 'required|array',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        try {
            $filters = $request->only(['country_code', 'area_code', 'features', 'limit']);
            $result = $this->telnyxService->searchNumbers($filters);

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
            Log::error('Phone number search error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while searching for phone numbers.'
            ], 500);
        }
    }

    /**
     * Purchase a phone number
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'country_code' => 'required|string|size:2',
            'features' => 'required|array',
            'area_code' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string'
        ]);

        try {
            // First, check if the number is still available
            $searchResult = $this->telnyxService->searchNumbers([
                'country_code' => $request->country_code,
                'area_code' => $request->area_code,
                'features' => $request->features,
                'limit' => 100
            ]);

            if (!$searchResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unable to verify number availability: ' . $searchResult['error']
                ], 400);
            }

            // Check if the specific number is still available
            $availableNumbers = collect($searchResult['data']);
            $targetNumber = $availableNumbers->firstWhere('phone_number', $request->phone_number);

            if (!$targetNumber) {
                return response()->json([
                    'success' => false,
                    'error' => 'This phone number is no longer available.'
                ], 400);
            }

            // Purchase the number through Telnyx
            $purchaseResult = $this->telnyxService->purchaseNumber($request->phone_number, [
                'country_code' => $request->country_code,
                'features' => $request->features
            ]);

            if (!$purchaseResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to purchase number: ' . $purchaseResult['error']
                ], 400);
            }

            // Create phone number record in database
            $phoneNumber = PhoneNumber::create([
                'user_id' => Auth::id(),
                'phone_number' => $request->phone_number,
                'country_code' => $request->country_code,
                'area_code' => $request->area_code,
                'city' => $request->city,
                'state' => $request->state,
                'telynx_id' => $purchaseResult['id'],
                'status' => 'purchased',
                'capabilities' => $request->features,
                'purchased_at' => now(),
                'monthly_rate' => $targetNumber->cost_information->monthly_cost ?? 0,
                'setup_fee' => $targetNumber->cost_information->upfront_cost ?? 0,
                'metadata' => [
                    'telnyx_data' => $purchaseResult['data'],
                    'search_filters' => $request->only(['country_code', 'area_code', 'features'])
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Phone number purchased successfully!',
                'data' => $phoneNumber
            ]);

        } catch (\Exception $e) {
            Log::error('Phone number purchase error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while purchasing the phone number.'
            ], 500);
        }
    }

    /**
     * Update phone number settings
     */
    public function update(Request $request, PhoneNumber $phoneNumber)
    {
        // Ensure user owns this phone number
        if ($phoneNumber->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'capabilities' => 'nullable|array',
            'metadata' => 'nullable|array'
        ]);

        try {
            // Update in Telnyx if needed
            if ($request->has('capabilities')) {
                $updateResult = $this->telnyxService->updateNumber($phoneNumber->telynx_id, [
                    'capabilities' => $request->capabilities
                ]);

                if (!$updateResult['success']) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to update number in Telnyx: ' . $updateResult['error']
                    ], 400);
                }
            }

            // Update local database
            $phoneNumber->update($request->only(['capabilities', 'metadata']));

            return response()->json([
                'success' => true,
                'message' => 'Phone number updated successfully!',
                'data' => $phoneNumber
            ]);

        } catch (\Exception $e) {
            Log::error('Phone number update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while updating the phone number.'
            ], 500);
        }
    }

    /**
     * Release/Delete a phone number
     */
    public function destroy(PhoneNumber $phoneNumber)
    {
        // Ensure user owns this phone number
        if ($phoneNumber->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            // Release from Telnyx
            if ($phoneNumber->telynx_id) {
                $deleteResult = $this->telnyxService->deleteNumber($phoneNumber->telynx_id);
                
                if (!$deleteResult['success']) {
                    Log::warning('Failed to delete number from Telnyx: ' . $deleteResult['error']);
                    // Continue with local deletion even if Telnyx fails
                }
            }

            // Delete from local database
            $phoneNumber->delete();

            return response()->json([
                'success' => true,
                'message' => 'Phone number released successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Phone number deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while releasing the phone number.'
            ], 500);
        }
    }

    /**
     * Get phone number details from Telnyx
     */
    public function sync(PhoneNumber $phoneNumber)
    {
        // Ensure user owns this phone number
        if ($phoneNumber->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            if (!$phoneNumber->telynx_id) {
                return response()->json([
                    'success' => false,
                    'error' => 'No Telnyx ID found for this number.'
                ], 400);
            }

            $result = $this->telnyxService->getNumberDetails($phoneNumber->telynx_id);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to sync with Telnyx: ' . $result['error']
                ], 400);
            }

            // Update local record with latest Telnyx data
            $telnyxData = $result['data'];
            $phoneNumber->update([
                'status' => $telnyxData->status ?? $phoneNumber->status,
                'capabilities' => $telnyxData->capabilities ?? $phoneNumber->capabilities,
                'metadata' => array_merge($phoneNumber->metadata ?? [], [
                    'last_sync' => now()->toISOString(),
                    'telnyx_data' => $telnyxData
                ])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Phone number synced successfully!',
                'data' => $phoneNumber
            ]);

        } catch (\Exception $e) {
            Log::error('Phone number sync error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while syncing the phone number.'
            ], 500);
        }
    }
}
