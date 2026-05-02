<x-app-layout>
    <x-slot name="header">
        <div class="customer-detail-header">
            <div class="customer-detail-header__title">
                <p class="page-kicker">Pelanggan</p>
                <h2 class="text-2xl tracking-tight text-slate-900">{{ $customer->name }}</h2>
            </div>
            <div class="customer-detail-header__actions">
                <a href="{{ route('customers.export-history.excel', $customer) }}" class="customer-btn customer-btn--success">Export Riwayat</a>
                <a href="{{ route('customers.edit', $customer) }}" class="btn-primary customer-btn customer-btn--edit-solid">Edit</a>
                <form method="POST" action="{{ route('customers.toggle-active', $customer) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn-danger-lite customer-btn {{ $customer->is_active ? 'customer-btn--danger' : 'customer-btn--success' }}" type="submit">{{ $customer->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                </form>
                <a href="{{ route('customers.index') }}" class="btn-danger-lite customer-btn customer-btn--ghost">Kembali</a>
            </div>
        </div>
    </x-slot>

    <div class="page-shell space-y-6">
        <div class="panel-card p-5 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div><p class="metric-label">Status</p><span class="status-chip customer-status-chip {{ $customer->is_active ? 'status-paid' : 'status-cancelled' }}">{{ $customer->is_active ? 'AKTIF' : 'NONAKTIF' }}</span></div>
            <div><p class="metric-label">Segmentasi</p><span class="customer-segment-chip customer-segment-chip--{{ strtolower($summary['segment']) }}">{{ $summary['segment'] }}</span></div>
            <div><p class="metric-label">No HP</p><p class="text-base font-semibold text-slate-800">{{ $customer->phone ?: '-' }}</p></div>
            <div><p class="metric-label">Email</p><p class="text-base font-semibold text-slate-800">{{ $customer->email ?: '-' }}</p></div>
            <div><p class="metric-label">Total Transaksi</p><p class="text-base font-semibold text-slate-800">{{ number_format($summary['transactions'], 0, ',', '.') }}</p></div>
            <div><p class="metric-label">Transaksi Pending</p><p class="text-base font-semibold text-amber-700">{{ number_format($summary['pending_count'], 0, ',', '.') }}</p></div>
            <div><p class="metric-label">Total Belanja</p><p class="text-base font-semibold text-slate-800">Rp {{ number_format($summary['total_spending'], 0, ',', '.') }}</p></div>
            <div><p class="metric-label">Poin Estimasi</p><p class="text-base font-semibold text-slate-800">{{ number_format($summary['points'], 0, ',', '.') }}</p></div>
            <div>
                <p class="metric-label">Skor Risiko</p>
                <p class="text-base font-semibold {{ $summary['risk_score'] >= 70 ? 'text-rose-700' : ($summary['risk_score'] >= 40 ? 'text-amber-700' : 'text-emerald-700') }}">
                    {{ $summary['risk_score'] }}/100
                </p>
            </div>
            <div><p class="metric-label">Belanja Terakhir</p><p class="text-base font-semibold text-slate-800">{{ $summary['last_purchase'] ? \Illuminate\Support\Carbon::parse($summary['last_purchase'])->format('d/m/Y H:i') : '-' }}</p></div>
            <div class="md:col-span-4"><p class="metric-label">Alamat</p><p class="text-base font-semibold text-slate-800">{{ $customer->address ?: '-' }}</p></div>
            <div class="md:col-span-4"><p class="metric-label">Catatan Internal</p><p class="text-base text-slate-800 whitespace-pre-line">{{ $customer->internal_note ?: '-' }}</p></div>
        </div>

        <div class="panel-card p-5">
            <h3 class="text-base font-semibold text-slate-900">Quick Action Follow-up</h3>
            <p class="text-xs text-slate-500 mt-1">Catat tindakan follow-up agar tim owner/admin punya jejak komunikasi yang rapi.</p>
            <form method="POST" action="{{ route('customers.quick-followup', $customer) }}" class="customer-followup-form mt-4">
                @csrf
                <select name="action_type" class="input-ui customer-followup-form__input">
                    <option value="note">Catat Follow-up</option>
                    <option value="reminder">Set Reminder</option>
                    <option value="message">Kirim Pesan</option>
                </select>
                <input type="datetime-local" name="reminder_at" class="input-ui customer-followup-form__input">
                <input type="text" name="note" class="input-ui customer-followup-form__input" placeholder="Contoh: Follow-up via telepon, respon baik, minta dihubungi lagi pekan depan.">
                <button type="submit" class="customer-btn customer-btn--primary customer-followup-form__btn">Simpan Follow-up</button>
            </form>
        </div>

        <div class="panel-card p-5">
            <h3 class="text-base font-semibold text-slate-900">Timeline Aktivitas Customer</h3>
            <div class="customer-timeline mt-3">
                @forelse($timeline as $item)
                    <div class="customer-timeline__item">
                        <div class="customer-timeline__dot"></div>
                        <div class="customer-timeline__content">
                            <p class="customer-timeline__title">{{ $item['title'] }}</p>
                            <p class="customer-timeline__desc">{{ $item['description'] }}</p>
                            <p class="customer-timeline__time">{{ \Illuminate\Support\Carbon::parse($item['at'])->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada aktivitas untuk ditampilkan.</p>
                @endforelse
            </div>
        </div>

        <div class="panel-card overflow-hidden">
            <div class="panel-head">
                <h3 class="text-base font-semibold text-slate-900">Aksi Cepat Transaksi Pending</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="table-ui customer-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Invoice</th>
                            <th class="text-right">Nilai Pending</th>
                            <th class="w-[44%]">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingSales as $sale)
                            @php
                                $ageDays = $sale->sold_at ? \Illuminate\Support\Carbon::parse($sale->sold_at)->diffInDays(now()) : 0;
                                $ageClass = $ageDays >= 8 ? 'customer-aging-chip--critical' : ($ageDays >= 4 ? 'customer-aging-chip--warning' : 'customer-aging-chip--normal');
                            @endphp
                            <tr class="customer-row">
                                <td>
                                    <div class="customer-contact-main">{{ $sale->sold_at?->format('d/m/Y') }}</div>
                                    <div class="customer-contact-sub">{{ $sale->sold_at?->format('H:i') }}</div>
                                </td>
                                <td>
                                    <div class="customer-contact-main">{{ $sale->invoice_number }}</div>
                                    <span class="customer-aging-chip {{ $ageClass }}">{{ $ageDays }} hari</span>
                                </td>
                                <td class="text-right customer-amount-cell">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                                <td>
                                    <div class="customer-pending-actions">
                                        <form method="POST" action="{{ route('customers.sales.settle-pending', [$customer, $sale]) }}">
                                            @csrf
                                            <button type="submit" class="customer-btn customer-btn--success customer-btn--pending" onclick="return confirm('Lunasi transaksi {{ $sale->invoice_number }}?')">Lunasi</button>
                                        </form>
                                        <form method="POST" action="{{ route('customers.sales.cancel-pending', [$customer, $sale]) }}">
                                            @csrf
                                            <button type="submit" class="customer-btn customer-btn--danger customer-btn--pending" onclick="return confirm('Batalkan transaksi {{ $sale->invoice_number }}?')">Batalkan</button>
                                        </form>
                                        <form method="POST" action="{{ route('customers.sales.reschedule-pending', [$customer, $sale]) }}" class="customer-reschedule-inline">
                                            @csrf
                                            <input type="date" name="reschedule_date" class="input-ui customer-date-input" required>
                                            <button type="submit" class="customer-btn customer-btn--info customer-btn--pending">Jadwal Ulang</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-slate-500 py-8">Tidak ada transaksi pending untuk pelanggan ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel-card overflow-hidden">
            <div class="panel-head customer-history-head">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Riwayat Pembelian</h3>
                    <p class="text-xs text-slate-500 mt-1">Filter transaksi customer berdasarkan tanggal dan status pembayaran.</p>
                </div>
                <form method="GET" action="{{ route('customers.show', $customer) }}" class="customer-history-filter">
                    <input type="date" name="from" value="{{ $from }}" class="input-ui customer-history-filter__input">
                    <input type="date" name="to" value="{{ $to }}" class="input-ui customer-history-filter__input">
                    <select name="status" class="input-ui customer-history-filter__input">
                        <option value="all" @selected($status === 'all')>Semua Status</option>
                        @foreach(\App\Enums\SaleStatus::options() as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="customer-btn customer-btn--primary customer-history-filter__btn">Filter</button>
                    <a href="{{ route('customers.show', $customer) }}" class="customer-btn customer-btn--ghost customer-history-filter__btn">Reset</a>
                </form>
            </div>
            <div class="overflow-x-auto customer-table-wrap customer-table-shell">
                <table class="table-ui customer-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Invoice</th>
                            <th>Kasir</th>
                            <th>Status</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                            <tr class="customer-row">
                                <td>
                                    <div class="customer-contact-main">{{ $sale->sold_at?->format('d/m/Y') }}</div>
                                    <div class="customer-contact-sub">{{ $sale->sold_at?->format('H:i') }}</div>
                                </td>
                                <td>
                                    <a href="{{ route('sales.show', $sale) }}" class="customer-name-link">{{ $sale->invoice_number }}</a>
                                </td>
                                <td>
                                    <div class="customer-contact-main">{{ $sale->user?->name ?: '-' }}</div>
                                </td>
                                <td>
                                    <span class="customer-sale-status {{ $sale->status->value === 'paid' ? 'customer-sale-status--paid' : ($sale->status->value === 'pending' ? 'customer-sale-status--pending' : 'customer-sale-status--cancelled') }}">
                                        {{ $sale->status->value === 'paid' ? 'LUNAS' : ($sale->status->value === 'pending' ? 'PENDING' : 'BATAL') }}
                                    </span>
                                </td>
                                <td class="text-right customer-amount-cell">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                                <td class="text-right">
                                    <a href="{{ route('sales.show', $sale) }}" class="customer-btn customer-btn--info customer-btn--pending">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-slate-500 py-8">Belum ada riwayat pembelian.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $sales->links() }}
            </div>
        </div>

        <div class="panel-card overflow-hidden">
            <div class="panel-head">
                <h3 class="text-base font-semibold text-slate-900">Produk Favorit Pelanggan</h3>
            </div>
            <div class="overflow-x-auto customer-table-wrap customer-table-shell">
                <table class="table-ui customer-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>SKU</th>
                            <th class="text-right">Qty Total</th>
                            <th class="text-right">Nilai Belanja</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topProducts as $item)
                            <tr class="customer-row">
                                <td>
                                    <div class="customer-name-link">{{ $item->product_name }}</div>
                                    <div class="customer-contact-sub">Produk favorit berdasarkan frekuensi beli</div>
                                </td>
                                <td>
                                    <span class="customer-sku-pill">{{ $item->sku ?: '-' }}</span>
                                </td>
                                <td class="text-right">
                                    <span class="customer-qty-pill">{{ number_format($item->total_qty, 0, ',', '.') }}</span>
                                </td>
                                <td class="text-right customer-amount-cell">Rp {{ number_format((float) $item->total_spending, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-slate-500 py-8">Belum ada produk untuk ditampilkan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel-card p-5">
            <h3 class="text-base font-semibold text-slate-900">Gabungkan Pelanggan Duplikat</h3>
            <p class="text-xs text-slate-500 mt-1">Semua riwayat transaksi pelanggan ini akan dipindahkan ke pelanggan tujuan.</p>
            @if($summary['pending_count'] > 0)
                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                    Merge sementara dikunci karena masih ada {{ number_format($summary['pending_count'], 0, ',', '.') }} transaksi pending pada customer ini.
                </div>
            @endif
            <form method="POST" action="{{ route('customers.merge', $customer) }}" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                @csrf
                <div class="md:col-span-2">
                    <label class="label-ui">Pelanggan Tujuan</label>
                    <input type="hidden" name="target_customer_id" id="merge-target-id" value="">
                    <div class="customer-search-wrap">
                        <input type="text" id="merge-target-query" class="input-ui" placeholder="Ketik nama / no HP / email tujuan..." autocomplete="off" required @disabled($summary['pending_count'] > 0)>
                        <div id="merge-suggest-box" class="customer-suggest-box hidden"></div>
                    </div>
                </div>
                <div>
                    <button type="submit" class="customer-btn customer-btn--danger w-full" onclick="return confirm('Gabungkan pelanggan ini ke tujuan terpilih?');" @disabled($summary['pending_count'] > 0)>Gabungkan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const input = document.getElementById('merge-target-query');
            const hiddenId = document.getElementById('merge-target-id');
            const box = document.getElementById('merge-suggest-box');
            const endpoint = @json(route('customers.merge-suggest', $customer));
            if (!input || !hiddenId || !box) return;

            let items = [];
            let activeIndex = -1;
            let timer = null;

            const hide = () => {
                box.innerHTML = '';
                box.classList.add('hidden');
                items = [];
                activeIndex = -1;
            };

            const render = (rows) => {
                if (!Array.isArray(rows) || rows.length === 0) {
                    hide();
                    return;
                }
                items = rows;
                box.innerHTML = rows.map((row, idx) => {
                    const phone = row.phone ? ` | ${row.phone}` : '';
                    const email = row.email ? ` | ${row.email}` : '';
                    return `<button type="button" class="customer-suggest-item" data-idx="${idx}"><strong>${row.name}</strong><span>#${row.id}${phone}${email}</span></button>`;
                }).join('');
                box.classList.remove('hidden');
            };

            const pick = (idx) => {
                const row = items[idx];
                if (!row) return;
                hiddenId.value = row.id;
                input.value = row.name;
                hide();
            };

            const fetchSuggest = async () => {
                const q = (input.value || '').trim();
                if (q.length < 2) {
                    hide();
                    return;
                }
                try {
                    const res = await fetch(`${endpoint}?q=${encodeURIComponent(q)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!res.ok) return;
                    render(await res.json());
                } catch (e) {}
            };

            input.addEventListener('input', () => {
                hiddenId.value = '';
                clearTimeout(timer);
                timer = setTimeout(fetchSuggest, 180);
            });

            input.addEventListener('keydown', (event) => {
                if (box.classList.contains('hidden')) return;
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    activeIndex = Math.min(activeIndex + 1, items.length - 1);
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    activeIndex = Math.max(activeIndex - 1, 0);
                } else if (event.key === 'Enter' && activeIndex >= 0) {
                    event.preventDefault();
                    pick(activeIndex);
                    return;
                } else if (event.key === 'Escape') {
                    hide();
                    return;
                }
                const nodes = box.querySelectorAll('.customer-suggest-item');
                nodes.forEach((node, i) => node.classList.toggle('is-active', i === activeIndex));
            });

            box.addEventListener('click', (event) => {
                const btn = event.target.closest('.customer-suggest-item');
                if (!btn) return;
                pick(Number(btn.getAttribute('data-idx')));
            });

            document.addEventListener('click', (event) => {
                if (!event.target.closest('.customer-search-wrap')) {
                    hide();
                }
            });
        })();
    </script>
</x-app-layout>
