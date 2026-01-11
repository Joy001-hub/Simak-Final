@extends('layouts.app')

@section('content')
    <div class="page-heading" style="margin-bottom:24px;">
        <div>
            <h2 style="font-size:20px; font-weight:700; color:#1E293B; margin:0;">Upgrade ke Cloud</h2>
            <p style="font-size:12px; color:#64748B; margin:4px 0 0 0;">Pindahkan data ke Neon dan aktifkan mode cloud.</p>
        </div>
    </div>

    <div class="panel-grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
        <div class="card" style="gap:10px;">
            <div>
                <h4 class="panel-title" style="margin:0;">Status Subscription</h4>
                <p class="panel-sub" style="margin:4px 0 0;">
                    {{ $subscriptionStatus === 'active' ? 'Aktif (Premium)' : 'Belum aktif (Basic)' }}
                </p>
                @if (!empty($subscriptionExpiresLabel))
                    <p class="hint" style="margin-top:6px;">
                        Langganan diperpanjang hingga {{ $subscriptionExpiresLabel }}
                    </p>
                @elseif ($subscriptionStatus === 'active')
                    <p class="hint" style="margin-top:6px;">Langganan aktif (tanpa tanggal berakhir).</p>
                @endif
                @if (config('license.upgrade_price'))
                    <p class="hint" style="margin-top:6px;">Harga: {{ config('license.upgrade_price') }}</p>
                @endif
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <form action="{{ route('license.upgrade.check') }}" method="POST">
                    @csrf
                    <button class="btn" type="submit" style="padding:8px 12px;">Refresh Status</button>
                </form>
                @if (!empty($upgradeUrl))
                    <a class="btn primary" href="{{ $upgradeUrl }}" target="_blank"
                        style="padding:8px 12px; box-shadow:none;">Buka Halaman Subscription</a>
                @endif
            </div>
        </div>

        @if ($subscriptionStatus === 'active')
            <div class="card" style="gap:10px;">
                <div>
                    <h4 class="panel-title" style="margin:0;">Perangkat</h4>
                    <p class="panel-sub" style="margin:4px 0 0;">
                        @if ($deviceStats)
                            Terdaftar: {{ $deviceStats['active'] }} / {{ $deviceStats['limit'] }}
                        @else
                            Data perangkat belum tersedia.
                        @endif
                    </p>
                </div>
                <form action="{{ route('license.upgrade.migrate') }}" method="POST"
                    style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    @csrf
                    <label style="font-size:12px; color:#64748B;">Mode migrasi</label>
                    <select name="mode" class="input" style="padding:8px;">
                        <option value="merge">Merge (gabung data)</option>
                        <option value="replace">Replace (hapus data cloud lalu isi)</option>
                    </select>
                    <button class="btn primary" type="submit" style="padding:8px 12px; box-shadow:none;"
                        onclick="return confirm('Mulai migrasi data ke cloud?');">Mulai Migrasi</button>
                </form>
            </div>

            <div class="card" style="gap:10px;">
                <div>
                    <h4 class="panel-title" style="margin:0;">Add-on Perangkat</h4>
                    <p class="panel-sub" style="margin:4px 0 0;">
                        Tambah kuota perangkat untuk akun premium.
                    </p>
                    @if ($deviceStats)
                        <p class="hint" style="margin-top:6px;">Limit sekarang: {{ $deviceStats['limit'] }} perangkat.</p>
                    @endif
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                    @if (!empty($addonUrl))
                        <a class="btn primary" href="{{ $addonUrl }}" target="_blank"
                            style="padding:8px 12px; box-shadow:none;">Beli Add-on</a>
                    @endif
                    <form action="{{ route('license.upgrade.devices') }}" method="POST"
                        style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                        @csrf
                        <input type="number" name="add_devices" min="1" max="10" value="1" class="input"
                            style="padding:8px; width:120px;">
                        <button class="btn" type="submit" style="padding:8px 12px;">Tambah Kuota</button>
                    </form>
                </div>
                <p class="hint" style="margin:0;">Masukkan jumlah perangkat tambahan setelah pembelian add-on.</p>
            </div>
        @endif
    </div>
@endsection
