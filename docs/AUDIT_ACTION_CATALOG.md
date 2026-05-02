# Audit Action Catalog

Standar action audit dan key context wajib.

## Format Wajib

- Semua record audit harus memiliki `context._meta.schema = cashier_audit_log.v1`.
- Semua record audit harus memiliki `context._meta.recorded_at` (ISO-8601).

## Action Inti

1. `checkout_success`
- Required keys: `sale_id`, `invoice`, `status`, `total_amount`

2. `pending_settled_pos`
- Required keys: `sale_id`, `invoice`, `payment_method`, `paid_amount`

3. `sale_refunded`
- Required keys: `sale_id`, `invoice`, `reason`, `approved_by`

4. `sale_voided`
- Required keys: `sale_id`, `invoice`, `reason`, `approved_by`

5. `sale_partial_refunded`
- Required keys: `sale_id`, `invoice`, `reason`, `refund_total`, `approved_by`

6. `sale_receipt_reprinted`
- Required keys: `sale_id`, `invoice`, `reason`, `mode`

7. `report_export_excel` / `report_export_pdf`
- Required keys: `start_date`, `end_date`, `selected_count`, `selected_total`

## Command Terkait

- Backfill metadata lama:
  - `php artisan audit:backfill-meta`
  - dry-run: `php artisan audit:backfill-meta --dry-run`

- Monitor operasional:
  - `php artisan ops:monitor-snapshot --days=7`

- Uji beban ringan report:
  - `php artisan reports:stress-check --days=30 --loops=5`
