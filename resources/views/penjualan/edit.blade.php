@extends('layouts.app')

@section('content')
    <div class="page-heading" style="margin-bottom:24px;">
        <div>
            <h2 style="font-size:20px; font-weight:700; color:#1E293B; margin:0;">Edit Penjualan</h2>
            <p style="font-size:12px; color:#64748B; margin:4px 0 0 0;">Ubah data transaksi penjualan</p>
        </div>
    </div>

    <form id="saleForm" action="{{ route('penjualan.update', $sale) }}" method="POST" class="card"
        style="max-width:960px; gap:14px;">
        @csrf
        @method('PUT')

        <h3 class="panel-title">Data Utama</h3>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Kavling (Tersedia)</label>
                <select name="lot_id" class="input" id="lotSelect" required>
                    <option value="">Pilih Kavling</option>
                    @foreach($lots as $lot)
                        <option value="{{ $lot->id }}" data-base-price="{{ $lot->base_price }}"
                            data-project="{{ optional($lot->project)->name }}" data-area="{{ $lot->area }}"
                            @if($sale->lot_id == $lot->id) selected @endif>{{ optional($lot->project)->name }} /
                            {{ $lot->block_number }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="hint">Pelanggan</label>
                <select name="buyer_id" class="input" required>
                    <option value="">Pilih Pelanggan</option>
                    @foreach($buyers as $buyer)
                        <option value="{{ $buyer->id }}" @if($sale->buyer_id == $buyer->id) selected @endif>{{ $buyer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="hint">Sales</label>
                <select name="marketer_id" class="input">
                    <option value="">Pilih Sales</option>
                    @foreach($marketers as $marketer)
                        <option value="{{ $marketer->id }}" @if($sale->marketer_id == $marketer->id) selected @endif>
                            {{ $marketer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="hint">Metode Pembayaran</label>
                <select name="payment_method" class="input" id="paymentMethod" required>
                    <option value="cash" @if($sale->payment_method == 'cash') selected @endif>Cash Keras</option>
                    <option value="installment" @if($sale->payment_method == 'installment') selected @endif>Angsuran In-house
                    </option>
                    <option value="kpr" @if($sale->payment_method == 'kpr') selected @endif>KPR Bank</option>
                </select>
            </div>
            <div class="field">
                <label class="hint">Tgl Booking</label>
                <input class="input" type="date" name="booking_date"
                    value="{{ optional($sale->booking_date)->format('Y-m-d') }}" required>
            </div>
        </div>

        <h3 class="panel-title">Detail Harga</h3>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Harga Dasar (Rp)</label>
                <input class="input" type="number" name="base_price" id="basePrice" min="0" required
                    value="{{ old('base_price', $sale->base_price ?? $sale->price ?? '') }}"
                    placeholder="Masukkan harga dasar">
            </div>
            <div class="field">
                <label class="hint">Promo/Diskon (Rp)</label>
                <input class="input" type="number" name="discount" id="discount" min="0"
                    value="{{ old('discount', $sale->discount ?? '') }}" placeholder="Diskon/promo">
            </div>
        </div>
        <div class="field">
            <label class="hint">Harga Netto (Rp)</label>
            <input class="input readonly" type="number" name="price" id="netPrice" min="0"
                value="{{ old('price', $sale->price ?? '') }}" placeholder="Otomatis dihitung" readonly>
        </div>

        <h3 class="panel-title">Biaya Tambahan</h3>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Biaya PPJB (Rp)</label>
                <input class="input" type="number" name="extra_ppjb" id="extraPpjb" min="0"
                    value="{{ old('extra_ppjb', $sale->extra_ppjb ?? '') }}" placeholder="Isi jika ada biaya PPJB">
            </div>
            <div class="field">
                <label class="hint">Biaya SHM (Rp)</label>
                <input class="input" type="number" name="extra_shm" id="extraShm" min="0"
                    value="{{ old('extra_shm', $sale->extra_shm ?? '') }}" placeholder="Isi jika ada biaya SHM">
            </div>
        </div>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Biaya Lain (Rp)</label>
                <input class="input" type="number" name="extra_other" id="extraOther" min="0"
                    value="{{ old('extra_other', $sale->extra_other ?? '') }}" placeholder="Biaya lain">
            </div>
            <div class="field">
                <label class="hint">Booking Fee (Rp)</label>
                <input class="input" type="number" name="booking_fee" id="bookingFeeInput" min="0"
                    value="{{ old('booking_fee', $sale->booking_fee ?? '') }}" placeholder="Booking Fee">
                <label
                    style="display:flex; align-items:center; gap:6px; margin-top:6px; font-size:12px; color:#64748B; cursor:pointer;">
                    <input type="checkbox" name="booking_fee_included" id="bookingFeeIncluded" value="1" {{ old('booking_fee_included', $sale->booking_fee_included ?? false) ? 'checked' : '' }}>
                    Sudah termasuk harga unit
                </label>
            </div>
        </div>
        <div class="field">
            <label class="hint">Grand Total (Rp)</label>
            <input class="input readonly" type="number" id="grandTotal" min="0"
                value="{{ old('price', $sale->price ?? '') }}" placeholder="Otomatis dihitung" readonly>
        </div>

        <h3 class="panel-title">Skema Pembayaran</h3>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Tenor (bulan)</label>
                <input class="input" type="number" name="tenor_months" id="tenorInput" min="1" required
                    value="{{ old('tenor_months', $sale->tenor_months) ?: '' }}" placeholder="Misal: 12, 24, 36">
            </div>
            <div class="field">
                <label class="hint">Tanggal Jatuh Tempo (1-28)</label>
                <input class="input" type="number" name="due_day" id="dueDayInput" min="1" max="28" required
                    value="{{ old('due_day', $sale->due_day ?? '') }}" placeholder="1 - 28">
            </div>
        </div>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Uang Muka (%)</label>
                <input class="input" type="number" name="dp_percent" id="dpPercentInput" min="0" max="100" step="any"
                    value="{{ old('dp_percent') ?: '' }}" placeholder="Misal: 10, 20, dst">
            </div>
            <div class="field">
                <label class="hint">Uang Muka (Rp)</label>
                <input class="input" type="number" name="down_payment" id="dpInput" min="0"
                    value="{{ old('down_payment', $sale->down_payment) ?: '' }}" placeholder="Masukkan nominal DP">
                <small class="hint" id="dpPercent">0% dari harga</small>
            </div>
        </div>
        <div class="field">
            <label class="hint">Estimasi Angsuran / Bulan</label>
            <input class="input readonly" type="text" id="installmentEstimate" value="Rp 0" readonly>
        </div>

        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <a class="btn light" href="{{ route('penjualan.index') }}">Batal</a>
            <button class="btn primary" type="submit">Simpan Perubahan</button>
        </div>
    </form>

    @push('scripts')
        <script>
            const basePrice = document.getElementById('basePrice');
            const discount = document.getElementById('discount');
            const netPrice = document.getElementById('netPrice');
            const extraPpjb = document.getElementById('extraPpjb');
            const extraShm = document.getElementById('extraShm');
            const extraOther = document.getElementById('extraOther');
            const grandTotal = document.getElementById('grandTotal');
            const dpPercentInput = document.getElementById('dpPercentInput');
            const priceInput = netPrice;
            const dpInput = document.getElementById('dpInput');
            const tenorInput = document.getElementById('tenorInput');
            const paymentMethod = document.getElementById('paymentMethod');
            const dpPercentEl = document.getElementById('dpPercent');
            const installmentEstimate = document.getElementById('installmentEstimate');
            const dueDayInput = document.getElementById('dueDayInput');
            const lotSelect = document.getElementById('lotSelect');
            const bookingFeeInput = document.getElementById('bookingFeeInput');
            const bookingFeeIncluded = document.getElementById('bookingFeeIncluded');
            window.lastDpChange = null;

            function formatIDR(n) {
                return 'Rp ' + (Number(n) || 0).toLocaleString('id-ID');
            }

            const isEmptyOrZero = (val) => val === '' || val === null || Number(val) === 0;

            function hydrateFromLot({ preserveExisting = false } = {}) {
                const selected = lotSelect?.selectedOptions?.[0];
                if (!selected) return;
                const base = Number(selected.getAttribute('data-base-price') || 0);
                if (base > 0 && (!preserveExisting || isEmptyOrZero(basePrice.value))) {
                    basePrice.value = base;
                }
                recalc();
            }

            async function autoFetchLotPricing(lotId, { forceDpDefaults = false, preserveExisting = false } = {}) {
                if (!lotId) return;
                try {
                    const res = await fetch(`/kavling/${lotId}/pricing`, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (Number.isFinite(Number(data?.base_price)) && (!preserveExisting || isEmptyOrZero(basePrice.value))) {
                        basePrice.value = data.base_price;
                    }
                    // Only fill installment defaults if payment method is installment
                    const isInstallment = paymentMethod.value === 'installment';
                    if (isInstallment) {
                        const defaults = data?.payment_defaults || {};
                        if ((forceDpDefaults || isEmptyOrZero(dpPercentInput.value)) && Number.isFinite(Number(defaults.dp_percent))) {
                            dpPercentInput.value = defaults.dp_percent;
                        }
                        if ((forceDpDefaults || isEmptyOrZero(dpInput.value)) && Number.isFinite(Number(defaults.dp_nominal))) {
                            dpInput.value = defaults.dp_nominal;
                        }
                        if ((forceDpDefaults || isEmptyOrZero(tenorInput.value)) && Number.isFinite(Number(defaults.tenor_months))) {
                            tenorInput.value = defaults.tenor_months;
                        }
                        if ((forceDpDefaults || isEmptyOrZero(dueDayInput.value)) && Number.isFinite(Number(defaults.due_day))) {
                            dueDayInput.value = defaults.due_day;
                        }
                    }
                } catch (e) {
                    // noop
                }
                recalc();
            }

            function recalc() {
                const base = Number(basePrice.value || 0);
                const disc = Number(discount.value || 0);
                const ppjb = Number(extraPpjb.value || 0);
                const shm = Number(extraShm.value || 0);
                const oth = Number(extraOther.value || 0);
                const bookingFee = Number(bookingFeeInput?.value || 0);
                const includeBookingFee = bookingFeeIncluded?.checked || false;

                // Harga Netto = Harga Dasar - Diskon
                const net = Math.max(0, base - disc);
                // Grand Total = Harga Netto + Biaya Tambahan (PPJB, SHM, Lain)
                // Jika TIDAK dicentang: Booking Fee ditambahkan ke Grand Total
                // Jika dicentang (Sudah Termasuk harga unit): Booking Fee TIDAK ditambahkan karena sudah termasuk di harga dasar
                const total = net + ppjb + shm + oth + (includeBookingFee ? 0 : bookingFee);
                netPrice.value = net;
                grandTotal.value = total;

                // Handle Cash Keras - full payment, disable all installment fields
                if (paymentMethod.value === 'cash') {
                    tenorInput.value = '';
                    tenorInput.disabled = true;
                    dueDayInput.value = '';
                    dueDayInput.disabled = true;
                    dpPercentInput.value = '';
                    dpPercentInput.disabled = true;
                    dpInput.value = '';
                    dpInput.disabled = true;
                    dpPercentEl.textContent = '100% (Cash Keras)';
                    installmentEstimate.value = '100% (Cash Keras)';
                    return;
                }

                // Handle KPR Bank - allow DP optional, disable tenor/due day
                if (paymentMethod.value === 'kpr') {
                    tenorInput.value = '';
                    tenorInput.disabled = true;
                    dueDayInput.value = '';
                    dueDayInput.disabled = true;
                    installmentEstimate.value = 'N/A (KPR Bank)';
                    // DP is optional for KPR
                    dpPercentInput.disabled = false;
                    dpInput.disabled = false;
                    const dpPercentVal = Number(dpPercentInput.value || 0);
                    const dpInputVal = Number(dpInput.value || 0);
                    let dp = dpInputVal;
                    if (window.lastDpChange === 'percent') {
                        dp = Math.max(0, Math.round(net * (dpPercentVal / 100)));
                        dpInput.value = dp;
                    } else if (window.lastDpChange === 'nominal') {
                        const pct = net > 0 ? (dp / net) * 100 : 0;
                        dpPercentInput.value = pct ? Number(pct.toFixed(2)) : 0;
                    } else {
                        dp = dpInputVal || Math.round(net * (dpPercentVal / 100));
                        dpInput.value = dp;
                    }
                    const percent = net > 0 ? Math.round((dp / net) * 100) : 0;
                    dpPercentEl.textContent = `${percent}% dari harga`;
                    return;
                }

                // Re-enable all fields for Installment
                tenorInput.disabled = false;
                dueDayInput.disabled = false;
                dpPercentInput.disabled = false;
                dpInput.disabled = false;
                tenorInput.required = true;
                dueDayInput.required = true;

                const dpPercentVal = Number(dpPercentInput.value || 0);
                const dpInputVal = Number(dpInput.value || 0);
                let dp = dpInputVal;

                if (window.lastDpChange === 'percent') {
                    dp = Math.max(0, Math.round(net * (dpPercentVal / 100)));
                    dpInput.value = dp;
                } else if (window.lastDpChange === 'nominal') {
                    const pct = net > 0 ? (dp / net) * 100 : 0;
                    dpPercentInput.value = pct ? Number(pct.toFixed(2)) : 0;
                } else {
                    dp = dpInputVal || Math.round(net * (dpPercentVal / 100));
                    dpInput.value = dp;
                }
                const tenor = Number(tenorInput.value || 0);

                const percent = net > 0 ? Math.round((dp / net) * 100) : 0;
                dpPercentEl.textContent = `${percent}% dari harga`;

                const outstanding = Math.max(0, net - dp);
                const monthly = (tenor > 0 && paymentMethod.value === 'installment') ? Math.ceil(outstanding / tenor) : outstanding;
                installmentEstimate.value = formatIDR(monthly);
            }

            [basePrice, discount, extraPpjb, extraShm, extraOther, bookingFeeInput, dpPercentInput, dpInput, tenorInput, paymentMethod].forEach(el => el?.addEventListener('input', recalc));
            bookingFeeIncluded?.addEventListener('change', recalc);
            dpPercentInput?.addEventListener('input', () => { window.lastDpChange = 'percent'; recalc(); });
            dpInput?.addEventListener('input', () => { window.lastDpChange = 'nominal'; recalc(); });
            [basePrice, discount, extraPpjb, extraShm, extraOther, tenorInput, paymentMethod].forEach(el => el?.addEventListener('input', () => { if (!['percent', 'nominal'].includes(window.lastDpChange)) window.lastDpChange = null; recalc(); }));
            lotSelect?.addEventListener('change', () => {
                window.lastDpChange = null;
                hydrateFromLot();
                autoFetchLotPricing(lotSelect.value, { forceDpDefaults: true });
            });

            const hasExistingPrice = Number(basePrice.value || 0) > 0;
            hydrateFromLot({ preserveExisting: hasExistingPrice });
            autoFetchLotPricing(lotSelect?.value || '', { preserveExisting: hasExistingPrice });
            recalc();
        </script>
    @endpush
@endsection