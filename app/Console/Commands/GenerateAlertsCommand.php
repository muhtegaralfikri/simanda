<?php

namespace App\Console\Commands;

use App\Services\DeadlineAlertService;
use Illuminate\Console\Command;

class GenerateAlertsCommand extends Command
{
    protected $signature = 'simanda:alerts:generate';
    protected $description = 'Pindai kondisi sistem dan hasilkan peringatan internal SIMANDA';

    public function handle(DeadlineAlertService $service)
    {
        $this->info('Memulai pemindaian peringatan internal...');

        try {
            $result = $service->generateAlerts();
            $this->info("Pemindaian selesai. Peringatan Dibuat: {$result['created']}, Peringatan Diselesaikan: {$result['resolved']}");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal menghasilkan peringatan: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
