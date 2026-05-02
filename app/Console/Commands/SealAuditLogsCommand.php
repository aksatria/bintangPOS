<?php

namespace App\Console\Commands;

use App\Models\CashierAuditLog;
use Illuminate\Console\Command;

class SealAuditLogsCommand extends Command
{
    protected $signature = 'audit:seal {--limit=1000 : Jumlah log terbaru yang akan diseal}';

    protected $description = 'Tambahkan hash chain pada audit log untuk tamper-evidence sederhana.';

    public function handle(): int
    {
        $limit = max(1, min(5000, (int) $this->option('limit')));
        $rows = CashierAuditLog::query()
            ->latest('id')
            ->limit($limit)
            ->get()
            ->sortBy('id')
            ->values();

        $prevHash = '';
        $sealed = 0;
        foreach ($rows as $row) {
            $context = is_array($row->context) ? $row->context : [];
            $meta = (array) ($context['_meta'] ?? []);
            $payload = json_encode([
                'id' => $row->id,
                'user_id' => $row->user_id,
                'action' => $row->action,
                'created_at' => optional($row->created_at)->toIso8601String(),
                'context' => $context,
                'prev_hash' => $prevHash,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $hash = hash('sha256', (string) $payload);
            $meta['chain_prev_hash'] = $prevHash;
            $meta['chain_hash'] = $hash;
            $meta['chain_alg'] = 'sha256';
            $context['_meta'] = $meta;
            $row->context = $context;
            $row->save();
            $prevHash = $hash;
            $sealed++;
        }

        $this->info("Audit log sealed: {$sealed}");
        return self::SUCCESS;
    }
}

