<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $table = 'compras_facturas';

    protected $fillable = [
        'proveedor_id',
        'bodega_id',
        'usuario_id',
        'movimiento_id',
        'numero_factura',
        'fecha_emision',
        'subtotal_sin_impuestos',
        'iva',
        'total',
        'observaciones'
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'bodega_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function detalles()
    {
        return $this->hasMany(CompraDetalle::class, 'compra_id');
    }
}
