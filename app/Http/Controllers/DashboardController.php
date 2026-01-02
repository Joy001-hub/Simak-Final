<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\SafeExecution;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Lot;
use App\Models\Project;

class DashboardController extends Controller
{
    use SafeExecution;

    public function index(Request $request)
    {
        return $this->safeExecute(function () use ($request) {
            $periodOptions = ['Minggu Ini', 'Bulan Ini', 'Tahun Ini', 'Tahun Lalu', 'Semua'];
            $now = now();
            $activePeriod = $request->query('periode', 'Tahun Ini');
            if (!$request->has('periode')) {
                $currentYear = $now->year;
                $hasCurrentYearSales = Sale::whereYear('booking_date', $currentYear)->exists();
                if (!$hasCurrentYearSales) {
                    $hasLastYearSales = Sale::whereYear('booking_date', $currentYear - 1)->exists();
                    $activePeriod = $hasLastYearSales ? 'Tahun Lalu' : 'Semua';
                }
            }
            $dateFrom = null;
            $dateTo = null;
            $periodLengthMonths = null;
            $trendInterval = 'month';
            $compareEnabled = $request->boolean('compare', false) && $activePeriod !== 'Semua';
            $canCompare = true;
            $compareFrom = null;
            $compareTo = null;
            $compareLabel = null;

            switch ($activePeriod) {
                case 'Minggu Ini':
                    $dateFrom = $now->copy()->startOfWeek();
                    $dateTo = $now->copy()->endOfWeek();
                    $periodLengthMonths = 1;
                    $trendInterval = 'day';
                    $compareFrom = $dateFrom->copy()->subWeek();
                    $compareTo = $dateTo->copy()->subWeek();
                    $compareLabel = 'Minggu lalu';
                    break;
                case 'Bulan Ini':
                    $dateFrom = $now->copy()->startOfMonth();
                    $dateTo = $now->copy()->endOfMonth();
                    $periodLengthMonths = 1;
                    $trendInterval = 'week';
                    $compareFrom = $dateFrom->copy()->subMonth();
                    $compareTo = $dateTo->copy()->subMonth();
                    $compareLabel = 'Bulan lalu';
                    break;
                case 'Tahun Ini':
                    $dateFrom = $now->copy()->startOfYear();
                    $dateTo = $now->copy()->endOfYear();
                    $periodLengthMonths = 12;
                    $trendInterval = 'month';
                    $compareFrom = $dateFrom->copy()->subYear();
                    $compareTo = $dateTo->copy()->subYear();
                    $compareLabel = 'Tahun lalu';
                    break;
                case 'Tahun Lalu':
                    $dateFrom = $now->copy()->subYear()->startOfYear();
                    $dateTo = $now->copy()->subYear()->endOfYear();
                    $periodLengthMonths = 12;
                    $trendInterval = 'month';
                    $compareFrom = $dateFrom->copy()->subYear();
                    $compareTo = $dateTo->copy()->subYear();
                    $compareLabel = '2 tahun lalu';
                    break;
                case 'Kustom':
                    $dateFrom = $request->query('custom_from') ? $now->copy()->parse($request->query('custom_from')) : null;
                    $dateTo = $request->query('custom_to') ? $now->copy()->parse($request->query('custom_to')) : null;
                    if ($dateFrom && $dateTo) {
                        $periodLengthMonths = max($dateFrom->diffInMonths($dateTo) + 1, 1);
                        $days = $dateFrom->diffInDays($dateTo) + 1;
                        if ($days <= 31) {
                            $trendInterval = 'day';
                        } elseif ($days <= 120) {
                            $trendInterval = 'week';
                        } elseif ($days <= 720) {
                            $trendInterval = 'month';
                        } else {
                            $trendInterval = 'quarter';
                        }
                        $compareFrom = $dateFrom->copy()->subDays($days);
                        $compareTo = $compareFrom->copy()->addDays($days - 1);
                        $compareLabel = 'Periode sebelumnya';
                    } else {
                        $canCompare = false;
                    }
                    break;
                case 'Semua':
                default:
                    $activePeriod = 'Semua';
                    $trendInterval = 'year';
                    $compareEnabled = false;
                    $canCompare = false;
                    break;
            }

            $compareEligible = $compareEnabled && $canCompare && $compareFrom && $compareTo;

            $sales = Sale::with(['payments', 'lot.project', 'marketer'])
                ->when($dateFrom, fn($q) => $q->whereDate('booking_date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('booking_date', '<=', $dateTo))
                ->get();

            $comparisonSales = $compareEligible
                ? Sale::with(['payments', 'lot.project', 'marketer'])
                    ->whereDate('booking_date', '>=', $compareFrom)
                    ->whereDate('booking_date', '<=', $compareTo)
                    ->get()
                : collect();

            $payments = Payment::when($dateFrom, fn($q) => $q->whereDate('due_date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('due_date', '<=', $dateTo))
                ->get();

            $availableLots = Lot::where('status', 'available')->with('project')->get();

            $projectAvailCounts = Project::withCount([
                'lots as avail_count' => function ($q) {
                    $q->where('status', 'available');
                }
            ])->get();

            $projectsWithLots = Project::with('lots')->get();

            $globalActiveSalesCount = Sale::where('status', 'active')->count();
            $globalAvailableLotsCount = Lot::where('status', 'available')->count();
            $globalOutstanding = Sale::whereNotIn('status', [
                'canceled',
                Sale::STATUS_CANCELED_HAPUS,
                Sale::STATUS_CANCELED_REFUND,
                Sale::STATUS_CANCELED_OPER_KREDIT
            ])->sum('outstanding_amount');

            $compareEnabled = $compareEligible;

            $firstSaleDate = $sales->whereNotNull('booking_date')->min('booking_date');
            $lastSaleDate = $sales->whereNotNull('booking_date')->max('booking_date');
            $dataRangeMonths = $firstSaleDate && $lastSaleDate ? $firstSaleDate->diffInMonths($lastSaleDate) + 1 : 0;

            $projectionMode = 'short';
            if ($activePeriod === 'Semua') {
                $projectionMode = 'all';
            } elseif (($periodLengthMonths ?? 0) >= 12) {
                $projectionMode = 'year';
            }

            $projectionMeta = [
                'mode' => $projectionMode,
                'aiEligible' => in_array($projectionMode, ['year', 'all']) && $dataRangeMonths >= 12,
                'rangeMonths' => $periodLengthMonths,
                'dataMonths' => $dataRangeMonths,
                'horizonMonths' => 12,
                'horizonQuarters' => 4,
            ];

            $comparisonMeta = [
                'enabled' => $compareEnabled,
                'label' => $compareLabel,
                'range' => $compareEnabled ? [$compareFrom?->toDateString(), $compareTo?->toDateString()] : null,
                'reason' => $canCompare ? null : 'Periode tidak bisa dibandingkan',
            ];

            $canceledStatuses = [
                'canceled',
                Sale::STATUS_CANCELED_HAPUS,
                Sale::STATUS_CANCELED_REFUND,
                Sale::STATUS_CANCELED_OPER_KREDIT,
            ];

            $activeSales = $sales->whereNotIn('status', $canceledStatuses);

            $calculatePaidDP = function ($salesCollection) {
                return $salesCollection->flatMap(function ($sale) {
                    return $sale->payments->filter(function ($payment) {
                        $note = $payment->note ?? '';
                        return \Illuminate\Support\Str::startsWith($note, 'Down Payment') && $payment->status === 'paid';
                    });
                })->sum('amount');
            };

            $calculateNetPaid = function ($salesAll, $activeSalesOnly) {
                $activeSum = $activeSalesOnly->sum('paid_amount');
                $refundedSales = $salesAll->where('status', Sale::STATUS_CANCELED_REFUND);
                $refundedNet = $refundedSales->sum(function ($sale) {
                    return max(0, ((int) $sale->paid_amount) - ((int) $sale->refund_amount));
                });
                return $activeSum + $refundedNet;
            };

            $totalPenjualan = $activeSales->count();
            $totalHarga = $activeSales->sum('price');
            $totalDP = $calculatePaidDP($activeSales);
            $totalPaid = $calculateNetPaid($sales, $activeSales);

            $canceledSales = $sales->whereIn('status', ['canceled', Sale::STATUS_CANCELED_HAPUS, Sale::STATUS_CANCELED_REFUND]);
            $canceledCount = $canceledSales->count();
            $canceledValue = $canceledSales->sum('price');

            $outstanding = $globalOutstanding;

            $today = now()->startOfDay();
            $sevenDaysFromNow = now()->addDays(7)->endOfDay();

            $allActiveSales = Sale::with('payments')
                ->whereNotIn('status', $canceledStatuses)
                ->where('outstanding_amount', '>', 0)
                ->get();

            $totalActiveSalesForStatus = max(1, $allActiveSales->count());

            $tunggakanCount = 0;
            $perhatianCount = 0;
            $amanCount = 0;

            foreach ($allActiveSales as $sale) {
                $hasOverdue = $sale->payments()
                    ->where('status', 'unpaid')
                    ->whereDate('due_date', '<', $today)
                    ->exists();

                if ($hasOverdue) {
                    $tunggakanCount++;
                    continue;
                }

                $hasUpcoming = $sale->payments()
                    ->where('status', 'unpaid')
                    ->whereDate('due_date', '>=', $today)
                    ->whereDate('due_date', '<=', $sevenDaysFromNow)
                    ->exists();

                if ($hasUpcoming) {
                    $perhatianCount++;
                } else {
                    $amanCount++;
                }
            }

            $tunggakanPct = $tunggakanCount / $totalActiveSalesForStatus;
            $perhatianPct = $perhatianCount / $totalActiveSalesForStatus;
            $amanPct = $amanCount / $totalActiveSalesForStatus;

            $trendCalc = function ($current, $previous) {
                if ($previous === null) {
                    return null;
                }
                if ($previous == 0) {
                    return [
                        'delta' => $current > 0 ? 100 : 0,
                        'direction' => $current >= $previous ? 'up' : 'down',
                    ];
                }
                $diff = $current - $previous;
                $delta = round(($diff / $previous) * 100, 1);
                return [
                    'delta' => $delta,
                    'direction' => $diff >= 0 ? 'up' : 'down',
                ];
            };

            $activeComparisonSales = $compareEnabled
                ? $comparisonSales->whereNotIn('status', $canceledStatuses)
                : collect();

            $previousTotals = $compareEnabled ? [
                'paid' => $calculateNetPaid($comparisonSales, $activeComparisonSales),
                'dp' => $calculatePaidDP($activeComparisonSales),
                'count' => $activeComparisonSales->count(),
                'price' => $activeComparisonSales->sum('price'),
                'outstanding' => $activeComparisonSales->sum('outstanding_amount'),
            ] : null;

            $summary = [
                [
                    'label' => 'Penerimaan Periode Ini',
                    'value' => $totalPaid,
                    'previous' => $previousTotals['paid'] ?? null,
                    'trend' => $compareEnabled ? $trendCalc($totalPaid, $previousTotals['paid'] ?? null) : null,
                    'hint' => $totalPenjualan ? "dari {$totalPenjualan} transaksi" : 'Belum ada data',
                ],
                [
                    'label' => 'Total DP Diterima',
                    'value' => $totalDP,
                    'previous' => $previousTotals['dp'] ?? null,
                    'trend' => $compareEnabled ? $trendCalc($totalDP, $previousTotals['dp'] ?? null) : null,
                    'hint' => $totalPenjualan ? "dari {$totalPenjualan} penjualan" : 'Belum ada data',
                ],
                [
                    'label' => 'Total Penjualan',
                    'value' => $totalPenjualan,
                    'isUnit' => true,
                    'previous' => $previousTotals['count'] ?? null,
                    'trend' => $compareEnabled ? $trendCalc($totalPenjualan, $previousTotals['count'] ?? null) : null,
                    'hint' => 'senilai Rp ' . number_format($totalHarga, 0, ',', '.'),
                ],
                [
                    'label' => 'Total Penjualan Batal',
                    'value' => $canceledCount,
                    'isUnit' => true,
                    'previous' => null,
                    'trend' => null,
                    'hint' => $canceledCount ? 'senilai Rp ' . number_format($canceledValue, 0, ',', '.') : 'Tidak ada penjualan batal',
                ],
                [
                    'label' => 'Total Piutang (Global)',
                    'value' => $outstanding,
                    'previous' => null,
                    'trend' => null,
                    'hint' => $globalActiveSalesCount > 0 ? "dari {$globalActiveSalesCount} penjualan aktif" : 'Belum ada data',
                    'statuses' => [
                        ['label' => 'Ada Tunggakan', 'color' => '#EF4444', 'value' => $tunggakanPct],
                        ['label' => 'Jatuh Tempo <7 hari', 'color' => '#EAB308', 'value' => $perhatianPct],
                        ['label' => 'Aman', 'color' => '#22C55E', 'value' => $amanPct],
                    ],
                ],
                [
                    'label' => 'Nilai Persediaan Kavling',
                    'value' => $availableLots->sum('base_price'),
                    'hint' => 'dari ' . $globalAvailableLotsCount . ' unit tersedia',
                    'totalUnits' => $globalAvailableLotsCount,
                    'inventories' => $projectAvailCounts->values()->map(function ($p, $index) use ($availableLots) {
                        $totalAvail = max($availableLots->count(), 1);
                        $colors = ['#9c0f2f', '#E65100', '#1565C0', '#2E7D32', '#F9A825', '#00838F', '#C62828', '#424242'];
                        return [
                            'label' => $p->name,
                            'color' => $colors[$index % count($colors)],
                            'value' => $p->avail_count / $totalAvail,
                            'units' => $p->avail_count,
                        ];
                    })->filter(fn($item) => $item['units'] > 0)->values(),
                ],
            ];

            $formatLabel = function (string $key, string $interval) {
                if ($key === 'N/A') {
                    return 'N/A';
                }
                switch ($interval) {
                    case 'day':
                        return \Carbon\Carbon::parse($key)->format('d M');
                    case 'week':
                        [$year, $week] = explode('-', $key);
                        return 'Minggu ' . $week;
                    case 'quarter':
                        [$year, $rest] = explode('-Q', $key);
                        return 'Q' . $rest . ' ' . $year;
                    case 'year':
                        return $key;
                    case 'month':
                    default:
                        return \Carbon\Carbon::createFromFormat('Y-m', $key)->format("M 'y");
                }
            };

            $groupByInterval = function ($sale) use ($trendInterval) {
                $date = optional($sale->booking_date);
                if (!$date) {
                    return 'N/A';
                }
                return match ($trendInterval) {
                    'day' => $date->format('Y-m-d'),
                    'week' => $date->copy()->startOfWeek()->format('o-W'),
                    'quarter' => $date->format('Y') . '-Q' . $date->quarter,
                    'year' => $date->format('Y'),
                    default => $date->format('Y-m'),
                };
            };

            $salesTrend = $sales->sortBy('booking_date')
                ->groupBy($groupByInterval)
                ->map(function ($group, $periodKey) use ($trendInterval, $formatLabel) {
                    return [
                        'label' => $formatLabel($periodKey, $trendInterval),
                        'period' => $periodKey !== 'N/A' ? $periodKey : null,
                        'value' => round($group->sum('price') / 1_000_000, 2),
                        'unit' => $group->count(),
                    ];
                })
                ->values()
                ->toArray();

            $comparisonSalesTrend = $comparisonSales->sortBy('booking_date')
                ->groupBy($groupByInterval)
                ->map(function ($group, $periodKey) use ($trendInterval, $formatLabel) {
                    return [
                        'label' => $formatLabel($periodKey, $trendInterval),
                        'period' => $periodKey !== 'N/A' ? $periodKey : null,
                        'value' => round($group->sum('price') / 1_000_000, 2),
                        'unit' => $group->count(),
                    ];
                })
                ->values()
                ->toArray();

            $marketingPerformance = $sales->groupBy(function ($sale) {
                return optional($sale->marketer)->name ?: 'Tanpa Marketer';
            })->map(function ($group, $name) {
                return [
                    'name' => $name,
                    'value' => round($group->sum('price') / 1_000_000, 2),
                    'unit' => $group->count(),
                ];
            })->values()->toArray();

            $projectSales = $sales->groupBy(function ($sale) {
                return optional($sale->lot?->project)?->name ?: 'Tanpa Proyek';
            })->map(function ($group, $name) {
                return [
                    'name' => $name,
                    'value' => round($group->sum('price') / 1_000_000, 2),
                    'unit' => $group->count(),
                ];
            })->values()->toArray();

            $projectInventory = $projectsWithLots->map(function ($project) {
                $available = $project->lots->where('status', 'available');
                return [
                    'name' => $project->name,
                    'value' => round($available->sum('base_price') / 1_000_000, 2),
                    'unit' => $available->count(),
                ];
            })->filter(fn($row) => $row['value'] > 0 || $row['unit'] > 0)->values()->toArray();

            $salesMax = max(collect($salesTrend)->max('value') ?: 1, collect($salesTrend)->max('unit') ?: 1);
            $marketingMax = max(collect($marketingPerformance)->max('value') ?: 1, collect($marketingPerformance)->max('unit') ?: 1);

            return view('dashboard', [
                'periodOptions' => $periodOptions,
                'summary' => $summary,
                'salesTrend' => $salesTrend,
                'comparisonSalesTrend' => $comparisonSalesTrend,
                'marketingPerformance' => $marketingPerformance,
                'projectSales' => $projectSales,
                'projectInventory' => $projectInventory,
                'activePeriod' => $activePeriod,
                'trendInterval' => $trendInterval,
                'compareEnabled' => $compareEnabled,
                'salesMax' => $salesMax,
                'marketingMax' => $marketingMax,
                'projectionMeta' => $projectionMeta,
                'comparisonMeta' => $comparisonMeta,
            ]);
        }, 'license.activate.form');
    }
}
