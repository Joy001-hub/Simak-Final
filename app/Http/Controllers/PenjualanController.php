<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\SafeExecution;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Lot;
use App\Models\Buyer;
use App\Models\Marketer;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\SaleRequest;
use App\Models\Payment;
use Carbon\Carbon;
use App\Models\CompanyProfile;

class PenjualanController extends Controller
{
    use SafeExecution;

    private function availableLots(?Sale $currentSale = null)
    {
        return Lot::with('project')
            ->where(function (Builder $outer) use ($currentSale) {
                $outer->where('status', 'available')
                    ->orWhere(function (Builder $q) use ($currentSale) {
                        $q->whereDoesntHave('sale', function (Builder $q2) {
                            $q2->whereNotIn('status', [
                                'canceled',
                                Sale::STATUS_CANCELED_HAPUS,
                                Sale::STATUS_CANCELED_REFUND,
                                Sale::STATUS_CANCELED_OPER_KREDIT,
                            ]);
                        });
                        if ($currentSale) {
                            $q->orWhereHas('sale', function (Builder $q2) use ($currentSale) {
                                $q2->where('id', $currentSale->id);
                            });
                        }
                    });
            })
            ->get();
    }

    public function cancel(Request $request, Sale $sale)
    {
        return $this->safeExecute(function () use ($request, $sale) {
            $type = $request->input('type');
            $sale->status_before_cancel = $sale->status_before_cancel ?? $sale->status;

            if ($type === 'hapus') {
                $sale->status = Sale::STATUS_CANCELED_HAPUS;
                $sale->save();
                $sale->lot?->update(['status' => 'available']);
            } elseif ($type === 'refund') {
                $amount = (int) str_replace('.', '', $request->input('refund_amount', 0));
                $realPaid = $sale->payments()->where('status', 'paid')->sum('amount');
                $sale->paid_amount = $realPaid;
                $sale->outstanding_amount = 0;
                $sale->status = Sale::STATUS_CANCELED_REFUND;
                $sale->refund_amount = $amount;
                $sale->save();
                $sale->lot?->update(['status' => 'available']);
            } elseif ($type === 'oper_kredit') {
                $newBuyerId = $request->input('new_buyer_id');
                $newMarketerId = $request->input('new_marketer_id');
                $oldBuyerName = optional($sale->buyer)->name;
                $sale->status_before_cancel = "Oper dari: {$oldBuyerName} (ID: {$sale->buyer_id})";
                $sale->buyer_id = $newBuyerId;
                if ($newMarketerId) {
                    $sale->marketer_id = $newMarketerId;
                }
                $sale->save();
                return redirect()->route('penjualan.show', $sale)
                    ->with('success', "Oper kredit berhasil! Pembeli diubah dari {$oldBuyerName} ke pembeli baru.");
            }

            return back()->with('success', 'Penjualan berhasil dibatalkan');
        }, 'penjualan.index');
    }

    public function updateNotes(Request $request, Sale $sale)
    {
        return $this->safeExecute(function () use ($request, $sale) {
            $sale->notes = $request->input('notes');
            $sale->save();
            return redirect()->route('penjualan.show', $sale)->with('success', 'Catatan berhasil diperbarui');
        }, 'penjualan.index');
    }

