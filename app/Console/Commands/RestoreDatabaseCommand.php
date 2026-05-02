<?php

namespace App\Console\Commands;

use App\Models\CashierAuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RestoreDatabaseCommand extends Command
{
    protected $signature = 'db:restore-safe {file : Path backup file} {--dry-run : Hanya validasi, tidak restore} {--force : Konfirmasi restore}';

    protected $description = 'Restore database dari backup file secara aman.';

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        if (! File::exists($path)) {
            $this->error('File backup tidak ditemukan: '.$path);
            return self::FAILURE;
        }

        $connection = config('database.default');
        $db = (array) config("database.connections.{$connection}", []);
        $driver = (string) ($db['driver'] ?? 'mysql');

        if ($this->option('dry-run')) {
            try {
                CashierAuditLog::query()->create([
                    'user_id' => auth()->id(),
                    'action' => 'database_restore_dry_run',
                    'context' => [
                        'driver' => $driver,
                        'file' => $path,
                    ],
                    'ip_address' => 'cli',
                    'user_agent' => 'artisan',
                ]);
            } catch (\Throwable $e) {
                // noop
            }
            $this->info("Dry-run restore OK. Driver={$driver}, file={$path}");
            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->error('Restore dibatalkan. Jalankan ulang dengan --force.');
            return self::FAILURE;
        }

        $ok = match ($driver) {
            'sqlite' => $this->restoreSqlite($db, $path),
            'mysql' => $this->restoreMysql($db, $path),
            default => false,
        };

        if (! $ok) {
            $this->error("Restore gagal untuk driver {$driver}.");
            return self::FAILURE;
        }

        try {
            CashierAuditLog::query()->create([
                'user_id' => auth()->id(),
                'action' => 'database_restore_executed',
                'context' => [
                    'driver' => $driver,
                    'file' => $path,
                ],
                'ip_address' => 'cli',
                'user_agent' => 'artisan',
            ]);
        } catch (\Throwable $e) {
            // noop
        }
        $this->info('Restore sukses.');
        return self::SUCCESS;
    }

    private function restoreSqlite(array $db, string $backupPath): bool
    {
        $databasePath = (string) ($db['database'] ?? '');
        if ($databasePath === '' || $databasePath === ':memory:') {
            return false;
        }

        return File::copy($backupPath, $databasePath);
    }

    private function restoreMysql(array $db, string $backupPath): bool
    {
        $host = (string) ($db['host'] ?? '127.0.0.1');
        $port = (string) ($db['port'] ?? '3306');
        $database = (string) ($db['database'] ?? '');
        $username = (string) ($db['username'] ?? '');
        $password = (string) ($db['password'] ?? '');
        if ($database === '' || $username === '') {
            return false;
        }

        $mysql = (string) env('MYSQL_BIN', 'mysql');
        $cmd = sprintf(
            '"%s" --host=%s --port=%s --user=%s --password=%s %s < "%s"',
            $mysql,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            $backupPath
        );
        @exec($cmd, $out, $code);
        return $code === 0;
    }
}
