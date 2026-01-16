@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="max-w-md w-full bg-white shadow-lg rounded-xl p-8 space-y-4 text-center">
        <h1 class="text-xl font-bold text-gray-900">Validasi Diperlukan</h1>
        <p class="text-sm text-gray-600">
            Internet Connection Required for Weekly Validation.
        </p>
        <p class="text-xs text-gray-500">
            Aktifkan internet, lalu coba lagi. Sistem akan membuka otomatis saat koneksi kembali.
        </p>
    </div>
</div>
@endsection

