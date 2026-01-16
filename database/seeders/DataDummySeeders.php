<?php

namespace Database\Seeders;

use App\Models\Buyer;
use App\Models\Lot;
use App\Models\Marketer;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DataDummySeeders extends Seeder
{

    public function run(): void
    {
        $this->resetTables();

        $now = Carbon::now();
        $today = $now->copy()->startOfDay();

        $projects = $this->seedProjects();
        $lots = $this->seedLots($projects);
        $marketers = $this->seedMarketers();
        $buyers = $this->seedBuyers();

        $this->seedSales($lots, $buyers, $marketers, $today, $now);
        $this->updateProjectStats();
    }

    private function resetTables(): void
    {
        $driver = \Illuminate\Support\Facades\DB::getDriverName();

        if ($driver === 'pgsql') {
            // Force schema fix for seeders
            try {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE sales ALTER COLUMN status TYPE VARCHAR(50)");
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE sales DROP CONSTRAINT IF EXISTS sales_status_check");
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE payments ALTER COLUMN status TYPE VARCHAR(50)");
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check");
            } catch (\Throwable $e) {
            }

            \Illuminate\Support\Facades\DB::statement('TRUNCATE TABLE payments, sales, lots, projects, buyers, marketers RESTART IDENTITY CASCADE');
        } else {
            Schema::disableForeignKeyConstraints();
            Payment::truncate();
            Sale::truncate();
            Lot::truncate();
            Project::truncate();
            Buyer::truncate();
            Marketer::truncate();
            Schema::enableForeignKeyConstraints();
        }
    }

    private function seedProjects(): array
    {
        $projectDefinitions = [
            ['name' => 'Kavling Harmoni Alam', 'location' => 'Ciawi, Bogor', 'notes' => 'Tahap 1, akses tol Ciawi', 'lots' => 40],
            ['name' => 'Kavling Mutiara Residence', 'location' => 'Sentul, Bogor', 'notes' => 'View gunung + fasum keluarga', 'lots' => 35],
            ['name' => 'Kavling Permata Hills', 'location' => 'Puncak, Bogor', 'notes' => 'Kawasan wisata premium', 'lots' => 28],
            ['name' => 'Kavling Surya Garden', 'location' => 'Jonggol, Bogor', 'notes' => 'Konsep hijau & pedestrian luas', 'lots' => 32],
        ];

        $projects = [];
        foreach ($projectDefinitions as $def) {
            $projects[] = Project::create([
                'name' => $def['name'],
                'location' => $def['location'],
                'notes' => $def['notes'],
                'total_units' => $def['lots'],
                'sold_units' => 0,
            ]);
        }

        return $projects;
    }

    private function seedLots(array $projects): array
    {
        $blocks = ['A', 'B', 'C', 'D', 'E', 'F'];
        $lotData = [];

        foreach ($projects as $project) {
            $totalLots = (int) ($project->total_units ?? 0);
            $lotsPerBlock = max(1, (int) ceil($totalLots / count($blocks)));
            $created = 0;

            foreach ($blocks as $block) {
                for ($num = 1; $num <= $lotsPerBlock && $created < $totalLots; $num++) {
                    $area = rand(84, 240);
                    $pricePerMeter = rand(900_000, 1_600_000);
                    $lot = Lot::create([
                        'project_id' => $project->id,
                        'block_number' => "{$block}-{$num}",
                        'area' => $area,
                        'base_price' => $area * $pricePerMeter,
                        'status' => 'available',
                    ]);

                    $lotData[] = [
                        'id' => $lot->id,
                        'project_id' => $project->id,
                        'block_number' => $lot->block_number,
                        'area' => $lot->area,
                        'base_price' => $lot->base_price,
                    ];

                    $created++;
                }
            }
        }

        return $lotData;
    }

    private function seedMarketers(): array
    {
        $names = [
            'Andi Firmansyah',
            'Bima Sakti',
            'Citra Dewi',
            'Denny Pratama',
            'Eka Putra',
            'Fauzi Rahman',
            'Gina Marlina',
            'Hadi Santoso',
        ];

        $ids = [];
        foreach ($names as $idx => $name) {
            $marketer = Marketer::create([
                'name' => $name,
                'phone' => '0812345670' . ($idx + 1),
            ]);
            $ids[] = $marketer->id;
        }

        return $ids;
    }

    private function seedBuyers(): array
    {
        $firstNames = [
            'Ahmad',
            'Budi',
            'Cahya',
            'Dewi',
            'Eka',
            'Fitri',
            'Galih',
            'Hana',
            'Irfan',
            'Joko',
            'Kartika',
            'Lina',
            'Maya',
            'Nanda',
            'Oscar',
            'Putri',
            'Reza',
            'Sari',
            'Taufik',
            'Umi',
            'Vera',
            'Wawan',
            'Yani',
            'Zainal',
            'Agus',
            'Bambang',
            'Clara',
            'Dian',
            'Endang',
            'Fajar',
            'Gunawan',
            'Hendra',
            'Indah',
            'Jihan',
            'Kurnia',
            'Lukman',
            'Mega',
            'Nurul',
            'Oki',
            'Pandu',
        ];
        $lastNames = [
            'Wijaya',
            'Santoso',
            'Kusuma',
            'Purnama',
            'Pratama',
            'Hidayat',
            'Saputra',
            'Wibowo',
            'Setiawan',
            'Nugraha',
            'Permana',
            'Gunawan',
            'Susanto',
            'Budiman',
            'Hartono',
            'Suryadi',
            'Prasetyo',
            'Ramadhan',
            'Utami',
            'Handoko',
        ];
        $cities = ['Jakarta Selatan', 'Jakarta Barat', 'Jakarta Timur', 'Bogor', 'Depok', 'Tangerang', 'Bekasi', 'Bandung'];
        $streets = ['Jl. Sudirman', 'Jl. Thamrin', 'Jl. Gatot Subroto', 'Jl. Kemang', 'Jl. Pondok Indah', 'Jl. Kelapa Gading'];

        $ids = [];
        for ($i = 0; $i < 90; $i++) {
            $first = $firstNames[array_rand($firstNames)];
            $last = $lastNames[array_rand($lastNames)];
            $buyer = Buyer::create([
                'name' => "{$first} {$last}",
                'phone' => '08' . rand(1, 9) . rand(10000000, 99999999),
                'email' => strtolower(str_replace(' ', '.', "{$first}.{$last}") . rand(1, 99) . '@email.com'),
                'address' => $streets[array_rand($streets)] . ' No. ' . rand(1, 120) . ', ' . $cities[array_rand($cities)],
            ]);
            $ids[] = $buyer->id;
        }

        return $ids;
    }

    private function seedSales(array $lots, array $buyers, array $marketers, Carbon $today, Carbon $now): void
    {
        $yearlyTargets = [
            2023 => 28,
            2024 => 32,
            2025 => 30,
        ];

        $lotPool = collect($lots)->shuffle()->values();
        $paymentInserts = [];

        foreach ($yearlyTargets as $year => $target) {
            for ($i = 0; $i < $target && $lotPool->isNotEmpty(); $i++) {
                $lot = $lotPool->shift();
                $bookingDate = Carbon::create($year, rand(1, 12), rand(1, 28));

                $futureLimit = $today->copy()->addMonths(6);
                if ($bookingDate->gt($futureLimit)) {
                    $bookingDate = $today->copy()->subDays(rand(5, 45));
                }

                $methodRoll = rand(1, 100);
                $paymentMethod = $methodRoll <= 55 ? 'installment' : ($methodRoll <= 80 ? 'cash' : 'kpr');

                [$saleData, $payments, $lotStatus] = $this->buildSalePayload(
                    $lot,
                    $buyers[array_rand($buyers)],
                    rand(1, 100) <= 10 ? null : $marketers[array_rand($marketers)],
                    $bookingDate,
                    $paymentMethod,
                    $today,
                    $now
                );

                $sale = Sale::create($saleData);

                foreach ($payments as $paymentRow) {
                    $paymentRow['sale_id'] = $sale->id;
                    $paymentInserts[] = $paymentRow;
                }

                Lot::where('id', $lot['id'])->update(['status' => $lotStatus]);
            }
        }

        foreach (array_chunk($paymentInserts, 500) as $chunk) {
            Payment::insert($chunk);
        }
    }

    private function buildSalePayload(
        array $lot,
        int $buyerId,
        ?int $marketerId,
        Carbon $bookingDate,
        string $paymentMethod,
        Carbon $today,
        Carbon $now
    ): array {
        $variation = rand(-7, 12) / 100;
        $price = (int) round($lot['base_price'] * (1 + $variation));
        $price = max(1_000_000, $price);

        $saleData = [
            'lot_id' => $lot['id'],
            'buyer_id' => $buyerId,
            'marketer_id' => $marketerId,
            'booking_date' => $bookingDate->toDateString(),
            'payment_method' => $paymentMethod,
            'price' => $price,
            'down_payment' => 0,
            'tenor_months' => 0,
            'due_day' => null,
            'paid_amount' => 0,
            'outstanding_amount' => $price,
            'status' => 'active',
            'refund_amount' => null,
            'status_before_cancel' => null,
            'parent_sale_id' => null,
            'notes' => null,
        ];

        $payments = [];
        $paidTotal = 0;
        $lotStatus = 'sold';

        if ($paymentMethod === 'installment') {
            $tenorOptions = [12, 18, 24, 36, 48];
            $tenor = $tenorOptions[array_rand($tenorOptions)];
            $dueDay = rand(3, 27);
            $dpPercent = rand(15, 35);
            $dpNominal = (int) round($price * ($dpPercent / 100));
            $dpPaid = rand(1, 100) <= 82;

            $saleData['down_payment'] = $dpNominal;
            $saleData['tenor_months'] = $tenor;
            $saleData['due_day'] = $dueDay;

            $payments[] = [
                'due_date' => $bookingDate->toDateString(),
                'amount' => $dpNominal,
                'status' => $dpPaid ? 'paid' : 'unpaid',
                'note' => 'Down Payment',
                'paid_at' => $dpPaid ? $bookingDate->copy()->addDays(rand(0, 5)) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if ($dpPaid) {
                $paidTotal += $dpNominal;
            }

            $remaining = max(0, $price - $dpNominal);
            $baseTerm = (int) floor($remaining / max(1, $tenor));
            $remainder = $remaining - ($baseTerm * $tenor);

            for ($inst = 1; $inst <= $tenor; $inst++) {
                $amount = $baseTerm + ($inst <= $remainder ? 1 : 0);
                $dueDate = $bookingDate->copy()->addMonths($inst)->day($dueDay);

                $status = 'unpaid';
                $paidAt = null;
                $paidPart = 0;

                if ($dueDate->lte($today)) {
                    $roll = rand(1, 100);
                    if ($roll <= 68) {
                        $status = 'paid';
                        $paidAt = $dueDate->copy()->addDays(rand(-3, 5));
                        $paidPart = $amount;
                    } elseif ($roll <= 82) {
                        $status = 'partial';
                        $paidPart = (int) round($amount * (rand(35, 70) / 100));
                        $remainingAmt = max(0, $amount - $paidPart);
                        if ($remainingAmt <= 0) {
                            $status = 'paid';
                            $paidPart = $amount;
                            $paidAt = $dueDate->copy()->addDays(rand(-2, 4));
                        } else {
                            $amount = $remainingAmt;
                        }
                    } else {
                        $status = 'overdue';
                    }
                }

                if ($status === 'paid') {
                    $paidTotal += $amount;
                } elseif ($status === 'partial') {
                    $paidTotal += $paidPart;
                }

                $payments[] = [
                    'due_date' => $dueDate->toDateString(),
                    'amount' => $amount,
                    'status' => $status,
                    'note' => "Angsuran ke-{$inst}",
                    'paid_at' => $paidAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        } elseif ($paymentMethod === 'cash') {
            $scenario = rand(1, 100);
            if ($scenario <= 60) {

                $payments[] = [
                    'due_date' => $bookingDate->toDateString(),
                    'amount' => $price,
                    'status' => 'paid',
                    'note' => 'Pembayaran Cash Keras',
                    'paid_at' => $bookingDate->copy()->addDays(rand(0, 3)),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $paidTotal = $price;
                $saleData['status'] = 'paid_off';
            } elseif ($scenario <= 80) {

                $paidPart = (int) round($price * (rand(40, 70) / 100));
                $payments[] = [
                    'due_date' => $bookingDate->toDateString(),
                    'amount' => $price,
                    'status' => 'unpaid',
                    'note' => 'Pembayaran Cash Keras',
                    'paid_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $payments[] = [
                    'due_date' => $bookingDate->toDateString(),
                    'amount' => $paidPart,
                    'status' => 'paid',
                    'note' => 'Pembayaran Fleksibel',
                    'paid_at' => $bookingDate->copy()->addDays(rand(0, 10)),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $paidTotal = $paidPart;
                $saleData['status'] = 'active';
            } else {

                $payments[] = [
                    'due_date' => $bookingDate->toDateString(),
                    'amount' => $price,
                    'status' => 'unpaid',
                    'note' => 'Pembayaran Cash Keras',
                    'paid_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $saleData['status'] = 'active';
            }
        } else {

            $dpPercent = rand(15, 25);
            $dpNominal = (int) round($price * ($dpPercent / 100));
            $dpPaid = rand(1, 100) <= 80;
            $dpPaidAmount = $dpPaid ? $dpNominal : 0;
            $saleData['down_payment'] = $dpNominal;
            $saleData['tenor_months'] = 0;
            $saleData['due_day'] = null;

            $payments[] = [
                'due_date' => $bookingDate->toDateString(),
                'amount' => $dpNominal,
                'status' => $dpPaid ? 'paid' : 'unpaid',
                'note' => 'Down Payment',
                'paid_at' => $dpPaid ? $bookingDate->copy()->addDays(rand(0, 7)) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $scenario = rand(1, 100);
            if ($scenario <= 60) {

                $bankDate = $bookingDate->copy()->addDays(rand(20, 45));
                $payments[] = [
                    'due_date' => $bankDate->toDateString(),
                    'amount' => $price - $dpNominal,
                    'status' => 'paid',
                    'note' => 'Pelunasan KPR Bank',
                    'paid_at' => $bankDate,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $paidTotal = $price;
                $saleData['status'] = 'paid_off';
            } elseif ($scenario <= 85) {

                $bankDue = $bookingDate->copy()->addDays(rand(25, 60));
                $payments[] = [
                    'due_date' => $bankDue->toDateString(),
                    'amount' => $price - $dpNominal,
                    'status' => 'kpr_bank',
                    'note' => 'Pencairan KPR Bank',
                    'paid_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $paidTotal = $dpPaidAmount;
                $saleData['status'] = 'active';
            } else {

                $paidTotal = $dpPaidAmount;
                $saleData['status'] = rand(0, 1) ? Sale::STATUS_CANCELED_REFUND : Sale::STATUS_CANCELED_HAPUS;
                $saleData['refund_amount'] = $saleData['status'] === Sale::STATUS_CANCELED_REFUND
                    ? (float) round($dpPaidAmount * 0.6, 2)
                    : null;
                $saleData['status_before_cancel'] = 'Pengajuan KPR bermasalah';
                $lotStatus = 'available';
            }
        }

        $saleData['paid_amount'] = min($price, $paidTotal);
        $saleData['outstanding_amount'] = max(0, $price - $saleData['paid_amount']);
        if ($saleData['outstanding_amount'] <= 0 && $saleData['status'] === 'active') {
            $saleData['status'] = 'paid_off';
        }

        if ($saleData['outstanding_amount'] <= 0 || $saleData['status'] === 'paid_off') {
            foreach ($payments as &$p) {
                if (str_starts_with($p['note'] ?? '', 'Down Payment')) {
                    $p['status'] = 'paid';
                    $p['paid_at'] = $p['paid_at'] ?? $bookingDate->copy()->addDays(rand(0, 5));
                }
            }
            unset($p);
        }

        if (in_array($saleData['status'], [Sale::STATUS_CANCELED_HAPUS, Sale::STATUS_CANCELED_REFUND, Sale::STATUS_CANCELED_OPER_KREDIT, 'canceled'], true)) {
            $saleData['outstanding_amount'] = 0;
            $lotStatus = 'available';
        }

        return [$saleData, $payments, $lotStatus];
    }

    private function updateProjectStats(): void
    {
        $projects = Project::with('lots')->get();
        foreach ($projects as $project) {
            $project->total_units = $project->lots->count();
            $project->sold_units = $project->lots->where('status', 'sold')->count();
            $project->save();
        }
    }
}
