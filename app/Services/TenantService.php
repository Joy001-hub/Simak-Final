<?php

namespace App\Services;

use App\Exceptions\DeviceLimitExceededException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TenantService
{
    public function __construct(
        private readonly AppModeService $modeService
    ) {
    }

    public function ensureTenant(string $licenseKey, ?string $subscriptionStatus = null): array
    {
        $tenantKey = $this->modeService->tenantKey($licenseKey);

        $tenant = DB::connection('pgsql')->table('tenants')->where('tenant_key', $tenantKey)->first();
        $plan = $subscriptionStatus === 'active' ? 'premium' : 'basic';

        if (!$tenant) {
            DB::connection('pgsql')->table('tenants')->insert([
                'tenant_key' => $tenantKey,
                'plan' => $plan,
                'subscription_status' => $subscriptionStatus,
                'max_devices' => $this->modeService->maxDevices(),
                'subscription_checked_at' => $subscriptionStatus ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } elseif ($subscriptionStatus) {
            DB::connection('pgsql')->table('tenants')->where('tenant_key', $tenantKey)->update([
                'plan' => $plan,
                'subscription_status' => $subscriptionStatus,
                'subscription_checked_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [
            'tenant_key' => $tenantKey,
            'plan' => $plan,
        ];
    }

    public function registerDevice(string $licenseKey, string $deviceId, ?string $deviceName = null): void
    {
        $tenantKey = $this->modeService->tenantKey($licenseKey);
        $tenant = DB::connection('pgsql')->table('tenants')->where('tenant_key', $tenantKey)->first();

        if (!$tenant) {
            $this->ensureTenant($licenseKey);
            $tenant = DB::connection('pgsql')->table('tenants')->where('tenant_key', $tenantKey)->first();
        }

        $device = DB::connection('pgsql')
            ->table('tenant_devices')
            ->where('tenant_key', $tenantKey)
            ->where('device_id', $deviceId)
            ->whereNull('revoked_at')
            ->first();

        if ($device) {
            $lastSeen = $device->last_seen_at ? Carbon::parse($device->last_seen_at) : null;
            if (!$lastSeen || $lastSeen->diffInMinutes(now()) >= 10) {
                DB::connection('pgsql')->table('tenant_devices')
                    ->where('id', $device->id)
                    ->update([
                        'last_seen_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
            return;
        }

        $activeCount = DB::connection('pgsql')
            ->table('tenant_devices')
            ->where('tenant_key', $tenantKey)
            ->whereNull('revoked_at')
            ->count();

        $limit = (int) ($tenant->max_devices ?? $this->modeService->maxDevices());
        if ($activeCount >= $limit) {
            throw new DeviceLimitExceededException('Batas perangkat tercapai.');
        }

        DB::connection('pgsql')->table('tenant_devices')->insert([
            'tenant_key' => $tenantKey,
            'device_id' => $deviceId,
            'device_name' => $deviceName,
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function getDeviceStats(string $licenseKey): array
    {
        $tenantKey = $this->modeService->tenantKey($licenseKey);
        $tenant = DB::connection('pgsql')->table('tenants')->where('tenant_key', $tenantKey)->first();

        $activeCount = DB::connection('pgsql')
            ->table('tenant_devices')
            ->where('tenant_key', $tenantKey)
            ->whereNull('revoked_at')
            ->count();

        return [
            'active' => $activeCount,
            'limit' => (int) ($tenant->max_devices ?? $this->modeService->maxDevices()),
        ];
    }
}
