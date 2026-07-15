<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AdminLog extends Model
{
    public $timestamps = false;

    protected $table = 'admin_logs';

    protected $fillable = [
        'admin_user', 'action', 'module', 'record_id',
        'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public static function record(string $action, ?string $module = null, mixed $recordId = null, array $old = [], array $new = []): void
    {
        static::create([
            'admin_user' => Auth::user()?->email ?? 'desconocido',
            'action'     => $action,
            'module'     => $module,
            'record_id'  => $recordId ? (string) $recordId : null,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
