<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\StarSenderService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ForgotPasswordController extends Controller
{
    private const OTP_EXPIRY_MINUTES = 5;
    private const OTP_RESEND_LIMIT = 3;
    private const OTP_RESEND_WINDOW_MINUTES = 10;
    private const OTP_MAX_ATTEMPTS = 5;

    public function showRequest()
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request, StarSenderService $starSender)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim((string) $request->input('email')));
        $user = User::query()->where('email', $email)->first();

        if (!$user) {
            return back()->withErrors(['msg' => 'Email tidak terdaftar.'])->withInput();
        }

        if (!$user->password_created_at) {
            return back()->withErrors(['msg' => 'Akun belum aktif. Silakan daftar terlebih dahulu.'])->withInput();
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
        $phone = $this->normalizePhone($user->phone);
        if ($phone === '') {
            return back()->withErrors(['msg' => 'Nomor WhatsApp tidak valid. Hubungi admin.'])->withInput();
        }

        $otpId = DB::table('otp_verifications')->insertGetId([
            'user_id' => $user->id,
            'identifier' => $phone,
            'identifier_type' => 'whatsapp',
            'otp_code' => Hash::make($otpCode),
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $message = $this->buildOtpMessage($user->name ?: 'User', $otpCode);
        $sendOk = $starSender->sendOtp($phone, $message);

        if (!$sendOk) {
            DB::table('otp_verifications')->where('id', $otpId)->delete();
            return back()->withErrors(['msg' => 'Gagal mengirim kode verifikasi. Coba lagi.'])->withInput();
        }

        session([
            'forgot_user_id' => $user->id,
            'forgot_email' => $email,
            'forgot_phone' => $phone,
            'forgot_phone_masked' => $this->maskPhone($user->phone),
            'forgot_otp_id' => $otpId,
        ]);

        return redirect()->route('password.forgot.verify')
            ->with('success', 'Kode verifikasi telah dikirim ke WhatsApp Anda.');
    }

    public function showVerify()
    {
        if (!session('forgot_user_id')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.forgot-verify-otp', [
            'phone_masked' => session('forgot_phone_masked'),
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $userId = session('forgot_user_id');
        $phone = session('forgot_phone');

        if (!$userId || !$phone) {
            return redirect()->route('password.forgot');
        }

        $record = DB::table('otp_verifications')
            ->where('user_id', $userId)
            ->where('identifier', $phone)
            ->where('identifier_type', 'whatsapp')
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
            'forgot_verified' => true,
            'forgot_verified_at' => now()->toDateTimeString(),
        ]);

        return redirect()->route('password.forgot.reset');
    }

    public function resendOtp(StarSenderService $starSender)
    {
        $userId = session('forgot_user_id');
        $phone = session('forgot_phone');

        if (!$userId || !$phone) {
            return redirect()->route('password.forgot');
        }

        $recentCount = DB::table('otp_verifications')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinutes(self::OTP_RESEND_WINDOW_MINUTES))
            ->count();

        if ($recentCount >= self::OTP_RESEND_LIMIT) {
            return back()->withErrors(['msg' => 'Terlalu banyak permintaan. Silakan coba lagi nanti.']);
        }

        $user = User::query()->find($userId);
        if (!$user) {
            return back()->withErrors(['msg' => 'User tidak ditemukan.']);
        }

        $otpCode = (string) random_int(100000, 999999);
        $otpId = DB::table('otp_verifications')->insertGetId([
            'user_id' => $userId,
            'identifier' => $phone,
            'identifier_type' => 'whatsapp',
            'otp_code' => Hash::make($otpCode),
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $message = $this->buildOtpMessage($user->name ?: 'User', $otpCode);
        $sendOk = $starSender->sendOtp($phone, $message);

        if (!$sendOk) {
            DB::table('otp_verifications')->where('id', $otpId)->delete();
            return back()->withErrors(['msg' => 'Gagal mengirim kode verifikasi. Coba lagi.']);
        }

        session(['forgot_otp_id' => $otpId]);

        return back()->with('success', 'Kode verifikasi telah dikirim ulang.');
    }

    public function showReset()
    {
        if (!session('forgot_verified') || !session('forgot_user_id')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.forgot-reset-password');
    }

    public function resetPassword(Request $request)
    {
        if (!session('forgot_verified') || !session('forgot_user_id')) {
            return redirect()->route('password.forgot');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::query()->find(session('forgot_user_id'));
        if (!$user) {
            return redirect()->route('password.forgot');
        }

        $user->password = Hash::make((string) $request->input('password'));
        $user->password_created_at = now();
        $user->save();

        $this->clearForgotSession();

        return redirect()->route('login')
            ->with('success', 'Password berhasil direset. Silakan login.');
    }

    private function normalizePhone(string $input): string
    {
        $digits = preg_replace('/\\D+/', '', $input);
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

    private function clearForgotSession(): void
    {
        session()->forget([
            'forgot_user_id',
            'forgot_email',
            'forgot_phone',
            'forgot_phone_masked',
            'forgot_otp_id',
            'forgot_verified',
            'forgot_verified_at',
        ]);
    }
}
