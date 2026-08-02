<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BackupHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class BackupService
{
    public function runBackup(string $type = 'daily', ?User $user = null, bool $dbOnly = false, bool $docOnly = false): BackupHistory
    {
        $startedAt = now();
        $baseDir = config('simanda.backup.path', storage_path('app/private/backups'));
        $targetDir = "{$baseDir}/{$type}/".date('Y-m-d-His');

        if (! File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $history = BackupHistory::create([
            'backup_type' => $type,
            'status' => 'running',
            'started_at' => $startedAt,
            'created_by' => $user ? $user->id : null,
        ]);

        try {
            $dbFile = null;
            $dbSize = 0;
            $dbSha256 = null;

            // 1. Backup SQLite Database
            if (! $docOnly) {
                $dbPath = config('database.connections.sqlite.database');
                if (File::exists($dbPath)) {
                    $targetDbPath = "{$targetDir}/database.sqlite";

                    // Flush WAL journal mode log to SQLite database before copy
                    try {
                        DB::connection('sqlite')->statement('PRAGMA wal_checkpoint(FULL);');
                    } catch (\Throwable $e) {
                        // Ignore if non-WAL
                    }

                    // Copy SQLite DB File
                    File::copy($dbPath, $targetDbPath);

                    // Integrity check on target backup database
                    $checkPdo = new \PDO("sqlite:{$targetDbPath}");
                    $stmt = $checkPdo->query('PRAGMA integrity_check');
                    $integrityResult = $stmt->fetchColumn();
                    $checkPdo = null;

                    if ($integrityResult !== 'ok') {
                        throw new \Exception("SQLite integrity check failed on backup file: {$integrityResult}");
                    }

                    $dbSize = File::size($targetDbPath);
                    $dbSha256 = hash_file('sha256', $targetDbPath);
                    $dbFile = 'database.sqlite';
                }
            }

            // 2. Backup Private Documents
            $docCount = 0;
            $docSize = 0;
            $docFile = null;
            $docSha256 = null;

            if (! $dbOnly) {
                $documentsPath = storage_path('app/private/documents');
                if (File::exists($documentsPath)) {
                    $allDocFiles = File::allFiles($documentsPath);
                    $docCount = count($allDocFiles);
                    foreach ($allDocFiles as $f) {
                        $docSize += $f->getSize();
                    }

                    $zipPath = "{$targetDir}/documents.zip";
                    $zip = new \ZipArchive;
                    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                        foreach ($allDocFiles as $file) {
                            $relativePath = str_replace($documentsPath.'/', '', $file->getRealPath());
                            $zip->addFile($file->getRealPath(), $relativePath);
                        }
                        $zip->close();
                    }

                    if (File::exists($zipPath)) {
                        $docFile = 'documents.zip';
                        $docSha256 = hash_file('sha256', $zipPath);
                    }
                }
            }

            // 3. Create Manifest JSON
            $manifestData = [
                'application' => 'SIMANDA',
                'created_at' => now()->toIso8601String(),
                'backup_type' => $type,
                'database' => [
                    'file' => $dbFile,
                    'size' => $dbSize,
                    'sha256' => $dbSha256,
                    'integrity' => 'ok',
                ],
                'documents' => [
                    'file' => $docFile,
                    'count' => $docCount,
                    'size' => $docSize,
                    'sha256' => $docSha256,
                ],
                'status' => 'success',
            ];

            File::put("{$targetDir}/manifest.json", json_encode($manifestData, JSON_PRETTY_PRINT));

            $history->update([
                'status' => 'success',
                'completed_at' => now(),
                'database_size' => $dbSize,
                'document_count' => $docCount,
                'document_size' => $docSize,
                'backup_path_reference' => "{$type}/".basename($targetDir),
                'message' => "Backup {$type} berhasil dibuat.",
            ]);

            ActivityLog::log('run_backup', 'Backup', "Pencadangan data {$type} berhasil (DB: ".round($dbSize / 1024, 1)." KB, Dokumen: {$docCount} berkas)", null);

            $this->cleanupOldBackups($type);

            return $history;
        } catch (Throwable $e) {
            if (File::exists($targetDir)) {
                File::deleteDirectory($targetDir);
            }

            $history->update([
                'status' => 'failed',
                'completed_at' => now(),
                'message' => 'Gagal membuat backup: '.$e->getMessage(),
            ]);

            ActivityLog::log('backup_failed', 'Backup', 'Gagal membuat backup: '.$e->getMessage(), null);

            throw $e;
        }
    }

    public function cleanupOldBackups(string $type, bool $dryRun = false): array
    {
        $baseDir = config('simanda.backup.path', storage_path('app/private/backups'))."/{$type}";
        if (! File::exists($baseDir)) {
            return [];
        }

        $keepKey = "{$type}_keep";
        $keepLimit = config("simanda.backup.{$keepKey}", 7);

        $directories = File::directories($baseDir);
        rsort($directories); // Newest first

        $deleted = [];
        if (count($directories) > $keepLimit) {
            $toDelete = array_slice($directories, $keepLimit);
            foreach ($toDelete as $dir) {
                $deleted[] = basename($dir);
                if (! $dryRun) {
                    File::deleteDirectory($dir);
                    ActivityLog::log('cleanup_backup', 'Backup', "Menghapus cadangan lama: {$type}/".basename($dir), null);
                }
            }
        }

        return $deleted;
    }
}
