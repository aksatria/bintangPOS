<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\ApprovalRequest;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ApprovalQueueResolvedSeeder extends Seeder
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

        $approvedExportPayload = [
            'format' => 'pdf',
            'start_date' => Carbon::today()->subDays(14)->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'customer_id' => 0,
            'payment_method' => 'all',
            'qris_reference' => '',
            'selected_ids' => [],
            'selected_count' => 398,
            'selected_total' => 178340000,
            'fingerprint' => sha1('approval-resolved-export-pdf-2001'),
            'export_executed_at' => now()->subHours(6)->toIso8601String(),
            'export_executed_by' => $owner->id,
            'export_executed_by_name' => $owner->name,
        ];

        $rows = [
            [
                'type' => 'report.export.pdf',
                'title' => 'Approval Export PDF Besar',
                'status' => 'approved',
                'requested_by' => $admin->id,
                'assigned_to' => $owner->id,
                'reviewed_by' => $owner->id,
                'reason' => 'Laporan bulanan untuk direksi.',
                'review_note' => 'Disetujui, jalankan export.',
                'created_at' => now()->subDay(),
                'reviewed_at' => now()->subHours(7),
                'payload' => $approvedExportPayload,
            ],
            [
                'type' => 'sale.quick_refund',
                'title' => 'Approval Refund UJI-INV-20260503-2002',
                'status' => 'rejected',
                'requested_by' => $cashier->id,
                'assigned_to' => $admin->id,
                'reviewed_by' => $admin->id,
                'reason' => 'Permintaan refund tanpa bukti transaksi.',
                'review_note' => 'Data tidak valid',
                'created_at' => now()->subHours(10),
                'reviewed_at' => now()->subHours(9),
                'payload' => [
                    'sale_id' => (int) ($salePaid?->id ?? 0),
                    'invoice' => (string) ($salePaid?->invoice_number ?? 'UJI-INV-20260503-2002'),
                    'reason' => 'Permintaan refund tanpa bukti transaksi.',
                    'total' => (float) ($salePaid?->total_amount ?? 99000),
                    'fingerprint' => sha1('approval-resolved-refund-2002'),
                ],
            ],
            [
                'type' => 'sale.quick_void',
                'title' => 'Approval Void UJI-INV-20260503-2003',
                'status' => 'rejected',
                'requested_by' => $cashier->id,
                'assigned_to' => $owner->id,
                'reviewed_by' => null,
                'reason' => 'Permintaan melewati SLA.',
                'review_note' => '[AUTO-EXPIRED] Pending melebihi 1440 menit.',
                'created_at' => now()->subDays(2),
                'reviewed_at' => now()->subDay(),
                'payload' => [
                    'sale_id' => 0,
                    'invoice' => 'UJI-INV-20260503-2003',
                    'reason' => 'Permintaan melewati SLA.',
                    'fingerprint' => sha1('approval-resolved-void-2003'),
                    'auto_expired_at' => now()->subDay()->toIso8601String(),
                    'auto_expire_threshold_minutes' => 1440,
                ],
            ],
        ];

        foreach ($rows as $row) {
            ApprovalRequest::query()->updateOrCreate(
                ['type' => $row['type'], 'title' => $row['title']],
                [
                    'status' => $row['status'],
                    'requested_by' => $row['requested_by'],
                    'assigned_to' => $row['assigned_to'],
                    'reviewed_by' => $row['reviewed_by'],
                    'reason' => $row['reason'],
                    'payload' => $row['payload'],
                    'review_note' => $row['review_note'],
                    'reviewed_at' => $row['reviewed_at'],
                    'snoozed_until' => null,
                    'snooze_note' => null,
                    'created_at' => $row['created_at'],
                    'updated_at' => now(),
                ]
            );
        }
    }
}

