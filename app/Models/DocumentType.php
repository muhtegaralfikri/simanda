<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'stage',
        'is_required',
        'allowed_extensions',
        'maximum_size',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'maximum_size' => 'integer',
    ];

    public function activityDocuments()
    {
        return $this->hasMany(ActivityDocument::class);
    }
}
