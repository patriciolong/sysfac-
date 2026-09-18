@extends('layouts.app')

@section('title', 'Notas de Crédito de Compras')

@section('content')

@if(session('success'))
    <div style="background: var(--success-light); border-left: 4px solid var(--success); padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.15rem; color: #065f46; display: flex; align-items: center; justify-content: space-between;">
        <div><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
        <button onclick="this.parentElement.remove()" style="background:none; border:none; color:#065f46; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
    </div>
@endif

@if(session('error'))
    <div style="background: var(--danger-light); border-left: 4px solid var(--danger); padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.15rem; color: #991b1b; display: flex; align-items: center; justify-content: space-between;">
        <div><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
        <button onclick="this.parentElement.remove()" style="background:none; border:none; color:#991b1b; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
    </div>
@endif

@if($errors->any())
    <div style="background: var(--danger-light); border-left: 4px solid var(--danger); padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.15rem; color: #991b1b;">
        <strong><i class="fa-solid fa-circle-exclamation"></i> Por favor verifique los siguientes errores:</strong>
        <ul style="margin: 0.35rem 0 0 1.25rem;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- NOTAS DE CRÉDITO HEADER -->
<div class="data-card" style="padding: 0.95rem 1.35rem; margin-bottom: 1.15rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                <a href="{{ route('compras.index') }}" class="btn-card-action btn-secondary btn-sm" style="padding: 3px 8px; font-size: 0.78rem;">
                    <i class="fa-solid fa-arrow-left"></i> Facturas de Compra
                </a>
                <span class="badge badge-danger" style="font-size: 0.75rem;">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Devoluciones a Proveedores
                </span>
            </div>
            <h1 style="margin-bottom: 0.2rem; font-size: 1.35rem;"><i class="fa-solid fa-file-circle-minus text-danger"></i> Notas de Crédito de Compras</h1>
            <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Registre devoluciones de mercadería a proveedores o descuentos comerciales con reverso automático de stock en Kardex.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="{{ route('compras.notas-credito.export', request()->query()) }}" class="btn-card-action btn-secondary btn-sm" title="Descargar Historial de Notas de Crédito en CSV">
                <i class="fa-solid fa-file-csv text-success"></i> Exportar CSV
            </a>
            @if(auth()->user()->hasPermission('Compras', 'master'))
                <button class="btn-card-action btn-danger btn-sm" onclick="abrirModalNuevaNC()">
                    <i class="fa-solid fa-plus-circle"></i> Nueva Nota de Crédito
                </button>
            @else
                <button class="btn-card-action btn-secondary btn-sm" style="opacity: 0.6;" onclick="alert('No tiene permisos para emitir notas de crédito.')">
                    <i class="fa-solid fa-plus-circle"></i> Nueva Nota de Crédito 🔒
                </button>
            @endif
        </div>
    </div>
</div>

<!-- KPI SUMMARY CARDS -->
<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.15rem;">
    <div class="metric-card">
        <div>
            <div class="metric-label">NCs Emitidas (Mes)</div>
            <div class="metric-val" style="color: var(--danger);">{{ number_format($kpis['total_ncs_mes']) }} <small style="font-size: 0.75rem; font-weight: normal; color: var(--text-muted);">docs</small></div>
        </div>
        <div class="icon-box icon-rose"><i class="fa-solid fa-file-circle-minus"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Monto Acreditado (Mes)</div>
            <div class="metric-val" style="color: var(--warning);">${{ number_format($kpis['monto_acreditado_mes'], 2) }}</div>
        </div>
        <div class="icon-box icon-amber"><i class="fa-solid fa-hand-holding-dollar"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Unidades Devueltas</div>
            <div class="metric-val" style="color: var(--accent);">{{ number_format($kpis['unidades_devueltas_mes'], 1) }} <small style="font-size: 0.75rem; font-weight: normal; color: var(--text-muted);">uds</small></div>
        </div>
        <div class="icon-box icon-sky"><i class="fa-solid fa-boxes-packing"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Facturas Modificadas</div>
            <div class="metric-val" style="color: var(--primary);">{{ number_format($kpis['facturas_afectadas_mes']) }} <small style="font-size: 0.75rem; font-weight: normal; color: var(--text-muted);">compras</small></div>
        </div>
        <div class="icon-box icon-blue"><i class="fa-solid fa-receipt"></i></div>
    </div>
