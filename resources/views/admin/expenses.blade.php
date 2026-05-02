<x-app-layout>
    <x-slot name="header">
        <div class="ex-head intro-y">
            <div>
                <p class="ex-kicker">Master Data</p>
                <h2 class="ex-title">Pengeluaran</h2>
                <p class="ex-sub">Catat dan pantau pengeluaran operasional dengan format audit-ready.</p>
            </div>
        </div>
    </x-slot>

    <style>
        .ex-shell { max-width: 1260px; margin: 0 auto; }
        .ex-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; }
        .ex-kicker { margin: 0; font-size: .73rem; text-transform: uppercase; letter-spacing: .08em; color: #64748b; font-weight: 700; }
        .ex-title { margin: .2rem 0 0; font-size: 1.95rem; line-height: 1.08; font-weight: 700; color: #0f172a; }
        .ex-sub { margin: .35rem 0 0; font-size: .9rem; color: #64748b; }

        .ex-kpi-grid { display: grid; gap: .75rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        @media (min-width: 900px) { .ex-kpi-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        .ex-kpi { border: 1px solid #dbe5f2; border-radius: 12px; background: #fff; padding: .72rem .82rem; }
        .ex-kpi-blue { background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%); border-color: #bfdbfe; }
        .ex-kpi-emerald { background: linear-gradient(180deg, #ecfdf5 0%, #ffffff 100%); border-color: #a7f3d0; }
        .ex-kpi-amber { background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%); border-color: #fde68a; }
        .ex-kpi-violet { background: linear-gradient(180deg, #f5f3ff 0%, #ffffff 100%); border-color: #ddd6fe; }
        .ex-kpi-label { margin: 0; font-size: .71rem; text-transform: uppercase; letter-spacing: .06em; color: #64748b; font-weight: 700; }
        .ex-kpi-value { margin: .2rem 0 0; font-size: 1.05rem; font-weight: 700; color: #0f172a; }
        .ex-icon { width: .9rem; height: .9rem; margin-right: .32rem; vertical-align: -2px; color: #64748b; }

        .ex-layout { display: grid; grid-template-columns: 1fr; gap: 1rem; align-items: start; }
        @media (min-width: 1080px) { .ex-layout { grid-template-columns: minmax(0, 1.45fr) minmax(340px, .55fr); } }

        .ex-card { background: #fff; border: 1px solid #dbe4f0; border-radius: 14px; box-shadow: 0 10px 22px rgba(15,23,42,.04); overflow: hidden; }
        .ex-headbar { padding: .92rem 1rem; border-bottom: 1px solid #e7eef7; background: linear-gradient(180deg,#fbfdff 0%,#f8fafc 100%); }
        .ex-card-title { margin: 0; font-size: .84rem; text-transform: uppercase; letter-spacing: .08em; color: #334155; font-weight: 700; }
        .ex-body { padding: 1rem; }
        .ex-card-form { position: sticky; top: 1rem; }
        @media (max-width: 1079px) { .ex-card-form { position: static; } }

        .ex-input,.ex-select,.ex-textarea {
            width: 100%;
            min-height: 2.6rem;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            color: #0f172a;
            padding: 0 .72rem;
            font-size: .9rem;
        }
        .ex-textarea { min-height: 84px; padding: .58rem .72rem; resize: vertical; }
        .ex-input:focus,.ex-select:focus,.ex-textarea:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.16); }
        .ex-label { display: block; margin-bottom: .3rem; font-size: .73rem; letter-spacing: .04em; text-transform: uppercase; color: #64748b; font-weight: 600; }

        .ex-btn { min-height: 2.45rem; padding: 0 .9rem; border-radius: 10px; border: 1px solid #1d4ed8; background: #1d4ed8; color: #fff; font-size: .8rem; font-weight: 600; display:inline-flex; align-items:center; justify-content:center; text-decoration: none; }
        .ex-btn:hover { background: #1e40af; border-color: #1e40af; }
        .ex-btn-soft { min-height: 2.1rem; padding: 0 .75rem; border-radius: 9px; border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: .76rem; font-weight: 600; display:inline-flex; align-items:center; justify-content:center; }
        .ex-btn-danger { border-color: #fecaca; background: #fff1f2; color: #b91c1c; }

        .ex-filter-wrap { border: 1px solid #dbe5f2; border-radius: 12px; padding: .75rem; background: linear-gradient(135deg, #f8fbff 0%, #f0f7ff 45%, #ffffff 100%); }
        .ex-filter-grid { display: grid; grid-template-columns: 1fr; gap: .55rem; }
        .ex-filter-search { grid-column: 1 / -1; }
        .ex-filter-secondary {
            display: grid;
            grid-template-columns: 1fr;
            gap: .55rem;
            width: 100%;
            min-width: 0;
        }
        @media (min-width: 980px) {
            .ex-filter-secondary {
                grid-template-columns: minmax(180px, 1fr) minmax(150px, .8fr) minmax(150px, .8fr) auto auto;
                align-items: center;
            }
        }
        .ex-filter-secondary > * { min-width: 0; }
        .ex-filter-secondary .ex-btn,
        .ex-filter-secondary .ex-btn-soft {
            white-space: nowrap;
            min-width: 0;
            padding-left: .78rem;
            padding-right: .78rem;
        }
        @media (max-width: 1200px) {
            .ex-filter-secondary {
                grid-template-columns: 1fr 1fr;
            }
            .ex-filter-secondary .ex-btn,
            .ex-filter-secondary .ex-btn-soft {
                width: 100%;
            }
        }
        @media (max-width: 760px) {
            .ex-filter-secondary {
                grid-template-columns: 1fr;
            }
        }
        .ex-chip-row { display:flex; flex-wrap:wrap; gap:.45rem; margin-bottom:.6rem; }
        .ex-chip {
            display:inline-flex; align-items:center; justify-content:center; min-height:2rem; padding:0 .72rem;
            border:1px solid #cbd5e1; border-radius:999px; background:#fff; color:#475569; font-size:.75rem; font-weight:700; text-decoration:none;
        }
        .ex-chip:hover { border-color:#bfdbfe; color:#1d4ed8; background:#eff6ff; }
        .ex-chip-active { border-color:#1d4ed8; background:#1d4ed8; color:#fff; }

        .ex-list { display: grid; gap: .7rem; }
        .ex-item { border: 1px solid #e2e8f0; border-radius: 12px; padding: .85rem .9rem; display: grid; gap: .52rem; background: #fff; }
        .ex-item-top { display:flex; align-items:flex-start; justify-content:space-between; gap:.7rem; }
        .ex-item-title { margin:0; font-size:1rem; font-weight:600; color:#0f172a; }
        .ex-item-meta { margin:0; color:#64748b; font-size:.8rem; }
        .ex-amount { display:inline-flex; align-items:center; padding:.22rem .62rem; border-radius:999px; border:1px solid #bfdbfe; background:#eff6ff; color:#1d4ed8; font-size:.8rem; font-weight:700; }
        .ex-note { margin:0; color:#334155; font-size:.83rem; line-height:1.4; }
        .ex-actions { display:flex; gap:.45rem; padding-top:.45rem; border-top:1px dashed #e2e8f0; flex-wrap: wrap; }
        .ex-category-summary {
            border: 1px solid #dbe5f2;
            border-radius: 12px;
            padding: .72rem .82rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }
        .ex-category-title { margin: 0 0 .55rem; font-size: .74rem; text-transform: uppercase; letter-spacing: .06em; color: #64748b; font-weight: 700; }
        .ex-category-list { display: grid; gap: .45rem; }
        .ex-category-row { display:flex; align-items:center; justify-content:space-between; gap:.6rem; font-size:.8rem; color:#334155; }
        .ex-category-name { font-weight: 600; color: #1e293b; }
        .ex-category-value { font-weight: 700; color: #0f172a; }
        .ex-history {
            border: 1px dashed #dbe5f2;
            border-radius: 10px;
            padding: .55rem .65rem;
            background: #fbfdff;
        }
        .ex-history-title { margin:0 0 .35rem; font-size:.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
        .ex-history-list { display:grid; gap:.24rem; }
        .ex-history-item { margin:0; font-size:.76rem; color:#475569; }
        .ex-receipt-link { font-size:.75rem; color:#1d4ed8; text-decoration:none; font-weight:600; }
        .ex-receipt-link:hover { text-decoration:underline; }
        .ex-inline-edit {
            display:grid; gap:.45rem; grid-template-columns: 1fr 160px auto; align-items:end;
            border:1px solid #e2e8f0; border-radius:10px; padding:.62rem; background:#f8fbff;
        }
        @media (max-width: 860px) { .ex-inline-edit { grid-template-columns: 1fr; } }
        .ex-inline-field { min-width:0; }
        .ex-inline-label { display:block; margin-bottom:.2rem; font-size:.68rem; color:#64748b; text-transform:uppercase; letter-spacing:.04em; font-weight:700; }

        .ex-pagination-custom { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.7rem; border:1px solid #dbe5f2; border-radius:12px; padding:.8rem .9rem; background: linear-gradient(180deg, #fff 0%, #f8fbff 100%); }
        .ex-pagination-summary { margin:0; font-size:.82rem; color:#64748b; font-weight:600; }
        .ex-pagination-pages { display:inline-flex; align-items:center; gap:.28rem; flex-wrap:wrap; }
        .ex-page-btn { min-width:2.15rem; height:2.15rem; display:inline-flex; align-items:center; justify-content:center; border:1px solid #dbe5f2; border-radius:9px; background:#fff; color:#334155; font-size:.82rem; font-weight:700; text-decoration:none; }
        .ex-page-btn:hover { background:#f8fafc; border-color:#bfdbfe; color:#1d4ed8; }
        .ex-page-btn-active { background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%); border-color:#1d4ed8; color:#fff; box-shadow:0 8px 16px rgba(37, 99, 235, .28); }
        .ex-page-dots { min-width:1.8rem; text-align:center; color:#94a3b8; font-weight:700; font-size:.85rem; line-height:1; }

        .ex-form-shell { display:grid; gap:.8rem; }
        .ex-form-group { border:1px solid #e2e8f0; border-radius:12px; padding:.85rem; background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); display:grid; gap:.65rem; }
        .ex-form-group-title { margin:0; font-size:.72rem; letter-spacing:.06em; text-transform:uppercase; color:#64748b; font-weight:700; }
        .ex-form-grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:.6rem; }
        .ex-form-grid-amount-wide { grid-template-columns: 1fr; }
        .ex-money-wrap { position: relative; }
        .ex-money-prefix { position:absolute; left:.62rem; top:50%; transform:translateY(-50%); font-size:.8rem; color:#64748b; font-weight:700; pointer-events:none; }
        .ex-input-money { padding-left: 2rem; }
        .ex-help { margin:0; font-size:.76rem; color:#94a3b8; }
        .ex-file { border:1px solid #dbe5f2; border-radius:10px; padding:.58rem; background:#fff; }
        .ex-file input[type="file"] { width:100%; font-size:.82rem; color:#334155; }
        .ex-file input[type="file"]::file-selector-button {
            margin-right:.58rem; border:1px solid #cbd5e1; border-radius:8px; padding:.32rem .62rem;
            background:#f8fafc; color:#334155; font-size:.78rem; font-weight:600; cursor:pointer;
        }
        .ex-file-preview {
            margin-top:.58rem; width:100%; min-height:88px; border:1px dashed #dbe5f2; border-radius:10px; background:#f8fafc;
            display:flex; align-items:center; justify-content:center; overflow:hidden;
        }
        .ex-file-preview img { width:100%; max-height:180px; object-fit:contain; display:block; }
        .ex-file-preview-empty { font-size:.78rem; color:#94a3b8; }

        .ex-modal { position: fixed; inset: 0; z-index: 50; display: none; align-items: center; justify-content: center; background: rgba(15,23,42,.58); padding: 1rem; }
        .ex-modal.open { display: flex; }
        .ex-modal-card {
            width: 100%;
            max-width: 760px;
            max-height: min(88vh, 900px);
            border-radius: 16px;
            border: 1px solid #dbe4f0;
            background: #fff;
            box-shadow: 0 20px 48px rgba(15, 23, 42, .22);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .ex-modal-head { padding: 1rem 1.1rem; border-bottom: 1px solid #e7eef7; background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%); }
        .ex-modal-title { margin: 0; font-size: .9rem; letter-spacing: .06em; text-transform: uppercase; color: #334155; font-weight: 700; }
        .ex-modal-body {
            padding: 1rem 1.1rem;
            overflow-y: auto;
            flex: 1 1 auto;
        }
        .ex-modal-actions { display: flex; justify-content: flex-end; gap: .55rem; margin-top: .95rem; padding-top: .8rem; border-top: 1px dashed #dbe5f2; }

        .ex-toast-wrap { position: fixed; right: 1rem; top: 1rem; z-index: 90; display: grid; gap: .55rem; width: min(360px, calc(100vw - 2rem)); }
        .ex-toast { border: 1px solid #bbf7d0; background: #ecfdf5; color: #065f46; border-radius: 11px; padding: .65rem .8rem; font-size: .82rem; font-weight: 600; box-shadow: 0 10px 18px rgba(15, 23, 42, .12); }
        .ex-toast-error { border-color: #fecaca; background: #fff1f2; color: #9f1239; }
    </style>

    <div class="ex-shell intro-y space-y-4">
        <div class="ex-kpi-grid">
            <div class="ex-kpi ex-kpi-blue">
                <p class="ex-kpi-label"><i data-feather="calendar" class="ex-icon"></i>Total Hari Ini</p>
                <p class="ex-kpi-value">Rp {{ number_format((float) ($stats['today'] ?? 0), 0, ',', '.') }}</p>
            </div>
            <div class="ex-kpi ex-kpi-emerald">
                <p class="ex-kpi-label"><i data-feather="bar-chart-2" class="ex-icon"></i>Total Bulan Ini</p>
                <p class="ex-kpi-value">Rp {{ number_format((float) ($stats['month'] ?? 0), 0, ',', '.') }}</p>
            </div>
            <div class="ex-kpi ex-kpi-amber">
                <p class="ex-kpi-label"><i data-feather="filter" class="ex-icon"></i>Total Sesuai Filter</p>
                <p class="ex-kpi-value">Rp {{ number_format((float) ($stats['filtered_total'] ?? 0), 0, ',', '.') }}</p>
            </div>
            <div class="ex-kpi ex-kpi-violet">
                <p class="ex-kpi-label"><i data-feather="layers" class="ex-icon"></i>Jumlah Data</p>
                <p class="ex-kpi-value">{{ number_format((int) ($stats['count'] ?? 0), 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="ex-layout">
            <div class="ex-card">
                <div class="ex-headbar"><h3 class="ex-card-title">Daftar Pengeluaran</h3></div>
                <div class="ex-body space-y-3">
                    <div class="ex-category-summary">
                        <p class="ex-category-title">Ringkasan Kategori (Sesuai Filter)</p>
                        <div class="ex-category-list">
                            @forelse(($byCategory ?? collect()) as $cat)
                                <div class="ex-category-row">
                                    <span class="ex-category-name">{{ $cat->category ?: 'Operasional' }}</span>
                                    <span class="ex-category-value">Rp {{ number_format((float) $cat->total, 0, ',', '.') }}</span>
                                </div>
                            @empty
                                <div class="ex-history-item">Belum ada data kategori.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="ex-filter-wrap">
                        <div class="ex-chip-row">
                            <a class="ex-chip {{ ($filters['preset'] ?? '') === 'today' ? 'ex-chip-active' : '' }}" href="{{ route('admin.expenses.index', array_merge(request()->except(['page']), ['preset' => 'today'])) }}">Hari Ini</a>
                            <a class="ex-chip {{ ($filters['preset'] ?? '') === '7d' ? 'ex-chip-active' : '' }}" href="{{ route('admin.expenses.index', array_merge(request()->except(['page']), ['preset' => '7d'])) }}">7 Hari</a>
                            <a class="ex-chip {{ ($filters['preset'] ?? '') === 'month' ? 'ex-chip-active' : '' }}" href="{{ route('admin.expenses.index', array_merge(request()->except(['page']), ['preset' => 'month'])) }}">Bulan Ini</a>
                            <a class="ex-chip" href="{{ route('admin.expenses.export.csv', request()->query()) }}">Export CSV</a>
                        </div>
                        <form method="GET" action="{{ route('admin.expenses.index') }}" class="ex-filter-grid">
                            <div class="ex-filter-search">
                                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="ex-input" placeholder="Cari judul / catatan pengeluaran">
                            </div>
                            <div class="ex-filter-secondary">
                                <select name="category" class="ex-select">
                                    <option value="all" @selected(($filters['category'] ?? 'all') === 'all')>Semua Kategori</option>
                                    @foreach(($categories ?? []) as $cat)
                                        <option value="{{ $cat }}" @selected(($filters['category'] ?? '') === $cat)>{{ $cat }}</option>
                                    @endforeach
                                </select>
                                <input type="date" name="date_from" value="{{ $filters['dateFrom'] ?? '' }}" class="ex-input">
                                <input type="date" name="date_to" value="{{ $filters['dateTo'] ?? '' }}" class="ex-input">
                                <input type="hidden" name="preset" value="">
                                <button class="ex-btn" type="submit"><i data-feather="search" class="ex-icon"></i>Terapkan</button>
                                <a href="{{ route('admin.expenses.index') }}" class="ex-btn-soft">Reset</a>
                            </div>
                        </form>
                    </div>

                    <div class="ex-list">
                        @forelse($expenses as $expense)
                            <div class="ex-item" id="expense-row-{{ $expense->id }}">
                                <div class="ex-item-top">
                                    <div>
                                        <h4 class="ex-item-title">{{ $expense->title }}</h4>
                                        <p class="ex-item-meta">
                                            {{ $expense->date?->format('d/m/Y') }} |
                                            {{ $expense->category ?? 'Operasional' }} |
                                            Input: {{ $expense->user?->name ?? '-' }}
                                        </p>
                                    </div>
                                    <span class="ex-amount">Rp {{ number_format((float) $expense->amount, 0, ',', '.') }}</span>
                                </div>
                                <p class="ex-note">{{ $expense->note ?: '-' }}</p>
                                @if(!empty($expense->receipt_path))
                                    <a href="{{ asset('storage/' . ltrim((string) $expense->receipt_path, '/')) }}" target="_blank" rel="noopener" class="ex-receipt-link">Lihat Bukti Lampiran</a>
                                @endif
                                <form method="POST" action="{{ route('admin.expenses.update', $expense) }}" class="ex-inline-edit" data-inline-expense="1">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="category" value="{{ $expense->category ?? 'Operasional' }}">
                                    <input type="hidden" name="title" value="{{ $expense->title }}">
                                    <input type="hidden" name="note" value="{{ $expense->note }}">
                                    <div class="ex-inline-field">
                                        <label class="ex-inline-label">Tanggal</label>
                                        <input type="date" name="date" class="ex-input" value="{{ optional($expense->date)->format('Y-m-d') }}" required>
                                    </div>
                                    <div class="ex-inline-field">
                                        <label class="ex-inline-label">Nominal</label>
                                        <input type="number" min="1" step="0.01" name="amount" class="ex-input" value="{{ (float) $expense->amount }}" required>
                                    </div>
                                    <button type="submit" class="ex-btn-soft">Simpan Cepat</button>
                                </form>
                                @php
                                    $rowHistory = collect($historyMap[$expense->id] ?? []);
                                    $actionLabel = [
                                        'expense_created' => 'Buat',
                                        'expense_updated' => 'Ubah',
                                        'expense_deleted' => 'Hapus',
                                        'expense_duplicated' => 'Duplikasi',
                                    ];
                                @endphp
                                @if($rowHistory->isNotEmpty())
                                    <div class="ex-history">
                                        <p class="ex-history-title">Riwayat</p>
                                        <div class="ex-history-list">
                                            @foreach($rowHistory as $log)
                                                <p class="ex-history-item">
                                                    {{ $actionLabel[(string) $log->action] ?? $log->action }}
                                                    · {{ $log->created_at?->format('d/m H:i') }}
                                                    · {{ $log->user?->name ?? '-' }}
                                                </p>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                <div class="ex-actions">
                                    <button
                                        type="button"
                                        class="ex-btn-soft"
                                        data-edit-expense="1"
                                        data-id="{{ $expense->id }}"
                                        data-title="{{ e($expense->title) }}"
                                        data-amount="{{ (float) $expense->amount }}"
                                        data-date="{{ optional($expense->date)->format('Y-m-d') }}"
                                        data-category="{{ $expense->category ?? 'Operasional' }}"
                                        data-note="{{ e((string) $expense->note) }}"
                                        data-receipt-url="{{ !empty($expense->receipt_path) ? asset('storage/' . ltrim((string) $expense->receipt_path, '/')) : '' }}"
                                    ><i data-feather="edit-2" class="ex-icon"></i>Edit</button>
                                    <form method="POST" action="{{ route('admin.expenses.destroy', $expense) }}" data-delete-expense="1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ex-btn-soft ex-btn-danger"><i data-feather="trash-2" class="ex-icon"></i>Hapus</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.expenses.duplicate', $expense) }}" data-duplicate-expense="1">
                                        @csrf
                                        <button type="submit" class="ex-btn-soft"><i data-feather="copy" class="ex-icon"></i>Duplikasi</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-slate-500 py-4">Belum ada data pengeluaran.</div>
                        @endforelse
                    </div>

                    @php
                        $current = $expenses->currentPage();
                        $last = $expenses->lastPage();
                        $pages = [1];
                        if ($current - 1 > 1) { $pages[] = $current - 1; }
                        if ($current !== 1 && $current !== $last) { $pages[] = $current; }
                        if ($current + 1 < $last) { $pages[] = $current + 1; }
                        if ($last > 1) { $pages[] = $last; }
                        $pages = array_values(array_unique(array_filter($pages, fn ($p) => $p >= 1 && $p <= $last)));
                        sort($pages);
                    @endphp
                    <div class="ex-pagination-custom">
                        <p class="ex-pagination-summary">
                            Menampilkan {{ number_format($expenses->firstItem() ?? 0, 0, ',', '.') }}
                            sampai {{ number_format($expenses->lastItem() ?? 0, 0, ',', '.') }}
                            dari {{ number_format($expenses->total(), 0, ',', '.') }} data
                        </p>
                        <div class="ex-pagination-pages">
                            @if($expenses->onFirstPage())
                                <span class="ex-page-btn" aria-disabled="true">&#8249;</span>
                            @else
                                <a href="{{ $expenses->previousPageUrl() }}" class="ex-page-btn" rel="prev">&#8249;</a>
                            @endif

                            @php $prevShown = null; @endphp
                            @foreach($pages as $page)
                                @if(!is_null($prevShown) && ($page - $prevShown) > 1)
                                    <span class="ex-page-dots">&hellip;</span>
                                @endif
                                @if($page === $current)
                                    <span class="ex-page-btn ex-page-btn-active">{{ $page }}</span>
                                @else
                                    <a href="{{ $expenses->url($page) }}" class="ex-page-btn">{{ $page }}</a>
                                @endif
                                @php $prevShown = $page; @endphp
                            @endforeach

                            @if($expenses->hasMorePages())
                                <a href="{{ $expenses->nextPageUrl() }}" class="ex-page-btn" rel="next">&#8250;</a>
                            @else
                                <span class="ex-page-btn" aria-disabled="true">&#8250;</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="ex-card ex-card-form">
                <div class="ex-headbar"><h3 class="ex-card-title">Tambah Pengeluaran</h3></div>
                <div class="ex-body">
                    <form id="create-expense-form" method="POST" action="{{ route('admin.expenses.store') }}" enctype="multipart/form-data" class="ex-form-shell">
                        @csrf
                        <div class="ex-form-group">
                            <h4 class="ex-form-group-title">Informasi Utama</h4>
                            <label>
                                <span class="ex-label">Kategori</span>
                                <select name="category" class="ex-select" required>
                                    @foreach(($categories ?? []) as $cat)
                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                <span class="ex-label">Judul Pengeluaran</span>
                                <input type="text" name="title" class="ex-input" required>
                            </label>
                            <div class="ex-form-grid-2 ex-form-grid-amount-wide">
                                <label>
                                    <span class="ex-label">Tanggal</span>
                                    <input type="date" name="date" class="ex-input" value="{{ now()->format('Y-m-d') }}" required>
                                </label>
                                <label>
                                    <span class="ex-label">Nominal</span>
                                    <div class="ex-money-wrap">
                                        <span class="ex-money-prefix">Rp</span>
                                        <input type="number" min="1" step="0.01" name="amount" class="ex-input ex-input-money" required>
                                    </div>
                                </label>
                            </div>
                            <label>
                                <span class="ex-label">Catatan</span>
                                <textarea name="note" class="ex-textarea" placeholder="Catatan tambahan (opsional)"></textarea>
                                <small class="text-slate-500">Catatan wajib jika nominal besar (>= Rp {{ number_format((float) ($expenseLargeThreshold ?? 1000000), 0, ',', '.') }}).</small>
                            </label>
                            <label>
                                <span class="ex-label">Lampiran Bukti (jpg/png/pdf)</span>
                                <div class="ex-file">
                                    <input type="file" name="receipt" id="create-receipt-input" accept=".jpg,.jpeg,.png,.pdf">
                                    <div class="ex-file-preview" id="create-receipt-preview">
                                        <span class="ex-file-preview-empty">Belum ada file dipilih</span>
                                    </div>
                                </div>
                            </label>
                            <p class="ex-help">Setiap pengeluaran otomatis tercatat dengan user yang login saat ini.</p>
                        </div>
                        <button type="submit" class="ex-btn w-full"><i data-feather="save" class="ex-icon"></i>Simpan Pengeluaran</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="expense-edit-modal" class="ex-modal">
        <div class="ex-modal-card">
            <div class="ex-modal-head">
                <h3 class="ex-modal-title">Edit Pengeluaran</h3>
            </div>
            <div class="ex-modal-body">
                <form id="edit-expense-form" method="POST" enctype="multipart/form-data" class="ex-form-shell">
                    @csrf
                    @method('PUT')
                    <div class="ex-form-group">
                        <h4 class="ex-form-group-title">Perbarui Data</h4>
                        <label>
                            <span class="ex-label">Kategori</span>
                            <select id="ee-category" name="category" class="ex-select" required>
                                @foreach(($categories ?? []) as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span class="ex-label">Judul Pengeluaran</span>
                            <input type="text" id="ee-title" name="title" class="ex-input" required>
                        </label>
                        <div class="ex-form-grid-2 ex-form-grid-amount-wide">
                            <label>
                                <span class="ex-label">Tanggal</span>
                                <input type="date" id="ee-date" name="date" class="ex-input" required>
                            </label>
                            <label>
                                <span class="ex-label">Nominal</span>
                                <div class="ex-money-wrap">
                                    <span class="ex-money-prefix">Rp</span>
                                    <input type="number" min="1" step="0.01" id="ee-amount" name="amount" class="ex-input ex-input-money" required>
                                </div>
                            </label>
                        </div>
                        <label>
                            <span class="ex-label">Catatan</span>
                            <textarea id="ee-note" name="note" class="ex-textarea" placeholder="Catatan tambahan (opsional)"></textarea>
                            <small class="text-slate-500">Catatan wajib jika nominal besar (>= Rp {{ number_format((float) ($expenseLargeThreshold ?? 1000000), 0, ',', '.') }}).</small>
                        </label>
                        <label>
                            <span class="ex-label">Lampiran Bukti (jpg/png/pdf)</span>
                            <div class="ex-file">
                                <input type="file" name="receipt" id="edit-receipt-input" accept=".jpg,.jpeg,.png,.pdf">
                                <div class="ex-file-preview" id="edit-receipt-preview">
                                    <span class="ex-file-preview-empty">Bukti saat ini tampil di sini</span>
                                </div>
                            </div>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remove_receipt" value="1"> Hapus lampiran saat ini
                        </label>
                    </div>
                    <div class="ex-modal-actions">
                        <button type="button" class="ex-btn-soft" onclick="closeExpenseEdit()">Batal</button>
                        <button type="submit" class="ex-btn"><i data-feather="save" class="ex-icon"></i>Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="expense-delete-modal" class="ex-modal">
        <div class="ex-modal-card">
            <div class="ex-modal-head"><h3 class="ex-modal-title">Alasan Hapus Pengeluaran</h3></div>
            <div class="ex-modal-body">
                <form id="delete-expense-form" class="ex-form-shell">
                    @csrf
                    @method('DELETE')
                    <div class="ex-form-group">
                        <label>
                            <span class="ex-label">Alasan Hapus (wajib)</span>
                            <textarea id="delete-expense-reason" class="ex-textarea" placeholder="Contoh: input ganda, salah nominal" required></textarea>
                        </label>
                    </div>
                    <div class="ex-modal-actions">
                        <button type="button" class="ex-btn-soft" onclick="closeExpenseDelete()">Batal</button>
                        <button type="submit" class="ex-btn ex-btn-danger">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="ex-toast-wrap" class="ex-toast-wrap" aria-live="polite"></div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        function showExpenseToast(message, type = 'success') {
            const wrap = document.getElementById('ex-toast-wrap');
            if (!wrap) return;
            const toast = document.createElement('div');
            toast.className = 'ex-toast';
            if (type === 'error') toast.classList.add('ex-toast-error');
            toast.textContent = message;
            wrap.appendChild(toast);
            setTimeout(() => toast.remove(), 2500);
        }

        function openExpenseEdit(expense) {
            const modal = document.getElementById('expense-edit-modal');
            const form = document.getElementById('edit-expense-form');
            if (!modal || !form || !expense) return;
            form.action = `/admin/expenses/${expense.id}`;
            document.getElementById('ee-category').value = expense.category || 'Operasional';
            document.getElementById('ee-title').value = expense.title || '';
            document.getElementById('ee-date').value = expense.date || '';
            document.getElementById('ee-amount').value = expense.amount || '';
            document.getElementById('ee-note').value = expense.note || '';
            const preview = document.getElementById('edit-receipt-preview');
            if (preview) {
                preview.innerHTML = expense.receiptUrl
                    ? `<img src="${expense.receiptUrl}" alt="Bukti pengeluaran">`
                    : '<span class="ex-file-preview-empty">Belum ada lampiran tersimpan</span>';
            }
            modal.classList.add('open');
        }

        function closeExpenseEdit() {
            const modal = document.getElementById('expense-edit-modal');
            if (!modal) return;
            modal.classList.remove('open');
        }

        let pendingDeleteUrl = '';
        function openExpenseDelete(url) {
            pendingDeleteUrl = url;
            const modal = document.getElementById('expense-delete-modal');
            if (!modal) return;
            const reasonEl = document.getElementById('delete-expense-reason');
            if (reasonEl) reasonEl.value = '';
            modal.classList.add('open');
        }
        function closeExpenseDelete() {
            const modal = document.getElementById('expense-delete-modal');
            if (!modal) return;
            modal.classList.remove('open');
            pendingDeleteUrl = '';
        }

        function wireReceiptPreview(inputId, previewId, emptyText = 'Belum ada file dipilih') {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            if (!input || !preview) return;
            input.addEventListener('change', () => {
                const file = input.files?.[0];
                if (!file) {
                    preview.innerHTML = `<span class="ex-file-preview-empty">${emptyText}</span>`;
                    return;
                }
                if (file.type.startsWith('image/')) {
                    const url = URL.createObjectURL(file);
                    preview.innerHTML = `<img src="${url}" alt="Preview lampiran">`;
                } else {
                    preview.innerHTML = `<span class="ex-file-preview-empty">${file.name}</span>`;
                }
            });
        }
        wireReceiptPreview('create-receipt-input', 'create-receipt-preview');
        wireReceiptPreview('edit-receipt-input', 'edit-receipt-preview', 'Bukti saat ini tampil di sini');

        document.querySelectorAll('[data-edit-expense="1"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                openExpenseEdit({
                    id: Number(btn.dataset.id || 0),
                    title: String(btn.dataset.title || ''),
                    amount: Number(btn.dataset.amount || 0),
                    date: String(btn.dataset.date || ''),
                    category: String(btn.dataset.category || 'Operasional'),
                    note: String(btn.dataset.note || ''),
                    receiptUrl: String(btn.dataset.receiptUrl || ''),
                });
            });
        });

        document.getElementById('create-expense-form')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: new FormData(form),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    if (data?.errors && typeof data.errors === 'object') {
                        const firstError = Object.values(data.errors)?.[0];
                        const text = Array.isArray(firstError) ? firstError[0] : 'Validasi gagal.';
                        throw new Error(text || 'Validasi gagal.');
                    }
                    throw new Error(data?.message || 'Gagal menambah pengeluaran.');
                }
                showExpenseToast(data?.message || 'Pengeluaran berhasil ditambahkan.');
                form.reset();
                setTimeout(() => window.location.reload(), 380);
            } catch (error) {
                showExpenseToast(error.message || 'Terjadi kesalahan saat menambah pengeluaran.', 'error');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });

        document.getElementById('edit-expense-form')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: new FormData(form),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    if (data?.errors && typeof data.errors === 'object') {
                        const firstError = Object.values(data.errors)?.[0];
                        const text = Array.isArray(firstError) ? firstError[0] : 'Validasi gagal.';
                        throw new Error(text || 'Validasi gagal.');
                    }
                    throw new Error(data?.message || 'Gagal memperbarui pengeluaran.');
                }
                closeExpenseEdit();
                showExpenseToast(data?.message || 'Pengeluaran berhasil diperbarui.');
                setTimeout(() => window.location.reload(), 350);
            } catch (error) {
                showExpenseToast(error.message || 'Terjadi kesalahan saat mengubah pengeluaran.', 'error');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });

        document.querySelectorAll('form[data-delete-expense="1"]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                openExpenseDelete(form.action);
            });
        });

        document.getElementById('delete-expense-form')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const reasonEl = document.getElementById('delete-expense-reason');
            const reason = String(reasonEl?.value || '').trim();
            if (reason.length < 5) {
                showExpenseToast('Alasan hapus minimal 5 karakter.', 'error');
                return;
            }
            const submitBtn = event.currentTarget.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            try {
                const payload = new FormData();
                payload.append('_token', csrfToken);
                payload.append('_method', 'DELETE');
                payload.append('delete_reason', reason);
                const response = await fetch(pendingDeleteUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: payload,
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(data?.message || 'Gagal menghapus pengeluaran.');
                }
                closeExpenseDelete();
                showExpenseToast(data?.message || 'Pengeluaran berhasil dihapus.');
                setTimeout(() => window.location.reload(), 320);
            } catch (error) {
                showExpenseToast(error.message || 'Terjadi kesalahan saat menghapus pengeluaran.', 'error');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });

        document.querySelectorAll('form[data-inline-expense="1"]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.disabled = true;
                try {
                    const payload = new FormData(form);
                    payload.append('_token', csrfToken);
                    payload.append('_method', 'PUT');
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: payload,
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(data?.message || 'Gagal menyimpan perubahan cepat.');
                    }
                    showExpenseToast(data?.message || 'Perubahan cepat berhasil disimpan.');
                    setTimeout(() => window.location.reload(), 280);
                } catch (error) {
                    showExpenseToast(error.message || 'Terjadi kesalahan saat menyimpan perubahan cepat.', 'error');
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                }
            });
        });

        document.querySelectorAll('form[data-duplicate-expense="1"]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.disabled = true;
                try {
                    const payload = new FormData();
                    payload.append('_token', csrfToken);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: payload,
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(data?.message || 'Gagal menduplikasi pengeluaran.');
                    }
                    showExpenseToast(data?.message || 'Pengeluaran berhasil diduplikasi.');
                    setTimeout(() => window.location.reload(), 320);
                } catch (error) {
                    showExpenseToast(error.message || 'Terjadi kesalahan saat duplikasi.', 'error');
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                }
            });
        });

        if (window.feather && typeof window.feather.replace === 'function') {
            window.feather.replace();
        }
    </script>
</x-app-layout>
