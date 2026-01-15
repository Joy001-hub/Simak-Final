<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SejoliWebhookController extends Controller
{
    public function handle(Request $request, LicenseService $licenseService)
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('X-Sejoli-Signature', '');
        $eventHeader = (string) $request->header('X-Sejoli-Event', '');
        $timestamp = (string) $request->header('X-Sejoli-Timestamp', '');
        $secret = config('services.sejoli.webhook_secret');

        if (!is_string($secret) || trim($secret) === '') {
            Log::error('[SejoliWebhook] Missing SEJOLI_WEBHOOK_SECRET');
            return response()->json([
                'success' => false,
                'message' => 'Webhook secret not configured.',
            ], 500);
        }

        if (trim($signature) === '') {
            Log::warning('[SejoliWebhook] Missing signature header');
            return response()->json([
                'success' => false,
                'message' => 'Missing signature.',
            ], 401);
        }

        $normalizedSignature = $this->normalizeSignature($signature);
        $expectedSignature = hash_hmac('sha256', $payload, trim($secret));

        if (!hash_equals($expectedSignature, $normalizedSignature)) {
            Log::warning('[SejoliWebhook] Invalid signature', [
                'event' => $eventHeader,
                'timestamp' => $timestamp,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid signature.',
            ], 401);
        }

        $payloadData = $request->json()->all();
        if (empty($payloadData) && is_string($payload) && trim($payload) !== '') {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                $payloadData = $decoded;
            }
        }
        $event = $eventHeader !== '' ? $eventHeader : (string) ($payloadData['event'] ?? '');

        $identifiers = $this->extractIdentifiers($payloadData);
        if (!$identifiers) {
            Log::warning('[SejoliWebhook] Missing user_id/product_id', [
                'event' => $event,
                'payload' => $payloadData,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Missing user_id or product_id.',
            ], 422);
        }

        $status = $this->resolveSubscriptionStatus($payloadData, $event);
        $endDate = $this->extractEndDate($payloadData);
        $userEmail = $payloadData['data']['user']['email'] ?? $payloadData['data']['subscription']['email'] ?? null;
        $userEmail = is_string($userEmail) ? strtolower(trim($userEmail)) : null;
        $userPhone = $this->extractPhone($payloadData);
        $orderId = $payloadData['data']['subscription']['order_id'] ?? null;

        $licenseKey = $licenseService->buildLicenseKey(
            $identifiers['user_id'],
            $identifiers['product_id']
        );

        // Upsert user subscription status (no device/tenant limit)
        try {
            $user = User::query()
                ->where('sejoli_user_id', $identifiers['user_id'])
                ->where('sejoli_product_id', $identifiers['product_id'])
                ->first();

            if (!$user && $userEmail) {
                $user = User::query()->where('email', $userEmail)->first();
            }

            $displayName = $payloadData['data']['user']['display_name'] ?? null;
            $fallbackEmail = 'user_' . $identifiers['user_id'] . '@example.com';

            if ($user) {
                $updates = [
                    'subscription_status' => $status,
                    'subscription_end_date' => $endDate,
                    'last_subscription_check_at' => now(),
                    'updated_at' => now(),
                ];

                if ($displayName) {
                    $updates['name'] = $displayName;
                }

                if ($userEmail && $user->email !== $userEmail) {
                    $updates['email'] = $userEmail;
                }

                if ($userPhone && (empty($user->phone) || $user->phone === $userPhone)) {
                    $updates['phone'] = $userPhone;
                }

                if (empty($user->sejoli_user_id)) {
                    $updates['sejoli_user_id'] = $identifiers['user_id'];
                }
                if (empty($user->sejoli_product_id)) {
                    $updates['sejoli_product_id'] = $identifiers['product_id'];
                }

                DB::table('users')->where('id', $user->id)->update($updates);
            } else {
                $insertEmail = $userEmail ?: $fallbackEmail;
                DB::table('users')->insert([
                    'name' => $displayName ?: 'User',
                    'email' => $insertEmail,
                    'phone' => $userPhone,
                    'password' => Hash::make(Str::random(32)),
                    'sejoli_user_id' => $identifiers['user_id'],
                    'sejoli_product_id' => $identifiers['product_id'],
                    'subscription_status' => $status,
                    'subscription_end_date' => $endDate,
                    'last_subscription_check_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[SejoliWebhook] Failed to upsert user subscription', [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $payloadJson = is_string($payload) && trim($payload) !== ''
                ? $payload
                : json_encode($payloadData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            DB::table('sejoli_webhook_events')->insert([
                'event' => $event,
                'user_id' => $identifiers['user_id'],
                'product_id' => $identifiers['product_id'],
                'order_id' => $orderId,
                'status' => $status,
                'end_date' => $endDate,
                'email' => $userEmail,
                'phone' => $userPhone,
                'payload' => $payloadJson,
                'signature' => $normalizedSignature,
                'received_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[SejoliWebhook] Failed to store event log', [
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('[SejoliWebhook] Event processed', [
            'event' => $event,
            'license_key' => $licenseKey,
            'status' => $status,
            'timestamp' => $timestamp,
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    private function normalizeSignature(string $signature): string
    {
        $signature = trim($signature);
        if (str_starts_with($signature, 'sha256=')) {
            $signature = substr($signature, 7);
        }

        return $signature;
    }

    private function extractIdentifiers(array $payload): ?array
    {
        $data = $payload['data'] ?? [];
        $subscription = $data['subscription'] ?? [];

        $userId = $data['user']['id'] ?? $data['user_id'] ?? $subscription['user_id'] ?? null;
        $productId = $data['product']['id'] ?? $data['product_id'] ?? $subscription['product_id'] ?? null;

        if (!$userId || !$productId) {
            return null;
        }

        return [
            'user_id' => (int) $userId,
            'product_id' => (int) $productId,
        ];
    }

    private function resolveSubscriptionStatus(array $payload, string $event): ?string
    {
        $status = $payload['data']['subscription']['status'] ?? null;
        if (is_string($status) && trim($status) !== '') {
            return $status;
        }

        if ($event === 'subscription.expired') {
            return 'expired';
        }

        if ($event === 'subscription.activated' || $event === 'subscription.renewed') {
            return 'active';
        }

        return null;
    }

    private function extractEndDate(array $payload): ?string
    {
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

        return null;
    }

    private function normalizePhone(string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', $input);
        if (!$digits) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        return $digits;
    }

    private function extractPhone(array $payload): ?string
    {
        $data = $payload['data'] ?? [];
        $candidates = [];

        if (isset($data['user']) && is_array($data['user'])) {
            foreach ([
                'phone',
                'phone_number',
                'whatsapp',
                'whatsapp_number',
                'whatsapp_phone',
                'mobile',
                'no_hp',
                'no_telp',
            ] as $key) {
                if (isset($data['user'][$key]) && is_scalar($data['user'][$key]) && $data['user'][$key] !== '') {
                    $candidates[] = $data['user'][$key];
                }
            }
        }

        if (isset($data['subscription']) && is_array($data['subscription'])) {
            foreach (['phone', 'customer_phone', 'billing_phone'] as $key) {
                if (isset($data['subscription'][$key]) && is_scalar($data['subscription'][$key]) && $data['subscription'][$key] !== '') {
                    $candidates[] = $data['subscription'][$key];
                }
            }
        }

        if (isset($data['order']) && is_array($data['order'])) {
            foreach (['phone', 'customer_phone', 'billing_phone'] as $key) {
                if (isset($data['order'][$key]) && is_scalar($data['order'][$key]) && $data['order'][$key] !== '') {
                    $candidates[] = $data['order'][$key];
                }
            }
        }

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizePhone((string) $candidate);
            if ($normalized) {
                return $normalized;
            }
        }

        return null;
    }
}
