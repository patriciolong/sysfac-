@extends('layouts.app')

@section('title', 'Compras y Registro de Entradas')

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
        <strong><i class="fa-solid fa-circle-exclamation"></i> Error al registrar compra:</strong>
        <ul style="margin: 0.35rem 0 0 1.25rem;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- COMPRAS HEADER -->
<div class="data-card" style="padding: 0.95rem 1.35rem; margin-bottom: 1.15rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
        <div>
            <h1 style="margin-bottom: 0.2rem; font-size: 1.35rem;"><i class="fa-solid fa-cart-shopping text-primary"></i> Registro de Compras & Entradas a Bodega</h1>
            <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Ingrese facturas de proveedores para aumentar existencias automáticamente y recalcular el costo promedio en Kardex.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="{{ route('compras.notas-credito.index') }}" class="btn-card-action btn-secondary btn-sm" title="Ver y Registrar Notas de Crédito a Proveedores">
                <i class="fa-solid fa-file-circle-minus text-danger"></i> Notas de Crédito
            </a>
            <a href="{{ route('compras.export', request()->query()) }}" class="btn-card-action btn-secondary btn-sm" title="Descargar Historial de Compras en CSV">
                <i class="fa-solid fa-file-csv text-success"></i> Exportar CSV
            </a>
            @if(auth()->user()->hasPermission('Compras', 'master'))
                <button class="btn-card-action btn-success btn-sm" onclick="abrirModalNuevaCompra()">
                    <i class="fa-solid fa-cart-plus"></i> Registrar Factura de Compra
                </button>
            @else
                <button class="btn-card-action btn-secondary btn-sm" style="opacity: 0.6;" onclick="alert('No tiene permisos para registrar compras.')">
                    <i class="fa-solid fa-cart-plus"></i> Registrar Compra 🔒
                </button>
            @endif
        </div>
    </div>
</div>

<!-- COMPRAS KPI SUMMARY -->
<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.15rem;">
    <div class="metric-card">
        <div>
            <div class="metric-label">Compras del Mes</div>
            <div class="metric-val">{{ $kpis['total_compras_mes'] }} Facturas</div>
        </div>
        <div class="icon-box icon-blue"><i class="fa-solid fa-file-invoice"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Total Invertido (Mes)</div>
            <div class="metric-val" style="color: var(--primary);">${{ number_format($kpis['monto_compras_mes'], 2) }}</div>
        </div>
        <div class="icon-box icon-green"><i class="fa-solid fa-money-bill-trend-up"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Proveedores Registrados</div>
            <div class="metric-val">{{ $kpis['total_proveedores'] }} Activos</div>
        </div>
        <div class="icon-box icon-purple"><i class="fa-solid fa-truck-field"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Unidades Ingresadas (Mes)</div>
            <div class="metric-val" style="color: var(--success);">{{ number_format($kpis['articulos_ingresados_mes'], 0) }} Uds</div>
        </div>
        <div class="icon-box icon-amber"><i class="fa-solid fa-boxes-stacked"></i></div>
    </div>
</div>

