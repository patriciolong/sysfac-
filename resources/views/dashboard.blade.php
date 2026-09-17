@extends('layouts.app')

@section('title', 'Dashboard Principal')

@section('content')

<!-- WELCOME HERO BANNER -->
<div class="hero-banner">
    <div>
        <h1>👋 ¡Hola, Juan Pérez!</h1>
        <p>Bienvenido al sistema de facturación electrónica. Seleccione un módulo para comenzar.</p>
    </div>
    <div>
        <a href="{{ url('/facturacion') }}" class="btn-card-action btn-success btn-lg">
            <i class="fa-solid fa-bolt"></i> NUEVA FACTURA (POS)
        </a>
    </div>
</div>

<!-- METRIC CARDS GRID -->
<div class="metrics-grid">
    <div class="metric-card">
        <div>
            <div class="metric-label">Ventas de Hoy</div>
            <div class="metric-val" style="color: var(--success);">${{ number_format($stats['ventas_hoy'], 2) }}</div>
        </div>
        <div class="icon-box icon-green"><i class="fa-solid fa-dollar-sign"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Facturas Emitidas</div>
            <div class="metric-val">{{ $stats['facturas_emitidas_hoy'] }}</div>
        </div>
        <div class="icon-box icon-blue"><i class="fa-solid fa-file-invoice"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Caja del Día</div>
            <div class="metric-val" style="font-size: 1.25rem; color: var(--primary);">{{ $stats['estado_caja'] }}</div>
        </div>
        <div class="icon-box icon-sky"><i class="fa-solid fa-cash-register"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Stock Bajo</div>
            <div class="metric-val" style="color: var(--danger);">{{ $stats['productos_stock_bajo'] }} Ítems</div>
        </div>
        <div class="icon-box icon-rose"><i class="fa-solid fa-triangle-exclamation"></i></div>
    </div>
</div>

<!-- ACTION CARDS NAV GRID -->
<div style="margin-bottom: 0.85rem; display: flex; align-items: center; justify-content: space-between;">
    <h2><i class="fa-solid fa-shapes text-primary"></i> Módulos Principales</h2>
</div>

