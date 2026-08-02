<?php

namespace App\Http\Controllers;

use App\Models\BackupHistory;
use App\Services\BackupService;
use App\Services\BackupVerificationService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    protected BackupService $backupService;
    protected BackupVerificationService $verificationService;

    public function __construct(BackupService $backupService, BackupVerificationService $verificationService)
    {
        $this->backupService = $backupService;
        $this->verificationService = $verificationService;
    }

    public function index()
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Manajemen backup hanya dapat diakses oleh Administrator.');
        }

        $backups = BackupHistory::with('creator')->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $latestSuccessful = BackupHistory::whereIn('status', ['success', 'verified'])->latest('completed_at')->first();

        return view('admin.system.backups', compact('backups', 'latestSuccessful'));
    }

    public function run(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Manajemen backup hanya dapat diakses oleh Administrator.');
        }

        $type = $request->input('backup_type', 'daily');

        try {
            $this->backupService->runBackup($type, auth()->user());

            return redirect()->route('admin.system.backups.index')->with('success', "Proses pencadangan data '{$type}' berhasil dilakukan.");
        } catch (\Throwable $e) {
            return redirect()->route('admin.system.backups.index')->with('error', 'Gagal membuat backup: '.$e->getMessage());
        }
    }

    public function verify(BackupHistory $backup)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Manajemen backup hanya dapat diakses oleh Administrator.');
        }

        if (! $backup->backup_path_reference) {
            return back()->with('error', 'Referensi lokasi cadangan tidak valid.');
        }

        $res = $this->verificationService->verifyBackup($backup->backup_path_reference);

        if ($res['status'] === 'success') {
            return redirect()->route('admin.system.backups.index')->with('success', $res['message']);
        } else {
            return redirect()->route('admin.system.backups.index')->with('error', $res['message']);
        }
    }
}
