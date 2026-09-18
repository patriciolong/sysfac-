<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompraNotaCreditoDetalle extends Model
{
    use HasFactory;

    protected $table = 'compras_notas_credito_detalles';

    public $timestamps = false;

    protected $fillable = [
        'nota_credito_id',
        'producto_id',
        'cantidad',
        'costo_unitario',
        'costo_total',
        'tarifa_iva',
        'iva_total',
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
        'costo_unitario' => 'decimal:4',
        'costo_total' => 'decimal:2',
        'tarifa_iva' => 'decimal:2',
        'iva_total' => 'decimal:2',
    ];

    public function notaCredito()
    {
        return $this->belongsTo(CompraNotaCredito::class, 'nota_credito_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
