@extends('layouts.app')

@section('content')
    <div class="page-heading" style="margin-bottom:24px; gap:12px; flex-wrap:wrap;">
        <div>
            <h2 style="font-size:20px; font-weight:700; color:#1E293B; margin:0;">Kavling</h2>
            <p style="font-size:12px; color:#64748B; margin:4px 0 0 0;">Kelola data unit kavling</p>
        </div>
        <div
            style="flex:1; min-width:260px; display:flex; gap:8px; align-items:center; justify-content:flex-end; flex-wrap:wrap;">
            <div style="position:relative; flex:1; min-width:220px; max-width:340px;">
                <input id="lotSearch" type="text" placeholder="Cari kavling / project..." autocomplete="off"
                    style="width:100%; padding:10px 12px 10px 34px; border:1px solid #e5e7eb; border-radius:10px; font-size:14px; box-shadow:0 6px 18px rgba(17,24,39,0.04);">
                <span
                    style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#9ca3af; display:flex; align-items:center;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"></circle>
                        <line x1="16.65" y1="16.65" x2="21" y2="21"></line>
                    </svg>
                </span>
                <div id="lotSuggestions"
                    style="display:none; position:absolute; z-index:10; left:0; right:0; background:#fff; border:1px solid #e5e7eb; border-radius:10px; margin-top:6px; box-shadow:0 10px 28px rgba(17,24,39,0.08); max-height:220px; overflow:auto;">
                </div>
            </div>
            <a href="{{ route('kavling.create') }}" class="chip is-active"
                style="display:flex; align-items:center; gap:8px; white-space:nowrap;">
                <span style="font-size:18px; line-height:0.9;">+</span> Tambah Kavling
            </a>
        </div>
    </div>

    <div class="card" style="padding: 0;">
        <table class="table-clean">
            <thead>
                <tr>
                    <th style="width:50px;">No.</th>
                    <th>Project</th>
                    <th style="width:120px;">Block/Number</th>
                    <th style="width:120px;">Area (m²)</th>
                    <th style="width:160px;">Base Price</th>
                    <th style="width:120px;">Status</th>
                    <th style="text-align:left; width:140px; padding-left:14px;">Actions</th>
                </tr>
            </thead>
            <tbody id="lotsTableBody">
                @forelse ($lots as $index => $lot)
                    @php
                        $projectName = optional($lot->project)->name ?? '';
                        $label = trim($projectName . ' ' . $lot->block_number);
                    @endphp
                    <tr data-lot-row data-label="{{ Str::lower($label) }}">
                        <td>{{ $index + 1 }}</td>
                        <td style="font-weight:700; color:#0f172a;">{{ $projectName }}</td>
                        <td>{{ $lot->block_number }}</td>
                        <td>{{ $lot->area }}</td>
                        <td>Rp {{ number_format($lot->base_price, 0, ',', '.') }}</td>
                        @php
                            $statusClass = $lot->status === 'available' ? 'success' : 'danger';
                        @endphp
                        <td><span class="status-chip {{ $statusClass }}">{{ $lot->status }}</span></td>
                        <td style="padding-left:14px; white-space: nowrap;">
                            <a href="{{ route('kavling.edit', $lot) }}" class="btn"
                                style="padding:8px 10px; border-color:#e5e7eb;">Edit</a>
                            <form action="{{ route('kavling.destroy', $lot) }}" method="POST" style="display:inline;"
                                class="lot-delete-form" data-confirm="Hapus kavling ini?">
                                @csrf
                                @method('DELETE')
                                <button class="btn"
                                    style="padding:8px 10px; border-color:#e5e7eb; color:#b4232a; margin-left:6px; cursor:pointer !important;">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:18px;">Belum ada data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    @php
        $lotLabels = $lots->map(function ($lot) {
            $project = optional($lot->project)->name ?? '';
            return ['label' => trim($project . ' ' . $lot->block_number)];
        })->values();
    @endphp
    <script>
        (() => {
            const searchInput = document.getElementById('lotSearch');
            const suggestionsBox = document.getElementById('lotSuggestions');
            const rows = Array.from(document.querySelectorAll('[data-lot-row]'));
            const labels = @json($lotLabels);
            const toast = document.getElementById('toast');

            const showToast = (message) => {
                if (!toast || !message) return;
                toast.textContent = message;
                toast.classList.add('active');
                setTimeout(() => toast.classList.remove('active'), 4000);
            };

            const renderSuggestions = (query) => {
                suggestionsBox.innerHTML = '';
                if (!query) {
                    suggestionsBox.style.display = 'none';
                    return;
                }
                const needle = query.toLowerCase();
                const matches = labels.filter(item => item.label.toLowerCase().includes(needle)).slice(0, 8);
                if (!matches.length) {
                    suggestionsBox.style.display = 'none';
                    return;
                }
                matches.forEach(item => {
                    const div = document.createElement('div');
                    div.textContent = item.label;
                    div.style.padding = '10px 12px';
                    div.style.cursor = 'pointer';
                    div.style.borderBottom = '1px solid #f3f4f6';
                    div.addEventListener('mouseenter', () => div.style.background = '#f9fafb');
                    div.addEventListener('mouseleave', () => div.style.background = '#fff');
                    div.addEventListener('click', () => {
                        searchInput.value = item.label;
                        suggestionsBox.style.display = 'none';
                        filterTable(item.label.toLowerCase());
                    });
                    suggestionsBox.appendChild(div);
                });
                suggestionsBox.style.display = 'block';
            };

            const filterTable = (query) => {
                const needle = (query || '').toLowerCase().trim();
                let visibleCount = 0;
                rows.forEach(row => {
                    const label = row.dataset.label || '';
                    const show = !needle || label.includes(needle);
                    row.style.display = show ? '' : 'none';
                    if (show) visibleCount++;
                });
                const tbody = document.getElementById('lotsTableBody');
                if (visibleCount === 0) {
                    if (!document.getElementById('lots-empty')) {
                        const emptyRow = document.createElement('tr');
                        emptyRow.id = 'lots-empty';
                        emptyRow.innerHTML = '<td colspan=\"7\" style=\"text-align:center; padding:18px; color:#6b7280;\">Tidak ada hasil</td>';
                        tbody.appendChild(emptyRow);
                    }
                } else {
                    document.getElementById('lots-empty')?.remove();
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

            const removeRow = (row) => {
                row?.remove();
                const tbody = document.getElementById('lotsTableBody');
                const remaining = tbody?.querySelectorAll('[data-lot-row]')?.length || 0;
                if (remaining === 0) {
                    if (!document.getElementById('lots-empty')) {
                        const emptyRow = document.createElement('tr');
                        emptyRow.id = 'lots-empty';
                        emptyRow.innerHTML = '<td colspan="7" style="text-align:center; padding:18px; color:#6b7280;">Belum ada data</td>';
                        tbody?.appendChild(emptyRow);
                    }
                }
            };

            document.querySelectorAll('.lot-delete-form').forEach((form) => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const message = form.dataset.confirm || 'Hapus data ini?';
                    if (!confirm(message)) return;

                    const submitBtn = form.querySelector('button[type="submit"], button');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                    }

                    const formData = new FormData(form);
                    const token = formData.get('_token');

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        if (!response.ok) {
                            throw new Error('Gagal menghapus kavling.');
                        }

                        const row = form.closest('[data-lot-row]');
                        removeRow(row);
                        showToast('Kavling dihapus');
                    } catch (err) {
                        showToast('Gagal menghapus kavling');
                        if (submitBtn) submitBtn.disabled = false;
                        return;
                    }

                    if (submitBtn) submitBtn.disabled = false;
                });
            });
        })();
    </script>
@endpush
