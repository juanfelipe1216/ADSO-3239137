<?php

return [
    'demo_clock_file' => env('DEMO_CLOCK_FILE', ''),
    'admin_username' => env('ADMIN_USERNAME', ''),
    'admin_password' => env('ADMIN_PASSWORD', ''),
    'jwt_secret' => env('JWT_SECRET', ''),
    'covers_dir' => env('COVERS_DIR', storage_path('covers')),
];