<!-- FILTERS CARD -->
<div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1.15rem;">
    <form method="GET" action="{{ url('/compras') }}" id="filterComprasForm">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; align-items: end;">
            <div>
                <label class="form-label" style="font-size: 0.75rem; font-weight: 600;">Buscar Factura o Proveedor</label>
                <div style="position: relative;">
                    <input type="text" name="search" class="form-control" placeholder="Nº Factura o Razón social..." value="{{ request('search') }}" style="height: 36px; padding-left: 2rem;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 0.85rem;"></i>
                </div>
            </div>

            <div>
                <label class="form-label" style="font-size: 0.75rem; font-weight: 600;">Filtrar por Proveedor</label>
                <select name="proveedor_id" class="form-control" style="height: 36px;" onchange="document.getElementById('filterComprasForm').submit()">
                    <option value="todos">-- Todos los Proveedores --</option>
                    @foreach($proveedores as $prov)
                        <option value="{{ $prov->id }}" {{ request('proveedor_id') == $prov->id ? 'selected' : '' }}>
                            {{ $prov->razon_social }} ({{ $prov->identificacion }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" style="font-size: 0.75rem; font-weight: 600;">Filtrar por Bodega</label>
                <select name="bodega_id" class="form-control" style="height: 36px;" onchange="document.getElementById('filterComprasForm').submit()">
                    <option value="todas">-- Todas las Bodegas --</option>
                    @foreach($bodegas as $b)
                        <option value="{{ $b->id }}" {{ request('bodega_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->nombre }}
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
                @if(request()->hasAny(['search', 'proveedor_id', 'bodega_id', 'fecha_desde', 'fecha_hasta']))
                    <a href="{{ url('/compras') }}" class="btn-card-action btn-secondary" style="height: 36px;" title="Limpiar Filtros">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>
</div>

<!-- COMPRAS TABLE VIEW -->
<div class="data-card">
    <div class="data-card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
        <h2><i class="fa-solid fa-truck-ramp-box text-primary"></i> Historial de Compras y Entradas a Bodega ({{ count($compras) }} facturas)</h2>
    </div>

    <div class="table-responsive">
        <table class="custom-table" id="tablaCompras">
            <thead>
                <tr>
                    <th style="width: 100px;">Fecha Emisión</th>
                    <th>Nº Factura Proveedor</th>
                    <th>Proveedor</th>
                    <th>Bodega Destino</th>
                    <th style="text-align: center;">Ítems</th>
                    <th style="text-align: right;">Subtotal</th>
                    <th style="text-align: right;">IVA</th>
                    <th style="text-align: right;">Total Compra</th>
                    <th>Tipo / Estado</th>
                    <th style="text-align: center; width: 130px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($compras as $compra)
                <tr style="{{ str_contains($compra['observaciones'] ?? '', '[ANULADA') ? 'opacity: 0.6; background-color: #fef2f2;' : '' }}">
                    <td><strong style="font-size: 0.85rem;">{{ $compra['fecha'] }}</strong></td>
                    <td>
                        <strong style="font-family: monospace; color: var(--primary); font-size: 0.9rem;">{{ $compra['referencia_factura'] }}</strong>
                        @if($compra['movimiento_id'])
                            <div style="font-size: 0.72rem; color: var(--text-muted);"><i class="fa-solid fa-link"></i> Kardex #{{ $compra['movimiento_id'] }}</div>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $compra['proveedor'] }}</strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">RUC: {{ $compra['proveedor_ruc'] }}</div>
                    </td>
                    <td><span class="badge badge-info"><i class="fa-solid fa-warehouse"></i> {{ $compra['bodega'] }}</span></td>
                    <td style="text-align: center;">
                        <span class="badge badge-secondary" style="font-size: 0.75rem;">
                            {{ $compra['items_count'] }} ref. ({{ number_format($compra['articulos_cantidad'], 0) }} uds)
                        </span>
                    </td>
                    <td style="text-align: right; color: var(--text-muted); font-size: 0.85rem;">${{ number_format($compra['subtotal'], 2) }}</td>
                    <td style="text-align: right; color: var(--text-muted); font-size: 0.85rem;">${{ number_format($compra['iva'], 2) }}</td>
                    <td style="text-align: right; color: var(--success); font-weight: 700; font-size: 0.95rem;">
                        ${{ number_format($compra['total_compra'], 2) }}
                    </td>
                    <td>
                        @if(str_contains($compra['observaciones'] ?? '', '[ANULADA'))
                            <span class="badge badge-danger"><i class="fa-solid fa-ban"></i> ANULADA</span>
                        @else
                            <span class="badge badge-success"><i class="fa-solid fa-arrow-down"></i> INGRESO BODEGA</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <div style="display: inline-flex; gap: 0.25rem; align-items: center;">
                            <!-- Ver Detalle Modal -->
                            <button type="button" class="btn-card-action btn-secondary btn-sm" onclick="verDetalleCompra({{ $compra['id'] }})" title="Ver detalle completo de la factura" style="padding: 4px 7px;">
                                <i class="fa-solid fa-eye text-primary"></i>
                            </button>

                            <!-- Enlace Kardex -->
                            <a href="{{ url('/kardex?search='.$compra['referencia_factura']) }}" class="btn-card-action btn-primary btn-sm" title="Ver movimientos en Kardex" style="padding: 4px 7px;">
                                <i class="fa-solid fa-chart-line"></i>
                            </a>

                            <!-- Emitir Nota de Crédito -->
                            @if(!str_contains($compra['observaciones'] ?? '', '[ANULADA'))
                                <a href="{{ route('compras.notas-credito.index', ['compra_id' => $compra['id']]) }}" class="btn-card-action btn-secondary btn-sm" title="Emitir Nota de Crédito / Devolución a Proveedor" style="padding: 4px 7px;">
                                    <i class="fa-solid fa-file-circle-minus text-danger"></i>
                                </a>
                            @endif

                            <!-- Anular Compra -->
                            @if(auth()->user()->hasPermission('Compras', 'master') && !str_contains($compra['observaciones'] ?? '', '[ANULADA'))
                                <button type="button" class="btn-card-action btn-secondary btn-sm" onclick="anularCompraPrompt({{ $compra['id'] }}, '{{ $compra['referencia_factura'] }}')" title="Anular factura y revertir stock" style="padding: 4px 7px;">
                                    <i class="fa-solid fa-trash-can text-danger"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                        <i class="fa-solid fa-cart-arrow-down" style="font-size: 2.5rem; color: var(--border-color); margin-bottom: 0.75rem;"></i>
                        <p style="margin: 0; font-weight: 600;">No se encontraron facturas de compra registradas.</p>
                        <small>Haga clic en "Registrar Factura de Compra" para ingresar mercadería.</small>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: NUEVA FACTURA DE COMPRA (MULTI-ROW) -->
<!-- ========================================== -->
<div class="modal-backdrop" id="modalNuevaCompra">
    <div class="modal-content" style="max-width: 860px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-cart-plus text-primary"></i> Registrar Factura de Compra a Proveedor</h2>
            <button class="btn-close" onclick="closeModal('modalNuevaCompra')">&times;</button>
        </div>
        <form action="{{ route('compras.store') }}" method="POST" id="formNuevaCompra" onsubmit="return validarEnvioCompra(event)">
            @csrf

            <!-- Encabezado Proveedor y Factura -->
            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 0.75rem; margin-bottom: 0.5rem;">
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                        <label class="form-label" style="margin: 0;">Proveedor <span class="text-danger">*</span></label>
                        <a href="javascript:void(0)" onclick="openModal('modalProveedorRapido')" style="font-size: 0.75rem; color: var(--primary); font-weight: 600;">+ Nuevo Proveedor</a>
                    </div>
                    <select name="proveedor_id" id="compra_proveedor_id" class="form-control" required>
                        <option value="">-- Seleccionar Proveedor --</option>
                        @foreach($proveedores as $prov)
                            <option value="{{ $prov->id }}">{{ $prov->razon_social }} ({{ $prov->identificacion }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nº Factura de Proveedor <span class="text-danger">*</span></label>
                    <input type="text" name="numero_factura" class="form-control" placeholder="Ej: 001-002-00012486" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Bodega de Destino <span class="text-danger">*</span></label>
                    <select name="bodega_id" class="form-control" required>
                        @foreach($bodegas as $b)
                            <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha de Emisión <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_emision" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Observaciones</label>
                    <input type="text" name="observaciones" class="form-control" placeholder="Ej: Reposición quincenal de stock">
                </div>
            </div>

            <!-- Tabla de Ítems Multi-Línea -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin: 1rem 0 0.5rem 0;">
                <h3 style="font-size: 0.95rem; margin: 0;"><i class="fa-solid fa-list-check text-primary"></i> Detalle de Ítems Comprados</h3>
                <button type="button" class="btn-card-action btn-secondary btn-sm" onclick="agregarFilaCompra()">
                    <i class="fa-solid fa-plus text-success"></i> Agregar Línea
                </button>
            </div>

            <div class="table-responsive" style="max-height: 260px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-sm); margin-bottom: 0.75rem;">
                <table class="custom-table" style="margin: 0;" id="tablaItemsCompra">
                    <thead>
                        <tr style="background: var(--bg-muted);">
                            <th style="min-width: 260px;">Producto</th>
                            <th style="width: 100px; text-align: right;">Cantidad</th>
                            <th style="width: 120px; text-align: right;">Costo Unit. ($)</th>
                            <th style="width: 90px; text-align: center;">IVA</th>
                            <th style="width: 110px; text-align: right;">Subtotal ($)</th>
                            <th style="width: 45px; text-align: center;"></th>
                        </tr>
                    </thead>
                    <tbody id="tbodyItemsCompra">
                        <!-- Dynamic item rows will be injected here -->
                    </tbody>
                </table>
            </div>

            <!-- Resumen Financiero -->
            <div style="display: flex; justify-content: flex-end; margin-top: 0.75rem;">
                <div style="width: 300px; background: var(--bg-muted); padding: 0.75rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.25rem;">
                        <span>Subtotal Sin Impuestos:</span>
                        <strong id="resumen_subtotal">$0.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.35rem; color: var(--text-muted);">
                        <span>IVA (15%):</span>
                        <strong id="resumen_iva">$0.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: 800; border-top: 1px solid var(--border-color); padding-top: 0.35rem; color: var(--success);">
                        <span>Total Factura:</span>
                        <strong id="resumen_total">$0.00</strong>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 0.65rem; margin-top: 1.25rem;">
                <button type="submit" class="btn-card-action btn-success" style="flex: 1;" id="btnGuardarCompra">
                    <i class="fa-solid fa-truck-ramp-box"></i> Guardar Compra e Incrementar Stock en Kardex
                </button>
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalNuevaCompra')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: DETALLE DE COMPRA (INVOICE VIEW)    -->
<!-- ========================================== -->
<div class="modal-backdrop" id="modalDetalleCompra">
    <div class="modal-content" style="max-width: 760px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-file-invoice text-primary"></i> Detalle de Factura de Compra</h2>
            <button class="btn-close" onclick="closeModal('modalDetalleCompra')">&times;</button>
        </div>
        <div id="detalleCompraContenido" style="padding: 0.5rem 0;">
            <div style="text-align: center; padding: 2rem;"><i class="fa-solid fa-circle-notch fa-spin text-primary" style="font-size: 2rem;"></i></div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: PROVEEDOR RÁPIDO (AJAX)             -->
<!-- ========================================== -->
<div class="modal-backdrop" id="modalProveedorRapido">
    <div class="modal-content" style="max-width: 520px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-truck-field text-primary"></i> Registrar Proveedor Rápido</h2>
            <button class="btn-close" onclick="closeModal('modalProveedorRapido')">&times;</button>
        </div>
        <form id="formProveedorRapido" onsubmit="guardarProveedorRapidoAjax(event)">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Tipo Doc. <span class="text-danger">*</span></label>
                    <select name="tipo_identificacion" class="form-control" required>
                        <option value="04">RUC (13 dígitos)</option>
                        <option value="05">Cédula (10 dígitos)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nº Identificación <span class="text-danger">*</span></label>
                    <input type="text" name="identificacion" class="form-control" placeholder="Ej: 1791234567001" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Razón Social / Nombre Proveedor <span class="text-danger">*</span></label>
                <input type="text" name="razon_social" class="form-control" placeholder="Ej: Laboratorios BioMed S.A." required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                    <input type="email" name="correo" class="form-control" placeholder="ventas@proveedor.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" placeholder="022987654">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Dirección</label>
                <input type="text" name="direccion" class="form-control" placeholder="Av. Principal N45 y Secundaria">
            </div>

            <div style="display: flex; gap: 0.65rem; margin-top: 1.25rem;">
                <button type="submit" class="btn-card-action btn-primary" style="flex: 1;" id="btnGuardarProveedorRapido">
                    <i class="fa-solid fa-check"></i> Guardar y Seleccionar
                </button>
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalProveedorRapido')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Global list of catalog products for dynamic rows
    const catalogoProductos = @json($productos);
    let rowCount = 0;

    function abrirModalNuevaCompra() {
        const tbody = document.getElementById('tbodyItemsCompra');
        if (tbody.children.length === 0) {
            agregarFilaCompra();
        }
        openModal('modalNuevaCompra');
    }

    function agregarFilaCompra() {
        const tbody = document.getElementById('tbodyItemsCompra');
        rowCount++;

        let optionsHtml = '<option value="">-- Seleccione un producto --</option>';
        catalogoProductos.forEach(p => {
            optionsHtml += `<option value="${p.id}" data-costo="${p.costo_promedio}" data-iva="${p.tarifa_iva}">
                ${p.nombre} (${p.codigo}) - Costo Actual: $${p.costo_promedio.toFixed(2)}
            </option>`;
        });

        const tr = document.createElement('tr');
        tr.id = `fila_compra_${rowCount}`;
        tr.innerHTML = `
            <td>
                <select name="detalles[${rowCount}][producto_id]" class="form-control" style="font-size: 0.825rem;" required onchange="onProductoSeleccionado(this, ${rowCount})">
                    ${optionsHtml}
                </select>
            </td>
            <td>
                <input type="number" step="0.0001" name="detalles[${rowCount}][cantidad]" id="cant_${rowCount}" class="form-control" value="1" min="0.0001" required style="text-align: right;" onkeyup="recalcularFilaCompra(${rowCount})">
            </td>
            <td>
                <input type="number" step="0.0001" name="detalles[${rowCount}][costo_unitario]" id="costo_${rowCount}" class="form-control" value="0.00" min="0" required style="text-align: right;" onkeyup="recalcularFilaCompra(${rowCount})">
            </td>
            <td style="text-align: center;">
                <span class="badge badge-secondary" id="badge_iva_${rowCount}">0%</span>
            </td>
            <td style="text-align: right; font-weight: 700;">
                <span id="subtotal_fila_${rowCount}">$0.00</span>
            </td>
            <td style="text-align: center;">
                <button type="button" class="btn-card-action btn-secondary btn-sm" onclick="eliminarFilaCompra(${rowCount})" style="color: var(--danger); padding: 4px 6px;">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
        recalcularTotalesCompra();
    }

    function onProductoSeleccionado(selectEl, idx) {
        const option = selectEl.options[selectEl.selectedIndex];
        if (option && option.value) {
            const costo = parseFloat(option.getAttribute('data-costo')) || 0;
            const tarifaIva = parseFloat(option.getAttribute('data-iva')) || 0;

            const costoInput = document.getElementById(`costo_${idx}`);
            if (costoInput && (parseFloat(costoInput.value) === 0 || !costoInput.value)) {
                costoInput.value = costo.toFixed(2);
            }

            const badgeIva = document.getElementById(`badge_iva_${idx}`);
            if (badgeIva) {
                badgeIva.innerText = `${tarifaIva}%`;
                badgeIva.className = tarifaIva > 0 ? 'badge badge-primary' : 'badge badge-secondary';
            }
        }
        recalcularFilaCompra(idx);
    }

    function recalcularFilaCompra(idx) {
        const cant = parseFloat(document.getElementById(`cant_${idx}`)?.value) || 0;
        const costo = parseFloat(document.getElementById(`costo_${idx}`)?.value) || 0;
        const lineSubtotal = cant * costo;

        const subtotalEl = document.getElementById(`subtotal_fila_${idx}`);
        if (subtotalEl) {
            subtotalEl.innerText = `$${lineSubtotal.toFixed(2)}`;
        }

        recalcularTotalesCompra();
    }

    function eliminarFilaCompra(idx) {
        const row = document.getElementById(`fila_compra_${idx}`);
        if (row) {
            row.remove();
            recalcularTotalesCompra();
        }
    }

    function recalcularTotalesCompra() {
        let subtotalSinImp = 0;
        let totalIva = 0;

        const rows = document.querySelectorAll('#tbodyItemsCompra tr');
        rows.forEach(tr => {
            const select = tr.querySelector('select');
            const cantInput = tr.querySelector('input[id^="cant_"]');
            const costoInput = tr.querySelector('input[id^="costo_"]');

            if (select && cantInput && costoInput && select.value) {
                const cant = parseFloat(cantInput.value) || 0;
                const costo = parseFloat(costoInput.value) || 0;
                const lineTotal = cant * costo;
                subtotalSinImp += lineTotal;

                const option = select.options[select.selectedIndex];
                const tarifaIva = parseFloat(option.getAttribute('data-iva')) || 0;
                if (tarifaIva > 0) {
                    totalIva += lineTotal * (tarifaIva / 100);
                }
            }
        });

        const total = subtotalSinImp + totalIva;

        document.getElementById('resumen_subtotal').innerText = `$${subtotalSinImp.toFixed(2)}`;
        document.getElementById('resumen_iva').innerText = `$${totalIva.toFixed(2)}`;
        document.getElementById('resumen_total').innerText = `$${total.toFixed(2)}`;
    }

    function validarEnvioCompra(event) {
        const rows = document.querySelectorAll('#tbodyItemsCompra tr');
        if (rows.length === 0) {
            alert('Debe agregar al menos un producto a la factura de compra.');
            event.preventDefault();
            return false;
        }
        return true;
    }

    function verDetalleCompra(id) {
        const container = document.getElementById('detalleCompraContenido');
        container.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fa-solid fa-circle-notch fa-spin text-primary" style="font-size: 2rem;"></i></div>';
        openModal('modalDetalleCompra');

        fetch(`{{ url('/compras') }}/${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const c = data.compra;
                    let itemsHtml = '';
                    c.detalles.forEach(d => {
                        itemsHtml += `
                            <tr>
                                <td><strong style="font-family: monospace;">${d.producto_codigo}</strong></td>
                                <td><strong>${d.producto_nombre}</strong></td>
                                <td style="text-align: right; font-weight: 700;">${d.cantidad}</td>
                                <td style="text-align: right;">$${d.costo_unitario.toFixed(2)}</td>
                                <td style="text-align: right; font-weight: 700; color: var(--success);">$${d.costo_total.toFixed(2)}</td>
                            </tr>
                        `;
                    });

                    container.innerHTML = `
                        <div style="background: var(--bg-muted); padding: 0.85rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Proveedor:</div>
                                <strong style="font-size: 1rem; color: var(--text-main);">${c.proveedor.razon_social}</strong>
                                <div style="font-family: monospace; font-size: 0.8rem; color: var(--text-muted);">RUC/C.I.: ${c.proveedor.identificacion}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><i class="fa-solid fa-location-dot"></i> ${c.proveedor.direccion}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Factura Proveedor:</div>
                                <strong style="font-family: monospace; font-size: 1.15rem; color: var(--primary);">${c.numero_factura}</strong>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">Fecha: <strong>${c.fecha_emision}</strong></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><i class="fa-solid fa-warehouse"></i> ${c.bodega} | Registrado por: ${c.usuario}</div>
                            </div>
                        </div>

                        <h3 style="font-size: 0.95rem; margin-bottom: 0.45rem;"><i class="fa-solid fa-boxes-stacked text-primary"></i> Productos Ingresados a Inventario</h3>
                        <table class="custom-table" style="margin-bottom: 1rem;">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Descripción</th>
                                    <th style="text-align: right;">Cantidad</th>
                                    <th style="text-align: right;">Costo Unit.</th>
                                    <th style="text-align: right;">Costo Total</th>
                                </tr>
                            </thead>
                            <tbody>${itemsHtml}</tbody>
                        </table>

                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                            <div>
                                ${c.movimiento_id ? `<a href="{{ url('/kardex?search=') }}${c.numero_factura}" class="btn-card-action btn-primary btn-sm"><i class="fa-solid fa-chart-line"></i> Ver en Kardex (Mov #${c.movimiento_id})</a>` : ''}
                            </div>
                            <div style="width: 250px; text-align: right;">
                                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.2rem;">Subtotal: <strong>$${c.subtotal.toFixed(2)}</strong></div>
                                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.35rem;">IVA: <strong>$${c.iva.toFixed(2)}</strong></div>
                                <div style="font-size: 1.15rem; font-weight: 800; color: var(--success);">Total Compra: $${c.total.toFixed(2)}</div>
                            </div>
                        </div>
                    `;
                }
            })
            .catch(err => {
                container.innerHTML = '<div style="color: var(--danger); text-align: center; padding: 2rem;">Error al cargar detalle de compra.</div>';
            });
    }

    function anularCompraPrompt(id, facturaNum) {
        const motivo = prompt(`¿Está seguro de anular la Factura de Compra #${facturaNum}? Esta acción revertirá el stock de los productos y registrará el movimiento de anulación en Kardex.\n\nIngrese motivo de anulación:`, 'Anulación por error de digitación');
        if (motivo !== null) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `{{ url('/compras') }}/${id}/anular`;
            form.innerHTML = `
                @csrf
                <input type="hidden" name="motivo" value="${motivo}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function guardarProveedorRapidoAjax(event) {
        event.preventDefault();
        const form = document.getElementById('formProveedorRapido');
        const btn = document.getElementById('btnGuardarProveedorRapido');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Guardando...';

        const formData = new FormData(form);

        fetch('{{ route("compras.proveedorRapido") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Guardar y Seleccionar';

            if (data.success) {
                const selectProv = document.getElementById('compra_proveedor_id');
                const option = new Option(data.proveedor.display, data.proveedor.id, true, true);
                selectProv.add(option);

                form.reset();
                closeModal('modalProveedorRapido');
                alert(`Proveedor "${data.proveedor.razon_social}" registrado y seleccionado.`);
            } else {
                alert('Error al guardar proveedor: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Guardar y Seleccionar';
            alert('Error de conexión al guardar proveedor.');
        });
    }
</script>
@endpush

