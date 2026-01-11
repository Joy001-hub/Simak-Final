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
                        $hardwareId = $license['hardware_id'] ?? $this->licenseService->getHardwareId();

                        $this->licenseService->saveLocalLicense([
                            'license_key' => $rememberedKey,
                            'status' => 'active',
                            'hardware_id' => $hardwareId,
                            'subscription_status' => $subscriptionStatus,
                            'subscription_expires_at' => $subscriptionExpiresAt,
                            'subscription_checked_at' => now()->toIso8601String(),
                            'last_check_at' => now()->toIso8601String(),
                            'message' => 'Auto-login via cookie',
                        ]);

                        try {
                            $tenantService = app(TenantService::class);
                            $tenantService->ensureTenant($rememberedKey, $subscriptionStatus);
                            $tenantService->registerDevice($rememberedKey, $hardwareId);
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
}
