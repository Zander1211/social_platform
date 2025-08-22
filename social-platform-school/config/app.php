<?php
return [
    'app_name' => 'Social Platform for School Announcement and Events',
    'app_version' => '1.0.0',
    'base_url' => 'http://localhost/social-platform-school/public',
    'timezone' => 'UTC',
    'debug' => true,
    'log_file' => __DIR__ . '/../logs/app.log',
    'session' => [
        'name' => 'school_platform_session',
        'lifetime' => 3600,
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'httponly' => true,
    ],
    'cookie' => [
        'lifetime' => 3600,
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'httponly' => true,
    ],
    'allowed_file_types' => ['image/jpeg', 'image/png', 'application/pdf', 'video/mp4'],
    'max_file_size' => 5242880, // 5MB
];