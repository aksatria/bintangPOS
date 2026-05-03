<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Exports\CustomerPurchaseHistoryExport;
use App\Exports\CustomersExport;
use App\Exports\CustomersFollowUpExport;
use App\Models\CashierAuditLog;
use App\Models\Customer;
use App\Models\CustomerFollowUp;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\CustomerDebt;
use App\Models\CustomerDebtPayment;
use App\Support\ActiveBranchContext;
use App\Support\AppliesBranchScope;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    use AppliesBranchScope;

    public function create()
    {
        return view('customers.form', [
            'customer' => new Customer(),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $data['branch_id'] = ActiveBranchContext::resolveBranchId($request->user());
        Customer::query()->create($data);

        return redirect()->route('customers.index')->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $segment = (string) $request->query('segment', 'all');
        $pending = (string) $request->query('pending', 'all');
        $preset = (string) $request->query('preset', 'all');

        $customers = $this->applyBranchScope(Customer::query(), $request->user())
            ->withMax(['sales as last_purchase_at' => fn ($query) => $query->paid()], 'sold_at')
            ->withCount(['sales', 'sales as paid_sales_count' => fn ($query) => $query->paid()])
            ->withCount(['sales as pending_sales_count' => fn ($query) => $query->where('status', SaleStatus::Pending->value)])
            ->withMin(['sales as oldest_pending_at' => fn ($query) => $query->where('status', SaleStatus::Pending->value)], 'sold_at')
            ->withSum(['sales as paid_sales_sum_total_amount' => fn ($query) => $query->paid()], 'total_amount')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('address', 'like', "%{$q}%");
                });
            })
            ->tap(fn ($query) => $this->applySegmentFilter($query, $segment))
            ->when($pending === 'with', fn ($query) => $query->whereHas('sales', fn ($sale) => $sale->where('status', SaleStatus::Pending->value)))
            ->when($pending === 'without', fn ($query) => $query->whereDoesntHave('sales', fn ($sale) => $sale->where('status', SaleStatus::Pending->value)))
            ->tap(fn ($query) => $this->applyPresetFilter($query, $preset))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $metrics = $this->customerMetrics();
        $segmentOptions = $this->segmentOptions();
        $sleepingCustomers = $this->sleepingCustomersFollowUp();
        $pendingAging = $this->pendingAgingSummary();

        $pendingOptions = [
            'all' => 'Semua Pending',
            'with' => 'Ada Pending',
            'without' => 'Tanpa Pending',
        ];

        $presetOptions = [
            'all' => 'Semua Data',
            'risky' => 'Berisiko',
            'vip_sleeping' => 'VIP Tidur',
            'pending_7' => 'Pending > 7 Hari',
            'new_14' => 'Baru 14 Hari',
        ];

        return view('customers.index', compact('customers', 'q', 'segment', 'pending', 'preset', 'metrics', 'segmentOptions', 'pendingOptions', 'presetOptions', 'sleepingCustomers', 'pendingAging'));
    }

    public function exportExcel(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $segment = (string) $request->query('segment', 'all');
        $filename = 'customers-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new CustomersExport(
            $q,
            $segment,
            (int) (ActiveBranchContext::resolveBranchId($request->user()) ?? 0),
            (bool) ($request->user()?->hasAnyRole(['owner']) ?? false),
        ), $filename);
    }

    public function exportFollowUp(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $segment = (string) $request->query('segment', 'all');
        $pending = (string) $request->query('pending', 'all');
        $preset = (string) $request->query('preset', 'all');
        $filename = 'customers-follow-up-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new CustomersFollowUpExport(
            $q,
            $segment,
            $pending,
            $preset,
            (int) (ActiveBranchContext::resolveBranchId($request->user()) ?? 0),
            (bool) ($request->user()?->hasAnyRole(['owner']) ?? false),
        ), $filename);
    }

    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $rows = $this->applyBranchScope(Customer::query(), $request->user())
            ->where('is_active', true)
            ->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'phone', 'email']);

        return response()->json($rows);
    }

    public function edit(Customer $customer)
    {
        return view('customers.form', [
            'customer' => $customer,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $this->validatePayload($request, $customer->id);
        $customer->update($data);

        return redirect()->route('customers.show', $customer)->with('success', 'Data pelanggan berhasil diperbarui.');
    }

    public function toggleActive(Customer $customer)
    {
        $customer->update(['is_active' => ! $customer->is_active]);

        return back()->with('success', $customer->is_active ? 'Pelanggan diaktifkan.' : 'Pelanggan dinonaktifkan.');
    }

    public function show(Request $request, Customer $customer)
    {
        $status = (string) $request->query('status', 'all');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');
        $debtStatus = (string) $request->query('debt_status', 'all');
        $debtAging = (string) $request->query('debt_aging', 'all');
        $debtFromDate = (string) $request->query('debt_from_date', now()->startOfMonth()->toDateString());
        $debtToDate = (string) $request->query('debt_to_date', now()->toDateString());

        $sales = $customer->sales()
            ->with('user:id,name')
            ->when($status !== 'all' && in_array($status, array_keys(SaleStatus::options()), true), fn ($query) => $query->where('status', $status))
            ->when($from !== '', fn ($query) => $query->whereDate('sold_at', '>=', $from))
            ->when($to !== '', fn ($query) => $query->whereDate('sold_at', '<=', $to))
            ->latest('sold_at')
            ->paginate(15)
            ->withQueryString();

        $paidSales = $customer->sales()->paid();
        $pendingCount = (int) $customer->sales()->where('status', SaleStatus::Pending->value)->count();
        $totalSpending = (float) (clone $paidSales)->sum('total_amount');
        $lastPurchase = (clone $paidSales)->latest('sold_at')->value('sold_at');

        $summary = [
            'transactions' => (int) (clone $paidSales)->count(),
            'total_spending' => $totalSpending,
            'last_purchase' => $lastPurchase,
            'points' => (int) floor($totalSpending / 10000),
            'segment' => $this->segmentLabel($customer, $totalSpending, $lastPurchase ? \Illuminate\Support\Carbon::parse($lastPurchase) : null),
            'pending_count' => $pendingCount,
            'risk_score' => $this->riskScore($customer, $pendingCount, $lastPurchase ? Carbon::parse($lastPurchase) : null),
        ];

        $topProducts = SaleItem::query()
            ->selectRaw('sale_items.product_name, sale_items.sku, SUM(sale_items.quantity) as total_qty, SUM(sale_items.subtotal) as total_spending')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.customer_id', $customer->id)
            ->groupBy('sale_items.product_name', 'sale_items.sku')
            ->orderByDesc('total_qty')
            ->orderByDesc('total_spending')
            ->limit(7)
            ->get();

        $pendingSales = $customer->sales()
            ->where('status', SaleStatus::Pending->value)
            ->latest('sold_at')
            ->limit(8)
            ->get();

        $timeline = $this->buildCustomerTimeline($customer);
        $debtBaseQuery = $this->applyBranchScope(CustomerDebt::query(), $request->user())
            ->where('customer_id', $customer->id)
            ->whereDate('debt_date', '>=', $debtFromDate)
            ->whereDate('debt_date', '<=', $debtToDate);

        if ($debtStatus !== 'all') {
            $debtBaseQuery->where('status', $debtStatus);
        }

        if ($debtAging !== 'all') {
            $today = now()->startOfDay()->toDateString();
            $debtBaseQuery->where('remaining_amount', '>', 0)->whereNotNull('due_date');
            if ($debtAging === 'current') {
                $debtBaseQuery->whereDate('due_date', '>=', $today);
            } elseif ($debtAging === 'overdue_1_7') {
                $debtBaseQuery->whereRaw('DATEDIFF(?, due_date) BETWEEN 1 AND 7', [$today]);
            } elseif ($debtAging === 'overdue_8_30') {
                $debtBaseQuery->whereRaw('DATEDIFF(?, due_date) BETWEEN 8 AND 30', [$today]);
            } elseif ($debtAging === 'overdue_30_plus') {
                $debtBaseQuery->whereRaw('DATEDIFF(?, due_date) > 30', [$today]);
            }
        }

        $debtSummary = [
            'active_overdue_count' => (int) (clone $debtBaseQuery)->whereIn('status', ['active', 'overdue'])->count(),
            'remaining_total' => (float) (clone $debtBaseQuery)->whereIn('status', ['active', 'overdue'])->sum('remaining_amount'),
            'paid_total' => (float) CustomerDebtPayment::query()
                ->join('customer_debts', 'customer_debts.id', '=', 'customer_debt_payments.customer_debt_id')
                ->where('customer_debts.customer_id', $customer->id)
                ->whereDate('customer_debts.debt_date', '>=', $debtFromDate)
                ->whereDate('customer_debts.debt_date', '<=', $debtToDate)
                ->when($debtStatus !== 'all', fn ($q) => $q->where('customer_debts.status', $debtStatus))
                ->when(! ($request->user()?->hasAnyRole(['owner']) ?? false), function ($q) use ($request) {
                    $branchId = (int) ($request->user()?->branch_id ?? 0);
                    if ($branchId > 0) {
                        $q->where('customer_debts.branch_id', $branchId);
                    }
                })
                ->sum('customer_debt_payments.amount'),
        ];

        $customerDebts = (clone $debtBaseQuery)
            ->with(['sale:id,invoice_number'])
            ->latest('debt_date')
            ->paginate(15, ['*'], 'debt_page')
            ->withQueryString();

        $debtPaymentHistory = CustomerDebtPayment::query()
            ->join('customer_debts', 'customer_debts.id', '=', 'customer_debt_payments.customer_debt_id')
            ->where('customer_debts.customer_id', $customer->id)
            ->whereDate('customer_debts.debt_date', '>=', $debtFromDate)
            ->whereDate('customer_debts.debt_date', '<=', $debtToDate)
            ->when($debtStatus !== 'all', fn ($q) => $q->where('customer_debts.status', $debtStatus))
            ->when(! ($request->user()?->hasAnyRole(['owner']) ?? false), function ($q) use ($request) {
                $branchId = (int) ($request->user()?->branch_id ?? 0);
                if ($branchId > 0) {
                    $q->where('customer_debts.branch_id', $branchId);
                }
            })
            ->orderByDesc('customer_debt_payments.paid_at')
            ->paginate(15, [
                'customer_debt_payments.customer_debt_id',
                'customer_debt_payments.amount',
                'customer_debt_payments.payment_method',
                'customer_debt_payments.paid_at',
                'customer_debt_payments.note',
            ], 'payment_page')
            ->withQueryString();

        return view('customers.show', compact(
            'customer',
            'sales',
            'summary',
            'status',
            'from',
            'to',
            'topProducts',
            'pendingSales',
            'timeline',
            'customerDebts',
            'debtPaymentHistory',
            'debtStatus',
            'debtAging',
            'debtFromDate',
            'debtToDate',
            'debtSummary'
        ));
    }

    public function exportPurchaseHistory(Request $request, Customer $customer)
    {
        $filename = 'customer-history-'.$customer->id.'-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new CustomerPurchaseHistoryExport(
            $customer,
            (int) (ActiveBranchContext::resolveBranchId($request->user()) ?? 0),
            (bool) ($request->user()?->hasAnyRole(['owner']) ?? false),
        ), $filename);
    }

    public function merge(Request $request, Customer $customer)
    {
        $pendingCount = $customer->sales()->where('status', SaleStatus::Pending->value)->count();
        if ($pendingCount > 0) {
            return back()->withErrors([
                'target_customer_id' => "Merge diblokir: pelanggan sumber masih memiliki {$pendingCount} transaksi pending.",
            ]);
        }

        $data = $request->validate([
            'target_customer_id' => ['required', 'integer', 'exists:customers,id'],
        ]);

        $target = $this->applyBranchScope(Customer::query(), $request->user())->findOrFail((int) $data['target_customer_id']);
        if ($target->id === $customer->id) {
            return back()->withErrors(['target_customer_id' => 'Pelanggan tujuan tidak boleh sama.']);
        }

        $movedSalesCount = 0;
        DB::transaction(function () use ($customer, $target, &$movedSalesCount) {
            $movedSalesCount = $this->applyBranchScope(\App\Models\Sale::query(), request()->user())
                ->where('customer_id', $customer->id)
                ->update(['customer_id' => $target->id]);

            if (! $target->phone && $customer->phone) {
                $target->phone = $customer->phone;
            }
            if (! $target->email && $customer->email) {
                $target->email = $customer->email;
            }
            if (! $target->address && $customer->address) {
                $target->address = $customer->address;
            }

            $sourceNote = trim((string) $customer->internal_note);
            $targetNote = trim((string) $target->internal_note);
            if ($sourceNote !== '') {
                $target->internal_note = $targetNote === ''
                    ? $sourceNote
                    : ($targetNote."\n\n[Gabungan dari {$customer->name}] ".$sourceNote);
            }
            $target->is_active = true;
            $target->save();

            $customer->is_active = false;
            $customer->internal_note = trim(((string) $customer->internal_note)."\n\nDigabung ke customer #{$target->id} ({$target->name}) pada ".now()->format('d/m/Y H:i'));
            $customer->save();
        });

        CashierAuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => 'customer_merged',
            'context' => [
                'source_customer_id' => $customer->id,
                'source_customer_name' => $customer->name,
                'target_customer_id' => $target->id,
                'target_customer_name' => $target->name,
                'moved_sales_count' => $movedSalesCount,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return redirect()
            ->route('customers.show', $target)
            ->with('success', "Pelanggan {$customer->name} berhasil digabung ke {$target->name}.");
    }

    public function settlePending(Request $request, Customer $customer, Sale $sale)
    {
        if ($sale->customer_id !== $customer->id || $sale->status !== SaleStatus::Pending) {
            abort(404);
        }

        $paidAmount = max((float) $request->input('paid_amount', (float) $sale->total_amount), (float) $sale->total_amount);
        $changeAmount = max($paidAmount - (float) $sale->total_amount, 0);
        $sale->status = SaleStatus::Paid;
        $sale->paid_amount = $paidAmount;
        $sale->change_amount = $changeAmount;
        $sale->note = trim(((string) $sale->note)."\n[Dilunasi] ".now()->format('d/m/Y H:i'));
        $sale->save();

        CashierAuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => 'pending_settled',
            'context' => [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'customer_id' => $customer->id,
                'paid_amount' => $paidAmount,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return back()->with('success', "Transaksi pending {$sale->invoice_number} berhasil dilunasi.");
    }

    public function cancelPending(Request $request, Customer $customer, Sale $sale)
    {
        if ($sale->customer_id !== $customer->id || $sale->status !== SaleStatus::Pending) {
            abort(404);
        }

        $sale->status = SaleStatus::Cancelled;
        $sale->note = trim(((string) $sale->note)."\n[Dibatalkan] ".now()->format('d/m/Y H:i'));
        $sale->save();

        CashierAuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => 'pending_cancelled',
            'context' => [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'customer_id' => $customer->id,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return back()->with('success', "Transaksi pending {$sale->invoice_number} berhasil dibatalkan.");
    }

    public function reschedulePending(Request $request, Customer $customer, Sale $sale)
    {
        if ($sale->customer_id !== $customer->id || $sale->status !== SaleStatus::Pending) {
            abort(404);
        }

        $data = $request->validate([
            'reschedule_date' => ['required', 'date'],
        ]);

        $newDate = Carbon::parse($data['reschedule_date']);
        $sale->sold_at = $newDate->setTimeFrom(now());
        $sale->note = trim(((string) $sale->note)."\n[Reschedule Pending] ke ".$newDate->format('d/m/Y'));
        $sale->save();

        CashierAuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => 'pending_rescheduled',
            'context' => [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'customer_id' => $customer->id,
                'new_date' => $newDate->toDateString(),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return back()->with('success', "Tanggal pending {$sale->invoice_number} berhasil diperbarui.");
    }

    public function mergeSuggest(Request $request, Customer $customer)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $rows = $this->applyBranchScope(Customer::query(), $request->user())
            ->where('id', '!=', $customer->id)
            ->where('is_active', true)
            ->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'phone', 'email']);

        return response()->json($rows);
    }

    public function quickFollowUp(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'action_type' => ['required', Rule::in(['note', 'reminder', 'message'])],
            'note' => ['nullable', 'string', 'max:500'],
            'reminder_at' => ['nullable', 'date'],
        ]);

        $actionType = (string) $data['action_type'];
        $note = trim((string) ($data['note'] ?? ''));
        $reminderAt = !empty($data['reminder_at']) ? Carbon::parse($data['reminder_at']) : null;

        $line = match ($actionType) {
            'note' => '[Follow-up] '.now()->format('d/m/Y H:i').($note !== '' ? " - {$note}" : ''),
            'reminder' => '[Reminder Follow-up] '.($reminderAt ? $reminderAt->format('d/m/Y H:i') : now()->format('d/m/Y H:i')).($note !== '' ? " - {$note}" : ''),
            'message' => '[Kirim Pesan] '.now()->format('d/m/Y H:i').($note !== '' ? " - {$note}" : ''),
        };

        $customer->internal_note = trim(((string) $customer->internal_note)."\n".$line);
        $customer->save();

        $followUp = CustomerFollowUp::query()->create([
            'branch_id' => ActiveBranchContext::resolveBranchId(auth()->user()),
            'customer_id' => $customer->id,
            'created_by' => auth()->id(),
            'action_type' => $actionType,
            'status' => 'baru',
            'note' => $note !== '' ? $note : $line,
            'reminder_at' => $actionType === 'reminder' ? $reminderAt : null,
        ]);

        CashierAuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => 'customer_followup_logged',
            'context' => [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'action_type' => $actionType,
                'note' => $note,
                'reminder_at' => $reminderAt?->toDateTimeString(),
                'followup_id' => $followUp->id,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return back()->with('success', 'Aksi follow-up pelanggan berhasil dicatat.');
    }

    public function quickCreateFollowUp(Customer $customer)
    {
        $followUp = CustomerFollowUp::query()->create([
            'branch_id' => ActiveBranchContext::resolveBranchId(auth()->user()),
            'customer_id' => $customer->id,
            'created_by' => auth()->id(),
            'action_type' => 'note',
            'status' => 'baru',
            'note' => 'Follow-up cepat dari daftar pelanggan.',
            'reminder_at' => now()->addHour(),
        ]);

        CashierAuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => 'customer_followup_quick_created',
            'context' => [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'followup_id' => $followUp->id,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);

        return back()->with('success', 'Follow-up cepat berhasil dibuat.');
    }

    public function followUpQueue(Request $request)
    {
        $date = (string) $request->query('date', now()->toDateString());
        $status = (string) $request->query('status', 'all');

        $queue = $this->applyBranchScope(CustomerFollowUp::query(), $request->user())
            ->with(['customer:id,name,phone,email', 'creator:id,name'])
            ->whereDate('reminder_at', $date)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByRaw("FIELD(status,'baru','proses','gagal','selesai')")
            ->orderBy('reminder_at')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total' => (int) $this->applyBranchScope(CustomerFollowUp::query(), $request->user())->whereDate('reminder_at', $date)->count(),
            'baru' => (int) $this->applyBranchScope(CustomerFollowUp::query(), $request->user())->whereDate('reminder_at', $date)->where('status', 'baru')->count(),
            'proses' => (int) $this->applyBranchScope(CustomerFollowUp::query(), $request->user())->whereDate('reminder_at', $date)->where('status', 'proses')->count(),
            'selesai' => (int) $this->applyBranchScope(CustomerFollowUp::query(), $request->user())->whereDate('reminder_at', $date)->where('status', 'selesai')->count(),
            'gagal' => (int) $this->applyBranchScope(CustomerFollowUp::query(), $request->user())->whereDate('reminder_at', $date)->where('status', 'gagal')->count(),
        ];

        return view('customers.followups', compact('queue', 'date', 'status', 'summary'));
    }

    public function updateFollowUpStatus(Request $request, CustomerFollowUp $followup)
    {
        if (! auth()->user()?->hasAnyRole(['owner', 'admin'])) {
            abort(403, 'Anda tidak memiliki akses ke aksi ini.');
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['baru', 'proses', 'selesai', 'gagal'])],
        ]);

        $status = (string) $data['status'];
        $followup->status = $status;
        $followup->completed_at = $status === 'selesai' ? now() : null;
        $followup->save();

        CashierAuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => 'customer_followup_status_updated',
            'context' => [
                'followup_id' => $followup->id,
                'customer_id' => $followup->customer_id,
                'status' => $status,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return back()->with('success', 'Status follow-up berhasil diperbarui.');
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('customers', 'phone')->ignore($ignoreId)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('customers', 'email')->ignore($ignoreId)],
            'address' => ['nullable', 'string', 'max:500'],
            'internal_note' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }

    private function customerMetrics(): array
    {
        $active = (int) $this->applyBranchScope(Customer::query(), auth()->user())->where('is_active', true)->count();
        $new30 = (int) $this->applyBranchScope(Customer::query(), auth()->user())->where('created_at', '>=', now()->subDays(30))->count();
        $customersWithPaidSales = (int) $this->applyBranchScope(Customer::query(), auth()->user())->whereHas('sales', fn ($query) => $query->paid())->count();
        $total = (int) $this->applyBranchScope(Customer::query(), auth()->user())->count();
        $paidRevenue = (float) $this->applyBranchScope(\App\Models\Sale::query(), auth()->user())->paid()->sum('total_amount');
        $pendingCustomers = (int) $this->applyBranchScope(Customer::query(), auth()->user())->whereHas('sales', fn ($query) => $query->where('status', SaleStatus::Pending->value))->count();
        $sleepingCount = (int) $this->applyBranchScope(Customer::query(), auth()->user())
            ->where('is_active', true)
            ->where(function ($sub) {
                $sub->whereDoesntHave('sales', fn ($sale) => $sale->paid())
                    ->orWhereRaw(
                        '(SELECT MAX(sold_at) FROM sales WHERE sales.customer_id = customers.id AND status = ?) < ?',
                        [SaleStatus::Paid->value, now()->subDays(30)->toDateTimeString()]
                    );
            })
            ->count();
        $mergedThisMonth = (int) $this->applyBranchScope(CashierAuditLog::query(), auth()->user())
            ->where('action', 'customer_merged')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        return [
            'active' => $active,
            'new30' => $new30,
            'repeat_rate' => $total > 0 ? round(($customersWithPaidSales / $total) * 100, 1) : 0,
            'avg_spending' => $customersWithPaidSales > 0 ? $paidRevenue / $customersWithPaidSales : 0,
            'pending_customers' => $pendingCustomers,
            'sleeping_customers' => $sleepingCount,
            'merged_this_month' => $mergedThisMonth,
        ];
    }

    private function segmentOptions(): array
    {
        return [
            'all' => 'Semua',
            'active' => 'Aktif',
            'vip' => 'VIP',
            'new' => 'Baru',
            'sleeping' => 'Tidur',
            'inactive' => 'Nonaktif',
        ];
    }

    private function applySegmentFilter($query, string $segment): void
    {
        $sleepingCutoff = now()->subDays(30)->toDateTimeString();
        match ($segment) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            'vip' => $query->whereRaw(
                '(SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE sales.customer_id = customers.id AND status = ?) >= ?',
                [SaleStatus::Paid->value, 1000000]
            ),
            'new' => $query->where('created_at', '>=', now()->subDays(30)),
            'sleeping' => $query->where(function ($sub) use ($sleepingCutoff) {
                $sub->whereDoesntHave('sales', fn ($sale) => $sale->paid())
                    ->orWhereRaw(
                        '(SELECT MAX(sold_at) FROM sales WHERE sales.customer_id = customers.id AND status = ?) < ?',
                        [SaleStatus::Paid->value, $sleepingCutoff]
                    );
            }),
            default => null,
        };
    }

    private function applyPresetFilter($query, string $preset): void
    {
        $sleepingCutoff = now()->subDays(30)->toDateTimeString();
        match ($preset) {
            'risky' => $query->where(function ($sub) use ($sleepingCutoff) {
                $sub->whereHas('sales', fn ($sale) => $sale->where('status', SaleStatus::Pending->value))
                    ->orWhere(function ($w) use ($sleepingCutoff) {
                        $w->where('is_active', true)
                            ->whereRaw(
                                '(SELECT MAX(sold_at) FROM sales WHERE sales.customer_id = customers.id AND status = ?) < ?',
                                [SaleStatus::Paid->value, $sleepingCutoff]
                            );
                    });
            }),
            'vip_sleeping' => $query
                ->whereRaw(
                    '(SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE sales.customer_id = customers.id AND status = ?) >= ?',
                    [SaleStatus::Paid->value, 1000000]
                )
                ->where(function ($sub) use ($sleepingCutoff) {
                    $sub->whereDoesntHave('sales', fn ($sale) => $sale->paid())
                        ->orWhereRaw(
                            '(SELECT MAX(sold_at) FROM sales WHERE sales.customer_id = customers.id AND status = ?) < ?',
                            [SaleStatus::Paid->value, $sleepingCutoff]
                        );
                }),
            'pending_7' => $query->whereHas('sales', fn ($sale) => $sale
                ->where('status', SaleStatus::Pending->value)
                ->whereDate('sold_at', '<=', now()->subDays(7)->toDateString())),
            'new_14' => $query->where('created_at', '>=', now()->subDays(14)),
            default => null,
        };
    }

    private function segmentLabel(Customer $customer, float $totalSpending, ?\Illuminate\Support\Carbon $lastPurchase): string
    {
        if (! $customer->is_active) {
            return 'Nonaktif';
        }

        if ($totalSpending >= 1000000) {
            return 'VIP';
        }

        if ($customer->created_at?->gte(now()->subDays(30))) {
            return 'Baru';
        }

        if (! $lastPurchase || $lastPurchase->lt(now()->subDays(30))) {
            return 'Tidur';
        }

        return 'Aktif';
    }

    private function sleepingCustomersFollowUp()
    {
        $sleepingCutoff = now()->subDays(30)->toDateTimeString();
        return $this->applyBranchScope(Customer::query(), auth()->user())
            ->where('is_active', true)
            ->withMax(['sales as last_purchase_at' => fn ($query) => $query->paid()], 'sold_at')
            ->withCount(['sales as paid_sales_count' => fn ($query) => $query->paid()])
            ->withSum(['sales as paid_sales_sum_total_amount' => fn ($query) => $query->paid()], 'total_amount')
            ->where(function ($sub) use ($sleepingCutoff) {
                $sub->whereDoesntHave('sales', fn ($sale) => $sale->paid())
                    ->orWhereRaw(
                        '(SELECT MAX(sold_at) FROM sales WHERE sales.customer_id = customers.id AND status = ?) < ?',
                        [SaleStatus::Paid->value, $sleepingCutoff]
                    );
            })
            ->orderByRaw('CASE WHEN last_purchase_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('last_purchase_at')
            ->limit(6)
            ->get()
            ->map(function ($customer) {
                $last = $customer->last_purchase_at ? Carbon::parse($customer->last_purchase_at) : null;

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'last_purchase_text' => $last ? $last->format('d M Y') : 'Belum pernah belanja',
                    'inactive_days' => $last ? (int) round(now()->diffInDays($last)) : null,
                    'total_spending' => (float) ($customer->paid_sales_sum_total_amount ?? 0),
                ];
            });
    }

    private function pendingAgingSummary(): array
    {
        $pendingSales = $this->applyBranchScope(Sale::query(), auth()->user())
            ->where('status', SaleStatus::Pending->value)
            ->get(['sold_at']);

        $bucket = [
            '0_3' => 0,
            '4_7' => 0,
            '8_14' => 0,
            '15_plus' => 0,
        ];

        foreach ($pendingSales as $sale) {
            $age = (int) round(Carbon::parse($sale->sold_at)->diffInDays(now()));
            if ($age <= 3) {
                $bucket['0_3']++;
            } elseif ($age <= 7) {
                $bucket['4_7']++;
            } elseif ($age <= 14) {
                $bucket['8_14']++;
            } else {
                $bucket['15_plus']++;
            }
        }

        return $bucket;
    }

    private function riskScore(Customer $customer, int $pendingCount, ?Carbon $lastPurchase): int
    {
        $score = 0;

        if (! $customer->is_active) {
            $score += 20;
        }

        if ($pendingCount >= 3) {
            $score += 40;
        } elseif ($pendingCount > 0) {
            $score += 20;
        }

        if (! $lastPurchase) {
            $score += 25;
        } else {
            $inactiveDays = (int) round(now()->diffInDays($lastPurchase));
            if ($inactiveDays >= 60) {
                $score += 30;
            } elseif ($inactiveDays >= 30) {
                $score += 15;
            }
        }

        return min($score, 100);
    }

    private function buildCustomerTimeline(Customer $customer): array
    {
        $events = [];

        $firstSale = $customer->sales()->orderBy('sold_at')->first(['sold_at', 'invoice_number', 'status']);
        if ($firstSale) {
            $events[] = [
                'at' => $firstSale->sold_at,
                'title' => 'Transaksi pertama',
                'description' => $firstSale->invoice_number.' ('.strtoupper($firstSale->status->value).')',
            ];
        }

        $lastSale = $customer->sales()->latest('sold_at')->first(['sold_at', 'invoice_number', 'status']);
        if ($lastSale) {
            $events[] = [
                'at' => $lastSale->sold_at,
                'title' => 'Transaksi terakhir',
                'description' => $lastSale->invoice_number.' ('.strtoupper($lastSale->status->value).')',
            ];
        }

        $logs = $this->applyBranchScope(CashierAuditLog::query(), auth()->user())
            ->whereIn('action', ['pending_settled', 'pending_cancelled', 'pending_rescheduled', 'customer_merged', 'customer_followup_logged'])
            ->where(function ($query) use ($customer) {
                $query->where('context->customer_id', $customer->id)
                    ->orWhere('context->source_customer_id', $customer->id)
                    ->orWhere('context->target_customer_id', $customer->id);
            })
            ->latest('created_at')
            ->limit(12)
            ->get(['action', 'context', 'created_at']);

        foreach ($logs as $log) {
            $description = match ($log->action) {
                'pending_settled' => 'Pending dilunasi: '.($log->context['invoice'] ?? '-'),
                'pending_cancelled' => 'Pending dibatalkan: '.($log->context['invoice'] ?? '-'),
                'pending_rescheduled' => 'Pending dijadwal ulang: '.($log->context['invoice'] ?? '-'),
                'customer_merged' => 'Data pelanggan digabung.',
                'customer_followup_logged' => 'Catatan follow-up: '.($log->context['action_type'] ?? '-'),
                default => $log->action,
            };
            $events[] = [
                'at' => $log->created_at,
                'title' => 'Aktivitas sistem',
                'description' => $description,
            ];
        }

        usort($events, fn ($a, $b) => strtotime((string) $b['at']) <=> strtotime((string) $a['at']));
        return array_slice($events, 0, 12);
    }
}
