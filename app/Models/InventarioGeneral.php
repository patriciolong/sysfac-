<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioGeneral extends Model
{
    protected $table = 'inventario_general';
    public $timestamps = false;

    protected $fillable = [
        'bodega_id',
        'producto_id',
        'stock_actual',
        'stock_minimo',
        'stock_maximo',
        'ultima_actualizacion'
    ];

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'bodega_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
