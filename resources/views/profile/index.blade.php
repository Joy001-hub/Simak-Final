@extends('layouts.app')

@section('content')
    <div class="page-heading" style="margin-bottom:24px;">
        <div>
            <h2 style="font-size:20px; font-weight:700; color:#1E293B; margin:0;">Profil Perusahaan</h2>
            <p style="font-size:12px; color:#64748B; margin:4px 0 0 0;">Kelola informasi perusahaan</p>
        </div>
    </div>




    <form id="companyForm" action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="card"
            style="width: 100%; gap: 18px; padding:22px; box-shadow:0 18px 36px rgba(17,24,39,0.06); border:1px solid #e5e7eb;">
            <h3 class="panel-title" style="padding:0 0 6px 0;">Informasi Dasar</h3>
            <div class="grid-2" style="column-gap:18px; row-gap:12px;">
                <div class="field">
                    <label class="hint">Nama Perusahaan <span style="color:red">*</span></label>
                    <input class="input sm" type="text" name="name" value="{{ old('name', $company?->name) }}" required>
                </div>
                <div class="field">
                    <label class="hint">NPWP <span style="color:red">*</span></label>
                    <input class="input sm" type="text" name="npwp" value="{{ old('npwp', $company?->npwp) }}" required>
                </div>
                <div class="field">
                    <label class="hint">Email <span style="color:red">*</span></label>
                    <input class="input sm" type="email" name="email" value="{{ old('email', $company?->email) }}" required>
                </div>
                <div class="field">
                    <label class="hint">Telepon <span style="color:red">*</span></label>
                    <input class="input sm" type="text" name="phone" value="{{ old('phone', $company?->phone) }}" required>
                </div>
            </div>
            <div class="field">
                <label class="hint">Alamat <span style="color:red">*</span></label>
                <textarea class="input sm" name="address" rows="2"
                    required>{{ old('address', $company?->address) }}</textarea>
            </div>

            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 4px 0 4px;">
            <h3 class="panel-title" style="padding:0 0 6px 0;">Pengaturan Cetakan</h3>
            <div class="upload-box"
                style="justify-content:flex-start; gap:14px; align-items:center; padding:14px 16px; border:1px dashed #cbd5e1; border-radius:14px; background:#f8fafc;">

                <input id="logoUpload" name="logo" type="file" accept="image/*">
                <input type="file" name="logo" id="logoInput" class="d-none" accept="image/*"
                    onchange="previewImage(event)">

                <label for="logoInput" class="btn btn-light btn-sm mb-2" style="cursor: pointer;">
                    Upload Logo
                </label>

                <div class="preview-box">
                    @php
                        // Logika server-side yang tadi (tetap dipakai untuk load awal)
                        $logoPath = $company->logo_path ?? '';
                        $filename = basename($logoPath);
                        if ($logoPath) {
                            $displayUrl = url('/native-img/logos/' . $filename) . '?v=' . time();
                        } else {
                            $displayUrl = '/logo-profile.png';
                        }
                    @endphp

                    <img id="logoPreview" src="{{ $displayUrl }}" alt="Logo Preview"
                        style="max-height: 150px; width: auto; object-fit: contain; border: 1px solid #eee; border-radius: 8px;"
                        onerror="this.onerror=null;this.src='/logo-profile.png';">
                </div>
                <div style="display:flex; flex-direction:column; gap:4px;">
                    <span class="hint">Format: 512x512, PNG/JPG, maks 1 MB.</span>
                    <span class="hint">Rekomendasi: background transparan.</span>
                </div>
            </div>

            <div class="grid-2" style="column-gap:18px;">
                <div class="field">
                    <label class="hint">Nama Tanda Tangan Admin <span style="color:red">*</span></label>
                    <input class="input sm" type="text" name="signer_name"
                        value="{{ old('signer_name', $company?->signer_name) }}" required>
                </div>
                <div class="field">
                    <label class="hint">Catatan Kaki Cetakan <span style="color:red">*</span></label>
                    <input class="input sm" type="text" name="footer_note"
                        value="{{ old('footer_note', $company?->footer_note) }}" required>
                </div>
            </div>

            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 4px 0 4px;">
            <h3 class="panel-title" style="padding:0 0 6px 0;">Format Penomoran</h3>
            <p class="hint" style="margin:0 0 10px 0;">Gunakan: {YYYY} = Tahun, {MM} = Bulan, {DD} = Hari, {####} = nomor
                urut (4 digit).</p>
            <div class="grid-2" style="column-gap:18px; row-gap:12px;">
                <div class="field">
                    <label class="hint">Format Faktur <span style="color:red">*</span></label>
                    <input class="input sm" type="text" name="invoice_format"
                        value="{{ old('invoice_format', $company?->invoice_format ?? 'INV/{YYYY}{MM}/{####}') }}" required>
                </div>
                <div class="field">
                    <label class="hint">Format Kwitansi <span style="color:red">*</span></label>
                    <input class="input sm" type="text" name="receipt_format"
                        value="{{ old('receipt_format', $company?->receipt_format ?? 'KW/{YYYY}{MM}/{####}') }}" required>
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; margin-top:6px;">
                <button class="btn primary" type="submit" form="companyForm" style="min-width:160px;">Simpan
                    Perubahan</button>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            const logoUpload = document.getElementById('logoUpload');
            const logoPreview = document.getElementById('logoPreview');

            logoUpload?.addEventListener('change', (event) => {
                const file = event.target.files?.[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (logoPreview && e.target?.result) {
                        logoPreview.src = `${e.target.result}`;
                    }
                };
                reader.readAsDataURL(file);
            });
        </script>
        <script>
            function previewImage(event) {
                var reader = new FileReader();
                var imageField = document.getElementById("logoPreview");

                reader.onload = function () {
                    if (reader.readyState == 2) {
                        // Ganti src gambar dengan hasil file yang baru dipilih
                        imageField.src = reader.result;
                    }
                }

                // Baca file yang dipilih user
                if (event.target.files[0]) {
                    reader.readAsDataURL(event.target.files[0]);
                }
            }
        </script>
    @endpush
@endsection