<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function budgetPlans()
    {
        return $this->hasMany(BudgetPlan::class);
    }

    public function realizations()
    {
        return $this->hasMany(Realization::class);
    }
}
