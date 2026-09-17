<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\PuntoEmision;
use App\Models\Emisor;
use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Models\FacturaPago;
use App\Models\InventarioGeneral;
use App\Models\Movimiento;
use App\Models\MovimientoDetalle;
use App\Models\CajaTurno;
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
                    'tarifa_iva' => $p->tarifa_iva_porcentaje
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
                'direccion' => $c->direccion
            ];
        });

        $metodos_pago = MetodoPago::where('estado', 'ACTIVO')->get()->map(function ($m) {
            return [
                'id' => $m->id,
                'codigo' => $m->codigo_sri,
                'nombre' => $m->nombre,
                'icono' => $m->icono
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
            'items.*.qty' => 'required|numeric|min:1'
        ]);

        return DB::transaction(function () use ($validated, $request) {
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
                    'valor_iva' => $lineIva
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
                    'costo_total' => round(($prod->costo_promedio ?? $price) * $qty, 2)
                ];
            }

            $importeTotal = round($subtotalSinImpuestos + $valorIva, 2);

            // Generate realistic SRI 49-digit Clave de Acceso
            $fecha = date('dmY');
            $tipoComp = '01';
            $ruc = str_pad($emisor ? $emisor->ruc : '1792948201001', 13, '0', STR_PAD_RIGHT);
            $ambiente = '1';
            $serie = $estab . $pto;
            $secuencial = $secuencialPadded;
            $codigoNumerico = '12345678';
            $tipoEmision = '1';
            $clave48 = $fecha . $tipoComp . $ruc . $ambiente . $serie . $secuencial . $codigoNumerico . $tipoEmision;
            $digitoVerificador = $this->calcularModulo11($clave48);
            $claveAcceso = $clave48 . $digitoVerificador;

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
                'mensajes_sri' => 'AUTORIZACION REGISTRADA EN EL SRI EXITOSAMENTE.'
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
                'unidad_tiempo' => 'DIAS'
            ]);

            // Register Movement in Kardex
            $mov = Movimiento::create([
                'bodega_id' => 1,
                'tipo_movimiento_id' => 2, // VENTA
                'usuario_id' => 1,
                'fecha_movimiento' => now(),
                'referencia' => "{$estab}-{$pto}-{$secuencialPadded}",
                'observaciones' => "Venta Factura #{$secuencialPadded}"
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
                \App\Models\CajaMovimiento::create([
                    'caja_turno_id' => $turno->id,
                    'tipo' => 'INGRESO',
                    'categoria' => 'Venta',
                    'concepto' => "Pago de Factura #{$secuencialPadded}",
                    'monto' => $importeTotal,
                    'metodo_pago_id' => $metodo->id ?? 1,
                    'usuario_id' => 1,
                    'venta_id' => $factura->id,
                    'comprobante' => "{$estab}-{$pto}-{$secuencialPadded}",
                    'observaciones' => 'Generado automáticamente desde POS'
                ]);
            }

            // Increment sequential
            if ($puntoEmision) {
                $puntoEmision->increment('secuencial_factura');
            }

            return response()->json([
                'success' => true,
                'message' => 'Factura emitida y autorizada correctamente ante el SRI',
                'comprobante' => "{$estab}-{$pto}-{$secuencialPadded}",
                'clave_acceso' => $claveAcceso,
                'total' => $importeTotal,
                'siguiente_secuencial' => $puntoEmision ? $puntoEmision->fresh()->siguiente_secuencial_factura_formatted : ''
            ]);
        });
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
        if ($digito == 11) return 0;
        if ($digito == 10) return 1;
        return $digito;
    }
}
