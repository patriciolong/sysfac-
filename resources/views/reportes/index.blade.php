@extends('layouts.app')

@section('title', 'Reportería y Estadísticas')

@section('content')

<!-- REPORTES HEADER -->
<div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1.15rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
        <div>
            <h1 style="margin-bottom: 0.15rem;"><i class="fa-solid fa-chart-pie text-primary"></i> Reportes & Estadísticas SRI</h1>
            <p style="margin: 0; font-size: 0.85rem;">Resumen de facturación, desglose de impuestos IVA y ranking de productos.</p>
        </div>
        <div style="display: flex; gap: 0.65rem;">
            <button class="btn-card-action btn-success btn-sm" onclick="alert('Generando archivo Excel de ventas para el SRI...')">
                <i class="fa-solid fa-file-excel"></i> Exportar a Excel
            </button>
        </div>
    </div>
</div>

<!-- METRIC CARDS -->
<div class="metrics-grid">
    <div class="metric-card">
        <div>
            <div class="metric-label">Ventas Totales (Mes)</div>
            <div class="metric-val" style="color: var(--success);">${{ number_format($reporte['ventas_mes'], 2) }}</div>
        </div>
        <div class="icon-box icon-green"><i class="fa-solid fa-chart-line"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Impuesto IVA (15%)</div>
            <div class="metric-val" style="color: var(--primary);">${{ number_format($reporte['valor_iva_15'], 2) }}</div>
        </div>
        <div class="icon-box icon-blue"><i class="fa-solid fa-receipt"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Subtotal Tarifa 0%</div>
            <div class="metric-val">${{ number_format($reporte['subtotal_0'], 2) }}</div>
        </div>
        <div class="icon-box icon-amber"><i class="fa-solid fa-percent"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Facturas del Mes</div>
            <div class="metric-val">{{ $reporte['total_facturas_mes'] }} Docs</div>
        </div>
        <div class="icon-box icon-purple"><i class="fa-solid fa-file-invoice"></i></div>
    </div>
</div>

<!-- REPORT DOWNLOADS TABLE -->
<div class="data-card">
    <div class="data-card-header">
        <h2><i class="fa-solid fa-file-export text-primary"></i> Catálogo de Informes y Exportaciones Oficiales SRI</h2>
    </div>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Nombre del Informe</th>
                    <th>Descripción y Contenido</th>
                    <th>Formato</th>
                    <th>Frecuencia SRI</th>
                    <th style="text-align: center;">Acción de Descarga</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><i class="fa-solid fa-file-excel text-success"></i> Ventas Mensuales SRI</strong></td>
                    <td style="color: var(--text-muted);">Detalle completo de facturas con clave de acceso, cliente, bases imponibles y desglose IVA.</td>
                    <td><span class="badge badge-success">EXCEL / CSV</span></td>
                    <td>Mensual / Tributario</td>
                    <td style="text-align: center;">
                        <button class="btn-card-action btn-success btn-sm" onclick="alert('Descargando Informe de Ventas SRI en Excel...')">
                            <i class="fa-solid fa-download"></i> Descargar Excel
                        </button>
                    </td>
                </tr>
                <tr>
                    <td><strong><i class="fa-solid fa-file-pdf text-danger"></i> Resumen de Cierres de Caja</strong></td>
                    <td style="color: var(--text-muted);">Informe acumulado de arqueos Z por turno, usuario y diferencias en gaveta física.</td>
                    <td><span class="badge badge-warning">PDF DOCUMENT</span></td>
                    <td>Diario / Por Turno</td>
                    <td style="text-align: center;">
                        <button class="btn-card-action btn-warning btn-sm" onclick="alert('Generando reporte PDF de cierres de caja...')">
                            <i class="fa-solid fa-download"></i> Descargar PDF
                        </button>
                    </td>
                </tr>
                <tr>
                    <td><strong><i class="fa-solid fa-boxes-stacked text-primary"></i> Valoración de Inventario</strong></td>
                    <td style="color: var(--text-muted);">Cálculo del patrimonio en mercadería basado en costo promedio ponderado de existencias.</td>
                    <td><span class="badge badge-info">EXCEL / PDF</span></td>
                    <td>En tiempo real</td>
                    <td style="text-align: center;">
                        <button class="btn-card-action btn-primary btn-sm" onclick="alert('Generando valoración de inventario...')">
                            <i class="fa-solid fa-download"></i> Descargar Inventario
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- TOP SELLING PRODUCTS TABLE -->
<div class="data-card" style="margin-top: 1.15rem;">
    <div class="data-card-header">
        <h2><i class="fa-solid fa-trophy text-primary"></i> Productos Más Vendidos del Mes</h2>
    </div>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Ranking</th>
                    <th>Nombre del Producto</th>
                    <th>Unidades Vendidas</th>
                    <th style="text-align: right;">Total Recaudado ($)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reporte['top_productos'] as $idx => $top)
                <tr>
                    <td>
                        <span class="badge {{ $idx === 0 ? 'badge-success' : 'badge-info' }}">
                            #{{ $idx + 1 }}
                        </span>
                    </td>
                    <td><strong>{{ $top['nombre'] }}</strong></td>
                    <td style="font-weight: 600;">{{ $top['vendidos'] }} Unidades</td>
                    <td style="color: var(--success); font-weight: 700; text-align: right;">${{ number_format($top['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
