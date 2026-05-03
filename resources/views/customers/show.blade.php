<x-app-layout>
    @php($idr = fn ($amount) => 'Rp ' . number_format((float) $amount, 0, ',', '.'))
    <style>
        .customer-detail-page .ux-card-head { padding: .94rem 1rem; border-bottom: 1px solid #e7eef7; background: linear-gradient(180deg,#fbfdff 0%,#f8fafc 100%); }
        .customer-detail-page .ux-card-title { margin: 0; font-size: .83rem; text-transform: uppercase; letter-spacing: .08em; color: #334155; font-weight: 700; }
        .customer-detail-page .ux-card-body { padding: 1rem; }
        .customer-detail-page .ux-filter-grid { display: grid; gap: .6rem; }
        .customer-detail-page .ux-filter-actions { display: flex; flex-wrap: wrap; gap: .6rem; }
        .customer-detail-page .ux-filter-shell { border: 1px solid #dbe5f2; background: linear-gradient(180deg,#f8fbff 0%,#ffffff 100%); border-radius: 12px; padding: .72rem; }
        .customer-detail-page .ux-label { font-size: .72rem; letter-spacing: .05em; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: .28rem; display: block; }
        .customer-detail-page .ux-input,
        .customer-detail-page .ux-select { width: 100%; min-height: 2.56rem; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; color: #0f172a; font-size: .9rem; padding: 0 .72rem; }
        .customer-detail-page .ux-input:focus,
        .customer-detail-page .ux-select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.16); }
        .customer-detail-page .ux-btn { min-height: 2.46rem; padding: 0 .92rem; border-radius: 10px; border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: .8rem; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; white-space: nowrap; }
        .customer-detail-page .ux-btn:hover { background: #f1f5f9; border-color: #93c5fd; color: #1e3a8a; }
        .customer-detail-page .ux-btn-primary { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        .customer-detail-page .ux-btn-primary:hover { background: #1e40af; border-color: #1e3a8a; box-shadow: 0 8px 16px rgba(29,78,216,.26); }
        .customer-detail-page .ux-note-box { border: 1px solid #e2e8f0; border-radius: 12px; padding: .72rem; background: linear-gradient(180deg,#fff 0%,#f8fbff 100%); }
        .customer-detail-page .ux-table-wrap { overflow: hidden; border: 1px solid #e2e8f0; border-radius: 12px; }
        .customer-detail-page .ux-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .customer-detail-page .ux-table th,
        .customer-detail-page .ux-table td { padding: .82rem .76rem; border-bottom: 1px solid #e2e8f0; font-size: .84rem; text-align: left; vertical-align: top; }
        .customer-detail-page .ux-table th { background: #f8fafc; color: #475569; font-weight: 700; }
        .customer-detail-page .ux-table thead th { border-top: 2px solid #dbeafe; }
    </style>
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

    <div class="page-shell customer-module customer-detail-page space-y-6">
        <div class="panel-card p-5 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div><p class="metric-label">Status</p><span class="status-chip customer-status-chip {{ $customer->is_active ? 'status-paid' : 'status-cancelled' }}">{{ $customer->is_active ? 'AKTIF' : 'NONAKTIF' }}</span></div>
            <div><p class="metric-label">Segmentasi</p><span class="customer-segment-chip customer-segment-chip--{{ strtolower($summary['segment']) }}">{{ $summary['segment'] }}</span></div>
            <div><p class="metric-label">No HP</p><p class="text-base font-semibold text-slate-800">{{ $customer->phone ?: '-' }}</p></div>
            <div><p class="metric-label">Email</p><p class="text-base font-semibold text-slate-800">{{ $customer->email ?: '-' }}</p></div>
            <div><p class="metric-label">Total Transaksi</p><p class="text-base font-semibold text-slate-800">{{ number_format($summary['transactions'], 0, ',', '.') }}</p></div>
            <div><p class="metric-label">Transaksi Pending</p><p class="text-base font-semibold text-amber-700">{{ number_format($summary['pending_count'], 0, ',', '.') }}</p></div>
            <div><p class="metric-label">Total Belanja</p><p class="text-base font-semibold text-slate-800">{{ $idr($summary['total_spending']) }}</p></div>
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

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
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

        <div class="panel-card ux-card overflow-hidden">
            <div class="panel-head ux-card-head">
                <h3 class="text-base font-semibold text-slate-900 ux-card-title">Piutang & Histori Cicilan</h3>
            </div>
            <div class="p-5 ux-card-body space-y-4">
                <div class="customer-kpi-grid">
                    <div class="customer-kpi-card customer-kpi-card--blue">
                        <span>Piutang Aktif/Overdue</span>
                        <strong>{{ number_format((int) ($debtSummary['active_overdue_count'] ?? 0), 0, ',', '.') }}</strong>
                    </div>
                    <div class="customer-kpi-card customer-kpi-card--rose">
                        <span>Total Sisa</span>
                        <strong>{{ $idr((float) ($debtSummary['remaining_total'] ?? 0)) }}</strong>
                    </div>
                    <div class="customer-kpi-card customer-kpi-card--green">
                        <span>Total Cicilan Dibayar</span>
                        <strong>{{ $idr((float) ($debtSummary['paid_total'] ?? 0)) }}</strong>
                    </div>
                </div>
                <form method="GET" action="{{ route('customers.show', $customer) }}" class="ux-filter-grid ux-filter-shell">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="hidden" name="from" value="{{ $from }}">
                    <input type="hidden" name="to" value="{{ $to }}">
                    <div class="ux-filter-actions">
                    <div>
                        <label class="ux-label">Dari Tanggal</label>
                        <input type="date" name="debt_from_date" class="ux-input" value="{{ $debtFromDate }}">
                    </div>
                    <div>
                        <label class="ux-label">Sampai Tanggal</label>
                        <input type="date" name="debt_to_date" class="ux-input" value="{{ $debtToDate }}">
                    </div>
                    <div>
                        <label class="ux-label">Status</label>
                        <select name="debt_status" class="ux-select">
                            <option value="all" @selected($debtStatus === 'all')>Semua</option>
                            <option value="active" @selected($debtStatus === 'active')>Active</option>
                            <option value="overdue" @selected($debtStatus === 'overdue')>Overdue</option>
                            <option value="paid" @selected($debtStatus === 'paid')>Paid</option>
                        </select>
                    </div>
                    <div>
                        <label class="ux-label">Aging</label>
                        <select name="debt_aging" class="ux-select">
                            <option value="all" @selected($debtAging === 'all')>Semua</option>
                            <option value="current" @selected($debtAging === 'current')>Belum Jatuh Tempo</option>
                            <option value="overdue_1_7" @selected($debtAging === 'overdue_1_7')>Overdue 1-7</option>
                            <option value="overdue_8_30" @selected($debtAging === 'overdue_8_30')>Overdue 8-30</option>
                            <option value="overdue_30_plus" @selected($debtAging === 'overdue_30_plus')>Overdue >30</option>
                        </select>
                    </div>
                    <button type="submit" class="ux-btn ux-btn-primary">Terapkan</button>
                    <a href="{{ route('customers.show', $customer) }}" class="ux-btn">Reset</a>
                    </div>
                </form>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 md:p-4 ux-note-box">
                    <form id="customer-fifo-pay-form" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <div class="md:col-span-2">
                            <label class="ux-label">Bayar Parsial Otomatis (FIFO)</label>
                            <input type="number" name="amount" class="ux-input" min="1" step="0.01" placeholder="Masukkan nominal pembayaran" required>
                        </div>
                        <div>
                            <label class="ux-label">Metode</label>
                            <select name="payment_method" class="ux-select">
                                <option value="cash">Cash</option>
                                <option value="transfer">Transfer</option>
                                <option value="debit">Debit</option>
                                <option value="qris">QRIS</option>
                                <option value="e_wallet">E-Wallet</option>
                            </select>
                        </div>
                        <div>
                            <label class="ux-label">Tanggal</label>
                            <input type="datetime-local" name="paid_at" class="ux-input">
                        </div>
                        <button type="submit" class="ux-btn ux-btn-primary w-full">Bayar Otomatis</button>
                    </form>
                </div>

                <div class="overflow-x-auto ux-table-wrap">
                    <table class="ux-table">
                        <thead>
                            <tr>
                                <th>No Piutang</th>
                                <th>Invoice</th>
                                <th>Status</th>
                                <th>Sisa</th>
                                <th>Jatuh Tempo</th>
                                <th>Aksi Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($customerDebts ?? collect()) as $debt)
                                <tr class="customer-row">
                                    <td>{{ $debt->number }}</td>
                                    <td>{{ $debt->sale?->invoice_number ?: '-' }}</td>
                                    <td>{{ strtoupper($debt->status) }}</td>
                                    <td class="text-right customer-amount-cell">{{ $idr((float) $debt->remaining_amount) }}</td>
                                    <td>{{ $debt->due_date ? $debt->due_date->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        @if(in_array((string) $debt->status, ['active', 'overdue'], true))
                                            <button
                                                type="button"
                                                class="ux-btn ux-btn-primary w-full js-open-pay-modal"
                                                data-action="{{ route('customers.debts.pay', $debt) }}"
                                                data-number="{{ $debt->number }}"
                                                data-remaining="{{ (float) $debt->remaining_amount }}"
                                            >
                                                Bayar Cicilan
                                            </button>
                                        @else
                                            <span class="text-slate-400 text-xs">Lunas</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-slate-500 py-8">Belum ada data piutang untuk pelanggan ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pt-2">
                    {{ $customerDebts->links() }}
                </div>

                <div class="overflow-x-auto ux-table-wrap">
                    <table class="ux-table">
                        <thead>
                            <tr>
                                <th>Tanggal Bayar</th>
                                <th>Ref Piutang</th>
                                <th>Metode</th>
                                <th>Nominal</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($debtPaymentHistory ?? collect()) as $pay)
                                <tr class="customer-row">
                                    <td>{{ optional($pay->paid_at)->format('d/m/Y H:i') ?: '-' }}</td>
                                    <td>#{{ $pay->customer_debt_id }}</td>
                                    <td>{{ strtoupper(str_replace('_', ' ', (string) $pay->payment_method)) }}</td>
                                    <td class="text-right customer-amount-cell">{{ $idr((float) $pay->amount) }}</td>
                                    <td>{{ $pay->note ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-slate-500 py-8">Belum ada histori cicilan pelanggan ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pt-2">
                    {{ $debtPaymentHistory->links() }}
                </div>
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
                            <th>Nilai Pending</th>
                            <th class="w-[44%]">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingSales as $sale)
                            @php($ageDays = $sale->sold_at ? (int) round(\Illuminate\Support\Carbon::parse($sale->sold_at)->diffInDays(now())) : 0)
                            @php($ageClass = $ageDays >= 8 ? 'customer-aging-chip--critical' : ($ageDays >= 4 ? 'customer-aging-chip--warning' : 'customer-aging-chip--normal'))
                            <tr class="customer-row">
                                <td>
                                    <div class="customer-contact-main">{{ $sale->sold_at?->format('d/m/Y') }}</div>
                                    <div class="customer-contact-sub">{{ $sale->sold_at?->format('H:i') }}</div>
                                </td>
                                <td>
                                    <div class="customer-contact-main">{{ $sale->invoice_number }}</div>
                                    <span class="customer-aging-chip {{ $ageClass }}">{{ $ageDays }} hari</span>
                                </td>
                                <td class="text-right customer-amount-cell">{{ $idr($sale->total_amount) }}</td>
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
                            <th>Total</th>
                            <th>Aksi</th>
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
                                <td class="text-right customer-amount-cell">{{ $idr($sale->total_amount) }}</td>
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
                            <th>Qty Total</th>
                            <th>Nilai Belanja</th>
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
                                <td class="text-right customer-amount-cell">{{ $idr((float) $item->total_spending) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-slate-500 py-8">Belum ada produk untuk ditampilkan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div id="customer-pay-modal" class="ux-modal-backdrop hidden fixed inset-0 z-[9998] bg-slate-900/50 items-center justify-center p-4">
        <div class="ux-modal w-full max-w-lg rounded-2xl bg-white shadow-xl border border-slate-200">
            <div class="ux-modal-head px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h4 class="text-base font-semibold text-slate-900">Pembayaran Cicilan</h4>
                <button type="button" class="text-slate-500 hover:text-slate-800 js-close-pay-modal">Tutup</button>
            </div>
            <form id="customer-pay-form" class="ux-modal-body p-5 space-y-3">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="action" value="">
                <div class="text-sm text-slate-600">No Piutang: <strong id="customer-pay-number" class="text-slate-900">-</strong></div>
                <div>
                    <label class="label-ui">Nominal Bayar</label>
                    <input type="number" name="amount" class="ux-input" min="1" step="0.01" required>
                </div>
                <div>
                    <label class="label-ui">Metode</label>
                    <select name="payment_method" class="ux-select">
                        <option value="cash">Cash</option>
                        <option value="transfer">Transfer</option>
                        <option value="debit">Debit</option>
                        <option value="qris">QRIS</option>
                        <option value="e_wallet">E-Wallet</option>
                    </select>
                </div>
                <div>
                    <label class="label-ui">Tanggal Bayar</label>
                    <input type="datetime-local" name="paid_at" class="ux-input">
                </div>
                <div>
                    <label class="label-ui">Catatan (opsional)</label>
                    <input type="text" name="note" class="ux-input" maxlength="500" placeholder="Contoh: pembayaran via kasir shift pagi">
                </div>
                <button type="submit" class="ux-btn ux-btn-primary w-full">Simpan Pembayaran</button>
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

        (function () {
            const toast = (message, type = 'success') => (window.AppUI?.toast ? window.AppUI.toast(String(message || ''), type) : null);
            const modal = document.getElementById('customer-pay-modal');
            const payForm = document.getElementById('customer-pay-form');
            const fifoForm = document.getElementById('customer-fifo-pay-form');
            if (!modal || !payForm) return;

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };
            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            document.querySelectorAll('.js-open-pay-modal').forEach((button) => {
                button.addEventListener('click', () => {
                    const action = button.getAttribute('data-action') || '';
                    const number = button.getAttribute('data-number') || '-';
                    const remaining = Number(button.getAttribute('data-remaining') || 0);
                    payForm.querySelector('input[name="action"]').value = action;
                    payForm.querySelector('input[name="amount"]').value = remaining > 0 ? remaining.toFixed(0) : '';
                    const numberNode = document.getElementById('customer-pay-number');
                    if (numberNode) numberNode.textContent = number;
                    openModal();
                });
            });

            document.querySelectorAll('.js-close-pay-modal').forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });

            payForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const action = payForm.querySelector('input[name="action"]').value;
                if (!action) return;

                const formData = new FormData(payForm);
                formData.delete('action');

                try {
                    const response = await fetch(action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData,
                    });
                    const json = await response.json();
                    if (!response.ok || json?.ok === false) {
                        throw new Error(json?.message || 'Gagal menyimpan pembayaran cicilan.');
                    }
                    toast(json?.message || 'Pembayaran cicilan berhasil dicatat.', 'success');
                    closeModal();
                    window.location.reload();
                } catch (error) {
                    toast(error?.message || 'Terjadi kesalahan.', 'error');
                }
            });

            if (fifoForm) {
                fifoForm.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    try {
                        const response = await fetch(@json(route('customers.debts.pay-fifo', $customer)), {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: new FormData(fifoForm),
                        });
                        const json = await response.json();
                        if (!response.ok || json?.ok === false) {
                            throw new Error(json?.message || 'Gagal memproses pembayaran parsial.');
                        }
                        toast(json?.message || 'Pembayaran parsial berhasil diproses.', 'success');
                        window.location.reload();
                    } catch (error) {
                        toast(error?.message || 'Terjadi kesalahan.', 'error');
                    }
                });
            }
        })();
    </script>
</x-app-layout>
