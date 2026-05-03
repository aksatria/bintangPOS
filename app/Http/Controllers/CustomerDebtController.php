<?php

namespace App\Http\Controllers;

use App\Exports\CustomerDebtsExport;
use App\Models\CashierAuditLog;
use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\CustomerDebtPayment;
use App\Models\Sale;
use App\Services\CustomerDebtNumberService;
use App\Support\ActiveBranchContext;
use App\Support\AppliesBranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerDebtController extends Controller
{
    use AppliesBranchScope;

    public function __construct(
        private readonly CustomerDebtNumberService $debtNumberService
    ) {}

    public function index(Request $request)
    {
        $status = (string) $request->query('status', 'all');
        $aging = (string) $request->query('aging', 'all');
        $q = trim((string) $request->query('q', ''));
        $fromDate = trim((string) $request->query('from_date', now()->startOfMonth()->toDateString()));
        $toDate = trim((string) $request->query('to_date', now()->toDateString()));

        $baseDebtQuery = $this->applyBranchScope(CustomerDebt::query(), $request->user());
        $periodDebtQuery = $this->applyPeriodFilter($baseDebtQuery->clone(), $fromDate, $toDate);

        $debts = $this->applyDebtFilters($periodDebtQuery->clone(), $status, $aging, $q)
            ->with([
                'customer:id,name,phone',
                'sale:id,invoice_number',
                'payments' => fn ($q) => $q->latest('paid_at')->with('receiver:id,name'),
            ])
            ->orderByRaw("CASE WHEN status='active' THEN 1 WHEN status='overdue' THEN 2 ELSE 3 END")
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $customers = $this->applyBranchScope(Customer::query(), $request->user())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $pendingSales = $this->applyBranchScope(Sale::query(), $request->user())
            ->where('status', 'pending')
            ->orderByDesc('sold_at')
            ->limit(200)
            ->get(['id', 'invoice_number', 'customer_id', 'total_amount', 'sold_at']);

        $summary = [
            'active' => (int) $periodDebtQuery->clone()->where('status', 'active')->count(),
            'overdue' => (int) $periodDebtQuery->clone()->where('status', 'overdue')->count(),
            'total_remaining' => (float) $periodDebtQuery->clone()->sum('remaining_amount'),
            'aging_current' => (int) $periodDebtQuery->clone()
                ->where('remaining_amount', '>', 0)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '>=', now()->toDateString())
                ->count(),
            'aging_overdue_1_7' => (int) $periodDebtQuery->clone()
                ->where('remaining_amount', '>', 0)
                ->whereNotNull('due_date')
                ->whereRaw('DATEDIFF(?, due_date) BETWEEN 1 AND 7', [now()->toDateString()])
                ->count(),
            'aging_overdue_8_30' => (int) $periodDebtQuery->clone()
                ->where('remaining_amount', '>', 0)
                ->whereNotNull('due_date')
                ->whereRaw('DATEDIFF(?, due_date) BETWEEN 8 AND 30', [now()->toDateString()])
                ->count(),
            'aging_overdue_30_plus' => (int) $periodDebtQuery->clone()
                ->where('remaining_amount', '>', 0)
                ->whereNotNull('due_date')
                ->whereRaw('DATEDIFF(?, due_date) > 30', [now()->toDateString()])
                ->count(),
        ];

        return view('customers.debts', compact('debts', 'customers', 'pendingSales', 'summary', 'status', 'aging', 'q', 'fromDate', 'toDate'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $status = (string) $request->query('status', 'all');
        $aging = (string) $request->query('aging', 'all');
        $q = trim((string) $request->query('q', ''));
        $fromDate = trim((string) $request->query('from_date', now()->startOfMonth()->toDateString()));
        $toDate = trim((string) $request->query('to_date', now()->toDateString()));

        $rows = $this->applyDebtFilters(
            $this->applyPeriodFilter($this->applyBranchScope(CustomerDebt::query(), $request->user()), $fromDate, $toDate),
            $status,
            $aging,
            $q
        )
            ->with(['customer:id,name,phone', 'sale:id,invoice_number'])
            ->orderByRaw("CASE WHEN status='active' THEN 1 WHEN status='overdue' THEN 2 ELSE 3 END")
            ->latest('id')
            ->get();

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'branch_id' => ActiveBranchContext::resolveBranchId($request->user()),
            'action' => 'customer_debt_export_csv',
            'context' => [
                'status' => $status,
                'aging' => $aging,
                'q' => $q,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'row_count' => $rows->count(),
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $filename = 'piutang-pelanggan-' . now()->format('Ymd-His') . '.csv';
        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if (! $out) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nomor', 'Pelanggan', 'No HP', 'Invoice', 'Tanggal Hutang', 'Jatuh Tempo', 'Aging (Hari)', 'Pokok', 'Terbayar', 'Sisa', 'Status', 'Catatan']);
            foreach ($rows as $debt) {
                $agingDays = '';
                if ($debt->due_date) {
                    $agingDays = max(now()->startOfDay()->diffInDays($debt->due_date->copy()->startOfDay(), false) * -1, 0);
                }
                fputcsv($out, [
                    (string) $debt->number,
                    (string) ($debt->customer?->name ?? '-'),
                    (string) ($debt->customer?->phone ?? '-'),
                    (string) ($debt->sale?->invoice_number ?? '-'),
                    (string) optional($debt->debt_date)->format('Y-m-d'),
                    (string) optional($debt->due_date)->format('Y-m-d'),
                    $agingDays,
                    (float) $debt->principal_amount,
                    (float) $debt->paid_amount,
                    (float) $debt->remaining_amount,
                    (string) $debt->status,
                    (string) ($debt->note ?? ''),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportExcel(Request $request)
    {
        $status = (string) $request->query('status', 'all');
        $aging = (string) $request->query('aging', 'all');
        $q = trim((string) $request->query('q', ''));
        $fromDate = trim((string) $request->query('from_date', now()->startOfMonth()->toDateString()));
        $toDate = trim((string) $request->query('to_date', now()->toDateString()));

        $query = $this->applyDebtFilters(
            $this->applyPeriodFilter($this->applyBranchScope(CustomerDebt::query(), $request->user()), $fromDate, $toDate),
            $status,
            $aging,
            $q
        );

        $rowCount = (clone $query)->count();
        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'branch_id' => ActiveBranchContext::resolveBranchId($request->user()),
            'action' => 'customer_debt_export_excel',
            'context' => [
                'status' => $status,
                'aging' => $aging,
                'q' => $q,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'row_count' => $rowCount,
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $filename = 'piutang-pelanggan-' . now()->format('Ymd-His') . '.xlsx';
        return Excel::download(new CustomerDebtsExport($query), $filename);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'sale_id' => ['nullable', 'integer', Rule::exists('sales', 'id')],
            'debt_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:debt_date'],
            'principal_amount' => ['required', 'numeric', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer = $this->applyBranchScope(Customer::query(), $request->user())->findOrFail((int) $validated['customer_id']);
        $branchId = ActiveBranchContext::resolveBranchId($request->user());

        if (! empty($validated['sale_id'])) {
            $sale = $this->applyBranchScope(Sale::query(), $request->user())->findOrFail((int) $validated['sale_id']);
            if ((int) $sale->customer_id !== (int) $customer->id) {
                return back()->withErrors(['sale_id' => 'Transaksi pending tidak sesuai dengan pelanggan yang dipilih.'])->withInput();
            }
        }

        $principal = (float) $validated['principal_amount'];

        $debt = $this->createDebtWithUniqueNumber([
            'branch_id' => $branchId,
            'customer_id' => $customer->id,
            'sale_id' => ! empty($validated['sale_id']) ? (int) $validated['sale_id'] : null,
            'created_by' => $request->user()?->id,
            'debt_date' => (string) $validated['debt_date'],
            'due_date' => ! empty($validated['due_date']) ? (string) $validated['due_date'] : null,
            'principal_amount' => $principal,
            'paid_amount' => 0,
            'remaining_amount' => $principal,
            'status' => ! empty($validated['due_date']) && Carbon::parse((string) $validated['due_date'])->isPast() ? 'overdue' : 'active',
            'note' => trim((string) ($validated['note'] ?? '')) ?: null,
        ]);

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'branch_id' => $branchId,
            'action' => 'customer_debt_created',
            'context' => [
                'customer_debt_id' => $debt->id,
                'number' => $debt->number,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'principal_amount' => $principal,
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return back()->with('success', 'Hutang pelanggan berhasil dibuat.');
    }

    public function pay(Request $request, CustomerDebt $debt)
    {
        $user = $request->user();
        $debt = ($user && $user->hasAnyRole(['owner']))
            ? CustomerDebt::query()->findOrFail($debt->id)
            : $this->applyBranchScope(CustomerDebt::query(), $user)->findOrFail($debt->id);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer', 'debit', 'qris', 'e_wallet'])],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $result = DB::transaction(function () use ($debt, $validated, $request): array {
            $debt->refresh();
            if (! in_array($debt->status, ['active', 'overdue'], true)) {
                abort(422, 'Hutang sudah ditutup.');
            }

            $amount = min((float) $validated['amount'], (float) $debt->remaining_amount);
            if ($amount <= 0) {
                abort(422, 'Nominal pembayaran tidak valid.');
            }

            CustomerDebtPayment::query()->create([
                'customer_debt_id' => $debt->id,
                'received_by' => $request->user()?->id,
                'paid_at' => ! empty($validated['paid_at']) ? Carbon::parse((string) $validated['paid_at']) : now(),
                'amount' => $amount,
                'payment_method' => (string) $validated['payment_method'],
                'note' => trim((string) ($validated['note'] ?? '')) ?: null,
            ]);

            $newPaid = (float) $debt->paid_amount + $amount;
            $newRemaining = max((float) $debt->principal_amount - $newPaid, 0);

            $debt->update([
                'paid_amount' => $newPaid,
                'remaining_amount' => $newRemaining,
                'status' => $newRemaining <= 0
                    ? 'paid'
                    : (($debt->due_date && $debt->due_date->isPast()) ? 'overdue' : 'active'),
            ]);

            CashierAuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'branch_id' => $debt->branch_id,
                'action' => 'customer_debt_paid',
                'context' => [
                    'customer_debt_id' => $debt->id,
                    'number' => $debt->number,
                    'payment_amount' => $amount,
                    'remaining_amount' => $newRemaining,
                ],
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);

            return [
                'debt_id' => (int) $debt->id,
                'debt_number' => (string) $debt->number,
                'paid_amount' => (float) $amount,
                'remaining_amount' => (float) $newRemaining,
                'status' => (string) ($newRemaining <= 0
                    ? 'paid'
                    : (($debt->due_date && $debt->due_date->isPast()) ? 'overdue' : 'active')),
            ];
        });

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Pembayaran cicilan berhasil dicatat.',
                'data' => $result,
            ]);
        }

        return back()->with('success', 'Pembayaran cicilan berhasil dicatat.');
    }

    public function payCustomerFifo(Request $request, Customer $customer)
    {
        $user = $request->user();
        $customer = ($user && $user->hasAnyRole(['owner']))
            ? Customer::query()->findOrFail($customer->id)
            : $this->applyBranchScope(Customer::query(), $user)->findOrFail($customer->id);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer', 'debit', 'qris', 'e_wallet'])],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $totalInput = (float) $validated['amount'];
        $paidAt = ! empty($validated['paid_at']) ? Carbon::parse((string) $validated['paid_at']) : now();

        $result = DB::transaction(function () use ($request, $customer, $validated, $totalInput, $paidAt): array {
            $debts = $this->applyBranchScope(CustomerDebt::query(), $request->user())
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['active', 'overdue'])
                ->where('remaining_amount', '>', 0)
                ->orderByRaw("CASE WHEN due_date IS NULL THEN 1 ELSE 0 END")
                ->orderBy('due_date')
                ->orderBy('debt_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($debts->isEmpty()) {
                abort(422, 'Tidak ada piutang aktif/overdue untuk pelanggan ini.');
            }

            $remainingPool = $totalInput;
            $allocated = 0.0;
            $affected = 0;

            foreach ($debts as $debt) {
                if ($remainingPool <= 0) {
                    break;
                }

                $pay = min((float) $debt->remaining_amount, $remainingPool);
                if ($pay <= 0) {
                    continue;
                }

                CustomerDebtPayment::query()->create([
                    'customer_debt_id' => $debt->id,
                    'received_by' => $request->user()?->id,
                    'paid_at' => $paidAt,
                    'amount' => $pay,
                    'payment_method' => (string) $validated['payment_method'],
                    'note' => trim((string) ($validated['note'] ?? '')) ?: 'Pembayaran parsial FIFO pelanggan',
                ]);

                $newPaid = (float) $debt->paid_amount + $pay;
                $newRemaining = max((float) $debt->principal_amount - $newPaid, 0);

                $debt->update([
                    'paid_amount' => $newPaid,
                    'remaining_amount' => $newRemaining,
                    'status' => $newRemaining <= 0
                        ? 'paid'
                        : (($debt->due_date && $debt->due_date->isPast()) ? 'overdue' : 'active'),
                ]);

                $remainingPool -= $pay;
                $allocated += $pay;
                $affected++;
            }

            if ($allocated <= 0) {
                abort(422, 'Nominal tidak dapat dialokasikan ke piutang pelanggan.');
            }

            CashierAuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'branch_id' => ActiveBranchContext::resolveBranchId($request->user()),
                'action' => 'customer_debt_paid_fifo',
                'context' => [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'input_amount' => $totalInput,
                    'allocated_amount' => $allocated,
                    'remaining_unallocated' => max($remainingPool, 0),
                    'affected_debts' => $affected,
                    'payment_method' => (string) $validated['payment_method'],
                ],
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);

            return [
                'input_amount' => $totalInput,
                'allocated_amount' => $allocated,
                'remaining_unallocated' => max($remainingPool, 0),
                'affected_debts' => $affected,
            ];
        });

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Pembayaran parsial berhasil dialokasikan otomatis.',
                'data' => $result,
            ]);
        }

        return back()->with('success', 'Pembayaran parsial berhasil dialokasikan otomatis.');
    }

    private function createDebtWithUniqueNumber(array $attributes): CustomerDebt
    {
        $attempts = 0;
        do {
            $attempts++;
            try {
                return CustomerDebt::query()->create($attributes + [
                    'number' => $this->debtNumberService->nextNumber(),
                ]);
            } catch (QueryException $e) {
                $duplicate = ($e->getCode() === '23000') || str_contains(strtolower((string) $e->getMessage()), 'duplicate');
                if (! $duplicate || $attempts >= 3) {
                    throw $e;
                }
            }
        } while ($attempts < 3);

        throw new \RuntimeException('Gagal membuat nomor hutang unik.');
    }

    private function applyDebtFilters($query, string $status, string $aging, string $q)
    {
        return $query
            ->when($status !== 'all', fn ($builder) => $builder->where('status', $status))
            ->when($aging !== 'all', function ($builder) use ($aging) {
                $today = now()->startOfDay()->toDateString();
                $builder->where('remaining_amount', '>', 0)
                    ->whereNotNull('due_date')
                    ->where(function ($sub) use ($aging, $today) {
                        if ($aging === 'current') {
                            $sub->whereDate('due_date', '>=', $today);
                            return;
                        }
                        if ($aging === 'overdue_1_7') {
                            $sub->whereRaw('DATEDIFF(?, due_date) BETWEEN 1 AND 7', [$today]);
                            return;
                        }
                        if ($aging === 'overdue_8_30') {
                            $sub->whereRaw('DATEDIFF(?, due_date) BETWEEN 8 AND 30', [$today]);
                            return;
                        }
                        if ($aging === 'overdue_30_plus') {
                            $sub->whereRaw('DATEDIFF(?, due_date) > 30', [$today]);
                        }
                    });
            })
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($sub) use ($q) {
                    $sub->where('number', 'like', "%{$q}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"));
                });
            });
    }

    private function applyPeriodFilter($query, string $fromDate, string $toDate)
    {
        $from = $this->normalizeDate($fromDate);
        $to = $this->normalizeDate($toDate);
        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        if ($from) {
            $query->whereDate('debt_date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('debt_date', '<=', $to);
        }

        return $query;
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
