<?php

namespace App\Services;

class TenantService
{
    public function __construct(
        private readonly AppModeService $modeService
    ) {
    }

    public function ensureTenant(string $licenseKey, ?string $subscriptionStatus = null): array
    {
        // Tenant management disabled (no device limits / no tenant isolation)
        return [
            'tenant_key' => $this->modeService->tenantKey($licenseKey),
            'plan' => null,
        ];
    }

    public function registerDevice(string $licenseKey, string $deviceId, ?string $deviceName = null): void
    {
        // Device tracking disabled (no limit, no persistence)
    }

    public function getDeviceStats(string $licenseKey): array
    {
        return [
            'active' => 0,
            'limit' => PHP_INT_MAX,
        ];
    }

    public function addDevices(string $licenseKey, int $additional): array
    {
        return [
            'previous' => PHP_INT_MAX,
            'current' => PHP_INT_MAX,
        ];
    }
}
