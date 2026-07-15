<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminRole extends Model
{
    protected $table = 'admin_roles';

    protected $fillable = ['name', 'slug', 'description', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function permissions(): HasMany
    {
        return $this->hasMany(AdminPermission::class, 'role_id');
    }
}
