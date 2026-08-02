<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivityClosingService
{
    public function closeActivity(Activity $activity, ?string $remainingBudgetNote, ?string $closingNote, User $verifier): void
    {
        DB::transaction(function () use ($activity, $remainingBudgetNote, $closingNote, $verifier) {
            if ($activity->status !== 'waiting_verification') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya kegiatan berstatus Menunggu Verifikasi yang dapat disetujui dan ditutup.',
                ]);
            }

            if ($activity->budgetYear->is_closed) {
                throw ValidationException::withMessages([
                    'status' => 'Tahun anggaran telah ditutup.',
                ]);
            }

            if ($activity->progress_percentage < 100) {
                throw ValidationException::withMessages([
                    'progress_percentage' => 'Progres kegiatan harus mencapai 100%.',
                ]);
            }

            // Check no pending realizations (draft, submitted, revision)
            $pendingRealizationCount = $activity->realizations()
                ->whereIn('status', ['draft', 'submitted', 'revision'])
                ->count();

            if ($pendingRealizationCount > 0) {
                throw ValidationException::withMessages([
                    'realizations' => "Masih terdapat {$pendingRealizationCount} transaksi realisasi yang belum terverifikasi atau perlu revisi.",
                ]);
            }

            // Check document completeness (100% valid required documents)
            $completeness = $activity->document_completeness;
            if ($completeness['valid_percentage'] < 100) {
                throw ValidationException::withMessages([
                    'documents' => 'Seluruh dokumen wajib harus berstatus Valid (100% Valid Terpenuhi) sebelum kegiatan dapat ditutup.',
                ]);
            }

            $finalRemaining = $activity->final_remaining_budget;
            $verifiedTotal = $activity->verified_realization_total;

            // Mandatory notes condition
            if ($finalRemaining > 0 && empty(trim($remainingBudgetNote ?? ''))) {
                throw ValidationException::withMessages([
                    'remaining_budget_note' => 'Catatan sisa anggaran wajib diisi karena terdapat sisa anggaran sebesar Rp '.number_format($finalRemaining, 0, ',', '.').'.',
                ]);
            }

            if ($verifiedTotal === 0 && $activity->budget_ceiling > 0 && empty(trim($closingNote ?? ''))) {
                throw ValidationException::withMessages([
                    'closing_note' => 'Catatan penutupan wajib diisi jika total realisasi terverifikasi bernilai Rp 0.',
                ]);
            }

            $prevStatus = $activity->status;

            $activity->update([
                'status' => 'completed',
                'submission_status' => 'approved',
                'completed_at' => now(),
                'completed_by' => $verifier->id,
                'remaining_budget_note' => $remainingBudgetNote,
                'closing_note' => $closingNote,
                'updated_by' => $verifier->id,
            ]);

            Verification::create([
                'verifier_id' => $verifier->id,
                'verifiable_type' => Activity::class,
                'verifiable_id' => $activity->id,
                'decision' => 'approved',
                'notes' => $closingNote ?? $remainingBudgetNote ?? 'Kegiatan disetujui dan ditutup.',
                'round' => $activity->verification_round,
                'previous_status' => $prevStatus,
                'new_status' => 'completed',
                'verified_at' => now(),
            ]);

            ActivityLog::log('close_activity', 'Verifikasi', "Menyetujui dan menutup kegiatan {$activity->activity_code} (Realisasi Final: Rp ".number_format($verifiedTotal, 0, ',', '.').", Sisa: Rp ".number_format($finalRemaining, 0, ',', '.').")", $activity);
        });
    }
}
