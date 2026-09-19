<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bodega extends Model
{
    protected $table = 'inventario_bodegas';

    public $timestamps = true;

    protected $fillable = [
        'codigo',
        'nombre',
        'ubicacion',
        'descripcion',
        'responsable',
        'telefono',
        'estado',
        'es_principal',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
    ];

    public function inventarios()
    {
        return $this->hasMany(InventarioGeneral::class, 'bodega_id');
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'bodega_id');
    }

    public function compras()
    {
        return $this->hasMany(Compra::class, 'bodega_id');
    }

    public function getTotalStockAttribute(): float
    {
        return (float) $this->inventarios()->sum('stock_actual');
    }

    public function getTotalProductosAttribute(): int
    {
        return (int) $this->inventarios()->where('stock_actual', '>', 0)->count();
    }

    public function getTotalItemsRegistradosAttribute(): int
    {
        return (int) $this->inventarios()->count();
    }

    public function getValorInventarioCostoAttribute(): float
    {
        return (float) $this->inventarios()
            ->join('inventario_productos', 'inventario_general.producto_id', '=', 'inventario_productos.id')
            ->selectRaw('SUM(inventario_general.stock_actual * inventario_productos.costo_promedio) as total')
            ->value('total') ?? 0.0;
    }

    public function getValorInventarioVentaAttribute(): float
    {
        return (float) $this->inventarios()
            ->join('inventario_productos', 'inventario_general.producto_id', '=', 'inventario_productos.id')
            ->selectRaw('SUM(inventario_general.stock_actual * inventario_productos.precio_unitario) as total')
            ->value('total') ?? 0.0;
    }

    public function getIsDeletableAttribute(): bool
    {
        if ($this->es_principal) {
            return false;
        }

        if (self::count() <= 1) {
            return false;
        }

        if ($this->total_stock > 0) {
            return false;
        }

        if ($this->movimientos()->exists()) {
            return false;
        }

        if ($this->compras()->exists()) {
            return false;
        }

        return true;
    }
}
