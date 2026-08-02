<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class RunBackupCommand extends Command
{
    protected $signature = 'simanda:backup {--type=daily : Tipe backup: daily, weekly, monthly} {--database-only} {--documents-only}';
    protected $description = 'Jalankan pencadangan database SQLite dan dokumen private SIMANDA';

    public function handle(BackupService $service)
    {
        $type = $this->option('type') ?? 'daily';
        $dbOnly = $this->option('database-only') ?? false;
        $docOnly = $this->option('documents-only') ?? false;

        $this->info("Memulai pencadangan data SIMANDA (Tipe: {$type})...");

        try {
            $history = $service->runBackup($type, null, $dbOnly, $docOnly);
            $this->info("Pencadangan berhasil dibuat! Ref: {$history->backup_path_reference}");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Pencadangan gagal: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
