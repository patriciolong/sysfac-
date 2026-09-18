<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPermiso extends Model
{
    protected $fillable = [
        'user_id',
        'modulo',
        'nivel',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
