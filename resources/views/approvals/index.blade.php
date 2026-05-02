<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <p class="page-kicker">Kontrol & Sistem</p>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Approval Queue</h2>
        </div>
    </x-slot>

    <div class="page-shell space-y-6">
        @if(session('success'))
            <div class="panel-card p-4 border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm flex flex-wrap items-center justify-between gap-3">
                <span>{{ session('success') }}</span>
                @if(session('approval_export_execute_url'))
                    <a href="{{ session('approval_export_execute_url') }}" class="btn-primary text-xs px-3 py-2 inline-flex items-center gap-1.5">
                        <i data-feather="download" class="w-3.5 h-3.5"></i>
                        <span>Eksekusi Export Sekarang</span>
                    </a>
                @endif
            </div>
        @endif
        @if(session('error'))
            <div class="panel-card p-4 border border-rose-200 bg-rose-50 text-rose-700 text-sm">{{ session('error') }}</div>
        @endif

        <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
            <div class="panel-card p-4 border summary-card summary-card--pending">
                <p class="text-xs uppercase tracking-wide text-sky-700 font-semibold">Pending</p>
                <p class="mt-1 text-2xl font-extrabold text-sky-900">{{ number_format((int) data_get($summary ?? [], 'pending', 0), 0, ',', '.') }}</p>
                <p class="text-xs text-sky-700 mt-1">Menunggu review</p>
            </div>
            <div class="panel-card p-4 border summary-card summary-card--overdue">
                <p class="text-xs uppercase tracking-wide text-amber-700 font-semibold">Overdue SLA</p>
                <p class="mt-1 text-2xl font-extrabold text-amber-800">{{ number_format((int) data_get($summary ?? [], 'overdue', 0), 0, ',', '.') }}</p>
                <p class="text-xs text-amber-700 mt-1">Lewat {{ (int) data_get($summary ?? [], 'sla_minutes', 120) }} menit</p>
            </div>
            <div class="panel-card p-4 border summary-card summary-card--approved">
                <p class="text-xs uppercase tracking-wide text-blue-700 font-semibold">Approved Belum Dieksekusi</p>
                <p class="mt-1 text-2xl font-extrabold text-blue-800">{{ number_format((int) data_get($summary ?? [], 'approved_not_executed', 0), 0, ',', '.') }}</p>
                <p class="text-xs text-blue-700 mt-1">Khusus export besar</p>
            </div>
            <div class="panel-card p-4 border summary-card summary-card--avg">
                <p class="text-xs uppercase tracking-wide text-emerald-700 font-semibold">Avg Review (7 Hari)</p>
                <p class="mt-1 text-2xl font-extrabold text-emerald-800">{{ number_format((int) data_get($summary ?? [], 'avg_review_minutes_last_7d', 0), 0, ',', '.') }}m</p>
                <p class="text-xs text-emerald-700 mt-1">Dari create sampai review</p>
            </div>
            <div class="panel-card p-4 border summary-card summary-card--reject">
                <p class="text-xs uppercase tracking-wide text-rose-700 font-semibold">Reject Rate (7 Hari)</p>
                <p class="mt-1 text-2xl font-extrabold text-rose-800">{{ number_format((float) data_get($summary ?? [], 'reject_rate_last_7d', 0), 1, ',', '.') }}%</p>
                <p class="text-xs text-rose-700 mt-1">Semua approval selesai</p>
            </div>
            <div class="panel-card p-4 border summary-card summary-card--expired">
                <p class="text-xs uppercase tracking-wide text-fuchsia-700 font-semibold">Auto Expired (7 Hari)</p>
                <p class="mt-1 text-2xl font-extrabold text-fuchsia-800">{{ number_format((int) data_get($summary ?? [], 'auto_expired_last_7d', 0), 0, ',', '.') }}</p>
                <p class="text-xs text-fuchsia-700 mt-1">Ditolak otomatis sistem</p>
            </div>
        </div>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="panel-card p-3 border summary-card summary-card--aging-1">
                <p class="text-[11px] uppercase tracking-wide text-emerald-700 font-semibold">Aging 0-30m</p>
                <p class="mt-1 text-xl font-extrabold text-emerald-800">{{ number_format((int) data_get($summary ?? [], 'pending_aging_buckets.lt30', 0), 0, ',', '.') }}</p>
            </div>
            <div class="panel-card p-3 border summary-card summary-card--aging-2">
                <p class="text-[11px] uppercase tracking-wide text-amber-700 font-semibold">Aging 30-120m</p>
                <p class="mt-1 text-xl font-extrabold text-amber-800">{{ number_format((int) data_get($summary ?? [], 'pending_aging_buckets.m30_120', 0), 0, ',', '.') }}</p>
            </div>
            <div class="panel-card p-3 border summary-card summary-card--aging-3">
                <p class="text-[11px] uppercase tracking-wide text-rose-700 font-semibold">Aging 120-240m</p>
                <p class="mt-1 text-xl font-extrabold text-rose-800">{{ number_format((int) data_get($summary ?? [], 'pending_aging_buckets.m120_240', 0), 0, ',', '.') }}</p>
            </div>
            <div class="panel-card p-3 border summary-card summary-card--aging-4">
                <p class="text-[11px] uppercase tracking-wide text-fuchsia-700 font-semibold">Aging >240m</p>
                <p class="mt-1 text-xl font-extrabold text-fuchsia-800">{{ number_format((int) data_get($summary ?? [], 'pending_aging_buckets.gt240', 0), 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="panel-card p-4 border border-slate-200">
            <div class="flex items-center justify-between gap-2 mb-3">
                <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Reviewer Workload</p>
                <p class="text-[11px] text-slate-500">Pending / Overdue / Avg 7h</p>
            </div>
            <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                @forelse(($reviewerWorkloads ?? collect()) as $w)
                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-900 truncate">{{ $w['name'] }}</p>
                            <span class="text-[10px] uppercase tracking-wide text-slate-500">{{ strtoupper((string) ($w['role'] ?? '-')) }}</span>
                        </div>
                        <div class="mt-2 grid grid-cols-3 gap-2 text-center">
                            <div class="rounded-lg bg-slate-50 border border-slate-200 py-1.5">
                                <p class="text-[10px] text-slate-500">Pending</p>
                                <p class="text-sm font-bold text-slate-800">{{ number_format((int) ($w['pending'] ?? 0), 0, ',', '.') }}</p>
                            </div>
                            <div class="rounded-lg bg-amber-50 border border-amber-200 py-1.5">
                                <p class="text-[10px] text-amber-700">Overdue</p>
                                <p class="text-sm font-bold text-amber-800">{{ number_format((int) ($w['overdue'] ?? 0), 0, ',', '.') }}</p>
                            </div>
                            <div class="rounded-lg bg-emerald-50 border border-emerald-200 py-1.5">
                                <p class="text-[10px] text-emerald-700">Avg</p>
                                <p class="text-sm font-bold text-emerald-800">{{ number_format((int) ($w['avg_review_minutes'] ?? 0), 0, ',', '.') }}m</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-xs text-slate-500">Belum ada data workload reviewer.</div>
                @endforelse
            </div>
        </div>
        <div class="grid gap-3 lg:grid-cols-2">
            <div class="panel-card p-4 border border-slate-200">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Tren SLA Mingguan</p>
                    <p class="text-[11px] text-slate-500">8 minggu terakhir</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-3">
                    <canvas id="sla-weekly-chart" height="190"></canvas>
                </div>
                <div class="mt-2 grid grid-cols-3 gap-2 text-[11px]">
                    @foreach(collect($slaTrendWeekly ?? [])->take(3) as $t)
                        <div class="rounded bg-slate-50 border border-slate-200 px-2 py-1 text-slate-700">
                            <div class="font-semibold">{{ $t['label'] }}</div>
                            <div>Reviewed: {{ number_format((int) $t['reviewed'], 0, ',', '.') }}</div>
                            <div>Avg: {{ number_format((int) $t['avg_review'], 0, ',', '.') }}m</div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="panel-card p-4 border border-slate-200">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Tren SLA Bulanan</p>
                    <p class="text-[11px] text-slate-500">6 bulan terakhir</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-3">
                    <canvas id="sla-monthly-chart" height="190"></canvas>
                </div>
                <div class="mt-2 grid grid-cols-3 gap-2 text-[11px]">
                    @foreach(collect($slaTrendMonthly ?? [])->take(3) as $t)
                        <div class="rounded bg-slate-50 border border-slate-200 px-2 py-1 text-slate-700">
                            <div class="font-semibold">{{ $t['label'] }}</div>
                            <div>Reviewed: {{ number_format((int) $t['reviewed'], 0, ',', '.') }}</div>
                            <div>Reject: {{ number_format((float) $t['reject_rate'], 1, ',', '.') }}%</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="panel-card p-4 border border-slate-200">
            <div class="flex items-center justify-between gap-2 mb-2">
                <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Last Escalation Run</p>
                <p class="text-[11px] text-slate-500">Scheduler Monitoring</p>
            </div>
            @php($escalationCtx = (array) data_get($lastEscalationRun ?? null, 'context', []))
            <div class="grid gap-2 md:grid-cols-4">
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-[11px] text-slate-500 uppercase tracking-wide">Waktu Run</p>
                    <p class="text-sm font-bold text-slate-800">{{ $lastEscalationRun?->created_at?->format('d/m/Y H:i:s') ?? '-' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-blue-50 px-3 py-2">
                    <p class="text-[11px] text-blue-600 uppercase tracking-wide">Checked</p>
                    <p class="text-sm font-bold text-blue-800">{{ number_format((int) data_get($escalationCtx, 'checked', 0), 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-emerald-50 px-3 py-2">
                    <p class="text-[11px] text-emerald-600 uppercase tracking-wide">Sent</p>
                    <p class="text-sm font-bold text-emerald-800">{{ number_format((int) data_get($escalationCtx, 'sent', 0), 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-amber-50 px-3 py-2">
                    <p class="text-[11px] text-amber-600 uppercase tracking-wide">Cooldown</p>
                    <p class="text-sm font-bold text-amber-800">{{ number_format((int) data_get($escalationCtx, 'cooldown_seconds', 0), 0, ',', '.') }}s</p>
                </div>
            </div>
        </div>

        <form method="GET" class="panel-card p-4">
            <div class="flex flex-wrap gap-3 items-end">
                <div class="min-w-[220px]">
                    <label class="label-ui">Status</label>
                    <select name="status" class="input-ui">
                        <option value="pending" @selected($status==='pending')>Pending</option>
                        <option value="approved" @selected($status==='approved')>Approved</option>
                        <option value="rejected" @selected($status==='rejected')>Rejected</option>
                        <option value="all" @selected($status==='all')>Semua</option>
                    </select>
                </div>
                <div class="min-w-[240px]">
                    <label class="label-ui">Tipe</label>
                    <select name="type" class="input-ui">
                        <option value="all" @selected(($type ?? 'all') === 'all')>Semua Tipe</option>
                        @foreach(($types ?? collect()) as $itemType)
                            <option value="{{ $itemType }}" @selected(($type ?? 'all') === $itemType)>{{ $itemType }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[220px]">
                    <label class="label-ui">Prioritas</label>
                    <select name="priority" class="input-ui">
                        <option value="all" @selected(($priority ?? 'all') === 'all')>Semua Prioritas</option>
                        <option value="critical" @selected(($priority ?? 'all') === 'critical')>Critical</option>
                        <option value="high" @selected(($priority ?? 'all') === 'high')>High</option>
                        <option value="normal" @selected(($priority ?? 'all') === 'normal')>Normal</option>
                        <option value="done" @selected(($priority ?? 'all') === 'done')>Done</option>
                    </select>
                </div>
                <div class="min-w-[240px]">
                    <label class="label-ui">Eksekusi Export</label>
                    <select name="execution" class="input-ui">
                        <option value="all" @selected(($execution ?? 'all') === 'all')>Semua</option>
                        <option value="pending" @selected(($execution ?? 'all') === 'pending')>Belum Dieksekusi</option>
                        <option value="done" @selected(($execution ?? 'all') === 'done')>Sudah Dieksekusi</option>
                    </select>
                </div>
                <div class="min-w-[240px]">
                    <label class="label-ui">Tag Hasil</label>
                    <select name="result_tag" class="input-ui">
                        <option value="all" @selected(($result_tag ?? 'all') === 'all')>Semua Tag</option>
                        <option value="executed" @selected(($result_tag ?? 'all') === 'executed')>Export Executed</option>
                        <option value="not_executed" @selected(($result_tag ?? 'all') === 'not_executed')>Export Belum Dieksekusi</option>
                        <option value="auto_expired" @selected(($result_tag ?? 'all') === 'auto_expired')>Auto Expired</option>
                    </select>
                </div>
                <div class="min-w-[220px]">
                    <label class="label-ui">Urutan Pending</label>
                    <select name="pending_order" class="input-ui">
                        <option value="oldest" @selected(($pending_order ?? 'oldest') === 'oldest')>Terlama Dulu</option>
                        <option value="newest" @selected(($pending_order ?? 'oldest') === 'newest')>Terbaru Dulu</option>
                    </select>
                </div>
                <div class="min-w-[220px]">
                    <label class="label-ui">Assignee</label>
                    <select name="assignee" class="input-ui">
                        <option value="all" @selected(($assignee ?? 'all') === 'all')>Semua</option>
                        <option value="mine" @selected(($assignee ?? 'all') === 'mine')>Milik Saya</option>
                        <option value="unassigned" @selected(($assignee ?? 'all') === 'unassigned')>Belum Di-assign</option>
                    </select>
                </div>
                <label class="inline-flex items-center gap-2 h-10 px-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-700">
                    <input type="checkbox" name="hide_snoozed" value="1" @checked(($hide_snoozed ?? false))>
                    <span>Sembunyikan Yang Snooze</span>
                </label>
                <button class="btn-primary h-10 px-5">Tampilkan</button>
            </div>
        </form>
        <div class="panel-card p-4 border border-slate-200">
            <div class="flex items-center justify-between gap-2 mb-2">
                <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Template Aksi Cepat Reviewer</p>
                <p class="text-[11px] text-slate-500">Isi otomatis note approve/reject/snooze</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach((array) ($actionTemplates ?? []) as $tplKey => $tpl)
                    <button type="button" class="btn-danger-lite text-xs js-approval-template-btn"
                        data-template-key="{{ $tplKey }}"
                        data-approve="{{ (string) data_get($tpl, 'approve_note', '') }}"
                        data-reject="{{ (string) data_get($tpl, 'reject_note', '') }}"
                        data-snooze="{{ (string) data_get($tpl, 'snooze_note', '') }}"
                    >{{ (string) data_get($tpl, 'label', $tplKey) }}</button>
                @endforeach
            </div>
        </div>

        <div class="grid gap-3 lg:grid-cols-2">
            <div class="panel-card p-4 border border-slate-200">
                <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold mb-2">SLA Capacity - Hari (14 hari)</p>
                <div class="grid grid-cols-7 gap-2">
                    @foreach((array) ($slaWeekdayHeat ?? []) as $day => $cell)
                        @php($c=(int) data_get($cell,'count',0))
                        <div class="rounded-lg border px-2 py-2 text-center {{ $c >= 10 ? 'bg-rose-50 border-rose-200' : ($c >= 5 ? 'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-200') }}">
                            <div class="text-[10px] text-slate-600">D{{ $day }}</div>
                            <div class="text-sm font-bold text-slate-800">{{ $c }}</div>
                            <div class="text-[10px] text-slate-500">{{ (int) data_get($cell,'avg',0) }}m</div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="panel-card p-4 border border-slate-200">
                <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold mb-2">SLA Capacity - Jam (14 hari)</p>
                <div class="grid grid-cols-5 gap-2">
                    @foreach((array) ($slaHourHeat ?? []) as $hour => $cell)
                        @php($c=(int) data_get($cell,'count',0))
                        <div class="rounded-lg border px-2 py-2 text-center {{ $c >= 8 ? 'bg-rose-50 border-rose-200' : ($c >= 4 ? 'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-200') }}">
                            <div class="text-[10px] text-slate-600">{{ str_pad((string) $hour,2,'0',STR_PAD_LEFT) }}:00</div>
                            <div class="text-sm font-bold text-slate-800">{{ $c }}</div>
                            <div class="text-[10px] text-slate-500">{{ (int) data_get($cell,'avg',0) }}m</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="panel-card overflow-hidden">
            <form method="POST" id="approval-bulk-form" class="p-4 border-b border-slate-200 bg-slate-50">
                @csrf
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-0 flex-1 basis-full md:basis-auto">
                        <label class="label-ui">Catatan Bulk</label>
                        <input type="text" name="review_note" id="bulk-review-note" class="input-ui" placeholder="Catatan untuk bulk approve/reject">
                    </div>
                    <button type="button" id="bulk-approve-btn" class="btn-primary h-10 px-4 inline-flex items-center gap-2">
                        <i data-feather="check-circle" class="w-4 h-4"></i>
                        <span>Bulk Approve</span>
                    </button>
                    <button type="button" id="bulk-reject-btn" class="btn-void aq-bulk-reject">
                        <i data-feather="x-circle" class="w-4 h-4"></i>
                        <span>Bulk Reject</span>
                    </button>
                    <button type="button" id="bulk-execute-export-btn" class="btn-primary h-10 px-4 inline-flex items-center gap-2">
                        <i data-feather="play-circle" class="w-4 h-4"></i>
                        <span>Bulk Execute Export</span>
                    </button>
                    <span id="bulk-selected-count" class="text-xs text-slate-500 md:ml-auto">0 terpilih</span>
                </div>
            </form>
            <div class="customer-table-wrap customer-table-shell">
                <table class="table-ui customer-table approval-table-compact">
                    <thead>
                        <tr>
                            <th style="width:42px;" class="text-center">
                                <input type="checkbox" id="bulk-select-all">
                            </th>
                            <th style="width:72px;">ID</th>
                            <th>Judul</th>
                            <th style="width:130px;">Peminta</th>
                            <th style="width:120px;">Assignee</th>
                            <th style="width:150px;">Status & SLA</th>
                            <th style="width:96px;">Waktu</th>
                            <th style="width:280px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $rowsList = $rows->items(); ?>
                    <?php if (empty($rowsList)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-slate-500 py-8">Tidak ada approval pada filter ini.</td>
                        </tr>
                    <?php endif; ?>
                    <?php for ($rowIdx = 0; $rowIdx < count($rowsList); $rowIdx++): ?>
                        <?php $row = $rowsList[$rowIdx]; ?>
                        <?php
                            $ageMinutes = (int) $row->created_at?->diffInMinutes(now()) ?: 0;
                            $isSale = in_array((string) $row->type, ['sale.quick_refund', 'sale.quick_void'], true);
                            $isAutoExpired = $row->status === 'rejected' && str_starts_with((string) ($row->review_note ?? ''), '[AUTO-EXPIRED]');
                            $isExportType = str_starts_with((string) $row->type, 'report.export.');
                            $executedAtMain = (string) data_get((array) ($row->payload ?? []), 'export_executed_at', '');
                            $autoAssignedLevel = (int) data_get((array) ($row->payload ?? []), 'sla_escalation_auto_assigned_level', 0);
                            $autoAssignedName = (string) data_get((array) ($row->payload ?? []), 'sla_escalation_auto_assigned_name', '');
                            $escalationLevels = (array) data_get((array) ($row->payload ?? []), 'sla_escalation_levels', []);
                            $escalationLastLevel = (int) data_get((array) ($row->payload ?? []), 'sla_escalation_last_level', 0);
                            $priorityLabel = 'normal';
                            $saleSla = max(5, (int) data_get($summary ?? [], 'sla_minutes_sale', 120));
                            $exportSla = max(5, (int) data_get($summary ?? [], 'sla_minutes_export', 360));
                            $baseSla = $isSale ? $saleSla : ($isExportType ? $exportSla : 120);
                            if ($row->status !== 'pending') {
                                $priorityLabel = 'done';
                            } elseif ($isSale && $ageMinutes >= ($saleSla * 2)) {
                                $priorityLabel = 'critical';
                            } elseif ($isSale && $ageMinutes >= $saleSla) {
                                $priorityLabel = 'high';
                            } elseif ($isExportType && $ageMinutes >= $exportSla) {
                                $priorityLabel = 'high';
                            }
                            $slaText = $row->status === 'pending'
                                ? ($ageMinutes >= 60 ? floor($ageMinutes / 60).'j '.$ageMinutes % 60 .'m' : $ageMinutes.'m')
                                : '-';
                            $slaState = 'ok';
                            if ($row->status === 'pending' && $ageMinutes >= ($baseSla * 2)) {
                                $slaState = 'critical';
                            } elseif ($row->status === 'pending' && $ageMinutes >= $baseSla) {
                                $slaState = 'breach';
                            } elseif ($row->status === 'pending' && $ageMinutes >= max(5, (int) floor($baseSla / 2))) {
                                $slaState = 'warn';
                            }
                        ?>
                        <tr class="customer-row
                            {{ $row->status === 'pending' && $slaState === 'critical' ? 'approval-row-critical' : '' }}
                            {{ $row->status === 'pending' && $slaState === 'breach' ? 'approval-row-breach' : '' }}
                            {{ $row->status === 'pending' && $slaState === 'warn' ? 'approval-row-warn' : '' }}
                            {{ ($rowIdx + 1) === count($rowsList) ? 'approval-row-last' : '' }}">
                            <td class="text-center align-middle approval-cell-check">
                                <span class="approval-head-left">
                                    @if($row->status === 'pending')
                                        <input type="checkbox" class="bulk-approval-checkbox" value="{{ $row->id }}">
                                    @endif
                                    <span class="approval-id-inline">#{{ $row->id }}</span>
                                </span>
                                <?php
                                    $typeMeta = match((string) $row->type) {
                                        'sale.quick_refund' => ['icon' => 'rotate-ccw', 'label' => 'Refund Cepat'],
                                        'sale.quick_void' => ['icon' => 'slash', 'label' => 'Void Cepat'],
                                        'report.export.pdf' => ['icon' => 'file-text', 'label' => 'Export PDF Besar'],
                                        'report.export.excel' => ['icon' => 'file', 'label' => 'Export Excel Besar'],
                                        default => ['icon' => 'clipboard', 'label' => 'Approval Umum'],
                                    };
                                ?>
                                <span class="approval-type-inline approval-type-inline--right {{ (string) $row->type === 'sale.quick_refund' ? 'approval-type-inline--danger' : '' }}">
                                    <i data-feather="{{ $typeMeta['icon'] }}" class="w-3.5 h-3.5"></i>
                                    <span>{{ $typeMeta['label'] }}</span>
                                </span>
                            </td>
                            <td class="whitespace-nowrap font-semibold text-slate-700 approval-cell-id"></td>
                            <td>
                                <div class="font-semibold text-slate-900">{{ $row->title }}</div>
                                <div class="text-slate-500 text-xs">{{ $row->reason ?: '-' }}</div>
                                @if($autoAssignedLevel >= 2)
                                    <div class="mt-1">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 text-indigo-700 px-2 py-0.5 text-[10px] font-semibold">
                                            <i data-feather="shuffle" class="w-3 h-3"></i>
                                            <span>Auto-assigned (L{{ $autoAssignedLevel }}){{ $autoAssignedName !== '' ? ' · '.$autoAssignedName : '' }}</span>
                                        </span>
                                    </div>
                                @endif
                                @if(!empty($escalationLevels))
                                    <div class="mt-1 flex flex-wrap gap-1.5">
                                        @foreach([1,2,3] as $levelNo)
                                            @if(!empty($escalationLevels[(string) $levelNo]))
                                                <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 px-2 py-0.5 text-[10px] font-semibold">
                                                    L{{ $levelNo }} · {{ \Illuminate\Support\Carbon::parse($escalationLevels[(string) $levelNo])->format('d/m H:i') }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                                <div class="mt-2 text-xs text-slate-500">{{ $row->created_at?->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="text-slate-700">
                                <div class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">Diajukan Oleh Pembeli</div>
                                <div class="font-semibold">{{ $row->requester?->name ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold mb-1">Assignee (Reviewer)</div>
                                @if($row->status === 'pending')
                                    <form method="POST" action="{{ route('admin.approvals.assign', $row) }}" class="flex gap-2 items-center approval-assign-form">
                                        @csrf
                                        <select name="assigned_to" class="input-ui text-xs min-w-0 flex-1 approval-input-sm">
                                            <option value="">-</option>
                                            @foreach(($approvers ?? collect()) as $approver)
                                                <option value="{{ $approver->id }}" @selected((int) ($row->assigned_to ?? 0) === (int) $approver->id)>{{ $approver->name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn-primary text-xs px-4 shrink-0 approval-set-btn">Set</button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-600">{{ $row->assignee?->name ?: '-' }}</span>
                                @endif
                            </td>
                            <td>
                                <?php
                                    $statusLabelClass = $row->status === 'pending'
                                        ? 'approval-label-amber'
                                        : ($row->status === 'approved' ? 'approval-label-emerald' : 'approval-label-rose');
                                    $priorityLabelClass = $priorityLabel === 'critical'
                                        ? 'approval-label-rose'
                                        : ($priorityLabel === 'high' ? 'approval-label-amber' : ($priorityLabel === 'done' ? 'approval-label-emerald' : 'approval-label-slate'));
                                    $slaLabelClass = $slaState === 'critical'
                                        ? 'approval-label-rose'
                                        : ($slaState === 'breach' ? 'approval-label-rose-soft' : ($slaState === 'warn' ? 'approval-label-amber' : 'approval-label-emerald'));
                                    $typeSlaTarget = str_starts_with((string) $row->type, 'report.export.')
                                        ? (int) data_get($summary ?? [], 'sla_minutes_export', 360)
                                        : (int) data_get($summary ?? [], 'sla_minutes_sale', 120);
                                    $typeSlaLabel = str_starts_with((string) $row->type, 'report.export.')
                                        ? 'Target Export'
                                        : ((string) $row->type === 'sale.quick_void' ? 'Target Void' : 'Target Refund');
                                ?>
                                <div class="approval-metric-card">
                                    <div class="approval-metric-grid2">
                                    <div class="approval-metric-row approval-metric-row--plain {{ $statusLabelClass }}">
                                        <span class="approval-metric-label">Status</span>
                                        <span class="inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                        {{ $row->status === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                                        {{ $row->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                        {{ $row->status === 'rejected' ? 'bg-rose-100 text-rose-700' : '' }}">
                                            {{ strtoupper($row->status) }}
                                        </span>
                                    </div>
                                    <div class="approval-metric-row approval-metric-row--plain {{ $priorityLabelClass }}">
                                        <span class="approval-metric-label">Prioritas</span>
                                        <div class="flex flex-wrap gap-1">
                                            <span class="inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                            {{ $priorityLabel === 'critical' ? 'bg-rose-200 text-rose-800' : '' }}
                                            {{ $priorityLabel === 'high' ? 'bg-amber-200 text-amber-800' : '' }}
                                            {{ $priorityLabel === 'normal' ? 'bg-slate-100 text-slate-700' : '' }}
                                            {{ $priorityLabel === 'done' ? 'bg-emerald-100 text-emerald-700' : '' }}">
                                                {{ strtoupper($priorityLabel) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="approval-metric-row approval-metric-row--plain {{ $slaLabelClass }}">
                                        <span class="approval-metric-label">SLA</span>
                                        @if($row->status === 'pending')
                                            <div class="flex flex-wrap items-center gap-1">
                                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold whitespace-nowrap
                                                {{ $slaState === 'critical' ? 'bg-rose-100 text-rose-800' : '' }}
                                                {{ $slaState === 'breach' ? 'bg-rose-50 text-rose-700' : '' }}
                                                {{ $slaState === 'warn' ? 'bg-amber-50 text-amber-700' : '' }}
                                                {{ $slaState === 'ok' ? 'bg-emerald-50 text-emerald-700' : '' }}">{{ $slaText }}</span>
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold
                                                {{ $slaState === 'critical' ? 'bg-rose-200 text-rose-800' : '' }}
                                                {{ $slaState === 'breach' ? 'bg-rose-100 text-rose-700' : '' }}
                                                {{ $slaState === 'warn' ? 'bg-amber-100 text-amber-700' : '' }}
                                                {{ $slaState === 'ok' ? 'bg-emerald-100 text-emerald-700' : '' }}">
                                                    {{ $slaState === 'critical' ? 'CRITICAL' : ($slaState === 'breach' ? 'BREACH' : ($slaState === 'warn' ? 'WARNING' : 'ON TRACK')) }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-xs font-semibold text-slate-500">-</span>
                                        @endif
                                    </div>
                                    <div class="approval-metric-row approval-metric-row--plain approval-label-slate">
                                        <span class="approval-metric-label">{{ $typeSlaLabel }}</span>
                                        <div class="flex flex-wrap items-center gap-1">
                                            <span class="inline-flex items-center rounded-full bg-sky-100 text-sky-700 px-2.5 py-1 text-xs font-semibold">{{ number_format($typeSlaTarget, 0, ',', '.') }}m</span>
                                            @if($row->status === 'pending')
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold
                                                {{ ($slaMinutesPending ?? 0) > $typeSlaTarget ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                    {{ ($slaMinutesPending ?? 0) > $typeSlaTarget ? 'LEWAT TARGET' : 'DALAM TARGET' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="approval-metric-row approval-metric-row--plain approval-label-slate">
                                        <span class="approval-metric-label">Tag</span>
                                        <div class="flex flex-wrap gap-1">
                                            @if($isAutoExpired)
                                                <span class="inline-flex w-fit items-center rounded-full bg-fuchsia-100 text-fuchsia-700 px-2.5 py-1 text-xs font-semibold">AUTO EXPIRED</span>
                                            @endif
                                            @if($row->status === 'approved' && $isExportType && $executedAtMain !== '')
                                                <span class="inline-flex w-fit items-center rounded-full bg-blue-100 text-blue-700 px-2.5 py-1 text-xs font-semibold">EXECUTED</span>
                                            @endif
                                            @if(! $isAutoExpired && ! ($row->status === 'approved' && $isExportType && $executedAtMain !== ''))
                                                <span class="text-xs font-semibold text-slate-500">-</span>
                                            @endif
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </td>
                            <td class="approval-actions-cell">
                                <div class="text-sm leading-tight text-slate-700">{{ $row->created_at?->format('d/m/Y') }}</div>
                                <div class="text-sm leading-tight text-slate-700">{{ $row->created_at?->format('H:i') }}</div>
                                @if($row->status === 'pending' && $row->snoozed_until && $row->snoozed_until->isFuture())
                                    <div class="mt-1 text-[11px] text-indigo-600 font-semibold">
                                        Snooze s.d. {{ $row->snoozed_until->format('d/m H:i') }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($row->status === 'pending')
                                    <div class="flex flex-col gap-2 approval-actions">
                                        <form method="POST" action="{{ route('admin.approvals.approve', $row) }}" class="grid grid-cols-[1fr_auto] items-center gap-2">
                                            @csrf
                                            <input type="text" name="review_note" class="input-ui text-xs approval-input-sm" placeholder="Catatan approve (opsional)">
                                            <button class="btn-primary text-xs px-3 py-2 whitespace-nowrap inline-flex items-center gap-1.5">
                                                <i data-feather="check" class="w-3.5 h-3.5"></i>
                                                <span>Approve</span>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.approvals.reject', $row) }}" class="flex flex-col gap-2">
                                            @csrf
                                            <select class="input-ui text-xs approval-input-sm approval-reject-preset">
                                                <option value="">Pilih preset alasan</option>
                                                @foreach(($rejectReasonPresets ?? collect()) as $preset)
                                                    <option value="{{ $preset }}">{{ $preset }}</option>
                                                @endforeach
                                            </select>
                                            <input type="text" name="review_note" class="input-ui text-xs approval-input-sm" placeholder="Alasan reject (wajib)">
                                            <button class="btn-void aq-row-reject">
                                                <i data-feather="x" class="w-3.5 h-3.5"></i>
                                                <span>Reject</span>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.approvals.snooze', $row) }}" class="grid grid-cols-[80px_1fr] gap-2">
                                            @csrf
                                            @if($escalationLastLevel >= 3)
                                                <div class="col-span-2 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-2 text-[11px] font-semibold text-rose-700">
                                                    Level escalation 3: Snooze dinonaktifkan. Wajib approve/reject.
                                                </div>
                                            @else
                                                <select name="minutes" class="input-ui text-xs approval-input-sm">
                                                    <option value="15">15m</option>
                                                    <option value="30">30m</option>
                                                    <option value="60">60m</option>
                                                </select>
                                                <input type="text" name="note" class="input-ui text-xs approval-input-sm" placeholder="Catatan snooze (opsional)">
                                                <button class="btn-danger-lite text-xs col-span-2">Snooze</button>
                                            @endif
                                        </form>
                                        @if($row->snoozed_until && $row->snoozed_until->isFuture())
                                            <form method="POST" action="{{ route('admin.approvals.unsnooze', $row) }}">
                                                @csrf
                                                <button class="btn-danger-lite text-xs w-full">Buka Snooze</button>
                                            </form>
                                        @endif
                                        <button
                                            type="button"
                                            class="btn-danger-lite text-xs w-fit"
                                            data-approval-detail-toggle="approval-detail-{{ $row->id }}"
                                        >Detail</button>
                                    </div>
                                @else
                                    <div class="text-xs text-slate-600 leading-relaxed">
                                        <div><span class="font-semibold text-slate-700">Reviewer:</span> {{ $row->reviewer?->name ?: '-' }}</div>
                                        <div><span class="font-semibold text-slate-700">Waktu:</span> {{ $row->reviewed_at?->format('d/m/Y H:i') ?: '-' }}</div>
                                        <div><span class="font-semibold text-slate-700">Catatan:</span> {{ $row->review_note ?: '-' }}</div>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn-danger-lite text-xs mt-2 w-fit"
                                        data-approval-detail-toggle="approval-detail-{{ $row->id }}"
                                    >Detail</button>
                                    @if($row->status === 'approved' && str_starts_with((string) $row->type, 'report.export.'))
                                        <?php
                                            $payload = is_array($row->payload) ? $row->payload : [];
                                            $executedAt = (string) data_get($payload, 'export_executed_at', '');
                                            $executedByName = (string) data_get($payload, 'export_executed_by_name', '');
                                        ?>
                                        <div class="mt-2">
                                            @if($executedAt !== '')
                                                <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-500">
                                                    <i data-feather="check-circle" class="w-3.5 h-3.5"></i>
                                                    <span>Sudah Dieksekusi</span>
                                                </span>
                                                <div class="mt-1 text-[11px] text-slate-500">
                                                    {{ \Illuminate\Support\Carbon::parse($executedAt)->format('d/m/Y H:i') }}
                                                    @if($executedByName !== '')
                                                        · {{ $executedByName }}
                                                    @endif
                                                </div>
                                            @else
                                                <a href="{{ route('admin.approvals.execute-export', $row) }}" class="btn-primary text-xs px-3 py-2 inline-flex items-center gap-1.5">
                                                    <i data-feather="download" class="w-3.5 h-3.5"></i>
                                                    <span>Eksekusi Export</span>
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        <tr id="approval-detail-{{ $row->id }}" class="approval-detail-row" style="display:none;">
                            <td colspan="8" class="bg-slate-50 border-t border-slate-100">
                                <div class="p-3 grid gap-3 md:grid-cols-2">
                                    <div>
                                        <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold mb-1">Ringkasan</p>
                                        <div class="text-sm text-slate-700 leading-relaxed">
                                            <div><strong>ID:</strong> #{{ $row->id }}</div>
                                            <div><strong>Tipe:</strong> {{ $row->type }}</div>
                                            <div><strong>Status:</strong> {{ strtoupper($row->status) }}</div>
                                            <div><strong>Peminta:</strong> {{ $row->requester?->name ?: '-' }}</div>
                                            <div><strong>Dibuat:</strong> {{ $row->created_at?->format('d/m/Y H:i:s') }}</div>
                                            <div><strong>Direview:</strong> {{ $row->reviewed_at?->format('d/m/Y H:i:s') ?: '-' }}</div>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold mb-1">Payload</p>
                                        <?php
                                            $payload = is_array($row->payload) ? $row->payload : [];
                                            $summaryFields = [
                                                'invoice' => data_get($payload, 'invoice'),
                                                'sale_id' => data_get($payload, 'sale_id'),
                                                'format' => data_get($payload, 'format'),
                                                'selected_count' => data_get($payload, 'selected_count'),
                                                'selected_total' => data_get($payload, 'selected_total'),
                                                'fingerprint' => data_get($payload, 'fingerprint'),
                                            ];
                                            $hasSummaryField = collect($summaryFields)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
                                        ?>
                                        @if($hasSummaryField)
                                            <div class="mb-2 grid gap-2 sm:grid-cols-2">
                                                @if(!empty($summaryFields['invoice']))
                                                    <div class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700"><span class="font-semibold text-slate-500">Invoice:</span> {{ $summaryFields['invoice'] }}</div>
                                                @endif
                                                @if(!empty($summaryFields['sale_id']))
                                                    <div class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700"><span class="font-semibold text-slate-500">Sale ID:</span> {{ $summaryFields['sale_id'] }}</div>
                                                @endif
                                                @if(!empty($summaryFields['format']))
                                                    <div class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700"><span class="font-semibold text-slate-500">Format:</span> {{ strtoupper((string) $summaryFields['format']) }}</div>
                                                @endif
                                                @if(!empty($summaryFields['selected_count']))
                                                    <div class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700"><span class="font-semibold text-slate-500">Selected Count:</span> {{ number_format((int) $summaryFields['selected_count'], 0, ',', '.') }}</div>
                                                @endif
                                                @if(!empty($summaryFields['selected_total']))
                                                    <div class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700"><span class="font-semibold text-slate-500">Selected Total:</span> Rp {{ number_format((float) $summaryFields['selected_total'], 0, ',', '.') }}</div>
                                                @endif
                                                @if(!empty($summaryFields['fingerprint']))
                                                    <div class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700 break-all"><span class="font-semibold text-slate-500">Fingerprint:</span> {{ $summaryFields['fingerprint'] }}</div>
                                                @endif
                                            </div>
                                        @endif
                                        <pre class="text-xs bg-white border border-slate-200 rounded-lg p-2 text-slate-700 whitespace-pre-wrap break-all">{{ json_encode($row->payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $rows->links() }}</div>
            <div class="px-4 pb-4">
                <div class="rounded-xl border border-amber-300 bg-amber-50 px-3 py-2 text-[10px] leading-4 text-amber-900">
                    <div>* SLA dihitung berdasarkan jam kerja: {{ data_get($summary ?? [], 'business_hours.start', '08:00') }} - {{ data_get($summary ?? [], 'business_hours.end', '22:00') }} (hari kerja: {{ implode(',', (array) data_get($summary ?? [], 'business_hours.workdays', [1,2,3,4,5,6,7])) }}).</div>
                    <div>* <strong>SLA</strong>: batas waktu penanganan.</div>
                    <div>* <strong>Overdue/Breach</strong>: sudah lewat SLA.</div>
                    <div>* <strong>Snooze</strong>: tunda review sementara.</div>
                    <div>* <strong>Execute Export</strong>: menjalankan file export yang sudah di-approve.</div>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const selectAll = document.getElementById('bulk-select-all');
            const checkboxes = Array.from(document.querySelectorAll('.bulk-approval-checkbox'));
            const selectedCountEl = document.getElementById('bulk-selected-count');
            const bulkForm = document.getElementById('approval-bulk-form');
            const bulkApproveBtn = document.getElementById('bulk-approve-btn');
            const bulkRejectBtn = document.getElementById('bulk-reject-btn');
            const bulkExecuteExportBtn = document.getElementById('bulk-execute-export-btn');
            const weeklyChartData = @json($slaTrendWeekly ?? []);
            const monthlyChartData = @json($slaTrendMonthly ?? []);

            const updateCount = () => {
                const selected = checkboxes.filter((el) => el.checked).length;
                if (selectedCountEl) selectedCountEl.textContent = `${selected} terpilih`;
            };

            const getSelected = () => checkboxes.filter((el) => el.checked).map((el) => String(el.value || ''));

            const prepareSubmit = (actionUrl, requireNote) => {
                if (!bulkForm) return;
                const selected = getSelected();
                if (selected.length === 0) {
                    alert('Pilih minimal 1 request pending.');
                    return;
                }

                const noteInput = document.getElementById('bulk-review-note');
                const note = String(noteInput?.value || '').trim();
                if (requireNote && note.length < 5) {
                    alert('Catatan bulk reject minimal 5 karakter.');
                    return;
                }

                bulkForm.action = actionUrl;
                bulkForm.method = 'POST';
                bulkForm.querySelectorAll('input[name=\"approval_ids[]\"]').forEach((el) => el.remove());
                selected.forEach((id) => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'approval_ids[]';
                    hidden.value = id;
                    bulkForm.appendChild(hidden);
                });
                bulkForm.submit();
            };

            selectAll?.addEventListener('change', () => {
                checkboxes.forEach((el) => { el.checked = !!selectAll.checked; });
                updateCount();
            });
            checkboxes.forEach((el) => {
                el.addEventListener('change', updateCount);
            });
            bulkApproveBtn?.addEventListener('click', () => prepareSubmit(@json(route('admin.approvals.bulk-approve')), false));
            bulkRejectBtn?.addEventListener('click', () => prepareSubmit(@json(route('admin.approvals.bulk-reject')), true));
            bulkExecuteExportBtn?.addEventListener('click', () => prepareSubmit(@json(route('admin.approvals.bulk-execute-exports')), false));

            document.querySelectorAll('.approval-reject-preset').forEach((selectEl) => {
                selectEl.addEventListener('change', () => {
                    const form = selectEl.closest('form');
                    if (!form) return;
                    const input = form.querySelector('input[name="review_note"]');
                    if (!input) return;
                    const value = String(selectEl.value || '').trim();
                    if (value !== '') input.value = value;
                });
            });

            document.querySelectorAll('.js-approval-template-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const approveText = String(btn.getAttribute('data-approve') || '').trim();
                    const rejectText = String(btn.getAttribute('data-reject') || '').trim();
                    const snoozeText = String(btn.getAttribute('data-snooze') || '').trim();

                    if (approveText !== '') {
                        document.querySelectorAll('form[action*="/approve"] input[name="review_note"]').forEach((el) => { if (!el.value) el.value = approveText; });
                    }
                    if (rejectText !== '') {
                        document.querySelectorAll('form[action*="/reject"] input[name="review_note"]').forEach((el) => { if (!el.value) el.value = rejectText; });
                    }
                    if (snoozeText !== '') {
                        document.querySelectorAll('form[action*="/snooze"] input[name="note"]').forEach((el) => { if (!el.value) el.value = snoozeText; });
                    }
                });
            });

            document.querySelectorAll('[data-approval-detail-toggle]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const targetId = btn.getAttribute('data-approval-detail-toggle');
                    if (!targetId) return;
                    const row = document.getElementById(targetId);
                    if (!row) return;
                    const hidden = row.style.display === 'none' || row.style.display === '';
                    row.style.display = hidden ? 'table-row' : 'none';
                    btn.textContent = hidden ? 'Tutup' : 'Detail';
                });
            });

            const initSlaChart = (canvasId, rows, palette) => {
                const el = document.getElementById(canvasId);
                if (!el || typeof Chart === 'undefined' || !Array.isArray(rows) || rows.length === 0) return;
                const ctx = el.getContext('2d');
                if (!ctx) return;
                const labels = rows.map((r) => String(r.label || ''));
                const reviewed = rows.map((r) => Number(r.reviewed || 0));
                const avgReview = rows.map((r) => Number(r.avg_review || 0));
                const rejectRate = rows.map((r) => Number(r.reject_rate || 0));
                const area = ctx.createLinearGradient(0, 0, 0, 220);
                area.addColorStop(0, palette.barGradTop);
                area.addColorStop(1, palette.barGradBottom);

                const datasets = [
                    {
                        type: 'bar',
                        label: 'Reviewed',
                        data: reviewed,
                        backgroundColor: area,
                        borderColor: palette.barBorder,
                        borderWidth: 1,
                        borderRadius: 10,
                        borderSkipped: false,
                        yAxisID: 'y',
                    },
                ];

                if (palette.metric === 'avg') {
                    datasets.push({
                        type: 'line',
                        label: 'Avg Review (m)',
                        data: avgReview,
                        borderColor: palette.lineA,
                        backgroundColor: palette.lineAFill,
                        fill: true,
                        tension: 0.38,
                        pointRadius: 3,
                        pointHoverRadius: 4.5,
                        pointBackgroundColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        pointBorderColor: palette.lineA,
                        yAxisID: 'y1',
                    });
                }

                if (palette.metric === 'reject') {
                    datasets.push({
                        type: 'line',
                        label: 'Reject Rate (%)',
                        data: rejectRate,
                        borderColor: palette.lineB,
                        backgroundColor: 'rgba(245,158,11,0.12)',
                        fill: true,
                        tension: 0.38,
                        pointRadius: 3,
                        pointHoverRadius: 4.5,
                        pointBackgroundColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        pointBorderColor: palette.lineB,
                        yAxisID: 'y1',
                    });
                }

                new Chart(el, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets,
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 520,
                            easing: 'easeOutCubic',
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 10 } },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleColor: '#f8fafc',
                                bodyColor: '#f1f5f9',
                                padding: 8,
                                displayColors: true,
                            },
                        },
                        elements: {
                            line: {
                                cubicInterpolationMode: 'monotone',
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: '#475569', maxRotation: 0 },
                            },
                            y: {
                                beginAtZero: true,
                                title: { display: true, text: 'Reviewed' },
                                ticks: { color: '#475569' },
                                grid: { color: 'rgba(148,163,184,.14)' },
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                grid: { drawOnChartArea: false },
                                title: { display: true, text: palette.metric === 'avg' ? 'Avg (m)' : 'Reject %' },
                                ticks: { color: palette.metric === 'avg' ? '#0f766e' : '#b45309' },
                            },
                        },
                    },
                });
            };
            const renderSlaCharts = () => {
                initSlaChart('sla-weekly-chart', weeklyChartData, {
                    metric: 'avg',
                    barGradTop: 'rgba(71,85,105,0.20)',
                    barGradBottom: 'rgba(71,85,105,0.08)',
                    barBorder: '#64748b',
                    lineA: '#0f766e',
                    lineAFill: 'rgba(15,118,110,0.06)',
                    lineB: '#e11d48',
                });
                initSlaChart('sla-monthly-chart', monthlyChartData, {
                    metric: 'reject',
                    barGradTop: 'rgba(100,116,139,0.20)',
                    barGradBottom: 'rgba(100,116,139,0.08)',
                    barBorder: '#64748b',
                    lineA: '#0ea5e9',
                    lineAFill: 'rgba(14,165,233,0.06)',
                    lineB: '#b45309',
                });
            };

            const loadChartJsAndRender = () => {
                if (typeof Chart !== 'undefined') {
                    renderSlaCharts();
                    return;
                }

                const existing = document.querySelector('script[data-chartjs-approval="1"]');
                if (existing) {
                    existing.addEventListener('load', renderSlaCharts, { once: true });
                    return;
                }

                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js';
                script.async = true;
                script.defer = true;
                script.dataset.chartjsApproval = '1';
                script.onload = renderSlaCharts;
                script.onerror = () => {
                    console.warn('Gagal memuat Chart.js untuk SLA chart.');
                };
                document.head.appendChild(script);
            };

            loadChartJsAndRender();

            updateCount();
        })();
    </script>
    <style>
        .summary-card{
            border-width: 1px !important;
        }
        .summary-card--pending{ background: #dbeafe !important; border-color: #93c5fd !important; }
        .summary-card--overdue{ background: #fef3c7 !important; border-color: #fcd34d !important; }
        .summary-card--approved{ background: #dbeafe !important; border-color: #93c5fd !important; }
        .summary-card--avg{ background: #d1fae5 !important; border-color: #86efac !important; }
        .summary-card--reject{ background: #ffe4e6 !important; border-color: #fda4af !important; }
        .summary-card--expired{ background: #fae8ff !important; border-color: #f0abfc !important; }
        .summary-card--aging-1{ background: #dcfce7 !important; border-color: #86efac !important; }
        .summary-card--aging-2{ background: #fef3c7 !important; border-color: #fcd34d !important; }
        .summary-card--aging-3{ background: #ffe4e6 !important; border-color: #fda4af !important; }
        .summary-card--aging-4{ background: #fae8ff !important; border-color: #f0abfc !important; }

        .aq-bulk-reject{
            height: 40px;
            padding: 0 14px;
            font-size: 14px;
            font-weight: 800;
            border-radius: 10px;
            white-space: nowrap;
            background: #e11d48;
            border: 1px solid #e11d48;
            color: #ffffff;
        }
        .aq-row-reject{
            width: 100%;
            min-height: 36px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 800;
            border-radius: 10px;
            line-height: 1.2;
            background: #e11d48;
            border: 1px solid #e11d48;
            color: #ffffff;
        }
        .customer-table-shell{ overflow-x: hidden; }
        .approval-table-compact{
            width: 100%;
            min-width: 0 !important;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 0;
        }
        .approval-table-compact thead th{
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .04em;
            color: #475569;
            white-space: normal;
        }
        .approval-table-compact th,
        .approval-table-compact td {
            vertical-align: top;
            word-break: normal;
            overflow-wrap: break-word;
            white-space: normal;
            padding-top: 14px;
            padding-bottom: 14px;
            line-height: 1.35;
        }
        .approval-table-compact td{
            font-size: 13px;
            color: #0f172a;
        }
        .approval-table-compact th:nth-child(3),
        .approval-table-compact td:nth-child(3){
            width: auto;
            word-break: normal;
            overflow-wrap: break-word;
        }
        .approval-table-compact td:nth-child(6),
        .approval-table-compact td:nth-child(7){
            white-space: normal;
        }
        .approval-table-compact th:first-child,
        .approval-table-compact td:first-child {
            padding-left: 10px;
            padding-right: 10px;
        }
        .approval-assign-form{
            min-width: 0;
        }
        .approval-assign-form select{
            min-width: 0;
            max-width: 280px;
            width: 100%;
        }
        .approval-actions-cell{ min-width: 0; display: none !important; }
        .approval-actions .btn-primary,
        .approval-actions .aq-row-reject {
            justify-content: center;
        }
        .approval-actions .btn-danger-lite {
            align-self: flex-start;
        }
        .approval-input-sm {
            min-height: 36px !important;
            padding-top: 6px !important;
            padding-bottom: 6px !important;
        }
        .approval-metric-card {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            padding: 8px;
        }
        .approval-metric-grid2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }
        .approval-metric-row {
            display: flex;
            flex-direction: column;
            gap: 3px;
            padding: 5px 7px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
        }
        .approval-metric-row.approval-metric-row--plain {
            border: 0;
            border-radius: 6px;
            padding: 4px 6px;
        }
        .approval-metric-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .06em;
            font-weight: 800;
            color: #475569;
        }
        .approval-metric-row.approval-label-slate {
            border-color: #94a3b8;
            background: #e2e8f0;
        }
        .approval-metric-row.approval-label-amber {
            border-color: #f59e0b;
            background: #fef3c7;
        }
        .approval-metric-row.approval-label-emerald {
            border-color: #10b981;
            background: #d1fae5;
        }
        .approval-metric-row.approval-label-rose {
            border-color: #e11d48;
            background: #ffe4e6;
        }
        .approval-metric-row.approval-label-rose-soft {
            border-color: #fb7185;
            background: #ffe4e6;
        }
        .approval-metric-row.approval-label-slate .approval-metric-label {
            color: #334155;
        }
        .approval-metric-row.approval-label-amber .approval-metric-label {
            color: #92400e;
        }
        .approval-metric-row.approval-label-emerald .approval-metric-label {
            color: #065f46;
        }
        .approval-metric-row.approval-label-rose .approval-metric-label,
        .approval-metric-row.approval-label-rose-soft .approval-metric-label {
            color: #9f1239;
        }
        .approval-actions form{
            width: 100%;
        }
        .approval-actions .input-ui,
        .approval-actions select{
            width: 100%;
            min-width: 0;
        }
        .approval-actions .btn-primary,
        .approval-actions .aq-row-reject,
        .approval-actions .btn-danger-lite{
            min-height: 34px;
        }
        /* Render rows as full cards (not table look) */
        .approval-table-compact thead {
            display: none;
        }
        .approval-table-compact,
        .approval-table-compact tbody,
        .approval-table-compact tr,
        .approval-table-compact td {
            display: block;
            width: 100%;
        }
        .approval-table-compact tbody {
            padding: 8px;
        }
        .approval-table-compact tr.customer-row {
            border: 1px solid #dbe3ef;
            border-radius: 14px;
            background: #ffffff;
            padding: 14px;
            margin-bottom: 22px;
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) minmax(340px, 420px);
            column-gap: 20px;
            row-gap: 12px;
            align-items: start;
            position: relative;
        }
        .approval-table-compact tr.customer-row:not(.approval-row-last)::after{
            content: "";
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: -12px;
            border-top: 1px solid #dbe3ef;
        }
        .approval-table-compact tr.customer-row td {
            padding: 6px 0 !important;
            border: 0 !important;
        }
        .approval-table-compact tr.customer-row td:first-child {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .approval-table-compact tr.approval-detail-row td {
            margin-top: -10px;
            border-radius: 10px;
        }
        .approval-table-compact tr.customer-row td:nth-child(1) { grid-column: 1; grid-row: 1; }
        .approval-table-compact tr.customer-row td:nth-child(2) { display: none; }
        .approval-table-compact tr.customer-row td:nth-child(3) { grid-column: 1; grid-row: 2; }
        .approval-table-compact tr.customer-row td:nth-child(4) { grid-column: 1; grid-row: 3; }
        .approval-table-compact tr.customer-row td:nth-child(5) { grid-column: 1; grid-row: 4; }
        .approval-table-compact tr.customer-row td:nth-child(6) { grid-column: 1; grid-row: 5; }
        .approval-table-compact tr.customer-row td:nth-child(7) { display: none !important; }
        .approval-table-compact tr.customer-row td:nth-child(8) {
            grid-column: 2;
            grid-row: 1 / span 5;
            border-left: 1px solid #e5eaf3 !important;
            padding-left: 16px !important;
        }
        .approval-table-compact tr.customer-row td:nth-child(1) {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 2px !important;
            justify-content: space-between;
        }
        .approval-head-left {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .approval-table-compact tr.customer-row td:nth-child(2) {
            display: none;
        }
        .approval-cell-check {
            width: auto;
        }
        .approval-cell-check input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin-top: 0;
        }
        .approval-id-inline {
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
            margin-right: 8px;
        }
        .approval-type-inline {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            padding: 3px 8px;
            border-radius: 999px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
        }
        .approval-type-inline--right {
            margin-left: auto;
        }
        .approval-type-inline--danger {
            background: #ffe4e6;
            border-color: #fb7185;
            color: #9f1239;
        }
        .approval-assign-form .approval-set-btn {
            min-height: 36px;
            border-radius: 10px;
            font-weight: 700;
            min-width: 72px;
        }
        @media (max-width: 1200px) {
            .approval-table-compact tr.customer-row {
                grid-template-columns: 1fr;
            }
            .approval-table-compact tr.customer-row td:nth-child(8) {
                grid-column: 1;
                grid-row: auto;
                border-left: 0 !important;
                padding-left: 0 !important;
                border-top: 1px dashed #dbe3ef !important;
                padding-top: 12px !important;
            }
        }
        @media (max-width: 1100px) {
            .approval-metric-grid2 {
                grid-template-columns: 1fr;
            }
        }
        .approval-row-warn { border-color: #fcd34d !important; }
        .approval-row-breach { border-color: #fda4af !important; }
        .approval-row-critical { border-color: #fb7185 !important; }
    </style>
</x-app-layout>


