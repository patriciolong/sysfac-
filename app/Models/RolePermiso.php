<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermiso extends Model
{
    protected $fillable = [
        'role_id',
        'modulo',
        'nivel',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
