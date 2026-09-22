<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Compra;
use App\Models\CompraNotaCredito;
use App\Models\CompraNotaCreditoDetalle;
use App\Models\InventarioGeneral;
use App\Models\Movimiento;
use App\Models\MovimientoDetalle;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\ExcelImportService;
use App\Services\SriXmlParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompraNotaCreditoController extends Controller
{
    public function index(Request $request)
    {
        $mesActual = now()->format('Y-m');

        $query = CompraNotaCredito::with(['compra', 'proveedor', 'bodega', 'detalles.producto', 'usuario'])
            ->latest('id');

        if ($request->filled('proveedor_id') && $request->proveedor_id !== 'todos') {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        if ($request->filled('bodega_id') && $request->bodega_id !== 'todas') {
            $query->where('bodega_id', $request->bodega_id);
        }

        if ($request->filled('compra_id') && $request->compra_id !== 'todas') {
            $query->where('compra_id', $request->compra_id);
        }

        if ($request->filled('tipo_modificacion') && $request->tipo_modificacion !== 'todos') {
            $query->where('tipo_modificacion', $request->tipo_modificacion);
        }

        if ($request->filled('estado') && $request->estado !== 'todos') {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_emision', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_emision', '<=', $request->fecha_hasta);
        }

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);
            $query->where(function ($q) use ($buscar) {
                $q->where('numero_nota_credito', 'LIKE', "%{$buscar}%")
                    ->orWhere('autorizacion_sri', 'LIKE', "%{$buscar}%")
                    ->orWhere('motivo', 'LIKE', "%{$buscar}%")
                    ->orWhereHas('proveedor', function ($pq) use ($buscar) {
                        $pq->where('razon_social', 'LIKE', "%{$buscar}%")
                            ->orWhere('identificacion', 'LIKE', "%{$buscar}%");
                    })
                    ->orWhereHas('compra', function ($cq) use ($buscar) {
                        $cq->where('numero_factura', 'LIKE', "%{$buscar}%");
                    });
            });
        }

        $notasCredito = $query->paginate(15)->withQueryString();

        // Calculate dynamic KPIs
        $ncsMes = CompraNotaCredito::with('detalles')
            ->where('fecha_emision', 'LIKE', "{$mesActual}%")
            ->where('estado', 'EMITIDA')
            ->get();

        $kpis = [
            'total_ncs_mes' => $ncsMes->count(),
            'monto_acreditado_mes' => (float) $ncsMes->sum('total'),
            'unidades_devueltas_mes' => (float) $ncsMes->sum(fn ($nc) => $nc->detalles->sum('cantidad')),
            'facturas_afectadas_mes' => $ncsMes->pluck('compra_id')->unique()->count(),
        ];

        $proveedores = Proveedor::orderBy('razon_social', 'asc')->get();
        $bodegas = Bodega::orderBy('id', 'asc')->get();
        $comprasDisponibles = Compra::with(['proveedor', 'bodega'])
            ->orderBy('fecha_emision', 'desc')
            ->limit(100)
            ->get();

        return view('compras.notas_credito.index', compact('notasCredito', 'proveedores', 'bodegas', 'comprasDisponibles', 'kpis'));
    }

    public function getCompraDetalles($compraId)
    {
        $compra = Compra::with(['proveedor', 'bodega', 'detalles.producto', 'notasCredito.detalles'])->findOrFail($compraId);

        // Compute previously returned quantity per product on this purchase
        $returnedPerProduct = [];
        foreach ($compra->notasCredito->where('estado', 'EMITIDA') as $nc) {
            foreach ($nc->detalles as $det) {
                $pId = $det->producto_id;
                $returnedPerProduct[$pId] = ($returnedPerProduct[$pId] ?? 0) + (float) $det->cantidad;
            }
        }

        $items = $compra->detalles->map(function ($d) use ($returnedPerProduct) {
            $pId = $d->producto_id;
            $qtyOriginal = (float) $d->cantidad;
            $qtyReturned = $returnedPerProduct[$pId] ?? 0.0;
            $qtyAvailable = max(0.0, round($qtyOriginal - $qtyReturned, 4));

            return [
                'producto_id' => $d->producto_id,
                'codigo' => $d->producto->codigo_principal ?? 'S/C',
                'nombre' => $d->producto->nombre ?? 'Producto',
                'cantidad_comprada' => $qtyOriginal,
                'cantidad_previa_devuelta' => $qtyReturned,
                'cantidad_disponible' => $qtyAvailable,
                'costo_unitario' => (float) $d->costo_unitario,
                'codigo_iva' => $d->producto->codigo_iva ?? '2',
                'tarifa_iva' => $d->producto->tarifa_iva_porcentaje ?? 15,
            ];
        });

        return response()->json([
            'success' => true,
            'compra' => [
                'id' => $compra->id,
                'numero_factura' => $compra->numero_factura,
                'fecha_emision' => $compra->fecha_emision,
                'total' => (float) $compra->total,
                'saldo_pendiente' => $compra->saldo_pendiente,
                'proveedor' => [
                    'id' => $compra->proveedor->id ?? null,
                    'razon_social' => $compra->proveedor->razon_social ?? 'Proveedor',
                    'identificacion' => $compra->proveedor->identificacion ?? 'N/A',
                ],
                'bodega' => [
                    'id' => $compra->bodega->id ?? null,
                    'nombre' => $compra->bodega->nombre ?? 'Bodega',
                ],
                'items' => $items,
            ],
        ]);
    }

    public function show($id)
    {
        $nc = CompraNotaCredito::with(['compra.proveedor', 'proveedor', 'bodega', 'usuario', 'detalles.producto', 'movimiento'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'nota_credito' => [
                'id' => $nc->id,
                'numero_nota_credito' => $nc->numero_nota_credito,
                'autorizacion_sri' => $nc->autorizacion_sri ?? 'N/A',
                'fecha_emision' => $nc->fecha_emision ? $nc->fecha_emision->format('Y-m-d') : 'N/A',
                'motivo' => $nc->motivo,
                'tipo_modificacion' => $nc->tipo_modificacion,
                'tipo_modificacion_texto' => $nc->tipo_modificacion_texto,
                'estado' => $nc->estado,
                'compra' => [
                    'id' => $nc->compra->id ?? null,
                    'numero_factura' => $nc->compra->numero_factura ?? 'N/A',
                    'fecha_emision' => $nc->compra->fecha_emision ?? 'N/A',
                    'total' => $nc->compra ? (float) $nc->compra->total : 0.0,
                ],
                'proveedor' => [
                    'id' => $nc->proveedor->id ?? null,
                    'razon_social' => $nc->proveedor->razon_social ?? 'Proveedor Desconocido',
                    'identificacion' => $nc->proveedor->identificacion ?? 'S/R',
                    'direccion' => $nc->proveedor->direccion ?? 'N/A',
                    'telefono' => $nc->proveedor->telefono ?? 'N/A',
                ],
                'bodega' => $nc->bodega->nombre ?? 'Bodega Principal',
                'subtotal' => (float) $nc->subtotal_sin_impuestos,
                'iva' => (float) $nc->iva,
                'total' => (float) $nc->total,
                'observaciones' => $nc->observaciones ?? '',
                'usuario' => $nc->nombre_usuario,
                'movimiento_id' => $nc->movimiento_id,
                'xml_path' => $nc->xml_path,
                'xml_nombre_original' => $nc->xml_nombre_original,
                'has_xml' => ! empty($nc->xml_path) && Storage::disk('public')->exists($nc->xml_path),
                'xml_download_url' => route('compras.notas-credito.descargarXml', $nc->id),
                'created_at' => $nc->created_at ? $nc->created_at->format('d/m/Y H:i') : 'N/A',
                'detalles' => $nc->detalles->map(fn ($d) => [
                    'id' => $d->id,
                    'producto_id' => $d->producto_id,
                    'producto_codigo' => $d->producto->codigo_principal ?? 'S/C',
                    'producto_nombre' => $d->producto->nombre ?? 'Producto',
                    'cantidad' => (float) $d->cantidad,
                    'costo_unitario' => (float) $d->costo_unitario,
                    'costo_total' => (float) $d->costo_total,
                    'tarifa_iva' => (float) $d->tarifa_iva,
                    'iva_total' => (float) $d->iva_total,
                ]),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'compra_id' => 'required|exists:compras_facturas,id',
            'numero_nota_credito' => 'required|string|max:50',
            'autorizacion_sri' => 'nullable|string|max:49',
            'fecha_emision' => 'required|date',
            'motivo' => 'required|string|max:300',
            'tipo_modificacion' => 'required|in:DEVOLUCION_MERCADERIA,DESCUENTO_VALOR',
            'observaciones' => 'nullable|string|max:500',
            'xml_path' => 'nullable|string|max:255',
            'xml_nombre_original' => 'nullable|string|max:255',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:inventario_productos,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.0001',
            'detalles.*.costo_unitario' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $compra = Compra::with(['detalles', 'notasCredito.detalles'])->findOrFail($validated['compra_id']);
            $bodegaId = $compra->bodega_id;
            $proveedorId = $compra->proveedor_id;
            $tipoModificacion = $validated['tipo_modificacion'];

            $xmlPath = $validated['xml_path'] ?? null;
            $xmlNombreOriginal = $validated['xml_nombre_original'] ?? null;

            if ($request->hasFile('xml_file')) {
                $file = $request->file('xml_file');
                $xmlNombreOriginal = $file->getClientOriginalName();
                $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $xmlNombreOriginal);
                $filename = 'nc_xml_'.uniqid().'_'.time().'_'.$safeName;
                $xmlPath = $file->storeAs('xmls/notas_credito', $filename, 'public');
            }

            // Compute already returned quantity per product
            $returnedPerProduct = [];
            foreach ($compra->notasCredito->where('estado', 'EMITIDA') as $ncPrev) {
                foreach ($ncPrev->detalles as $detPrev) {
                    $pId = $detPrev->producto_id;
                    $returnedPerProduct[$pId] = ($returnedPerProduct[$pId] ?? 0) + (float) $detPrev->cantidad;
                }
            }

            // Original purchase map
            $compraItems = $compra->detalles->keyBy('producto_id');

            $subtotalSinImpuestos = 0.0;
            $totalIva = 0.0;
            $itemsProcessed = [];

            foreach ($validated['detalles'] as $item) {
                $prodId = (int) $item['producto_id'];
                $qty = (float) $item['cantidad'];
                $costUnit = (float) $item['costo_unitario'];

                if (! isset($compraItems[$prodId])) {
                    abort(422, "El producto ID {$prodId} no pertenece a la factura de compra seleccionada.");
                }

                $originalQty = (float) $compraItems[$prodId]->cantidad;
                $prevReturned = $returnedPerProduct[$prodId] ?? 0.0;
                $maxReturnable = max(0.0, round($originalQty - $prevReturned, 4));

                if ($qty > ($maxReturnable + 0.0001)) {
                    abort(422, "La cantidad a devolver ({$qty}) excede la cantidad disponible de la factura ({$maxReturnable}) para el producto ID {$prodId}.");
                }

                $lineTotal = round($qty * $costUnit, 2);
                $subtotalSinImpuestos += $lineTotal;

                $prod = Producto::find($prodId);
                $tarifaIva = $prod ? (float) $prod->tarifa_iva_porcentaje : 0.0;
                $lineIva = ($tarifaIva > 0) ? round($lineTotal * ($tarifaIva / 100), 2) : 0.0;
                $totalIva += $lineIva;

                $itemsProcessed[] = [
                    'producto' => $prod ?: $compraItems[$prodId]->producto,
                    'cantidad' => $qty,
                    'costo_unitario' => $costUnit,
                    'costo_total' => $lineTotal,
                    'tarifa_iva' => $tarifaIva,
                    'iva_total' => $lineIva,
                ];
            }

            $total = round($subtotalSinImpuestos + $totalIva, 2);

            $movimientoId = null;

            // If physical return, reduce inventory and record Kardex
            if ($tipoModificacion === 'DEVOLUCION_MERCADERIA') {
                $mov = Movimiento::create([
                    'bodega_id' => $bodegaId,
                    'tipo_movimiento_id' => 4, // 4 = DEVOLUCION COMPRA (EGRESO, factor -1)
                    'usuario_id' => 1,
                    'fecha_movimiento' => now(),
                    'referencia' => 'NC-PROV-'.trim($validated['numero_nota_credito']),
                    'observaciones' => "Devolución a proveedor según NC #{$validated['numero_nota_credito']} sobre Factura #{$compra->numero_factura}. Motivo: {$validated['motivo']}",
                ]);
                $movimientoId = $mov->id;

                foreach ($itemsProcessed as $item) {
                    // Create Kardex movement detail
                    MovimientoDetalle::create([
                        'movimiento_id' => $mov->id,
                        'producto_id' => $item['producto']->id,
                        'cantidad' => $item['cantidad'],
                        'costo_unitario' => $item['costo_unitario'],
                        'costo_total' => $item['costo_total'],
                    ]);

                    // Decrement warehouse stock
                    $inv = InventarioGeneral::firstOrCreate(
                        ['bodega_id' => $bodegaId, 'producto_id' => $item['producto']->id],
                        ['stock_actual' => 0, 'stock_minimo' => 5]
                    );

                    $inv->stock_actual = max(0.0, (float) $inv->stock_actual - $item['cantidad']);
                    $inv->save();
                }
            }

            // Create CompraNotaCredito
            $notaCredito = CompraNotaCredito::create([
                'compra_id' => $compra->id,
                'proveedor_id' => $proveedorId,
                'bodega_id' => $bodegaId,
                'usuario_id' => 1,
                'movimiento_id' => $movimientoId,
                'numero_nota_credito' => trim($validated['numero_nota_credito']),
                'autorizacion_sri' => ! empty($validated['autorizacion_sri']) ? trim($validated['autorizacion_sri']) : null,
                'fecha_emision' => $validated['fecha_emision'],
                'motivo' => trim($validated['motivo']),
                'tipo_modificacion' => $tipoModificacion,
                'subtotal_sin_impuestos' => $subtotalSinImpuestos,
                'iva' => $totalIva,
                'total' => $total,
                'xml_path' => $xmlPath,
                'xml_nombre_original' => $xmlNombreOriginal,
                'observaciones' => $validated['observaciones'] ?? "Nota de crédito sobre factura #{$compra->numero_factura}",
                'estado' => 'EMITIDA',
            ]);

            // Save details
            foreach ($itemsProcessed as $item) {
                CompraNotaCreditoDetalle::create([
                    'nota_credito_id' => $notaCredito->id,
                    'producto_id' => $item['producto']->id,
                    'cantidad' => $item['cantidad'],
                    'costo_unitario' => $item['costo_unitario'],
                    'costo_total' => $item['costo_total'],
                    'tarifa_iva' => $item['tarifa_iva'],
                    'iva_total' => $item['iva_total'],
                ]);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'nota_credito' => $notaCredito,
                    'message' => 'Nota de crédito registrada exitosamente.',
                ]);
            }

            return redirect()->route('compras.notas-credito.index')
                ->with('success', "Nota de Crédito #{$notaCredito->numero_nota_credito} registrada con éxito.");
        });
    }

    public function anular(Request $request, $id)
    {
        $nc = CompraNotaCredito::with(['detalles', 'bodega'])->findOrFail($id);

        if ($nc->estado === 'ANULADA') {
            return redirect()->back()->with('error', 'Esta Nota de Crédito ya se encuentra anulada.');
        }

        return DB::transaction(function () use ($nc, $request) {
            // If physical return was performed, reverse the stock reduction
            if ($nc->tipo_modificacion === 'DEVOLUCION_MERCADERIA') {
                $movAnulacion = Movimiento::create([
                    'bodega_id' => $nc->bodega_id,
                    'tipo_movimiento_id' => 5, // 5 = AJUSTE INGRESO (factor +1)
                    'usuario_id' => 1,
                    'fecha_movimiento' => now(),
                    'referencia' => 'ANUL-NC-'.$nc->numero_nota_credito,
                    'observaciones' => 'Reverso de stock por anulación de NC #'.$nc->numero_nota_credito.'. Motivo: '.($request->motivo ?? 'Anulación de Nota de Crédito'),
                ]);

                foreach ($nc->detalles as $det) {
                    MovimientoDetalle::create([
                        'movimiento_id' => $movAnulacion->id,
                        'producto_id' => $det->producto_id,
                        'cantidad' => $det->cantidad,
                        'costo_unitario' => $det->costo_unitario,
                        'costo_total' => $det->costo_total,
                    ]);

                    $inv = InventarioGeneral::firstOrCreate(
                        ['bodega_id' => $nc->bodega_id, 'producto_id' => $det->producto_id],
                        ['stock_actual' => 0, 'stock_minimo' => 5]
                    );

                    $inv->stock_actual = (float) $inv->stock_actual + (float) $det->cantidad;
                    $inv->save();
                }
            }

            $nc->estado = 'ANULADA';
            $nc->observaciones = trim(($nc->observaciones ?? '').' [ANULADA: '.($request->motivo ?? 'Sin motivo').']');
            $nc->save();

            return redirect()->route('compras.notas-credito.index')
                ->with('success', "Nota de Crédito #{$nc->numero_nota_credito} anulada correctamente.");
        });
    }

    public function export(Request $request): StreamedResponse
    {
        $query = CompraNotaCredito::with(['compra', 'proveedor', 'bodega', 'detalles.producto'])
            ->latest('id');

        if ($request->filled('proveedor_id') && $request->proveedor_id !== 'todos') {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        if ($request->filled('bodega_id') && $request->bodega_id !== 'todas') {
            $query->where('bodega_id', $request->bodega_id);
        }

        if ($request->filled('tipo_modificacion') && $request->tipo_modificacion !== 'todos') {
            $query->where('tipo_modificacion', $request->tipo_modificacion);
        }

        if ($request->filled('estado') && $request->estado !== 'todos') {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_emision', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_emision', '<=', $request->fecha_hasta);
        }

        $notasCredito = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="notas_credito_compras_'.date('Y-m-d_His').'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($notasCredito) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID NC',
                'No. Nota Credito',
                'Autorizacion SRI',
                'Fecha Emision',
                'No. Factura Modificada',
                'RUC/Identificacion',
                'Proveedor',
                'Bodega',
                'Tipo Modificacion',
                'Motivo',
                'Items Devueltos',
                'Subtotal ($)',
                'IVA ($)',
                'Total ($)',
                'Estado',
            ]);

            foreach ($notasCredito as $nc) {
                fputcsv($handle, [
                    $nc->id,
                    $nc->numero_nota_credito,
                    $nc->autorizacion_sri ?? 'N/A',
                    $nc->fecha_emision ? $nc->fecha_emision->format('Y-m-d') : '',
                    $nc->compra->numero_factura ?? 'N/A',
                    $nc->proveedor->identificacion ?? '',
                    $nc->proveedor->razon_social ?? '',
                    $nc->bodega->nombre ?? '',
                    $nc->tipo_modificacion_texto,
                    $nc->motivo,
                    $nc->cantidad_total_articulos,
                    number_format((float) $nc->subtotal_sin_impuestos, 2, '.', ''),
                    number_format((float) $nc->iva, 2, '.', ''),
                    number_format((float) $nc->total, 2, '.', ''),
                    $nc->estado,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function parseXml(Request $request, SriXmlParserService $parser)
    {
        $request->validate([
            'xml_file' => 'required|file|max:5120',
        ]);

        try {
            $file = $request->file('xml_file');
            $originalName = $file->getClientOriginalName();
            $content = file_get_contents($file->getRealPath());
            $resultado = $parser->parseNotaCreditoXml($content);

            // Store XML in public storage for persistence
            $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $originalName);
            $filename = 'nc_xml_'.uniqid().'_'.time().'_'.$safeName;
            $path = $file->storeAs('xmls/notas_credito', $filename, 'public');

            return response()->json([
                'success' => true,
                'data' => $resultado,
                'xml_path' => $path,
                'xml_nombre_original' => $originalName,
                'message' => 'Nota de crédito XML del SRI procesada y guardada correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function descargarXml($id)
    {
        $nc = CompraNotaCredito::findOrFail($id);

        if (! $nc->xml_path || ! Storage::disk('public')->exists($nc->xml_path)) {
            return back()->with('error', 'El archivo XML asociado a esta Nota de Crédito no se encuentra disponible.');
        }

        $nombreDescarga = $nc->xml_nombre_original ?: "Nota_Credito_{$nc->numero_nota_credito}.xml";

        return Storage::disk('public')->download($nc->xml_path, $nombreDescarga);
    }

    public function parseExcel(Request $request, ExcelImportService $excelService)
    {
        $request->validate([
            'excel_file' => 'required|file|max:10240',
            'compra_id' => 'nullable|exists:compras_facturas,id',
        ]);

        try {
            $file = $request->file('excel_file');
            $compraId = $request->filled('compra_id') ? (int) $request->compra_id : null;
            $resultado = $excelService->parseDetallesNotaCredito($file, $compraId);

            return response()->json([
                'success' => true,
                'data' => $resultado,
                'message' => 'Detalle de Nota de Crédito importado desde Excel correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function descargarPlantillaExcel(ExcelImportService $excelService): StreamedResponse
    {
        return $excelService->generarPlantillaNotaCredito();
    }
}
