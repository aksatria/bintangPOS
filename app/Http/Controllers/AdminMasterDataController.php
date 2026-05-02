<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\CashierAuditLog;
use App\Models\Expense;
use App\Models\RolePermissionGrant;
use App\Models\StoreSetting;
use App\Models\User;
use App\Support\AppliesBranchScope;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminMasterDataController extends Controller
{
    use AppliesBranchScope;

    private const EXPENSE_CATEGORIES = [
        'Operasional',
        'Utilitas',
        'Inventaris',
        'Marketing',
        'Transport',
        'Karyawan',
        'Lainnya',
    ];

    private function wantsJsonResponse(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }

    private function resolveExpenseFilters(Request $request): array
    {
        $q = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', 'all'));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $preset = trim((string) $request->query('preset', ''));

        if ($preset === 'today') {
            $dateFrom = now()->toDateString();
            $dateTo = now()->toDateString();
        } elseif ($preset === '7d') {
            $dateFrom = now()->subDays(6)->toDateString();
            $dateTo = now()->toDateString();
        } elseif ($preset === 'month') {
            $dateFrom = now()->startOfMonth()->toDateString();
            $dateTo = now()->endOfMonth()->toDateString();
        }

        if (!in_array($category, self::EXPENSE_CATEGORIES, true)) {
            $category = 'all';
        }

        return compact('q', 'category', 'dateFrom', 'dateTo', 'preset');
    }

    private function buildExpenseQuery(array $filters)
    {
        $query = $this->applyBranchScope(Expense::query(), request()->user())
            ->with('user:id,name')
            ->when(($filters['q'] ?? '') !== '', function ($builder) use ($filters) {
                $q = (string) $filters['q'];
                $builder->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', "%{$q}%")
                        ->orWhere('note', 'like', "%{$q}%");
                });
            })
            ->when(($filters['category'] ?? 'all') !== 'all', fn ($builder) => $builder->where('category', $filters['category']));

        if (($filters['dateFrom'] ?? '') !== '') {
            $query->whereDate('date', '>=', $filters['dateFrom']);
        }
        if (($filters['dateTo'] ?? '') !== '') {
            $query->whereDate('date', '<=', $filters['dateTo']);
        }

        return $query;
    }

    private function logExpenseAction(Request $request, string $action, Expense $expense, array $context = []): void
    {
        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'context' => array_merge([
                'expense_id' => $expense->id,
                'title' => $expense->title,
                'category' => $expense->category,
                'amount' => (float) $expense->amount,
                'date' => optional($expense->date)->format('Y-m-d'),
            ], $context),
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
    }

    public function expensesIndex(Request $request)
    {
        $filters = $this->resolveExpenseFilters($request);
        $baseQuery = $this->buildExpenseQuery($filters);

        $expenses = (clone $baseQuery)
            ->latest('date')
            ->latest('id')
            ->paginate(5)
            ->withQueryString();

        $totalAmount = (clone $baseQuery)->sum('amount');
        $byCategory = (clone $baseQuery)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $expenseIds = $expenses->pluck('id')->map(fn ($id) => (int) $id)->all();
        $historyRows = collect();
        if (count($expenseIds) > 0) {
            $historyRows = CashierAuditLog::query()
                ->with('user:id,name')
                ->whereIn('action', ['expense_created', 'expense_updated', 'expense_deleted', 'expense_duplicated'])
                ->where(function ($query) use ($expenseIds) {
                    foreach ($expenseIds as $expenseId) {
                        $query->orWhereRaw("JSON_EXTRACT(context, '$.expense_id') = ?", [$expenseId]);
                    }
                })
                ->latest('id')
                ->limit(200)
                ->get();
        }
        $historyMap = $historyRows
            ->groupBy(fn (CashierAuditLog $log) => (int) (data_get($log->context, 'expense_id') ?? 0))
            ->map(fn ($rows) => $rows->take(3)->values())
            ->all();
        $monthAmount = $this->applyBranchScope(Expense::query(), $request->user())
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->sum('amount');
        $todayAmount = $this->applyBranchScope(Expense::query(), $request->user())
            ->whereDate('date', now()->toDateString())
            ->sum('amount');
        $setting = StoreSetting::query()->first();
        $expenseLargeThreshold = (float) ($setting?->expense_large_threshold ?? 1000000);

        return view('admin.expenses', [
            'expenses' => $expenses,
            'filters' => $filters,
            'categories' => self::EXPENSE_CATEGORIES,
            'byCategory' => $byCategory,
            'historyMap' => $historyMap,
            'stats' => [
                'today' => $todayAmount,
                'month' => $monthAmount,
                'filtered_total' => $totalAmount,
                'count' => $expenses->total(),
            ],
            'expenseLargeThreshold' => $expenseLargeThreshold,
        ]);
    }

    public function expensesStore(Request $request)
    {
        $validated = $request->validate([
            'category' => ['required', Rule::in(self::EXPENSE_CATEGORIES)],
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $setting = StoreSetting::query()->first();
        $largeExpenseThreshold = (float) ($setting?->expense_large_threshold ?? 1000000);
        if ((float) $validated['amount'] >= $largeExpenseThreshold && trim((string) ($validated['note'] ?? '')) === '') {
            $message = 'Catatan wajib diisi untuk nominal besar (>= Rp ' . number_format($largeExpenseThreshold, 0, ',', '.') . ').';
            if ($this->wantsJsonResponse($request)) {
                return response()->json(['message' => $message], 422);
            }
            return back()->withErrors(['note' => $message])->withInput();
        }

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('expenses', 'public');
        }

        $expense = Expense::query()->create([
            'user_id' => $request->user()?->id,
            'branch_id' => $request->user()?->branch_id,
            'category' => (string) $validated['category'],
            'title' => trim((string) $validated['title']),
            'amount' => (float) $validated['amount'],
            'date' => (string) $validated['date'],
            'note' => trim((string) ($validated['note'] ?? '')) ?: null,
            'receipt_path' => $receiptPath,
        ]);
        $this->logExpenseAction($request, 'expense_created', $expense);

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'message' => 'Pengeluaran berhasil ditambahkan.',
                'expense' => [
                    'id' => $expense->id,
                    'title' => $expense->title,
                    'amount' => (float) $expense->amount,
                    'date' => optional($expense->date)->format('Y-m-d'),
                    'note' => $expense->note,
                    'category' => $expense->category,
                ],
            ]);
        }

        return back()->with('status', 'Pengeluaran berhasil ditambahkan.');
    }

    public function expensesUpdate(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'category' => ['required', Rule::in(self::EXPENSE_CATEGORIES)],
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'remove_receipt' => ['nullable', 'in:1'],
        ]);

        $setting = StoreSetting::query()->first();
        $largeExpenseThreshold = (float) ($setting?->expense_large_threshold ?? 1000000);
        if ((float) $validated['amount'] >= $largeExpenseThreshold && trim((string) ($validated['note'] ?? '')) === '') {
            $message = 'Catatan wajib diisi untuk nominal besar (>= Rp ' . number_format($largeExpenseThreshold, 0, ',', '.') . ').';
            if ($this->wantsJsonResponse($request)) {
                return response()->json(['message' => $message], 422);
            }
            return back()->withErrors(['note' => $message])->withInput();
        }

        $payload = [
            'category' => (string) $validated['category'],
            'title' => trim((string) $validated['title']),
            'amount' => (float) $validated['amount'],
            'date' => (string) $validated['date'],
            'note' => trim((string) ($validated['note'] ?? '')) ?: null,
        ];

        if (($validated['remove_receipt'] ?? null) === '1' && !empty($expense->receipt_path)) {
            Storage::disk('public')->delete((string) $expense->receipt_path);
            $payload['receipt_path'] = null;
        }

        if ($request->hasFile('receipt')) {
            if (!empty($expense->receipt_path)) {
                Storage::disk('public')->delete((string) $expense->receipt_path);
            }
            $payload['receipt_path'] = $request->file('receipt')->store('expenses', 'public');
        }

        $expense->update($payload);
        $this->logExpenseAction($request, 'expense_updated', $expense);

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'message' => 'Pengeluaran berhasil diperbarui.',
            ]);
        }

        return back()->with('status', 'Pengeluaran berhasil diperbarui.');
    }

    public function expensesDestroy(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'delete_reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $expense->update([
            'delete_reason' => trim((string) $validated['delete_reason']),
        ]);
        $this->logExpenseAction($request, 'expense_deleted', $expense, [
            'delete_reason' => trim((string) $validated['delete_reason']),
        ]);
        $expense->delete();

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'message' => 'Pengeluaran berhasil dihapus.',
            ]);
        }

        return back()->with('status', 'Pengeluaran berhasil dihapus.');
    }

    public function expensesDuplicate(Request $request, Expense $expense)
    {
        $copy = Expense::query()->create([
            'user_id' => $request->user()?->id,
            'branch_id' => $request->user()?->branch_id,
            'category' => (string) ($expense->category ?: 'Operasional'),
            'title' => (string) $expense->title . ' (Copy)',
            'amount' => (float) $expense->amount,
            'date' => now()->toDateString(),
            'note' => (string) ($expense->note ?: ''),
            'receipt_path' => null,
        ]);

        $this->logExpenseAction($request, 'expense_duplicated', $copy, [
            'source_expense_id' => $expense->id,
        ]);

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'message' => 'Pengeluaran berhasil diduplikasi.',
            ]);
        }

        return back()->with('status', 'Pengeluaran berhasil diduplikasi.');
    }

    public function expensesExportCsv(Request $request)
    {
        $filters = $this->resolveExpenseFilters($request);
        $rows = $this->buildExpenseQuery($filters)
            ->latest('date')
            ->latest('id')
            ->get();

        $filename = 'pengeluaran-' . Carbon::now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Tanggal', 'Kategori', 'Judul', 'Nominal', 'Catatan', 'Input Oleh']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    optional($row->date)->format('Y-m-d'),
                    (string) ($row->category ?? 'Operasional'),
                    (string) $row->title,
                    (float) $row->amount,
                    (string) ($row->note ?? ''),
                    (string) ($row->user?->name ?? '-'),
                ]);
            }
            fclose($out);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function usersIndex(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $role = (string) $request->query('role', 'all');
        $branchId = (int) $request->query('branch_id', 0);

        $usersQuery = $this->applyBranchScope(User::query(), $request->user())
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when(in_array($role, ['owner', 'admin', 'kasir'], true), fn ($query) => $query->where('role', $role))
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))
            ->with('branch:id,name')
            ->latest('id')
            ->paginate(12);
        $users = $usersQuery->withQueryString();

        $branches = Branch::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        return view('admin.users', [
            'users' => $users,
            'filters' => compact('q', 'role', 'branchId'),
            'branches' => $branches,
        ]);
    }

    public function usersSuggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([
                'data' => [],
            ]);
        }

        $suggestions = $this->applyBranchScope(User::query(), $request->user())
            ->select(['id', 'name', 'email', 'role'])
            ->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'role' => $user->role->value,
                'label' => sprintf('%s (%s)', (string) $user->name, (string) $user->email),
            ])
            ->values();

        return response()->json([
            'data' => $suggestions,
        ]);
    }

    public function usersStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', Rule::in([UserRole::Owner->value, UserRole::Admin->value, UserRole::Cashier->value])],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'password' => ['required', 'string', 'min:6', 'max:120'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $payload = [
            'name' => trim((string) $validated['name']),
            'email' => strtolower(trim((string) $validated['email'])),
            'role' => (string) $validated['role'],
            'branch_id' => $this->resolveAssignedBranchId($request, $validated['branch_id'] ?? null),
            'password' => (string) $validated['password'],
        ];

        if ($request->hasFile('photo')) {
            $payload['photo'] = $request->file('photo')->store('users', 'public');
        }

        $user = User::query()->create($payload);

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'message' => 'Pengguna berhasil ditambahkan.',
                'user' => [
                    'id' => $user->id,
                    'name' => (string) $user->name,
                    'email' => (string) $user->email,
                    'role' => $user->role->value,
                    'branch_id' => $user->branch_id,
                ],
            ]);
        }

        return back()->with('status', 'Pengguna berhasil ditambahkan.');
    }

    public function usersUpdate(Request $request, User $user)
    {
        $beforeRole = (string) ($user->role?->value ?? $user->role ?? '');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in([UserRole::Owner->value, UserRole::Admin->value, UserRole::Cashier->value])],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'password' => ['nullable', 'string', 'min:6', 'max:120'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'remove_photo' => ['nullable', 'in:1'],
        ]);

        $payload = [
            'name' => trim((string) $validated['name']),
            'email' => strtolower(trim((string) $validated['email'])),
            'role' => (string) $validated['role'],
            'branch_id' => $this->resolveAssignedBranchId($request, $validated['branch_id'] ?? null),
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = (string) $validated['password'];
        }

        if (($validated['remove_photo'] ?? null) === '1' && ! empty($user->photo)) {
            Storage::disk('public')->delete((string) $user->photo);
            $payload['photo'] = null;
        }

        if ($request->hasFile('photo')) {
            if (! empty($user->photo)) {
                Storage::disk('public')->delete((string) $user->photo);
            }
            $payload['photo'] = $request->file('photo')->store('users', 'public');
        }

        $user->update($payload);
        $afterRole = (string) ($user->role?->value ?? $user->role ?? '');
        if ($beforeRole !== '' && $afterRole !== '' && $beforeRole !== $afterRole) {
            $revoked = RolePermissionGrant::query()
                ->where('is_active', true)
                ->where(function ($q) use ($user, $beforeRole) {
                    $q->where('user_id', $user->id)
                        ->orWhere(function ($qr) use ($beforeRole) {
                            $qr->whereNull('user_id')->where('role', $beforeRole);
                        });
                })
                ->update(['is_active' => false]);

            if ($revoked > 0) {
                CashierAuditLog::query()->create([
                    'user_id' => $request->user()?->id,
                    'action' => 'rbac_temp_grants_auto_revoked_role_changed',
                    'context' => [
                        'target_user_id' => (int) $user->id,
                        'from_role' => $beforeRole,
                        'to_role' => $afterRole,
                        'revoked_count' => (int) $revoked,
                    ],
                    'ip_address' => (string) $request->ip(),
                    'user_agent' => (string) ($request->userAgent() ?? ''),
                ]);
            }
        }

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'message' => 'Pengguna berhasil diperbarui.',
                'user' => [
                    'id' => $user->id,
                    'name' => (string) $user->name,
                    'email' => (string) $user->email,
                    'role' => $user->role->value,
                    'branch_id' => $user->branch_id,
                    'photo_url' => !empty($user->photo) ? asset('storage/' . ltrim((string) $user->photo, '/')) : null,
                ],
            ]);
        }

        return back()->with('status', 'Pengguna berhasil diperbarui.');
    }

    public function usersDestroy(Request $request, User $user)
    {
        if ((int) $request->user()?->id === (int) $user->id) {
            if ($this->wantsJsonResponse($request)) {
                return response()->json([
                    'message' => 'Akun Anda sendiri tidak bisa dihapus.',
                ], 422);
            }
            return back()->withErrors(['user' => 'Akun Anda sendiri tidak bisa dihapus.']);
        }

        if (! empty($user->photo)) {
            Storage::disk('public')->delete((string) $user->photo);
        }
        $targetRole = (string) ($user->role?->value ?? $user->role ?? '');
        $revoked = RolePermissionGrant::query()
            ->where('is_active', true)
            ->where(function ($q) use ($user, $targetRole) {
                $q->where('user_id', $user->id)
                    ->orWhere(function ($qr) use ($targetRole) {
                        $qr->whereNull('user_id')->where('role', $targetRole);
                    });
            })
            ->update(['is_active' => false]);

        $user->delete();

        if ($revoked > 0) {
            CashierAuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'action' => 'rbac_temp_grants_auto_revoked_user_deleted',
                'context' => [
                    'target_user_id' => (int) $user->id,
                    'target_role' => $targetRole,
                    'revoked_count' => (int) $revoked,
                ],
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) ($request->userAgent() ?? ''),
            ]);
        }

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'message' => 'Pengguna berhasil dihapus.',
            ]);
        }

        return back()->with('status', 'Pengguna berhasil dihapus.');
    }

    public function branchesStore(Request $request): JsonResponse
    {
        $this->ensureOwnerOnly($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:branches,code'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama cabang wajib diisi.',
            'name.max' => 'Nama cabang maksimal 120 karakter.',
            'code.required' => 'Kode cabang wajib diisi.',
            'code.max' => 'Kode cabang maksimal 50 karakter.',
            'code.alpha_dash' => 'Kode cabang hanya boleh berisi huruf, angka, tanda minus (-), dan underscore (_).',
            'code.unique' => 'Kode cabang sudah digunakan. Gunakan kode lain.',
            'address.max' => 'Alamat cabang maksimal 255 karakter.',
        ]);

        $branchCode = $this->normalizeBranchCode((string) $validated['code']);
        if (Branch::query()->where('code', $branchCode)->exists()) {
            return response()->json([
                'message' => 'Kode cabang sudah digunakan. Gunakan kode lain.',
            ], 422);
        }

        $branch = Branch::query()->create([
            'name' => trim((string) $validated['name']),
            'code' => $branchCode,
            'address' => trim((string) ($validated['address'] ?? '')) ?: null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return response()->json([
            'message' => 'Cabang berhasil ditambahkan.',
            'branch' => $branch->only(['id', 'name', 'code', 'address', 'is_active']),
        ]);
    }

    public function branchesUpdate(Request $request, Branch $branch): JsonResponse
    {
        $this->ensureOwnerOnly($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('branches', 'code')->ignore($branch->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama cabang wajib diisi.',
            'name.max' => 'Nama cabang maksimal 120 karakter.',
            'code.required' => 'Kode cabang wajib diisi.',
            'code.max' => 'Kode cabang maksimal 50 karakter.',
            'code.alpha_dash' => 'Kode cabang hanya boleh berisi huruf, angka, tanda minus (-), dan underscore (_).',
            'code.unique' => 'Kode cabang sudah digunakan. Gunakan kode lain.',
            'address.max' => 'Alamat cabang maksimal 255 karakter.',
        ]);

        $branchCode = $this->normalizeBranchCode((string) $validated['code']);
        if (Branch::query()->where('code', $branchCode)->whereKeyNot($branch->id)->exists()) {
            return response()->json([
                'message' => 'Kode cabang sudah digunakan. Gunakan kode lain.',
            ], 422);
        }

        $branch->update([
            'name' => trim((string) $validated['name']),
            'code' => $branchCode,
            'address' => trim((string) ($validated['address'] ?? '')) ?: null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return response()->json([
            'message' => 'Cabang berhasil diperbarui.',
            'branch' => $branch->only(['id', 'name', 'code', 'address', 'is_active']),
        ]);
    }

    public function branchesDestroy(Request $request, Branch $branch): JsonResponse
    {
        $this->ensureOwnerOnly($request);

        if (User::query()->where('branch_id', $branch->id)->exists()) {
            return response()->json([
                'message' => 'Cabang tidak bisa dihapus karena masih dipakai user.',
            ], 422);
        }

        $branch->delete();

        return response()->json([
            'message' => 'Cabang berhasil dihapus.',
        ]);
    }

    private function ensureOwnerOnly(Request $request): void
    {
        abort_unless($request->user()?->hasAnyRole(['owner']), 403);
    }

    private function resolveAssignedBranchId(Request $request, mixed $candidate): ?int
    {
        $resolved = $candidate !== null ? (int) $candidate : null;
        if ($request->user()?->hasAnyRole(['owner'])) {
            return $resolved;
        }

        $actorBranchId = $request->user()?->branch_id ? (int) $request->user()->branch_id : null;
        if ($resolved !== null && $actorBranchId !== null && $resolved !== $actorBranchId) {
            abort(422, 'Anda hanya bisa menetapkan cabang Anda sendiri.');
        }

        return $actorBranchId;
    }

    private function normalizeBranchCode(string $raw): string
    {
        $value = strtoupper(trim($raw));
        $value = preg_replace('/\s+/', '_', $value) ?: '';

        return $value;
    }
}
