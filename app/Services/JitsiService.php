<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class JitsiService
{
    /**
     * Generate a JWT token for Jitsi/JaaS authentication
     * Based on: https://github.com/8x8/jaas_demo/blob/main/jaas-jwt-samples/php/jaas-jwt-firebase.php
     *
     * @param string $roomName
     * @param string $userName
     * @param string $userEmail
     * @param array $options
     * @return string|null
     */
    public function generateJWT(string $roomName, string $userName, string $userEmail, array $options = []): ?string
    {
        $appId = config('services.jitsi.app_id');
        $apiKey = config('services.jitsi.jwt_secret');

        // If no JWT credentials configured, return null (will use public Jitsi)
        if (empty($appId) || empty($apiKey)) {
            return null;
        }

        // Determine if this is JaaS (8x8.vc) or self-hosted
        $domain = config('services.jitsi.domain', 'meet.jit.si');
        $isJaaS = str_contains($appId, 'vpaas-magic-cookie') || str_contains($domain, '8x8.vc');

        $now = time();
        $expSeconds = $options['exp_seconds'] ?? 7200; // 2 hours default

        // Build the payload according to 8x8 JaaS specification
        $payload = [
            'aud' => $isJaaS ? 'jitsi' : 'jitsi',
            'iss' => $isJaaS ? 'chat' : $appId,
            'sub' => $appId,
            'room' => '*', // Wildcard room or specific room
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $expSeconds,
            'context' => [
                'user' => [
                    'id' => $options['user_id'] ?? uniqid(),
                    'name' => $userName,
                    'email' => $userEmail,
                    'avatar' => $options['avatar'] ?? '',
                    'moderator' => $options['moderator'] ?? false,
                ],
                'features' => [
                    'livestreaming' => $options['livestreaming'] ?? false,
                    'recording' => $options['recording'] ?? false,
                    'transcription' => $options['transcription'] ?? false,
                    'outbound-call' => $options['outbound_call'] ?? false,
                ],
            ],
        ];

        // Add moderator flag at root level if specified (for compatibility)
        if (isset($options['moderator']) && $options['moderator']) {
            $payload['moderator'] = true;
        }

        try {
            // Encode JWT token using HS256 algorithm
            $token = JWT::encode($payload, $apiKey, 'HS256');
            
            Log::info('Jitsi JWT generated successfully', [
                'app_id' => $appId,
                'user' => $userName,
                'room' => $roomName,
                'is_jaas' => $isJaaS,
            ]);

            return $token;
        } catch (\Exception $e) {
            Log::error('Failed to generate Jitsi JWT', [
                'error' => $e->getMessage(),
                'app_id' => $appId,
                'user' => $userName,
            ]);
            return null;
        }
    }

    /**
     * Generate JWT for a moderator (host)
     *
     * @param string $roomName
     * @param string $userName
     * @param string $userEmail
     * @param int $userId
     * @return string|null
     */
    public function generateModeratorJWT(string $roomName, string $userName, string $userEmail, int $userId): ?string
    {
        return $this->generateJWT($roomName, $userName, $userEmail, [
            'user_id' => (string) $userId,
            'moderator' => true,
            'recording' => true,
            'livestreaming' => config('services.jitsi.config.liveStreamingEnabled', false),
        ]);
    }

    /**
     * Generate JWT for a regular participant
     *
     * @param string $roomName
     * @param string $userName
     * @param string $userEmail
     * @param int $userId
     * @return string|null
     */
    public function generateParticipantJWT(string $roomName, string $userName, string $userEmail, int $userId): ?string
    {
        return $this->generateJWT($roomName, $userName, $userEmail, [
            'user_id' => (string) $userId,
            'moderator' => false,
        ]);
    }

    /**
     * Check if JWT authentication is enabled
     *
     * @return bool
     */
    public function isJWTEnabled(): bool
    {
        return !empty(config('services.jitsi.app_id')) && !empty(config('services.jitsi.jwt_secret'));
    }

    /**
     * Get Jitsi domain
     *
     * @return string
     */
    public function getDomain(): string
    {
        return config('services.jitsi.domain', 'meet.jit.si');
    }
}

