<?php

namespace App\Console\Commands;

use App\Models\CashierAuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class DisasterRecoveryDrillCommand extends Command
{
    protected $signature = 'ops:drill-disaster-recovery {--staging : Konfirmasi bahwa ini dijalankan di staging}';

    protected $description = 'Simulasi drill recovery bulanan: cek backup terbaru + dry-run restore + health check.';

    public function handle(): int
    {
        if (! $this->option('staging')) {
            $this->error('Drill dibatalkan. Jalankan dengan --staging untuk mencegah salah eksekusi.');
            return self::FAILURE;
        }

        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            $this->error('Folder backup tidak ditemukan.');
            return self::FAILURE;
        }

        $latest = collect(File::files($backupDir))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->first();
        if (! $latest) {
            $this->error('Belum ada file backup.');
            return self::FAILURE;
        }

        $backupPath = $latest->getPathname();
        $this->info('Backup terbaru: '.$backupPath);

        Artisan::call('db:restore-safe', [
            'file' => $backupPath,
            '--dry-run' => true,
        ]);
        $restoreOutput = trim((string) Artisan::output());

        Artisan::call('ops:health-check');
        $healthOutput = trim((string) Artisan::output());

        CashierAuditLog::query()->create([
            'user_id' => null,
            'action' => 'disaster_recovery_drill_completed',
            'context' => [
                'backup_file' => $backupPath,
                'restore_dry_run_output' => $restoreOutput,
                'health_check_output' => $healthOutput,
            ],
            'ip_address' => 'cli',
            'user_agent' => 'artisan',
        ]);

        $this->info('Drill selesai. Lihat audit action: disaster_recovery_drill_completed');
        return self::SUCCESS;
    }
}

