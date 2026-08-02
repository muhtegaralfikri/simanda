<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Realization extends Model
{
    protected $fillable = [
        'activity_id',
        'budget_plan_id',
        'expense_type_id',
        'transaction_date',
        'receipt_number',
        'recipient_name',
        'vendor_name',
        'gross_amount',
        'tax_amount',
        'net_amount',
        'payment_method',
        'description',
        'status',
        'submitted_at',
        'created_by',
        'updated_by',
        'verified_by',
        'verified_at',
        'verification_note',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'gross_amount' => 'integer',
        'tax_amount' => 'integer',
        'net_amount' => 'integer',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function budgetPlan()
    {
        return $this->belongsTo(BudgetPlan::class);
    }

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function documents()
    {
        return $this->hasMany(ActivityDocument::class);
    }

    public function verifications()
    {
        return $this->morphMany(Verification::class, 'verifiable');
    }
}
