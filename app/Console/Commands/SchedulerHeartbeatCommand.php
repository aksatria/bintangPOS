<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SchedulerHeartbeatCommand extends Command
{
    protected $signature = 'ops:scheduler-heartbeat';

    protected $description = 'Simpan heartbeat scheduler agar monitoring bisa deteksi schedule:run macet.';

    public function handle(): int
    {
        Cache::put('ops.scheduler.heartbeat_at', now()->toIso8601String(), now()->addDay());
        Cache::increment('ops.scheduler.heartbeat_count');

        $this->info('Scheduler heartbeat updated at '.now()->toDateTimeString());

        return self::SUCCESS;
    }
}

