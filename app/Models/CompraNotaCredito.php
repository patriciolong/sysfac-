<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompraNotaCredito extends Model
{
    use HasFactory;

    protected $table = 'compras_notas_credito';

    protected $fillable = [
        'compra_id',
        'proveedor_id',
        'bodega_id',
        'usuario_id',
        'movimiento_id',
        'numero_nota_credito',
        'autorizacion_sri',
        'fecha_emision',
        'motivo',
        'tipo_modificacion',
        'subtotal_sin_impuestos',
        'iva',
        'total',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'subtotal_sin_impuestos' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

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

    public function movimiento()
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_id');
    }

    public function detalles()
    {
        return $this->hasMany(CompraNotaCreditoDetalle::class, 'nota_credito_id');
    }

    public function getNombreUsuarioAttribute(): string
    {
        if ($this->usuario) {
            return trim(($this->usuario->nombres ?? '').' '.($this->usuario->apellidos ?? '')) ?: $this->usuario->username ?? 'Usuario';
        }

        return 'Administrador';
    }

    public function getItemsCountAttribute(): int
    {
        return $this->detalles->count();
    }

    public function getCantidadTotalArticulosAttribute(): float
    {
        return (float) $this->detalles->sum('cantidad');
    }

    public function getTipoModificacionTextoAttribute(): string
    {
        return $this->tipo_modificacion === 'DEVOLUCION_MERCADERIA' ? 'Devolución de Mercadería' : 'Descuento / Ajuste de Valor';
    }
}