    public function index(Request $request)
    {
        return $this->safeExecute(function () use ($request) {
            $filters = [
                'kavling' => $request->query('kavling', ''),
                'pembeli' => $request->query('pembeli', ''),
                'status_tagihan' => $request->query('status_tagihan', 'Semua'),
                'status_dp' => $request->query('status_dp', 'Semua'),
                'status_penjualan' => $request->query('status_penjualan', 'Semua'),
                'marketing' => $request->query('marketing', 'Semua'),
                'metode_bayar' => $request->query('metode_bayar', 'Semua'),
                'tgl_booking_dari' => $request->query('tgl_booking_dari', ''),
                'tgl_booking_sampai' => $request->query('tgl_booking_sampai', ''),
                'harga_min' => $request->query('harga_min', ''),
                'harga_max' => $request->query('harga_max', ''),
                'sort_by' => $request->query('sort_by', 'booking_date'),
                'sort_dir' => $request->query('sort_dir', 'desc'),
            ];

            $query = Sale::with(['lot.project', 'buyer', 'marketer']);

            if ($filters['kavling'] !== '') {
                $key = $filters['kavling'];
                $parts = array_map('trim', explode('/', $key));
                $query->where(function ($q) use ($key, $parts) {
                    if (count($parts) >= 2) {
                        [$projectPart, $blockPart] = $parts;
                        $q->whereHas('lot', function ($q2) use ($projectPart, $blockPart) {
                            $q2->where('block_number', 'like', "%{$blockPart}%")
                                ->whereHas('project', function ($q3) use ($projectPart) {
                                    $q3->where('name', 'like', "%{$projectPart}%");
                                });
                        });
                    } else {
                        $q->whereHas('lot', function ($q2) use ($key) {
                            $q2->where('block_number', 'like', "%{$key}%");
                        })->orWhereHas('lot.project', function ($q3) use ($key) {
                            $q3->where('name', 'like', "%{$key}%");
                        });
                    }
                });
            }

            if ($filters['pembeli'] !== '') {
                $key = $filters['pembeli'];
                $query->whereHas('buyer', function ($q) use ($key) {
                    $q->where('name', 'like', "%{$key}%");
                });
            }

            if ($filters['metode_bayar'] !== 'Semua') {
                $map = [
                    'Cash Keras' => 'cash',
                    'Angsuran In-house' => 'installment',
                    'KPR Bank' => 'kpr',
                    'cash' => 'cash',
                    'installment' => 'installment',
                    'kpr' => 'kpr',
                ];
                $method = $map[$filters['metode_bayar']] ?? $filters['metode_bayar'];
                $query->where('payment_method', $method);
            }

            if ($filters['marketing'] !== 'Semua' && $filters['marketing'] !== '') {
                $query->where('marketer_id', $filters['marketing']);
            }

            if ($filters['status_penjualan'] !== 'Semua') {
                $statusMap = [
                    'Paid Off' => ['paid_off'],
                    'Active' => ['active'],
                    'Canceled' => [\App\Models\Sale::STATUS_CANCELED_REFUND, 'canceled'],
                    'Batal (Refund)' => [\App\Models\Sale::STATUS_CANCELED_REFUND, 'canceled'],
                ];
                $statuses = $statusMap[$filters['status_penjualan']] ?? [$filters['status_penjualan']];
                $query->whereIn('status', $statuses);
            }

            if ($filters['status_tagihan'] !== 'Semua') {
                $today = Carbon::today();
                if ($filters['status_tagihan'] === 'Ada Tunggakan') {
                    $query->whereHas('payments', function ($q) use ($today) {
                        $q->where('status', 'unpaid')->whereDate('due_date', '<', $today);
                    });
                } elseif ($filters['status_tagihan'] === 'Jatuh Tempo < 7 Hari') {
                    $targetDate = $today->copy()->addDays(7);
                    $query->whereHas('payments', function ($q) use ($today, $targetDate) {
                        $q->where('status', 'unpaid')
                            ->whereDate('due_date', '>=', $today)
                            ->whereDate('due_date', '<=', $targetDate);
                    })->whereDoesntHave('payments', function ($q) use ($today) {
                        $q->where('status', 'unpaid')->whereDate('due_date', '<', $today);
                    });
                } elseif ($filters['status_tagihan'] === 'Aman') {
                    $query->where('outstanding_amount', '<=', 0);
                }
            }

            if ($filters['status_dp'] !== 'Semua') {
                if ($filters['status_dp'] === 'Lunas') {
                    $query->where('down_payment', '>', 0)
                        ->whereHas('payments', function ($q) {
                            $q->where('note', 'Down Payment')->where('status', 'paid');
                        });
                } elseif ($filters['status_dp'] === 'Belum') {
                    $query->where('down_payment', '>', 0)
                        ->where(function ($q) {
                            $q->whereHas('payments', function ($q2) {
                                $q2->where('note', 'Down Payment')->where('status', '!=', 'paid');
                            })->orWhereDoesntHave('payments', function ($q2) {
                                $q2->where('note', 'Down Payment');
                            });
                        });
                }
            }

            if ($filters['tgl_booking_dari']) {
                $query->whereDate('booking_date', '>=', $filters['tgl_booking_dari']);
            }
            if ($filters['tgl_booking_sampai']) {
                $query->whereDate('booking_date', '<=', $filters['tgl_booking_sampai']);
            }
            if ($filters['harga_min'] !== '') {
                $query->where('price', '>=', (int) $filters['harga_min']);
            }
            if ($filters['harga_max'] !== '') {
                $query->where('price', '<=', (int) $filters['harga_max']);
            }

            $penjualan = $query->get()->map(function ($sale) {
                $today = Carbon::today();
                $targetDate = $today->copy()->addDays(7);
                $overduePayment = $sale->payments()->where('status', 'unpaid')->whereDate('due_date', '<', $today)->first();
                $upcomingPayment = $sale->payments()->where('status', 'unpaid')
                    ->whereDate('due_date', '>=', $today)
                    ->whereDate('due_date', '<=', $targetDate)
                    ->first();

                $statusTagihan = 'Aman';
                if ($overduePayment) {
                    $statusTagihan = 'Ada Tunggakan';
                } elseif ($upcomingPayment) {
                    $statusTagihan = 'Jatuh Tempo < 7 Hari';
                } elseif (($sale->outstanding_amount ?? 0) > 0) {
                    $statusTagihan = 'Aktif';
                }

                $bookingTs = $sale->booking_date ? $sale->booking_date->timestamp : 0;
                $estimasiDate = optional(optional($sale->booking_date)?->addMonths($sale->tenor_months));
                $estimasiTs = $estimasiDate ? $estimasiDate->timestamp : 0;

                $statusColor = 'info';
                if ($sale->status === 'paid_off')
                    $statusColor = 'success';
                elseif (in_array($sale->status, ['canceled', Sale::STATUS_CANCELED_HAPUS, Sale::STATUS_CANCELED_REFUND, Sale::STATUS_CANCELED_OPER_KREDIT]))
                    $statusColor = 'danger';

                $statusValMap = [
                    'canceled' => 0,
                    Sale::STATUS_CANCELED_HAPUS => 0,
                    Sale::STATUS_CANCELED_REFUND => 0,
                    Sale::STATUS_CANCELED_OPER_KREDIT => 0,
                    'active' => 1,
                    'paid_off' => 2
                ];
                $statusVal = $statusValMap[$sale->status] ?? 0;

                $statusLabel = 'Canceled';
                if ($sale->status === 'active')
                    $statusLabel = 'Active';
                elseif ($sale->status === 'paid_off')
                    $statusLabel = 'Paid Off';
                elseif ($sale->status === Sale::STATUS_CANCELED_HAPUS)
                    $statusLabel = 'Batal (Hapus)';
                elseif ($sale->status === Sale::STATUS_CANCELED_REFUND)
                    $statusLabel = 'Batal (Refund)';
                elseif ($sale->status === Sale::STATUS_CANCELED_OPER_KREDIT)
                    $statusLabel = 'Oper Kredit';

                $dpPayment = $sale->payments->where('note', 'Down Payment')->first();
                if ($sale->payment_method === 'cash') {
                    $statusDp = 'N/A';
                } elseif ($sale->down_payment <= 0) {
                    $statusDp = 'N/A';
                } elseif ($dpPayment && $dpPayment->status === 'paid') {
                    $statusDp = 'Lunas';
                } else {
                    $statusDp = 'Belum';
                }

                return [
                    'id' => $sale->id,
                    'kavling' => optional($sale->lot)->project?->name . ' / ' . optional($sale->lot)->block_number,
                    'pembeli' => optional($sale->buyer)->name,
                    'buyer_phone' => optional($sale->buyer)->phone,
                    'tgl_booking' => optional($sale->booking_date)?->format('d M Y'),
                    'tgl_booking_ts' => $bookingTs,
                    'metode_bayar' => $sale->payment_method === 'cash' ? 'Cash Keras' : ($sale->payment_method === 'kpr' ? 'KPR Bank' : 'Angsuran In-house'),
                    'harga_jual' => $sale->price,
                    'sisa_piutang' => $sale->outstanding_amount,
                    'status_dp' => $statusDp,
                    'status' => $statusLabel,
                    'estimasi_lunas' => $estimasiDate?->format('M Y'),
                    'estimasi_ts' => $estimasiTs,
                    'status_value' => $statusVal,
                    'marketing' => optional($sale->marketer)->name,
                    'status_color' => $statusColor,
                    'status_tagihan' => $statusTagihan,
                ];
            })->toArray();

            $sortBy = $filters['sort_by'];
            $sortDir = strtolower($filters['sort_dir']) === 'asc' ? 'asc' : 'desc';
            $penjualan = collect($penjualan)->sortBy(function ($item) use ($sortBy) {
                return match ($sortBy) {
                    'booking_date' => $item['tgl_booking_ts'] ?? 0,
                    'estimasi_lunas' => $item['estimasi_ts'] ?? 0,
                    'sisa_piutang' => $item['sisa_piutang'] ?? 0,
                    'status' => $item['status_value'] ?? 0,
                    default => $item['tgl_booking_ts'] ?? 0,
                };
            }, SORT_REGULAR, $sortDir === 'desc')->values()->all();

            if ($filters['kavling'] !== '') {
                $needle = mb_strtolower($filters['kavling']);
                $penjualan = array_values(array_filter($penjualan, function ($row) use ($needle) {
                    $label = mb_strtolower($row['kavling'] ?? '');
                    return stripos($label, $needle) !== false;
                }));
            }

            $marketers = Marketer::all();

            if ($request->wantsJson()) {
                $kavlingSuggestions = collect($penjualan)->pluck('kavling')->filter()->unique()->take(12)->values();
                $pembeliSuggestions = collect($penjualan)->pluck('pembeli')->filter()->unique()->take(12)->values();
                return response()->json([
                    'data' => $penjualan,
                    'suggestions' => [
                        'kavling' => $kavlingSuggestions,
                        'pembeli' => $pembeliSuggestions,
                    ],
                ]);
            }

            return view('penjualan.index', [
                'filters' => $filters,
                'penjualan' => $penjualan,
                'marketers' => $marketers,
            ]);
        }, 'dashboard');
    }

