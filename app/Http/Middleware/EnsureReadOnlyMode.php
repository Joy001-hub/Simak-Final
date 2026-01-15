<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Carbon;

class EnsureReadOnlyMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(TenantContext::class);
        $this->applySubscriptionReadOnly($context);

        if ($context->isReadOnly() && $this->isWriteMethod($request) && !$this->isExempt($request)) {
            $message = $context->getReadOnlyReason() ?: 'Subscription expired. Akses hanya baca.';

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
            'register',
            'register/*',
            'reset',
            'activate',
            'license/*',
            'upgrade',
            'upgrade/*'
        );
    }

    private function applySubscriptionReadOnly(TenantContext $context): void
    {
        if (!auth()->check()) {
            return;
        }

        $user = auth()->user();
        $status = $user->subscription_status ?? null;
        $endDate = $user->subscription_end_date ?? null;

        $expired = false;
        if ($status && strtolower((string) $status) === 'expired') {
            $expired = true;
        }

        if (!$expired && $endDate) {
            try {
                $expired = Carbon::parse($endDate)->isPast();
            } catch (\Throwable $e) {
                $expired = false;
            }
        }

        if ($expired) {
            $context->setReadOnly(true, 'Subscription expired. Akses hanya baca.');
        }
    }
}
