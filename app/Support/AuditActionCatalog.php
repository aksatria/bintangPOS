<?php

namespace App\Support;

class AuditActionCatalog
{
    /**
     * @return array<string, array{severity:string, required_keys:string[]}>
     */
    public static function definitions(): array
    {
        return [
            'checkout_success' => ['severity' => 'info', 'required_keys' => ['sale_id', 'invoice', 'status', 'total_amount']],
            'checkout_failed_client' => ['severity' => 'warning', 'required_keys' => []],
            'pending_settled_pos' => ['severity' => 'info', 'required_keys' => ['sale_id', 'invoice', 'payment_method', 'paid_amount']],
            'sale_refunded' => ['severity' => 'warning', 'required_keys' => ['sale_id', 'invoice', 'reason', 'approved_by']],
            'sale_voided' => ['severity' => 'warning', 'required_keys' => ['sale_id', 'invoice', 'reason', 'approved_by']],
            'sale_partial_refunded' => ['severity' => 'warning', 'required_keys' => ['sale_id', 'invoice', 'reason', 'refund_total', 'approved_by']],
            'sale_receipt_reprinted' => ['severity' => 'info', 'required_keys' => ['sale_id', 'invoice', 'reason', 'mode']],
            'report_export_excel' => ['severity' => 'info', 'required_keys' => ['start_date', 'end_date', 'selected_count', 'selected_total']],
            'report_export_pdf' => ['severity' => 'info', 'required_keys' => ['start_date', 'end_date', 'selected_count', 'selected_total']],
            'customer_merged' => ['severity' => 'warning', 'required_keys' => ['source_customer_id', 'target_customer_id']],
            'stock_opname_posted' => ['severity' => 'warning', 'required_keys' => ['stock_opname_id']],
            'stock_transfer_requested' => ['severity' => 'warning', 'required_keys' => ['stock_transfer_id', 'source_branch_id', 'destination_branch_id']],
            'stock_transfer_approved' => ['severity' => 'info', 'required_keys' => ['stock_transfer_id']],
            'stock_transfer_rejected' => ['severity' => 'warning', 'required_keys' => ['stock_transfer_id']],
            'stock_transfer_received' => ['severity' => 'warning', 'required_keys' => ['stock_transfer_id']],
            'stock_transfer_cancelled' => ['severity' => 'info', 'required_keys' => ['stock_transfer_id']],
        ];
    }

    /**
     * @return string[]
     */
    public static function requiredKeys(string $action): array
    {
        return self::definitions()[$action]['required_keys'] ?? [];
    }
}
