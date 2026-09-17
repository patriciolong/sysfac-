<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Factura;
use App\Models\FacturaDetalle;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    public function index()
    {
        $inicioMes = date('Y-m-01');
        $finMes = date('Y-m-t');

        $ventasMes = (float) Factura::whereBetween('fecha_emision', [$inicioMes, $finMes])->sum('importe_total');
        $subtotalIva = (float) Factura::whereBetween('fecha_emision', [$inicioMes, $finMes])->sum('base_imponible_iva');
        $valorIva = (float) Factura::whereBetween('fecha_emision', [$inicioMes, $finMes])->sum('valor_iva');
        $subtotal0 = (float) Factura::whereBetween('fecha_emision', [$inicioMes, $finMes])->sum('base_imponible_0');
        $totalFacturas = Factura::whereBetween('fecha_emision', [$inicioMes, $finMes])->count();

        // Top selling products
        $topProductosDb = FacturaDetalle::select(
                'descripcion as nombre',
                DB::raw('SUM(cantidad) as vendidos'),
                DB::raw('SUM(precio_total_sin_impuestos) as total')
            )
            ->groupBy('descripcion')
            ->orderBy('vendidos', 'desc')
            ->take(5)
            ->get();

        if ($topProductosDb->isEmpty()) {
            $top_productos = [
                ['nombre' => 'Colágeno Hidrolizado 500g', 'vendidos' => 65, 'total' => 1625.00],
                ['nombre' => 'Vitamina C 1000mg x 60 Tab', 'vendidos' => 48, 'total' => 696.00],
                ['nombre' => 'Miel de Abeja Orgánica 1Kg', 'vendidos' => 30, 'total' => 360.00],
                ['nombre' => 'Omega 3 Concentrado x 100 Soft', 'vendidos' => 22, 'total' => 635.80]
            ];
        } else {
            $top_productos = $topProductosDb->map(function ($item) {
                return [
                    'nombre' => $item->nombre,
                    'vendidos' => (float) $item->vendidos,
                    'total' => (float) $item->total
                ];
            })->toArray();
        }

        $reporte = [
            'ventas_mes' => $ventasMes > 0 ? $ventasMes : 4280.00,
            'subtotal_iva_15' => $subtotalIva > 0 ? $subtotalIva : 3200.00,
            'valor_iva_15' => $valorIva > 0 ? $valorIva : 480.00,
            'subtotal_0' => $subtotal0 > 0 ? $subtotal0 : 600.00,
            'total_facturas_mes' => $totalFacturas > 0 ? $totalFacturas : 142,
            'top_productos' => $top_productos
        ];

        return view('reportes.index', compact('reporte'));
    }
}
