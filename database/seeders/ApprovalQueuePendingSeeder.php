<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\ApprovalRequest;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ApprovalQueuePendingSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('role', UserRole::Owner->value)->orderBy('id')->first();
        $admin = User::query()->where('role', UserRole::Admin->value)->orderBy('id')->first();
        $cashier = User::query()->where('role', UserRole::Cashier->value)->orderBy('id')->first();

        if (! $owner || ! $admin || ! $cashier) {
            return;
        }

        $salePaid = Sale::query()->where('status', 'paid')->orderByDesc('id')->first();
        $salePending = Sale::query()->where('status', 'pending')->orderByDesc('id')->first();

        $rows = [
            [
                'type' => 'sale.quick_refund',
                'title' => 'Approval Refund UJI-INV-20260503-1001',
                'reason' => 'Customer batal, minta refund penuh.',
                'requested_by' => $cashier->id,
                'assigned_to' => $admin->id,
                'created_at' => now()->subHours(4),
                'snoozed_until' => now()->subMinutes(10),
                'snooze_note' => 'Menunggu verifikasi nota',
                'payload' => [
                    'sale_id' => (int) ($salePaid?->id ?? 0),
                    'invoice' => (string) ($salePaid?->invoice_number ?? 'UJI-INV-20260503-1001'),
                    'reason' => 'Customer batal, minta refund penuh.',
                    'total' => (float) ($salePaid?->total_amount ?? 180000),
                    'fingerprint' => sha1('approval-pending-refund-1001'),
                ],
            ],
            [
                'type' => 'sale.quick_void',
                'title' => 'Approval Void UJI-INV-20260503-1002',
                'reason' => 'Pembayaran gagal berulang, minta pembatalan.',
                'requested_by' => $admin->id,
                'assigned_to' => $owner->id,
                'created_at' => now()->subMinutes(85),
                'snoozed_until' => null,
                'snooze_note' => null,
                'payload' => [
                    'sale_id' => (int) ($salePending?->id ?? 0),
                    'invoice' => (string) ($salePending?->invoice_number ?? 'UJI-INV-20260503-1002'),
                    'reason' => 'Pembayaran gagal berulang, minta pembatalan.',
                    'fingerprint' => sha1('approval-pending-void-1002'),
                ],
            ],
            [
                'type' => 'report.export.excel',
                'title' => 'Approval Export EXCEL Besar',
                'reason' => 'Permintaan audit mingguan owner.',
                'requested_by' => $owner->id,
                'assigned_to' => null,
                'created_at' => now()->subMinutes(35),
                'snoozed_until' => now()->addMinutes(20),
                'snooze_note' => 'Tunggu finalisasi data kasir',
                'payload' => [
                    'format' => 'excel',
                    'start_date' => Carbon::today()->subDays(7)->toDateString(),
                    'end_date' => Carbon::today()->toDateString(),
                    'customer_id' => 0,
                    'payment_method' => 'all',
                    'qris_reference' => '',
                    'selected_ids' => [],
                    'selected_count' => 486,
                    'selected_total' => 223450000,
                    'fingerprint' => sha1('approval-pending-export-excel-1003'),
                ],
            ],
        ];

        foreach ($rows as $row) {
            ApprovalRequest::query()->updateOrCreate(
                ['type' => $row['type'], 'title' => $row['title']],
                [
                    'status' => 'pending',
                    'requested_by' => $row['requested_by'],
                    'assigned_to' => $row['assigned_to'],
                    'reviewed_by' => null,
                    'reason' => $row['reason'],
                    'payload' => $row['payload'],
                    'review_note' => null,
                    'reviewed_at' => null,
                    'snoozed_until' => $row['snoozed_until'],
                    'snooze_note' => $row['snooze_note'],
                    'created_at' => $row['created_at'],
                    'updated_at' => now(),
                ]
            );
        }
    }
}

