<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja Turno #{{ str_pad($sesion->id, 6, '0', STR_PAD_LEFT) }}</title>
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
        .info-table, .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 2px 0;
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
        <button class="btn-print" onclick="window.print()">🖨️ Imprimir Cierre (Navegador)</button>
        <button class="btn-print" style="background: #059669; margin-left: 5px;" onclick="enviarServidorLocal()">🚀 Enviar a Ticketera Local</button>
    </div>

    <div class="text-center header">
        <div class="company-name">{{ $ticketData['emisor']['razon_social'] }}</div>
        @if(!empty($ticketData['emisor']['nombre_comercial']))
            <div class="bold">{{ $ticketData['emisor']['nombre_comercial'] }}</div>
        @endif
        <div>RUC: {{ $ticketData['emisor']['ruc'] }}</div>
        <div class="bold" style="font-size: 13px; margin-top: 4px;">*** CIERRE / ARQUEO DE CAJA ***</div>
    </div>

    <div class="divider-double"></div>

    <div>
        <div><span class="bold">TURNO N°:</span> {{ str_pad($ticketData['turno_id'], 6, '0', STR_PAD_LEFT) }}</div>
        <div><span class="bold">CAJA:</span> {{ $ticketData['caja'] }} ({{ $ticketData['sucursal'] }})</div>
        <div><span class="bold">CAJERO:</span> {{ $ticketData['usuario'] }}</div>
        <div><span class="bold">ESTADO:</span> {{ $ticketData['estado'] }}</div>
        <div><span class="bold">APERTURA:</span> {{ $ticketData['fecha_apertura'] }}</div>
        <div><span class="bold">CIERRE:</span> {{ $ticketData['fecha_cierre'] }}</div>
    </div>

    <div class="divider-double"></div>

    <div class="bold text-center">RESUMEN FINANCIERO</div>
    <div class="divider"></div>

    <table class="totals-table">
        <tr>
            <td>MONTO INICIAL (FONDO):</td>
            <td class="text-right bold">${{ number_format($ticketData['monto_inicial'], 2) }}</td>
        </tr>
        <tr>
            <td>(+) VENTAS EN EFECTIVO:</td>
            <td class="text-right bold">${{ number_format($ticketData['ventas_efectivo'], 2) }}</td>
        </tr>
        <tr>
            <td>(+) VENTAS TARJETAS:</td>
            <td class="text-right">${{ number_format($ticketData['ventas_tarjetas'], 2) }}</td>
        </tr>
        <tr>
            <td>(+) VENTAS TRANSFERENCIA:</td>
            <td class="text-right">${{ number_format($ticketData['ventas_transferencia'], 2) }}</td>
        </tr>
        <tr style="border-top: 1px dashed #000;">
            <td class="bold">TOTAL FACTURADO:</td>
            <td class="text-right bold">${{ number_format($ticketData['total_ventas'], 2) }}</td>
        </tr>
        <tr>
            <td>(+) INGRESOS MANUALES:</td>
            <td class="text-right">${{ number_format($ticketData['ingresos_manuales'], 2) }}</td>
        </tr>
        <tr>
            <td>(-) EGRESOS MANUALES:</td>
            <td class="text-right">-${{ number_format($ticketData['egresos_manuales'], 2) }}</td>
        </tr>
        <tr style="border-top: 1px solid #000; font-size: 13px;">
            <td class="bold">SALDO TEÓRICO EFECTIVO:</td>
            <td class="text-right bold">${{ number_format($ticketData['saldo_teorico'], 2) }}</td>
        </tr>
        <tr style="font-size: 13px;">
            <td class="bold">EFECTIVO CONTADO REAL:</td>
            <td class="text-right bold">${{ number_format($ticketData['efectivo_real'], 2) }}</td>
        </tr>
        <tr style="border-top: 1px dashed #000; font-size: 13px;">
            <td class="bold">DIFERENCIA:</td>
            <td class="text-right bold {{ $ticketData['diferencia'] < 0 ? 'text-danger' : '' }}">
                ${{ number_format($ticketData['diferencia'], 2) }}
            </td>
        </tr>
    </table>

    @if(!empty($ticketData['observaciones']))
    <div class="divider"></div>
    <div>
        <span class="bold">OBSERVACIONES:</span><br>
        {{ $ticketData['observaciones'] }}
    </div>
    @endif

    <div class="divider-double"></div>

    <br><br>
    <div class="text-center" style="margin-top: 25px;">
        <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto 4px auto;"></div>
        <div>FIRMA DEL CAJERO</div>
        <div style="font-size: 10px;">{{ $ticketData['usuario'] }}</div>
    </div>

    <div class="text-center" style="margin-top: 15px; font-size: 10px;">
        SYSFACT+ &bull; COMPROBANTE DE CONTROL INTERNO
    </div>

    <script>
        const ticketData = @json($ticketData);

        function enviarServidorLocal() {
            fetch('http://127.0.0.1:8080/print_cierre_caja', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(ticketData)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'ok') {
                    alert('✅ Cierre enviado correctamente a la ticketera local.');
                } else {
                    alert('⚠️ Error de ticketera: ' + (data.message || 'Desconocido'));
                }
            })
            .catch(err => {
                alert('⚠️ No se pudo conectar con SysFact_Printer en http://127.0.0.1:8080. Verifique que el programa esté iniciado.');
            });
        }
    </script>
</body>
</html>
