<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LicenseService
{
    protected $licenseFile;
    protected $licenseDir;
    protected $jsonLicenseFile;
    protected $lastError = null;

    public function __construct()
    {

        $this->licenseDir = base_path('storage/app/license');
        $this->licenseFile = $this->licenseDir . '/license.dat';
        $this->jsonLicenseFile = $this->licenseDir . '/license.json';
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    protected function ensureLicenseDirectory(): bool
    {
        $this->lastError = null;

        if ($this->tryCreateDirectory($this->licenseDir)) {
            return true;
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $appData = getenv('LOCALAPPDATA') ?: getenv('APPDATA');
            if ($appData) {
                $fallbackDir = $appData . DIRECTORY_SEPARATOR . 'SIMAK' . DIRECTORY_SEPARATOR . 'license';
                if ($this->tryCreateDirectory($fallbackDir)) {
                    $this->licenseDir = $fallbackDir;
                    $this->licenseFile = $fallbackDir . '/license.dat';
                    $this->jsonLicenseFile = $fallbackDir . '/license.json';
                    Log::info('[License] Using fallback directory: ' . $fallbackDir);
                    return true;
                }
            }
        }

        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'simak_license';
        if ($this->tryCreateDirectory($tempDir)) {
            $this->licenseDir = $tempDir;
            $this->licenseFile = $tempDir . '/license.dat';
            $this->jsonLicenseFile = $tempDir . '/license.json';
            Log::warning('[License] Using temp directory (not persistent): ' . $tempDir);
            return true;
        }

        return false;
    }

    protected function tryCreateDirectory(string $dir): bool
    {
        try {
            if (!File::exists($dir)) {

                if (!@mkdir($dir, 0755, true)) {
                    $error = error_get_last();
                    $this->lastError = "Tidak dapat membuat folder: " . ($error['message'] ?? 'Unknown error');
                    Log::warning('[License] Failed to create directory: ' . $dir, ['error' => $error]);
                    return false;
                }
            }

            $testFile = $dir . DIRECTORY_SEPARATOR . '.write_test_' . time();
            if (@file_put_contents($testFile, 'test') === false) {
                $error = error_get_last();
                $this->lastError = "Tidak memiliki izin menulis ke folder: " . ($error['message'] ?? 'Permission denied');
                Log::warning('[License] No write permission: ' . $dir, ['error' => $error]);
                return false;
            }

            @unlink($testFile);

            return true;
        } catch (\Throwable $e) {
            $this->lastError = "Error akses folder: " . $e->getMessage();
            Log::error('[License] Directory access error: ' . $e->getMessage());
            return false;
        }
    }

    public function generateLicense(string $clientName, string $clientEmail): string
    {
        $timestamp = time();
        $hash = hash('sha256', $clientName . $clientEmail . $timestamp);
        $segment1 = strtoupper(substr($hash, 0, 4));
        $segment2 = strtoupper(substr($hash, 4, 4));
        $segment3 = strtoupper(substr($hash, 8, 4));
        $segment4 = strtoupper(substr($hash, 12, 4));
        return "SIMAK-{$segment1}-{$segment2}-{$segment3}-{$segment4}";
    }

    public function saveLicense(string $licenseKey, string $clientName, string $clientEmail): bool
    {
        if (!$this->ensureLicenseDirectory()) {
            return false;
        }

        try {
            $licenseData = [
                'license_key' => $licenseKey,
                'client' => $clientName,
                'email' => $clientEmail,
                'installed_at' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
                'status' => 'active',
            ];

            $jsonResult = @file_put_contents($this->jsonLicenseFile, json_encode($licenseData));
            if ($jsonResult === false) {
                $this->lastError = "Gagal menyimpan file lisensi JSON";
                return false;
            }

            $encrypted = Crypt::encryptString(json_encode($licenseData));
            $datResult = @file_put_contents($this->licenseFile, $encrypted);
            if ($datResult === false) {
                $this->lastError = "Gagal menyimpan file lisensi terenkripsi";
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->lastError = "Error menyimpan lisensi: " . $e->getMessage();
            Log::error('[License] Save error: ' . $e->getMessage());
            return false;
        }
    }

    public function saveLocalLicense(array $payload): bool
    {
        if (!$this->ensureLicenseDirectory()) {
            Log::warning('[License] saveLocalLicense failed - directory not accessible', [
                'error' => $this->lastError,
                'attempted_dir' => $this->licenseDir,
            ]);
            return false;
        }

        if (!empty($payload['license_key']) && !empty($payload['hardware_id'])) {
            $payload['signature'] = hash_hmac(
                'sha256',
                $payload['license_key'] . '|' . $payload['hardware_id'],
                (string) config('app.key')
            );
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT);

        try {
            $result = @file_put_contents($this->jsonLicenseFile, $json);
            if ($result === false) {
                $error = error_get_last();
                $this->lastError = "Gagal menulis file lisensi: " . ($error['message'] ?? 'Unknown');
                Log::error('[License] Failed to write license file', [
                    'file' => $this->jsonLicenseFile,
                    'error' => $error,
                ]);
                return false;
            }

            Log::info('[License] License saved successfully', [
                'file' => $this->jsonLicenseFile,
                'bytes' => $result,
            ]);
            return true;
        } catch (\Throwable $e) {
            $this->lastError = "Error menyimpan lisensi: " . $e->getMessage();
            Log::error('[License] Save exception: ' . $e->getMessage());
            return false;
        }
    }

    public function loadLocalLicense(): ?array
    {

        $locations = [
            $this->jsonLicenseFile,
            $this->licenseFile,
        ];

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $appData = getenv('LOCALAPPDATA') ?: getenv('APPDATA');
            if ($appData) {
                $fallbackDir = $appData . DIRECTORY_SEPARATOR . 'SIMAK' . DIRECTORY_SEPARATOR . 'license';
                $locations[] = $fallbackDir . '/license.json';
                $locations[] = $fallbackDir . '/license.dat';
            }
        }

        foreach ($locations as $file) {
            if (str_ends_with($file, '.json') && File::exists($file)) {
                try {
                    $data = json_decode(File::get($file), true);
                    if (is_array($data) && !empty($data)) {

                        if ($file !== $this->jsonLicenseFile) {
                            $this->licenseDir = dirname($file);
                            $this->jsonLicenseFile = $file;
                            $this->licenseFile = str_replace('.json', '.dat', $file);
                        }

                        if (!empty($data['license_key']) && !empty($data['hardware_id'])) {
                            $expected = hash_hmac(
                                'sha256',
                                $data['license_key'] . '|' . $data['hardware_id'],
                                (string) config('app.key')
                            );
                            $signature = $data['signature'] ?? null;
                            if ($signature && hash_equals($expected, (string) $signature)) {
                                return $data;
                            }
                        }
                        return $data;
                    }
                } catch (\Throwable $e) {
                    Log::warning('[License] Error reading JSON: ' . $file, ['error' => $e->getMessage()]);
                }
            }
        }

        foreach ($locations as $file) {
            if (str_ends_with($file, '.dat') && File::exists($file)) {
                try {
                    $decrypted = Crypt::decryptString(File::get($file));
                    $data = json_decode($decrypted, true);
                    if (is_array($data) && !empty($data)) {
                        return $data;
                    }
                } catch (\Throwable $e) {
                    Log::warning('[License] Error reading DAT: ' . $file, ['error' => $e->getMessage()]);
                }
            }
        }

        return null;
    }

    public function validateLicense(): array
    {
        $licenseData = $this->loadLocalLicense();

        if (empty($licenseData)) {
            return [
                'valid' => false,
                'message' => 'License not found. Please activate your license.',
                'status' => 'not_found',
            ];
        }

        $status = $licenseData['status'] ?? 'inactive';
        if ($status !== 'active') {
            return [
                'valid' => false,
                'message' => 'License is not active.',
                'status' => $status,
            ];
        }

        $expiresAt = $licenseData['expires_at'] ?? null;
        if ($expiresAt && strtotime($expiresAt) < time()) {
            return [
                'valid' => false,
                'message' => 'License has expired. Please renew your license.',
                'status' => 'expired',
                'expires_at' => $expiresAt,
            ];
        }

        return [
            'valid' => true,
            'message' => 'License is valid.',
            'status' => 'active',
            'client' => $licenseData['client'] ?? null,
            'email' => $licenseData['email'] ?? null,
            'expires_at' => $expiresAt,
            'key' => $licenseData['license_key'] ?? null,
        ];
    }

    public function getLicenseInfo(): ?array
    {
        return $this->loadLocalLicense();
    }

    public function revokeLocalLicense(): bool
    {
        return $this->revokeLicense();
    }

    public function blockLocalLicense(string $message): bool
    {
        $existing = $this->loadLocalLicense() ?? [];
        return $this->saveLocalLicense(array_merge($existing, [
            'status' => 'blocked',
            'message' => $message,
            'blocked_at' => now()->toIso8601String(),
        ]));
    }

    public function getHardwareId(): string
    {
        try {
            return app(SejoliService::class)->getHardwareID();
        } catch (\Throwable $e) {
            $path = storage_path('app/device_id.txt');
            if (File::exists($path)) {
                return trim((string) File::get($path));
            }
            $newId = (string) Str::uuid();
            @File::put($path, $newId);
            return $newId;
        }
    }

    public function sharedIdentifier(string $licenseKey): string
    {
        $salt = (string) config('license.shared_identifier_salt', 'simak');
        return hash('sha256', $salt . '|' . $licenseKey);
    }

    private function resolveSejoliIdentifier(string $licenseKey, ?string $identifier = null): string
    {
        $mode = strtolower((string) config('license.sejoli_identifier', 'shared'));
        if ($mode === 'hardware') {
            if (is_string($identifier) && trim($identifier) !== '') {
                return $identifier;
            }
            return $this->getHardwareId();
        }

        return $this->sharedIdentifier($licenseKey);
    }

    public function activate(string $email, string $password, string $licenseKey, ?string $deviceId = null): ?array
    {
        $identifier = $this->resolveSejoliIdentifier($licenseKey, $deviceId);
        return app(SejoliService::class)->registerLicense($email, $password, $licenseKey, $identifier);
    }

    public function validateRemote(string $licenseKey, ?string $hardwareId = null): ?array
    {
        $sejoli = app(SejoliService::class);
        $identifier = $this->resolveSejoliIdentifier($licenseKey, null);
        $primary = $sejoli->validateLicense($licenseKey, $identifier);

        if ($primary === null) {
            return null;
        }

        if ($this->isRemoteValid($primary, $licenseKey, 'validate')) {
            return $primary;
        }

        $fallbacks = [];
        if (is_string($hardwareId) && trim($hardwareId) !== '' && $hardwareId !== $identifier) {
            $fallbacks[] = $hardwareId;
        }
        $legacy = $this->getHardwareId();
        if ($legacy !== $identifier && $legacy !== ($fallbacks[0] ?? null)) {
            $fallbacks[] = $legacy;
        }

        foreach ($fallbacks as $fallback) {
            $secondary = $sejoli->validateLicense($licenseKey, $fallback);
            if ($secondary && $this->isRemoteValid($secondary, $licenseKey, 'validate')) {
                return $secondary;
            }
        }

        return $primary;
    }

    public function validateRemoteWithAuth(string $email, string $password, string $licenseKey, ?string $hardwareId = null): ?array
    {
        $sejoli = app(SejoliService::class);
        $identifier = $this->resolveSejoliIdentifier($licenseKey, null);
        $primary = $sejoli->validateLicenseWithAuth($email, $password, $licenseKey, $identifier);

        if ($primary === null) {
            return null;
        }

        if ($this->isRemoteValid($primary, $licenseKey, 'validate')) {
            return $primary;
        }

        $fallbacks = [];
        if (is_string($hardwareId) && trim($hardwareId) !== '' && $hardwareId !== $identifier) {
            $fallbacks[] = $hardwareId;
        }
        $legacy = $this->getHardwareId();
        if ($legacy !== $identifier && $legacy !== ($fallbacks[0] ?? null)) {
            $fallbacks[] = $legacy;
        }

        foreach ($fallbacks as $fallback) {
            $secondary = $sejoli->validateLicenseWithAuth($email, $password, $licenseKey, $fallback);
            if ($secondary && $this->isRemoteValid($secondary, $licenseKey, 'validate')) {
                return $secondary;
            }
        }

        return $primary;
    }

    public function resetRemote(string $email, string $password, string $licenseKey, ?string $deviceId = null): ?array
    {
        $sejoli = app(SejoliService::class);
        $identifier = $this->resolveSejoliIdentifier($licenseKey, null);
        $primary = $sejoli->resetLicense($email, $password, $licenseKey, $identifier);

        if ($primary === null) {
            return null;
        }

        if ($this->isRemoteValid($primary, $licenseKey, 'reset')) {
            return $primary;
        }

        $fallbacks = [];
        if (is_string($deviceId) && trim($deviceId) !== '' && $deviceId !== $identifier) {
            $fallbacks[] = $deviceId;
        }
        $legacy = $this->getHardwareId();
        if ($legacy !== $identifier && $legacy !== ($fallbacks[0] ?? null)) {
            $fallbacks[] = $legacy;
        }

        foreach ($fallbacks as $fallback) {
            $secondary = $sejoli->resetLicense($email, $password, $licenseKey, $fallback);
            if ($secondary && $this->isRemoteValid($secondary, $licenseKey, 'reset')) {
                return $secondary;
            }
        }

        return $primary;
    }

    public function messageContains($payload, string|array $needles): bool
    {
        if (!is_array($payload)) {
            return false;
        }

        $needles = (array) $needles;
        $message = $payload['message'] ?? '';
        $messages = $payload['messages'] ?? [];

        if (is_string($messages)) {
            $messages = [$messages];
        }

        $haystacks = [];
        if (is_string($message)) {
            $haystacks[] = $message;
        }
        foreach ($messages as $msg) {
            if (is_string($msg)) {
                $haystacks[] = $msg;
            }
        }

        foreach ($haystacks as $text) {
            foreach ($needles as $needle) {
                if (stripos($text, (string) $needle) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function extractSubscriptionStatus(?array $payload): ?string
    {
        if (!is_array($payload)) {
            return null;
        }

        return $payload['data']['subscription_status'] ?? null;
    }

    public function extractSubscriptionExpirationDate(?array $payload): ?string
    {
        if (!is_array($payload)) {
            return null;
        }

        $date = $payload['data']['expiration_date'] ?? null;
        if (is_string($date) && trim($date) !== '') {
            return $date;
        }

        return null;
    }

    public function isRemoteValid($payload, string $licenseKey, string $mode = 'validate'): bool
    {
        if (!is_array($payload)) {
            return false;
        }

        $validFlag = filter_var($payload['valid'] ?? false, FILTER_VALIDATE_BOOLEAN);

        switch ($mode) {
            case 'activate':
                if ($validFlag) {
                    return true;
                }
                $message = $payload['message'] ?? '';
                return stripos($message, 'is registered to') !== false ||
                    stripos($message, 'Aktivasi berhasil') !== false;

            case 'reset':
                if ($validFlag) {
                    return true;
                }
                $message = $payload['message'] ?? '';
                return stripos($message, 'telah dihapus') !== false ||
                    stripos($message, 'Reset lisensi berhasil') !== false;

            case 'validate':
            default:
                if ($validFlag) {
                    $data = $payload['data'] ?? [];
                    $subscriptionStatus = $data['subscription_status'] ?? 'active';
                    if ($subscriptionStatus === 'expired') {
                        return false;
                    }
                    return true;
                }
                return false;
        }
    }

    public function revokeLicense(): bool
    {
        $deleted = true;

        $filesToDelete = [
            $this->jsonLicenseFile,
            $this->licenseFile,
        ];

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $appData = getenv('LOCALAPPDATA') ?: getenv('APPDATA');
            if ($appData) {
                $fallbackDir = $appData . DIRECTORY_SEPARATOR . 'SIMAK' . DIRECTORY_SEPARATOR . 'license';
                $filesToDelete[] = $fallbackDir . '/license.json';
                $filesToDelete[] = $fallbackDir . '/license.dat';
            }
        }

        foreach ($filesToDelete as $file) {
            if (File::exists($file)) {
                try {
                    $deleted = @unlink($file) && $deleted;
                } catch (\Throwable $e) {
                    Log::warning('[License] Failed to delete: ' . $file);
                }
            }
        }

        return $deleted;
    }
}
