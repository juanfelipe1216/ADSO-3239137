<?php

return [
    'jwt_secret' => env('JWT_SECRET', ''),
    'covers_dir' => env('COVERS_DIR', storage_path('covers')),
];