    public function show(string $id)
    {
        return $this->safeExecute(function () use ($id) {
            $sale = Sale::with(['lot.project', 'buyer', 'marketer', 'payments'])->findOrFail($id);
            $companyProfile = CompanyProfile::first();

            if (!$companyProfile || !$companyProfile->name) {
                return redirect()->route('profile.index')
                    ->with('error', 'Silahkan isi Profil Perusahaan terlebih dahulu sebelum memulai.');
            }

            $invoiceFormat = $companyProfile->invoice_format ?? 'INV/{YYYY}/{MM}/{####}';
            $receiptFormat = $companyProfile->receipt_format ?? 'KW/{YYYY}/{MM}/{####}';
            $booking = $sale->booking_date ?? now();
            $invoiceNumber = $this->formatDocumentNumber($invoiceFormat, $sale, $booking);
            $receiptNumber = $this->formatDocumentNumber($receiptFormat, $sale, $booking);

            $dpAmount = (int) ($sale->down_payment ?? 0);
            $dpPayment = $sale->payments->where('note', 'Down Payment')->first();
            $dpPaid = $dpPayment && $dpPayment->status === 'paid' ? $dpPayment->amount : 0;
            $dpRemaining = max(0, $dpAmount - $dpPaid);
            $dpStatus = $dpAmount > 0 ? ($dpRemaining > 0 ? 'unpaid' : 'paid') : null;

            $sale->load('payments');
            $cashPayment = $sale->payments->where('note', 'Pembayaran Cash Keras')->first();
            $cashAmount = $cashPayment ? (int) $cashPayment->amount : 0;
            $cashStatus = $cashPayment ? $cashPayment->status : null;
            $cashPaymentId = $cashPayment ? $cashPayment->id : null;
            $flexiblePaid = $sale->payments->where('status', 'paid')
                ->whereNotIn('note', ['Pembayaran Cash Keras', 'Pelunasan Cash Keras', 'Booking Fee', 'Down Payment'])
                ->sum('amount');
            $cashRemaining = max(0, $cashAmount - $flexiblePaid);

            $bfPayment = $sale->payments->where('note', 'Booking Fee')->first();
            $bfAmount = $bfPayment ? (int) $bfPayment->amount : 0;
            $bfPaid = $bfPayment && $bfPayment->status === 'paid' ? $bfAmount : 0;
            $bfRemaining = max(0, $bfAmount - $bfPaid);
            $bfStatus = $bfAmount > 0 ? ($bfRemaining > 0 ? 'unpaid' : 'paid') : null;

            $penjualan = [
                'id' => $sale->id,
                'invoice' => $invoiceNumber,
                'kavling' => optional($sale->lot)->project?->name . ' / ' . optional($sale->lot)->block_number,
                'pembeli' => optional($sale->buyer)->name,
                'buyer_phone' => optional($sale->buyer)->phone,
                'tgl_booking' => optional($sale->booking_date)?->format('d M Y'),
                'metode_bayar' => $sale->payment_method === 'cash' ? 'Cash Keras' : ($sale->payment_method === 'kpr' ? 'KPR Bank' : 'Angsuran In-house'),
                'marketing' => optional($sale->marketer)->name,
                'harga_jual' => $sale->price,
                'total_terbayar' => $sale->paid_amount,
                'sisa_piutang' => $sale->outstanding_amount,
                'tenor' => $sale->tenor_months,
                'tgl_jatuh_tempo' => $sale->due_day,
                'dp_amount' => $dpAmount,
                'dp_paid' => $dpPaid,
                'dp_remaining' => $dpRemaining + ($bfStatus === 'unpaid' ? $bfRemaining : 0),
                'dp_status' => $dpStatus,
                'dp_payment_id' => $dpPayment?->id,
                'bf_amount' => $bfAmount,
                'bf_paid' => $bfPaid,
                'bf_remaining' => $bfRemaining,
                'bf_status' => $bfStatus,
                'bf_payment_id' => $bfPayment ? $bfPayment->id : null,
                'cash_amount' => $cashAmount,
                'cash_status' => $cashStatus,
                'cash_payment_id' => $cashPaymentId,
                'cash_remaining' => $cashRemaining + ($bfStatus === 'unpaid' ? $bfRemaining : 0),
                'cash_flexible_paid' => $flexiblePaid,
                'company' => [
                    'nama' => $companyProfile->name ?? 'Nama Perusahaan',
                    'alamat' => $companyProfile->address ?? 'Alamat Belum Diatur',
                    'telepon' => $companyProfile->phone ?? '-',
                    'email' => $companyProfile->email ?? '-',
                    'logo_url' => $companyProfile->logo_path ? asset('storage/' . $companyProfile->logo_path) : null,
                ],
                'schedule' => $sale->payments->sortBy(function ($p) {
                    return $p->due_date ? $p->due_date->timestamp : 0;
                })->values()->map(function ($p, $index) {
                    return [
                        'no' => $index + 1, // Change from $p->id to sequential number
                        'id' => $p->id,     // Keep ID if needed elsewhere
                        'jatuh_tempo' => optional($p->due_date)?->format('d M Y'),
                        'jumlah' => $p->amount,
                        'status' => $p->status,
                    ];
                })->toArray(),
                'payments' => $sale->payments->sortBy(function ($p) {
                    return $p->due_date ? $p->due_date->timestamp : 0;
                })->values()->map(function ($p) {
                    return [
                        'tanggal' => optional($p->due_date)?->format('d M Y'),
                        'keterangan' => $p->note ?? 'Pembayaran',
                        'jumlah' => $p->amount,
                    ];
                })->toArray(),
            ];

            $buyers = Buyer::all();
            $marketers = Marketer::all();

            return view('penjualan.show', compact('penjualan', 'sale', 'receiptNumber', 'buyers', 'marketers'));
        }, 'penjualan.index');
    }

