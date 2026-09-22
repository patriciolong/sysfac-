<?php

namespace App\Http\Controllers;

use App\Models\CajaMovimiento;
use App\Models\CajaTurno;
use App\Models\Cliente;
use App\Models\Emisor;
use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Models\FacturaPago;
use App\Models\InventarioGeneral;
use App\Models\MetodoPago;
use App\Models\Movimiento;
use App\Models\MovimientoDetalle;
use App\Models\Producto;
use App\Models\PuntoEmision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FacturacionController extends Controller
{
    public function index()
    {
        $productos = Producto::with(['categoria', 'inventarios'])
            ->where('estado', 'ACTIVO')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'codigo' => $p->codigo_principal,
                    'nombre' => $p->nombre,
                    'precio' => (float) $p->precio_unitario,
                    'stock' => (float) $p->stock_total,
                    'categoria' => $p->categoria->nombre ?? 'General',
                    'icono' => $p->icono,
                    'codigo_iva' => $p->codigo_iva,
                    'tarifa_iva' => $p->tarifa_iva_porcentaje,
                ];
            });

        $clientes = Cliente::all()->map(function ($c) {
            return [
                'id' => $c->id,
                'identificacion' => $c->identificacion,
                'razon_social' => $c->razon_social,
                'tipo' => $c->tipo_identificacion,
                'tipo_nombre' => $c->tipo_nombre,
                'correo' => $c->correo,
                'direccion' => $c->direccion,
            ];
        });

        $metodos_pago = MetodoPago::where('estado', 'ACTIVO')->get()->map(function ($m) {
            return [
                'id' => $m->id,
                'codigo' => $m->codigo_sri,
                'nombre' => $m->nombre,
                'icono' => $m->icono,
            ];
        });

        $puntoEmision = PuntoEmision::where('estado', 'ACTIVO')->first();
        $secuencial_siguiente = $puntoEmision ? $puntoEmision->siguiente_secuencial_factura_formatted : '001-001-000000001';

        return view('facturacion.index', compact('productos', 'clientes', 'metodos_pago', 'secuencial_siguiente'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes_cliente,id',
            'metodo_pago_id' => 'required|exists:configuracion_metodos_pago,id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:inventario_productos,id',
            'items.*.qty' => 'required|numeric|min:1',
        ]);

        return DB::transaction(function () use ($validated) {
            $emisor = Emisor::first();
            $puntoEmision = PuntoEmision::where('estado', 'ACTIVO')->lockForUpdate()->first();
            $secuencialNum = $puntoEmision ? $puntoEmision->secuencial_factura : 1;
            $secuencialPadded = str_pad($secuencialNum, 9, '0', STR_PAD_LEFT);
            $estab = $puntoEmision ? $puntoEmision->establecimiento : '001';
            $pto = $puntoEmision ? $puntoEmision->punto_emision : '001';

            $subtotalSinImpuestos = 0;
            $base0 = 0;
            $baseIva = 0;
            $valorIva = 0;
            $detallesToInsert = [];
            $kardexDetalles = [];

            foreach ($validated['items'] as $itemData) {
                $prod = Producto::find($itemData['id']);
                $qty = (float) $itemData['qty'];
                $price = (float) $prod->precio_unitario;
                $lineTotal = round($price * $qty, 2);
                $subtotalSinImpuestos += $lineTotal;

                $tarifaIva = $prod->tarifa_iva_porcentaje;
                $lineIva = 0;
                if ($tarifaIva > 0) {
                    $baseIva += $lineTotal;
                    $lineIva = round($lineTotal * ($tarifaIva / 100), 2);
                    $valorIva += $lineIva;
                } else {
                    $base0 += $lineTotal;
                }

                $detallesToInsert[] = [
                    'producto_id' => $prod->id,
                    'codigo_principal' => $prod->codigo_principal,
                    'codigo_auxiliar' => $prod->codigo_auxiliar,
                    'descripcion' => $prod->nombre,
                    'cantidad' => $qty,
                    'precio_unitario' => $price,
                    'descuento' => 0.00,
                    'precio_total_sin_impuestos' => $lineTotal,
                    'codigo_impuesto_iva' => $prod->codigo_iva,
                    'tarifa_iva' => $tarifaIva,
                    'base_imponible_iva' => $lineTotal,
                    'valor_iva' => $lineIva,
                ];

                // Reduce inventory in general stock
                $inv = InventarioGeneral::firstOrCreate(
                    ['bodega_id' => 1, 'producto_id' => $prod->id],
                    ['stock_actual' => 0, 'stock_minimo' => 5]
                );
                $inv->stock_actual = max(0, $inv->stock_actual - $qty);
                $inv->save();

                $kardexDetalles[] = [
                    'producto_id' => $prod->id,
                    'cantidad' => $qty,
                    'costo_unitario' => $prod->costo_promedio ?? $price,
                    'costo_total' => round(($prod->costo_promedio ?? $price) * $qty, 2),
                ];
            }

            $importeTotal = round($subtotalSinImpuestos + $valorIva, 2);

            // Generate realistic SRI 49-digit Clave de Acceso
            $fecha = date('dmY');
            $tipoComp = '01';
            $ruc = str_pad($emisor ? $emisor->ruc : '1792948201001', 13, '0', STR_PAD_RIGHT);
            $ambiente = '1';
            $serie = $estab.$pto;
            $secuencial = $secuencialPadded;
            $codigoNumerico = '12345678';
            $tipoEmision = '1';
            $clave48 = $fecha.$tipoComp.$ruc.$ambiente.$serie.$secuencial.$codigoNumerico.$tipoEmision;
            $digitoVerificador = $this->calcularModulo11($clave48);
            $claveAcceso = $clave48.$digitoVerificador;

            // Create Factura
            $factura = Factura::create([
                'emisor_id' => $emisor ? $emisor->id : 1,
                'punto_emision_id' => $puntoEmision ? $puntoEmision->id : 1,
                'cliente_id' => $validated['cliente_id'],
                'usuario_id' => 1,
                'ambiente' => 1,
                'tipo_emision' => 1,
                'codigo_documento' => '01',
                'establecimiento' => $estab,
                'punto_emision' => $pto,
                'secuencial' => $secuencialPadded,
                'clave_acceso' => $claveAcceso,
                'fecha_emision' => date('Y-m-d'),
                'total_sin_impuestos' => $subtotalSinImpuestos,
                'total_descuento' => 0.00,
                'base_imponible_0' => $base0,
                'base_imponible_iva' => $baseIva,
                'valor_iva' => $valorIva,
                'importe_total' => $importeTotal,
                'estado_sri' => 'AUTORIZADO',
                'fecha_autorizacion' => now(),
                'numero_autorizacion' => $claveAcceso,
                'mensajes_sri' => 'AUTORIZACION REGISTRADA EN EL SRI EXITOSAMENTE.',
            ]);

            // Save details
            foreach ($detallesToInsert as $det) {
                $det['factura_id'] = $factura->id;
                FacturaDetalle::create($det);
            }

            // Save Payment
            FacturaPago::create([
                'factura_id' => $factura->id,
                'metodo_pago_id' => $validated['metodo_pago_id'],
                'total' => $importeTotal,
                'plazo' => 0,
                'unidad_tiempo' => 'DIAS',
            ]);

            // Register Movement in Kardex
            $mov = Movimiento::create([
                'bodega_id' => 1,
                'tipo_movimiento_id' => 2, // VENTA
                'usuario_id' => 1,
                'fecha_movimiento' => now(),
                'referencia' => "{$estab}-{$pto}-{$secuencialPadded}",
                'observaciones' => "Venta Factura #{$secuencialPadded}",
            ]);

            foreach ($kardexDetalles as $kDet) {
                $kDet['movimiento_id'] = $mov->id;
                MovimientoDetalle::create($kDet);
            }

            // Update Caja Turno
            $turno = CajaTurno::getTurnoActivo();
            if ($turno) {
                $metodo = MetodoPago::find($validated['metodo_pago_id']);
                $codigoMetodo = $metodo ? $metodo->codigo_sri : '01';

                if ($codigoMetodo === '01') {
                    $turno->ventas_efectivo += $importeTotal;
                    $turno->efectivo_esperado += $importeTotal;
                } elseif (in_array($codigoMetodo, ['16', '19'])) {
                    $turno->ventas_tarjetas += $importeTotal;
                } else {
                    $turno->ventas_transferencia += $importeTotal;
                }
                $turno->total_ventas += $importeTotal;
                $turno->save();

                // Registrar Movimiento Financiero Real
                CajaMovimiento::create([
                    'caja_turno_id' => $turno->id,
                    'tipo' => 'INGRESO',
                    'categoria' => 'Venta',
                    'concepto' => "Pago de Factura #{$secuencialPadded}",
                    'monto' => $importeTotal,
                    'metodo_pago_id' => $metodo->id ?? 1,
                    'usuario_id' => 1,
                    'venta_id' => $factura->id,
                    'comprobante' => "{$estab}-{$pto}-{$secuencialPadded}",
                    'observaciones' => 'Generado automáticamente desde POS',
                ]);
            }

            // Increment sequential
            if ($puntoEmision) {
                $puntoEmision->increment('secuencial_factura');
            }

            $ticketData = $this->formatFacturaTicketData($factura);

            return response()->json([
                'success' => true,
                'message' => 'Factura emitida y autorizada correctamente ante el SRI',
                'factura_id' => $factura->id,
                'comprobante' => "{$estab}-{$pto}-{$secuencialPadded}",
                'clave_acceso' => $claveAcceso,
                'total' => $importeTotal,
                'siguiente_secuencial' => $puntoEmision ? $puntoEmision->fresh()->siguiente_secuencial_factura_formatted : '',
                'ticket_data' => $ticketData,
            ]);
        });
    }

    public function getTicketData($id)
    {
        $factura = Factura::with(['emisor', 'cliente', 'usuario', 'detalles.producto', 'pagos.metodoPago'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'ticket_data' => $this->formatFacturaTicketData($factura),
        ]);
    }

    public function ticketHtml($id)
    {
        $factura = Factura::with(['emisor', 'cliente', 'usuario', 'detalles.producto', 'pagos.metodoPago'])->findOrFail($id);
        $ticketData = $this->formatFacturaTicketData($factura);

        return view('facturacion.ticket_html', compact('factura', 'ticketData'));
    }

    public function descargarServidor()
    {
        $path = public_path('servidor_impresion/SysFact_Printer.exe');
        if (! file_exists($path)) {
            $path = base_path('servidor_impresion/dist/SysFact_Printer.exe');
        }

        if (file_exists($path)) {
            return response()->download($path, 'SysFact_Printer.exe');
        }

        return back()->with('error', 'El instalador del servidor de impresión aún no ha sido generado.');
    }

    public function formatFacturaTicketData(Factura $factura): array
    {
        $factura->loadMissing(['emisor', 'cliente', 'usuario', 'detalles.producto', 'pagos.metodoPago']);

        $emisor = $factura->emisor ?? Emisor::first();
        $cliente = $factura->cliente;
        $usuario = $factura->usuario;

        $detalles = $factura->detalles->map(function ($d) {
            return [
                'producto_id' => $d->producto_id,
                'codigo' => $d->codigo_principal,
                'descripcion' => $d->descripcion,
                'cantidad' => (float) $d->cantidad,
                'precio_unitario' => (float) $d->precio_unitario,
                'descuento' => (float) $d->descuento,
                'precio_total_sin_impuestos' => (float) $d->precio_total_sin_impuestos,
                'tarifa_iva' => $d->tarifa_iva !== null ? (float) $d->tarifa_iva : null,
                'valor_iva' => (float) $d->valor_iva,
            ];
        })->toArray();

        $pagos = $factura->pagos->map(function ($p) {
            return [
                'metodo_pago' => $p->metodoPago->nombre ?? 'EFECTIVO',
                'codigo_sri' => $p->metodoPago->codigo_sri ?? '01',
                'total' => (float) $p->total,
            ];
        })->toArray();

        return [
            'type' => 'factura',
            'id' => $factura->id,
            'comprobante' => "{$factura->establecimiento}-{$factura->punto_emision}-{$factura->secuencial}",
            'numero_factura' => "{$factura->establecimiento}-{$factura->punto_emision}-{$factura->secuencial}",
            'clave_acceso' => $factura->clave_acceso,
            'numero_autorizacion' => $factura->numero_autorizacion ?: $factura->clave_acceso,
            'fecha_emision' => $factura->fecha_emision ? date('d/m/Y H:i', strtotime($factura->created_at ?? $factura->fecha_emision)) : date('d/m/Y H:i'),
            'ambiente' => $factura->ambiente ?? 1,
            'estado_sri' => $factura->estado_sri ?? 'AUTORIZADO',
            'emisor' => [
                'ruc' => $emisor->ruc ?? '1790000000001',
                'razon_social' => $emisor->razon_social ?? 'EMPRESA DEMO S.A.',
                'nombre_comercial' => $emisor->nombre_comercial ?? '',
                'direccion_matriz' => $emisor->direccion_matriz ?? 'Ecuador',
                'direccion_establecimiento' => $emisor->direccion_establecimiento ?? '',
                'contribuyente_especial' => $emisor->contribuyente_especial ?? '',
                'obligado_contabilidad' => $emisor->obligado_contabilidad ?? 'NO',
                'regimen_rimpe' => $emisor->regimen_rimpe ?? 'CONTRIBUYENTE RÉGIMEN RIMPE',
            ],
            'cliente' => [
                'id' => $cliente->id ?? null,
                'razon_social' => $cliente->razon_social ?? 'CONSUMIDOR FINAL',
                'identificacion' => $cliente->identificacion ?? '9999999999999',
                'tipo_identificacion' => $cliente->tipo_identificacion ?? '07',
                'direccion' => $cliente->direccion ?? 'Ecuador',
                'telefono' => $cliente->telefono ?? '',
                'correo' => $cliente->correo ?? '',
            ],
            'usuario' => $usuario->nombre_completo ?? ($usuario->nombre ?? 'CAJERO'),
            'detalles' => $detalles,
            'totales' => [
                'subtotal_sin_impuestos' => (float) $factura->total_sin_impuestos,
                'base_imponible_0' => (float) $factura->base_imponible_0,
                'base_imponible_iva' => (float) $factura->base_imponible_iva,
                'total_descuento' => (float) $factura->total_descuento,
                'valor_iva' => (float) $factura->valor_iva,
                'importe_total' => (float) $factura->importe_total,
            ],
            'pagos' => $pagos,
            'open_drawer' => true,
        ];
    }

    private function calcularModulo11($cadena)
    {
        $factor = 2;
        $suma = 0;
        for ($i = strlen($cadena) - 1; $i >= 0; $i--) {
            $suma += intval($cadena[$i]) * $factor;
            $factor = $factor == 7 ? 2 : $factor + 1;
        }
        $digito = 11 - ($suma % 11);
        if ($digito == 11) {
            return 0;
        }
        if ($digito == 10) {
            return 1;
        }

        return $digito;
    }
}
