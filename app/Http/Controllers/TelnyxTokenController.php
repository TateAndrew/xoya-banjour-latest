<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Telnyx\Telnyx;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TelnyxTokenController extends Controller
{
    /**
     * Generate a Telnyx token for WebRTC calls
     */
    public function generateToken(Request $request)
    {
        try {
            $request->validate([
                'phone_number' => 'required|string',
            ]);

            $user = Auth::user();
            $phoneNumber = $request->phone_number;

            // Set up Telnyx API key
            Telnyx::setApiKey('KEY01981DE4FB0F5A066B6B7338FD74AAD1_jLcLOza8i560QqeovISomb');

            // Generate JWT token for WebRTC
            $payload = [
                'iss' => 'telnyx', // issuer
                'sub' => $phoneNumber, // subject (phone number)
                'aud' => 'telnyx', // audience
                'exp' => time() + 3600, // expires in 1 hour
                'iat' => time(), // issued at
                'jti' => uniqid(), // unique token ID
                'data' => [
                    'phone_number' => $phoneNumber,
                    'user_id' => $user->id,
                    'type' => 'client'
                ]
            ];

            // Use a secret key for JWT signing (you should store this in your .env file)
            $secret = config('services.telnyx.jwt_secret', 'your-jwt-secret-key');
            
            $token = JWT::encode($payload, $secret, 'HS256');

            Log::info('Telnyx JWT token generated', [
                'user_id' => $user->id,
                'phone_number' => $phoneNumber,
                'token_id' => $payload['jti']
            ]);

            return response()->json([
                'success' => true,
                'token' => $token,
                'expires_at' => $payload['exp'],
            ]);

        } catch (\Exception $e) {
            Log::error('Token generation failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Alternative method using Telnyx Client SDK
     */
    public function generateClientToken(Request $request)
    {
        try {
            $request->validate([
                'phone_number' => 'required|string',
            ]);

            $user = Auth::user();
            $phoneNumber = $request->phone_number;

            // Set up Telnyx API key
            Telnyx::setApiKey(config('services.telnyx.api_key'));

            // Create a client token for WebRTC using JWT
            $payload = [
                'iss' => 'telnyx',
                'sub' => $phoneNumber,
                'aud' => 'telnyx',
                'exp' => time() + 3600,
                'iat' => time(),
                'jti' => uniqid(),
                'data' => [
                    'client_name' => 'Vue Dialer',
                    'phone_number' => $phoneNumber,
                    'user_id' => $user->id,
                    'type' => 'client'
                ]
            ];

            $secret = config('services.telnyx.jwt_secret', 'your-jwt-secret-key');
            $clientToken = JWT::encode($payload, $secret, 'HS256');

            Log::info('Telnyx client JWT token generated', [
                'user_id' => $user->id,
                'phone_number' => $phoneNumber,
                'token_id' => $payload['jti']
            ]);

            return response()->json([
                'success' => true,
                'token' => $clientToken,
                'expires_at' => $payload['exp'],
            ]);

        } catch (\Exception $e) {
            Log::error('Client token generation failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate client token: ' . $e->getMessage()
            ], 500);
        }
    }
} 