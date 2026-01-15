<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\StarSenderService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegistrationController extends Controller
{
    private const OTP_EXPIRY_MINUTES = 5;
    private const OTP_RESEND_LIMIT = 3;
    private const OTP_RESEND_WINDOW_MINUTES = 10;
    private const OTP_MAX_ATTEMPTS = 5;

    public function showRegister()
    {
        return view('auth.register');
    }

    public function checkIdentifier(Request $request, StarSenderService $starSender)
    {
        $request->validate([
            'identifier_type' => 'required|in:email,whatsapp',
            'identifier' => 'required|string|max:255',
        ]);

        $identifierType = $request->input('identifier_type');
        $identifier = trim((string) $request->input('identifier'));

        if ($identifierType === 'email') {
            $identifier = strtolower($identifier);
            if (!filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                return back()->withErrors(['msg' => 'Format email tidak valid.'])->withInput();
            }
        } else {
            $identifier = $this->normalizePhone($identifier);
            if ($identifier === '') {
                return back()->withErrors(['msg' => 'Nomor WhatsApp tidak valid.'])->withInput();
            }
        }

        $user = $this->findUserByIdentifier($identifierType, $identifier);
        if (!$user) {
            return back()->withErrors(['msg' => 'Email atau WhatsApp tidak terdaftar dalam sistem.'])->withInput();
        }

        if (!$this->isSubscriptionActive($user)) {
            return back()->withErrors(['msg' => 'Subscription Anda belum aktif atau sudah expired.'])->withInput();
        }

        if ($user->password_created_at) {
            return back()->withErrors(['msg' => 'Anda sudah terdaftar. Silakan login.'])->withInput();
        }

        if (empty($user->phone)) {
            return back()->withErrors(['msg' => 'Nomor WhatsApp tidak ditemukan. Hubungi admin.'])->withInput();
        }

        $recentCount = DB::table('otp_verifications')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subMinutes(self::OTP_RESEND_WINDOW_MINUTES))
            ->count();

        if ($recentCount >= self::OTP_RESEND_LIMIT) {
            return back()->withErrors(['msg' => 'Terlalu banyak permintaan. Silakan coba lagi nanti.'])->withInput();
        }

        $otpCode = (string) random_int(100000, 999999);
        $otpId = DB::table('otp_verifications')->insertGetId([
            'user_id' => $user->id,
            'identifier' => $identifier,
            'identifier_type' => $identifierType,
            'otp_code' => Hash::make($otpCode),
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $message = $this->buildOtpMessage($user->name ?: 'User', $otpCode);
        $sendOk = $starSender->sendOtp($this->normalizePhone($user->phone), $message);

        if (!$sendOk) {
            DB::table('otp_verifications')->where('id', $otpId)->delete();
            return back()->withErrors(['msg' => 'Gagal mengirim kode verifikasi. Coba lagi.'])->withInput();
        }

        session([
            'registration_user_id' => $user->id,
            'registration_identifier' => $identifier,
            'registration_identifier_type' => $identifierType,
            'registration_phone' => $this->normalizePhone($user->phone),
            'registration_phone_masked' => $this->maskPhone($user->phone),
            'registration_otp_id' => $otpId,
        ]);

        return redirect()->route('register.verify')
            ->with('success', 'Kode verifikasi telah dikirim ke WhatsApp Anda.');
    }

    public function showVerify()
    {
        if (!session('registration_user_id')) {
            return redirect()->route('register');
        }

        return view('auth.verify-otp', [
            'phone_masked' => session('registration_phone_masked'),
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $userId = session('registration_user_id');
        $identifier = session('registration_identifier');
        $identifierType = session('registration_identifier_type');

        if (!$userId || !$identifier || !$identifierType) {
            return redirect()->route('register');
        }

        $record = DB::table('otp_verifications')
            ->where('user_id', $userId)
            ->where('identifier', $identifier)
            ->where('identifier_type', $identifierType)
            ->whereNull('verified_at')
            ->orderByDesc('id')
            ->first();

        if (!$record) {
            return back()->withErrors(['msg' => 'Kode OTP tidak ditemukan. Silakan minta kode baru.']);
        }

        if (Carbon::parse($record->expires_at)->isPast()) {
            return back()->withErrors(['msg' => 'Kode OTP sudah kedaluwarsa. Silakan minta kode baru.']);
        }

        if ($record->attempts >= self::OTP_MAX_ATTEMPTS) {
            return back()->withErrors(['msg' => 'Terlalu banyak percobaan. Silakan minta kode baru.']);
        }

        $otpInput = (string) $request->input('otp');
        if (!Hash::check($otpInput, $record->otp_code)) {
            DB::table('otp_verifications')->where('id', $record->id)->update([
                'attempts' => $record->attempts + 1,
                'updated_at' => now(),
            ]);
            return back()->withErrors(['msg' => 'Kode OTP tidak valid.']);
        }

        DB::table('otp_verifications')->where('id', $record->id)->update([
            'verified_at' => now(),
            'updated_at' => now(),
        ]);

        session([
            'registration_verified' => true,
            'registration_verified_at' => now()->toDateTimeString(),
        ]);

        return redirect()->route('register.password');
    }

    public function resendOtp(StarSenderService $starSender)
    {
        $userId = session('registration_user_id');
        $identifier = session('registration_identifier');
        $identifierType = session('registration_identifier_type');

        if (!$userId || !$identifier || !$identifierType) {
            return redirect()->route('register');
        }

        $recentCount = DB::table('otp_verifications')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinutes(self::OTP_RESEND_WINDOW_MINUTES))
            ->count();

        if ($recentCount >= self::OTP_RESEND_LIMIT) {
            return back()->withErrors(['msg' => 'Terlalu banyak permintaan. Silakan coba lagi nanti.']);
        }

        $user = User::query()->find($userId);
        if (!$user || empty($user->phone)) {
            return back()->withErrors(['msg' => 'Nomor WhatsApp tidak ditemukan. Hubungi admin.']);
        }

        $otpCode = (string) random_int(100000, 999999);
        $otpId = DB::table('otp_verifications')->insertGetId([
            'user_id' => $userId,
            'identifier' => $identifier,
            'identifier_type' => $identifierType,
            'otp_code' => Hash::make($otpCode),
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $message = $this->buildOtpMessage($user->name ?: 'User', $otpCode);
        $sendOk = $starSender->sendOtp($this->normalizePhone($user->phone), $message);

        if (!$sendOk) {
            DB::table('otp_verifications')->where('id', $otpId)->delete();
            return back()->withErrors(['msg' => 'Gagal mengirim kode verifikasi. Coba lagi.']);
        }

        session(['registration_otp_id' => $otpId]);

        return back()->with('success', 'Kode verifikasi telah dikirim ulang.');
    }

    public function showPassword()
    {
        if (!session('registration_verified') || !session('registration_user_id')) {
            return redirect()->route('register');
        }

        return view('auth.create-password');
    }

    public function createPassword(Request $request)
    {
        if (!session('registration_verified') || !session('registration_user_id')) {
            return redirect()->route('register');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::query()->find(session('registration_user_id'));
        if (!$user) {
            return redirect()->route('register');
        }

        $user->password = Hash::make((string) $request->input('password'));
        $user->password_created_at = now();

        if (session('registration_identifier_type') === 'email') {
            $user->email_verified_at = now();
        }

        $user->save();

        $this->clearRegistrationSession();

        return redirect()->route('login')
            ->with('success', 'Password berhasil dibuat. Silakan login.');
    }

    private function findUserByIdentifier(string $type, string $identifier): ?User
    {
        if ($type === 'email') {
            return User::query()->where('email', $identifier)->first();
        }

        return User::query()->where('phone', $identifier)->first();
    }

    private function isSubscriptionActive(User $user): bool
    {
        if (strtolower((string) $user->subscription_status) !== 'active') {
            return false;
        }

        if ($user->subscription_end_date) {
            try {
                return !Carbon::parse($user->subscription_end_date)->isPast();
            } catch (\Throwable $e) {
                return true;
            }
        }

        return true;
    }

    private function normalizePhone(string $input): string
    {
        $digits = preg_replace('/\D+/', '', $input);
        if (!$digits) {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        return $digits;
    }

    private function maskPhone(string $phone): string
    {
        $normalized = $this->normalizePhone($phone);
        if (strlen($normalized) < 6) {
            return $normalized;
        }

        $start = substr($normalized, 0, 4);
        $end = substr($normalized, -3);
        return $start . '****' . $end;
    }

    private function buildOtpMessage(string $name, string $otpCode): string
    {
        return "SIMAK\n\nHai {$name},\nKode verifikasi Anda: {$otpCode}\n\nBerlaku 5 menit.\nAbaikan jika bukan Anda.";
    }

    private function clearRegistrationSession(): void
    {
        session()->forget([
            'registration_user_id',
            'registration_identifier',
            'registration_identifier_type',
            'registration_phone',
            'registration_phone_masked',
            'registration_otp_id',
            'registration_verified',
            'registration_verified_at',
        ]);
    }
}
