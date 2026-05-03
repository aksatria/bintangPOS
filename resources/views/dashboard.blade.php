<x-app-layout>
    @php
        $idrFull = fn ($v) => 'Rp ' . number_format($v, 0, ',', '.');
        $baseQuery = request()->query();
        $canReportsView = auth()->user()?->hasPermission('reports.view') ?? false;
        $canReportsExport = auth()->user()?->hasPermission('reports.export') ?? false;
        $canNotificationSettings = auth()->user()?->hasPermission('settings.notification.manage') ?? false;
        $pctChange = function ($current, $previous) {
            if ((float) $previous === 0.0) {
                return null;
            }

            return (($current - $previous) / abs($previous)) * 100;
        };
    @endphp

    <x-slot name="header">
        <div class="db-head-wrap db-sticky-actions intro-y">
            <div>
                <p class="db-head-kicker">Ringkasan Operasional</p>
                <h2 class="db-head-title">Dashboard Penjualan</h2>
            </div>
            <div class="db-quick-actions">
                <a href="{{ route('dashboard', array_merge($baseQuery, ['compact' => $compactMode ? 0 : 1])) }}" class="db-quick-btn">
                    <i data-feather="layout" class="w-4 h-4"></i>
                    <span>{{ $compactMode ? 'Mode Normal' : 'Mode Compact' }}</span>
                </a>
                <a href="{{ route('dashboard', array_merge($baseQuery, ['auto_refresh' => $autoRefresh ? 0 : 1])) }}" class="db-quick-btn">
                    <i data-feather="refresh-cw" class="w-4 h-4"></i>
                    <span>{{ $autoRefresh ? 'Auto Refresh: ON' : 'Auto Refresh: OFF' }}</span>
                </a>
                <a href="{{ route('pos.index') }}" class="db-quick-btn db-quick-btn--primary">
                    <i data-feather="shopping-cart" class="w-4 h-4"></i>
                    <span>Buka POS</span>
                </a>
                @if($isManager && $canReportsView)
                    <a href="{{ route('reports.index') }}" class="db-quick-btn">
                        <i data-feather="bar-chart-2" class="w-4 h-4"></i>
                        <span>Lihat Report</span>
                    </a>
                @endif
                @if($isManager && $canNotificationSettings)
                    <a href="{{ route('notification-settings.edit') }}" class="db-quick-btn">
                        <i data-feather="send" class="w-4 h-4"></i>
                        <span>Notif Telegram</span>
                    </a>
                @endif
                @if($isManager)
                    <a href="{{ route('dashboard.export.pdf', ['preset' => $selectedPreset, 'start_date' => $startDate, 'end_date' => $endDate, 'compact' => $compactMode ? 1 : 0, 'auto_refresh' => $autoRefresh ? 1 : 0]) }}" class="db-quick-btn">
                        <i data-feather="file-text" class="w-4 h-4"></i>
                        <span>Snapshot PDF</span>
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="db-shell {{ $compactMode ? 'db-compact' : '' }}">
        @if($isManager)
            <div id="db-audit-toast" class="db-audit-toast hidden"></div>
        @endif
        <div class="db-kpi-grid">
            <a href="{{ ($isManager && $canReportsView) ? route('reports.index', ['period' => 'custom', 'start_date' => $startDate, 'end_date' => $endDate]) : route('pos.index') }}" class="db-kpi-card db-kpi-card--positive intro-y">
                <div class="db-kpi-top">
                    <span class="db-kpi-chip">Omzet</span>
                    <i data-feather="shopping-cart" class="db-kpi-icon"></i>
                </div>
                <div class="db-kpi-value db-tooltip" data-tooltip="{{ $idrFull($omzetToday) }}">{{ $idrFull($omzetToday) }}</div>
                <div class="db-kpi-label">Omzet Hari Ini</div>
                <div class="db-kpi-note">Penjualan paid tanggal hari ini</div>
                <div class="db-kpi-compare {{ ($kpiComparisons['omzetTodayPct'] ?? 0) >= 0 ? 'db-insight-up' : 'db-insight-down' }}">
                    @if($kpiComparisons['omzetTodayPct'] === null)
                        vs kemarin: N/A
                    @else
                        vs kemarin: {{ ($kpiComparisons['omzetTodayPct'] >= 0 ? '+' : '-') . number_format(abs($kpiComparisons['omzetTodayPct']), 1, ',', '.') }}%
                    @endif
                </div>
            </a>

            <a href="{{ ($isManager && $canReportsView) ? route('reports.index', ['period' => 'custom', 'start_date' => $startDate, 'end_date' => $endDate]) : route('pos.index') }}" class="db-kpi-card db-kpi-card--neutral intro-y">
                <div class="db-kpi-top">
                    <span class="db-kpi-chip">Transaksi</span>
                    <i data-feather="credit-card" class="db-kpi-icon"></i>
                </div>
                <div class="db-kpi-value">{{ number_format($transactionsToday, 0, ',', '.') }}</div>
                <div class="db-kpi-label">Jumlah Transaksi</div>
                <div class="db-kpi-note">Total transaksi paid hari ini</div>
                <div class="db-kpi-compare {{ ($kpiComparisons['transactionsTodayPct'] ?? 0) >= 0 ? 'db-insight-up' : 'db-insight-down' }}">
                    @if($kpiComparisons['transactionsTodayPct'] === null)
                        vs kemarin: N/A
                    @else
                        vs kemarin: {{ ($kpiComparisons['transactionsTodayPct'] >= 0 ? '+' : '-') . number_format(abs($kpiComparisons['transactionsTodayPct']), 1, ',', '.') }}%
                    @endif
                </div>
            </a>

            <a href="{{ route('dashboard', array_merge($baseQuery, ['compact' => $compactMode ? 1 : 0, 'auto_refresh' => $autoRefresh ? 1 : 0])) }}#stok-habis" class="db-kpi-card db-kpi-card--danger intro-y">
                <div class="db-kpi-top">
                    <span class="db-kpi-chip">Stok Habis</span>
                    <i data-feather="alert-octagon" class="db-kpi-icon"></i>
                </div>
                <div class="db-kpi-value">{{ number_format($outOfStockCount, 0, ',', '.') }}</div>
                <div class="db-kpi-label">Produk Stok Habis</div>
                <div class="db-kpi-note">Produk aktif dengan stok 0 atau kurang</div>
            </a>

            <a href="{{ route('dashboard', array_merge($baseQuery, ['compact' => $compactMode ? 1 : 0, 'auto_refresh' => $autoRefresh ? 1 : 0])) }}#stok-menipis" class="db-kpi-card db-kpi-card--primary intro-y">
                <div class="db-kpi-top">
                    <span class="db-kpi-chip">Aset Stok</span>
                    <i data-feather="archive" class="db-kpi-icon"></i>
                </div>
                <div class="db-kpi-value db-tooltip" data-tooltip="{{ $idrFull($inventoryAssetValue) }}">{{ $idrFull($inventoryAssetValue) }}</div>
                <div class="db-kpi-label">Nilai Aset Stok</div>
                <div class="db-kpi-note">Estimasi nilai modal persediaan aktif</div>
            </a>
        </div>

        @if($isManager)
        <div class="db-panel db-sticky-filter mt-6 intro-y">
            <div class="db-panel-head">
                <div class="db-panel-head-main">
                    <h3 class="db-panel-title">Ringkasan Profit Periode</h3>
                    <p class="db-panel-subtitle">Omzet, HPP, pengeluaran, laba, dan jumlah transaksi untuk periode aktif.</p>
                    <div class="db-period-info mt-2">
                        <span class="db-period-badge">{{ $presetLabel }}</span>
                        <span class="db-period-text">Periode Aktif: {{ $periodText }} ({{ $periodDays }} hari)</span>
                        <span class="db-period-text">Update: {{ $lastUpdatedText }}</span>
                    </div>
                </div>
                <div class="db-panel-head-side">
                    <form method="GET" action="{{ route('dashboard') }}" class="db-filter-form">
                        <input type="hidden" name="compact" value="{{ $compactMode ? 1 : 0 }}">
                        <input type="hidden" name="auto_refresh" value="{{ $autoRefresh ? 1 : 0 }}">
                        <select name="preset" class="db-filter-input">
                            <option value="hari_ini" @selected($selectedPreset === 'hari_ini')>Hari Ini</option>
                            <option value="7_hari" @selected($selectedPreset === '7_hari')>7 Hari</option>
                            <option value="30_hari" @selected($selectedPreset === '30_hari')>30 Hari</option>
                            <option value="custom" @selected($selectedPreset === 'custom')>Custom</option>
                        </select>
                        <input type="date" name="start_date" value="{{ $startDate }}" class="db-filter-input">
                        <input type="date" name="end_date" value="{{ $endDate }}" class="db-filter-input">
                        <button type="submit" class="db-reload-btn db-action-btn db-action-btn--apply">
                            <i data-feather="filter" class="w-4 h-4 db-action-icon db-action-icon--apply"></i>
                            <span>Terapkan</span>
                        </button>
                        <a href="{{ route('dashboard', ['compact' => $compactMode ? 1 : 0, 'auto_refresh' => $autoRefresh ? 1 : 0]) }}" class="db-reload-btn db-action-btn db-action-btn--reset">
                            <i data-feather="refresh-ccw" class="w-4 h-4 db-action-icon db-action-icon--reset"></i>
                            <span>Reset</span>
                        </a>
                    </form>
                    <div class="db-export-actions">
                        @if($canNotificationSettings)
                        <button type="button" id="db-telegram-test-btn" class="db-reload-btn db-export-btn db-export-btn--telegram">
                            <i data-feather="send" class="w-4 h-4 db-action-icon db-action-icon--telegram"></i>
                            <span class="db-telegram-test-label">Tes Telegram</span>
                        </button>
                        @endif
                        @if($canReportsExport)
                        <a href="{{ route('reports.export.excel', ['period' => 'custom', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="db-reload-btn db-export-btn db-export-btn--excel">
                            <i data-feather="download" class="w-4 h-4 db-action-icon db-action-icon--excel"></i>
                            <span>Export Excel</span>
                        </a>
                        <a href="{{ route('reports.export.pdf', ['period' => 'custom', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="db-reload-btn db-export-btn db-export-btn--pdf">
                            <i data-feather="file-text" class="w-4 h-4 db-action-icon db-action-icon--pdf"></i>
                            <span>Export PDF</span>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="db-panel-body db-profit-body">
                <div class="db-metric-grid db-profit-grid">
                    <div class="db-metric-item db-profit-item">
                        <div class="db-metric-name db-tooltip" data-tooltip="Persentase realisasi omzet dibanding target periode.">Target vs Realisasi</div>
                        <div class="db-metric-value">{{ number_format($targetAchievementPct, 1, ',', '.') }}%</div>
                        <div class="db-kpi-note">Target periode: {{ $idrFull($periodTarget) }}</div>
                        <div class="db-target-status db-target-status--{{ $targetStatus }}">{{ $targetStatusLabel }}</div>
                        <div class="db-progress mt-2">
                            <div class="db-progress-bar" style="width: {{ min(100, max(0, $targetAchievementPct)) }}%;"></div>
                        </div>
                    </div>
                    <div class="db-metric-item db-profit-item">
                        <div class="db-metric-name db-tooltip" data-tooltip="Total nilai penjualan transaksi paid pada periode aktif.">Omzet Periode</div>
                        <div class="db-metric-value db-tooltip" data-tooltip="{{ $idrFull($omzetRange) }}">{{ $idrFull($omzetRange) }}</div>
                    </div>
                    <div class="db-metric-item db-profit-item">
                        <div class="db-metric-name db-tooltip" data-tooltip="Harga pokok penjualan: total modal dari barang yang terjual.">Total HPP</div>
                        <div class="db-metric-value db-tooltip" data-tooltip="{{ $idrFull($hppRange) }}">{{ $idrFull($hppRange) }}</div>
                    </div>
                    <div class="db-metric-item db-profit-item">
                        <div class="db-metric-name db-tooltip" data-tooltip="Total biaya operasional yang tercatat pada periode aktif.">Pengeluaran</div>
                        <div class="db-metric-value db-tooltip" data-tooltip="{{ $idrFull($expensesRange) }}">{{ $idrFull($expensesRange) }}</div>
                    </div>
                    <div class="db-metric-item db-metric-item--good db-profit-item">
                        <div class="db-metric-name db-tooltip" data-tooltip="Laba Kotor = Omzet Periode - Total HPP.">Laba Kotor</div>
                        <div class="db-metric-value db-tooltip" data-tooltip="{{ $idrFull($grossProfitRange) }}">{{ $idrFull($grossProfitRange) }}</div>
                    </div>
                    <div class="db-metric-item db-metric-item--good db-profit-item">
                        <div class="db-metric-name db-tooltip" data-tooltip="Laba Bersih = Laba Kotor - Pengeluaran.">Laba Bersih</div>
                        <div class="db-metric-value db-tooltip" data-tooltip="{{ $idrFull($netProfitRange) }}">{{ $idrFull($netProfitRange) }}</div>
                    </div>
                    <div class="db-metric-item db-profit-item">
                        <div class="db-metric-name db-tooltip" data-tooltip="Jumlah transaksi dengan status paid pada periode aktif.">Transaksi Paid</div>
                        <div class="db-metric-value">{{ number_format($transactionsRange, 0, ',', '.') }}</div>
                    </div>
                    </div>
            </div>
        </div>

        <div class="db-panel mt-6 intro-y">
            <div class="db-panel-head">
                <div>
                    <h3 class="db-panel-title">Ringkasan Pengeluaran</h3>
                    <p class="db-panel-subtitle">Monitoring biaya operasional terbaru agar kontrol cashflow lebih cepat.</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.expenses.export.csv', ['from' => $startDate, 'to' => $endDate]) }}" class="db-reload-btn">Export CSV</a>
                    <a href="{{ route('admin.expenses.index') }}" class="db-reload-btn">Kelola Pengeluaran</a>
                </div>
            </div>
            <div class="db-panel-body">
                @if($expenseOverBudgetDaily || $expenseOverBudgetMonthly)
                    <div class="mb-4 rounded-xl px-4 py-3" style="border:1px solid #fca5a5;background:linear-gradient(135deg,#fff1f2 0%,#ffedd5 100%);">
                        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.25rem;">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:1.5rem;height:1.5rem;border-radius:999px;background:#dc2626;color:#fff;font-weight:700;">!</span>
                            <div style="font-weight:700;color:#9f1239;">Alert Budget Pengeluaran</div>
                        </div>
                        @if($expenseOverBudgetDaily)
                            <div style="font-size:.92rem;color:#9f1239;">Hari ini melewati budget: <strong>{{ $idrFull($expensesToday) }}</strong> / {{ $idrFull($expenseDailyBudget) }}</div>
                        @endif
                        @if($expenseOverBudgetMonthly)
                            <div style="font-size:.92rem;color:#9f1239;">Bulan ini melewati budget: <strong>{{ $idrFull($expensesThisMonth) }}</strong> / {{ $idrFull($expenseMonthlyBudget) }}</div>
                        @endif
                    </div>
                @else
                    <div class="mb-4 rounded-xl px-4 py-3" style="border:1px solid #86efac;background:#f0fdf4;">
                        <div style="font-size:.9rem;color:#166534;">
                            Budget pengeluaran aman. Hari ini: <strong>{{ $idrFull($expensesToday) }}</strong> dari batas {{ $idrFull($expenseDailyBudget) }}.
                        </div>
                    </div>
                @endif

                <div class="db-metric-grid db-profit-grid">
                    <div class="db-metric-item db-profit-item">
                        <div class="db-metric-name">Hari Ini</div>
                        <div class="db-metric-value db-tooltip" data-tooltip="{{ $idrFull($expensesToday) }}">{{ $idrFull($expensesToday) }}</div>
                    </div>
                    <div class="db-metric-item db-profit-item">
                        <div class="db-metric-name">7 Hari</div>
                        <div class="db-metric-value db-tooltip" data-tooltip="{{ $idrFull($expensesLast7Days) }}">{{ $idrFull($expensesLast7Days) }}</div>
                    </div>
                    <div class="db-metric-item db-profit-item">
                        <div class="db-metric-name">Bulan Ini</div>
                        <div class="db-metric-value db-tooltip" data-tooltip="{{ $idrFull($expensesThisMonth) }}">{{ $idrFull($expensesThisMonth) }}</div>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <h4 class="text-sm font-semibold text-slate-800">Tren Pengeluaran</h4>
                        <p class="text-xs text-slate-500 mb-3">Periode: {{ $periodText }}</p>
                        <div class="report-chart" style="height: 220px;">
                            <canvas id="expense-trend-chart"></canvas>
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <h4 class="text-sm font-semibold text-slate-800">Top Kategori Pengeluaran</h4>
                        <p class="text-xs text-slate-500 mb-3">Kategori terbesar pada periode aktif</p>
                        @forelse($expenseTopCategories as $cat)
                            <div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-b-0">
                                <div>
                                    <div class="text-sm font-medium text-slate-800">{{ $cat->category ?: 'Lainnya' }}</div>
                                    <div class="text-xs text-slate-500">{{ number_format((float) ($cat->pct ?? 0), 1, ',', '.') }}%</div>
                                </div>
                                <div class="text-sm font-semibold text-slate-900">{{ $idrFull((float) $cat->total) }}</div>
                            </div>
                        @empty
                            <div class="text-sm text-slate-500">Belum ada kategori pengeluaran di periode ini.</div>
                        @endforelse
                    </div>
                </div>

                <div class="mt-4 rounded-xl border border-slate-200 p-4">
                    <h4 class="text-sm font-semibold text-slate-800">Audit Aksi Pengeluaran</h4>
                    <p class="text-xs text-slate-500 mb-3">Ringkasan siapa mengubah apa untuk pengeluaran.</p>
                    @forelse($expenseAuditRows as $log)
                        <div class="py-2 border-b border-slate-100 last:border-b-0 flex items-center justify-between gap-3">
                            <div>
                                <div class="text-sm text-slate-800">{{ $log->label }}</div>
                                <div class="text-xs text-slate-500">{{ $log->user?->name ?: '-' }} • {{ optional($log->created_at)->format('d/m/Y H:i') }}</div>
                            </div>
                            <a href="{{ route('audit-logs.index', ['action' => $log->action, 'from' => $startDate, 'to' => $endDate]) }}" class="text-xs text-blue-700 hover:underline">Lihat</a>
                        </div>
                    @empty
                        <div class="text-sm text-slate-500">Belum ada aksi pengeluaran pada periode ini.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="db-panel mt-6 intro-y">
            <div class="db-panel-head">
                <div>
                    <h3 class="db-panel-title">Insight Periode</h3>
                    <p class="db-panel-subtitle">Perbandingan dengan periode sebelumnya: {{ $previousPeriodText }}.</p>
                </div>
            </div>
            <div class="db-panel-body">
                <div class="db-insight-grid">
                    @foreach($insights as $insight)
                        @php
                            $delta = $insight['current'] - $insight['previous'];
                            $pct = $pctChange($insight['current'], $insight['previous']);
                            $isUp = $delta >= 0;
                            $deltaLabel = $insight['unit'] === 'currency'
                                ? $idrFull(abs($delta))
                                : number_format(abs($delta), 0, ',', '.');
                        @endphp
                        <div class="db-insight-item">
                            <div class="db-insight-title">{{ $insight['title'] }}</div>
                            <div class="db-insight-row">
                                <span class="db-insight-trend {{ $isUp ? 'db-insight-up' : 'db-insight-down' }}"><span class="db-insight-arrow">{{ $isUp ? '+' : '-' }}</span>{{ $isUp ? 'Naik' : 'Turun' }} {{ $deltaLabel }}</span>
                                <span class="db-insight-pct {{ $isUp ? 'db-insight-up' : 'db-insight-down' }}">@if($pct === null) N/A @else {{ number_format(abs($pct), 1, ',', '.') }}% @endif</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <div class="db-status-legend db-sticky-legend intro-y mt-6">
            <span class="db-legend-title">Legenda Status:</span>
            <span class="db-legend-item"><span class="db-legend-dot db-legend-dot--danger"></span> Stok Habis</span>
            <span class="db-legend-item"><span class="db-legend-dot db-legend-dot--warning"></span> Stok Menipis</span>
            <span class="db-legend-item"><span class="db-legend-dot db-legend-dot--primary"></span> Produk Terlaris</span>
        </div>

        @if($isManager)
        <div class="db-panel mt-6 intro-y">
            <div class="db-panel-head">
                <div>
                    <h3 class="db-panel-title">Audit Aktivitas Kasir</h3>
                    <p class="db-panel-subtitle">Ringkasan log hari ini dan pemantauan anomali checkout gagal.</p>
                </div>
                <div>
                    <a href="{{ route('audit-logs.index') }}" class="db-reload-btn">Buka Audit Log</a>
                </div>
            </div>
            <div class="db-panel-body">
                @if($checkoutFailAnomaly)
                    <div id="db-audit-alert" class="db-audit-alert db-audit-alert--critical">
                        <span id="db-audit-alert-text">Anomali: checkout gagal <span id="db-audit-count">{{ $checkoutFailLastHour }}</span> kali dalam 1 jam terakhir (ambang: <span id="db-audit-threshold">{{ $checkoutFailThreshold }}</span>).</span>
                        <button id="db-audit-ack-btn" type="button" class="db-audit-ack-btn {{ $checkoutFailAcknowledged ? 'db-audit-ack-btn--done' : '' }}">
                            {{ $checkoutFailAcknowledged ? 'Sudah ditindak' : 'Tandai Sudah Ditindak' }}
                        </button>
                    </div>
                @else
                    <div id="db-audit-alert" class="db-audit-alert">
                        <span id="db-audit-alert-text">Checkout gagal 1 jam terakhir: <span id="db-audit-count">{{ $checkoutFailLastHour }}</span> (ambang anomali: <span id="db-audit-threshold">{{ $checkoutFailThreshold }}</span>).</span>
                        <button id="db-audit-ack-btn" type="button" class="db-audit-ack-btn hidden">Tandai Sudah Ditindak</button>
                    </div>
                @endif
                <div class="db-audit-grid mt-3">
                    <a href="{{ route('audit-logs.index', ['from' => now()->toDateString(), 'to' => now()->toDateString()]) }}" class="db-audit-card db-audit-card--info">
                        <div class="db-audit-card-label">Info Hari Ini</div>
                        <div id="db-audit-info" class="db-audit-card-value">{{ number_format($auditSummary['today_info'] ?? 0, 0, ',', '.') }}</div>
                    </a>
                    <a href="{{ route('audit-logs.index', ['from' => now()->toDateString(), 'to' => now()->toDateString(), 'action' => 'checkout_failed_client']) }}" class="db-audit-card db-audit-card--warning">
                        <div class="db-audit-card-label">Warning Hari Ini</div>
                        <div id="db-audit-warning" class="db-audit-card-value">{{ number_format($auditSummary['today_warning'] ?? 0, 0, ',', '.') }}</div>
                    </a>
                    <a href="{{ route('audit-logs.index', ['from' => now()->toDateString(), 'to' => now()->toDateString(), 'action' => 'idle_warning']) }}" class="db-audit-card db-audit-card--critical">
                        <div class="db-audit-card-label">Critical Hari Ini</div>
                        <div id="db-audit-critical" class="db-audit-card-value">{{ number_format($auditSummary['today_critical'] ?? 0, 0, ',', '.') }}</div>
                    </a>
                </div>
            </div>
        </div>

        <div class="db-panel mt-6 intro-y">
            <div class="db-panel-head">
                <div>
                    <h3 class="db-panel-title">Performa Follow-up Hari Ini</h3>
                    <p class="db-panel-subtitle">Pantau antrian follow-up, penyelesaian, overdue, dan konversi ke transaksi paid.</p>
                </div>
                <div>
                    <a href="{{ route('customers.followups') }}" class="db-reload-btn">Buka Antrian Follow-up</a>
                </div>
            </div>
            <div class="db-panel-body">
                <div class="db-audit-grid">
                    <a href="{{ route('customers.followups', ['date' => now()->toDateString()]) }}" class="db-audit-card db-audit-card--info">
                        <div class="db-audit-card-label">Follow-up Hari Ini</div>
                        <div class="db-audit-card-value">{{ number_format((int) ($followupSummary['today_total'] ?? 0), 0, ',', '.') }}</div>
                    </a>
                    <a href="{{ route('customers.followups', ['date' => now()->toDateString(), 'status' => 'selesai']) }}" class="db-audit-card db-audit-card--warning">
                        <div class="db-audit-card-label">Selesai</div>
                        <div class="db-audit-card-value">{{ number_format((int) ($followupSummary['today_done'] ?? 0), 0, ',', '.') }}</div>
                    </a>
                    <a href="{{ route('customers.followups', ['date' => now()->toDateString()]) }}" class="db-audit-card db-audit-card--critical">
                        <div class="db-audit-card-label">Overdue</div>
                        <div class="db-audit-card-value">{{ number_format((int) ($followupSummary['today_overdue'] ?? 0), 0, ',', '.') }}</div>
                    </a>
                </div>
                <div class="mt-3 text-xs text-slate-600">
                    Konversi follow-up ke transaksi paid hari ini: <strong>{{ number_format((int) ($followupSummary['today_conversion'] ?? 0), 0, ',', '.') }}</strong> customer.
                </div>
            </div>
        </div>

        <div class="db-panel mt-6 intro-y">
            <div class="db-panel-head">
                <div>
                    <h3 class="db-panel-title">Rekonsiliasi Kas Shift</h3>
                    <p class="db-panel-subtitle">Pantau kas expected vs aktual dari kasir hari ini.</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('pos.index') }}" class="db-reload-btn">Input Rekonsiliasi</a>
                    <a href="{{ route('sales.pending-attempts') }}" class="db-reload-btn">Histori Attempt Pending</a>
                </div>
            </div>
            <div class="db-panel-body">
                <div class="db-audit-grid">
                    <div class="db-audit-card db-audit-card--info">
                        <div class="db-audit-card-label">Kasir Sudah Rekonsiliasi</div>
                        <div class="db-audit-card-value">{{ number_format((int) ($cashReconSummary['today_reconciled'] ?? 0), 0, ',', '.') }}</div>
                    </div>
                    <div class="db-audit-card db-audit-card--warning">
                        <div class="db-audit-card-label">Selisih Lebih (+)</div>
                        <div class="db-audit-card-value">{{ $idrFull((float) ($cashReconSummary['today_diff_plus'] ?? 0)) }}</div>
                    </div>
                    <div class="db-audit-card db-audit-card--critical">
                        <div class="db-audit-card-label">Selisih Kurang (-)</div>
                        <div class="db-audit-card-value">{{ $idrFull((float) ($cashReconSummary['today_diff_minus'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="mt-3 text-xs text-slate-600">
                    Kasir belum rekonsiliasi hari ini: <strong>{{ number_format((int) ($cashReconSummary['today_unreconciled_cashier'] ?? 0), 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>
        @endif

        @if($isManager)
        <div class="db-panel mt-6 intro-y">
            <div class="db-panel-head">
                <div>
                    <h3 class="db-panel-title">KPI Mutasi Per Cabang (30 Hari)</h3>
                    <p class="db-panel-subtitle">Aging mutasi, SLA approve/receive, dan discrepancy rate per cabang asal.</p>
                </div>
            </div>
            <div class="db-panel-body overflow-x-auto">
                <table class="table-ui w-full">
                    <thead>
                        <tr>
                            <th class="text-left">Cabang Asal</th>
                            <th class="text-left">Total Mutasi</th>
                            <th class="text-left">Overdue</th>
                            <th class="text-left">Avg SLA Approve</th>
                            <th class="text-left">Avg SLA Receive</th>
                            <th class="text-left">Discrepancy Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($branchTransferKpis ?? collect()) as $kpi)
                            @php
                                $th = (array) ($kpi['thresholds'] ?? []);
                                $overdueWarn = (int) ($th['overdue_warning_count'] ?? 3);
                                $approveWarn = (int) ($th['approve_sla_minutes'] ?? 60);
                                $receiveWarn = (int) ($th['receive_sla_minutes'] ?? 180);
                                $discWarn = (float) ($th['discrepancy_warning_pct'] ?? 5);
                                $isOverdueWarn = (int) $kpi['overdue_transfer'] >= $overdueWarn;
                                $isApproveWarn = $kpi['avg_approve_minutes'] !== null && (float) $kpi['avg_approve_minutes'] > $approveWarn;
                                $isReceiveWarn = $kpi['avg_receive_minutes'] !== null && (float) $kpi['avg_receive_minutes'] > $receiveWarn;
                                $isDiscWarn = (float) $kpi['discrepancy_rate'] > $discWarn;
                            @endphp
                            <tr>
                                <td class="font-semibold">{{ $kpi['branch_name'] }}</td>
                                <td>{{ number_format((int) $kpi['total_transfer'], 0, ',', '.') }}</td>
                                <td class="{{ $isOverdueWarn ? 'text-rose-700 font-semibold' : '' }}">{{ number_format((int) $kpi['overdue_transfer'], 0, ',', '.') }}</td>
                                <td class="{{ $isApproveWarn ? 'text-rose-700 font-semibold' : '' }}">{{ $kpi['avg_approve_minutes'] !== null ? number_format((float) $kpi['avg_approve_minutes'], 1, ',', '.') . ' menit' : '-' }}</td>
                                <td class="{{ $isReceiveWarn ? 'text-rose-700 font-semibold' : '' }}">{{ $kpi['avg_receive_minutes'] !== null ? number_format((float) $kpi['avg_receive_minutes'], 1, ',', '.') . ' menit' : '-' }}</td>
                                <td class="{{ $isDiscWarn ? 'text-rose-700 font-semibold' : '' }}">{{ number_format((float) $kpi['discrepancy_rate'], 2, ',', '.') }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-slate-500">Belum ada data mutasi pada 30 hari terakhir.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="db-top-grid mt-6">
            <div class="db-top-main intro-y">
                <div class="db-panel db-panel-equal">
                    <div class="db-panel-head"><div><h3 class="db-panel-title">Sales Trend</h3><p class="db-panel-subtitle">Pergerakan omzet harian selama periode aktif: {{ $periodText }}.</p><p class="db-panel-meta">Update: {{ $lastUpdatedText }}</p></div></div>
                    <div class="db-panel-body db-panel-body-equal">
                        <div class="report-chart"><canvas id="report-line-chart" height="160"></canvas></div>
                        <div class="grid grid-cols-7 gap-2 mt-6 text-center text-xs text-gray-600">@foreach(($chartLabels ?? []) as $label)<div>{{ $label }}</div>@endforeach</div>
                    </div>
                </div>
            </div>

            <div class="db-top-side intro-y">
                <div class="db-panel db-panel-equal">
                    <div class="db-panel-head"><div><h3 class="db-panel-title">Produk Terlaris</h3><p class="db-panel-subtitle">Top produk berdasarkan kuantitas terjual pada periode: {{ $periodText }}.</p><p class="db-panel-meta">Update: {{ $lastUpdatedText }}</p></div></div>
                    <div class="db-panel-body db-panel-body-equal">
                        @forelse($bestSellingProducts as $item)
                            @php
                                $bestProductUrl = route('reports.index', [
                                    'period' => 'custom',
                                    'start_date' => $startDate,
                                    'end_date' => $endDate,
                                    'product' => $item->product_name,
                                ]);
                            @endphp
                            <a href="{{ ($isManager && $canReportsView) ? $bestProductUrl : route('dashboard') }}" class="db-list-row db-list-row-link"><div><div class="font-medium">{{ $item->product_name }}</div><div class="text-gray-600 text-xs">Qty Terjual</div></div><div class="db-stock-pill db-stock-pill--primary">{{ $item->total_qty }}</div></a>
                        @empty
                            <div class="db-empty-state"><div class="db-empty-title">Belum ada data produk terlaris.</div><div class="db-empty-sub">Belum ada transaksi paid pada periode aktif.</div><a href="{{ route('pos.index') }}" class="db-empty-action">Buka POS</a></div>
                        @endforelse
                        <div class="mt-3">
                            <a class="db-empty-action" href="{{ route('dashboard', array_merge($baseQuery, ['all_best' => $showAllBest ? 0 : 1, 'all_low' => $showAllLow ? 1 : 0, 'all_out' => $showAllOut ? 1 : 0])) }}">{{ $showAllBest ? 'Tampilkan Ringkas' : 'Lihat Semua' }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="db-bottom-grid mt-6">
            <div class="db-bottom-item intro-y">
                <div class="db-panel db-panel-equal" id="stok-menipis">
                    <div class="db-panel-head"><div><h3 class="db-panel-title">Produk Stok Menipis</h3><p class="db-panel-subtitle">Produk aktif yang stoknya menyentuh batas minimum.</p><p class="db-panel-meta">Periode acuan: {{ $periodText }} | Update: {{ $lastUpdatedText }}</p></div></div>
                    <div class="db-panel-body db-panel-body-equal">
                        @forelse($lowStockProducts as $product)
                            @php
                                $lowStockUrl = route('reports.index', [
                                    'period' => 'custom',
                                    'start_date' => $startDate,
                                    'end_date' => $endDate,
                                    'product' => $product->sku ?: $product->name,
                                ]);
                            @endphp
                            <a href="{{ ($isManager && $canReportsView) ? $lowStockUrl : route('dashboard') }}" class="db-list-row db-list-row-link"><div><div class="font-medium">{{ $product->name }}</div><div class="text-gray-600 text-xs">SKU: {{ $product->sku }} | Batas: {{ $product->low_stock_threshold }}</div></div><div class="db-stock-pill db-stock-pill--warning">{{ $product->stock }}</div></a>
                        @empty
                            <div class="db-empty-state"><div class="db-empty-title">Tidak ada produk menipis.</div><div class="db-empty-sub">Stok produk aktif masih di atas batas minimum.</div><a href="{{ ($isManager && $canReportsView) ? route('reports.index') : route('dashboard') }}" class="db-empty-action">{{ ($isManager && $canReportsView) ? 'Lihat Laporan' : 'Lihat Dashboard' }}</a></div>
                        @endforelse
                        <div class="mt-3">
                            <a class="db-empty-action" href="{{ route('dashboard', array_merge($baseQuery, ['all_low' => $showAllLow ? 0 : 1, 'all_best' => $showAllBest ? 1 : 0, 'all_out' => $showAllOut ? 1 : 0])) }}">{{ $showAllLow ? 'Tampilkan Ringkas' : 'Lihat Semua' }}</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="db-bottom-item intro-y">
                <div class="db-panel db-panel-equal" id="stok-habis">
                    <div class="db-panel-head"><div><h3 class="db-panel-title">Notifikasi Stok Habis</h3><p class="db-panel-subtitle">Produk aktif dengan stok 0 atau kurang.</p><p class="db-panel-meta">Periode acuan: {{ $periodText }} | Update: {{ $lastUpdatedText }}</p></div></div>
                    <div class="db-panel-body db-panel-body-equal">
                        @forelse($outOfStockProducts as $product)
                            @php
                                $outStockUrl = route('reports.index', [
                                    'period' => 'custom',
                                    'start_date' => $startDate,
                                    'end_date' => $endDate,
                                    'product' => $product->sku ?: $product->name,
                                ]);
                            @endphp
                            <a href="{{ ($isManager && $canReportsView) ? $outStockUrl : route('dashboard') }}" class="db-list-row db-list-row-link"><div><div class="font-medium">{{ $product->name }}</div><div class="text-gray-600 text-xs">SKU: {{ $product->sku }}</div></div><div class="db-stock-pill db-stock-pill--danger">{{ $product->stock }}</div></a>
                        @empty
                            <div class="db-empty-state"><div class="db-empty-title">Tidak ada produk stok habis.</div><div class="db-empty-sub">Semua produk aktif masih memiliki stok.</div><a href="{{ ($isManager && $canReportsView) ? route('reports.index') : route('dashboard') }}" class="db-empty-action">{{ ($isManager && $canReportsView) ? 'Lihat Ringkasan' : 'Lihat Dashboard' }}</a></div>
                        @endforelse
                        <div class="mt-3">
                            <a class="db-empty-action" href="{{ route('dashboard', array_merge($baseQuery, ['all_out' => $showAllOut ? 0 : 1, 'all_best' => $showAllBest ? 1 : 0, 'all_low' => $showAllLow ? 1 : 0])) }}">{{ $showAllOut ? 'Tampilkan Ringkas' : 'Lihat Semua' }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($isManager)
        <div class="db-panel mt-6 intro-y">
            <div class="db-panel-head">
                <div>
                    <h3 class="db-panel-title">Top 5 Transaksi Terbesar</h3>
                    <p class="db-panel-subtitle">Transaksi paid dengan nilai tertinggi pada periode: {{ $periodText }}.</p>
                </div>
            </div>
            <div class="db-panel-body">
                @forelse($topTransactions as $sale)
                    <div class="db-list-row">
                        <div>
                            <div class="font-medium">{{ $sale->invoice_number }}</div>
                            <div class="text-gray-600 text-xs">{{ $sale->sold_at?->format('d/m/Y H:i') }} | {{ $sale->user?->name }}</div>
                        </div>
                        <div class="db-stock-pill db-stock-pill--primary">{{ $idrFull((float) $sale->total_amount) }}</div>
                    </div>
                @empty
                    <div class="db-empty-state">
                        <div class="db-empty-title">Belum ada transaksi pada periode ini.</div>
                    </div>
                @endforelse
            </div>
        </div>
        @endif
    </div>

    @if($autoRefresh)
        <script>
            setTimeout(function () {
                window.location.reload();
            }, {{ $refreshSeconds * 1000 }});
        </script>
    @endif

    <script>
        (function () {
            const expenseTrendLabels = @json($expenseTrendLabels ?? []);
            const expenseTrendData = @json($expenseTrendData ?? []);

            const buildExpenseTrend = () => {
                const expenseEl = document.getElementById('expense-trend-chart');
                if (!expenseEl || expenseTrendLabels.length === 0) return;
                try {
                    new Chart(expenseEl, {
                        type: 'bar',
                        data: {
                            labels: expenseTrendLabels,
                            datasets: [{
                                label: 'Pengeluaran',
                                data: expenseTrendData,
                                backgroundColor: '#93c5fd',
                                borderColor: '#2563eb',
                                borderWidth: 1,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            interaction: { mode: 'nearest', intersect: false },
                            legend: { display: false },
                            plugins: {
                                legend: { display: false },
                                datalabels: { display: false },
                            },
                            scales: {
                                x: { grid: { display: false } },
                                y: {
                                    beginAtZero: true,
                                    ticks: { callback: function (value) { return 'Rp ' + Number(value).toLocaleString('id-ID'); } },
                                },
                                xAxes: [{ gridLines: { display: false } }],
                                yAxes: [{
                                    ticks: {
                                        beginAtZero: true,
                                        callback: function (value) { return 'Rp ' + Number(value).toLocaleString('id-ID'); },
                                    },
                                }],
                            },
                        },
                    });
                } catch (e) {}
            };

            const initExpenseChart = (attempt = 0) => {
                if (typeof Chart === 'undefined') {
                    if (attempt < 40) {
                        setTimeout(function () { initExpenseChart(attempt + 1); }, 150);
                    }
                    return;
                }
                buildExpenseTrend();
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () {
                    initExpenseChart(0);
                });
            } else {
                initExpenseChart(0);
            }
        })();
    </script>

    @if($isManager)
        <script>
            (function () {
                const endpoint = @json(route('dashboard.audit-anomaly-status'));
                const ackEndpoint = @json(route('dashboard.audit-anomaly-ack'));
                const telegramTestEndpoint = @json(route('dashboard.telegram-test'));
                const toast = document.getElementById('db-audit-toast');
                const alertBox = document.getElementById('db-audit-alert');
                const alertText = document.getElementById('db-audit-alert-text');
                const infoEl = document.getElementById('db-audit-info');
                const warningEl = document.getElementById('db-audit-warning');
                const criticalEl = document.getElementById('db-audit-critical');
                const ackBtn = document.getElementById('db-audit-ack-btn');
                const telegramTestBtn = document.getElementById('db-telegram-test-btn');
                const telegramTestLabel = telegramTestBtn?.querySelector('.db-telegram-test-label');
                let prevAnomaly = @json((bool) ($checkoutFailAnomaly ?? false));
                let prevCount = @json((int) ($checkoutFailLastHour ?? 0));

                const showToast = (message, isCritical = false) => {
                    if (!toast) return;
                    toast.textContent = message;
                    toast.classList.remove('hidden', 'db-audit-toast--critical');
                    if (isCritical) toast.classList.add('db-audit-toast--critical');
                    clearTimeout(window.__dbAuditToastTimer);
                    window.__dbAuditToastTimer = setTimeout(() => {
                        toast.classList.add('hidden');
                    }, 4500);
                };

                const poll = async () => {
                    try {
                        const res = await fetch(endpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        if (!res.ok) return;
                        const data = await res.json();
                        const anomaly = Boolean(data.checkoutFailAnomaly);
                        const count = Number(data.checkoutFailLastHour || 0);
                        const threshold = Number(data.checkoutFailThreshold || 0);
                        const todayInfo = Number(data.todayInfo || 0);
                        const todayWarning = Number(data.todayWarning || 0);
                        const todayCritical = Number(data.todayCritical || 0);
                        const acknowledged = Boolean(data.checkoutFailAcknowledged);

                        if (infoEl) infoEl.textContent = String(todayInfo);
                        if (warningEl) warningEl.textContent = String(todayWarning);
                        if (criticalEl) criticalEl.textContent = String(todayCritical);
                        if (alertBox) {
                            if (anomaly) {
                                alertBox.classList.add('db-audit-alert--critical');
                                if (alertText) alertText.textContent = `Anomali: checkout gagal ${count} kali dalam 1 jam terakhir (ambang: ${threshold}).`;
                            } else {
                                alertBox.classList.remove('db-audit-alert--critical');
                                if (alertText) alertText.textContent = `Checkout gagal 1 jam terakhir: ${count} (ambang anomali: ${threshold}).`;
                            }
                        }

                        if (ackBtn) {
                            if (anomaly) {
                                ackBtn.classList.remove('hidden');
                                ackBtn.classList.toggle('db-audit-ack-btn--done', acknowledged);
                                ackBtn.textContent = acknowledged ? 'Sudah ditindak' : 'Tandai Sudah Ditindak';
                            } else {
                                ackBtn.classList.add('hidden');
                            }
                        }

                        if (anomaly && (!prevAnomaly || count > prevCount)) {
                            showToast(`Anomali terdeteksi: checkout gagal ${count} kali dalam 1 jam (ambang ${threshold}).`, true);
                        } else if (!anomaly && prevAnomaly) {
                            showToast('Status anomali checkout kembali normal.');
                        }

                        prevAnomaly = anomaly;
                        prevCount = count;
                    } catch (e) {}
                };

                if (ackBtn) {
                    ackBtn.addEventListener('click', async () => {
                        try {
                            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                            const res = await fetch(ackEndpoint, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': token,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });
                            if (res.ok) {
                                ackBtn.classList.add('db-audit-ack-btn--done');
                                ackBtn.textContent = 'Sudah ditindak';
                                showToast('Alert sudah ditandai ditindak.');
                            }
                        } catch (e) {}
                    });
                }

                if (telegramTestBtn) {
                    telegramTestBtn.addEventListener('click', async () => {
                        telegramTestBtn.disabled = true;
                        telegramTestBtn.classList.add('is-loading');
                        if (telegramTestLabel) telegramTestLabel.textContent = 'Mengirim...';
                        try {
                            const res = await fetch(telegramTestEndpoint, {
                                method: 'GET',
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            });
                            const data = await res.json();
                            if (res.ok && data.ok) {
                                showToast(data.message || 'Pesan tes Telegram berhasil dikirim.');
                            } else {
                                showToast(data.message || 'Gagal mengirim tes Telegram.', true);
                            }
                        } catch (e) {
                            showToast('Gagal mengirim tes Telegram. Periksa koneksi server.', true);
                        } finally {
                            telegramTestBtn.disabled = false;
                            telegramTestBtn.classList.remove('is-loading');
                            if (telegramTestLabel) telegramTestLabel.textContent = 'Tes Telegram';
                        }
                    });
                }

                setInterval(poll, 30000);
            })();
        </script>
    @endif
</x-app-layout>
