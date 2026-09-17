<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    protected $table = 'inventario_movimientos';
    public $timestamps = false;

    protected $fillable = [
        'bodega_id',
        'tipo_movimiento_id',
        'usuario_id',
        'fecha_movimiento',
        'referencia',
        'observaciones'
    ];

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'bodega_id');
    }

    public function tipoMovimiento()
    {
        return $this->belongsTo(TipoMovimiento::class, 'tipo_movimiento_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function detalles()
    {
        return $this->hasMany(MovimientoDetalle::class, 'movimiento_id');
    }
}
