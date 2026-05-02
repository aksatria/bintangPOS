<?php

namespace App\Console\Commands;

use App\Models\CashierAuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'db:backup-safe {--name= : Nama file backup} {--prune-days=14 : Hapus backup lama}';

    protected $description = 'Backup database aman (tanpa wipe), otomatis simpan ke storage/app/backups';

    public function handle(): int
    {
        $connection = config('database.default');
        $db = (array) config("database.connections.{$connection}", []);
        $driver = (string) ($db['driver'] ?? 'mysql');

        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $baseName = trim((string) $this->option('name'));
        if ($baseName === '') {
            $baseName = 'backup-'.now()->format('Ymd-His');
        }

        if ($driver === 'sqlite') {
            return $this->backupSqlite($db, $dir, $baseName);
        }

        if ($driver === 'mysql') {
            return $this->backupMysql($db, $dir, $baseName);
        }

        $this->error("Driver {$driver} belum didukung backup command ini.");
        return self::FAILURE;
    }

    private function backupSqlite(array $db, string $dir, string $baseName): int
    {
        $databasePath = (string) ($db['database'] ?? '');
        if ($databasePath === '' || $databasePath === ':memory:' || ! File::exists($databasePath)) {
            $this->error('Database sqlite tidak valid untuk dibackup.');
            return self::FAILURE;
        }

        $target = $dir.DIRECTORY_SEPARATOR.$baseName.'.sqlite';
        File::copy($databasePath, $target);
        $this->finalize($target, 'sqlite');
        return self::SUCCESS;
    }

    private function backupMysql(array $db, string $dir, string $baseName): int
    {
        $host = (string) ($db['host'] ?? '127.0.0.1');
        $port = (string) ($db['port'] ?? '3306');
        $database = (string) ($db['database'] ?? '');
        $username = (string) ($db['username'] ?? '');
        $password = (string) ($db['password'] ?? '');
        if ($database === '' || $username === '') {
            $this->error('Konfigurasi mysql tidak lengkap.');
            return self::FAILURE;
        }

        $target = $dir.DIRECTORY_SEPARATOR.$baseName.'.sql';
        $mysqldump = (string) env('MYSQLDUMP_BIN', 'mysqldump');
        $cmd = sprintf(
            '"%s" --host=%s --port=%s --user=%s --password=%s --single-transaction --quick --skip-lock-tables %s > "%s"',
            $mysqldump,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            $target
        );

        @exec($cmd, $output, $code);
        if ($code !== 0 || ! File::exists($target) || File::size($target) === 0) {
            $this->error('Backup mysql gagal. Pastikan mysqldump tersedia.');
            return self::FAILURE;
        }

        $this->finalize($target, 'mysql');
        return self::SUCCESS;
    }

    private function finalize(string $path, string $driver): void
    {
        $days = max(1, (int) $this->option('prune-days'));
        $cutoff = now()->subDays($days)->getTimestamp();
        foreach (File::files(dirname($path)) as $file) {
            if ($file->getMTime() < $cutoff) {
                @File::delete($file->getPathname());
            }
        }

        try {
            CashierAuditLog::query()->create([
                'user_id' => auth()->id(),
                'action' => 'database_backup_created',
                'context' => [
                    'driver' => $driver,
                    'path' => str_replace(base_path().'\\', '', $path),
                    'size_bytes' => (int) @filesize($path),
                ],
                'ip_address' => 'cli',
                'user_agent' => 'artisan',
            ]);
        } catch (\Throwable $e) {
            // noop: backup tetap dianggap sukses walau audit insert gagal
        }

        $this->info('Backup sukses: '.$path);
    }
}
