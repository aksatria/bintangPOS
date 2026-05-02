<?php

namespace App\Console\Commands;

use App\Models\ApprovalRequest;
use App\Models\Branch;
use App\Models\StoreSetting;
use App\Support\BusinessHourSla;
use App\Support\TelegramNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendApprovalSlaAnomalyAlertCommand extends Command
{
    protected $signature = 'approval:send-sla-anomaly-alert';

    protected $description = 'Kirim alert Telegram jika jumlah overdue approval melebihi threshold.';

    public function handle(): int
    {
        if (! TelegramNotifier::enabled()) {
            $this->info('Telegram nonaktif. Alert anomali SLA dilewati.');
            return self::SUCCESS;
        }

        $store = StoreSetting::query()->first();
        $rules = is_array($store?->approval_rules) ? $store->approval_rules : [];
        $saleSla = max(5, (int) data_get($rules, 'sla_minutes_sale', 120));
        $exportSla = max(5, (int) data_get($rules, 'sla_minutes_export', 360));
        $threshold = max(1, (int) data_get($rules, 'overdue_alert_threshold', 5));
        $businessHours = [
            'start' => (string) data_get($rules, 'business_hours.start', '08:00'),
            'end' => (string) data_get($rules, 'business_hours.end', '22:00'),
            'workdays' => (array) data_get($rules, 'business_hours.workdays', [1, 2, 3, 4, 5, 6, 7]),
        ];

        $cooldown = max(300, (int) env('APPROVAL_SLA_ANOMALY_COOLDOWN_SECONDS', 1800));
        $branches = Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $branches = $branches->concat([(object) ['id' => null, 'name' => 'Global']]);
        $sent = 0;

        foreach ($branches as $branch) {
            $pending = ApprovalRequest::query()
                ->when($branch->id, fn ($q) => $q->where('branch_id', $branch->id), fn ($q) => $q->whereNull('branch_id'))
                ->where('status', 'pending')
                ->orderBy('created_at')
                ->limit(300)
                ->get(['id', 'type', 'created_at', 'title', 'assigned_to']);

            $overdue = [];
            foreach ($pending as $row) {
                $minutes = BusinessHourSla::diffInBusinessMinutes($row->created_at, now(), $businessHours);
                $limit = str_starts_with((string) $row->type, 'report.export.') ? $exportSla : $saleSla;
                if ($minutes >= $limit) {
                    $overdue[] = [
                        'id' => (int) $row->id,
                        'type' => (string) $row->type,
                        'title' => (string) $row->title,
                        'minutes' => (int) $minutes,
                    ];
                }
            }

            if (count($overdue) < $threshold) {
                continue;
            }

            $fingerprint = sha1(json_encode([
                'branch_id' => $branch->id ? (int) $branch->id : 'global',
                'count' => count($overdue),
                'top' => array_map(fn ($x) => [$x['id'], $x['minutes']], array_slice($overdue, 0, 5)),
            ]));
            $cooldownKey = 'approval:sla-anomaly:'.$fingerprint;
            if (Cache::has($cooldownKey)) {
                continue;
            }

            $lines = [
                'Anomali SLA Approval Terdeteksi',
                'Cabang: '.$branch->name,
                'Total overdue: '.count($overdue),
                'Threshold alert: '.$threshold,
            ];
            foreach (array_slice($overdue, 0, 5) as $idx => $item) {
                $lines[] = ($idx + 1).'. #'.$item['id'].' - '.$item['minutes'].'m - '.$item['type'];
            }
            if (count($overdue) > 5) {
                $lines[] = '+'.(count($overdue) - 5).' overdue lainnya.';
            }

            $ok = TelegramNotifier::send(
                implode("\n", $lines),
                'approval_sla_anomaly',
                TelegramNotifier::defaultChatId()
            );
            if (! $ok) {
                continue;
            }

            Cache::put($cooldownKey, 1, now()->addSeconds($cooldown));
            $sent++;
        }

        if ($sent === 0) {
            $this->info('Tidak ada alert anomali SLA baru untuk dikirim.');
            return self::SUCCESS;
        }

        $this->info("Alert anomali SLA berhasil dikirim untuk {$sent} cabang.");

        return self::SUCCESS;
    }
}
