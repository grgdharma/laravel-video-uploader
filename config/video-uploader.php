<?php

return [
    'disk' => env('VIDEO_UPLOAD_DISK', 'public'),
    'path' => 'videos',
    'max_size' => 1024 * 1024 * 500, // 500 MB
];
