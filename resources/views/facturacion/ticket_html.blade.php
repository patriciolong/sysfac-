<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Factura {{ $ticketData['comprobante'] }}</title>
    <style>
        @page {
            margin: 0;
            size: 80mm auto;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.3;
            color: #000;
            background: #fff;
            padding: 10px;
            width: 80mm;
            max-width: 100%;
            margin: 0 auto;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .divider-double {
            border-top: 2px solid #000;
            margin: 6px 0;
        }
        .header { margin-bottom: 6px; }
        .company-name { font-size: 14px; font-weight: bold; }
        .info-table, .items-table, .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th, .items-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .totals-table td {
            padding: 2px 0;
        }
        .barcode-box {
            font-size: 10px;
            word-break: break-all;
            margin: 6px 0;
        }
        .no-print {
            margin-bottom: 12px;
            padding: 8px;
            background: #f1f5f9;
            border-radius: 6px;
            text-align: center;
        }
        .btn-print {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 6px 14px;
            font-family: sans-serif;
            font-size: 12px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Imprimir Ticket (Navegador)</button>
        <button class="btn-print" style="background: #059669; margin-left: 5px;" onclick="enviarServidorLocal()">🚀 Enviar a Ticketera Local</button>
    </div>

    <div class="text-center header">
        <div class="company-name">{{ $ticketData['emisor']['razon_social'] }}</div>
        @if(!empty($ticketData['emisor']['nombre_comercial']) && $ticketData['emisor']['nombre_comercial'] !== $ticketData['emisor']['razon_social'])
            <div class="bold">{{ $ticketData['emisor']['nombre_comercial'] }}</div>
        @endif
        <div>RUC: {{ $ticketData['emisor']['ruc'] }}</div>
        <div>{{ $ticketData['emisor']['direccion_matriz'] }}</div>
        @if($ticketData['emisor']['obligado_contabilidad'] === 'SI')
            <div>OBLIGADO A LLEVAR CONTABILIDAD: SI</div>
        @endif
        @if(!empty($ticketData['emisor']['regimen_rimpe']) && $ticketData['emisor']['regimen_rimpe'] !== 'NO APLICA')
            <div>{{ $ticketData['emisor']['regimen_rimpe'] }}</div>
        @endif
    </div>

    <div class="divider-double"></div>

    <div>
        <div><span class="bold">FACTURA:</span> {{ $ticketData['comprobante'] }}</div>
        <div><span class="bold">FECHA:</span> {{ $ticketData['fecha_emision'] }}</div>
        <div><span class="bold">AMBIENTE:</span> {{ $ticketData['ambiente'] == 2 ? 'PRODUCCIÓN' : 'PRUEBAS' }}</div>
        @if(!empty($ticketData['clave_acceso']))
            <div class="barcode-box">
                <span class="bold">CLAVE DE ACCESO:</span><br>
                {{ $ticketData['clave_acceso'] }}
            </div>
        @endif
    </div>

    <div class="divider"></div>

    <div>
        <div><span class="bold">CLIENTE:</span> {{ $ticketData['cliente']['razon_social'] }}</div>
        <div><span class="bold">RUC/CI:</span> {{ $ticketData['cliente']['identificacion'] }}</div>
        @if(!empty($ticketData['cliente']['telefono']))
            <div><span class="bold">TELF:</span> {{ $ticketData['cliente']['telefono'] }}</div>
        @endif
        @if(!empty($ticketData['cliente']['direccion']))
            <div><span class="bold">DIR:</span> {{ $ticketData['cliente']['direccion'] }}</div>
        @endif
    </div>

    <div class="divider-double"></div>

    <table class="items-table">
        <thead>
            <tr style="border-bottom: 1px dashed #000;">
                <th class="text-left" style="width: 15%;">CANT</th>
                <th class="text-left">DESCRIPCIÓN</th>
                <th class="text-right" style="width: 20%;">P.U.</th>
                <th class="text-right" style="width: 22%;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ticketData['detalles'] as $item)
            <tr>
                <td>{{ $item['cantidad'] }}</td>
                <td>{{ $item['descripcion'] }}</td>
                <td class="text-right">${{ number_format($item['precio_unitario'], 2) }}</td>
                <td class="text-right bold">${{ number_format($item['precio_total_sin_impuestos'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <table class="totals-table">
        @if($ticketData['totales']['base_imponible_0'] > 0)
        <tr>
            <td class="text-left">SUBTOTAL 0%:</td>
            <td class="text-right">${{ number_format($ticketData['totales']['base_imponible_0'], 2) }}</td>
        </tr>
        @endif
        @if($ticketData['totales']['base_imponible_iva'] > 0)
        <tr>
            <td class="text-left">SUBTOTAL 15%:</td>
            <td class="text-right">${{ number_format($ticketData['totales']['base_imponible_iva'], 2) }}</td>
        </tr>
        @endif
        <tr>
            <td class="text-left">SUBTOTAL SIN IMPUESTOS:</td>
            <td class="text-right">${{ number_format($ticketData['totales']['subtotal_sin_impuestos'], 2) }}</td>
        </tr>
        @if($ticketData['totales']['total_descuento'] > 0)
        <tr>
            <td class="text-left">DESCUENTO:</td>
            <td class="text-right">${{ number_format($ticketData['totales']['total_descuento'], 2) }}</td>
        </tr>
        @endif
        <tr>
            <td class="text-left">IVA (15%):</td>
            <td class="text-right">${{ number_format($ticketData['totales']['valor_iva'], 2) }}</td>
        </tr>
        <tr style="font-size: 14px; font-weight: bold; border-top: 1px dashed #000; border-bottom: 1px dashed #000;">
            <td class="text-left" style="padding: 4px 0;">TOTAL A PAGAR:</td>
            <td class="text-right" style="padding: 4px 0;">${{ number_format($ticketData['totales']['importe_total'], 2) }}</td>
        </tr>
    </table>

    @if(!empty($ticketData['pagos']))
    <div style="margin-top: 6px;">
        <div class="bold">FORMA DE PAGO:</div>
        @foreach($ticketData['pagos'] as $pago)
            <div> - {{ $pago['metodo_pago'] }}: ${{ number_format($pago['total'], 2) }}</div>
        @endforeach
    </div>
    @endif

    <div class="divider"></div>

    <div class="text-center" style="font-size: 10px; margin-top: 6px;">
        <div>CAJERO: {{ $ticketData['usuario'] }}</div>
        <div style="margin-top: 4px;">DOCUMENTO EMITIDO ELECTRÓNICAMENTE</div>
        <div>Consulte su factura en www.sri.gob.ec</div>
        <div class="bold" style="margin-top: 4px; font-size: 11px;">¡GRACIAS POR SU COMPRA!</div>
    </div>

    <script>
        const ticketPayload = @json($ticketData);

        function enviarServidorLocal() {
            fetch('http://127.0.0.1:8080/print_factura', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(ticketPayload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('¡Ticket enviado exitosamente a la ticketera local!');
                } else {
                    alert('Error en ticketera: ' + data.message);
                }
            })
            .catch(err => {
                alert('No se pudo conectar con el Servidor de Impresión (puerto 8080). Asegúrese de tener abierta la aplicación SysFact_Printer.exe');
            });
        }
    </script>
</body>
</html>
