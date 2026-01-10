@extends('layouts.app')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <style>
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .chart-grid line {
            stroke: #CBD5E1;
            stroke-opacity: 0.6;
        }

        .chart-text {
            fill: #64748B;
            font-size: 10px;
        }
    </style>
@endpush

@section('content')
    <div class="font-display text-text-main">
        {{-- Header Section --}}
        <header class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-text-main tracking-tight">Dashboard Overview</h2>
                <p class="text-xs text-slate-500 font-normal mt-1">Selamat datang kembali, berikut adalah ringkasan data</p>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-2"
                    id="periodForm">
                    <div class="flex items-center bg-slate-100 rounded p-1 border border-slate-200">
                        @foreach ($periodOptions as $option)
                            <button type="submit" name="periode" value="{{ $option }}"
                                class="px-3 py-1.5 text-xs font-medium rounded transition-all {{ $option === $activePeriod ? 'text-white bg-primary shadow-sm font-semibold' : 'text-slate-600 hover:text-text-main' }}">
                                {{ $option }}
                            </button>
                        @endforeach
                    </div>

                    <input type="hidden" name="compare" value="0">

                    <label
                        class="flex items-center gap-2 px-3 py-2 rounded bg-slate-100 border border-slate-200 cursor-pointer hover:border-slate-300 transition-colors h-[38px]"
                        title="Bandingkan tidak tersedia untuk periode &quot;Semua&quot;.">
                        <input type="checkbox" name="compare" value="1" id="compareToggle"
                            class="rounded border-slate-300 bg-transparent text-primary focus:ring-0 focus:ring-offset-0 size-4"
                            {{ $compareEnabled ? 'checked' : '' }} {{ $activePeriod === 'Semua' ? 'disabled' : '' }}>
                        <span class="text-xs text-slate-600 font-medium select-none">Bandingkan</span>
                    </label>

                    {{-- Custom Date Range --}}
                    <button type="button"
                        class="px-3 py-1.5 text-xs font-medium rounded transition-all {{ $activePeriod === 'Kustom' ? 'text-white bg-primary shadow-sm font-semibold' : 'text-slate-600 hover:text-text-main bg-slate-100 border border-slate-200' }}"
                        id="customToggle">
                        Kustom
                    </button>

                    <div id="customRange" style="display: {{ $activePeriod === 'Kustom' ? 'flex' : 'none' }};"
                        class="flex flex-wrap items-center gap-2 mt-2 lg:mt-0">
                        <input type="date" name="custom_from"
                            class="px-3 py-1.5 text-xs border border-slate-200 rounded-md shadow-sm"
                            value="{{ request('custom_from') }}">
                        <input type="date" name="custom_to"
                            class="px-3 py-1.5 text-xs border border-slate-200 rounded-md shadow-sm"
                            value="{{ request('custom_to') }}">
                        <button type="submit" name="periode" value="Kustom"
                            class="px-4 py-1.5 text-xs font-semibold text-white bg-primary rounded-md hover:bg-primary-hover">Terapkan</button>
                    </div>
                </form>
            </div>
        </header>

        {{-- Summary Cards Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @forelse ($summary as $card)
                @php
                    $isWideCard = in_array($card['label'], ['Total Piutang (Global)', 'Nilai Persediaan Kavling']);
                @endphp
                <div
                    class="bg-card-bg p-5 rounded-lg border border-card-border flex flex-col shadow-card hover:border-slate-300 transition-colors {{ $isWideCard ? 'lg:col-span-2' : '' }}">
                    <div class="flex justify-between items-start mb-2">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wide leading-tight">
                            {!! nl2br(e($card['label'])) !!}
                        </p>
                        @if (!empty($card['trend']))
                            @php
                                $dir = $card['trend']['direction'] ?? 'up';
                                $isUp = $dir === 'up';
                            @endphp
                            <span class="material-symbols-outlined text-sm {{ $isUp ? 'text-emerald-500' : 'text-red-500' }}">
                                {{ $isUp ? 'trending_up' : 'trending_down' }}
                            </span>
                        @else
                            @php
                                $iconMap = [
                                    'Penerimaan Periode Ini' => ['icon' => 'account_balance_wallet', 'color' => 'text-blue-500'],
                                    'Total DP Diterima' => ['icon' => 'credit_card', 'color' => 'text-emerald-500'],
                                    'Total Penjualan' => ['icon' => 'home', 'color' => 'text-primary'],
                                    'Total Penjualan Batal' => ['icon' => 'cancel', 'color' => 'text-red-500'],
                                    'Total Piutang (Global)' => ['icon' => 'receipt_long', 'color' => 'text-amber-500'],
                                    'Nilai Persediaan Kavling' => ['icon' => 'landscape', 'color' => 'text-teal-500'],
                                ];
                                $iconData = $iconMap[$card['label']] ?? ['icon' => 'payments', 'color' => 'text-blue-500'];
                            @endphp
                            <span class="material-symbols-outlined {{ $iconData['color'] }} text-sm">{{ $iconData['icon'] }}</span>
                        @endif
                    </div>

                    <div class="flex items-baseline gap-1 mt-1">
                        @if (!empty($card['isUnit']))
                            <h3 class="text-2xl font-bold text-text-main tracking-tight">{{ number_format($card['value']) }}</h3>
                            <span class="text-sm font-normal text-slate-500">Unit</span>
                        @else
                            <span class="text-sm font-medium text-slate-500">IDR</span>
                            <h3 class="text-2xl font-bold text-text-main tracking-tight">
                                {{ number_format($card['value'], 0, ',', '.') }}
                            </h3>
                        @endif
                    </div>

                    @if (!empty($card['trend']))
                        @php
                            $dir = $card['trend']['direction'] ?? 'up';
                            $delta = abs($card['trend']['delta'] ?? 0);
                            $isUp = $dir === 'up';
                            $textClasses = $isUp ? 'text-emerald-500' : 'text-red-500';
                        @endphp
                        <p class="text-[10px] {{ $textClasses }} mt-1">
                            {!! $isUp ? '&#8593;' : '&#8595;' !!} {{ number_format($delta, 1) }}% vs periode lalu
                        </p>
                    @endif

                    @if (!empty($card['hint']))
                        <p class="text-[10px] text-slate-400 mt-1">{{ $card['hint'] }}</p>
                        @if (!empty($card['previous']))
                            <p class="text-[10px] text-slate-400 mt-1">Periode lalu:
                                @if(!empty($card['isUnit'])){{ number_format($card['previous']) }} unit @else IDR
                                {{ number_format($card['previous'], 0, ',', '.') }} @endif
                            </p>
                        @endif
                    @endif

                    {{-- Status Badges --}}
                    @if (!empty($card['statuses']))
                        <div class="flex flex-wrap gap-2 mt-auto pt-3">
                            @foreach ($card['statuses'] as $status)
                                @php
                                    $displayLabel = match ($status['label']) {
                                        'Ada Tunggakan' => 'Tunggakan',
                                        'Jatuh Tempo <7 hari' => 'Perhatian',
                                        default => $status['label']
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                    style="background: {{ $status['color'] }}15; color: {{ $status['color'] }};">
                                    <span class="size-1.5 rounded-full mr-1.5" style="background: {{ $status['color'] }};"></span>
                                    {{ $displayLabel }}: {{ number_format($status['value'] * 100, 0) }}%
                                </span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Inventory Donut Chart --}}
                    @if (!empty($card['inventories']))
                        @php
                            $inventories = collect($card['inventories']);
                            $totalUnits = $card['totalUnits'] ?? $inventories->sum('units');
                            $circumference = 2 * M_PI * 40; // radius = 40
                            $currentOffset = 0;
                            $largestProject = $inventories->sortByDesc('units')->first();
                        @endphp
                        <div class="mt-auto pt-3 flex items-center justify-center gap-6">
                            {{-- Donut Chart --}}
                            <div class="relative size-[100px] shrink-0">
                                <svg class="size-full transform -rotate-90" viewBox="0 0 100 100">
                                    @foreach ($inventories as $inventory)
                                        @php
                                            $dashLength = ($inventory['value'] ?? 0) * $circumference;
                                            $dashGap = $circumference - $dashLength;
                                            $offset = -$currentOffset;
                                            $currentOffset += $dashLength;
                                        @endphp
                                        <circle cx="50" cy="50" r="40" fill="transparent" stroke="{{ $inventory['color'] }}"
                                            stroke-width="12" stroke-dasharray="{{ $dashLength }} {{ $dashGap }}"
                                            stroke-dashoffset="{{ $offset }}"></circle>
                                    @endforeach
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                    <span class="text-xl font-bold text-text-main leading-none">{{ $totalUnits }}</span>
                                    <span class="text-[10px] text-slate-500 mt-0.5">unit</span>
                                </div>
                            </div>
                            {{-- Legend --}}
                            <div class="flex flex-col gap-1.5 text-[10px] text-slate-600 font-medium">
                                @foreach ($inventories as $inventory)
                                    <div class="flex items-center gap-1.5">
                                        <span class="size-2 rounded-full shrink-0"
                                            style="background: {{ $inventory['color'] }};"></span>
                                        <span>{{ $inventory['label'] }} ({{ $inventory['units'] ?? 0 }})</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-card-bg p-6 rounded-lg border border-card-border col-span-full text-center shadow-card">
                    <p class="text-slate-500 font-medium">Belum ada data</p>
                    <p class="text-xs text-slate-400 mt-1">Mulai tambahkan penjualan untuk melihat ringkasan.</p>
                </div>
            @endforelse
        </div>

        {{-- Charts Section - Row 1 --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Sales Trend Chart --}}
            <div class="bg-card-bg rounded-lg border border-card-border p-6 flex flex-col shadow-card h-[380px]">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h3 class="text-base font-bold text-text-main">Tren Penjualan</h3>
                        <p class="text-xs text-slate-500 mt-1">Performa penjualan bulanan tahun ini</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <div class="flex bg-slate-100 rounded overflow-hidden border border-slate-200">
                            <button class="px-3 py-1 text-[10px] font-bold bg-primary text-white is-active"
                                data-toggle="sales-mode" data-mode="value">Nilai (Rp)</button>
                            <button
                                class="px-3 py-1 text-[10px] font-medium text-slate-600 hover:text-text-main transition-colors"
                                data-toggle="sales-mode" data-mode="unit">Unit</button>
                        </div>
                        <label class="toggle flex items-center gap-2 cursor-pointer">
                            <input id="projectionToggle" type="checkbox"
                                class="rounded text-primary focus:ring-0 border-none size-3.5">
                            <span class="text-[10px] text-slate-600">Proyeksi AI</span>
                        </label>
                    </div>
                </div>
                <div class="flex-1 w-full relative">
                    <canvas id="salesTrendChart" class="w-full h-full"></canvas>
                </div>
            </div>

            {{-- Marketing Performance Chart --}}
            <div class="bg-card-bg rounded-lg border border-card-border p-6 flex flex-col shadow-card h-[380px]">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h3 class="text-base font-bold text-text-main">Performa Tim Marketing</h3>
                        <p class="text-xs text-slate-500 mt-1">Top sales person</p>
                    </div>
                    <div class="flex bg-slate-100 rounded overflow-hidden border border-slate-200">
                        <button class="px-3 py-1 text-[10px] font-bold bg-primary text-white is-active"
                            data-toggle="marketing-mode" data-mode="value">Nilai (Rp)</button>
                        <button
                            class="px-3 py-1 text-[10px] font-medium text-slate-600 hover:text-text-main transition-colors"
                            data-toggle="marketing-mode" data-mode="unit">Unit</button>
                    </div>
                </div>
                <div class="flex-1 w-full relative">
                    <canvas id="marketingChart" class="w-full h-full"></canvas>
                </div>
            </div>
        </div>

        {{-- Charts Section - Row 2 (Doughnut Charts) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Project Sales Chart --}}
            <div class="bg-card-bg rounded-lg border border-card-border p-6 flex flex-col shadow-card h-[320px]">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-text-main">Penjualan per Proyek</h3>
                    <div class="flex bg-slate-100 rounded overflow-hidden border border-slate-200">
                        <button class="px-3 py-1 text-[10px] font-bold bg-primary text-white is-active"
                            data-toggle="project-sales-mode" data-mode="value">Nilai (Rp)</button>
                        <button
                            class="px-3 py-1 text-[10px] font-medium text-slate-600 hover:text-text-main transition-colors"
                            data-toggle="project-sales-mode" data-mode="unit">Unit</button>
                    </div>
                </div>
                <div class="flex-1 w-full flex items-center justify-center overflow-hidden">
                    <canvas id="projectSalesChart" class="max-w-full max-h-[220px]"></canvas>
                </div>
            </div>

            {{-- Project Inventory Chart --}}
            <div class="bg-card-bg rounded-lg border border-card-border p-6 flex flex-col shadow-card h-[320px]">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-text-main">Nilai Persediaan per Proyek</h3>
                    <div class="flex bg-slate-100 rounded overflow-hidden border border-slate-200">
                        <button class="px-3 py-1 text-[10px] font-bold bg-primary text-white is-active"
                            data-toggle="inventory-mode" data-mode="value">Nilai (Rp)</button>
                        <button
                            class="px-3 py-1 text-[10px] font-medium text-slate-600 hover:text-text-main transition-colors"
                            data-toggle="inventory-mode" data-mode="unit">Unit</button>
                    </div>
                </div>
                <div class="flex-1 w-full flex items-center justify-center overflow-hidden">
                    <canvas id="projectInventoryChart" class="max-w-full max-h-[220px]"></canvas>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (() => {
            let salesTrend = @json($salesTrend);
            let comparisonSalesTrend = [];
            let marketingPerformance = @json($marketingPerformance);
            let projectSales = @json($projectSales);
            let projectInventory = @json($projectInventory);
            let salesMode = 'value';
            let marketingMode = 'value';
            let projectSalesMode = 'value';
            let inventoryMode = 'value';
            const activePeriod = @json($activePeriod);
            const projectionMeta = @json($projectionMeta);
            const comparisonMeta = @json($comparisonMeta);
            const compareEnabled = @json($compareEnabled);
            const trendInterval = @json($trendInterval ?? 'month');

            if (!salesTrend || salesTrend.length === 0) {
                salesTrend = [{ label: '-', value: 0, unit: 0, period: null }];
            }
            comparisonSalesTrend = [];
            if (!marketingPerformance || marketingPerformance.length === 0) {
                marketingPerformance = [{ name: '-', value: 0 }];
            }

            const salesCtx = document.getElementById('salesTrendChart');
            const marketingCtx = document.getElementById('marketingChart');
            const projectSalesCtx = document.getElementById('projectSalesChart');
            const projectInventoryCtx = document.getElementById('projectInventoryChart');
            const projectionToggle = document.getElementById('projectionToggle');
            const compareToggle = document.getElementById('compareToggle');
            const projectionContainer = projectionToggle?.closest('label');

            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
            const maxValue = (...arrays) => {
                const numbers = arrays.flat().filter(v => Number.isFinite(v));
                return numbers.length ? Math.max(...numbers) : 1;
            };
            const parsePeriod = (period) => {
                if (!period || typeof period !== 'string') return null;
                if (trendInterval === 'day') {
                    const d = new Date(period);
                    return isNaN(d) ? null : d;
                }
                if (trendInterval === 'week') {
                    const match = period.match(/^(\d{4})-W?(\d{1,2})$/);
                    if (!match) return null;
                    const year = Number(match[1]);
                    const week = Number(match[2]);
                    const simple = new Date(year, 0, 1 + (week - 1) * 7);
                    const dow = simple.getDay();
                    const ISOweekStart = simple;
                    if (dow <= 4) {
                        ISOweekStart.setDate(simple.getDate() - simple.getDay() + 1);
                    } else {
                        ISOweekStart.setDate(simple.getDate() + 8 - simple.getDay());
                    }
                    return ISOweekStart;
                }
                if (trendInterval === 'quarter') {
                    const match = period.match(/^(\d{4})-Q(\d)$/);
                    if (!match) return null;
                    const year = Number(match[1]);
                    const q = Number(match[2]);
                    if (q < 1 || q > 4) return null;
                    return new Date(year, (q - 1) * 3, 1);
                }
                if (trendInterval === 'year') {
                    const year = Number(period);
                    if (!year) return null;
                    return new Date(year, 0, 1);
                }
                const [year, month] = period.split('-').map(Number);
                if (!year || !month) return null;
                return new Date(year, month - 1, 1);
            };
            const addStep = (date, count) => {
                const base = date instanceof Date && !isNaN(date) ? new Date(date) : new Date();
                switch (trendInterval) {
                    case 'day':
                        return new Date(base.getFullYear(), base.getMonth(), base.getDate() + count);
                    case 'week':
                        return new Date(base.getFullYear(), base.getMonth(), base.getDate() + 7 * count);
                    case 'quarter':
                        return new Date(base.getFullYear(), base.getMonth() + 3 * count, 1);
                    case 'year':
                        return new Date(base.getFullYear() + count, 0, 1);
                    case 'month':
                    default:
                        return new Date(base.getFullYear(), base.getMonth() + count, 1);
                }
            };
            const formatPeriodLabel = (date) => {
                if (!(date instanceof Date) || isNaN(date)) return '-';
                const weekday = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
                switch (trendInterval) {
                    case 'day':
                        return weekday[date.getDay()];
                    case 'week': {
                        const onejan = new Date(date.getFullYear(), 0, 1);
                        const millisecsInDay = 86400000;
                        const week = Math.ceil((((date - onejan) / millisecsInDay) + onejan.getDay() + 1) / 7);
                        return `W${week}`;
                    }
                    case 'quarter': {
                        const q = Math.floor(date.getMonth() / 3) + 1;
                        return `Q${q} ${date.getFullYear()}`;
                    }
                    case 'year':
                        return `${date.getFullYear()}`;
                    case 'month':
                    default: {
                        const year = String(date.getFullYear()).slice(-2);
                        return `${monthNames[date.getMonth()]} '${year}`;
                    }
                }
            };
            const linearRegressionForecast = (values, steps) => {
                const clean = values.filter(v => Number.isFinite(v));
                const n = clean.length;
                if (!n) return Array(steps).fill(0);
                if (n === 1) return Array.from({ length: steps }, () => Number(clean[0].toFixed(2)));
                const xs = clean.map((_, i) => i + 1);
                const meanX = xs.reduce((a, b) => a + b, 0) / n;
                const meanY = clean.reduce((a, b) => a + b, 0) / n;
                let numerator = 0;
                let denominator = 0;
                for (let i = 0; i < n; i++) {
                    numerator += (xs[i] - meanX) * (clean[i] - meanY);
                    denominator += (xs[i] - meanX) ** 2;
                }
                const slope = denominator === 0 ? 0 : numerator / denominator;
                const intercept = meanY - slope * meanX;
                return Array.from({ length: steps }, (_, idx) => {
                    const x = n + idx + 1;
                    return Math.max(0, Number((intercept + slope * x).toFixed(2)));
                });
            };
            const fallbackForecast = (values, steps) => {
                const clean = values.filter(v => Number.isFinite(v));
                if (!clean.length) return Array(steps).fill(0);
                if (clean.length === 1) return Array.from({ length: steps }, () => Number(clean[0].toFixed(2)));
                const deltas = [];
                for (let i = 1; i < clean.length; i++) {
                    deltas.push(clean[i] - clean[i - 1]);
                }
                const avgDelta = deltas.length ? deltas.reduce((a, b) => a + b, 0) / deltas.length : 0;
                return Array.from({ length: steps }, (_, idx) => {
                    const next = clean[clean.length - 1] + avgDelta * (idx + 1);
                    const safety = idx > 2 ? avgDelta * 0.25 * (idx + 1) : 0;
                    return Math.max(0, Number((next + safety).toFixed(2)));
                });
            };
            const buildProjection = (periods, values) => {
                // Disable projection outside "Tahun Ini"
                if (activePeriod !== 'Tahun Ini') {
                    return { futureLabels: [], futureValues: [], usingAI: false, label: '' };
                }
                // Random Walk with Drift (deterministic seed -> hasil statis)
                const meta = projectionMeta || {};
                const mode = meta.mode || 'short';
                const horizon = mode === 'all'
                    ? (meta.horizonQuarters || 4)
                    : mode === 'year'
                        ? (meta.horizonMonths || 12)
                        : 12;

                const clean = values.filter(v => Number.isFinite(v) && v > 0);
                const hasHistory = clean.length >= 3;
                const useAI = hasHistory;

                // ========================================
                // Holt's Double Exponential Smoothing
                // ========================================
                // α (alpha) = smoothing factor for level (0-1, higher = more responsive)
                // β (beta) = smoothing factor for trend (0-1, higher = more responsive)
                const alpha = 0.4; // Level smoothing - moderately responsive
                const beta = 0.3;  // Trend smoothing - slightly conservative

                // Initialize level and trend
                let level = clean[0] || 0;
                let trend = clean.length >= 2 ? (clean[1] - clean[0]) : 0;

                // Apply Holt's method to historical data
                for (let i = 1; i < clean.length; i++) {
                    const prevLevel = level;
                    level = alpha * clean[i] + (1 - alpha) * (level + trend);
                    trend = beta * (level - prevLevel) + (1 - beta) * trend;
                }

                // Dampen trend for long-term projections (prevent unrealistic growth)
                const dampingFactor = 0.9; // 10% reduction per period

                // Deterministic seed for consistent small variations
                const seedFromValues = (arr) => {
                    let h = 0;
                    arr.forEach((v, i) => {
                        const n = Math.floor((v ?? 0) * 1000) + i;
                        h = Math.imul(31, h) + n;
                        h |= 0;
                    });
                    return h >>> 0;
                };
                const mulberry32 = (a) => () => {
                    a |= 0; a = a + 0x6D2B79F5 | 0;
                    let t = Math.imul(a ^ a >>> 15, 1 | a);
                    t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t;
                    return ((t ^ t >>> 14) >>> 0) / 4294967296;
                };
                const rng = mulberry32(seedFromValues(clean.length ? clean : [0]));
                const randVariation = () => (rng() * 0.1 - 0.05); // ±5% small variation

                const lastPeriod = (() => {
                    const reversed = [...periods].reverse();
                    for (const period of reversed) {
                        const parsed = parsePeriod(period);
                        if (parsed) return parsed;
                    }
                    return new Date();
                })();

                // Generate future values using Holt's forecast with damping
                const futureValues = [];
                let cumulativeDamping = 1;
                for (let i = 0; i < horizon; i++) {
                    cumulativeDamping *= dampingFactor;
                    const dampedTrend = trend * cumulativeDamping;
                    const baseProjection = level + dampedTrend * (i + 1);
                    const variation = 1 + randVariation();
                    const projectedValue = Math.max(0, baseProjection * variation);
                    futureValues.push(Number(projectedValue.toFixed(2)));
                }

                const futureLabels = Array.from({ length: horizon }, (_, idx) => formatPeriodLabel(addStep(lastPeriod, idx + 1)));
                return {
                    futureLabels,
                    futureValues,
                    usingAI: useAI,
                    label: 'Proyeksi AI',
                };
            };
            const buildTimeline = (projection, modeKey) => {
                const actualMap = new Map(salesTrend.map(item => [item.period || item.label, Number(item[modeKey]) || 0]));
                const timeline = [];
                const pushEntry = (key, label, date, futureIndex = null) => {
                    const finalKey = key || `label-${label}`;
                    if (!finalKey) return;
                    timeline.push({ key: finalKey, label, date, futureIndex });
                };
                if (trendInterval === 'month' && (activePeriod === 'Tahun Ini' || activePeriod === 'Tahun Lalu')) {
                    const year = activePeriod === 'Tahun Ini' ? new Date().getFullYear() : new Date().getFullYear() - 1;
                    for (let m = 0; m < 12; m++) {
                        const date = new Date(year, m, 1);
                        const periodKey = `${date.getFullYear()}-${String(m + 1).padStart(2, '0')}`;
                        pushEntry(periodKey, formatPeriodLabel(date), date);
                    }
                }
                salesTrend.forEach(item => pushEntry(item.period, item.label, parsePeriod(item.period)));
                const lastPeriodDate = (() => {
                    const reversed = [...salesTrend].reverse();
                    for (const item of reversed) {
                        const parsed = parsePeriod(item.period);
                        if (parsed) return parsed;
                    }
                    return new Date();
                })();
                projection.futureLabels.forEach((label, idx) => {
                    // Only add future labels to timeline if projection is enabled
                    if (projectionToggle && projectionToggle.checked) {
                        pushEntry(`future-${idx}`, label, addStep(lastPeriodDate, idx + 1), idx);
                    }
                });
                const unique = new Map();
                timeline.forEach(entry => {
                    if (!unique.has(entry.key)) unique.set(entry.key, entry);
                });
                const sorted = Array.from(unique.values()).sort((a, b) => {
                    if (a.date && b.date && !isNaN(a.date) && !isNaN(b.date)) return a.date - b.date;
                    if (a.date && !b.date) return -1;
                    if (!a.date && b.date) return 1;
                    return (a.label || '').localeCompare(b.label || '');
                });
                const labels = sorted.map((e, idx) => {
                    if (trendInterval === 'week') {
                        return `W${idx + 1}`;
                    }
                    const label = formatPeriodLabel(e.date);
                    return (label === '-' && e.label) ? e.label : label;
                });
                const values = sorted.map(e => actualMap.get(e.key) ?? null);
                const comparisonData = sorted.map(() => null);
                // Pastikan projected hanya tampil jika toggle proyeksi aktif
                const projected = sorted.map((e, idx) => {
                    // Jika proyeksi toggle off, semua nilai null
                    if (!projectionToggle || !projectionToggle.checked) {
                        return null;
                    }
                    if (Number.isInteger(e.futureIndex)) {
                        return projection.futureValues[e.futureIndex];
                    }
                    // jika ini titik terakhir historis dan setelahnya ada proyeksi, duplikasi sebagai anchor
                    const isLastHist = idx === sorted.length - 1 && projection.futureValues.length > 0;
                    return isLastHist ? (values[idx] ?? null) : null;
                });
                return { labels, values, projected, projectionLabel: projection.label, comparisonData };
            };
            const buildSalesData = () => {
                const modeKey = salesMode === 'unit' ? 'unit' : 'value';
                const values = salesTrend.map(item => Number(item[modeKey]) || 0);
                const periods = salesTrend.map(item => item.period);
                // Untuk filter "Semua" atau data terlalu sedikit, jangan tampilkan proyeksi supaya sumbu tidak kacau
                const hasHistoryForProjection = values.filter(v => Number.isFinite(v)).length >= 3;
                const projection = (activePeriod === 'Semua' || !hasHistoryForProjection)
                    ? { futureLabels: [], futureValues: [], usingAI: false, label: '' }
                    : buildProjection(periods, values);
                return buildTimeline(projection, modeKey);
            };

            let { labels: salesLabels, values: salesValues, projected, projectionLabel, comparisonData } = buildSalesData();
            let comparisonSeries = comparisonData;

            const formatRupiahCompact = (valueMillions) => {
                const nominal = Number(valueMillions || 0) * 1_000_000;
                const abs = Math.abs(nominal);
                let unit = 'Jt';
                let divisor = 1_000_000;
                if (abs >= 1_000_000_000_000) {
                    unit = 'T';
                    divisor = 1_000_000_000_000;
                } else if (abs >= 1_000_000_000) {
                    unit = 'M';
                    divisor = 1_000_000_000;
                }
                const scaled = nominal / divisor;
                const fractionDigits = Math.abs(scaled) >= 10 ? 0 : 1;
                const formatted = scaled.toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: fractionDigits,
                });
                return `Rp${formatted} ${unit}`;
            };
            const formatRupiahFull = (valueMillions) => {
                const nominal = Number(valueMillions || 0) * 1_000_000;
                return nominal.toLocaleString('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
            };
            const currencyTick = (value) => formatRupiahCompact(value);
            const unitTick = (value) => `${value} unit`;
            const buildAxisConfig = (maxVal, isUnit) => {
                const safeMax = Number.isFinite(maxVal) && maxVal > 0 ? maxVal : 1;
                if (isUnit) {
                    const step = safeMax <= 10 ? 1 : Math.ceil(safeMax / 5);
                    return {
                        suggestedMax: safeMax + step,
                        stepSize: step,
                        callback: unitTick,
                    };
                }
                return {
                    suggestedMax: safeMax * 1.1,
                    stepSize: undefined,
                    callback: currencyTick,
                };
            };

            const sharedOptions = {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                interaction: { intersect: false, mode: 'index' },
            };

            const upColor = '#0F9D58';
            const downColor = '#DB4437';

            const salesChart = new Chart(salesCtx, {
                type: 'bar',
                data: {
                    labels: salesLabels,
                    datasets: [
                        {
                            label: 'Nilai (Rp)',
                            data: salesValues,
                            backgroundColor: '#9c0f2f',
                            borderRadius: 10,
                            maxBarThickness: 32
                        },
                        {
                            type: 'bar',
                            id: 'projection',
                            label: projectionLabel,
                            data: projected,
                            backgroundColor: '#9ca3af',
                            borderRadius: 10,
                            maxBarThickness: 32
                        }
                    ]
                },
                options: {
                    ...sharedOptions,
                    layout: { padding: { right: 25, left: 10, bottom: 25 } },
                    scales: {
                        x: {
                            grid: { drawOnChartArea: false, drawTicks: true },
                            ticks: {
                                color: '#64748B',
                                maxRotation: 45,
                                minRotation: 45,
                                autoSkip: false,
                                maxTicksLimit: 36,
                                font: { size: 10 }
                            }
                        },
                        y: {
                            grid: { color: '#E2E8F0' },
                            ticks: {
                                color: '#64748B',
                                callback: value => salesMode === 'unit' ? unitTick(value) : currencyTick(value),
                                stepSize: buildAxisConfig(maxValue(salesValues, projected), salesMode === 'unit').stepSize,
                            },
                            suggestedMax: buildAxisConfig(maxValue(salesValues, projected), salesMode === 'unit').suggestedMax
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            filter: function (tooltipItem) {
                                return tooltipItem.parsed.y > 0 && tooltipItem.dataset.label !== '';
                            },
                            callbacks: {
                                label: ctx => `${ctx.dataset.label}: ${salesMode === 'unit' ? unitTick(ctx.parsed.y) : formatRupiahFull(ctx.parsed.y)}`
                            }
                        }
                    }
                }
            });

            const buildMarketingData = () => {
                const labels = marketingPerformance.map(item => item.name);
                const values = marketingPerformance.map(item => Number(item[marketingMode === 'unit' ? 'unit' : 'value']));
                return { labels, values };
            };

            let { labels: marketingLabels, values: marketingValues } = buildMarketingData();
            const applyMarketingAxis = () => {
                const axisCfg = buildAxisConfig(maxValue(marketingValues), marketingMode === 'unit');
                marketingChart.options.scales.y.ticks.callback = axisCfg.callback;
                marketingChart.options.scales.y.ticks.stepSize = axisCfg.stepSize;
                marketingChart.options.scales.y.suggestedMax = axisCfg.suggestedMax;
            };

            const marketingColors = ['#9c0f2f', '#E65100', '#F9A825', '#2E7D32', '#1565C0', '#6A1B9A', '#00838F', '#424242'];
            const marketingChart = new Chart(marketingCtx, {
                type: 'bar',
                data: {
                    labels: marketingLabels,
                    datasets: [
                        {
                            label: 'Nilai (Rp)',
                            data: marketingValues,
                            backgroundColor: marketingLabels.map((_, idx) => marketingColors[idx % marketingColors.length]),
                            borderRadius: 12,
                            maxBarThickness: 40
                        }
                    ]
                },
                options: {
                    ...sharedOptions,
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#64748B', maxRotation: 50, minRotation: 30 }
                        },
                        y: {
                            grid: { color: '#E2E8F0' },
                            ticks: {
                                color: '#64748B',
                                callback: value => marketingMode === 'unit' ? unitTick(value) : currencyTick(value),
                            },
                            suggestedMax: Math.max(...marketingValues) + 1
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.dataset.label}: ${marketingMode === 'unit' ? unitTick(ctx.parsed.y) : formatRupiahFull(ctx.parsed.y)}`
                            }
                        }
                    }
                }
            });
            applyMarketingAxis();
            if (!projectSales || projectSales.length === 0) {
                projectSales = [{ name: '-', value: 0, unit: 0 }];
            }
            const buildProjectSalesData = () => {
                const labels = projectSales.map(item => item.name);
                const values = projectSales.map(item => Number(item[projectSalesMode === 'unit' ? 'unit' : 'value']) || 0);
                return { labels, values };
            };
            let { labels: projectSalesLabels, values: projectSalesValues } = buildProjectSalesData();
            const projectSalesColors = ['#1565C0', '#E65100', '#9c0f2f', '#2E7D32', '#F9A825', '#00838F', '#C62828', '#424242'];

            const projectSalesChart = new Chart(projectSalesCtx, {
                type: 'doughnut',
                data: {
                    labels: projectSalesLabels,
                    datasets: [
                        {
                            label: projectSalesMode === 'unit' ? 'Jumlah Unit' : 'Nilai (Rp)',
                            data: projectSalesValues,
                            backgroundColor: projectSalesLabels.map((_, idx) => projectSalesColors[idx % projectSalesColors.length]),
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }
                    ]
                },
                options: {
                    ...sharedOptions,
                    cutout: '55%',
                    plugins: {
                        legend: {
                            display: true,
                            position: 'right',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 8,
                                boxHeight: 8,
                                padding: 20,
                                font: { size: 11 },
                                generateLabels: function (chart) {
                                    const data = chart.data;
                                    const values = data.datasets[0].data;
                                    const total = values.reduce((a, b) => a + b, 0);
                                    return data.labels.map((label, i) => {
                                        const pct = total > 0 ? ((values[i] / total) * 100).toFixed(0) : 0;
                                        return {
                                            text: `${label}    ${pct}%`,
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            strokeStyle: data.datasets[0].backgroundColor[i],
                                            hidden: false,
                                            index: i
                                        };
                                    });
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => {
                                    const valueStr = projectSalesMode === 'unit' ? unitTick(ctx.parsed) : formatRupiahFull(ctx.parsed);
                                    return `${ctx.label}: ${valueStr}`;
                                }
                            }
                        }
                    }
                }
            });

            const projectSalesChips = document.querySelectorAll('[data-toggle="project-sales-mode"]');
            projectSalesChips.forEach(chip => {
                chip.addEventListener('click', () => {
                    projectSalesMode = chip.dataset.mode;
                    projectSalesChips.forEach(c => {
                        c.classList.remove('bg-primary', 'text-white', 'font-bold');
                        c.classList.add('text-slate-600', 'font-medium');
                    });
                    chip.classList.remove('text-slate-600', 'font-medium');
                    chip.classList.add('bg-primary', 'text-white', 'font-bold');
                    const { labels, values } = buildProjectSalesData();
                    projectSalesLabels = labels;
                    projectSalesValues = values;
                    projectSalesChart.data.labels = labels;
                    projectSalesChart.data.datasets[0].data = values;
                    projectSalesChart.data.datasets[0].label = projectSalesMode === 'unit' ? 'Jumlah Unit' : 'Nilai (Rp)';
                    projectSalesChart.update();
                });
            });

            if (!projectInventory || projectInventory.length === 0) {
                projectInventory = [{ name: '-', value: 0, unit: 0 }];
            }
            const buildInventoryData = () => {
                const labels = projectInventory.map(item => item.name);
                const values = projectInventory.map(item => Number(item[inventoryMode === 'unit' ? 'unit' : 'value']) || 0);
                return { labels, values };
            };
            let { labels: inventoryLabels, values: inventoryValues } = buildInventoryData();
            const inventoryColors = ['#9c0f2f', '#E65100', '#1565C0', '#2E7D32', '#F9A825', '#00838F', '#C62828', '#424242'];
            const projectInventoryChart = new Chart(projectInventoryCtx, {
                type: 'doughnut',
                data: {
                    labels: inventoryLabels,
                    datasets: [
                        {
                            label: inventoryMode === 'unit' ? 'Jumlah Unit' : 'Nilai (Rp)',
                            data: inventoryValues,
                            backgroundColor: inventoryLabels.map((_, idx) => inventoryColors[idx % inventoryColors.length]),
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    ...sharedOptions,
                    cutout: '55%',
                    plugins: {
                        legend: {
                            display: true,
                            position: 'right',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 8,
                                boxHeight: 8,
                                padding: 20,
                                font: { size: 11 },
                                generateLabels: function (chart) {
                                    const data = chart.data;
                                    const values = data.datasets[0].data;
                                    const total = values.reduce((a, b) => a + b, 0);
                                    return data.labels.map((label, i) => {
                                        const pct = total > 0 ? ((values[i] / total) * 100).toFixed(0) : 0;
                                        return {
                                            text: `${label}    ${pct}%`,
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            strokeStyle: data.datasets[0].backgroundColor[i],
                                            hidden: false,
                                            index: i
                                        };
                                    });
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.label}: ${inventoryMode === 'unit' ? unitTick(ctx.parsed) : formatRupiahFull(ctx.parsed)}`
                            }
                        }
                    }
                }
            });

            const inventoryChips = document.querySelectorAll('[data-toggle="inventory-mode"]');
            inventoryChips.forEach(chip => {
                chip.addEventListener('click', () => {
                    inventoryMode = chip.dataset.mode;
                    inventoryChips.forEach(c => {
                        c.classList.remove('bg-primary', 'text-white', 'font-bold');
                        c.classList.add('text-slate-600', 'font-medium');
                    });
                    chip.classList.remove('text-slate-600', 'font-medium');
                    chip.classList.add('bg-primary', 'text-white', 'font-bold');
                    const { labels, values } = buildInventoryData();
                    inventoryLabels = labels;
                    inventoryValues = values;
                    projectInventoryChart.data.labels = labels;
                    projectInventoryChart.data.datasets[0].data = values;
                    projectInventoryChart.data.datasets[0].label = inventoryMode === 'unit' ? 'Jumlah Unit' : 'Nilai (Rp)';
                    projectInventoryChart.update();
                });
            });

            const syncProjectionToggle = () => {
                if (!projectionToggle) return;
                const projectionIndex = salesChart.data.datasets.findIndex(ds => ds.id === 'projection');
                if (projectionIndex >= 0) {
                    const meta = salesChart.getDatasetMeta(projectionIndex);
                    meta.hidden = !projectionToggle.checked;
                }
            };

            const updateProjectionTooltip = () => {
                if (!projectionToggle || !projectionContainer) return;

                // Reset state first
                projectionToggle.disabled = false;
                projectionContainer.removeAttribute('title');
                projectionContainer.style.opacity = '1';
                projectionContainer.style.cursor = 'pointer';

                // Condition 1: Period Check - Strict "Tahun Ini" only
                if (activePeriod !== 'Tahun Ini') {
                    projectionToggle.disabled = true;
                    projectionContainer.title = "Fitur ini hanya berfungsi di periode Tahun Ini";
                    projectionContainer.style.opacity = '0.5';
                    projectionContainer.style.cursor = 'not-allowed';
                    if (projectionToggle.checked) {
                        projectionToggle.checked = false;
                        projectionToggle.dispatchEvent(new Event('change'));
                    }
                    return;
                }

                // Condition 2: Data Eligibility Check
                if (projectionMeta && !projectionMeta.aiEligible) {
                    projectionToggle.disabled = true;
                    projectionContainer.title = "Diperlukan data setahun untuk mengaktifkan fitur ini";
                    projectionContainer.style.opacity = '0.5';
                    projectionContainer.style.cursor = 'not-allowed';
                    if (projectionToggle.checked) {
                        projectionToggle.checked = false;
                        projectionToggle.dispatchEvent(new Event('change'));
                    }
                }
            };

            // Run initial check
            updateProjectionTooltip();
            projectionToggle?.addEventListener('change', () => {
                const rebuilt = buildSalesData();
                salesLabels = rebuilt.labels;
                salesValues = rebuilt.values;
                projected = rebuilt.projected;
                projectionLabel = rebuilt.projectionLabel;

                salesChart.data.labels = salesLabels;
                salesChart.data.datasets[0].data = salesValues;
                const projectionIndex = salesChart.data.datasets.findIndex(ds => ds.id === 'projection');

                if (projectionIndex >= 0) {
                    salesChart.data.datasets[projectionIndex].data = projected;
                    salesChart.data.datasets[projectionIndex].label = projectionLabel;
                }

                const axisCfg = buildAxisConfig(maxValue(salesValues, projected), salesMode === 'unit');
                salesChart.options.scales.y.ticks.callback = axisCfg.callback;
                salesChart.options.scales.y.ticks.stepSize = axisCfg.stepSize;
                salesChart.options.scales.y.suggestedMax = axisCfg.suggestedMax;

                syncProjectionToggle();
                salesChart.update();
            });
            syncProjectionToggle();

            if (compareToggle) {
                if (!compareEnabled) {
                    compareToggle.checked = false;
                }
                if (activePeriod === 'Semua') {
                    compareToggle.title = 'Bandingkan tidak tersedia untuk periode "Semua"';
                }
                compareToggle.addEventListener('change', () => {
                    if (compareToggle.disabled) return;
                    document.getElementById('periodForm')?.submit();
                });
            }

            const salesChips = document.querySelectorAll('[data-toggle="sales-mode"]');
            salesChips.forEach(chip => {
                chip.addEventListener('click', () => {
                    salesMode = chip.dataset.mode;
                    salesChips.forEach(c => {
                        c.classList.remove('bg-primary', 'text-white', 'font-bold');
                        c.classList.add('text-slate-600', 'font-medium');
                    });
                    chip.classList.remove('text-slate-600', 'font-medium');
                    chip.classList.add('bg-primary', 'text-white', 'font-bold');
                    const rebuilt = buildSalesData();
                    salesLabels = rebuilt.labels;
                    salesValues = rebuilt.values;
                    projected = rebuilt.projected;
                    projectionLabel = rebuilt.projectionLabel;
                    comparisonSeries = rebuilt.comparisonData;
                    salesChart.data.labels = salesLabels;
                    const projectionIndex = salesChart.data.datasets.findIndex(ds => ds.id === 'projection');
                    salesChart.data.datasets[0].data = salesValues;
                    salesChart.data.datasets[0].label = salesMode === 'unit' ? 'Jumlah Unit' : 'Nilai (Rp)';
                    if (projectionIndex >= 0) {
                        salesChart.data.datasets[projectionIndex].data = projected;
                        salesChart.data.datasets[projectionIndex].label = projectionLabel;
                    }
                    const axisCfg = buildAxisConfig(maxValue(salesValues, projected), salesMode === 'unit');
                    salesChart.options.scales.y.ticks.callback = axisCfg.callback;
                    salesChart.options.scales.y.ticks.stepSize = axisCfg.stepSize;
                    salesChart.options.scales.y.suggestedMax = axisCfg.suggestedMax;
                    syncProjectionToggle();
                    salesChart.update();
                });
            });

            const marketingChips = document.querySelectorAll('[data-toggle="marketing-mode"]');
            marketingChips.forEach(chip => {
                chip.addEventListener('click', () => {
                    marketingMode = chip.dataset.mode;
                    marketingChips.forEach(c => {
                        c.classList.remove('bg-primary', 'text-white', 'font-bold');
                        c.classList.add('text-slate-600', 'font-medium');
                    });
                    chip.classList.remove('text-slate-600', 'font-medium');
                    chip.classList.add('bg-primary', 'text-white', 'font-bold');
                    const { labels, values } = buildMarketingData();
                    marketingChart.data.labels = labels;
                    marketingChart.data.datasets[0].data = values;
                    marketingChart.data.datasets[0].label = marketingMode === 'unit' ? 'Jumlah Unit' : 'Nilai (Rp)';
                    const axisCfg = buildAxisConfig(maxValue(values), marketingMode === 'unit');
                    marketingChart.options.scales.y.ticks.callback = axisCfg.callback;
                    marketingChart.options.scales.y.ticks.stepSize = axisCfg.stepSize;
                    marketingChart.options.scales.y.suggestedMax = axisCfg.suggestedMax;
                    marketingChart.update();
                });
            });

            const customToggle = document.getElementById('customToggle');
            const customRange = document.getElementById('customRange');
            customToggle?.addEventListener('click', () => {
                const visible = customRange?.style.display !== 'none';
                customRange.style.display = visible ? 'none' : 'flex';
            });

            if (activePeriod === 'Kustom' && customRange) {
                customRange.style.display = 'flex';
                customToggle?.classList.add('bg-primary', 'text-white', 'font-semibold');
            }
        })();
    </script>
@endpush
