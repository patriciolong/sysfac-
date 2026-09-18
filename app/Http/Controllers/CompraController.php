<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\InventarioGeneral;
use App\Models\Movimiento;
use App\Models\MovimientoDetalle;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $query = Compra::with(['proveedor', 'bodega', 'usuario', 'detalles.producto', 'movimiento']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('numero_factura', 'LIKE', "%{$search}%")
                    ->orWhere('observaciones', 'LIKE', "%{$search}%")
                    ->orWhereHas('proveedor', function ($prov) use ($search) {
                        $prov->where('razon_social', 'LIKE', "%{$search}%")
                            ->orWhere('identificacion', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($request->filled('proveedor_id') && $request->proveedor_id !== 'todos') {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        if ($request->filled('bodega_id') && $request->bodega_id !== 'todas') {
            $query->where('bodega_id', $request->bodega_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_emision', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_emision', '<=', $request->fecha_hasta);
        }

        $compras = $query->latest('id')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'fecha' => $c->fecha_emision,
                    'proveedor_id' => $c->proveedor_id,
                    'proveedor' => $c->proveedor->razon_social ?? 'Proveedor Desconocido',
                    'proveedor_ruc' => $c->proveedor->identificacion ?? 'S/R',
                    'referencia_factura' => $c->numero_factura,
                    'bodega_id' => $c->bodega_id,
                    'bodega' => $c->bodega->nombre ?? 'Bodega Principal',
                    'subtotal' => (float) $c->subtotal_sin_impuestos,
                    'iva' => (float) $c->iva,
                    'total_compra' => (float) $c->total,
                    'observaciones' => $c->observaciones,
                    'usuario' => $c->nombre_usuario,
                    'items_count' => $c->detalles->count(),
                    'articulos_cantidad' => (float) $c->detalles->sum('cantidad'),
                    'movimiento_id' => $c->movimiento_id,
                    'detalles' => $c->detalles->map(fn ($d) => [
                        'producto_id' => $d->producto_id,
                        'producto_nombre' => $d->producto->nombre ?? 'Producto',
                        'producto_codigo' => $d->producto->codigo_principal ?? '',
                        'cantidad' => (float) $d->cantidad,
                        'costo_unitario' => (float) $d->costo_unitario,
                        'costo_total' => (float) $d->costo_total,
                    ]),
                ];
            });

        // Dynamic KPI calculations for Compras
        $now = now();
        $mesActual = $now->format('Y-m');
        $allComprasMes = Compra::with('detalles')->where('fecha_emision', 'LIKE', "{$mesActual}%")->get();

        $kpis = [
            'total_compras_mes' => $allComprasMes->count(),
            'monto_compras_mes' => (float) $allComprasMes->sum('total'),
            'total_proveedores' => Proveedor::count(),
            'articulos_ingresados_mes' => (float) $allComprasMes->sum(fn ($c) => $c->detalles->sum('cantidad')),
        ];

        $proveedores = Proveedor::orderBy('razon_social', 'asc')->get();
        $bodegas = Bodega::orderBy('id', 'asc')->get();
        $productos = Producto::where('estado', 'ACTIVO')
            ->orderBy('nombre', 'asc')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'codigo' => $p->codigo_principal,
                    'nombre' => $p->nombre,
                    'costo_promedio' => (float) ($p->costo_promedio ?? 0),
                    'precio_unitario' => (float) $p->precio_unitario,
                    'codigo_iva' => $p->codigo_iva,
                    'tarifa_iva' => $p->tarifa_iva_porcentaje,
                    'stock_total' => $p->stock_total,
                ];
            });

        return view('compras.index', compact('compras', 'proveedores', 'bodegas', 'productos', 'kpis'));
    }

    public function show($id)
    {
        $compra = Compra::with(['proveedor', 'bodega', 'usuario', 'detalles.producto', 'movimiento'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'compra' => [
                'id' => $compra->id,
                'numero_factura' => $compra->numero_factura,
                'fecha_emision' => $compra->fecha_emision,
                'proveedor' => [
                    'id' => $compra->proveedor->id ?? null,
                    'razon_social' => $compra->proveedor->razon_social ?? 'Proveedor Desconocido',
                    'identificacion' => $compra->proveedor->identificacion ?? 'S/R',
                    'direccion' => $compra->proveedor->direccion ?? 'N/A',
                    'telefono' => $compra->proveedor->telefono ?? 'N/A',
                    'correo' => $compra->proveedor->correo ?? 'N/A',
                ],
                'bodega' => $compra->bodega->nombre ?? 'Bodega Principal',
                'subtotal' => (float) $compra->subtotal_sin_impuestos,
                'iva' => (float) $compra->iva,
                'total' => (float) $compra->total,
                'observaciones' => $compra->observaciones,
                'usuario' => $compra->nombre_usuario,
                'movimiento_id' => $compra->movimiento_id,
                'created_at' => $compra->created_at ? $compra->created_at->format('d/m/Y H:i') : 'N/A',
                'detalles' => $compra->detalles->map(fn ($d) => [
                    'id' => $d->id,
                    'producto_id' => $d->producto_id,
                    'producto_codigo' => $d->producto->codigo_principal ?? 'N/A',
                    'producto_nombre' => $d->producto->nombre ?? 'Producto',
                    'cantidad' => (float) $d->cantidad,
                    'costo_unitario' => (float) $d->costo_unitario,
                    'costo_total' => (float) $d->costo_total,
                ]),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'proveedor_id' => 'required|exists:compras_proveedores,id',
            'numero_factura' => 'required|string|max:50',
            'fecha_emision' => 'required|date',
            'bodega_id' => 'required|exists:inventario_bodegas,id',
            'observaciones' => 'nullable|string|max:500',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:inventario_productos,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.0001',
            'detalles.*.costo_unitario' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $bodegaId = (int) $validated['bodega_id'];
            $proveedorId = (int) $validated['proveedor_id'];
            $numeroFactura = trim($validated['numero_factura']);
            $fechaEmision = $validated['fecha_emision'];

            $subtotalSinImpuestos = 0.0;
            $totalIva = 0.0;
            $itemsProcessed = [];

            // Calculate totals and validate items
            foreach ($validated['detalles'] as $item) {
                $prod = Producto::findOrFail($item['producto_id']);
                $qty = (float) $item['cantidad'];
                $costUnit = (float) $item['costo_unitario'];
                $lineTotal = round($qty * $costUnit, 2);

                $subtotalSinImpuestos += $lineTotal;

                // IVA calculation based on product tax code
                $tarifaIva = $prod->tarifa_iva_porcentaje;
                $lineIva = ($tarifaIva > 0) ? round($lineTotal * ($tarifaIva / 100), 2) : 0.0;
                $totalIva += $lineIva;

                $itemsProcessed[] = [
                    'producto' => $prod,
                    'cantidad' => $qty,
                    'costo_unitario' => $costUnit,
                    'costo_total' => $lineTotal,
                ];
            }

            $total = round($subtotalSinImpuestos + $totalIva, 2);

            // 1. Create Purchase Invoice
            $compra = Compra::create([
                'proveedor_id' => $proveedorId,
                'bodega_id' => $bodegaId,
                'usuario_id' => 1, // Fallback to usuarios_usuario ID
                'numero_factura' => $numeroFactura,
                'fecha_emision' => $fechaEmision,
                'subtotal_sin_impuestos' => $subtotalSinImpuestos,
                'iva' => $totalIva,
                'total' => $total,
                'observaciones' => $validated['observaciones'] ?? "Ingreso por factura #{$numeroFactura}",
            ]);

            // 2. Register Kardex Entry (Tipo 1 = COMPRA)
            $mov = Movimiento::create([
                'bodega_id' => $bodegaId,
                'tipo_movimiento_id' => 1, // COMPRA
                'usuario_id' => 1,
                'fecha_movimiento' => now(),
                'referencia' => $numeroFactura,
                'observaciones' => "Ingreso por compra #{$numeroFactura} - Proveedor ID: {$proveedorId}",
            ]);

            // Update Compra with movimiento_id
            $compra->movimiento_id = $mov->id;
            $compra->save();

            // 3. Process each line: Details, Stock Increase & Weighted Average Cost Recalculation
            foreach ($itemsProcessed as $item) {
                $prod = $item['producto'];
                $qty = $item['cantidad'];
                $costUnit = $item['costo_unitario'];
                $lineTotal = $item['costo_total'];

                // Insert purchase detail
                CompraDetalle::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $prod->id,
                    'cantidad' => $qty,
                    'costo_unitario' => $costUnit,
                    'costo_total' => $lineTotal,
                ]);

                // Insert movement detail
                MovimientoDetalle::create([
                    'movimiento_id' => $mov->id,
                    'producto_id' => $prod->id,
                    'cantidad' => $qty,
                    'costo_unitario' => $costUnit,
                    'costo_total' => $lineTotal,
                ]);

                // Increase stock in warehouse
                $inv = InventarioGeneral::firstOrCreate(
                    ['bodega_id' => $bodegaId, 'producto_id' => $prod->id],
                    ['stock_actual' => 0, 'stock_minimo' => 5]
                );

                $stockPrevioTotal = (float) $prod->stock_total;
                $costoPrevio = (float) ($prod->costo_promedio ?? 0);

                $inv->stock_actual += $qty;
                $inv->save();

                // Recalculate Weighted Average Cost: ( (S_prev * C_prev) + (Qty * CostUnit) ) / (S_prev + Qty)
                if ($prod->tipo_producto === 'BIEN') {
                    if ($stockPrevioTotal <= 0 || $costoPrevio <= 0) {
                        $nuevoCostoPromedio = $costUnit;
                    } else {
                        $nuevoStockTotal = $stockPrevioTotal + $qty;
                        $nuevoCostoPromedio = round((($stockPrevioTotal * $costoPrevio) + ($qty * $costUnit)) / $nuevoStockTotal, 4);
                    }

                    $prod->costo_promedio = $nuevoCostoPromedio;
                    $prod->save();
                }
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'compra' => $compra,
                    'message' => "Factura de compra #{$numeroFactura} registrada y stock actualizado en Kardex exitosamente.",
                ]);
            }

            return redirect()->route('compras.index')->with('success', "Factura de compra #{$numeroFactura} registrada exitosamente.");
        });
    }

    public function anular(Request $request, $id)
    {
        $compra = Compra::with(['detalles.producto', 'bodega'])->findOrFail($id);

        return DB::transaction(function () use ($compra, $request) {
            $bodegaId = $compra->bodega_id;

            // Register Reversal Movement in Kardex (Tipo 4 = DEVOLUCION EN COMPRA / ANULACION)
            $mov = Movimiento::create([
                'bodega_id' => $bodegaId,
                'tipo_movimiento_id' => 4, // DEVOLUCIÓN EN COMPRA
                'usuario_id' => 1,
                'fecha_movimiento' => now(),
                'referencia' => "ANULACION-{$compra->numero_factura}",
                'observaciones' => "Anulación de Factura de Compra #{$compra->numero_factura}. Motivo: ".($request->motivo ?? 'Anulación administrativa'),
            ]);

            // Reverse stock for each detail item
            foreach ($compra->detalles as $det) {
                $inv = InventarioGeneral::where('bodega_id', $bodegaId)
                    ->where('producto_id', $det->producto_id)
                    ->first();

                if ($inv) {
                    $inv->stock_actual = max(0, $inv->stock_actual - $det->cantidad);
                    $inv->save();
                }

                MovimientoDetalle::create([
                    'movimiento_id' => $mov->id,
                    'producto_id' => $det->producto_id,
                    'cantidad' => $det->cantidad,
                    'costo_unitario' => $det->costo_unitario,
                    'costo_total' => $det->costo_total,
                ]);
            }

            $compra->observaciones = '[ANULADA - '.now()->format('d/m/Y H:i')."] {$compra->observaciones}";
            $compra->save();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Compra #{$compra->numero_factura} anulada e inventario revertido correctamente.",
                ]);
            }

            return redirect()->route('compras.index')->with('success', "Compra #{$compra->numero_factura} anulada e inventario revertido correctamente.");
        });
    }

    public function storeProveedorRapido(Request $request)
    {
        $validated = $request->validate([
            'tipo_identificacion' => 'required|string|max:2',
            'identificacion' => 'required|string|max:20|unique:compras_proveedores,identificacion',
            'razon_social' => 'required|string|max:300',
            'correo' => 'required|email|max:150',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:300',
        ]);

        $proveedor = Proveedor::create([
            'tipo_identificacion' => $validated['tipo_identificacion'],
            'identificacion' => trim($validated['identificacion']),
            'razon_social' => trim($validated['razon_social']),
            'correo' => trim($validated['correo']),
            'telefono' => $validated['telefono'] ? trim($validated['telefono']) : null,
            'direccion' => $validated['direccion'] ? trim($validated['direccion']) : null,
        ]);

        return response()->json([
            'success' => true,
            'proveedor' => [
                'id' => $proveedor->id,
                'razon_social' => $proveedor->razon_social,
                'identificacion' => $proveedor->identificacion,
                'display' => "{$proveedor->razon_social} ({$proveedor->identificacion})",
            ],
            'message' => 'Proveedor registrado exitosamente',
        ]);
    }

    public function export(Request $request)
    {
        $query = Compra::with(['proveedor', 'bodega', 'detalles']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('numero_factura', 'LIKE', "%{$search}%")
                    ->orWhereHas('proveedor', function ($prov) use ($search) {
                        $prov->where('razon_social', 'LIKE', "%{$search}%")
                            ->orWhere('identificacion', 'LIKE', "%{$search}%");
                    });
            });
        }

        $compras = $query->latest('fecha_emision')->get();

        $filename = 'historial_compras_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($compras) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Fecha Emisión',
                'Nº Factura',
                'RUC / Identificación Proveedor',
                'Razón Social Proveedor',
                'Bodega Destino',
                'Ítems Distintos',
                'Total Unidades',
                'Subtotal ($)',
                'IVA ($)',
                'Total ($)',
                'Observaciones',
            ]);

            foreach ($compras as $c) {
                fputcsv($handle, [
                    $c->id,
                    $c->fecha_emision,
                    $c->numero_factura,
                    $c->proveedor->identificacion ?? 'N/A',
                    $c->proveedor->razon_social ?? 'N/A',
                    $c->bodega->nombre ?? 'Bodega Principal',
                    $c->detalles->count(),
                    $c->detalles->sum('cantidad'),
                    number_format($c->subtotal_sin_impuestos, 2, '.', ''),
                    number_format($c->iva, 2, '.', ''),
                    number_format($c->total, 2, '.', ''),
                    $c->observaciones ?? '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
