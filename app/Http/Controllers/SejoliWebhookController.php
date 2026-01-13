<?php

namespace App\Http\Controllers;

use App\Services\LicenseService;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SejoliWebhookController extends Controller
{
    public function handle(Request $request, LicenseService $licenseService, TenantService $tenantService)
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
        $userPhone = $payloadData['data']['user']['phone'] ?? null;
        $orderId = $payloadData['data']['subscription']['order_id'] ?? null;

        $licenseKey = $licenseService->buildLicenseKey(
            $identifiers['user_id'],
            $identifiers['product_id']
        );

        try {
            $tenantService->ensureTenant($licenseKey, $status);
        } catch (\Throwable $e) {
            Log::error('[SejoliWebhook] Failed to update tenant', [
                'license_key' => $licenseKey,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tenant.',
            ], 500);
        }

        try {
            DB::table('sejoli_webhook_events')->insert([
                'event' => $event,
                'user_id' => $identifiers['user_id'],
                'product_id' => $identifiers['product_id'],
                'order_id' => $orderId,
                'status' => $status,
                'end_date' => $endDate,
                'email' => $userEmail,
                'phone' => $userPhone,
                'payload' => $payloadData,
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
}
