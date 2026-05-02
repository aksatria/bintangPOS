<?php

namespace App\Console\Commands;

use App\Models\CashierAuditLog;
use Illuminate\Console\Command;

class BackfillAuditMetaCommand extends Command
{
    protected $signature = 'audit:backfill-meta {--chunk=500 : Jumlah record per batch} {--dry-run : Hanya hitung tanpa update}';

    protected $description = 'Lengkapi context._meta pada audit log lama yang belum punya metadata schema';

    public function handle(): int
    {
        $chunk = max(100, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $totalChecked = 0;
        $totalMissing = 0;
        $totalUpdated = 0;

        CashierAuditLog::query()
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use ($dryRun, &$totalChecked, &$totalMissing, &$totalUpdated): void {
                foreach ($rows as $row) {
                    $totalChecked++;
                    $context = is_array($row->context) ? $row->context : [];
                    $meta = data_get($context, '_meta');
                    $hasSchema = is_array($meta) && ! empty($meta['schema']);

                    if ($hasSchema) {
                        continue;
                    }

                    $totalMissing++;
                    if ($dryRun) {
                        continue;
                    }

                    $context['_meta'] = [
                        'schema' => 'cashier_audit_log.v1',
                        'recorded_at' => optional($row->created_at)->toIso8601String() ?: now()->toIso8601String(),
                        'backfilled_at' => now()->toIso8601String(),
                    ];

                    $row->context = $context;
                    $row->save();
                    $totalUpdated++;
                }
            });

        $this->info("Total checked : {$totalChecked}");
        $this->info("Missing _meta : {$totalMissing}");
        $this->info("Updated       : {$totalUpdated}".($dryRun ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }
}
