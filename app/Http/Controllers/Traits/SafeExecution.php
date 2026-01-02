<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\Log;

trait SafeExecution
{

    protected function safeExecute(callable $callback, string $fallbackRoute = null, string $errorMessage = null)
    {
        try {
            return $callback();
        } catch (\Illuminate\Validation\ValidationException $e) {

            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[SafeExec] Model not found: ' . $e->getMessage());
            $message = $errorMessage ?? 'Data tidak ditemukan.';
            return $this->handleError($message, $fallbackRoute);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('[SafeExec] Database error: ' . $e->getMessage(), [
                'sql' => $e->getSql() ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            $message = $errorMessage ?? 'Terjadi kesalahan database. Silakan coba lagi.';
            return $this->handleError($message, $fallbackRoute);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('[SafeExec] Connection error: ' . $e->getMessage());
            $message = $errorMessage ?? 'Gagal terhubung ke server. Periksa koneksi internet Anda.';
            return $this->handleError($message, $fallbackRoute);
        } catch (\Throwable $e) {
            Log::error('[SafeExec] Error in ' . static::class . ': ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            $message = $errorMessage ?? 'Terjadi kesalahan sistem. Silakan coba lagi.';
            return $this->handleError($message, $fallbackRoute);
        }
    }

    protected function handleError(string $message, string $fallbackRoute = null)
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 500);
        }

        if ($fallbackRoute && \Illuminate\Support\Facades\Route::has($fallbackRoute)) {
            return redirect()->route($fallbackRoute)->withErrors(['msg' => $message]);
        }

        return redirect()->back()->withErrors(['msg' => $message])->withInput();
    }

    protected function safeJson(callable $callback, string $errorMessage = null)
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            Log::error('[SafeJson] Error: ' . $e->getMessage(), [
                'class' => static::class,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $errorMessage ?? 'Terjadi kesalahan sistem.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
