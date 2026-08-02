<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityDocument;
use App\Models\BudgetYear;
use App\Models\DocumentType;
use App\Models\FundingSource;
use App\Models\Realization;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    public function getAnalytics(array $filters, User $user): array
    {
        $activeYearId = $filters['budget_year_id'] ?? BudgetYear::where('is_active', true)->value('id');
        if (! $activeYearId) {
            $activeYearId = BudgetYear::latest('year')->value('id');
        }

        // Base Query scoped to role
        $query = Activity::query();

        if ($activeYearId) {
            $query->where('budget_year_id', $activeYearId);
        }

        // Apply Role Scoping
        if ($user->isPPTK()) {
            $query->where('person_in_charge_id', $user->id);
        } elseif ($user->isVerifier()) {
            // Verifiers see submitted/under review/completed/revision activities
            if (! isset($filters['unit_id'])) {
                $query->whereIn('status', ['waiting_verification', 'revision', 'completed']);
            }
        }

        // Apply Filters
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
        if (! empty($filters['person_in_charge_id']) && ! $user->isPPTK()) {
            $query->where('person_in_charge_id', $filters['person_in_charge_id']);
        }
        if (! empty($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }

        $activities = (clone $query)->get();
        $activityIds = $activities->pluck('id')->toArray();

        // 1. Budget Cards
        $totalCeiling = (int) $activities->sum('budget_ceiling');
        $totalRab = (int) DB::table('budget_plans')->whereIn('activity_id', $activityIds)->sum('total');

        $activeRealizations = DB::table('realizations')
            ->whereIn('activity_id', $activityIds)
            ->whereIn('status', ['draft', 'submitted', 'verified', 'revision'])
            ->get();

        $activeRealizationTotal = (int) $activeRealizations->sum('gross_amount');
        $verifiedRealizationTotal = (int) $activeRealizations->where('status', 'verified')->sum('gross_amount');

        $finalRemainingBudget = max(0, $totalCeiling - $verifiedRealizationTotal);
        $absorptionPercentage = $totalCeiling > 0 ? round(($verifiedRealizationTotal / $totalCeiling) * 100, 2) : 0.0;

        // 2. Activity Count Cards
        $statusCounts = [
            'total' => $activities->count(),
            'draft' => $activities->where('status', 'draft')->count(),
            'planned' => $activities->where('status', 'planned')->count(),
            'ongoing' => $activities->where('status', 'ongoing')->count(),
            'waiting_verification' => $activities->where('status', 'waiting_verification')->count(),
            'revision' => $activities->where('status', 'revision')->count(),
            'completed' => $activities->where('status', 'completed')->count(),
            'cancelled' => $activities->where('status', 'cancelled')->count(),
        ];

        // 3. Delayed Activities
        $delayedActivities = (clone $query)
            ->where('end_date', '<', now()->startOfDay())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with(['unit', 'personInCharge'])
            ->get();

        // 4. Action Items for PPTK
        $actionItems = [];
        if ($user->isPPTK()) {
            $plannedNotStarted = $activities->where('status', 'planned');
            foreach ($plannedNotStarted as $act) {
                $actionItems[] = [
                    'type' => 'planned',
                    'title' => "Kegiatan {$act->activity_code} siap dimulai pelaksanaannya",
                    'activity_id' => $act->id,
                    'badge' => 'Perencanaan Selesai',
                ];
            }

            $revisionActivities = $activities->where('status', 'revision');
            foreach ($revisionActivities as $act) {
                $actionItems[] = [
                    'type' => 'revision',
                    'title' => "Kegiatan {$act->activity_code} memerlukan perbaikan revisi",
                    'activity_id' => $act->id,
                    'badge' => 'Perlu Revisi',
                ];
            }

            $ongoingReadyForSubmit = $activities->where('status', 'ongoing')->where('progress_percentage', 100);
            foreach ($ongoingReadyForSubmit as $act) {
                $actionItems[] = [
                    'type' => 'ready_submit',
                    'title' => "Kegiatan {$act->activity_code} sudah 100% progres & siap diajukan",
                    'activity_id' => $act->id,
                    'badge' => 'Siap Diajukan',
                ];
            }
        }

        // 5. Chart Datasets
        // A. Pagu vs Verified Realization per Unit
        $units = Unit::where('is_active', true)->get();
        $unitChartLabels = [];
        $unitCeilingData = [];
        $unitRealizedData = [];

        foreach ($units as $u) {
            $uActivities = $activities->where('unit_id', $u->id);
            if ($uActivities->count() > 0) {
                $unitChartLabels[] = $u->code;
                $uCeiling = $uActivities->sum('budget_ceiling');
                $uActIds = $uActivities->pluck('id')->toArray();
                $uVerified = DB::table('realizations')->whereIn('activity_id', $uActIds)->where('status', 'verified')->sum('gross_amount');

                $unitCeilingData[] = $uCeiling;
                $unitRealizedData[] = $uVerified;
            }
        }

        // B. Monthly Realization (12 months)
        $monthlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $monthlyVerifiedData = array_fill(0, 12, 0);

        $verifiedRealizations = Realization::whereIn('activity_id', $activityIds)
            ->where('status', 'verified')
            ->get();

        foreach ($verifiedRealizations as $rel) {
            if ($rel->transaction_date) {
                $monthIndex = (int) $rel->transaction_date->format('n') - 1;
                if ($monthIndex >= 0 && $monthIndex < 12) {
                    $monthlyVerifiedData[$monthIndex] += $rel->gross_amount;
                }
            }
        }

        // C. Funding Source Distribution
        $fundingSources = FundingSource::where('is_active', true)->get();
        $fundingLabels = [];
        $fundingCeilingData = [];
        $fundingRealizedData = [];

        foreach ($fundingSources as $fs) {
            $fsActivities = $activities->where('funding_source_id', $fs->id);
            if ($fsActivities->count() > 0) {
                $fundingLabels[] = $fs->code;
                $fundingCeilingData[] = $fsActivities->sum('budget_ceiling');
                $fsActIds = $fsActivities->pluck('id')->toArray();
                $fundingRealizedData[] = DB::table('realizations')->whereIn('activity_id', $fsActIds)->where('status', 'verified')->sum('gross_amount');
            }
        }

        return [
            'active_year_id' => $activeYearId,
            'budget_cards' => [
                'total_ceiling' => $totalCeiling,
                'total_rab' => $totalRab,
                'active_realization_total' => $activeRealizationTotal,
                'verified_realization_total' => $verifiedRealizationTotal,
                'final_remaining_budget' => $finalRemainingBudget,
                'absorption_percentage' => $absorptionPercentage,
            ],
            'status_counts' => $statusCounts,
            'delayed_activities' => $delayedActivities,
            'action_items' => $actionItems,
            'charts' => [
                'unit_chart' => [
                    'labels' => $unitChartLabels,
                    'ceilings' => $unitCeilingData,
                    'verified' => $unitRealizedData,
                    'ceiling' => $unitCeilingData,
                    'realized' => $unitRealizedData,
                ],
                'monthly_chart' => [
                    'labels' => $monthlyLabels,
                    'totals' => $monthlyVerifiedData,
                    'verified' => $monthlyVerifiedData,
                ],
                'funding_chart' => [
                    'labels' => $fundingLabels,
                    'ceilings' => $fundingCeilingData,
                    'verified' => $fundingRealizedData,
                    'ceiling' => $fundingCeilingData,
                    'realized' => $fundingRealizedData,
                ],
                'status_chart' => [
                    'labels' => ['Draft', 'Direncanakan', 'Sedang Berjalan', 'Menunggu Verifikasi', 'Perlu Revisi', 'Selesai', 'Dibatalkan'],
                    'data' => [
                        $statusCounts['draft'],
                        $statusCounts['planned'],
                        $statusCounts['ongoing'],
                        $statusCounts['waiting_verification'],
                        $statusCounts['revision'],
                        $statusCounts['completed'],
                        $statusCounts['cancelled'],
                    ],
                ],
            ],
        ];
    }
}
