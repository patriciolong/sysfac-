<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CajaTurno extends Model
{
    protected $table = 'caja_turnos';

    protected $fillable = [
        'punto_emision_id',
        'caja_id',
        'usuario_id',
        'fecha_apertura',
        'monto_inicial',
        'ventas_efectivo',
        'ventas_tarjetas',
        'ventas_transferencia',
        'total_ventas',
        'efectivo_esperado',
        'efectivo_real',
        'diferencia',
        'fecha_cierre',
        'observaciones',
        'estado',
    ];

    public function puntoEmision()
    {
        return $this->belongsTo(PuntoEmision::class, 'punto_emision_id');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function movimientos()
    {
        return $this->hasMany(CajaMovimiento::class, 'caja_turno_id');
    }

    public function arqueos()
    {
        return $this->hasMany(CajaArqueo::class, 'caja_turno_id');
    }

    public function desgloses()
    {
        return $this->hasMany(CajaDesglose::class, 'caja_turno_id');
    }

    public static function getTurnoActivo()
    {
        return self::where('estado', 'ABIERTA')->latest('id')->first();
    }
}
