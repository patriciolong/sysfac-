<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\CajaArqueo;
use App\Models\CajaDesglose;
use App\Models\CajaMovimiento;
use App\Models\CajaTurno;
use App\Models\Emisor;
use App\Models\Usuario;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CajaController extends Controller
{
    // Vista principal con tabs
    public function index(Request $request)
    {
        // Obtener la caja principal
        $caja = Caja::first();
        if (! $caja) {
            $caja = Caja::create(['nombre' => 'Caja Principal', 'sucursal' => 'Matriz', 'usuario_id' => 1, 'estado' => 'ACTIVA']);
        }

        $usuarios = Usuario::where('estado', 'ACTIVO')->get();

        // Verificar si hay una caja abierta
        $turnoActual = CajaTurno::with(['caja', 'usuario', 'movimientos.metodoPago'])
            ->where('estado', 'ABIERTA')
            ->latest('id')
            ->first();

        // Determinar tab activo
        $default_tab = $turnoActual ? 'control' : 'apertura';
        $active_tab = $request->query('tab', session('active_cajas_tab', $default_tab));

        // Si quieren ver apertura y ya hay turno, forzar a control
        if ($active_tab == 'apertura' && $turnoActual) {
            $active_tab = 'control';
        }
        session(['active_cajas_tab' => $active_tab]);

        $historial = [];
        $ingresos_manuales = 0;
        $egresos_manuales = 0;
        $saldo_teorico = 0;

        if ($active_tab === 'control' && $turnoActual) {
            $ingresos_manuales = $turnoActual->movimientos()->where('tipo', 'INGRESO')->whereNull('venta_id')->sum('monto');
            $egresos_manuales = $turnoActual->movimientos()->where('tipo', 'EGRESO')->whereNull('venta_id')->sum('monto');
            $saldo_teorico = $turnoActual->monto_inicial + $turnoActual->ventas_efectivo + $ingresos_manuales - $egresos_manuales;
        }

        if ($active_tab === 'historial') {
            $query = CajaTurno::with(['caja', 'usuario', 'arqueos'])->latest('id');

            if ($request->filled('fecha_inicio')) {
                $query->whereDate('fecha_apertura', '>=', $request->fecha_inicio);
            }
            if ($request->filled('fecha_fin')) {
                $query->whereDate('fecha_apertura', '<=', $request->fecha_fin);
            }
            if ($request->filled('usuario_id')) {
                $query->where('usuario_id', $request->usuario_id);
            }

            $historial = $query->paginate(20);
        }

        return view('caja.index', compact(
            'active_tab', 'caja', 'usuarios', 'turnoActual',
            'ingresos_manuales', 'egresos_manuales', 'saldo_teorico', 'historial'
        ));
    }

    // Apertura de caja tab-form
    public function storeApertura(Request $request, $caja_id)
    {
        $caja = Caja::findOrFail($caja_id);

        if ($caja->turnos()->where('estado', 'ABIERTA')->exists()) {
            return redirect()->route('caja.index', ['tab' => 'control'])->with('error', 'Esta caja ya se encuentra abierta.');
        }

        DB::beginTransaction();
        try {
            $turno = CajaTurno::create([
                'caja_id' => $caja->id,
                'punto_emision_id' => 1,
                'usuario_id' => $request->usuario_id,
                'fecha_apertura' => now(),
                'monto_inicial' => $request->saldo_inicial,
                'estado' => 'ABIERTA',
                'observaciones' => $request->observaciones,
                'efectivo_esperado' => $request->saldo_inicial,
                'ventas_efectivo' => 0,
                'ventas_tarjetas' => 0,
                'ventas_transferencia' => 0,
                'total_ventas' => 0,
            ]);

            $desgloses = $request->input('desgloses', []);
            foreach ($desgloses as $denominacion => $cantidad) {
                if ($cantidad > 0) {
                    CajaDesglose::create([
                        'caja_turno_id' => $turno->id,
                        'tipo_operacion' => 'APERTURA',
                        'tipo_moneda' => $denominacion >= 1 ? 'BILLETE' : 'MONEDA',
                        'denominacion' => $denominacion,
                        'cantidad' => $cantidad,
                        'subtotal' => $denominacion * $cantidad,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('caja.index', ['tab' => 'control'])->with('success', 'Caja abierta correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Error al abrir caja: '.$e->getMessage());
        }
    }

    // Movimientos manuales
    public function storeMovimiento(Request $request, $turno_id)
    {
        $turno = CajaTurno::findOrFail($turno_id);

        if ($turno->estado !== 'ABIERTA') {
            return redirect()->back()->with('error', 'No se puede registrar movimientos en una caja cerrada.');
        }

        $validated = $request->validate([
            'tipo' => 'required|in:INGRESO,EGRESO',
            'categoria' => 'required|string',
            'concepto' => 'required|string',
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago_id' => 'required|integer',
            'observaciones' => 'nullable|string',
        ]);

        CajaMovimiento::create([
            'caja_turno_id' => $turno->id,
            'tipo' => $validated['tipo'],
            'categoria' => $validated['categoria'],
            'concepto' => $validated['concepto'],
            'monto' => $validated['monto'],
            'metodo_pago_id' => $validated['metodo_pago_id'],
            'usuario_id' => 1,
            'observaciones' => $validated['observaciones'],
        ]);

        return redirect()->route('caja.index', ['tab' => 'control'])->with('success', 'Movimiento registrado correctamente.');
    }

    // Cierre / Arqueo
    public function storeArqueo(Request $request, $turno_id)
    {
        $turno = CajaTurno::findOrFail($turno_id);

        DB::beginTransaction();
        try {
            $arqueo = CajaArqueo::create([
                'caja_turno_id' => $turno->id,
                'usuario_id' => 1,
                'total_billetes' => $request->total_billetes,
                'total_monedas' => $request->total_monedas,
                'efectivo_contado' => $request->efectivo_contado,
                'saldo_teorico' => $request->saldo_teorico,
                'diferencia' => $request->efectivo_contado - $request->saldo_teorico,
                'tipo_arqueo' => 'CIERRE',
                'observaciones' => $request->observaciones,
            ]);

            $desgloses = $request->input('desgloses', []);
            foreach ($desgloses as $denominacion => $cantidad) {
                if ($cantidad > 0) {
                    CajaDesglose::create([
                        'caja_turno_id' => $turno->id,
                        'caja_arqueo_id' => $arqueo->id,
                        'tipo_operacion' => 'ARQUEO',
                        'tipo_moneda' => $denominacion >= 1 ? 'BILLETE' : 'MONEDA',
                        'denominacion' => $denominacion,
                        'cantidad' => $cantidad,
                        'subtotal' => $denominacion * $cantidad,
                    ]);
                }
            }

            $turno->update([
                'estado' => 'CERRADA',
                'fecha_cierre' => now(),
                'efectivo_real' => $request->efectivo_contado,
                'diferencia' => $arqueo->diferencia,
                'observaciones' => $request->observaciones,
            ]);

            DB::commit();

            return redirect()->route('caja.index', ['tab' => 'historial'])->with('success', 'Caja cerrada correctamente con arqueo.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Error al cerrar caja: '.$e->getMessage());
        }
    }

    // Anular turno
    public function anularTurno($turno_id)
    {
        $turno = CajaTurno::findOrFail($turno_id);

        DB::beginTransaction();
        try {
            $turno->update([
                'estado' => 'ANULADA',
                'observaciones' => ltrim($turno->observaciones.' | CAJA ANULADA POR USUARIO', ' |'),
            ]);

            DB::commit();

            return redirect()->route('caja.index', ['tab' => 'historial'])->with('success', 'Sesión de caja anulada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Error al anular caja: '.$e->getMessage());
        }
    }

    // Reporte PDF Generado
    public function reporte($turno_id)
    {
        $sesion = CajaTurno::with(['caja', 'usuario', 'movimientos.metodoPago', 'arqueos'])->findOrFail($turno_id);

        $ingresos_manuales = $sesion->movimientos()->where('tipo', 'INGRESO')->whereNull('venta_id')->sum('monto');
        $egresos_manuales = $sesion->movimientos()->where('tipo', 'EGRESO')->whereNull('venta_id')->sum('monto');

        $total_efectivo = $sesion->ventas_efectivo;

        $saldo_teorico = $sesion->monto_inicial + $total_efectivo + $ingresos_manuales - $egresos_manuales;

        $movimientos = $sesion->movimientos()->with('metodoPago')->get();
        $arqueo = $sesion->arqueos()->first();

        $pdf = Pdf::loadView('caja.reporte', compact(
            'sesion', 'ingresos_manuales', 'egresos_manuales',
            'total_efectivo', 'saldo_teorico', 'movimientos', 'arqueo'
        ));

        return $pdf->stream('Reporte_Caja_'.str_pad($sesion->id, 6, '0', STR_PAD_LEFT).'.pdf');
    }

    public function getTurnoTicketData($turno_id)
    {
        $sesion = CajaTurno::with(['caja', 'usuario', 'movimientos.metodoPago', 'arqueos.desgloses'])->findOrFail($turno_id);

        return response()->json([
            'success' => true,
            'ticket_data' => $this->formatCierreTicketData($sesion),
        ]);
    }

    public function turnoTicketHtml($turno_id)
    {
        $sesion = CajaTurno::with(['caja', 'usuario', 'movimientos.metodoPago', 'arqueos.desgloses'])->findOrFail($turno_id);
        $ticketData = $this->formatCierreTicketData($sesion);

        return view('caja.ticket_turno_html', compact('sesion', 'ticketData'));
    }

    public function formatCierreTicketData(CajaTurno $sesion): array
    {
        $sesion->loadMissing(['caja', 'usuario', 'movimientos.metodoPago', 'arqueos.desgloses']);

        $emisor = Emisor::first();
        $ingresos_manuales = (float) $sesion->movimientos()->where('tipo', 'INGRESO')->whereNull('venta_id')->sum('monto');
        $egresos_manuales = (float) $sesion->movimientos()->where('tipo', 'EGRESO')->whereNull('venta_id')->sum('monto');
        $total_efectivo = (float) $sesion->ventas_efectivo;
        $saldo_teorico = (float) ($sesion->monto_inicial + $total_efectivo + $ingresos_manuales - $egresos_manuales);
        $arqueo = $sesion->arqueos()->first();

        $movimientos = $sesion->movimientos->map(function ($m) {
            return [
                'tipo' => $m->tipo,
                'categoria' => $m->categoria,
                'concepto' => $m->concepto,
                'monto' => (float) $m->monto,
                'metodo' => $m->metodoPago->nombre ?? 'EFECTIVO',
                'comprobante' => $m->comprobante,
            ];
        })->toArray();

        return [
            'type' => 'cierre_caja',
            'turno_id' => $sesion->id,
            'caja' => $sesion->caja->nombre ?? 'Caja Principal',
            'sucursal' => $sesion->caja->sucursal ?? 'Matriz',
            'usuario' => $sesion->usuario->nombre_completo ?? ($sesion->usuario->nombre ?? 'CAJERO'),
            'fecha_apertura' => $sesion->fecha_apertura ? date('d/m/Y H:i', strtotime($sesion->fecha_apertura)) : '',
            'fecha_cierre' => $sesion->fecha_cierre ? date('d/m/Y H:i', strtotime($sesion->fecha_cierre)) : date('d/m/Y H:i'),
            'estado' => $sesion->estado,
            'emisor' => [
                'ruc' => $emisor->ruc ?? '1790000000001',
                'razon_social' => $emisor->razon_social ?? 'EMPRESA DEMO S.A.',
                'nombre_comercial' => $emisor->nombre_comercial ?? '',
                'direccion_matriz' => $emisor->direccion_matriz ?? 'Ecuador',
            ],
            'monto_inicial' => (float) $sesion->monto_inicial,
            'ventas_efectivo' => (float) $sesion->ventas_efectivo,
            'ventas_tarjetas' => (float) $sesion->ventas_tarjetas,
            'ventas_transferencia' => (float) $sesion->ventas_transferencia,
            'total_ventas' => (float) $sesion->total_ventas,
            'ingresos_manuales' => $ingresos_manuales,
            'egresos_manuales' => $egresos_manuales,
            'saldo_teorico' => $saldo_teorico,
            'efectivo_real' => $sesion->efectivo_real !== null ? (float) $sesion->efectivo_real : ($arqueo ? (float) $arqueo->efectivo_contado : $saldo_teorico),
            'diferencia' => $sesion->diferencia !== null ? (float) $sesion->diferencia : ($arqueo ? (float) $arqueo->diferencia : 0.0),
            'observaciones' => $sesion->observaciones ?? '',
            'movimientos' => $movimientos,
            'open_drawer' => false,
        ];
    }
}