    public function create(Request $request)
    {
        return $this->safeExecute(function () use ($request) {
            $lots = $this->availableLots();
            $buyers = Buyer::all();
            $marketers = Marketer::all();
            $parentSaleId = $request->query('from_sale');
            $buyerId = $request->query('buyer_id');
            return view('penjualan.create', compact('lots', 'buyers', 'marketers', 'parentSaleId', 'buyerId'));
        }, 'penjualan.index');
    }

    public function store(SaleRequest $request)
    {
        return $this->safeExecute(function () use ($request) {
            $data = $request->validated();
            $base = (int) ($data['base_price'] ?? 0);
            $discount = (int) ($data['discount'] ?? 0);
            $ppjb = (int) ($data['extra_ppjb'] ?? 0);
            $shm = (int) ($data['extra_shm'] ?? 0);
            $other = (int) ($data['extra_other'] ?? 0);
            $bookingFee = (int) ($data['booking_fee'] ?? 0);
            $includeBookingFee = $request->has('booking_fee_included') && $request->input('booking_fee_included');

            $netPrice = max(0, $base - $discount);
            $grandTotal = $netPrice + $ppjb + $shm + $other + ($includeBookingFee ? 0 : $bookingFee);
            $salePrice = $netPrice + $ppjb + $shm + $other + ($includeBookingFee ? 0 : $bookingFee);
            $data['price'] = $salePrice;

            if ($data['payment_method'] === 'cash') {
                $data['down_payment'] = 0;
                $data['tenor_months'] = 0;
                $data['due_day'] = null;
                $data['paid_amount'] = 0;
                $data['outstanding_amount'] = $grandTotal;
                $data['status'] = 'active';
                $sale = Sale::create($data);
                $this->syncBookingFeePayment($sale, $bookingFee);
                $sale->payments()->create([
                    'due_date' => $sale->booking_date ?? now(),
                    'amount' => max(0, $grandTotal - $bookingFee),
                    'status' => 'unpaid',
                    'note' => "Pembayaran Cash Keras",
                    'paid_at' => null,
                ]);
                if ($sale->lot) {
                    $sale->lot->update(['status' => 'sold']);
                }
                return redirect()->route('penjualan.index')->with('success', 'Penjualan ditambahkan');
            }

            if ($data['payment_method'] === 'kpr') {
                $dpPercent = (float) ($data['dp_percent'] ?? 0);
                $dpInput = (int) ($data['down_payment'] ?? 0);
                $dp = $dpInput > 0 ? $dpInput : (int) round($grandTotal * ($dpPercent / 100));
                $data['down_payment'] = $dp;
                $data['tenor_months'] = (int) ($data['tenor_months'] ?? 0);
                $data['due_day'] = max(1, min(28, (int) ($data['due_day'] ?? 1)));
                $data['paid_amount'] = 0;
                $data['outstanding_amount'] = $grandTotal;
                $data['status'] = 'active';
                $sale = Sale::create($data);
                $this->syncBookingFeePayment($sale, $bookingFee);
                if ($data['tenor_months'] > 0) {
                    $this->rebuildSchedule($sale);
                }
                $this->syncDownPaymentHistory($sale);
                if ($sale->lot) {
                    $sale->lot->update(['status' => 'sold']);
                }
                return redirect()->route('penjualan.index')->with('success', 'Penjualan ditambahkan');
            }

            $tenor = (int) ($data['tenor_months'] ?? 0);
            $dueDay = max(1, min(28, (int) ($data['due_day'] ?? 1)));
            $dpPercent = (float) ($data['dp_percent'] ?? 0);
            $dpInput = (int) ($data['down_payment'] ?? 0);
            $dp = $dpInput > 0 ? $dpInput : (int) round($grandTotal * ($dpPercent / 100));

            if ($dp >= $grandTotal || $dpPercent >= 100) {
                $data['down_payment'] = $grandTotal;
                $data['tenor_months'] = 0;
                $data['due_day'] = null;
                $data['paid_amount'] = $grandTotal;
                $data['outstanding_amount'] = 0;
                $data['status'] = 'paid_off';
                $sale = Sale::create($data);
                $this->syncBookingFeePayment($sale, $bookingFee);
                $sale->payments()->create([
                    'due_date' => $sale->booking_date ?? now(),
                    'amount' => max(0, $grandTotal - $bookingFee),
                    'status' => 'paid',
                    'note' => 'Down Payment (100%)',
                    'paid_at' => $sale->booking_date ?? now(),
                ]);
                if ($sale->lot) {
                    $sale->lot->update(['status' => 'sold']);
                }
                return redirect()->route('penjualan.index')->with('success', 'Penjualan ditambahkan');
            }

            $data['down_payment'] = $dp;
            $data['tenor_months'] = $tenor;
            $data['due_day'] = $dueDay;
            $data['paid_amount'] = 0;
            $data['outstanding_amount'] = $grandTotal;
            $data['status'] = 'active';
            $sale = Sale::create($data);
            $this->syncBookingFeePayment($sale, $bookingFee);
            $this->rebuildSchedule($sale);
            $this->syncDownPaymentHistory($sale);
            if ($sale->lot) {
                $sale->lot->update(['status' => 'sold']);
            }
            return redirect()->route('penjualan.index')->with('success', 'Penjualan ditambahkan');
        }, 'penjualan.index');
    }

