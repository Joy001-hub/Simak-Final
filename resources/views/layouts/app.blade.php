<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} | Dashboard</title>
    <meta name="turbo-cache-control" content="no-cache">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%239c0f2f'/%3E%3Cpath d='M18 20h28v8H34v16H18V20Zm14 8h14v16H32V28Z' fill='%23fff'/%3E%3C/svg%3E">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .content-area {
            flex: 1;
            padding: 20px;
            min-width: 0;
        }

        .content-area .page {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            margin: 0 auto;
            padding: 0;
        }

        .table-responsive {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
        }

        .table-clean th,
        .table-clean td {
            white-space: nowrap;
            font-size: 13px;
        }
    </style>
    @stack('styles')
</head>

<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark logo" aria-label="SIMAK Logo"
                    style="width: 100%; max-width: 16rem; height: auto;">
                    <img class="app-logo-img" src="{{ asset('/logo-app.png') }}" alt="SIMAK Logo"
                        onerror="this.onerror=null;this.src='{{ asset('/logo-simak.svg') }}';"
                        style="width: 100%; height: auto; object-fit: contain;">
                </div>
                <div class="brand-text">
                    <span class="brand-sub">Sistem Informasi Manajemen Kavling</span>
                </div>
            </div>

            <nav class="nav">
                <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    href="{{ route('dashboard') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path
                            d="M4 11.5 12 4l8 7.5V20a1 1 0 0 1-1 1h-4.5a.5.5 0 0 1-.5-.5v-4a1 1 0 0 0-1-1h-3a1 1 0 0 0-1 1v4a.5.5 0 0 1-.5.5H5a1 1 0 0 1-1-1v-8.5Z" />
                    </svg>
                    <span class="label">Dashboard</span>
                </a>
                <a class="nav-item {{ request()->routeIs('penjualan.*') ? 'active' : '' }}"
                    href="{{ route('penjualan.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="3" y="6" width="18" height="12" rx="2" ry="2" />
                        <path d="M7 10h10M7 14h6" />
                    </svg>
                    <span class="label">Penjualan</span>
                </a>
                <a class="nav-item {{ request()->routeIs('projects.*') ? 'active' : '' }}"
                    href="{{ route('projects.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M5 5h14v6H5zM5 13h8v6H5zM15 13h4v6h-4z" />
                    </svg>
                    <span class="label">Projects</span>
                </a>
                <a class="nav-item {{ request()->routeIs('kavling.*') ? 'active' : '' }}"
                    href="{{ route('kavling.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="4" y="4" width="7" height="7" rx="1.5" />
                        <rect x="4" y="13" width="7" height="7" rx="1.5" />
                        <rect x="13" y="4" width="7" height="7" rx="1.5" />
                        <path d="M16 15h4v5h-4z" />
                    </svg>
                    <span class="label">Kavling</span>
                </a>
                <a class="nav-item {{ request()->routeIs('buyers.*') ? 'active' : '' }}"
                    href="{{ route('buyers.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm-8 8a8 8 0 0 1 16 0" />
                    </svg>
                    <span class="label">Buyer</span>
                </a>
                <a class="nav-item {{ request()->routeIs('marketing.*') ? 'active' : '' }}"
                    href="{{ route('marketing.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <circle cx="8" cy="7" r="3" />
                        <circle cx="17" cy="7" r="3" />
                        <path d="M3 20a5 5 0 0 1 10 0M11 20h10a4 4 0 0 0-4-4h-2" />
                    </svg>
                    <span class="label">Tim Marketing</span>
                </a>
                <a class="nav-item {{ request()->routeIs('data.*') ? 'active' : '' }}" href="{{ route('data.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="3" y="4" width="18" height="6" rx="1.5" />
                        <rect x="3" y="14" width="18" height="6" rx="1.5" />
                        <path d="M7 7h.01M12 7h.01M17 7h.01M7 17h.01M12 17h.01M17 17h.01" />
                    </svg>
                    <span class="label">Manajemen Data</span>
                </a>
                <a class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                    href="{{ route('profile.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="4" y="4" width="16" height="16" rx="2" />
                        <path d="M8 11h8M8 15h6" />
                    </svg>
                    <span class="label">Profil Perusahaan</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-card" style="display:flex; align-items:center; gap:10px; min-height:56px;">
                    <div class="upload-preview"
                        style="width:48px; height:48px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
                        @php
                            // 1. Ambil nama file-nya saja (misal: "gambar.png") dari URL yang error tadi
                            $filename = basename($companyLogo);

                            // 2. Arahkan ke route baru di web.php (/native-img/logos/...)
                            // 3. Tambahkan time() agar kalau ganti logo, langsung berubah (anti-cache)
                            $nativeUrl = url('/native-img/logos/' . $filename) . '?v=' . time();
                        @endphp

                        <img class="company-logo-img" src="{{ $nativeUrl }}" alt="Logo perusahaan"
                            onerror="this.onerror=null;this.src='{{ asset('logo-profile.png') }}';">
                    </div>
                    <div style="display:flex; flex-direction:column; gap:2px; overflow:hidden;">
                        <div class="brand-sub"
                            style="color:#0f172a; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:180px;">
                            {{ optional($companyProfile ?? null)->name ?? config('company.name') }}
                        </div>
                        <div class="hint"
                            style="color:#475569; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:180px;">
                            {{ optional($companyProfile ?? null)->email ?? config('company.email') }}
                        </div>
                    </div>
                </div>
                <a id="sidebarLogoutBtn" href="{{ route('logout') }}"
                    style="margin-top:12px; width:100%; display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:10px 12px; border-radius:12px; border:1px solid #fecdd3; background:#fff1f2; color:#b91c1c; font-weight:700; cursor:pointer; transition:background 0.2s ease; text-decoration:none;"
                    onmouseover="this.style.background='#ffe4e6'" onmouseout="this.style.background='#fff1f2'">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.7" width="18" height="18">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                    </svg>
                    Keluar
                </a>
            </div>
        </aside>

        <div class="content-area">
            <main class="page" style="padding-bottom: 80px;">
                @yield('content')
            </main>
            <footer
                style="position: fixed; bottom: 0; left: 280px; right: 0; z-index: 20; background: rgba(249, 251, 255, 0.95); border-top: 1px solid #e2e8f0;">
                <div
                    style="width: 100%; max-width: 1500px; margin: 0 auto; padding: 20px 28px; text-align: center; font-size: 12px; color: #94a3b8; font-weight: 500;">
                    SIMAK™ &copy; 2025. All rights reserved.
                </div>
            </footer>
        </div>
    </div>
    <div id="toast" class="floating-alert" ari&times;live="polite" aria-label="Notifikasi"></div>
    <div id="bellButton" class="bell-floating">
        <div class="bell-icon">
            <svg viewBox="0 0 48 48" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round"
                stroke-linejoin="round" ari&times;hidden="true">
                <path
                    d="M24 10c-6.6 0-12 5.4-12 12v6.5c0 1-.4 2-1.1 2.7l-1.3 1.3c-.2.2 0 .5.3.5h28c.3 0 .4-.3.3-.5l-1.3-1.3A3.8 3.8 0 0 1 36 28.5V22c0-6.6-5.4-12-12-12Z" />
                <path d="M20 34a4 4 0 0 0 8 0" />
            </svg>
        </div>
        @if(($overdueCount ?? 0) > 0)
            <span class="bell-badge">{{ $overdueCount }}</span>
        @endif
    </div>

    <div id="bellPanel" class="bell-panel" aria-label="Notifikasi penagihan">
        <div class="bell-panel-header">
            <div>
                <div class="bell-title">Daftar Penagihan Mendesak</div>
                <div class="bell-sub">Total {{ $overdueCount ?? 0 }} angsuran dari {{ count($overduePayments ?? []) }}
                    pelanggan telah jatuh tempo.
                </div>
            </div>
            <button id="bellClose" type="button" aria-label="Tutup notifikasi"
                style="background:transparent; border:none; color:#fff; font-size:16px; cursor:pointer; line-height:1; font-weight:600;">×</button>
        </div>
        <div class="bell-panel-body">
            @forelse($overduePayments ?? [] as $payment)
                @php
                    $sale = $payment->sale;
                    $buyer = optional($sale)->buyer;
                    $totalOverdue = $payment->total_overdue_amount ?? $payment->amount;
                    $overduePaymentCount = $payment->overdue_payment_count ?? 1;
                    $kavlingList = $payment->kavling_list ?? [];
                    $lateDaysRaw = $payment->due_date ? \Carbon\Carbon::parse($payment->due_date)->diffInDays(\Carbon\Carbon::now()) : 0;
                    $lateDays = max(0, (int) $lateDaysRaw);
                    $totalMonths = intdiv($lateDays, 30);
                    $remainingAfterMonths = $lateDays % 30;
                    $years = intdiv($totalMonths, 12);
                    $months = $totalMonths % 12;
                    $weeks = intdiv($remainingAfterMonths, 7);
                    $days = $remainingAfterMonths % 7;
                    $parts = [];
                    if ($years > 0)
                        $parts[] = $years . ' tahun';
                    if ($months > 0)
                        $parts[] = $months . ' bulan';
                    if ($weeks > 0)
                        $parts[] = $weeks . ' minggu';
                    if ($days > 0 || empty($parts))
                        $parts[] = $days . ' hari';
                    $lateLabel = implode(', ', $parts);
                @endphp
                <div class="bell-card">
                    <div class="bell-card-content">
                        <strong class="bell-card-name">{{ $buyer->name ?? 'Tidak diketahui' }}</strong>
                        <div class="bell-card-info">
                            <div class="bell-info-row">
                                <span class="bell-info-label">{{ $overduePaymentCount }} angsuran tertunggak</span>
                                @if(count($kavlingList) > 1)
                                    <span class="bell-info-kavling"
                                        style="font-size:11px; color:#64748b;">({{ count($kavlingList) }} kavling)</span>
                                @endif
                                <br />
                                <span class="bell-info-value">Total: Rp
                                    {{ number_format($totalOverdue, 0, ',', '.') }}</span>
                            </div>
                            <span class="bell-info-late">Terlambat {{ $lateLabel }}</span>
                        </div>
                    </div>
                    @if(count($kavlingList) === 1)
                        <a class="bell-btn" href="{{ route('penjualan.show', $kavlingList[0]['sale_id']) }}">Lihat Detail</a>
                    @else
                        <button type="button" class="bell-btn bell-btn-popup" data-kavling='@json($kavlingList)'
                            data-buyer="{{ $buyer->name ?? 'Pembeli' }}">Lihat Detail</button>
                    @endif
                </div>
            @empty
                <p class="bell-empty">Tidak ada penagihan jatuh tempo.</p>
            @endforelse
        </div>
    </div>


    <style>
        .bell-floating {
            position: fixed;
            bottom: 90px;
            right: 24px;
            z-index: 50;
        }

        .bell-icon {
            width: 54px;
            height: 54px;
            background: linear-gradient(145deg, #8e0f2a, #a21535);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 22px rgba(156, 15, 47, 0.24);
            cursor: pointer;
        }

        .bell-badge {
            position: absolute;
            top: -6px;
            right: -8px;
            background: #e11d48;
            color: #fff;
            border-radius: 999px;
            min-width: 20px;
            padding: 3px 6px;
            font-size: 12px;
            font-weight: 800;
            border: 2px solid #fff;
            text-align: center;
            line-height: 1;
        }

        .bell-panel {
            position: fixed;
            top: 0;
            right: -420px;
            width: 360px;
            height: 100vh;
            background: #fff;
            box-shadow: -8px 0 24px rgba(0, 0, 0, 0.12);
            z-index: 49;
            display: flex;
            flex-direction: column;
            transition: right 0.25s ease;
        }

        .bell-panel.active {
            right: 0;
        }

        .bell-panel-header {
            background: #b91c3b;
            padding: 16px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .bell-panel-body {
            padding: 16px;
            overflow: auto;
            flex: 1;
            background: #f9fafb;
        }

        .bell-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }

        .bell-card-content {
            flex: 1;
            min-width: 0;
        }

        .bell-card-name {
            display: block;
            color: #1f2937;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .bell-card-info {
            font-size: 12px;
            line-height: 1.4;
        }

        .bell-info-row {
            color: #666;
            margin-bottom: 4px;
        }

        .bell-info-label {
            color: #666;
        }

        .bell-info-value {
            color: #b91c3b;
            font-weight: 700;
        }

        .bell-info-late {
            color: #2563eb;
            display: block;
            margin-top: 6px;
        }

        .bell-btn {
            flex-shrink: 0;
            padding: 8px 20px;
            background: #b91c3b;
            color: #fff;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            border-radius: 8px;
            transition: background 0.2s;
            border: none;
            cursor: pointer;
            white-space: nowrap;
            display: inline-block;
        }

        .bell-btn:hover {
            background: #9a1630;
        }

        .bell-empty {
            margin: 0;
            color: #6b7280;
            font-size: 13px;
            text-align: center;
            padding: 20px;
        }

        .bell-title {
            color: #fff;
            font-weight: 700;
            font-size: 15px;
            margin: 0;
        }

        .bell-sub {
            color: #fce7eb;
            font-size: 12px;
            margin: 4px 0 0;
        }

        /* Kavling Popup Modal */
        .kavling-popup-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .kavling-popup-overlay.active {
            display: flex;
        }

        .kavling-popup {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            max-width: 360px;
            width: 100%;
            overflow: hidden;
            animation: popupIn 0.2s ease;
        }

        @keyframes popupIn {
            from {
                transform: scale(0.9);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .kavling-popup-header {
            background: linear-gradient(145deg, #b91c3b, #9a1630);
            color: #fff;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .kavling-popup-title {
            font-weight: 700;
            font-size: 15px;
        }

        .kavling-popup-close {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            opacity: 0.8;
        }

        .kavling-popup-close:hover {
            opacity: 1;
        }

        .kavling-popup-body {
            padding: 16px 20px;
        }

        .kavling-popup-subtitle {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .kavling-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .kavling-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            text-decoration: none;
            color: #1f2937;
            transition: all 0.15s ease;
        }

        .kavling-item:hover {
            background: #fee2e2;
            border-color: #fca5a5;
        }

        .kavling-item-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .kavling-item-name {
            font-weight: 600;
            font-size: 14px;
        }

        .kavling-item-detail {
            font-size: 11px;
            color: #6b7280;
        }

        .kavling-item-arrow {
            color: #9ca3af;
            font-size: 18px;
        }

        .kavling-item:hover .kavling-item-arrow {
            color: #b91c3b;
        }
    </style>
    <script>
        (function () {
            const toast = document.getElementById('toast');
            const message = @json(session('success') ?? session('error') ?? '');
            if (toast && message) {
                toast.textContent = message;
                toast.classList.add('active');
                setTimeout(() => toast.classList.remove('active'), 4000);
            }

            const bellButton = document.getElementById('bellButton');
            const bellPanel = document.getElementById('bellPanel');
            const bellClose = document.getElementById('bellClose');

            const showBellButton = () => {
                if (bellButton) bellButton.style.display = '';
            };
            const hideBellButton = () => {
                if (bellButton) bellButton.style.display = 'none';
            };

            const toggleBell = () => {
                if (!bellPanel || ({{ $overdueCount ?? 0 }} === 0)) return;
                const isOpening = !bellPanel.classList.contains('active');
                bellPanel.classList.toggle('active');
                if (isOpening) {
                    hideBellButton();
                } else {
                    showBellButton();
                }
            };

            const closeBellPanel = () => {
                bellPanel?.classList.remove('active');
                showBellButton();
            };

            bellButton?.addEventListener('click', toggleBell);
            bellClose?.addEventListener('click', closeBellPanel);
            bellPanel?.addEventListener('click', (e) => { if (e.target === bellPanel) closeBellPanel(); });
        })();

        // Logout button on sidebar - redirects to login page
        (() => {
            const btn = document.getElementById('sidebarLogoutBtn');
            if (!btn) return;
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const confirmLogout = confirm('Keluar dari SIMAK? Anda akan diarahkan ke halaman login.');
                if (!confirmLogout) return;

                // Redirect to logout route
                window.location.href = btn.getAttribute('href') || '/logout';
            });
        })();
    </script>

    <style>
        .input-error {
            border-color: #dc2626 !important;
            background-color: #fef2f2 !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1) !important;
        }

        .input-error:focus {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2) !important;
        }
    </style>

    {{-- Include Maintenance Modal Component --}}
    @include('components.maintenance-modal')

    {{-- Kavling Selection Popup --}}
    <div id="kavlingPopupOverlay" class="kavling-popup-overlay">
        <div class="kavling-popup">
            <div class="kavling-popup-header">
                <span class="kavling-popup-title" id="kavlingPopupTitle">Pilih Kavling</span>
                <button type="button" class="kavling-popup-close" id="kavlingPopupClose">&times;</button>
            </div>
            <div class="kavling-popup-body">
                <div class="kavling-popup-subtitle">Pembeli ini memiliki tunggakan di beberapa kavling:</div>
                <div class="kavling-list" id="kavlingList"></div>
            </div>
        </div>
    </div>

    <script>
        // Kavling popup handler
        (function () {
            const overlay = document.getElementById('kavlingPopupOverlay');
            const popupTitle = document.getElementById('kavlingPopupTitle');
            const kavlingList = document.getElementById('kavlingList');
            const closeBtn = document.getElementById('kavlingPopupClose');

            function formatRupiah(num) {
                return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            function showKavlingPopup(buyerName, kavlings) {
                popupTitle.textContent = buyerName;
                kavlingList.innerHTML = kavlings.map(k => `
                    <a href="/penjualan/${k.sale_id}" class="kavling-item">
                        <div class="kavling-item-info">
                            <span class="kavling-item-name">${k.kavling || 'Kavling'}</span>
                            <span class="kavling-item-detail">${k.count} angsuran · ${formatRupiah(k.amount)}</span>
                        </div>
                        <span class="kavling-item-arrow">→</span>
                    </a>
                `).join('');
                overlay.classList.add('active');
            }

            function closePopup() {
                overlay.classList.remove('active');
            }

            // Event listeners
            closeBtn?.addEventListener('click', closePopup);
            overlay?.addEventListener('click', (e) => {
                if (e.target === overlay) closePopup();
            });

            // Handle popup button clicks
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.bell-btn-popup');
                if (btn) {
                    e.preventDefault();
                    const kavlings = JSON.parse(btn.dataset.kavling || '[]');
                    const buyerName = btn.dataset.buyer || 'Pembeli';
                    showKavlingPopup(buyerName, kavlings);
                }
            });

            // Close on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && overlay.classList.contains('active')) {
                    closePopup();
                }
            });
        })();
    </script>

    {{-- Check untuk license validation failure --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Cek jika ada cache flag bahwa validasi gagal
            const licenseValidationFailed = {!! json_encode(\Illuminate\Support\Facades\Cache::get('license_validation_failed', false)) !!};

            if (licenseValidationFailed) {
                // Tampilkan modal maintenance
                window.showMaintenanceModal?.();
            }
        });
    </script>

    <script>
        // Enable horizontal scrolling for wide tables (trackpad / Shift+wheel).
        (function () {
            document.addEventListener('wheel', (e) => {
                const container = e.target?.closest?.('.table-responsive');
                if (!container) return;
                if (container.scrollWidth <= container.clientWidth) return;

                const absX = Math.abs(e.deltaX || 0);
                const absY = Math.abs(e.deltaY || 0);
                const isHorizontalGesture = absX > absY;
                const shouldScrollX = isHorizontalGesture || e.shiftKey;
                if (!shouldScrollX) return;

                const delta = (e.shiftKey && absX === 0) ? (e.deltaY || 0) : (e.deltaX || 0);
                if (!delta) return;

                container.scrollLeft += delta;
                e.preventDefault();
            }, { passive: false });
        })();
    </script>

    <script>
        (function () {
            const STYLE_ID = 'validationPopupStyle';
            const POPUP_ID = 'validationPopup';
            const MESSAGE_REQUIRED = 'please fill in this field';
            let popupEl = null;
            let currentTarget = null;

            const ensurePopup = () => {
                if (!document.getElementById(STYLE_ID)) {
                    const style = document.createElement('style');
                    style.id = STYLE_ID;
                    style.textContent = `
                        .validation-popup {
                            position: fixed;
                            z-index: 10050;
                            background: #111827;
                            color: #fff;
                            font-size: 12px;
                            line-height: 1.3;
                            padding: 8px 10px;
                            border-radius: 8px;
                            box-shadow: 0 10px 24px rgba(0,0,0,0.25);
                            max-width: 260px;
                            display: none;
                        }
                        .validation-popup-arrow {
                            position: absolute;
                            width: 0;
                            height: 0;
                            border-left: 6px solid transparent;
                            border-right: 6px solid transparent;
                        }
                        .validation-popup-arrow.is-above {
                            bottom: -6px;
                            border-top: 6px solid #111827;
                        }
                        .validation-popup-arrow.is-below {
                            top: -6px;
                            border-bottom: 6px solid #111827;
                        }
                        .validation-error {
                            outline: 2px solid #ef4444;
                            outline-offset: 2px;
                        }
                    `;
                    document.head.appendChild(style);
                }
                if (!popupEl) {
                    popupEl = document.createElement('div');
                    popupEl.id = POPUP_ID;
                    popupEl.className = 'validation-popup';
                    popupEl.setAttribute('role', 'alert');
                    popupEl.innerHTML = `
                        <div class="validation-popup-text"></div>
                        <div class="validation-popup-arrow is-above"></div>
                    `;
                    document.body.appendChild(popupEl);
                }
            };

            const clearHighlight = () => {
                if (currentTarget) {
                    currentTarget.classList.remove('validation-error');
                    currentTarget = null;
                }
            };

            const hidePopup = () => {
                if (popupEl) {
                    popupEl.style.display = 'none';
                }
                clearHighlight();
            };

            const showPopup = (target, message) => {
                if (!target || !(target instanceof Element)) return;
                ensurePopup();
                clearHighlight();
                currentTarget = target;
                currentTarget.classList.add('validation-error');

                try {
                    currentTarget.scrollIntoView({ block: 'center' });
                } catch (err) {
                    // Ignore scroll errors.
                }
                try {
                    currentTarget.focus({ preventScroll: true });
                } catch (err) {
                    currentTarget.focus();
                }

                const textEl = popupEl.querySelector('.validation-popup-text');
                if (textEl) textEl.textContent = message;
                popupEl.style.display = 'block';
                popupEl.style.left = '0px';
                popupEl.style.top = '0px';
                popupEl.style.visibility = 'hidden';

                const rect = target.getBoundingClientRect();
                const popupRect = popupEl.getBoundingClientRect();
                const margin = 10;
                const canPlaceAbove = rect.top > popupRect.height + margin;
                const placeAbove = canPlaceAbove;
                const top = placeAbove
                    ? rect.top - popupRect.height - margin
                    : rect.bottom + margin;
                let left = rect.left + (rect.width / 2) - (popupRect.width / 2);
                left = Math.max(margin, Math.min(left, window.innerWidth - popupRect.width - margin));

                const arrow = popupEl.querySelector('.validation-popup-arrow');
                if (arrow) {
                    arrow.classList.toggle('is-above', placeAbove);
                    arrow.classList.toggle('is-below', !placeAbove);
                    const arrowLeft = Math.min(
                        popupRect.width - 14,
                        Math.max(14, (rect.left + rect.width / 2) - left)
                    );
                    arrow.style.left = `${arrowLeft}px`;
                }

                popupEl.style.left = `${left}px`;
                popupEl.style.top = `${Math.max(margin, top)}px`;
                popupEl.style.visibility = 'visible';
            };

            const getInvalidField = (form) => {
                if (!form || !(form instanceof HTMLFormElement)) return null;
                return form.querySelector(':invalid');
            };

            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (!(form instanceof HTMLFormElement)) return;
                if (form.noValidate) return;

                const invalid = getInvalidField(form);
                if (!invalid) return;

                e.preventDefault();
                e.stopPropagation();

                const isMissing = invalid.validity?.valueMissing;
                const message = isMissing
                    ? MESSAGE_REQUIRED
                    : (invalid.validationMessage || 'invalid input');
                showPopup(invalid, message);
            }, true);

            document.addEventListener('input', hidePopup, true);
            document.addEventListener('change', hidePopup, true);
            document.addEventListener('focusin', (e) => {
                if (currentTarget && e.target !== currentTarget) {
                    hidePopup();
                }
            }, true);
            window.addEventListener('scroll', hidePopup, true);
            window.addEventListener('resize', hidePopup);
        })();
    </script>

    {{-- NativePHP Focus Doctor: aggressive input focus recovery --}}
    <script>
        (function () {
            const logPrefix = '[FocusDoctor]';
            const editableSelector = 'input, textarea, select, [contenteditable=""], [contenteditable="true"], [role="textbox"]';
            let lastEditable = null;
            let needsRescue = false;
            let lastResetAt = 0;
            let lastNativeFocusAt = 0;
            let lastWebContentsFocusAt = 0;

            const describeElement = (el) => {
                if (!el || !(el instanceof Element)) return null;
                const id = el.id ? `#${el.id}` : '';
                const classes = el.classList && el.classList.length ? `.${Array.from(el.classList).join('.')}` : '';
                return `${el.tagName.toLowerCase()}${id}${classes}`;
            };

            const cursorForElement = (el) => {
                if (el instanceof HTMLInputElement) {
                    const type = (el.type || '').toLowerCase();
                    const pointerTypes = new Set([
                        'checkbox',
                        'radio',
                        'range',
                        'button',
                        'submit',
                        'reset',
                        'color',
                        'file',
                        'image',
                    ]);
                    return pointerTypes.has(type) ? 'pointer' : 'text';
                }
                if (el instanceof HTMLSelectElement) {
                    return 'pointer';
                }
                return 'text';
            };

            const forceNoDrag = (el) => {
                if (!el || !(el instanceof Element)) return;
                const cursor = cursorForElement(el);
                el.classList?.add('no-drag');
                el.style.setProperty('-webkit-app-region', 'no-drag', 'important');
                el.style.setProperty('user-select', cursor === 'text' ? 'text' : 'none', 'important');
                el.style.setProperty('pointer-events', 'auto', 'important');
                el.style.setProperty('cursor', cursor, 'important');
            };

            const forceNoDragAncestors = (el) => {
                let node = el?.parentElement;
                while (node && node !== document.body) {
                    node.style.setProperty('-webkit-app-region', 'no-drag', 'important');
                    node = node.parentElement;
                }
                if (document.body) {
                    document.body.style.setProperty('-webkit-app-region', 'no-drag', 'important');
                }
            };

            const focusWebContents = (reason) => {
                const remote = window.remote;
                if (!remote?.getCurrentWebContents) return;
                const wc = remote.getCurrentWebContents();
                if (!wc) return;
                const now = Date.now();
                if (now - lastWebContentsFocusAt < 200) return;
                lastWebContentsFocusAt = now;
                try {
                    wc.focus();
                    console.log(`${logPrefix} webContents focus`, reason || '', {
                        hasFocus: document.hasFocus(),
                        wcFocused: wc.isFocused?.(),
                    });
                } catch (err) {
                    console.warn(`${logPrefix} webContents focus failed`, err);
                }
            };

            const focusNativeWindow = (reason) => {
                const remote = window.remote;
                if (!remote?.getCurrentWindow) return;
                const win = remote.getCurrentWindow();
                if (!win) return;
                const now = Date.now();
                if (now - lastNativeFocusAt < 250) return;
                lastNativeFocusAt = now;
                try {
                    if (remote.app?.focus) {
                        try {
                            remote.app.focus({ steal: true });
                        } catch (err) {
                            // Ignore app focus failures.
                        }
                    }
                    if (win.setFocusable) {
                        win.setFocusable(true);
                    }
                    if (win.isMinimized?.()) {
                        win.restore();
                    }
                    if (win.isVisible && !win.isVisible()) {
                        win.show();
                    }
                    win.focus();
                    setTimeout(() => {
                        if (!win.isFocused?.()) {
                            if (win.setAlwaysOnTop) {
                                win.setAlwaysOnTop(true);
                                setTimeout(() => win.setAlwaysOnTop(false), 200);
                            }
                            win.focus();
                        }
                    }, 60);
                    setTimeout(() => focusWebContents('native-window-focus'), 0);
                    console.log(`${logPrefix} native window focus`, reason || '', {
                        hasFocus: document.hasFocus(),
                        winFocused: win.isFocused?.(),
                    });
                } catch (err) {
                    console.warn(`${logPrefix} native focus failed`, err);
                }
            };

            const scanDragRegions = (reason) => {
                const hits = [];
                const all = document.querySelectorAll('body *');
                all.forEach((el) => {
                    const style = window.getComputedStyle(el);
                    const region = style.getPropertyValue('-webkit-app-region');
                    if (region === 'drag') {
                        hits.push(describeElement(el));
                    }
                });
                if (hits.length) {
                    console.warn(`${logPrefix} drag regions`, reason, hits);
                }
            };

            const resetWindowFocus = (reason) => {
                const now = Date.now();
                if (now - lastResetAt < 250) return;
                lastResetAt = now;
                if (!document.hasFocus()) {
                    focusNativeWindow(reason || 'reset');
                    try {
                        window.focus();
                    } catch (err) {
                        // Ignore focus failures.
                    }
                }
                if (!document.hasFocus()) {
                    focusWebContents(reason || 'reset');
                }
                console.log(`${logPrefix} focus reset`, reason || '', { hasFocus: document.hasFocus() });
            };

            const focusSandbox = () => {
                if (!document.body) return;
                const dummy = document.createElement('input');
                dummy.type = 'text';
                dummy.setAttribute('aria-hidden', 'true');
                dummy.tabIndex = -1;
                dummy.style.position = 'fixed';
                dummy.style.opacity = '0';
                dummy.style.pointerEvents = 'none';
                dummy.style.height = '1px';
                dummy.style.width = '1px';
                document.body.appendChild(dummy);
                try {
                    dummy.focus({ preventScroll: true });
                } catch (err) {
                    dummy.focus();
                }
                setTimeout(() => dummy.remove(), 0);
            };

            const rescueFocus = (target, reason) => {
                if (!target || !(target instanceof Element)) return;
                if ('disabled' in target && target.disabled) return;
                lastEditable = target;
                forceNoDrag(target);
                forceNoDragAncestors(target);
                if (!document.hasFocus()) {
                    focusNativeWindow(reason || 'rescue');
                    resetWindowFocus(reason || 'rescue');
                }
                focusWebContents(reason || 'rescue');
                focusSandbox();
                const hadReadOnly = 'readOnly' in target && target.readOnly;
                if (hadReadOnly) target.readOnly = false;
                try {
                    target.focus({ preventScroll: true });
                } catch (err) {
                    target.focus();
                }
                if (target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement) {
                    try {
                        const len = target.value?.length ?? 0;
                        target.setSelectionRange(len, len);
                    } catch (err) {
                        // Ignore selection failures.
                    }
                }
                if (hadReadOnly) target.readOnly = true;
            };

            const logClick = (event) => {
                const target = event.target;
                const topElement = document.elementFromPoint?.(event.clientX, event.clientY);
                const wc = window.remote?.getCurrentWebContents?.();
                const info = {
                    target: describeElement(target),
                    top: describeElement(topElement),
                    x: event.clientX,
                    y: event.clientY,
                    active: describeElement(document.activeElement),
                    hasFocus: document.hasFocus(),
                    webContentsFocused: wc?.isFocused?.(),
                };
                if (target instanceof Element) {
                    const style = window.getComputedStyle(target);
                    info.pointerEvents = style.pointerEvents;
                    info.zIndex = style.zIndex;
                    info.opacity = style.opacity;
                }
                if (topElement instanceof Element) {
                    const style = window.getComputedStyle(topElement);
                    info.topPointerEvents = style.pointerEvents;
                    info.topZIndex = style.zIndex;
                    info.topOpacity = style.opacity;
                }
                const path = typeof event.composedPath === 'function' ? event.composedPath() : [];
                console.log(`${logPrefix} click`, info, path.map(describeElement).filter(Boolean));
            };

            const checkOverlayCandidates = (reason) => {
                if (!document.elementsFromPoint) return;
                const x = Math.floor(window.innerWidth / 2);
                const y = Math.floor(window.innerHeight / 2);
                const stack = document.elementsFromPoint(x, y);
                const candidates = [];
                for (const el of stack) {
                    if (!(el instanceof Element)) continue;
                    const style = window.getComputedStyle(el);
                    if (style.pointerEvents === 'none') continue;
                    if (style.display === 'none' || style.visibility === 'hidden') continue;
                    const zIndex = Number.parseInt(style.zIndex, 10);
                    const opacity = Number.parseFloat(style.opacity);
                    if (!Number.isFinite(zIndex) || zIndex < 999) continue;
                    if (!Number.isFinite(opacity) || opacity > 0.05) continue;
                    const rect = el.getBoundingClientRect();
                    if (rect.width < window.innerWidth * 0.9 || rect.height < window.innerHeight * 0.9) continue;
                    candidates.push({
                        element: describeElement(el),
                        zIndex: style.zIndex,
                        opacity: style.opacity,
                        pointerEvents: style.pointerEvents,
                        position: style.position,
                    });
                }
                if (candidates.length) {
                    console.warn(`${logPrefix} overlay candidates`, reason, candidates);
                }
            };

            const isEditableTarget = (el) => el && el.matches?.(editableSelector);

            const getEditableFromPoint = (x, y) => {
                if (!document.elementsFromPoint) return null;
                const stack = document.elementsFromPoint(x, y);
                for (const el of stack) {
                    if (isEditableTarget(el)) return el;
                }
                for (const el of stack) {
                    if (el instanceof HTMLLabelElement && el.htmlFor) {
                        const input = document.getElementById(el.htmlFor);
                        if (input && isEditableTarget(input)) return input;
                    }
                }
                return null;
            };

            let nativeEventsBound = false;
            const bindNativeWindowEvents = () => {
                if (nativeEventsBound) return;
                const win = window.remote?.getCurrentWindow?.();
                if (!win?.on) return;
                nativeEventsBound = true;
                win.on('focus', () => {
                    resetWindowFocus('native-window-focus-event');
                    focusWebContents('native-window-focus-event');
                });
                win.on('blur', () => {
                    needsRescue = true;
                });
            };
            bindNativeWindowEvents();

            const handlePointer = (event) => {
                const target = event.target;
                const candidate = isEditableTarget(target)
                    ? target
                    : getEditableFromPoint(event.clientX, event.clientY);
                if (!document.hasFocus()) {
                    focusNativeWindow('pointerdown');
                    focusWebContents('pointerdown');
                }
                if (candidate) {
                    rescueFocus(candidate, 'pointerdown');
                    requestAnimationFrame(() => {
                        if (document.activeElement !== candidate) {
                            resetWindowFocus('pointerdown-retry');
                            rescueFocus(candidate, 'pointerdown-retry');
                            scanDragRegions('pointerdown-retry');
                        }
                    });
                } else {
                    checkOverlayCandidates('pointerdown');
                }
            };

            document.addEventListener('click', (event) => {
                logClick(event);
                checkOverlayCandidates('click');
            }, true);

            document.addEventListener('pointerdown', handlePointer, true);
            document.addEventListener('mousedown', handlePointer, true);
            document.addEventListener('mouseover', (event) => {
                if (isEditableTarget(event.target)) {
                    forceNoDrag(event.target);
                    forceNoDragAncestors(event.target);
                }
            }, true);

            document.addEventListener('focusin', (event) => {
                if (isEditableTarget(event.target)) {
                    forceNoDrag(event.target);
                    forceNoDragAncestors(event.target);
                    lastEditable = event.target;
                }
            }, true);

            window.addEventListener('focus', () => {
                if (needsRescue && lastEditable) {
                    rescueFocus(lastEditable, 'window-focus');
                    needsRescue = false;
                }
                resetWindowFocus('window-focus');
                focusWebContents('window-focus');
                checkOverlayCandidates('window-focus');
                scanDragRegions('window-focus');
            });

            window.addEventListener('blur', () => {
                needsRescue = true;
            });

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    if (needsRescue && lastEditable) {
                        rescueFocus(lastEditable, 'visibility-visible');
                        needsRescue = false;
                    }
                    resetWindowFocus('visibility-visible');
                    focusWebContents('visibility-visible');
                    checkOverlayCandidates('visibility-visible');
                }
            });

            setTimeout(() => {
                resetWindowFocus('initial');
                checkOverlayCandidates('initial');
                scanDragRegions('initial');
            }, 200);
        })();
    </script>

    @stack('scripts')

    {{-- Form Validation Script - runs after all page scripts --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function (e) {
                    // Remove previous error messages
                    form.querySelectorAll('.field-error').forEach(el => el.remove());
                    form.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));

                    let firstInvalid = null;

                    // Check all required fields (only visible ones)
                    form.querySelectorAll('[required]').forEach(input => {
                        // Skip if input or its container is hidden
                        const isHidden = input.offsetParent === null ||
                            input.closest('[style*="display: none"]') ||
                            input.closest('[style*="display:none"]');
                        if (isHidden) return;

                        const value = input.value.trim();
                        const isSelect = input.tagName === 'SELECT';
                        const isEmpty = isSelect ? (!value || value === '') : !value;

                        if (isEmpty) {
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            input.classList.add('input-error');

                            // Get label text
                            const field = input.closest('.field');
                            const label = field?.querySelector('label, .hint');
                            let labelText = label?.textContent?.trim() || input.name || 'Field ini';
                            // Remove asterisk from label
                            labelText = labelText.replace(/\s*\*\s*$/g, '').replace(/\*/g, '').trim();

                            // Create error message
                            const errorMsg = document.createElement('div');
                            errorMsg.className = 'field-error';
                            errorMsg.style.cssText = 'color:#dc2626; font-size:12px; margin-top:4px; display:flex; align-items:center; gap:4px;';
                            errorMsg.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><circle cx="12" cy="16" r="1" fill="currentColor"/></svg> ${labelText} harus diisi`;

                            // Insert after input
                            input.insertAdjacentElement('afterend', errorMsg);

                            if (!firstInvalid) firstInvalid = input;
                        }
                    });

                    // Scroll to first invalid field
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstInvalid.focus();
                    }
                });

                // Remove error on input
                form.addEventListener('input', function (e) {
                    if (e.target.classList.contains('input-error')) {
                        e.target.classList.remove('input-error');
                        const nextError = e.target.nextElementSibling;
                        if (nextError?.classList.contains('field-error')) {
                            nextError.remove();
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>