<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;

class EnsureLicenseIsActive
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login')
                ->withErrors(['msg' => 'Silakan login terlebih dahulu.']);
        }

        $user = auth()->user();
        $status = strtolower((string) ($user->subscription_status ?? ''));
        $endDate = $user->subscription_end_date ?? null;

        if ($status === 'active') {
            return $next($request);
        }

        if ($endDate) {
            try {
                if (Carbon::parse($endDate)->isPast()) {
                    return $next($request);
                }
            } catch (\Throwable $e) {
                // Ignore parse errors, continue to block below.
            }
        }

        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        Cookie::queue(Cookie::forget('simak_license'));

        return redirect()->route('login')
            ->withErrors(['msg' => 'Subscription belum aktif. Silakan tunggu aktivasi atau hubungi admin.']);
    }
}
