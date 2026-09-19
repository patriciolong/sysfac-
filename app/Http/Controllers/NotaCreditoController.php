<?php

namespace App\Http\Controllers;

use App\Mail\NotaCreditoCreada;
use App\Models\CajaMovimiento;
use App\Models\CajaTurno;
use App\Models\Cliente;
use App\Models\ConfiguracionEmpresa;
use App\Models\Emisor;
use App\Models\Factura;
use App\Models\InventarioGeneral;
use App\Models\Movimiento;
use App\Models\MovimientoDetalle;
use App\Models\NotaCredito;
use App\Models\NotaCreditoDetalle;
use App\Models\Producto;
use App\Models\PuntoEmision;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotaCreditoController extends Controller
{
    private function getCodigoIvaPorTarifa($tarifa): string
    {
        $map = [
            0 => '0',
            12 => '2',
            14 => '3',
            15 => '4',
            5 => '5',
            8 => '8',
            13 => '10',
        ];

        return $map[(int) $tarifa] ?? '4';
    }

    public function index(Request $request)
    {
        $facturasRecientes = Factura::with('cliente')
            ->whereIn('estado_sri', ['AUTORIZADO', 'ENVIADO'])
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

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

        $puntoEmision = PuntoEmision::where('estado', 'ACTIVO')->first();
        $estab = $puntoEmision ? $puntoEmision->establecimiento : '001';
        $pto = $puntoEmision ? $puntoEmision->punto_emision : '001';

        $ultimaNC = NotaCredito::where('establecimiento', $estab)
            ->where('punto_emision', $pto)
            ->orderBy('secuencial', 'desc')
            ->first();

        $maxSecBD = $ultimaNC ? (int) $ultimaNC->secuencial : 0;
        $secConfig = $puntoEmision ? (int) $puntoEmision->secuencial_nota_credito : 1;
        $nextSec = max($maxSecBD + 1, $secConfig);
        $secuencial_siguiente = $estab.'-'.$pto.'-'.str_pad($nextSec, 9, '0', STR_PAD_LEFT);

        // Historial con Filtros
        $query = NotaCredito::with(['cliente', 'factura'])->orderBy('id', 'desc');

        if ($request->filled('numero')) {
            $query->where(function ($q) use ($request) {
                $q->where('secuencial', 'like', '%'.str_pad($request->numero, 9, '0', STR_PAD_LEFT).'%')
                    ->orWhere('secuencial', 'like', '%'.$request->numero.'%');
            });
        }

        if ($request->filled('cliente')) {
            $query->whereHas('cliente', function ($q) use ($request) {
                $q->where('razon_social', 'like', '%'.$request->cliente.'%')
                    ->orWhere('identificacion', 'like', '%'.$request->cliente.'%');
            });
        }

        if ($request->filled('factura')) {
            $query->where(function ($q) use ($request) {
                $q->where('numero_documento', 'like', '%'.$request->factura.'%')
                    ->orWhereHas('factura', function ($fQuery) use ($request) {
                        $fQuery->where('secuencial', 'like', '%'.$request->factura.'%');
                    });
            });
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_emision', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_emision', '<=', $request->fecha_fin);
        }

        if ($request->filled('estado')) {
            $query->where('estado_sri', $request->estado);
        }

        $notasCredito = $query->paginate(10)->appends($request->query());
        $pendientes = NotaCredito::whereNotIn('estado_sri', ['AUTORIZADO', 'ANULADA'])->count();
        $iva_defecto = ConfiguracionEmpresa::first()->iva_defecto ?? 15;

        return view('notas_credito.index', compact(
            'facturasRecientes',
            'productos',
            'clientes',
            'secuencial_siguiente',
            'notasCredito',
            'pendientes',
            'iva_defecto'
        ));
    }

    public function buscarFacturas(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        $query = Factura::with('cliente')
            ->whereIn('estado_sri', ['AUTORIZADO', 'ENVIADO']);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('secuencial', 'like', "%{$q}%")
                    ->orWhere('numero_documento', 'like', "%{$q}%")
                    ->orWhere('clave_acceso', 'like', "%{$q}%")
                    ->orWhereHas('cliente', function ($cQuery) use ($q) {
                        $cQuery->where('razon_social', 'like', "%{$q}%")
                            ->orWhere('identificacion', 'like', "%{$q}%");
                    });
            });
        }

        $facturas = $query->orderBy('id', 'desc')->take(15)->get();

        return response()->json([
            'success' => true,
            'facturas' => $facturas->map(function ($f) {
                return [
                    'id' => $f->id,
                    'comprobante' => $f->numero_comprobante,
                    'secuencial' => $f->secuencial,
                    'fecha_emision' => Carbon::parse($f->fecha_emision)->format('d/m/Y'),
                    'cliente_nombre' => $f->cliente ? $f->cliente->razon_social : 'Consumidor Final',
                    'cliente_identificacion' => $f->cliente ? $f->cliente->identificacion : '9999999999999',
                    'total' => (float) $f->importe_total,
                ];
            }),
        ]);
    }

    public function getFactura($id)
    {
        $factura = Factura::with(['cliente', 'detalles.producto', 'pagos.metodoPago'])->find($id);
        if (! $factura) {
            return response()->json(['success' => false, 'message' => 'Factura no encontrada'], 404);
        }

        return response()->json(['success' => true, 'factura' => $factura]);
    }

    public function show($id)
    {
        $notaCredito = NotaCredito::with(['cliente', 'detalles.producto', 'factura', 'emisor'])->find($id);
        if (! $notaCredito) {
            return response()->json(['success' => false, 'message' => 'Nota de Crédito no encontrada'], 404);
        }

        return response()->json(['success' => true, 'notaCredito' => $notaCredito]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'factura_id' => 'required|exists:ventas_facturas,id',
            'motivo' => 'required|string|max:255',
            'tipo_modificacion' => 'required|in:DEVOLUCION,DESCUENTO',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:inventario_productos,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
        ]);

        return DB::transaction(function () use ($validated) {
            $emisor = Emisor::first();
            if (! $emisor) {
                return response()->json(['success' => false, 'message' => 'Emisor no configurado'], 400);
            }

            $factura = Factura::with(['cliente', 'detalles'])->findOrFail($validated['factura_id']);
            $cliente = $factura->cliente;

            $puntoEmision = PuntoEmision::where('estado', 'ACTIVO')->lockForUpdate()->first();
            $estab = $puntoEmision ? $puntoEmision->establecimiento : '001';
            $pto = $puntoEmision ? $puntoEmision->punto_emision : '001';

            $ultimaNC = NotaCredito::where('establecimiento', $estab)
                ->where('punto_emision', $pto)
                ->orderBy('secuencial', 'desc')
                ->first();

            $maxSecBD = $ultimaNC ? (int) $ultimaNC->secuencial : 0;
            $secConfig = $puntoEmision ? (int) $puntoEmision->secuencial_nota_credito : 1;
            $secuencialNum = max($maxSecBD + 1, $secConfig);

            $secuencialPadded = str_pad($secuencialNum, 9, '0', STR_PAD_LEFT);

            $subtotalSinImpuestos = 0;
            $base0 = 0;
            $baseIva = 0;
            $valorIva = 0;
            $detallesToInsert = [];
            $kardexDetalles = [];
            $jsonDetalles = [];

            $iva_defecto = ConfiguracionEmpresa::first()->iva_defecto ?? 15;

            foreach ($validated['items'] as $itemData) {
                $prod = Producto::find($itemData['id']);
                $qty = (float) $itemData['qty'];

                // Buscar precio en los detalles de la factura original o en el producto
                $facDetalle = $factura->detalles->firstWhere('producto_id', $prod->id);
                $price = $facDetalle ? (float) $facDetalle->precio_unitario : (float) $prod->precio_unitario;

                $lineTotal = round($price * $qty, 2);
                $subtotalSinImpuestos += $lineTotal;

                $tarifaIva = $facDetalle ? (float) $facDetalle->tarifa_iva : (float) $prod->tarifa_iva_porcentaje;
                $codigoIva = $facDetalle ? (string) $facDetalle->codigo_impuesto_iva : (string) $prod->codigo_iva;

                if ($codigoIva !== '0') {
                    $tarifaIva = $tarifaIva > 0 ? $tarifaIva : $iva_defecto;
                    $codigoIva = $this->getCodigoIvaPorTarifa($tarifaIva);
                }

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
                    'codigo_interno' => $prod->codigo_principal ?? (string) $prod->id,
                    'descripcion' => $prod->nombre,
                    'cantidad' => $qty,
                    'precio_unitario' => $price,
                    'descuento' => 0.00,
                    'precio_total_sin_impuestos' => $lineTotal,
                    'codigo_impuesto_iva' => $codigoIva,
                    'tarifa_iva' => $tarifaIva,
                    'base_imponible_iva' => $lineTotal,
                    'valor_iva' => $lineIva,
                ];

                $jsonDetalles[] = [
                    'codigo_interno' => $prod->codigo_principal ?? (string) $prod->id,
                    'codigo_adicional' => $prod->codigo_auxiliar ?? '',
                    'descripcion' => $prod->nombre,
                    'cantidad' => $qty,
                    'precio_unitario' => $price,
                    'descuento' => 0,
                    'precio_total_sin_impuestos' => $lineTotal,
                    'codigo_impuesto' => '2',
                    'codigo_porcentaje_iva' => $codigoIva,
                    'tarifa_iva' => $tarifaIva,
                    'base_imponible' => $lineTotal,
                    'valor_iva' => $lineIva,
                ];

                if ($validated['tipo_modificacion'] === 'DEVOLUCION') {
                    // Reversión de stock a bodega
                    $inv = InventarioGeneral::firstOrCreate(
                        ['bodega_id' => 1, 'producto_id' => $prod->id],
                        ['stock_actual' => 0, 'stock_minimo' => 5]
                    );
                    $inv->stock_actual += $qty;
                    $inv->save();

                    $kardexDetalles[] = [
                        'producto_id' => $prod->id,
                        'cantidad' => $qty,
                        'costo_unitario' => $prod->costo_promedio ?? $price,
                        'costo_total' => round(($prod->costo_promedio ?? $price) * $qty, 2),
                    ];
                }
            }

            $valorModificacion = round($subtotalSinImpuestos + $valorIva, 2);
            $fechaEmisionFac = date('d/m/Y', strtotime($factura->fecha_emision));

            $payloadSRI = [
                'ambiente' => $emisor->ambiente ?? 1,
                'emisor' => [
                    'razon_social' => $emisor->razon_social,
                    'nombre_comercial' => $emisor->nombre_comercial,
                    'ruc' => $emisor->ruc,
                    'direccion_matriz' => $emisor->direccion_matriz,
                    'direccion_establecimiento' => $emisor->direccion_establecimiento,
                    'contribuyente_especial' => $emisor->contribuyente_especial,
                    'obligado_contabilidad' => $emisor->obligado_contabilidad,
                    'regimen_rimpe' => $emisor->regimen_rimpe,
                ],
                'cliente' => [
                    'tipo_identificacion' => $cliente->tipo_identificacion,
                    'identificacion' => $cliente->identificacion,
                    'razon_social' => $cliente->razon_social,
                    'direccion' => $cliente->direccion,
                    'correo' => $cliente->correo,
                ],
                'nota_credito' => [
                    'establecimiento' => $estab,
                    'punto_emision' => $pto,
                    'secuencial' => $secuencialPadded,
                    'fecha_emision' => date('d/m/Y'),
                    'num_doc_modificado' => $factura->numero_comprobante,
                    'fecha_emision_doc_sustento' => $fechaEmisionFac,
                    'total_sin_impuestos' => $subtotalSinImpuestos,
                    'valor_modificacion' => $valorModificacion,
                    'base_imponible_0' => $base0,
                    'base_imponible_iva' => $baseIva,
                    'codigo_porcentaje_iva' => $this->getCodigoIvaPorTarifa($iva_defecto),
                    'valor_iva' => $valorIva,
                    'motivo' => $validated['motivo'],
                ],
                'detalles' => $jsonDetalles,
                'config_firma' => [
                    'ruta' => storage_path('app/private/'.$emisor->firma_electronica_ruta),
                    'pass' => $emisor->firma_electronica_clave,
                ],
            ];

            // Ejecutar Python para Nota de Crédito
            $jsonBase64 = base64_encode(json_encode($payloadSRI));
            $scriptPath = app_path('Services/SRI/sri_nota_credito.py');
            $command = 'python '.escapeshellarg($scriptPath).' '.escapeshellarg($jsonBase64).' 2>&1';

            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            $sriResponse = null;
            $fullOutput = implode("\n", $output);

            if (! empty($output)) {
                foreach (array_reverse($output) as $line) {
                    $decoded = json_decode($line, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($decoded['estado'])) {
                        $sriResponse = $decoded;
                        break;
                    }
                }
            }

            if (! $sriResponse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error interno procesando firma electrónica de Nota de Crédito.',
                    'error' => $fullOutput,
                ], 500);
            }

            $estado_sri = $sriResponse['estado'] ?? 'CREADO';
            $claveAcceso = $sriResponse['clave_acceso'] ?? '';
            $xml_generado = $sriResponse['xml_autorizacion'] ?? '';
            $mensaje = $sriResponse['mensaje'] ?? 'Procesado';

            if ($estado_sri === 'AUTORIZADO') {
                $mensaje = 'AUTORIZACION REGISTRADA EN EL SRI EXITOSAMENTE.';
            } else {
                if (empty($mensaje) && ! empty($xml_generado)) {
                    $mensaje = substr(strip_tags($xml_generado), 0, 500);
                }
            }

            if ($estado_sri === 'DEVUELTA') {
                $estado_sri = 'DEVUELTO';
            }
            if ($estado_sri === 'ERROR' || $estado_sri === 'NO AUTORIZADO') {
                $estado_sri = 'RECHAZADO';
            }
            if ($estado_sri === 'RECIBIDA' || $estado_sri === 'RECIBIDO' || $estado_sri === 'EN_PROCESO') {
                $estado_sri = 'ENVIADO';
            }

            $validEstados = ['CREADO', 'FIRMADO', 'ENVIADO', 'AUTORIZADO', 'RECHAZADO', 'DEVUELTO', 'ANULADO'];
            if (! in_array($estado_sri, $validEstados)) {
                $estado_sri = 'RECHAZADO';
            }

            // Crear registro en ventas_notas_credito
            $notaCredito = NotaCredito::create([
                'emisor_id' => $emisor->id,
                'punto_emision_id' => $puntoEmision ? $puntoEmision->id : 1,
                'cliente_id' => $cliente->id,
                'usuario_id' => auth()->id() ?? 1,
                'factura_modificada_id' => $factura->id,
                'ambiente' => $emisor->ambiente ?? 1,
                'tipo_emision' => 1,
                'codigo_documento' => '04',
                'establecimiento' => $estab,
                'punto_emision' => $pto,
                'secuencial' => $secuencialPadded,
                'clave_acceso' => $claveAcceso,
                'fecha_emision' => date('Y-m-d'),
                'motivo' => $validated['motivo'],
                'tipo_modificacion' => $validated['tipo_modificacion'],
                'fecha_emision_documento_modificado' => $factura->fecha_emision,
                'total_sin_impuestos' => $subtotalSinImpuestos,
                'base_imponible_0' => $base0,
                'base_imponible_iva' => $baseIva,
                'base_no_objeto' => 0.00,
                'base_exento' => 0.00,
                'valor_iva' => $valorIva,
                'valor_ice' => 0.00,
                'valor_modificacion' => $valorModificacion,
                'moneda' => 'DOLAR',
                'estado_sri' => $estado_sri,
                'fecha_autorizacion' => $estado_sri === 'AUTORIZADO' ? now() : null,
                'numero_autorizacion' => $estado_sri === 'AUTORIZADO' ? $claveAcceso : null,
                'mensajes_sri' => $mensaje,
                'xml_generado' => $xml_generado,
                'created_at' => now(),
            ]);

            // Guardar detalles en ventas_detalles_nc
            foreach ($detallesToInsert as $det) {
                $det['nota_credito_id'] = $notaCredito->id;
                NotaCreditoDetalle::create($det);
            }

            // Kardex para Devolución en Venta (Tipo movimiento 3: DEVOLUCIÓN EN VENTA)
            if ($validated['tipo_modificacion'] === 'DEVOLUCION' && count($kardexDetalles) > 0) {
                $mov = Movimiento::create([
                    'bodega_id' => 1,
                    'tipo_movimiento_id' => 3, // Devolución en venta
                    'usuario_id' => auth()->id() ?? 1,
                    'fecha_movimiento' => now(),
                    'referencia' => "{$estab}-{$pto}-{$secuencialPadded}",
                    'observaciones' => "Devolución Nota de Crédito #{$secuencialPadded} a Factura #{$factura->secuencial}",
                ]);

                foreach ($kardexDetalles as $kDet) {
                    $kDet['movimiento_id'] = $mov->id;
                    MovimientoDetalle::create($kDet);
                }
            }

            // Ajuste de caja si hay turno activo
            $turno = CajaTurno::getTurnoActivo();
            if ($turno) {
                $turno->ventas_efectivo = max(0, $turno->ventas_efectivo - $valorModificacion);
                $turno->efectivo_esperado = max(0, $turno->efectivo_esperado - $valorModificacion);
                $turno->total_ventas = max(0, $turno->total_ventas - $valorModificacion);
                $turno->save();

                CajaMovimiento::create([
                    'caja_turno_id' => $turno->id,
                    'tipo' => 'EGRESO',
                    'categoria' => 'Devolución',
                    'concepto' => "Nota de Crédito #{$secuencialPadded} afectando Factura #{$factura->secuencial}",
                    'monto' => $valorModificacion,
                    'metodo_pago_id' => 1,
                    'usuario_id' => auth()->id() ?? 1,
                    'venta_id' => $factura->id,
                    'comprobante' => "{$estab}-{$pto}-{$secuencialPadded}",
                    'observaciones' => 'Generado automáticamente desde Nota de Crédito',
                ]);
            }

            // Enviar correo al cliente
            if ($cliente && ! empty($cliente->correo)) {
                try {
                    Mail::to($cliente->correo)->send(new NotaCreditoCreada($notaCredito));
                } catch (\Exception $e) {
                    Log::error('Error enviando correo de Nota de Crédito: '.$e->getMessage());
                }
            }

            if ($puntoEmision) {
                $puntoEmision->secuencial_nota_credito = $secuencialNum + 1;
                $puntoEmision->save();
            }

            if ($estado_sri === 'AUTORIZADO' || $estado_sri === 'ENVIADO') {
                return response()->json([
                    'success' => true,
                    'id' => $notaCredito->id,
                    'message' => 'Nota de Crédito procesada en el SRI.',
                    'comprobante' => "{$estab}-{$pto}-{$secuencialPadded}",
                    'clave_acceso' => $claveAcceso,
                    'total' => $valorModificacion,
                    'siguiente_secuencial' => $estab.'-'.$pto.'-'.str_pad($secuencialNum + 1, 9, '0', STR_PAD_LEFT),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Nota de Crédito rechazada por el SRI',
                    'error' => $mensaje,
                    'comprobante' => "{$estab}-{$pto}-{$secuencialPadded}",
                    'siguiente_secuencial' => $estab.'-'.$pto.'-'.str_pad($secuencialNum + 1, 9, '0', STR_PAD_LEFT),
                ], 400);
            }
        });
    }

    public function reautorizar(Request $request)
    {
        $ids = $request->input('ids');
        if (empty($ids) || ! is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'No se seleccionaron notas de crédito.']);
        }

        $notasCredito = NotaCredito::with(['cliente', 'detalles', 'factura', 'emisor'])->whereIn('id', $ids)->get();
        $resultados = [];

        foreach ($notasCredito as $nc) {
            $cliente = $nc->cliente;
            $len = strlen(trim($cliente->identificacion));

            if ($cliente->identificacion !== '9999999999999') {
                if (! in_array($len, [10, 13])) {
                    $resultados[] = [
                        'id' => $nc->id,
                        'comprobante' => $nc->numero_comprobante,
                        'success' => false,
                        'message' => "Identificación de cliente inválida ($len dígitos).",
                    ];

                    continue;
                }
            }

            $emisor = $nc->emisor ?? Emisor::first();

            $jsonDetalles = [];
            foreach ($nc->detalles as $det) {
                $jsonDetalles[] = [
                    'codigo_interno' => $det->codigo_interno,
                    'codigo_adicional' => '',
                    'descripcion' => $det->descripcion,
                    'cantidad' => $det->cantidad,
                    'precio_unitario' => $det->precio_unitario,
                    'descuento' => $det->descuento ?? 0,
                    'precio_total_sin_impuestos' => $det->precio_total_sin_impuestos,
                    'codigo_impuesto' => '2',
                    'codigo_porcentaje_iva' => $det->codigo_impuesto_iva ?? '4',
                    'tarifa_iva' => $det->tarifa_iva,
                    'base_imponible' => $det->base_imponible_iva ?? $det->precio_total_sin_impuestos,
                    'valor_iva' => $det->valor_iva,
                ];
            }

            $fechaEmisionFac = $nc->factura
                ? date('d/m/Y', strtotime($nc->factura->fecha_emision))
                : date('d/m/Y', strtotime($nc->fecha_emision_documento_modificado));

            $payloadSRI = [
                'ambiente' => $nc->ambiente,
                'emisor' => [
                    'razon_social' => $emisor->razon_social,
                    'nombre_comercial' => $emisor->nombre_comercial,
                    'ruc' => $emisor->ruc,
                    'direccion_matriz' => $emisor->direccion_matriz,
                    'direccion_establecimiento' => $emisor->direccion_establecimiento,
                    'contribuyente_especial' => $emisor->contribuyente_especial,
                    'obligado_contabilidad' => $emisor->obligado_contabilidad,
                    'regimen_rimpe' => $emisor->regimen_rimpe,
                ],
                'cliente' => [
                    'tipo_identificacion' => $cliente->tipo_identificacion,
                    'identificacion' => $cliente->identificacion,
                    'razon_social' => $cliente->razon_social,
                    'direccion' => $cliente->direccion,
                    'correo' => $cliente->correo,
                ],
                'nota_credito' => [
                    'establecimiento' => $nc->establecimiento,
                    'punto_emision' => $nc->punto_emision,
                    'secuencial' => str_pad($nc->secuencial, 9, '0', STR_PAD_LEFT),
                    'fecha_emision' => date('d/m/Y'),
                    'num_doc_modificado' => $nc->numero_factura_modificada,
                    'fecha_emision_doc_sustento' => $fechaEmisionFac,
                    'total_sin_impuestos' => $nc->total_sin_impuestos,
                    'valor_modificacion' => $nc->valor_modificacion,
                    'base_imponible_0' => $nc->base_imponible_0,
                    'base_imponible_iva' => $nc->base_imponible_iva,
                    'codigo_porcentaje_iva' => $this->getCodigoIvaPorTarifa($nc->detalles->first()->tarifa_iva ?? 15),
                    'valor_iva' => $nc->valor_iva,
                    'motivo' => $nc->motivo,
                ],
                'detalles' => $jsonDetalles,
                'config_firma' => [
                    'ruta' => storage_path('app/private/'.$emisor->firma_electronica_ruta),
                    'pass' => $emisor->firma_electronica_clave,
                ],
            ];

            // Ejecutar Python
            $jsonBase64 = base64_encode(json_encode($payloadSRI));
            $scriptPath = app_path('Services/SRI/sri_nota_credito.py');
            $command = 'python '.escapeshellarg($scriptPath).' '.escapeshellarg($jsonBase64).' 2>&1';

            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            $sriResponse = null;
            $fullOutput = implode("\n", $output);

            if (! empty($output)) {
                foreach (array_reverse($output) as $line) {
                    $decoded = json_decode($line, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($decoded['estado'])) {
                        $sriResponse = $decoded;
                        break;
                    }
                }
            }

            if (! $sriResponse) {
                $resultados[] = [
                    'id' => $nc->id,
                    'comprobante' => $nc->numero_comprobante,
                    'success' => false,
                    'message' => 'Error interno ejecutando script de firma.',
                ];

                continue;
            }

            $estado_sri = $sriResponse['estado'] ?? 'CREADO';
            $claveAcceso = $sriResponse['clave_acceso'] ?? '';
            $xml_generado = $sriResponse['xml_autorizacion'] ?? '';
            $mensaje = $sriResponse['mensaje'] ?? 'Procesado';

            if ($estado_sri === 'AUTORIZADO') {
                $mensaje = 'AUTORIZACION REGISTRADA EN EL SRI EXITOSAMENTE.';
            } else {
                if (empty($mensaje) && ! empty($xml_generado)) {
                    $mensaje = substr(strip_tags($xml_generado), 0, 500);
                }
            }

            if ($estado_sri === 'DEVUELTA') {
                $estado_sri = 'DEVUELTO';
            }
            if ($estado_sri === 'ERROR' || $estado_sri === 'NO AUTORIZADO') {
                $estado_sri = 'RECHAZADO';
            }
            if ($estado_sri === 'RECIBIDA' || $estado_sri === 'RECIBIDO' || $estado_sri === 'EN_PROCESO') {
                $estado_sri = 'ENVIADO';
            }

            $validEstados = ['CREADO', 'FIRMADO', 'ENVIADO', 'AUTORIZADO', 'RECHAZADO', 'DEVUELTO', 'ANULADO'];
            if (! in_array($estado_sri, $validEstados)) {
                $estado_sri = 'RECHAZADO';
            }

            $nc->estado_sri = $estado_sri;
            $nc->fecha_emision = date('Y-m-d');
            $nc->mensajes_sri = $mensaje;
            $nc->xml_generado = $xml_generado;

            if ($estado_sri === 'AUTORIZADO') {
                $nc->fecha_autorizacion = now();
                $nc->numero_autorizacion = $claveAcceso;
                $nc->clave_acceso = $claveAcceso;
            } else {
                $nc->clave_acceso = $claveAcceso;
            }
            $nc->save();

            $resultados[] = [
                'id' => $nc->id,
                'comprobante' => $nc->numero_comprobante,
                'success' => in_array($estado_sri, ['AUTORIZADO', 'ENVIADO']),
                'message' => $mensaje,
                'estado' => $estado_sri,
            ];
        }

        return response()->json([
            'success' => true,
            'resultados' => $resultados,
        ]);
    }

    public function pdf($id)
    {
        $notaCredito = NotaCredito::with(['cliente', 'detalles.producto', 'factura', 'emisor'])->findOrFail($id);

        $pdf = Pdf::loadView('notas_credito.pdf', compact('notaCredito'));

        return $pdf->stream('RIDE_Nota_Credito_'.$notaCredito->numero_comprobante.'.pdf');
    }

    public function reenviarCorreo($id)
    {
        try {
            $notaCredito = NotaCredito::with('cliente')->findOrFail($id);
            $cliente = $notaCredito->cliente;

            if (! $cliente || empty($cliente->correo)) {
                return response()->json(['success' => false, 'message' => 'El cliente no tiene un correo configurado.']);
            }

            Mail::to($cliente->correo)->send(new NotaCreditoCreada($notaCredito));

            return response()->json(['success' => true, 'message' => 'Correo reenviado exitosamente a '.$cliente->correo]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al reenviar el correo: '.$e->getMessage()]);
        }
    }
}
