<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Push Driver
    |--------------------------------------------------------------------------
    |
    | When dispatching push notifications without explicitly specifying a driver,
    | this driver will be selected. Options: 'auto' (detects platform by token/model),
    | 'fcm', 'apns'.
    |
    */
    'default' => env('PUSH_NOTIFICATION_DRIVER', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (FCM HTTP v1) Settings
    |--------------------------------------------------------------------------
    |
    | The Google FCM HTTP v1 API requires service account credentials with
    | Firebase Cloud Messaging API enabled.
    |
    */
    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID'),

        // Path to the service account JSON file downloaded from Firebase Console
        'credentials_path' => env('FCM_CREDENTIALS_PATH', storage_path('app/firebase-credentials.json')),

        // Alternatively provide raw JSON credentials or environment variables
        'credentials_json' => env('FCM_CREDENTIALS_JSON'),
        'client_email' => env('FCM_CLIENT_EMAIL'),
        'private_key' => env('FCM_PRIVATE_KEY'),

        // Default Android notification channel and configuration
        'channel_id' => env('FCM_DEFAULT_CHANNEL_ID', 'high_importance_channel'),
        'sound' => env('FCM_DEFAULT_SOUND', 'default'),
        'priority' => env('FCM_DEFAULT_PRIORITY', 'HIGH'), // HIGH or NORMAL
    ],

    /*
    |--------------------------------------------------------------------------
    | Apple Push Notification service (APNs HTTP/2) Settings
    |--------------------------------------------------------------------------
    |
    | APNs authentication uses modern .p8 token-based authentication (ES256).
    |
    */
    'apns' => [
        // Your Apple 10-character Key ID from Apple Developer Member Center
        'key_id' => env('APNS_KEY_ID'),

        // Your 10-character Team ID
        'team_id' => env('APNS_TEAM_ID'),

        // Your iOS app's Bundle ID (e.g., com.example.myapp)
        'bundle_id' => env('APNS_BUNDLE_ID'),

        // Path to your downloaded .p8 authentication key
        'private_key_path' => env('APNS_PRIVATE_KEY_PATH', storage_path('app/AuthKey_APNS.p8')),

        // Alternatively provide the raw .p8 key content
        'private_key_content' => env('APNS_PRIVATE_KEY_CONTENT'),

        // True for production (gateway.push.apple.com), false for sandbox (gateway.sandbox.push.apple.com)
        'production' => env('APNS_PRODUCTION', false),

        // Default sound file name or 'default'
        'sound' => env('APNS_DEFAULT_SOUND', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Token Storage & Auto-Pruning
    |--------------------------------------------------------------------------
    |
    | When invalid or unregistered device tokens (Unregistered, BadDeviceToken)
    | are returned from Google or Apple, the package can automatically prune
    | or deactivate them in your database.
    |
    */
    'device_tokens' => [
        'table' => 'device_tokens',
        'auto_prune_invalid_tokens' => env('PUSH_AUTO_PRUNE_INVALID_TOKENS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging & Diagnostics
    |--------------------------------------------------------------------------
    |
    | Log full request payloads and provider responses for debugging delivery.
    |
    */
    'logging' => [
        'enabled' => env('PUSH_LOGGING_ENABLED', false),
        'channel' => env('PUSH_LOG_CHANNEL', 'daily'),
    ],

];
