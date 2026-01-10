<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CloudMigrationService
{
    public function __construct(
        private readonly AppModeService $modeService
    ) {
    }

    public function migrateLocalToCloud(string $licenseKey, bool $merge): void
    {
        $tenantKey = $this->modeService->tenantKey($licenseKey);
        $source = DB::connection('sqlite');
        $dest = DB::connection('pgsql');

        $dest->beginTransaction();

        try {
            if (!$merge) {
                $dest->table('payments')->where('tenant_key', $tenantKey)->delete();
                $dest->table('sales')->where('tenant_key', $tenantKey)->delete();
                $dest->table('lots')->where('tenant_key', $tenantKey)->delete();
                $dest->table('projects')->where('tenant_key', $tenantKey)->delete();
                $dest->table('buyers')->where('tenant_key', $tenantKey)->delete();
                $dest->table('marketers')->where('tenant_key', $tenantKey)->delete();
                $dest->table('company_profiles')->where('tenant_key', $tenantKey)->delete();
            }

            $projectMap = [];
            foreach ($source->table('projects')->get() as $project) {
                $newId = $dest->table('projects')->insertGetId([
                    'tenant_key' => $tenantKey,
                    'name' => $project->name ?? 'Project',
                    'location' => $project->location ?? null,
                    'notes' => $project->notes ?? null,
                    'total_units' => $project->total_units ?? 0,
                    'sold_units' => $project->sold_units ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $projectMap[$project->id] = $newId;
            }

            $lotMap = [];
            foreach ($source->table('lots')->get() as $lot) {
                $newId = $dest->table('lots')->insertGetId([
                    'tenant_key' => $tenantKey,
                    'project_id' => $projectMap[$lot->project_id] ?? null,
                    'block_number' => $lot->block_number ?? 'BLK',
                    'area' => $lot->area ?? 0,
                    'base_price' => $lot->base_price ?? 0,
                    'status' => $lot->status ?? 'available',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $lotMap[$lot->id] = $newId;
            }

            $buyerMap = [];
            foreach ($source->table('buyers')->get() as $buyer) {
                $newId = $dest->table('buyers')->insertGetId([
                    'tenant_key' => $tenantKey,
                    'name' => $buyer->name ?? 'Buyer',
                    'phone' => $buyer->phone ?? null,
                    'email' => $buyer->email ?? null,
                    'address' => $buyer->address ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $buyerMap[$buyer->id] = $newId;
            }

            $marketerMap = [];
            foreach ($source->table('marketers')->get() as $marketer) {
                $newId = $dest->table('marketers')->insertGetId([
                    'tenant_key' => $tenantKey,
                    'name' => $marketer->name ?? 'Marketer',
                    'phone' => $marketer->phone ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $marketerMap[$marketer->id] = $newId;
            }

            $saleMap = [];
            foreach ($source->table('sales')->get() as $sale) {
                $newId = $dest->table('sales')->insertGetId([
                    'tenant_key' => $tenantKey,
                    'lot_id' => $lotMap[$sale->lot_id] ?? null,
                    'buyer_id' => $buyerMap[$sale->buyer_id] ?? null,
                    'marketer_id' => $marketerMap[$sale->marketer_id] ?? null,
                    'booking_date' => $sale->booking_date ?? null,
                    'payment_method' => $sale->payment_method ?? null,
                    'price' => $sale->price ?? 0,
                    'down_payment' => $sale->down_payment ?? 0,
                    'tenor_months' => $sale->tenor_months ?? 0,
                    'due_day' => $sale->due_day ?? null,
                    'paid_amount' => $sale->paid_amount ?? 0,
                    'outstanding_amount' => $sale->outstanding_amount ?? 0,
                    'status' => $sale->status ?? 'active',
                    'refund_amount' => $sale->refund_amount ?? null,
                    'status_before_cancel' => $sale->status_before_cancel ?? null,
                    'parent_sale_id' => $sale->parent_sale_id ?? null,
                    'notes' => $sale->notes ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $saleMap[$sale->id] = $newId;
            }

            foreach ($source->table('payments')->get() as $payment) {
                $saleId = $saleMap[$payment->sale_id] ?? null;
                if (!$saleId) {
                    continue;
                }

                $dest->table('payments')->insert([
                    'tenant_key' => $tenantKey,
                    'sale_id' => $saleId,
                    'due_date' => $payment->due_date ?? null,
                    'amount' => $payment->amount ?? 0,
                    'status' => $payment->status ?? 'unpaid',
                    'note' => $payment->note ?? null,
                    'paid_at' => $payment->paid_at ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $companyProfile = $source->table('company_profiles')->first();
            if ($companyProfile) {
                $dest->table('company_profiles')->insert([
                    'tenant_key' => $tenantKey,
                    'name' => $companyProfile->name ?? 'Perusahaan Properti',
                    'npwp' => $companyProfile->npwp ?? null,
                    'email' => $companyProfile->email ?? null,
                    'phone' => $companyProfile->phone ?? null,
                    'address' => $companyProfile->address ?? null,
                    'signer_name' => $companyProfile->signer_name ?? null,
                    'footer_note' => $companyProfile->footer_note ?? null,
                    'invoice_format' => $companyProfile->invoice_format ?? null,
                    'receipt_format' => $companyProfile->receipt_format ?? null,
                    'logo_path' => $companyProfile->logo_path ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $dest->commit();
        } catch (\Throwable $e) {
            $dest->rollBack();
            throw $e;
        }
    }
}