    public function destroy(Sale $penjualan)
    {
        return $this->safeExecute(function () use ($penjualan) {
            $penjualan->delete();
            return redirect()->route('penjualan.index')->with('success', 'Penjualan dihapus');
        }, 'penjualan.index');
    }

    public function edit(Sale $penjualan)
    {
        return $this->safeExecute(function () use ($penjualan) {
            $lots = $this->availableLots($penjualan);
            $buyers = Buyer::all();
            $marketers = Marketer::all();
            return view('penjualan.edit', [
                'sale' => $penjualan,
                'lots' => $lots,
                'buyers' => $buyers,
                'marketers' => $marketers,
            ]);
        }, 'penjualan.index');
    }

    public function update(SaleRequest $request, Sale $penjualan)
    {
        return $this->safeExecute(function () use ($request, $penjualan) {
            if ($request->has('notes_only')) {
                $penjualan->notes = $request->input('notes');
                $penjualan->save();
                return redirect()->route('penjualan.show', $penjualan)->with('success', 'Catatan berhasil diperbarui');
            }

            $data = $request->validated();
            $base = (int) ($data['base_price'] ?? 0);
            $discount = (int) ($data['discount'] ?? 0);
            $ppjb = (int) ($data['extra_ppjb'] ?? 0);
            $shm = (int) ($data['extra_shm'] ?? 0);
            $other = (int) ($data['extra_other'] ?? 0);
            $bookingFee = (int) ($data['booking_fee'] ?? 0);
            $includeBookingFee = $request->has('booking_fee_included') && $request->input('booking_fee_included');

            $netPrice = max(0, $base - $discount);
            $grandTotal = $netPrice + $ppjb + $shm + $other + ($includeBookingFee ? 0 : $bookingFee);
            $salePrice = $netPrice + $ppjb + $shm + $other + ($includeBookingFee ? 0 : $bookingFee);
            $data['price'] = $salePrice;

            if ($data['payment_method'] === 'cash') {
                $data['down_payment'] = 0;
                $data['tenor_months'] = 0;
                $data['due_day'] = null;
                $data['paid_amount'] = 0;
                $data['outstanding_amount'] = $salePrice;
                $data['status'] = 'active';
                $penjualan->update($data);
                $penjualan->payments()->delete();
                $this->syncBookingFeePayment($penjualan, $bookingFee);
                $penjualan->payments()->create([
                    'due_date' => $penjualan->booking_date ?? now(),
                    'amount' => max(0, $salePrice - $bookingFee),
                    'status' => 'unpaid',
                    'note' => "Pembayaran Cash Keras",
                    'paid_at' => null,
                ]);
                return redirect()->route('penjualan.index')->with('success', 'Penjualan diperbarui');
            }

            if ($data['payment_method'] === 'kpr') {
                $dpPercent = (float) ($data['dp_percent'] ?? 0);
                $dpInput = (int) ($data['down_payment'] ?? 0);
                $dp = $dpInput > 0 ? $dpInput : (int) round($salePrice * ($dpPercent / 100));
                $data['down_payment'] = $dp;
                $data['tenor_months'] = 0;
                $data['due_day'] = null;

                if ($dp <= 0) {
                    $data['paid_amount'] = $salePrice;
                    $data['outstanding_amount'] = 0;
                    $data['status'] = 'paid_off';
                    $penjualan->update($data);
                    $penjualan->payments()->delete();
                    $this->syncBookingFeePayment($penjualan, $bookingFee);
                    $penjualan->payments()->create([
                        'due_date' => $penjualan->booking_date ?? now(),
                        'amount' => max(0, $salePrice - $bookingFee),
                        'status' => 'paid',
                        'note' => "Pembayaran Penuh (KPR Bank)",
                        'paid_at' => $penjualan->booking_date ?? now(),
                    ]);
                } else {
                    $data['paid_amount'] = max(0, $salePrice - $dp);
                    $data['outstanding_amount'] = $dp;
                    $data['status'] = 'active';
                    $penjualan->update($data);
                    $this->syncBookingFeePayment($penjualan, $bookingFee);
                    $this->syncDownPaymentHistory($penjualan);
                }
                return redirect()->route('penjualan.index')->with('success', 'Penjualan diperbarui');
            }

            $tenor = (int) ($data['tenor_months'] ?? 0);
            $dueDay = max(1, min(28, (int) ($data['due_day'] ?? 1)));
            $dpPercent = (float) ($data['dp_percent'] ?? 0);
            $dpInput = (int) ($data['down_payment'] ?? 0);
            $dp = $dpInput > 0 ? $dpInput : (int) round($salePrice * ($dpPercent / 100));

            if ($dp >= $salePrice || $dpPercent >= 100) {
                $data['down_payment'] = $salePrice;
                $data['tenor_months'] = 0;
                $data['due_day'] = null;
                $data['paid_amount'] = $salePrice;
                $data['outstanding_amount'] = 0;
                $data['status'] = 'paid_off';
                $penjualan->update($data);
                $penjualan->payments()->delete();
                $this->syncBookingFeePayment($penjualan, $bookingFee);
                $penjualan->payments()->create([
                    'due_date' => $penjualan->booking_date ?? now(),
                    'amount' => max(0, $salePrice - $bookingFee),
                    'status' => 'paid',
                    'note' => 'Down Payment (100%)',
                    'paid_at' => $penjualan->booking_date ?? now(),
                ]);
                return redirect()->route('penjualan.index')->with('success', 'Penjualan diperbarui');
            }

            $outstandingFromSchedule = $penjualan->payments()
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->sum('amount');
            $paidSum = $penjualan->payments()->where('status', 'paid')->sum('amount');
            $dpBuffer = $penjualan->payments()->where('note', 'Down Payment')->exists() ? 0 : $dp;

            if ($outstandingFromSchedule > 0) {
                $paidAmount = max(0, $salePrice - $outstandingFromSchedule);
                $outstanding = $outstandingFromSchedule;
            } else {
                $paidAmount = min($salePrice, $dpBuffer + $paidSum);
                $outstanding = max(0, $salePrice - $paidAmount);
            }

            $data['down_payment'] = $dp;
            $data['tenor_months'] = $tenor;
            $data['due_day'] = $dueDay;
            $data['paid_amount'] = $paidAmount;
            $data['outstanding_amount'] = $outstanding;
            $data['status'] = $outstanding > 0 ? 'active' : 'paid_off';
            $penjualan->update($data);
            $this->syncBookingFeePayment($penjualan, $bookingFee);
            $this->rebuildSchedule($penjualan);
            $this->syncDownPaymentHistory($penjualan);
            return redirect()->route('penjualan.index')->with('success', 'Penjualan diperbarui');
        }, 'penjualan.index');
    }