</div>

<!-- FILTER TOOLBAR -->
<div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1.15rem;">
    <form method="GET" action="{{ route('compras.notas-credito.index') }}" id="filterNCForm">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 0.75rem; align-items: end;">
            <div>
                <label class="form-label" style="font-size: 0.75rem; font-weight: 600;">Buscar</label>
                <input type="text" name="buscar" class="form-control" style="height: 36px;" placeholder="No. NC, Factura, Proveedor..." value="{{ request('buscar') }}">
            </div>

            <div>
                <label class="form-label" style="font-size: 0.75rem; font-weight: 600;">Proveedor</label>
                <select name="proveedor_id" class="form-control" style="height: 36px;" onchange="document.getElementById('filterNCForm').submit()">
                    <option value="todos">-- Todos los Proveedores --</option>
                    @foreach($proveedores as $prov)
                        <option value="{{ $prov->id }}" {{ request('proveedor_id') == $prov->id ? 'selected' : '' }}>
                            {{ $prov->razon_social }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" style="font-size: 0.75rem; font-weight: 600;">Bodega</label>
                <select name="bodega_id" class="form-control" style="height: 36px;" onchange="document.getElementById('filterNCForm').submit()">
                    <option value="todas">-- Todas las Bodegas --</option>
                    @foreach($bodegas as $b)
                        <option value="{{ $b->id }}" {{ request('bodega_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" style="font-size: 0.75rem; font-weight: 600;">Tipo de Modificación</label>
                <select name="tipo_modificacion" class="form-control" style="height: 36px;" onchange="document.getElementById('filterNCForm').submit()">
                    <option value="todos">-- Todos los Tipos --</option>
                    <option value="DEVOLUCION_MERCADERIA" {{ request('tipo_modificacion') == 'DEVOLUCION_MERCADERIA' ? 'selected' : '' }}>Devolución Mercadería</option>
                    <option value="DESCUENTO_VALOR" {{ request('tipo_modificacion') == 'DESCUENTO_VALOR' ? 'selected' : '' }}>Descuento en Valor</option>
                </select>
            </div>

            <div>
                <label class="form-label" style="font-size: 0.75rem; font-weight: 600;">Estado</label>
                <select name="estado" class="form-control" style="height: 36px;" onchange="document.getElementById('filterNCForm').submit()">
                    <option value="todos">-- Estado --</option>
                    <option value="EMITIDA" {{ request('estado') == 'EMITIDA' ? 'selected' : '' }}>EMITIDA</option>
                    <option value="ANULADA" {{ request('estado') == 'ANULADA' ? 'selected' : '' }}>ANULADA</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.4rem;">
                <button type="submit" class="btn-card-action btn-primary btn-sm" style="flex: 1; height: 36px;">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
                @if(request()->hasAny(['buscar', 'proveedor_id', 'bodega_id', 'tipo_modificacion', 'estado', 'fecha_desde', 'fecha_hasta', 'compra_id']))
                    <a href="{{ route('compras.notas-credito.index') }}" class="btn-card-action btn-secondary btn-sm" style="height: 36px; padding: 0 10px;" title="Limpiar Filtros">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>
</div>

<!-- MAIN DATA TABLE -->
<div class="data-card" style="padding: 0; overflow: hidden; margin-bottom: 1.15rem;">
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="padding-left: 1.25rem;">No. Nota Crédito</th>
                    <th>Fecha</th>
                    <th>Factura Origen</th>
                    <th>Proveedor</th>
                    <th>Bodega</th>
                    <th>Tipo / Motivo</th>
                    <th style="text-align: center;">Ítems</th>
                    <th style="text-align: right;">Subtotal</th>
                    <th style="text-align: right;">IVA</th>
                    <th style="text-align: right;">Total</th>
                    <th style="text-align: center;">Estado</th>
                    <th style="text-align: center; padding-right: 1.25rem; width: 110px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notasCredito as $nc)
                <tr style="{{ $nc->estado === 'ANULADA' ? 'opacity: 0.6; background-color: #fef2f2;' : '' }}">
                    <td style="padding-left: 1.25rem;">
                        <strong style="font-family: monospace; color: var(--danger); font-size: 0.9rem;">{{ $nc->numero_nota_credito }}</strong>
                        @if($nc->autorizacion_sri)
                            <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;" title="{{ $nc->autorizacion_sri }}">
                                Aut: {{ Str::limit($nc->autorizacion_sri, 18) }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <strong style="font-size: 0.85rem;">{{ $nc->fecha_emision ? $nc->fecha_emision->format('d/m/Y') : '-' }}</strong>
                    </td>
                    <td>
                        @if($nc->compra)
                            <span class="badge badge-secondary" style="font-family: monospace; font-size: 0.78rem;">
                                <i class="fa-solid fa-file-invoice text-primary"></i> {{ $nc->compra->numero_factura }}
                            </span>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $nc->proveedor->razon_social ?? 'Proveedor Desconocido' }}</strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">{{ $nc->proveedor->identificacion ?? 'S/R' }}</div>
                    </td>
                    <td>
                        <span class="badge badge-info"><i class="fa-solid fa-warehouse"></i> {{ $nc->bodega->nombre ?? 'Principal' }}</span>
                    </td>
                    <td>
                        <div>
                            @if($nc->tipo_modificacion === 'DEVOLUCION_MERCADERIA')
                                <span class="badge badge-danger" style="font-size: 0.72rem; margin-bottom: 0.2rem;">
                                    <i class="fa-solid fa-box-archive"></i> Devolución Mercadería
                                </span>
                            @else
                                <span class="badge badge-warning" style="font-size: 0.72rem; margin-bottom: 0.2rem;">
                                    <i class="fa-solid fa-tag"></i> Descuento en Valor
                                </span>
                            @endif
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); max-width: 190px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $nc->motivo }}">
                            {{ $nc->motivo }}
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-secondary" style="font-size: 0.75rem;">
                            {{ $nc->items_count }} ref. ({{ number_format($nc->cantidad_total_articulos, 1) }} uds)
                        </span>
                    </td>
                    <td style="text-align: right; color: var(--text-muted); font-size: 0.85rem; font-family: monospace;">
                        ${{ number_format($nc->subtotal_sin_impuestos, 2) }}
                    </td>
                    <td style="text-align: right; color: var(--text-muted); font-size: 0.85rem; font-family: monospace;">
                        ${{ number_format($nc->iva, 2) }}
                    </td>
                    <td style="text-align: right; color: var(--danger); font-weight: 700; font-size: 0.95rem; font-family: monospace;">
                        ${{ number_format($nc->total, 2) }}
                    </td>
                    <td style="text-align: center;">
                        @if($nc->estado === 'EMITIDA')
                            <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> EMITIDA</span>
                        @else
                            <span class="badge badge-danger"><i class="fa-solid fa-ban"></i> ANULADA</span>
                        @endif
                    </td>
                    <td style="text-align: center; padding-right: 1.25rem;">
                        <div style="display: inline-flex; gap: 0.25rem; align-items: center;">
                            <!-- Ver Detalle Modal -->
                            <button type="button" class="btn-card-action btn-secondary btn-sm" onclick="verDetalleNotaCredito({{ $nc->id }})" title="Ver detalle de la nota de crédito" style="padding: 4px 7px;">
                                <i class="fa-solid fa-eye text-primary"></i>
                            </button>

                            <!-- Anular NC -->
                            @if($nc->estado === 'EMITIDA' && auth()->user()->hasPermission('Compras', 'master'))
                                <button type="button" class="btn-card-action btn-secondary btn-sm" onclick="confirmarAnulacionNC({{ $nc->id }}, '{{ $nc->numero_nota_credito }}')" title="Anular nota de crédito y restituir stock" style="padding: 4px 7px;">
                                    <i class="fa-solid fa-trash-can text-danger"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                        <i class="fa-solid fa-file-invoice-dollar" style="font-size: 2.5rem; color: var(--border-color); margin-bottom: 0.75rem;"></i>
                        <p style="margin: 0; font-weight: 600;">No se encontraron notas de crédito de compras registradas.</p>
                        <small>Haga clic en "Nueva Nota de Crédito" para registrar una devolución a proveedor.</small>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($notasCredito->hasPages())
        <div style="padding: 0.85rem 1.25rem; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 0.8rem; color: var(--text-muted);">Mostrando {{ $notasCredito->firstItem() }} a {{ $notasCredito->lastItem() }} de {{ $notasCredito->total() }} registros</span>
            <div>{{ $notasCredito->links() }}</div>
        </div>
    @endif
