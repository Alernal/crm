<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalParameter extends Model
{
    protected $table = 'global_parameters';

    protected $fillable = ['key', 'value', 'type', 'label', 'description', 'group', 'editable'];

    protected $casts = ['editable' => 'boolean'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $param = static::where('key', $key)->first();
        return $param ? $param->value : $default;
    }
}
