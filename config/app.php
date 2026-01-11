<?php

$packageVersion = null;
$packageJsonPath = base_path('package.json');

if (is_file($packageJsonPath)) {
    $packageJson = json_decode((string) file_get_contents($packageJsonPath), true);
    if (is_array($packageJson) && isset($packageJson['version'])) {
        $packageVersion = $packageJson['version'];
    }
}

return [
    'name' => env('APP_NAME', 'Simak'),
    'version' => env('APP_VERSION', $packageVersion ?? env('NATIVEPHP_APP_VERSION', '1.0.0')),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'Asia/Jakarta',
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY', 'base64:CXr+jYf2hCzTHZ0ij85W/L+O/hYtkgIKSYAorPKK42MA='),
    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],
];
