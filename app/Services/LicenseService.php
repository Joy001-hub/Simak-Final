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

    public function buildLicenseKey(int|string $userId, int|string $productId): string
    {
        $userId = trim((string) $userId);
        $productId = trim((string) $productId);
        return 'SEJOLI-' . $userId . '-' . $productId;
    }

    public function parseLicenseKey(string $licenseKey): ?array
    {
        $key = trim($licenseKey);
        if ($key === '') {
            return null;
        }

        if (preg_match('/^SEJOLI-(\d+)-(\d+)$/', $key, $matches)) {
            return [
                'user_id' => (int) $matches[1],
                'product_id' => (int) $matches[2],
            ];
        }

        if (preg_match('/^(\d+):(\d+)$/', $key, $matches)) {
            return [
                'user_id' => (int) $matches[1],
                'product_id' => (int) $matches[2],
            ];
        }

        return null;
    }

    private function resolveSubscriptionIdentifiers(?string $licenseKey = null, ?array $license = null): ?array
    {
        if (is_string($licenseKey) && trim($licenseKey) !== '') {
            $parsed = $this->parseLicenseKey($licenseKey);
            if ($parsed) {
                return $parsed;
            }
        }

        if (!$license) {
            $license = $this->loadLocalLicense();
        }

        if (is_array($license)) {
            $userId = $license['sejoli_user_id'] ?? null;
            $productId = $license['sejoli_product_id'] ?? null;
            if ($userId && $productId) {
                return [
                    'user_id' => (int) $userId,
                    'product_id' => (int) $productId,
                ];
            }
        }

        return null;
    }

    public function activate(int|string $userId, int|string $productId): ?array
    {
        return app(SejoliService::class)->validateSubscription($userId, $productId);
    }

    public function validateRemote(string $licenseKey, ?string $hardwareId = null): ?array
    {
        $identifiers = $this->resolveSubscriptionIdentifiers($licenseKey);
        if (!$identifiers) {
            return null;
        }

        return app(SejoliService::class)->validateSubscription(
            $identifiers['user_id'],
            $identifiers['product_id']
        );
    }

    public function validateRemoteWithAuth(string $email, string $password, string $licenseKey, ?string $hardwareId = null): ?array
    {
        return $this->validateRemote($licenseKey, $hardwareId);
    }

    public function resetRemote(string $email, string $password, string $licenseKey, ?string $deviceId = null): ?array
    {
        return $this->validateRemote($licenseKey, $deviceId);
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
        $subscription = $payload['data']['subscription'] ?? null;
        if (is_array($subscription)) {
            $status = $subscription['status'] ?? null;
            if (is_string($status) && trim($status) !== '') {
                return $status;
            }
        }

        $subscriptions = $payload['data']['subscriptions'] ?? null;
        if (is_array($subscriptions) && !empty($subscriptions)) {
            $first = reset($subscriptions);
            if (is_array($first)) {
                $status = $first['status'] ?? null;
                if (is_string($status) && trim($status) !== '') {
                    return $status;
                }
            }
        }

        $hasAccess = $payload['data']['has_access'] ?? null;
        if ($hasAccess === true) {
            return 'active';
        }
        if ($hasAccess === false) {
            return 'inactive';
        }

        return $payload['data']['subscription_status'] ?? null;
    }

    public function extractSubscriptionExpirationDate(?array $payload): ?string
    {
        if (!is_array($payload)) {
            return null;
        }
        $subscription = $payload['data']['subscription'] ?? null;
        if (is_array($subscription)) {
            $date = $subscription['end_date'] ?? $subscription['expiration_date'] ?? null;
            if (is_string($date) && trim($date) !== '') {
                return $date;
            }
        }

        $subscriptions = $payload['data']['subscriptions'] ?? null;
        if (is_array($subscriptions) && !empty($subscriptions)) {
            $first = reset($subscriptions);
            if (is_array($first)) {
                $date = $first['end_date'] ?? $first['expiration_date'] ?? null;
                if (is_string($date) && trim($date) !== '') {
                    return $date;
                }
            }
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
        $validFlag = filter_var($payload['success'] ?? ($payload['valid'] ?? false), FILTER_VALIDATE_BOOLEAN);

        switch ($mode) {
            case 'activate':
                if ($validFlag) {
                    $hasAccess = $payload['data']['has_access'] ?? null;
                    if ($hasAccess === false) {
                        return false;
                    }
                    $status = $payload['data']['subscription']['status'] ?? null;
                    if (is_string($status) && strtolower($status) === 'expired') {
                        return false;
                    }
                    return true;
                }
                return false;

            case 'reset':
                return $validFlag;

            case 'validate':
            default:
                if (!$validFlag) {
                    return false;
                }

                $data = $payload['data'] ?? [];
                $hasAccess = $data['has_access'] ?? null;
                if ($hasAccess === false) {
                    return false;
                }

                $subscriptionStatus = $data['subscription']['status'] ?? $data['subscription_status'] ?? null;
                if (is_string($subscriptionStatus) && strtolower($subscriptionStatus) === 'expired') {
                    return false;
                }

                return true;
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
