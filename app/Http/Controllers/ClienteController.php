<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::all()->map(function ($c) {
            return [
                'id' => $c->id,
                'tipo_identificacion' => $c->tipo_identificacion,
                'tipo_nombre' => $c->tipo_nombre,
                'identificacion' => $c->identificacion,
                'razon_social' => $c->razon_social,
                'direccion' => $c->direccion ?? 'N/A',
                'telefono' => $c->telefono ?? 'N/A',
                'correo' => $c->correo ?? 'N/A',
                'obligado_contabilidad' => $c->obligado_contabilidad
            ];
        });

        return view('clientes.index', compact('clientes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_identificacion' => 'required|string|max:2',
            'identificacion' => 'required|string|max:20|unique:clientes_cliente,identificacion',
            'razon_social' => 'required|string|max:300',
            'correo' => 'required|email|max:150',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:300',
            'obligado_contabilidad' => 'nullable|in:SI,NO'
        ]);

        $cliente = Cliente::create([
            'tipo_identificacion' => $validated['tipo_identificacion'],
            'identificacion' => $validated['identificacion'],
            'razon_social' => $validated['razon_social'],
            'correo' => $validated['correo'],
            'telefono' => $validated['telefono'] ?? null,
            'direccion' => $validated['direccion'] ?? null,
            'obligado_contabilidad' => $validated['obligado_contabilidad'] ?? 'NO'
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cliente' => $cliente,
                'message' => 'Cliente guardado exitosamente'
            ]);
        }

        return redirect('/clientes')->with('success', 'Cliente guardado exitosamente');
    }
}
