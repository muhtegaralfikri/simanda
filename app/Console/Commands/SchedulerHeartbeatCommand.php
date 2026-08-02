<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SchedulerHeartbeatCommand extends Command
{
    protected $signature = 'simanda:scheduler:heartbeat';
    protected $description = 'Perbarui timestamp heartbeat scheduler Laravel SIMANDA';

    public function handle()
    {
        Cache::put('simanda_scheduler_heartbeat', now()->toIso8601String());
        $this->info('Scheduler heartbeat diperbarui.');

        return Command::SUCCESS;
    }
}
