<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Client\PendingRequest;

class SejoliService
{
    protected string $baseUrl = 'https://kavling.pro';
    protected ?string $apiKey = null;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.sejoli.base_url', $this->baseUrl), '/');
        $this->apiKey = config('services.sejoli.api_key');
    }

    private function http(): PendingRequest
    {
        $curlOptions = [];
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            $curlOptions[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }

        $request = Http::withOptions([
            'verify' => false,
            'curl' => $curlOptions,
        ])
            ->acceptJson()
            ->connectTimeout(8)
            ->timeout(20)
            ->retry(1, 1500, throw: false);

        if (is_string($this->apiKey) && trim($this->apiKey) !== '') {
            $request = $request->withToken(trim($this->apiKey));
        }

        return $request;
    }

    private function ensureApiKey(): bool
    {
        if (!is_string($this->apiKey) || trim($this->apiKey) === '') {
            Log::error('[Sejoli] API key belum di-set (SEJOLI_API_KEY).');
            return false;
        }

        return true;
    }

    public function getSubscriptions(int|string $userId): ?array
    {
        if (!$this->ensureApiKey()) {
            return null;
        }

        try {
            $response = $this->http()->get($this->baseUrl . '/wp-json/sejoli/v1/subscription/' . $userId);
            $body = $response->json();

            Log::info('[Sejoli] Subscription list response', [
                'user_id' => $userId,
                'status' => $response->status(),
                'body' => $body,
            ]);

            return $response->successful() ? $body : null;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('[Sejoli] Subscription list connection failed: ' . $e->getMessage());
            return null;
        } catch (\Throwable $e) {
            Log::error('[Sejoli] Subscription list failed: ' . $e->getMessage());
            return null;
        }
    }

    public function validateSubscription(int|string $userId, int|string $productId): ?array
    {
        if (!$this->ensureApiKey()) {
            return null;
        }

        try {
            $payload = [
                'user_id' => (int) $userId,
                'product_id' => (int) $productId,
            ];

            $response = $this->http()
                ->asJson()
                ->post($this->baseUrl . '/wp-json/sejoli/v1/subscription/validate', $payload);

            $body = $response->json();

            Log::info('[Sejoli] Validate response', [
                'user_id' => $userId,
                'product_id' => $productId,
                'status' => $response->status(),
                'body' => $body,
            ]);

            return $response->successful() ? $body : null;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('[Sejoli] Validate connection failed: ' . $e->getMessage());
            return null;
        } catch (\Throwable $e) {
            Log::warning('[Sejoli] Validate failed: ' . $e->getMessage());
            return null;
        }
    }

    public function getSubscriptionByOrder(int|string $orderId): ?array
    {
        if (!$this->ensureApiKey()) {
            return null;
        }

        try {
            $response = $this->http()->get($this->baseUrl . '/wp-json/sejoli/v1/subscription/order/' . $orderId);
            $body = $response->json();

            Log::info('[Sejoli] Subscription order response', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => $body,
            ]);

            return $response->successful() ? $body : null;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('[Sejoli] Subscription order connection failed: ' . $e->getMessage());
            return null;
        } catch (\Throwable $e) {
            Log::error('[Sejoli] Subscription order failed: ' . $e->getMessage());
            return null;
        }
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
}
