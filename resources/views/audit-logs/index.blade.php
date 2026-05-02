<x-app-layout>
    @php
        $actionLabels = [
            'hold_saved' => 'Simpan Hold',
            'hold_loaded' => 'Muat Hold',
            'hold_deleted' => 'Hapus Hold',
            'hold_overwritten' => 'Timpa Hold',
            'cart_cleared' => 'Kosongkan Keranjang',
            'checkout_submit_started' => 'Mulai Checkout',
            'checkout_failed_client' => 'Checkout Gagal',
            'idle_warning' => 'Kasir Idle',
            'stock_sync_adjusted' => 'Penyesuaian Stok',
            'checkout_success' => 'Checkout Berhasil',
            'customer_merged' => 'Gabung Pelanggan',
            'sale_refunded' => 'Refund Transaksi',
            'sale_voided' => 'Void Transaksi',
            'pending_settled_pos' => 'Lunasi Pending POS',
            'report_export_excel' => 'Export Laporan Excel',
            'report_export_pdf' => 'Export Laporan PDF',
            'expense_created' => 'Pengeluaran Ditambah',
            'expense_updated' => 'Pengeluaran Diubah',
            'expense_deleted' => 'Pengeluaran Dihapus',
            'expense_duplicated' => 'Pengeluaran Diduplikasi',
        ];
        $humanContext = function ($log) {
            $ctx = (array) ($log->context ?? []);
            $action = (string) $log->action;

            if ($action === 'idle_warning' && isset($ctx['idle_ms'])) {
                $minutes = (int) round(((int) $ctx['idle_ms']) / 60000);
                return "Kasir tidak beraktivitas selama {$minutes} menit.";
            }

            if ($action === 'hold_saved' && isset($ctx['hold_id'])) {
                $qty = isset($ctx['total_qty']) ? ' ('.$ctx['total_qty'].' qty)' : '';
                return "Menyimpan transaksi hold {$ctx['hold_id']}{$qty}.";
            }

            if ($action === 'hold_loaded' && isset($ctx['hold_id'])) {
                $qty = isset($ctx['total_qty']) ? ' ('.$ctx['total_qty'].' qty)' : '';
                return "Memuat transaksi hold {$ctx['hold_id']}{$qty}.";
            }

            if ($action === 'hold_deleted' && isset($ctx['hold_id'])) {
                return "Menghapus transaksi hold {$ctx['hold_id']}.";
            }

            if ($action === 'hold_overwritten' && isset($ctx['hold_id'])) {
                return "Menimpa data hold {$ctx['hold_id']} dengan keranjang terbaru.";
            }

            if ($action === 'cart_cleared') {
                $reason = (string) ($ctx['reason'] ?? '');
                if ($reason === 'void_before_finalize') {
                    $reasonText = trim((string) ($ctx['reason_text'] ?? ''));
                    return $reasonText !== ''
                        ? "Kasir melakukan void sebelum checkout. Alasan: {$reasonText}."
                        : 'Kasir melakukan void sebelum checkout.';
                }
                return 'Kasir mengosongkan seluruh isi keranjang.';
            }

            if ($action === 'checkout_submit_started') {
                return 'Kasir menekan simpan transaksi dan memulai proses checkout.';
            }

            if ($action === 'checkout_failed_client' && isset($ctx['reason'])) {
                $reason = match($ctx['reason']) {
                    'empty_cart' => 'keranjang kosong',
                    'split_invalid' => 'split payment tidak valid',
                    'payment_limit_exceeded' => 'nominal melebihi batas metode',
                    default => 'jumlah bayar kurang',
                };
                return "Checkout gagal karena {$reason}.";
            }

            if ($action === 'pending_settled_pos') {
                $inv = $ctx['invoice'] ?? '-';
                $method = strtoupper(str_replace('_', ' ', (string) ($ctx['payment_method'] ?? '-')));
                return "Pending {$inv} dilunasi dari POS via {$method}.";
            }

            if ($action === 'sale_refunded') {
                $inv = $ctx['invoice'] ?? '-';
                $reason = $ctx['reason'] ?? '-';
                return "Refund transaksi {$inv}. Alasan: {$reason}.";
            }

            if ($action === 'sale_voided') {
                $inv = $ctx['invoice'] ?? '-';
                $reason = $ctx['reason'] ?? '-';
                return "Void transaksi {$inv}. Alasan: {$reason}.";
            }

            if ($action === 'stock_sync_adjusted') {
                return 'Sistem menyesuaikan qty keranjang mengikuti stok terbaru.';
            }

            if ($action === 'report_export_excel' || $action === 'report_export_pdf') {
                $type = $action === 'report_export_excel' ? 'Excel' : 'PDF';
                $count = (int) ($ctx['selected_count'] ?? 0);
                $total = (float) ($ctx['selected_total'] ?? 0);
                $range = (($ctx['start_date'] ?? '-') . ' s/d ' . ($ctx['end_date'] ?? '-'));
                return "Export laporan {$type} | {$count} transaksi | Rp ".number_format($total, 0, ',', '.')." | {$range}.";
            }

            if ($action === 'checkout_success' && isset($ctx['invoice'])) {
                return "Checkout berhasil, invoice {$ctx['invoice']}.";
            }

            if ($action === 'customer_merged') {
                $src = $ctx['source_customer_name'] ?? ('#'.($ctx['source_customer_id'] ?? '-'));
                $dst = $ctx['target_customer_name'] ?? ('#'.($ctx['target_customer_id'] ?? '-'));
                $moved = (int) ($ctx['moved_sales_count'] ?? 0);
                return "Menggabungkan customer {$src} ke {$dst} ({$moved} transaksi dipindahkan).";
            }

            return !empty($ctx)
                ? collect($ctx)->map(function ($v, $k) {
                    $value = is_array($v) || is_object($v)
                        ? json_encode($v, JSON_UNESCAPED_UNICODE)
                        : (string) $v;
                    return ucfirst(str_replace('_', ' ', (string) $k)).': '.$value;
                })->implode(' | ')
                : '-';
        };
    @endphp
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="page-kicker">Monitoring</p>
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Audit Log Kasir</h2>
            </div>
        </div>
    </x-slot>

    <div class="page-shell space-y-6">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="panel-card p-5 grid grid-cols-1 md:grid-cols-7 gap-3 items-end audit-filter-form">
            <div>
                <label class="label-ui">Dari</label>
                <input type="date" name="from" value="{{ $from }}" class="input-ui audit-filter-input">
            </div>
            <div>
                <label class="label-ui">Sampai</label>
                <input type="date" name="to" value="{{ $to }}" class="input-ui audit-filter-input">
            </div>
            <div>
                <label class="label-ui">Kasir</label>
                <select name="user_id" class="input-ui audit-filter-input">
                    <option value="0">Semua Kasir</option>
                    @foreach($cashiers as $cashier)
                        <option value="{{ $cashier->id }}" @selected($userId === (int) $cashier->id)>{{ $cashier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label-ui">Aksi</label>
                <select name="action" class="input-ui audit-filter-input">
                    <option value="">Semua Aksi</option>
                    @foreach($actions as $item)
                        <option value="{{ $item }}" @selected($action === $item)>{{ $actionLabels[$item] ?? $item }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label-ui">Cari Cepat</label>
                <input type="text" name="q" value="{{ $q ?? '' }}" class="input-ui audit-filter-input" placeholder="Invoice / Hold ID / IP / Context">
            </div>
            <div class="md:col-span-2 flex flex-wrap gap-2 audit-filter-actions">
                <button class="btn-primary audit-filter-btn">Filter</button>
                <a href="{{ route('audit-logs.index') }}" class="btn-danger-lite audit-filter-btn audit-filter-btn--reset">Reset</a>
                <a href="{{ route('audit-logs.export.excel', request()->query()) }}" class="btn-success audit-filter-btn">Export Excel</a>
                <a href="{{ route('audit-logs.export.pdf', request()->query()) }}" class="btn-primary audit-filter-btn">Export PDF</a>
            </div>
        </form>
        <div class="audit-quick-filters">
            <a href="{{ route('audit-logs.index', array_merge(request()->query(), ['export_type' => 'all'])) }}" class="audit-quick-chip {{ ($exportType ?? 'all') === 'all' ? 'is-active' : '' }}">Semua</a>
            <a href="{{ route('audit-logs.index', array_merge(request()->query(), ['export_type' => 'excel'])) }}" class="audit-quick-chip {{ ($exportType ?? 'all') === 'excel' ? 'is-active' : '' }}">Jenis: Excel</a>
            <a href="{{ route('audit-logs.index', array_merge(request()->query(), ['export_type' => 'pdf'])) }}" class="audit-quick-chip {{ ($exportType ?? 'all') === 'pdf' ? 'is-active' : '' }}">Jenis: PDF</a>
            <a href="{{ route('audit-logs.index', array_merge(request()->query(), ['export_type' => 'mass'])) }}" class="audit-quick-chip {{ ($exportType ?? 'all') === 'mass' ? 'is-active' : '' }}">Mass Export</a>
            <a href="{{ route('audit-logs.index', array_merge(request()->query(), ['export_type' => 'mass', 'sort' => 'export_priority', 'action' => ''])) }}" class="audit-quick-chip {{ ($exportType ?? 'all') === 'mass' && ($sortMode ?? 'latest') === 'export_priority' ? 'is-active' : '' }}">Lihat Semua Anomali</a>
            <a href="{{ route('audit-logs.index', array_merge(request()->query(), ['sort' => 'export_priority'])) }}" class="audit-quick-chip {{ ($sortMode ?? 'latest') === 'export_priority' ? 'is-active' : '' }}">Prioritas Nilai</a>
        </div>
        <div class="audit-quick-filters">
            <a href="{{ route('audit-logs.index', array_merge(request()->query(), ['action' => 'customer_merged', 'export_type' => 'all'])) }}" class="audit-quick-chip {{ $action === 'customer_merged' ? 'is-active' : '' }}">Gabung Pelanggan</a>
            <a href="{{ route('audit-logs.index', array_merge(request()->query(), ['action' => 'checkout_failed_client', 'export_type' => 'all'])) }}" class="audit-quick-chip {{ $action === 'checkout_failed_client' ? 'is-active' : '' }}">Checkout Gagal</a>
            <a href="{{ route('audit-logs.index', array_merge(request()->query(), ['action' => 'stock_sync_adjusted', 'export_type' => 'all'])) }}" class="audit-quick-chip {{ $action === 'stock_sync_adjusted' ? 'is-active' : '' }}">Penyesuaian Stok</a>
            <a href="{{ route('audit-logs.index', array_merge(request()->query(), ['action' => 'expense_created', 'export_type' => 'all'])) }}" class="audit-quick-chip {{ $action === 'expense_created' ? 'is-active' : '' }}">Pengeluaran Ditambah</a>
            <a href="{{ route('audit-logs.index', array_merge(request()->query(), ['action' => 'expense_updated', 'export_type' => 'all'])) }}" class="audit-quick-chip {{ $action === 'expense_updated' ? 'is-active' : '' }}">Pengeluaran Diubah</a>
            <a href="{{ route('audit-logs.index', array_merge(request()->query(), ['action' => 'expense_deleted', 'export_type' => 'all'])) }}" class="audit-quick-chip {{ $action === 'expense_deleted' ? 'is-active' : '' }}">Pengeluaran Dihapus</a>
            <a href="{{ route('audit-logs.index', array_merge(request()->query(), ['action' => 'expense_duplicated', 'export_type' => 'all'])) }}" class="audit-quick-chip {{ $action === 'expense_duplicated' ? 'is-active' : '' }}">Pengeluaran Duplikasi</a>
            <a href="{{ route('audit-logs.index', array_merge(request()->query(), ['action' => 'report_export_excel', 'export_type' => 'all'])) }}" class="audit-quick-chip {{ $action === 'report_export_excel' ? 'is-active' : '' }}">Export Excel</a>
            <a href="{{ route('audit-logs.index', array_merge(request()->query(), ['action' => 'report_export_pdf', 'export_type' => 'all'])) }}" class="audit-quick-chip {{ $action === 'report_export_pdf' ? 'is-active' : '' }}">Export PDF</a>
        </div>
        <div class="panel-card p-4 border border-slate-200">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-bold text-slate-900">Incident Timeline</h3>
                <span class="text-xs text-slate-500">20 kejadian terakhir</span>
            </div>
            <div class="space-y-2">
                @forelse(($incidentTimeline ?? collect()) as $evt)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <div class="text-xs text-slate-500">{{ $evt->created_at?->format('d/m/Y H:i:s') }} · {{ $evt->user?->name ?: 'SYSTEM' }}</div>
                        <div class="text-sm font-semibold text-slate-800">{{ $evt->action }}</div>
                        <div class="text-xs text-slate-600 break-all">{{ json_encode((array) ($evt->context ?? []), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</div>
                    </div>
                @empty
                    <div class="text-xs text-slate-500">Belum ada incident timeline.</div>
                @endforelse
            </div>
        </div>

        <div class="audit-metric-grid">
            <div class="audit-metric-card audit-metric-card--sky"><p class="audit-metric-label">Total Log</p><p class="audit-metric-value">{{ number_format($summary['total'], 0, ',', '.') }}</p></div>
            <div class="audit-metric-card audit-metric-card--teal"><p class="audit-metric-label">Event Hold</p><p class="audit-metric-value">{{ number_format($summary['hold_events'], 0, ',', '.') }}</p></div>
            <div class="audit-metric-card audit-metric-card--rose"><p class="audit-metric-label">Checkout Gagal</p><p class="audit-metric-value">{{ number_format($summary['checkout_failed'], 0, ',', '.') }}</p></div>
            <div class="audit-metric-card audit-metric-card--amber"><p class="audit-metric-label">Stok Disesuaikan</p><p class="audit-metric-value">{{ number_format($summary['stock_adjusted'], 0, ',', '.') }}</p></div>
            <div class="audit-metric-card audit-metric-card--violet"><p class="audit-metric-label">Gabung Pelanggan</p><p class="audit-metric-value">{{ number_format($summary['customer_merged'], 0, ',', '.') }}</p></div>
            <div class="audit-metric-card audit-metric-card--sky"><p class="audit-metric-label">Aksi Pengeluaran</p><p class="audit-metric-value">{{ number_format($summary['expense_events'] ?? 0, 0, ',', '.') }}</p></div>
            <div class="audit-metric-card audit-metric-card--sky"><p class="audit-metric-label">Export Excel</p><p class="audit-metric-value">{{ number_format($summary['report_export_excel'] ?? 0, 0, ',', '.') }}</p></div>
            <div class="audit-metric-card audit-metric-card--teal"><p class="audit-metric-label">Export PDF</p><p class="audit-metric-value">{{ number_format($summary['report_export_pdf'] ?? 0, 0, ',', '.') }}</p></div>
            <div class="audit-metric-card audit-metric-card--amber"><p class="audit-metric-label">Total Nilai Export</p><p class="audit-metric-value">Rp {{ number_format((float) ($summary['report_export_total'] ?? 0), 0, ',', '.') }}</p></div>
        </div>
        <div class="panel-card overflow-hidden">
            <div class="panel-head">
                <h3 class="text-base font-bold text-slate-900">Aktivitas Kasir</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="table-ui audit-log-table">
                    <colgroup>
                        <col class="audit-col-time">
                        <col class="audit-col-user">
                        <col class="audit-col-action">
                        <col class="audit-col-level">
                        <col class="audit-col-context">
                        <col class="audit-col-ip">
                        <col class="audit-col-detail">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Kasir</th>
                            <th>Aksi</th>
                            <th>Level</th>
                            <th>Context</th>
                            <th>IP</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($logs->count() > 0)
                            @foreach($logs as $log)
                            @php
                                $ctx = (array) ($log->context ?? []);
                                $exportCount = (int) ($ctx['selected_count'] ?? 0);
                                $exportTotal = (float) ($ctx['selected_total'] ?? 0);
                                $isExportAction = in_array((string) $log->action, ['report_export_excel', 'report_export_pdf'], true);
                                if ($isExportAction) {
                                    if ($exportCount >= 300 || $exportTotal >= 100000000) {
                                        $severity = 'critical';
                                    } elseif ($exportCount >= 150 || $exportTotal >= 50000000) {
                                        $severity = 'warning';
                                    } else {
                                        $severity = 'info';
                                    }
                                } else {
                                    $severity = match($log->action) {
                                        'checkout_failed_client', 'stock_sync_adjusted' => 'warning',
                                        'idle_warning' => 'critical',
                                        default => 'info',
                                    };
                                }
                            @endphp
                            <tr>
                                <td>{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $log->user?->name ?? '-' }}</td>
                                <td><span class="audit-action-chip {{ $log->action === 'customer_merged' ? 'audit-action-chip--merge' : '' }}">{{ $actionLabels[$log->action] ?? $log->action }}</span></td>
                                <td><span class="audit-severity audit-severity--{{ $severity }}">{{ strtoupper($severity) }}</span></td>
                                <td class="audit-context">{{ $humanContext($log) }}</td>
                                <td>{{ $log->ip_address ?? '-' }}</td>
                                <td>
                                    @if($isExportAction)
                                        <div class="mb-2">
                                            <span class="audit-severity audit-severity--{{ $severity }}">
                                                Risiko Export: {{ strtoupper($severity) }}
                                            </span>
                                        </div>
                                    @endif
                                    <details class="audit-detail">
                                        <summary>Lihat</summary>
                                        <div class="audit-detail-body">
                                            <div><strong>User Agent:</strong> {{ $log->user_agent ?? '-' }}</div>
                                            <div><strong>Context Mentah:</strong></div>
                                            <pre>{{ json_encode($log->context ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="text-center text-slate-500 py-8">Belum ada data audit log.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</x-app-layout>

