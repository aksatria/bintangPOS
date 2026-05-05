<x-app-layout>
    <x-slot name="header">
        <div class="px-head intro-y">
            <div>
                <p class="px-kicker">Master Data</p>
                <h2 class="px-title">Produk</h2>
                <p class="px-subtitle">Kelola katalog produk, harga, dan stok dengan tampilan yang lebih cepat dibaca.</p>
            </div>
            <div class="px-head-actions">
                <span class="px-badge">{{ number_format($products->total(), 0, ',', '.') }} item</span>
            </div>
        </div>
    </x-slot>

    <style>
        .px-shell { max-width: 1360px; margin: 0 auto; }
        .px-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; flex-wrap: wrap; }
        .px-kicker { margin: 0; font-size: .74rem; letter-spacing: .1em; text-transform: uppercase; color: #64748b; font-weight: 700; }
        .px-title { margin: .25rem 0 0; font-size: 2.15rem; line-height: 1.1; font-weight: 700; color: #0f172a; }
        .px-subtitle { margin: .4rem 0 0; font-size: .98rem; color: #475569; }
        .px-badge { display: inline-flex; align-items: center; min-height: 2.5rem; padding: 0 .95rem; border: 1px solid #bfdbfe; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: .84rem; font-weight: 700; }

        .px-layout { display: grid; grid-template-columns: 1fr; gap: 1rem; align-items: start; }
        @media (min-width: 1200px) { .px-layout { grid-template-columns: minmax(0, 1.45fr) minmax(360px, .55fr); } }

        .px-card { background: #fff; border: 1px solid #dbe5f2; border-radius: 16px; box-shadow: 0 8px 24px rgba(15, 23, 42, .05); overflow: hidden; }
        .px-card-head { padding: 1rem 1.15rem; border-bottom: 1px solid #e8eff8; background: linear-gradient(180deg, #fbfdff 0%, #f8fbff 100%); }
        .px-card-title { margin: 0; font-size: .9rem; letter-spacing: .08em; text-transform: uppercase; color: #334155; font-weight: 700; }
        .px-card-body { padding: 1.25rem; }

        .px-alert { border-radius: 12px; border: 1px solid; padding: .8rem .92rem; font-size: .86rem; }
        .px-alert-ok { border-color: #bbf7d0; background: #ecfdf5; color: #047857; }
        .px-alert-err { border-color: #fecaca; background: #fff1f2; color: #be123c; }

        .px-input, .px-select {
            width: 100%;
            min-height: 2.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            color: #0f172a;
            padding: 0 .76rem;
            font-size: .96rem;
        }
        .px-input:focus, .px-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .16);
        }
        .px-label { display: block; margin-bottom: .38rem; font-size: .75rem; letter-spacing: .05em; text-transform: uppercase; color: #64748b; font-weight: 600; }

        .px-btn {
            min-height: 2.55rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 1rem;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            font-size: .85rem;
            font-weight: 600;
            letter-spacing: .01em;
            transition: background-color .16s ease, border-color .16s ease, box-shadow .16s ease, color .16s ease;
        }
        .px-btn:hover { background: #f8fafc; }
        .px-btn-icon {
            width: .92rem;
            height: .92rem;
            margin-right: .4rem;
            vertical-align: -2px;
            flex: 0 0 auto;
        }
        .px-label-icon {
            width: .86rem;
            height: .86rem;
            margin-right: .32rem;
            vertical-align: -1px;
            color: #64748b;
        }
        .px-chip-icon {
            width: .8rem;
            height: .8rem;
            margin-right: .22rem;
            vertical-align: -1px;
        }
        .px-btn-primary { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        .px-btn-primary:hover { background: #1e40af; border-color: #1e40af; box-shadow: 0 6px 14px rgba(30, 64, 175, .18); }
        .px-btn-danger { border-color: #fecaca; color: #b91c1c; background: #fff1f2; }
        .px-btn-danger:hover { background: #ffe4e6; }
        .px-btn-soft { border-color: #bfdbfe; background: #eff6ff; color: #1d4ed8; }
        .px-btn-soft:hover { background: #dbeafe; }
        .px-btn-ghost {
            min-width: 140px;
            height: 2.55rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 1rem;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            font-size: .86rem;
            font-weight: 600;
            line-height: 1;
            text-decoration: none;
        }
        .px-btn-ghost:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #1e293b;
        }

        .px-filter-grid { display: grid; grid-template-columns: 1fr; gap: .6rem; }
        @media (min-width: 980px) { .px-filter-grid { grid-template-columns: 1fr 220px 180px; } }
        .px-filter-actions { display: flex; gap: .55rem; flex-wrap: wrap; align-items: center; }
        .px-filter-actions .px-btn-primary { min-width: 160px; justify-content: center; }
        .px-filter-wrap {
            border: 1px solid #dbe5f2;
            border-radius: 14px;
            padding: .9rem;
            background: linear-gradient(135deg, #f8fbff 0%, #f0f7ff 45%, #ffffff 100%);
        }
        .px-filter-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .7rem;
            margin-bottom: .65rem;
            padding-bottom: .55rem;
            border-bottom: 1px solid #dbe5f2;
        }
        .px-filter-title { margin: 0; font-size: .8rem; letter-spacing: .07em; text-transform: uppercase; color: #334155; font-weight: 700; }
        .px-filter-note { margin: 0; font-size: .8rem; color: #64748b; }

        .px-list { display: grid; gap: .8rem; }
        .px-row {
            border: 1px solid #e3ebf5;
            border-radius: 12px;
            padding: 1rem;
            background: #fff;
        }
        .px-row:hover { border-color: #bfdbfe; box-shadow: 0 6px 14px rgba(37, 99, 235, .07); }
        .px-row-top {
            display: grid;
            grid-template-columns: 28px 72px minmax(0, 1fr) auto;
            gap: .9rem;
            align-items: start;
        }
        @media (max-width: 980px) { .px-row-top { grid-template-columns: 1fr; } }
        .px-check {
            width: 1rem;
            height: 1rem;
            accent-color: #1d4ed8;
            margin-top: .35rem;
        }
        .px-photo {
            width: 80px;
            height: 80px;
            border-radius: 14px;
            border: 1px solid #dbe5f2;
            overflow: hidden;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .45);
        }
        .px-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }
        .px-photo-empty {
            font-size: .66rem;
            color: #94a3b8;
            font-weight: 600;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .px-name { margin: 0; font-size: 1.02rem; line-height: 1.35; font-weight: 600; color: #0f172a; }
        .px-meta { margin-top: .5rem; display: flex; flex-wrap: wrap; gap: .5rem .75rem; color: #475569; font-size: .87rem; }
        .px-meta span { white-space: normal; word-break: break-word; }
        .px-tag {
            display: inline-flex;
            align-items: center;
            gap: .32rem;
            border-radius: 999px;
            border: 1px solid;
            padding: .18rem .52rem;
            font-size: .76rem;
            font-weight: 700;
        }
        .px-tag b { font-weight: 700; }
        .px-tag-sku { color: #1e3a8a; background: #eff6ff; border-color: #bfdbfe; }
        .px-tag-barcode { color: #0f766e; background: #ecfeff; border-color: #a5f3fc; }
        .px-tag-unit { color: #7c2d12; background: #fff7ed; border-color: #fdba74; }
        .px-tag-category { color: #14532d; background: #f0fdf4; border-color: #86efac; }

        .px-prices, .px-stock { font-size: .92rem; color: #1f2937; line-height: 1.5; }
        .px-prices b, .px-stock b { color: #64748b; font-weight: 600; margin-right: .2rem; }
        .px-metrics {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }
        .px-metric {
            display: inline-flex;
            align-items: center;
            gap: .28rem;
            border-radius: 999px;
            border: 1px solid;
            padding: .2rem .55rem;
            font-size: .8rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .px-metric b { font-weight: 700; }
        .px-metric-modal { color: #1e3a8a; border-color: #bfdbfe; background: #eff6ff; }
        .px-metric-jual { color: #14532d; border-color: #86efac; background: #f0fdf4; }
        .px-metric-stok { color: #7c2d12; border-color: #fdba74; background: #fff7ed; }
        .px-metric-min { color: #0f766e; border-color: #99f6e4; background: #ecfeff; }
        .px-inline-form {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) minmax(130px, 160px) auto;
            align-items: end;
            gap: .6rem;
            margin-top: .7rem;
            padding: .7rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fbff;
            max-width: 100%;
            overflow: hidden;
        }
        .px-inline-field { min-width: 0; }
        .px-inline-field label {
            display: block;
            margin-bottom: .22rem;
            font-size: .68rem;
            color: #64748b;
            letter-spacing: .04em;
            text-transform: uppercase;
            font-weight: 600;
        }
        .px-inline-input-wrap {
            position: relative;
        }
        .px-inline-prefix {
            position: absolute;
            left: .55rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: .78rem;
            color: #64748b;
            font-weight: 600;
            pointer-events: none;
        }
        .px-inline-input {
            width: 100%;
            min-height: 2rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 0 .55rem;
            font-size: .83rem;
            color: #0f172a;
            background: #fff;
            font-variant-numeric: tabular-nums;
            max-width: 100%;
        }
        .px-inline-input-money { padding-left: 1.9rem; }
        .px-inline-input:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(59,130,246,.14);
        }
        .px-btn-inline {
            min-height: 2.1rem;
            border-radius: 8px;
            font-size: .78rem;
            padding: 0 .9rem;
            background: #1d4ed8;
            border-color: #1d4ed8;
            color: #fff;
            font-weight: 600;
            white-space: nowrap;
        }
        .px-btn-inline:hover {
            background: #1e40af;
            border-color: #1e40af;
        }
        @media (max-width: 1240px) {
            .px-inline-form {
                grid-template-columns: 1fr;
            }
            .px-btn-inline {
                width: 100%;
            }
        }

        .px-state { display: flex; flex-direction: column; align-items: flex-start; gap: .36rem; }
        .px-chip { display: inline-flex; align-items: center; padding: .2rem .56rem; border-radius: 999px; border: 1px solid; font-size: .68rem; font-weight: 700; letter-spacing: .06em; }
        .px-chip-on { color: #047857; border-color: #a7f3d0; background: #ecfdf5; }
        .px-chip-off { color: #b91c1c; border-color: #fecaca; background: #fff1f2; }
        .px-chip-low { color: #b45309; border-color: #fde68a; background: #fffbeb; }
        .px-hidden { display: none !important; }

        .px-actions { display: flex; flex-wrap: wrap; gap: .55rem; margin-top: .95rem; padding-top: .8rem; border-top: 1px dashed #e2e8f0; }
        .px-actions .px-btn { min-width: 120px; }
        .px-file-wrap {
            border: 1px solid #dbe5f2;
            border-radius: 10px;
            padding: .62rem;
            background: #ffffff;
        }
        .px-file-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
            margin-bottom: .45rem;
        }
        .px-file-title {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .79rem;
            color: #475569;
            font-weight: 600;
            letter-spacing: .02em;
        }
        .px-file-hint {
            font-size: .72rem;
            color: #94a3b8;
            white-space: nowrap;
        }
        .px-file-input { display: block; width: 100%; font-size: .86rem; color: #334155; }
        .px-file-input::file-selector-button {
            margin-right: .6rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: .34rem .66rem;
            background: #f8fafc;
            color: #334155;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
        }
        .px-file-preview {
            margin-top: .6rem;
            width: 100%;
            min-height: 86px;
            border: 1px dashed #dbe5f2;
            border-radius: 10px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .px-file-preview img {
            width: 100%;
            max-height: 190px;
            object-fit: contain;
            display: block;
        }
        .px-file-preview-empty {
            font-size: .78rem;
            color: #94a3b8;
        }
        .px-toggle {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            border: 1px solid #dbe5f2;
            border-radius: 999px;
            padding: .32rem .72rem .32rem .4rem;
            background: #f8fbff;
            color: #334155;
            font-size: .85rem;
            font-weight: 700;
        }
        .px-toggle input { width: 1rem; height: 1rem; accent-color: #1d4ed8; }

        .px-card-form { position: sticky; top: 1rem; }
        @media (max-width: 1199px) { .px-card-form { position: static; } }
        .px-form-shell { display: grid; gap: .9rem; }
        .px-form-group {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }
        .px-form-group-title {
            margin: 0 0 .8rem;
            font-size: .74rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #475569;
            font-weight: 700;
        }
        .px-form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: .8rem; }
        .px-form-group .px-label { margin-top: .2rem; }
        .px-form-group > label + label,
        .px-form-group .px-form-grid-2 + label,
        .px-form-group label + .px-form-grid-2,
        .px-form-group label + .px-toggle {
            margin-top: .7rem;
        }
        .px-form-submit {
            margin-top: .35rem;
            min-height: 2.9rem;
            font-size: .92rem;
            border-radius: 12px;
            box-shadow: 0 12px 20px rgba(29, 78, 216, .2);
        }

        .px-pagination nav > div:first-child { display: none; }
        .px-pagination nav > div:last-child {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
            border: 1px solid #dbe5f2;
            border-radius: 12px;
            padding: .65rem .8rem;
            background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        }
        .px-pagination nav p {
            margin: 0 !important;
            font-size: .82rem !important;
            color: #64748b !important;
            font-weight: 600;
        }
        .px-pagination nav span.relative.z-0.inline-flex.shadow-sm.rounded-md,
        .px-pagination nav span.relative.z-0.inline-flex.rtl\\:flex-row-reverse.shadow-sm.rounded-md {
            display: inline-flex;
            align-items: center;
            gap: .22rem;
            box-shadow: none !important;
            border-radius: 10px;
            background: transparent;
        }
        .px-pagination nav span[aria-current="page"] span,
        .px-pagination nav a,
        .px-pagination nav span[aria-disabled="true"] span {
            min-width: 2.25rem;
            height: 2.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: 1px solid #dbe5f2;
            background: #fff;
            color: #334155;
            font-size: .84rem;
            font-weight: 700;
            margin: 0;
            box-shadow: 0 2px 7px rgba(15, 23, 42, .05);
        }
        .px-pagination nav a:hover { background: #f8fafc; border-color: #bfdbfe; color: #1d4ed8; }
        .px-pagination nav span[aria-current="page"] span {
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            border-color: #1d4ed8;
            color: #fff;
            box-shadow: 0 8px 16px rgba(37, 99, 235, .28);
        }
        .px-pagination nav span[aria-disabled="true"] span { opacity: .5; }
        .px-pagination-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: .7rem;
            border: 1px solid #dbe5f2;
            border-radius: 12px;
            padding: .8rem .9rem;
            background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        }
        .px-pagination-summary {
            margin: 0;
            font-size: .82rem;
            color: #64748b;
            font-weight: 600;
        }
        .px-pagination-pages { display: inline-flex; align-items: center; gap: .28rem; flex-wrap: wrap; }
        .px-page-btn {
            min-width: 2.15rem;
            height: 2.15rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dbe5f2;
            border-radius: 9px;
            background: #fff;
            color: #334155;
            font-size: .82rem;
            font-weight: 700;
            text-decoration: none;
        }
        .px-page-btn:hover { background: #f8fafc; border-color: #bfdbfe; color: #1d4ed8; }
        .px-page-btn-active {
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            border-color: #1d4ed8;
            color: #fff;
            box-shadow: 0 8px 16px rgba(37, 99, 235, .25);
            pointer-events: none;
        }
        .px-page-dots {
            min-width: 2rem;
            text-align: center;
            color: #94a3b8;
            font-weight: 700;
        }
        .px-search-hint {
            margin: .42rem 0 0;
            font-size: .78rem;
            color: #64748b;
        }
        .px-inline-note { font-size: .78rem; color: #64748b; line-height: 1.45; }
        .px-low-note {
            margin-top: .4rem;
            font-size: .76rem;
            color: #64748b;
        }
        .px-toast-wrap {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 70;
            display: flex;
            flex-direction: column;
            gap: .5rem;
            pointer-events: none;
        }
        .px-toast {
            pointer-events: auto;
            min-width: 250px;
            max-width: 360px;
            border-radius: 10px;
            border: 1px solid #bbf7d0;
            background: #ecfdf5;
            color: #065f46;
            padding: .7rem .8rem;
            font-size: .83rem;
            font-weight: 500;
            box-shadow: 0 8px 18px rgba(6, 95, 70, .15);
        }
        .px-toast-error {
            border-color: #fecaca;
            background: #fff1f2;
            color: #9f1239;
            box-shadow: 0 8px 18px rgba(159, 18, 57, .14);
        }
        .px-bulk-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: .6rem .7rem;
            background: #f8fafc;
        }
        .px-bulk-left {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-size: .82rem;
            color: #475569;
            font-weight: 600;
        }
        .px-bulk-actions { display: inline-flex; align-items: center; gap: .45rem; flex-wrap: wrap; }
        .px-btn-bulk { min-height: 2.15rem; font-size: .8rem; padding: 0 .8rem; border-radius: 8px; }
        .px-btn-bulk-danger { border-color: #fecaca; background: #fff1f2; color: #b91c1c; }
        .px-btn-bulk-danger:hover { background: #ffe4e6; }
    </style>

    <div class="px-shell intro-y space-y-4">
        @if (session('status'))
            <div class="px-alert px-alert-ok">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="px-alert px-alert-err">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(!empty($reorderSuggestions) && count($reorderSuggestions) > 0)
            <section class="px-card">
                <div class="px-card-head">
                    <h3 class="px-card-title">Reorder Suggestion Otomatis</h3>
                    <p class="px-filter-note">Lookback {{ (int) data_get($reorderWindow ?? [], 'lookback_days', 30) }} hari · Lead {{ (int) data_get($reorderWindow ?? [], 'lead_days', 7) }} hari · Safety {{ (int) data_get($reorderWindow ?? [], 'safety_days', 3) }} hari</p>
                </div>
                <div class="px-card-body">
                    <div style="display:grid;gap:.55rem;">
                        @foreach($reorderSuggestions as $row)
                            <div style="display:flex;justify-content:space-between;gap:.75rem;flex-wrap:wrap;border:1px solid #e2e8f0;border-radius:10px;padding:.6rem .75rem;">
                                <div>
                                    <strong>{{ $row['name'] }}</strong>
                                    <div style="font-size:.82rem;color:#64748b;">{{ $row['sku'] }} · Avg {{ number_format((float) $row['daily_avg'], 2, ',', '.') }}/hari</div>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-size:.82rem;color:#475569;">Stok {{ (int) $row['stock'] }} / Target {{ (int) $row['target_stock'] }}</div>
                                    <strong style="color:#b45309;">Saran order: {{ number_format((int) $row['suggested_qty'], 0, ',', '.') }}</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <div class="px-layout">
            <section class="px-card">
                <div class="px-card-head"><h3 class="px-card-title">Daftar Produk</h3></div>
                <div class="px-card-body space-y-3">
                    <div class="px-filter-wrap">
                        <div class="px-filter-head">
                            <h4 class="px-filter-title">Filter Produk</h4>
                            <p class="px-filter-note">Cari cepat berdasarkan nama, kategori, dan status.</p>
                        </div>
                        <form method="GET" action="{{ route('admin.products.index') }}" class="px-filter-grid">
                            <input
                                type="text"
                                name="q"
                                value="{{ $filters['q'] ?? '' }}"
                                class="px-input"
                                placeholder="Cari cepat: nama SKU barcode kategori (contoh: k24 tablet promo)"
                                autocomplete="off"
                            >
                            <select name="category_id" class="px-select">
                                <option value="0">Semua Kategori</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected((int)($filters['category_id'] ?? 0)===(int)$cat->id)>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <select name="status" class="px-select">
                                <option value="all" @selected(($filters['status'] ?? 'all')==='all')>Semua Status</option>
                                <option value="active" @selected(($filters['status'] ?? '')==='active')>Aktif</option>
                                <option value="inactive" @selected(($filters['status'] ?? '')==='inactive')>Nonaktif</option>
                            </select>
                            <div class="px-filter-actions md:col-span-3">
                                <button class="px-btn px-btn-primary"><i data-feather="filter" class="px-btn-icon"></i>Terapkan Filter</button>
                                <a href="{{ route('admin.products.index') }}" class="px-btn px-btn-ghost"><i data-feather="rotate-ccw" class="px-btn-icon"></i>Reset</a>
                            </div>
                        </form>
                        <p class="px-search-hint">Pencarian pintar: bisa beberapa kata sekaligus, contoh `k24 tablet promo`.</p>
                    </div>
                    <div class="px-list">
                        @forelse($products as $product)
                            <article class="px-row" id="product-row-{{ $product->id }}">
                                <div class="px-row-top">
                                    <input type="checkbox" class="px-check" data-product-check="1" value="{{ $product->id }}">
                                    <figure class="px-photo">
                                        @if(!empty($product->image))
                                            <img
                                                data-role="image"
                                                src="{{ asset('storage/' . ltrim((string) $product->image, '/')) }}"
                                                alt="{{ $product->name }}"
                                            >
                                        @else
                                            <span class="px-photo-empty" data-role="image-fallback">No Photo</span>
                                        @endif
                                    </figure>
                                    <div>
                                        <h4 class="px-name" data-role="name">{{ $product->name }}</h4>
                                        <div class="px-meta">
                                            <span class="px-tag px-tag-sku"><b>SKU</b><span data-role="sku">{{ $product->sku }}</span></span>
                                            <span class="px-tag px-tag-barcode"><b>Barcode</b><span data-role="barcode">{{ $product->barcode ?: '-' }}</span></span>
                                            <span class="px-tag px-tag-unit"><b>Unit</b><span data-role="unit">{{ $product->unit }}</span></span>
                                            <span class="px-tag px-tag-category"><b>Kategori</b><span data-role="category">{{ $product->category?->name ?? '-' }}</span></span>
                                        </div>
                                    </div>
                                    <div class="px-state">
                                        <span
                                            class="px-chip px-chip-low {{ (int)$product->stock <= (int)$product->low_stock_threshold ? '' : 'px-hidden' }}"
                                            data-role="low-chip"
                                            title="LOW berarti stok sudah menipis dan perlu restock."
                                        ><i data-feather="alert-triangle" class="px-chip-icon"></i>LOW</span>
                                        <span class="px-chip {{ $product->is_active ? 'px-chip-on' : 'px-chip-off' }}" data-role="active-chip">{{ $product->is_active ? 'AKTIF' : 'NONAKTIF' }}</span>
                                    </div>
                                </div>
                                <div class="px-metrics mt-2">
                                    <span class="px-metric px-metric-modal"><b>Modal</b><span data-role="purchase-price">Rp {{ number_format((float)$product->purchase_price, 0, ',', '.') }}</span></span>
                                    <span class="px-metric px-metric-jual"><b>Jual</b><span data-role="selling-price">Rp {{ number_format((float)$product->selling_price, 0, ',', '.') }}</span></span>
                                    <span class="px-metric px-metric-stok"><b>Stok</b><span data-role="stock">{{ number_format((int)$product->stock, 0, ',', '.') }}</span></span>
                                </div>
                                <p class="px-low-note">Note: label <b>LOW</b> berarti stok produk sudah menipis.</p>
                                <form class="px-inline-form" method="POST" data-inline-form="1" action="{{ route('admin.products.update', $product) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="inline_only" value="1">
                                    <div class="px-inline-field">
                                        <label>Harga Jual</label>
                                        <div class="px-inline-input-wrap">
                                            <span class="px-inline-prefix">Rp</span>
                                            <input class="px-inline-input px-inline-input-money" type="number" min="0" step="0.01" name="selling_price" value="{{ (float)$product->selling_price }}">
                                        </div>
                                    </div>
                                    <div class="px-inline-field">
                                        <label>Stok</label>
                                        <input class="px-inline-input" type="number" min="0" step="1" name="stock" value="{{ (int)$product->stock }}">
                                    </div>
                                    <button type="submit" class="px-btn px-btn-inline"><i data-feather="save" class="px-btn-icon"></i>Simpan Cepat</button>
                                </form>

                                <div class="px-actions">
                                    <form method="POST" data-toggle-form="1" action="{{ route('admin.products.update', $product) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="toggle_only" value="1">
                                        <input type="hidden" name="category_id" value="{{ $product->category_id }}">
                                        <input type="hidden" name="name" value="{{ $product->name }}">
                                        <input type="hidden" name="sku" value="{{ $product->sku }}">
                                        <input type="hidden" name="barcode" value="{{ $product->barcode }}">
                                        <input type="hidden" name="purchase_price" value="{{ (float)$product->purchase_price }}">
                                        <input type="hidden" name="selling_price" value="{{ (float)$product->selling_price }}">
                                        <input type="hidden" name="stock" value="{{ (int)$product->stock }}">
                                        <input type="hidden" name="low_stock_threshold" value="{{ (int)$product->low_stock_threshold }}">
                                        <input type="hidden" name="unit" value="{{ $product->unit }}">
                                        <input type="hidden" name="is_active" value="{{ $product->is_active ? 0 : 1 }}">
                                        <button class="px-btn" type="submit" data-role="toggle-btn"><i data-feather="power" class="px-btn-icon"></i>{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                    <button
                                        class="px-btn"
                                        type="button"
                                        data-edit-product="1"
                                        data-id="{{ $product->id }}"
                                        data-category-id="{{ $product->category_id }}"
                                        data-name="{{ $product->name }}"
                                        data-sku="{{ $product->sku }}"
                                        data-barcode="{{ $product->barcode }}"
                                        data-purchase-price="{{ (float) $product->purchase_price }}"
                                        data-selling-price="{{ (float) $product->selling_price }}"
                                        data-stock="{{ (int) $product->stock }}"
                                        data-low-stock-threshold="{{ (int) $product->low_stock_threshold }}"
                                        data-unit="{{ $product->unit }}"
                                        data-is-active="{{ $product->is_active ? 1 : 0 }}"
                                        data-image-url="{{ !empty($product->image) ? asset('storage/' . ltrim((string) $product->image, '/')) : '' }}"
                                    ><i data-feather="edit-3" class="px-btn-icon"></i>Edit</button>
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Hapus produk ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="px-btn px-btn-danger" type="submit"><i data-feather="trash-2" class="px-btn-icon"></i>Hapus</button>
                                    </form>
                                </div>
                            </article>
                        @empty
                            <div class="text-center text-slate-500 py-4">Belum ada produk.</div>
                        @endforelse
                    </div>

                    @php
                        $current = $products->currentPage();
                        $last = $products->lastPage();
                        $pages = [1];
                        if ($current - 1 > 1) { $pages[] = $current - 1; }
                        if ($current !== 1 && $current !== $last) { $pages[] = $current; }
                        if ($current + 1 < $last) { $pages[] = $current + 1; }
                        if ($last > 1) { $pages[] = $last; }
                        $pages = array_values(array_unique(array_filter($pages, fn ($p) => $p >= 1 && $p <= $last)));
                        sort($pages);
                    @endphp
                    <div class="px-pagination-custom">
                        <p class="px-pagination-summary">
                            Menampilkan {{ number_format($products->firstItem() ?? 0, 0, ',', '.') }}
                            sampai {{ number_format($products->lastItem() ?? 0, 0, ',', '.') }}
                            dari {{ number_format($products->total(), 0, ',', '.') }} produk
                        </p>
                        <div class="px-pagination-pages">
                            @if($products->onFirstPage())
                                <span class="px-page-btn" aria-disabled="true">&#8249;</span>
                            @else
                                <a href="{{ $products->previousPageUrl() }}" class="px-page-btn" rel="prev">&#8249;</a>
                            @endif

                            @php $prevShown = null; @endphp
                            @foreach($pages as $page)
                                @if(!is_null($prevShown) && ($page - $prevShown) > 1)
                                    <span class="px-page-dots">&hellip;</span>
                                @endif

                                @if($page === $current)
                                    <span class="px-page-btn px-page-btn-active">{{ $page }}</span>
                                @else
                                    <a href="{{ $products->url($page) }}" class="px-page-btn">{{ $page }}</a>
                                @endif
                                @php $prevShown = $page; @endphp
                            @endforeach

                            @if($products->hasMorePages())
                                <a href="{{ $products->nextPageUrl() }}" class="px-page-btn" rel="next">&#8250;</a>
                            @else
                                <span class="px-page-btn" aria-disabled="true">&#8250;</span>
                            @endif
                        </div>
                    </div>
                    <p class="px-inline-note">Perubahan disimpan tanpa reload (AJAX) dan notifikasi muncul otomatis.</p>
                </div>
            </section>

            <section class="px-card px-card-form">
                <div class="px-card-head"><h3 class="px-card-title">Tambah Produk</h3></div>
                <div class="px-card-body">
                    <form id="create-product-form" method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="px-form-shell">
                        @csrf
                        <div class="px-form-group">
                            <h4 class="px-form-group-title">Info Dasar</h4>
                            <label><span class="px-label">Kategori</span>
                                <select name="category_id" class="px-select" required>
                                    <option value="">Pilih kategori</option>
                                    @foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach
                                </select>
                            </label>
                            <label><span class="px-label">Nama Produk</span><input type="text" name="name" class="px-input" required></label>
                            <div class="px-form-grid-2">
                                <label><span class="px-label">SKU</span><input type="text" name="sku" class="px-input" required></label>
                                <label><span class="px-label">Barcode</span><input type="text" name="barcode" class="px-input"></label>
                            </div>
                            <label><span class="px-label">Unit</span><input type="text" name="unit" class="px-input" value="pcs" required></label>
                        </div>
                        <div class="px-form-group">
                            <h4 class="px-form-group-title">Harga dan Stok</h4>
                            <div class="px-form-grid-2">
                                <label><span class="px-label">Harga Modal</span><input type="number" min="0" step="0.01" name="purchase_price" class="px-input" required></label>
                                <label><span class="px-label">Harga Jual</span><input type="number" min="0" step="0.01" name="selling_price" class="px-input" required></label>
                                <label><span class="px-label">Stok</span><input type="number" min="0" step="1" name="stock" class="px-input" required></label>
                                <label><span class="px-label">Min Stok</span><input type="number" min="0" step="1" name="low_stock_threshold" class="px-input" required></label>
                            </div>
                        </div>
                        <div class="px-form-group">
                            <h4 class="px-form-group-title">Aset dan Status</h4>
                            <label>
                                <span class="px-label">Foto Produk</span>
                                <div class="px-file-wrap">
                                    <div class="px-file-head">
                                        <span class="px-file-title"><i data-feather="image" class="px-label-icon"></i>Upload Gambar Produk</span>
                                        <span class="px-file-hint">JPG/PNG · max 2MB</span>
                                    </div>
                                    <input type="file" name="image" id="create-image-input" class="px-file-input" accept="image/*">
                                    <div class="px-file-preview" id="create-image-preview">
                                        <span class="px-file-preview-empty">Belum ada gambar dipilih</span>
                                    </div>
                                </div>
                            </label>
                            <label class="px-toggle"><input type="checkbox" name="is_active" value="1" checked> Produk Aktif</label>
                        </div>
                        <button class="px-btn px-btn-primary px-form-submit w-full" type="submit"><i data-feather="save" class="px-btn-icon"></i>Simpan Produk</button>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <div id="px-toast-wrap" class="px-toast-wrap" aria-live="polite"></div>

    <div id="edit-product-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/55 p-4">
        <div class="w-full max-w-2xl rounded-xl border border-slate-200 bg-white p-4 shadow-2xl">
            <h3 class="text-base font-semibold text-slate-900">Edit Produk</h3>
            <form id="edit-product-form" method="POST" enctype="multipart/form-data" class="mt-3 space-y-2">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <label><span class="px-label">Kategori</span>
                        <select id="ep-category" name="category_id" class="px-select" required>
                            @foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach
                        </select>
                    </label>
                    <label><span class="px-label">Nama Produk</span><input id="ep-name" type="text" name="name" class="px-input" required></label>
                    <label><span class="px-label">SKU</span><input id="ep-sku" type="text" name="sku" class="px-input" required></label>
                    <label><span class="px-label">Barcode</span><input id="ep-barcode" type="text" name="barcode" class="px-input"></label>
                    <label><span class="px-label">Harga Modal</span><input id="ep-purchase" type="number" min="0" step="0.01" name="purchase_price" class="px-input" required></label>
                    <label><span class="px-label">Harga Jual</span><input id="ep-selling" type="number" min="0" step="0.01" name="selling_price" class="px-input" required></label>
                    <label><span class="px-label">Stok</span><input id="ep-stock" type="number" min="0" step="1" name="stock" class="px-input" required></label>
                    <label><span class="px-label">Min Stok</span><input id="ep-low" type="number" min="0" step="1" name="low_stock_threshold" class="px-input" required></label>
                    <label><span class="px-label">Unit</span><input id="ep-unit" type="text" name="unit" class="px-input" required></label>
                    <label>
                        <span class="px-label">Ganti Foto</span>
                        <input type="file" id="edit-image-input" name="image" class="px-input" accept="image/*">
                        <div class="px-file-preview mt-2" id="edit-image-preview">
                            <span class="px-file-preview-empty">Foto saat ini akan tampil di sini</span>
                        </div>
                    </label>
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-slate-600"><input id="ep-active" type="checkbox" name="is_active" value="1"> Aktif</label>
                <div class="flex justify-end gap-2 pt-1">
                    <button class="px-btn" type="button" onclick="closeEditProduct()">Batal</button>
                    <button class="px-btn px-btn-primary" type="submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const idr = new Intl.NumberFormat('id-ID');
        function showToast(message, type = 'success') {
            const wrap = document.getElementById('px-toast-wrap');
            if (!wrap) return;
            const toast = document.createElement('div');
            toast.className = 'px-toast';
            if (type === 'error') toast.classList.add('px-toast-error');
            toast.textContent = message;
            wrap.appendChild(toast);
            setTimeout(() => toast.remove(), 2200);
        }
        function formatRupiah(value) {
            return `Rp ${idr.format(Number(value || 0))}`;
        }
        function formatInt(value) {
            return idr.format(Number(value || 0));
        }
        function applyProductDataToRow(data) {
            const row = document.getElementById(`product-row-${data.id}`);
            if (!row) return;
            const setText = (role, value) => {
                const el = row.querySelector(`[data-role="${role}"]`);
                if (el) el.textContent = value;
            };
            setText('name', data.name);
            setText('sku', data.sku);
            setText('barcode', data.barcode);
            setText('unit', data.unit);
            setText('category', data.category_name);
            setText('purchase-price', formatRupiah(data.purchase_price));
            setText('selling-price', formatRupiah(data.selling_price));
            setText('stock', formatInt(data.stock));

            const lowChip = row.querySelector('[data-role="low-chip"]');
            if (lowChip) lowChip.classList.toggle('px-hidden', !data.is_low);

            const activeChip = row.querySelector('[data-role="active-chip"]');
            if (activeChip) {
                activeChip.textContent = data.is_active ? 'AKTIF' : 'NONAKTIF';
                activeChip.classList.toggle('px-chip-on', !!data.is_active);
                activeChip.classList.toggle('px-chip-off', !data.is_active);
            }
            const toggleBtn = row.querySelector('[data-role="toggle-btn"]');
            if (toggleBtn) toggleBtn.textContent = data.is_active ? 'Nonaktifkan' : 'Aktifkan';

            const toggleForm = row.querySelector('form[data-toggle-form="1"]');
            if (toggleForm) {
                const hidden = toggleForm.querySelector('input[name="is_active"]');
                if (hidden) hidden.value = data.is_active ? '0' : '1';
            }

            const inlineForm = row.querySelector('form[data-inline-form="1"]');
            if (inlineForm) {
                const sp = inlineForm.querySelector('input[name="selling_price"]');
                const st = inlineForm.querySelector('input[name="stock"]');
                if (sp) sp.value = String(Number(data.selling_price || 0));
                if (st) st.value = String(Number(data.stock || 0));
            }

            const editBtn = row.querySelector('[data-edit-product="1"]');
            if (editBtn) {
                editBtn.dataset.name = data.name;
                editBtn.dataset.sku = data.sku;
                editBtn.dataset.barcode = data.barcode === '-' ? '' : data.barcode;
                editBtn.dataset.purchasePrice = String(data.purchase_price);
                editBtn.dataset.sellingPrice = String(data.selling_price);
                editBtn.dataset.stock = String(data.stock);
                editBtn.dataset.lowStockThreshold = String(data.low_stock_threshold);
                editBtn.dataset.unit = data.unit;
                editBtn.dataset.isActive = data.is_active ? '1' : '0';
                editBtn.dataset.imageUrl = data.image_url || '';
            }
        }
        function openEditProduct(data) {
            const modal = document.getElementById('edit-product-modal');
            const form = document.getElementById('edit-product-form');
            if (!modal || !form || !data) return;
            form.action = `/admin/products/${data.id}`;
            document.getElementById('ep-category').value = String(data.category_id || '');
            document.getElementById('ep-name').value = String(data.name || '');
            document.getElementById('ep-sku').value = String(data.sku || '');
            document.getElementById('ep-barcode').value = String(data.barcode || '');
            document.getElementById('ep-purchase').value = String(data.purchase_price || 0);
            document.getElementById('ep-selling').value = String(data.selling_price || 0);
            document.getElementById('ep-stock').value = String(data.stock || 0);
            document.getElementById('ep-low').value = String(data.low_stock_threshold || 0);
            document.getElementById('ep-unit').value = String(data.unit || '');
            document.getElementById('ep-active').checked = !!data.is_active;
            const preview = document.getElementById('edit-image-preview');
            if (preview) {
                if (data.image_url) {
                    preview.innerHTML = `<img src="${data.image_url}" alt="Foto produk saat ini">`;
                } else {
                    preview.innerHTML = '<span class="px-file-preview-empty">Produk ini belum punya foto</span>';
                }
            }
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function closeEditProduct() {
            const modal = document.getElementById('edit-product-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        function wireImagePreview(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            if (!input || !preview) return;
            input.addEventListener('change', () => {
                const file = input.files?.[0];
                if (!file) {
                    preview.innerHTML = '<span class="px-file-preview-empty">Belum ada gambar dipilih</span>';
                    return;
                }
                if (!file.type.startsWith('image/')) {
                    preview.innerHTML = '<span class="px-file-preview-empty">File bukan gambar valid</span>';
                    return;
                }
                const url = URL.createObjectURL(file);
                preview.innerHTML = `<img src="${url}" alt="Preview gambar produk">`;
            });
        }
        wireImagePreview('create-image-input', 'create-image-preview');
        wireImagePreview('edit-image-input', 'edit-image-preview');
        document.querySelectorAll('form[data-toggle-form="1"]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const submitBtn = form.querySelector('[data-role="toggle-btn"]');
                if (submitBtn) submitBtn.disabled = true;
                try {
                    const payload = new FormData();
                    payload.append('_token', csrfToken);
                    payload.append('_method', 'PUT');
                    payload.append('toggle_only', '1');
                    payload.append('is_active', form.querySelector('input[name="is_active"]')?.value ?? '0');
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Product-Toggle': '1',
                            'Accept': 'application/json',
                        },
                        body: payload,
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(data?.message || 'Gagal mengubah status produk.');
                    if (!data?.product) throw new Error('Respons server tidak lengkap.');
                    applyProductDataToRow(data.product);
                    showToast(data?.message || 'Status produk berhasil diperbarui.');
                } catch (error) {
                    showToast(error.message || 'Terjadi kesalahan saat mengubah status.', 'error');
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                }
            });
        });
        document.querySelectorAll('form[data-inline-form="1"]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.disabled = true;
                try {
                    const payload = new FormData();
                    payload.append('_token', csrfToken);
                    payload.append('_method', 'PUT');
                    payload.append('inline_only', '1');
                    payload.append('selling_price', form.querySelector('input[name="selling_price"]')?.value ?? '0');
                    payload.append('stock', form.querySelector('input[name="stock"]')?.value ?? '0');
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Product-Inline': '1',
                            'Accept': 'application/json',
                        },
                        body: payload,
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(data?.message || 'Gagal menyimpan inline edit.');
                    if (!data?.product) throw new Error('Respons server tidak lengkap.');
                    applyProductDataToRow(data.product);
                    showToast(data?.message || 'Data berhasil diperbarui.');
                } catch (error) {
                    showToast(error.message || 'Terjadi kesalahan saat inline edit.', 'error');
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                }
            });
        });
        document.getElementById('edit-product-form')?.addEventListener('submit', async (event) => {
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
                if (!response.ok) throw new Error(data?.message || 'Gagal menyimpan perubahan produk.');
                if (!data?.product) throw new Error('Respons server tidak lengkap.');
                applyProductDataToRow(data.product);
                closeEditProduct();
                showToast(data?.message || 'Produk berhasil diperbarui.');
            } catch (error) {
                showToast(error.message || 'Terjadi kesalahan saat menyimpan perubahan.', 'error');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
        document.getElementById('create-product-form')?.addEventListener('submit', async (event) => {
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
                    throw new Error(data?.message || 'Gagal menambahkan produk.');
                }
                showToast(data?.message || 'Produk berhasil ditambahkan.');
                form.reset();
                setTimeout(() => {
                    window.location.reload();
                }, 450);
            } catch (error) {
                showToast(error.message || 'Terjadi kesalahan saat menambah produk.', 'error');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
        document.querySelectorAll('[data-edit-product="1"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                openEditProduct({
                    id: Number(btn.dataset.id || 0),
                    category_id: Number(btn.dataset.categoryId || 0),
                    name: String(btn.dataset.name || ''),
                    sku: String(btn.dataset.sku || ''),
                    barcode: String(btn.dataset.barcode || ''),
                    purchase_price: Number(btn.dataset.purchasePrice || 0),
                    selling_price: Number(btn.dataset.sellingPrice || 0),
                    stock: Number(btn.dataset.stock || 0),
                    low_stock_threshold: Number(btn.dataset.lowStockThreshold || 0),
                    unit: String(btn.dataset.unit || ''),
                    is_active: String(btn.dataset.isActive || '0') === '1',
                    image_url: String(btn.dataset.imageUrl || ''),
                });
            });
        });
        if (window.feather && typeof window.feather.replace === 'function') {
            window.feather.replace();
        }
    </script>
</x-app-layout>


