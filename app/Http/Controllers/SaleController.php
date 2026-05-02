<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Models\CashierAuditLog;
use App\Models\ApprovalRequest;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleRefundItem;
use App\Models\User;
use App\Models\StoreSetting;
use App\Support\AppliesBranchScope;
use App\Support\TelegramNotifier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    use AppliesBranchScope;

    public function pendingAttempts(Request $request)
    {
        $query = $this->applyBranchScope(Sale::query(), $request->user())
            ->with('user:id,name')
            ->where('payment_attempt_count', '>', 0);

        $from = $request->date('from');
        $to = $request->date('to');
        $status = (string) $request->query('status', '');
        $method = (string) $request->query('method', '');

        if ($from) {
            $query->whereDate('sold_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('sold_at', '<=', $to);
        }
        if (in_array($status, ['paid', 'pending', 'cancelled'], true)) {
            $query->where('status', $status);
        }
        if (in_array($method, ['cash', 'qris', 'debit', 'transfer', 'e_wallet', 'mixed'], true)) {
            $query->where('payment_method', $method);
        }

        $sales = $query->latest('sold_at')->paginate(20)->withQueryString();

        return view('sales.pending-attempts', [
            'sales' => $sales,
            'filters' => [
                'from' => $request->query('from', ''),
                'to' => $request->query('to', ''),
                'status' => $status,
                'method' => $method,
            ],
        ]);
    }

    public function show(Sale $sale)
    {
        $sale->load(['items', 'user', 'customer']);
        $correctionLogs = CashierAuditLog::query()
            ->with('user:id,name')
            ->whereIn('action', [
                'sale_refunded',
                'sale_voided',
                'pending_settled_pos',
                'sale_partial_refunded',
                'sale_refunded_pos',
            ])
            ->where(function ($q) use ($sale) {
                $q->where('context->sale_id', $sale->id)
                    ->orWhere('context->invoice', $sale->invoice_number);
            })
            ->latest('id')
            ->limit(20)
            ->get();

        return view('sales.show', [
            'sale' => $sale,
            'correctionLogs' => $correctionLogs,
        ]);
    }

    public function receipt(Sale $sale)
    {
        $sale->load(['items', 'user', 'customer']);
        $store = StoreSetting::query()->first();
        $itemCount = max(1, (int) $sale->items->count());
        $splitCount = (is_array($sale->payment_breakdown) ? count($sale->payment_breakdown) : 0);
        // Thermal 80mm width, dynamic height to keep one page.
        $paperHeight = (float) min(2200, 330 + ($itemCount * 38) + ($splitCount * 18));

        $pdf = Pdf::loadView('pdf.receipt', [
            'sale' => $sale,
            'store' => $store,
        ])->setPaper([0, 0, 226.77, $paperHeight], 'portrait');

        return $pdf->stream("struk-{$sale->invoice_number}.pdf");
    }

    public function receiptPrint(Sale $sale)
    {
        $sale->load(['items', 'user', 'customer']);
        $store = StoreSetting::query()->first();
        $paper = request()->query('paper', '80');
        if (! in_array($paper, ['58', '80'], true)) {
            $paper = '80';
        }

        return view('sales.receipt-print', [
            'sale' => $sale,
            'store' => $store,
            'paper' => $paper,
        ]);
    }

    public function reprintReceipt(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:300'],
            'mode' => ['nullable', 'in:pdf,print'],
        ]);

        $reason = trim((string) $validated['reason']);
        $mode = (string) ($validated['mode'] ?? 'pdf');

        $sale->note = trim(($sale->note ? $sale->note."\n" : '').'[REPRINT] '.$reason.' | by: '.($request->user()?->name ?? 'system').' | at: '.now()->format('d/m/Y H:i:s'));
        $sale->save();

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => 'sale_receipt_reprinted',
            'context' => [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'reason' => $reason,
                'mode' => $mode,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        if ($mode === 'print') {
            return redirect()->route('sales.receipt-print', $sale);
        }

        return redirect()->route('sales.receipt', $sale);
    }

    public function quickRefund(Request $request, Sale $sale)
    {
        if (! $request->user()?->hasAnyRole(['owner', 'admin'])) {
            abort(403, 'Hanya owner/admin yang dapat melakukan refund cepat.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'reason.required' => 'Alasan refund wajib diisi.',
            'reason.min' => 'Alasan refund minimal 5 karakter.',
        ]);

        if ($sale->status !== SaleStatus::Paid) {
            throw ValidationException::withMessages([
                'reason' => 'Refund hanya bisa dilakukan pada transaksi PAID.',
            ]);
        }

        $reason = trim((string) $validated['reason']);
        $fingerprint = sha1(json_encode([
            'type' => 'sale.quick_refund',
            'sale_id' => (int) $sale->id,
            'invoice' => (string) $sale->invoice_number,
            'reason' => $reason,
            'total' => round((float) $sale->total_amount, 2),
        ]));

        $existingPending = ApprovalRequest::query()
            ->where('branch_id', $sale->branch_id)
            ->where('type', 'sale.quick_refund')
            ->where('status', 'pending')
            ->where('payload->fingerprint', $fingerprint)
            ->first();
        if ($existingPending) {
            return back()->with('error', "Permintaan refund untuk {$sale->invoice_number} sudah ada di antrian approval.");
        }

        ApprovalRequest::query()->create([
            'branch_id' => $request->user()?->branch_id,
            'type' => 'sale.quick_refund',
            'status' => 'pending',
            'requested_by' => (int) $request->user()->id,
            'title' => "Approval Refund {$sale->invoice_number}",
            'reason' => $reason,
            'payload' => [
                'sale_id' => (int) $sale->id,
                'invoice' => (string) $sale->invoice_number,
                'reason' => $reason,
                'total' => (float) $sale->total_amount,
                'fingerprint' => $fingerprint,
            ],
        ]);

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => 'approval_request_created',
            'context' => [
                'type' => 'sale.quick_refund',
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) ($request->userAgent() ?? ''),
        ]);

        if (TelegramNotifier::enabled()) {
            TelegramNotifier::send(
                implode("\n", [
                    'Approval Request Baru',
                    'Tipe: sale.quick_refund',
                    'Invoice: '.$sale->invoice_number,
                    'Requester: '.($request->user()?->name ?? '-'),
                    'Alasan: '.trim((string) $validated['reason']),
                ]),
                'approval_request_created',
                TelegramNotifier::defaultChatId()
            );
        }

        return back()->with('success', "Permintaan refund {$sale->invoice_number} masuk antrian approval.");
    }

    public function quickVoid(Request $request, Sale $sale)
    {
        if (! $request->user()?->hasAnyRole(['owner', 'admin'])) {
            abort(403, 'Hanya owner/admin yang dapat melakukan void cepat.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'reason.required' => 'Alasan void wajib diisi.',
            'reason.min' => 'Alasan void minimal 5 karakter.',
        ]);

        if ($sale->status !== SaleStatus::Pending) {
            throw ValidationException::withMessages([
                'reason' => 'Void cepat hanya untuk transaksi PENDING.',
            ]);
        }

        $reason = trim((string) $validated['reason']);
        $fingerprint = sha1(json_encode([
            'type' => 'sale.quick_void',
            'sale_id' => (int) $sale->id,
            'invoice' => (string) $sale->invoice_number,
            'reason' => $reason,
        ]));

        $existingPending = ApprovalRequest::query()
            ->where('branch_id', $sale->branch_id)
            ->where('type', 'sale.quick_void')
            ->where('status', 'pending')
            ->where('payload->fingerprint', $fingerprint)
            ->first();
        if ($existingPending) {
            return back()->with('error', "Permintaan void untuk {$sale->invoice_number} sudah ada di antrian approval.");
        }

        ApprovalRequest::query()->create([
            'branch_id' => $request->user()?->branch_id,
            'type' => 'sale.quick_void',
            'status' => 'pending',
            'requested_by' => (int) $request->user()->id,
            'title' => "Approval Void {$sale->invoice_number}",
            'reason' => $reason,
            'payload' => [
                'sale_id' => (int) $sale->id,
                'invoice' => (string) $sale->invoice_number,
                'reason' => $reason,
                'fingerprint' => $fingerprint,
            ],
        ]);

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => 'approval_request_created',
            'context' => [
                'type' => 'sale.quick_void',
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) ($request->userAgent() ?? ''),
        ]);

        if (TelegramNotifier::enabled()) {
            TelegramNotifier::send(
                implode("\n", [
                    'Approval Request Baru',
                    'Tipe: sale.quick_void',
                    'Invoice: '.$sale->invoice_number,
                    'Requester: '.($request->user()?->name ?? '-'),
                    'Alasan: '.trim((string) $validated['reason']),
                ]),
                'approval_request_created',
                TelegramNotifier::defaultChatId()
            );
        }

        return back()->with('success', "Permintaan void {$sale->invoice_number} masuk antrian approval.");
    }

    public function quickSettlePending(Request $request, Sale $sale)
    {
        if (! $request->user()?->hasAnyRole(['owner', 'admin', 'kasir'])) {
            abort(403);
        }

        if ($sale->status !== SaleStatus::Pending) {
            throw ValidationException::withMessages([
                'paid_amount' => 'Hanya transaksi pending yang bisa dilunasi.',
            ]);
        }

        $sale->refresh();
        if ($sale->payment_due_at && now()->greaterThan($sale->payment_due_at) && in_array((string) $sale->payment_method, ['qris', 'debit', 'transfer', 'e_wallet'], true)) {
            $sale->status = SaleStatus::Cancelled;
            $sale->note = trim(($sale->note ? $sale->note."\n" : '').'[EXPIRED] pembayaran non-cash melewati batas waktu pada '.now()->format('d/m/Y H:i:s'));
            $sale->save();
            $this->appendAttemptLog($sale, $request, false, 'expired_before_settle');
            throw ValidationException::withMessages([
                'paid_amount' => 'Pembayaran pending sudah expired. Buat transaksi baru.',
            ]);
        }

        $validated = $request->validate([
            'payment_method' => ['required', 'in:cash,qris,debit,transfer,e_wallet'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $limits = [
            'cash' => 100000000.0,
            'qris' => 50000000.0,
            'debit' => 50000000.0,
            'transfer' => 100000000.0,
            'e_wallet' => 20000000.0,
        ];

        $paidAmount = max((float) $validated['paid_amount'], (float) $sale->total_amount);
        $method = (string) $validated['payment_method'];
        $limit = (float) ($limits[$method] ?? 0);
        if ($limit > 0 && $paidAmount > $limit) {
            $this->appendAttemptLog($sale, $request, false, 'method_limit_exceeded', [
                'method' => $method,
                'paid_amount' => $paidAmount,
            ]);
            throw ValidationException::withMessages([
                'paid_amount' => 'Nominal melebihi batas metode '.strtoupper(str_replace('_', ' ', $method)).': Rp '.number_format($limit, 0, ',', '.'),
            ]);
        }
        if (in_array($method, ['qris', 'debit', 'transfer', 'e_wallet'], true) && $paidAmount > (float) $sale->total_amount) {
            $this->appendAttemptLog($sale, $request, false, 'non_cash_overpay', [
                'method' => $method,
                'paid_amount' => $paidAmount,
            ]);
            throw ValidationException::withMessages([
                'paid_amount' => 'Nominal metode non-cash tidak boleh melebihi total transaksi.',
            ]);
        }

        $store = StoreSetting::query()->first();
        $overpayRules = is_array($store?->payment_overpay_rules) ? $store->payment_overpay_rules : [];
        if (array_key_exists($method, $overpayRules)) {
            $rule = data_get($overpayRules, $method);
            if (is_string($rule) && is_numeric($rule)) {
                $rule = ['max_overpay' => (float) $rule];
            }
            $maxOverpay = is_array($rule) ? (float) ($rule['max_overpay'] ?? 0) : 0;
            $overpay = $paidAmount - (float) $sale->total_amount;
            if ($overpay > $maxOverpay) {
                $this->appendAttemptLog($sale, $request, false, 'overpay_exceeded', [
                    'method' => $method,
                    'overpay' => $overpay,
                    'max_overpay' => $maxOverpay,
                ]);
                throw ValidationException::withMessages([
                    'paid_amount' => 'Overpay untuk metode '.strtoupper(str_replace('_', ' ', $method)).' melebihi batas: Rp '.number_format($maxOverpay, 0, ',', '.'),
                ]);
            }
        }

        $sale->status = SaleStatus::Paid;
        $sale->payment_method = $method;
        $sale->payment_breakdown = null;
        $sale->paid_amount = $paidAmount;
        $sale->change_amount = max($paidAmount - (float) $sale->total_amount, 0);
        $sale->payment_due_at = null;
        $sale->note = trim(($sale->note ? $sale->note."\n" : '').'[SETTLE PENDING POS] by: '.($request->user()?->name ?? 'system').' | at: '.now()->format('d/m/Y H:i:s'));
        $sale->save();
        $this->appendAttemptLog($sale, $request, true, 'settled', [
            'method' => $method,
            'paid_amount' => $paidAmount,
        ]);

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => 'pending_settled_pos',
            'context' => [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'paid_amount' => $paidAmount,
                'payment_method' => $method,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return back()->with('success', "Transaksi pending {$sale->invoice_number} berhasil dilunasi dari POS.");
    }

    public function quickPartialRefund(Request $request, Sale $sale)
    {
        if (! $request->user()?->hasAnyRole(['owner', 'admin'])) {
            abort(403, 'Hanya owner/admin yang dapat melakukan partial refund.');
        }
        if ($sale->status !== SaleStatus::Paid) {
            throw ValidationException::withMessages([
                'reason' => 'Partial refund hanya untuk transaksi PAID.',
            ]);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'manager_approval_reason' => ['required', 'string', 'min:5', 'max:500'],
            'manager_approval_email' => ['required', 'email', 'max:150'],
            'manager_approval_password' => ['required', 'string', 'max:120'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'integer', 'exists:sale_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:0'],
        ]);
        $manager = $this->resolveManagerApproval(
            (string) $validated['manager_approval_email'],
            (string) $validated['manager_approval_password'],
            $request->user()
        );

        DB::transaction(function () use ($sale, $validated, $request, $manager): void {
            $lockedSale = Sale::query()->with('items')->lockForUpdate()->findOrFail($sale->id);
            if ($lockedSale->status !== SaleStatus::Paid) {
                throw ValidationException::withMessages([
                    'reason' => 'Status transaksi berubah, partial refund dibatalkan.',
                ]);
            }

            $reason = trim((string) $validated['reason']);
            $itemMap = $lockedSale->items->keyBy('id');
            $refundTotal = 0.0;

            foreach ($validated['items'] as $row) {
                $saleItemId = (int) $row['sale_item_id'];
                $qty = (int) $row['quantity'];
                if ($qty <= 0) {
                    continue;
                }
                $saleItem = $itemMap->get($saleItemId);
                if (! $saleItem) {
                    throw ValidationException::withMessages([
                        'items' => "Item refund tidak ditemukan: #{$saleItemId}.",
                    ]);
                }
                if ($qty > (int) $saleItem->quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Qty refund melebihi qty item {$saleItem->product_name}.",
                    ]);
                }

                $unitNet = ((float) $saleItem->subtotal) / max((int) $saleItem->quantity, 1);
                $refundAmount = $unitNet * $qty;
                $refundTotal += $refundAmount;

                $saleItem->quantity = (int) $saleItem->quantity - $qty;
                $saleItem->subtotal = max((float) $saleItem->subtotal - $refundAmount, 0);
                $saleItem->save();

                if ($saleItem->product_id) {
                    Product::query()->whereKey($saleItem->product_id)->lockForUpdate()->first()?->increment('stock', $qty);
                }

                SaleRefundItem::query()->create([
                    'sale_id' => $lockedSale->id,
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'quantity' => $qty,
                    'refund_amount' => $refundAmount,
                    'reason' => $reason,
                    'created_by' => $request->user()?->id,
                ]);
            }

            if ($refundTotal <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'Pilih minimal satu qty item untuk partial refund.',
                ]);
            }

            $lockedSale->subtotal = max((float) $lockedSale->subtotal - $refundTotal, 0);
            $lockedSale->total_amount = max((float) $lockedSale->total_amount - $refundTotal, 0);
            $lockedSale->change_amount = max((float) $lockedSale->paid_amount - (float) $lockedSale->total_amount, 0);
            $lockedSale->note = trim(($lockedSale->note ? $lockedSale->note."\n" : '').'[PARTIAL REFUND] Rp '.number_format($refundTotal, 0, ',', '.').' | '.$reason.' | by: '.($request->user()?->name ?? 'system').' | at: '.now()->format('d/m/Y H:i:s'));
            $lockedSale->save();

            CashierAuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'action' => 'sale_partial_refunded',
                'context' => [
                    'sale_id' => $lockedSale->id,
                    'invoice' => $lockedSale->invoice_number,
                    'reason' => $reason,
                    'refund_total' => $refundTotal,
                    'items' => $validated['items'],
                    'approval_reason' => trim((string) $validated['manager_approval_reason']),
                    'approved_by' => $this->maskEmail((string) $manager->email),
                ],
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        });

        return back()->with('success', "Partial refund transaksi {$sale->invoice_number} berhasil disimpan.");
    }

    private function appendAttemptLog(Sale $sale, Request $request, bool $success, string $message, array $extra = []): void
    {
        $logs = is_array($sale->payment_attempt_logs) ? $sale->payment_attempt_logs : [];
        $logs[] = array_merge([
            'id' => (string) Str::uuid(),
            'at' => now()->toDateTimeString(),
            'user_id' => $request->user()?->id,
            'success' => $success,
            'message' => $message,
        ], $extra);

        $sale->payment_attempt_logs = $logs;
        $sale->payment_attempt_count = (int) $sale->payment_attempt_count + 1;
        $sale->save();
    }

    private function resolveManagerApproval(string $email, string $password, ?User $actor = null): User
    {
        $manager = User::query()
            ->where('email', trim($email))
            ->whereIn('role', ['owner', 'admin'])
            ->when(
                $actor && ! $actor->hasAnyRole(['owner']) && $actor->branch_id,
                fn ($q) => $q->where(function ($s) use ($actor) {
                    $s->where('role', 'owner')
                        ->orWhere('branch_id', $actor->branch_id);
                })
            )
            ->first();

        if (! $manager || ! Hash::check($password, (string) $manager->password)) {
            throw ValidationException::withMessages([
                'manager_approval_email' => 'Approval manager tidak valid.',
            ]);
        }

        return $manager;
    }

    private function maskEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '' || ! str_contains($email, '@')) {
            return '-';
        }

        [$local, $domain] = explode('@', $email, 2);
        $local = trim($local);
        if ($local === '') {
            return '*@'.$domain;
        }

        if (strlen($local) <= 2) {
            return substr($local, 0, 1).'*@'.$domain;
        }

        return substr($local, 0, 2).str_repeat('*', max(strlen($local) - 2, 2)).'@'.$domain;
    }
}
