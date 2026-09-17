<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'inventario_productos';
    public $timestamps = false;

    protected $fillable = [
        'categoria_id',
        'codigo_principal',
        'codigo_auxiliar',
        'nombre',
        'tipo_producto',
        'precio_unitario',
        'costo_promedio',
        'codigo_iva',
        'codigo_ice',
        'tiene_irbpnr',
        'estado'
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function inventarios()
    {
        return $this->hasMany(InventarioGeneral::class, 'producto_id');
    }

    public function getStockTotalAttribute()
    {
        return (float) $this->inventarios()->sum('stock_actual');
    }

    public function getStockMinimoTotalAttribute()
    {
        return (float) ($this->inventarios()->first()->stock_minimo ?? 5);
    }

    public function getEstadoStockAttribute()
    {
        $stock = $this->stock_total;
        $min = $this->stock_minimo_total;

        if ($stock <= 0) {
            return 'AGOTADO';
        } elseif ($stock <= $min) {
            return 'STOCK_BAJO';
        }
        return 'EN_STOCK';
    }

    public function getTarifaIvaPorcentajeAttribute()
    {
        return $this->codigo_iva === '2' ? 15.0 : 0.0;
    }

    public function getIvaTextoAttribute()
    {
        return $this->codigo_iva === '2' ? '15%' : '0%';
    }

    public function getIconoAttribute()
    {
        $cat = strtolower($this->categoria->nombre ?? '');
        if (str_contains($cat, 'suplement')) return 'fa-bottle-droplet';
        if (str_contains($cat, 'vitamin')) return 'fa-pills';
        if (str_contains($cat, 'jarabe')) return 'fa-prescription-bottle';
        if (str_contains($cat, 'natural') || str_contains($cat, 'miel')) return 'fa-jar';
        if (str_contains($cat, 'personal') || str_contains($cat, 'jabon')) return 'fa-soap';
        if (str_contains($cat, 'infusion') || str_contains($cat, 'te')) return 'fa-mug-hot';
        return 'fa-box-open';
    }
}