<div class="action-grid">
    <!-- CARD 1: FACTURACIÓN -->
    <div class="action-card">
        <div>
            <div class="action-card-header">
                <div class="icon-box icon-green"><i class="fa-solid fa-cash-register"></i></div>
                <div>
                    <div class="action-card-title">Facturación POS</div>
                    <span class="badge badge-success">Módulo Principal</span>
                </div>
            </div>
            <div class="action-card-desc">Emisión ágil de facturas electrónicas con lector de código de barras o selección rápida de catálogo.</div>
        </div>
        <a href="{{ url('/facturacion') }}" class="btn-card-action btn-success">
            <i class="fa-solid fa-plus-circle"></i> Abrir Punto de Venta
        </a>
    </div>

    <!-- CARD 2: CAJA -->
    <div class="action-card">
        <div>
            <div class="action-card-header">
                <div class="icon-box icon-amber"><i class="fa-solid fa-vault"></i></div>
                <div>
                    <div class="action-card-title">Apertura / Cierre Caja</div>
                    <span class="badge badge-warning">Turno Activo</span>
                </div>
            </div>
            <div class="action-card-desc">Control de dinero en efectivo, arqueos en tiempo real y cierre de turno Z con impresión.</div>
        </div>
        <a href="{{ url('/caja') }}" class="btn-card-action btn-warning">
            <i class="fa-solid fa-lock-open"></i> Ver Estado de Caja
        </a>
    </div>

    <!-- CARD 3: PRODUCTOS & INVENTARIO -->
    <div class="action-card">
        <div>
            <div class="action-card-header">
                <div class="icon-box icon-blue"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div>
                    <div class="action-card-title">Productos & Stock</div>
                    <span class="badge badge-info">Catálogo</span>
                </div>
            </div>
            <div class="action-card-desc">Administración de precios de venta, tarifas de IVA, existencias mínimas y control de inventario.</div>
        </div>
        <a href="{{ url('/productos') }}" class="btn-card-action btn-primary">
            <i class="fa-solid fa-box"></i> Ver Inventario
        </a>
    </div>

    <!-- CARD 4: CLIENTES -->
    <div class="action-card">
        <div>
            <div class="action-card-header">
                <div class="icon-box icon-purple"><i class="fa-solid fa-address-book"></i></div>
                <div>
                    <div class="action-card-title">Gestión de Clientes</div>
                    <span class="badge badge-info">Directorio</span>
                </div>
            </div>
            <div class="action-card-desc">Registro y validación de clientes con Cédula, RUC, Pasaporte o Consumidor Final para facturación.</div>
        </div>
        <a href="{{ url('/clientes') }}" class="btn-card-action btn-secondary">
            <i class="fa-solid fa-user-plus"></i> Abrir Clientes
        </a>
    </div>

    <!-- CARD 5: COMPRAS -->
    <div class="action-card">
        <div>
            <div class="action-card-header">
                <div class="icon-box icon-rose"><i class="fa-solid fa-truck-ramp-box"></i></div>
                <div>
                    <div class="action-card-title">Compras & Entradas</div>
                    <span class="badge badge-info">Proveedores</span>
                </div>
            </div>
            <div class="action-card-desc">Ingreso de facturas de compra a proveedores con incremento automático de existencias en bodega.</div>
        </div>
        <a href="{{ url('/compras') }}" class="btn-card-action btn-secondary">
            <i class="fa-solid fa-cart-plus"></i> Registrar Compra
        </a>
    </div>

    <!-- CARD 6: KARDEX -->
    <div class="action-card">
        <div>
            <div class="action-card-header">
                <div class="icon-box icon-sky"><i class="fa-solid fa-arrow-right-arrow-left"></i></div>
                <div>
                    <div class="action-card-title">Kardex de Movimientos</div>
                    <span class="badge badge-info">Auditoría</span>
                </div>
            </div>
            <div class="action-card-desc">Trazabilidad detallada de entradas, salidas y ajustes de inventario por producto y bodega.</div>
        </div>
        <a href="{{ url('/kardex') }}" class="btn-card-action btn-secondary">
            <i class="fa-solid fa-list-check"></i> Ver Kardex
        </a>
    </div>
</div>

<!-- RECENT SALES TABLE -->
<div class="data-card">
    <div class="data-card-header">
        <h2><i class="fa-solid fa-clock-rotate-left text-primary"></i> Últimas Facturas Emitidas Hoy</h2>
        <a href="{{ url('/facturacion') }}" class="btn-card-action btn-secondary btn-sm">
            Ver Todas las Facturas
        </a>
    </div>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Nº Comprobante</th>
                    <th>Fecha / Hora</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Método Pago</th>
                    <th>Estado SRI</th>
                    <th style="text-align: center;">Acción</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ultimasFacturas as $fac)
                <tr>
                    <td><strong style="font-family: monospace;">{{ $fac->numero_comprobante }}</strong></td>
                    <td>{{ $fac->created_at ? $fac->created_at->format('d/m/Y H:i') : $fac->fecha_emision }}</td>
                    <td>{{ $fac->cliente->razon_social ?? 'CONSUMIDOR FINAL' }}</td>
                    <td style="color: var(--success); font-weight: 700;">${{ number_format($fac->importe_total, 2) }}</td>
                    <td>{{ $fac->pagos->first()->metodoPago->nombre ?? 'EFECTIVO' }}</td>
                    <td><span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> {{ $fac->estado_sri }}</span></td>
                    <td style="text-align: center;">
                        <button class="btn-card-action btn-secondary btn-sm" onclick="alert('Imprimiendo comprobante {{ $fac->numero_comprobante }}...')">
                            <i class="fa-solid fa-print"></i> Imprimir
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                        No hay facturas registradas hoy.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
