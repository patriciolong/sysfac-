<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CajaArqueo extends Model
{
    use SoftDeletes;

    protected $table = 'caja_arqueos';

    protected $fillable = [
        'caja_turno_id',
        'usuario_id',
        'fecha_hora',
        'total_billetes',
        'total_monedas',
        'efectivo_contado',
        'saldo_teorico',
        'diferencia',
        'tipo_arqueo',
        'observaciones',
        'estado'
    ];

    public function turno()
    {
        return $this->belongsTo(CajaTurno::class, 'caja_turno_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function desgloses()
    {
        return $this->hasMany(CajaDesglose::class, 'caja_arqueo_id');
    }
}
