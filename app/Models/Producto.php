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
        'estado',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function inventarios()
    {
        return $this->hasMany(InventarioGeneral::class, 'producto_id');
    }

    public function movimientoDetalles()
    {
        return $this->hasMany(MovimientoDetalle::class, 'producto_id');
    }

    public function compraDetalles()
    {
        return $this->hasMany(CompraDetalle::class, 'producto_id');
    }

    public function facturaDetalles()
    {
        return $this->hasMany(FacturaDetalle::class, 'producto_id');
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
        if ($this->codigo_iva === '4') {
            return 15.0;
        } elseif ($this->codigo_iva === '5') {
            return 5.0;
        } elseif ($this->codigo_iva === '2') {
            return 12.0;
        }

        return 0.0;
    }

    public function getIvaTextoAttribute()
    {
        if ($this->codigo_iva === '4') {
            return '15%';
        } elseif ($this->codigo_iva === '5') {
            return '5%';
        } elseif ($this->codigo_iva === '2') {
            return '12%';
        }

        return '0%';
    }

    public function getPrecioConIvaAttribute()
    {
        $tarifa = $this->tarifa_iva_porcentaje;

        return round($this->precio_unitario * (1 + ($tarifa / 100)), 2);
    }

    public function getMargenGananciaAttribute()
    {
        return (float) max(0, $this->precio_unitario - ($this->costo_promedio ?? 0));
    }

    public function getMargenPorcentajeAttribute()
    {
        $costo = (float) ($this->costo_promedio ?? 0);
        if ($costo <= 0) {
            return 100.0;
        }

        return round((($this->precio_unitario - $costo) / $costo) * 100, 1);
    }

    public function getValorInventarioCostoAttribute()
    {
        return (float) round($this->stock_total * ($this->costo_promedio ?? 0), 2);
    }

    public function getValorInventarioVentaAttribute()
    {
        return (float) round($this->stock_total * $this->precio_unitario, 2);
    }

    public function getIconoAttribute()
    {
        $cat = strtolower($this->categoria->nombre ?? '');
        if (str_contains($cat, 'suplement')) {
            return 'fa-bottle-droplet';
        }
        if (str_contains($cat, 'vitamin')) {
            return 'fa-pills';
        }
        if (str_contains($cat, 'jarabe')) {
            return 'fa-prescription-bottle';
        }
        if (str_contains($cat, 'natural') || str_contains($cat, 'miel')) {
            return 'fa-jar';
        }
        if (str_contains($cat, 'personal') || str_contains($cat, 'jabon')) {
            return 'fa-soap';
        }
        if (str_contains($cat, 'infusion') || str_contains($cat, 'te')) {
            return 'fa-mug-hot';
        }

        return 'fa-box-open';
    }
}
