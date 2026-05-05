<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use App\Models\CashierAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StoreSettingController extends Controller
{
    public function edit()
    {
        $this->ensureOwnerOrAdmin();

        $setting = StoreSetting::query()->firstOrCreate(
            ['id' => 1],
            ['name' => config('app.name', 'BINTANG')]
        );

        return view('admin.store-settings', [
            'setting' => $setting,
            'promoLines' => $this->promoRulesToLines((array) ($setting->promo_buy_x_get_y_rules ?? [])),
            'quickPayPresetsText' => implode(',', (array) ($setting->quick_pay_presets ?? StoreSetting::DEFAULT_QUICK_PAY_PRESETS)),
            'promoRows' => $this->buildPromoRowsForView((array) ($setting->promo_buy_x_get_y_rules ?? [])),
            'backupStatus' => $this->buildBackupStatus(),
        ]);
    }

    public function update(Request $request)
    {
        $this->ensureOwnerOrAdmin();

        $setting = StoreSetting::query()->firstOrCreate(
            ['id' => 1],
            ['name' => config('app.name', 'BINTANG')]
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'receipt_footer' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'quick_pay_presets' => ['nullable', 'string', 'max:255'],
            'pending_non_cash_timeout_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'stock_opname_outlier_threshold' => ['required', 'integer', 'min:1', 'max:1000000'],
            'expense_daily_budget' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'expense_monthly_budget' => ['required', 'numeric', 'min:0', 'max:10000000000'],
            'expense_large_threshold' => ['required', 'numeric', 'min:1', 'max:1000000000'],
            'payment_overpay_qris' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'payment_overpay_debit' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'payment_overpay_transfer' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'payment_overpay_e_wallet' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'payment_overpay_cash' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'approval_export_min_rows' => ['required', 'integer', 'min:1', 'max:1000000'],
            'approval_export_min_total' => ['required', 'numeric', 'min:1', 'max:1000000000000'],
            'approval_auto_expire_minutes' => ['required', 'integer', 'min:10', 'max:10080'],
            'approval_sla_minutes_sale' => ['required', 'integer', 'min:5', 'max:10080'],
            'approval_sla_minutes_export' => ['required', 'integer', 'min:5', 'max:10080'],
            'approval_overdue_alert_threshold' => ['required', 'integer', 'min:1', 'max:1000'],
            'approval_business_start' => ['required', 'date_format:H:i'],
            'approval_business_end' => ['required', 'date_format:H:i'],
            'approval_business_workdays' => ['nullable', 'string', 'max:40'],
            'approval_reject_reason_presets' => ['nullable', 'string', 'max:1000'],
            'stock_transfer_kpi_overdue_warning_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'stock_transfer_kpi_approve_sla_minutes' => ['required', 'integer', 'min:5', 'max:10080'],
            'stock_transfer_kpi_receive_sla_minutes' => ['required', 'integer', 'min:5', 'max:10080'],
            'stock_transfer_kpi_discrepancy_warning_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'supplier_three_way_tolerance_pct' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'reorder_lookback_days' => ['nullable', 'integer', 'min:7', 'max:180'],
            'reorder_lead_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'reorder_safety_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'approval_routing_supplier_purchase_threshold' => ['nullable', 'numeric', 'min:1', 'max:1000000000000'],
            'approval_routing_supplier_payment_threshold' => ['nullable', 'numeric', 'min:1', 'max:1000000000000'],
            'approval_routing_report_export_threshold' => ['nullable', 'numeric', 'min:1', 'max:1000000000000'],
            'promo_sku' => ['nullable', 'array'],
            'promo_sku.*' => ['nullable', 'string', 'max:100'],
            'promo_buy_qty' => ['nullable', 'array'],
            'promo_buy_qty.*' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'promo_get_qty' => ['nullable', 'array'],
            'promo_get_qty.*' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ]);
        $warning = null;

        $data = [
            'name' => trim((string) $validated['name']),
            'address' => trim((string) ($validated['address'] ?? '')),
            'whatsapp' => trim((string) ($validated['whatsapp'] ?? '')),
            'receipt_footer' => trim((string) ($validated['receipt_footer'] ?? '')),
            'quick_pay_presets' => $this->parseQuickPayPresets((string) ($validated['quick_pay_presets'] ?? '')),
            'pending_non_cash_timeout_minutes' => (int) $validated['pending_non_cash_timeout_minutes'],
            'stock_opname_outlier_threshold' => (int) $validated['stock_opname_outlier_threshold'],
            'expense_daily_budget' => (float) $validated['expense_daily_budget'],
            'expense_monthly_budget' => (float) $validated['expense_monthly_budget'],
            'expense_large_threshold' => (float) $validated['expense_large_threshold'],
            'payment_overpay_rules' => [
                'qris' => ['max_overpay' => (float) $validated['payment_overpay_qris']],
                'debit' => ['max_overpay' => (float) $validated['payment_overpay_debit']],
                'transfer' => ['max_overpay' => (float) $validated['payment_overpay_transfer']],
                'e_wallet' => ['max_overpay' => (float) $validated['payment_overpay_e_wallet']],
                'cash' => ['max_overpay' => (float) $validated['payment_overpay_cash']],
            ],
            'approval_rules' => [
                'export_min_rows' => (int) $validated['approval_export_min_rows'],
                'export_min_total' => (float) $validated['approval_export_min_total'],
                'auto_expire_minutes' => (int) $validated['approval_auto_expire_minutes'],
                'sla_minutes_sale' => (int) $validated['approval_sla_minutes_sale'],
                'sla_minutes_export' => (int) $validated['approval_sla_minutes_export'],
                'overdue_alert_threshold' => (int) $validated['approval_overdue_alert_threshold'],
                'business_hours' => [
                    'start' => (string) $validated['approval_business_start'],
                    'end' => (string) $validated['approval_business_end'],
                    'workdays' => collect(explode(',', (string) ($validated['approval_business_workdays'] ?? '1,2,3,4,5,6,7')))
                        ->map(fn ($x) => (int) trim($x))
                        ->filter(fn ($d) => $d >= 1 && $d <= 7)
                        ->unique()
                        ->values()
                        ->all(),
                ],
                'reject_reason_presets' => collect(explode("\n", (string) ($validated['approval_reject_reason_presets'] ?? '')))
                    ->map(fn ($x) => trim($x))
                    ->filter(fn ($x) => $x !== '')
                    ->values()
                    ->all(),
                'stock_transfer_kpi' => [
                    'overdue_warning_count' => (int) $validated['stock_transfer_kpi_overdue_warning_count'],
                    'approve_sla_minutes' => (int) $validated['stock_transfer_kpi_approve_sla_minutes'],
                    'receive_sla_minutes' => (int) $validated['stock_transfer_kpi_receive_sla_minutes'],
                    'discrepancy_warning_pct' => (float) $validated['stock_transfer_kpi_discrepancy_warning_pct'],
                ],
                'supplier_three_way_tolerance_pct' => (float) ($validated['supplier_three_way_tolerance_pct'] ?? 2),
                'reorder_lookback_days' => (int) ($validated['reorder_lookback_days'] ?? 30),
                'reorder_lead_days' => (int) ($validated['reorder_lead_days'] ?? 7),
                'reorder_safety_days' => (int) ($validated['reorder_safety_days'] ?? 3),
                'approval_routing' => [
                    'supplier_purchase_threshold' => (float) ($validated['approval_routing_supplier_purchase_threshold'] ?? 10000000),
                    'supplier_payment_threshold' => (float) ($validated['approval_routing_supplier_payment_threshold'] ?? 5000000),
                    'report_export_threshold' => (float) ($validated['approval_routing_report_export_threshold'] ?? 100000000),
                ],
            ],
            'promo_buy_x_get_y_rules' => $this->parsePromoRows(
                (array) ($validated['promo_sku'] ?? []),
                (array) ($validated['promo_buy_qty'] ?? []),
                (array) ($validated['promo_get_qty'] ?? [])
            ),
        ];
        $bhStart = (string) data_get($data, 'approval_rules.business_hours.start', '08:00');
        $bhEnd = (string) data_get($data, 'approval_rules.business_hours.end', '22:00');
        $bhWorkdays = (array) data_get($data, 'approval_rules.business_hours.workdays', []);
        if ($bhEnd <= $bhStart) {
            data_set($data, 'approval_rules.business_hours.start', '08:00');
            data_set($data, 'approval_rules.business_hours.end', '22:00');
            $warning = 'Jam kerja SLA tidak valid, dikembalikan ke default 08:00-22:00.';
        }
        if (count($bhWorkdays) === 0) {
            data_set($data, 'approval_rules.business_hours.workdays', [1, 2, 3, 4, 5, 6, 7]);
            $warning = trim(($warning ? $warning.' ' : '').'Hari kerja SLA kosong, dikembalikan ke 1-7.');
        }

        if ($request->hasFile('logo')) {
            if (! empty($setting->logo)) {
                Storage::disk('public')->delete((string) $setting->logo);
            }
            $data['logo'] = $request->file('logo')->store('store', 'public');
        }

        $setting->update(StoreSetting::withPaymentDefaults($data));

        $response = back()->with('status', 'Pengaturan toko berhasil disimpan.');
        if ($warning) {
            $response->with('warning', $warning);
        }
        return $response;
    }

    public function exportRuntimeConfig()
    {
        $this->ensureOwnerOrAdmin();
        $setting = StoreSetting::query()->firstOrCreate(['id' => 1], ['name' => config('app.name', 'BINTANG')]);
        $permissions = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->select('role_permissions.role', 'permissions.code')
            ->get()
            ->groupBy('role')
            ->map(fn ($rows) => $rows->pluck('code')->values()->all())
            ->all();

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'approval_rules' => (array) ($setting->approval_rules ?? []),
            'rbac' => $permissions,
        ];

        CashierAuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => 'runtime_config_exported',
            'context' => [
                'approval_rules_keys' => array_keys((array) ($setting->approval_rules ?? [])),
                'rbac_roles' => array_keys((array) $permissions),
            ],
            'ip_address' => (string) request()?->ip(),
            'user_agent' => (string) (request()?->userAgent() ?? ''),
        ]);

        return response()->json($payload, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="runtime-config-'.now()->format('Ymd_His').'.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function importRuntimeConfig(Request $request)
    {
        $this->ensureOwnerOrAdmin();
        $validated = $request->validate([
            'config_json' => ['required', 'string', 'max:200000'],
            'dry_run' => ['nullable', 'boolean'],
        ]);
        $decoded = json_decode((string) $validated['config_json'], true);
        if (! is_array($decoded)) {
            return back()->with('error', 'JSON konfigurasi tidak valid.');
        }
        $setting = StoreSetting::query()->firstOrCreate(['id' => 1], ['name' => config('app.name', 'BINTANG')]);
        $approvalRules = (array) data_get($decoded, 'approval_rules', []);
        $rbac = (array) data_get($decoded, 'rbac', []);
        $diff = $this->buildRuntimeConfigDiff($setting, $approvalRules, $rbac);
        $isDryRun = (bool) ($validated['dry_run'] ?? false);

        if ($isDryRun) {
            CashierAuditLog::query()->create([
                'user_id' => auth()->id(),
                'action' => 'runtime_config_import_dry_run',
                'context' => [
                    'diff' => $diff,
                ],
                'ip_address' => (string) request()?->ip(),
                'user_agent' => (string) (request()?->userAgent() ?? ''),
            ]);

            return back()
                ->with('status', 'Dry-run selesai. Konfigurasi belum diubah.')
                ->with('runtime_config_diff', $diff)
                ->withInput();
        }

        DB::transaction(function () use ($setting, $approvalRules, $rbac): void {
            $existing = $setting->toArray();
            $existing['approval_rules'] = $approvalRules;
            $setting->update(StoreSetting::withPaymentDefaults($existing));
            if ($rbac !== []) {
                foreach (['owner', 'admin', 'kasir'] as $role) {
                    $codes = collect((array) ($rbac[$role] ?? []))->map(fn ($x) => trim((string) $x))->filter()->values()->all();
                    $permissionIds = DB::table('permissions')->whereIn('code', $codes)->pluck('id')->all();
                    DB::table('role_permissions')->where('role', $role)->delete();
                    foreach ($permissionIds as $permissionId) {
                        DB::table('role_permissions')->insert([
                            'role' => $role,
                            'permission_id' => (int) $permissionId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });

        CashierAuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => 'runtime_config_imported',
            'context' => [
                'diff' => $diff,
            ],
            'ip_address' => (string) request()?->ip(),
            'user_agent' => (string) (request()?->userAgent() ?? ''),
        ]);

        return back()->with('status', 'Runtime config berhasil di-import.');
    }

    private function buildRuntimeConfigDiff(StoreSetting $setting, array $incomingApprovalRules, array $incomingRbac): array
    {
        $currentApproval = (array) ($setting->approval_rules ?? []);
        $normalizedIncomingApproval = (array) data_get(
            StoreSetting::withPaymentDefaults(['approval_rules' => $incomingApprovalRules]),
            'approval_rules',
            []
        );

        $approvalDiff = [];
        foreach (array_unique(array_merge(array_keys($currentApproval), array_keys($normalizedIncomingApproval))) as $key) {
            $before = data_get($currentApproval, $key);
            $after = data_get($normalizedIncomingApproval, $key);
            if ($before !== $after) {
                $approvalDiff[$key] = [
                    'before' => $before,
                    'after' => $after,
                ];
            }
        }

        $currentRbac = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->select('role_permissions.role', 'permissions.code')
            ->get()
            ->groupBy('role')
            ->map(fn ($rows) => $rows->pluck('code')->sort()->values()->all())
            ->all();

        $rbacDiff = [];
        foreach (['owner', 'admin', 'kasir'] as $role) {
            $before = collect((array) ($currentRbac[$role] ?? []))->filter()->values();
            $after = collect((array) ($incomingRbac[$role] ?? []))
                ->map(fn ($x) => trim((string) $x))
                ->filter()
                ->unique()
                ->sort()
                ->values();

            $added = $after->diff($before)->values()->all();
            $removed = $before->diff($after)->values()->all();
            if ($added !== [] || $removed !== []) {
                $rbacDiff[$role] = [
                    'added' => $added,
                    'removed' => $removed,
                    'before_count' => $before->count(),
                    'after_count' => $after->count(),
                ];
            }
        }

        return [
            'approval_rules_changed' => $approvalDiff,
            'rbac_changed' => $rbacDiff,
        ];
    }

    private function parseQuickPayPresets(string $raw): array
    {
        return collect(explode(',', $raw))
            ->map(fn ($x) => (int) trim($x))
            ->filter(fn ($x) => $x > 0)
            ->take(6)
            ->values()
            ->all();
    }

    private function parsePromoRules(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        return collect($lines)
            ->map(function (string $line) {
                $parts = array_map('trim', explode(',', $line));
                if (count($parts) < 3) {
                    return null;
                }
                $sku = strtoupper((string) ($parts[0] ?? ''));
                $buyQty = max(1, (int) ($parts[1] ?? 0));
                $getQty = max(1, (int) ($parts[2] ?? 0));
                if ($sku === '') {
                    return null;
                }

                return [
                    'sku' => $sku,
                    'buy_qty' => $buyQty,
                    'get_qty' => $getQty,
                    'active' => true,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function parsePromoRows(array $skus, array $buyQtys, array $getQtys): array
    {
        $max = max(count($skus), count($buyQtys), count($getQtys));
        $rows = [];

        for ($i = 0; $i < $max; $i++) {
            $sku = strtoupper(trim((string) ($skus[$i] ?? '')));
            $buy = (int) ($buyQtys[$i] ?? 0);
            $get = (int) ($getQtys[$i] ?? 0);
            if ($sku === '' || $buy <= 0 || $get <= 0) {
                continue;
            }
            $rows[] = [
                'sku' => $sku,
                'buy_qty' => $buy,
                'get_qty' => $get,
                'active' => true,
            ];
        }

        return $rows;
    }

    private function promoRulesToLines(array $rules): string
    {
        return collect($rules)
            ->map(fn ($row) => strtoupper(trim((string) ($row['sku'] ?? ''))).','.max(1, (int) ($row['buy_qty'] ?? 1)).','.max(1, (int) ($row['get_qty'] ?? 1)))
            ->implode("\n");
    }

    private function buildPromoRowsForView(array $rules): array
    {
        $rows = collect($rules)
            ->map(fn ($r) => [
                'sku' => (string) ($r['sku'] ?? ''),
                'buy_qty' => (string) ($r['buy_qty'] ?? '1'),
                'get_qty' => (string) ($r['get_qty'] ?? '1'),
            ])
            ->values()
            ->all();

        if (count($rows) === 0) {
            $rows[] = ['sku' => '', 'buy_qty' => '1', 'get_qty' => '1'];
        }

        return $rows;
    }

    private function ensureOwnerOrAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()?->hasAnyRole(['owner', 'admin']), 403);
    }

    private function buildBackupStatus(): array
    {
        $backupDir = storage_path('app/backups');
        $files = is_dir($backupDir) ? collect(scandir($backupDir) ?: [])
            ->filter(fn ($f) => ! in_array($f, ['.', '..'], true))
            ->map(fn ($f) => $backupDir.DIRECTORY_SEPARATOR.$f)
            ->filter(fn ($p) => is_file($p))
            ->values() : collect();

        $lastBackupPath = $files
            ->sortByDesc(fn ($p) => filemtime($p))
            ->first();
        $lastBackup = null;
        if (is_string($lastBackupPath) && is_file($lastBackupPath)) {
            $lastBackup = [
                'name' => basename($lastBackupPath),
                'size' => (int) filesize($lastBackupPath),
                'updated_at' => date('Y-m-d H:i:s', (int) filemtime($lastBackupPath)),
            ];
        }

        $lastRestore = CashierAuditLog::query()
            ->whereIn('action', ['database_restore_executed', 'database_restore_dry_run'])
            ->latest('id')
            ->first(['action', 'created_at', 'context']);

        return [
            'last_backup' => $lastBackup,
            'backups_count' => (int) $files->count(),
            'last_restore' => $lastRestore,
        ];
    }
}
