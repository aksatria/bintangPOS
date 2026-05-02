<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="page-kicker">Pembayaran Pending</p>
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Histori Attempt Pembayaran</h2>
            </div>
            <a href="{{ route('dashboard') }}" class="btn-danger-lite">Kembali Dashboard</a>
        </div>
    </x-slot>

    <div class="page-shell space-y-4">
        <form method="GET" class="panel-card p-4 grid grid-cols-1 md:grid-cols-5 gap-3">
            <input type="date" name="from" value="{{ $filters['from'] }}" class="input-ui">
            <input type="date" name="to" value="{{ $filters['to'] }}" class="input-ui">
            <select name="status" class="input-ui">
                <option value="">Semua Status</option>
                <option value="paid" @selected($filters['status']==='paid')>Paid</option>
                <option value="pending" @selected($filters['status']==='pending')>Pending</option>
                <option value="cancelled" @selected($filters['status']==='cancelled')>Cancelled</option>
            </select>
            <select name="method" class="input-ui">
                <option value="">Semua Metode</option>
                @foreach(['cash','qris','debit','transfer','e_wallet','mixed'] as $m)
                    <option value="{{ $m }}" @selected($filters['method']===$m)>{{ strtoupper(str_replace('_',' ',$m)) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-primary">Filter</button>
        </form>

        <div class="panel-card overflow-hidden">
            <div class="panel-head"><h3 class="text-base font-bold text-slate-900">Daftar Transaksi</h3></div>
            <div class="overflow-x-auto">
                <table class="table-ui">
                    <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Tanggal</th>
                        <th>Kasir</th>
                        <th>Status</th>
                        <th>Metode</th>
                        <th>Attempt</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($sales as $sale)
                        <tr>
                            <td class="font-semibold">{{ $sale->invoice_number }}</td>
                            <td>{{ $sale->sold_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $sale->user?->name }}</td>
                            <td>{{ strtoupper($sale->status->value) }}</td>
                            <td>{{ strtoupper(str_replace('_',' ', (string) $sale->payment_method)) }}</td>
                            <td>{{ (int) $sale->payment_attempt_count }}</td>
                            <td><a class="btn-danger-lite" href="{{ route('sales.show', $sale) }}">Detail</a></td>
                        </tr>
                        @if(is_array($sale->payment_attempt_logs) && count($sale->payment_attempt_logs) > 0)
                            <tr>
                                <td colspan="7" class="bg-slate-50">
                                    <div class="text-xs text-slate-600 space-y-1 py-2">
                                        @foreach(array_slice(array_reverse($sale->payment_attempt_logs), 0, 4) as $log)
                                            <div>
                                                <strong class="{{ !empty($log['success']) ? 'text-emerald-700' : 'text-rose-700' }}">{{ !empty($log['success']) ? 'SUCCESS' : 'FAILED' }}</strong>
                                                - {{ $log['at'] ?? '-' }} - {{ $log['message'] ?? '-' }}
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="7" class="text-center text-slate-500">Belum ada histori attempt.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $sales->links() }}</div>
        </div>
    </div>
</x-app-layout>
