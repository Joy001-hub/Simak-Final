<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LicenseService;
use App\Services\SejoliService;
use Illuminate\Support\Facades\Log;

class LicenseController extends Controller
{
    public function showActivate(LicenseService $licenseService)
    {
        try {
            $validation = $licenseService->validateLicense();
            if ($validation['valid'] ?? false) {
                return redirect()->route('dashboard');
            }
            return view('license.activate');
        } catch (\Throwable $e) {
            Log::error('[License] showActivate error: ' . $e->getMessage());
            return view('license.activate');
        }
    }

    public function showLogin(LicenseService $licenseService)
    {
        try {
            return view('license.login', ['hardwareId' => $licenseService->getHardwareId()]);
        } catch (\Throwable $e) {
            Log::error('[License] showLogin error: ' . $e->getMessage());
            return view('license.login', ['hardwareId' => 'unknown']);
        }
    }

    public function processActivate(Request $request, LicenseService $licenseService)
    {
        Log::info('[License] processActivate hit', ['email' => $request->input('email')]);

        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
                'license' => 'required|string',
            ]);

            $email = trim($request->email);
            $password = $request->password;
            $licenseKey = trim($request->license);

            $result = $licenseService->activate($email, $password, $licenseKey);

            if ($result === null) {
                Log::warning('[License] activation failed - server unreachable', [
                    'email' => $email,
                    'license' => $licenseKey,
                ]);
                return redirect()->route('license.activate.form')
                    ->withErrors(['msg' => 'Gagal menghubungi server lisensi. Periksa koneksi internet Anda dan coba lagi.'])
                    ->withInput();
            }

            if ($licenseService->messageContains($result, 'already registered')) {
                return redirect()->route('license.activate.form')
                    ->withErrors(['msg' => 'Lisensi sudah terdaftar di device lain. Silakan reset terlebih dahulu.'])
                    ->withInput();
            }

            $isValid = $licenseService->isRemoteValid($result, $licenseKey, 'activate');

            if ($isValid) {
                $saved = $licenseService->saveLocalLicense([
                    'license_key' => $licenseKey,
                    'status' => 'active',
                    'hardware_id' => $licenseService->getHardwareId(),
                    'email' => $email,
                    'last_check_at' => now()->toIso8601String(),
                    'message' => 'Registered via activation',
                ]);

                if (!$saved) {
                    $errorMsg = $licenseService->getLastError() ?? 'Gagal menyimpan lisensi ke perangkat.';
                    Log::error('[License] Failed to save license locally', [
                        'error' => $errorMsg,
                        'email' => $email,
                    ]);
                    return redirect()->route('license.activate.form')
                        ->withErrors(['msg' => 'Aktivasi berhasil, tapi ' . $errorMsg . ' Coba jalankan aplikasi sebagai Administrator.'])
                        ->withInput();
                }

                session(['license_authenticated' => true]);
                session(['license_user_email' => $email]);

                return redirect()->route('dashboard');
            }

            Log::warning('[License] activation failed', [
                'email' => $email,
                'license' => $licenseKey,
                'response' => $result,
            ]);

            return redirect()->route('license.activate.form')
                ->withErrors(['msg' => $result['message'] ?? 'Lisensi tidak valid atau kredensial salah.'])
                ->withInput();

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('[License] processActivate connection error: ' . $e->getMessage());
            return redirect()->route('license.activate.form')
                ->withErrors(['msg' => 'Gagal menghubungi server. Periksa koneksi internet Anda.'])
                ->withInput();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('[License] processActivate error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('license.activate.form')
                ->withErrors(['msg' => 'Terjadi kesalahan saat aktivasi. Silakan coba lagi.'])
                ->withInput();
        }
    }

    public function processLogin(Request $request, LicenseService $licenseService)
    {
        Log::info('[License] processLogin hit', ['license' => $request->input('license')]);

        try {
            $request->validate(['license' => 'required|string']);

            $licenseKey = trim($request->license);
            $local = $licenseService->loadLocalLicense();

            if (!$local || empty($local['license_key'])) {
                return redirect()->route('license.activate.form')
                    ->withErrors(['msg' => 'Lisensi belum terdaftar. Silakan aktivasi terlebih dahulu.']);
            }

            $result = $licenseService->validateRemote($licenseKey);

            if ($result === null) {
                return redirect()->route('license.activate.form')
                    ->withErrors(['msg' => 'Gagal menghubungi server lisensi. Periksa koneksi internet Anda.']);
            }

            Log::info('[License] validateLicense response', ['license' => $licenseKey, 'response' => $result]);

            $isValid = $licenseService->isRemoteValid($result, $licenseKey, 'validate');

            if ($isValid) {
                $licenseService->saveLocalLicense([
                    'license_key' => $licenseKey,
                    'status' => 'active',
                    'hardware_id' => $licenseService->getHardwareId(),
                    'last_check_at' => now()->toIso8601String(),
                    'message' => 'Validated via login',
                ]);

                Log::info('[License] login successful', ['license' => $licenseKey]);
                return redirect()->route('dashboard');
            }

            Log::warning('[License] login failed - invalid license', ['license' => $licenseKey]);
            return redirect()->route('license.activate.form')
                ->withErrors(['msg' => 'License key tidak valid. Silakan aktivasi ulang.']);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('[License] processLogin error: ' . $e->getMessage());
            return redirect()->route('license.activate.form')
                ->withErrors(['msg' => 'Terjadi kesalahan saat login. Silakan coba lagi.']);
        }
    }

    public function processAuthLogin(Request $request, LicenseService $licenseService)
    {
        Log::info('[License] processAuthLogin hit', ['email' => $request->input('email')]);

        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $email = trim($request->email);
            $password = $request->password;

            $local = $licenseService->loadLocalLicense();
            $licenseKey = $local['license_key'] ?? null;

            if (!$licenseKey) {
                return redirect()->route('license.activate.form')
                    ->withErrors(['msg' => 'Lisensi belum terdaftar. Silakan aktivasi terlebih dahulu.']);
            }

            $sejoli = app(SejoliService::class);
            $result = $sejoli->validateLicenseWithAuth($email, $password, $licenseKey);

            if ($result === null) {
                return redirect()->route('login')
                    ->withErrors(['msg' => 'Gagal menghubungi server. Periksa koneksi internet Anda.'])
                    ->withInput();
            }

            Log::info('[License] authLogin validateLicense response', [
                'license' => $licenseKey,
                'email' => $email,
                'response' => $result,
            ]);

            if ($result && isset($result['valid']) && $result['valid'] === false) {
                $message = 'Email atau password yang dimasukan salah';
                return redirect()->route('login')
                    ->withErrors(['msg' => $message])
                    ->withInput();
            }

            $isValid = $licenseService->isRemoteValid($result, $licenseKey, 'validate');

            if ($isValid) {
                $remember = $request->boolean('remember');
                $licenseService->saveLocalLicense([
                    'license_key' => $licenseKey,
                    'status' => 'active',
                    'hardware_id' => $licenseService->getHardwareId(),
                    'email' => $email,
                    'last_check_at' => now()->toIso8601String(),
                    'message' => 'Validated via auth login',
                    'remember_session' => $remember,
                ]);

                session(['license_authenticated' => true]);
                session(['license_user_email' => $email]);

                if ($remember) {
                    session(['remember_session' => true]);
                    session(['persist_license' => true]);
                }

                return redirect()->route('dashboard');
            }

            return redirect()->route('login')
                ->withErrors(['msg' => $result['message'] ?? 'Login gagal. Kredensial tidak valid atau lisensi tidak ditemukan.'])
                ->withInput();

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('[License] processAuthLogin error: ' . $e->getMessage());
            return redirect()->route('login')
                ->withErrors(['msg' => 'Terjadi kesalahan saat login. Silakan coba lagi.'])
                ->withInput();
        }
    }

    public function processAuthReset(Request $request, LicenseService $licenseService)
    {
        Log::info('[License] processAuthReset hit', ['email' => $request->input('email')]);

        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $email = trim($request->email);
            $password = $request->password;

            $local = $licenseService->loadLocalLicense();
            $licenseKey = $local['license_key'] ?? null;

            if (!$licenseKey) {
                return redirect()->route('license.activate.form')
                    ->withErrors(['msg' => 'Tidak ada lisensi yang tersimpan untuk direset.']);
            }

            $sejoli = app(SejoliService::class);
            $result = $sejoli->resetLicense($email, $password, $licenseKey);

            if ($result === null) {
                return redirect()->route('license.activate.form')
                    ->withErrors(['msg' => 'Gagal menghubungi server. Periksa koneksi internet Anda.']);
            }

            Log::info('[License] authReset response', ['license' => $licenseKey, 'response' => $result]);

            if ($licenseService->messageContains($result, ['tidak ditemukan', "doesn't exist"])) {
                return redirect()->route('license.activate.form')
                    ->withErrors(['msg' => 'Lisensi tidak ditemukan.']);
            }

            $success = $licenseService->isRemoteValid($result, $licenseKey, 'reset');

            if ($success) {
                $licenseService->revokeLocalLicense();
                return redirect()->route('license.activate.form')
                    ->with('success', 'Lisensi berhasil direset. Silakan aktivasi ulang.');
            } else {
                $licenseService->revokeLocalLicense();
                return redirect()->route('license.activate.form')
                    ->withErrors(['msg' => 'Reset gagal, lisensi lokal dihapus. Silakan aktivasi ulang.']);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('[License] processAuthReset error: ' . $e->getMessage());
            return redirect()->route('license.activate.form')
                ->withErrors(['msg' => 'Terjadi kesalahan saat reset. Silakan coba lagi.']);
        }
    }

    public function logout()
    {
        try {
            app(LicenseService::class)->revokeLocalLicense();
            session()->invalidate();
            session()->regenerateToken();
        } catch (\Throwable $e) {
            Log::error('[License] logout error: ' . $e->getMessage());
        }

        return redirect()->route('license.activate.form');
    }

    public function reset(Request $request, LicenseService $licenseService)
    {
        Log::info('[License] reset called');

        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
                'license' => 'required|string',
            ]);

            $email = trim($request->email);
            $password = $request->password;
            $licenseKey = trim($request->license);

            $resp = $licenseService->resetRemote($email, $password, $licenseKey);

            if ($resp === null) {
                return redirect()->route('license.activate.form')
                    ->withErrors(['msg' => 'Gagal menghubungi server. Periksa koneksi internet Anda.']);
            }

            Log::info('[License] reset response', ['license' => $licenseKey, 'response' => $resp]);

            if ($licenseService->messageContains($resp, ['tidak ditemukan', "doesn't exist"])) {
                return redirect()->route('license.activate.form')
                    ->withErrors(['msg' => 'Lisensi tidak terdaftar.']);
            }

            $success = $licenseService->isRemoteValid($resp, $licenseKey, 'reset');

            if ($success) {
                $licenseService->revokeLocalLicense();
                Log::info('[License] reset successful');
                return redirect()->route('license.activate.form')
                    ->with('success', 'Lisensi sudah direset. Silakan aktivasi ulang di perangkat baru.');
            }

            Log::warning('[License] reset failed', ['response' => $resp]);
            return redirect()->route('license.activate.form')
                ->withErrors(['msg' => 'Reset lisensi gagal: ' . ($resp['message'] ?? 'Unknown error')]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('[License] reset error: ' . $e->getMessage());
            return redirect()->route('license.activate.form')
                ->withErrors(['msg' => 'Terjadi kesalahan saat reset. Silakan coba lagi.']);
        }
    }

    public function revalidate(LicenseService $licenseService)
    {
        try {
            $info = $licenseService->loadLocalLicense();

            if (!$info || empty($info['license_key'])) {
                return redirect()->route('license.activate.form')
                    ->withErrors(['msg' => 'Lisensi belum terdaftar atau hilang.']);
            }

            $licenseKey = $info['license_key'];
            $hardwareId = $info['hardware_id'] ?? $info['string'] ?? null;

            if (!$hardwareId) {
                $hardwareId = $licenseService->getHardwareId();
            }

            $resp = $licenseService->validateRemote($licenseKey, $hardwareId);

            if ($resp === null) {
                return redirect()->route('license.activate.form')
                    ->withErrors(['msg' => 'Gagal menghubungi server. Periksa koneksi internet Anda.']);
            }

            $valid = $licenseService->isRemoteValid($resp, $licenseKey, 'validate');

            if ($valid) {
                $licenseService->saveLocalLicense(array_merge($info, [
                    'license_key' => $licenseKey,
                    'status' => 'active',
                    'hardware_id' => $hardwareId,
                    'string' => $hardwareId,
                    'email' => $info['email'] ?? null,
                    'last_check_at' => now()->toIso8601String(),
                    'message' => 'Revalidate successful',
                ]));

                return redirect()->route('dashboard')
                    ->with('success', 'Lisensi tervalidasi ulang.');
            }

            $licenseService->revokeLocalLicense();
            return redirect()->route('license.activate.form')
                ->withErrors(['msg' => 'Validasi gagal. Silakan aktivasi ulang.']);

        } catch (\Throwable $e) {
            Log::error('[License] revalidate error: ' . $e->getMessage());
            return redirect()->route('license.activate.form')
                ->withErrors(['msg' => 'Terjadi kesalahan saat validasi. Silakan coba lagi.']);
        }
    }

    public function blocked()
    {
        return redirect()->route('license.activate.form')
            ->withErrors(['msg' => 'Lisensi diblokir atau tidak valid. Silakan aktivasi ulang.']);
    }

    public function locked()
    {
        return redirect()->route('license.activate.form')
            ->withErrors(['msg' => 'Aplikasi terkunci. Silakan aktivasi ulang untuk melanjutkan.']);
    }

    public function forceRevoke(LicenseService $licenseService)
    {
        try {
            Log::info('[License] forceRevoke called - deleting license.json');
            $deleted = $licenseService->revokeLocalLicense();
            session()->invalidate();
            session()->regenerateToken();

            return response()->json([
                'success' => $deleted,
                'message' => $deleted ? 'License berhasil dihapus dari perangkat.' : 'Gagal menghapus license.',
                'redirect' => route('license.activate.form'),
            ]);
        } catch (\Throwable $e) {
            Log::error('[License] forceRevoke error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus lisensi.',
                'redirect' => route('license.activate.form'),
            ]);
        }
    }
}