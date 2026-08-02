<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityDocument extends Model
{
    protected $fillable = [
        'activity_id',
        'document_type_id',
        'realization_id',
        'original_name',
        'stored_name',
        'file_path',
        'file_size',
        'mime_type',
        'status',
        'version',
        'is_current',
        'uploaded_by',
        'uploaded_at',
        'updated_by',
        'verified_by',
        'verified_at',
        'verification_note',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'version' => 'integer',
        'is_current' => 'boolean',
        'uploaded_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function realization()
    {
        return $this->belongsTo(Realization::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verifications()
    {
        return $this->morphMany(Verification::class, 'verifiable');
    }
}
