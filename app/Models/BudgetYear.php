<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetYear extends Model
{
    protected $fillable = [
        'year',
        'name',
        'start_date',
        'end_date',
        'is_active',
        'is_closed',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_closed' => 'boolean',
    ];

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
}
