<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\CashierAuditLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AuditLogDemoSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('role', UserRole::Owner->value)->orderBy('id')->first();
        $admin = User::query()->where('role', UserRole::Admin->value)->orderBy('id')->first();
        $cashier = User::query()->where('role', UserRole::Cashier->value)->orderBy('id')->first();

        $actorA = $cashier ?? $admin ?? $owner;
        $actorB = $admin ?? $owner ?? $cashier;

        if (! $actorA || ! $actorB) {
            return;
        }

        $base = Carbon::now()->subDays(10)->startOfDay();

        $rows = [
            [
                'action' => 'hold_saved',
                'user_id' => $actorA->id,
                'context' => ['hold_id' => 'HLD-DEMO-001', 'total_qty' => 4, 'seed_demo' => 'audit_log_demo_v1'],
                'created_at' => $base->copy()->addHours(8),
            ],
            [
                'action' => 'hold_loaded',
                'user_id' => $actorA->id,
                'context' => ['hold_id' => 'HLD-DEMO-001', 'total_qty' => 4, 'seed_demo' => 'audit_log_demo_v1'],
                'created_at' => $base->copy()->addHours(9),
            ],
            [
                'action' => 'hold_deleted',
                'user_id' => $actorA->id,
                'context' => ['hold_id' => 'HLD-DEMO-001', 'seed_demo' => 'audit_log_demo_v1'],
                'created_at' => $base->copy()->addHours(10),
            ],
            [
                'action' => 'checkout_failed_client',
                'user_id' => $actorA->id,
                'context' => ['reason' => 'split_invalid', 'seed_demo' => 'audit_log_demo_v1'],
                'created_at' => $base->copy()->addDay()->addHours(11),
            ],
            [
                'action' => 'stock_sync_adjusted',
                'user_id' => $actorB->id,
                'context' => ['invoice' => 'INV-DEMO-1001', 'adjusted_items' => 2, 'seed_demo' => 'audit_log_demo_v1'],
                'created_at' => $base->copy()->addDay()->addHours(13),
            ],
            [
                'action' => 'customer_merged',
                'user_id' => $actorB->id,
                'context' => [
                    'source_customer_name' => 'Demo Lama',
                    'target_customer_name' => 'Demo Baru',
                    'moved_sales_count' => 7,
                    'seed_demo' => 'audit_log_demo_v1',
                ],
                'created_at' => $base->copy()->addDays(2)->addHours(9),
            ],
            [
                'action' => 'expense_created',
                'user_id' => $actorB->id,
                'context' => ['expense_id' => 9101, 'title' => 'Biaya Plastik', 'amount' => 45000, 'seed_demo' => 'audit_log_demo_v1'],
                'created_at' => $base->copy()->addDays(3)->addHours(8),
            ],
            [
                'action' => 'expense_updated',
                'user_id' => $actorB->id,
                'context' => ['expense_id' => 9101, 'title' => 'Biaya Plastik (update)', 'amount' => 50000, 'seed_demo' => 'audit_log_demo_v1'],
                'created_at' => $base->copy()->addDays(3)->addHours(10),
            ],
            [
                'action' => 'expense_deleted',
                'user_id' => $actorB->id,
                'context' => ['expense_id' => 9102, 'title' => 'Biaya Uji Hapus', 'delete_reason' => 'Data duplikat', 'seed_demo' => 'audit_log_demo_v1'],
                'created_at' => $base->copy()->addDays(4)->addHours(9),
            ],
            [
                'action' => 'expense_duplicated',
                'user_id' => $actorB->id,
                'context' => ['source_expense_id' => 9103, 'new_expense_id' => 9104, 'seed_demo' => 'audit_log_demo_v1'],
                'created_at' => $base->copy()->addDays(4)->addHours(12),
            ],
            [
                'action' => 'report_export_excel',
                'user_id' => $actorA->id,
                'context' => [
                    'selected_count' => 25,
                    'selected_total' => 3500000,
                    'start_date' => $base->copy()->subDays(7)->toDateString(),
                    'end_date' => $base->copy()->toDateString(),
                    'seed_demo' => 'audit_log_demo_v1',
                ],
                'created_at' => $base->copy()->addDays(5)->addHours(10),
            ],
            [
                'action' => 'report_export_pdf',
                'user_id' => $actorB->id,
                'context' => [
                    'selected_count' => 15,
                    'selected_total' => 1750000,
                    'start_date' => $base->copy()->subDays(7)->toDateString(),
                    'end_date' => $base->copy()->toDateString(),
                    'seed_demo' => 'audit_log_demo_v1',
                ],
                'created_at' => $base->copy()->addDays(5)->addHours(11),
            ],
            [
                'action' => 'report_export_excel',
                'user_id' => $actorA->id,
                'context' => [
                    'selected_count' => 260,
                    'selected_total' => 125000000,
                    'start_date' => $base->copy()->subDays(30)->toDateString(),
                    'end_date' => $base->copy()->toDateString(),
                    'seed_demo' => 'audit_log_demo_v1',
                ],
                'created_at' => $base->copy()->addDays(6)->addHours(10),
            ],
            [
                'action' => 'report_export_pdf',
                'user_id' => $actorB->id,
                'context' => [
                    'selected_count' => 210,
                    'selected_total' => 76000000,
                    'start_date' => $base->copy()->subDays(45)->toDateString(),
                    'end_date' => $base->copy()->toDateString(),
                    'seed_demo' => 'audit_log_demo_v1',
                ],
                'created_at' => $base->copy()->addDays(7)->addHours(14),
            ],
        ];

        foreach ($rows as $row) {
            CashierAuditLog::query()->firstOrCreate(
                [
                    'action' => $row['action'],
                    'user_id' => $row['user_id'],
                    'context->seed_demo' => 'audit_log_demo_v1',
                    'context->selected_count' => $row['context']['selected_count'] ?? null,
                    'context->expense_id' => $row['context']['expense_id'] ?? null,
                    'created_at' => $row['created_at'],
                ],
                [
                    'context' => $row['context'],
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'AuditLogDemoSeeder',
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['created_at'],
                ]
            );
        }
    }
}

