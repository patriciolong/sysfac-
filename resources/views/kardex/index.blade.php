@extends('layouts.app')

@section('title', 'Kardex de Movimientos de Inventario')

@section('content')

@if(session('success'))
    <div style="background: var(--success-light); border-left: 4px solid var(--success); padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.15rem; color: #065f46; display: flex; align-items: center; justify-content: space-between;">
        <div><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
        <button onclick="this.parentElement.remove()" style="background:none; border:none; color:#065f46; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
    </div>
@endif

@if(session('warning'))
    <div style="background: var(--warning-light); border-left: 4px solid var(--warning); padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.15rem; color: #92400e; display: flex; align-items: center; justify-content: space-between;">
        <div><i class="fa-solid fa-triangle-exclamation"></i> {{ session('warning') }}</div>
        <button onclick="this.parentElement.remove()" style="background:none; border:none; color:#92400e; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
    </div>
@endif

@if($errors->any())
    <div style="background: var(--danger-light); border-left: 4px solid var(--danger); padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.15rem; color: #991b1b;">
        <strong><i class="fa-solid fa-circle-exclamation"></i> Error al registrar ajuste:</strong>
        <ul style="margin: 0.35rem 0 0 1.25rem;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- KARDEX HEADER -->
<div class="data-card" style="padding: 0.95rem 1.35rem; margin-bottom: 1.15rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
        <div>
            <h1 style="margin-bottom: 0.2rem; font-size: 1.35rem;"><i class="fa-solid fa-chart-line text-primary"></i> Kardex de Movimientos de Inventario</h1>
            <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Auditoría cronológica y trazabilidad de entradas (compras, ajustes) y salidas (ventas, mermas) con saldos valorizados.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="{{ route('kardex.export', request()->query()) }}" class="btn-card-action btn-secondary btn-sm" title="Descargar movimientos en CSV">
                <i class="fa-solid fa-file-csv text-success"></i> Exportar CSV
            </a>
            @if(auth()->user()->hasPermission('Kardex', 'master'))
                <button class="btn-card-action btn-warning btn-sm" onclick="openModal('modalAjusteKardex')">
                    <i class="fa-solid fa-sliders"></i> Registrar Ajuste de Stock
                </button>
            @else
                <button class="btn-card-action btn-secondary btn-sm" style="opacity: 0.6;" onclick="alert('No tiene permisos para realizar ajustes en Kardex.')">
                    <i class="fa-solid fa-sliders"></i> Ajuste Stock 🔒
                </button>
            @endif
        </div>
    </div>
</div>

<!-- KARDEX KPI SUMMARY -->
<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.15rem;">
    <div class="metric-card">
        <div>
            <div class="metric-label">Total Movimientos</div>
            <div class="metric-val">{{ $kpis['total_movimientos'] }} Registros</div>
        </div>
        <div class="icon-box icon-blue"><i class="fa-solid fa-list-ol"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Entradas Totales</div>
            <div class="metric-val" style="color: var(--success);">+{{ number_format($kpis['total_entradas'], 0) }} Uds</div>
        </div>
        <div class="icon-box icon-green"><i class="fa-solid fa-arrow-trend-up"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Salidas Totales</div>
            <div class="metric-val" style="color: var(--danger);">-{{ number_format($kpis['total_salidas'], 0) }} Uds</div>
        </div>
        <div class="icon-box icon-rose"><i class="fa-solid fa-arrow-trend-down"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Valor Total Transaccionado</div>
            <div class="metric-val" style="color: var(--primary);">${{ number_format($kpis['valor_total_movido'], 2) }}</div>
        </div>
        <div class="icon-box icon-purple"><i class="fa-solid fa-receipt"></i></div>
    </div>
</div>

