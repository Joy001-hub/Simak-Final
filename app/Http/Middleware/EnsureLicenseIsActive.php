<?php

namespace App\Http\Middleware;

use App\Exceptions\DeviceLimitExceededException;
use App\Services\LicenseService;
use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class EnsureLicenseIsActive
{
    protected LicenseService $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    public function handle(Request $request, Closure $next)
    {
        if (!session('license_authenticated')) {
            $license = $this->licenseService->loadLocalLicense();

            if (!$license || empty($license['license_key'])) {
                $rememberedKey = $request->cookie('simak_license');
                if ($rememberedKey) {
                    $resp = $this->licenseService->validateRemote($rememberedKey);

                    if (is_array($resp) && $this->licenseService->isRemoteValid($resp, $rememberedKey, 'validate')) {
                        $subscriptionStatus = $this->licenseService->extractSubscriptionStatus($resp);
                        $subscriptionExpiresAt = $this->licenseService->extractSubscriptionExpirationDate($resp);
                        $deviceId = $this->resolveDeviceId($request, $license);
                        $deviceName = $this->resolveDeviceName($request);

                        $this->licenseService->saveLocalLicense([
                            'license_key' => $rememberedKey,
                            'status' => 'active',
                            'device_id' => $deviceId,
                            'hardware_id' => $deviceId,
                            'subscription_status' => $subscriptionStatus,
                            'subscription_expires_at' => $subscriptionExpiresAt,
                            'subscription_checked_at' => now()->toIso8601String(),
                            'last_check_at' => now()->toIso8601String(),
                            'message' => 'Auto-login via cookie',
                        ]);

                        try {
                            $tenantService = app(TenantService::class);
                            $tenantService->ensureTenant($rememberedKey, $subscriptionStatus);
                            $tenantService->registerDevice($rememberedKey, $deviceId, $deviceName);
                        } catch (DeviceLimitExceededException $e) {
                            Cookie::queue(Cookie::forget('simak_license'));
                            return redirect()->route('license.activate.form')
                                ->withErrors(['msg' => 'Batas perangkat tercapai. Silakan upgrade add-on perangkat.']);
                        } catch (\Throwable $e) {
                            // Best-effort only; allow login if tenant registry fails.
                        }

                        session(['license_authenticated' => true]);

                        return $next($request);
                    }

                    Cookie::queue(Cookie::forget('simak_license'));
                }

                return redirect()->route('license.activate.form')
                    ->withErrors(['msg' => 'Lisensi tidak ditemukan. Silakan aktivasi terlebih dahulu.']);
            }

            return redirect()->route('login')
                ->withErrors(['msg' => 'Silakan login terlebih dahulu.']);
        }

        $license = $this->licenseService->loadLocalLicense();
        if (!$license || ($license['status'] ?? 'inactive') !== 'active') {
            session()->forget('license_authenticated');
            session()->forget('license_user_email');
            Cookie::queue(Cookie::forget('simak_license'));

            return redirect()->route('license.activate.form')
                ->withErrors(['msg' => 'Lisensi tidak aktif. Silakan aktivasi ulang.']);
        }

        return $next($request);
    }

    private function resolveDeviceId(Request $request, ?array $license): string
    {
        $deviceId = trim((string) $request->input('device_id', ''));
        if ($deviceId !== '') {
            return $deviceId;
        }

        $cookieId = trim((string) $request->cookie('simak_device_id', ''));
        if ($cookieId !== '') {
            return $cookieId;
        }

        if (is_array($license)) {
            $licenseId = $license['device_id'] ?? $license['hardware_id'] ?? $license['string'] ?? '';
            $licenseId = is_string($licenseId) ? trim($licenseId) : '';
            if ($licenseId !== '') {
                return $licenseId;
            }
        }

        return $this->licenseService->getHardwareId();
    }

    private function resolveDeviceName(Request $request): ?string
    {
        $deviceName = trim((string) $request->input('device_name', ''));
        if ($deviceName === '') {
            $deviceName = trim((string) $request->cookie('simak_device_name', ''));
        }
        if ($deviceName === '') {
            $deviceName = trim((string) $request->header('User-Agent', ''));
        }
        if ($deviceName === '') {
            return null;
        }

        return substr($deviceName, 0, 120);
    }
}
