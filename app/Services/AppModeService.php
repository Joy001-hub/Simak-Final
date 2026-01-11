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
        $forcedMode = config('license.force_mode');
        if (is_string($forcedMode)) {
            $forcedMode = strtolower(trim($forcedMode));
            if (in_array($forcedMode, [self::MODE_LOCAL, self::MODE_CLOUD], true)) {
                $tenantKey = null;
                if ($forcedMode === self::MODE_CLOUD) {
                    $tenantKey = config('license.force_tenant_key');
                    if (!$tenantKey) {
                        $license = $this->licenseService->loadLocalLicense();
                        if ($license && !empty($license['license_key'])) {
                            $tenantKey = $this->tenantKey($license['license_key']);
                        }
                    }
                }

                return $this->setContext($forcedMode, $tenantKey, false, null);
            }
        }

        $license = $this->licenseService->loadLocalLicense();

        if (!$license || empty($license['license_key'])) {
            return $this->setContext(self::MODE_LOCAL, null, false, null);
        }

        $subscriptionStatus = Arr::get($license, 'subscription_status');
        $isPremium = $subscriptionStatus === 'active';
        $mode = $isPremium ? self::MODE_CLOUD : self::MODE_LOCAL;

        $readOnly = $this->shouldReadOnly($license);
        $reason = $readOnly ? 'offline_grace' : null;

        $tenantKey = $isPremium ? $this->tenantKey($license['license_key']) : null;

        return $this->setContext($mode, $tenantKey, $readOnly, $reason);
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
}
