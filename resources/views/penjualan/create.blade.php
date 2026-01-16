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
                <label class="hint">Uang Muka</label>
                <div style="display:flex; gap:8px;">
                    <select id="dpType" class="input"
                        style="width:75px; padding-left:8px; padding-right:4px; background-color:#f8fafc; cursor:pointer;">
                        <option value="percent">%</option>
                        <option value="nominal">Rp</option>
                    </select>

                    <div style="flex:1;">
                        <input class="input" type="number" name="dp_percent" id="dpPercentInput" min="0" max="100"
                            step="any" value="{{ old('dp_percent') ?: '' }}" placeholder="Misal: 10, 20...">

                        <input class="input currency-input" type="text" id="dpNominalDisplayInput"
                            placeholder="Masukkan nominal DP" style="display:none;">
                    </div>
                </div>
                <div style="margin-top:6px; font-size:13px; color:#64748B; display:flex; justify-content:flex-end;">
                    <span id="dpConversiDisplay">Setara: <strong id="dpRupiahDisplay">Rp 0</strong></span>
                </div>
                <!-- Hidden input for the actual nominal value sent to backend -->
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
        
        // DP Elements
        const dpType = document.getElementById('dpType');
        const dpPercentInput = document.getElementById('dpPercentInput');
        const dpNominalDisplayInput = document.getElementById('dpNominalDisplayInput');
        const dpConversiDisplay = document.getElementById('dpConversiDisplay');
        const dpInput = document.getElementById('dpInput'); // Hidden Input
        
        const tenorInput = document.getElementById('tenorInput');
        const paymentMethod = document.getElementById('paymentMethod');
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
        
        function getNetPrice() {
            const base = parseIDR(basePrice.value);
            const disc = parseIDR(discount.value);
            return Math.max(0, base - disc);
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

        async function autoFetchLotPricing(lotId, { forceDpDefaults = false } = {}) {
            if (!lotId) return;
            try {
                const res = await fetch(`/kavling/${lotId}/pricing`, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                
                if (Number.isFinite(Number(data?.base_price)) && isEmptyOrZero(basePrice.value)) {
                    basePrice.value = formatNumber(data.base_price);
                }
                
                const isInstallment = paymentMethod.value === 'installment';
                if (isInstallment) {
                    const defaults = data?.payment_defaults || {};
                    if ((forceDpDefaults || isEmptyOrZero(dpPercentInput.value)) && Number.isFinite(Number(defaults.dp_percent))) {
                         dpPercentInput.value = defaults.dp_percent;
                         // Set mode to percent by default when loading defaults
                         if(dpType) {
                            dpType.value = 'percent';
                            dpType.dispatchEvent(new Event('change')); 
                         }
                    }
                    if ((forceDpDefaults || isEmptyOrZero(tenorInput.value)) && Number.isFinite(Number(defaults.tenor_months))) {
                        tenorInput.value = defaults.tenor_months;
                    }
                    if ((forceDpDefaults || isEmptyOrZero(dueDayInput.value)) && Number.isFinite(Number(defaults.due_day))) {
                        dueDayInput.value = defaults.due_day;
                    }
                }
            } catch (e) { }
            recalc();
        }

        // DP Type Toggle Logic
        dpType?.addEventListener('change', () => {
             const isPercent = dpType.value === 'percent';
             
             if(isPercent) {
                 dpPercentInput.style.display = 'block';
                 dpNominalDisplayInput.style.display = 'none';
                 // Update hint text
                 dpConversiDisplay.innerHTML = 'Setara: <strong id="dpRupiahDisplay">Rp 0</strong>';
                 
                 // If switching back to percent, maybe recalc percent from nominal?
                 const net = getNetPrice();
                 const currentNominal = parseIDR(dpNominalDisplayInput.value);
                 if(net > 0 && currentNominal > 0) {
                     const p = (currentNominal / net) * 100;
                     dpPercentInput.value = parseFloat(p.toFixed(2));
                 }
             } else {
                 dpPercentInput.style.display = 'none';
                 dpNominalDisplayInput.style.display = 'block';
                 // Update hint text
                 dpConversiDisplay.innerHTML = 'Setara: <strong id="dpCheckPercent">0%</strong>';
                 
                 // Prefill nominal from current hidden input
                 const currentVal = dpInput.value;
                 if(currentVal) dpNominalDisplayInput.value = formatNumber(currentVal);
             }
             recalc();
        });

        // Nominal Input Event
        dpNominalDisplayInput?.addEventListener('input', function() {
              window.lastDpChange = 'nominal';
              let val = this.value.replace(/\D/g, '');
              if (val !== '') this.value = Number(val).toLocaleString('id-ID');
              else this.value = '';
              recalc();
        });

        function recalc() {
            const base = parseIDR(basePrice.value);
            const disc = parseIDR(discount.value);
            const ppjb = parseIDR(extraPpjb.value);
            const shm = parseIDR(extraShm.value);
            const oth = parseIDR(extraOther.value);
            const bookingFee = parseIDR(bookingFeeInput?.value || '0');
            const includeBookingFee = bookingFeeIncluded?.checked || false;

            const net = Math.max(0, base - disc);
            const total = net + ppjb + shm + oth + (includeBookingFee ? 0 : bookingFee);
            
            netPrice.value = formatNumber(net);
            grandTotal.value = formatNumber(total);

            if (bookingFeeInput) bookingFeeInput.disabled = false;

            // Handle Cash
            if (paymentMethod.value === 'cash') {
                tenorInput.value = ''; tenorInput.disabled = true; tenorInput.removeAttribute('required');
                dueDayInput.value = ''; dueDayInput.disabled = true; dueDayInput.removeAttribute('required');
                
                dpPercentInput.disabled = true;
                dpNominalDisplayInput.disabled = true;
                dpType.disabled = true; 
                dpInput.value = '';
                
                document.querySelectorAll('#tenorField .req-mark, #dueDayField .req-mark').forEach(el => el.style.display = 'none');
                
                if(dpType.value === 'percent') {
                   const displayEl = document.getElementById('dpRupiahDisplay');
                   if(displayEl) displayEl.textContent = '100% (Cash Keras)';
                } else {
                    dpNominalDisplayInput.value = 'LUNAS (Cash)';
                }
                
                installmentEstimate.value = '100% (Cash Keras)';
                return;
            }

            // Enable inputs
            tenorInput.disabled = false;
            dueDayInput.disabled = false;
            dpPercentInput.disabled = false;
            dpNominalDisplayInput.disabled = false;
            dpType.disabled = false;

            // KPR specific logic (optional validation)
            if (paymentMethod.value === 'kpr') {
                tenorInput.removeAttribute('required');
                dueDayInput.removeAttribute('required');
                document.querySelectorAll('#tenorField .req-mark, #dueDayField .req-mark').forEach(el => el.style.display = 'none');
            } else {
                tenorInput.setAttribute('required', 'required');
                dueDayInput.setAttribute('required', 'required');
                document.querySelectorAll('#tenorField .req-mark, #dueDayField .req-mark').forEach(el => el.style.display = 'inline');
            }
            
            // --- DP Calculation ---
            let dp = 0;
            if (dpType.value === 'percent') {
                 // Calculate Numeric from Percent
                 const dpPercentVal = Number(dpPercentInput.value || 0);
                 dp = Math.max(0, Math.round(net * (dpPercentVal / 100)));
                 
                 // Update UI
                 const displayEl = document.getElementById('dpRupiahDisplay');
                 if (displayEl) displayEl.textContent = formatIDR(dp);
                 
                 // Update nominal input in background
                 dpNominalDisplayInput.value = formatNumber(dp);
            } else {
                 // Calculate Percent from Numeric
                 dp = parseIDR(dpNominalDisplayInput.value);
                 
                 let percent = 0;
                 if(net > 0) percent = (dp / net) * 100;
                 
                 // Update UI
                 const percentEl = document.getElementById('dpCheckPercent');
                 if(percentEl) percentEl.textContent = percent.toFixed(2) + '%';
                 
                 // Determine if we should update the hidden percent input
                 // Only if current mode is nominal, we might want to sync back
                 if (document.activeElement !== dpPercentInput) {
                    dpPercentInput.value = parseFloat(percent.toFixed(2));
                 }
            }
            dpInput.value = dp;

            // --- Installment ---
            const tenor = Number(tenorInput.value || 0);
            const outstanding = Math.max(0, net - dp);
            
            if (paymentMethod.value === 'kpr') {
                 if (tenor > 0) {
                     const monthly = Math.ceil(outstanding / tenor);
                     installmentEstimate.value = formatIDR(monthly);
                 } else {
                     installmentEstimate.value = 'Masukkan tenor untuk estimasi';
                 }
            } else {
                 // Regular Installment
                 const monthly = (tenor > 0) ? Math.ceil(outstanding / tenor) : outstanding;
                 installmentEstimate.value = formatIDR(monthly);
            }
        }

        // Global Formatters
        document.querySelectorAll('.currency-input').forEach(input => {
            if(input.id === 'dpNominalDisplayInput') return; // Skip our custom one as we handled it
            input.addEventListener('input', function (e) {
                let cursorPosition = this.selectionStart;
                let oldLength = this.value.length;
                let val = this.value.replace(/\D/g, '');
                if (val !== '') this.value = Number(val).toLocaleString('id-ID');
                else this.value = '';
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
            // Ensure dpInput is correct
            // backend uses 'down_payment' (dpInput) or 'dp_percent'
        });

        [basePrice, discount, extraPpjb, extraShm, extraOther, dpPercentInput, tenorInput, paymentMethod].forEach(el => el?.addEventListener('input', recalc));
        bookingFeeIncluded?.addEventListener('change', recalc);

        lotSelect?.addEventListener('change', () => {
            hydrateFromLot();
            autoFetchLotPricing(lotSelect.value, { forceDpDefaults: true });
        });

        // Initialize
        hydrateFromLot();
        autoFetchLotPricing(lotSelect?.value || '');
        recalc();
    </script>
    @endpush
@endsection