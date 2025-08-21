<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TelnyxController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
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
    Route::post('/api/telnyx/sip-credentials', [TelnyxController::class, 'getSipCredentials']);
    Route::post('/api/telnyx/call', [TelnyxController::class, 'makeCall']);
    Route::post('/api/telnyx/simple-call', [TelnyxController::class, 'makeSimpleCall']);
    Route::post('/api/telnyx/end-call', [TelnyxController::class, 'endCall']);
    Route::get('/api/calls/history', [TelnyxController::class, 'getCallHistory']);
});

// Webhook route (no auth required)
Route::post('/api/telnyx/webhook', [TelnyxController::class, 'webhook']);

require __DIR__.'/auth.php';
