<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Caja extends Model
{
    use SoftDeletes;

    protected $table = 'cajas';

    protected $fillable = [
        'nombre',
        'codigo',
        'sucursal',
        'usuario_id',
        'punto_emision_id',
        'descripcion',
        'estado'
    ];

    public function responsable()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function puntoEmision()
    {
        return $this->belongsTo(PuntoEmision::class, 'punto_emision_id');
    }

    public function turnos()
    {
        return $this->hasMany(CajaTurno::class, 'caja_id');
    }
}
