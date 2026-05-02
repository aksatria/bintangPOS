<x-app-layout>
    <x-slot name="header">
        <div class="so-page-head">
            <div>
                <p class="so-kicker">Inventori</p>
                <h2 class="so-title">Stock Opname</h2>
                <p class="so-subtitle">Kelola sesi opname dengan tampilan ringkas dan fokus audit.</p>
            </div>
        </div>
    </x-slot>

    <style>
        .so-page { max-width: 1260px; margin: 0 auto; }
        .so-page-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; }
        .so-kicker { margin: 0; font-size: .74rem; letter-spacing: .08em; text-transform: uppercase; color: #64748b; font-weight: 700; }
        .so-title { margin: .3rem 0 0; font-size: 2rem; line-height: 1.08; color: #0f172a; font-weight: 800; }
        .so-subtitle { margin: .4rem 0 0; color: #64748b; font-size: .9rem; }

        .so-grid-top { display: grid; gap: 1rem; grid-template-columns: 1fr; }
        @media (min-width: 1024px) { .so-grid-top { grid-template-columns: 1.35fr 1fr; } }

        .so-card { background: #fff; border: 1px solid #dbe4f0; border-radius: 14px; box-shadow: 0 10px 24px rgba(15, 23, 42, .04); }
        .so-card-head { padding: .95rem 1rem; border-bottom: 1px solid #e7eef7; display: flex; align-items: center; justify-content: space-between; gap: .7rem; }
        .so-card-title { margin: 0; font-size: .82rem; letter-spacing: .08em; text-transform: uppercase; color: #334155; font-weight: 800; }
        .so-card-body { padding: 1rem; }

        .so-open-pill { display: inline-flex; align-items: center; padding: .26rem .62rem; border-radius: 999px; font-size: .72rem; font-weight: 700; color: #1d4ed8; border: 1px solid #bfdbfe; background: #eff6ff; }

        .so-form-grid { display: grid; gap: .75rem; grid-template-columns: 1fr; }
        .so-filter-grid { display: grid; gap: .75rem; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        @media (min-width: 900px) { .so-filter-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); } }

        .so-btn { min-height: 2.45rem; padding: 0 .9rem; border-radius: 10px; border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: .82rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
        .so-btn:hover { background: #f8fafc; }
        .so-btn-primary { background: #2563eb; border-color: #1d4ed8; color: #fff; }
        .so-btn-primary:hover { background: #1d4ed8; }

        .so-list { display: grid; gap: .75rem; }
        .so-item { border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; padding: .9rem 1rem; display: grid; gap: .8rem; grid-template-columns: 1fr; }
        @media (min-width: 1100px) { .so-item { grid-template-columns: 1.8fr 1fr 1.3fr .9fr auto; align-items: center; } }
        .so-code { font-weight: 700; color: #0f172a; letter-spacing: .01em; word-break: break-word; }
        .so-meta { font-size: .8rem; color: #475569; margin-top: .25rem; }
        .so-chip { display: inline-flex; align-items: center; padding: .22rem .58rem; border-radius: 999px; font-size: .69rem; font-weight: 800; letter-spacing: .07em; }
        .so-chip-open { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .so-chip-posted { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .so-chip-origin { margin-left: .35rem; background: #f8fafc; border: 1px solid #cbd5e1; color: #334155; }
        .so-chip-origin-dup { margin-left: .35rem; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; }
        .so-chip-near { margin-left: .35rem; background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; }
        .so-summary { font-size: .82rem; color: #334155; line-height: 1.35; }
        .so-summary strong { color: #0f172a; font-weight: 700; }
        .so-empty { text-align: center; color: #64748b; padding: 1.2rem; }
        .so-quick-chips { display: flex; flex-wrap: wrap; gap: .5rem; }
        .so-quick-chip { display: inline-flex; align-items: center; padding: .4rem .7rem; border-radius: 999px; border: 1px solid #cbd5e1; color: #334155; background: #fff; font-size: .78rem; font-weight: 700; }
        .so-quick-chip:hover { background: #f8fafc; }
        .so-quick-chip-active { border-color: #1d4ed8; background: #eff6ff; color: #1d4ed8; }
        .so-metric-chips { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .2rem; }
        .so-metric-chip { display: inline-flex; align-items: center; gap: .32rem; padding: .24rem .58rem; border-radius: 999px; border: 1px solid #dbe4f0; background: #f8fafc; color: #334155; font-size: .74rem; font-weight: 700; }
        .so-metric-chip strong { font-weight: 800; color: #0f172a; }
        .so-metric-chip-item { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
        .so-metric-chip-adjusted { background: #ecfdf5; border-color: #a7f3d0; color: #047857; }
        .so-metric-chip-diff { background: #fff7ed; border-color: #fed7aa; color: #c2410c; }
    </style>

    <div class="so-page page-shell space-y-4">
        @php
            $extractDuplicateSource = function (?string $note): ?string {
                $note = trim((string) $note);
                if ($note === '' || !str_starts_with($note, 'Duplikasi dari ')) {
                    return null;
                }
                return trim(substr($note, strlen('Duplikasi dari '))) ?: null;
            };
        @endphp

        @if($errors->any())
            <div class="so-card" style="border-color:#fecaca;background:#fff1f2;">
                <div class="so-card-body">
                    <p class="text-sm font-semibold text-rose-700">Tidak bisa memproses:</p>
                    <ul class="mt-1 text-sm text-rose-700 list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="so-grid-top">
            <form method="POST" action="{{ route('stock-opnames.store') }}" class="so-card">
                @csrf
                <div class="so-card-head">
                    <h3 class="so-card-title">Buat Sesi Opname Baru</h3>
                </div>
                <div class="so-card-body so-form-grid">
                    <p class="text-sm text-slate-600">Snapshot stok sistem saat ini akan disalin ke sesi baru.</p>
                    <div>
                        <label class="label-ui">Catatan Awal (opsional)</label>
                        <textarea name="note" rows="2" class="input-ui" placeholder="Contoh: opname rutin akhir minggu"></textarea>
                    </div>
                    <button class="so-btn so-btn-primary">Mulai Opname</button>
                </div>
            </form>

            <div class="so-card">
                <div class="so-card-head">
                    <h3 class="so-card-title">Sesi Aktif</h3>
                    @if($openSession)
                        <span class="so-open-pill">OPEN</span>
                    @endif
                </div>
                <div class="so-card-body space-y-3">
                    @if($openSession)
                        <div class="text-sm text-slate-700">
                            <div class="font-semibold text-slate-900">{{ $openSession->code }}</div>
                            <div class="mt-1">{{ number_format((int) $openSession->total_items, 0, ',', '.') }} item - {{ $openSession->created_at?->format('d/m/Y H:i') }}</div>
                        </div>
                        <a href="{{ route('stock-opnames.show', $openSession) }}" class="so-btn so-btn-primary">Lanjutkan Sesi OPEN</a>
                    @else
                        <p class="text-sm text-slate-600">Belum ada sesi OPEN aktif.</p>
                    @endif
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('stock-opnames.index') }}" class="so-card">
            <div class="so-card-head">
                <h3 class="so-card-title">Filter Riwayat</h3>
            </div>
            <div class="so-card-body so-filter-grid">
                <div>
                    <label class="label-ui">Status</label>
                    <select name="status" class="input-ui">
                        <option value="">Semua</option>
                        <option value="open" @selected(($filters['status'] ?? '') === 'open')>OPEN</option>
                        <option value="posted" @selected(($filters['status'] ?? '') === 'posted')>POSTED</option>
                    </select>
                </div>
                <div>
                    <label class="label-ui">Petugas</label>
                    <select name="user_id" class="input-ui">
                        <option value="0">Semua</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected((int) ($filters['user_id'] ?? 0) === (int) $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label-ui">Dari Tanggal</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="input-ui">
                </div>
                <div>
                    <label class="label-ui">Sampai Tanggal</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="input-ui">
                </div>
                <div>
                    <label class="label-ui">Urutkan</label>
                    <select name="sort" class="input-ui">
                        <option value="latest" @selected(($filters['sort'] ?? 'latest') === 'latest')>Terbaru</option>
                        <option value="progress_desc" @selected(($filters['sort'] ?? '') === 'progress_desc')>Progress Tertinggi</option>
                        <option value="diff_desc" @selected(($filters['sort'] ?? '') === 'diff_desc')>Selisih Terbesar</option>
                    </select>
                </div>
                <button class="so-btn so-btn-primary">Terapkan</button>
            </div>
            <div class="px-4 pb-4">
                <div class="so-quick-chips">
                    <a href="{{ route('stock-opnames.index', array_merge(request()->except('page'), ['quick' => ''])) }}" class="so-quick-chip {{ ($filters['quick'] ?? '') === '' ? 'so-quick-chip-active' : '' }}">Semua</a>
                    <a href="{{ route('stock-opnames.index', array_merge(request()->except('page'), ['quick' => 'open'])) }}" class="so-quick-chip {{ ($filters['quick'] ?? '') === 'open' ? 'so-quick-chip-active' : '' }}">OPEN saja</a>
                    <a href="{{ route('stock-opnames.index', array_merge(request()->except('page'), ['quick' => 'posted'])) }}" class="so-quick-chip {{ ($filters['quick'] ?? '') === 'posted' ? 'so-quick-chip-active' : '' }}">POSTED saja</a>
                    <a href="{{ route('stock-opnames.index', array_merge(request()->except('page'), ['quick' => 'incomplete'])) }}" class="so-quick-chip {{ ($filters['quick'] ?? '') === 'incomplete' ? 'so-quick-chip-active' : '' }}">Belum 100%</a>
                </div>
            </div>
        </form>

        <div class="so-card">
            <div class="so-card-head">
                <h3 class="so-card-title">Riwayat Sesi Opname</h3>
            </div>
            <div class="so-card-body">
                <div class="so-list">
                    @forelse($sessions as $s)
                        @php($duplicateSource = $extractDuplicateSource($s->note))
                        <div class="so-item">
                            <div>
                                <div class="so-code">{{ $s->code }}</div>
                                <div class="mt-2">
                                    <span class="so-chip {{ $s->status === 'posted' ? 'so-chip-posted' : 'so-chip-open' }}">{{ strtoupper($s->status) }}</span>
                                    @if($duplicateSource)
                                        <span class="so-chip so-chip-origin-dup">Duplikasi</span>
                                    @else
                                        <span class="so-chip so-chip-origin">Sesi Asli</span>
                                    @endif
                                </div>
                                @if($duplicateSource)
                                    <div class="so-meta">Asal: {{ $duplicateSource }}</div>
                                @endif
                            </div>
                            <div class="so-summary"><strong>Petugas:</strong> {{ $s->user?->name ?? '-' }}</div>
                            <div class="so-summary">
                                <div class="so-metric-chips">
                                    <span class="so-metric-chip so-metric-chip-item">Item <strong>{{ number_format((int) $s->total_items, 0, ',', '.') }}</strong></span>
                                    <span class="so-metric-chip so-metric-chip-adjusted">Adjusted <strong>{{ number_format((int) $s->adjusted_items, 0, ',', '.') }}</strong></span>
                                    <span class="so-metric-chip so-metric-chip-diff">Selisih <strong>{{ number_format((int) $s->total_difference, 0, ',', '.') }}</strong></span>
                                </div>
                                @if($s->status === 'open')
                                    @php($filled = (int) ($s->filled_items_count ?? 0))
                                    @php($total = max((int) $s->total_items, 0))
                                    @php($pct = $total > 0 ? (int) round(($filled / $total) * 100) : 0)
                                    <div><strong>Progress:</strong> {{ number_format($filled, 0, ',', '.') }}/{{ number_format($total, 0, ',', '.') }} ({{ $pct }}%)</div>
                                    @if($pct >= 90 && $pct < 100)
                                        <div class="mt-1"><span class="so-chip so-chip-near">Hampir Selesai</span></div>
                                    @endif
                                @endif
                            </div>
                            <div class="so-summary">{{ $s->created_at?->format('d/m/Y H:i') }}</div>
                            <div class="flex flex-wrap gap-2">
                                <a class="so-btn so-btn-primary" href="{{ route('stock-opnames.show', $s) }}">Detail</a>
                                @if($s->status === 'posted')
                                    <form method="POST" action="{{ route('stock-opnames.duplicate', $s) }}">
                                        @csrf
                                        <button type="submit" class="so-btn">Duplikasi</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="so-empty">Belum ada sesi opname.</div>
                    @endforelse
                </div>
                <div class="pt-4">{{ $sessions->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>

