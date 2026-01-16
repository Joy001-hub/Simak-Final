<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\SafeExecution;
use App\Models\Buyer;
use App\Models\CompanyProfile;
use App\Models\Lot;
use App\Models\Marketer;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Sale;
use App\Services\LicenseService;
use Database\Seeders\DataDummySeeders;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DataManagementController extends Controller
{
    use SafeExecution;

    public function index()
    {
        return $this->safeExecute(function () {
            $stats = [
                'projects' => Project::count(),
                'lots' => Lot::count(),
                'buyers' => Buyer::count(),
                'sales' => Sale::count(),
                'payments' => Payment::count(),
            ];
            $subscriptionStatus = null;
            $subscriptionExpiresLabel = null;

            try {
                $license = app(LicenseService::class)->loadLocalLicense();
                $subscriptionStatus = $license['subscription_status'] ?? null;
                $subscriptionExpiresAt = $license['subscription_expires_at'] ?? null;
                if ($subscriptionExpiresAt) {
                    try {
                        $subscriptionExpiresLabel = Carbon::parse($subscriptionExpiresAt)->format('d/m/Y');
                    } catch (\Throwable $e) {
                        $subscriptionExpiresLabel = $subscriptionExpiresAt;
                    }
                }
            } catch (\Throwable $e) {
                $subscriptionStatus = null;
                $subscriptionExpiresLabel = null;
            }

            return view('data-management.index', compact('stats', 'subscriptionStatus', 'subscriptionExpiresLabel'));
        }, 'dashboard');
    }

    public function loadDemo()
    {
        try {
            DB::transaction(function () {
                $this->resetData();
                (new DataDummySeeders())->run();
                $this->seedDefaultCompanyProfile();
            });
            $this->clearCaches();
            return redirect()->route('data.index')->with('success', 'Data demo berhasil dibuat.');
        } catch (\Throwable $e) {
            logger()->error('LoadDemo Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('data.index')->with('error', 'Gagal membuat data demo: ' . $e->getMessage());
        }
    }

    public function reset()
    {
        return $this->safeExecute(function () {
            DB::transaction(function () {
                $this->resetData();
            });
            $this->clearCaches();
            return redirect()->route('data.index')->with('success', 'Data aplikasi berhasil direset ke keadaan kosong.');
        }, 'data.index', 'Gagal mereset data. Silakan coba lagi.');
    }

    public function backup()
    {
        return $this->safeExecute(function () {
            $payload = [
                'projects' => Project::all(),
                'lots' => Lot::all(),
                'buyers' => Buyer::all(),
                'marketers' => Marketer::all(),
                'sales' => Sale::all(),
                'payments' => Payment::all(),
                'company_profile' => CompanyProfile::first(),
            ];
            $json = json_encode($payload, JSON_PRETTY_PRINT);
            $filename = 'backup-' . now()->format('Ymd-His') . '.json';
            return response($json)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', "attachment; filename=\"$filename\"");
        }, 'data.index', 'Gagal membuat backup. Silakan coba lagi.');
    }

    public function restore(Request $request)
    {
        return $this->safeExecute(function () use ($request) {
            $request->validate([
                'backup_file' => ['required', 'file', 'mimes:json', 'max:51200'],
            ]);

            $content = file_get_contents($request->file('backup_file')->getRealPath());
            $data = json_decode($content, true);

            if (!$data || !is_array($data)) {
                return back()->with('error', 'File backup tidak valid.');
            }

            DB::transaction(function () use ($data) {
                $this->resetData();

                $projectMap = [];
                foreach ($data['projects'] ?? [] as $p) {
                    $project = Project::create([
                        'name' => $p['name'] ?? 'Project',
                        'location' => $p['location'] ?? null,
                        'notes' => $p['notes'] ?? null,
                        'total_units' => $p['total_units'] ?? 0,
                        'sold_units' => $p['sold_units'] ?? 0,
                    ]);
                    $projectMap[$p['id'] ?? null] = $project->id;
                }

                $lotMap = [];
                foreach ($data['lots'] ?? [] as $l) {
                    $lot = Lot::create([
                        'project_id' => $projectMap[$l['project_id'] ?? null] ?? null,
                        'block_number' => $l['block_number'] ?? 'BLK',
                        'area' => $l['area'] ?? 0,
                        'base_price' => $l['base_price'] ?? 0,
                        'status' => $l['status'] ?? 'available',
                    ]);
                    $lotMap[$l['id'] ?? null] = $lot->id;
                }

                $buyerMap = [];
                foreach ($data['buyers'] ?? [] as $b) {
                    $buyer = Buyer::create([
                        'name' => $b['name'] ?? 'Buyer',
                        'phone' => $b['phone'] ?? null,
                        'email' => $b['email'] ?? null,
                        'address' => $b['address'] ?? null,
                    ]);
                    $buyerMap[$b['id'] ?? null] = $buyer->id;
                }

                $marketerMap = [];
                foreach ($data['marketers'] ?? [] as $m) {
                    $marketer = Marketer::create([
                        'name' => $m['name'] ?? 'Marketer',
                        'phone' => $m['phone'] ?? null,
                    ]);
                    $marketerMap[$m['id'] ?? null] = $marketer->id;
                }

                $saleMap = [];
                foreach ($data['sales'] ?? [] as $s) {
                    $lotIdKey = $s['lot_id'] ?? $s['kavling_id'] ?? null;
                    $lotId = $lotMap[$lotIdKey] ?? null;
                    $buyerIdKey = $s['buyer_id'] ?? $s['customer_id'] ?? null;
                    $buyerId = $buyerMap[$buyerIdKey] ?? null;
                    $marketerIdKey = $s['marketer_id'] ?? $s['sales_id'] ?? null;
                    $marketerId = $marketerMap[$marketerIdKey] ?? null;

                    if (!$lotId) {
                        logger()->warning('[Restore] Skipping sale with invalid lot_id', ['sale_data' => $s]);
                        continue;
                    }

                    $price = $s['price'] ?? $s['grand_total'] ?? $s['harga_netto'] ?? 0;
                    $dp = $s['down_payment'] ?? $s['dp_terbayar'] ?? $s['uang_muka_rp'] ?? 0;
                    $tenor = $s['tenor_months'] ?? $s['tenor'] ?? 0;
                    $dueDay = $s['due_day'] ?? $s['jatuh_tempo_hari'] ?? null;
                    $bookingDate = $s['booking_date'] ?? $s['invoice_date'] ?? null;
                    $paymentMethod = $s['payment_method'] ?? ($tenor > 0 ? 'installment' : 'cash');
                    $status = $s['status'] ?? 'active';
                    $paidAmount = $s['paid_amount'] ?? 0;
                    $outstandingAmount = $s['outstanding_amount'] ?? $price;

                    $sale = Sale::create([
                        'lot_id' => $lotId,
                        'buyer_id' => $buyerId,
                        'marketer_id' => $marketerId,
                        'booking_date' => $bookingDate,
                        'payment_method' => $paymentMethod,
                        'price' => $price,
                        'down_payment' => $dp,
                        'tenor_months' => $tenor,
                        'due_day' => $dueDay,
                        'paid_amount' => $paidAmount,
                        'outstanding_amount' => $outstandingAmount,
                        'status' => $status,
                    ]);
                    $saleMap[$s['id'] ?? null] = $sale->id;

                    if ($dp > 0) {
                        Payment::create([
                            'sale_id' => $sale->id,
                            'due_date' => $bookingDate,
                            'amount' => $dp,
                            'status' => 'paid',
                            'note' => 'Down Payment',
                            'paid_at' => $bookingDate,
                        ]);
                    }
                }

                $paymentsList = $data['payments'] ?? $data['installments'] ?? [];
                foreach ($paymentsList as $pmt) {
                    $saleId = $saleMap[$pmt['sale_id'] ?? null] ?? null;
                    if (!$saleId) {
                        continue;
                    }
                    $status = $pmt['status'] ?? 'unpaid';
                    if (!in_array($status, ['paid', 'unpaid', 'overdue'])) {
                        $status = $status === 'paid' ? 'paid' : 'unpaid';
                    }
                    Payment::create([
                        'sale_id' => $saleId,
                        'due_date' => $pmt['due_date'] ?? null,
                        'amount' => $pmt['amount'] ?? 0,
                        'status' => $status,
                        'note' => $pmt['note'] ?? (isset($pmt['installment_number']) ? 'Angsuran ke-' . $pmt['installment_number'] : null),
                        'paid_at' => $pmt['paid_at'] ?? $pmt['payment_date'] ?? null,
                    ]);
                }

                $projects = Project::with('lots')->get();
                foreach ($projects as $project) {
                    $project->total_units = $project->lots->count();
                    $project->sold_units = $project->lots->where('status', 'sold')->count();
                    $project->save();
                }

                foreach (Sale::all() as $sale) {
                    $paidSum = $sale->payments()->where('status', 'paid')->sum('amount');
                    $sale->paid_amount = min($sale->price, $paidSum);
                    $sale->outstanding_amount = max(0, $sale->price - $sale->paid_amount);
                    $sale->status = $sale->outstanding_amount <= 0 ? 'paid_off' : 'active';
                    $sale->save();
                }

                if (isset($data['company_profile'])) {
                    $cpData = $data['company_profile'];
                    CompanyProfile::create([
                        'name' => $cpData['name'] ?? 'Perusahaan Properti',
                        'npwp' => $cpData['npwp'] ?? null,
                        'email' => $cpData['email'] ?? null,
                        'phone' => $cpData['phone'] ?? null,
                        'address' => $cpData['address'] ?? null,
                        'signer_name' => $cpData['signer_name'] ?? 'Admin Keuangan',
                        'footer_note' => $cpData['footer_note'] ?? 'Terima kasih atas pembayaran Anda.',
                        'invoice_format' => $cpData['invoice_format'] ?? 'INV/{YYYY}/{MM}/{####}',
                        'receipt_format' => $cpData['receipt_format'] ?? 'KW/{YYYY}/{MM}/{####}',
                        'logo_path' => $cpData['logo_path'] ?? null,
                    ]);
                } else {
                    $this->seedDefaultCompanyProfile();
                }
            });

            $this->clearCaches();
            return redirect()->route('data.index')->with('success', 'Backup berhasil dipulihkan.');
        }, 'data.index', 'Gagal memulihkan backup. Silakan coba lagi.');
    }

    private function clearCaches(): void
    {
        Cache::flush();
    }

    private function resetData(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // FORCE FIX STRUCTURE: Remove strict enum constraints to allow 'partial', 'DIBATALKAN_xxx', etc.
            // This ensures loadDemo works even if migration hasn't run yet.
            try {
                DB::statement("ALTER TABLE sales ALTER COLUMN status TYPE VARCHAR(50)");
                DB::statement("ALTER TABLE sales ALTER COLUMN status SET DEFAULT 'active'");
                DB::statement("ALTER TABLE sales DROP CONSTRAINT IF EXISTS sales_status_check");

                DB::statement("ALTER TABLE payments ALTER COLUMN status TYPE VARCHAR(50)");
                DB::statement("ALTER TABLE payments ALTER COLUMN status SET DEFAULT 'unpaid'");
                DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check");
            } catch (\Throwable $e) {
                // Ignore if already fixed or permission error, proceed to truncate
                logger()->warning('Auto-fix schema failed: ' . $e->getMessage());
            }

            // PostgreSQL: use TRUNCATE with CASCADE
            DB::statement('TRUNCATE TABLE payments, sales, lots, projects, buyers, marketers, company_profiles RESTART IDENTITY CASCADE');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
            Payment::query()->delete();
            Sale::query()->delete();
            Lot::query()->delete();
            Project::query()->delete();
            Buyer::query()->delete();
            Marketer::query()->delete();
            CompanyProfile::query()->delete();
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            // MySQL and others
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Payment::truncate();
            Sale::truncate();
            Lot::truncate();
            Project::truncate();
            Buyer::truncate();
            Marketer::truncate();
            CompanyProfile::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function seedDefaultCompanyProfile(): void
    {
        $defaults = config('company');
        CompanyProfile::firstOrCreate([], [
            'name' => $defaults['name'] ?? 'Perusahaan Properti',
            'npwp' => '01.234.567.8-901.000',
            'email' => $defaults['email'] ?? null,
            'phone' => $defaults['phone'] ?? null,
            'address' => $defaults['address'] ?? null,
            'signer_name' => 'Admin Keuangan',
            'footer_note' => 'Terima kasih atas pembayaran Anda.',
            'invoice_format' => 'INV/{YYYY}/{MM}/{####}',
            'receipt_format' => 'KW/{YYYY}/{MM}/{####}',
            'logo_path' => null,
        ]);
    }
}