</div>

<!-- ======================================================= -->
<!-- MODAL NATIVO: NUEVA NOTA DE CRÉDITO DE COMPRA -->
<!-- ======================================================= -->
<div class="modal-backdrop" id="modalNuevaNotaCredito">
    <div class="modal-content" style="max-width: 960px;">
        <div class="modal-header">
            <h2 style="font-size: 1.15rem; color: var(--danger);"><i class="fa-solid fa-file-circle-minus"></i> Registrar Nota de Crédito de Compra</h2>
            <button class="btn-close" onclick="closeModal('modalNuevaNotaCredito')"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('compras.notas-credito.store') }}" method="POST" id="formNuevaNotaCredito">
            @csrf

            <!-- STEP 1: FACTURA DE ORIGEN -->
            <div style="background: var(--bg-muted); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.85rem 1rem; margin-bottom: 1rem;">
                <div style="font-weight: 700; font-size: 0.85rem; color: var(--text-main); margin-bottom: 0.5rem;">
                    <i class="fa-solid fa-receipt text-primary"></i> 1. Seleccionar Factura de Compra de Origen
                </div>
                <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 0.85rem; align-items: center;">
                    <div>
                        <label class="form-label" style="font-size: 0.78rem;">Factura de Compra a Modificar <span style="color:var(--danger)">*</span></label>
                        <select name="compra_id" id="nc_compra_id" class="form-control" required onchange="cargarDetallesCompra(this.value)">
                            <option value="">-- Seleccione una Factura de Compra --</option>
                            @foreach($comprasDisponibles as $c)
                                <option value="{{ $c->id }}" {{ request('compra_id') == $c->id ? 'selected' : '' }}>
                                    #{{ $c->numero_factura }} - {{ $c->proveedor->razon_social ?? 'Proveedor' }} (Total: ${{ number_format($c->total, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="compra_info_box" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-xs); padding: 0.5rem 0.75rem; font-size: 0.78rem; display: none;">
                        <div><strong>Proveedor:</strong> <span id="info_proveedor">-</span></div>
                        <div><strong>Bodega:</strong> <span id="info_bodega">-</span></div>
                        <div><strong>Saldo Pendiente:</strong> $<span id="info_saldo" style="color: var(--danger); font-weight: 700;">0.00</span></div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: DATOS DEL COMPROBANTE -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.85rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label" style="font-size: 0.78rem;">No. Nota de Crédito <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="numero_nota_credito" id="nc_numero" class="form-control" placeholder="001-001-000000001" style="font-family: monospace;" required>
                    <small style="font-size: 0.7rem; color: var(--text-muted);">Formato SRI: 001-001-123456789</small>
                </div>

                <div>
                    <label class="form-label" style="font-size: 0.78rem;">Fecha de Emisión <span style="color:var(--danger)">*</span></label>
                    <input type="date" name="fecha_emision" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>

                <div>
                    <label class="form-label" style="font-size: 0.78rem;">Tipo de Modificación <span style="color:var(--danger)">*</span></label>
                    <select name="tipo_modificacion" id="nc_tipo_modificacion" class="form-control" required>
                        <option value="DEVOLUCION_MERCADERIA" selected>Devolución Mercadería (Resta Stock)</option>
                        <option value="DESCUENTO_VALOR">Descuento en Valor (Sin Kardex)</option>
                    </select>
                </div>

                <div>
                    <label class="form-label" style="font-size: 0.78rem;">Autorización SRI (Opcional)</label>
                    <input type="text" name="autorizacion_sri" class="form-control" placeholder="49 dígitos numéricos" maxlength="49" style="font-family: monospace;">
                </div>

                <div style="grid-column: span 2;">
                    <label class="form-label" style="font-size: 0.78rem;">Motivo de la Nota de Crédito <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="motivo" class="form-control" placeholder="Ej: Devolución por producto deteriorado / vencido / error de despacho" required>
                </div>

                <div style="grid-column: span 2;">
                    <label class="form-label" style="font-size: 0.78rem;">Observaciones Internas</label>
                    <input type="text" name="observaciones" class="form-control" placeholder="Notas adicionales de control interno">
                </div>
            </div>

            <!-- STEP 3: ITEMS TABLE -->
            <div style="border: 1px solid var(--border-color); border-radius: var(--radius-sm); overflow: hidden; margin-bottom: 1rem;">
                <div style="background: var(--bg-muted); padding: 0.5rem 0.85rem; font-weight: 700; font-size: 0.82rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between;">
                    <span><i class="fa-solid fa-boxes-stacked text-danger"></i> 3. Artículos a Devolver / Descontar</span>
                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">Especifique la cantidad a devolver por producto</span>
                </div>

                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                    <table class="custom-table" style="font-size: 0.82rem;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th style="text-align: center;">Comprado</th>
                                <th style="text-align: center;">Ya Devuelto</th>
                                <th style="text-align: center; background: #fee2e2; color: #991b1b;">Cant. Devolver</th>
                                <th style="text-align: right;">Costo Unit.</th>
                                <th style="text-align: center;">IVA</th>
                                <th style="text-align: right;">Subtotal</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody id="nc_items_body">
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                    <i class="fa-solid fa-hand-point-up"></i> Seleccione una Factura de Compra arriba para cargar sus productos.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TOTALS BOX -->
            <div style="display: flex; justify-content: flex-end; margin-bottom: 1.25rem;">
                <div style="width: 280px; background: var(--bg-muted); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.75rem 1rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 0.25rem;">
                        <span style="color: var(--text-muted);">Subtotal:</span>
                        <span id="nc_calc_subtotal" style="font-family: monospace; font-weight: 600;">$0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 0.4rem;">
                        <span style="color: var(--text-muted);">Total IVA:</span>
                        <span id="nc_calc_iva" style="font-family: monospace; font-weight: 600;">$0.00</span>
                    </div>
                    <div style="border-top: 1px solid var(--border-color); padding-top: 0.4rem; display: flex; justify-content: space-between; align-items: center;">
                        <strong style="font-size: 0.95rem;">Total NC:</strong>
                        <strong id="nc_calc_total" style="font-size: 1.15rem; color: var(--danger); font-family: monospace;">$0.00</strong>
                    </div>
                </div>
            </div>

            <!-- MODAL ACTIONS -->
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid var(--border-color); padding-top: 0.85rem;">
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalNuevaNotaCredito')">Cancelar</button>
                <button type="submit" class="btn-card-action btn-danger" id="btnGuardarNC" disabled>
                    <i class="fa-solid fa-save"></i> Registrar Nota de Crédito
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL NATIVO: DETALLE DE NOTA DE CRÉDITO -->
<!-- ======================================================= -->
<div class="modal-backdrop" id="modalDetalleNotaCredito">
    <div class="modal-content" style="max-width: 780px;">
        <div class="modal-header">
            <div>
                <h2 style="font-size: 1.15rem; color: var(--primary);"><i class="fa-solid fa-file-invoice-dollar text-danger"></i> Detalle de Nota de Crédito</h2>
                <small id="view_nc_title" style="color: var(--text-muted); font-family: monospace;">NC-000</small>
            </div>
            <button class="btn-close" onclick="closeModal('modalDetalleNotaCredito')"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div style="background: var(--bg-muted); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.85rem 1rem; margin-bottom: 1rem; font-size: 0.82rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.65rem;">
                <div><span style="color: var(--text-muted); display: block;">No. Nota Crédito:</span> <strong id="v_numero_nc" style="font-family: monospace; color: var(--danger);">-</strong></div>
                <div><span style="color: var(--text-muted); display: block;">Fecha Emisión:</span> <strong id="v_fecha">-</strong></div>
                <div><span style="color: var(--text-muted); display: block;">Factura Modificada:</span> <span id="v_factura_origen" class="badge badge-secondary">-</span></div>
                <div><span style="color: var(--text-muted); display: block;">Estado:</span> <span id="v_estado">-</span></div>
                <div style="grid-column: span 2;"><span style="color: var(--text-muted); display: block;">Proveedor:</span> <strong id="v_proveedor">-</strong> <span id="v_proveedor_ruc" style="color: var(--text-muted); font-size: 0.75rem;"></span></div>
                <div><span style="color: var(--text-muted); display: block;">Bodega:</span> <span id="v_bodega" class="badge badge-info">-</span></div>
                <div><span style="color: var(--text-muted); display: block;">Registrado Por:</span> <strong id="v_usuario">-</strong></div>
                <div style="grid-column: span 2;"><span style="color: var(--text-muted); display: block;">Motivo:</span> <strong id="v_motivo" style="color: var(--danger);">-</strong></div>
            </div>
        </div>

        <div style="border: 1px solid var(--border-color); border-radius: var(--radius-sm); overflow: hidden; margin-bottom: 1rem;">
            <table class="custom-table" style="font-size: 0.82rem;">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th style="text-align: center;">Cantidad</th>
                        <th style="text-align: right;">Costo Unit.</th>
                        <th style="text-align: right;">IVA</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody id="v_detalles_body"></tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
            <div style="width: 250px; background: var(--bg-muted); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.65rem 0.85rem; font-size: 0.82rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                    <span style="color: var(--text-muted);">Subtotal:</span>
                    <span id="v_subtotal" style="font-family: monospace; font-weight: 600;">$0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                    <span style="color: var(--text-muted);">IVA:</span>
                    <span id="v_iva" style="font-family: monospace; font-weight: 600;">$0.00</span>
                </div>
                <div style="border-top: 1px solid var(--border-color); padding-top: 0.35rem; display: flex; justify-content: space-between; font-weight: 700;">
                    <span>Total NC:</span>
                    <span id="v_total" style="color: var(--danger); font-family: monospace; font-size: 1rem;">$0.00</span>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; border-top: 1px solid var(--border-color); padding-top: 0.85rem;">
            <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalDetalleNotaCredito')">Cerrar</button>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL NATIVO: ANULAR NOTA DE CRÉDITO -->
