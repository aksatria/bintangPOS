<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="page-kicker">Pelanggan</p>
                <h2 class="text-2xl tracking-tight text-slate-900">Follow-up Hari Ini</h2>
            </div>
            <a href="{{ route('customers.index') }}" class="customer-btn customer-btn--ghost">Kembali ke Pelanggan</a>
        </div>
    </x-slot>

    <div class="page-shell space-y-6">
        <div class="customer-kpi-grid">
            <div class="customer-kpi-card customer-kpi-card--blue"><span>Total</span><strong>{{ number_format($summary['total'], 0, ',', '.') }}</strong></div>
            <div class="customer-kpi-card customer-kpi-card--amber"><span>Baru</span><strong>{{ number_format($summary['baru'], 0, ',', '.') }}</strong></div>
            <div class="customer-kpi-card customer-kpi-card--slate"><span>Proses</span><strong>{{ number_format($summary['proses'], 0, ',', '.') }}</strong></div>
            <div class="customer-kpi-card customer-kpi-card--green"><span>Selesai</span><strong>{{ number_format($summary['selesai'], 0, ',', '.') }}</strong></div>
        </div>

        <form method="GET" class="panel-card p-5 customer-toolbar">
            <div class="customer-toolbar__segment">
                <label class="label-ui">Tanggal</label>
                <input type="date" name="date" value="{{ $date }}" class="input-ui">
            </div>
            <div class="customer-toolbar__segment">
                <label class="label-ui">Status</label>
                <select name="status" class="input-ui">
                    <option value="all" @selected($status === 'all')>Semua</option>
                    <option value="baru" @selected($status === 'baru')>Baru</option>
                    <option value="proses" @selected($status === 'proses')>Proses</option>
                    <option value="selesai" @selected($status === 'selesai')>Selesai</option>
                    <option value="gagal" @selected($status === 'gagal')>Gagal</option>
                </select>
            </div>
            <div class="customer-toolbar__actions">
                <button class="customer-btn customer-btn--primary">Filter</button>
                <a href="{{ route('customers.followups') }}" class="customer-btn customer-btn--ghost">Reset</a>
            </div>
        </form>

        <div class="panel-card overflow-hidden">
            <div class="overflow-x-auto customer-table-wrap customer-table-shell">
                <table class="table-ui customer-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pelanggan</th>
                            <th>Tipe</th>
                            <th>Catatan</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($queue as $item)
                            <tr class="customer-row">
                                <td>
                                    <div class="customer-contact-main">{{ $item->reminder_at?->format('d/m/Y') }}</div>
                                    <div class="customer-contact-sub">{{ $item->reminder_at?->format('H:i') }}</div>
                                </td>
                                <td>
                                    <a class="customer-name-link" href="{{ route('customers.show', $item->customer_id) }}">{{ $item->customer?->name ?: '-' }}</a>
                                    <div class="customer-contact-sub">{{ $item->customer?->phone ?: '-' }}</div>
                                </td>
                                <td><span class="customer-sku-pill">{{ strtoupper($item->action_type) }}</span></td>
                                <td class="customer-contact-sub">{{ $item->note ?: '-' }}</td>
                                <td><span class="customer-sale-status {{ $item->status === 'selesai' ? 'customer-sale-status--paid' : ($item->status === 'gagal' ? 'customer-sale-status--cancelled' : 'customer-sale-status--pending') }}">{{ strtoupper($item->status) }}</span></td>
                                <td class="text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @foreach(['proses' => 'Proses', 'selesai' => 'Selesai', 'gagal' => 'Gagal'] as $value => $label)
                                            <form method="POST" action="{{ route('customers.followups.status', $item) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $value }}">
                                                <button class="customer-btn {{ $value === 'selesai' ? 'customer-btn--success' : ($value === 'gagal' ? 'customer-btn--danger' : 'customer-btn--info') }} customer-btn--pending">{{ $label }}</button>
                                            </form>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-slate-500 py-8">
                                Tidak ada antrian follow-up untuk tanggal ini.
                                <div class="mt-2">
                                    <a href="{{ route('customers.index') }}" class="customer-btn customer-btn--info">Buat Follow-up dari Daftar Pelanggan</a>
                                </div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">{{ $queue->links() }}</div>
        </div>
    </div>
</x-app-layout>
