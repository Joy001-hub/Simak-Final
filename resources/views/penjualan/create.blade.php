@extends('layouts.app')

@section('content')
    <div class="page-heading" style="margin-bottom:24px;">
        <div>
            <h2 style="font-size:20px; font-weight:700; color:#1E293B; margin:0;">Tambah Penjualan</h2>
            <p style="font-size:12px; color:#64748B; margin:4px 0 0 0;">Buat transaksi penjualan baru</p>
        </div>
    </div>

    <form id="saleForm" action="{{ route('penjualan.store') }}" method="POST" class="card"
        style="max-width:960px; gap:14px;">
        @csrf
        @if(isset($parentSaleId))
            <input type="hidden" name="parent_sale_id" value="{{ $parentSaleId }}">
        @endif

        <h3 class="panel-title">Data Utama</h3>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Kavling (Tersedia) <span style="color:red">*</span></label>
                <select name="lot_id" class="input" id="lotSelect" required>
                    <option value="">Pilih Kavling</option>
                    @foreach($lots as $lot)
                        <option value="{{ $lot->id }}" data-base-price="{{ $lot->base_price }}"
                            data-project="{{ optional($lot->project)->name }}" data-area="{{ $lot->area }}">
                            {{ optional($lot->project)->name }} / {{ $lot->block_number }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="hint">Pelanggan <span style="color:red">*</span></label>
                <select name="buyer_id" class="input" required>
                    <option value="">Pilih Pelanggan</option>
                    @foreach($buyers as $buyer)
                        <option value="{{ $buyer->id }}" {{ (isset($buyerId) && $buyerId == $buyer->id) ? 'selected' : '' }}>
                            {{ $buyer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="hint">Sales <span style="color:red">*</span></label>
                <select name="marketer_id" class="input" required>
                    <option value="">Pilih Sales</option>
                    @foreach($marketers as $marketer)
                        <option value="{{ $marketer->id }}">{{ $marketer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="hint">Metode Pembayaran <span style="color:red">*</span></label>
                <select name="payment_method" class="input" id="paymentMethod" required>
                    <option value="">Pilih Metode Pembayaran</option>
                    <option value="cash">Cash Keras</option>
                    <option value="installment">Angsuran In-house</option>
                    <option value="kpr">KPR Bank</option>
                </select>
            </div>
            <div class="field">
                <label class="hint">Tgl Booking <span style="color:red">*</span></label>
                <input class="input" type="date" name="booking_date" required>
            </div>
        </div>

        <h3 class="panel-title">Detail Harga</h3>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Harga Dasar (Rp) <span style="color:red">*</span></label>
                <input class="input currency-input" type="text" name="base_price" id="basePrice"
                    value="{{ old('base_price') ? number_format((float) str_replace('.', '', old('base_price')), 0, ',', '.') : '' }}"
                    placeholder="Masukkan harga dasar" required>
            </div>
            <div class="field">
                <label class="hint">Promo/Diskon (Rp)</label>
                <input class="input currency-input" type="text" name="discount" id="discount"
                    value="{{ old('discount') ? number_format((float) str_replace('.', '', old('discount')), 0, ',', '.') : '' }}"
                    placeholder="Diskon/promo">
            </div>
        </div>
        <div class="field">
            <label class="hint">Harga Netto (Rp)</label>
            <input class="input readonly" type="text" name="price" id="netPrice"
                value="{{ old('price') ? number_format((float) str_replace('.', '', old('price')), 0, ',', '.') : '' }}"
                placeholder="Otomatis dihitung" readonly>
        </div>

        <h3 class="panel-title">Biaya Tambahan</h3>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Biaya PPJB (Rp)</label>
                <input class="input currency-input" type="text" name="extra_ppjb" id="extraPpjb"
                    value="{{ old('extra_ppjb') ? number_format((float) str_replace('.', '', old('extra_ppjb')), 0, ',', '.') : '' }}"
                    placeholder="Isi jika ada biaya PPJB">
            </div>
            <div class="field">
                <label class="hint">Biaya SHM (Rp)</label>
                <input class="input currency-input" type="text" name="extra_shm" id="extraShm"
                    value="{{ old('extra_shm') ? number_format((float) str_replace('.', '', old('extra_shm')), 0, ',', '.') : '' }}"
                    placeholder="Isi jika ada biaya SHM">
            </div>
        </div>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Biaya Lain (Rp)</label>
                <input class="input currency-input" type="text" name="extra_other" id="extraOther"
                    value="{{ old('extra_other') ? number_format((float) str_replace('.', '', old('extra_other')), 0, ',', '.') : '' }}"
                    placeholder="Biaya lain">
            </div>
            <div class="field">
                <label class="hint">Booking Fee (Rp)</label>
                <input class="input currency-input" type="text" name="booking_fee" id="bookingFeeInput"
                    value="{{ old('booking_fee') ? number_format((float) str_replace('.', '', old('booking_fee')), 0, ',', '.') : '' }}"
                    placeholder="Booking Fee">
                <label
                    style="display:flex; align-items:center; gap:6px; margin-top:6px; font-size:12px; color:#64748B; cursor:pointer;">
                    <input type="checkbox" name="booking_fee_included" id="bookingFeeIncluded" value="1" {{ old('booking_fee_included') ? 'checked' : '' }}>
                    Sudah Termasuk harga unit
                </label>
            </div>
        </div>
        <div class="field">
            <label class="hint">Grand Total (Rp)</label>
            <input class="input readonly" type="text" id="grandTotal"
                value="{{ old('price') ? number_format((float) str_replace('.', '', old('price')), 0, ',', '.') : '' }}"
                placeholder="Otomatis dihitung" readonly>
        </div>

        <h3 class="panel-title">Skema Pembayaran</h3>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field" id="tenorField">
                <label class="hint">Tenor (bulan) <span class="req-mark" style="color:red">*</span></label>
                <input class="input" type="number" name="tenor_months" id="tenorInput" min="0"
                    value="{{ old('tenor_months') ?: '' }}" placeholder="Misal: 12, 24, 36">
            </div>
            <div class="field" id="dueDayField">
                <label class="hint">Tanggal Jatuh Tempo (1-31) <span class="req-mark" style="color:red">*</span></label>
                <input class="input" type="number" name="due_day" id="dueDayInput" min="1" max="31"
                    value="{{ old('due_day', '') }}" placeholder="1 - 31">
            </div>
        </div>
        <div class="grid-2" style="column-gap:18px;">
            <div class="field">
                <label class="hint">Uang Muka (%)</label>
                <div style="display:flex; align-items:center; gap:10px;">
                    <input class="input" type="number" name="dp_percent" id="dpPercentInput" min="0" max="100" step="any"
                        value="{{ old('dp_percent') ?: '' }}" placeholder="Misal: 10, 20, dst " style="flex:1;">
                    <span style="color:#64748B; font-size:13px; white-space:nowrap;">/ <strong id="dpRupiahDisplay">Rp
                            0</strong></span>
                </div>
                <input type="hidden" name="down_payment" id="dpInput" value="{{ old('down_payment') ?: '' }}">
            </div>
        </div>
        <div class="field">
            <label class="hint">Estimasi Angsuran / Bulan</label>
            <input class="input readonly" type="text" id="installmentEstimate" value="Rp 0" readonly>
        </div>

        <div class="field">
            <label class="hint">Catatan Transaksi</label>
            <textarea class="input" name="notes" rows="3"
                placeholder="Catatan tambahan untuk transaksi ini">{{ old('notes', '') }}</textarea>
        </div>

        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <a class="btn light" href="{{ route('penjualan.index') }}">Batal</a>
            <button class="btn primary" type="submit">Simpan</button>
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
            const priceInput = netPrice; // for compatibility
            const dpInput = document.getElementById('dpInput');
            const tenorInput = document.getElementById('tenorInput');
            const paymentMethod = document.getElementById('paymentMethod');
            const dpRupiahDisplay = document.getElementById('dpRupiahDisplay');
            const installmentEstimate = document.getElementById('installmentEstimate');
            const dueDayInput = document.getElementById('dueDayInput');
            const lotSelect = document.getElementById('lotSelect');
            const bookingFeeInput = document.getElementById('bookingFeeInput');
            const bookingFeeIncluded = document.getElementById('bookingFeeIncluded');
            window.lastDpChange = null;

            function formatIDR(n) {
                return 'Rp ' + (Number(n) || 0).toLocaleString('id-ID');
            }

            function parseIDR(str) {
                if (typeof str !== 'string') str = String(str || '');
                return Number(str.replace(/\./g, '')) || 0;
            }

            function formatNumber(n) {
                return (Number(n) || 0).toLocaleString('id-ID');
            }

            const isEmptyOrZero = (val) => val === '' || val === null || parseIDR(val) === 0;

            function hydrateFromLot() {
                const selected = lotSelect?.selectedOptions?.[0];
                if (!selected) return;
                const base = Number(selected.getAttribute('data-base-price') || 0);
                if (base > 0) {
                    basePrice.value = formatNumber(base);
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
                        basePrice.value = formatNumber(data.base_price);
                    }
                    // Only fill installment defaults if payment method is installment
                    const isInstallment = paymentMethod.value === 'installment';
                    if (isInstallment) {
                        const defaults = data?.payment_defaults || {};
                        if ((forceDpDefaults || isEmptyOrZero(dpPercentInput.value)) && Number.isFinite(Number(defaults.dp_percent))) {
                            dpPercentInput.value = defaults.dp_percent;
                        }
                        if (forceDpDefaults || isEmptyOrZero(dpInput.value)) {
                            // dp_nominal is usually calculated, but if provided in defaults:
                            if (Number.isFinite(Number(defaults.dp_nominal))) {
                                // We don't set dpInput (hidden) directly, we let recalc handle it or set it if needed
                                // But here we rely on recalc mostly.
                            }
                        }
                        if ((forceDpDefaults || isEmptyOrZero(tenorInput.value)) && Number.isFinite(Number(defaults.tenor_months))) {
                            tenorInput.value = defaults.tenor_months;
                        }
                        if ((forceDpDefaults || isEmptyOrZero(dueDayInput.value)) && Number.isFinite(Number(defaults.due_day))) {
                            dueDayInput.value = defaults.due_day;
                        }
                    }
                } catch (e) {
                    // noop fallback to local data attributes
                }
                recalc();
            }

            function recalc() {
                const base = parseIDR(basePrice.value);
                const disc = parseIDR(discount.value);
                const ppjb = parseIDR(extraPpjb.value);
                const shm = parseIDR(extraShm.value);
                const oth = parseIDR(extraOther.value);
                const bookingFee = parseIDR(bookingFeeInput?.value || '0');
                const includeBookingFee = bookingFeeIncluded?.checked || false;

                // Harga Netto = Harga Dasar - Diskon
                const net = Math.max(0, base - disc);
                // Grand Total = Harga Netto + Biaya Tambahan (PPJB, SHM, Lain)
                // Jika TIDAK dicentang: Booking Fee ditambahkan ke Grand Total
                // Jika dicentang (Sudah Termasuk harga unit): Booking Fee TIDAK ditambahkan karena sudah termasuk di harga dasar
                const total = net + ppjb + shm + oth + (includeBookingFee ? 0 : bookingFee);
                netPrice.value = formatNumber(net);
                grandTotal.value = formatNumber(total);

                // Enable booking fee for all payment methods
                if (bookingFeeInput) {
                    bookingFeeInput.disabled = false;
                }

                // Handle Cash Keras - full payment, disable all installment fields
                if (paymentMethod.value === 'cash') {
                    tenorInput.value = '';
                    tenorInput.disabled = true;
                    tenorInput.removeAttribute('required');
                    dueDayInput.value = '';
                    dueDayInput.disabled = true;
                    dueDayInput.removeAttribute('required');
                    dpPercentInput.value = '';
                    dpPercentInput.disabled = true;
                    dpInput.value = '';

                    // Hide asterisks for tenor/due day
                    document.querySelectorAll('#tenorField .req-mark, #dueDayField .req-mark').forEach(el => el.style.display = 'none');
                    if (dpRupiahDisplay) dpRupiahDisplay.textContent = '100% (Cash Keras)';
                    installmentEstimate.value = '100% (Cash Keras)';
                    return;
                }

                // Handle KPR Bank - allow DP optional, tenor/due day are optional but enabled
                if (paymentMethod.value === 'kpr') {
                    // Keep tenor and due day enabled but optional
                    tenorInput.disabled = false;
                    tenorInput.removeAttribute('required');
                    dueDayInput.disabled = false;
                    dueDayInput.removeAttribute('required');
                    // Hide asterisks for tenor/due day (optional)
                    document.querySelectorAll('#tenorField .req-mark, #dueDayField .req-mark').forEach(el => el.style.display = 'none');

                    // Calculate installment estimate if tenor is provided
                    const tenor = Number(tenorInput.value || 0);
                    const dpPercentVal = Number(dpPercentInput.value || 0);
                    const dp = Math.max(0, Math.round(net * (dpPercentVal / 100)));
                    dpInput.value = dp;
                    if (dpRupiahDisplay) dpRupiahDisplay.textContent = formatIDR(dp);

                    const outstanding = Math.max(0, net - dp);
                    if (tenor > 0) {
                        const monthly = Math.ceil(outstanding / tenor);
                        installmentEstimate.value = formatIDR(monthly);
                    } else {
                        installmentEstimate.value = 'Masukkan tenor untuk estimasi';
                    }

                    // DP is optional for KPR
                    dpPercentInput.disabled = false;
                    return;
                }

                // Re-enable all fields for Installment
                tenorInput.disabled = false;
                tenorInput.setAttribute('required', 'required');
                dueDayInput.disabled = false;
                dueDayInput.setAttribute('required', 'required');
                dpPercentInput.disabled = false;
                // Show asterisks for required fields
                document.querySelectorAll('#tenorField .req-mark, #dueDayField .req-mark').forEach(el => el.style.display = 'inline');

                // Calculate DP from percentage only
                const dpPercentVal = Number(dpPercentInput.value || 0);
                const dp = Math.max(0, Math.round(net * (dpPercentVal / 100)));
                dpInput.value = dp;

                // Display rupiah value next to percentage input
                if (dpRupiahDisplay) dpRupiahDisplay.textContent = formatIDR(dp);

                const tenor = Number(tenorInput.value || 0);
                const outstanding = Math.max(0, net - dp);
                const monthly = (tenor > 0 && paymentMethod.value === 'installment') ? Math.ceil(outstanding / tenor) : outstanding;
                installmentEstimate.value = formatIDR(monthly);
            }

            // Format currency inputs on type
            document.querySelectorAll('.currency-input').forEach(input => {
                input.addEventListener('input', function (e) {
                    let cursorPosition = this.selectionStart;
                    let oldLength = this.value.length;

                    let val = this.value.replace(/\D/g, '');
                    if (val !== '') {
                        this.value = Number(val).toLocaleString('id-ID');
                    } else {
                        this.value = '';
                    }

                    let newLength = this.value.length;
                    cursorPosition = cursorPosition + (newLength - oldLength);
                    this.setSelectionRange(cursorPosition, cursorPosition);

                    recalc();
                });
            });

            // Strip dots on submit
            document.getElementById('saleForm').addEventListener('submit', function (e) {
                document.querySelectorAll('.currency-input, .readonly').forEach(input => {
                    input.value = input.value.replace(/\./g, '');
                });
                // Enable disabled fields so they are submitted (if key logic requires them) 
                // - usually disabled fields are not submitted. 
                // If backend expects them, we should use hidden inputs. 
                // But in this form, 'price', 'grandTotal' etc are mostly for display or calculated on backend?
                // Let's assume backend recalculates essential totals or validates them.
                // However, base_price IS essential.

                // Also ensure dpInput is set correctly if it was relying on calc
                // dpInput is hidden, so it's fine.
            });

            [basePrice, discount, extraPpjb, extraShm, extraOther, dpPercentInput, dpInput, tenorInput, paymentMethod].forEach(el => el?.addEventListener('input', recalc));
            bookingFeeIncluded?.addEventListener('change', recalc);
            // Remove previous event listeners on currency inputs to avoid double trigger if any?
            // Actually 'input' event bubbles/multi-binds fine. 
            // Note: recalc is called inside the currency-input listener above.
            // But we keep this for non-currency inputs like dpPercent.

            dpPercentInput?.addEventListener('input', () => { window.lastDpChange = 'percent'; recalc(); });
            dpInput?.addEventListener('input', () => { window.lastDpChange = 'nominal'; recalc(); });

            // Fix double event on keys that are currency inputs, but it doesn't hurt much.
            // We can remove currency inputs from the array below if we want optimization.
            const nonCurrencyInputs = [dpPercentInput, dpInput, tenorInput, paymentMethod];
            nonCurrencyInputs.forEach(el => el?.addEventListener('input', () => {
                if (!['percent', 'nominal'].includes(window.lastDpChange)) window.lastDpChange = null;
                recalc();
            }));

            lotSelect?.addEventListener('change', () => {
                window.lastDpChange = null;
                hydrateFromLot();
                autoFetchLotPricing(lotSelect.value, { forceDpDefaults: true });
            });

            // Prefill on first load using DB data when available
            hydrateFromLot();
            autoFetchLotPricing(lotSelect?.value || '');
            recalc();
        </script>
    @endpush
@endsection

