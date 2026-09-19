<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'apellido', 'email', 'password', 'estado', 'ultimo_acceso', 'role_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function permisos(): HasMany
    {
        return $this->hasMany(UserPermiso::class);
    }

    /**
     * Comprueba si el usuario tiene permiso sobre un módulo específico
     */
    public function hasPermission(string $modulo, string $nivelRequerido = 'lectura'): bool
    {
        // Administrador principal tiene acceso a todo.
        // Asumimos que el usuario ID 1 o con rol 'Administrador' es superadmin.
        if ($this->id === 1 || ($this->role && $this->role->nombre === 'Administrador')) {
            return true;
        }

        if ($this->estado !== 'activo') {
            return false;
        }

        // Buscar permiso específico del usuario
        $userPermiso = $this->permisos()->where('modulo', $modulo)->first();

        if ($userPermiso) {
            $nivelUsuario = $userPermiso->nivel;
        } else {
            // Si no tiene permiso específico, buscar el permiso del rol
            if (! $this->role || $this->role->estado !== 'activo') {
                return false;
            }

            $rolePermiso = $this->role->permisos()->where('modulo', $modulo)->first();
            $nivelUsuario = $rolePermiso ? $rolePermiso->nivel : 'ninguno';
        }

        if ($nivelUsuario === 'ninguno') {
            return false;
        }

        if ($nivelRequerido === 'lectura') {
            return in_array($nivelUsuario, ['lectura', 'master']);
        }

        if ($nivelRequerido === 'master') {
            return $nivelUsuario === 'master';
        }

        return false;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
