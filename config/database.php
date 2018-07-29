<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PDO Fetch Style
    |--------------------------------------------------------------------------
    |
    | By default, database results will be returned as instances of the PHP
    | stdClass object; however, you may desire to retrieve records in an
    | array format for simplicity. Here you can tweak the fetch style.
    |
    */

    'fetch' => PDO::FETCH_CLASS,

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => database_path('database.sqlite'),
            'prefix'   => '',
        ],

        'mysql' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', 'localhost'),
            'database'  => env('DB_DATABASE', 'forge'),
            'username'  => env('DB_USERNAME', 'forge'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'modes'     => [
                //disabled to allow rearranging featured lists
                // 'STRICT_TRANS_TABLES',
                'NO_ZERO_IN_DATE',
                'NO_ZERO_DATE',
                'ERROR_FOR_DIVISION_BY_ZERO',
                'NO_AUTO_CREATE_USER',
                'NO_ENGINE_SUBSTITUTION'
            ]
        ],

        'mysql_testing' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST_TEST', 'localhost'),
            'database'  => env('DB_DATABASE_TEST', 'forge'),
            'username'  => env('DB_USERNAME_TEST', 'forge'),
            'password'  => env('DB_PASSWORD_TEST', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'modes'     => [
                // 'STRICT_TRANS_TABLES',
                'NO_ZERO_IN_DATE',
                'NO_ZERO_DATE',
                'ERROR_FOR_DIVISION_BY_ZERO',
                'NO_AUTO_CREATE_USER',
                'NO_ENGINE_SUBSTITUTION'
            ]
        ],

        'mysql_logging' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST_LOGS', 'localhost'),
            'database'  => env('DB_DATABASE_LOGS', 'forge'),
            'username'  => env('DB_USERNAME_LOGS', 'forge'),
            'password'  => env('DB_PASSWORD_LOGS', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'modes'     => [
                'STRICT_TRANS_TABLES',
                'NO_ZERO_IN_DATE',
                'NO_ZERO_DATE',
                'ERROR_FOR_DIVISION_BY_ZERO',
                'NO_AUTO_CREATE_USER',
                'NO_ENGINE_SUBSTITUTION'
            ]
        ],

        'mysql_logging_testing' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST_LOGS_TEST', 'localhost'),
            'database'  => env('DB_DATABASE_LOGS_TEST', 'forge'),
            'username'  => env('DB_USERNAME_LOGS_TEST', 'forge'),
            'password'  => env('DB_PASSWORD_LOGS_TEST', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'modes'     => [
                'STRICT_TRANS_TABLES',
                'NO_ZERO_IN_DATE',
                'NO_ZERO_DATE',
                'ERROR_FOR_DIVISION_BY_ZERO',
                'NO_AUTO_CREATE_USER',
                'NO_ENGINE_SUBSTITUTION'
            ]
        ],

        'mysql_apple' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST_APPLE', 'localhost'),
            'database'  => env('DB_DATABASE_APPLE', 'forge'),
            'username'  => env('DB_USERNAME_APPLE', 'forge'),
            'password'  => env('DB_PASSWORD_APPLE', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'modes'     => [
                'STRICT_TRANS_TABLES',
                'NO_ZERO_IN_DATE',
                'NO_ZERO_DATE',
                'ERROR_FOR_DIVISION_BY_ZERO',
                'NO_AUTO_CREATE_USER',
                'NO_ENGINE_SUBSTITUTION'
            ]
        ],

        'mysql_apple_testing' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST_APPLE_TEST', 'localhost'),
            'database'  => env('DB_DATABASE_APPLE_TEST', 'forge'),
            'username'  => env('DB_USERNAME_APPLE_TEST', 'forge'),
            'password'  => env('DB_PASSWORD_APPLE_TEST', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'modes'     => [
                'STRICT_TRANS_TABLES',
                'NO_ZERO_IN_DATE',
                'NO_ZERO_DATE',
                'ERROR_FOR_DIVISION_BY_ZERO',
                'NO_AUTO_CREATE_USER',
                'NO_ENGINE_SUBSTITUTION'
            ]
        ],

        'mysql_apple' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST_APPLE', 'localhost'),
            'database'  => env('DB_DATABASE_APPLE', 'forge'),
            'username'  => env('DB_USERNAME_APPLE', 'forge'),
            'password'  => env('DB_PASSWORD_APPLE', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'modes'     => [
                'STRICT_TRANS_TABLES',
                'NO_ZERO_IN_DATE',
                'NO_ZERO_DATE',
                'ERROR_FOR_DIVISION_BY_ZERO',
                'NO_AUTO_CREATE_USER',
                'NO_ENGINE_SUBSTITUTION'
            ]
        ],

        'mysql_apple_testing' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST_APPLE_TEST', 'localhost'),
            'database'  => env('DB_DATABASE_APPLE_TEST', 'forge'),
            'username'  => env('DB_USERNAME_APPLE_TEST', 'forge'),
            'password'  => env('DB_PASSWORD_APPLE_TEST', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'modes'     => [
                'STRICT_TRANS_TABLES',
                'NO_ZERO_IN_DATE',
                'NO_ZERO_DATE',
                'ERROR_FOR_DIVISION_BY_ZERO',
                'NO_AUTO_CREATE_USER',
                'NO_ENGINE_SUBSTITUTION'
            ]
        ],

        'pgsql' => [
            'driver'   => 'pgsql',
            'host'     => env('DB_HOST', 'localhost'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8mb4',
            'prefix'   => '',
            'schema'   => 'public',
        ],

        'sqlsrv' => [
            'driver'   => 'sqlsrv',
            'host'     => env('DB_HOST', 'localhost'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8mb4',
            'prefix'   => '',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'cluster' => false,

        'default' => [
            'host'     => env('REDIS_HOST', 'localhost'),
            'password' => env('REDIS_PASSWORD', null),
            'port'     => env('REDIS_PORT', 6379),
            'database' => 0,
            'persistent' => 0
        ],
        'contextual_data' => [
            'host'     => env('REDIS_HOST', 'localhost'),
            'password' => env('REDIS_PASSWORD', null),
            'port'     => env('REDIS_PORT', 6379),
            'database' => 3,
            'persistent' => 0
        ],
        'cache' => [
            'host'     => env('CACHE_HOST', 'localhost'),
            'password' => env('CACHE_PASSWORD', null),
            'port'     => env('CACHE_PORT', 6379),
            'database' => 1,
        ],

        'apple' => [
            'host'     => env('APPLE_STREAMABLE_HOST', 'localhost'),
            'password' => env('APPLE_STREAMABLE_PASSWORD', null),
            'port'     => env('APPLE_STREAMABLE_PORT', 6379),
            'database' => 2,
        ],
    ],
];
