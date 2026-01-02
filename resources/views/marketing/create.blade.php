@extends('layouts.app')

@section('content')
    <div class="page-heading" style="margin-bottom:24px;">
        <div>
            <h2 style="font-size:20px; font-weight:700; color:#1E293B; margin:0;">Tambah Salesman</h2>
            <p style="font-size:12px; color:#64748B; margin:4px 0 0 0;">Tambah anggota tim marketing</p>
        </div>
    </div>
    <form action="{{ route('marketing.store') }}" method="POST" class="card" style="max-width:500px; gap:10px;">
        @csrf
        <div class="field">
            <label class="hint">Nama <span style="color:red">*</span></label>
            <input class="input" type="text" name="name" required>
        </div>
        <div class="field">
            <label class="hint">Telepon</label>
            <input class="input" type="text" name="phone">
        </div>
        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <a class="btn light" href="{{ route('marketing.index') }}">Batal</a>
            <button class="btn primary" type="submit">Simpan</button>
        </div>
    </form>
@endsection