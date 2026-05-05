<x-app-layout>
    <x-slot name="header">
        <div class="ac-head">
            <div>
                <p class="ac-kicker">Akuntansi</p>
                <h2 class="ac-title">Buku Besar</h2>
                <p class="ac-sub">Telusuri mutasi debit-kredit per akun.</p>
            </div>
            <div class="ac-head-actions">
                <a href="{{ route('admin.accounting.reports') }}" class="ac-btn ac-btn-nav ac-btn-nav-report">Laporan Akuntansi</a>
                <a href="{{ route('admin.accounting.journals') }}" class="ac-btn ac-btn-nav ac-btn-nav-journal">Jurnal Umum</a>
                <a href="{{ route('admin.accounting.ledger') }}" class="ac-btn ac-btn-nav ac-btn-nav-ledger ac-btn-nav-active">Buku Besar</a>
            </div>
        </div>
    </x-slot>

    <div class="ac-shell">
        <section class="ac-card ac-hero-card">
            <div class="ac-card-body ac-hero">
                <div>
                    <p class="ac-blue-note">Mutasi per akun</p>
                    <h1 class="ac-hero-title">Lihat asal angka dari setiap saldo akun.</h1>
                    <p class="ac-hero-text">Filter akun untuk memeriksa riwayat mutasi yang membentuk saldo neraca dan laba rugi.</p>
                </div>
                <div class="ac-hero-stats">
                    <div class="ac-mini-stat"><span>Total Mutasi</span><strong>{{ number_format($lines->total(), 0, ',', '.') }}</strong></div>
                </div>
            </div>
        </section>

        <section class="ac-card">
            <div class="ac-card-body">
                <form method="GET" class="ac-filter-grid ac-filter-grid-ledger">
                    <label class="ac-field">
                        <span class="ac-label">Pilih Akun</span>
                        <select name="account_id" class="ac-select">
                            <option value="0">Semua Akun</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" @selected($accountId === $account->id)>{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="ac-btn ac-btn-primary ac-filter-btn" type="submit">Terapkan Filter</button>
                </form>
            </div>
        </section>

        <section class="ac-card">
            <div class="ac-card-head">
                <div>
                    <h3 class="ac-card-title">Mutasi Buku Besar</h3>
                    <p class="ac-card-sub">Menampilkan jurnal line paling baru terlebih dahulu.</p>
                </div>
                <span class="ac-count">{{ number_format($lines->total(), 0, ',', '.') }} mutasi</span>
            </div>

            <div class="ac-table-wrap">
                <table class="ac-table">
                    <thead>
                        <tr>
                            <th style="width:13%">Tanggal</th>
                            <th style="width:26%">Akun</th>
                            <th>Jurnal</th>
                            <th class="ac-money" style="width:15%">Debit</th>
                            <th class="ac-money" style="width:15%">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lines as $line)
                            <tr>
                                <td class="ac-nowrap">{{ optional($line->entry?->posted_at)->format('d/m/Y') }}</td>
                                <td>
                                    <div class="ac-cell-title">{{ $line->account?->code }}</div>
                                    <div class="ac-cell-sub">{{ $line->account?->name }}</div>
                                </td>
                                <td>
                                    <div class="ac-cell-title ac-link-text">{{ $line->entry?->number }}</div>
                                    <div class="ac-cell-sub">{{ $line->description ?: $line->entry?->memo }}</div>
                                </td>
                                <td class="ac-money ac-strong">Rp {{ number_format((float) $line->debit, 0, ',', '.') }}</td>
                                <td class="ac-money ac-strong">Rp {{ number_format((float) $line->credit, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="ac-empty">
                                        <strong>Belum ada mutasi buku besar.</strong>
                                        <span>Mutasi akan terisi otomatis ketika jurnal dibuat.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="ac-pagination">{{ $lines->links() }}</div>
        </section>
    </div>
</x-app-layout>
