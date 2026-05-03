<x-app-layout>
    <x-slot name="header">
        <div class="pro-page-head">
            <div>
                <p class="page-kicker">Operasional</p>
                <h2 class="pro-page-title">Detail Mutasi Stok</h2>
                <p class="pro-page-sub">Rincian lengkap proses mutasi antar cabang.</p>
            </div>
        </div>
    </x-slot>

    <div class="page-shell space-y-4">
        <div class="pro-panel">
            <div class="pro-headbar flex items-center justify-between gap-2">
                <h3 class="pro-section-title">{{ $transfer->code }}</h3>
                <div class="flex items-center gap-2">
                    @if($canExport)
                        <a class="ad-btn-soft" href="{{ route('stock-transfers.export.csv', $transfer->id) }}">Export CSV</a>
                        <a class="ad-btn-soft" href="{{ route('stock-transfers.export.pdf', $transfer->id) }}">Export PDF</a>
                    @endif
                    <a class="ad-btn-soft" href="{{ route('stock-transfers.index') }}">Kembali</a>
                </div>
            </div>
            <div class="p-4 grid gap-3 md:grid-cols-2">
                <div>
                    <div class="text-xs text-slate-500">Status</div>
                    <div class="font-semibold text-slate-900 mt-1">{{ strtoupper($transfer->status) }}</div>
                    @if($isOverdue)
                        <div class="mt-2 inline-flex items-center rounded-full border border-rose-300 bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700">
                            Overdue {{ (int) $overdueHours }} jam
                        </div>
                    @endif
                </div>
                <div>
                    <div class="text-xs text-slate-500">Rute</div>
                    <div class="font-semibold text-slate-900 mt-1">
                        {{ $transfer->sourceBranch?->name }} ({{ $transfer->sourceBranch?->code }}) -> {{ $transfer->destinationBranch?->name }} ({{ $transfer->destinationBranch?->code }})
                    </div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Requester</div>
                    <div class="text-slate-800 mt-1">{{ $transfer->requester?->name ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Approver</div>
                    <div class="text-slate-800 mt-1">{{ $transfer->approver?->name ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Receiver</div>
                    <div class="text-slate-800 mt-1">{{ $transfer->receiver?->name ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Delivery Ref / Kurir</div>
                    <div class="text-slate-800 mt-1">{{ $transfer->delivery_ref ?: '-' }} / {{ $transfer->courier_name ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Bukti Kirim</div>
                    <div class="text-slate-800 mt-1">
                        @if($transfer->dispatch_proof_path)
                            <a class="text-blue-700 hover:underline" target="_blank" href="{{ asset('storage/' . ltrim((string) $transfer->dispatch_proof_path, '/')) }}">Lihat Lampiran</a>
                        @else
                            -
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Bukti Terima</div>
                    <div class="text-slate-800 mt-1">
                        @if($transfer->receive_proof_path)
                            <a class="text-blue-700 hover:underline" target="_blank" href="{{ asset('storage/' . ltrim((string) $transfer->receive_proof_path, '/')) }}">Lihat Lampiran</a>
                        @else
                            -
                        @endif
                    </div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-xs text-slate-500">Catatan</div>
                    <div class="text-slate-800 mt-1">{{ $transfer->note ?: '-' }}</div>
                    @if($transfer->receive_note)
                        <div class="text-slate-700 mt-2 text-sm">Catatan receive: {{ $transfer->receive_note }}</div>
                    @endif
                    @if($transfer->reject_reason)
                        <div class="text-rose-700 mt-2 text-sm">Alasan reject: {{ $transfer->reject_reason }}</div>
                    @endif
                </div>
            </div>
        </div>

        @if($transfer->status === 'approved' && $canReceive)
            <div class="pro-panel">
                <div class="pro-headbar">
                    <h3 class="pro-section-title">Proses Receive Parsial</h3>
                </div>
                <div class="p-4">
                    <form method="POST" action="{{ route('stock-transfers.receive', $transfer->id) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        @foreach($transfer->items as $item)
                            <div class="rounded border border-slate-200 p-3 bg-slate-50">
                                <div class="font-semibold text-slate-800">{{ $item->product_name }} ({{ $item->sku }})</div>
                                <div class="text-xs text-slate-500 mt-1">Qty Request: {{ $item->requested_qty }} {{ $item->unit ?: '' }}</div>
                                <div class="grid gap-2 md:grid-cols-2 mt-2">
                                    <input type="hidden" name="items[{{ $item->id }}][id]" value="{{ $item->id }}">
                                    <div>
                                        <label class="label-ui">Qty Receive</label>
                                        <input type="number" name="items[{{ $item->id }}][received_qty]" class="input-ui" min="0" max="{{ (int) $item->requested_qty }}" value="{{ (int) $item->requested_qty }}">
                                    </div>
                                    <div>
                                        <label class="label-ui">Alasan Selisih (wajib jika qty beda)</label>
                                        <input type="text" name="items[{{ $item->id }}][discrepancy_reason]" class="input-ui" placeholder="Contoh: 2 strip rusak saat kirim">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div>
                            <label class="label-ui">Catatan Receive</label>
                            <input type="text" name="receive_note" class="input-ui" placeholder="Catatan umum penerimaan">
                        </div>
                        <div>
                            <label class="label-ui">Lampiran Bukti Terima</label>
                            <input type="file" name="receive_proof" class="input-ui" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                        <button type="submit" class="ad-btn">Simpan Receive</button>
                    </form>
                </div>
            </div>
        @endif

        <div class="pro-panel">
            <div class="pro-headbar">
                <h3 class="pro-section-title">Item Mutasi</h3>
            </div>
            <div class="p-4 overflow-x-auto">
                <table class="table-ui w-full">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Produk</th>
                            <th>Unit</th>
                            <th>Qty Request</th>
                            <th>Qty Receive</th>
                            <th>Alasan Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfer->items as $item)
                            <tr>
                                <td>{{ $item->sku }}</td>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->unit ?: '-' }}</td>
                                <td>{{ number_format((int) $item->requested_qty, 0, ',', '.') }}</td>
                                <td>{{ $item->received_qty !== null ? number_format((int) $item->received_qty, 0, ',', '.') : '-' }}</td>
                                <td>{{ $item->discrepancy_reason ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-slate-500 py-6">Tidak ada item mutasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pro-panel">
            <div class="pro-headbar">
                <h3 class="pro-section-title">Timeline Proses</h3>
            </div>
            <div class="p-4">
                <div class="space-y-3">
                    @foreach($timeline as $event)
                        <div class="rounded-lg border px-3 py-2 {{ $event['done'] ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50' }}">
                            <div class="flex items-center justify-between gap-2">
                                <div class="font-semibold {{ $event['done'] ? 'text-emerald-700' : 'text-slate-700' }}">
                                    {{ $event['label'] }}
                                </div>
                                <div class="text-xs {{ $event['done'] ? 'text-emerald-700' : 'text-slate-500' }}">
                                    {{ $event['at'] ? $event['at']->format('d/m/Y H:i') : '-' }}
                                </div>
                            </div>
                            <div class="text-xs text-slate-600 mt-1">Aktor: {{ $event['actor'] ?: '-' }}</div>
                            @if(!empty($event['note']))
                                <div class="text-xs mt-1 {{ $event['done'] ? 'text-emerald-700' : 'text-slate-600' }}">
                                    Catatan: {{ $event['note'] }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
