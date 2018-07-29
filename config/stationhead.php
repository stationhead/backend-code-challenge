<?php

return [
    'environment' => env('ENVIRONMENT'),
    'app_env' => env('APP_ENV'),


    'spotify' => [
        'client_id' => env('SPOTIFY_CLIENT_ID'),
        'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
        'client_callback' => env('SPOTIFY_CLIENT_CALLBACK'),
        'stationhead_account_username' => env("SPOTIFY_STATIONHEAD_ACCOUNT_USERNAME"),
        'client_callback_beta' => 'stationhead-beta://'
    ],

    'apple' => [
        'username' => env('APPLE_USERNAME'),
        'password' => env('APPLE_PASSWORD'),
        'team_id' => env("APPLE_STATIONHEAD_TEAM_ID"),
        'music_kit_key_id' => env("APPLE_MUSIC_KIT_KEY_ID"),
        'music_kit_private_key_path' => env("APPLE_MUSIC_KIT_PRIVATE_KEY_PATH"),
        'api_key_ttl' => env("APPLE_MUSIC_API_TTL") // seconds
    ],

    'database' => [
        'connections' => [
            'main' => env('DB_CONNECTION'),
            'main_testing' => env('DB_CONNECTION') . '_testing',
        ]
    ],
];
