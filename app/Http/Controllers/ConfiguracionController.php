<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionEmpresa;
use App\Models\Emisor;
use App\Models\MetodoPago;
use App\Models\PuntoEmision;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $emisorModel = ConfiguracionEmpresa::first();
        $emisorReal = Emisor::first();
        $emisor = [
            'ruc' => $emisorModel ? $emisorModel->ruc : '',
            'razon_social' => $emisorModel ? $emisorModel->razon_social : '',
            'nombre_comercial' => $emisorModel ? $emisorModel->nombre_comercial : '',
            'direccion_matriz' => $emisorModel ? $emisorModel->direccion_matriz : '',
            'regimen_rimpe' => $emisorModel ? $emisorModel->regimen_rimpe : 'NO APLICA',
            'ambiente_sri' => $emisorModel ? $emisorModel->ambiente_sri : 1,
            'firma_ruta' => $emisorModel ? $emisorModel->firma_ruta : '',
            'firma_clave' => $emisorModel ? $emisorModel->firma_clave : '',
            'iva_defecto' => $emisorModel ? $emisorModel->iva_defecto : 15,
            'obligado_contabilidad' => $emisorReal ? $emisorReal->obligado_contabilidad : 'NO',
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
            'obligado_contabilidad' => 'nullable|string|in:SI,NO',
            'ambiente_sri' => 'nullable|integer|in:1,2',
            'firma_archivo' => 'nullable|file|max:2048',
            'firma_clave' => 'nullable|string',
            'establecimiento' => 'nullable|string|size:3',
            'punto_emision' => 'nullable|string|size:3',
            'secuencial_factura' => 'nullable|integer|min:1',
            'iva_defecto' => 'nullable|numeric|min:0|max:100',
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
        $emisor->iva_defecto = $validated['iva_defecto'] ?? $emisor->iva_defecto;

        if ($request->filled('firma_clave')) {
            $emisor->firma_clave = $validated['firma_clave'];
        }

        if ($request->hasFile('firma_archivo')) {
            $file = $request->file('firma_archivo');
            $filename = time().'_'.$file->getClientOriginalName();
            $path = $file->storeAs('certificados', $filename, 'local'); // Cambiado a certificados
            $emisor->firma_ruta = $path;
        }

        $emisor->save();

        // Sincronizar también con la tabla configuracion_emisor para mantener compatibilidad
        $config_emisor = Emisor::first();
        if ($config_emisor) {
            $config_emisor->ruc = $emisor->ruc;
            $config_emisor->razon_social = $emisor->razon_social;
            $config_emisor->nombre_comercial = $emisor->nombre_comercial;
            $config_emisor->direccion_matriz = $emisor->direccion_matriz;
            $config_emisor->regimen_rimpe = $emisor->regimen_rimpe;
            $config_emisor->ambiente_sri = $emisor->ambiente_sri;

            if (isset($validated['obligado_contabilidad'])) {
                $config_emisor->obligado_contabilidad = $validated['obligado_contabilidad'];
            }

            if ($request->filled('firma_clave')) {
                $config_emisor->firma_electronica_clave = $emisor->firma_clave;
            }
            if ($request->hasFile('firma_archivo')) {
                $config_emisor->firma_electronica_ruta = $emisor->firma_ruta;
            }
            $config_emisor->save();
        }

        // Actualizar Punto de Emision
        $punto = PuntoEmision::where('estado', 'ACTIVO')->first();
        if (! $punto) {
            $punto = new PuntoEmision;
            $punto->estado = 'ACTIVO';
        }
        if ($request->filled('establecimiento')) {
            $punto->establecimiento = str_pad($request->establecimiento, 3, '0', STR_PAD_LEFT);
        }
        if ($request->filled('punto_emision')) {
            $punto->punto_emision = str_pad($request->punto_emision, 3, '0', STR_PAD_LEFT);
        }
        if ($request->filled('secuencial_factura')) {
            $punto->secuencial_factura = $request->secuencial_factura;
        }
        if ($request->filled('secuencial_nota_credito')) {
            $punto->secuencial_nota_credito = $request->secuencial_nota_credito;
        }
        $punto->save();

        return redirect('/configuracion')->with('success', 'Configuración actualizada correctamente');
    }
}
