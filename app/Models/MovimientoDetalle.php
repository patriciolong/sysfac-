<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoDetalle extends Model
{
    protected $table = 'inventario_movimientos_detalles';

    public $timestamps = false;

    protected $fillable = [
        'movimiento_id',
        'producto_id',
        'cantidad',
        'costo_unitario',
        'costo_total',
    ];

    public function movimiento()
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
