<?php

namespace App\Services;

use App\Models\BackupHistory;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SystemHealthService
{
    public function getHealthStatus(): array
    {
        $checks = [];

        // 1. Environment Check
        $appEnv = config('app.env');
        $appDebug = config('app.debug');
        $checks['environment'] = [
            'label' => 'Environment & Debug Status',
            'value' => "ENV: {$appEnv} | DEBUG: ".($appDebug ? 'TRUE (Bahaya)' : 'FALSE (Aman)'),
            'status' => (! $appDebug) ? 'good' : 'warning',
            'note' => $appDebug ? 'Matikan APP_DEBUG pada lingkungan produksi.' : 'Konfigurasi produksi aman.',
        ];

        // 2. Database Connection & WAL Mode
        try {
            $journalMode = DB::select('PRAGMA journal_mode')[0]->journal_mode ?? 'unknown';
            $checks['database'] = [
                'label' => 'Database SQLite Connection',
                'value' => "SQLite (Journal Mode: ".strtoupper($journalMode).')',
                'status' => strtolower($journalMode) === 'wal' ? 'good' : 'warning',
                'note' => 'Database SQLite terhubung dengan sukses.',
            ];
        } catch (\Throwable $e) {
            $checks['database'] = [
                'label' => 'Database SQLite Connection',
                'value' => 'Terputus / Eror',
                'status' => 'danger',
                'note' => $e->getMessage(),
            ];
        }

        // 3. Storage Permissions
        $storageWritable = is_writable(storage_path());
        $bootstrapWritable = is_writable(base_path('bootstrap/cache'));
        $checks['storage'] = [
            'label' => 'Izin Tulis Direktori Storage',
            'value' => $storageWritable && $bootstrapWritable ? 'Dapat Ditulis (Writable)' : 'Akses Ditolak',
            'status' => $storageWritable && $bootstrapWritable ? 'good' : 'danger',
            'note' => 'Direktori storage dan cache dapat diakses oleh PHP-FPM.',
        ];

        // 4. Private Documents Directory
        $privateDocPath = storage_path('app/private/documents');
        $privateDocExists = File::exists($privateDocPath) && is_writable($privateDocPath);
        $checks['documents_storage'] = [
            'label' => 'Penyimpanan Dokumen Private',
            'value' => $privateDocExists ? 'Tersedia & Privat' : 'Belum Dibuat / Tidak Writable',
            'status' => $privateDocExists ? 'good' : 'warning',
            'note' => 'Dokumen tersimpan privat di luar folder web public.',
        ];

        // 5. Backup Status & Age
        $latestBackup = BackupHistory::where('status', 'success')->latest('completed_at')->first();
        if ($latestBackup && $latestBackup->completed_at) {
            $hoursOld = (int) now()->diffInHours($latestBackup->completed_at);
            $checks['backup'] = [
                'label' => 'Status Cadangan Terakhir',
                'value' => "{$latestBackup->backup_type} ({$hoursOld} jam lalu)",
                'status' => $hoursOld <= 48 ? 'good' : 'warning',
                'note' => "Backup terakhir pada: {$latestBackup->completed_at->format('d/m/Y H:i')}",
            ];
        } else {
            $checks['backup'] = [
                'label' => 'Status Cadangan Terakhir',
                'value' => 'Belum Ada Backup',
                'status' => 'danger',
                'note' => 'Belum pernah ada backup berhasil.',
            ];
        }

        // 6. Disk Free Space
        $diskPath = base_path();
        $freeSpace = @disk_free_space($diskPath);
        $totalSpace = @disk_total_space($diskPath);
        if ($freeSpace !== false && $totalSpace !== false && $totalSpace > 0) {
            $freePercent = round(($freeSpace / $totalSpace) * 100, 1);
            $warningPercent = config('simanda.disk.warning_percent', 15);
            $status = 'good';
            if ($freePercent < 15) {
                $status = 'danger';
            } elseif ($freePercent <= 20) {
                $status = 'warning';
            }

            $checks['disk_space'] = [
                'label' => 'Kapasitas Disk Bebas',
                'value' => "{$freePercent}% Bebas (".round($freeSpace / (1024 * 1024 * 1024), 2).' GB)',
                'status' => $status,
                'note' => "Kapasitas disk tersisa: {$freePercent}%.",
            ];
        }

        // 7. PHP Version & OPcache
        $opcacheActive = function_exists('opcache_get_status') && @opcache_get_status()['opcache_enabled'];
        $checks['php_opcache'] = [
            'label' => 'PHP Version & OPcache',
            'value' => 'PHP '.PHP_VERSION.' | OPcache: '.($opcacheActive ? 'Aktif' : 'Non-Aktif'),
            'status' => 'good',
            'note' => 'Versi PHP kompatibel dengan SIMANDA.',
        ];

        // 8. Scheduler Heartbeat
        $lastHeartbeat = Cache::get('simanda_scheduler_heartbeat');
        if ($lastHeartbeat) {
            $diffMinutes = (int) now()->diffInMinutes($lastHeartbeat);
            $checks['scheduler'] = [
                'label' => 'Scheduler Heartbeat',
                'value' => "Aktif ({$diffMinutes} menit lalu)",
                'status' => $diffMinutes <= 90 ? 'good' : 'warning',
                'note' => "Heartbeat terakhir: {$lastHeartbeat}",
            ];
        } else {
            $checks['scheduler'] = [
                'label' => 'Scheduler Heartbeat',
                'value' => 'Belum Terdeteksi',
                'status' => 'warning',
                'note' => 'Pastikan Cron Laravel Scheduler aktif.',
            ];
        }

        // Overall Health Status
        $overall = 'good';
        foreach ($checks as $c) {
            if ($c['status'] === 'danger') {
                $overall = 'danger';
                break;
            } elseif ($c['status'] === 'warning' && $overall !== 'danger') {
                $overall = 'warning';
            }
        }

        return [
            'overall' => $overall,
            'checks' => $checks,
        ];
    }
}
