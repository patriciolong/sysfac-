<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacturaPago extends Model
{
    protected $table = 'ventas_pagos_factura';
    public $timestamps = false;

    protected $fillable = [
        'factura_id',
        'metodo_pago_id',
        'total',
        'plazo',
        'unidad_tiempo'
    ];

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }
}
