<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CajaMovimiento extends Model
{
    use SoftDeletes;

    protected $table = 'caja_movimientos';

    protected $fillable = [
        'caja_turno_id',
        'tipo',
        'categoria',
        'concepto',
        'descripcion',
        'monto',
        'metodo_pago_id',
        'usuario_id',
        'venta_id',
        'fecha_hora',
        'comprobante',
        'observaciones',
    ];

    public function turno()
    {
        return $this->belongsTo(CajaTurno::class, 'caja_turno_id');
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function venta()
    {
        return $this->belongsTo(Factura::class, 'venta_id'); // Assuming Factura represents venta
    }
}
