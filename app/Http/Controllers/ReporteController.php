<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\CajaTurno;
use App\Models\Compra;
use App\Models\CompraNotaCredito;
use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    private function resolveDateRange(Request $request): array
    {
        $rango = $request->get('rango', 'este_mes');
        $hoy = now()->format('Y-m-d');

        switch ($rango) {
            case 'hoy':
                $desde = $hoy;
                $hasta = $hoy;
                $titulo = 'Hoy ('.now()->format('d/m/Y').')';
                break;
            case 'esta_semana':
                $desde = now()->startOfWeek()->format('Y-m-d');
                $hasta = now()->endOfWeek()->format('Y-m-d');
                $titulo = 'Esta Semana ('.now()->startOfWeek()->format('d/m').' al '.now()->endOfWeek()->format('d/m/Y').')';
                break;
            case 'mes_anterior':
                $desde = now()->subMonth()->startOfMonth()->format('Y-m-d');
                $hasta = now()->subMonth()->endOfMonth()->format('Y-m-d');
                $titulo = 'Mes Anterior ('.now()->subMonth()->isoFormat('MMMM Y').')';
                break;
            case 'este_anio':
                $desde = now()->startOfYear()->format('Y-m-d');
                $hasta = now()->endOfYear()->format('Y-m-d');
                $titulo = 'Año '.now()->format('Y');
                break;
            case 'personalizado':
                $desde = $request->get('fecha_desde', now()->startOfMonth()->format('Y-m-d'));
                $hasta = $request->get('fecha_hasta', now()->format('Y-m-d'));
                $titulo = 'Personalizado ('.date('d/m/Y', strtotime($desde)).' al '.date('d/m/Y', strtotime($hasta)).')';
                break;
            case 'este_mes':
            default:
                $rango = 'este_mes';
                $desde = now()->startOfMonth()->format('Y-m-d');
                $hasta = now()->endOfMonth()->format('Y-m-d');
                $titulo = 'Este Mes ('.now()->isoFormat('MMMM Y').')';
                break;
        }

        return [$desde, $hasta, $rango, $titulo];
    }

    public function index(Request $request)
    {
        [$desde, $hasta, $rango, $tituloRango] = $this->resolveDateRange($request);
        $tab = $request->get('tab', 'general');

        // =========================================================================
        // 1. DATASETS: VENTAS
        // =========================================================================
        $facturasQuery = Factura::with('cliente')
            ->whereBetween('fecha_emision', [$desde, $hasta]);

        $ventasTotales = (float) $facturasQuery->sum('importe_total');
        $ventasSubtotal = (float) $facturasQuery->sum('total_sin_impuestos');
        $ventasBase15 = (float) $facturasQuery->sum('base_imponible_iva');
        $ventasIva15 = (float) $facturasQuery->sum('valor_iva');
        $ventasBase0 = (float) $facturasQuery->sum('base_imponible_0');
        $conteoFacturas = $facturasQuery->count();
        $ticketPromedio = $conteoFacturas > 0 ? round($ventasTotales / $conteoFacturas, 2) : 0.0;

        // Top Selling Products in Range
        $topProductos = FacturaDetalle::whereHas('factura', function ($q) use ($desde, $hasta) {
            $q->whereBetween('fecha_emision', [$desde, $hasta]);
        })
            ->select(
                'producto_id',
                'descripcion as nombre',
                DB::raw('SUM(cantidad) as total_unidades'),
                DB::raw('SUM(precio_total_sin_impuestos) as total_recaudado')
            )
            ->groupBy('producto_id', 'descripcion')
            ->orderByDesc('total_unidades')
            ->limit(10)
            ->get();

        // Top Clients in Range
        $topClientes = Factura::whereBetween('fecha_emision', [$desde, $hasta])
            ->join('clientes_cliente', 'ventas_facturas.cliente_id', '=', 'clientes_cliente.id')
            ->select(
                'clientes_cliente.id',
                'clientes_cliente.razon_social',
                'clientes_cliente.identificacion',
                DB::raw('COUNT(ventas_facturas.id) as total_facturas'),
                DB::raw('SUM(ventas_facturas.importe_total) as total_comprado')
            )
            ->groupBy('clientes_cliente.id', 'clientes_cliente.razon_social', 'clientes_cliente.identificacion')
            ->orderByDesc('total_comprado')
            ->limit(10)
            ->get();

        // Recent Invoices in Range (Paginated for table)
        $facturasLista = Factura::with('cliente')
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->latest('id')
            ->limit(15)
            ->get();

        // =========================================================================
        // 2. DATASETS: COMPRAS & PROVEEDORES
        // =========================================================================
        $comprasQuery = Compra::with('proveedor')
            ->whereBetween('fecha_emision', [$desde, $hasta]);

        $comprasTotales = (float) $comprasQuery->sum('total');
        $comprasSubtotal = (float) $comprasQuery->sum('subtotal_sin_impuestos');
        $comprasIva = (float) $comprasQuery->sum('iva');
        $conteoCompras = $comprasQuery->count();

        // Notas de Credito Compras in Range
        $ncsQuery = CompraNotaCredito::with(['proveedor', 'compra'])
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->where('estado', 'EMITIDA');

        $ncsTotales = (float) $ncsQuery->sum('total');
        $ncsIva = (float) $ncsQuery->sum('iva');
        $ncsSubtotal = (float) $ncsQuery->sum('subtotal_sin_impuestos');
        $conteoNcs = $ncsQuery->count();

        $comprasNetas = max(0.0, round($comprasTotales - $ncsTotales, 2));

        // Top Suppliers in Range
        $topProveedores = Compra::whereBetween('fecha_emision', [$desde, $hasta])
            ->join('compras_proveedores', 'compras_facturas.proveedor_id', '=', 'compras_proveedores.id')
            ->select(
                'compras_proveedores.id',
                'compras_proveedores.razon_social',
                'compras_proveedores.identificacion',
                DB::raw('COUNT(compras_facturas.id) as total_facturas'),
                DB::raw('SUM(compras_facturas.total) as total_comprado')
            )
            ->groupBy('compras_proveedores.id', 'compras_proveedores.razon_social', 'compras_proveedores.identificacion')
            ->orderByDesc('total_comprado')
            ->limit(10)
            ->get();

        $comprasLista = Compra::with(['proveedor', 'bodega'])
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->latest('id')
            ->limit(15)
            ->get();

        // =========================================================================
        // 3. DATASETS: INVENTARIO VALORIZADO & STOCK
        // =========================================================================
        $productosInventario = Producto::with(['categoria', 'inventarios.bodega'])->get();
        $totalItems = $productosInventario->count();
        $totalStockUnidades = (float) $productosInventario->sum(fn ($p) => $p->stock_total);
        $valoracionCosto = (float) $productosInventario->sum(fn ($p) => $p->valor_inventario_costo);
        $valoracionPVP = (float) $productosInventario->sum(fn ($p) => $p->valor_inventario_venta);
        $margenPotencial = max(0.0, round($valoracionPVP - $valoracionCosto, 2));

        $productosBajoStock = $productosInventario->filter(fn ($p) => $p->stock_total > 0 && $p->stock_total <= $p->stock_minimo_total)->count();
        $productosAgotados = $productosInventario->filter(fn ($p) => $p->stock_total <= 0)->count();

        $bodegasResumen = Bodega::with('inventarios')->get()->map(function ($b) {
            $stockUds = (float) $b->inventarios->sum('stock_actual');
            $valCosto = (float) $b->inventarios->sum(fn ($inv) => (float) $inv->stock_actual * (float) ($inv->producto->costo_promedio ?? 0));

            return [
                'id' => $b->id,
                'nombre' => $b->nombre,
                'codigo' => $b->codigo ?? 'B'.$b->id,
                'unidades' => $stockUds,
                'valor_costo' => $valCosto,
            ];
        });

        // =========================================================================
        // 4. DATASETS: CAJA & ARQUEOS
        // =========================================================================
        $turnosQuery = CajaTurno::with(['caja', 'usuario'])
            ->whereDate('fecha_apertura', '>=', $desde)
            ->whereDate('fecha_apertura', '<=', $hasta);

        $totalVentasEfectivo = (float) $turnosQuery->sum('ventas_efectivo');
        $totalVentasTarjetas = (float) $turnosQuery->sum('ventas_tarjetas');
        $totalVentasTransferencia = (float) $turnosQuery->sum('ventas_transferencia');
        $totalRecaudadoCaja = (float) $turnosQuery->sum('total_ventas');
        $diferenciasCaja = (float) $turnosQuery->sum('diferencia');
        $totalTurnos = $turnosQuery->count();

        $turnosLista = $turnosQuery->latest('id')->limit(15)->get();

        // =========================================================================
        // 5. CÁLCULOS FINANCIEROS Y FORMULARIO 104 (SRI)
        // =========================================================================
        // Cost of Goods Sold (COGS) Estimation: sum(qty * costo_promedio)
        $costoMercaderiaVendida = FacturaDetalle::whereHas('factura', function ($q) use ($desde, $hasta) {
            $q->whereBetween('fecha_emision', [$desde, $hasta]);
        })
            ->join('inventario_productos', 'ventas_detalles_factura.producto_id', '=', 'inventario_productos.id')
            ->sum(DB::raw('ventas_detalles_factura.cantidad * COALESCE(inventario_productos.costo_promedio, 0)'));

        $utilidadBruta = max(0.0, round($ventasSubtotal - $costoMercaderiaVendida, 2));
        $margenBrutoPorcentaje = $ventasSubtotal > 0 ? round(($utilidadBruta / $ventasSubtotal) * 100, 1) : 0.0;

        // SRI 104 Calculations
        $ivaDebitoFiscal = $ventasIva15; // IVA generado en ventas
        $ivaCreditoFiscal = max(0.0, round($comprasIva - $ncsIva, 2)); // IVA pagado en compras menos NCs
        $impuestoCausado = round($ivaDebitoFiscal - $ivaCreditoFiscal, 2);

        $kpis = compact(
            'ventasTotales',
            'ventasSubtotal',
            'ventasBase15',
            'ventasIva15',
            'ventasBase0',
            'conteoFacturas',
            'ticketPromedio',
            'comprasTotales',
            'comprasSubtotal',
            'comprasIva',
            'comprasNetas',
            'conteoCompras',
            'ncsTotales',
            'conteoNcs',
            'totalItems',
            'totalStockUnidades',
            'valoracionCosto',
            'valoracionPVP',
            'margenPotencial',
            'productosBajoStock',
            'productosAgotados',
            'totalVentasEfectivo',
            'totalVentasTarjetas',
            'totalVentasTransferencia',
            'totalRecaudadoCaja',
            'diferenciasCaja',
            'totalTurnos',
            'costoMercaderiaVendida',
            'utilidadBruta',
            'margenBrutoPorcentaje',
            'ivaDebitoFiscal',
            'ivaCreditoFiscal',
            'impuestoCausado'
        );

        return view('reportes.index', compact(
            'kpis',
            'tab',
            'desde',
            'hasta',
            'rango',
            'tituloRango',
            'topProductos',
            'topClientes',
            'topProveedores',
            'facturasLista',
            'comprasLista',
            'bodegasResumen',
            'turnosLista'
        ));
    }

    public function exportVentas(Request $request): StreamedResponse
    {
        [$desde, $hasta] = $this->resolveDateRange($request);
        $facturas = Factura::with(['cliente', 'detalles'])
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->latest('id')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_ventas_sri_'.$desde.'_'.$hasta.'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($facturas) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, [
                'ID',
                'No. Factura',
                'Clave de Acceso',
                'Fecha Emision',
                'RUC / Cedula Cliente',
                'Razon Social Cliente',
                'Subtotal sin Impuestos ($)',
                'Base Tarifa 15% ($)',
                'IVA 15% ($)',
                'Base Tarifa 0% ($)',
                'Importe Total ($)',
                'Estado SRI',
            ]);

            foreach ($facturas as $f) {
                fputcsv($handle, [
                    $f->id,
                    $f->numero_comprobante ?? '001-001-'.str_pad($f->id, 9, '0', STR_PAD_LEFT),
                    $f->clave_acceso ?? 'N/A',
                    $f->fecha_emision ? date('Y-m-d', strtotime($f->fecha_emision)) : '',
                    $f->cliente->identificacion ?? 'S/I',
                    $f->cliente->razon_social ?? 'Consumidor Final',
                    number_format((float) $f->total_sin_impuestos, 2, '.', ''),
                    number_format((float) $f->base_imponible_iva, 2, '.', ''),
                    number_format((float) $f->valor_iva, 2, '.', ''),
                    number_format((float) $f->base_imponible_0, 2, '.', ''),
                    number_format((float) $f->importe_total, 2, '.', ''),
                    $f->estado_sri ?? 'AUTORIZADO',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function exportCompras(Request $request): StreamedResponse
    {
        [$desde, $hasta] = $this->resolveDateRange($request);
        $compras = Compra::with(['proveedor', 'bodega', 'notasCredito'])
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->latest('id')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_compras_'.$desde.'_'.$hasta.'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($compras) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID Compra',
                'No. Factura Proveedor',
                'Fecha Emision',
                'RUC Proveedor',
                'Razon Social Proveedor',
                'Bodega Destino',
                'Subtotal ($)',
                'IVA ($)',
                'Total Factura ($)',
                'Notas de Credito ($)',
                'Saldo Neto Compra ($)',
            ]);

            foreach ($compras as $c) {
                fputcsv($handle, [
                    $c->id,
                    $c->numero_factura,
                    $c->fecha_emision ? date('Y-m-d', strtotime($c->fecha_emision)) : '',
                    $c->proveedor->identificacion ?? 'S/R',
                    $c->proveedor->razon_social ?? 'Proveedor Desconocido',
                    $c->bodega->nombre ?? 'Principal',
                    number_format((float) $c->subtotal_sin_impuestos, 2, '.', ''),
                    number_format((float) $c->iva, 2, '.', ''),
                    number_format((float) $c->total, 2, '.', ''),
                    number_format($c->total_notas_credito, 2, '.', ''),
                    number_format($c->saldo_pendiente, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function exportInventario(Request $request): StreamedResponse
    {
        $productos = Producto::with(['categoria', 'inventarios.bodega'])->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_inventario_valorizado_'.date('Y-m-d').'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($productos) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Codigo Principal',
                'Codigo Auxiliar',
                'Producto',
                'Categoria',
                'Tipo',
                'Stock Total',
                'Stock Minimo',
                'Costo Promedio ($)',
                'Precio Venta PVP ($)',
                'Valoracion al Costo ($)',
                'Valoracion a PVP ($)',
                'Margen Bruto ($)',
                'Margen (%)',
                'Estado',
            ]);

            foreach ($productos as $p) {
                fputcsv($handle, [
                    $p->id,
                    $p->codigo_principal,
                    $p->codigo_auxiliar ?? '',
                    $p->nombre,
                    $p->categoria->nombre ?? 'General',
                    $p->tipo_producto,
                    $p->stock_total,
                    $p->stock_minimo_total,
                    number_format((float) $p->costo_promedio, 2, '.', ''),
                    number_format((float) $p->precio_unitario, 2, '.', ''),
                    number_format($p->valor_inventario_costo, 2, '.', ''),
                    number_format($p->valor_inventario_venta, 2, '.', ''),
                    number_format($p->margen_ganancia, 2, '.', ''),
                    $p->margen_porcentaje.'%',
                    $p->estado,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function exportCaja(Request $request): StreamedResponse
    {
        [$desde, $hasta] = $this->resolveDateRange($request);
        $turnos = CajaTurno::with(['caja', 'usuario'])
            ->whereDate('fecha_apertura', '>=', $desde)
            ->whereDate('fecha_apertura', '<=', $hasta)
            ->latest('id')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_caja_turnos_'.$desde.'_'.$hasta.'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($turnos) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID Turno',
                'Caja / Punto',
                'Cajero / Usuario',
                'Fecha Apertura',
                'Fecha Cierre',
                'Monto Inicial ($)',
                'Ventas Efectivo ($)',
                'Ventas Tarjeta ($)',
                'Ventas Transferencia ($)',
                'Total Ventas ($)',
                'Efectivo Esperado ($)',
                'Efectivo Real ($)',
                'Diferencia Arqueo ($)',
                'Estado Turno',
            ]);

            foreach ($turnos as $t) {
                fputcsv($handle, [
                    $t->id,
                    $t->caja->nombre ?? 'Caja 001',
                    $t->usuario ? ($t->usuario->name.' '.$t->usuario->apellido) : 'Usuario',
                    $t->fecha_apertura ? date('Y-m-d H:i', strtotime($t->fecha_apertura)) : '',
                    $t->fecha_cierre ? date('Y-m-d H:i', strtotime($t->fecha_cierre)) : 'ABIERTA',
                    number_format((float) $t->monto_inicial, 2, '.', ''),
                    number_format((float) $t->ventas_efectivo, 2, '.', ''),
                    number_format((float) $t->ventas_tarjetas, 2, '.', ''),
                    number_format((float) $t->ventas_transferencia, 2, '.', ''),
                    number_format((float) $t->total_ventas, 2, '.', ''),
                    number_format((float) $t->efectivo_esperado, 2, '.', ''),
                    $t->efectivo_real !== null ? number_format((float) $t->efectivo_real, 2, '.', '') : 'N/A',
                    $t->diferencia !== null ? number_format((float) $t->diferencia, 2, '.', '') : '0.00',
                    $t->estado,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function exportTributario(Request $request): StreamedResponse
    {
        [$desde, $hasta] = $this->resolveDateRange($request);

        $ventasBase15 = (float) Factura::whereBetween('fecha_emision', [$desde, $hasta])->sum('base_imponible_iva');
        $ventasIva15 = (float) Factura::whereBetween('fecha_emision', [$desde, $hasta])->sum('valor_iva');
        $ventasBase0 = (float) Factura::whereBetween('fecha_emision', [$desde, $hasta])->sum('base_imponible_0');
        $ventasTotal = (float) Factura::whereBetween('fecha_emision', [$desde, $hasta])->sum('importe_total');

        $comprasSubtotal = (float) Compra::whereBetween('fecha_emision', [$desde, $hasta])->sum('subtotal_sin_impuestos');
        $comprasIva = (float) Compra::whereBetween('fecha_emision', [$desde, $hasta])->sum('iva');
        $comprasTotal = (float) Compra::whereBetween('fecha_emision', [$desde, $hasta])->sum('total');

        $ncsSubtotal = (float) CompraNotaCredito::whereBetween('fecha_emision', [$desde, $hasta])->where('estado', 'EMITIDA')->sum('subtotal_sin_impuestos');
        $ncsIva = (float) CompraNotaCredito::whereBetween('fecha_emision', [$desde, $hasta])->where('estado', 'EMITIDA')->sum('iva');
        $ncsTotal = (float) CompraNotaCredito::whereBetween('fecha_emision', [$desde, $hasta])->where('estado', 'EMITIDA')->sum('total');

        $ivaCreditoNeto = max(0.0, round($comprasIva - $ncsIva, 2));
        $impuestoCausado = round($ventasIva15 - $ivaCreditoNeto, 2);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="resumen_tributario_sri_104_'.$desde.'_'.$hasta.'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use (
            $desde, $hasta, $ventasBase15, $ventasIva15, $ventasBase0, $ventasTotal,
            $comprasSubtotal, $comprasIva, $ncsSubtotal, $ncsIva,
            $ivaCreditoNeto, $impuestoCausado
        ) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['RESUMEN TRIBUTARIO SRI - FORMULARIO 104 (DECLARACION IVA)']);
            fputcsv($handle, ['Periodo:', $desde.' al '.$hasta]);
            fputcsv($handle, []);

            fputcsv($handle, ['SECCION 1: VENTAS Y OPERACIONES GRAVADAS', 'BASE IMPONIBLE ($)', 'IMPUESTO GENERADO / IVA ($)']);
            fputcsv($handle, ['Ventas Locales Gravadas Tarifa 15%', number_format($ventasBase15, 2, '.', ''), number_format($ventasIva15, 2, '.', '')]);
            fputcsv($handle, ['Ventas Locales Tarifa 0% (Bienes/Servicios)', number_format($ventasBase0, 2, '.', ''), '0.00']);
            fputcsv($handle, ['TOTAL FACTURADO EN VENTAS', number_format($ventasBase15 + $ventasBase0, 2, '.', ''), number_format($ventasTotal, 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['SECCION 2: COMPRAS Y CREDITO TRIBUTARIO', 'BASE IMPONIBLE ($)', 'CREDITO TRIBUTARIO / IVA ($)']);
            fputcsv($handle, ['Compras Locales Facturas Recibidas', number_format($comprasSubtotal, 2, '.', ''), number_format($comprasIva, 2, '.', '')]);
            fputcsv($handle, ['(-) Notas de Credito a Proveedores Aplicadas', number_format($ncsSubtotal, 2, '.', ''), number_format($ncsIva, 2, '.', '')]);
            fputcsv($handle, ['COMPRAS NETAS Y CREDITO TRIBUTARIO DEL MES', number_format($comprasSubtotal - $ncsSubtotal, 2, '.', ''), number_format($ivaCreditoNeto, 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['SECCION 3: LIQUIDACION DEL IMPUESTO', 'RESULTADO ($)']);
            fputcsv($handle, ['Total IVA Debito Fiscal (Ventas)', number_format($ventasIva15, 2, '.', '')]);
            fputcsv($handle, ['Total IVA Credito Fiscal (Compras Netas)', number_format($ivaCreditoNeto, 2, '.', '')]);
            fputcsv($handle, [
                $impuestoCausado >= 0 ? '(=) IMPUESTO A PAGAR AL SRI' : '(=) SALDO A FAVOR DE CREDITO TRIBUTARIO',
                number_format(abs($impuestoCausado), 2, '.', ''),
            ]);

            fclose($handle);
        }, 200, $headers);
    }
}