    private function formatDocumentNumber(string $format, Sale $sale, Carbon $date): string
    {
        $replaced = str_replace(
            ['{YYYY}', '{MM}', '{DD}', '{####}'],
            [
                $date->format('Y'),
                $date->format('m'),
                $date->format('d'),
                str_pad((string) $sale->id, 4, '0', STR_PAD_LEFT)
            ],
            $format
        );
        return $replaced;
    }

    private function rebuildSchedule(Sale $sale): void
    {
        $price = (int) $sale->price;
        $dp = (int) $sale->down_payment;
        $paidInstallments = $sale->payments()
            ->where('status', 'paid')
            ->where(function ($q) {
                $q->whereNull('note')->orWhere('note', 'like', 'Angsuran%');
            })
            ->sum('amount');
        $bookingFee = $sale->payments()->where('note', 'Booking Fee')->sum('amount');
        $outstandingForSchedule = max(0, $price - $dp - $paidInstallments - $bookingFee);

        if ($outstandingForSchedule <= 0 || ($sale->tenor_months ?? 0) <= 0) {
            $sale->payments()
                ->where('status', 'unpaid')
                ->where('note', 'like', 'Angsuran%')
                ->delete();
            return;
        }

        $paid = $sale->payments()
            ->where('status', 'paid')
            ->where(function ($q) {
                $q->whereNull('note')->orWhere('note', 'like', 'Angsuran%');
            })
            ->orderBy('due_date')
            ->get();
        $paidCount = $paid->count();
        $remainingTenor = max(1, (int) $sale->tenor_months - $paidCount);

        $baseDate = $paid->last()?->due_date ?? ($sale->booking_date ?? Carbon::now());
        $day = max(1, min(28, (int) ($sale->due_day ?? ($baseDate instanceof Carbon ? $baseDate->day : 1))));
        $startDate = ($baseDate instanceof Carbon ? $baseDate->copy() : Carbon::parse($baseDate ?? now()))->day($day);

        if ($paid->last()) {
            $startDate->addMonth();
        } else {
            $startDate->addMonth();
        }

        $statusesToDelete = ['unpaid'];
        if ($sale->payment_method === 'kpr') {
            $statusesToDelete[] = 'kpr_bank';
        }
        $sale->payments()
            ->whereIn('status', $statusesToDelete)
            ->where(function ($q) {
                $q->where('note', 'like', 'Angsuran%')->orWhereNull('note');
            })
            ->delete();

        $perTerm = intdiv($outstandingForSchedule, $remainingTenor);
        $remainder = $outstandingForSchedule - ($perTerm * $remainingTenor);
        $status = $sale->payment_method === 'kpr' ? 'kpr_bank' : 'unpaid';
        $notePrefix = $sale->payment_method === 'kpr' ? 'Angsuran Bank ke-' : 'Angsuran ke-';

        for ($i = 0; $i < $remainingTenor; $i++) {
            $amount = $perTerm + ($i < $remainder ? 1 : 0);
            $dueDate = $startDate->copy()->addMonths($i);
            $sale->payments()->create([
                'due_date' => $dueDate,
                'amount' => $amount,
                'status' => $status,
                'note' => $notePrefix . ($paidCount + $i + 1),
            ]);
        }
    }

