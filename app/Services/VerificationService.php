<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityDocument;
use App\Models\ActivityLog;
use App\Models\Realization;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerificationService
{
    public function startReview(Activity $activity, User $verifier): void
    {
        DB::transaction(function () use ($activity, $verifier) {
            if ($activity->status !== 'waiting_verification') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya kegiatan berstatus Menunggu Verifikasi yang dapat diperiksa.',
                ]);
            }

            $activity->update([
                'submission_status' => 'under_review',
                'review_started_at' => now(),
                'review_started_by' => $verifier->id,
            ]);

            ActivityLog::log('start_review', 'Verifikasi', "Verifier {$verifier->name} mulai memeriksa kegiatan {$activity->activity_code}", $activity);
        });
    }

    public function verifyRealization(Realization $realization, string $decision, ?string $notes, User $verifier): Verification
    {
        return DB::transaction(function () use ($realization, $decision, $notes, $verifier) {
            $activity = $realization->activity;

            if ($activity->budgetYear->is_closed) {
                throw ValidationException::withMessages(['status' => 'Tahun anggaran telah ditutup.']);
            }

            if (! in_array($decision, ['verified', 'revision', 'rejected'])) {
                throw ValidationException::withMessages(['decision' => 'Keputusan verifikasi realisasi tidak valid.']);
            }

            if (in_array($decision, ['revision', 'rejected']) && empty(trim($notes ?? ''))) {
                throw ValidationException::withMessages(['notes' => 'Catatan wajib diisi untuk keputusan Revisi atau Penolakan.']);
            }

            $prevStatus = $realization->status;

            $realization->update([
                'status' => $decision,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
                'verification_note' => $notes,
            ]);

            $verification = Verification::create([
                'verifier_id' => $verifier->id,
                'verifiable_type' => Realization::class,
                'verifiable_id' => $realization->id,
                'decision' => $decision === 'verified' ? 'approved' : $decision,
                'notes' => $notes,
                'round' => $activity->verification_round,
                'previous_status' => $prevStatus,
                'new_status' => $decision,
                'verified_at' => now(),
            ]);

            ActivityLog::log('verify_realization', 'Verifikasi', "Keputusan realisasi {$realization->receipt_number}: ".strtoupper($decision), $activity);

            return $verification;
        });
    }

    public function verifyDocument(ActivityDocument $document, string $decision, ?string $notes, User $verifier): Verification
    {
        return DB::transaction(function () use ($document, $decision, $notes, $verifier) {
            $activity = $document->activity;

            if ($activity->budgetYear->is_closed) {
                throw ValidationException::withMessages(['status' => 'Tahun anggaran telah ditutup.']);
            }

            if (! $document->is_current) {
                throw ValidationException::withMessages(['document' => 'Hanya versi dokumen aktif (current version) yang dapat diverifikasi.']);
            }

            if (! in_array($decision, ['valid', 'revision', 'rejected'])) {
                throw ValidationException::withMessages(['decision' => 'Keputusan verifikasi dokumen tidak valid.']);
            }

            if (in_array($decision, ['revision', 'rejected']) && empty(trim($notes ?? ''))) {
                throw ValidationException::withMessages(['notes' => 'Catatan wajib diisi untuk keputusan Revisi atau Penolakan.']);
            }

            $prevStatus = $document->status;

            $document->update([
                'status' => $decision,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
                'verification_note' => $notes,
            ]);

            $verification = Verification::create([
                'verifier_id' => $verifier->id,
                'verifiable_type' => ActivityDocument::class,
                'verifiable_id' => $document->id,
                'decision' => $decision === 'valid' ? 'approved' : $decision,
                'notes' => $notes,
                'round' => $activity->verification_round,
                'previous_status' => $prevStatus,
                'new_status' => $decision,
                'verified_at' => now(),
            ]);

            ActivityLog::log('verify_document', 'Verifikasi', "Keputusan dokumen '{$document->original_name}': ".strtoupper($decision), $activity);

            return $verification;
        });
    }

    public function requestActivityRevision(Activity $activity, string $notes, User $verifier): void
    {
        DB::transaction(function () use ($activity, $notes, $verifier) {
            if (empty(trim($notes))) {
                throw ValidationException::withMessages(['notes' => 'Catatan pengembalian revisi kegiatan wajib diisi.']);
            }

            $prevStatus = $activity->status;

            $activity->update([
                'status' => 'revision',
                'submission_status' => 'revision',
                'updated_by' => $verifier->id,
            ]);

            Verification::create([
                'verifier_id' => $verifier->id,
                'verifiable_type' => Activity::class,
                'verifiable_id' => $activity->id,
                'decision' => 'revision',
                'notes' => $notes,
                'round' => $activity->verification_round,
                'previous_status' => $prevStatus,
                'new_status' => 'revision',
                'verified_at' => now(),
            ]);

            ActivityLog::log('request_activity_revision', 'Verifikasi', "Mengembalikan kegiatan {$activity->activity_code} untuk revisi putaran {$activity->verification_round}", $activity);
        });
    }

    public function rejectActivitySubmission(Activity $activity, string $notes, User $verifier): void
    {
        DB::transaction(function () use ($activity, $notes, $verifier) {
            if (empty(trim($notes))) {
                throw ValidationException::withMessages(['notes' => 'Catatan penolakan pengajuan kegiatan wajib diisi.']);
            }

            $prevStatus = $activity->status;

            $activity->update([
                'status' => 'ongoing',
                'submission_status' => 'rejected',
                'updated_by' => $verifier->id,
            ]);

            Verification::create([
                'verifier_id' => $verifier->id,
                'verifiable_type' => Activity::class,
                'verifiable_id' => $activity->id,
                'decision' => 'rejected',
                'notes' => $notes,
                'round' => $activity->verification_round,
                'previous_status' => $prevStatus,
                'new_status' => 'ongoing',
                'verified_at' => now(),
            ]);

            ActivityLog::log('reject_activity_submission', 'Verifikasi', "Menolak pengajuan kegiatan {$activity->activity_code}", $activity);
        });
    }
}
