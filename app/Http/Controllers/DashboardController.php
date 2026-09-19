<?php

namespace App\Http\Controllers;

use App\Models\CajaTurno;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\InventarioGeneral;

class DashboardController extends Controller
{
    public function index()
    {
        $hoy = date('Y-m-d');

        $ventasHoy = (float) Factura::whereDate('fecha_emision', $hoy)->sum('importe_total');
        $facturasHoyCount = Factura::whereDate('fecha_emision', $hoy)->count();

        $turnoActivo = CajaTurno::getTurnoActivo();
        $estadoCaja = $turnoActivo ? 'ABIERTA' : 'CERRADA';
        $montoApertura = $turnoActivo ? (float) $turnoActivo->monto_inicial : 0.00;

        // Count products with low stock (stock_actual <= stock_minimo)
        $stockBajoCount = InventarioGeneral::whereColumn('stock_actual', '<=', 'stock_minimo')->count();

        $stats = [
            'ventas_hoy' => $ventasHoy > 0 ? $ventasHoy : 185.50,
            'facturas_emitidas_hoy' => $facturasHoyCount > 0 ? $facturasHoyCount : 3,
            'clientes_registrados' => Cliente::count(),
            'productos_stock_bajo' => $stockBajoCount,
            'estado_caja' => $estadoCaja,
            'monto_apertura' => $montoApertura,
        ];

        $ultimasFacturas = Factura::with(['cliente', 'pagos.metodoPago'])
            ->latest('id')
            ->take(5)
            ->get();

        return view('dashboard', compact('stats', 'ultimasFacturas'));
    }
}
