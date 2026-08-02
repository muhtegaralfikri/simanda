<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    protected $fillable = [
        'verifier_id',
        'verifiable_type',
        'verifiable_id',
        'decision',
        'notes',
        'round',
        'previous_status',
        'new_status',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'round' => 'integer',
    ];

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }

    public function verifiable()
    {
        return $this->morphTo();
    }
}
