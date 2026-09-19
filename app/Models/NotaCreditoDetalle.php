<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaCreditoDetalle extends Model
{
    protected $table = 'ventas_detalles_nc';

    public $timestamps = false;

    protected $fillable = [
        'nota_credito_id',
        'producto_id',
        'codigo_interno',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'descuento',
        'precio_total_sin_impuestos',
        'codigo_impuesto_iva',
        'tarifa_iva',
        'base_imponible_iva',
        'valor_iva',
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
        'precio_unitario' => 'decimal:4',
        'descuento' => 'decimal:2',
        'precio_total_sin_impuestos' => 'decimal:2',
        'tarifa_iva' => 'decimal:2',
        'base_imponible_iva' => 'decimal:2',
        'valor_iva' => 'decimal:2',
    ];

    public function notaCredito()
    {
        return $this->belongsTo(NotaCredito::class, 'nota_credito_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
