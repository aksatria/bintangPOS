<x-app-layout>
    <x-slot name="header">
        <div class="ac-head">
            <div>
                <p class="ac-kicker">Akuntansi</p>
                <h2 class="ac-title">Jurnal Umum</h2>
                <p class="ac-sub">Audit jurnal otomatis dari pembelian supplier, pembayaran, retur, dan POS.</p>
            </div>
            <div class="ac-head-actions">
                <a href="{{ route('admin.accounting.reports') }}" class="ac-btn ac-btn-nav ac-btn-nav-report">Laporan Akuntansi</a>
                <a href="{{ route('admin.accounting.journals') }}" class="ac-btn ac-btn-nav ac-btn-nav-journal ac-btn-nav-active">Jurnal Umum</a>
                <a href="{{ route('admin.accounting.ledger') }}" class="ac-btn ac-btn-nav ac-btn-nav-ledger">Buku Besar</a>
            </div>
        </div>
    </x-slot>

    <div class="ac-shell">
        <section class="ac-card ac-hero-card">
            <div class="ac-card-body ac-hero">
                <div>
                    <p class="ac-blue-note">Posting otomatis ERP</p>
                    <h1 class="ac-hero-title">Semua jurnal tersusun rapi dan bisa diaudit.</h1>
                    <p class="ac-hero-text">Setiap transaksi otomatis membentuk debit-kredit. Detail akun ditampilkan langsung di bawah jurnal agar mudah dicek tanpa membuka halaman lain.</p>
                </div>
                <div class="ac-hero-stats">
                    <div class="ac-mini-stat"><span>Total Jurnal</span><strong>{{ number_format($entries->total(), 0, ',', '.') }}</strong></div>
                </div>
            </div>
        </section>

        <section class="ac-card">
            <div class="ac-card-head">
                <div>
                    <h3 class="ac-card-title">Daftar Jurnal</h3>
                    <p class="ac-card-sub">Urutan terbaru berdasarkan tanggal posting dan nomor jurnal.</p>
                </div>
                <span class="ac-count">{{ number_format($entries->total(), 0, ',', '.') }} jurnal</span>
            </div>

            <div class="ac-table-wrap">
                <table class="ac-table">
                    <thead>
                        <tr>
                            <th style="width:18%">Jurnal</th>
                            <th style="width:12%">Tanggal</th>
                            <th>Memo</th>
                            <th class="ac-money" style="width:15%">Debit</th>
                            <th class="ac-money" style="width:15%">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                            @php
                                $entryDebit = (float) $entry->lines->sum('debit');
                                $entryCredit = (float) $entry->lines->sum('credit');
                            @endphp
                            <tr class="ac-main-row">
                                <td>
                                    <div class="ac-cell-title">{{ $entry->number }}</div>
                                    <span class="ac-chip ac-chip-blue">{{ str_replace('_', ' ', $entry->event) }}</span>
                                </td>
                                <td class="ac-nowrap">{{ optional($entry->posted_at)->format('d/m/Y') }}</td>
                                <td>
                                    <div class="ac-cell-text">{{ $entry->memo ?: '-' }}</div>
                                    <div class="ac-cell-sub">Dibuat oleh: {{ $entry->creator?->name ?: '-' }}</div>
                                </td>
                                <td class="ac-money ac-strong">Rp {{ number_format($entryDebit, 0, ',', '.') }}</td>
                                <td class="ac-money ac-strong">Rp {{ number_format($entryCredit, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="ac-detail-row">
                                <td colspan="5">
                                    <div class="ac-line-box">
                                        <div class="ac-line-head">
                                            <span>Akun & Keterangan</span>
                                            <span class="ac-money">Debit</span>
                                            <span class="ac-money">Kredit</span>
                                        </div>
                                        @foreach($entry->lines as $line)
                                            <div class="ac-line-row">
                                                <div>
                                                    <div class="ac-cell-title">{{ $line->account?->code }} - {{ $line->account?->name }}</div>
                                                    <div class="ac-cell-sub">{{ $line->description ?: '-' }}</div>
                                                </div>
                                                <div class="ac-money">Rp {{ number_format((float) $line->debit, 0, ',', '.') }}</div>
                                                <div class="ac-money">Rp {{ number_format((float) $line->credit, 0, ',', '.') }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="ac-empty">
                                        <strong>Belum ada jurnal.</strong>
                                        <span>Jurnal akan muncul otomatis setelah ada penerimaan pembelian, pembayaran supplier, retur, atau transaksi POS.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="ac-pagination">{{ $entries->links() }}</div>
        </section>
    </div>
</x-app-layout>
