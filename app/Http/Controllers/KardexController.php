<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\InventarioGeneral;
use App\Models\Movimiento;
use App\Models\MovimientoDetalle;
use App\Models\Producto;
use App\Models\TipoMovimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KardexController extends Controller
{
    public function index(Request $request)
    {
        $bodegas = Bodega::orderBy('id', 'asc')->get();
        $tiposMovimiento = TipoMovimiento::orderBy('id', 'asc')->get();
        $productos = Producto::orderBy('nombre', 'asc')->get();

        $query = DB::table('inventario_movimientos_detalles as d')
            ->join('inventario_movimientos as m', 'd.movimiento_id', '=', 'm.id')
            ->join('inventario_tipos_movimiento as tm', 'm.tipo_movimiento_id', '=', 'tm.id')
            ->join('inventario_bodegas as b', 'm.bodega_id', '=', 'b.id')
            ->join('inventario_productos as p', 'd.producto_id', '=', 'p.id')
            ->leftJoin('usuarios_usuario as u', 'm.usuario_id', '=', 'u.id')
            ->select(
                'm.id as movimiento_id',
                'm.fecha_movimiento',
                'm.referencia',
                'm.observaciones',
                'm.bodega_id',
                'b.nombre as bodega_nombre',
                'tm.id as tipo_movimiento_id',
                'tm.nombre as concepto_movimiento',
                'tm.naturaleza as tipo_naturaleza',
                'tm.factor',
                'p.id as producto_id',
                'p.codigo_principal as producto_codigo',
                'p.nombre as producto_nombre',
                'd.id as detalle_id',
                'd.cantidad',
                'd.costo_unitario',
                'd.costo_total',
                DB::raw('(d.cantidad * tm.factor) as variacion_stock'),
                DB::raw("CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, '')) as usuario_nombre")
            );

        if ($request->filled('bodega_id') && $request->bodega_id !== 'todas') {
            $query->where('m.bodega_id', $request->bodega_id);
        }

        if ($request->filled('tipo_movimiento_id') && $request->tipo_movimiento_id !== 'todos') {
            $query->where('m.tipo_movimiento_id', $request->tipo_movimiento_id);
        }

        if ($request->filled('producto_id') && $request->producto_id !== 'todos') {
            $query->where('p.id', $request->producto_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('m.fecha_movimiento', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('m.fecha_movimiento', '<=', $request->fecha_hasta);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('p.nombre', 'like', "%{$search}%")
                    ->orWhere('p.codigo_principal', 'like', "%{$search}%")
                    ->orWhere('m.referencia', 'like', "%{$search}%")
                    ->orWhere('m.observaciones', 'like', "%{$search}%");
            });
        }

        $allRawMovimientos = $query->orderBy('m.fecha_movimiento', 'desc')
            ->orderBy('m.id', 'desc')
            ->get();

        // Calculate dynamic KPIs
        $kpis = [
            'total_movimientos' => $allRawMovimientos->count(),
            'total_entradas' => (float) $allRawMovimientos->where('tipo_naturaleza', 'INGRESO')->sum('cantidad'),
            'total_salidas' => (float) $allRawMovimientos->where('tipo_naturaleza', 'EGRESO')->sum('cantidad'),
            'valor_total_movido' => (float) $allRawMovimientos->sum('costo_total'),
        ];

        // Specific Product History running balance calculation if product is selected
        $selectedProduct = null;
        $productKardexTrace = [];

        if ($request->filled('producto_id') && $request->producto_id !== 'todos') {
            $selectedProduct = Producto::with(['categoria', 'inventarios.bodega'])->find($request->producto_id);

            if ($selectedProduct) {
                // Get all movements for this product in chronological order (ASC) to calculate rolling balance
                $chronoMovements = DB::table('inventario_movimientos_detalles as d')
                    ->join('inventario_movimientos as m', 'd.movimiento_id', '=', 'm.id')
                    ->join('inventario_tipos_movimiento as tm', 'm.tipo_movimiento_id', '=', 'tm.id')
                    ->join('inventario_bodegas as b', 'm.bodega_id', '=', 'b.id')
                    ->where('d.producto_id', $selectedProduct->id)
                    ->when($request->filled('bodega_id') && $request->bodega_id !== 'todas', function ($q) use ($request) {
                        $q->where('m.bodega_id', $request->bodega_id);
                    })
                    ->select(
                        'm.id as movimiento_id',
                        'm.fecha_movimiento',
                        'm.referencia',
                        'm.observaciones',
                        'b.nombre as bodega_nombre',
                        'tm.nombre as concepto',
                        'tm.naturaleza',
                        'tm.factor',
                        'd.cantidad',
                        'd.costo_unitario',
                        'd.costo_total'
                    )
                    ->orderBy('m.fecha_movimiento', 'asc')
                    ->orderBy('m.id', 'asc')
                    ->get();

                $runningBalance = 0.0;
                foreach ($chronoMovements as $mov) {
                    $saldoAnterior = $runningBalance;
                    $variacion = (float) $mov->cantidad * (int) $mov->factor;
                    $runningBalance += $variacion;

                    $productKardexTrace[] = [
                        'movimiento_id' => $mov->movimiento_id,
                        'fecha' => $mov->fecha_movimiento,
                        'bodega' => $mov->bodega_nombre,
                        'concepto' => $mov->concepto,
                        'naturaleza' => $mov->naturaleza,
                        'referencia' => $mov->referencia ?? '-',
                        'observaciones' => $mov->observaciones ?? '',
                        'saldo_anterior' => $saldoAnterior,
                        'cantidad_entrada' => $mov->naturaleza === 'INGRESO' ? (float) $mov->cantidad : null,
                        'cantidad_salida' => $mov->naturaleza === 'EGRESO' ? (float) $mov->cantidad : null,
                        'costo_unitario' => (float) $mov->costo_unitario,
                        'costo_total' => (float) $mov->costo_total,
                        'saldo_resultante' => $runningBalance,
                    ];
                }

                // Show most recent first in UI
                $productKardexTrace = array_reverse($productKardexTrace);
            }
        }

        $movimientos = $allRawMovimientos->map(function ($m) {
            return [
                'id_movimiento' => $m->movimiento_id,
                'fecha_movimiento' => $m->fecha_movimiento,
                'bodega' => $m->bodega_nombre,
                'concepto_movimiento' => $m->concepto_movimiento,
                'tipo' => $m->tipo_naturaleza,
                'referencia' => $m->referencia ?? '-',
                'observaciones' => $m->observaciones,
                'codigo_producto' => $m->producto_codigo,
                'producto_id' => $m->producto_id,
                'producto' => $m->producto_nombre,
                'cantidad' => (float) $m->cantidad,
                'costo_unitario' => (float) $m->costo_unitario,
                'costo_total' => (float) $m->costo_total,
                'variacion_stock' => (float) $m->variacion_stock,
                'usuario' => ! empty(trim($m->usuario_nombre)) ? $m->usuario_nombre : 'Administrador',
            ];
        });

        return view('kardex.index', compact('movimientos', 'bodegas', 'tiposMovimiento', 'productos', 'kpis', 'selectedProduct', 'productKardexTrace'));
    }

    public function productoKardex($id)
    {
        $producto = Producto::with(['categoria', 'inventarios.bodega'])->findOrFail($id);

        $chronoMovements = DB::table('inventario_movimientos_detalles as d')
            ->join('inventario_movimientos as m', 'd.movimiento_id', '=', 'm.id')
            ->join('inventario_tipos_movimiento as tm', 'm.tipo_movimiento_id', '=', 'tm.id')
            ->join('inventario_bodegas as b', 'm.bodega_id', '=', 'b.id')
            ->where('d.producto_id', $producto->id)
            ->select(
                'm.id as movimiento_id',
                'm.fecha_movimiento',
                'm.referencia',
                'm.observaciones',
                'b.nombre as bodega_nombre',
                'tm.nombre as concepto',
                'tm.naturaleza',
                'tm.factor',
                'd.cantidad',
                'd.costo_unitario',
                'd.costo_total'
            )
            ->orderBy('m.fecha_movimiento', 'asc')
            ->orderBy('m.id', 'asc')
            ->get();

        $runningBalance = 0.0;
        $trace = [];
        foreach ($chronoMovements as $mov) {
            $saldoAnterior = $runningBalance;
            $variacion = (float) $mov->cantidad * (int) $mov->factor;
            $runningBalance += $variacion;

            $trace[] = [
                'movimiento_id' => $mov->movimiento_id,
                'fecha' => $mov->fecha_movimiento,
                'bodega' => $mov->bodega_nombre,
                'concepto' => $mov->concepto,
                'naturaleza' => $mov->naturaleza,
                'referencia' => $mov->referencia,
                'saldo_anterior' => $saldoAnterior,
                'cantidad' => (float) $mov->cantidad,
                'costo_unitario' => (float) $mov->costo_unitario,
                'costo_total' => (float) $mov->costo_total,
                'saldo_resultante' => $runningBalance,
            ];
        }

        return response()->json([
            'success' => true,
            'producto' => [
                'id' => $producto->id,
                'codigo' => $producto->codigo_principal,
                'nombre' => $producto->nombre,
                'categoria' => $producto->categoria->nombre ?? 'General',
                'stock_total' => $producto->stock_total,
                'costo_promedio' => $producto->costo_promedio,
                'precio_unitario' => $producto->precio_unitario,
                'valor_inventario_costo' => $producto->valor_inventario_costo,
            ],
            'movimientos' => array_reverse($trace),
        ]);
    }

    public function storeAjuste(Request $request)
    {
        $validated = $request->validate([
            'bodega_id' => 'required|exists:inventario_bodegas,id',
            'producto_id' => 'required|exists:inventario_productos,id',
            'tipo_movimiento_id' => 'required|in:5,6', // 5=Ajuste Ingreso, 6=Ajuste Egreso
            'cantidad' => 'required|numeric|min:0.0001',
            'costo_unitario' => 'nullable|numeric|min:0',
            'observaciones' => 'required|string|max:300',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $prod = Producto::findOrFail($validated['producto_id']);
            $bodegaId = (int) $validated['bodega_id'];
            $tipoMovId = (int) $validated['tipo_movimiento_id'];
            $cantidad = (float) $validated['cantidad'];
            $costoUnitario = (float) ($validated['costo_unitario'] ?? $prod->costo_promedio ?? 0.00);
            $costoTotal = round($cantidad * $costoUnitario, 2);
            $factor = ($tipoMovId === 5) ? 1 : -1;

            $inv = InventarioGeneral::firstOrCreate(
                ['bodega_id' => $bodegaId, 'producto_id' => $prod->id],
                ['stock_actual' => 0, 'stock_minimo' => 5]
            );

            // Calculate new stock
            if ($factor < 0 && $inv->stock_actual < $cantidad) {
                // Warning if decreasing more than present, but adjust floor to 0
                $inv->stock_actual = 0;
            } else {
                $inv->stock_actual = max(0, $inv->stock_actual + ($cantidad * $factor));
            }
            $inv->save();

            $referencia = 'AJUSTE-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -5));

            $mov = Movimiento::create([
                'bodega_id' => $bodegaId,
                'tipo_movimiento_id' => $tipoMovId,
                'usuario_id' => 1,
                'fecha_movimiento' => now(),
                'referencia' => $referencia,
                'observaciones' => trim($validated['observaciones']),
            ]);

            MovimientoDetalle::create([
                'movimiento_id' => $mov->id,
                'producto_id' => $prod->id,
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario,
                'costo_total' => $costoTotal,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Ajuste de inventario (#{$referencia}) registrado exitosamente en el Kardex.",
                ]);
            }

            return redirect()->route('kardex.index', ['producto_id' => $prod->id])
                ->with('success', "Ajuste de inventario (#{$referencia}) registrado correctamente.");
        });
    }

    public function export(Request $request)
    {
        $query = DB::table('inventario_movimientos_detalles as d')
            ->join('inventario_movimientos as m', 'd.movimiento_id', '=', 'm.id')
            ->join('inventario_tipos_movimiento as tm', 'm.tipo_movimiento_id', '=', 'tm.id')
            ->join('inventario_bodegas as b', 'm.bodega_id', '=', 'b.id')
            ->join('inventario_productos as p', 'd.producto_id', '=', 'p.id')
            ->leftJoin('usuarios_usuario as u', 'm.usuario_id', '=', 'u.id')
            ->select(
                'm.id as movimiento_id',
                'm.fecha_movimiento',
                'm.referencia',
                'm.observaciones',
                'b.nombre as bodega_nombre',
                'tm.nombre as concepto_movimiento',
                'tm.naturaleza as tipo_naturaleza',
                'tm.factor',
                'p.codigo_principal as producto_codigo',
                'p.nombre as producto_nombre',
                'd.cantidad',
                'd.costo_unitario',
                'd.costo_total',
                DB::raw('(d.cantidad * tm.factor) as variacion_stock'),
                DB::raw("CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, '')) as usuario_nombre")
            );

        if ($request->filled('bodega_id') && $request->bodega_id !== 'todas') {
            $query->where('m.bodega_id', $request->bodega_id);
        }

        if ($request->filled('tipo_movimiento_id') && $request->tipo_movimiento_id !== 'todos') {
            $query->where('m.tipo_movimiento_id', $request->tipo_movimiento_id);
        }

        if ($request->filled('producto_id') && $request->producto_id !== 'todos') {
            $query->where('p.id', $request->producto_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('m.fecha_movimiento', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('m.fecha_movimiento', '<=', $request->fecha_hasta);
        }

        $movimientos = $query->orderBy('m.fecha_movimiento', 'desc')->get();

        $filename = 'kardex_movimientos_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($movimientos) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID Movimiento',
                'Fecha & Hora',
                'Bodega',
                'Concepto / Tipo Movimiento',
                'Naturaleza',
                'Referencia Comprobante',
                'Código Producto',
                'Nombre del Producto',
                'Cantidad',
                'Variación Stock',
                'Costo Unitario ($)',
                'Costo Total ($)',
                'Usuario Responsable',
                'Observaciones',
            ]);

            foreach ($movimientos as $m) {
                fputcsv($handle, [
                    $m->movimiento_id,
                    $m->fecha_movimiento,
                    $m->bodega_nombre,
                    $m->concepto_movimiento,
                    $m->tipo_naturaleza,
                    $m->referencia ?? '-',
                    $m->producto_codigo,
                    $m->producto_nombre,
                    $m->cantidad,
                    $m->variacion_stock,
                    number_format($m->costo_unitario, 4, '.', ''),
                    number_format($m->costo_total, 2, '.', ''),
                    ! empty(trim($m->usuario_nombre)) ? $m->usuario_nombre : 'Administrador',
                    $m->observaciones ?? '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