    private function syncBookingFeePayment(Sale $sale, int $amount): void
    {
        $payment = $sale->payments()->where('note', 'Booking Fee')->first();
        if ($amount > 0) {
            if (!$payment) {
                $sale->payments()->create([
                    'due_date' => $sale->booking_date ?? now(),
                    'amount' => $amount,
                    'status' => 'unpaid',
                    'note' => 'Booking Fee',
                    'paid_at' => null,
                ]);
            } else {
                $payment->update(['amount' => $amount]);
            }
        } elseif ($payment) {
            $payment->delete();
        }
    }

    private function syncDownPaymentHistory(Sale $sale): void
    {
        $dpAmount = max(0, (int) $sale->down_payment);
        $dpPayment = $sale->payments()->where('note', 'Down Payment')->first();

        if ($dpAmount > 0) {
            if (!$dpPayment) {
                $dpPayment = $sale->payments()->create([
                    'due_date' => $sale->booking_date ?? now(),
                    'amount' => $dpAmount,
                    'status' => 'unpaid',
                    'note' => 'Down Payment',
                    'paid_at' => null,
                ]);
            } else {
                $dpPayment->update([
                    'amount' => $dpAmount,
                    'due_date' => $sale->booking_date ?? $dpPayment->due_date ?? now(),
                    'note' => 'Down Payment',
                ]);
            }
        } elseif ($dpPayment) {
            $dpPayment->delete();
        }

        $outstandingFromSchedule = $sale->payments()
            ->whereIn('status', ['unpaid', 'partial', 'overdue', 'kpr_bank'])
            ->sum('amount');
        $paidSum = $sale->payments()->where('status', 'paid')->sum('amount');

        $sale->paid_amount = min($sale->price, $paidSum);
        $sale->outstanding_amount = max(0, $sale->price - $sale->paid_amount);

        if ($sale->outstanding_amount <= 0) {
            $sale->status = 'paid_off';
        } else {
            if ($sale->status === 'paid_off') {
                $sale->status = 'active';
            }
            if (!in_array($sale->status, ['canceled', Sale::STATUS_CANCELED_HAPUS, Sale::STATUS_CANCELED_REFUND, Sale::STATUS_CANCELED_OPER_KREDIT])) {
                $sale->status = 'active';
            }
        }
        $sale->save();
    }

