<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bodega extends Model
{
    protected $table = 'inventario_bodegas';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'ubicacion'
    ];

    public function inventarios()
    {
        return $this->hasMany(InventarioGeneral::class, 'bodega_id');
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'bodega_id');
    }
}
