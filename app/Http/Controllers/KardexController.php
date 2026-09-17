<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bodega;
use App\Models\Producto;
use App\Models\InventarioGeneral;
use App\Models\Movimiento;
use App\Models\MovimientoDetalle;
use Illuminate\Support\Facades\DB;

class KardexController extends Controller
{
    public function index(Request $request)
    {
        $bodegas = Bodega::pluck('nombre')->toArray();
        array_unshift($bodegas, 'Todas las Bodegas');

        $query = DB::table('vw_inventario_kardex');

        if ($request->filled('bodega') && $request->bodega !== 'Todas las Bodegas') {
            $query->where('bodega', $request->bodega);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('producto', 'like', "%{$search}%")
                  ->orWhere('codigo_producto', 'like', "%{$search}%")
                  ->orWhere('referencia', 'like', "%{$search}%");
            });
        }

        $movimientos = $query->orderBy('fecha_movimiento', 'desc')
            ->get()
            ->map(function ($m) {
                return [
                    'id_movimiento' => $m->id_movimiento,
                    'fecha_movimiento' => $m->fecha_movimiento,
                    'bodega' => $m->bodega,
                    'concepto_movimiento' => $m->concepto_movimiento,
                    'tipo' => $m->tipo,
                    'referencia' => $m->referencia,
                    'codigo_producto' => $m->codigo_producto,
                    'producto' => $m->producto,
                    'cantidad' => (float) $m->cantidad,
                    'costo_unitario' => (float) $m->costo_unitario,
                    'costo_total' => (float) $m->costo_total,
                    'variacion_stock' => (float) $m->variacion_stock,
                    'stock_resultante' => (float) $m->cantidad // Or cumulative calculation
                ];
            });

        $productos = Producto::all();

        return view('kardex.index', compact('movimientos', 'bodegas', 'productos'));
    }

    public function storeAjuste(Request $request)
    {
        $validated = $request->validate([
            'producto_id' => 'required|exists:inventario_productos,id',
            'tipo_movimiento_id' => 'required|in:5,6', // 5=Ajuste Ingreso, 6=Ajuste Egreso
            'cantidad' => 'required|numeric|min:0.01',
            'observaciones' => 'nullable|string|max:255'
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $prod = Producto::find($validated['producto_id']);
            $cantidad = (float) $validated['cantidad'];
            $factor = $validated['tipo_movimiento_id'] == 5 ? 1 : -1;

            $inv = InventarioGeneral::firstOrCreate(
                ['bodega_id' => 1, 'producto_id' => $prod->id],
                ['stock_actual' => 0, 'stock_minimo' => 5]
            );

            $inv->stock_actual = max(0, $inv->stock_actual + ($cantidad * $factor));
            $inv->save();

            $mov = Movimiento::create([
                'bodega_id' => 1,
                'tipo_movimiento_id' => $validated['tipo_movimiento_id'],
                'usuario_id' => 1,
                'fecha_movimiento' => now(),
                'referencia' => 'AJUSTE-MANUAL-' . strtoupper(uniqid()),
                'observaciones' => $validated['observaciones'] ?? 'Ajuste manual de inventario'
            ]);

            MovimientoDetalle::create([
                'movimiento_id' => $mov->id,
                'producto_id' => $prod->id,
                'cantidad' => $cantidad,
                'costo_unitario' => $prod->costo_promedio ?? 0.00,
                'costo_total' => round(($prod->costo_promedio ?? 0.00) * $cantidad, 2)
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ajuste registrado exitosamente en el Kardex'
                ]);
            }

            return redirect('/kardex')->with('success', 'Ajuste de inventario registrado correctamente');
        });
    }
}
