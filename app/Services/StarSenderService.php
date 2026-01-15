<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StarSenderService
{
    private ?string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.starsender.api_key');
        $this->apiUrl = rtrim((string) config('services.starsender.api_url'), '/');
    }

    public function sendOtp(string $phoneNumber, string $message): bool
    {
        if (!is_string($this->apiKey) || trim($this->apiKey) === '') {
            Log::error('[StarSender] API key not set (STARSENDER_API_KEY).');
            return false;
        }

        try {
            $payload = [
                'messageType' => 'text',
                'to' => $phoneNumber,
                'body' => $message,
            ];

            $response = Http::acceptJson()
                ->timeout(15)
                ->withHeaders([
                    'Authorization' => trim($this->apiKey),
                ])
                ->post($this->apiUrl, $payload);

            if (!$response->successful()) {
                Log::warning('[StarSender] Failed to send OTP', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('[StarSender] Exception: ' . $e->getMessage());
            return false;
        }
    }
}
