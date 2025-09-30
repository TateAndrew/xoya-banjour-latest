<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TelnyxController;
use App\Http\Controllers\PhoneNumbersController;
use App\Http\Controllers\SipTrunkController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MessagingProfileController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\DialerController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dialer', function () {
    return Inertia::render('Dialer/Index', [
        'phoneNumbers' => \App\Models\PhoneNumber::where('user_id', Auth::id())->get(),
        'user' => Auth::user()
    ]);
})->middleware(['auth', 'verified'])->name('dialer');

Route::get('/dialer/history', function () {
    return Inertia::render('Dialer/History');
})->middleware(['auth', 'verified'])->name('dialer.history');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Telnyx API Routes
Route::middleware('auth')->group(function () {
    Route::get('/api/telnyx/test', [TelnyxController::class, 'test']);
    Route::get('/api/telnyx/test-sip', [TelnyxController::class, 'testSip']);
    Route::get('/api/telnyx/connections', [TelnyxController::class, 'listConnections']);
    Route::get('/api/telnyx/telnyx-connections', [TelnyxController::class, 'listTelnyxConnections']); // Fallback to Telnyx API
    Route::post('/api/telnyx/sip-credentials', [TelnyxController::class, 'getSipCredentials']);
    Route::post('/api/telnyx/call', [TelnyxController::class, 'makeCall']);
    Route::post('/api/telnyx/simple-call', [TelnyxController::class, 'makeSimpleCall']);
    Route::post('/api/telnyx/end-call', [TelnyxController::class, 'endCall']);
    Route::get('/api/calls/history', [TelnyxController::class, 'getCallHistory']);
    Route::get('/api/telnyx/countries', [TelnyxController::class, 'getCountries']);
    Route::get('/api/telnyx/area-codes/{countryCode}', [TelnyxController::class, 'getAreaCodes']);
});

// Phone Numbers Routes
Route::middleware('auth')->group(function () {
    Route::get('/phone-numbers', [PhoneNumbersController::class, 'index'])->name('phone-numbers.index');
    Route::get('/phone-numbers/manage', [PhoneNumbersController::class, 'manage'])->name('phone-numbers.manage');
    Route::get('/phone-numbers/purchase', [PhoneNumbersController::class, 'purchasePage'])->name('phone-numbers.purchase-page');
    Route::get('/phone-numbers/{phoneNumber}', [PhoneNumbersController::class, 'show'])->name('phone-numbers.show');
    Route::post('/phone-numbers/search', [PhoneNumbersController::class, 'search'])->name('phone-numbers.search');
    Route::post('/phone-numbers/purchase', [PhoneNumbersController::class, 'purchase'])->name('phone-numbers.purchase');
    Route::put('/phone-numbers/{phoneNumber}', [PhoneNumbersController::class, 'update'])->name('phone-numbers.update');
    Route::delete('/phone-numbers/{phoneNumber}', [PhoneNumbersController::class, 'destroy'])->name('phone-numbers.destroy');
    Route::post('/phone-numbers/{phoneNumber}/sync', [PhoneNumbersController::class, 'sync'])->name('phone-numbers.sync');
    
    // API endpoint for phone numbers (used by SIP trunk assignment)
    Route::get('/api/phone-numbers', [PhoneNumbersController::class, 'getPhoneNumbersJson'])->name('phone-numbers.json');
});

