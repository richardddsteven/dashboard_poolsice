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

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
    ],

    'zone_geocoding' => [
        'enabled'  => env('ZONE_GEOCODING_ENABLED', true),
        'endpoint' => env('ZONE_GEOCODING_ENDPOINT', 'https://nominatim.openstreetmap.org/search'),
        'email'    => env('ZONE_GEOCODING_EMAIL'),
        'timeout'  => env('ZONE_GEOCODING_TIMEOUT', 8),
    ],

    'firebase' => [
        'project_id'  => env('FIREBASE_PROJECT_ID', 'aplikasi-supir'),
        'credentials' => env('FIREBASE_CREDENTIALS', 'storage/app/firebase-service-account.json'),
    ],

    'routing' => [
        'max_backtrack_distance_meters' => env('ROUTING_MAX_BACKTRACK_DISTANCE_METERS', 1000),
        'timeout' => env('ROUTING_API_TIMEOUT', 8),
        'google_maps' => [
            'api_key'  => env('GOOGLE_MAPS_API_KEY'),
            'endpoint' => env('GOOGLE_MAPS_DIRECTIONS_ENDPOINT', 'https://maps.googleapis.com/maps/api/directions/json'),
            'mode'     => env('GOOGLE_MAPS_DIRECTIONS_MODE', 'driving'),
            'language' => env('GOOGLE_MAPS_DIRECTIONS_LANGUAGE', 'id'),
            'region'   => env('GOOGLE_MAPS_DIRECTIONS_REGION', 'id'),
        ],
    ],

];
