<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Proveedor;
use App\Models\Bodega;
use App\Models\Producto;
use App\Models\InventarioGeneral;
use App\Models\Movimiento;
use App\Models\MovimientoDetalle;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function index()
    {
        $compras = Compra::with(['proveedor', 'bodega', 'usuario', 'detalles'])
            ->latest('id')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'fecha' => $c->fecha_emision,
                    'proveedor' => $c->proveedor->razon_social ?? 'Proveedor Desconocido',
                    'referencia_factura' => $c->numero_factura,
                    'bodega' => $c->bodega->nombre ?? 'Bodega Principal',
                    'total_compra' => (float) $c->total,
                    'usuario' => $c->usuario->nombre_completo ?? 'Juan Pérez',
                    'items_count' => $c->detalles->count() > 0 ? $c->detalles->count() : 1
                ];
            });

        $proveedores = Proveedor::all()->map(function ($p) {
            return "{$p->razon_social} ({$p->identificacion})";
        })->toArray();

        $bodegas = Bodega::pluck('nombre')->toArray();

        return view('compras.index', compact('compras', 'proveedores', 'bodegas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'proveedor_id' => 'nullable|exists:compras_proveedores,id',
            'proveedor_nombre' => 'nullable|string',
            'numero_factura' => 'required|string|max:50',
            'fecha_emision' => 'required|date',
            'bodega_id' => 'nullable|exists:inventario_bodegas,id',
            'producto_id' => 'nullable|exists:inventario_productos,id',
            'cantidad' => 'nullable|numeric|min:0.01',
            'costo_unitario' => 'nullable|numeric|min:0'
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $proveedorId = $validated['proveedor_id'] ?? 1;
            $bodegaId = $validated['bodega_id'] ?? 1;
            $prodId = $validated['producto_id'] ?? 1;
            $cantidad = (float) ($validated['cantidad'] ?? 10);
            $costoUnit = (float) ($validated['costo_unitario'] ?? 15.00);
            $subtotal = round($cantidad * $costoUnit, 2);
            $iva = round($subtotal * 0.15, 2);
            $total = $subtotal + $iva;

            $compra = Compra::create([
                'proveedor_id' => $proveedorId,
                'bodega_id' => $bodegaId,
                'usuario_id' => 1,
                'numero_factura' => $validated['numero_factura'],
                'fecha_emision' => $validated['fecha_emision'],
                'subtotal_sin_impuestos' => $subtotal,
                'iva' => $iva,
                'total' => $total,
                'observaciones' => 'Ingreso de mercadería por compra'
            ]);

            CompraDetalle::create([
                'compra_id' => $compra->id,
                'producto_id' => $prodId,
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnit,
                'costo_total' => $subtotal
            ]);

            // Increase stock in bodega
            $inv = InventarioGeneral::firstOrCreate(
                ['bodega_id' => $bodegaId, 'producto_id' => $prodId],
                ['stock_actual' => 0, 'stock_minimo' => 5]
            );
            $inv->stock_actual += $cantidad;
            $inv->save();

            // Register Movement in Kardex (COMPRA = 1)
            $mov = Movimiento::create([
                'bodega_id' => $bodegaId,
                'tipo_movimiento_id' => 1, // COMPRA
                'usuario_id' => 1,
                'fecha_movimiento' => now(),
                'referencia' => $validated['numero_factura'],
                'observaciones' => "Ingreso por compra #{$validated['numero_factura']}"
            ]);

            MovimientoDetalle::create([
                'movimiento_id' => $mov->id,
                'producto_id' => $prodId,
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnit,
                'costo_total' => $subtotal
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'compra' => $compra,
                    'message' => 'Compra registrada e inventario aumentado exitosamente'
                ]);
            }

            return redirect('/compras')->with('success', 'Compra e inventario registrados exitosamente');
        });
    }
}
