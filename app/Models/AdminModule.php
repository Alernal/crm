<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminModule extends Model
{
    protected $table = 'admin_modules';

    protected $fillable = ['key', 'name', 'description', 'icon', 'active', 'order'];

    protected $casts = ['active' => 'boolean'];

    public function fields(): HasMany
    {
        return $this->hasMany(AdminCustomField::class, 'module_key', 'key');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(AdminPermission::class, 'module_key', 'key');
    }
}
