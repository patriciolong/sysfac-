<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacturaDetalle extends Model
{
    protected $table = 'ventas_detalles_factura';
    public $timestamps = false;

    protected $fillable = [
        'factura_id',
        'producto_id',
        'codigo_principal',
        'codigo_auxiliar',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'descuento',
        'precio_total_sin_impuestos',
        'codigo_impuesto_iva',
        'tarifa_iva',
        'base_imponible_iva',
        'valor_iva'
    ];

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
