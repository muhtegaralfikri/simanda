<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupHistory extends Model
{
    protected $fillable = [
        'backup_type',
        'status',
        'started_at',
        'completed_at',
        'database_size',
        'document_count',
        'document_size',
        'backup_path_reference',
        'message',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'database_size' => 'integer',
        'document_count' => 'integer',
        'document_size' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
