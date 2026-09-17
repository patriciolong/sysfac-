<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Caja PDF</title>
    <style>
        @page { margin: 30px 40px; }
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            font-size: 12px; 
            color: #2c3e50; 
            line-height: 1.5; 
        }
        
        /* HEADER PRINCIPAL */
        .header { 
            width: 100%; 
            background-color: #1abc9c; 
            color: #fff; 
            padding: 15px 20px; 
            border-radius: 5px; 
            margin-bottom: 25px;
        }
        .header table { width: 100%; color: #fff; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 300; letter-spacing: 1px; }
        .header .sub-title { font-size: 11px; opacity: 0.9; margin-top: 5px; }
        .session-id { font-size: 18px; font-weight: bold; background: rgba(0,0,0,0.15); padding: 5px 15px; border-radius: 4px; }
        
        /* TARJETAS DE INFORMACION */
        .info-panel { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 25px; 
        }
        .info-panel td { 
            background: #ecf0f1; 
            padding: 10px 15px; 
            border: 1px solid #bdc3c7; 
            width: 25%;
        }
        .info-panel .lbl { font-size: 10px; text-transform: uppercase; color: #7f8c8d; font-weight: bold; display: block; margin-bottom: 2px; }
        .info-panel .val { font-size: 13px; font-weight: bold; color: #34495e; }
        
        /* ESTADOS */
        .badge { padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; color: #fff; display: inline-block; }
        .bg-open { background-color: #f39c12; }
        .bg-closed { background-color: #27ae60; }
        .bg-void { background-color: #c0392b; }

        /* TABLAS DE DATOS */
        .data-section { margin-bottom: 20px; }
        .section-title { 
            font-size: 14px; 
            color: #2c3e50; 
            border-bottom: 2px solid #3498db; 
            padding-bottom: 5px; 
            margin-bottom: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .clean-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .clean-table th { 
            background-color: #f8f9fa; 
            color: #34495e; 
            font-size: 11px; 
            padding: 8px; 
            text-align: left; 
            border-bottom: 2px solid #bdc3c7; 
        }
        .clean-table td { 
            padding: 8px; 
            font-size: 12px; 
            border-bottom: 1px solid #ecf0f1; 
        }
        .clean-table tr:nth-child(even) td { background-color: #fcfcfc; }
        
        /* UTILIDADES */
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .font-bold { font-weight: bold; }
        .text-green { color: #27ae60; }
        .text-red { color: #c0392b; }
        .text-blue { color: #2980b9; }
        
        .highlight-row td { background-color: #e8f4f8 !important; font-weight: bold; border-top: 2px solid #3498db; border-bottom: 2px solid #3498db; }
        
        /* FIRMAS */
        .signature-box { 
            width: 250px; 
            border-top: 1px solid #7f8c8d; 
            text-align: center; 
            margin: 60px auto 10px auto; 
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <!-- CABECERA -->
    <div class="header">
        <table border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td width="70%">
                    <h1>RESUMEN DE TURNO Y ARQUEO</h1>
                    <div class="sub-title">
                        <b>{{ $sesion->caja->nombre ?? 'Caja Principal' }}</b> | {{ $sesion->caja->sucursal ?? 'Sucursal Matriz' }}<br>
                        Emitido el: {{ date('d/m/Y H:i') }}
                    </div>
                </td>
                <td width="30%" class="text-right">
                    <span class="session-id">REF: #{{ str_pad($sesion->id, 7, "0", STR_PAD_LEFT) }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- PANELES DE INFORMACION -->
    <table class="info-panel">
        <tr>
            <td>
                <span class="lbl">Cajero Responsable</span>
                <span class="val">{{ mb_strtoupper($sesion->usuario->nombres) }}</span>
            </td>
            <td>
                <span class="lbl">Apertura</span>
                <span class="val">{{ date('d/m/Y h:i A', strtotime($sesion->fecha_apertura)) }}</span>
            </td>
            <td>
                <span class="lbl">Cierre</span>
                <span class="val">
                    {{ $sesion->fecha_cierre ? date('d/m/Y h:i A', strtotime($sesion->fecha_cierre)) : 'Sin Cierre' }}
                </span>
            </td>
            <td>
                <span class="lbl">Estado del Turno</span>
                <span class="val">
                    @if($sesion->estado === 'ABIERTA')
                        <span class="badge bg-open">EN CURSO</span>
                    @elseif($sesion->estado === 'ANULADA')
                        <span class="badge bg-void">ANULADA</span>
                    @else
                        <span class="badge bg-closed">CERRADA</span>
                    @endif
                </span>
            </td>
        </tr>
    </table>

    <!-- CONTENEDOR DE DOS COLUMNAS -->
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
        <tr>
            <!-- COLUMNA IZQUIERDA: RESUMEN FINANCIERO -->
            <td width="48%" valign="top">
                <div class="data-section">
                    <div class="section-title">Análisis de Valores (Sistema)</div>
                    <table class="clean-table">
                        <tr>
                            <td>[+] Fondo de Apertura</td>
                            <td class="text-right">${{ number_format($sesion->monto_inicial, 2) }}</td>
                        </tr>
                        <tr>
                            <td>[+] Ventas Efectivo</td>
                            <td class="text-right text-green font-bold">${{ number_format($total_efectivo, 2) }}</td>
                        </tr>
                        <tr>
                            <td>[+] Entradas Manuales</td>
                            <td class="text-right">${{ number_format($ingresos_manuales, 2) }}</td>
                        </tr>
                        <tr>
                            <td>[-] Salidas Manuales</td>
                            <td class="text-right text-red">-${{ number_format($egresos_manuales, 2) }}</td>
                        </tr>
                        <tr class="highlight-row">
                            <td>(=) SALDO TEÓRICO ESPERADO</td>
                            <td class="text-right font-bold text-blue">${{ number_format($saldo_teorico, 2) }}</td>
                        </tr>
                    </table>
                </div>

                <div class="data-section">
                    <div class="section-title">Liquidación de Arqueo Físico</div>
                    <table class="clean-table">
                        <tr>
                            <td>Efectivo en Sistema</td>
                            <td class="text-right">${{ number_format($saldo_teorico, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Efectivo Contado por Cajero</td>
                            <td class="text-right font-bold">${{ number_format($sesion->efectivo_real ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            @php
                                $diferencia = $sesion->diferencia ?? 0;
                            @endphp
                            <td class="font-bold">RESULTADO (Faltante / Sobrante)</td>
                            <td class="text-right font-bold {{ $diferencia < 0 ? 'text-red' : ($diferencia > 0 ? 'text-green' : '') }}">
                                ${{ number_format($diferencia, 2) }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            
            <td width="4%"></td> <!-- Espacio Central -->

            <!-- COLUMNA DERECHA: DESGLOSES -->
            <td width="48%" valign="top">
                <div class="data-section">
                    <div class="section-title">Consolidado General de Ingresos</div>
                    <table class="clean-table">
                        <thead>
                            <tr>
                                <th>Forma de Pago</th>
                                <th class="text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Efectivo</td>
                                <td class="text-right">${{ number_format($sesion->ventas_efectivo, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Tarjetas de Crédito / Débito</td>
                                <td class="text-right">${{ number_format($sesion->ventas_tarjetas, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Transferencias Bancarias</td>
                                <td class="text-right">${{ number_format($sesion->ventas_transferencia, 2) }}</td>
                            </tr>
                            <tr style="background-color: #ecf0f1;">
                                <td class="font-bold">TOTAL VENDIDO</td>
                                <td class="text-right font-bold">${{ number_format($sesion->ventas_efectivo + $sesion->ventas_tarjetas + $sesion->ventas_transferencia, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                @if($arqueo && $sesion->desgloses && $sesion->desgloses->where('tipo_operacion', 'ARQUEO')->count() > 0)
                <div class="data-section">
                    <div class="section-title">Desglose Físico de Moneda (Cierre)</div>
                    <table class="clean-table">
                        <thead>
                            <tr>
                                <th>Denominación</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sesion->desgloses->where('tipo_operacion', 'ARQUEO') as $des)
                            <tr>
                                <td>{{ $des->tipo_moneda }} de ${{ number_format($des->denominacion, 2) }}</td>
                                <td class="text-center">{{ $des->cantidad }}</td>
                                <td class="text-right">${{ number_format($des->subtotal, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </td>
        </tr>
    </table>

    <!-- SECCION DETALLE DE MOVIMIENTOS -->
    @if($movimientos->whereNull('venta_id')->count() > 0)
    <div class="data-section" style="margin-top: 20px;">
        <div class="section-title">Bitácora de Movimientos Manuales (Ingresos/Egresos)</div>
        <table class="clean-table">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Operación</th>
                    <th>Concepto Registrado</th>
                    <th class="text-right">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movimientos->whereNull('venta_id') as $mov)
                <tr>
                    <td>{{ date('H:i A', strtotime($mov->fecha_hora)) }}</td>
                    <td><span class="badge {{ $mov->tipo === 'INGRESO' ? 'bg-closed' : 'bg-void' }}">{{ $mov->tipo }}</span></td>
                    <td>{{ $mov->concepto }}</td>
                    <td class="text-right font-bold {{ $mov->tipo === 'INGRESO' ? 'text-green' : 'text-red' }}">
                        {{ $mov->tipo === 'INGRESO' ? '+' : '-' }} ${{ number_format($mov->monto, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- OBSERVACIONES -->
    @if($sesion->observaciones)
    <div class="data-section" style="margin-top: 20px;">
        <div class="section-title">Anotaciones / Observaciones</div>
        <div style="background-color: #fdfbf7; border: 1px solid #e1d8c1; padding: 15px; border-radius: 4px; font-size: 12px; font-style: italic;">
            {!! nl2br(e($sesion->observaciones)) !!}
        </div>
    </div>
    @endif

    <!-- FIRMA -->
    <div class="signature-box">
        <span class="font-bold">Firma de Conformidad</span><br>
        <span style="font-size: 11px; color: #7f8c8d;">{{ mb_strtoupper($sesion->usuario->nombres) }}</span>
    </div>

</body>
</html>
