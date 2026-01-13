<?php

namespace App\Services;

use App\Support\TenantContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class AppModeService
{
    public const MODE_LOCAL = 'local';
    public const MODE_CLOUD = 'cloud';

    public function __construct(
        private readonly LicenseService $licenseService,
        private readonly TenantContext $tenantContext
    ) {
    }

    public function resolve(): array
    {
        $licenseKey = $this->resolveLicenseKey();
        $tenantKey = $licenseKey ? $this->tenantKey($licenseKey) : null;

        return $this->setContext(self::MODE_CLOUD, $tenantKey, false, null);
    }

    public function tenantKey(string $licenseKey): string
    {
        return hash('sha256', $licenseKey);
    }

    public function sharedIdentifier(string $licenseKey): string
    {
        return $this->licenseService->sharedIdentifier($licenseKey);
    }

    public function shouldReadOnly(array $license): bool
    {
        $subscriptionStatus = Arr::get($license, 'subscription_status');
        if ($subscriptionStatus !== 'active') {
            return false;
        }

        $lastCheck = Arr::get($license, 'last_check_at');
        if (!$lastCheck) {
            return false;
        }

        $graceDays = (int) config('license.grace_days', 7);
        try {
            $last = Carbon::parse($lastCheck);
        } catch (\Throwable $e) {
            return false;
        }

        return $last->diffInDays(now()) > $graceDays;
    }

    public function maxDevices(?string $subscriptionStatus = null): int
    {
        if ($subscriptionStatus === 'active') {
            return (int) config('license.device_limit', 2);
        }

        return (int) config('license.basic_device_limit', 1);
    }

    private function setContext(string $mode, ?string $tenantKey, bool $readOnly, ?string $reason): array
    {
        $this->tenantContext->setMode($mode);
        $this->tenantContext->setTenantKey($tenantKey);
        $this->tenantContext->setReadOnly($readOnly, $reason);

        return [
            'mode' => $mode,
            'tenant_key' => $tenantKey,
            'read_only' => $readOnly,
            'read_only_reason' => $reason,
        ];
    }

    private function resolveLicenseKey(): ?string
    {
        $sessionKey = session('license_key');
        if (is_string($sessionKey) && trim($sessionKey) !== '') {
            return trim($sessionKey);
        }

        try {
            $cookieKey = request()?->cookie('simak_license');
        } catch (\Throwable $e) {
            $cookieKey = null;
        }

        if (is_string($cookieKey) && trim($cookieKey) !== '') {
            return trim($cookieKey);
        }

        $local = $this->licenseService->loadLocalLicense();
        $localKey = $local['license_key'] ?? null;

        return is_string($localKey) && trim($localKey) !== '' ? trim($localKey) : null;
    }
}
