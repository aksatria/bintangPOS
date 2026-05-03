<x-app-layout>
    @php
        $canReportsExport = auth()->user()?->hasPermission('reports.export') ?? false;
        $hasActiveFilters = (($productQuery ?? '') !== '')
            || ((int) ($customerId ?? 0) > 0)
            || (($paymentMethod ?? 'all') !== 'all')
            || (($qrisReference ?? '') !== '');
        $resetQuery = ['period' => 'daily', 'start_date' => now()->toDateString(), 'end_date' => now()->toDateString(), 'customer_id' => 0, 'payment_method' => 'all', 'product' => '', 'qris_reference' => '', 'compact' => !empty($compact) ? 1 : 0];
    @endphp
    <x-slot name="header">
        <div class="pro-page-head">
            <div>
                <p class="page-kicker">Monitoring</p>
                <h2 class="pro-page-title">Laporan Penjualan</h2>
                <p class="pro-page-sub">Pantau performa penjualan lintas periode, kasir, dan metode bayar dalam satu layar rapi.</p>
            </div>
        </div>
    </x-slot>

    <div class="page-shell report-shell space-y-6 {{ !empty($compact) ? 'report-shell--compact' : '' }}">
        @if($errors->any())
            <div class="panel-card p-4 border border-rose-200 bg-rose-50 text-rose-700 text-sm">
                {{ $errors->first() }}
            </div>
        @endif
        <form method="GET" action="{{ route('reports.index') }}" class="panel-card pro-panel report-filter-card report-filter-card--sticky-mobile" id="report-filter-form">
            <div class="report-filter-head">
                <div>
                    <h3 class="report-section-title">Filter Laporan</h3>
                    <p class="report-section-subtitle">Atur periode, pelanggan, produk, dan metode pembayaran untuk melihat performa penjualan secara presisi.</p>
                </div>
                <div class="report-filter-actions report-filter-actions--responsive">
                    @php($compactQuery = array_merge(request()->query(), ['compact' => !empty($compact) ? 0 : 1]))
                    <a href="{{ route('reports.index', $compactQuery) }}" class="report-btn report-btn--density {{ !empty($compact) ? 'is-active' : '' }}">
                        {{ !empty($compact) ? 'Mode Normal' : 'Mode Compact' }}
                    </a>
                    <button class="btn-primary report-btn report-btn--primary">Tampilkan Data</button>
                    @if($canReportsExport)
                        <a href="{{ route('reports.export.excel', request()->query()) }}" class="report-btn report-btn--excel">Export Excel</a>
                        <a href="{{ route('reports.export.pdf', request()->query()) }}" class="report-btn report-btn--pdf">Export PDF</a>
                    @endif
                    <span id="report-selected-count" class="report-selected-count">Terpilih: 0 transaksi</span>
                    <span id="report-selected-total" class="report-selected-count">Nilai: Rp 0</span>
                    <button type="button" id="report-select-page" class="report-btn report-btn--density">Pilih Halaman Ini</button>
                    <button type="button" id="report-clear-selection" class="report-btn report-btn--density">Kosongkan Pilihan</button>
                    @if($canReportsExport)
                        <button type="button" id="report-export-selected-excel" class="report-btn report-btn--excel">Excel Terpilih</button>
                        <button type="button" id="report-export-selected-pdf" class="report-btn report-btn--pdf">PDF Terpilih</button>
                    @endif
                </div>
            </div>
            <div class="report-quick-preset">
                <button type="button" class="report-preset-chip" data-preset="today">Hari Ini</button>
                <button type="button" class="report-preset-chip" data-preset="week">7 Hari</button>
                <button type="button" class="report-preset-chip" data-preset="month">Bulan Ini</button>
                <button type="button" class="report-preset-chip" data-preset="qris">QRIS Saja</button>
            </div>
            <div class="report-filter-grid report-filter-grid--responsive">
                <input type="hidden" name="compact" value="{{ !empty($compact) ? 1 : 0 }}">
                <div class="report-filter-col">
                    <label class="label-ui">Periode</label>
                    <select name="period" class="input-ui">
                        <option value="daily" @selected($period === 'daily')>Harian</option>
                        <option value="weekly" @selected($period === 'weekly')>Mingguan</option>
                        <option value="monthly" @selected($period === 'monthly')>Bulanan</option>
                        <option value="custom" @selected($period === 'custom')>Custom</option>
                    </select>
                </div>
                <div class="report-filter-col">
                    <label class="label-ui">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="input-ui">
                </div>
                <div class="report-filter-col">
                    <label class="label-ui">Tanggal Akhir</label>
                    <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="input-ui">
                </div>
                <div class="report-filter-col report-filter-col--wide">
                    <label class="label-ui">Produk / SKU</label>
                    <input type="text" name="product" value="{{ $productQuery ?? '' }}" class="input-ui" placeholder="Contoh: THIAMYCIN / K24-574">
                </div>
                <div class="report-filter-col">
                    <label class="label-ui">Pelanggan</label>
                    <select name="customer_id" class="input-ui">
                        <option value="0">Semua Pelanggan</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(($customerId ?? 0) === (int) $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="report-filter-col">
                    <label class="label-ui">Metode Bayar</label>
                    <select name="payment_method" class="input-ui">
                        <option value="all" @selected(($paymentMethod ?? 'all') === 'all')>Semua Metode</option>
                        <option value="cash" @selected(($paymentMethod ?? 'all') === 'cash')>Cash</option>
                        <option value="qris" @selected(($paymentMethod ?? 'all') === 'qris')>QRIS</option>
                        <option value="debit" @selected(($paymentMethod ?? 'all') === 'debit')>Debit</option>
                        <option value="transfer" @selected(($paymentMethod ?? 'all') === 'transfer')>Transfer</option>
                        <option value="e_wallet" @selected(($paymentMethod ?? 'all') === 'e_wallet')>E-Wallet</option>
                        <option value="mixed" @selected(($paymentMethod ?? 'all') === 'mixed')>Mixed / Split</option>
                    </select>
                </div>
                <div class="report-filter-col">
                    <label class="label-ui">Ref QRIS</label>
                    <input type="text" name="qris_reference" value="{{ $qrisReference ?? '' }}" class="input-ui" placeholder="Cari Ref QRIS" id="report-qris-reference-input">
                    <p id="report-qris-reference-error" class="report-inline-error" style="display:none;">Format tidak valid. Gunakan huruf/angka dan simbol - _ . /</p>
                </div>
            </div>
        </form>
        <x-approval-modal
            id="report-approval-modal"
            title="Approval Export Besar"
            note="Export berisiko tinggi akan masuk Approval Queue. Isi alasan export."
            reason-id="report-approval-reason"
            email-id="report-approval-email"
            password-id="report-approval-password"
            reason-error-id="report-approval-reason-error"
            email-error-id="report-approval-email-error"
            password-error-id="report-approval-password-error"
            error-id="report-approval-error"
            cancel-id="report-approval-cancel"
            submit-id="report-approval-submit"
            reason-label="Alasan Export"
            email-label="Email Manager"
            password-label="Password Manager"
        />
        <div class="report-mobile-actionbar" id="report-mobile-actionbar">
            <div class="report-mobile-actionbar-meta">
                <span id="report-mobile-selected-count">0 dipilih</span>
                <span id="report-mobile-selected-total">Rp 0</span>
            </div>
            <div class="report-mobile-actionbar-actions">
                <button type="button" id="report-mobile-clear-selection" class="report-btn report-btn--density">Kosongkan</button>
                @if($canReportsExport)
                    <button type="button" id="report-mobile-export-excel" class="report-btn report-btn--excel">Excel</button>
                    <button type="button" id="report-mobile-export-pdf" class="report-btn report-btn--pdf">PDF</button>
                @endif
            </div>
        </div>

        <div class="report-mini-summary">
            <div class="report-mini-item">
                <p class="report-mini-label">Transaksi Hasil Filter</p>
                <p class="report-mini-value">{{ number_format($sales->total(), 0, ',', '.') }}</p>
            </div>
            <div class="report-mini-item">
                <p class="report-mini-label">Total Nilai Filter</p>
                <p class="report-mini-value">Rp {{ number_format((float) $sales->getCollection()->sum('total_amount'), 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="report-kpi-grid">
            <div class="report-kpi-card report-kpi-card--blue">
                <p class="report-kpi-label">Omzet</p>
                <p class="report-kpi-value">Rp {{ number_format($omzet, 0, ',', '.') }}</p>
            </div>
            <div class="report-kpi-card report-kpi-card--teal">
                <p class="report-kpi-label">Modal</p>
                <p class="report-kpi-value">Rp {{ number_format($modal, 0, ',', '.') }}</p>
            </div>
            <div class="report-kpi-card report-kpi-card--rose">
                <p class="report-kpi-label">Pengeluaran</p>
                <p class="report-kpi-value">Rp {{ number_format($expenses, 0, ',', '.') }}</p>
            </div>
            <div class="report-kpi-card report-kpi-card--amber">
                <p class="report-kpi-label">Laba</p>
                <p class="report-kpi-value">Rp {{ number_format($profit, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="panel-card overflow-hidden">
            <div class="panel-head report-panel-head">
                <h3 class="report-section-title">Komposisi Metode Pembayaran (Transaksi Paid)</h3>
            </div>
            <div class="panel-body">
                <div class="report-payment-layout">
                    <div class="report-donut-wrap">
                        <div id="payment-method-donut" class="report-payment-donut" aria-label="Komposisi metode pembayaran">
                            <div class="report-payment-donut-center">
                                <div id="report-donut-pct" class="report-payment-donut-pct">0%</div>
                                <div class="report-payment-donut-caption">Kontribusi Terbesar</div>
                                <div id="report-donut-total" class="report-payment-donut-total">Rp 0</div>
                            </div>
                        </div>
                    </div>
                    <div class="report-table-wrap">
                        <table class="table-ui report-table">
                            <thead>
                                <tr>
                                    <th>Metode</th>
                                    <th class="text-left">Jumlah Transaksi</th>
                                    <th class="text-left">Nilai Omzet</th>
                                    <th class="text-left">Kontribusi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($paymentSummary ?? collect()) as $row)
                                    <tr>
                                        <td class="font-semibold text-slate-800">{{ strtoupper(str_replace('_', ' ', (string) $row['method'])) }}</td>
                                        <td class="text-left">{{ number_format((int) $row['count'], 0, ',', '.') }}</td>
                                        <td class="text-left font-semibold">Rp {{ number_format((float) $row['amount'], 0, ',', '.') }}</td>
                                        <td class="text-left">{{ number_format((float) $row['pct'], 1, ',', '.') }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-left text-slate-500 py-8">Belum ada data metode pembayaran pada periode ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-card overflow-hidden">
            <div class="panel-head report-panel-head">
                <h3 class="report-section-title">Daftar Transaksi</h3>
            </div>
            <div id="report-loading-skeleton" class="report-loading-skeleton" style="display:none;">
                <div class="report-skeleton-line"></div>
                <div class="report-skeleton-line"></div>
                <div class="report-skeleton-line"></div>
            </div>
            <div class="report-mobile-cards">
                @forelse ($sales as $sale)
                    <article class="report-mobile-card">
                        <div class="report-mobile-card-head">
                            <label class="report-select-wrap">
                                <input type="checkbox" class="report-sale-selector" value="{{ $sale->id }}" data-total="{{ (float) $sale->total_amount }}">
                                <span>Pilih</span>
                            </label>
                            <a class="report-invoice-link" href="{{ route('sales.show', $sale) }}">{{ $sale->invoice_number }}</a>
                            <span class="status-chip {{ $sale->status->value === 'paid' ? 'status-paid' : ($sale->status->value === 'pending' ? 'status-pending' : 'status-cancelled') }}">
                                {{ strtoupper($sale->status->value) }}
                            </span>
                        </div>
                        <p class="report-mobile-meta">{{ $sale->sold_at?->format('d/m/Y H:i') }} • {{ $sale->user?->name ?: '-' }}</p>
                        <p class="report-mobile-meta">{{ $sale->customer?->name ?: ($sale->customer_name ?: 'Pelanggan Umum') }}</p>
                        <p class="report-mobile-meta">Metode: {{ strtoupper(str_replace('_', ' ', (string) ($sale->payment_method ?? 'cash'))) }}</p>
                        <div class="report-mobile-qris">
                            <span>Ref QRIS: {{ $sale->qris_reference_id ?: '-' }}</span>
                            @if($sale->qris_reference_id)
                                <button type="button" class="report-copy-btn" data-copy="{{ $sale->qris_reference_id }}">Salin</button>
                            @endif
                        </div>
                        <button type="button" class="report-mobile-detail-toggle" data-target="mobile-detail-{{ $sale->id }}">Detail cepat</button>
                        <div id="mobile-detail-{{ $sale->id }}" class="report-mobile-detail" hidden>
                            <p class="report-mobile-detail-title">Item Penjualan</p>
                            @forelse($sale->items as $item)
                                <div class="report-mobile-detail-row">
                                    <span>{{ $item->product_name }}</span>
                                    <span>{{ number_format((float) $item->quantity, 0, ',', '.') }} x Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</span>
                                </div>
                            @empty
                                <p class="report-mobile-meta">Tidak ada item.</p>
                            @endforelse
                        </div>
                        <p class="report-mobile-total">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</p>
                    </article>
                @empty
                    <div class="report-empty-state">
                        <p class="report-empty-title">Tidak ada transaksi untuk filter saat ini.</p>
                        <p class="report-empty-note">{{ $hasActiveFilters ? 'Coba ubah atau reset filter agar data muncul.' : 'Belum ada transaksi pada periode ini.' }}</p>
                        <a href="{{ route('reports.index', $resetQuery) }}" class="report-btn report-btn--density">Reset Filter</a>
                    </div>
                @endforelse
            </div>
            <div class="overflow-x-auto customer-table-wrap customer-table-shell report-table-wrap">
                <table class="table-ui customer-table report-table report-table--transactions report-table--transactions-mobile">
                    <thead>
                        <tr>
                            <th style="width:52px;min-width:52px;max-width:52px;padding-left:1rem;padding-right:.45rem;text-align:left !important;">
                                <input type="checkbox" id="report-select-all">
                            </th>
                            <th class="w-[30%]" style="text-align:left !important;">Transaksi</th>
                            <th class="w-[20%]" style="text-align:left !important;">Petugas</th>
                            <th class="w-[16%]" style="text-align:left !important;">Pembeli</th>
                            <th class="w-[20%]" style="text-align:left !important;">Pembayaran</th>
                            <th class="w-[14%]" style="text-align:left !important;">Ref QRIS</th>
                            <th class="w-[8%] text-left" style="text-align:left !important;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr class="customer-row">
                                <td style="width:52px;min-width:52px;max-width:52px;padding-left:1rem;padding-right:.45rem;text-align:left !important;">
                                    <input type="checkbox" class="report-sale-selector" value="{{ $sale->id }}" data-total="{{ (float) $sale->total_amount }}">
                                </td>
                                <td style="text-align:left !important;">
                                    <div class="report-cell-title">{{ $sale->sold_at?->format('d/m/Y H:i') }}</div>
                                    <a class="report-invoice-link" href="{{ route('sales.show', $sale) }}">{{ $sale->invoice_number }}</a>
                                </td>
                                <td style="text-align:left !important;">
                                    <div class="report-cell-title">{{ $sale->user?->name ?: '-' }}</div>
                                </td>
                                <td style="text-align:left !important;">
                                    <div class="report-cell-title">{{ $sale->customer?->name ?: ($sale->customer_name ?: 'Pelanggan Umum') }}</div>
                                </td>
                                <td style="text-align:left !important;">
                                    <div class="report-payment-badges">
                                        <span class="status-chip {{ $sale->status->value === 'paid' ? 'status-paid' : ($sale->status->value === 'pending' ? 'status-pending' : 'status-cancelled') }}">
                                            {{ strtoupper($sale->status->value) }}
                                        </span>
                                        @if($sale->status->value === 'paid')
                                            <span class="report-method-chip">{{ strtoupper(str_replace('_', ' ', (string) ($sale->payment_method ?? 'cash'))) }}</span>
                                        @else
                                            <span class="report-method-chip report-method-chip--muted">BELUM DIBAYAR</span>
                                        @endif
                                    </div>
                                </td>
                                <td style="text-align:left !important;">
                                    <div class="report-cell-sub report-qris-ref-cell">{{ $sale->qris_reference_id ?: '-' }}</div>
                                    @if($sale->qris_reference_id)
                                        <button type="button" class="report-copy-btn mt-1" data-copy="{{ $sale->qris_reference_id }}">Salin</button>
                                    @endif
                                </td>
                                <td class="text-left report-total-cell" style="text-align:left !important;">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-left text-slate-500 py-8">
                                    Tidak ada data transaksi pada periode ini.
                                    <div class="mt-2">
                                        <a href="{{ route('reports.index', $resetQuery) }}" class="report-btn report-btn--density">Reset Filter</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $sales->links() }}
            </div>
        </div>

        <div class="panel-card overflow-hidden">
            <div class="panel-head report-panel-head">
                <h3 class="report-section-title">Riwayat Metode Bayar per Kasir</h3>
            </div>
            <div class="overflow-x-auto customer-table-wrap customer-table-shell report-table-wrap">
                <table class="table-ui customer-table report-table report-table--cashier-payment">
                    <thead>
                        <tr>
                            <th style="width:32%;text-align:left !important;">Kasir</th>
                            <th class="text-left" style="width:14%;text-align:left !important;">Transaksi Paid</th>
                            <th style="width:18%;text-align:left !important;">Metode Favorit</th>
                            <th class="text-left" style="width:18%;text-align:left !important;">Proporsi Split</th>
                            <th class="text-left" style="width:18%;text-align:left !important;">Omzet Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($cashierPaymentStats ?? collect()) as $row)
                            <tr class="customer-row">
                                <td class="report-cell-title" style="text-align:left !important;">{{ $row['cashier_name'] }}</td>
                                <td class="text-left" style="text-align:left !important;">{{ number_format((int) $row['total_tx'], 0, ',', '.') }}</td>
                                <td style="text-align:left !important;">
                                    <span class="report-method-chip">{{ strtoupper(str_replace('_', ' ', (string) $row['favorite_method'])) }}</span>
                                </td>
                                <td class="text-left" style="text-align:left !important;">
                                    <span class="{{ (float) $row['split_pct'] >= 30 ? 'text-amber-700' : 'text-slate-700' }}">
                                        {{ number_format((float) $row['split_pct'], 1, ',', '.') }}% ({{ number_format((int) $row['split_count'], 0, ',', '.') }})
                                    </span>
                                </td>
                                <td class="text-left report-total-cell" style="text-align:left !important;">Rp {{ number_format((float) $row['total_paid'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-left text-slate-500 py-8">Belum ada transaksi paid pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const filterForm = document.getElementById('report-filter-form');
            const qrisInput = document.getElementById('report-qris-reference-input');
            const qrisError = document.getElementById('report-qris-reference-error');
            const skeleton = document.getElementById('report-loading-skeleton');
            const storageKey = 'report_filters_pref_v1';
            const selectedStorageKey = 'report_selected_sales_v1';
            const selectedMetaStorageKey = 'report_selected_sales_meta_v1';
            const hasQuery = window.location.search && window.location.search.length > 1;
            const periodEl = filterForm?.querySelector('select[name="period"]');
            const startEl = filterForm?.querySelector('input[name="start_date"]');
            const endEl = filterForm?.querySelector('input[name="end_date"]');
            const methodEl = filterForm?.querySelector('select[name="payment_method"]');

            const selectedCheckboxes = () => Array.from(document.querySelectorAll('.report-sale-selector'));
            const selectedIds = () => selectedCheckboxes().filter((x) => x.checked).map((x) => String(x.value || '').trim()).filter(Boolean);
            const selectAll = document.getElementById('report-select-all');
            const selectedCountEl = document.getElementById('report-selected-count');
            const selectedTotalEl = document.getElementById('report-selected-total');
            const exportSelectedExcelBtn = document.getElementById('report-export-selected-excel');
            const exportSelectedPdfBtn = document.getElementById('report-export-selected-pdf');
            const selectPageBtn = document.getElementById('report-select-page');
            const clearSelectionBtn = document.getElementById('report-clear-selection');
            const mobileCountEl = document.getElementById('report-mobile-selected-count');
            const mobileTotalEl = document.getElementById('report-mobile-selected-total');
            const mobileClearBtn = document.getElementById('report-mobile-clear-selection');
            const mobileExportExcelBtn = document.getElementById('report-mobile-export-excel');
            const mobileExportPdfBtn = document.getElementById('report-mobile-export-pdf');
            const approvalModal = document.getElementById('report-approval-modal');
            const approvalReason = document.getElementById('report-approval-reason');
            const approvalEmail = document.getElementById('report-approval-email');
            const approvalPassword = document.getElementById('report-approval-password');
            const approvalError = document.getElementById('report-approval-error');
            const approvalReasonError = document.getElementById('report-approval-reason-error');
            const approvalEmailError = document.getElementById('report-approval-email-error');
            const approvalPasswordError = document.getElementById('report-approval-password-error');
            const approvalCancel = document.getElementById('report-approval-cancel');
            const approvalSubmit = document.getElementById('report-approval-submit');
            let pendingExportUrl = '';
            const serverErrors = @json($errors->toArray());

            const readSelectedStore = () => {
                try {
                    const raw = sessionStorage.getItem(selectedStorageKey);
                    const parsed = raw ? JSON.parse(raw) : [];
                    return Array.isArray(parsed) ? parsed.map((v) => String(v)).filter(Boolean) : [];
                } catch (e) {
                    return [];
                }
            };
            const writeSelectedStore = (values) => {
                const unique = Array.from(new Set((values || []).map((v) => String(v)).filter(Boolean)));
                sessionStorage.setItem(selectedStorageKey, JSON.stringify(unique));
            };
            const readSelectedMetaStore = () => {
                try {
                    const raw = sessionStorage.getItem(selectedMetaStorageKey);
                    const parsed = raw ? JSON.parse(raw) : {};
                    return parsed && typeof parsed === 'object' ? parsed : {};
                } catch (e) {
                    return {};
                }
            };
            const writeSelectedMetaStore = (meta) => {
                sessionStorage.setItem(selectedMetaStorageKey, JSON.stringify(meta || {}));
            };

            const applySelectAllState = () => {
                const all = selectedCheckboxes();
                const selected = selectedIds();
                writeSelectedStore(selected);
                const checkedCount = selected.length;
                const meta = readSelectedMetaStore();
                selectedCheckboxes().forEach((cb) => {
                    const id = String(cb.value || '').trim();
                    if (!id) return;
                    if (cb.checked) {
                        meta[id] = Number(cb.getAttribute('data-total') || 0);
                    } else {
                        delete meta[id];
                    }
                });
                writeSelectedMetaStore(meta);
                const checkedTotal = selected.reduce((sum, id) => sum + Number(meta[id] || 0), 0);
                const formattedTotal = `Rp ${new Intl.NumberFormat('id-ID').format(Math.round(checkedTotal))}`;
                if (exportSelectedExcelBtn) exportSelectedExcelBtn.disabled = checkedCount === 0;
                if (exportSelectedPdfBtn) exportSelectedPdfBtn.disabled = checkedCount === 0;
                if (mobileExportExcelBtn) mobileExportExcelBtn.disabled = checkedCount === 0;
                if (mobileExportPdfBtn) mobileExportPdfBtn.disabled = checkedCount === 0;
                if (clearSelectionBtn) clearSelectionBtn.disabled = checkedCount === 0;
                if (mobileClearBtn) mobileClearBtn.disabled = checkedCount === 0;
                if (selectedCountEl) {
                    selectedCountEl.textContent = `Terpilih: ${checkedCount} transaksi`;
                }
                if (selectedTotalEl) selectedTotalEl.textContent = `Nilai: ${formattedTotal}`;
                if (mobileCountEl) mobileCountEl.textContent = `${checkedCount} dipilih`;
                if (mobileTotalEl) mobileTotalEl.textContent = formattedTotal;
                if (!selectAll) return;
                if (all.length === 0) {
                    selectAll.checked = false;
                    selectAll.indeterminate = false;
                    return;
                }
                const checkedOnPage = all.filter((x) => x.checked).length;
                selectAll.checked = checkedOnPage > 0 && checkedOnPage === all.length;
                selectAll.indeterminate = checkedOnPage > 0 && checkedOnPage < all.length;
            };
            const hydrateSelectedFromStore = () => {
                const selected = new Set(readSelectedStore());
                selectedCheckboxes().forEach((cb) => {
                    cb.checked = selected.has(String(cb.value || ''));
                });
            };
            hydrateSelectedFromStore();
            selectedCheckboxes().forEach((cb) => cb.addEventListener('change', applySelectAllState));
            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    selectedCheckboxes().forEach((cb) => { cb.checked = !!selectAll.checked; });
                    applySelectAllState();
                });
            }
            if (selectPageBtn) {
                selectPageBtn.addEventListener('click', () => {
                    selectedCheckboxes().forEach((cb) => { cb.checked = true; });
                    applySelectAllState();
                });
            }
            if (clearSelectionBtn) {
                clearSelectionBtn.addEventListener('click', () => {
                    selectedCheckboxes().forEach((cb) => { cb.checked = false; });
                    sessionStorage.removeItem(selectedStorageKey);
                    sessionStorage.removeItem(selectedMetaStorageKey);
                    applySelectAllState();
                });
            }
            if (mobileClearBtn) {
                mobileClearBtn.addEventListener('click', () => {
                    selectedCheckboxes().forEach((cb) => { cb.checked = false; });
                    sessionStorage.removeItem(selectedStorageKey);
                    sessionStorage.removeItem(selectedMetaStorageKey);
                    applySelectAllState();
                });
            }
            applySelectAllState();

            const buildSelectedExportUrl = (baseUrl) => {
                const ids = selectedIds();
                if (ids.length === 0) return '';
                const url = new URL(baseUrl, window.location.origin);
                const formData = new FormData(filterForm || document.createElement('form'));
                for (const [k, v] of formData.entries()) {
                    if (k === 'selected_ids[]') continue;
                    url.searchParams.set(k, String(v));
                }
                ids.forEach((id) => url.searchParams.append('selected_ids[]', id));
                return url.toString();
            };
            const isLargeSelection = () => {
                const selected = selectedCheckboxes().filter((x) => x.checked);
                const count = selected.length;
                const total = selected.reduce((sum, x) => sum + Number(x.getAttribute('data-total') || 0), 0);
                return count >= 300 || total >= 100000000;
            };
            const closeApprovalModal = () => {
                if (!approvalModal) return;
                approvalModal.classList.remove('is-open');
                approvalModal.setAttribute('aria-hidden', 'true');
                pendingExportUrl = '';
                if (approvalError) approvalError.style.display = 'none';
                [approvalReasonError, approvalEmailError, approvalPasswordError].forEach((el) => {
                    if (!el) return;
                    el.style.display = 'none';
                    el.textContent = '';
                });
            };
            const openApprovalModal = (exportUrl) => {
                pendingExportUrl = exportUrl;
                if (!approvalModal) return;
                approvalModal.classList.add('is-open');
                approvalModal.setAttribute('aria-hidden', 'false');
                if (approvalError) approvalError.style.display = 'none';
                approvalReason?.focus();
            };
            const renderApprovalServerErrors = () => {
                const reasonMsg = Array.isArray(serverErrors?.export_reason) ? String(serverErrors.export_reason[0] || '') : '';
                const emailMsg = Array.isArray(serverErrors?.manager_approval_email) ? String(serverErrors.manager_approval_email[0] || '') : '';
                const passwordMsg = Array.isArray(serverErrors?.manager_approval_password) ? String(serverErrors.manager_approval_password[0] || '') : '';
                const rateLimitMsg = Array.isArray(serverErrors?.export_rate_limit) ? String(serverErrors.export_rate_limit[0] || '') : '';
                if (approvalReasonError) {
                    approvalReasonError.textContent = reasonMsg;
                    approvalReasonError.style.display = reasonMsg ? 'block' : 'none';
                }
                if (approvalEmailError) {
                    approvalEmailError.textContent = emailMsg;
                    approvalEmailError.style.display = emailMsg ? 'block' : 'none';
                }
                if (approvalPasswordError) {
                    approvalPasswordError.textContent = passwordMsg;
                    approvalPasswordError.style.display = passwordMsg ? 'block' : 'none';
                }
                const combined = [reasonMsg, emailMsg, passwordMsg, rateLimitMsg].filter(Boolean).join(' | ');
                if (approvalError) {
                    approvalError.textContent = combined;
                    approvalError.style.display = combined ? 'block' : 'none';
                }
                return combined !== '';
            };
            approvalCancel?.addEventListener('click', closeApprovalModal);
            approvalModal?.addEventListener('click', (event) => {
                if (event.target === approvalModal) closeApprovalModal();
            });
            approvalSubmit?.addEventListener('click', () => {
                const reason = String(approvalReason?.value || '').trim();
                if (reason.length < 5) {
                    if (approvalError) {
                        approvalError.textContent = 'Lengkapi alasan minimal 5 karakter.';
                        approvalError.style.display = 'block';
                    }
                    return;
                }
                const url = new URL(String(pendingExportUrl || ''), window.location.origin);
                url.searchParams.set('export_reason', reason);
                closeApprovalModal();
                window.location.href = url.toString();
            });
            if (approvalReason) approvalReason.value = @json((string) request('export_reason', ''));
            if (approvalEmail) approvalEmail.value = @json((string) request('manager_approval_email', ''));
            if (renderApprovalServerErrors()) {
                openApprovalModal('');
            }
            if (exportSelectedExcelBtn) {
                exportSelectedExcelBtn.addEventListener('click', () => {
                    const ids = readSelectedStore();
                    if (ids.length > 100 && !window.confirm(`Anda memilih ${ids.length} transaksi. Lanjut export Excel?`)) return;
                    const url = ids.length > 0
                        ? (() => {
                            const u = new URL(@json(route('reports.export.excel')), window.location.origin);
                            const formData = new FormData(filterForm || document.createElement('form'));
                            for (const [k, v] of formData.entries()) u.searchParams.set(k, String(v));
                            ids.forEach((id) => u.searchParams.append('selected_ids[]', id));
                            return u.toString();
                        })()
                        : '';
                    if (!url) return alert('Pilih minimal 1 transaksi.');
                    if (isLargeSelection()) return openApprovalModal(url);
                    window.location.href = url;
                });
            }
            if (exportSelectedPdfBtn) {
                exportSelectedPdfBtn.addEventListener('click', () => {
                    const ids = readSelectedStore();
                    if (ids.length > 100 && !window.confirm(`Anda memilih ${ids.length} transaksi. Lanjut export PDF?`)) return;
                    const url = ids.length > 0
                        ? (() => {
                            const u = new URL(@json(route('reports.export.pdf')), window.location.origin);
                            const formData = new FormData(filterForm || document.createElement('form'));
                            for (const [k, v] of formData.entries()) u.searchParams.set(k, String(v));
                            ids.forEach((id) => u.searchParams.append('selected_ids[]', id));
                            return u.toString();
                        })()
                        : '';
                    if (!url) return alert('Pilih minimal 1 transaksi.');
                    if (isLargeSelection()) return openApprovalModal(url);
                    window.location.href = url;
                });
            }
            if (mobileExportExcelBtn) {
                mobileExportExcelBtn.addEventListener('click', () => exportSelectedExcelBtn?.click());
            }
            if (mobileExportPdfBtn) {
                mobileExportPdfBtn.addEventListener('click', () => exportSelectedPdfBtn?.click());
            }

            const qrisRegex = /^[A-Za-z0-9][A-Za-z0-9\-_.\/]{2,119}$/;
            const validateQrisRefInput = () => {
                if (!qrisInput || !qrisError) return true;
                const value = String(qrisInput.value || '').trim();
                if (value === '') {
                    qrisError.style.display = 'none';
                    qrisInput.classList.remove('report-input-invalid');
                    return true;
                }
                const ok = qrisRegex.test(value);
                qrisError.style.display = ok ? 'none' : 'block';
                qrisInput.classList.toggle('report-input-invalid', !ok);
                return ok;
            };
            if (qrisInput) {
                qrisInput.addEventListener('input', validateQrisRefInput);
                validateQrisRefInput();
            }

            if (filterForm) {
                filterForm.addEventListener('submit', (event) => {
                    if (!validateQrisRefInput()) {
                        event.preventDefault();
                        return;
                    }
                    if (skeleton) skeleton.style.display = 'block';
                    const payload = Object.fromEntries(new FormData(filterForm).entries());
                    localStorage.setItem(storageKey, JSON.stringify(payload));
                });
            }

            if (!hasQuery && filterForm) {
                try {
                    const raw = localStorage.getItem(storageKey);
                    if (raw) {
                        const payload = JSON.parse(raw);
                        Object.entries(payload).forEach(([k, v]) => {
                            const el = filterForm.querySelector(`[name="${k}"]`);
                            if (el && typeof v === 'string') el.value = v;
                        });
                    }
                } catch (e) {}
            }

            document.querySelectorAll('.report-preset-chip').forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (!filterForm || !periodEl || !startEl || !endEl || !methodEl) return;
                    const preset = String(btn.dataset.preset || '');
                    const now = new Date();
                    const iso = (d) => d.toISOString().slice(0, 10);
                    const start = new Date(now);
                    const end = new Date(now);
                    if (preset === 'week') start.setDate(now.getDate() - 6);
                    if (preset === 'month') start.setDate(1);
                    periodEl.value = preset === 'today' ? 'daily' : (preset === 'month' ? 'monthly' : 'custom');
                    startEl.value = iso(start);
                    endEl.value = iso(end);
                    if (preset === 'qris') methodEl.value = 'qris';
                    filterForm.requestSubmit();
                });
            });

            document.querySelectorAll('.report-copy-btn').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const text = String(btn.getAttribute('data-copy') || '').trim();
                    if (!text) return;
                    try {
                        await navigator.clipboard.writeText(text);
                        btn.textContent = 'Tersalin';
                        setTimeout(() => { btn.textContent = 'Salin'; }, 1200);
                    } catch (e) {}
                });
            });
            document.querySelectorAll('.report-mobile-detail-toggle').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const target = document.getElementById(String(btn.getAttribute('data-target') || ''));
                    if (!target) return;
                    const willOpen = target.hasAttribute('hidden');
                    if (willOpen) {
                        target.removeAttribute('hidden');
                        btn.textContent = 'Tutup detail';
                    } else {
                        target.setAttribute('hidden', 'hidden');
                        btn.textContent = 'Detail cepat';
                    }
                });
            });

            const rows = @json(collect($paymentSummary ?? [])->values());
            const omzetPaid = Number(@json((float) ($omzet ?? 0)));
            const donut = document.getElementById('payment-method-donut');
            const pctEl = document.getElementById('report-donut-pct');
            const totalEl = document.getElementById('report-donut-total');
            if (!donut || !pctEl || !totalEl || !Array.isArray(rows) || rows.length === 0) return;

            const palette = ['#2563eb', '#16a34a', '#f59e0b', '#0ea5e9', '#7c3aed', '#ef4444'];
            let start = 0;
            const parts = [];
            rows.forEach((row, idx) => {
                const pct = Math.max(0, Number(row.pct || 0));
                const end = Math.min(100, start + pct);
                const color = palette[idx % palette.length];
                parts.push(`${color} ${start}% ${end}%`);
                start = end;
            });
            if (start < 100) {
                parts.push(`#e2e8f0 ${start}% 100%`);
            }

            donut.style.background = `conic-gradient(${parts.join(', ')})`;

            const top = rows[0] || { pct: 0 };
            const formatter = new Intl.NumberFormat('id-ID');
            pctEl.textContent = `${Number(top.pct || 0).toFixed(1).replace('.', ',')}%`;
            totalEl.textContent = `Rp ${formatter.format(Math.round(omzetPaid))}`;
        })();
    </script>
</x-app-layout>
