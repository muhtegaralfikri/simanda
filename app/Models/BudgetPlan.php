<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetPlan extends Model
{
    protected $fillable = [
        'activity_id',
        'expense_type_id',
        'account_code',
        'description',
        'volume',
        'unit',
        'unit_price',
        'total',
        'notes',
    ];

    protected $casts = [
        'volume' => 'integer',
        'unit_price' => 'integer',
        'total' => 'integer',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class);
    }

    public function realizations()
    {
        return $this->hasMany(Realization::class);
    }
}
