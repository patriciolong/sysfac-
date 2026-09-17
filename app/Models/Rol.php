<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'usuarios_roles';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'permisos_json'
    ];

    protected $casts = [
        'permisos_json' => 'array'
    ];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'rol_id');
    }
}
