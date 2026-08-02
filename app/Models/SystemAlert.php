<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemAlert extends Model
{
    protected $fillable = [
        'user_id',
        'alert_type',
        'severity',
        'subject_type',
        'subject_id',
        'title',
        'message',
        'action_url',
        'due_date',
        'unique_key',
        'read_at',
        'resolved_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'read_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }
}
