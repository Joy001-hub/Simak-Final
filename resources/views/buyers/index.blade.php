@extends('layouts.app')

@section('content')
    <div class="page-heading" style="margin-bottom:24px; gap: 12px; flex-wrap: wrap;">
        <div>
            <h2 style="font-size:20px; font-weight:700; color:#1E293B; margin:0;">Pelanggan</h2>
            <p style="font-size:12px; color:#64748B; margin:4px 0 0 0;">Kelola data pelanggan</p>
        </div>
        <div
            style="flex:1; min-width:260px; display:flex; gap:8px; align-items:center; justify-content:flex-end; flex-wrap:wrap;">
            <div style="position:relative; flex:1; min-width:220px; max-width:340px;">
                <input id="buyerSearch" type="text" placeholder="Cari nama buyer..." autocomplete="off"
                    style="width:100%; padding:10px 12px 10px 34px; border:1px solid #e5e7eb; border-radius:10px; font-size:14px; box-shadow:0 6px 18px rgba(17,24,39,0.04);">
                <span
                    style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#9ca3af; display:flex; align-items:center;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"></circle>
                        <line x1="16.65" y1="16.65" x2="21" y2="21"></line>
                    </svg>
                </span>
                <div id="buyerSuggestions"
                    style="display:none; position:absolute; z-index:10; left:0; right:0; background:#fff; border:1px solid #e5e7eb; border-radius:10px; margin-top:6px; box-shadow:0 10px 28px rgba(17,24,39,0.08); max-height:220px; overflow:auto;">
                </div>
            </div>
            <a href="{{ route('buyers.create') }}" class="chip is-active"
                style="display:flex; align-items:center; gap:8px; white-space:nowrap;">
                <span style="font-size:18px; line-height:0.9;">+</span> Add Customer
            </a>
        </div>
    </div>

    <div class="card" style="padding: 0;">
        <table class="table-clean">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>Name</th>
                    <th style="width:180px;">Phone</th>
                    <th>Address</th>
                    <th style="text-align:left; width:140px; padding-left:14px;">Actions</th>
                </tr>
            </thead>
            <tbody id="buyersTableBody">
                @forelse ($buyers as $buyer)
                    <tr data-buyer-row data-name="{{ Str::lower($buyer->name) }}">
                        <td>{{ $buyer->id }}</td>
                        <td style="font-weight:700; color:#0f172a;">{{ $buyer->name }}</td>
                        <td>{{ $buyer->phone }}</td>
                        <td>{{ $buyer->address }}</td>
                        <td style="padding-left:14px; white-space: nowrap;">
                            <a href="{{ route('buyers.edit', $buyer) }}" class="btn"
                                style="padding:8px 10px; border-color:#e5e7eb;">Edit</a>
                            <form action="{{ route('buyers.destroy', $buyer) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button class="btn"
                                    style="padding:8px 10px; border-color:#e5e7eb; color:#b4232a; margin-left:6px;"
                                    onclick="return confirm('Hapus buyer ini?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding:18px;">Belum ada data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    @php
        $buyerNames = $buyers->map(function ($b) {
            return ['name' => $b->name, 'id' => $b->id];
        })->values();
    @endphp
    <script>
        (() => {
            const searchInput = document.getElementById('buyerSearch');
            const suggestionsBox = document.getElementById('buyerSuggestions');
            const rows = Array.from(document.querySelectorAll('[data-buyer-row]'));
            const names = @json($buyerNames);

            const renderSuggestions = (query) => {
                suggestionsBox.innerHTML = '';
                if (!query) {
                    suggestionsBox.style.display = 'none';
                    return;
                }
                const needle = query.toLowerCase();
                const matches = names.filter(item => item.name.toLowerCase().includes(needle)).slice(0, 8);
                if (!matches.length) {
                    suggestionsBox.style.display = 'none';
                    return;
                }
                matches.forEach(item => {
                    const div = document.createElement('div');
                    div.textContent = item.name;
                    div.style.padding = '10px 12px';
                    div.style.cursor = 'pointer';
                    div.style.borderBottom = '1px solid #f3f4f6';
                    div.addEventListener('mouseenter', () => div.style.background = '#f9fafb');
                    div.addEventListener('mouseleave', () => div.style.background = '#fff');
                    div.addEventListener('click', () => {
                        searchInput.value = item.name;
                        suggestionsBox.style.display = 'none';
                        filterTable(item.name.toLowerCase());
                    });
                    suggestionsBox.appendChild(div);
                });
                suggestionsBox.style.display = 'block';
            };

            const filterTable = (query) => {
                const needle = (query || '').toLowerCase().trim();
                let visibleCount = 0;
                rows.forEach(row => {
                    const name = row.dataset.name || '';
                    const show = !needle || name.includes(needle);
                    row.style.display = show ? '' : 'none';
                    if (show) visibleCount++;
                });
                if (visibleCount === 0) {
                    const tbody = document.getElementById('buyersTableBody');
                    if (!document.getElementById('buyers-empty')) {
                        const emptyRow = document.createElement('tr');
                        emptyRow.id = 'buyers-empty';
                        emptyRow.innerHTML = '<td colspan=\"5\" style=\"text-align:center; padding:18px; color:#6b7280;\">Tidak ada hasil</td>';
                        tbody.appendChild(emptyRow);
                    }
                } else {
                    document.getElementById('buyers-empty')?.remove();
                }
            };

            searchInput?.addEventListener('input', (e) => {
                const val = e.target.value;
                renderSuggestions(val);
                filterTable(val);
            });

            document.addEventListener('click', (e) => {
                if (!suggestionsBox.contains(e.target) && e.target !== searchInput) {
                    suggestionsBox.style.display = 'none';
                }
            });
        })();
    </script>
@endpush