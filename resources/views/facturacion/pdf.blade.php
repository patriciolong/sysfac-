<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>RIDE - Factura {{ $factura->numero_comprobante }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; margin: 0; padding: 0; }
        .container { width: 100%; padding: 15px; }
        .box { border: 1px solid #333; border-radius: 6px; padding: 10px; margin-bottom: 12px; position: relative; }
        .col-left { width: 48%; float: left; }
        .col-right { width: 49%; float: right; }
        .clearfix { clear: both; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        th, td { border: 1px solid #555; padding: 4px 6px; vertical-align: middle; }
        th { background-color: #f0f0f0; font-weight: bold; text-align: center; font-size: 9px; }
        td { font-size: 9px; }
        .no-border { border: none !important; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .header-ruc { font-size: 14px; margin-bottom: 5px; }
        .header-doc { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
        .logo-section { text-align: center; margin-bottom: 10px; }
        .logo-img { max-height: 100px; max-width: 90%; }
        .info-trib { font-size: 9px; line-height: 1.3; }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(255, 0, 0, 0.2);
            border: 5px solid rgba(255, 0, 0, 0.2);
            padding: 20px;
            font-weight: bold;
            z-index: 100;
            text-transform: uppercase;
            width: 100%;
            text-align: center;
        }
    </style>
</head>
<body>
    @if($factura->estado_sri === 'ANULADA' || $factura->estado_sri === 'RECHAZADO')
        <div class="watermark">{{ $factura->estado_sri }}</div>
    @endif

    <div class="container">
        <div class="col-left">
            <div class="logo-section">
                @php 
                    $logoPath = public_path('assets/img/logo.png');
                    if(file_exists($logoPath)){
                        $type = pathinfo($logoPath, PATHINFO_EXTENSION);
                        $data = file_get_contents($logoPath);
                        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                        echo '<img src="'.$base64.'" class="logo-img">';
                    } else {
                        echo '<h2 style="color:#888;">SIN LOGO</h2>';
                    }
                @endphp
            </div>
            <div class="box info-trib">
                <strong>{{ $factura->emisor->razon_social ?? 'EMISOR' }}</strong><br>
                @if(!empty($factura->emisor->nombre_comercial))
                    <em>{{ $factura->emisor->nombre_comercial }}</em><br>
                @endif
                <br>
                <strong>Dirección Matriz:</strong> {{ $factura->emisor->direccion_matriz ?? '' }}<br>
                <strong>Dirección Sucursal:</strong> {{ $factura->emisor->direccion_establecimiento ?? '' }}<br>
                @if(!empty($factura->emisor->contribuyente_especial))
                    <strong>Contribuyente Especial Nro:</strong> {{ $factura->emisor->contribuyente_especial }}<br>
                @endif
                <strong>OBLIGADO A LLEVAR CONTABILIDAD:</strong> {{ $factura->emisor->obligado_contabilidad ?? 'NO' }}<br>
                @if(!empty($factura->emisor->regimen_rimpe) && $factura->emisor->regimen_rimpe !== 'NINGUNO')
                    <strong>{{ $factura->emisor->regimen_rimpe }}</strong>
                @endif
            </div>
        </div>
        <div class="col-right">
            <div class="box">
                <div class="header-ruc">R.U.C.: {{ $factura->emisor->ruc ?? '' }}</div>
                <div class="header-doc">FACTURA</div>
                <div style="font-size: 12px; margin-bottom: 5px;">
                    No. {{ $factura->numero_comprobante }}
                </div>
                <strong>NÚMERO DE AUTORIZACIÓN</strong><br>
                <div style="font-size: 12px; margin-bottom: 5px; word-break: break-all;">
                    {{ $factura->clave_acceso ? $factura->clave_acceso : 'PENDIENTE DE AUTORIZACIÓN' }}
                </div>
                <br>
                <strong>FECHA Y HORA DE AUTORIZACIÓN</strong><br>
                {{ $factura->fecha_autorizacion ? \Carbon\Carbon::parse($factura->fecha_autorizacion)->format('d/m/Y H:i:s') : 'PENDIENTE' }}<br>
                <br>
                <strong>AMBIENTE:</strong> {{ $factura->ambiente == 1 ? 'PRUEBAS' : 'PRODUCCIÓN' }}<br>
                <strong>EMISIÓN:</strong> NORMAL<br>
                <br>
                <strong>CLAVE DE ACCESO</strong>
                <div style="font-size: 11px; margin-bottom: 5px; text-align: center;">
                    @if($factura->clave_acceso && strlen($factura->clave_acceso) == 49)
                        @php
                            $generator = new Picqer\Barcode\BarcodeGeneratorPNG();
                            $codigo_datos = $generator->getBarcode($factura->clave_acceso, $generator::TYPE_CODE_128);
                            $imagen_barra = 'data:image/png;base64,' . base64_encode($codigo_datos);
                        @endphp
                        <img src="{{ $imagen_barra }}" style="width: 100%; height: 45px; object-fit: contain; margin-bottom: 3px;">
                    @endif
                    <div style="font-size: 10px; letter-spacing: 1px;">{{ $factura->clave_acceso }}</div>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>

        <div class="box" style="margin-top: -5px;">
            <table class="no-border" style="width:100%">
                <tr>
                    <td class="no-border" width="16%"><strong>Razón Social:</strong></td>
                    <td class="no-border">{{ $factura->cliente->razon_social ?? 'Consumidor Final' }}</td>
                    <td class="no-border" width="15%"><strong>Identificación:</strong></td>
                    <td class="no-border">{{ $factura->cliente->identificacion ?? '9999999999999' }}</td>
                </tr>
                <tr>
                    <td class="no-border"><strong>Fecha Emisión:</strong></td>
                    <td class="no-border">{{ \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/Y') }}</td>
                    <td class="no-border"><strong>Dirección:</strong></td>
                    <td class="no-border">{{ $factura->cliente->direccion ?? '' }}</td>
                </tr>
                @if(!empty($factura->cliente->correo))
                <tr>
                    <td class="no-border"><strong>Correo Electrónico:</strong></td>
                    <td colspan="3" class="no-border">{{ $factura->cliente->correo }}</td>
                </tr>
                @endif
            </table>
        </div>

        <table style="margin-top: 10px;">
            <thead>
                <tr>
                    <th>Cod. Principal</th>
                    <th>Cantidad</th>
                    <th>Descripción</th>
                    <th>Precio Unitario</th>
                    <th>Descuento</th>
                    <th>Precio Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($factura->detalles as $det)
                <tr>
                    <td class="text-center">{{ $det->codigo_principal }}</td>
                    <td class="text-center">{{ number_format($det->cantidad, 2) }}</td>
                    <td>{{ $det->descripcion }}</td>
                    <td class="text-right">{{ number_format($det->precio_unitario, 2) }}</td>
                    <td class="text-right">{{ number_format($det->descuento, 2) }}</td>
                    <td class="text-right">{{ number_format($det->precio_total_sin_impuestos, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="width: 100%; margin-top: 15px;">
            <div class="col-left">
                @if(!empty($factura->cliente->correo) || !empty($factura->cliente->direccion))
                <div class="box">
                    <strong>Información Adicional</strong>
                    <table class="no-border">
                        @if(!empty($factura->cliente->correo))
                        <tr><td class="no-border"><strong>Email:</strong></td><td class="no-border">{{ $factura->cliente->correo }}</td></tr>
                        @endif
                        @if(!empty($factura->cliente->direccion))
                        <tr><td class="no-border"><strong>Dirección:</strong></td><td class="no-border">{{ $factura->cliente->direccion }}</td></tr>
                        @endif
                    </table>
                </div>
                @endif
                <div class="box">
                    <strong>Formas de Pago</strong>
                    <table class="no-border" style="margin-top: 5px;">
                        @foreach($factura->pagos as $pago)
                        <tr>
                            <td class="no-border">{{ $pago->metodoPago->nombre ?? 'EFECTIVO' }}</td>
                            <td class="no-border text-right">${{ number_format($pago->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            </div>
            
            <div class="col-right">
                <table style="width: 100%;">
                    <tr>
                        <td class="font-bold">SUBTOTAL 15%</td>
                        <td class="text-right">${{ number_format($factura->base_imponible_iva, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">SUBTOTAL 0%</td>
                        <td class="text-right">${{ number_format($factura->base_imponible_0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">SUBTOTAL No objeto de IVA</td>
                        <td class="text-right">${{ number_format($factura->base_no_objeto, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">SUBTOTAL Exento de IVA</td>
                        <td class="text-right">${{ number_format($factura->base_exento, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">SUBTOTAL SIN IMPUESTOS</td>
                        <td class="text-right">${{ number_format($factura->total_sin_impuestos, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">TOTAL Descuento</td>
                        <td class="text-right">${{ number_format($factura->total_descuento, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">IVA 15%</td>
                        <td class="text-right">${{ number_format($factura->valor_iva, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">VALOR TOTAL</td>
                        <td class="text-right font-bold" style="font-size: 11px;">${{ number_format($factura->importe_total, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
