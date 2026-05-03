<x-app-layout>
    <x-slot name="header">
        <div class="ux-head intro-y">
            <div>
                <p class="ux-kicker">Master Data</p>
                <h2 class="ux-title">Piutang Pelanggan</h2>
                <p class="ux-sub">Pantau piutang pelanggan, catat cicilan, dan kendalikan risiko overdue.</p>
            </div>
        </div>
    </x-slot>

    <style>
        .ux-shell { max-width: 1280px; margin: 0 auto; }
        .ux-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; }
        .ux-kicker { margin: 0; font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; color: #64748b; font-weight: 700; }
        .ux-title { margin: .2rem 0 0; font-size: 1.95rem; line-height: 1.1; color: #0f172a; font-weight: 800; }
        .ux-sub { margin: .36rem 0 0; color: #64748b; font-size: .9rem; }
        .ux-alert { border-radius: 12px; border: 1px solid; padding: .8rem .92rem; font-size: .82rem; }
        .ux-alert-ok { border-color: #bbf7d0; background: #ecfdf5; color: #047857; }
        .ux-alert-err { border-color: #fecaca; background: #fff1f2; color: #be123c; }
        .ux-kpi { display: grid; gap: .75rem; grid-template-columns: 1fr; }
        @media (min-width: 900px) { .ux-kpi { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .ux-aging-kpi { display: grid; gap: .62rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        @media (min-width: 900px) { .ux-aging-kpi { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        .ux-aging-kpi .ux-kpi-card { padding: .58rem .7rem; min-height: 78px; }
        .ux-aging-kpi .ux-kpi-label { font-size: .68rem; }
        .ux-aging-kpi .ux-kpi-value { font-size: 1.1rem; }
        .ux-kpi-card { border: 1px solid #dbe5f2; border-radius: 12px; background: #fff; padding: .82rem .9rem; }
        .ux-kpi-active { background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%); border-color: #bfdbfe; }
        .ux-kpi-overdue { background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%); border-color: #fde68a; }
        .ux-kpi-remaining { background: linear-gradient(180deg, #fff1f2 0%, #ffffff 100%); border-color: #fecdd3; }
        .ux-kpi-label { margin: 0; font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; color: #64748b; font-weight: 700; }
        .ux-kpi-value { margin: .28rem 0 0; font-size: 1.15rem; font-weight: 700; color: #0f172a; }
        .ux-layout { display: grid; gap: 1rem; grid-template-columns: 1fr; align-items: start; }
        @media (min-width: 1120px) { .ux-layout { grid-template-columns: minmax(0,1.38fr) minmax(360px,.62fr); } }
        .ux-side { position: sticky; top: 1rem; }
        @media (max-width: 1119px) { .ux-side { position: static; } }
        .ux-card { background: #fff; border: 1px solid #dbe4f0; border-radius: 14px; box-shadow: 0 10px 22px rgba(15,23,42,.04); overflow: hidden; }
        .ux-card-head { padding: .94rem 1rem; border-bottom: 1px solid #e7eef7; background: linear-gradient(180deg,#fbfdff 0%,#f8fafc 100%); }
        .ux-card-title { margin: 0; font-size: .83rem; text-transform: uppercase; letter-spacing: .08em; color: #334155; font-weight: 700; }
        .ux-card-body { padding: 1rem; }
        .ux-filter-grid {
            display: grid;
            gap: .6rem;
            grid-template-columns: 1fr;
            align-items: stretch;
        }
        .ux-filter-grid > * { min-width: 0; }
        .ux-filter-search {
            grid-column: 1 / -1;
            min-height: 2.56rem;
        }
        .ux-filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            grid-column: 1 / -1;
        }
        .ux-filter-actions .ux-select {
            flex: 1 1 220px;
            min-width: 180px;
            max-width: 280px;
            min-height: 2.56rem;
        }
        .ux-filter-actions .ux-input {
            flex: 0 1 190px;
            min-width: 170px;
            min-height: 2.56rem;
        }
        .ux-filter-actions .ux-btn {
            flex: 0 0 auto;
            min-height: 2.56rem;
            padding-inline: 1rem;
        }
        .ux-filter-shell {
            border: 1px solid #dbe5f2;
            background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
            border-radius: 12px;
            padding: .72rem;
        }
        .ux-form-grid { display: grid; gap: .68rem; }
        .ux-field { display: grid; gap: .32rem; }
        .ux-label { font-size: .72rem; letter-spacing: .05em; text-transform: uppercase; color: #64748b; font-weight: 700; }
        .ux-input, .ux-select, .ux-textarea {
            width: 100%; min-height: 2.56rem; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff;
            color: #0f172a; font-size: .9rem; padding: 0 .72rem;
        }
        .ux-textarea { min-height: 84px; padding: .58rem .72rem; resize: vertical; }
        .ux-input:focus, .ux-select:focus, .ux-textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.16); }
        .ux-btn {
            min-height: 2.46rem; padding: 0 .92rem; border-radius: 10px; border: 1px solid #cbd5e1; background: #fff;
            color: #334155; font-size: .8rem; font-weight: 600; display: inline-flex; align-items: center; justify-content: center;
            white-space: nowrap;
        }
        .ux-btn:hover { background: #f1f5f9; border-color: #93c5fd; color: #1e3a8a; }
        .ux-btn-primary { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        .ux-btn-primary:hover { background: #1e40af; border-color: #1e3a8a; box-shadow: 0 8px 16px rgba(29, 78, 216, .26); }
        .ux-btn-csv { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
        .ux-btn-csv:hover { background: #dbeafe; border-color: #93c5fd; color: #1e40af; }
        .ux-btn-excel { background: #ecfdf5; border-color: #a7f3d0; color: #047857; }
        .ux-btn-excel:hover { background: #d1fae5; border-color: #6ee7b7; color: #065f46; }
        .ux-btn-block { width: 100%; }
        .ux-table-wrap { overflow: hidden; border: 1px solid #e2e8f0; border-radius: 12px; }
        .ux-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .ux-table th:nth-child(1), .ux-table td:nth-child(1) { width: 18%; }
        .ux-table th:nth-child(2), .ux-table td:nth-child(2) { width: 30%; }
        .ux-table th:nth-child(3), .ux-table td:nth-child(3) { width: 27%; }
        .ux-table th:nth-child(4), .ux-table td:nth-child(4) { width: 25%; min-width: 200px; }
        .ux-table th, .ux-table td { padding: .82rem .76rem; border-bottom: 1px solid #e2e8f0; font-size: .84rem; text-align: left; vertical-align: top; }
        .ux-table th { background: #f8fafc; color: #475569; font-weight: 700; }
        .ux-table thead th { border-top: 2px solid #dbeafe; }
        .ux-table th, .ux-table td { word-wrap: break-word; overflow-wrap: anywhere; }
        .ux-customer-cell { display: grid; gap: .28rem; }
        .ux-customer-head { display: flex; align-items: center; justify-content: space-between; gap: .45rem; }
        .ux-customer-name { font-weight: 700; color: #0f172a; }
        .ux-customer-phone { color: #64748b; font-size: .76rem; }
        .ux-number { font-size: .78rem; color: #334155; font-weight: 700; letter-spacing: .01em; }
        .ux-number-wrap { display: grid; gap: .28rem; }
        .ux-number-short { font-weight: 800; color: #0f172a; font-size: .82rem; }
        .ux-subline { font-size: .74rem; color: #64748b; }
        .ux-amount-wrap { display: grid; gap: .18rem; }
        .ux-amount-main { font-weight: 700; color: #0f172a; }
        .ux-chip { display: inline-flex; align-items: center; padding: .2rem .54rem; border-radius: 999px; border: 1px solid; font-size: .65rem; letter-spacing: .04em; font-weight: 800; white-space: nowrap; text-transform: uppercase; }
        .ux-chip-active { color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff; }
        .ux-chip-overdue { color: #b45309; border-color: #fde68a; background: #fffbeb; }
        .ux-chip-paid { color: #047857; border-color: #a7f3d0; background: #ecfdf5; }
        .ux-pay-btn { min-width: 98px; min-height: 2.12rem; }
        .ux-pay-hint { font-size: .73rem; color: #64748b; margin-top: .3rem; }
        .ux-history { margin-top: .45rem; border: 1px dashed #cbd5e1; border-radius: 10px; background: #f8fafc; }
        .ux-history summary { list-style: none; cursor: pointer; padding: .45rem .58rem; font-size: .74rem; color: #334155; font-weight: 700; }
        .ux-history summary::-webkit-details-marker { display: none; }
        .ux-history-list { border-top: 1px dashed #dbe5f2; padding: .5rem .58rem .56rem; display: grid; gap: .38rem; }
        .ux-history-row { font-size: .72rem; color: #475569; line-height: 1.35; }
        .ux-history-amt { color: #0f172a; font-weight: 700; }
        .ux-modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, .5); z-index: 120; display: none; align-items: center; justify-content: center; padding: 1rem; }
        .ux-modal-backdrop.show { display: flex; }
        .ux-modal { width: 100%; max-width: 460px; background: #fff; border: 1px solid #dbe5f2; border-radius: 14px; box-shadow: 0 22px 40px rgba(15,23,42,.22); overflow: hidden; }
        .ux-modal-head { padding: .9rem 1rem; border-bottom: 1px solid #e2e8f0; background: linear-gradient(180deg,#fbfdff 0%,#f8fafc 100%); font-weight: 700; color: #1e293b; }
        .ux-modal-body { padding: 1rem; display: grid; gap: .72rem; }
        .ux-modal-foot { padding: .9rem 1rem; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: .55rem; }
        .ux-note-box { border: 1px solid #e2e8f0; border-radius: 12px; padding: .72rem; background: linear-gradient(180deg, #fff 0%, #f8fbff 100%); }
    </style>

    <div class="ux-shell customer-module intro-y space-y-4">
        @php($formatRupiah = fn ($amount) => 'Rp ' . number_format((float) $amount, 0, ',', '.'))
        @if(session('success'))
            <div class="ux-alert ux-alert-ok">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="ux-alert ux-alert-err">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="ux-kpi">
            <div class="ux-kpi-card ux-kpi-active"><p class="ux-kpi-label">Active</p><p class="ux-kpi-value">{{ number_format($summary['active'], 0, ',', '.') }}</p></div>
            <div class="ux-kpi-card ux-kpi-overdue"><p class="ux-kpi-label">Overdue</p><p class="ux-kpi-value" style="color:#b45309">{{ number_format($summary['overdue'], 0, ',', '.') }}</p></div>
            <div class="ux-kpi-card ux-kpi-remaining"><p class="ux-kpi-label">Total Sisa</p><p class="ux-kpi-value" style="color:#be123c">{{ $formatRupiah($summary['total_remaining']) }}</p></div>
        </div>
        <div class="ux-aging-kpi">
            <div class="ux-kpi-card ux-kpi-active"><p class="ux-kpi-label">Belum Jatuh Tempo</p><p class="ux-kpi-value">{{ number_format((int) ($summary['aging_current'] ?? 0), 0, ',', '.') }}</p></div>
            <div class="ux-kpi-card ux-kpi-overdue"><p class="ux-kpi-label">Overdue 1-7 Hari</p><p class="ux-kpi-value" style="color:#b45309">{{ number_format((int) ($summary['aging_overdue_1_7'] ?? 0), 0, ',', '.') }}</p></div>
            <div class="ux-kpi-card ux-kpi-overdue"><p class="ux-kpi-label">Overdue 8-30 Hari</p><p class="ux-kpi-value" style="color:#b45309">{{ number_format((int) ($summary['aging_overdue_8_30'] ?? 0), 0, ',', '.') }}</p></div>
            <div class="ux-kpi-card ux-kpi-remaining"><p class="ux-kpi-label">Overdue >30 Hari</p><p class="ux-kpi-value" style="color:#be123c">{{ number_format((int) ($summary['aging_overdue_30_plus'] ?? 0), 0, ',', '.') }}</p></div>
        </div>

        <div class="ux-layout">
            <div class="ux-card">
                <div class="ux-card-head"><h3 class="ux-card-title">Daftar Piutang Pelanggan</h3></div>
                <div class="ux-card-body space-y-4">
                    <form method="GET" class="ux-filter-grid ux-filter-shell">
                        <input type="text" name="q" value="{{ $q }}" class="ux-input ux-filter-search" placeholder="Cari nomor hutang, nama, atau no HP pelanggan">
                        <div class="ux-filter-actions">
                            <input type="date" name="from_date" value="{{ $fromDate ?? '' }}" class="ux-input" title="Dari tanggal hutang">
                            <input type="date" name="to_date" value="{{ $toDate ?? '' }}" class="ux-input" title="Sampai tanggal hutang">
                            <select name="status" class="ux-select">
                                <option value="all" @selected($status==='all')>Semua Status</option>
                                <option value="active" @selected($status==='active')>Active</option>
                                <option value="overdue" @selected($status==='overdue')>Overdue</option>
                                <option value="paid" @selected($status==='paid')>Paid</option>
                            </select>
                            <select name="aging" class="ux-select">
                                <option value="all" @selected(($aging ?? 'all')==='all')>Semua Aging</option>
                                <option value="current" @selected(($aging ?? '')==='current')>Belum Jatuh Tempo</option>
                                <option value="overdue_1_7" @selected(($aging ?? '')==='overdue_1_7')>Overdue 1-7 Hari</option>
                                <option value="overdue_8_30" @selected(($aging ?? '')==='overdue_8_30')>Overdue 8-30 Hari</option>
                                <option value="overdue_30_plus" @selected(($aging ?? '')==='overdue_30_plus')>Overdue >30 Hari</option>
                            </select>
                            <button class="ux-btn ux-btn-primary" type="submit">Terapkan Filter</button>
                            <a href="{{ route('customers.debts.export.csv', request()->query()) }}" class="ux-btn ux-btn-csv ux-export-btn">Export CSV</a>
                            <a href="{{ route('customers.debts.export.excel', request()->query()) }}" class="ux-btn ux-btn-excel ux-export-btn">Export Excel</a>
                        </div>
                    </form>

                    <div class="ux-table-wrap">
                        <table class="ux-table">
                            <thead>
                                <tr>
                                    <th>Nomor</th>
                                    <th>Pelanggan</th>
                                    <th>Sisa / Jatuh Tempo</th>
                                    <th>Pembayaran</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($debts as $debt)
                                    <tr>
                                        <td>
                                            <div class="ux-number-wrap">
                                                @php($shortNumber = preg_match('/(\d{4})$/', (string) $debt->number, $m) ? ('AR-' . $m[1]) : (string) $debt->number)
                                                <span class="ux-number-short">{{ $shortNumber ?: $debt->number }}</span>
                                                <span class="ux-chip {{ $debt->status === 'paid' ? 'ux-chip-paid' : ($debt->status === 'overdue' ? 'ux-chip-overdue' : 'ux-chip-active') }}">
                                                    {{ $debt->status }}
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="ux-customer-cell">
                                                <div class="ux-customer-head">
                                                    <span class="ux-customer-name">{{ $debt->customer?->name }}</span>
                                                </div>
                                                <div class="ux-customer-phone">{{ $debt->customer?->phone ?: '-' }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="ux-amount-wrap">
                                                <div class="ux-amount-main">{{ $formatRupiah($debt->remaining_amount) }}</div>
                                                <div class="ux-subline">JT: {{ $debt->due_date ? $debt->due_date->format('d/m/Y') : '-' }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            @if(in_array($debt->status, ['active','overdue'], true))
                                                <button
                                                    class="ux-btn ux-btn-primary ux-pay-btn"
                                                    type="button"
                                                    data-open-pay-modal="1"
                                                    data-pay-action="{{ route('customers.debts.pay', $debt) }}"
                                                    data-pay-number="{{ $debt->number }}"
                                                    data-pay-customer="{{ $debt->customer?->name }}"
                                                    data-pay-remaining="{{ (float) $debt->remaining_amount }}"
                                                >Bayar</button>
                                                <div class="ux-pay-hint">Sisa: {{ $formatRupiah($debt->remaining_amount) }}</div>
                                            @else
                                                <span class="text-slate-400">Lunas</span>
                                            @endif
                                            @if($debt->payments->isNotEmpty())
                                                <details class="ux-history">
                                                    <summary>Riwayat Cicilan ({{ $debt->payments->count() }})</summary>
                                                    <div class="ux-history-list">
                                                        @foreach($debt->payments->take(5) as $payment)
                                                            <div class="ux-history-row">
                                                                <div><span class="ux-history-amt">{{ $formatRupiah($payment->amount) }}</span> - {{ strtoupper(str_replace('_', ' ', (string) $payment->payment_method)) }}</div>
                                                                <div>{{ optional($payment->paid_at)->format('d/m/Y H:i') ?: '-' }} oleh {{ $payment->receiver?->name ?: '-' }}</div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </details>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-slate-500 py-8">Belum ada data hutang pelanggan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>{{ $debts->links() }}</div>
                </div>
            </div>

            <div class="ux-side">
                <div class="ux-card">
                    <div class="ux-card-head"><h3 class="ux-card-title">Input Hutang Baru</h3></div>
                    <div class="ux-card-body">
                        <form method="POST" action="{{ route('customers.debts.store') }}" class="ux-form-grid" data-ajax-form="1">
                            @csrf
                            <label class="ux-field">
                                <span class="ux-label">Pelanggan</span>
                                <select name="customer_id" class="ux-select" required>
                                    <option value="">Pilih pelanggan</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->phone ?: '-' }})</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="ux-field">
                                <span class="ux-label">Link Transaksi Pending (Opsional)</span>
                                <select name="sale_id" class="ux-select">
                                    <option value="">Tanpa link transaksi</option>
                                    @foreach($pendingSales as $sale)
                                        <option value="{{ $sale->id }}">{{ $sale->invoice_number }} - {{ $formatRupiah($sale->total_amount) }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <div class="ux-note-box">
                                <div class="grid grid-cols-1 gap-3">
                                    <label class="ux-field"><span class="ux-label">Tanggal Hutang</span><input type="date" name="debt_date" value="{{ now()->toDateString() }}" class="ux-input" required></label>
                                    <label class="ux-field"><span class="ux-label">Jatuh Tempo</span><input type="date" name="due_date" class="ux-input"></label>
                                </div>
                            </div>
                            <label class="ux-field"><span class="ux-label">Pokok Hutang</span><input type="number" min="1" step="0.01" name="principal_amount" class="ux-input" required></label>
                            <label class="ux-field"><span class="ux-label">Catatan</span><textarea name="note" rows="2" class="ux-textarea"></textarea></label>
                            <button class="ux-btn ux-btn-primary ux-btn-block" type="submit">Simpan Hutang</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="ux-pay-modal" class="ux-modal-backdrop" aria-hidden="true">
        <div class="ux-modal">
            <div class="ux-modal-head">Pembayaran Piutang</div>
            <form id="ux-pay-form" method="POST" data-ajax-form="1">
                @csrf
                <div class="ux-modal-body">
                    <div class="ux-note-box">
                        <div class="ux-subline"><strong id="ux-pay-number">-</strong></div>
                        <div class="ux-subline" id="ux-pay-customer">-</div>
                        <div class="ux-subline">Sisa saat ini: <strong id="ux-pay-remaining">{{ $formatRupiah(0) }}</strong></div>
                    </div>
                    <label class="ux-field">
                        <span class="ux-label">Nominal Bayar</span>
                        <input id="ux-pay-amount" type="number" min="1" step="0.01" name="amount" class="ux-input" required>
                    </label>
                    <label class="ux-field">
                        <span class="ux-label">Metode Pembayaran</span>
                        <select name="payment_method" class="ux-select">
                            <option value="cash">Cash</option>
                            <option value="transfer">Transfer</option>
                            <option value="debit">Debit</option>
                            <option value="qris">QRIS</option>
                            <option value="e_wallet">E-Wallet</option>
                        </select>
                    </label>
                </div>
                <div class="ux-modal-foot">
                    <button class="ux-btn" type="button" id="ux-pay-cancel">Batal</button>
                    <button class="ux-btn ux-btn-primary" type="submit">Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        (function () {
            const toast = (message, type = 'success') => (window.AppUI?.toast ? window.AppUI.toast(String(message || ''), type) : null);
            const modal = document.getElementById('ux-pay-modal');
            const payForm = document.getElementById('ux-pay-form');
            const payAmount = document.getElementById('ux-pay-amount');
            const payNumber = document.getElementById('ux-pay-number');
            const payCustomer = document.getElementById('ux-pay-customer');
            const payRemaining = document.getElementById('ux-pay-remaining');

            const formatRupiah = (value) => {
                const amount = Number(value || 0);
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(amount).replace('IDR', 'Rp').replace(/\s+/g, ' ').trim();
            };

            const openPayModal = (button) => {
                if (!modal || !payForm || !payAmount || !payNumber || !payCustomer || !payRemaining) return;
                const action = button.getAttribute('data-pay-action') || '';
                const number = button.getAttribute('data-pay-number') || '-';
                const customer = button.getAttribute('data-pay-customer') || '-';
                const remaining = Number(button.getAttribute('data-pay-remaining') || 0);
                payForm.action = action;
                payNumber.textContent = number;
                payCustomer.textContent = customer;
                payRemaining.textContent = formatRupiah(remaining);
                payAmount.value = remaining > 0 ? String(remaining) : '';
                payAmount.max = remaining > 0 ? String(remaining) : '';
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
            };

            const closePayModal = () => {
                if (!modal) return;
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
            };

            document.querySelectorAll('[data-open-pay-modal="1"]').forEach((button) => {
                button.addEventListener('click', () => openPayModal(button));
            });
            document.getElementById('ux-pay-cancel')?.addEventListener('click', closePayModal);
            modal?.addEventListener('click', (event) => {
                if (event.target === modal) closePayModal();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') closePayModal();
            });

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            document.querySelectorAll('form[data-ajax-form="1"]').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const btn = form.querySelector('button[type="submit"]');
                    if (btn) btn.disabled = true;
                    try {
                        const resp = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                            },
                            body: new FormData(form),
                        });
                        if (!resp.ok) {
                            const data = await resp.json().catch(() => ({}));
                            const msg = data?.message || Object.values(data?.errors || {})?.flat?.()[0] || 'Gagal menyimpan data.';
                            throw new Error(msg);
                        }
                        toast('Perubahan berhasil disimpan.', 'success');
                        setTimeout(() => window.location.reload(), 300);
                    } catch (err) {
                        toast(err?.message || 'Terjadi kesalahan.', 'error');
                    } finally {
                        if (btn) btn.disabled = false;
                    }
                });
            });
        })();
    </script>
</x-app-layout>
