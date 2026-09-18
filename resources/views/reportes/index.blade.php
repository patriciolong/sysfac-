@extends('layouts.app')

@section('title', 'Centro de Reportes & Analítica')

@section('content')

<!-- REPORTES HEADER -->
<div class="data-card" style="padding: 0.95rem 1.35rem; margin-bottom: 1.15rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
        <div>
            <h1 style="margin-bottom: 0.2rem; font-size: 1.35rem;"><i class="fa-solid fa-chart-pie text-primary"></i> Centro de Reportes & Analítica Integral</h1>
            <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Informes consolidados de facturación, compras, inventario valorizado, arqueos de caja y liquidación tributaria SRI.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            @if($tab === 'ventas')
                <a href="{{ route('reportes.export.ventas', request()->query()) }}" class="btn-card-action btn-success btn-sm">
                    <i class="fa-solid fa-file-csv"></i> Exportar Ventas CSV
                </a>
            @elseif($tab === 'compras')
                <a href="{{ route('reportes.export.compras', request()->query()) }}" class="btn-card-action btn-success btn-sm">
                    <i class="fa-solid fa-file-csv"></i> Exportar Compras CSV
                </a>
            @elseif($tab === 'inventario')
                <a href="{{ route('reportes.export.inventario', request()->query()) }}" class="btn-card-action btn-success btn-sm">
                    <i class="fa-solid fa-file-csv"></i> Exportar Inventario CSV
                </a>
            @elseif($tab === 'caja')
                <a href="{{ route('reportes.export.caja', request()->query()) }}" class="btn-card-action btn-success btn-sm">
                    <i class="fa-solid fa-file-csv"></i> Exportar Cierres Caja CSV
                </a>
            @elseif($tab === 'tributario')
                <a href="{{ route('reportes.export.tributario', request()->query()) }}" class="btn-card-action btn-success btn-sm">
                    <i class="fa-solid fa-file-csv"></i> Exportar SRI 104 CSV
                </a>
            @else
                <a href="{{ route('reportes.export.ventas', request()->query()) }}" class="btn-card-action btn-secondary btn-sm">
                    <i class="fa-solid fa-download text-primary"></i> Ventas CSV
                </a>
                <a href="{{ route('reportes.export.tributario', request()->query()) }}" class="btn-card-action btn-success btn-sm">
                    <i class="fa-solid fa-file-invoice-dollar"></i> SRI 104 CSV
                </a>
            @endif
        </div>
    </div>
</div>

<!-- GLOBAL DATE FILTER TOOLBAR -->
<div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1.15rem;">
    <form method="GET" action="{{ route('reportes.index') }}" id="formFiltroReportes">
        <input type="hidden" name="tab" value="{{ $tab }}">
        
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
            <!-- PRESET BUTTONS -->
            <div style="display: flex; gap: 0.35rem; flex-wrap: wrap; align-items: center;">
                <span style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-right: 0.25rem;">
                    <i class="fa-solid fa-calendar-days"></i> Período:
                </span>
                <a href="{{ route('reportes.index', ['tab' => $tab, 'rango' => 'hoy']) }}" 
                   class="btn-card-action btn-sm {{ $rango === 'hoy' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 4px 10px;">Hoy</a>
                
                <a href="{{ route('reportes.index', ['tab' => $tab, 'rango' => 'esta_semana']) }}" 
                   class="btn-card-action btn-sm {{ $rango === 'esta_semana' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 4px 10px;">Esta Semana</a>
                
                <a href="{{ route('reportes.index', ['tab' => $tab, 'rango' => 'este_mes']) }}" 
                   class="btn-card-action btn-sm {{ $rango === 'este_mes' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 4px 10px;">Este Mes</a>
                
                <a href="{{ route('reportes.index', ['tab' => $tab, 'rango' => 'mes_anterior']) }}" 
                   class="btn-card-action btn-sm {{ $rango === 'mes_anterior' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 4px 10px;">Mes Anterior</a>
                
                <a href="{{ route('reportes.index', ['tab' => $tab, 'rango' => 'este_anio']) }}" 
                   class="btn-card-action btn-sm {{ $rango === 'este_anio' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 4px 10px;">Este Año</a>
            </div>

            <!-- CUSTOM DATE PICKERS -->
            <div style="display: flex; align-items: center; gap: 0.45rem;">
                <input type="hidden" name="rango" value="personalizado">
                <div style="display: flex; align-items: center; gap: 0.25rem;">
                    <span style="font-size: 0.75rem; color: var(--text-muted);">Desde:</span>
                    <input type="date" name="fecha_desde" class="form-control" style="height: 32px; font-size: 0.8rem; width: 135px; padding: 2px 6px;" value="{{ $desde }}">
                </div>
                <div style="display: flex; align-items: center; gap: 0.25rem;">
                    <span style="font-size: 0.75rem; color: var(--text-muted);">Hasta:</span>
                    <input type="date" name="fecha_hasta" class="form-control" style="height: 32px; font-size: 0.8rem; width: 135px; padding: 2px 6px;" value="{{ $hasta }}">
                </div>
                <button type="submit" class="btn-card-action btn-primary btn-sm" style="height: 32px; padding: 0 10px;">
                    <i class="fa-solid fa-play"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- NAVIGATION TABS -->
