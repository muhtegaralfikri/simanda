<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityLog;
use App\Models\ActivityProgressLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivityExecutionService
{
    public function startExecution(Activity $activity, User $user): void
    {
        DB::transaction(function () use ($activity, $user) {
            if ($activity->status !== 'planned') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya kegiatan berstatus Direncanakan yang dapat dimulai pelaksanaannya.',
                ]);
            }

            if ($activity->budgetYear->is_closed) {
                throw ValidationException::withMessages([
                    'status' => 'Tahun anggaran sudah ditutup.',
                ]);
            }

            if ($activity->total_budget_plan !== $activity->budget_ceiling) {
                throw ValidationException::withMessages([
                    'status' => 'Total RAB harus persis sama dengan Pagu Kegiatan sebelum pelaksanaan dimulai.',
                ]);
            }

            $activity->update([
                'status' => 'ongoing',
                'started_at' => now(),
                'updated_by' => $user->id,
            ]);

            ActivityLog::log('start_execution', 'Pelaksanaan', "Memulai pelaksanaan kegiatan {$activity->activity_code} — {$activity->activity_name}", $activity);
        });
    }

    public function updateProgress(Activity $activity, int $percentage, ?string $note, User $user): void
    {
        DB::transaction(function () use ($activity, $percentage, $note, $user) {
            if ($activity->status !== 'ongoing') {
                throw ValidationException::withMessages([
                    'progress_percentage' => 'Hanya kegiatan dalam pelaksanaan (Ongoing) yang dapat diperbarui progresnya.',
                ]);
            }

            if ($activity->budgetYear->is_closed) {
                throw ValidationException::withMessages([
                    'progress_percentage' => 'Tahun anggaran telah ditutup.',
                ]);
            }

            if ($percentage < 0 || $percentage > 100) {
                throw ValidationException::withMessages([
                    'progress_percentage' => 'Persentase progres harus berada pada rentang 0 - 100%.',
                ]);
            }

            $oldProgress = $activity->progress_percentage;

            $activity->update([
                'progress_percentage' => $percentage,
                'progress_note' => $note,
                'progress_updated_at' => now(),
                'updated_by' => $user->id,
            ]);

            ActivityProgressLog::create([
                'activity_id' => $activity->id,
                'progress_percentage' => $percentage,
                'note' => $note,
                'updated_by' => $user->id,
            ]);

            ActivityLog::log('update_progress', 'Pelaksanaan', "Memperbarui progres kegiatan {$activity->activity_code} dari {$oldProgress}% menjadi {$percentage}%", $activity);
        });
    }
}
