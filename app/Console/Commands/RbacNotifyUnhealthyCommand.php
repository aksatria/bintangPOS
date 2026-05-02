<?php

namespace App\Console\Commands;

use App\Support\TelegramNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RbacNotifyUnhealthyCommand extends Command
{
    protected $signature = 'rbac:notify-unhealthy';

    protected $description = 'Kirim alert Telegram jika RBAC health check gagal.';

    public function handle(): int
    {
        $exitCode = Artisan::call('rbac:health-check', ['--json' => true, '--no-ansi' => true]);
        if ($exitCode === 0) {
            $this->info('RBAC sehat. Tidak kirim alert.');
            return self::SUCCESS;
        }

        $message = implode("\n", [
            'RBAC ALERT: health check gagal',
            'Waktu: '.now()->toDateTimeString(),
            'Exit code: '.$exitCode,
            'Aksi: jalankan `php artisan rbac:health-check --repair` lalu audit manual.',
        ]);

        $sent = TelegramNotifier::send($message, 'rbac_health_alert', TelegramNotifier::defaultChatId());
        if (! $sent) {
            $this->error('Gagal kirim alert Telegram.');
            return self::FAILURE;
        }

        $this->warn('RBAC tidak sehat. Alert Telegram berhasil dikirim.');
        return self::FAILURE;
    }
}
