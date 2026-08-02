<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityLog;
use App\Models\BudgetPlan;
use App\Models\Realization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RealizationService
{
    public function storeRealization(Activity $activity, array $data, User $user): Realization
    {
        return DB::transaction(function () use ($activity, $data, $user) {
            if ($activity->status !== 'ongoing' || $activity->budgetYear->is_closed) {
                throw ValidationException::withMessages([
                    'activity_id' => 'Realisasi hanya dapat dicatat pada kegiatan yang sedang berjalan.',
                ]);
            }

            $budgetPlan = BudgetPlan::find($data['budget_plan_id']);
            if (! $budgetPlan || $budgetPlan->activity_id !== $activity->id) {
                throw ValidationException::withMessages([
                    'budget_plan_id' => 'Rincian RAB tidak ditemukan atau tidak sesuai dengan kegiatan ini.',
                ]);
            }

            $grossAmount = (int) $data['gross_amount'];
            $taxAmount = isset($data['tax_amount']) ? (int) $data['tax_amount'] : 0;

            if ($grossAmount <= 0) {
                throw ValidationException::withMessages([
                    'gross_amount' => 'Nilai bruto realisasi harus lebih besar dari Rp 0.',
                ]);
            }

            if ($taxAmount < 0 || $taxAmount > $grossAmount) {
                throw ValidationException::withMessages([
                    'tax_amount' => 'Nilai pajak tidak boleh negatif atau melebihi nilai bruto.',
                ]);
            }

            $netAmount = $grossAmount - $taxAmount;

            // Check Budget Plan limit
            $currentRabActiveRealization = Realization::where('budget_plan_id', $budgetPlan->id)
                ->whereIn('status', ['draft', 'submitted', 'verified'])
                ->sum('gross_amount');

            $newRabRealization = $currentRabActiveRealization + $grossAmount;
            if ($newRabRealization > $budgetPlan->total) {
                $selisih = $newRabRealization - $budgetPlan->total;
                throw ValidationException::withMessages([
                    'gross_amount' => 'Nilai realisasi (Rp '.number_format($grossAmount, 0, ',', '.').') menyebabkan alokasi RAB terlampaui sebesar Rp '.number_format($selisih, 0, ',', '.').'. (Sisa RAB: Rp '.number_format($budgetPlan->total - $currentRabActiveRealization, 0, ',', '.').')',
                ]);
            }

            // Check Activity Ceiling limit
            $currentActivityActiveRealization = $activity->active_realization_total;
            $newActivityRealization = $currentActivityActiveRealization + $grossAmount;
            if ($newActivityRealization > $activity->budget_ceiling) {
                throw ValidationException::withMessages([
                    'gross_amount' => 'Total realisasi akan melebihi Pagu Anggaran Kegiatan (Rp '.number_format($activity->budget_ceiling, 0, ',', '.').').',
                ]);
            }

            $realization = Realization::create([
                'activity_id' => $activity->id,
                'budget_plan_id' => $budgetPlan->id,
                'expense_type_id' => $budgetPlan->expense_type_id,
                'transaction_date' => $data['transaction_date'],
                'receipt_number' => $data['receipt_number'],
                'recipient_name' => $data['recipient_name'] ?? null,
                'vendor_name' => $data['vendor_name'] ?? null,
                'gross_amount' => $grossAmount,
                'tax_amount' => $taxAmount,
                'net_amount' => $netAmount,
                'payment_method' => $data['payment_method'] ?? 'transfer',
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'created_by' => $user->id,
            ]);

            ActivityLog::log('create_realization', 'Realisasi', "Mencatat realisasi draft Rp ".number_format($grossAmount, 0, ',', '.')." (No Bukti: {$realization->receipt_number}) untuk kegiatan {$activity->activity_code}", $activity);

            return $realization;
        });
    }

    public function updateRealization(Realization $realization, array $data, User $user): Realization
    {
        return DB::transaction(function () use ($realization, $data, $user) {
            $activity = $realization->activity;

            if ($activity->status !== 'ongoing' || $activity->budgetYear->is_closed || $realization->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya realisasi berstatus Draft pada kegiatan yang sedang berjalan yang dapat diubah.',
                ]);
            }

            $budgetPlan = BudgetPlan::find($data['budget_plan_id']);
            if (! $budgetPlan || $budgetPlan->activity_id !== $activity->id) {
                throw ValidationException::withMessages([
                    'budget_plan_id' => 'Rincian RAB tidak ditemukan.',
                ]);
            }

            $grossAmount = (int) $data['gross_amount'];
            $taxAmount = isset($data['tax_amount']) ? (int) $data['tax_amount'] : 0;

            if ($grossAmount <= 0 || $taxAmount < 0 || $taxAmount > $grossAmount) {
                throw ValidationException::withMessages([
                    'gross_amount' => 'Nominal bruto dan pajak tidak valid.',
                ]);
            }

            $netAmount = $grossAmount - $taxAmount;

            // Check Budget Plan Limit excluding current realization
            $otherRabRealization = Realization::where('budget_plan_id', $budgetPlan->id)
                ->where('id', '!=', $realization->id)
                ->whereIn('status', ['draft', 'submitted', 'verified'])
                ->sum('gross_amount');

            if (($otherRabRealization + $grossAmount) > $budgetPlan->total) {
                throw ValidationException::withMessages([
                    'gross_amount' => 'Nilai realisasi melebihi alokasi sisa RAB.',
                ]);
            }

            $realization->update([
                'budget_plan_id' => $budgetPlan->id,
                'expense_type_id' => $budgetPlan->expense_type_id,
                'transaction_date' => $data['transaction_date'],
                'receipt_number' => $data['receipt_number'],
                'recipient_name' => $data['recipient_name'] ?? null,
                'vendor_name' => $data['vendor_name'] ?? null,
                'gross_amount' => $grossAmount,
                'tax_amount' => $taxAmount,
                'net_amount' => $netAmount,
                'payment_method' => $data['payment_method'] ?? 'transfer',
                'description' => $data['description'] ?? null,
                'updated_by' => $user->id,
            ]);

            ActivityLog::log('update_realization', 'Realisasi', "Memperbarui realisasi draft Rp ".number_format($grossAmount, 0, ',', '.')." (No Bukti: {$realization->receipt_number})", $activity);

            return $realization;
        });
    }

    public function deleteRealization(Realization $realization, User $user): void
    {
        DB::transaction(function () use ($realization, $user) {
            $activity = $realization->activity;

            if ($activity->status !== 'ongoing' || $activity->budgetYear->is_closed || $realization->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya realisasi draft yang dapat dihapus.',
                ]);
            }

            $receipt = $realization->receipt_number;
            $amount = $realization->gross_amount;

            $realization->delete();

            ActivityLog::log('delete_realization', 'Realisasi', "Menghapus realisasi draft Rp ".number_format($amount, 0, ',', '.')." (No Bukti: {$receipt})", $activity);
        });
    }

    public function submitRealization(Realization $realization, User $user): void
    {
        DB::transaction(function () use ($realization, $user) {
            $activity = $realization->activity;

            if ($activity->status !== 'ongoing' || $activity->budgetYear->is_closed || $realization->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya realisasi berstatus Draft yang dapat diajukan.',
                ]);
            }

            $realization->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'updated_by' => $user->id,
            ]);

            ActivityLog::log('submit_realization', 'Realisasi', "Mengajukan realisasi Rp ".number_format($realization->gross_amount, 0, ',', '.')." (No Bukti: {$realization->receipt_number}) untuk verifikasi", $activity);
        });
    }
}
