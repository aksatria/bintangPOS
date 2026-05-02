<?php

namespace App\Console\Commands;

use App\Support\TelegramNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OpsRecoveryPackCommand extends Command
{
    protected $signature = 'ops:recovery-pack {--staging : Konfirmasi eksekusi di staging} {--telegram : Kirim ringkasan ke Telegram}';

    protected $description = 'One-click recovery pack: backup check, drill dry-run restore, health check.';

    public function handle(): int
    {
        if (! $this->option('staging')) {
            $this->error('Jalankan dengan --staging untuk mencegah salah lingkungan.');
            return self::FAILURE;
        }

        $steps = [];
        Artisan::call('db:backup-safe');
        $steps[] = ['step' => 'backup', 'output' => trim((string) Artisan::output())];

        Artisan::call('ops:drill-disaster-recovery', ['--staging' => true]);
        $steps[] = ['step' => 'drill', 'output' => trim((string) Artisan::output())];

        $healthCode = Artisan::call('ops:health-check');
        $steps[] = ['step' => 'health', 'output' => trim((string) Artisan::output()), 'exit' => $healthCode];

        if ($this->option('telegram') && TelegramNotifier::enabled()) {
            TelegramNotifier::send(
                implode("\n", [
                    'Recovery Pack Selesai',
                    'backup: '.(str_contains(strtolower($steps[0]['output']), 'sukses') ? 'OK' : 'CHECK'),
                    'drill: '.(str_contains(strtolower($steps[1]['output']), 'selesai') ? 'OK' : 'CHECK'),
                    'health_exit: '.(string) $healthCode,
                ]),
                'ops_recovery_pack',
                TelegramNotifier::defaultChatId()
            );
        }

        $this->info('Recovery pack selesai.');
        return self::SUCCESS;
    }
}

