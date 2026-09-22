<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\InventarioGeneral;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BodegaController extends Controller
{
    public function index(Request $request)
    {
        $query = Bodega::withCount('inventarios')
            ->orderBy('es_principal', 'desc')
            ->orderBy('id', 'asc');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%")
                    ->orWhere('ubicacion', 'like', "%{$search}%")
                    ->orWhere('responsable', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        if ($request->filled('estado') && $request->estado !== 'todos') {
            $query->where('estado', $request->estado);
        }

        $bodegas = $query->get()->map(function ($b) {
            return [
                'id' => $b->id,
                'codigo' => $b->codigo ?? ('BOD-0'.$b->id),
                'nombre' => $b->nombre,
                'ubicacion' => $b->ubicacion ?? 'No especificada',
                'descripcion' => $b->descripcion ?? '',
                'responsable' => $b->responsable ?? 'No asignado',
                'telefono' => $b->telefono ?? '',
                'estado' => $b->estado ?? 'ACTIVO',
                'es_principal' => (bool) $b->es_principal,
                'total_stock' => $b->total_stock,
                'total_productos' => $b->total_productos,
                'total_items' => $b->total_items_registrados,
                'valor_costo' => $b->valor_inventario_costo,
                'valor_venta' => $b->valor_inventario_venta,
                'is_deletable' => $b->is_deletable,
                'created_at' => $b->created_at ? $b->created_at->format('Y-m-d') : '-',
            ];
        });

        $todas = Bodega::all();
        $kpis = [
            'total_bodegas' => $todas->count(),
            'bodegas_activas' => $todas->where('estado', 'ACTIVO')->count(),
            'total_stock' => (float) InventarioGeneral::sum('stock_actual'),
            'valor_costo' => (float) DB::table('inventario_general')
                ->join('inventario_productos', 'inventario_general.producto_id', '=', 'inventario_productos.id')
                ->selectRaw('SUM(inventario_general.stock_actual * inventario_productos.costo_promedio) as total')
                ->value('total') ?? 0.0,
        ];

        return view('bodegas.index', compact('bodegas', 'kpis'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'nullable|string|max:30|unique:inventario_bodegas,codigo',
            'nombre' => 'required|string|max:200|unique:inventario_bodegas,nombre',
            'ubicacion' => 'nullable|string|max:300',
            'descripcion' => 'nullable|string|max:500',
            'responsable' => 'nullable|string|max:200',
            'telefono' => 'nullable|string|max:50',
            'estado' => 'nullable|in:ACTIVO,INACTIVO',
            'es_principal' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $esPrincipal = $request->boolean('es_principal', false);

            if ($esPrincipal) {
                Bodega::query()->update(['es_principal' => false]);
            }

            // Si es la primera bodega, debe ser principal
            if (Bodega::count() === 0) {
                $esPrincipal = true;
            }

            $codigo = ! empty($validated['codigo']) ? trim($validated['codigo']) : ('BOD-0'.(Bodega::max('id') + 1));

            $bodega = Bodega::create([
                'codigo' => $codigo,
                'nombre' => trim($validated['nombre']),
                'ubicacion' => ! empty($validated['ubicacion']) ? trim($validated['ubicacion']) : null,
                'descripcion' => ! empty($validated['descripcion']) ? trim($validated['descripcion']) : null,
                'responsable' => ! empty($validated['responsable']) ? trim($validated['responsable']) : null,
                'telefono' => ! empty($validated['telefono']) ? trim($validated['telefono']) : null,
                'estado' => $validated['estado'] ?? 'ACTIVO',
                'es_principal' => $esPrincipal,
            ]);

            // Sincronizar catálogo: crear registro de InventarioGeneral en 0 para productos existentes
            $productos = Producto::all();
            foreach ($productos as $p) {
                InventarioGeneral::firstOrCreate(
                    ['bodega_id' => $bodega->id, 'producto_id' => $p->id],
                    ['stock_actual' => 0.0, 'stock_minimo' => 5.0]
                );
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'bodega' => $bodega,
                    'message' => 'Bodega creada exitosamente.',
                ]);
            }

            return redirect()->route('bodegas.index')->with('success', 'Bodega "'.$bodega->nombre.'" creada exitosamente.');
        });
    }

    public function show($id)
    {
        $bodega = Bodega::with(['inventarios.producto.categoria', 'movimientos.tipoMovimiento'])->findOrFail($id);

        $productos = $bodega->inventarios->map(function ($inv) {
            $p = $inv->producto;
            if (! $p) {
                return null;
            }

            return [
                'id' => $p->id,
                'codigo' => $p->codigo_principal,
                'codigo_auxiliar' => $p->codigo_auxiliar,
                'nombre' => $p->nombre,
                'categoria' => $p->categoria->nombre ?? 'General',
                'stock' => (float) $inv->stock_actual,
                'minimo' => (float) $inv->stock_minimo,
                'costo_unitario' => (float) $p->costo_promedio,
                'precio_unitario' => (float) $p->precio_unitario,
                'valor_costo' => round((float) $inv->stock_actual * (float) $p->costo_promedio, 2),
                'estado_stock' => $inv->stock_actual <= 0 ? 'AGOTADO' : ($inv->stock_actual <= $inv->stock_minimo ? 'BAJO' : 'NORMAL'),
            ];
        })->filter()->values();

        $movimientos = $bodega->movimientos()
            ->with(['tipoMovimiento'])
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'fecha' => $m->fecha_movimiento ? date('Y-m-d H:i', strtotime($m->fecha_movimiento)) : '-',
                    'tipo' => $m->tipoMovimiento->nombre ?? 'Movimiento',
                    'naturaleza' => $m->tipoMovimiento->naturaleza ?? 'INGRESO',
                    'referencia' => $m->referencia ?? '-',
                    'total_cantidad' => $m->total_cantidad,
                ];
            });

        return response()->json([
            'success' => true,
            'bodega' => [
                'id' => $bodega->id,
                'codigo' => $bodega->codigo ?? ('BOD-0'.$bodega->id),
                'nombre' => $bodega->nombre,
                'ubicacion' => $bodega->ubicacion ?? 'No especificada',
                'descripcion' => $bodega->descripcion ?? '',
                'responsable' => $bodega->responsable ?? 'No asignado',
                'telefono' => $bodega->telefono ?? 'Sin teléfono',
                'estado' => $bodega->estado ?? 'ACTIVO',
                'es_principal' => (bool) $bodega->es_principal,
                'total_stock' => $bodega->total_stock,
                'total_productos' => $bodega->total_productos,
                'valor_costo' => $bodega->valor_inventario_costo,
                'valor_venta' => $bodega->valor_inventario_venta,
                'productos' => $productos,
                'movimientos' => $movimientos,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $bodega = Bodega::findOrFail($id);

        $validated = $request->validate([
            'codigo' => 'nullable|string|max:30|unique:inventario_bodegas,codigo,'.$bodega->id,
            'nombre' => 'required|string|max:200|unique:inventario_bodegas,nombre,'.$bodega->id,
            'ubicacion' => 'nullable|string|max:300',
            'descripcion' => 'nullable|string|max:500',
            'responsable' => 'nullable|string|max:200',
            'telefono' => 'nullable|string|max:50',
            'estado' => 'nullable|in:ACTIVO,INACTIVO',
            'es_principal' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($validated, $request, $bodega) {
            $esPrincipal = $request->boolean('es_principal', false);

            if ($esPrincipal) {
                Bodega::where('id', '!=', $bodega->id)->update(['es_principal' => false]);
                $bodega->es_principal = true;
            } elseif ($bodega->es_principal && Bodega::count() > 1) {
                // Si intenta desmarcar la principal sin seleccionar otra, no permitir
                $bodega->es_principal = true;
            }

            $codigo = ! empty($validated['codigo']) ? trim($validated['codigo']) : ($bodega->codigo ?: ('BOD-0'.$bodega->id));

            $bodega->codigo = $codigo;
            $bodega->nombre = trim($validated['nombre']);
            $bodega->ubicacion = ! empty($validated['ubicacion']) ? trim($validated['ubicacion']) : null;
            $bodega->descripcion = ! empty($validated['descripcion']) ? trim($validated['descripcion']) : null;
            $bodega->responsable = ! empty($validated['responsable']) ? trim($validated['responsable']) : null;
            $bodega->telefono = ! empty($validated['telefono']) ? trim($validated['telefono']) : null;
            if (isset($validated['estado'])) {
                $bodega->estado = $validated['estado'];
            }
            $bodega->save();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'bodega' => $bodega,
                    'message' => 'Bodega actualizada exitosamente.',
                ]);
            }

            return redirect()->route('bodegas.index')->with('success', 'Bodega "'.$bodega->nombre.'" actualizada correctamente.');
        });
    }

    public function toggleEstado($id)
    {
        $bodega = Bodega::findOrFail($id);

        if ($bodega->es_principal && $bodega->estado === 'ACTIVO') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede desactivar la bodega principal del sistema.',
            ], 422);
        }

        if ($bodega->estado === 'ACTIVO' && $bodega->total_stock > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede desactivar una bodega que tiene existencias físicas ('.$bodega->total_stock.' unidades).',
            ], 422);
        }

        $bodega->estado = ($bodega->estado === 'ACTIVO') ? 'INACTIVO' : 'ACTIVO';
        $bodega->save();

        return response()->json([
            'success' => true,
            'estado' => $bodega->estado,
            'message' => 'Estado de bodega cambiado a '.$bodega->estado.'.',
        ]);
    }

    public function destroy($id)
    {
        $bodega = Bodega::findOrFail($id);

        if ($bodega->es_principal) {
            return redirect()->route('bodegas.index')->with('error', 'No se puede eliminar la bodega principal del sistema.');
        }

        if (Bodega::count() <= 1) {
            return redirect()->route('bodegas.index')->with('error', 'No se puede eliminar la única bodega existente.');
        }

        if ($bodega->total_stock > 0) {
            return redirect()->route('bodegas.index')->with('error', 'No se puede eliminar la bodega porque contiene '.$bodega->total_stock.' unidades de productos en inventario.');
        }

        if ($bodega->movimientos()->exists()) {
            return redirect()->route('bodegas.index')->with('error', 'No se puede eliminar la bodega porque tiene movimientos históricos registrados en Kardex. Se recomienda cambiar su estado a INACTIVO.');
        }

        if ($bodega->compras()->exists()) {
            return redirect()->route('bodegas.index')->with('error', 'No se puede eliminar la bodega porque tiene facturas de compras asociadas. Se recomienda cambiar su estado a INACTIVO.');
        }

        DB::transaction(function () use ($bodega) {
            InventarioGeneral::where('bodega_id', $bodega->id)->delete();
            $bodega->delete();
        });

        return redirect()->route('bodegas.index')->with('success', 'Bodega eliminada exitosamente.');
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Bodega::with('inventarios.producto');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%")
                    ->orWhere('ubicacion', 'like', "%{$search}%")
                    ->orWhere('responsable', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        if ($request->filled('estado') && $request->estado !== 'todos') {
            $query->where('estado', $request->estado);
        }

        $bodegas = $query->orderBy('id', 'asc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="listado_bodegas_'.date('Ymd_His').'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($bodegas) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Codigo',
                'Nombre',
                'Ubicacion',
                'Responsable',
                'Telefono',
                'Estado',
                'Es Principal',
                'Total Unidades Stock',
                'Productos con Stock',
                'Valor Inventario Costo ($)',
                'Valor Inventario Venta ($)',
            ], ';');

            foreach ($bodegas as $b) {
                fputcsv($handle, [
                    $b->id,
                    $b->codigo ?? ('BOD-0'.$b->id),
                    $b->nombre,
                    $b->ubicacion ?? 'N/A',
                    $b->responsable ?? 'N/A',
                    $b->telefono ?? 'N/A',
                    $b->estado ?? 'ACTIVO',
                    $b->es_principal ? 'SI' : 'NO',
                    $b->total_stock,
                    $b->total_productos,
                    number_format($b->valor_inventario_costo, 2, '.', ''),
                    number_format($b->valor_inventario_venta, 2, '.', ''),
                ], ';');
            }

            fclose($handle);
        }, 200, $headers);
    }
}
