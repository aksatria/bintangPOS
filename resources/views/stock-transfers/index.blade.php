<x-app-layout>
    <x-slot name="header">
        <div class="pro-page-head">
            <div>
                <p class="page-kicker">Operasional</p>
                <h2 class="pro-page-title">Mutasi Stok Antar Cabang</h2>
                <p class="pro-page-sub">Alur: request dari cabang asal, approve cabang tujuan, lalu receive untuk perpindahan stok.</p>
            </div>
        </div>
    </x-slot>

    <div class="page-shell space-y-4">
        @if(session('status'))
            <div class="ad-alert ad-ok">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="ad-alert ad-err">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="pro-panel">
            <div class="pro-headbar">
                <h3 class="pro-section-title">Request Mutasi Baru</h3>
            </div>
            <div class="p-4">
                @if($canRequest)
                <form method="POST" action="{{ route('stock-transfers.store') }}" class="grid gap-3" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="MAX_FILE_SIZE" value="4194304">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="label-ui">Cabang Tujuan</label>
                            <select name="destination_branch_id" class="input-ui" required>
                                <option value="">Pilih cabang tujuan</option>
                                @foreach($destinationBranches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label-ui">Catatan (opsional)</label>
                            <input type="text" name="note" class="input-ui" placeholder="Contoh: Restok akhir pekan cabang tujuan">
                        </div>
                        <div>
                            <label class="label-ui">Referensi Pengiriman</label>
                            <input type="text" name="delivery_ref" class="input-ui" placeholder="Contoh: SJ-2026-0001 / Plat kendaraan">
                        </div>
                        <div>
                            <label class="label-ui">Nama Kurir / Pengantar</label>
                            <input type="text" name="courier_name" class="input-ui" placeholder="Contoh: Budi / Expedisi Internal">
                        </div>
                        <div class="md:col-span-2">
                            <label class="label-ui">Lampiran Bukti Pengiriman (opsional)</label>
                            <input type="file" name="dispatch_proof" class="input-ui" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                    </div>
                    <div>
                        <label class="label-ui">Cari Produk Cepat</label>
                        <input type="text" id="transfer-product-search" class="input-ui" placeholder="Ketik nama / SKU untuk memfilter pilihan produk">
                    </div>

                    <div>
                        <label class="label-ui">Item Transfer</label>
                        <div class="grid gap-2">
                            @for($i = 0; $i < 3; $i++)
                                <div class="grid gap-2 md:grid-cols-4">
                                    <select name="items[{{ $i }}][product_id]" class="input-ui transfer-product-select">
                                        <option value="">Pilih produk</option>
                                        @foreach($sourceProducts as $product)
                                            <option value="{{ $product->id }}" data-search="{{ strtolower($product->name . ' ' . $product->sku) }}">{{ $product->name }} ({{ $product->sku }}) - stok: {{ $product->stock }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" name="items[{{ $i }}][qty]" class="input-ui" min="1" placeholder="Qty">
                                    <div class="md:col-span-2 text-xs text-slate-500 self-center">
                                        Isi baris yang diperlukan saja. Baris kosong akan diabaikan.
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="ad-btn">Kirim Request Mutasi</button>
                    </div>
                </form>
                @else
                    <div class="text-sm text-slate-500">Anda tidak memiliki izin membuat request mutasi.</div>
                @endif
            </div>
        </div>

        <div class="pro-panel">
            <div class="pro-headbar flex items-center justify-between gap-2">
                <h3 class="pro-section-title">Daftar Mutasi</h3>
            </div>
            <div class="p-4 border-b border-slate-200">
                <div class="flex flex-wrap gap-2 mb-3">
                    <a href="{{ route('stock-transfers.index', array_merge(request()->query(), ['quick' => 'today'])) }}"
                       class="ad-btn-soft {{ ($filters['quick'] ?? '') === 'today' ? 'bg-blue-50 border-blue-300 text-blue-700' : '' }}">
                        Hari Ini ({{ (int) ($presetCounts['today'] ?? 0) }})
                    </a>
                    <a href="{{ route('stock-transfers.index', array_merge(request()->query(), ['quick' => '7d'])) }}"
                       class="ad-btn-soft {{ ($filters['quick'] ?? '') === '7d' ? 'bg-blue-50 border-blue-300 text-blue-700' : '' }}">
                        7 Hari ({{ (int) ($presetCounts['7d'] ?? 0) }})
                    </a>
                    <a href="{{ route('stock-transfers.index', array_merge(request()->query(), ['quick' => 'month'])) }}"
                       class="ad-btn-soft {{ ($filters['quick'] ?? '') === 'month' ? 'bg-blue-50 border-blue-300 text-blue-700' : '' }}">
                        Bulan Ini ({{ (int) ($presetCounts['month'] ?? 0) }})
                    </a>
                    <a href="{{ route('stock-transfers.index', array_merge(request()->query(), ['quick' => 'pending'])) }}"
                       class="ad-btn-soft {{ ($filters['quick'] ?? '') === 'pending' ? 'bg-amber-50 border-amber-300 text-amber-700' : '' }}">
                        Pending Saja ({{ (int) ($presetCounts['pending'] ?? 0) }})
                    </a>
                    <a href="{{ route('stock-transfers.index', array_merge(request()->query(), ['quick' => 'overdue'])) }}"
                       class="ad-btn-soft {{ ($filters['quick'] ?? '') === 'overdue' ? 'bg-rose-50 border-rose-300 text-rose-700' : '' }}">
                        Overdue >24j ({{ (int) ($presetCounts['overdue'] ?? 0) }})
                    </a>
                </div>
                <form method="GET" action="{{ route('stock-transfers.index') }}" class="grid gap-2 md:grid-cols-6">
                    <input type="hidden" name="quick" value="">
                    <select name="status" class="input-ui">
                        <option value="all" @selected($status === 'all')>Semua Status</option>
                        <option value="requested" @selected($status === 'requested')>Requested</option>
                        <option value="approved" @selected($status === 'approved')>Approved</option>
                        <option value="received" @selected($status === 'received')>Received</option>
                        <option value="rejected" @selected($status === 'rejected')>Rejected</option>
                        <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
                    </select>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="input-ui">
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="input-ui">
                    <select name="source_branch_id" class="input-ui">
                        <option value="0">Semua Cabang Asal</option>
                        @foreach(($branchOptions ?? collect()) as $branch)
                            <option value="{{ $branch->id }}" @selected((int) ($filters['source_branch_id'] ?? 0) === (int) $branch->id)>
                                {{ $branch->name }} ({{ $branch->code }})
                            </option>
                        @endforeach
                    </select>
                    <select name="destination_branch_id" class="input-ui">
                        <option value="0">Semua Cabang Tujuan</option>
                        @foreach(($branchOptions ?? collect()) as $branch)
                            <option value="{{ $branch->id }}" @selected((int) ($filters['destination_branch_id'] ?? 0) === (int) $branch->id)>
                                {{ $branch->name }} ({{ $branch->code }})
                            </option>
                        @endforeach
                    </select>
                    <div class="flex items-center gap-2">
                        <button class="ad-btn" type="submit">Filter</button>
                        <a href="{{ route('stock-transfers.index') }}" class="ad-btn-soft">Reset</a>
                    </div>
                </form>
            </div>
            <div class="p-4 overflow-x-auto">
                <table class="table-ui w-full">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Rute</th>
                            <th>Status</th>
                            <th>Item</th>
                            <th>Requester</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $transfer)
                            @php
                                $statusClass = match($transfer->status) {
                                    'approved' => 'status-paid',
                                    'received' => 'status-paid',
                                    'rejected' => 'status-cancelled',
                                    'cancelled' => 'status-cancelled',
                                    default => 'status-pending',
                                };
                                $canApprove = $transfer->status === 'requested' && ($isOwner || (int) $activeBranchId === (int) $transfer->destination_branch_id);
                                $canReceive = $transfer->status === 'approved' && ($isOwner || (int) $activeBranchId === (int) $transfer->destination_branch_id);
                                $canCancel = $transfer->status === 'requested' && ($isOwner || (int) $activeBranchId === (int) $transfer->source_branch_id);
                                $isOverdue = in_array($transfer->status, ['requested', 'approved'], true) && $transfer->created_at?->lte(now()->subHours(24));
                                $ageHours = $transfer->created_at ? $transfer->created_at->diffInHours(now()) : 0;
                            @endphp
                            <tr>
                                <td class="font-semibold">
                                    <a href="{{ route('stock-transfers.show', $transfer->id) }}" class="text-blue-700 hover:underline">
                                        {{ $transfer->code }}
                                    </a>
                                </td>
                                <td>
                                    {{ $transfer->sourceBranch?->name }} -> {{ $transfer->destinationBranch?->name }}
                                    <div class="text-xs text-slate-500 mt-1">
                                        {{ $transfer->created_at?->format('d/m/Y H:i') }}
                                    </div>
                                </td>
                                <td>
                                    <span class="status-chip {{ $statusClass }}">{{ strtoupper($transfer->status) }}</span>
                                    @if($isOverdue)
                                        <div class="mt-1 inline-flex items-center rounded-full border border-rose-300 bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700">
                                            Overdue {{ $ageHours }}j
                                        </div>
                                    @endif
                                    @if($transfer->reject_reason)
                                        <div class="text-xs text-rose-700 mt-1">Alasan: {{ $transfer->reject_reason }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $transfer->items_count }} item</div>
                                    <div class="text-xs text-slate-500 mt-1">{{ $transfer->note ?: '-' }}</div>
                                    <div class="text-xs text-slate-500 mt-1">Ref: {{ $transfer->delivery_ref ?: '-' }}</div>
                                    <div class="text-xs text-slate-500 mt-1">Kurir: {{ $transfer->courier_name ?: '-' }}</div>
                                    @if($transfer->dispatch_proof_path)
                                        <div class="text-xs mt-1">
                                            <a class="text-blue-700 hover:underline" target="_blank" href="{{ asset('storage/' . ltrim((string) $transfer->dispatch_proof_path, '/')) }}">Bukti Kirim</a>
                                        </div>
                                    @endif
                                    @if($transfer->receive_proof_path)
                                        <div class="text-xs mt-1">
                                            <a class="text-blue-700 hover:underline" target="_blank" href="{{ asset('storage/' . ltrim((string) $transfer->receive_proof_path, '/')) }}">Bukti Terima</a>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $transfer->requester?->name }}</div>
                                    @if($transfer->approver)
                                        <div class="text-xs text-slate-500 mt-1">Approve: {{ $transfer->approver->name }}</div>
                                    @endif
                                    @if($transfer->receiver)
                                        <div class="text-xs text-slate-500 mt-1">Receive: {{ $transfer->receiver->name }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-2">
                                        @if($canExport)
                                            <a class="ad-btn-soft" href="{{ route('stock-transfers.export.csv', $transfer->id) }}">CSV</a>
                                            <a class="ad-btn-soft" href="{{ route('stock-transfers.export.pdf', $transfer->id) }}">PDF</a>
                                        @endif
                                        @if($canApprove)
                                            <form method="POST" action="{{ route('stock-transfers.approve', $transfer->id) }}">
                                                @csrf
                                                <button class="ad-btn-soft" type="submit">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('stock-transfers.reject', $transfer->id) }}" class="flex items-center gap-1">
                                                @csrf
                                                <input type="text" name="reject_reason" class="input-ui" placeholder="Alasan tolak" required>
                                                <button class="ad-btn-soft ad-btn-danger" type="submit">Reject</button>
                                            </form>
                                        @endif
                                        @if($canReceive)
                                            <form method="POST" action="{{ route('stock-transfers.receive', $transfer->id) }}">
                                                @csrf
                                                <button class="ad-btn" type="submit">Receive</button>
                                            </form>
                                        @endif
                                        @if($canRequest && $canCancel)
                                            <form method="POST" action="{{ route('stock-transfers.cancel', $transfer->id) }}">
                                                @csrf
                                                <button class="ad-btn-soft ad-btn-danger" type="submit">Cancel</button>
                                            </form>
                                        @endif
                                    </div>
                                    <details class="mt-2">
                                        <summary class="text-xs text-slate-600 cursor-pointer">Lihat detail item</summary>
                                        <div class="mt-2 rounded border border-slate-200 p-2 bg-slate-50">
                                            @foreach($transfer->items as $item)
                                                <div class="text-xs text-slate-700 py-1 border-b border-slate-200 last:border-b-0">
                                                    {{ $item->product_name }} ({{ $item->sku }}) - Req: {{ $item->requested_qty }} {{ $item->unit ?: '' }}
                                                    @if($item->received_qty !== null)
                                                        | Recv: {{ $item->received_qty }}
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-slate-500 py-6">Belum ada data mutasi stok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $transfers->links() }}
            </div>
        </div>
    </div>
    <script>
        (function () {
            const searchInput = document.getElementById('transfer-product-search');
            if (!searchInput) return;
            const selects = Array.from(document.querySelectorAll('.transfer-product-select'));
            const allOptions = selects.map((select) => Array.from(select.querySelectorAll('option')));
            searchInput.addEventListener('input', function () {
                const q = String(searchInput.value || '').trim().toLowerCase();
                selects.forEach((select, idx) => {
                    const current = select.value;
                    select.innerHTML = '';
                    allOptions[idx].forEach((opt) => {
                        const isPlaceholder = !opt.value;
                        const search = String(opt.getAttribute('data-search') || '').toLowerCase();
                        if (isPlaceholder || q === '' || search.includes(q)) {
                            select.appendChild(opt.cloneNode(true));
                        }
                    });
                    if (current) select.value = current;
                });
            });
        })();
    </script>
</x-app-layout>
