<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\InventarioGeneral;
use Illuminate\Support\Facades\DB;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::with(['categoria', 'inventarios'])
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'codigo_principal' => $p->codigo_principal,
                    'nombre' => $p->nombre,
                    'categoria' => $p->categoria->nombre ?? 'General',
                    'precio_unitario' => (float) $p->precio_unitario,
                    'costo_promedio' => (float) $p->costo_promedio,
                    'stock_actual' => (float) $p->stock_total,
                    'stock_minimo' => (float) $p->stock_minimo_total,
                    'estado_stock' => $p->estado_stock,
                    'tipo_producto' => $p->tipo_producto,
                    'iva' => $p->iva_texto
                ];
            });

        $categorias = Categoria::pluck('nombre')->toArray();

        return view('productos.index', compact('productos', 'categorias'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_principal' => 'required|string|max:25|unique:inventario_productos,codigo_principal',
            'nombre' => 'required|string|max:300',
            'categoria' => 'required|string',
            'tipo_producto' => 'required|in:BIEN,SERVICIO',
            'precio_unitario' => 'required|numeric|min:0',
            'costo_promedio' => 'nullable|numeric|min:0',
            'codigo_iva' => 'required|string',
            'stock_minimo' => 'nullable|numeric|min:0',
            'stock_inicial' => 'nullable|numeric|min:0'
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $cat = Categoria::firstOrCreate(['nombre' => $validated['categoria']]);

            $prod = Producto::create([
                'categoria_id' => $cat->id,
                'codigo_principal' => $validated['codigo_principal'],
                'nombre' => $validated['nombre'],
                'tipo_producto' => $validated['tipo_producto'],
                'precio_unitario' => $validated['precio_unitario'],
                'costo_promedio' => $validated['costo_promedio'] ?? 0.00,
                'codigo_iva' => $validated['codigo_iva'],
                'estado' => 'ACTIVO'
            ]);

            // Create inventory entry for Bodega 1
            InventarioGeneral::create([
                'bodega_id' => 1,
                'producto_id' => $prod->id,
                'stock_actual' => $validated['stock_inicial'] ?? 0,
                'stock_minimo' => $validated['stock_minimo'] ?? 5
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'producto' => $prod,
                    'message' => 'Producto guardado exitosamente'
                ]);
            }

            return redirect('/productos')->with('success', 'Producto registrado exitosamente');
        });
    }
}
