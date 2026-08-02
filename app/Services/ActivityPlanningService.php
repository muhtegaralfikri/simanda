<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityLog;
use App\Models\BudgetYear;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivityPlanningService
{
    public function createActivity(User $user, array $data): Activity
    {
        return DB::transaction(function () use ($user, $data) {
            $activeYear = BudgetYear::where('is_active', true)->first();

            if (! $activeYear || $activeYear->is_closed) {
                throw ValidationException::withMessages([
                    'budget_year_id' => 'Tahun anggaran sedang tidak aktif atau sudah ditutup.',
                ]);
            }

            // PPTK restriction: unit & person in charge forced to user's unit & user ID
            if ($user->isPPTK()) {
                $data['unit_id'] = $user->unit_id;
                $data['person_in_charge_id'] = $user->id;
            }

            // Verify Program matches budget year & unit
            $program = Program::find($data['program_id']);
            if (! $program || ! $program->is_active || $program->budget_year_id != $activeYear->id || $program->unit_id != $data['unit_id']) {
                throw ValidationException::withMessages([
                    'program_id' => 'Program yang dipilih tidak aktif atau tidak sesuai dengan unit kerja dan tahun anggaran.',
                ]);
            }

            // Verify PPTK matches unit & role
            $pptkUser = User::find($data['person_in_charge_id']);
            if (! $pptkUser || ! $pptkUser->is_active || $pptkUser->unit_id != $data['unit_id']) {
                throw ValidationException::withMessages([
                    'person_in_charge_id' => 'Penanggung Jawab (PPTK) harus aktif dan berasal dari unit kerja yang sama.',
                ]);
            }

            // Code uniqueness check
            $exists = Activity::where('budget_year_id', $activeYear->id)
                ->where('unit_id', $data['unit_id'])
                ->where('activity_code', $data['activity_code'])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'activity_code' => 'Kode kegiatan sudah digunakan untuk unit kerja dan tahun anggaran ini.',
                ]);
            }

            $activity = Activity::create([
                'budget_year_id' => $activeYear->id,
                'unit_id' => $data['unit_id'],
                'program_id' => $data['program_id'],
                'person_in_charge_id' => $data['person_in_charge_id'],
                'funding_source_id' => $data['funding_source_id'],
                'activity_code' => $data['activity_code'],
                'activity_name' => $data['activity_name'],
                'description' => $data['description'] ?? null,
                'location' => $data['location'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'target' => $data['target'] ?? null,
                'budget_ceiling' => (int) $data['budget_ceiling'],
                'progress_percentage' => 0,
                'status' => 'draft',
                'created_by' => $user->id,
            ]);

            ActivityLog::log('create', 'Kegiatan', "Membuat kegiatan baru {$activity->activity_code} - {$activity->activity_name}", $activity);

            return $activity;
        });
    }

    public function updateActivity(User $user, Activity $activity, array $data): Activity
    {
        return DB::transaction(function () use ($user, $activity, $data) {
            if ($activity->isClosedOrLocked()) {
                throw ValidationException::withMessages([
                    'status' => 'Kegiatan pada tahun anggaran yang ditutup atau dibatalkan tidak dapat diubah.',
                ]);
            }

            $oldCeiling = $activity->budget_ceiling;
            $newCeiling = (int) $data['budget_ceiling'];

            // Validation: new ceiling cannot be less than total RAB already input
            if ($newCeiling < $activity->total_budget_plan) {
                throw ValidationException::withMessages([
                    'budget_ceiling' => 'Pagu anggaran baru (Rp '.number_format($newCeiling, 0, ',', '.').') tidak boleh lebih kecil dari total RAB yang sudah diinput (Rp '.number_format($activity->total_budget_plan, 0, ',', '.').').',
                ]);
            }

            if ($user->isPPTK()) {
                $data['unit_id'] = $user->unit_id;
                $data['person_in_charge_id'] = $user->id;
            }

            $program = Program::find($data['program_id']);
            if (! $program || ! $program->is_active || $program->unit_id != $data['unit_id']) {
                throw ValidationException::withMessages([
                    'program_id' => 'Program yang dipilih tidak aktif atau tidak sesuai dengan unit kerja.',
                ]);
            }

            // Code uniqueness
            $exists = Activity::where('budget_year_id', $activity->budget_year_id)
                ->where('unit_id', $data['unit_id'])
                ->where('activity_code', $data['activity_code'])
                ->where('id', '!=', $activity->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'activity_code' => 'Kode kegiatan sudah digunakan oleh kegiatan lain.',
                ]);
            }

            $activity->update([
                'unit_id' => $data['unit_id'],
                'program_id' => $data['program_id'],
                'person_in_charge_id' => $data['person_in_charge_id'],
                'funding_source_id' => $data['funding_source_id'],
                'activity_code' => $data['activity_code'],
                'activity_name' => $data['activity_name'],
                'description' => $data['description'] ?? null,
                'location' => $data['location'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'target' => $data['target'] ?? null,
                'budget_ceiling' => $newCeiling,
                'updated_by' => $user->id,
            ]);

            $logMsg = "Memperbarui kegiatan {$activity->activity_code}";
            if ($oldCeiling != $newCeiling) {
                $logMsg .= " (Mengubah pagu dari Rp ".number_format($oldCeiling, 0, ',', '.')." menjadi Rp ".number_format($newCeiling, 0, ',', '.').")";
            }
            ActivityLog::log('update', 'Kegiatan', $logMsg, $activity);

            return $activity;
        });
    }

    public function setPlanned(Activity $activity): void
    {
        DB::transaction(function () use ($activity) {
            if ($activity->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya kegiatan berstatus Draft yang dapat ditetapkan sebagai Direncanakan.',
                ]);
            }

            if ($activity->budgetYear->is_closed) {
                throw ValidationException::withMessages([
                    'status' => 'Tahun anggaran sudah ditutup.',
                ]);
            }

            if ($activity->budget_ceiling <= 0) {
                throw ValidationException::withMessages([
                    'status' => 'Pagu anggaran kegiatan harus lebih dari 0.',
                ]);
            }

            if ($activity->budgetPlans()->count() === 0) {
                throw ValidationException::withMessages([
                    'status' => 'Kegiatan harus memiliki minimal satu rincian RAB sebelum ditetapkan sebagai Direncanakan.',
                ]);
            }

            $totalRab = $activity->total_budget_plan;
            if ($totalRab !== $activity->budget_ceiling) {
                throw ValidationException::withMessages([
                    'status' => 'Total RAB (Rp '.number_format($totalRab, 0, ',', '.').') harus persis sama dengan Pagu Kegiatan (Rp '.number_format($activity->budget_ceiling, 0, ',', '.').') untuk dapat ditetapkan sebagai Direncanakan.',
                ]);
            }

            $activity->update(['status' => 'planned']);
            ActivityLog::log('set_planned', 'Kegiatan', "Menetapkan status kegiatan {$activity->activity_code} sebagai Direncanakan", $activity);
        });
    }

    public function returnToDraft(Activity $activity): void
    {
        DB::transaction(function () use ($activity) {
            if ($activity->status !== 'planned') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya kegiatan berstatus Direncanakan yang dapat dikembalikan ke Draft.',
                ]);
            }

            if ($activity->budgetYear->is_closed) {
                throw ValidationException::withMessages([
                    'status' => 'Tahun anggaran sudah ditutup.',
                ]);
            }

            $activity->update(['status' => 'draft']);
            ActivityLog::log('return_to_draft', 'Kegiatan', "Mengembalikan status kegiatan {$activity->activity_code} menjadi Draft", $activity);
        });
    }

    public function cancelActivity(Activity $activity, string $reason): void
    {
        DB::transaction(function () use ($activity, $reason) {
            if ($activity->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'status' => 'Kegiatan sudah dibatalkan.',
                ]);
            }

            $activity->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
            ]);

            ActivityLog::log('cancel', 'Kegiatan', "Membatalkan kegiatan {$activity->activity_code} dengan alasan: {$reason}", $activity);
        });
    }
}
