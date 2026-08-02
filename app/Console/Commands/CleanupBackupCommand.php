<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class CleanupBackupCommand extends Command
{
    protected $signature = 'simanda:backup:cleanup {--dry-run : Simulasi pembersihan tanpa menghapus file}';
    protected $description = 'Bersihkan cadangan lama SIMANDA sesuai batas retensi';

    public function handle(BackupService $service)
    {
        $dryRun = $this->option('dry-run') ?? false;

        $this->info('Memulai pembersihan cadangan lama...'.($dryRun ? ' (Simulasi/Dry-Run)' : ''));

        $types = ['daily', 'weekly', 'monthly'];
        $totalDeleted = 0;

        foreach ($types as $t) {
            $deleted = $service->cleanupOldBackups($t, $dryRun);
            $count = count($deleted);
            $totalDeleted += $count;
            if ($count > 0) {
                $this->info("Pembersihan [{$t}]: ".implode(', ', $deleted));
            }
        }

        $this->info("Pembersihan selesai. Total folder ".($dryRun ? 'dapat dihapus' : 'dihapus').": {$totalDeleted}");

        return Command::SUCCESS;
    }
}
