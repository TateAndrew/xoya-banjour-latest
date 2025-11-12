<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'telnyx' => [
        'api_key' => env('TELNYX_API_KEY'),
        'public_key' => env('TELNYX_PUBLIC_KEY'),
    ],

    'pusher' => [
        'app_id' => env('PUSHER_APP_ID'),
        'app_key' => env('PUSHER_APP_KEY'),
        'app_secret' => env('PUSHER_APP_SECRET'),
        'app_cluster' => env('PUSHER_APP_CLUSTER'),
    ],

    'jitsi' => [
        'domain' => env('JITSI_DOMAIN', 'meet.jit.si'),
        'app_id' => env('JITSI_APP_ID', null), // For JaaS: vpaas-magic-cookie-xxxxx/yyyyy
        'jwt_secret' => env('JITSI_JWT_SECRET', null), // API Key for JaaS
        
        'config' => [
            // Basic configuration
            'startWithAudioMuted' => false,
            'startWithVideoMuted' => false,
            'enableWelcomePage' => false,
            'prejoinPageEnabled' => false,
            'disableDeepLinking' => true,
            
            // UI customization
            'toolbarButtons' => [
                'camera',
                'chat',
                'closedcaptions',
                'desktop',
                'download',
                'embedmeeting',
                'etherpad',
                'feedback',
                'filmstrip',
                'fullscreen',
                'hangup',
                'help',
                'highlight',
                'invite',
                'linktosalesforce',
                'livestreaming',
                'microphone',
                'noisesuppression',
                'participants-pane',
                'profile',
                'raisehand',
                'recording',
                'security',
                'select-background',
                'settings',
                'shareaudio',
                'sharedvideo',
                'shortcuts',
                'stats',
                'tileview',
                'toggle-camera',
                'videoquality',
                'whiteboard',
            ],
            
            // Security settings
            'enableLayerSuspension' => true,
            'requireDisplayName' => true,
            
            // Recording
            'fileRecordingsEnabled' => env('JITSI_FILE_RECORDING_ENABLED', false),
            'liveStreamingEnabled' => env('JITSI_LIVE_STREAMING_ENABLED', false),
            
            // Performance
            'disableSimulcast' => false,
            'resolution' => 720,
            'constraints' => [
                'video' => [
                    'height' => [
                        'ideal' => 720,
                        'max' => 720,
                        'min' => 240
                    ]
                ]
            ],
        ],
        
        'interface_config' => [
            'SHOW_JITSI_WATERMARK' => false,
            'SHOW_WATERMARK_FOR_GUESTS' => false,
            'SHOW_BRAND_WATERMARK' => false,
            'BRAND_WATERMARK_LINK' => '',
            'SHOW_POWERED_BY' => false,
            'DISPLAY_WELCOME_PAGE_CONTENT' => false,
            'DISPLAY_WELCOME_PAGE_TOOLBAR_ADDITIONAL_CONTENT' => false,
            'APP_NAME' => env('APP_NAME', 'Video Conference'),
            'NATIVE_APP_NAME' => env('APP_NAME', 'Video Conference'),
            'PROVIDER_NAME' => env('APP_NAME', 'Video Conference'),
            'MOBILE_APP_PROMO' => false,
            'HIDE_INVITE_MORE_HEADER' => false,
            'DISABLE_JOIN_LEAVE_NOTIFICATIONS' => false,
            'DISABLE_VIDEO_BACKGROUND' => false,
            'DISABLE_FOCUS_INDICATOR' => false,
            'DISABLE_DOMINANT_SPEAKER_INDICATOR' => false,
            'DISABLE_RINGING' => false,
            'AUDIO_LEVEL_PRIMARY_COLOR' => 'rgba(255,255,255,0.4)',
            'AUDIO_LEVEL_SECONDARY_COLOR' => 'rgba(255,255,255,0.2)',
            'POLICY_LOGO' => null,
            'LOCAL_THUMBNAIL_RATIO' => 16 / 9,
            'REMOTE_THUMBNAIL_RATIO' => 1,
            'VERTICAL_FILMSTRIP' => true,
            'TILE_VIEW_MAX_COLUMNS' => 5,
            'SETTINGS_SECTIONS' => ['devices', 'language', 'moderator', 'profile', 'calendar'],
            'VIDEO_LAYOUT_FIT' => 'both',
            'filmStripOnly' => false,
            'OPTIMAL_BROWSERS' => ['chrome', 'chromium', 'firefox', 'safari', 'edge'],
        ],
    ],

];
