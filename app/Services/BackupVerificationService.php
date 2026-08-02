<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BackupHistory;
use Illuminate\Support\Facades\File;

class BackupVerificationService
{
    public function verifyBackup(string $backupRef): array
    {
        $baseDir = config('simanda.backup.path', storage_path('app/private/backups'));
        $targetDir = "{$baseDir}/{$backupRef}";

        if (! File::exists($targetDir)) {
            return [
                'status' => 'failed',
                'message' => "Direktori backup '{$backupRef}' tidak ditemukan.",
            ];
        }

        $manifestPath = "{$targetDir}/manifest.json";
        if (! File::exists($manifestPath)) {
            return [
                'status' => 'failed',
                'message' => "File manifest.json tidak ditemukan pada cadangan '{$backupRef}'.",
            ];
        }

        $manifest = json_decode(File::get($manifestPath), true);
        if (! $manifest) {
            return [
                'status' => 'failed',
                'message' => 'Format manifest.json tidak valid.',
            ];
        }

        // 1. Verify Database File
        if (! empty($manifest['database']['file'])) {
            $dbPath = "{$targetDir}/".$manifest['database']['file'];
            if (! File::exists($dbPath)) {
                return ['status' => 'failed', 'message' => 'File database SQLite backup tidak ditemukan.'];
            }

            $currentDbHash = hash_file('sha256', $dbPath);
            if ($currentDbHash !== $manifest['database']['sha256']) {
                return ['status' => 'failed', 'message' => 'Checksum SHA-256 database backup tidak cocok!'];
            }

            // Verify SQLite integrity
            try {
                $pdo = new \PDO("sqlite:{$dbPath}");
                $stmt = $pdo->query('PRAGMA integrity_check');
                $res = $stmt->fetchColumn();
                if ($res !== 'ok') {
                    return ['status' => 'failed', 'message' => "SQLite integrity check gagal: {$res}"];
                }
            } catch (\Throwable $e) {
                return ['status' => 'failed', 'message' => 'Gagal menguji file database: '.$e->getMessage()];
            }
        }

        // 2. Verify Documents Zip File
        if (! empty($manifest['documents']['file'])) {
            $docPath = "{$targetDir}/".$manifest['documents']['file'];
            if (! File::exists($docPath)) {
                return ['status' => 'failed', 'message' => 'Archive dokumen backup tidak ditemukan.'];
            }

            $currentDocHash = hash_file('sha256', $docPath);
            if ($currentDocHash !== $manifest['documents']['sha256']) {
                return ['status' => 'failed', 'message' => 'Checksum SHA-256 archive dokumen tidak cocok!'];
            }
        }

        // Update BackupHistory record if found
        BackupHistory::where('backup_path_reference', $backupRef)->update([
            'status' => 'verified',
        ]);

        ActivityLog::log('verify_backup', 'Backup', "Verifikasi keutuhan dan integritas cadangan '{$backupRef}' BERHASIL", null);

        return [
            'status' => 'success',
            'message' => "Verifikasi integritas cadangan '{$backupRef}' berhasil (Database & Dokumen Valid).",
        ];
    }
}
