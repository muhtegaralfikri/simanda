<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $fillable = [
        'budget_year_id',
        'unit_id',
        'program_code',
        'program_name',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function budgetYear()
    {
        return $this->belongsTo(BudgetYear::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
