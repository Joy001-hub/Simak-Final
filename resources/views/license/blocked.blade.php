@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="max-w-md w-full bg-white shadow-lg rounded-xl p-8 space-y-6 text-center">
        <div class="space-y-2">
            <h1 class="text-xl font-bold text-gray-900">Validasi Diperlukan</h1>
            <p class="text-sm text-gray-600">
                Sistem sedang melakukan validasi akun. Mohon aktifkan internet sampai proses selesai.
            </p>
            <p class="text-sm text-red-600 font-semibold">
                Wajib connect internet untuk validasi mingguan.
            </p>
            @if ($errors->any())
                <div class="text-sm text-red-600">
                    {{ $errors->first() }}
                </div>
            @endif
            @if (session('success'))
                <div class="text-sm text-green-600">
                    {{ session('success') }}
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('license.revalidate') }}">
            @csrf
            <button type="submit"
                class="w-full px-4 py-2 rounded-md bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">
                Sudah mengaktifkan internet?
            </button>
        </form>

        <p class="text-xs text-gray-500">
            Aplikasi tidak bisa digunakan sampai koneksi internet aktif dan validasi berhasil.
        </p>
    </div>
</div>
@endsection
