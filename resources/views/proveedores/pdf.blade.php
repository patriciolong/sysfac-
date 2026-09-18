<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Proveedor - {{ $proveedor->identificacion }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0ea5e9;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #0f172a;
            font-size: 20px;
        }
        .header p {
            margin: 5px 0 0;
            color: #64748b;
        }
        .section-title {
            background-color: #f1f5f9;
            padding: 8px;
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            border-left: 4px solid #0ea5e9;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .info-grid {
            width: 100%;
            margin-bottom: 15px;
        }
        .info-grid td {
            border: none;
            padding: 4px;
        }
        .summary-box {
            width: 32%;
            display: inline-block;
            border: 1px solid #cbd5e1;
            padding: 10px;
            text-align: center;
            margin-bottom: 10px;
            box-sizing: border-box;
        }
        .summary-title {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 5px;
        }
        .summary-value {
            font-size: 16px;
            font-weight: bold;
            color: #0ea5e9;
        }
        .page-break {
            page-break-after: always;
        }
        .footer {
            position: fixed;
            bottom: -30px;
            left: 0px;
            right: 0px;
            height: 30px;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <div class="footer">
        Generado el {{ date('d/m/Y H:i:s') }} - Sistema de GestiÃ³n
    </div>

    <div class="header">
        <h1>Reporte de Proveedor</h1>
        <p>Documento Informativo Comercial</p>
    </div>

    @if($incluirGeneral)
    <div class="section-title">InformaciÃ³n del Proveedor</div>
    <table class="info-grid">
        <tr>
            <td width="15%"><strong>Razón Social:</strong></td>
            <td width="35%">{{ $proveedor->razon_social }}</td>
            <td width="15%"><strong>Doc. Identidad:</strong></td>
            <td width="35%">{{ $proveedor->identificacion }} ({{ $proveedor->tipo_nombre }})</td>
        </tr>
        <tr>
            <td><strong>Correo:</strong></td>
            <td>{{ $proveedor->correo ?? 'N/A' }}</td>
            <td><strong>Teléfono:</strong></td>
            <td>{{ $proveedor->telefono ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Dirección:</strong></td>
            <td colspan="3">{{ $proveedor->direccion ?? 'N/A' }}</td>
        </tr>
    </table>
    @endif

    @if($incluirResumen && $resumen)
    <div class="section-title">Resumen Financiero</div>
    <div style="text-align: justify;">
        <div class="summary-box">
            <div class="summary-title">Total Comprado</div>
            <div class="summary-value">${{ number_format($resumen->total_comprado, 2) }}</div>
        </div>
        <div class="summary-box">
            <div class="summary-title">Facturas Registradas</div>
            <div class="summary-value">{{ $resumen->total_facturas }}</div>
        </div>
        <div class="summary-box">
            <div class="summary-title">Promedio por Factura</div>
            <div class="summary-value">${{ number_format($resumen->promedio_factura, 2) }}</div>
        </div>
        <div class="summary-box">
            <div class="summary-title">Total IVA Pagado</div>
            <div class="summary-value">${{ number_format($resumen->total_iva, 2) }}</div>
        </div>
        <div class="summary-box">
            <div class="summary-title">Última Compra</div>
            <div class="summary-value" style="color: #334155;">{{ $resumen->ultima_compra ? \Carbon\Carbon::parse($resumen->ultima_compra)->format('d/m/Y') : 'N/A' }}</div>
        </div>
    </div>
    @endif

    @if($incluirFacturas)
    <div class="section-title">Historial de Facturas de Compra</div>
    
    @if($compras->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Nro. Factura</th>
                    <th>Subtotal</th>
                    <th>IVA</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($compras as $compra)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($compra->fecha_emision)->format('d/m/Y') }}</td>
                    <td>{{ $compra->numero_factura }}</td>
                    <td class="text-right">${{ number_format($compra->subtotal_sin_impuestos, 2) }}</td>
                    <td class="text-right">${{ number_format($compra->iva, 2) }}</td>
                    <td class="text-right"><strong>${{ number_format($compra->total, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-right">TOTALES:</th>
                    <th class="text-right">${{ number_format($compras->sum('subtotal_sin_impuestos'), 2) }}</th>
                    <th class="text-right">${{ number_format($compras->sum('iva'), 2) }}</th>
                    <th class="text-right">${{ number_format($compras->sum('total'), 2) }}</th>
                </tr>
            </tfoot>
        </table>
    @else
        <p>No se encontraron facturas de compra para este proveedor en el perÃ­odo seleccionado.</p>
    @endif
    @endif

    @if($incluirDetalles && $compras->count() > 0)
    <div class="page-break"></div>
    <div class="header">
        <h1>Detalle de Productos por Factura</h1>
        <p>Proveedor: {{ $proveedor->razon_social }}</p>
    </div>

    @foreach($compras as $compra)
        <div class="section-title" style="background-color: #e2e8f0; border-left-color: #475569;">
            Factura Nro: {{ $compra->numero_factura }} | Fecha: {{ \Carbon\Carbon::parse($compra->fecha_emision)->format('d/m/Y') }}
        </div>
        
        @if($compra->detalles->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Código/Producto</th>
                    <th class="text-right">Cantidad</th>
                    <th class="text-right">Costo Unit.</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($compra->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->producto ? $detalle->producto->nombre : 'Producto ID: '.$detalle->producto_id }}</td>
                    <td class="text-right">{{ number_format($detalle->cantidad, 2) }}</td>
                    <td class="text-right">${{ number_format($detalle->costo_unitario, 4) }}</td>
                    <td class="text-right"><strong>${{ number_format($detalle->costo_total, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="font-style: italic; color: #64748b; margin-bottom: 20px;">No hay detalles de productos registrados para esta factura.</p>
        @endif
    @endforeach
    @endif

</body>
</html>
