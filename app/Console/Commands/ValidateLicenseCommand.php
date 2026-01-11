<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Native\Laravel\Facades\Notification;

class ValidateLicenseCommand extends Command
{
    protected $signature = 'license:weekly-validate';
    protected $description = 'Validate license against Sejoli weekly and update local cache';

    public function handle(LicenseService $license): int
    {
        $local = $license->loadLocalLicense();
        if (!$local || empty($local['license_key']) || empty($local['hardware_id'])) {
            $this->warn('No valid local license found. Skipping weekly validation.');
            return Command::SUCCESS;
        }

        $lastCheck = $local['last_check_at'] ?? null;
        if ($lastCheck) {
            $lastCheckDate = \Carbon\Carbon::parse($lastCheck);
            $daysDiff = $lastCheckDate->diffInDays(now());
            if ($daysDiff < 7) {
                $this->info("License validated {$daysDiff} days ago. Skipping weekly check.");
                return Command::SUCCESS;
            }
        }

        $key = $local['license_key'];
        $hardwareId = $local['hardware_id'];

        try {
            $this->info('Validating license ' . $key);
            $response = $license->validateRemote($key, $hardwareId);

            if ($response === null) {
                throw new ConnectionException('No response (null) from Sejoli');
            }

            $isValid = $license->isRemoteValid($response, $key, 'validate');
            if ($isValid) {
                $subscriptionStatus = $license->extractSubscriptionStatus($response);
                $subscriptionExpiresAt = $license->extractSubscriptionExpirationDate($response);
                $license->saveLocalLicense(array_merge($local, [
                    'license_key' => $key,
                    'status' => 'active',
                    'hardware_id' => $hardwareId,
                    'subscription_status' => $subscriptionStatus,
                    'subscription_expires_at' => $subscriptionExpiresAt,
                    'subscription_checked_at' => now()->toIso8601String(),
                    'last_check_at' => now()->toIso8601String(),
                    'message' => 'Weekly validation successful',
                ]));
                Cache::forget('app_offline_lock');
                Log::info('[License] Weekly validation success');
                $this->info('License valid.');
                return Command::SUCCESS;
            }

            $license->blockLocalLicense('Lisensi dibekukan karena gagal validasi mingguan.');
            Cache::forever('app_offline_lock', true);
            $this->error('License invalid. Offline lock activated.');
            Notification::title('Security Alert')->message('Validation failed. App locked until online.')->show();
            Log::warning('[License] Weekly validation invalid', ['response' => $response]);
        } catch (ConnectionException $e) {
            $license->saveLocalLicense(array_merge($local, [
                'last_check_error' => $e->getMessage(),
                'last_check_failed_at' => now()->toIso8601String(),
            ]));
            $this->error('Offline mode - last check failed');
            Log::warning('[License] Weekly validation failed: offline', ['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            $license->blockLocalLicense('Validasi gagal karena error: ' . $e->getMessage());
            $this->error('Offline/Error Lock Activated');
            Notification::title('Security Alert')->message('Validation failed. App locked until online.')->show();
            Log::error('[License] Weekly validation exception', ['error' => $e->getMessage()]);
        }

        return Command::SUCCESS;
    }
}