// SIP Trunks Routes
Route::middleware('auth')->group(function () {
    Route::get('/sip-trunks', [SipTrunkController::class, 'index'])->name('sip-trunks.index');
    Route::get('/sip-trunks/create', [SipTrunkController::class, 'create'])->name('sip-trunks.create');
    Route::post('/sip-trunks', [SipTrunkController::class, 'store'])->name('sip-trunks.store');
    Route::get('/sip-trunks/{sipTrunk}', [SipTrunkController::class, 'show'])->name('sip-trunks.show');
    Route::get('/sip-trunks/{sipTrunk}/edit', [SipTrunkController::class, 'edit'])->name('sip-trunks.edit');
    Route::put('/sip-trunks/{sipTrunk}', [SipTrunkController::class, 'update'])->name('sip-trunks.update');
    Route::delete('/sip-trunks/{sipTrunk}', [SipTrunkController::class, 'destroy'])->name('sip-trunks.destroy');
    Route::post('/sip-trunks/{sipTrunk}/activate', [SipTrunkController::class, 'activate'])->name('sip-trunks.activate');
    Route::post('/sip-trunks/{sipTrunk}/deactivate', [SipTrunkController::class, 'deactivate'])->name('sip-trunks.deactivate');
    Route::post('/sip-trunks/{sipTrunk}/test', [SipTrunkController::class, 'test'])->name('sip-trunks.test');
    Route::post('/sip-trunks/{sipTrunk}/assign-number', [SipTrunkController::class, 'assignPhoneNumber'])->name('sip-trunks.assign-number');
    Route::delete('/sip-trunks/{sipTrunk}/unassign-number/{phoneNumber}', [SipTrunkController::class, 'unassignPhoneNumber'])->name('sip-trunks.unassign-number');
    
    // Voice Profile Routes
    Route::get('/api/voice-profiles', [SipTrunkController::class, 'listVoiceProfiles'])->name('voice-profiles.list');
    Route::get('/api/voice-profiles/{profileId}', [SipTrunkController::class, 'getVoiceProfile'])->name('voice-profiles.show');
    
    // Test validation route
    Route::post('/test-validation', function () {
        $validator = Validator::make(request()->all(), [
            'name' => 'required|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator);
        }
        
        return response()->json(['success' => true]);
    })->name('test-validation');
});

// User Management Routes
Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class);
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    
    // Phone number assignment routes
    Route::post('/users/{user}/assign-phone-number', [UserController::class, 'assignPhoneNumber'])->name('users.assign-phone-number');
    Route::post('/users/{user}/unassign-phone-number', [UserController::class, 'unassignPhoneNumber'])->name('users.unassign-phone-number');
    Route::get('/api/available-telnyx-numbers', [UserController::class, 'getAvailableTelnyxNumbers'])->name('users.available-telnyx-numbers');
    Route::get('/api/refresh-telnyx-numbers', [UserController::class, 'refreshTelnyxNumbers'])->name('users.refresh-telnyx-numbers');
    
    // Phone number details routes
    Route::get('/api/telnyx-number-details', [UserController::class, 'getTelnyxNumberDetails'])->name('users.telnyx-number-details');
    Route::get('/users/{user}/phone-number-usage', [UserController::class, 'getPhoneNumberUsage'])->name('users.phone-number-usage');
});

// Messaging Profile Routes
Route::middleware('auth')->group(function () {
    Route::get('/messaging-profiles', [MessagingProfileController::class, 'index'])->name('messaging-profiles.index');
    Route::get('/messaging-profiles/create', [MessagingProfileController::class, 'create'])->name('messaging-profiles.create');
    Route::post('/messaging-profiles', [MessagingProfileController::class, 'store'])->name('messaging-profiles.store');
    Route::get('/messaging-profiles/{messagingProfile}', [MessagingProfileController::class, 'show'])->name('messaging-profiles.show');
    Route::get('/messaging-profiles/{messagingProfile}/edit', [MessagingProfileController::class, 'edit'])->name('messaging-profiles.edit');
    Route::put('/messaging-profiles/{messagingProfile}', [MessagingProfileController::class, 'update'])->name('messaging-profiles.update');
    Route::delete('/messaging-profiles/{messagingProfile}', [MessagingProfileController::class, 'destroy'])->name('messaging-profiles.destroy');
    Route::get('/messaging-profiles/{messagingProfile}/phone-numbers', [MessagingProfileController::class, 'getPhoneNumbers'])->name('messaging-profiles.phone-numbers');
    Route::post('/messaging-profiles/{messagingProfile}/assign-phone', [MessagingProfileController::class, 'assignPhoneNumber'])->name('messaging-profiles.assign-phone');
    Route::delete('/messaging-profiles/{messagingProfile}/unassign-phone', [MessagingProfileController::class, 'unassignPhoneNumber'])->name('messaging-profiles.unassign-phone');
    
    // API endpoint for messaging profiles (used by other components)
    Route::get('/api/messaging-profiles', [MessagingProfileController::class, 'getMessagingProfilesJson'])->name('messaging-profiles.json');
});

