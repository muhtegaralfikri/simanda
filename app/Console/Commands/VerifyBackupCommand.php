<?php

namespace App\Console\Commands;

use App\Services\BackupVerificationService;
use Illuminate\Console\Command;

class VerifyBackupCommand extends Command
{
    protected $signature = 'simanda:backup:verify {--path= : Path relatif cadangan, misal daily/2026-08-02-013000}';
    protected $description = 'Verifikasi keutuhan dan integritas cadangan SIMANDA';

    public function handle(BackupVerificationService $service)
    {
        $path = $this->option('path');

        if (! $path) {
            // Pick latest backup that completed successfully or has already been verified.
            $latest = \App\Models\BackupHistory::whereIn('status', ['success', 'verified'])->latest('completed_at')->first();
            if ($latest) {
                $path = $latest->backup_path_reference;
            }
        }

        if (! $path) {
            $this->error('Tidak ada cadangan untuk diverifikasi.');

            return Command::FAILURE;
        }

        $this->info("Memverifikasi cadangan: {$path}...");

        $res = $service->verifyBackup($path);

        if ($res['status'] === 'success') {
            $this->info($res['message']);

            return Command::SUCCESS;
        } else {
            $this->error($res['message']);

            return Command::FAILURE;
        }
    }
}
