<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Shuchkin\SimpleXLSXGen;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('razon_social', 'LIKE', "%{$search}%")
                ->orWhere('identificacion', 'LIKE', "%{$search}%")
                ->orWhere('correo', 'LIKE', "%{$search}%");
        }

        $clientes = $query->paginate(10)->through(function ($c) {
            return [
                'id' => $c->id,
                'tipo_identificacion' => $c->tipo_identificacion,
                'tipo_nombre' => $c->tipo_nombre,
                'identificacion' => $c->identificacion,
                'razon_social' => $c->razon_social,
                'direccion' => $c->direccion ?? 'N/A',
                'telefono' => $c->telefono ?? 'N/A',
                'correo' => $c->correo ?? 'N/A',
                'obligado_contabilidad' => $c->obligado_contabilidad,
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
            'obligado_contabilidad' => 'nullable|in:SI,NO',
        ]);

        $cliente = Cliente::create([
            'tipo_identificacion' => $validated['tipo_identificacion'],
            'identificacion' => $validated['identificacion'],
            'razon_social' => $validated['razon_social'],
            'correo' => $validated['correo'],
            'telefono' => $validated['telefono'] ?? null,
            'direccion' => $validated['direccion'] ?? null,
            'obligado_contabilidad' => $validated['obligado_contabilidad'] ?? 'NO',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cliente' => $cliente,
                'message' => 'Cliente guardado exitosamente',
            ]);
        }

        return redirect()->route('clientes.index')->with('success', 'Cliente guardado exitosamente');
    }

    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);

        $validated = $request->validate([
            'tipo_identificacion' => 'required|string|max:2',
            'identificacion' => 'required|string|max:20|unique:clientes_cliente,identificacion,'.$cliente->id,
            'razon_social' => 'required|string|max:300',
            'correo' => 'required|email|max:150',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:300',
            'obligado_contabilidad' => 'nullable|in:SI,NO',
        ]);

        $cliente->update([
            'tipo_identificacion' => $validated['tipo_identificacion'],
            'identificacion' => $validated['identificacion'],
            'razon_social' => $validated['razon_social'],
            'correo' => $validated['correo'],
            'telefono' => $validated['telefono'] ?? null,
            'direccion' => $validated['direccion'] ?? null,
            'obligado_contabilidad' => $validated['obligado_contabilidad'] ?? 'NO',
        ]);

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado exitosamente');
    }

    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);

        // Prevent deleting if it has facturas, or just delete.
        if ($cliente->facturas()->count() > 0) {
            return redirect()->route('clientes.index')->with('error', 'No se puede eliminar el cliente porque tiene facturas asociadas.');
        }

        $cliente->delete();

        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado exitosamente');
    }

    public function export(Request $request)
    {
        $query = Cliente::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('razon_social', 'LIKE', "%{$search}%")
                ->orWhere('identificacion', 'LIKE', "%{$search}%")
                ->orWhere('correo', 'LIKE', "%{$search}%");
        }

        $clientes = $query->get();

        $filename = 'clientes_'.date('Ymd_His').'.xlsx';

        $data = [
            [
                'Tipo Documento',
                'Identificación',
                'Razón Social',
                'Correo',
                'Teléfono',
                'Dirección',
                'Obligado Contabilidad',
            ],
        ];

        foreach ($clientes as $c) {
            $data[] = [
                $c->tipo_nombre,
                $c->identificacion,
                $c->razon_social,
                $c->correo,
                $c->telefono,
                $c->direccion,
                $c->obligado_contabilidad,
            ];
        }

        $xlsx = SimpleXLSXGen::fromArray($data);
        $xlsx->downloadAs($filename);
        exit;
    }
}
