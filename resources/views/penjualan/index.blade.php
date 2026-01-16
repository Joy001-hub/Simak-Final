@extends('layouts.app')

@section('content')
    <div class="page-heading" style="margin-bottom:24px;">
        <div>
            <h2 style="font-size:20px; font-weight:700; color:#1E293B; margin:0;">Penjualan</h2>
            <p style="font-size:12px; color:#64748B; margin:4px 0 0 0;">Kelola data penjualan kavling</p>
        </div>

        <a href="{{ route('penjualan.create') }}" class="chip is-active"
            style="display:inline-flex; align-items:center; gap:8px;">
            <span style="font-size:18px; line-height:0.9;">+</span> Tambah Penjualan
        </a>
    </div>

    <div class="card" style="padding:16px; margin-bottom:16px;">
        <form method="GET" action="{{ route('penjualan.index') }}" id="salesFilterForm" autocomplete="off">
            <div class="grid-3" style="gap:12px; display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));">
                <div class="field">
                    <label class="hint">Nama Kavling</label>
                    <input list="kavlingSuggestions" type="text" class="input" name="kavling"
                        value="{{ $filters['kavling'] ?? '' }}" placeholder="Cari nama proyek...">
                    <datalist id="kavlingSuggestions"></datalist>
                </div>
                <div class="field">
                    <label class="hint">Nama Pembeli</label>
                    <input list="pembeliSuggestions" type="text" class="input" name="pembeli"
                        value="{{ $filters['pembeli'] ?? '' }}" placeholder="Cari nama pembeli...">
                    <datalist id="pembeliSuggestions"></datalist>
                </div>
                <div class="field">
                    <label class="hint">Rentang Harga (min)</label>
                    <input type="number" class="input" name="harga_min" value="{{ $filters['harga_min'] ?? '' }}"
                        placeholder="contoh: 500000000">
                </div>
                <div class="field">
                    <label class="hint">Rentang Harga (max)</label>
                    <input type="number" class="input" name="harga_max" value="{{ $filters['harga_max'] ?? '' }}"
                        placeholder="contoh: 2000000000">
                </div>
                <div class="field">
                    <label class="hint">Tgl Booking (dari)</label>
                    <input type="date" class="input" name="tgl_booking_dari"
                        value="{{ $filters['tgl_booking_dari'] ?? '' }}">
                </div>
                <div class="field">
                    <label class="hint">Tgl Booking (sampai)</label>
                    <input type="date" class="input" name="tgl_booking_sampai"
                        value="{{ $filters['tgl_booking_sampai'] ?? '' }}">
                </div>
                <div class="field">
                    <label class="hint">Metode Bayar</label>
                    <select name="metode_bayar" class="input">
                        @foreach (['Semua', 'Cash Keras', 'Angsuran In-house', 'KPR Bank'] as $opt)
                            <option value="{{ $opt }}" @selected(($filters['metode_bayar'] ?? 'Semua') === $opt)>{{ $opt }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="hint">Status Tagihan</label>
                    <select name="status_tagihan" class="input">
                        @foreach (['Semua', 'Ada Tunggakan', 'Jatuh Tempo < 7 Hari', 'Aman'] as $opt)
                            <option value="{{ $opt }}" @selected(($filters['status_tagihan'] ?? 'Semua') === $opt)>{{ $opt }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="hint">Status DP</label>
                    <select name="status_dp" class="input">
                        @foreach (['Semua', 'Lunas', 'Belum'] as $opt)
                            <option value="{{ $opt }}" @selected(($filters['status_dp'] ?? 'Semua') === $opt)>{{ $opt }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="hint">Status Penjualan</label>
                    <select name="status_penjualan" class="input">
                        @foreach (['Semua', 'Paid Off', 'Active', 'Batal (Refund)'] as $opt)
                            <option value="{{ $opt }}" @selected(($filters['status_penjualan'] ?? 'Semua') === $opt)>
                                {{ $opt }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="hint">Marketing</label>
                    <select name="marketing" class="input">
                        <option value="Semua">Semua</option>
                        @foreach($marketers as $marketer)
                            <option value="{{ $marketer->id }}" @selected(($filters['marketing'] ?? 'Semua') == $marketer->id)>
                                {{ $marketer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div
                style="display:flex; justify-content:space-between; align-items:center; margin-top:12px; gap:12px; flex-wrap:wrap;">
                <div style="display:flex; gap:8px; align-items:center;">
                    <a href="{{ route('penjualan.index') }}" class="btn light">Reset Filter</a>
                    <button type="submit" class="btn primary" id="manualSearchBtn">Cari</button>
                </div>
                <div id="filterStatus" class="hint" style="min-width:140px; text-align:right;">&nbsp;</div>
            </div>
        </form>
    </div>

    <div class="card" style="padding: 0;">
        <div class="table-responsive">
            <table class="table-clean" style="min-width:1100px;">
                <thead>
                    <tr>
                        <th style="width:40px; text-align:center;"></th>
                        <th style="width:220px;">Kavling</th>
                        <th style="width:160px;">Pembeli</th>
                        <th style="width:140px;">
                            <a href="{{ route('penjualan.index', array_merge(request()->query(), ['sort_by' => 'booking_date', 'sort_dir' => ($filters['sort_by'] === 'booking_date' && ($filters['sort_dir'] ?? 'desc') === 'asc') ? 'desc' : 'asc'])) }}"
                                class="inline-flex items-center gap-1 text-gray-700">
                                Tgl. Booking
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:#9ca3af;">
                                    <path d="M7 15l5 5 5-5" />
                                    <path d="M7 9l5-5 5 5" />
                                </svg>
                            </a>
                        </th>
                        <th style="width:140px;">Metode Bayar</th>
                        <th style="width:140px;">
                            <a href="{{ route('penjualan.index', array_merge(request()->query(), ['sort_by' => 'price', 'sort_dir' => ($filters['sort_by'] === 'price' && ($filters['sort_dir'] ?? 'desc') === 'asc') ? 'desc' : 'asc'])) }}"
                                class="inline-flex items-center gap-1 text-gray-700">
                                Harga Jual
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:#9ca3af;">
                                    <path d="M7 15l5 5 5-5" />
                                    <path d="M7 9l5-5 5 5" />
                                </svg>
                            </a>
                        </th>
                        <th style="width:140px;">
                            <a href="{{ route('penjualan.index', array_merge(request()->query(), ['sort_by' => 'sisa_piutang', 'sort_dir' => ($filters['sort_by'] === 'sisa_piutang' && ($filters['sort_dir'] ?? 'desc') === 'asc') ? 'desc' : 'asc'])) }}"
                                class="inline-flex items-center gap-1 text-gray-700">
                                Sisa Piutang
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:#9ca3af;">
                                    <path d="M7 15l5 5 5-5" />
                                    <path d="M7 9l5-5 5 5" />
                                </svg>
                            </a>
                        </th>
                        <th style="width:120px;">Status DP</th>
                        <th style="width:120px;">
                            <a href="{{ route('penjualan.index', array_merge(request()->query(), ['sort_by' => 'status', 'sort_dir' => ($filters['sort_by'] === 'status' && ($filters['sort_dir'] ?? 'desc') === 'asc') ? 'desc' : 'asc'])) }}"
                                class="inline-flex items-center gap-1 text-gray-700">
                                Status
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:#9ca3af;">
                                    <path d="M7 15l5 5 5-5" />
                                    <path d="M7 9l5-5 5 5" />
                                </svg>
                            </a>
                        </th>
                        <th style="width:140px;">
                            <a href="{{ route('penjualan.index', array_merge(request()->query(), ['sort_by' => 'estimasi_lunas', 'sort_dir' => ($filters['sort_by'] === 'estimasi_lunas' && ($filters['sort_dir'] ?? 'desc') === 'asc') ? 'desc' : 'asc'])) }}"
                                class="inline-flex items-center gap-1 text-gray-700">
                                Estimasi Lunas
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:#9ca3af;">
                                    <path d="M7 15l5 5 5-5" />
                                    <path d="M7 9l5-5 5 5" />
                                </svg>
                            </a>
                        </th>
                        <th style="width:140px;">Marketing</th>
                        <th style="text-align:left; width:120px; padding-left:14px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="salesTableBody">
                    @forelse ($penjualan as $item)
                        @php
                            $statusTagihan = $item['status_tagihan'] ?? 'Aman';
                            $icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block; vertical-align:middle;"><circle cx="12" cy="12" r="10" fill="#22c55e"/><path d="M7.5 12L10.5 15L16.5 9" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                            if ($statusTagihan === 'Ada Tunggakan')
                                $icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block; vertical-align:middle;"><circle cx="12" cy="12" r="10" fill="#ef4444"/><path d="M12 7V13" stroke="white" stroke-width="2.5" stroke-linecap="round"/><circle cx="12" cy="16.5" r="1.5" fill="white"/></svg>';
                            if ($statusTagihan === 'Jatuh Tempo < 7 Hari')
                                $icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block; vertical-align:middle;"><circle cx="12" cy="12" r="10" fill="#f59e0b"/><path d="M12 7V12L15 15" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                            $dpStatus = $item['status_dp'] ?? 'N/A';
                            if ($dpStatus === 'Lunas') {
                                $dpColor = '#16a34a';
                                $dpBg = 'rgba(22,163,74,0.12)';
                            } elseif ($dpStatus === 'Belum') {
                                $dpColor = '#ca8a04';
                                $dpBg = 'rgba(234,179,8,0.15)';
                            } else {
                                $dpColor = '#6b7280';
                                $dpBg = 'rgba(107,114,128,0.12)';
                            }
                            $saleStatus = $item['status'] ?? '';
                            $statusBg = $saleStatus === 'Paid Off' ? 'rgba(16,185,129,0.15)' : ($saleStatus === 'Active' ? 'rgba(59,130,246,0.15)' : 'rgba(107,114,128,0.15)');
                            $statusColor = $saleStatus === 'Paid Off' ? '#0f9d58' : ($saleStatus === 'Active' ? '#2563eb' : '#6b7280');
                        @endphp
                        <tr>
                            <td style="text-align:center; white-space:nowrap;" title="{{ $statusTagihan }}">{!! $icon !!}
                            </td>
                            <td style="font-weight:700; color:#0f172a; white-space:nowrap;">{{ $item['kavling'] }}</td>
                            <td style="white-space:nowrap;">{{ $item['pembeli'] }}</td>
                            <td style="white-space:nowrap;">{{ $item['tgl_booking'] }}</td>
                            <td style="white-space:nowrap;">{{ $item['metode_bayar'] }}</td>
                            <td style="white-space:nowrap;">Rp {{ number_format($item['harga_jual'], 0, ',', '.') }}</td>
                            <td
                                style="color:{{ ($item['sisa_piutang'] ?? 0) > 0 ? '#b4232a' : '#0f172a' }}; font-weight:700; white-space:nowrap;">
                                Rp {{ number_format($item['sisa_piutang'] ?? 0, 0, ',', '.') }}
                            </td>
                            <td>
                                <span
                                    style="display:inline-block; padding:4px 10px; border-radius:999px; background:{{ $dpBg }}; color:{{ $dpColor }}; font-weight:700; white-space:nowrap;">
                                    {{ $dpStatus }}
                                </span>
                            </td>
                            <td>
                                <span
                                    style="display:inline-block; padding:4px 10px; border-radius:999px; background:{{ $statusBg }}; color:{{ $statusColor }}; font-weight:700; white-space:nowrap;">
                                    {{ $saleStatus === 'Paid Off' ? '🤝 ' : '' }}{{ $saleStatus ?: '-' }}
                                </span>
                            </td>
                            <td style="white-space:nowrap;">{{ $item['estimasi_lunas'] ?? '-' }}</td>
                            <td style="white-space:nowrap;">{{ $item['marketing'] ?? '-' }}</td>
                            <td style="padding-left:14px; white-space: nowrap;">
                                <a href="{{ route('penjualan.show', $item['id']) }}" class="btn light"
                                    style="padding:8px 10px; border-color:#e5e7eb;">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" style="text-align:center; padding:18px;">Belum ada data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('salesFilterForm');
            const tableBody = document.getElementById('salesTableBody');
            const statusBox = document.getElementById('filterStatus');
            const manualSearchBtn = document.getElementById('manualSearchBtn');
            const kavlingList = document.getElementById('kavlingSuggestions');
            const pembeliList = document.getElementById('pembeliSuggestions');
            const initialData = @json($penjualan);

            const debounce = (fn, delay = 350) => {
                let t;
                return (...args) => {
                    clearTimeout(t);
                    t = setTimeout(() => fn(...args), delay);
                };
            };

            const renderRows = (rows) => {
                if (!rows || rows.length === 0) {
                    return `<tr><td colspan="12" style="text-align:center; padding:18px;">Belum ada data</td></tr>`;
                }
                return rows.map(item => {
                    const outstandingRed = (item.sisa_piutang ?? 0) > 0 ? '#b4232a' : '#0f172a';
                    const harga = Number(item.harga_jual || 0).toLocaleString('id-ID');
                    const sisa = Number(item.sisa_piutang || 0).toLocaleString('id-ID');
                    const statusTagihan = item.status_tagihan || 'Aman';
                    let icon = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block; vertical-align:middle;"><circle cx="12" cy="12" r="10" fill="#22c55e"/><path d="M7.5 12L10.5 15L16.5 9" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
                    if (statusTagihan === 'Ada Tunggakan') icon = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block; vertical-align:middle;"><circle cx="12" cy="12" r="10" fill="#ef4444"/><path d="M12 7V13" stroke="white" stroke-width="2.5" stroke-linecap="round"/><circle cx="12" cy="16.5" r="1.5" fill="white"/></svg>`;
                    if (statusTagihan === 'Jatuh Tempo < 7 Hari') icon = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block; vertical-align:middle;"><circle cx="12" cy="12" r="10" fill="#f59e0b"/><path d="M12 7V12L15 15" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
                    const dpStatus = item.status_dp || 'N/A';
                    let dpBg = 'rgba(107,114,128,0.12)';
                    let dpColor = '#6b7280';
                    if (dpStatus === 'Lunas') {
                        dpBg = 'rgba(22,163,74,0.12)';
                        dpColor = '#16a34a';
                    } else if (dpStatus === 'Belum') {
                        dpBg = 'rgba(234,179,8,0.15)';
                        dpColor = '#ca8a04';
                    }
                    const saleStatus = item.status || '';
                    let statusBg = 'rgba(107,114,128,0.15)';
                    let statusColor = '#6b7280';

                    if (saleStatus === 'Paid Off') {
                        statusBg = 'rgba(16,185,129,0.15)';
                        statusColor = '#0f9d58';
                    } else if (saleStatus === 'Active') {
                        statusBg = 'rgba(59,130,246,0.15)';
                        statusColor = '#2563eb';
                    } else if (saleStatus.includes('Batal')) {
                        statusBg = 'rgba(239, 68, 68, 0.15)';
                        statusColor = '#b91c1c';
                    } else if (saleStatus === 'Oper Kredit') {
                        statusBg = 'rgba(245, 158, 11, 0.15)';
                        statusColor = '#b45309';
                    }
                    return `<tr>
                                                                                <td style="text-align:center; white-space:nowrap;" title="${statusTagihan}">${icon}</td>
                                                                                <td style="font-weight:700; color:#0f172a; white-space:nowrap;">${item.kavling ?? '-'}</td>
                                                                                <td style="white-space:nowrap;">${item.pembeli ?? '-'}</td>
                                                                                <td style="white-space:nowrap;">${item.tgl_booking ?? '-'}</td>
                                                                                <td style="white-space:nowrap;">${item.metode_bayar ?? '-'}</td>
                                                                                <td style="white-space:nowrap;">Rp ${harga}</td>
                                                                                <td style="color:${outstandingRed}; font-weight:700; white-space:nowrap;">Rp ${sisa}</td>
                                                                                <td><span style="display:inline-block; padding:4px 10px; border-radius:999px; background:${dpBg}; color:${dpColor}; font-weight:700; white-space:nowrap;">${dpStatus}</span></td>
                                                                                <td><span style="display:inline-block; padding:4px 10px; border-radius:999px; background:${statusBg}; color:${statusColor}; font-weight:700; white-space:nowrap;">${saleStatus === 'Paid Off' ? '🤝 ' : ''}${saleStatus || '-'}</span></td>
                                                                                <td style="white-space:nowrap;">${item.estimasi_lunas ?? '-'}</td>
                                                                                <td style="white-space:nowrap;">${item.marketing ?? '-'}</td>
                                                                                <td style="padding-left:14px; white-space: nowrap;">
                                                                                    <a href="/penjualan/${item.id}" class="btn light" style="padding:8px 10px; border-color:#e5e7eb;">Detail</a>
                                                                                </td>
                                                                            </tr>`;
                }).join('');
            };

            const renderSuggestions = (listEl, items) => {
                if (!listEl || !items) return;
                listEl.innerHTML = items.map(item => `<option value="${item}">`).join('');
            };

            const setStatus = (msg) => {
                if (!statusBox) return;
                if (!msg) {
                    statusBox.style.visibility = 'hidden';
                    statusBox.textContent = '';
                } else {
                    statusBox.style.visibility = 'visible';
                    statusBox.textContent = msg;
                }
            };

            const fetchData = debounce(() => {
                if (!form) return;
                const params = new URLSearchParams(new FormData(form));
                setStatus('Menerapkan filter...');
                if (manualSearchBtn) manualSearchBtn.disabled = true;
                fetch(`${form.action}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(res => res.json())
                    .then(({ data, suggestions }) => {
                        tableBody.innerHTML = renderRows(data);
                        renderSuggestions(kavlingList, suggestions?.kavling);
                        renderSuggestions(pembeliList, suggestions?.pembeli);
                        setStatus('Filter diterapkan');
                        setTimeout(() => setStatus(''), 800);
                    })
                    .catch(() => setStatus('Gagal memuat data'))
                    .finally(() => {
                        if (manualSearchBtn) manualSearchBtn.disabled = false;
                    });
            }, 400);

            const bindAuto = () => {
                if (!form) return;
                form.querySelectorAll('input, select').forEach(el => {
                    if (el.type === 'date' || el.tagName === 'SELECT') {
                        el.addEventListener('change', fetchData);
                    } else {
                        el.addEventListener('input', fetchData);
                    }
                });
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    fetchData();
                });
            };

            const bootstrap = () => {
                tableBody.dataset.initialized = '1';
                renderSuggestions(kavlingList, [...new Set(initialData.map(i => i.kavling).filter(Boolean))].slice(0, 12));
                renderSuggestions(pembeliList, [...new Set(initialData.map(i => i.pembeli).filter(Boolean))].slice(0, 12));
                bindAuto();
            };

            bootstrap();
        })();
    </script>
@endpush
