<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualFileLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'virtual_file_id', 'filename', 'action', 'details', 'created_at',
    ];

    protected $casts = [
        'details'    => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(VirtualFile::class, 'virtual_file_id');
    }
}
