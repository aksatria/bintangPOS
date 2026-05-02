<x-app-layout>
    <x-slot name="header">
        <div class="so-page-head">
            <div>
                <p class="so-kicker">Inventori</p>
                <h2 class="so-title">Detail Opname {{ $session->code }}</h2>
            </div>
            <div class="so-toolbar">
                @if($session->status === 'posted')
                    <form method="POST" action="{{ route('stock-opnames.duplicate', $session) }}">
                        @csrf
                        <button type="submit" class="so-btn">Duplikasi</button>
                    </form>
                @endif
                @if($session->status === 'open')
                    <a href="{{ route('stock-opnames.template.csv', $session) }}" class="so-btn">Template CSV</a>
                    <form method="POST" action="{{ route('stock-opnames.import.csv', $session) }}" enctype="multipart/form-data" class="flex items-center gap-2 flex-wrap">
                        @csrf
                        <input type="file" name="csv_file" accept=".csv,text/csv" class="text-xs text-slate-600 max-w-[180px]" required>
                        <button type="submit" name="preview_only" value="1" class="so-btn">Preview CSV</button>
                        <button type="submit" name="preview_only" value="0" class="so-btn">Terapkan Import</button>
                    </form>
                @endif
                <a href="{{ route('stock-opnames.export.csv', $session) }}" class="so-btn">CSV</a>
                <a href="{{ route('stock-opnames.export.csv', [$session, 'only_diff' => 1]) }}" class="so-btn">CSV Selisih</a>
                <a href="{{ route('stock-opnames.export.pdf', $session) }}" class="so-btn" target="_blank" rel="noopener">PDF</a>
                <a href="{{ route('stock-opnames.export.pdf', [$session, 'only_diff' => 1]) }}" class="so-btn" target="_blank" rel="noopener">PDF Selisih</a>
                <a href="{{ route('stock-opnames.index') }}" class="so-btn so-btn-primary">Kembali</a>
            </div>
        </div>
    </x-slot>

    <style>
        .so-page { max-width: 1260px; margin: 0 auto; }
        .so-page-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; flex-wrap: wrap; }
        .so-kicker { margin: 0; font-size: .74rem; letter-spacing: .08em; text-transform: uppercase; color: #64748b; font-weight: 700; }
        .so-title { margin: .25rem 0 0; font-size: 2rem; line-height: 1.06; color: #0f172a; font-weight: 800; }
        .so-toolbar { display: flex; flex-wrap: wrap; gap: .45rem; }

        .so-btn { min-height: 2.35rem; padding: 0 .85rem; border-radius: 10px; border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: .8rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
        .so-btn:hover { background: #f8fafc; }
        .so-btn-primary { background: #2563eb; border-color: #1d4ed8; color: #fff; }
        .so-btn-primary:hover { background: #1d4ed8; }

        .so-chip { display: inline-flex; align-items: center; padding: .24rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 800; letter-spacing: .06em; border: 1px solid #d1dae8; color: #334155; background: #f8fafc; }
        .so-chip-dup { border-color: #bfdbfe; color: #1d4ed8; background: #eff6ff; }

        .so-kpi-grid { display: grid; gap: .75rem; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        @media (min-width: 900px) { .so-kpi-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        .so-kpi { border: 1px solid #dbe4f0; border-radius: 14px; background: #fff; padding: .85rem .95rem; box-shadow: 0 10px 22px rgba(15, 23, 42, .04); }
        .so-kpi-label { margin: 0; font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; color: #64748b; font-weight: 700; }
        .so-kpi-value { margin: .2rem 0 0; font-size: 1.48rem; font-weight: 800; color: #0f172a; line-height: 1.05; }
        .so-kpi-item { background: #eff6ff; border-color: #bfdbfe; }
        .so-kpi-adjusted { background: #ecfdf5; border-color: #a7f3d0; }
        .so-kpi-diff { background: #fff7ed; border-color: #fed7aa; }

        .so-card { background: #fff; border: 1px solid #dbe4f0; border-radius: 14px; box-shadow: 0 10px 24px rgba(15, 23, 42, .04); overflow: hidden; }
        .so-card-head { padding: .9rem 1rem; border-bottom: 1px solid #e8eef7; display: flex; align-items: center; justify-content: space-between; gap: .6rem; }
        .so-card-title { margin: 0; font-size: .9rem; color: #1e293b; font-weight: 700; }
        .so-card-body { padding: 1rem; }

        .so-filter-grid { display: grid; gap: .75rem; grid-template-columns: 1fr; }
        @media (min-width: 900px) { .so-filter-grid { grid-template-columns: 1fr 220px auto; align-items: end; } }

        .so-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .so-table thead th { padding: .95rem 1rem; background: #f8fafc; border-bottom: 1px solid #dbe4f0; font-size: .74rem; letter-spacing: .08em; text-transform: uppercase; color: #64748b; text-align: left; }
        .so-table td { padding: .95rem 1rem; border-bottom: 1px solid #edf2f8; color: #1f2937; }
        .so-table tbody tr:last-child td { border-bottom: 0; }
        .so-table tbody tr:hover td { background: #f9fbff; }
        .so-product { font-weight: 600; color: #0f172a; }

        .so-log-item { border: 1px solid #e2e8f0; border-radius: 12px; padding: .8rem .85rem; }
        .so-log-title { font-size: .82rem; letter-spacing: .07em; text-transform: uppercase; font-weight: 800; color: #334155; }
        .so-log-meta { margin-top: .2rem; font-size: .75rem; color: #64748b; }
        .so-log-expand { margin-top: .45rem; border: 1px solid #d6dfec; background: #f8fbff; border-radius: 10px; padding: .38rem .55rem; font-size: .76rem; color: #334155; cursor: pointer; font-weight: 700; }
        .so-log-table { width: 100%; border-collapse: collapse; margin-top: .5rem; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
        .so-log-table th, .so-log-table td { padding: .48rem .52rem; border-bottom: 1px solid #eef2f7; font-size: .74rem; text-align: left; }
        .so-log-table th { background: #f8fafc; color: #64748b; text-transform: uppercase; letter-spacing: .07em; }
        .so-log-table tbody tr:last-child td { border-bottom: 0; }
        .so-row-diff td { background: #fff7ed !important; }
        .so-row-outlier td { background: #fff1f2 !important; }
        .so-outlier-badge { display: inline-flex; align-items: center; margin-left: .45rem; padding: .12rem .45rem; border-radius: 999px; font-size: .66rem; font-weight: 800; letter-spacing: .06em; border: 1px solid #fecaca; background: #ffe4e6; color: #be123c; }
        .so-progress { display: inline-flex; gap: .4rem; align-items: center; font-size: .76rem; color: #334155; background: #f8fafc; border: 1px solid #dbe4f0; padding: .28rem .58rem; border-radius: 999px; }
        .so-warning-box { margin-top: .6rem; border: 1px solid #fecaca; background: #fff1f2; color: #9f1239; border-radius: 10px; padding: .55rem .65rem; font-size: .8rem; }
        .so-draft-badge { display: inline-flex; align-items: center; padding: .2rem .52rem; border-radius: 999px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: .72rem; font-weight: 700; }
        .so-draft-meta { font-size: .74rem; color: #64748b; }
        .so-live-summary { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .65rem; }
        .so-live-pill { display: inline-flex; align-items: center; padding: .22rem .56rem; border-radius: 999px; font-size: .72rem; font-weight: 700; border: 1px solid #dbe4f0; background: #f8fafc; color: #334155; }
        .so-live-pill-up { border-color: #bbf7d0; background: #ecfdf5; color: #047857; }
        .so-live-pill-down { border-color: #fed7aa; background: #fff7ed; color: #c2410c; }
        .so-live-pill-eq { border-color: #cbd5e1; background: #f8fafc; color: #334155; }
        .so-live-pill-empty { border-color: #fecaca; background: #fff1f2; color: #b91c1c; font-weight: 800; }
        .so-mini-btn { min-height: 2rem; padding: 0 .62rem; border: 1px solid #cbd5e1; background: #fff; color: #334155; border-radius: 8px; font-size: .72rem; font-weight: 700; }
        .so-mini-btn:hover { background: #f8fafc; }
        .so-sticky-footer { position: sticky; bottom: 0; z-index: 20; background: #fff; box-shadow: 0 -6px 16px rgba(15, 23, 42, .06); }
        .so-toggle-diff { display: inline-flex; align-items: center; gap: .45rem; font-size: .76rem; color: #334155; }
        .so-toggle-diff input { width: 1rem; height: 1rem; }
        .so-preview-alert { border: 1px solid #fecaca; background: #fff1f2; color: #9f1239; border-radius: 10px; padding: .65rem .75rem; font-size: .82rem; font-weight: 700; }
        .so-preview-ok { border: 1px solid #bbf7d0; background: #ecfdf5; color: #047857; border-radius: 10px; padding: .65rem .75rem; font-size: .82rem; font-weight: 700; }
    </style>

    @php
        $duplicateSource = null;
        $sessionNote = trim((string) $session->note);
        if ($sessionNote !== '' && str_starts_with($sessionNote, 'Duplikasi dari ')) {
            $duplicateSource = trim(substr($sessionNote, strlen('Duplikasi dari '))) ?: null;
        }
    @endphp

    <div class="so-page page-shell space-y-4" x-data="stockOpnameDetailState({ sessionId: {{ (int) $session->id }}, totalItems: {{ (int) $session->total_items }}, status: '{{ $session->status }}', outlierThreshold: {{ (int) ($outlierThreshold ?? 10) }} })" x-init="init()">
        @if($errors->any())
            <div class="so-card" style="border-color:#fecaca;background:#fff1f2;">
                <div class="so-card-body">
                    <p class="text-sm font-semibold text-rose-700">Posting gagal, periksa data berikut:</p>
                    <ul class="mt-1 text-sm text-rose-700 list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @php($importPreview = session('stock_opname_import_preview'))
        @if(is_array($importPreview))
            <div class="so-card">
                <div class="so-card-head"><h3 class="so-card-title">Preview Import CSV</h3></div>
                <div class="so-card-body text-sm text-slate-700 space-y-2">
                    @php($invalidCount = (int) ($importPreview['invalid_counted'] ?? 0))
                    @php($unmatchedCount = (int) ($importPreview['unmatched_rows'] ?? 0))
                    @if($invalidCount > 0 || $unmatchedCount > 0)
                        <div class="so-preview-alert">
                            Warning: ditemukan {{ number_format($invalidCount, 0, ',', '.') }} baris invalid dan {{ number_format($unmatchedCount, 0, ',', '.') }} baris tidak match. Perbaiki CSV dulu sebelum klik \"Terapkan Import\".
                        </div>
                    @else
                        <div class="so-preview-ok">
                            CSV terlihat valid. Anda bisa lanjut klik \"Terapkan Import\".
                        </div>
                    @endif
                    <div>Total baris dibaca: <strong>{{ number_format((int) ($importPreview['rows_read'] ?? 0), 0, ',', '.') }}</strong></div>
                    <div>Baris valid: <strong>{{ number_format((int) ($importPreview['valid_rows'] ?? 0), 0, ',', '.') }}</strong></div>
                    <div>Baris counted_stock invalid/kosong: <strong>{{ number_format((int) ($importPreview['invalid_counted'] ?? 0), 0, ',', '.') }}</strong></div>
                    <div>Baris tidak match item_id/sku sesi ini: <strong>{{ number_format((int) ($importPreview['unmatched_rows'] ?? 0), 0, ',', '.') }}</strong></div>
                    @if(!empty($importPreview['preview_changes']) && is_array($importPreview['preview_changes']))
                        <div class="overflow-x-auto">
                            <table class="so-log-table">
                                <thead>
                                    <tr>
                                        <th>Item ID</th>
                                        <th>SKU</th>
                                        <th>Sistem</th>
                                        <th>Fisik</th>
                                        <th>Selisih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($importPreview['preview_changes'] as $row)
                                        <tr>
                                            <td>{{ (int) ($row['item_id'] ?? 0) }}</td>
                                            <td>{{ (string) ($row['sku'] ?? '-') }}</td>
                                            <td>{{ number_format((int) ($row['system_stock'] ?? 0), 0, ',', '.') }}</td>
                                            <td>{{ number_format((int) ($row['counted_stock'] ?? 0), 0, ',', '.') }}</td>
                                            <td>{{ number_format((int) ($row['difference'] ?? 0), 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="flex items-center gap-2">
            @if($duplicateSource)
                <span class="so-chip so-chip-dup">Sesi Duplikasi</span>
                <span class="text-sm text-slate-600">Sumber: {{ $duplicateSource }}</span>
            @else
                <span class="so-chip">Sesi Asli</span>
            @endif
        </div>

        <div class="so-kpi-grid">
            <div class="so-kpi"><p class="so-kpi-label">Status</p><p class="so-kpi-value">{{ strtoupper($session->status) }}</p></div>
            <div class="so-kpi so-kpi-item"><p class="so-kpi-label">Total Item</p><p class="so-kpi-value">{{ number_format((int) $session->total_items, 0, ',', '.') }}</p></div>
            <div class="so-kpi so-kpi-adjusted"><p class="so-kpi-label">Adjusted</p><p class="so-kpi-value">{{ number_format((int) $session->adjusted_items, 0, ',', '.') }}</p></div>
            <div class="so-kpi so-kpi-diff"><p class="so-kpi-label">Total Selisih</p><p class="so-kpi-value">{{ number_format((int) $session->total_difference, 0, ',', '.') }}</p></div>
        </div>

        <form method="GET" action="{{ route('stock-opnames.show', $session) }}" class="so-card">
            <div class="so-card-head"><h3 class="so-card-title">Filter Item</h3></div>
            <div class="so-card-body so-filter-grid">
                <div>
                    <label class="label-ui">Cari Produk/SKU</label>
                    <input type="text" name="q" value="{{ $q }}" class="input-ui" placeholder="Nama produk / SKU">
                </div>
                <div>
                    <label class="label-ui">Status Input</label>
                    <select name="fill" class="input-ui">
                        <option value="all" @selected($fill==='all')>Semua</option>
                        <option value="filled" @selected($fill==='filled')>Sudah Diisi</option>
                        <option value="empty" @selected($fill==='empty')>Belum Diisi</option>
                        <option value="diff" @selected($fill==='diff')>Ada Selisih</option>
                    </select>
                </div>
                <button class="so-btn so-btn-primary">Terapkan</button>
            </div>
        </form>

        <form id="stock-opname-post-form" method="POST" action="{{ route('stock-opnames.post', $session) }}" class="so-card" @submit="isPosting = true; clearDraft()">
            @csrf
            <div class="so-card-head">
                <h3 class="so-card-title">Input Stok Fisik</h3>
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="so-progress" x-text="progressLabel"></div>
                    <span class="so-live-pill so-live-pill-empty" x-text="`Belum Diisi: ${emptyCount}`"></span>
                    <template x-if="draftSaved">
                        <span class="so-draft-badge">Draft tersimpan</span>
                    </template>
                    <span class="so-draft-meta" x-text="draftSavedAtLabel"></span>
                </div>
                <label class="so-toggle-diff">
                    <input type="checkbox" x-model="showOnlyDiff" @change="applyDiffFilter()">
                    Hanya tampilkan item selisih
                </label>
                @if($session->status === 'open')
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="so-btn" @click="fillAllFromSystem()">Isi Semua = Stok Sistem</button>
                        <button type="button" class="so-btn" @click="fillEmptyWithZero()">Isi Kosong = 0</button>
                        <button type="button" class="so-btn" @click="clearAllInputs()">Kosongkan Semua</button>
                    </div>
                @endif
            </div>
            <div class="px-4 pt-3">
                <div class="so-live-summary">
                    <span class="so-live-pill so-live-pill-up" x-text="`Naik: ${liveUp}`"></span>
                    <span class="so-live-pill so-live-pill-down" x-text="`Turun: ${liveDown}`"></span>
                    <span class="so-live-pill so-live-pill-eq" x-text="`Tetap: ${liveEqual}`"></span>
                    <span class="so-live-pill" x-text="`Outlier >= ${outlierThreshold}`"></span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="so-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>SKU</th>
                            <th>Stok Sistem</th>
                            <th>Stok Fisik</th>
                            <th>Selisih</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $idx => $item)
                            <tr data-opname-row="1" data-system-stock="{{ (int) $item->system_stock }}" data-original-diff="{{ (int) ($item->difference ?? 0) }}">
                                <td class="so-product">
                                    {{ $item->product?->name ?? '-' }}
                                    <span class="so-outlier-badge hidden" data-outlier-badge>Outlier</span>
                                </td>
                                <td>{{ $item->product?->sku ?? '-' }}</td>
                                <td>{{ number_format((int) $item->system_stock, 0, ',', '.') }}</td>
                                <td>
                                    <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $item->id }}">
                                    <input type="number" min="0" step="1" name="items[{{ $idx }}][counted_stock]" value="{{ $item->counted_stock }}" class="input-ui" data-opname-input="1" data-item-id="{{ $item->id }}" @input="handleInputChange()">
                                </td>
                                <td>{{ $item->difference !== null ? number_format((int) $item->difference, 0, ',', '.') : '-' }}</td>
                                <td>
                                    <button type="button" class="so-mini-btn" @click="resetRowToSystem($event)">Reset</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-slate-500 py-8">Tidak ada item.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="so-card-body so-sticky-footer grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3 items-end" style="border-top:1px solid #e8eef7;">
                <div>
                    <label class="label-ui">Catatan Posting (opsional)</label>
                    <textarea name="posted_note" rows="2" class="input-ui" placeholder="Contoh: penyesuaian karena barang rusak/expired" data-posted-note @input="saveDraft()">{{ $session->posted_note }}</textarea>
                    @if($requireManagerApproval)
                        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                            <div>
                                <label class="label-ui">Email Manager</label>
                                <input type="email" name="manager_approval_email" class="input-ui" placeholder="manager@domain.com">
                            </div>
                            <div>
                                <label class="label-ui">Password Manager</label>
                                <input type="password" name="manager_approval_password" class="input-ui" placeholder="Password approval">
                            </div>
                        </div>
                    @endif
                </div>
                @if($session->status === 'open')
                    <div class="flex items-center gap-2">
                        <button type="button" class="so-btn" @click="resetDraft()">Reset Draft</button>
                        <button type="button" class="so-btn so-btn-primary" :disabled="isPosting" @click="openPostConfirm()">Posting Opname</button>
                    </div>
                @else
                    <span class="text-xs text-slate-500">Sesi sudah diposting pada {{ $session->posted_at?->format('d/m/Y H:i') }}</span>
                @endif
            </div>
        </form>

        <div x-show="showPostConfirm" x-transition.opacity x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" role="dialog" aria-modal="true">
            <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
                <h3 class="text-base font-semibold text-slate-900">Konfirmasi Posting Opname</h3>
                <p class="mt-2 text-sm text-slate-600">Stok sistem akan disesuaikan berdasarkan angka stok fisik yang Anda input.</p>
                <template x-if="guardrailWarning">
                    <div class="so-warning-box" x-text="guardrailWarning"></div>
                </template>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <button type="button" class="so-btn" @click="showPostConfirm = false">Batal</button>
                    <button type="submit" form="stock-opname-post-form" class="so-btn so-btn-primary" :disabled="isPosting">Ya, Posting</button>
                </div>
            </div>
        </div>

        <div x-show="showDraftPrompt" x-transition.opacity x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" role="dialog" aria-modal="true">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
                <h3 class="text-base font-semibold text-slate-900">Draft Ditemukan</h3>
                <p class="mt-2 text-sm text-slate-600">Ada draft input sebelumnya untuk sesi ini. Mau dipulihkan?</p>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <button type="button" class="so-btn" @click="discardDraftAndContinue()">Abaikan Draft</button>
                    <button type="button" class="so-btn so-btn-primary" @click="restoreDraftAndContinue()">Restore Draft</button>
                </div>
            </div>
        </div>

        <div class="so-card">
            <div class="so-card-head"><h3 class="so-card-title">Riwayat Perubahan Opname</h3></div>
            <div class="so-card-body space-y-2">
                @forelse($adjustLogs as $log)
                    <div class="so-log-item">
                        <div class="so-log-title">{{ strtoupper(str_replace('_', ' ', $log->action)) }}</div>
                        <div class="so-log-meta">{{ $log->created_at?->format('d/m/Y H:i:s') }} oleh {{ $log->user?->name ?? '-' }}</div>
                        @if($log->action === 'stock_opname_posted')
                            <div class="text-sm text-slate-700 mt-2">Adjusted: {{ (int) data_get($log->context, 'adjusted_items', 0) }} item | Selisih total: {{ number_format((int) data_get($log->context, 'total_difference', 0), 0, ',', '.') }}</div>
                            @php($adjustments = data_get($log->context, 'adjustments', []))
                            @if(is_array($adjustments) && count($adjustments) > 0)
                                <details class="mt-2">
                                    <summary class="so-log-expand">Lihat detail item berubah ({{ count($adjustments) }})</summary>
                                    <table class="so-log-table">
                                        <thead>
                                            <tr>
                                                <th>Item ID</th>
                                                <th>Produk</th>
                                                <th>SKU</th>
                                                <th>Sistem</th>
                                                <th>Fisik</th>
                                                <th>Selisih</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($adjustments as $adj)
                                                <tr>
                                                    <td>{{ (int) data_get($adj, 'item_id', 0) }}</td>
                                                    <td>{{ data_get($adj, 'product_name', '-') }}</td>
                                                    <td>{{ data_get($adj, 'sku', '-') }}</td>
                                                    <td>{{ number_format((int) data_get($adj, 'system_stock', 0), 0, ',', '.') }}</td>
                                                    <td>{{ number_format((int) data_get($adj, 'counted_stock', 0), 0, ',', '.') }}</td>
                                                    <td>{{ number_format((int) data_get($adj, 'difference', 0), 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </details>
                            @endif
                        @elseif($log->action === 'stock_opname_duplicated')
                            <div class="text-sm text-slate-700 mt-2">Sumber: {{ data_get($log->context, 'source_code', '-') }} -> Baru: {{ data_get($log->context, 'new_code', '-') }}</div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada riwayat perubahan.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
<script>
function stockOpnameDetailState({ sessionId, totalItems, status, outlierThreshold }) {
    return {
        showPostConfirm: false,
        isPosting: false,
        guardrailWarning: '',
        progressLabel: 'Terisi 0/0 (0%)',
        draftSaved: false,
        draftSavedAtLabel: '',
        showOnlyDiff: false,
        showDraftPrompt: false,
        pendingDraftPayload: null,
        liveUp: 0,
        liveDown: 0,
        liveEqual: 0,
        emptyCount: 0,
        outlierThreshold: Number(outlierThreshold || 10),
        init() {
            this.prepareDraftPrompt();
            this.bindEnterToNextInput();
            this.updateVisuals();
        },
        getDraftKey() {
            return `stock_opname_draft_${sessionId}`;
        },
        allInputs() {
            return Array.from(document.querySelectorAll('[data-opname-input="1"]'));
        },
        updateVisuals() {
            const inputs = this.allInputs();
            let filled = 0;
            let diffCount = 0;
            let up = 0;
            let down = 0;
            let equal = 0;
            let empty = 0;
            inputs.forEach((input) => {
                const row = input.closest('tr');
                const system = Number(row?.dataset.systemStock || 0);
                const raw = String(input.value ?? '').trim();
                const val = raw === '' ? null : Number(raw);
                if (raw !== '') filled++;
                if (raw === '') empty++;
                const hasDiff = val !== null && Number.isFinite(val) && val !== system;
                const diffValue = val !== null && Number.isFinite(val) ? (val - system) : 0;
                const isOutlier = Math.abs(diffValue) >= this.outlierThreshold;
                row?.classList.toggle('so-row-diff', hasDiff);
                row?.classList.toggle('so-row-outlier', isOutlier && hasDiff);
                const outlierBadge = row?.querySelector('[data-outlier-badge]');
                if (outlierBadge) {
                    outlierBadge.classList.toggle('hidden', !(isOutlier && hasDiff));
                }
                row.style.display = this.showOnlyDiff && !hasDiff ? 'none' : '';
                if (hasDiff) diffCount++;
                if (val !== null && Number.isFinite(val)) {
                    if (val > system) up++;
                    else if (val < system) down++;
                    else equal++;
                }
            });
            const total = Number(totalItems || inputs.length || 0);
            const pct = total > 0 ? Math.round((filled / total) * 100) : 0;
            this.progressLabel = `Terisi ${filled}/${total} (${pct}%) | Selisih ${diffCount} item`;
            this.liveUp = up;
            this.liveDown = down;
            this.liveEqual = equal;
            this.emptyCount = empty;
        },
        saveDraft() {
            if (String(status) !== 'open') return;
            const payload = {
                inputs: {},
                posted_note: String(document.querySelector('[data-posted-note]')?.value || ''),
                updated_at: new Date().toISOString(),
            };
            this.allInputs().forEach((input) => {
                payload.inputs[String(input.dataset.itemId || '')] = String(input.value ?? '');
            });
            localStorage.setItem(this.getDraftKey(), JSON.stringify(payload));
            this.draftSaved = true;
            this.draftSavedAtLabel = `Terakhir disimpan: ${new Date(payload.updated_at).toLocaleString('id-ID')}`;
        },
        restoreDraft(payload = null) {
            if (String(status) !== 'open') return;
            try {
                const parsed = payload ?? (() => {
                    const raw = localStorage.getItem(this.getDraftKey());
                    return raw ? JSON.parse(raw) : null;
                })();
                if (!parsed || typeof parsed !== 'object') {
                    this.draftSaved = false;
                    this.draftSavedAtLabel = '';
                    return;
                }
                this.allInputs().forEach((input) => {
                    const key = String(input.dataset.itemId || '');
                    if (Object.prototype.hasOwnProperty.call(parsed.inputs || {}, key)) {
                        input.value = String(parsed.inputs[key] ?? '');
                    }
                });
                const noteEl = document.querySelector('[data-posted-note]');
                if (noteEl && typeof parsed.posted_note === 'string') noteEl.value = parsed.posted_note;
                this.draftSaved = true;
                if (parsed.updated_at) {
                    this.draftSavedAtLabel = `Terakhir disimpan: ${new Date(parsed.updated_at).toLocaleString('id-ID')}`;
                }
            } catch (e) {}
        },
        prepareDraftPrompt() {
            if (String(status) !== 'open') return;
            try {
                const raw = localStorage.getItem(this.getDraftKey());
                if (!raw) return;
                const parsed = JSON.parse(raw);
                if (!parsed || typeof parsed !== 'object') return;
                this.pendingDraftPayload = parsed;
                this.showDraftPrompt = true;
            } catch (e) {}
        },
        restoreDraftAndContinue() {
            this.showDraftPrompt = false;
            this.restoreDraft(this.pendingDraftPayload);
            this.pendingDraftPayload = null;
            this.updateVisuals();
        },
        discardDraftAndContinue() {
            this.clearDraft();
            this.showDraftPrompt = false;
            this.pendingDraftPayload = null;
            this.updateVisuals();
        },
        clearDraft() {
            localStorage.removeItem(this.getDraftKey());
            this.draftSaved = false;
            this.draftSavedAtLabel = '';
        },
        resetDraft() {
            this.clearDraft();
            this.allInputs().forEach((input) => { input.value = ''; });
            const noteEl = document.querySelector('[data-posted-note]');
            if (noteEl) noteEl.value = '';
            this.updateVisuals();
        },
        handleInputChange() {
            this.updateVisuals();
            this.saveDraft();
        },
        fillAllFromSystem() {
            this.allInputs().forEach((input) => {
                const row = input.closest('tr');
                input.value = String(Number(row?.dataset.systemStock || 0));
            });
            this.handleInputChange();
        },
        fillEmptyWithZero() {
            this.allInputs().forEach((input) => {
                const raw = String(input.value ?? '').trim();
                if (raw === '') input.value = '0';
            });
            this.handleInputChange();
        },
        clearAllInputs() {
            this.allInputs().forEach((input) => { input.value = ''; });
            this.handleInputChange();
        },
        resetRowToSystem(event) {
            const row = event?.target?.closest('tr');
            if (!row) return;
            const input = row.querySelector('[data-opname-input="1"]');
            if (!input) return;
            input.value = String(Number(row.dataset.systemStock || 0));
            this.handleInputChange();
        },
        applyDiffFilter() {
            this.updateVisuals();
        },
        bindEnterToNextInput() {
            const inputs = this.allInputs();
            inputs.forEach((input, idx) => {
                input.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter') return;
                    event.preventDefault();
                    const next = inputs[idx + 1];
                    if (next) {
                        next.focus();
                        next.select?.();
                    }
                });
            });
        },
        openPostConfirm() {
            this.updateVisuals();
            const inputs = this.allInputs();
            let totalAbsDiff = 0;
            let diffItems = 0;
            inputs.forEach((input) => {
                const row = input.closest('tr');
                const system = Number(row?.dataset.systemStock || 0);
                const raw = String(input.value ?? '').trim();
                const val = raw === '' ? system : Number(raw);
                if (Number.isFinite(val)) {
                    const d = val - system;
                    if (d !== 0) diffItems++;
                    totalAbsDiff += Math.abs(d);
                }
            });
            this.guardrailWarning = '';
            if (diffItems >= 50 || totalAbsDiff >= 500) {
                this.guardrailWarning = `Peringatan: perubahan cukup besar (${diffItems} item, total selisih absolut ${totalAbsDiff}). Pastikan hitungan fisik sudah benar.`;
            }
            this.showPostConfirm = true;
        },
    };
}
</script>