<div style="display: flex; gap: 0.4rem; margin-bottom: 1.15rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; overflow-x: auto;">
    <a href="{{ route('reportes.index', ['tab' => 'general', 'rango' => $rango, 'fecha_desde' => $desde, 'fecha_hasta' => $hasta]) }}" 
       class="nav-card-item {{ $tab === 'general' ? 'active' : '' }}">
        <i class="fa-solid fa-chart-line icon"></i> <span>Resumen General</span>
    </a>
    <a href="{{ route('reportes.index', ['tab' => 'ventas', 'rango' => $rango, 'fecha_desde' => $desde, 'fecha_hasta' => $hasta]) }}" 
       class="nav-card-item {{ $tab === 'ventas' ? 'active' : '' }}">
        <i class="fa-solid fa-file-invoice-dollar icon"></i> <span>Ventas & Facturación SRI</span>
    </a>
    <a href="{{ route('reportes.index', ['tab' => 'compras', 'rango' => $rango, 'fecha_desde' => $desde, 'fecha_hasta' => $hasta]) }}" 
       class="nav-card-item {{ $tab === 'compras' ? 'active' : '' }}">
        <i class="fa-solid fa-cart-shopping icon"></i> <span>Compras & Proveedores</span>
    </a>
    <a href="{{ route('reportes.index', ['tab' => 'inventario', 'rango' => $rango, 'fecha_desde' => $desde, 'fecha_hasta' => $hasta]) }}" 
       class="nav-card-item {{ $tab === 'inventario' ? 'active' : '' }}">
        <i class="fa-solid fa-boxes-stacked icon"></i> <span>Inventario Valorizado</span>
    </a>
    <a href="{{ route('reportes.index', ['tab' => 'caja', 'rango' => $rango, 'fecha_desde' => $desde, 'fecha_hasta' => $hasta]) }}" 
       class="nav-card-item {{ $tab === 'caja' ? 'active' : '' }}">
        <i class="fa-solid fa-cash-register icon"></i> <span>Caja & Arqueos</span>
    </a>
    <a href="{{ route('reportes.index', ['tab' => 'tributario', 'rango' => $rango, 'fecha_desde' => $desde, 'fecha_hasta' => $hasta]) }}" 
       class="nav-card-item {{ $tab === 'tributario' ? 'active' : '' }}">
        <i class="fa-solid fa-receipt icon"></i> <span>Declaración SRI (104)</span>
    </a>
</div>

