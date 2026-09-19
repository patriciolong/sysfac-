<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CajaDesglose extends Model
{
    protected $table = 'caja_desgloses';

    protected $fillable = [
        'caja_turno_id',
        'caja_arqueo_id',
        'tipo_operacion',
        'tipo_moneda',
        'denominacion',
        'cantidad',
        'subtotal',
    ];

    public function turno()
    {
        return $this->belongsTo(CajaTurno::class, 'caja_turno_id');
    }

    public function arqueo()
    {
        return $this->belongsTo(CajaArqueo::class, 'caja_arqueo_id');
    }
}
