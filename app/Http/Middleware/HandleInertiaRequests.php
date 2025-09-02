<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'errors' => function () use ($request) {
                $errors = session('errors');
                
                // Debug: Log the type and content of errors
                Log::info('Session errors type: ' . gettype($errors));
                Log::info('Session errors content: ', ['errors' => $errors]);
                
                if ($errors instanceof \Illuminate\Support\ViewErrorBag) {
                    return $errors->getBag('default')->getMessages();
                }
                
                if (is_array($errors)) {
                    return $errors;
                }
                
                // Handle case where errors might be stored differently
                if (is_object($errors) && method_exists($errors, 'toArray')) {
                    return $errors->toArray();
                }
                
                // Check if errors are stored in a different session key
                $alternativeErrors = session('validation_errors') ?? session('form_errors') ?? [];
                if (!empty($alternativeErrors)) {
                    Log::info('Found alternative errors: ', ['errors' => $alternativeErrors]);
                    return $alternativeErrors;
                }
                
                // Fallback: Check request for validation errors
                if ($request->hasSession() && $request->session()->has('errors')) {
                    $requestErrors = $request->session()->get('errors');
                    Log::info('Request session errors: ', ['errors' => $requestErrors]);
                    
                    if (is_array($requestErrors)) {
                        return $requestErrors;
                    }
                }
                
                return [];
            },
            'flash' => [
                'message' => fn () => session('message'),
                'success' => fn () => session('success'),
                'error' => fn () => session('error'),
                'warning' => fn () => session('warning'),
                'info' => fn () => session('info'),
            ],
        ];
    }
}
