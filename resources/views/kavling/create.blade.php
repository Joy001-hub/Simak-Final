@extends('layouts.app')

@section('content')
    <div class="page-heading" style="margin-bottom:24px;">
        <div>
            <h2 style="font-size:20px; font-weight:700; color:#1E293B; margin:0;">Tambah Kavling</h2>
            <p style="font-size:12px; color:#64748B; margin:4px 0 0 0;">Buat unit kavling baru</p>
        </div>
    </div>
    <form action="{{ route('kavling.store') }}" method="POST" class="card" style="max-width:600px; gap:10px;">
        @csrf
        <input type="hidden" name="mode" id="inputMode" value="single">

        <div style="display:flex; justify-content:flex-end; margin-bottom:10px;">
            <div style="background:#f1f5f9; padding:4px; border-radius:8px; display:inline-flex;">
                <button type="button" id="btnSingle" class="btn is-active"
                    style="padding:6px 12px; font-size:12px; border:none; box-shadow:none; background:#fff;">Satuan</button>
                <button type="button" id="btnBulk" class="btn ghost"
                    style="padding:6px 12px; font-size:12px; border:none;">Banyak (Bulk)</button>
            </div>
        </div>

        <div class="field">
            <label class="hint">Project</label>
            <select class="input" name="project_id" required>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Single Mode Input --}}
        <div id="singleModeFields" class="field">
            <label class="hint">Blok/Number <span style="color:red">*</span></label>
            <input class="input @error('block_number') input-error @enderror" type="text" name="block_number"
                id="singleBlockNumber" placeholder="Contoh: A-10" required>
            @error('block_number')
                <div class="field-error"
                    style="color:#dc2626; font-size:12px; margin-top:4px; display:flex; align-items:center; gap:4px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <circle cx="12" cy="16" r="1" fill="currentColor" />
                    </svg>
                    {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Bulk Mode Inputs --}}
        <div id="bulkModeFields"
            style="display:none; flex-direction:column; gap:10px; background:#f8fafc; padding:14px; border-radius:10px; border:1px dashed #cbd5e1;">
            <div class="grid-2" style="column-gap:10px;">
                <div class="field">
                    <label class="hint">Blok Prefix <span style="color:red">*</span></label>
                    <input class="input" type="text" name="bulk_prefix" placeholder="Contoh: A" id="bulkPrefixInput">
                </div>
                <div class="field">
                    <label class="hint">Suffix (Opsional)</label>
                    <input class="input" type="text" name="bulk_suffix" placeholder="Contoh: B">
                </div>
            </div>
            <div class="grid-2" style="column-gap:10px;">
                <div class="field">
                    <label class="hint">Mulai Angka <span style="color:red">*</span></label>
                    <input class="input" type="number" name="bulk_start" min="1" placeholder="1" id="bulkStartInput">
                </div>
                <div class="field">
                    <label class="hint">Sampai Angka <span style="color:red">*</span></label>
                    <input class="input @error('bulk_end') input-error @enderror" type="number" name="bulk_end" min="1"
                        placeholder="10" id="bulkEndInput">
                    @error('bulk_end')
                        <div class="field-error"
                            style="color:#dc2626; font-size:12px; margin-top:4px; display:flex; align-items:center; gap:4px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <circle cx="12" cy="16" r="1" fill="currentColor" />
                            </svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
            <p style="font-size:11px; color:#64748B; margin:0;">
                Akan membuat kavling: <strong><span id="previewFirst">-</span></strong> s/d <strong><span
                        id="previewLast">-</span></strong>
            </p>
        </div>

        <div class="grid-2" style="column-gap:10px;">
            <div class="field">
                <label class="hint">Luas (m²) <span style="color:red">*</span></label>
                <input class="input" type="number" name="area" min="0" required>
            </div>
            <div class="field">
                <div class="field">
                    <label class="hint">Harga Dasar (Rp) <span style="color:red">*</span></label>
                    <input class="input currency-input" type="text" name="base_price" min="0" required
                        value="{{ old('base_price') ? number_format((float) str_replace('.', '', old('base_price')), 0, ',', '.') : '' }}">
                </div>
            </div>

            <div class="field">
                <label class="hint">Status</label>
                <select class="input" name="status">
                    <option value="available">Available</option>
                    <option value="sold">Sold</option>
                    <option value="reserved">Reserved</option>
                    <option value="active">Active</option>
                </select>
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:10px;">
                <a class="btn light" href="{{ route('kavling.index') }}">Batal</a>
                <button class="btn primary" type="submit">Simpan</button>
            </div>
    </form>

    <script>
        // Currency Formatting Logic
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
            });
        });

        document.querySelector('form').addEventListener('submit', function (e) {
            document.querySelectorAll('.currency-input').forEach(input => {
                input.value = input.value.replace(/\./g, '');
            });
        });

        const btnSingle = document.getElementById('btnSingle');
        const btnBulk = document.getElementById('btnBulk');
        const inputMode = document.getElementById('inputMode');
        const singleFields = document.getElementById('singleModeFields');
        const bulkFields = document.getElementById('bulkModeFields');
        const singleInput = document.getElementById('singleBlockNumber');

        // Preview Elements
        const bulkPrefix = document.getElementsByName('bulk_prefix')[0];
        const bulkSuffix = document.getElementsByName('bulk_suffix')[0];
        const bulkStart = document.getElementsByName('bulk_start')[0];
        const bulkEnd = document.getElementsByName('bulk_end')[0];
        const previewFirst = document.getElementById('previewFirst');
        const previewLast = document.getElementById('previewLast');

        function toggleMode(mode) {
            inputMode.value = mode;
            if (mode === 'single') {
                btnSingle.classList.add('is-active');
                btnSingle.style.background = '#fff';
                btnBulk.classList.remove('is-active');
                btnBulk.style.background = 'transparent';

                singleFields.style.display = 'block';
                bulkFields.style.display = 'none';

                singleInput.required = true;
                bulkPrefix.required = false;
                bulkStart.required = false;
                bulkEnd.required = false;
            } else {
                btnBulk.classList.add('is-active');
                btnBulk.style.background = '#fff';
                btnSingle.classList.remove('is-active');
                btnSingle.style.background = 'transparent';

                singleFields.style.display = 'none';
                bulkFields.style.display = 'flex';

                singleInput.required = false;
                bulkPrefix.required = true;
                bulkStart.required = true;
                bulkEnd.required = true;
            }
        }

        btnSingle.addEventListener('click', () => toggleMode('single'));
        btnBulk.addEventListener('click', () => toggleMode('bulk'));

        function updatePreview() {
            const prefix = bulkPrefix.value.trim();
            const suffix = bulkSuffix.value.trim();
            const start = bulkStart.value;
            const end = bulkEnd.value;

            if (prefix && start) {
                const sfx = suffix ? ` ${suffix}` : '';
                previewFirst.textContent = `${prefix}-${start}${sfx}`;
            } else {
                previewFirst.textContent = '-';
            }

            if (prefix && end) {
                const sfx = suffix ? ` ${suffix}` : '';
                previewLast.textContent = `${prefix}-${end}${sfx}`;
            } else {
                previewLast.textContent = '-';
            }
        }

        [bulkPrefix, bulkSuffix, bulkStart, bulkEnd].forEach(el => {
            el.addEventListener('input', updatePreview);
        });

        // Restore mode if there was an error (page reloaded with old input)
        @if(old('mode') === 'bulk' || $errors->has('bulk_end'))
            toggleMode('bulk');
            updatePreview();
        @endif
    </script>
@endsection
