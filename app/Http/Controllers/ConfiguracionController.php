<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionEmpresa;
use App\Models\MetodoPago;
use App\Models\PuntoEmision;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $emisorModel = ConfiguracionEmpresa::first();
        $emisor = [
            'ruc' => $emisorModel ? $emisorModel->ruc : '',
            'razon_social' => $emisorModel ? $emisorModel->razon_social : '',
            'nombre_comercial' => $emisorModel ? $emisorModel->nombre_comercial : '',
            'direccion_matriz' => $emisorModel ? $emisorModel->direccion_matriz : '',
            'regimen_rimpe' => $emisorModel ? $emisorModel->regimen_rimpe : 'NO APLICA',
            'ambiente_sri' => $emisorModel ? $emisorModel->ambiente_sri : 1,
            'firma_ruta' => $emisorModel ? $emisorModel->firma_ruta : '',
            'firma_clave' => $emisorModel ? $emisorModel->firma_clave : '',
        ];

        $puntos_emision = PuntoEmision::all()->map(function ($p) {
            return [
                'id' => $p->id,
                'establecimiento' => $p->establecimiento,
                'punto_emision' => $p->punto_emision,
                'secuencial_factura' => $p->secuencial_factura,
                'secuencial_nota_credito' => $p->secuencial_nota_credito,
                'estado' => $p->estado,
            ];
        });

        $metodos_pago_sri = MetodoPago::all()->map(function ($m) {
            return [
                'id' => $m->id,
                'codigo' => $m->codigo_sri,
                'nombre' => $m->nombre,
                'estado' => $m->estado,
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
            'ambiente_sri' => 'nullable|integer|in:1,2',
            'firma_archivo' => 'nullable|file|mimes:p12,pfx|max:2048',
            'firma_clave' => 'nullable|string',
        ]);

        $emisor = ConfiguracionEmpresa::first();
        if (! $emisor) {
            $emisor = new ConfiguracionEmpresa;
        }

        $emisor->ruc = $validated['ruc'] ?? $emisor->ruc;
        $emisor->razon_social = $validated['razon_social'] ?? $emisor->razon_social;
        $emisor->nombre_comercial = $validated['nombre_comercial'] ?? $emisor->nombre_comercial;
        $emisor->direccion_matriz = $validated['direccion_matriz'] ?? $emisor->direccion_matriz;
        $emisor->regimen_rimpe = $validated['regimen_rimpe'] ?? $emisor->regimen_rimpe;
        $emisor->ambiente_sri = $validated['ambiente_sri'] ?? $emisor->ambiente_sri;

        if ($request->filled('firma_clave')) {
            $emisor->firma_clave = $validated['firma_clave'];
        }

        if ($request->hasFile('firma_archivo')) {
            $file = $request->file('firma_archivo');
            $filename = time().'_'.$file->getClientOriginalName();
            $path = $file->storeAs('firmas', $filename, 'local');
            $emisor->firma_ruta = $path;
        }

        $emisor->save();

        return redirect('/configuracion')->with('success', 'Configuración actualizada correctamente');
    }
}
