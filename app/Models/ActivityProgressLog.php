<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityProgressLog extends Model
{
    protected $fillable = [
        'activity_id',
        'progress_percentage',
        'note',
        'updated_by',
    ];

    protected $casts = [
        'progress_percentage' => 'integer',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