    public function approveKpr(Sale $sale)
    {
        return $this->safeExecute(function () use ($sale) {
            if ($sale->payment_method !== 'kpr') {
                return back()->with('error', 'Hanya untuk penjualan KPR');
            }

            $sale->refresh();
            $totalPaid = $sale->payments()->where('status', 'paid')->sum('amount');
            $price = $sale->price;
            $remaining = max(0, $price - $totalPaid);

            if ($remaining > 0) {
                $sale->payments()->create([
                    'due_date' => now(),
                    'amount' => $remaining,
                    'status' => 'paid',
                    'note' => 'Pencairan KPR Bank',
                    'paid_at' => now(),
                ]);
            }

            $sale->payments()->where('status', 'kpr_bank')->delete();
            $this->syncDownPaymentHistory($sale);

            return back()->with('success', 'KPR Disetujui. Pembayaran Bank tercatat.');
        }, 'penjualan.index');
    }

    public function payOffCash(Sale $sale)
    {
        return $this->safeExecute(function () use ($sale) {
            if ($sale->payment_method !== 'cash') {
                return back()->with('error', 'Hanya untuk penjualan Cash Keras');
            }

            $bfPayment = $sale->payments()->where('note', 'Booking Fee')->where('status', 'unpaid')->first();
            if ($bfPayment) {
                $bfPayment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            $cashPayment = $sale->payments()->where('note', 'Pembayaran Cash Keras')->where('status', 'unpaid')->first();
            if (!$cashPayment) {
                return back()->with('error', 'Tidak ada pembayaran Cash Keras yang belum lunas');
            }

            $flexiblePaid = $sale->payments()
                ->where('status', 'paid')
                ->whereNotIn('note', ['Pembayaran Cash Keras', 'Pelunasan Cash Keras', 'Booking Fee', 'Down Payment'])
                ->sum('amount');
            $remaining = max(0, $cashPayment->amount - $flexiblePaid);

            if ($remaining > 0) {
                $sale->payments()->create([
                    'due_date' => now(),
                    'amount' => $remaining,
                    'status' => 'paid',
                    'note' => 'Pelunasan Cash Keras',
                    'paid_at' => now(),
                ]);
            }

            $cashPayment->delete();
            $this->syncDownPaymentHistory($sale);

            return back()->with('success', 'Penjualan Cash Keras berhasil dilunasi.');
        }, 'penjualan.index');
    }
}