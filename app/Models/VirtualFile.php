<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VirtualFile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'client_id', 'folder_id',
        'original_filename', 'storage_filename', 'file_path',
        'mime_type', 'file_size',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(VirtualFolder::class, 'folder_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(VirtualFileLog::class);
    }

    public function isPreviewable(): bool
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        ]);
    }

    public function humanSize(): string
    {
        $bytes = $this->file_size;
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->original_filename, PATHINFO_EXTENSION));
    }
}
