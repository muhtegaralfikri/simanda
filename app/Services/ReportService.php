<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityDocument;
use App\Models\BudgetPlan;
use App\Models\BudgetYear;
use App\Models\DocumentType;
use App\Models\Realization;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function scopeRoleAccess(Builder $query, User $user): Builder
    {
        if ($user->isPPTK()) {
            return $query->where('person_in_charge_id', $user->id);
        }

        return $query;
    }

    public function getBudgetSummaryReport(array $filters, User $user, bool $paginate = true)
    {
        $query = Activity::with(['unit', 'program', 'fundingSource']);
        $this->scopeRoleAccess($query, $user);
        $this->applyActivityFilters($query, $filters);

        $query->orderBy('activity_code', 'asc');

        return $paginate ? $query->paginate(25) : $query->get();
    }

    public function getRealizationReport(array $filters, User $user, bool $paginate = true)
    {
        $query = Realization::with(['activity.unit', 'budgetPlan', 'expenseType', 'creator', 'verifier']);

        if ($user->isPPTK()) {
            $query->whereHas('activity', function ($q) use ($user) {
                $q->where('person_in_charge_id', $user->id);
            });
        }

        if (! empty($filters['budget_year_id'])) {
            $query->whereHas('activity', function ($q) use ($filters) {
                $q->where('budget_year_id', $filters['budget_year_id']);
            });
        }

        if (! empty($filters['unit_id'])) {
            $query->whereHas('activity', function ($q) use ($filters) {
                $q->where('unit_id', $filters['unit_id']);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['expense_type_id'])) {
            $query->where('expense_type_id', $filters['expense_type_id']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('transaction_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('transaction_date', '<=', $filters['end_date']);
        }

        $query->orderBy('transaction_date', 'desc');

        return $paginate ? $query->paginate(25) : $query->get();
    }

    public function getActivityReport(array $filters, User $user, bool $paginate = true)
    {
        $query = Activity::with(['unit', 'program', 'personInCharge', 'fundingSource']);
        $this->scopeRoleAccess($query, $user);
        $this->applyActivityFilters($query, $filters);

        $query->orderBy('start_date', 'asc');

        return $paginate ? $query->paginate(25) : $query->get();
    }

    public function getProgressReport(array $filters, User $user, bool $paginate = true)
    {
        $query = Activity::with(['unit', 'personInCharge', 'progressLogs.updater']);
        $this->scopeRoleAccess($query, $user);
        $this->applyActivityFilters($query, $filters);

        if (isset($filters['min_progress'])) {
            $query->where('progress_percentage', '>=', $filters['min_progress']);
        }

        if (isset($filters['max_progress'])) {
            $query->where('progress_percentage', '<=', $filters['max_progress']);
        }

        $query->orderBy('progress_percentage', 'desc');

        return $paginate ? $query->paginate(25) : $query->get();
    }

    public function getDocumentReport(array $filters, User $user, bool $paginate = true)
    {
        $query = Activity::with(['unit', 'personInCharge', 'documents.documentType']);
        $this->scopeRoleAccess($query, $user);
        $this->applyActivityFilters($query, $filters);

        $query->orderBy('activity_code', 'asc');

        return $paginate ? $query->paginate(25) : $query->get();
    }

    public function getVerificationReport(array $filters, User $user, bool $paginate = true)
    {
        $query = Verification::with(['verifier', 'verifiable']);

        if ($user->isPPTK()) {
            $userActivityIds = Activity::where('person_in_charge_id', $user->id)->pluck('id')->toArray();
            $userRealizationIds = Realization::whereIn('activity_id', $userActivityIds)->pluck('id')->toArray();
            $userDocIds = ActivityDocument::whereIn('activity_id', $userActivityIds)->pluck('id')->toArray();

            $query->where(function ($q) use ($userActivityIds, $userRealizationIds, $userDocIds) {
                $q->where(function ($q1) use ($userActivityIds) {
                    $q1->where('verifiable_type', 'App\Models\Activity')->whereIn('verifiable_id', $userActivityIds);
                })->orWhere(function ($q2) use ($userRealizationIds) {
                    $q2->where('verifiable_type', 'App\Models\Realization')->whereIn('verifiable_id', $userRealizationIds);
                })->orWhere(function ($q3) use ($userDocIds) {
                    $q3->where('verifiable_type', 'App\Models\ActivityDocument')->whereIn('verifiable_id', $userDocIds);
                });
            });
        }

        if (! empty($filters['decision'])) {
            $query->where('decision', $filters['decision']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('verified_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('verified_at', '<=', $filters['end_date']);
        }

        $query->orderBy('verified_at', 'desc');

        return $paginate ? $query->paginate(25) : $query->get();
    }

    public function getMonthlyAbsorptionReport(array $filters, User $user): array
    {
        $activeYearId = $filters['budget_year_id'] ?? BudgetYear::where('is_active', true)->value('id');
        $query = Activity::query();
        if ($activeYearId) {
            $query->where('budget_year_id', $activeYearId);
        }
        $this->scopeRoleAccess($query, $user);

        if (! empty($filters['unit_id'])) {
            $query->where('unit_id', $filters['unit_id']);
        }

        $activities = $query->get();
        $totalPagu = (int) $activities->sum('budget_ceiling');
        $actIds = $activities->pluck('id')->toArray();

        $realizations = Realization::whereIn('activity_id', $actIds)
            ->where('status', 'verified')
            ->get();

        $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $monthlyRows = [];
        $cumulativeVerified = 0;

        for ($m = 1; $m <= 12; $m++) {
            $monthlyGross = $realizations->filter(function ($rel) use ($m) {
                return $rel->transaction_date && (int) $rel->transaction_date->format('n') === $m;
            })->sum('gross_amount');

            $cumulativeVerified += $monthlyGross;
            $cumulativeAbsorption = $totalPagu > 0 ? round(($cumulativeVerified / $totalPagu) * 100, 2) : 0.0;
            $remaining = max(0, $totalPagu - $cumulativeVerified);

            $monthlyRows[] = [
                'month_name' => $months[$m - 1],
                'month_number' => $m,
                'monthly_verified' => $monthlyGross,
                'cumulative_verified' => $cumulativeVerified,
                'total_pagu' => $totalPagu,
                'cumulative_absorption_percentage' => $cumulativeAbsorption,
                'remaining_budget' => $remaining,
            ];
        }

        return [
            'total_pagu' => $totalPagu,
            'total_verified' => $cumulativeVerified,
            'remaining' => max(0, $totalPagu - $cumulativeVerified),
            'absorption_percentage' => $totalPagu > 0 ? round(($cumulativeVerified / $totalPagu) * 100, 2) : 0.0,
            'rows' => $monthlyRows,
        ];
    }

    public function getRemainingBudgetReport(array $filters, User $user, bool $paginate = true)
    {
        $query = Activity::with(['unit', 'program', 'personInCharge']);
        $this->scopeRoleAccess($query, $user);
        $this->applyActivityFilters($query, $filters);

        $query->orderBy('activity_code', 'asc');

        $result = $paginate ? $query->paginate(25) : $query->get();

        return $result;
    }

    protected function applyActivityFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['budget_year_id'])) {
            $query->where('budget_year_id', $filters['budget_year_id']);
        }
        if (! empty($filters['unit_id'])) {
            $query->where('unit_id', $filters['unit_id']);
        }
        if (! empty($filters['program_id'])) {
            $query->where('program_id', $filters['program_id']);
        }
        if (! empty($filters['funding_source_id'])) {
            $query->where('funding_source_id', $filters['funding_source_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }
    }
}