<!-- ======================================================= -->
<div class="modal-backdrop" id="modalAnularNC">
    <div class="modal-content" style="max-width: 460px;">
        <div class="modal-header">
            <h2 style="font-size: 1.1rem; color: var(--danger);"><i class="fa-solid fa-triangle-exclamation"></i> Anular Nota de Crédito</h2>
            <button class="btn-close" onclick="closeModal('modalAnularNC')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="formAnularNC" method="POST">
            @csrf
            <div style="padding: 0.5rem 0 1rem 0; font-size: 0.88rem;">
                <p style="margin-bottom: 0.5rem;">¿Está seguro de anular la Nota de Crédito <strong id="anular_nc_num" style="color: var(--danger); font-family: monospace;"></strong>?</p>
                <div style="background: var(--danger-light); border: 1px solid var(--danger-border); border-radius: var(--radius-xs); padding: 0.5rem 0.75rem; font-size: 0.78rem; color: #991b1b; margin-bottom: 0.85rem;">
                    <i class="fa-solid fa-info-circle"></i> Esta acción restituirá el stock físico en la bodega y dejará sin efecto este comprobante.
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.78rem;">Motivo de Anulación <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="motivo" class="form-control" placeholder="Ej: Error en emisión / acuerdo revertido" required>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid var(--border-color); padding-top: 0.85rem;">
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalAnularNC')">Cancelar</button>
                <button type="submit" class="btn-card-action btn-danger">Confirmar Anulación</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function abrirModalNuevaNC() {
        openModal('modalNuevaNotaCredito');
    }

    function cargarDetallesCompra(compraId) {
        if (!compraId) {
            document.getElementById('compra_info_box').style.display = 'none';
            document.getElementById('nc_items_body').innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        <i class="fa-solid fa-hand-point-up"></i> Seleccione una Factura de Compra arriba para cargar sus productos.
                    </td>
                </tr>
            `;
            calcularTotalesNC();
            return;
        }

        fetch(`{{ url('/compras/notas-credito/compra') }}/${compraId}/detalles`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const c = data.compra;
                    document.getElementById('info_proveedor').innerText = c.proveedor.razon_social;
                    document.getElementById('info_bodega').innerText = c.bodega.nombre;
                    document.getElementById('info_saldo').innerText = parseFloat(c.saldo_pendiente).toFixed(2);
                    document.getElementById('compra_info_box').style.display = 'block';

                    renderNCItems(c.items);
                }
            })
            .catch(err => {
                console.error('Error al cargar compra:', err);
                alert('No se pudo cargar la información de la factura de compra.');
            });
    }

    function renderNCItems(items) {
        const tbody = document.getElementById('nc_items_body');
        tbody.innerHTML = '';

        if (!items || items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; padding: 1.5rem; color: var(--text-muted);">Esta compra no contiene artículos disponibles para devolver.</td></tr>`;
            return;
        }

        items.forEach((item, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="color: var(--text-muted); font-size: 0.75rem;">${index + 1}</td>
                <td>
                    <strong>${item.nombre}</strong>
                    <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">${item.codigo}</div>
                    <input type="hidden" name="detalles[${index}][producto_id]" value="${item.producto_id}">
                    <input type="hidden" name="detalles[${index}][costo_unitario]" value="${item.costo_unitario}">
                </td>
                <td style="text-align: center; font-family: monospace;">${parseFloat(item.cantidad_comprada).toFixed(2)}</td>
                <td style="text-align: center; font-family: monospace; color: var(--text-muted);">${parseFloat(item.cantidad_previa_devuelta).toFixed(2)}</td>
                <td style="text-align: center; background: #fff1f2;">
                    <input type="number" step="0.01" min="0" max="${item.cantidad_disponible}" 
                           name="detalles[${index}][cantidad]" 
                           class="form-control input-nc-qty" 
                           style="height: 30px; text-align: center; font-family: monospace; font-weight: 700; width: 85px; margin: 0 auto;" 
                           value="0" 
                           data-index="${index}"
                           data-cost="${item.costo_unitario}"
                           data-tax="${item.tarifa_iva}"
                           data-max="${item.cantidad_disponible}"
                           oninput="onQtyChange(this)">
                    <div style="font-size: 0.68rem; color: var(--text-muted);">Máx: ${parseFloat(item.cantidad_disponible).toFixed(2)}</div>
                </td>
                <td style="text-align: right; font-family: monospace;">$${parseFloat(item.costo_unitario).toFixed(2)}</td>
                <td style="text-align: center; font-size: 0.75rem;">${item.tarifa_iva}%</td>
                <td style="text-align: right; font-family: monospace;" id="row_sub_${index}">$0.00</td>
                <td style="text-align: right; font-family: monospace; font-weight: 700; color: var(--danger);" id="row_tot_${index}">$0.00</td>
            `;
            tbody.appendChild(tr);
        });

        calcularTotalesNC();
    }

    function onQtyChange(input) {
        const val = parseFloat(input.value) || 0;
        const max = parseFloat(input.getAttribute('data-max')) || 0;
        const index = input.getAttribute('data-index');
        const cost = parseFloat(input.getAttribute('data-cost')) || 0;
        const taxRate = parseFloat(input.getAttribute('data-tax')) || 0;

        if (val > max) {
            input.value = max;
            alert(`La cantidad a devolver no puede superar el límite disponible (${max})`);
        }

        const effectiveQty = parseFloat(input.value) || 0;
        const rowSubtotal = effectiveQty * cost;
        const rowIva = (taxRate > 0) ? (rowSubtotal * (taxRate / 100)) : 0;
        const rowTotal = rowSubtotal + rowIva;

        document.getElementById(`row_sub_${index}`).innerText = '$' + rowSubtotal.toFixed(2);
        document.getElementById(`row_tot_${index}`).innerText = '$' + rowTotal.toFixed(2);

        calcularTotalesNC();
    }

    function calcularTotalesNC() {
        let subtotal = 0;
        let totalIva = 0;
        let hasValidItems = false;

        document.querySelectorAll('.input-nc-qty').forEach(input => {
            const qty = parseFloat(input.value) || 0;
            if (qty > 0) {
                hasValidItems = true;
                const cost = parseFloat(input.getAttribute('data-cost')) || 0;
                const taxRate = parseFloat(input.getAttribute('data-tax')) || 0;

                const lineSub = qty * cost;
                const lineIva = (taxRate > 0) ? (lineSub * (taxRate / 100)) : 0;

                subtotal += lineSub;
                totalIva += lineIva;
            }
        });

        const total = subtotal + totalIva;

        document.getElementById('nc_calc_subtotal').innerText = '$' + subtotal.toFixed(2);
        document.getElementById('nc_calc_iva').innerText = '$' + totalIva.toFixed(2);
        document.getElementById('nc_calc_total').innerText = '$' + total.toFixed(2);

        const btn = document.getElementById('btnGuardarNC');
        if (btn) {
            btn.disabled = !hasValidItems;
        }
    }

    function verDetalleNotaCredito(id) {
        fetch(`{{ url('/compras/notas-credito') }}/${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const nc = data.nota_credito;
                    document.getElementById('view_nc_title').innerText = `#${nc.numero_nota_credito} - ${nc.tipo_modificacion_texto}`;
                    document.getElementById('v_numero_nc').innerText = nc.numero_nota_credito;
                    document.getElementById('v_fecha').innerText = nc.fecha_emision;
                    document.getElementById('v_factura_origen').innerText = nc.compra.numero_factura;
                    document.getElementById('v_proveedor').innerText = nc.proveedor.razon_social;
                    document.getElementById('v_proveedor_ruc').innerText = 'RUC/CI: ' + nc.proveedor.identificacion;
                    document.getElementById('v_bodega').innerText = nc.bodega;
                    document.getElementById('v_usuario').innerText = nc.usuario;
                    document.getElementById('v_motivo').innerText = nc.motivo;

                    document.getElementById('v_estado').innerHTML = (nc.estado === 'EMITIDA')
                        ? `<span class="badge badge-success">EMITIDA</span>`
                        : `<span class="badge badge-danger">ANULADA</span>`;

                    const tbody = document.getElementById('v_detalles_body');
                    tbody.innerHTML = '';
                    nc.detalles.forEach(d => {
                        tbody.innerHTML += `
                            <tr>
                                <td style="font-family: monospace; font-size: 0.78rem;">${d.producto_codigo}</td>
                                <td><strong>${d.producto_nombre}</strong></td>
                                <td style="text-align: center; font-family: monospace; font-weight: 700; color: var(--danger);">-${parseFloat(d.cantidad).toFixed(2)}</td>
                                <td style="text-align: right; font-family: monospace;">$${parseFloat(d.costo_unitario).toFixed(2)}</td>
                                <td style="text-align: right; font-family: monospace; color: var(--text-muted);">$${parseFloat(d.iva_total).toFixed(2)} (${d.tarifa_iva}%)</td>
                                <td style="text-align: right; font-family: monospace; font-weight: 700;">$${parseFloat(d.costo_total).toFixed(2)}</td>
                            </tr>
                        `;
                    });

                    document.getElementById('v_subtotal').innerText = '$' + parseFloat(nc.subtotal).toFixed(2);
                    document.getElementById('v_iva').innerText = '$' + parseFloat(nc.iva).toFixed(2);
                    document.getElementById('v_total').innerText = '$' + parseFloat(nc.total).toFixed(2);

                    openModal('modalDetalleNotaCredito');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error al obtener los detalles de la Nota de Crédito');
            });
    }

    function confirmarAnulacionNC(id, numero) {
        document.getElementById('anular_nc_num').innerText = numero;
        document.getElementById('formAnularNC').action = `{{ url('/compras/notas-credito') }}/${id}/anular`;
        openModal('modalAnularNC');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const selectCompra = document.getElementById('nc_compra_id');
        if (selectCompra && selectCompra.value) {
            cargarDetallesCompra(selectCompra.value);
            abrirModalNuevaNC();
        }
    });
</script>
@endpush
@endsection
