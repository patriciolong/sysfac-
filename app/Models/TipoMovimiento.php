<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoMovimiento extends Model
{
    protected $table = 'inventario_tipos_movimiento';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'naturaleza',
        'factor',
        'descripcion'
    ];

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'tipo_movimiento_id');
    }
}
