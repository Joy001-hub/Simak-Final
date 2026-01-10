<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureReadOnlyMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(TenantContext::class);

        if ($context->isReadOnly() && $this->isWriteMethod($request) && !$this->isExempt($request)) {
            $message = 'Mode offline aktif. Aplikasi read-only sampai koneksi kembali normal.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 423);
            }

            return redirect()->back()->withErrors(['msg' => $message]);
        }

        return $next($request);
    }

    private function isWriteMethod(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private function isExempt(Request $request): bool
    {
        return $request->is(
            'login',
            'reset',
            'activate',
            'license/*',
            'upgrade',
            'upgrade/*'
        );
    }
}
