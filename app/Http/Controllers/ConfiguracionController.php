<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Emisor;
use App\Models\PuntoEmision;
use App\Models\MetodoPago;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $emisorModel = Emisor::first();
        $emisor = [
            'ruc' => $emisorModel ? $emisorModel->ruc : '1792948201001',
            'razon_social' => $emisorModel ? $emisorModel->razon_social : 'NATURISTA EXPRESS CIA. LTDA.',
            'nombre_comercial' => $emisorModel ? $emisorModel->nombre_comercial : 'NATURISTA EXPRESS',
            'direccion_matriz' => $emisorModel ? $emisorModel->direccion_matriz : 'Av. 10 de Agosto N24-150 y Colón',
            'regimen_rimpe' => $emisorModel ? $emisorModel->regimen_rimpe : 'CONTRIBUYENTE RÉGIMEN RIMPE',
            'ambiente_sri' => $emisorModel ? $emisorModel->ambiente_sri : 1,
            'firma_ruta' => $emisorModel ? $emisorModel->firma_electronica_ruta : 'firma_naturista.p12'
        ];

        $puntos_emision = PuntoEmision::all()->map(function ($p) {
            return [
                'id' => $p->id,
                'establecimiento' => $p->establecimiento,
                'punto_emision' => $p->punto_emision,
                'secuencial_factura' => $p->secuencial_factura,
                'secuencial_nota_credito' => $p->secuencial_nota_credito,
                'estado' => $p->estado
            ];
        });

        $metodos_pago_sri = MetodoPago::all()->map(function ($m) {
            return [
                'id' => $m->id,
                'codigo' => $m->codigo_sri,
                'nombre' => $m->nombre,
                'estado' => $m->estado
            ];
        });

        return view('configuracion.index', compact('emisor', 'puntos_emision', 'metodos_pago_sri'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'ruc' => 'nullable|string|max:13',
            'razon_social' => 'nullable|string|max:300',
            'nombre_comercial' => 'nullable|string|max:300',
            'direccion_matriz' => 'nullable|string|max:300',
            'regimen_rimpe' => 'nullable|string',
            'ambiente_sri' => 'nullable|integer|in:1,2'
        ]);

        $emisor = Emisor::first();
        if ($emisor) {
            $emisor->update(array_filter($validated));
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Configuración actualizada']);
        }

        return redirect('/configuracion')->with('success', 'Configuración actualizada correctamente');
    }
}
