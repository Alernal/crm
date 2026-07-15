<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VirtualFolder extends Model
{
    protected $fillable = [
        'user_id', 'client_id', 'parent_id', 'name', 'path', 'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(VirtualFolder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(VirtualFolder::class, 'parent_id')->orderBy('name');
    }

    public function files(): HasMany
    {
        return $this->hasMany(VirtualFile::class, 'folder_id');
    }

    public function breadcrumbs(): array
    {
        $crumbs = [];
        $folder = $this;
        while ($folder) {
            array_unshift($crumbs, $folder);
            $folder = $folder->parent;
        }
        return $crumbs;
    }
}
