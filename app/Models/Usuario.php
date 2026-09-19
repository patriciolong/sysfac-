<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuarios_usuario';

    public $timestamps = false;

    protected $fillable = [
        'rol_id',
        'punto_emision_id',
        'identificacion',
        'nombres',
        'apellidos',
        'correo',
        'password_hash',
        'estado',
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function puntoEmision()
    {
        return $this->belongsTo(PuntoEmision::class, 'punto_emision_id');
    }

    public function getNombreCompletoAttribute()
    {
        return "{$this->nombres} {$this->apellidos}";
    }
}
