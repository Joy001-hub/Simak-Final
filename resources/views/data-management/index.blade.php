@extends('layouts.app')

@section('content')
    <div class="page-heading" style="margin-bottom:24px;">
        <div>
            <h2 style="font-size:20px; font-weight:700; color:#1E293B; margin:0;">Manajemen Data</h2>
            <p style="font-size:12px; color:#64748B; margin:4px 0 0 0;">Kelola data contoh dan backup</p>
        </div>
    </div>

    <div class="panel-grid grid-2" style="gap:18px;">
        <div class="card" style="gap:10px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
                <div>
                    <h4 class="panel-title" style="margin:0;">Data Contoh</h4>
                    <p class="panel-sub" style="margin:4px 0 0;">Isi aplikasi dengan data contoh 3 tahun untuk
                        demo/pengujian atau kosongkan semua data agar kembali fresh.</p>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <form action="{{ route('data.demo') }}" method="POST">
                        @csrf
                        <button class="btn primary" type="submit"
                            onclick="return confirm('Muat ulang data contoh? Data yang ada akan diganti.');"
                            style="padding:8px 12px; box-shadow:none;">Muat Ulang Data Contoh</button>
                    </form>
                    <form action="{{ route('data.reset') }}" method="POST">
                        @csrf
                        <button class="btn" type="submit"
                            onclick="return confirm('Kosongkan semua data agar aplikasi fresh? Tindakan ini tidak dapat dibatalkan.');"
                            style="padding:8px 12px; background:#fff; color:#b31334; border:1px solid #e2e8f0;">Reset ke
                            Data Kosong</button>
                    </form>
                </div>
            </div>
            <div class="hint" style="margin-top:6px;">Status: {{ $stats['projects'] ?? 0 }} proyek,
                {{ $stats['lots'] ?? 0 }} kavling, {{ $stats['buyers'] ?? 0 }} buyer, {{ $stats['sales'] ?? 0 }} penjualan.
            </div>
        </div>

        <div class="card" style="gap:10px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
                <div>
                    <h5 class="panel-title" style="margin:0;">Backup & Restore</h5>
                    <p class="panel-sub" style="margin:4px 0 0;">Ekspor data ke file JSON atau pulihkan dari backup.</p>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <a class="btn primary" href="{{ route('data.backup') }}"
                        style="padding:8px 12px; box-shadow:none;">Unduh Backup</a>
                </div>
            </div>
            <form action="{{ route('data.restore') }}" method="POST" enctype="multipart/form-data"
                style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                @csrf
                <input type="file" name="backup_file" accept="application/json" class="input" style="padding:8px;" required>
                <button class="btn" type="submit" style="padding:8px 12px;">Pulihkan</button>
            </form>
        </div>

        <div class="card" style="gap:10px; grid-column: 1 / -1;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <div>
                    <h5 class="panel-title" style="margin:0;">Upgrade Simak Pro</h5>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <a class="btn primary" href="{{ config('services.sejoli.upgrade_url') }}" target="_blank"
                        rel="noopener noreferrer" style="padding:8px 12px; box-shadow:none;">Upgrade Akun</a>
                </div>
            </div>
        </div>
    </div>
@endsection
