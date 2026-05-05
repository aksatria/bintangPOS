<x-app-layout>
    <x-slot name="header">
        <div class="ac-head">
            <div>
                <p class="ac-kicker">Akuntansi</p>
                <h2 class="ac-title">Laporan Akuntansi</h2>
                <p class="ac-sub">Trial balance, laba rugi, dan neraca ringkas dari jurnal otomatis.</p>
            </div>
            <div class="ac-head-actions">
                <a href="{{ route('admin.accounting.reports') }}" class="ac-btn ac-btn-nav ac-btn-nav-report ac-btn-nav-active">Laporan Akuntansi</a>
                <a href="{{ route('admin.accounting.journals') }}" class="ac-btn ac-btn-nav ac-btn-nav-journal">Jurnal Umum</a>
                <a href="{{ route('admin.accounting.ledger') }}" class="ac-btn ac-btn-nav ac-btn-nav-ledger">Buku Besar</a>
            </div>
        </div>
    </x-slot>

    <div class="ac-shell">
        <section class="ac-card ac-hero-card">
            <div class="ac-card-body ac-hero">
                <div>
                    <p class="ac-blue-note">ERP Accounting</p>
                    <h1 class="ac-hero-title">Trial Balance, Laba Rugi, dan Neraca.</h1>
                    <p class="ac-hero-text">Bersumber dari jurnal otomatis pembelian supplier, pembayaran, retur, dan penjualan POS. Gunakan halaman ini untuk mengecek keseimbangan debit-kredit dan membaca kondisi usaha secara ringkas.</p>
                </div>
            </div>
            <form method="GET" class="ac-filter-panel">
                <div class="ac-filter-grid">
                    <label class="ac-field">
                        <span class="ac-label">Dari Tanggal</span>
                        <input type="date" name="from" value="{{ $from }}" class="ac-input">
                    </label>
                    <label class="ac-field">
                        <span class="ac-label">Sampai Tanggal</span>
                        <input type="date" name="to" value="{{ $to }}" class="ac-input">
                    </label>
                    <button class="ac-btn ac-btn-primary ac-filter-btn" type="submit">Terapkan Filter</button>
                </div>
            </form>
        </section>

        <section class="ac-kpi-grid">
            <div class="ac-kpi">
                <span>Total Debit</span>
                <strong>Rp {{ number_format($trialDebit, 0, ',', '.') }}</strong>
                <small>Akumulasi debit periode terpilih.</small>
            </div>
            <div class="ac-kpi">
                <span>Total Kredit</span>
                <strong>Rp {{ number_format($trialCredit, 0, ',', '.') }}</strong>
                <small>Akumulasi kredit periode terpilih.</small>
            </div>
            <div class="ac-kpi">
                <span>Laba Bersih</span>
                <strong class="{{ $netIncome >= 0 ? 'ac-text-ok' : 'ac-text-danger' }}">Rp {{ number_format($netIncome, 0, ',', '.') }}</strong>
                <small>Penjualan dikurangi potongan dan beban.</small>
            </div>
            <div class="ac-kpi {{ abs($trialDebit - $trialCredit) <= 0.01 ? 'ac-kpi-ok' : 'ac-kpi-danger' }}">
                <span>Validasi</span>
                <strong>{{ abs($trialDebit - $trialCredit) <= 0.01 ? 'Debit = Kredit' : 'Selisih Rp '.number_format(abs($trialDebit - $trialCredit), 0, ',', '.') }}</strong>
                <small>Cek dasar sebelum laporan dipakai.</small>
            </div>
        </section>

        <section class="ac-report-grid">
            <div class="ac-card">
                <div class="ac-card-head">
                    <div>
                        <h3 class="ac-card-title">Laba Rugi</h3>
                        <p class="ac-card-sub">Ringkasan pendapatan dan beban dari jurnal.</p>
                    </div>
                </div>
                <div class="ac-card-body ac-summary-list">
                    <div><span>Penjualan</span><strong>Rp {{ number_format($income, 0, ',', '.') }}</strong></div>
                    <div><span>Potongan Penjualan</span><strong class="ac-text-danger">Rp {{ number_format($contraIncome, 0, ',', '.') }}</strong></div>
                    <div><span>Beban</span><strong class="ac-text-danger">Rp {{ number_format($expense, 0, ',', '.') }}</strong></div>
                    <div><span>Pengurang Beban</span><strong class="ac-text-ok">Rp {{ number_format($contraExpense, 0, ',', '.') }}</strong></div>
                    <div class="ac-summary-total"><span>Laba Bersih</span><strong class="{{ $netIncome >= 0 ? 'ac-text-ok' : 'ac-text-danger' }}">Rp {{ number_format($netIncome, 0, ',', '.') }}</strong></div>
                </div>
            </div>

            <div class="ac-card">
                <div class="ac-card-head">
                    <div>
                        <h3 class="ac-card-title">Neraca Ringkas</h3>
                        <p class="ac-card-sub">Posisi aset, kewajiban, dan ekuitas berjalan.</p>
                    </div>
                </div>
                <div class="ac-card-body ac-summary-list">
                    <div><span>Aset</span><strong>Rp {{ number_format($assets, 0, ',', '.') }}</strong></div>
                    <div><span>Kewajiban</span><strong>Rp {{ number_format($liabilities, 0, ',', '.') }}</strong></div>
                    <div class="ac-summary-total"><span>Ekuitas Berjalan</span><strong class="ac-link-text">Rp {{ number_format($equityClosing, 0, ',', '.') }}</strong></div>
                    <p class="ac-note">Ekuitas berjalan dihitung dari aset dikurangi kewajiban. Untuk laporan formal akhir periode, modal awal, laba berjalan, dan prive dapat dipisah pada tahap tutup buku.</p>
                </div>
            </div>
        </section>

        <section class="ac-card">
            <div class="ac-card-head">
                <div>
                    <h3 class="ac-card-title">Neraca Saldo</h3>
                    <p class="ac-card-sub">Daftar akun beserta total debit, kredit, dan saldo normal.</p>
                </div>
            </div>
            <div class="ac-card-body">
                <div class="ac-note">
                    <strong style="display:block; margin-bottom:.45rem;">Arti kode akun:</strong>
                    <div class="ac-code-grid">
                        @foreach($accountCodeHints as $code => $desc)
                            <div><span>{{ $code }}</span> {{ $desc }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="ac-table-wrap">
                <table class="ac-table">
                    <thead>
                        <tr>
                            <th>Kode & Nama Akun</th>
                            <th style="width:16%">Tipe</th>
                            <th class="ac-money" style="width:16%">Debit</th>
                            <th class="ac-money" style="width:16%">Kredit</th>
                            <th class="ac-money" style="width:17%">Saldo Normal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($accounts as $account)
                            <tr>
                                <td>
                                    <div class="ac-cell-title">{{ $account->code }}</div>
                                    <div class="ac-cell-sub">{{ $account->name }}</div>
                                </td>
                                <td><span class="ac-chip ac-chip-muted">{{ $typeLabels[$account->type] ?? str_replace('_', ' ', $account->type) }}</span></td>
                                <td class="ac-money">Rp {{ number_format((float) $account->debit_total, 0, ',', '.') }}</td>
                                <td class="ac-money">Rp {{ number_format((float) $account->credit_total, 0, ',', '.') }}</td>
                                <td class="ac-money ac-strong">Rp {{ number_format((float) $account->balance, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
