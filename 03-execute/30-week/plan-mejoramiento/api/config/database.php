<?php

return [
    'default' => 'pgsql',
    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', 'db'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'lab_equipment'),
            'username' => env('DB_USERNAME', 'lab_app'),
            'password' => env('DB_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],
    ],
    'migrations' => ['table' => 'migrations', 'update_date_on_publish' => true],
];