// SMS Messenger Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/messenger', [SmsController::class, 'index'])->name('messenger.index');
    Route::get('/messenger/test', function () {
        return Inertia::render('Messenger/Test');
    })->name('messenger.test');
    Route::get('/messenger/conversation/{conversation}', [SmsController::class, 'showConversation'])->name('messenger.conversation');
    Route::post('/messenger/send', [SmsController::class, 'sendMessage'])->name('messenger.send');
    Route::post('/messenger/start-conversation', [SmsController::class, 'startConversation'])->name('messenger.start-conversation');
    Route::get('/messenger/contacts', [SmsController::class, 'getContacts'])->name('messenger.contacts');
    Route::post('/messenger/contacts', [SmsController::class, 'storeContact'])->name('messenger.contacts.store');
    Route::post('/messenger/conversation/{conversation}/read', [SmsController::class, 'markAsRead'])->name('messenger.mark-as-read');
    Route::get('/messenger/conversation/{conversation}/messages', [SmsController::class, 'getMessages'])->name('messenger.messages');
    Route::get('/messenger/contacts/search', [SmsController::class, 'searchContacts'])->name('messenger.contacts.search');
    
    // API endpoints for AJAX requests
    Route::get('/api/conversations', [SmsController::class, 'getConversations'])->name('api.conversations');
    
    // Call API endpoints (authenticated)
    Route::get('/api/calls/{sessionId}', [DialerController::class, 'getCall']);
    
    // Transcription endpoints (authenticated)
    Route::post('/api/transcription/start', [DialerController::class, 'startTranscription']);
    Route::post('/api/transcription/stop', [DialerController::class, 'stopTranscription']);
});


// Webhook routes (no auth required)
Route::post('/api/telnyx/webhook', [TelnyxController::class, 'webhook']);
Route::post('/webhooks/telnyx/sms', [WebhookController::class, 'handleSmsWebhook']);
Route::post('/webhooks/telnyx/dlr', [WebhookController::class, 'handleDeliveryReceipt']);
Route::post('/webhook/call', [WebhookController::class, 'handleCallWebhook']);
Route::get('/api/webhook/call', [WebhookController::class, 'getCall']);

// Development/Testing routes
Route::post('/test/pusher', function() {
    if (!app()->environment('local')) {
        abort(404);
    }
    
    // Test Pusher broadcasting
    event(new \App\Events\CallStatusUpdated(
        new \App\Models\Call([
            'call_session_id' => 'test-session-' . time(),
            'call_control_id' => 'test-control-' . time(),
            'from_number' => '+1234567890',
            'to_number' => '+0987654321',
            'status' => 'test',
            'direction' => 'outgoing'
        ]),
        'test',
        'test.event'
    ));
    
    return response()->json(['message' => 'Test event broadcasted']);
})->middleware('throttle:10,1');

Route::post('/test/simulate-inbound-sms', function(Illuminate\Http\Request $request) {
    if (!app()->environment('local')) {
        abort(404);
    }
    
    $request->validate([
        'from' => 'required|string',
        'to' => 'required|string',
        'text' => 'required|string'
    ]);
    
    $payload = [
        'event_type' => 'message.received',
        'data' => [
            'payload' => [
                'id' => 'test_' . uniqid(),
                'from' => ['phone_number' => $request->from],
                'to' => ['phone_number' => $request->to],
                'text' => $request->text,
                'received_at' => now()->toISOString()
            ]
        ]
    ];
    
    $controller = new App\Http\Controllers\WebhookController();
    $simulatedRequest = new Illuminate\Http\Request();
    $simulatedRequest->merge($payload);
    
    return $controller->handleSmsWebhook($simulatedRequest);
})->middleware('throttle:10,1'); // Rate limit: 10 requests per minute

require __DIR__.'/auth.php';
