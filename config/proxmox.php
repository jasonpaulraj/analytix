<?php

if (!function_exists('env')) {
    function env($key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
}

return [
    /*
    |--------------------------------------------------------------------------
    | Proxmox API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration settings for interacting with Proxmox APIs
    |
    */

    // Default API port for Proxmox VE
    'default_port' => env('PROXMOX_DEFAULT_PORT', 8006),
    
    // Default timeout values for API requests
    'timeouts' => [
        'connection' => env('PROXMOX_CONNECTION_TIMEOUT', 10), // seconds
        'request' => env('PROXMOX_REQUEST_TIMEOUT', 30), // seconds
    ],
    
    // Default SSL verification setting
    'verify_ssl' => env('PROXMOX_VERIFY_SSL', true),
    
    // Connection retry settings
    'retry' => [
        'max_attempts' => env('PROXMOX_RETRY_ATTEMPTS', 3),
        'delay' => env('PROXMOX_RETRY_DELAY', 1000), // milliseconds
    ],
    
    // Logging settings
    'logging' => [
        'enabled' => env('PROXMOX_LOG_ENABLED', true),
        'level' => env('PROXMOX_LOG_LEVEL', 'info'),
    ],
];
