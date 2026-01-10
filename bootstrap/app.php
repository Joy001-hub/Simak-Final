<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require_once __DIR__ . '/mbstring_polyfill.php';

// Set NATIVEPHP_STORAGE_PATH only in production builds (no .env file)
$isProductionBuild = !file_exists(__DIR__ . '/../.env');

if ($isProductionBuild && getenv('APPDATA')) {
    $nativeStorage = rtrim(getenv('APPDATA'), '\\/') . DIRECTORY_SEPARATOR . 'Simak' . DIRECTORY_SEPARATOR . 'storage';

    if (!is_dir($nativeStorage)) {
        @mkdir($nativeStorage, 0755, true);
        @mkdir($nativeStorage . DIRECTORY_SEPARATOR . 'app', 0755, true);
        @mkdir($nativeStorage . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'license', 0755, true);
        @mkdir($nativeStorage . DIRECTORY_SEPARATOR . 'framework', 0755, true);
        @mkdir($nativeStorage . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache', 0755, true);
        @mkdir($nativeStorage . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'sessions', 0755, true);
        @mkdir($nativeStorage . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'views', 0755, true);
        @mkdir($nativeStorage . DIRECTORY_SEPARATOR . 'logs', 0755, true);
    }

    $dbPath = $nativeStorage . DIRECTORY_SEPARATOR . 'database.sqlite';
    if (!file_exists($dbPath)) {
        @touch($dbPath);
    }

    putenv("NATIVEPHP_STORAGE_PATH={$nativeStorage}");
    $_ENV['NATIVEPHP_STORAGE_PATH'] = $nativeStorage;
    $_SERVER['NATIVEPHP_STORAGE_PATH'] = $nativeStorage;

    // Only set NATIVEPHP_RUNNING in production - REMOVED from development!
    // NativePHP Electron will set this automatically when running the packaged app
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(\App\Console\Commands\ValidateLicenseCommand::class)->everyFiveMinutes();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*', headers: SymfonyRequest::HEADER_X_FORWARDED_ALL);
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\ResolveAppMode::class,
            \App\Http\Middleware\EnsureReadOnlyMode::class,
            \App\Http\Middleware\EnsureDatabaseMigrated::class,
            \App\Http\Middleware\SanitizeInput::class,
            \App\Http\Middleware\ContentSecurityPolicy::class,
            \App\Http\Middleware\EnsureNativeCookie::class,
            \App\Http\Middleware\EnsureLicenseIsValid::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle all exceptions gracefully - never show 500 error page
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            // Log the error for debugging
            \Illuminate\Support\Facades\Log::error('[Exception] ' . get_class($e) . ': ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'trace' => $e->getTraceAsString(),
            ]);

            // For AJAX/API requests, return JSON error
            if ($request->expectsJson() || $request->is('api/*')) {
                $statusCode = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException
                    ? $e->getStatusCode()
                    : 500;

                return response()->json([
                    'success' => false,
                    'message' => $e instanceof \Illuminate\Validation\ValidationException
                        ? $e->getMessage()
                        : 'Terjadi kesalahan sistem. Silakan coba lagi.',
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ], $statusCode);
            }

            // Handle specific exception types
            if ($e instanceof \Illuminate\Session\TokenMismatchException) {
                return redirect()->back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors(['msg' => 'Sesi Anda telah berakhir. Silakan coba lagi.']);
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return redirect()->back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors($e->errors());
            }

            if ($e instanceof \Illuminate\Database\QueryException) {
                return redirect()->back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors(['msg' => 'Terjadi kesalahan database. Silakan coba lagi.']);
            }

            if (
                $e instanceof \Illuminate\Http\Client\ConnectionException ||
                $e instanceof \Illuminate\Http\Client\RequestException
            ) {
                return redirect()->back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors(['msg' => 'Gagal terhubung ke server. Periksa koneksi internet Anda.']);
            }

            // Check if we can use the error view
            try {
                return response()->view('errors.general', [
                    'message' => 'Terjadi kesalahan sistem.',
                    'description' => config('app.debug') ? $e->getMessage() : 'Silakan coba lagi atau hubungi administrator.',
                    'code' => $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException ? $e->getStatusCode() : 500,
                ], 500);
            } catch (\Throwable $viewError) {
                // Fallback: redirect to a safe page with error message
                $redirectRoute = 'license.activate.form';
                try {
                    if (\Illuminate\Support\Facades\Route::has($redirectRoute)) {
                        return redirect()->route($redirectRoute)
                            ->withErrors(['msg' => 'Terjadi kesalahan sistem. Silakan coba lagi.']);
                    }
                } catch (\Throwable $redirectError) {
                    // Ultimate fallback
                }

                // If all else fails, return simple HTML response
                return response(
                    '<!DOCTYPE html>
                    <html><head><title>Error</title><meta charset="UTF-8">
                    <style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f1f5f9;}
                    .box{text-align:center;padding:40px;background:white;border-radius:16px;box-shadow:0 4px 6px rgba(0,0,0,0.1);}
                    h1{color:#ef4444;margin-bottom:10px;}
                    p{color:#64748b;}
                    a{color:#b91c3b;text-decoration:none;font-weight:600;}
                    </style></head>
                    <body><div class="box"><h1>Oops!</h1><p>Terjadi kesalahan sistem.<br>Silakan <a href="javascript:location.reload()">muat ulang halaman</a> atau coba lagi nanti.</p></div></body></html>',
                    500
                );
            }
        });
    })->create();
