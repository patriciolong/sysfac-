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
        'observaciones',
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

    public function movimiento()
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_id');
    }

    public function notasCredito()
    {
        return $this->hasMany(CompraNotaCredito::class, 'compra_id');
    }

    public function getNombreUsuarioAttribute()
    {
        if ($this->usuario) {
            return $this->usuario->nombre_completo;
        }
        $user = User::find($this->usuario_id);

        return $user ? "{$user->name} {$user->apellido}" : 'Usuario Sistema';
    }

    public function getItemsCountAttribute()
    {
        return $this->detalles()->count();
    }

    public function getCantidadTotalArticulosAttribute()
    {
        return (float) $this->detalles()->sum('cantidad');
    }

    public function getNotasCreditoValidasAttribute()
    {
        return $this->notasCredito()->where('estado', 'EMITIDA')->get();
    }

    public function getTotalNotasCreditoAttribute(): float
    {
        return (float) $this->notasCredito()->where('estado', 'EMITIDA')->sum('total');
    }

    public function getSaldoPendienteAttribute(): float
    {
        $saldo = (float) $this->total - $this->total_notas_credito;

        return max(0.0, round($saldo, 2));
    }
}
