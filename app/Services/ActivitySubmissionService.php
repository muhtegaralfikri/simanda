<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityDocument;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivitySubmissionService
{
    public function submitForVerification(Activity $activity, User $user): void
    {
        DB::transaction(function () use ($activity, $user) {
            if (! in_array($activity->status, ['ongoing', 'revision'])) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya kegiatan berstatus Sedang Berjalan (Ongoing) atau Perlu Revisi yang dapat diajukan.',
                ]);
            }

            if ($activity->budgetYear->is_closed) {
                throw ValidationException::withMessages([
                    'status' => 'Tahun anggaran telah ditutup.',
                ]);
            }

            if ($activity->progress_percentage < 100) {
                throw ValidationException::withMessages([
                    'progress_percentage' => 'Progres kegiatan harus mencapai 100% sebelum diajukan untuk verifikasi.',
                ]);
            }

            if ($activity->total_budget_plan !== $activity->budget_ceiling) {
                throw ValidationException::withMessages([
                    'budget_ceiling' => 'Total RAB harus persis sama dengan Pagu Kegiatan.',
                ]);
            }

            // Check no draft or revision realizations
            $hasDraftOrRevisionRealizations = $activity->realizations()
                ->whereIn('status', ['draft', 'revision'])
                ->exists();

            if ($hasDraftOrRevisionRealizations) {
                throw ValidationException::withMessages([
                    'realizations' => 'Terdapat realisasi yang masih berstatus Draft atau Perlu Revisi. Harap ajukan/perbaiki seluruh realisasi terlebih dahulu.',
                ]);
            }

            // Check document completeness: all required document types must have current version
            $completeness = $activity->document_completeness;
            if ($completeness['unfulfilled_required'] > 0) {
                throw ValidationException::withMessages([
                    'documents' => "Masih terdapat {$completeness['unfulfilled_required']} jenis dokumen wajib yang belum diunggah.",
                ]);
            }

            // Check no required documents with status 'revision'
            $hasRevisionRequiredDocs = $activity->documents()
                ->where('is_current', true)
                ->where('status', 'revision')
                ->exists();

            if ($hasRevisionRequiredDocs) {
                throw ValidationException::withMessages([
                    'documents' => 'Terdapat dokumen aktif yang berstatus Perlu Revisi. Harap unggah berkas pengganti terlebih dahulu.',
                ]);
            }

            // Auto-promote uploaded active documents to submitted
            $activity->documents()
                ->where('is_current', true)
                ->where('status', 'uploaded')
                ->update(['status' => 'submitted']);

            $newRound = $activity->submitted_at ? $activity->verification_round + 1 : 1;

            $activity->update([
                'status' => 'waiting_verification',
                'submission_status' => 'submitted',
                'submitted_at' => now(),
                'submitted_by' => $user->id,
                'verification_round' => $newRound,
                'updated_by' => $user->id,
            ]);

            ActivityLog::log('submit_verification', 'Verifikasi', "Mengajukan kegiatan {$activity->activity_code} untuk verifikasi putaran {$newRound}", $activity);
        });
    }
}
