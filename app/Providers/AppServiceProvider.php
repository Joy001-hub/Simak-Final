<?php

namespace App\Providers;

use App\Console\Commands\NativeConfigFallback;
use App\Console\Commands\NativePhpIniFallback;
use App\Models\CompanyProfile;
use App\Models\Payment;
use App\Services\LicenseService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, fn() => new TenantContext());
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            Vite::useHotFile(storage_path('app/vite.hot'));
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                NativeConfigFallback::class,
                NativePhpIniFallback::class,
            ]);
        }

        try {
            // Target specific layouts to avoid performance issues with wildcard '*'
            // Wildcard causes the query to run for EVERY partial view included
            View::composer(['layouts.app'], function ($view) {
                $companyProfile = Schema::hasTable('company_profiles') ? CompanyProfile::first() : null;
                $logoConfigPath = config('company.logo_url', '/logo-app.png');
                $logoPath = asset($logoConfigPath);
                $version = null;

                if ($companyProfile?->logo_path && Storage::disk('public')->exists($companyProfile->logo_path)) {
                    $logoPath = Storage::url($companyProfile->logo_path);
                    $version = Storage::disk('public')->lastModified($companyProfile->logo_path);
                } else {
                    $defaultLogoFile = public_path(ltrim($logoConfigPath, '/'));
                    if (is_file($defaultLogoFile)) {
                        $version = filemtime($defaultLogoFile);
                    }
                }

                if ($version) {
                    $logoPath .= (str_contains($logoPath, '?') ? '&' : '?') . 'v=' . $version;
                }

                $overdueCount = 0;
                $overduePayments = collect();
                if (Schema::hasTable('payments') && Schema::hasTable('sales')) {
                    $today = Carbon::today();
                    $overduePayments = Payment::with(['sale.buyer', 'sale.lot.project'])
                        ->where('status', 'unpaid')
                        ->whereDate('due_date', '<', $today)
                        ->whereHas('sale.buyer')
                        ->get()
                        ->groupBy(fn($payment) => $payment->sale?->buyer_id)
                        ->map(function ($payments) {
                            $totalAmount = $payments->sum('amount');
                            $overdueCount = $payments->count();
                            $oldestPayment = $payments->sortBy('due_date')->first();
                            $kavlingList = $payments->groupBy(fn($p) => $p->sale_id)
                                ->map(function ($salePayments) {
                                    $sale = $salePayments->first()->sale;
                                    $lot = $sale?->lot;
                                    $projectName = $lot?->project?->name ?? '';
                                    $blockNumber = $lot?->block_number ?? '';
                                    return [
                                        'sale_id' => $sale->id,
                                        'kavling' => trim($projectName . ' / ' . $blockNumber, ' /'),
                                        'amount' => $salePayments->sum('amount'),
                                        'count' => $salePayments->count(),
                                    ];
                                })->values()->toArray();

                            $oldestPayment->total_overdue_amount = $totalAmount;
                            $oldestPayment->overdue_payment_count = $overdueCount;
                            $oldestPayment->kavling_list = $kavlingList;
                            return $oldestPayment;
                        })
                        ->sortBy('due_date')
                        ->values()
                        ->take(50);
                    $overdueCount = $overduePayments->count();
                }

                $licenseLastCheckAt = null;
                $licenseLastCheckDays = null;
                $licenseGraceDays = (int) config('license.grace_days', 7);

                try {
                    $licenseData = app(LicenseService::class)->loadLocalLicense();
                    if (is_array($licenseData)) {
                        $licenseLastCheckAt = $licenseData['last_check_at'] ?? null;
                        if ($licenseLastCheckAt) {
                            try {
                                $licenseLastCheckDays = Carbon::parse($licenseLastCheckAt)->diffInDays(now());
                            } catch (\Throwable $e) {
                                $licenseLastCheckDays = null;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $licenseLastCheckAt = null;
                    $licenseLastCheckDays = null;
                }

                $context = app(TenantContext::class);

                $view
                    ->with('companyProfile', $companyProfile)
                    ->with('companyLogo', $logoPath)
                    ->with('overdueCount', $overdueCount)
                    ->with('overduePayments', $overduePayments)
                    ->with('appMode', $context->getMode())
                    ->with('appReadOnly', $context->isReadOnly())
                    ->with('appReadOnlyReason', $context->getReadOnlyReason())
                    ->with('licenseLastCheckAt', $licenseLastCheckAt)
                    ->with('licenseLastCheckDays', $licenseLastCheckDays)
                    ->with('licenseGraceDays', $licenseGraceDays);
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('View Composer Error: ' . $e->getMessage());
        }
    }
}