<!-- ========================================================================= -->
<!-- TAB 1: RESUMEN GENERAL & MARGENES -->
<!-- ========================================================================= -->
@if($tab === 'general')

    <div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 1.15rem;">
        <div class="metric-card">
            <div>
                <div class="metric-label">Ventas Totales Brutas</div>
                <div class="metric-val" style="color: var(--success);">${{ number_format($kpis['ventasTotales'], 2) }}</div>
                <small style="color: var(--text-muted); font-size: 0.72rem;">{{ $kpis['conteoFacturas'] }} Facturas Emitidas</small>
            </div>
            <div class="icon-box icon-green"><i class="fa-solid fa-money-bill-trend-up"></i></div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">Utilidad Bruta Estimada</div>
                <div class="metric-val" style="color: var(--primary);">${{ number_format($kpis['utilidadBruta'], 2) }}</div>
                <small style="color: var(--success); font-weight: 700; font-size: 0.75rem;">Margen Bruto: {{ $kpis['margenBrutoPorcentaje'] }}%</small>
            </div>
            <div class="icon-box icon-purple"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">Compras Netas Proveedor</div>
                <div class="metric-val" style="color: var(--danger);">${{ number_format($kpis['comprasNetas'], 2) }}</div>
                <small style="color: var(--text-muted); font-size: 0.72rem;">{{ $kpis['conteoCompras'] }} Facturas Compras</small>
            </div>
            <div class="icon-box icon-rose"><i class="fa-solid fa-cart-shopping"></i></div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">Patrimonio en Inventario</div>
                <div class="metric-val">${{ number_format($kpis['valoracionCosto'], 2) }}</div>
                <small style="color: var(--accent); font-size: 0.72rem;">PVP Est.: ${{ number_format($kpis['valoracionPVP'], 2) }}</small>
            </div>
            <div class="icon-box icon-blue"><i class="fa-solid fa-vault"></i></div>
        </div>
    </div>

    <!-- P&L / FINANCIAL SUMMARY BREAKDOWN -->
    <div class="data-card" style="margin-bottom: 1.15rem;">
        <div class="data-card-header">
            <h2><i class="fa-solid fa-scale-balanced text-primary"></i> Estado de Resultados Operativo ({{ $tituloRango }})</h2>
            <span class="badge badge-info"><i class="fa-solid fa-calendar"></i> Período Activo</span>
        </div>

        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 1.25rem;">
            <div>
                <table class="custom-table" style="font-size: 0.88rem;">
                    <tbody>
                        <tr>
                            <td><strong>(+) Ventas Netas sin Impuestos</strong></td>
                            <td style="text-align: right; font-family: monospace; font-weight: 700; color: var(--success);">
                                ${{ number_format($kpis['ventasSubtotal'], 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td><span style="color: var(--text-muted); padding-left: 1rem;">• Base Gravada Tarifa 15%</span></td>
                            <td style="text-align: right; font-family: monospace; color: var(--text-muted);">
                                ${{ number_format($kpis['ventasBase15'], 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td><span style="color: var(--text-muted); padding-left: 1rem;">• Base Tarifa 0%</span></td>
                            <td style="text-align: right; font-family: monospace; color: var(--text-muted);">
                                ${{ number_format($kpis['ventasBase0'], 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong>(-) Costo de Mercadería Vendida (COGS)</strong></td>
                            <td style="text-align: right; font-family: monospace; font-weight: 700; color: var(--danger);">
                                -${{ number_format($kpis['costoMercaderiaVendida'], 2) }}
                            </td>
                        </tr>
                        <tr style="background: var(--bg-muted); border-top: 2px solid var(--border-color);">
                            <td><strong style="font-size: 0.95rem; color: var(--text-main);">(=) Utilidad Bruta Operativa</strong></td>
                            <td style="text-align: right; font-family: monospace; font-weight: 800; font-size: 1.1rem; color: var(--primary);">
                                ${{ number_format($kpis['utilidadBruta'], 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Margen Bruto sobre Ventas</strong></td>
                            <td style="text-align: right; font-weight: 800; color: var(--success);">
                                {{ $kpis['margenBrutoPorcentaje'] }}%
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- LIQUIDITY & TAX PREVIEW -->
            <div style="background: var(--bg-muted); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 1rem;">
                <h3 style="font-size: 0.9rem; margin-bottom: 0.65rem; color: var(--text-main);"><i class="fa-solid fa-landmark text-primary"></i> Resumen Tributario del Período</h3>
                
                <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 0.35rem;">
                    <span style="color: var(--text-muted);">IVA Débito (Ventas):</span>
                    <span style="font-family: monospace; font-weight: 600;">+${{ number_format($kpis['ivaDebitoFiscal'], 2) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">IVA Crédito (Compras Netas):</span>
                    <span style="font-family: monospace; font-weight: 600; color: var(--danger);">-${{ number_format($kpis['ivaCreditoFiscal'], 2) }}</span>
                </div>
                
                <div style="border-top: 1px solid var(--border-color); padding-top: 0.5rem; margin-bottom: 0.85rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong style="font-size: 0.85rem;">{{ $kpis['impuestoCausado'] >= 0 ? 'IVA a Pagar al SRI:' : 'Crédito Tributario a Favor:' }}</strong>
                        <strong style="font-family: monospace; font-size: 1.1rem; color: {{ $kpis['impuestoCausado'] >= 0 ? 'var(--danger)' : 'var(--success)' }};">
                            ${{ number_format(abs($kpis['impuestoCausado']), 2) }}
                        </strong>
                    </div>
                </div>

                <a href="{{ route('reportes.index', ['tab' => 'tributario', 'rango' => $rango, 'fecha_desde' => $desde, 'fecha_hasta' => $hasta]) }}" class="btn-card-action btn-secondary btn-sm" style="width: 100%; text-align: center;">
                    Ver Formulario 104 Completo <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- PREVIEWS: TOP PRODUCTS & CLIENTS -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.15rem;">
        <div class="data-card">
            <div class="data-card-header">
                <h2><i class="fa-solid fa-trophy text-primary"></i> Top 5 Productos Más Vendidos</h2>
            </div>
            <table class="custom-table" style="font-size: 0.82rem;">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th style="text-align: center;">Unidades</th>
                        <th style="text-align: right;">Total ($)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProductos->take(5) as $idx => $p)
                    <tr>
                        <td><strong>{{ $p->nombre }}</strong></td>
                        <td style="text-align: center; font-weight: 700;">{{ number_format($p->total_unidades, 0) }}</td>
                        <td style="text-align: right; font-family: monospace; color: var(--success); font-weight: 700;">${{ number_format($p->total_recaudado, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">Sin ventas en el período</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="data-card">
            <div class="data-card-header">
                <h2><i class="fa-solid fa-star text-warning"></i> Top 5 Clientes Principales</h2>
            </div>
            <table class="custom-table" style="font-size: 0.82rem;">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th style="text-align: center;">Facturas</th>
                        <th style="text-align: right;">Total Comprado ($)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topClientes->take(5) as $c)
                    <tr>
                        <td>
                            <strong>{{ $c->razon_social }}</strong>
                            <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">{{ $c->identificacion }}</div>
                        </td>
                        <td style="text-align: center; font-weight: 700;">{{ $c->total_facturas }}</td>
                        <td style="text-align: right; font-family: monospace; color: var(--primary); font-weight: 700;">${{ number_format($c->total_comprado, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">Sin ventas en el período</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

<!-- ========================================================================= -->
<!-- TAB 2: VENTAS & FACTURACIÓN SRI -->
<!-- ========================================================================= -->
@elseif($tab === 'ventas')

    <div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.15rem;">
        <div class="metric-card">
            <div>
                <div class="metric-label">Total Facturado</div>
                <div class="metric-val" style="color: var(--success);">${{ number_format($kpis['ventasTotales'], 2) }}</div>
            </div>
            <div class="icon-box icon-green"><i class="fa-solid fa-chart-line"></i></div>
        </div>
        <div class="metric-card">
            <div>
                <div class="metric-label">Base Tarifa 15%</div>
                <div class="metric-val">${{ number_format($kpis['ventasBase15'], 2) }}</div>
            </div>
            <div class="icon-box icon-purple"><i class="fa-solid fa-percent"></i></div>
        </div>
        <div class="metric-card">
            <div>
                <div class="metric-label">IVA 15% Generado</div>
                <div class="metric-val" style="color: var(--primary);">${{ number_format($kpis['ventasIva15'], 2) }}</div>
            </div>
            <div class="icon-box icon-blue"><i class="fa-solid fa-receipt"></i></div>
        </div>
        <div class="metric-card">
            <div>
                <div class="metric-label">Ticket Promedio</div>
                <div class="metric-val">${{ number_format($kpis['ticketPromedio'], 2) }}</div>
                <small style="color: var(--text-muted); font-size: 0.72rem;">{{ $kpis['conteoFacturas'] }} Facturas</small>
            </div>
            <div class="icon-box icon-amber"><i class="fa-solid fa-receipt"></i></div>
        </div>
    </div>

    <!-- TOP PRODUCTS RANKING -->
    <div class="data-card" style="margin-bottom: 1.15rem;">
        <div class="data-card-header">
            <h2><i class="fa-solid fa-ranking-star text-primary"></i> Ranking de Productos Más Vendidos ({{ $tituloRango }})</h2>
            <a href="{{ route('reportes.export.ventas', request()->query()) }}" class="btn-card-action btn-success btn-sm">
                <i class="fa-solid fa-file-csv"></i> Exportar Todo a CSV
            </a>
        </div>
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Producto / Descripción</th>
                    <th style="text-align: center;">Unidades Vendidas</th>
                    <th style="text-align: right;">Total Recaudado sin Impuestos</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topProductos as $idx => $p)
                <tr>
                    <td style="color: var(--text-muted); font-weight: 700;">#{{ $idx + 1 }}</td>
                    <td><strong>{{ $p->nombre }}</strong></td>
                    <td style="text-align: center; font-weight: 700; font-family: monospace;">{{ number_format($p->total_unidades, 0) }} uds</td>
                    <td style="text-align: right; font-family: monospace; font-weight: 700; color: var(--success);">${{ number_format($p->total_recaudado, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">Sin movimientos de ventas en este período</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- INVOICES LIST TABLE -->
    <div class="data-card">
        <div class="data-card-header">
            <h2><i class="fa-solid fa-list-check text-primary"></i> Últimas Facturas Emitidas en el Período</h2>
        </div>
        <table class="custom-table" style="font-size: 0.84rem;">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>No. Factura</th>
                    <th>Cliente</th>
                    <th style="text-align: right;">Subtotal</th>
                    <th style="text-align: right;">IVA (15%)</th>
                    <th style="text-align: right;">Total</th>
                    <th style="text-align: center;">Estado SRI</th>
                </tr>
            </thead>
            <tbody>
                @forelse($facturasLista as $f)
                <tr>
                    <td>{{ $f->fecha_emision ? date('d/m/Y', strtotime($f->fecha_emision)) : '-' }}</td>
                    <td><strong style="font-family: monospace; color: var(--primary);">{{ $f->numero_comprobante ?? '001-001-'.str_pad($f->id, 9, '0', STR_PAD_LEFT) }}</strong></td>
                    <td>
                        <strong>{{ $f->cliente->razon_social ?? 'Consumidor Final' }}</strong>
                        <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">{{ $f->cliente->identificacion ?? '9999999999999' }}</div>
                    </td>
                    <td style="text-align: right; font-family: monospace;">${{ number_format($f->total_sin_impuestos, 2) }}</td>
                    <td style="text-align: right; font-family: monospace; color: var(--text-muted);">${{ number_format($f->valor_iva, 2) }}</td>
                    <td style="text-align: right; font-family: monospace; font-weight: 700; color: var(--success);">${{ number_format($f->importe_total, 2) }}</td>
                    <td style="text-align: center;"><span class="badge badge-success">{{ $f->estado_sri ?? 'AUTORIZADO' }}</span></td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">No hay facturas registradas en este rango</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

<!-- ========================================================================= -->
<!-- TAB 3: COMPRAS & PROVEEDORES -->
<!-- ========================================================================= -->
@elseif($tab === 'compras')

    <div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.15rem;">
        <div class="metric-card">
            <div>
                <div class="metric-label">Compras Brutas</div>
                <div class="metric-val" style="color: var(--danger);">${{ number_format($kpis['comprasTotales'], 2) }}</div>
                <small style="color: var(--text-muted); font-size: 0.72rem;">{{ $kpis['conteoCompras'] }} Facturas Registradas</small>
            </div>
            <div class="icon-box icon-rose"><i class="fa-solid fa-cart-shopping"></i></div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">Notas de Crédito Recibidas</div>
                <div class="metric-val" style="color: var(--warning);">${{ number_format($kpis['ncsTotales'], 2) }}</div>
                <small style="color: var(--text-muted); font-size: 0.72rem;">{{ $kpis['conteoNcs'] }} Devoluciones / NCs</small>
            </div>
            <div class="icon-box icon-amber"><i class="fa-solid fa-file-circle-minus"></i></div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">Compras Netas (Inversión)</div>
                <div class="metric-val" style="color: var(--primary);">${{ number_format($kpis['comprasNetas'], 2) }}</div>
                <small style="color: var(--text-muted); font-size: 0.72rem;">Compras (-) Notas de Crédito</small>
            </div>
            <div class="icon-box icon-purple"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">IVA Crédito Tributario</div>
                <div class="metric-val" style="color: var(--accent);">${{ number_format($kpis['ivaCreditoFiscal'], 2) }}</div>
                <small style="color: var(--text-muted); font-size: 0.72rem;">A favor en declaración SRI</small>
            </div>
            <div class="icon-box icon-sky"><i class="fa-solid fa-receipt"></i></div>
        </div>
    </div>

    <!-- TOP SUPPLIERS TABLE -->
    <div class="data-card" style="margin-bottom: 1.15rem;">
        <div class="data-card-header">
            <h2><i class="fa-solid fa-truck-field text-primary"></i> Proveedores con Mayor Volumen de Compra ({{ $tituloRango }})</h2>
            <a href="{{ route('reportes.export.compras', request()->query()) }}" class="btn-card-action btn-success btn-sm">
                <i class="fa-solid fa-file-csv"></i> Exportar Compras CSV
            </a>
        </div>
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Proveedor</th>
                    <th>Identificación / RUC</th>
                    <th style="text-align: center;">Facturas</th>
                    <th style="text-align: right;">Total Comprado ($)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topProveedores as $idx => $prov)
                <tr>
                    <td style="color: var(--text-muted); font-weight: 700;">#{{ $idx + 1 }}</td>
                    <td><strong>{{ $prov->razon_social }}</strong></td>
                    <td><span style="font-family: monospace;">{{ $prov->identificacion }}</span></td>
                    <td style="text-align: center; font-weight: 700;">{{ $prov->total_facturas }}</td>
                    <td style="text-align: right; font-family: monospace; font-weight: 700; color: var(--danger);">${{ number_format($prov->total_comprado, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">Sin compras registradas en este período</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- PURCHASES LIST TABLE -->
    <div class="data-card">
        <div class="data-card-header">
            <h2><i class="fa-solid fa-list-check text-primary"></i> Últimas Facturas de Compra</h2>
        </div>
        <table class="custom-table" style="font-size: 0.84rem;">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>No. Factura Proveedor</th>
                    <th>Proveedor</th>
                    <th>Bodega Destino</th>
                    <th style="text-align: right;">Subtotal</th>
                    <th style="text-align: right;">IVA</th>
                    <th style="text-align: right;">Total Factura</th>
                    <th style="text-align: right;">Saldo Pendiente</th>
                </tr>
            </thead>
            <tbody>
                @forelse($comprasLista as $c)
                <tr>
                    <td>{{ $c->fecha_emision ? date('d/m/Y', strtotime($c->fecha_emision)) : '-' }}</td>
                    <td><strong style="font-family: monospace; color: var(--primary);">{{ $c->numero_factura }}</strong></td>
                    <td>
                        <strong>{{ $c->proveedor->razon_social ?? 'Proveedor' }}</strong>
                        <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">{{ $c->proveedor->identificacion ?? 'S/R' }}</div>
                    </td>
                    <td><span class="badge badge-info">{{ $c->bodega->nombre ?? 'Principal' }}</span></td>
                    <td style="text-align: right; font-family: monospace;">${{ number_format($c->subtotal_sin_impuestos, 2) }}</td>
                    <td style="text-align: right; font-family: monospace; color: var(--text-muted);">${{ number_format($c->iva, 2) }}</td>
                    <td style="text-align: right; font-family: monospace; font-weight: 700; color: var(--danger);">${{ number_format($c->total, 2) }}</td>
                    <td style="text-align: right; font-family: monospace; font-weight: 700;">${{ number_format($c->saldo_pendiente, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">No hay compras en este rango de fechas</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

<!-- ========================================================================= -->
<!-- TAB 4: INVENTARIO VALORIZADO -->
<!-- ========================================================================= -->
@elseif($tab === 'inventario')

    <div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.15rem;">
        <div class="metric-card">
            <div>
                <div class="metric-label">Valor Total (Al Costo)</div>
                <div class="metric-val" style="color: var(--primary);">${{ number_format($kpis['valoracionCosto'], 2) }}</div>
                <small style="color: var(--text-muted); font-size: 0.72rem;">Costo Promedio Ponderado</small>
            </div>
            <div class="icon-box icon-purple"><i class="fa-solid fa-vault"></i></div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">Valor Estimado (A PVP)</div>
                <div class="metric-val" style="color: var(--success);">${{ number_format($kpis['valoracionPVP'], 2) }}</div>
                <small style="color: var(--success); font-size: 0.72rem;">Margen Potencial: ${{ number_format($kpis['margenPotencial'], 2) }}</small>
            </div>
            <div class="icon-box icon-green"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">Existencias Físicas</div>
                <div class="metric-val">{{ number_format($kpis['totalStockUnidades'], 0) }} <small style="font-size: 0.75rem; font-weight: normal; color: var(--text-muted);">uds</small></div>
                <small style="color: var(--text-muted); font-size: 0.72rem;">{{ $kpis['totalItems'] }} Referencias Activas</small>
            </div>
            <div class="icon-box icon-blue"><i class="fa-solid fa-boxes-packing"></i></div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">Alertas de Stock</div>
                <div class="metric-val" style="font-size: 1.1rem; display: flex; gap: 0.4rem; align-items: center;">
                    <span class="badge badge-warning" title="Stock Bajo">{{ $kpis['productosBajoStock'] }} Bajos</span>
                    <span class="badge badge-danger" title="Agotados">{{ $kpis['productosAgotados'] }} Cero</span>
                </div>
            </div>
            <div class="icon-box icon-amber"><i class="fa-solid fa-triangle-exclamation"></i></div>
        </div>
    </div>

    <!-- WAREHOUSE BREAKDOWN TABLE -->
    <div class="data-card" style="margin-bottom: 1.15rem;">
        <div class="data-card-header">
            <h2><i class="fa-solid fa-warehouse text-primary"></i> Existencias y Valoración por Bodega Física</h2>
            <a href="{{ route('reportes.export.inventario') }}" class="btn-card-action btn-success btn-sm">
                <i class="fa-solid fa-file-csv"></i> Exportar Inventario Completo CSV
            </a>
        </div>
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Código Bodega</th>
                    <th>Nombre de Bodega</th>
                    <th style="text-align: center;">Total Unidades</th>
                    <th style="text-align: right;">Valoración al Costo ($)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bodegasResumen as $b)
                <tr>
                    <td><strong style="font-family: monospace;">{{ $b['codigo'] }}</strong></td>
                    <td><strong>{{ $b['nombre'] }}</strong></td>
                    <td style="text-align: center; font-family: monospace; font-weight: 700;">{{ number_format($b['unidades'], 0) }} uds</td>
                    <td style="text-align: right; font-family: monospace; font-weight: 700; color: var(--primary);">${{ number_format($b['valor_costo'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

<!-- ========================================================================= -->
<!-- TAB 5: CAJA & ARQUEOS -->
<!-- ========================================================================= -->
@elseif($tab === 'caja')

    <div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.15rem;">
        <div class="metric-card">
            <div>
                <div class="metric-label">Recaudación en Caja</div>
                <div class="metric-val" style="color: var(--success);">${{ number_format($kpis['totalRecaudadoCaja'], 2) }}</div>
                <small style="color: var(--text-muted); font-size: 0.72rem;">{{ $kpis['totalTurnos'] }} Turnos Procesados</small>
            </div>
            <div class="icon-box icon-green"><i class="fa-solid fa-cash-register"></i></div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">Ventas en Efectivo</div>
                <div class="metric-val">${{ number_format($kpis['totalVentasEfectivo'], 2) }}</div>
            </div>
            <div class="icon-box icon-blue"><i class="fa-solid fa-money-bill-1-wave"></i></div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">Ventas Transferencias</div>
                <div class="metric-val" style="color: var(--primary);">${{ number_format($kpis['totalVentasTransferencia'], 2) }}</div>
            </div>
            <div class="icon-box icon-purple"><i class="fa-solid fa-building-columns"></i></div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">Ventas con Tarjetas</div>
                <div class="metric-val" style="color: var(--accent);">${{ number_format($kpis['totalVentasTarjetas'], 2) }}</div>
            </div>
            <div class="icon-box icon-sky"><i class="fa-solid fa-credit-card"></i></div>
        </div>
    </div>

    <!-- CASH SESSIONS TABLE -->
    <div class="data-card">
        <div class="data-card-header">
            <h2><i class="fa-solid fa-clock-rotate-left text-primary"></i> Historial de Turnos y Cierres Z de Caja</h2>
            <a href="{{ route('reportes.export.caja', request()->query()) }}" class="btn-card-action btn-success btn-sm">
                <i class="fa-solid fa-file-csv"></i> Exportar Cierres CSV
            </a>
        </div>
        <table class="custom-table" style="font-size: 0.84rem;">
            <thead>
                <tr>
                    <th>Caja / Punto</th>
                    <th>Cajero</th>
                    <th>Apertura</th>
                    <th>Cierre</th>
                    <th style="text-align: right;">Efectivo ($)</th>
                    <th style="text-align: right;">Transferencia ($)</th>
                    <th style="text-align: right;">Tarjeta ($)</th>
                    <th style="text-align: right;">Total Turno ($)</th>
                    <th style="text-align: center;">Diferencia Arqueo</th>
                    <th style="text-align: center;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($turnosLista as $t)
                <tr>
                    <td><strong>{{ $t->caja->nombre ?? 'Caja 001' }}</strong></td>
                    <td>{{ $t->usuario ? ($t->usuario->name.' '.$t->usuario->apellido) : 'Cajero' }}</td>
                    <td><small>{{ $t->fecha_apertura ? date('d/m/Y H:i', strtotime($t->fecha_apertura)) : '-' }}</small></td>
                    <td><small>{{ $t->fecha_cierre ? date('d/m/Y H:i', strtotime($t->fecha_cierre)) : 'ABIERTA' }}</small></td>
                    <td style="text-align: right; font-family: monospace;">${{ number_format($t->ventas_efectivo, 2) }}</td>
                    <td style="text-align: right; font-family: monospace;">${{ number_format($t->ventas_transferencia, 2) }}</td>
                    <td style="text-align: right; font-family: monospace;">${{ number_format($t->ventas_tarjetas, 2) }}</td>
                    <td style="text-align: right; font-family: monospace; font-weight: 700; color: var(--success);">${{ number_format($t->total_ventas, 2) }}</td>
                    <td style="text-align: center; font-family: monospace;">
                        @if($t->diferencia !== null)
                            <span style="font-weight: 700; color: {{ $t->diferencia < 0 ? 'var(--danger)' : ($t->diferencia > 0 ? 'var(--warning)' : 'var(--success)') }};">
                                {{ $t->diferencia >= 0 ? '+$' : '-$' }}{{ number_format(abs($t->diferencia), 2) }}
                            </span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <span class="badge {{ $t->estado === 'ABIERTA' ? 'badge-success' : 'badge-secondary' }}">{{ $t->estado }}</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align: center; padding: 2rem; color: var(--text-muted);">No hay turnos registrados en este período</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

<!-- ========================================================================= -->
<!-- TAB 6: RESUMEN TRIBUTARIO SRI (FORMULARIO 104) -->
<!-- ========================================================================= -->
@elseif($tab === 'tributario')

    <div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.15rem;">
        <div class="metric-card">
            <div>
                <div class="metric-label">IVA Débito (Ventas)</div>
                <div class="metric-val" style="color: var(--primary);">${{ number_format($kpis['ivaDebitoFiscal'], 2) }}</div>
                <small style="color: var(--text-muted); font-size: 0.72rem;">Generado en facturas emitidas</small>
            </div>
            <div class="icon-box icon-purple"><i class="fa-solid fa-file-invoice"></i></div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">IVA Crédito (Compras Netas)</div>
                <div class="metric-val" style="color: var(--accent);">${{ number_format($kpis['ivaCreditoFiscal'], 2) }}</div>
                <small style="color: var(--text-muted); font-size: 0.72rem;">Compras (-) Notas de Crédito</small>
            </div>
            <div class="icon-box icon-sky"><i class="fa-solid fa-cart-shopping"></i></div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">{{ $kpis['impuestoCausado'] >= 0 ? 'Impuesto a Pagar SRI' : 'Crédito Tributario a Favor' }}</div>
                <div class="metric-val" style="color: {{ $kpis['impuestoCausado'] >= 0 ? 'var(--danger)' : 'var(--success)' }};">
                    ${{ number_format(abs($kpis['impuestoCausado']), 2) }}
                </div>
                <small style="color: var(--text-muted); font-size: 0.72rem;">Liquidación del período</small>
            </div>
            <div class="icon-box {{ $kpis['impuestoCausado'] >= 0 ? 'icon-rose' : 'icon-green' }}"><i class="fa-solid fa-landmark"></i></div>
        </div>

        <div class="metric-card">
            <div>
                <div class="metric-label">Total Declarado Ventas</div>
                <div class="metric-val">${{ number_format($kpis['ventasTotales'], 2) }}</div>
                <small style="color: var(--text-muted); font-size: 0.72rem;">Bases gravadas + exentas</small>
            </div>
            <div class="icon-box icon-blue"><i class="fa-solid fa-receipt"></i></div>
        </div>
    </div>

    <!-- FORMULARIO 104 OFFICIAL BREAKDOWN TABLE -->
    <div class="data-card">
        <div class="data-card-header">
            <h2><i class="fa-solid fa-file-contract text-primary"></i> Pre-Liquidación Mensual de IVA (Formulario 104 SRI) - {{ $tituloRango }}</h2>
            <a href="{{ route('reportes.export.tributario', request()->query()) }}" class="btn-card-action btn-success btn-sm">
                <i class="fa-solid fa-file-excel"></i> Exportar Formulario 104 CSV
            </a>
        </div>

        <div class="table-responsive">
            <table class="custom-table" style="font-size: 0.9rem;">
                <thead>
                    <tr style="background: var(--bg-muted);">
                        <th style="min-width: 320px;">Casillero / Concepto Tributario SRI</th>
                        <th style="text-align: right; width: 180px;">Base Imponible ($)</th>
                        <th style="text-align: right; width: 180px;">Impuesto / IVA ($)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- SECCIÓN 1: VENTAS -->
                    <tr style="background: #f8fafc;">
                        <td colspan="3"><strong style="color: var(--primary); text-transform: uppercase; font-size: 0.8rem;"><i class="fa-solid fa-arrow-up"></i> 1. Ventas Locales y Operaciones Gravadas</strong></td>
                    </tr>
                    <tr>
                        <td style="padding-left: 1.5rem;">Ventas Locales Gravadas Tarifa 15% (Bienes y Servicios)</td>
                        <td style="text-align: right; font-family: monospace;">${{ number_format($kpis['ventasBase15'], 2) }}</td>
                        <td style="text-align: right; font-family: monospace; font-weight: 700; color: var(--primary);">${{ number_format($kpis['ventasIva15'], 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding-left: 1.5rem;">Ventas Locales Tarifa 0% (Sin IVA)</td>
                        <td style="text-align: right; font-family: monospace;">${{ number_format($kpis['ventasBase0'], 2) }}</td>
                        <td style="text-align: right; font-family: monospace; color: var(--text-muted);">$0.00</td>
                    </tr>
                    <tr style="font-weight: 700;">
                        <td style="padding-left: 1.5rem;">Total Facturado en Ventas del Período</td>
                        <td style="text-align: right; font-family: monospace;">${{ number_format($kpis['ventasSubtotal'], 2) }}</td>
                        <td style="text-align: right; font-family: monospace; color: var(--primary);">${{ number_format($kpis['ivaDebitoFiscal'], 2) }}</td>
                    </tr>

                    <!-- SECCIÓN 2: COMPRAS -->
                    <tr style="background: #f8fafc;">
                        <td colspan="3"><strong style="color: var(--danger); text-transform: uppercase; font-size: 0.8rem;"><i class="fa-solid fa-arrow-down"></i> 2. Compras Locales y Crédito Tributario</strong></td>
                    </tr>
                    <tr>
                        <td style="padding-left: 1.5rem;">Compras Locales (Facturas Físicas / Electrónicas de Proveedor)</td>
                        <td style="text-align: right; font-family: monospace;">${{ number_format($kpis['comprasSubtotal'], 2) }}</td>
                        <td style="text-align: right; font-family: monospace;">${{ number_format($kpis['comprasIva'], 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding-left: 1.5rem;">(-) Notas de Crédito de Compras a Proveedores</td>
                        <td style="text-align: right; font-family: monospace; color: var(--warning);">${{ number_format($kpis['ncsSubtotal'] ?? 0, 2) }}</td>
                        <td style="text-align: right; font-family: monospace; color: var(--warning);">${{ number_format($kpis['ncsIva'] ?? 0, 2) }}</td>
                    </tr>
                    <tr style="font-weight: 700;">
                        <td style="padding-left: 1.5rem;">Compras Netas y Crédito Tributario del Mes</td>
                        <td style="text-align: right; font-family: monospace;">${{ number_format($kpis['comprasSubtotal'] - ($kpis['ncsSubtotal'] ?? 0), 2) }}</td>
                        <td style="text-align: right; font-family: monospace; color: var(--accent);">${{ number_format($kpis['ivaCreditoFiscal'], 2) }}</td>
                    </tr>

                    <!-- SECCIÓN 3: LIQUIDACIÓN -->
                    <tr style="background: #f1f5f9; border-top: 2px solid var(--border-color);">
                        <td colspan="2"><strong style="color: var(--text-main); font-size: 0.95rem;">3. Liquidación del Impuesto (Débito Fiscal - Crédito Fiscal)</strong></td>
                        <td style="text-align: right; font-family: monospace; font-weight: 800; font-size: 1.1rem; color: {{ $kpis['impuestoCausado'] >= 0 ? 'var(--danger)' : 'var(--success)' }};">
                            ${{ number_format(abs($kpis['impuestoCausado']), 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="font-size: 0.78rem; color: var(--text-muted);">
                            @if($kpis['impuestoCausado'] >= 0)
                                <i class="fa-solid fa-triangle-exclamation text-danger"></i> Valor positivo: Representa impuesto causado a pagar a favor del SRI para el período fiscal seleccionado.
                            @else
                                <i class="fa-solid fa-circle-check text-success"></i> Saldo a favor: El crédito tributario por compras superó las ventas gravadas. Se arrastra para el mes siguiente.
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

@endif

@endsection
