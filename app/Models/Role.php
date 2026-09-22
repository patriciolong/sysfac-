<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
    ];

    public function permisos(): HasMany
    {
        return $this->hasMany(RolePermiso::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