<!-- ADVANCED FILTERS CARD -->
<div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1.15rem;">
    <form method="GET" action="{{ url('/kardex') }}" id="filterKardexForm">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; align-items: end;">
            <div>
                <label class="form-label" style="font-size: 0.75rem; font-weight: 600;">Filtrar por Producto</label>
                <select name="producto_id" class="form-control" style="height: 36px;" onchange="document.getElementById('filterKardexForm').submit()">
                    <option value="todos">-- Todos los Productos --</option>
                    @foreach($productos as $p)
                        <option value="{{ $p->id }}" {{ request('producto_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->nombre }} ({{ $p->codigo_principal }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" style="font-size: 0.75rem; font-weight: 600;">Filtrar por Bodega</label>
                <select name="bodega_id" class="form-control" style="height: 36px;" onchange="document.getElementById('filterKardexForm').submit()">
                    <option value="todas">-- Todas las Bodegas --</option>
                    @foreach($bodegas as $b)
                        <option value="{{ $b->id }}" {{ request('bodega_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" style="font-size: 0.75rem; font-weight: 600;">Tipo de Movimiento</label>
                <select name="tipo_movimiento_id" class="form-control" style="height: 36px;" onchange="document.getElementById('filterKardexForm').submit()">
                    <option value="todos">-- Todos los Conceptos --</option>
                    @foreach($tiposMovimiento as $tm)
                        <option value="{{ $tm->id }}" {{ request('tipo_movimiento_id') == $tm->id ? 'selected' : '' }}>
                            {{ $tm->nombre }} ({{ $tm->naturaleza }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" style="font-size: 0.75rem; font-weight: 600;">Fecha Desde</label>
                <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}" style="height: 36px;">
            </div>

            <div>
                <label class="form-label" style="font-size: 0.75rem; font-weight: 600;">Fecha Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}" style="height: 36px;">
            </div>

            <div style="display: flex; gap: 0.4rem;">
                <button type="submit" class="btn-card-action btn-primary" style="height: 36px; flex: 1;">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
                @if(request()->hasAny(['producto_id', 'bodega_id', 'tipo_movimiento_id', 'fecha_desde', 'fecha_hasta', 'search']))
                    <a href="{{ url('/kardex') }}" class="btn-card-action btn-secondary" style="height: 36px;" title="Limpiar Filtros">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>
</div>

<!-- ================================================================= -->
<!-- PRODUCT SPECIFIC TIMELINE (When a specific product is filtered)  -->
<!-- ================================================================= -->
@if($selectedProduct)
<div class="data-card" style="margin-bottom: 1.15rem; border: 1.5px solid var(--primary-border); background: #fdfefe;">
    <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span class="badge badge-info">{{ $selectedProduct->categoria->nombre ?? 'General' }}</span>
                <span class="badge {{ $selectedProduct->estado === 'ACTIVO' ? 'badge-success' : 'badge-secondary' }}">{{ $selectedProduct->estado }}</span>
            </div>
            <h2 style="margin: 0.35rem 0 0.15rem 0; font-size: 1.3rem; color: var(--primary);">
                <i class="fa-solid fa-box-open"></i> {{ $selectedProduct->nombre }}
            </h2>
            <div style="font-family: monospace; font-size: 0.85rem; color: var(--text-muted);">
                SKU / Código Principal: <strong>{{ $selectedProduct->codigo_principal }}</strong>
                @if($selectedProduct->codigo_auxiliar) | Auxiliar: {{ $selectedProduct->codigo_auxiliar }} @endif
            </div>
        </div>

        <div style="display: flex; gap: 1rem; align-items: center; background: var(--bg-muted); padding: 0.65rem 1rem; border-radius: var(--radius-sm);">
            <div style="text-align: right;">
                <div style="font-size: 0.72rem; color: var(--text-muted);">Costo Promedio:</div>
                <strong style="font-size: 1rem;">${{ number_format($selectedProduct->costo_promedio ?? 0, 2) }}</strong>
            </div>
            <div style="border-left: 1px solid var(--border-color); height: 30px;"></div>
            <div style="text-align: right;">
                <div style="font-size: 0.72rem; color: var(--text-muted);">Precio Venta:</div>
                <strong style="font-size: 1rem; color: var(--success);">${{ number_format($selectedProduct->precio_unitario, 2) }}</strong>
            </div>
            <div style="border-left: 1px solid var(--border-color); height: 30px;"></div>
            <div style="text-align: right;">
                <div style="font-size: 0.72rem; color: var(--text-muted);">Stock Total Actual:</div>
                <strong style="font-size: 1.25rem; color: {{ $selectedProduct->stock_total <= 0 ? 'var(--danger)' : 'var(--success)' }};">
                    {{ number_format($selectedProduct->stock_total, 0) }} uds
                </strong>
            </div>
        </div>
    </div>

    <!-- Running Chronological Balance Table -->
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr style="background: #f1f5f9;">
                    <th>Fecha & Hora</th>
                    <th>Bodega</th>
                    <th>Concepto</th>
                    <th>Referencia</th>
                    <th style="text-align: right; background: #e2e8f0;">Saldo Ant.</th>
                    <th style="text-align: right; color: var(--success);">Entrada (+)</th>
                    <th style="text-align: right; color: var(--danger);">Salida (-)</th>
                    <th style="text-align: right; background: #e2e8f0; font-weight: 800;">Saldo Final</th>
                    <th style="text-align: right;">Costo Unit.</th>
                    <th style="text-align: right;">Costo Total</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productKardexTrace as $t)
                <tr>
                    <td><small>{{ $t['fecha'] }}</small></td>
                    <td>{{ $t['bodega'] }}</td>
                    <td>
                        @if($t['naturaleza'] === 'INGRESO')
                            <span class="badge badge-success"><i class="fa-solid fa-arrow-down"></i> {{ $t['concepto'] }}</span>
                        @else
                            <span class="badge badge-danger"><i class="fa-solid fa-arrow-up"></i> {{ $t['concepto'] }}</span>
                        @endif
                    </td>
                    <td style="font-family: monospace; font-size: 0.8rem;">
                        <strong>{{ $t['referencia'] }}</strong>
                    </td>
                    <td style="text-align: right; background: #f8fafc; font-weight: 600; color: var(--text-muted);">
                        {{ number_format($t['saldo_anterior'], 0) }}
                    </td>
                    <td style="text-align: right; font-weight: 700; color: var(--success);">
                        {{ $t['cantidad_entrada'] !== null ? '+'.number_format($t['cantidad_entrada'], 0) : '-' }}
                    </td>
                    <td style="text-align: right; font-weight: 700; color: var(--danger);">
                        {{ $t['cantidad_salida'] !== null ? '-'.number_format($t['cantidad_salida'], 0) : '-' }}
                    </td>
                    <td style="text-align: right; background: #f8fafc; font-weight: 800; font-size: 0.95rem; color: {{ $t['saldo_resultante'] <= 0 ? 'var(--danger)' : 'var(--primary)' }};">
                        {{ number_format($t['saldo_resultante'], 0) }}
                    </td>
                    <td style="text-align: right;">${{ number_format($t['costo_unitario'], 2) }}</td>
                    <td style="text-align: right; font-weight: 700;">${{ number_format($t['costo_total'], 2) }}</td>
                    <td style="font-size: 0.8rem; color: var(--text-muted);">{{ $t['observaciones'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        No hay movimientos registrados para este producto en los filtros especificados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- ================================================================= -->
<!-- GENERAL MOVEMENTS TABLE                                           -->
<!-- ================================================================= -->
<div class="data-card">
    <div class="data-card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
        <h2>
            <i class="fa-solid fa-list-check text-primary"></i> 
            Todos los Movimientos de Inventario ({{ count($movimientos) }} registros)
        </h2>
    </div>

    <div class="table-responsive">
        <table class="custom-table" id="tablaKardex">
            <thead>
                <tr>
                    <th style="width: 130px;">Fecha & Hora</th>
                    <th>Bodega</th>
                    <th>Concepto</th>
                    <th>Referencia / Doc</th>
                    <th>Producto</th>
                    <th>Tipo</th>
                    <th style="text-align: right;">Variación</th>
                    <th style="text-align: right;">Costo Unit.</th>
                    <th style="text-align: right;">Costo Total</th>
                    <th>Responsable</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movimientos as $mov)
                <tr>
                    <td><small>{{ $mov['fecha_movimiento'] }}</small></td>
                    <td><span class="badge badge-info"><i class="fa-solid fa-warehouse"></i> {{ $mov['bodega'] }}</span></td>
                    <td><strong>{{ $mov['concepto_movimiento'] }}</strong></td>
                    <td>
                        <strong style="font-family: monospace; font-size: 0.85rem; color: var(--primary);">{{ $mov['referencia'] }}</strong>
                        @if($mov['observaciones'])
                            <div style="font-size: 0.72rem; color: var(--text-muted); max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $mov['observaciones'] }}">
                                {{ $mov['observaciones'] }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <a href="{{ url('/kardex?producto_id='.$mov['producto_id']) }}" style="font-weight: 700; color: var(--text-main);" title="Filtrar trazabilidad de este producto">
                            {{ $mov['producto'] }}
                        </a>
                        <div style="font-family: monospace; font-size: 0.75rem; color: var(--text-muted);">{{ $mov['codigo_producto'] }}</div>
                    </td>
                    <td>
                        @if($mov['tipo'] == 'INGRESO')
                            <span class="badge badge-success"><i class="fa-solid fa-arrow-down"></i> INGRESO</span>
                        @else
                            <span class="badge badge-danger"><i class="fa-solid fa-arrow-up"></i> EGRESO</span>
                        @endif
                    </td>
                    <td style="text-align: right; font-weight: 800; font-size: 0.95rem; color: {{ $mov['variacion_stock'] > 0 ? 'var(--success)' : 'var(--danger)' }};">
                        {{ $mov['variacion_stock'] > 0 ? '+'.number_format($mov['variacion_stock'], 0) : number_format($mov['variacion_stock'], 0) }}
                    </td>
                    <td style="text-align: right; color: var(--text-muted); font-size: 0.85rem;">
                        ${{ number_format($mov['costo_unitario'], 2) }}
                    </td>
                    <td style="text-align: right; font-weight: 700; font-size: 0.9rem;">
                        ${{ number_format($mov['costo_total'], 2) }}
                    </td>
                    <td><small>{{ $mov['usuario'] }}</small></td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                        <i class="fa-solid fa-timeline" style="font-size: 2.5rem; color: var(--border-color); margin-bottom: 0.75rem;"></i>
                        <p style="margin: 0; font-weight: 600;">No se encontraron movimientos de Kardex con los filtros aplicados.</p>
                        <small>Realice una compra, venta o ajuste para visualizar la trazabilidad de inventario.</small>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: REGISTRAR AJUSTE DE STOCK           -->
<!-- ========================================== -->
<div class="modal-backdrop" id="modalAjusteKardex">
    <div class="modal-content" style="max-width: 620px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-sliders text-warning"></i> Registrar Ajuste Manual de Inventario</h2>
            <button class="btn-close" onclick="closeModal('modalAjusteKardex')">&times;</button>
        </div>
        <form action="{{ route('kardex.ajuste') }}" method="POST">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Bodega a Ajustar <span class="text-danger">*</span></label>
                    <select name="bodega_id" class="form-control" required>
                        @foreach($bodegas as $b)
                            <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo de Movimiento <span class="text-danger">*</span></label>
                    <select name="tipo_movimiento_id" id="ajuste_tipo" class="form-control" required>
                        <option value="5">🟢 AJUSTE INGRESO (Sobrante Físico / Donación)</option>
                        <option value="6">🔴 AJUSTE EGRESO (Merma / Daño / Caducidad / Pérdida)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Producto a Ajustar <span class="text-danger">*</span></label>
                <select name="producto_id" id="ajuste_producto_id" class="form-control" required onchange="onAjusteProductoChange(this)">
                    <option value="">-- Seleccione un Producto --</option>
                    @foreach($productos as $p)
                    <option value="{{ $p->id }}" data-costo="{{ $p->costo_promedio }}" data-stock="{{ $p->stock_total }}">
                        {{ $p->nombre }} (SKU: {{ $p->codigo_principal }} | Stock Actual: {{ $p->stock_total }} uds)
                    </option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Cantidad a Ajustar <span class="text-danger">*</span></label>
                    <input type="number" step="0.0001" name="cantidad" class="form-control" placeholder="Ej: 5" required min="0.0001">
                </div>
                <div class="form-group">
                    <label class="form-label">Costo Unitario ($)</label>
                    <input type="number" step="0.0001" name="costo_unitario" id="ajuste_costo_unitario" class="form-control" placeholder="0.00">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Motivo / Justificación del Ajuste <span class="text-danger">*</span></label>
                <textarea name="observaciones" class="form-control" rows="2" placeholder="Ej: Conteo físico mensual, rotura accidental de envase o regularización de inventario" required></textarea>
            </div>

            <div style="display: flex; gap: 0.65rem; margin-top: 1.25rem;">
                <button type="submit" class="btn-card-action btn-warning" style="flex: 1;">
                    <i class="fa-solid fa-check"></i> Aplicar Ajuste en Kardex
                </button>
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalAjusteKardex')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function onAjusteProductoChange(selectEl) {
        const option = selectEl.options[selectEl.selectedIndex];
        if (option && option.value) {
            const costo = parseFloat(option.getAttribute('data-costo')) || 0;
            document.getElementById('ajuste_costo_unitario').value = costo.toFixed(2);
        }
    }
</script>
@endpush

