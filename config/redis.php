<?php

return [

    'channels' => [
        'db' => 'default',
        'namespace' => 'channels'
    ],

    'pushes' => [
        'db' => 'default',
        'namespace' => 'pushes'
    ],
    'share' => [
        'db' => 'default',
        'namespace' => 'share'
    ],

    'jobs' => [
        'db' => 'default',
        'namespace' => 'jobs'
    ],

    'apple_library' => [
        'db' => 'default',
        'namespace' => 'apple_library'
    ],

    'apple_pricing' => [
        'db' => 'apple',
        'namespace' => 'ap'
    ],

    "leaderboard" => [
        'db' => 'default',
        'namespace' => 'leaderboard'
    ],

    "phone_number_verification" => [
        'db' => 'default',
        'namespace' => 'phone_number_verification'
    ],

    "password_reset" => [
        'db' => 'default',
        'namespace' => 'password_reset',
        'ttl' => 1800 // 30 minutes
    ],

    "contextual_data" => [
        'db' => 'contextual_data'
    ],

    'messages' => [
        'station_launched' => [
            'db' => "default",
            'namespace' => 'messages_station_launched',
        ]
    ],

    "latest_heartbeat" => [
        'db' => 'default',
        'namespace' => 'latest_heartbeat'
    ],

    'default' => 'default'
];