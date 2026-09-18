<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Shuchkin\SimpleXLSXGen;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $query = Proveedor::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('razon_social', 'LIKE', "%{$search}%")
                ->orWhere('identificacion', 'LIKE', "%{$search}%")
                ->orWhere('correo', 'LIKE', "%{$search}%");
        }

        $proveedores = $query->paginate(10)->through(function ($p) {
            return [
                'id' => $p->id,
                'tipo_identificacion' => $p->tipo_identificacion,
                'tipo_nombre' => $p->tipo_nombre,
                'identificacion' => $p->identificacion,
                'razon_social' => $p->razon_social,
                'direccion' => $p->direccion ?? 'N/A',
                'telefono' => $p->telefono ?? 'N/A',
                'correo' => $p->correo ?? 'N/A',
            ];
        });

        return view('proveedores.index', compact('proveedores'));
    }

    public function store(Request $request)
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
            'identificacion' => $validated['identificacion'],
            'razon_social' => $validated['razon_social'],
            'correo' => $validated['correo'],
            'telefono' => $validated['telefono'] ?? null,
            'direccion' => $validated['direccion'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'proveedor' => $proveedor,
                'message' => 'Proveedor guardado exitosamente',
            ]);
        }

        return redirect()->route('proveedores.index')->with('success', 'Proveedor guardado exitosamente');
    }

    public function show(Request $request, $id)
    {
        $proveedor = Proveedor::findOrFail($id);

        // Calculate financial summary using aggregations
        $resumen = DB::table('compras_facturas')
            ->where('proveedor_id', $proveedor->id)
            ->select(
                DB::raw('COUNT(*) as total_facturas'),
                DB::raw('COALESCE(SUM(total), 0) as total_comprado'),
                DB::raw('COALESCE(SUM(iva), 0) as total_iva'),
                DB::raw('COALESCE(SUM(subtotal_sin_impuestos), 0) as total_subtotal'),
                DB::raw('MAX(fecha_emision) as ultima_compra'),
                DB::raw('MIN(fecha_emision) as primera_compra')
            )
            ->first();

        $resumen->promedio_factura = $resumen->total_facturas > 0 ? ($resumen->total_comprado / $resumen->total_facturas) : 0;

        // Fetch purchase history (paginated)
        $comprasQuery = $proveedor->compras()->with(['detalles.producto', 'usuario']);

        if ($request->filled('fecha_desde')) {
            $comprasQuery->where('fecha_emision', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $comprasQuery->where('fecha_emision', '<=', $request->fecha_hasta);
        }
        if ($request->filled('numero_factura')) {
            $comprasQuery->where('numero_factura', 'LIKE', "%{$request->numero_factura}%");
        }

        $compras = $comprasQuery->orderBy('fecha_emision', 'desc')->paginate(10);

        return view('proveedores.show', compact('proveedor', 'resumen', 'compras'));
    }

    public function update(Request $request, $id)
    {
        $proveedor = Proveedor::findOrFail($id);

        $validated = $request->validate([
            'tipo_identificacion' => 'required|string|max:2',
            'identificacion' => 'required|string|max:20|unique:compras_proveedores,identificacion,'.$proveedor->id,
            'razon_social' => 'required|string|max:300',
            'correo' => 'required|email|max:150',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:300',
        ]);

        $proveedor->update([
            'tipo_identificacion' => $validated['tipo_identificacion'],
            'identificacion' => $validated['identificacion'],
            'razon_social' => $validated['razon_social'],
            'correo' => $validated['correo'],
            'telefono' => $validated['telefono'] ?? null,
            'direccion' => $validated['direccion'] ?? null,
        ]);

        return redirect()->route('proveedores.index')->with('success', 'Proveedor actualizado exitosamente');
    }

    public function destroy($id)
    {
        $proveedor = Proveedor::findOrFail($id);

        if ($proveedor->compras()->count() > 0) {
            return redirect()->route('proveedores.index')->with('error', 'No se puede eliminar el proveedor porque tiene facturas de compra asociadas.');
        }

        $proveedor->delete();

        return redirect()->route('proveedores.index')->with('success', 'Proveedor eliminado exitosamente');
    }

    public function export(Request $request)
    {
        $query = Proveedor::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('razon_social', 'LIKE', "%{$search}%")
                ->orWhere('identificacion', 'LIKE', "%{$search}%")
                ->orWhere('correo', 'LIKE', "%{$search}%");
        }

        $proveedores = $query->withCount('compras as facturas_count')
            ->withSum('compras as total_comprado', 'total')
            ->withMax('compras as ultima_compra', 'fecha_emision')
            ->get();

        $filename = 'proveedores_'.date('Ymd_His').'.xlsx';

        $data = [
            [
                'Tipo Documento',
                'Identificación',
                'Razón Social',
                'Correo',
                'Teléfono',
                'Dirección',
                'Nro Facturas',
                'Total Comprado',
                'Última Compra',
            ],
        ];

        foreach ($proveedores as $p) {
            $data[] = [
                $p->tipo_nombre,
                $p->identificacion,
                $p->razon_social,
                $p->correo,
                $p->telefono,
                $p->direccion,
                $p->facturas_count ?? 0,
                $p->total_comprado ? number_format($p->total_comprado, 2) : '0.00',
                $p->ultima_compra ?? 'N/A',
            ];
        }

        $xlsx = SimpleXLSXGen::fromArray($data);
        $xlsx->downloadAs($filename);
        exit;
    }

    public function exportPdf(Request $request, $id)
    {
        $proveedor = Proveedor::findOrFail($id);

        $periodo = $request->input('periodo', 'all');
        $incluirGeneral = $request->boolean('incluir_general', true);
        $incluirResumen = $request->boolean('incluir_resumen', true);
        $incluirFacturas = $request->boolean('incluir_facturas', true);
        $incluirDetalles = $request->boolean('incluir_detalles', false);

        $comprasQuery = $proveedor->compras()->orderBy('fecha_emision', 'desc');

        if ($incluirDetalles) {
            $comprasQuery->with('detalles.producto');
        }

        if (is_numeric($periodo)) {
            $comprasQuery->limit((int) $periodo);
        }

        $compras = $comprasQuery->get();

        $resumen = null;
        if ($incluirResumen) {
            $resumen = (object) [
                'total_facturas' => $compras->count(),
                'total_comprado' => $compras->sum('total'),
                'total_iva' => $compras->sum('iva'),
                'promedio_factura' => $compras->count() > 0 ? ($compras->sum('total') / $compras->count()) : 0,
                'ultima_compra' => $compras->max('fecha_emision'),
            ];
        }

        $pdf = Pdf::loadView('proveedores.pdf', compact(
            'proveedor', 'compras', 'resumen',
            'incluirGeneral', 'incluirResumen', 'incluirFacturas', 'incluirDetalles'
        ));

        // Adjust paper if there are many details
        if ($incluirDetalles) {
            $pdf->setPaper('A4', 'landscape');
        } else {
            $pdf->setPaper('A4', 'portrait');
        }

        return $pdf->download('reporte_proveedor_'.$proveedor->identificacion.'_'.date('YmdHis').'.pdf');
    }
}
