<?php

namespace App\Http\Controllers;

use App\Services\TelynxService;
use App\Models\PhoneNumber;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class TelynxController extends Controller
{
    protected $telnyxService;

    public function __construct(TelynxService $telnyxService)
    {
        $this->telnyxService = $telnyxService;
    }

    /**
     * Show the phone number search page
     */
    public function index()
    {
        $userNumbers = Auth::user()->phoneNumbers()->latest()->get();
        return Inertia::render('PhoneNumbers/Index', [
            'userNumbers' => $userNumbers
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
            'features' => 'nullable|array',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        $filters = $request->only(['country_code', 'area_code', 'features', 'limit']);
        
        $result = $this->telnyxService->searchNumbers($filters);
        
        return response()->json($result);
    }

    /**
     * Purchase a phone number
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'country_code' => 'required|string|size:2',
            'features' => 'nullable|array'
        ]);

        // Purchase through Telnyx
        $result = $this->telnyxService->purchaseNumber(
            $request->phone_number,
            $request->only(['connection_id', 'messaging_product_id', 'voice_product_id'])
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        // Save to local database
        $phoneNumber = PhoneNumber::create([
            'user_id' => Auth::id(),
            'phone_number' => $request->phone_number,
            'country_code' => $request->country_code,
            'telynx_id' => $result['id'],
            'status' => 'purchased',
            'capabilities' => $request->features ?? ['voice', 'sms'],
            'purchased_at' => now(),
            'metadata' => $result['data']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Phone number purchased successfully',
            'phone_number' => $phoneNumber
        ]);
    }

    /**
     * Get user's phone numbers
     */
    public function myNumbers()
    {
        $numbers = Auth::user()->phoneNumbers()->latest()->get();
        
        return Inertia::render('PhoneNumbers/MyNumbers', [
            'numbers' => $numbers
        ]);
    }

    /**
     * Get phone number details
     */
    public function show($id)
    {
        $phoneNumber = PhoneNumber::where('user_id', Auth::id())
            ->findOrFail($id);

        // Get fresh data from Telnyx
        $telnyxData = $this->telnyxService->getNumberDetails($phoneNumber->telynx_id);

        return Inertia::render('PhoneNumbers/Show', [
            'phoneNumber' => $phoneNumber,
            'telnyxData' => $telnyxData
        ]);
    }

    /**
     * Update phone number settings
     */
    public function update(Request $request, $id)
    {
        $phoneNumber = PhoneNumber::where('user_id', Auth::id())
            ->findOrFail($id);

        $request->validate([
            'connection_id' => 'nullable|string',
            'messaging_product_id' => 'nullable|string',
            'voice_product_id' => 'nullable|string'
        ]);

        $result = $this->telnyxService->updateNumber(
            $phoneNumber->telynx_id,
            $request->only(['connection_id', 'messaging_product_id', 'voice_product_id'])
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        $phoneNumber->update([
            'metadata' => $result['data']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Phone number updated successfully'
        ]);
    }

    /**
     * Release a phone number
     */
    public function destroy($id)
    {
        $phoneNumber = PhoneNumber::where('user_id', Auth::id())
            ->findOrFail($id);

        $result = $this->telnyxService->deleteNumber($phoneNumber->telynx_id);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        $phoneNumber->delete();

        return response()->json([
            'success' => true,
            'message' => 'Phone number released successfully'
        ]);
    }

    /**
     * Get available countries
     */
    public function countries()
    {
        $result = $this->telnyxService->getAvailableCountries();
        
        return response()->json($result);
    }

    /**
     * Get pricing information
     */
    public function pricing(Request $request)
    {
        $request->validate([
            'country_code' => 'required|string|size:2'
        ]);

        $result = $this->telnyxService->getPricing($request->country_code);
        
        return response()->json($result);
    }
}
