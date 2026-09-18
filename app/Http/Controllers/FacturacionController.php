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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FacturacionController extends Controller
{
    public function index(Request $request)
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
        $estab = $puntoEmision ? $puntoEmision->establecimiento : '001';
        $pto = $puntoEmision ? $puntoEmision->punto_emision : '001';
        $ultimaFactura = Factura::where('establecimiento', $estab)
            ->where('punto_emision', $pto)
            ->orderBy('secuencial', 'desc')
            ->first();
        if ($ultimaFactura) {
            $nextSec = ((int) $ultimaFactura->secuencial) + 1;
        } else {
            $nextSec = $puntoEmision ? $puntoEmision->secuencial_factura : 1;
        }
        $secuencial_siguiente = $estab.'-'.$pto.'-'.str_pad($nextSec, 9, '0', STR_PAD_LEFT);

        // Historial con Filtros
        $query = Factura::with('cliente')->orderBy('id', 'desc');

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
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_emision', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_emision', '<=', $request->fecha_fin);
        }
        if ($request->filled('estado')) {
            $query->where('estado_sri', $request->estado);
        }

        $facturas = $query->paginate(10)->appends($request->query());
        $pendientes = Factura::whereNotIn('estado_sri', ['AUTORIZADO', 'ANULADA'])->count();

        return view('facturacion.index', compact('productos', 'clientes', 'metodos_pago', 'secuencial_siguiente', 'facturas', 'pendientes'));
    }

    public function show($id)
    {
        $factura = Factura::with(['cliente', 'detalles'])->find($id);
        if (! $factura) {
            return response()->json(['success' => false, 'message' => 'Factura no encontrada']);
        }

        return response()->json(['success' => true, 'factura' => $factura]);
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
            if (! $emisor) {
                return response()->json(['success' => false, 'message' => 'Emisor no configurado'], 400);
            }

            $puntoEmision = PuntoEmision::where('estado', 'ACTIVO')->lockForUpdate()->first();
            $estab = $puntoEmision ? $puntoEmision->establecimiento : '001';
            $pto = $puntoEmision ? $puntoEmision->punto_emision : '001';

            // Lógica para secuencial: usar la última factura si existe, sino el inicial de config
            $ultimaFactura = Factura::where('establecimiento', $estab)
                ->where('punto_emision', $pto)
                ->orderBy('secuencial', 'desc')
                ->first();

            if ($ultimaFactura) {
                $secuencialNum = ((int) $ultimaFactura->secuencial) + 1;
            } else {
                $secuencialNum = $puntoEmision ? $puntoEmision->secuencial_factura : 1;
            }

            $secuencialPadded = str_pad($secuencialNum, 9, '0', STR_PAD_LEFT);

            $subtotalSinImpuestos = 0;
            $base0 = 0;
            $baseIva = 0;
            $valorIva = 0;
            $detallesToInsert = [];
            $kardexDetalles = [];
            $jsonDetalles = [];

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

                $det = [
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
                $detallesToInsert[] = $det;

                $jsonDetalles[] = [
                    'codigo_principal' => $prod->codigo_principal ?? $prod->id,
                    'codigo_auxiliar' => $prod->codigo_auxiliar ?? '',
                    'descripcion' => $prod->nombre,
                    'cantidad' => $qty,
                    'precio_unitario' => $price,
                    'descuento' => 0,
                    'precio_total_sin_impuestos' => $lineTotal,
                    'codigo_porcentaje_iva' => $prod->codigo_iva,
                    'tarifa_iva' => $tarifaIva,
                    'base_imponible' => $lineTotal,
                    'valor_iva' => $lineIva,
                ];

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

            $cliente = Cliente::find($validated['cliente_id']);
            $metodoPago = MetodoPago::find($validated['metodo_pago_id']);

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
                'factura' => [
                    'establecimiento' => $estab,
                    'punto_emision' => $pto,
                    'secuencial' => $secuencialPadded,
                    'fecha_emision' => date('d/m/Y'),
                    'total_sin_impuestos' => $subtotalSinImpuestos,
                    'total_descuento' => 0,
                    'base_imponible_0' => $base0,
                    'base_imponible_iva' => $baseIva,
                    'valor_iva' => $valorIva,
                    'importe_total' => $importeTotal,
                ],
                'pagos' => [
                    [
                        'codigo_sri' => $metodoPago->codigo_sri,
                        'total' => $importeTotal,
                        'plazo' => 0,
                        'unidad_tiempo' => 'dias',
                    ],
                ],
                'detalles' => $jsonDetalles,
                'config_firma' => [
                    'ruta' => storage_path('app/private/'.$emisor->firma_electronica_ruta),
                    'pass' => $emisor->firma_electronica_clave,
                ],
            ];

            // Ejecutar Python
            $jsonBase64 = base64_encode(json_encode($payloadSRI));
            $scriptPath = app_path('Services/SRI/sri_factura.py');
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
                // If python script fails completely
                return response()->json([
                    'success' => false,
                    'message' => 'Error interno procesando firma electrónica.',
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

            // Mapear el estado devuelto por Python al ENUM permitido en la base de datos
            // Valores permitidos: 'CREADO','FIRMADO','ENVIADO','AUTORIZADO','RECHAZADO','DEVUELTO','ANULADO'
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
                $estado_sri = 'RECHAZADO'; // Fallback por defecto si ocurre un error interno
            }

            // Create Factura
            $factura = Factura::create([
                'emisor_id' => $emisor->id,
                'punto_emision_id' => $puntoEmision ? $puntoEmision->id : 1,
                'cliente_id' => $validated['cliente_id'],
                'usuario_id' => 1,
                'ambiente' => $emisor->ambiente ?? 1,
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
                'estado_sri' => $estado_sri,
                'fecha_autorizacion' => $estado_sri === 'AUTORIZADO' ? now() : null,
                'numero_autorizacion' => $estado_sri === 'AUTORIZADO' ? $claveAcceso : null,
                'mensajes_sri' => $mensaje,
                'xml_generado' => $xml_generado,
            ]);

            // Save details
            foreach ($detallesToInsert as $det) {
                $det['factura_id'] = $factura->id;
                FacturaDetalle::create($det);
            }

            FacturaPago::create([
                'factura_id' => $factura->id,
                'metodo_pago_id' => $validated['metodo_pago_id'],
                'total' => $importeTotal,
                'plazo' => 0,
                'unidad_tiempo' => 'DIAS',
            ]);

            $mov = Movimiento::create([
                'bodega_id' => 1,
                'tipo_movimiento_id' => 2,
                'usuario_id' => 1,
                'fecha_movimiento' => now(),
                'referencia' => "{$estab}-{$pto}-{$secuencialPadded}",
                'observaciones' => "Venta Factura #{$secuencialPadded}",
            ]);

            foreach ($kardexDetalles as $kDet) {
                $kDet['movimiento_id'] = $mov->id;
                MovimientoDetalle::create($kDet);
            }

            $turno = CajaTurno::getTurnoActivo();
            if ($turno) {
                $codigoMetodo = $metodoPago ? $metodoPago->codigo_sri : '01';

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

                CajaMovimiento::create([
                    'caja_turno_id' => $turno->id,
                    'tipo' => 'INGRESO',
                    'categoria' => 'Venta',
                    'concepto' => "Pago de Factura #{$secuencialPadded}",
                    'monto' => $importeTotal,
                    'metodo_pago_id' => $metodoPago->id ?? 1,
                    'usuario_id' => 1,
                    'venta_id' => $factura->id,
                    'comprobante' => "{$estab}-{$pto}-{$secuencialPadded}",
                    'observaciones' => 'Generado automáticamente desde POS',
                ]);
            }

            if ($estado_sri === 'AUTORIZADO' || $estado_sri === 'ENVIADO') {
                return response()->json([
                    'success' => true,
                    'message' => 'Factura procesada en el SRI.',
                    'comprobante' => "{$estab}-{$pto}-{$secuencialPadded}",
                    'clave_acceso' => $claveAcceso,
                    'total' => $importeTotal,
                    'siguiente_secuencial' => $estab.'-'.$pto.'-'.str_pad($secuencialNum + 1, 9, '0', STR_PAD_LEFT),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Factura rechazada por el SRI',
                    'error' => $mensaje,
                    'comprobante' => "{$estab}-{$pto}-{$secuencialPadded}",
                    'siguiente_secuencial' => $estab.'-'.$pto.'-'.str_pad($secuencialNum + 1, 9, '0', STR_PAD_LEFT),
                ], 400); // 400 Bad Request
            }
        });
    }

    public function reautorizar(Request $request)
    {
        $ids = $request->input('ids');
        if (empty($ids) || ! is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'No se seleccionaron facturas.']);
        }

        $facturas = Factura::with(['cliente', 'detalles', 'pagos.metodoPago', 'emisor'])->whereIn('id', $ids)->get();
        $resultados = [];

        foreach ($facturas as $factura) {
            $cliente = $factura->cliente;
            $len = strlen(trim($cliente->identificacion));

            // Basic validation for common errors
            if ($cliente->identificacion !== '9999999999999') {
                if (! in_array($len, [10, 13])) {
                    $resultados[] = [
                        'id' => $factura->id,
                        'comprobante' => "{$factura->establecimiento}-{$factura->punto_emision}-{$factura->secuencial}",
                        'success' => false,
                        'message' => "Identificación de cliente inválida ($len dígitos).",
                    ];

                    continue;
                }
            }

            $emisor = $factura->emisor;
            if (! $emisor) {
                $emisor = Emisor::first();
            }

            // Build jsonDetalles
            $jsonDetalles = [];
            foreach ($factura->detalles as $det) {
                $jsonDetalles[] = [
                    'codigo_principal' => $det->codigo_principal ?? $det->producto_id,
                    'codigo_auxiliar' => $det->codigo_auxiliar ?? '',
                    'descripcion' => $det->descripcion,
                    'cantidad' => $det->cantidad,
                    'precio_unitario' => $det->precio_unitario,
                    'descuento' => $det->descuento ?? 0,
                    'precio_total_sin_impuestos' => $det->precio_total_sin_impuestos,
                    'codigo_porcentaje_iva' => $det->codigo_impuesto_iva ?? '4',
                    'tarifa_iva' => $det->tarifa_iva,
                    'base_imponible' => $det->base_imponible_iva ?? $det->precio_total_sin_impuestos,
                    'valor_iva' => $det->valor_iva,
                ];
            }

            // Build jsonPagos
            $jsonPagos = [];
            foreach ($factura->pagos as $pago) {
                $jsonPagos[] = [
                    'codigo_sri' => $pago->metodoPago ? $pago->metodoPago->codigo_sri : '01',
                    'total' => $pago->total,
                    'plazo' => $pago->plazo ?? 0,
                    'unidad_tiempo' => $pago->unidad_tiempo ?? 'dias',
                ];
            }

            // Default pago si no hay
            if (empty($jsonPagos)) {
                $jsonPagos[] = [
                    'codigo_sri' => '01',
                    'total' => $factura->importe_total,
                    'plazo' => 0,
                    'unidad_tiempo' => 'dias',
                ];
            }

            $payloadSRI = [
                'ambiente' => $factura->ambiente,
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
                'factura' => [
                    'establecimiento' => $factura->establecimiento,
                    'punto_emision' => $factura->punto_emision,
                    'secuencial' => str_pad($factura->secuencial, 9, '0', STR_PAD_LEFT),
                    'fecha_emision' => date('d/m/Y'), // Update to current date
                    'total_sin_impuestos' => $factura->total_sin_impuestos,
                    'total_descuento' => $factura->total_descuento ?? 0,
                    'base_imponible_0' => $factura->base_imponible_0,
                    'base_imponible_iva' => $factura->base_imponible_iva,
                    'valor_iva' => $factura->valor_iva,
                    'importe_total' => $factura->importe_total,
                ],
                'pagos' => $jsonPagos,
                'detalles' => $jsonDetalles,
                'config_firma' => [
                    'ruta' => storage_path('app/private/'.$emisor->firma_electronica_ruta),
                    'pass' => $emisor->firma_electronica_clave,
                ],
            ];

            // Ejecutar Python
            $jsonBase64 = base64_encode(json_encode($payloadSRI));
            $scriptPath = app_path('Services/SRI/sri_factura.py');
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
                    'id' => $factura->id,
                    'comprobante' => "{$factura->establecimiento}-{$factura->punto_emision}-{$factura->secuencial}",
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

            // Actualizar factura
            $factura->estado_sri = $estado_sri;
            $factura->fecha_emision = date('Y-m-d'); // Current date
            $factura->mensajes_sri = $mensaje;
            $factura->xml_generado = $xml_generado;

            if ($estado_sri === 'AUTORIZADO') {
                $factura->fecha_autorizacion = now();
                $factura->numero_autorizacion = $claveAcceso;
                $factura->clave_acceso = $claveAcceso;
            } else {
                // Actualizar clave acceso por si cambió (fecha emision cambió)
                $factura->clave_acceso = $claveAcceso;
            }
            $factura->save();

            $resultados[] = [
                'id' => $factura->id,
                'comprobante' => "{$factura->establecimiento}-{$factura->punto_emision}-{$factura->secuencial}",
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
        $factura = Factura::with(['cliente', 'detalles.producto', 'pagos.metodoPago', 'emisor'])->findOrFail($id);

        $pdf = Pdf::loadView('facturacion.pdf', compact('factura'));

        return $pdf->stream('RIDE_Factura_'.$factura->numero_comprobante.'.pdf');
    }
}
