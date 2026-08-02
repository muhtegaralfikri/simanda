<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_year_id',
        'unit_id',
        'program_id',
        'person_in_charge_id',
        'funding_source_id',
        'activity_code',
        'activity_name',
        'description',
        'start_date',
        'end_date',
        'location',
        'target',
        'budget_ceiling',
        'progress_percentage',
        'progress_note',
        'submission_status',
        'submitted_at',
        'submitted_by',
        'verification_round',
        'review_started_at',
        'review_started_by',
        'remaining_budget_note',
        'closing_note',
        'completed_at',
        'completed_by',
        'status',
        'cancellation_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'submitted_at' => 'datetime',
        'review_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'budget_ceiling' => 'integer',
        'progress_percentage' => 'integer',
        'verification_round' => 'integer',
    ];

    public function budgetYear(): BelongsTo
    {
        return $this->belongsTo(BudgetYear::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function personInCharge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'person_in_charge_id');
    }

    public function fundingSource(): BelongsTo
    {
        return $this->belongsTo(FundingSource::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function budgetPlans(): HasMany
    {
        return $this->hasMany(BudgetPlan::class);
    }

    public function realizations(): HasMany
    {
        return $this->hasMany(Realization::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ActivityDocument::class);
    }

    public function verifications(): MorphMany
    {
        return $this->morphMany(Verification::class, 'verifiable');
    }

    public function progressLogs(): HasMany
    {
        return $this->hasMany(ActivityProgressLog::class)->orderBy('created_at', 'desc');
    }

    // Calculated properties for RAB & Planning
    public function getTotalBudgetPlanAttribute(): int
    {
        return (int) $this->budgetPlans()->sum('total');
    }

    public function getRemainingCeilingAttribute(): int
    {
        return $this->budget_ceiling - $this->total_budget_plan;
    }

    public function getRabPercentageAttribute(): float
    {
        if ($this->budget_ceiling <= 0) {
            return 0.0;
        }

        return round(($this->total_budget_plan / $this->budget_ceiling) * 100, 2);
    }

    // Execution & Realization properties
    public function getActiveRealizationTotalAttribute(): int
    {
        return (int) $this->realizations()
            ->whereIn('status', ['draft', 'submitted', 'verified', 'revision'])
            ->sum('gross_amount');
    }

    public function getVerifiedRealizationTotalAttribute(): int
    {
        return (int) $this->realizations()
            ->where('status', 'verified')
            ->sum('gross_amount');
    }

    public function getRejectedRealizationTotalAttribute(): int
    {
        return (int) $this->realizations()
            ->where('status', 'rejected')
            ->sum('gross_amount');
    }

    public function getRemainingBudgetAttribute(): int
    {
        return max(0, $this->budget_ceiling - $this->active_realization_total);
    }

    public function getFinalRemainingBudgetAttribute(): int
    {
        return max(0, $this->budget_ceiling - $this->verified_realization_total);
    }

    public function getRealizationPercentageAttribute(): float
    {
        if ($this->budget_ceiling <= 0) {
            return 0.0;
        }

        return round(($this->active_realization_total / $this->budget_ceiling) * 100, 2);
    }

    // Document completeness calculation
    public function getDocumentCompletenessAttribute(): array
    {
        $requiredDocTypes = DocumentType::where('is_active', true)->where('is_required', true)->get();
        $totalRequired = $requiredDocTypes->count();

        if ($totalRequired === 0) {
            return [
                'total_required' => 0,
                'fulfilled_required' => 0,
                'valid_required' => 0,
                'unfulfilled_required' => 0,
                'percentage' => 100.0,
                'valid_percentage' => 100.0,
            ];
        }

        $uploadedDocTypeIds = $this->documents()
            ->where('is_current', true)
            ->whereIn('status', ['uploaded', 'submitted', 'valid', 'under_review'])
            ->pluck('document_type_id')
            ->unique();

        $validDocTypeIds = $this->documents()
            ->where('is_current', true)
            ->where('status', 'valid')
            ->pluck('document_type_id')
            ->unique();

        $fulfilledCount = 0;
        $validCount = 0;
        foreach ($requiredDocTypes as $dt) {
            if ($uploadedDocTypeIds->contains($dt->id)) {
                $fulfilledCount++;
            }
            if ($validDocTypeIds->contains($dt->id)) {
                $validCount++;
            }
        }

        return [
            'total_required' => $totalRequired,
            'fulfilled_required' => $fulfilledCount,
            'valid_required' => $validCount,
            'unfulfilled_required' => $totalRequired - $fulfilledCount,
            'percentage' => round(($fulfilledCount / $totalRequired) * 100, 1),
            'valid_percentage' => round(($validCount / $totalRequired) * 100, 1),
        ];
    }

    public function isClosedOrLocked(): bool
    {
        return $this->status === 'completed' || ($this->budgetYear && $this->budgetYear->is_closed);
    }

    public function isReadOnly(): bool
    {
        return $this->isClosedOrLocked() || $this->status === 'cancelled';
    }
}
