<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Client\PendingRequest;

class SejoliService
{
    protected string $baseUrl = 'https://kavling.pro';

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.sejoli.base_url', $this->baseUrl), '/');
    }

    private function http(): PendingRequest
    {
        $curlOptions = [];
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            $curlOptions[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }

        return Http::withOptions([
            'verify' => false,
            'curl' => $curlOptions,
        ])
            ->acceptJson()
            ->connectTimeout(8)
            ->timeout(20)
            ->retry(1, 1500, throw: false);
    }

    public function getHardwareID(): string
    {
        $biosUUID = $this->getBiosHardwareID();
        if ($biosUUID) {
            return $biosUUID;
        }

        $path = storage_path('app/device_id.txt');
        if (file_exists($path)) {
            $saved = trim(file_get_contents($path));
            if (!empty($saved)) {
                return $saved;
            }
        }

        $newId = (string) Str::uuid();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, $newId);
        return $newId;
    }

    public function getBiosHardwareID(): ?string
    {
        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $output = shell_exec('wmic csproduct get uuid 2>nul');
                if ($output && preg_match('/[A-F0-9-]{36}/i', $output, $matches)) {
                    return strtoupper($matches[0]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[SejoliService] HWID error: ' . $e->getMessage());
        }
        return null;
    }

    public function registerLicense(string $email, string $password, string $licenseKey, ?string $deviceId = null): ?array
    {
        try {
            $deviceId = $deviceId ?: $this->getHardwareID();
            $requestData = [
                'user_email' => trim($email),
                'user_pass' => $password,
                'license' => trim($licenseKey),
                'string' => $deviceId,
            ];

            Log::info('[Sejoli] Register request', [
                'url' => $this->baseUrl . '/sejoli-license/',
                'email' => $email,
                'license' => $licenseKey,
                'hardware_id' => $requestData['string'],
            ]);

            $response = $this->http()->asForm()->post($this->baseUrl . '/sejoli-license/', $requestData);
            $body = $response->json();

            Log::info('[Sejoli] Register response', [
                'status' => $response->status(),
                'body' => $body,
            ]);

            return $response->successful() ? $body : null;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('[Sejoli] Register connection failed: ' . $e->getMessage());
            return null;
        } catch (\Throwable $e) {
            Log::error('[Sejoli] Register failed: ' . $e->getMessage());
            return null;
        }
    }

    public function validateLicense(string $licenseKey, ?string $hardwareId = null, ?string $token = null, ?string $email = null, ?string $password = null): ?array
    {
        try {
            $string = $hardwareId ?: $this->getHardwareID();
            $http = $this->http()->asForm();

            if ($token) {
                $http = $http->withToken($token);
            }

            $requestData = ['string' => $string];

            if ($email && $password) {
                $requestData['user_email'] = trim($email);
                $requestData['user_pass'] = $password;
            }

            $response = $http->post($this->baseUrl . '/sejoli-validate-license/', $requestData);
            $body = $response->json();

            Log::info('[Sejoli] Validate response', [
                'license' => $licenseKey,
                'has_credentials' => !empty($email),
                'status' => $response->status(),
                'body' => $body,
            ]);

            return $response->successful() ? $body : null;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('[Sejoli] Validate connection failed: ' . $e->getMessage());
            return null;
        } catch (\Throwable $e) {
            Log::warning('[Sejoli] Validate failed (network issue?): ' . $e->getMessage());
            return null;
        }
    }

    public function validateLicenseWithAuth(string $email, string $password, string $licenseKey, ?string $hardwareId = null): ?array
    {
        return $this->validateLicense($licenseKey, $hardwareId, null, $email, $password);
    }

    public function resetLicense(string $email, string $password, string $licenseKey, ?string $deviceId = null, ?string $token = null): ?array
    {
        try {
            $http = $this->http()->asForm();

            if ($token) {
                $http = $http->withToken($token);
            }

            $deviceId = $deviceId ?: $this->getHardwareID();
            $response = $http->post($this->baseUrl . '/sejoli-delete-license/', [
                'user_email' => trim($email),
                'user_pass' => $password,
                'license' => trim($licenseKey),
                'string' => $deviceId,
            ]);

            $body = $response->json();

            Log::info('[Sejoli] Reset response', [
                'license' => $licenseKey,
                'status' => $response->status(),
                'body' => $body,
            ]);

            return $response->successful() ? $body : null;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('[Sejoli] Reset connection failed: ' . $e->getMessage());
            return null;
        } catch (\Throwable $e) {
            Log::error('[Sejoli] Reset failed: ' . $e->getMessage());
            return null;
        }
    }
}
