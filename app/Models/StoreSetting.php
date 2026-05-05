<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    use HasFactory;

    public const DEFAULT_QUICK_PAY_PRESETS = [10000, 20000, 50000, 100000];

    public const DEFAULT_ADMIN_FEE_RULES = [
        'cash' => ['type' => 'fixed', 'value' => 0],
        'qris' => ['type' => 'percent', 'value' => 0],
        'debit' => ['type' => 'percent', 'value' => 0],
        'transfer' => ['type' => 'percent', 'value' => 0],
        'e_wallet' => ['type' => 'percent', 'value' => 0],
    ];

    public const DEFAULT_OVERPAY_RULES = [
        'cash' => ['max_overpay' => 500000],
        'qris' => ['max_overpay' => 0],
        'debit' => ['max_overpay' => 0],
        'transfer' => ['max_overpay' => 0],
        'e_wallet' => ['max_overpay' => 0],
    ];

    public const DEFAULT_BUY_X_GET_Y_RULES = [];
    public const DEFAULT_APPROVAL_RULES = [
        'export_min_rows' => 300,
        'export_min_total' => 100000000,
        'auto_expire_minutes' => 1440,
        'sla_minutes_sale' => 120,
        'sla_minutes_export' => 360,
        'overdue_alert_threshold' => 5,
        'business_hours' => [
            'start' => '08:00',
            'end' => '22:00',
            'workdays' => [1, 2, 3, 4, 5, 6, 7],
        ],
        'reject_reason_presets' => [
            'Data tidak valid',
            'Butuh dokumen pendukung',
            'Melebihi kebijakan toko',
        ],
        'stock_transfer_kpi' => [
            'overdue_warning_count' => 3,
            'approve_sla_minutes' => 60,
            'receive_sla_minutes' => 180,
            'discrepancy_warning_pct' => 5,
        ],
        'supplier_three_way_tolerance_pct' => 2,
        'supplier_three_way_enforced' => false,
        'reorder_lookback_days' => 30,
        'reorder_lead_days' => 7,
        'reorder_safety_days' => 3,
        'approval_routing' => [
            'supplier_purchase_threshold' => 10000000,
            'supplier_payment_threshold' => 5000000,
            'report_export_threshold' => 100000000,
        ],
    ];

    protected $fillable = [
        'name',
        'address',
        'whatsapp',
        'logo',
        'receipt_footer',
        'cash_rounding_enabled',
        'cash_rounding_step',
        'quick_pay_presets',
        'payment_admin_fee_rules',
        'payment_overpay_rules',
        'approval_rules',
        'pending_non_cash_timeout_minutes',
        'promo_buy_x_get_y_rules',
        'telegram_enabled',
        'telegram_notify_checkout_anomaly',
        'telegram_checkout_fail_threshold',
        'telegram_approval_sla_minutes',
        'telegram_daily_summary_enabled',
        'telegram_daily_summary_time',
        'telegram_override_chat_id',
        'telegram_daily_summary_sent_at',
        'stock_opname_require_manager_approval',
        'stock_opname_outlier_threshold',
        'expense_daily_budget',
        'expense_monthly_budget',
        'expense_large_threshold',
    ];

    protected function casts(): array
    {
        return [
            'telegram_enabled' => 'boolean',
            'cash_rounding_enabled' => 'boolean',
            'cash_rounding_step' => 'integer',
            'quick_pay_presets' => 'array',
            'payment_admin_fee_rules' => 'array',
            'payment_overpay_rules' => 'array',
            'approval_rules' => 'array',
            'pending_non_cash_timeout_minutes' => 'integer',
            'promo_buy_x_get_y_rules' => 'array',
            'telegram_notify_checkout_anomaly' => 'boolean',
            'telegram_approval_sla_minutes' => 'integer',
            'telegram_daily_summary_enabled' => 'boolean',
            'telegram_daily_summary_sent_at' => 'datetime',
            'stock_opname_require_manager_approval' => 'boolean',
            'stock_opname_outlier_threshold' => 'integer',
            'expense_daily_budget' => 'decimal:2',
            'expense_monthly_budget' => 'decimal:2',
            'expense_large_threshold' => 'decimal:2',
        ];
    }

    public static function withPaymentDefaults(array $data): array
    {
        $data['cash_rounding_enabled'] = (bool) ($data['cash_rounding_enabled'] ?? false);
        $data['cash_rounding_step'] = max(1, (int) ($data['cash_rounding_step'] ?? 100));
        $data['pending_non_cash_timeout_minutes'] = max(1, (int) ($data['pending_non_cash_timeout_minutes'] ?? 30));
        $data['stock_opname_outlier_threshold'] = max(1, (int) ($data['stock_opname_outlier_threshold'] ?? 10));
        $data['expense_daily_budget'] = max(0, (float) ($data['expense_daily_budget'] ?? 1000000));
        $data['expense_monthly_budget'] = max(0, (float) ($data['expense_monthly_budget'] ?? 30000000));
        $data['expense_large_threshold'] = max(1, (float) ($data['expense_large_threshold'] ?? 1000000));

        $presets = $data['quick_pay_presets'] ?? self::DEFAULT_QUICK_PAY_PRESETS;
        if (! is_array($presets)) {
            $presets = self::DEFAULT_QUICK_PAY_PRESETS;
        }
        $data['quick_pay_presets'] = collect($presets)
            ->map(fn ($x) => (int) $x)
            ->filter(fn ($x) => $x > 0)
            ->unique()
            ->take(6)
            ->values()
            ->all();
        if (count($data['quick_pay_presets']) === 0) {
            $data['quick_pay_presets'] = self::DEFAULT_QUICK_PAY_PRESETS;
        }

        $adminRules = $data['payment_admin_fee_rules'] ?? self::DEFAULT_ADMIN_FEE_RULES;
        if (! is_array($adminRules)) {
            $adminRules = self::DEFAULT_ADMIN_FEE_RULES;
        }

        $normalizedAdmin = self::DEFAULT_ADMIN_FEE_RULES;
        foreach ($normalizedAdmin as $method => $rule) {
            $type = (string) data_get($adminRules, $method.'.type', $rule['type']);
            $value = max(0, (float) data_get($adminRules, $method.'.value', $rule['value']));
            if ($type !== 'fixed') {
                $type = 'percent';
                $value = min($value, 100);
            }
            $normalizedAdmin[$method] = [
                'type' => $type,
                'value' => $value,
            ];
        }
        $data['payment_admin_fee_rules'] = $normalizedAdmin;

        $overpayRules = $data['payment_overpay_rules'] ?? self::DEFAULT_OVERPAY_RULES;
        if (! is_array($overpayRules)) {
            $overpayRules = self::DEFAULT_OVERPAY_RULES;
        }

        $normalizedOverpay = self::DEFAULT_OVERPAY_RULES;
        foreach ($normalizedOverpay as $method => $rule) {
            $maxOverpay = max(0, (float) data_get($overpayRules, $method.'.max_overpay', $rule['max_overpay']));
            $normalizedOverpay[$method] = [
                'max_overpay' => min($maxOverpay, 1000000000),
            ];
        }
        $data['payment_overpay_rules'] = $normalizedOverpay;

        $approvalRules = $data['approval_rules'] ?? self::DEFAULT_APPROVAL_RULES;
        if (! is_array($approvalRules)) {
            $approvalRules = self::DEFAULT_APPROVAL_RULES;
        }
        $data['approval_rules'] = [
            'export_min_rows' => max(1, (int) data_get($approvalRules, 'export_min_rows', self::DEFAULT_APPROVAL_RULES['export_min_rows'])),
            'export_min_total' => max(1, (float) data_get($approvalRules, 'export_min_total', self::DEFAULT_APPROVAL_RULES['export_min_total'])),
            'auto_expire_minutes' => max(10, (int) data_get($approvalRules, 'auto_expire_minutes', self::DEFAULT_APPROVAL_RULES['auto_expire_minutes'])),
            'sla_minutes_sale' => max(5, (int) data_get($approvalRules, 'sla_minutes_sale', self::DEFAULT_APPROVAL_RULES['sla_minutes_sale'])),
            'sla_minutes_export' => max(5, (int) data_get($approvalRules, 'sla_minutes_export', self::DEFAULT_APPROVAL_RULES['sla_minutes_export'])),
            'overdue_alert_threshold' => max(1, (int) data_get($approvalRules, 'overdue_alert_threshold', self::DEFAULT_APPROVAL_RULES['overdue_alert_threshold'])),
            'business_hours' => [
                'start' => preg_match('/^\d{2}:\d{2}$/', (string) data_get($approvalRules, 'business_hours.start', self::DEFAULT_APPROVAL_RULES['business_hours']['start']))
                    ? (string) data_get($approvalRules, 'business_hours.start', self::DEFAULT_APPROVAL_RULES['business_hours']['start'])
                    : self::DEFAULT_APPROVAL_RULES['business_hours']['start'],
                'end' => preg_match('/^\d{2}:\d{2}$/', (string) data_get($approvalRules, 'business_hours.end', self::DEFAULT_APPROVAL_RULES['business_hours']['end']))
                    ? (string) data_get($approvalRules, 'business_hours.end', self::DEFAULT_APPROVAL_RULES['business_hours']['end'])
                    : self::DEFAULT_APPROVAL_RULES['business_hours']['end'],
                'workdays' => collect((array) data_get($approvalRules, 'business_hours.workdays', self::DEFAULT_APPROVAL_RULES['business_hours']['workdays']))
                    ->map(fn ($d) => (int) $d)
                    ->filter(fn ($d) => $d >= 1 && $d <= 7)
                    ->unique()
                    ->values()
                    ->all(),
            ],
            'reject_reason_presets' => collect((array) data_get($approvalRules, 'reject_reason_presets', self::DEFAULT_APPROVAL_RULES['reject_reason_presets']))
                ->map(fn ($x) => trim((string) $x))
                ->filter(fn ($x) => $x !== '')
                ->take(10)
                ->values()
                ->all(),
            'stock_transfer_kpi' => [
                'overdue_warning_count' => max(1, (int) data_get($approvalRules, 'stock_transfer_kpi.overdue_warning_count', 3)),
                'approve_sla_minutes' => max(5, (int) data_get($approvalRules, 'stock_transfer_kpi.approve_sla_minutes', 60)),
                'receive_sla_minutes' => max(5, (int) data_get($approvalRules, 'stock_transfer_kpi.receive_sla_minutes', 180)),
                'discrepancy_warning_pct' => max(0, min(100, (float) data_get($approvalRules, 'stock_transfer_kpi.discrepancy_warning_pct', 5))),
            ],
            'supplier_three_way_tolerance_pct' => max(0, min(20, (float) data_get($approvalRules, 'supplier_three_way_tolerance_pct', 2))),
            'supplier_three_way_enforced' => (bool) data_get($approvalRules, 'supplier_three_way_enforced', false),
            'reorder_lookback_days' => max(7, min(180, (int) data_get($approvalRules, 'reorder_lookback_days', 30))),
            'reorder_lead_days' => max(1, min(90, (int) data_get($approvalRules, 'reorder_lead_days', 7))),
            'reorder_safety_days' => max(0, min(90, (int) data_get($approvalRules, 'reorder_safety_days', 3))),
            'approval_routing' => [
                'supplier_purchase_threshold' => max(1, (float) data_get($approvalRules, 'approval_routing.supplier_purchase_threshold', 10000000)),
                'supplier_payment_threshold' => max(1, (float) data_get($approvalRules, 'approval_routing.supplier_payment_threshold', 5000000)),
                'report_export_threshold' => max(1, (float) data_get($approvalRules, 'approval_routing.report_export_threshold', 100000000)),
            ],
        ];

        $promoRules = $data['promo_buy_x_get_y_rules'] ?? self::DEFAULT_BUY_X_GET_Y_RULES;
        if (! is_array($promoRules)) {
            $promoRules = self::DEFAULT_BUY_X_GET_Y_RULES;
        }
        $data['promo_buy_x_get_y_rules'] = collect($promoRules)
            ->map(function ($row) {
                return [
                    'sku' => strtoupper(trim((string) data_get($row, 'sku', ''))),
                    'buy_qty' => max(1, (int) data_get($row, 'buy_qty', 0)),
                    'get_qty' => max(1, (int) data_get($row, 'get_qty', 0)),
                    'active' => (bool) data_get($row, 'active', true),
                ];
            })
            ->filter(fn ($row) => $row['sku'] !== '')
            ->values()
            ->take(100)
            ->all();

        return $data;
    }
}
