<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="page-kicker">Master Data</p>
                <h2 class="text-2xl tracking-tight text-slate-900">Pelanggan</h2>
                <p class="text-sm text-slate-500 mt-1">Kelola data customer dan riwayat transaksinya dalam satu tampilan.</p>
            </div>
            <a href="{{ route('customers.create') }}" class="customer-btn customer-btn--create">+ Tambah Pelanggan</a>
        </div>
    </x-slot>

    <div class="page-shell customer-module space-y-6">
        <div class="customer-kpi-grid">
            <div class="customer-kpi-card customer-kpi-card--blue">
                <span>Total Aktif</span>
                <strong>{{ number_format($metrics['active'], 0, ',', '.') }}</strong>
            </div>
            <div class="customer-kpi-card customer-kpi-card--green">
                <span>Baru 30 Hari</span>
                <strong>{{ number_format($metrics['new30'], 0, ',', '.') }}</strong>
            </div>
            <div class="customer-kpi-card customer-kpi-card--amber">
                <span>Repeat Rate</span>
                <strong>{{ number_format($metrics['repeat_rate'], 1, ',', '.') }}%</strong>
            </div>
            <div class="customer-kpi-card customer-kpi-card--slate">
                <span>Avg Belanja</span>
                <strong>Rp {{ number_format($metrics['avg_spending'], 0, ',', '.') }}</strong>
            </div>
        </div>

        <div class="panel-card p-5">
            <h3 class="text-base font-semibold text-slate-900">Aging Transaksi Pending</h3>
            <p class="text-xs text-slate-500 mt-1">Ringkasan umur transaksi pending agar follow-up lebih terarah.</p>
            <div class="customer-kpi-grid mt-3">
                <div class="customer-kpi-card customer-kpi-card--blue">
                    <span>0-3 Hari</span>
                    <strong>{{ number_format($pendingAging['0_3'] ?? 0, 0, ',', '.') }}</strong>
                </div>
                <div class="customer-kpi-card customer-kpi-card--green">
                    <span>4-7 Hari</span>
                    <strong>{{ number_format($pendingAging['4_7'] ?? 0, 0, ',', '.') }}</strong>
                </div>
                <div class="customer-kpi-card customer-kpi-card--amber">
                    <span>8-14 Hari</span>
                    <strong>{{ number_format($pendingAging['8_14'] ?? 0, 0, ',', '.') }}</strong>
                </div>
                <div class="customer-kpi-card customer-kpi-card--slate">
                    <span>&gt;=15 Hari</span>
                    <strong>{{ number_format($pendingAging['15_plus'] ?? 0, 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>

        <form id="customer-search-form" method="GET" action="{{ route('customers.index') }}" class="panel-card p-5">
            <div class="customer-toolbar">
                <div class="customer-toolbar__search">
                    <label class="label-ui">Cari Pelanggan</label>
                    <div class="customer-search-wrap">
                        <input id="customer-search-input" type="text" name="q" value="{{ $q }}" class="input-ui" placeholder="Nama / No HP / Email / Alamat">
                        <div id="customer-suggest-box" class="customer-suggest-box hidden"></div>
                    </div>
                </div>
                <div class="customer-toolbar__segment">
                    <label class="label-ui">Segmentasi</label>
                    <select name="segment" class="input-ui">
                        @foreach($segmentOptions as $value => $label)
                            <option value="{{ $value }}" @selected($segment === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="customer-toolbar__segment">
                    <label class="label-ui">Status Pending</label>
                    <select name="pending" class="input-ui">
                        @foreach($pendingOptions as $value => $label)
                            <option value="{{ $value }}" @selected($pending === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="customer-toolbar__segment">
                    <label class="label-ui">Preset Pintar</label>
                    <select name="preset" class="input-ui">
                        @foreach($presetOptions as $value => $label)
                            <option value="{{ $value }}" @selected($preset === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="customer-toolbar__actions customer-filter-actions">
                    <button class="btn-primary customer-btn customer-btn--primary">Cari</button>
                    <a href="{{ route('customers.index') }}" class="btn-danger-lite customer-btn customer-btn--ghost">Reset</a>
                    <a href="{{ route('customers.export.excel', request()->query()) }}" class="customer-btn customer-btn--success">Export Excel</a>
                    <a href="{{ route('customers.export.followup.excel', request()->query()) }}" class="customer-btn customer-btn--info">Export Follow-up</a>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-2">Pencarian cepat aktif: ketik langsung untuk filter, tekan <strong>/</strong> untuk fokus.</p>
        </form>

        <div class="panel-card overflow-hidden">
            <div class="panel-head">
                <h3 class="text-base font-bold text-slate-900">Database Customer</h3>
            </div>
            <div class="overflow-x-auto customer-table-wrap customer-table-shell">
                <table class="table-ui customer-table customer-db-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Kontak</th>
                            <th class="text-right">Total Transaksi</th>
                            <th class="text-right">Total Belanja</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            @php
                                $totalBelanja = (float) ($customer->paid_sales_sum_total_amount ?? 0);
                                $lastPurchase = $customer->last_purchase_at ? \Illuminate\Support\Carbon::parse($customer->last_purchase_at) : null;
                                $pendingCount = (int) ($customer->pending_sales_count ?? 0);
                                $inactiveDays = $lastPurchase ? now()->diffInDays($lastPurchase) : null;
                                $healthScore = 100;
                                if (!$customer->is_active) $healthScore -= 30;
                                if ($pendingCount >= 3) $healthScore -= 35;
                                elseif ($pendingCount > 0) $healthScore -= 18;
                                if ($inactiveDays === null) $healthScore -= 20;
                                elseif ($inactiveDays >= 60) $healthScore -= 30;
                                elseif ($inactiveDays >= 30) $healthScore -= 16;
                                $healthScore = max(0, min(100, $healthScore));
                                $healthClass = $healthScore >= 75 ? 'customer-health-pill--good' : ($healthScore >= 45 ? 'customer-health-pill--warning' : 'customer-health-pill--risk');
                                $segmentLabel = ! $customer->is_active
                                    ? 'Nonaktif'
                                    : ($totalBelanja >= 1000000
                                        ? 'VIP'
                                        : ($customer->created_at?->gte(now()->subDays(30))
                                            ? 'Baru'
                                            : ((! $lastPurchase || $lastPurchase->lt(now()->subDays(30))) ? 'Tidur' : 'Aktif')));
                            @endphp
                            <tr class="customer-row">
                                <td>
                                    <a href="{{ route('customers.show', $customer) }}" class="customer-name-link">{{ $customer->name }}</a>
                                    <div class="mt-2 customer-tag-row">
                                        <span class="status-chip customer-status-chip {{ $customer->is_active ? 'status-paid' : 'status-cancelled' }}">
                                            {{ $customer->is_active ? 'AKTIF' : 'NONAKTIF' }}
                                        </span>
                                        <span class="customer-health-pill {{ $healthClass }}">Health {{ $healthScore }}</span>
                                        @if((int) ($customer->pending_sales_count ?? 0) > 0)
                                            <span class="status-chip customer-pending-chip">
                                                Pending {{ number_format((int) $customer->pending_sales_count, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="customer-contact-main">{{ $customer->phone ?: '-' }}</div>
                                    <div class="customer-contact-sub">{{ $customer->email ?: '-' }}</div>
                                </td>
                                <td class="text-right">
                                    <span class="customer-qty-pill">{{ number_format($customer->paid_sales_count, 0, ',', '.') }}</span>
                                </td>
                                <td class="text-right customer-amount-cell whitespace-nowrap">Rp {{ number_format($totalBelanja, 0, ',', '.') }}</td>
                                <td>
                                    <div class="customer-actions">
                                        <a href="{{ route('customers.show', $customer) }}" class="customer-btn customer-btn--info">Detail</a>
                                        <form method="POST" action="{{ route('customers.quick-followup-inline', $customer) }}">
                                            @csrf
                                            <button class="customer-btn customer-btn--primary" type="submit">Buat Follow-up</button>
                                        </form>
                                        @if((int) ($customer->pending_sales_count ?? 0) > 0)
                                            <a href="{{ route('customers.show', array_merge([$customer], ['status' => 'pending'])) }}" class="customer-btn customer-btn--danger">Lihat Pending</a>
                                        @endif
                                        <a href="{{ route('customers.edit', $customer) }}" class="customer-btn customer-btn--edit">Edit</a>
                                        <form method="POST" action="{{ route('customers.toggle-active', $customer) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="customer-btn {{ $customer->is_active ? 'customer-btn--danger' : 'customer-btn--success' }}" type="submit">{{ $customer->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-slate-500 py-8">Belum ada data pelanggan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $customers->links() }}
            </div>
        </div>

        <div class="panel-card overflow-hidden">
            <div class="panel-head">
                <h3 class="text-base font-semibold text-slate-900">Follow-up Pelanggan Tidur (&gt;30 Hari)</h3>
            </div>
            <div class="overflow-x-auto customer-table-wrap customer-table-shell">
                <table class="table-ui customer-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Kontak</th>
                            <th>Belanja Terakhir</th>
                            <th class="text-right">Total Belanja</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sleepingCustomers as $row)
                            <tr class="customer-row">
                                <td class="customer-name-link">{{ $row['name'] }}</td>
                                <td>
                                    <div class="customer-contact-main">{{ $row['phone'] ?: '-' }}</div>
                                </td>
                                <td>
                                    <div class="customer-contact-main">{{ $row['last_purchase_text'] }}</div>
                                    <div class="customer-contact-sub">
                                        @if($row['inactive_days'] !== null)
                                            <span class="customer-aging-chip {{ $row['inactive_days'] >= 60 ? 'customer-aging-chip--critical' : ($row['inactive_days'] >= 30 ? 'customer-aging-chip--warning' : 'customer-aging-chip--normal') }}">
                                                {{ number_format((int) $row['inactive_days'], 0, ',', '.') }} hari tidak belanja
                                            </span>
                                        @else
                                            Belum ada transaksi paid
                                        @endif
                                    </div>
                                </td>
                                <td class="text-right customer-amount-cell whitespace-nowrap">Rp {{ number_format($row['total_spending'], 0, ',', '.') }}</td>
                                <td>
                                    <a href="{{ route('customers.show', $row['id']) }}" class="customer-btn customer-btn--info">Lihat Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-slate-500 py-8">Tidak ada pelanggan tidur saat ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const form = document.getElementById('customer-search-form');
            const input = document.getElementById('customer-search-input');
            const suggestBox = document.getElementById('customer-suggest-box');
            const suggestEndpoint = @json(route('customers.suggest'));
            if (!form || !input || !suggestBox) return;

            let submitTimer = null;
            let suggestTimer = null;
            let activeIndex = -1;
            let items = [];

            const hideSuggest = () => {
                suggestBox.innerHTML = '';
                suggestBox.classList.add('hidden');
                activeIndex = -1;
                items = [];
            };

            const renderSuggest = (rows) => {
                if (!Array.isArray(rows) || rows.length === 0) {
                    hideSuggest();
                    return;
                }
                items = rows;
                suggestBox.innerHTML = rows.map((row, idx) => {
                    const phone = row.phone ? ` • ${row.phone}` : '';
                    const email = row.email ? ` • ${row.email}` : '';
                    return `<button type="button" class="customer-suggest-item" data-idx="${idx}"><strong>${row.name}</strong><span>${phone}${email}</span></button>`;
                }).join('');
                suggestBox.classList.remove('hidden');
            };

            const fetchSuggest = async () => {
                const q = (input.value || '').trim();
                if (q.length < 2) {
                    hideSuggest();
                    return;
                }
                try {
                    const res = await fetch(`${suggestEndpoint}?q=${encodeURIComponent(q)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    renderSuggest(data);
                } catch (e) {}
            };

            const pickItem = (idx) => {
                const row = items[idx];
                if (!row) return;
                input.value = row.name || '';
                hideSuggest();
                form.requestSubmit();
            };

            input.addEventListener('input', () => {
                clearTimeout(submitTimer);
                clearTimeout(suggestTimer);
                suggestTimer = setTimeout(fetchSuggest, 180);
                submitTimer = setTimeout(() => form.requestSubmit(), 420);
            });

            window.addEventListener('keydown', (event) => {
                const tag = (event.target?.tagName || '').toLowerCase();
                const editable = ['input', 'textarea', 'select'].includes(tag) || event.target?.isContentEditable;
                if (event.key === '/' && !editable) {
                    event.preventDefault();
                    input.focus();
                    input.select();
                }
            });

            input.addEventListener('keydown', (event) => {
                if (suggestBox.classList.contains('hidden')) return;
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    activeIndex = Math.min(activeIndex + 1, items.length - 1);
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    activeIndex = Math.max(activeIndex - 1, 0);
                } else if (event.key === 'Enter' && activeIndex >= 0) {
                    event.preventDefault();
                    pickItem(activeIndex);
                    return;
                } else if (event.key === 'Escape') {
                    hideSuggest();
                    return;
                }
                const nodes = suggestBox.querySelectorAll('.customer-suggest-item');
                nodes.forEach((n, i) => n.classList.toggle('is-active', i === activeIndex));
            });

            suggestBox.addEventListener('click', (event) => {
                const btn = event.target.closest('.customer-suggest-item');
                if (!btn) return;
                const idx = Number(btn.getAttribute('data-idx'));
                pickItem(idx);
            });

            document.addEventListener('click', (event) => {
                if (!event.target.closest('.customer-search-wrap')) {
                    hideSuggest();
                }
            });
        })();
    </script>
</x-app-layout>
