@extends('layouts.app')

@section('content')
    <div class="page-heading" style="margin-bottom:24px;">
        <div>
            <h2 style="font-size:20px; font-weight:700; color:#1E293B; margin:0;">Tambah Buyer</h2>
            <p style="font-size:12px; color:#64748B; margin:4px 0 0 0;">Masukkan data pelanggan baru</p>
        </div>
    </div>
    <form action="{{ route('buyers.store') }}" method="POST" class="card" style="max-width:600px; gap:10px;">
        @csrf
        @if(isset($fromSale))
            <input type="hidden" name="from_sale" value="{{ $fromSale }}">
        @endif
        <div class="field">
            <label class="hint">Nama <span style="color:red">*</span></label>
            <input class="input" type="text" name="name" required>
        </div>
        <div class="field">
            <label class="hint">Telepon <span style="color:red">*</span></label>
            <input class="input" type="text" name="phone" required>
        </div>
        <div class="field">
            <label class="hint">Email</label>
            <input class="input" type="email" name="email">
        </div>
        <div class="field">
            <label class="hint">Alamat</label>
            <textarea class="input" name="address" rows="2"></textarea>
        </div>
        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <a class="btn light" href="{{ route('buyers.index') }}">Batal</a>
            <button class="btn primary" type="submit">Simpan</button>
        </div>
    </form>
@endsection