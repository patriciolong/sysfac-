<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Categoria;
use App\Models\InventarioGeneral;
use App\Models\Movimiento;
use App\Models\MovimientoDetalle;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $query = Producto::with(['categoria', 'inventarios.bodega']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('codigo_principal', 'LIKE', "%{$search}%")
                    ->orWhere('codigo_auxiliar', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('categoria_id') && $request->categoria_id !== 'todas') {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('estado') && $request->estado !== 'todos') {
            $query->where('estado', $request->estado);
        }

        $allProductos = Producto::with(['categoria', 'inventarios'])->get();

        // Calculate dynamic KPIs across entire catalog
        $kpis = [
            'total_items' => $allProductos->count(),
            'total_stock' => (float) $allProductos->sum(fn ($p) => $p->stock_total),
            'valor_costo' => (float) $allProductos->sum(fn ($p) => $p->valor_inventario_costo),
            'valor_venta' => (float) $allProductos->sum(fn ($p) => $p->valor_inventario_venta),
            'en_stock' => $allProductos->filter(fn ($p) => $p->estado_stock === 'EN_STOCK')->count(),
            'stock_bajo' => $allProductos->filter(fn ($p) => $p->estado_stock === 'STOCK_BAJO')->count(),
            'agotados' => $allProductos->filter(fn ($p) => $p->estado_stock === 'AGOTADO')->count(),
        ];

        $productos = $query->orderBy('nombre', 'asc')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'codigo_principal' => $p->codigo_principal,
                    'codigo_auxiliar' => $p->codigo_auxiliar,
                    'nombre' => $p->nombre,
                    'categoria_id' => $p->categoria_id,
                    'categoria' => $p->categoria->nombre ?? 'General',
                    'tipo_producto' => $p->tipo_producto,
                    'precio_unitario' => (float) $p->precio_unitario,
                    'costo_promedio' => (float) ($p->costo_promedio ?? 0),
                    'margen_ganancia' => $p->margen_ganancia,
                    'margen_porcentaje' => $p->margen_porcentaje,
                    'stock_actual' => (float) $p->stock_total,
                    'stock_minimo' => (float) $p->stock_minimo_total,
                    'estado_stock' => $p->estado_stock,
                    'codigo_iva' => $p->codigo_iva,
                    'tarifa_iva' => $p->tarifa_iva_porcentaje,
                    'iva' => $p->iva_texto,
                    'precio_con_iva' => $p->precio_con_iva,
                    'valor_inventario_costo' => $p->valor_inventario_costo,
                    'valor_inventario_venta' => $p->valor_inventario_venta,
                    'estado' => $p->estado,
                    'icono' => $p->icono,
                    'inventarios_bodega' => $p->inventarios->map(function ($inv) {
                        return [
                            'bodega' => $inv->bodega->nombre ?? 'Bodega',
                            'stock' => (float) $inv->stock_actual,
                            'minimo' => (float) $inv->stock_minimo,
                        ];
                    }),
                ];
            });

        // Filter by Stock Status in collection if requested
        if ($request->filled('estado_stock') && $request->estado_stock !== 'todos') {
            $status = $request->estado_stock;
            $productos = $productos->filter(function ($item) use ($status) {
                return $item['estado_stock'] === $status;
            })->values();
        }

        $categorias = Categoria::orderBy('nombre', 'asc')->get();
        $bodegas = Bodega::orderBy('id', 'asc')->get();

        return view('productos.index', compact('productos', 'categorias', 'bodegas', 'kpis'));
    }

    public function show($id)
    {
        $producto = Producto::with(['categoria', 'inventarios.bodega', 'movimientoDetalles.movimiento.tipoMovimiento'])->findOrFail($id);

        $movimientos = $producto->movimientoDetalles()
            ->with(['movimiento.tipoMovimiento', 'movimiento.bodega'])
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(function ($d) {
                return [
                    'fecha' => $d->movimiento->fecha_movimiento ?? 'N/A',
                    'concepto' => $d->movimiento->tipoMovimiento->nombre ?? 'Movimiento',
                    'naturaleza' => $d->movimiento->tipoMovimiento->naturaleza ?? 'INGRESO',
                    'referencia' => $d->movimiento->referencia ?? '-',
                    'bodega' => $d->movimiento->bodega->nombre ?? 'Bodega Principal',
                    'cantidad' => (float) $d->cantidad,
                    'costo_unitario' => (float) $d->costo_unitario,
                    'costo_total' => (float) $d->costo_total,
                ];
            });

        return response()->json([
            'success' => true,
            'producto' => [
                'id' => $producto->id,
                'codigo_principal' => $producto->codigo_principal,
                'codigo_auxiliar' => $producto->codigo_auxiliar,
                'nombre' => $producto->nombre,
                'categoria_id' => $producto->categoria_id,
                'categoria' => $producto->categoria->nombre ?? 'General',
                'tipo_producto' => $producto->tipo_producto,
                'precio_unitario' => (float) $producto->precio_unitario,
                'costo_promedio' => (float) ($producto->costo_promedio ?? 0),
                'precio_con_iva' => $producto->precio_con_iva,
                'margen_ganancia' => $producto->margen_ganancia,
                'margen_porcentaje' => $producto->margen_porcentaje,
                'codigo_iva' => $producto->codigo_iva,
                'tarifa_iva' => $producto->tarifa_iva_porcentaje,
                'iva_texto' => $producto->iva_texto,
                'stock_actual' => $producto->stock_total,
                'stock_minimo' => $producto->stock_minimo_total,
                'estado_stock' => $producto->estado_stock,
                'estado' => $producto->estado,
                'valor_costo' => $producto->valor_inventario_costo,
                'valor_venta' => $producto->valor_inventario_venta,
                'bodegas' => $producto->inventarios->map(fn ($i) => [
                    'bodega_id' => $i->bodega_id,
                    'bodega_nombre' => $i->bodega->nombre ?? 'Bodega',
                    'stock' => (float) $i->stock_actual,
                    'minimo' => (float) $i->stock_minimo,
                ]),
                'movimientos_recientes' => $movimientos,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_principal' => 'required|string|max:25|unique:inventario_productos,codigo_principal',
            'codigo_auxiliar' => 'nullable|string|max:25',
            'nombre' => 'required|string|max:300',
            'categoria_id' => 'required|exists:inventario_categorias,id',
            'tipo_producto' => 'required|in:BIEN,SERVICIO',
            'precio_unitario' => 'required|numeric|min:0',
            'costo_promedio' => 'nullable|numeric|min:0',
            'codigo_iva' => 'required|string|in:0,2,4',
            'stock_minimo' => 'nullable|numeric|min:0',
            'stock_inicial' => 'nullable|numeric|min:0',
            'bodega_id' => 'nullable|exists:inventario_bodegas,id',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $costo = (float) ($validated['costo_promedio'] ?? 0.00);
            $stockInicial = (float) ($validated['stock_inicial'] ?? 0);
            $stockMin = (float) ($validated['stock_minimo'] ?? 5);
            $bodegaId = $validated['bodega_id'] ?? 1;

            $prod = Producto::create([
                'categoria_id' => $validated['categoria_id'],
                'codigo_principal' => trim($validated['codigo_principal']),
                'codigo_auxiliar' => ! empty($validated['codigo_auxiliar']) ? trim($validated['codigo_auxiliar']) : null,
                'nombre' => trim($validated['nombre']),
                'tipo_producto' => $validated['tipo_producto'],
                'precio_unitario' => (float) $validated['precio_unitario'],
                'costo_promedio' => $costo,
                'codigo_iva' => $validated['codigo_iva'],
                'estado' => 'ACTIVO',
            ]);

            // Create inventory entry for all existing bodegas
            $bodegas = Bodega::all();
            if ($bodegas->isEmpty()) {
                InventarioGeneral::create([
                    'bodega_id' => 1,
                    'producto_id' => $prod->id,
                    'stock_actual' => $stockInicial,
                    'stock_minimo' => $stockMin,
                ]);
            } else {
                foreach ($bodegas as $b) {
                    $qty = ($b->id == $bodegaId) ? $stockInicial : 0;
                    InventarioGeneral::create([
                        'bodega_id' => $b->id,
                        'producto_id' => $prod->id,
                        'stock_actual' => $qty,
                        'stock_minimo' => $stockMin,
                    ]);
                }
            }

            // If initial stock is provided, log movement in Kardex
            if ($stockInicial > 0 && $validated['tipo_producto'] === 'BIEN') {
                $mov = Movimiento::create([
                    'bodega_id' => $bodegaId,
                    'tipo_movimiento_id' => 5, // AJUSTE INGRESO / INICIAL
                    'usuario_id' => 1, // Reference to usuarios_usuario ID
                    'fecha_movimiento' => now(),
                    'referencia' => 'INV-INICIAL-'.strtoupper($prod->codigo_principal),
                    'observaciones' => 'Registro de inventario inicial al crear el producto',
                ]);

                MovimientoDetalle::create([
                    'movimiento_id' => $mov->id,
                    'producto_id' => $prod->id,
                    'cantidad' => $stockInicial,
                    'costo_unitario' => $costo,
                    'costo_total' => round($stockInicial * $costo, 2),
                ]);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'producto' => $prod,
                    'message' => 'Producto registrado exitosamente en el catálogo',
                ]);
            }

            return redirect()->route('productos.index')->with('success', 'Producto registrado exitosamente en el catálogo');
        });
    }

    public function update(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);

        $validated = $request->validate([
            'codigo_principal' => 'required|string|max:25|unique:inventario_productos,codigo_principal,'.$producto->id,
            'codigo_auxiliar' => 'nullable|string|max:25',
            'nombre' => 'required|string|max:300',
            'categoria_id' => 'required|exists:inventario_categorias,id',
            'tipo_producto' => 'required|in:BIEN,SERVICIO',
            'precio_unitario' => 'required|numeric|min:0',
            'costo_promedio' => 'nullable|numeric|min:0',
            'codigo_iva' => 'required|string|in:0,2,4',
            'stock_minimo' => 'nullable|numeric|min:0',
            'estado' => 'required|in:ACTIVO,INACTIVO',
        ]);

        return DB::transaction(function () use ($validated, $producto, $request) {
            $producto->update([
                'categoria_id' => $validated['categoria_id'],
                'codigo_principal' => trim($validated['codigo_principal']),
                'codigo_auxiliar' => ! empty($validated['codigo_auxiliar']) ? trim($validated['codigo_auxiliar']) : null,
                'nombre' => trim($validated['nombre']),
                'tipo_producto' => $validated['tipo_producto'],
                'precio_unitario' => (float) $validated['precio_unitario'],
                'costo_promedio' => (float) ($validated['costo_promedio'] ?? $producto->costo_promedio),
                'codigo_iva' => $validated['codigo_iva'],
                'estado' => $validated['estado'],
            ]);

            // Update minimum stock across warehouse entries
            if (isset($validated['stock_minimo'])) {
                InventarioGeneral::where('producto_id', $producto->id)
                    ->update(['stock_minimo' => (float) $validated['stock_minimo']]);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'producto' => $producto,
                    'message' => 'Producto actualizado exitosamente',
                ]);
            }

            return redirect()->route('productos.index')->with('success', 'Producto actualizado exitosamente');
        });
    }

    public function toggleEstado($id)
    {
        $producto = Producto::findOrFail($id);
        $producto->estado = ($producto->estado === 'ACTIVO') ? 'INACTIVO' : 'ACTIVO';
        $producto->save();

        $estadoStr = $producto->estado === 'ACTIVO' ? 'activado' : 'desactivado';

        return redirect()->route('productos.index')->with('success', "Producto {$producto->nombre} {$estadoStr} correctamente");
    }

    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);

        $hasMovements = $producto->movimientoDetalles()->count() > 0;
        $hasPurchases = $producto->compraDetalles()->count() > 0;
        $hasSales = $producto->facturaDetalles()->count() > 0;

        if ($hasMovements || $hasPurchases || $hasSales) {
            $producto->estado = 'INACTIVO';
            $producto->save();

            return redirect()->route('productos.index')->with('warning', 'El producto tiene historial de movimientos, ventas o compras. Se cambió su estado a INACTIVO para proteger el Kardex y la facturación.');
        }

        InventarioGeneral::where('producto_id', $producto->id)->delete();
        $producto->delete();

        return redirect()->route('productos.index')->with('success', 'Producto eliminado exitosamente del catálogo');
    }

    public function categoriaStore(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:inventario_categorias,nombre',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $categoria = Categoria::create([
            'nombre' => trim($validated['nombre']),
            'descripcion' => $validated['descripcion'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'categoria' => $categoria,
            'message' => 'Categoría creada exitosamente',
        ]);
    }

    public function export(Request $request)
    {
        $query = Producto::with(['categoria', 'inventarios']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('codigo_principal', 'LIKE', "%{$search}%")
                    ->orWhere('codigo_auxiliar', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('categoria_id') && $request->categoria_id !== 'todas') {
            $query->where('categoria_id', $request->categoria_id);
        }

        $productos = $query->orderBy('nombre', 'asc')->get();

        $filename = 'catalogo_productos_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($productos) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Código Principal (SKU)',
                'Código Auxiliar',
                'Nombre del Producto',
                'Categoría',
                'Tipo',
                'Precio Venta ($)',
                'Costo Promedio ($)',
                'Margen Utilidad ($)',
                'Margen (%)',
                'Tarifa IVA',
                'Precio Con IVA ($)',
                'Stock Total',
                'Stock Mínimo',
                'Estado Stock',
                'Valor Inventario Costo ($)',
                'Valor Inventario Venta ($)',
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
                    number_format($p->precio_unitario, 2, '.', ''),
                    number_format($p->costo_promedio ?? 0, 2, '.', ''),
                    number_format($p->margen_ganancia, 2, '.', ''),
                    $p->margen_porcentaje.'%',
                    $p->iva_texto,
                    number_format($p->precio_con_iva, 2, '.', ''),
                    $p->stock_total,
                    $p->stock_minimo_total,
                    $p->estado_stock,
                    number_format($p->valor_inventario_costo, 2, '.', ''),
                    number_format($p->valor_inventario_venta, 2, '.', ''),
                    $p->estado,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
