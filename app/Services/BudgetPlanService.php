<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityLog;
use App\Models\BudgetPlan;
use App\Models\ExpenseType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BudgetPlanService
{
    public function storeBudgetPlan(Activity $activity, array $data): BudgetPlan
    {
        return DB::transaction(function () use ($activity, $data) {
            if ($activity->isClosedOrLocked()) {
                throw ValidationException::withMessages([
                    'activity_id' => 'RAB tidak dapat ditambah pada kegiatan yang dibatalkan atau tahun anggaran yang ditutup.',
                ]);
            }

            $expenseType = ExpenseType::find($data['expense_type_id']);
            if (! $expenseType || ! $expenseType->is_active) {
                throw ValidationException::withMessages([
                    'expense_type_id' => 'Jenis belanja tidak aktif atau tidak ditemukan.',
                ]);
            }

            $volume = (int) $data['volume'];
            $unitPrice = (int) $data['unit_price'];

            if ($volume <= 0) {
                throw ValidationException::withMessages([
                    'volume' => 'Volume harus lebih dari 0.',
                ]);
            }

            if ($unitPrice < 0) {
                throw ValidationException::withMessages([
                    'unit_price' => 'Harga satuan tidak boleh negatif.',
                ]);
            }

            $total = $volume * $unitPrice;

            // Check budget ceiling limit
            $currentTotalRab = $activity->total_budget_plan;
            $newTotalRab = $currentTotalRab + $total;

            if ($newTotalRab > $activity->budget_ceiling) {
                $selisih = $newTotalRab - $activity->budget_ceiling;
                throw ValidationException::withMessages([
                    'unit_price' => 'Total rincian RAB (Rp '.number_format($newTotalRab, 0, ',', '.').') melebihi pagu kegiatan (Rp '.number_format($activity->budget_ceiling, 0, ',', '.').') sebesar Rp '.number_format($selisih, 0, ',', '.').'.',
                ]);
            }

            $budgetPlan = BudgetPlan::create([
                'activity_id' => $activity->id,
                'expense_type_id' => $data['expense_type_id'],
                'account_code' => $data['account_code'] ?? $expenseType->code,
                'description' => $data['description'],
                'volume' => $volume,
                'unit' => $data['unit'],
                'unit_price' => $unitPrice,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            ActivityLog::log('create_rab', 'Rencana Anggaran', "Menambahkan item RAB '{$budgetPlan->description}' sebesar Rp ".number_format($total, 0, ',', '.')." pada kegiatan {$activity->activity_code}", $activity);

            return $budgetPlan;
        });
    }

    public function updateBudgetPlan(BudgetPlan $budgetPlan, array $data): BudgetPlan
    {
        return DB::transaction(function () use ($budgetPlan, $data) {
            $activity = $budgetPlan->activity;

            if ($activity->isClosedOrLocked()) {
                throw ValidationException::withMessages([
                    'activity_id' => 'RAB tidak dapat diubah pada kegiatan yang dibatalkan atau tahun anggaran yang ditutup.',
                ]);
            }

            $expenseType = ExpenseType::find($data['expense_type_id']);
            if (! $expenseType || ! $expenseType->is_active) {
                throw ValidationException::withMessages([
                    'expense_type_id' => 'Jenis belanja tidak aktif atau tidak ditemukan.',
                ]);
            }

            $volume = (int) $data['volume'];
            $unitPrice = (int) $data['unit_price'];

            if ($volume <= 0) {
                throw ValidationException::withMessages([
                    'volume' => 'Volume harus lebih dari 0.',
                ]);
            }

            if ($unitPrice < 0) {
                throw ValidationException::withMessages([
                    'unit_price' => 'Harga satuan tidak boleh negatif.',
                ]);
            }

            $newTotal = $volume * $unitPrice;

            // Check budget ceiling limit subtracting old budget plan total
            $otherRabTotal = $activity->budgetPlans()->where('id', '!=', $budgetPlan->id)->sum('total');
            $newTotalRab = $otherRabTotal + $newTotal;

            if ($newTotalRab > $activity->budget_ceiling) {
                $selisih = $newTotalRab - $activity->budget_ceiling;
                throw ValidationException::withMessages([
                    'unit_price' => 'Total rincian RAB (Rp '.number_format($newTotalRab, 0, ',', '.').') melebihi pagu kegiatan (Rp '.number_format($activity->budget_ceiling, 0, ',', '.').') sebesar Rp '.number_format($selisih, 0, ',', '.').'.',
                ]);
            }

            $budgetPlan->update([
                'expense_type_id' => $data['expense_type_id'],
                'account_code' => $data['account_code'] ?? $expenseType->code,
                'description' => $data['description'],
                'volume' => $volume,
                'unit' => $data['unit'],
                'unit_price' => $unitPrice,
                'total' => $newTotal,
                'notes' => $data['notes'] ?? null,
            ]);

            ActivityLog::log('update_rab', 'Rencana Anggaran', "Memperbarui item RAB '{$budgetPlan->description}' menjadi Rp ".number_format($newTotal, 0, ',', '.')." pada kegiatan {$activity->activity_code}", $activity);

            return $budgetPlan;
        });
    }

    public function deleteBudgetPlan(BudgetPlan $budgetPlan): void
    {
        DB::transaction(function () use ($budgetPlan) {
            $activity = $budgetPlan->activity;

            if ($activity->isClosedOrLocked()) {
                throw ValidationException::withMessages([
                    'activity_id' => 'RAB tidak dapat dihapus pada kegiatan yang dibatalkan atau tahun anggaran yang ditutup.',
                ]);
            }

            $desc = $budgetPlan->description;
            $budgetPlan->delete();

            ActivityLog::log('delete_rab', 'Rencana Anggaran', "Menghapus item RAB '{$desc}' dari kegiatan {$activity->activity_code}", $activity);
        });
    }
}
