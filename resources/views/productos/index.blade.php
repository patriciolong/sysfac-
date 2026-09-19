@extends('layouts.app')

@section('title', 'Inventario y Catálogo de Productos')

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
        <strong><i class="fa-solid fa-circle-exclamation"></i> Por favor verifique los siguientes errores:</strong>
        <ul style="margin: 0.35rem 0 0 1.25rem;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- PRODUCTOS HEADER -->
<div class="data-card" style="padding: 0.95rem 1.35rem; margin-bottom: 1.15rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
        <div>
            <h1 style="margin-bottom: 0.2rem; font-size: 1.35rem;"><i class="fa-solid fa-boxes-stacked text-primary"></i> Inventario & Catálogo de Productos</h1>
            <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Administre precios, costos, existencias por bodega y controle los márgenes comerciales en tiempo real.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="{{ route('productos.export', request()->query()) }}" class="btn-card-action btn-secondary btn-sm" title="Descargar Catálogo en formato CSV">
                <i class="fa-solid fa-file-csv text-success"></i> Exportar CSV
            </a>
            @if(auth()->user()->hasPermission('Productos', 'master'))
                <a href="{{ route('productos.plantillaExcel') }}" class="btn-card-action btn-secondary btn-sm" title="Descargar plantilla Excel (.xlsx) para carga masiva">
                    <i class="fa-solid fa-file-excel" style="color: #107c41;"></i> Plantilla Excel (.xlsx)
                </a>
                <button class="btn-card-action btn-secondary btn-sm" onclick="openModal('modalImportarExcelProductos')" title="Importar catálogo masivamente desde archivo Excel (.xlsx)">
                    <i class="fa-solid fa-file-arrow-up text-primary"></i> Importar Excel (.xlsx)
                </button>
                <button class="btn-card-action btn-secondary btn-sm" onclick="openModal('modalNuevaCategoria')">
                    <i class="fa-solid fa-folder-plus text-primary"></i> Nueva Categoría
                </button>
                <button class="btn-card-action btn-success btn-sm" onclick="openModal('modalNuevoProducto')">
                    <i class="fa-solid fa-plus-circle"></i> Nuevo Producto
                </button>
            @else
                <button class="btn-card-action btn-secondary btn-sm" style="opacity: 0.6;" onclick="alert('No tiene permisos para crear productos.')">
                    <i class="fa-solid fa-plus-circle"></i> Nuevo Producto 🔒
                </button>
            @endif
        </div>
    </div>
</div>

<!-- INVENTORY KPI SUMMARY -->
<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.15rem;">
    <div class="metric-card">
        <div>
            <div class="metric-label">Total en Catálogo</div>
            <div class="metric-val">{{ $kpis['total_items'] }} <small style="font-size: 0.75rem; font-weight: normal; color: var(--text-muted);">({{ number_format($kpis['total_stock'], 0) }} uds)</small></div>
        </div>
        <div class="icon-box icon-blue"><i class="fa-solid fa-boxes-packing"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Valor Inv. (Al Costo)</div>
            <div class="metric-val" style="color: var(--primary);">${{ number_format($kpis['valor_costo'], 2) }}</div>
        </div>
        <div class="icon-box icon-purple"><i class="fa-solid fa-vault"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Valor Estimado Venta</div>
            <div class="metric-val" style="color: var(--success);">${{ number_format($kpis['valor_venta'], 2) }}</div>
        </div>
        <div class="icon-box icon-green"><i class="fa-solid fa-hand-holding-dollar"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Stock Normal / Bajo / Agotado</div>
            <div class="metric-val" style="font-size: 1.1rem; display: flex; gap: 0.4rem; align-items: center;">
                <span class="badge badge-success" title="En Stock">{{ $kpis['en_stock'] }}</span>
                <span class="badge badge-warning" title="Stock Bajo">{{ $kpis['stock_bajo'] }}</span>
                <span class="badge badge-danger" title="Agotados">{{ $kpis['agotados'] }}</span>
            </div>
        </div>
        <div class="icon-box icon-amber"><i class="fa-solid fa-chart-pie"></i></div>
    </div>
</div>

<!-- FILTERS & SEARCH TOOLBAR -->
<div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1.15rem;">
    <form method="GET" action="{{ url('/productos') }}" id="filterForm">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; align-items: end;">
            <div>
                <label class="form-label" style="font-size: 0.75rem; font-weight: 600;">Buscar Producto</label>
                <div style="position: relative;">
                    <input type="text" name="search" id="searchTable" class="form-control" placeholder="Nombre, SKU o código..." value="{{ request('search') }}" style="height: 36px; padding-left: 2rem;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 0.85rem;"></i>
                </div>
            </div>

            <div>
                <label class="form-label" style="font-size: 0.75rem; font-weight: 600;">Filtrar por Categoría</label>
                <select name="categoria_id" class="form-control" style="height: 36px;" onchange="document.getElementById('filterForm').submit()">
                    <option value="todas">-- Todas las Categorías --</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" style="font-size: 0.75rem; font-weight: 600;">Estado de Stock</label>
                <select name="estado_stock" class="form-control" style="height: 36px;" onchange="document.getElementById('filterForm').submit()">
                    <option value="todos" {{ request('estado_stock') == 'todos' ? 'selected' : '' }}>Todos los Estados</option>
                    <option value="EN_STOCK" {{ request('estado_stock') == 'EN_STOCK' ? 'selected' : '' }}>🟢 Stock Normal</option>
                    <option value="STOCK_BAJO" {{ request('estado_stock') == 'STOCK_BAJO' ? 'selected' : '' }}>🟡 Stock Bajo (&le; Mínimo)</option>
                    <option value="AGOTADO" {{ request('estado_stock') == 'AGOTADO' ? 'selected' : '' }}>🔴 Agotados (0)</option>
                </select>
            </div>

            <div>
                <label class="form-label" style="font-size: 0.75rem; font-weight: 600;">Estado Registro</label>
                <select name="estado" class="form-control" style="height: 36px;" onchange="document.getElementById('filterForm').submit()">
                    <option value="todos" {{ request('estado') == 'todos' ? 'selected' : '' }}>Todos (Activos / Inactivos)</option>
                    <option value="ACTIVO" {{ request('estado', 'ACTIVO') == 'ACTIVO' ? 'selected' : '' }}>Solo Activos</option>
                    <option value="INACTIVO" {{ request('estado') == 'INACTIVO' ? 'selected' : '' }}>Solo Inactivos</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.4rem;">
                <button type="submit" class="btn-card-action btn-primary" style="height: 36px; flex: 1;">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
                @if(request()->hasAny(['search', 'categoria_id', 'estado_stock', 'estado']))
                    <a href="{{ url('/productos') }}" class="btn-card-action btn-secondary" style="height: 36px;" title="Limpiar Filtros">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>
</div>

<!-- PRODUCTOS TABLE -->
<div class="data-card">
    <div class="data-card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
        <h2><i class="fa-solid fa-table-list text-primary"></i> Catálogo de Productos ({{ count($productos) }} listados)</h2>
    </div>

    <div class="table-responsive">
        <table class="custom-table" id="tablaProductos">
            <thead>
                <tr>
                    <th style="width: 110px;">SKU / Código</th>
                    <th>Nombre del Producto</th>
                    <th>Categoría</th>
                    <th>Tipo</th>
                    <th style="text-align: right;">Stock Actual</th>
                    <th style="text-align: right;">Costo Prom.</th>
                    <th style="text-align: right;">Precio Venta</th>
                    <th style="text-align: center;">Margen (%)</th>
                    <th>IVA SRI</th>
                    <th>Estado</th>
                    <th style="text-align: center; width: 140px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productos as $prod)
                <tr style="{{ $prod['estado'] === 'INACTIVO' ? 'opacity: 0.6; background-color: #fafafa;' : '' }}">
                    <td>
                        <strong style="font-family: monospace; color: var(--primary); font-size: 0.85rem;">{{ $prod['codigo_principal'] }}</strong>
                        @if(!empty($prod['codigo_auxiliar']))
                            <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">Aux: {{ $prod['codigo_auxiliar'] }}</div>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.55rem;">
                            <div style="width: 32px; height: 32px; border-radius: var(--radius-xs); background: var(--bg-muted); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.9rem; flex-shrink: 0;">
                                <i class="fa-solid {{ $prod['icono'] }}"></i>
                            </div>
                            <div>
                                <strong style="color: var(--text-main); font-size: 0.9rem;">{{ $prod['nombre'] }}</strong>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">P. con IVA: ${{ number_format($prod['precio_con_iva'], 2) }}</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-info">{{ $prod['categoria'] }}</span></td>
                    <td>
                        <span class="badge {{ $prod['tipo_producto'] === 'BIEN' ? 'badge-secondary' : 'badge-primary' }}" style="font-size: 0.7rem;">
                            {{ $prod['tipo_producto'] }}
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <div style="font-weight: 700; font-size: 0.92rem; color: {{ $prod['stock_actual'] <= 0 ? 'var(--danger)' : ($prod['stock_actual'] <= $prod['stock_minimo'] ? 'var(--warning)' : 'var(--text-main)') }};">
                            {{ number_format($prod['stock_actual'], 0) }}
                        </div>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">Mín: {{ number_format($prod['stock_minimo'], 0) }}</div>
                    </td>
                    <td style="text-align: right; color: var(--text-muted); font-size: 0.875rem;">
                        ${{ number_format($prod['costo_promedio'], 2) }}
                    </td>
                    <td style="text-align: right;">
                        <strong style="color: var(--success); font-size: 0.95rem;">${{ number_format($prod['precio_unitario'], 2) }}</strong>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge {{ $prod['margen_porcentaje'] >= 30 ? 'badge-success' : ($prod['margen_porcentaje'] > 0 ? 'badge-warning' : 'badge-danger') }}" style="font-size: 0.75rem;">
                            {{ $prod['margen_porcentaje'] }}%
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $prod['codigo_iva'] === '2' ? 'badge-primary' : 'badge-secondary' }}" style="font-size: 0.72rem;">
                            IVA {{ $prod['iva'] }}
                        </span>
                    </td>
                    <td>
                        @if($prod['estado'] === 'INACTIVO')
                            <span class="badge badge-secondary"><i class="fa-solid fa-power-off"></i> INACTIVO</span>
                        @elseif($prod['estado_stock'] == 'EN_STOCK')
                            <span class="badge badge-success"><i class="fa-solid fa-check"></i> NORMAL</span>
                        @elseif($prod['estado_stock'] == 'STOCK_BAJO')
                            <span class="badge badge-warning"><i class="fa-solid fa-triangle-exclamation"></i> BAJO</span>
                        @else
                            <span class="badge badge-danger"><i class="fa-solid fa-circle-xmark"></i> AGOTADO</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <div style="display: inline-flex; gap: 0.25rem; align-items: center;">
                            <!-- Ver Ficha Detalle -->
                            <button type="button" class="btn-card-action btn-secondary btn-sm" onclick="verFichaProducto({{ $prod['id'] }})" title="Ver ficha técnica y existencias por bodega" style="padding: 4px 7px;">
                                <i class="fa-solid fa-eye text-primary"></i>
                            </button>

                            <!-- Editar -->
                            @if(auth()->user()->hasPermission('Productos', 'master'))
                                <button type="button" class="btn-card-action btn-secondary btn-sm" onclick="abrirModalEditar({{ $prod['id'] }})" title="Editar producto" style="padding: 4px 7px;">
                                    <i class="fa-solid fa-pen text-warning"></i>
                                </button>
                            @endif

                            <!-- Kardex direct link -->
                            <a href="{{ url('/kardex?producto_id='.$prod['id']) }}" class="btn-card-action btn-primary btn-sm" title="Consultar movimientos en Kardex" style="padding: 4px 7px;">
                                <i class="fa-solid fa-chart-line"></i>
                            </a>

                            <!-- Toggle Estado -->
                            @if(auth()->user()->hasPermission('Productos', 'master'))
                                <form action="{{ route('productos.toggleEstado', $prod['id']) }}" method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('¿Desea cambiar el estado de este producto?')">
                                    @csrf
                                    <button type="submit" class="btn-card-action {{ $prod['estado'] === 'ACTIVO' ? 'btn-secondary' : 'btn-success' }} btn-sm" title="{{ $prod['estado'] === 'ACTIVO' ? 'Desactivar producto' : 'Activar producto' }}" style="padding: 4px 7px;">
                                        <i class="fa-solid fa-power-off {{ $prod['estado'] === 'ACTIVO' ? 'text-danger' : 'text-success' }}"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                        <i class="fa-solid fa-box-open" style="font-size: 2.5rem; color: var(--border-color); margin-bottom: 0.75rem;"></i>
                        <p style="margin: 0; font-weight: 600;">No se encontraron productos con los filtros seleccionados.</p>
                        <small>Intente cambiar el criterio de búsqueda o cree un nuevo producto.</small>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: NUEVO PRODUCTO                      -->
<!-- ========================================== -->
<div class="modal-backdrop" id="modalNuevoProducto">
    <div class="modal-content" style="max-width: 680px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-box text-primary"></i> Registrar Nuevo Producto en Catálogo</h2>
            <button class="btn-close" onclick="closeModal('modalNuevoProducto')">&times;</button>
        </div>
        <form action="{{ route('productos.store') }}" method="POST">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Código Principal (SKU / Barras) <span class="text-danger">*</span></label>
                    <input type="text" name="codigo_principal" class="form-control" placeholder="Ej: PROD-009" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Código Auxiliar (Opcional)</label>
                    <input type="text" name="codigo_auxiliar" class="form-control" placeholder="Ej: BAR-78610023">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Nombre Comercial del Producto <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control" placeholder="Ej: Aceite Esencial de Eucalipto 100ml" required>
            </div>

            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                        <label class="form-label" style="margin: 0;">Categoría <span class="text-danger">*</span></label>
                        <a href="javascript:void(0)" onclick="openModal('modalNuevaCategoria')" style="font-size: 0.75rem; color: var(--primary); font-weight: 600;">+ Nueva Categoría</a>
                    </div>
                    <select name="categoria_id" id="selectCategoriaNuevo" class="form-control" required>
                        @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo de Producto <span class="text-danger">*</span></label>
                    <select name="tipo_producto" class="form-control" required>
                        <option value="BIEN">BIEN (Físico con Stock)</option>
                        <option value="SERVICIO">SERVICIO (Intangible / Sin Stock)</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem; background: var(--bg-muted); padding: 0.85rem; border-radius: var(--radius-sm); margin-bottom: 0.85rem;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Costo Compra / Prom. ($)</label>
                    <input type="number" step="0.0001" name="costo_promedio" id="nuevo_costo" class="form-control" placeholder="0.00" onkeyup="calcularMargenNuevo()">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Precio de Venta ($) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="precio_unitario" id="nuevo_precio" class="form-control" placeholder="0.00" required onkeyup="calcularMargenNuevo()">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Margen Estimado</label>
                    <div id="nuevo_margen_preview" style="font-size: 0.95rem; font-weight: 700; color: var(--success); padding-top: 0.35rem;">
                        0.00% ($0.00)
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Impuesto IVA SRI <span class="text-danger">*</span></label>
                    <select name="codigo_iva" class="form-control" required>
                        <option value="2">IVA 15% (Tarifa General)</option>
                        <option value="0">IVA 0% (Exento / Tarifa 0%)</option>
                        <option value="4">IVA 5% (Materiales / Tarifa Reducida)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Stock Mínimo para Alerta</label>
                    <input type="number" name="stock_minimo" class="form-control" placeholder="5" value="5" min="0">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 0.75rem; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Bodega Destino para Stock Inicial</label>
                    <select name="bodega_id" class="form-control">
                        @foreach($bodegas as $b)
                            <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Stock Inicial (Uds)</label>
                    <input type="number" step="0.01" name="stock_inicial" class="form-control" placeholder="0" value="0" min="0">
                </div>
            </div>

            <div style="display: flex; gap: 0.65rem; margin-top: 1.25rem;">
                <button type="submit" class="btn-card-action btn-success" style="flex: 1;">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Producto
                </button>
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalNuevoProducto')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: EDITAR PRODUCTO                     -->
<!-- ========================================== -->
<div class="modal-backdrop" id="modalEditarProducto">
    <div class="modal-content" style="max-width: 680px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-pen-to-square text-warning"></i> Editar Producto</h2>
            <button class="btn-close" onclick="closeModal('modalEditarProducto')">&times;</button>
        </div>
        <form id="formEditarProducto" method="POST">
            @csrf
            @method('PUT')
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Código Principal (SKU) <span class="text-danger">*</span></label>
                    <input type="text" name="codigo_principal" id="edit_codigo_principal" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Código Auxiliar</label>
                    <input type="text" name="codigo_auxiliar" id="edit_codigo_auxiliar" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Nombre Comercial del Producto <span class="text-danger">*</span></label>
                <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
            </div>

            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Categoría <span class="text-danger">*</span></label>
                    <select name="categoria_id" id="edit_categoria_id" class="form-control" required>
                        @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo de Producto <span class="text-danger">*</span></label>
                    <select name="tipo_producto" id="edit_tipo_producto" class="form-control" required>
                        <option value="BIEN">BIEN (Físico con Stock)</option>
                        <option value="SERVICIO">SERVICIO (Intangible)</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem; background: var(--bg-muted); padding: 0.85rem; border-radius: var(--radius-sm); margin-bottom: 0.85rem;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Costo Promedio ($)</label>
                    <input type="number" step="0.0001" name="costo_promedio" id="edit_costo_promedio" class="form-control" onkeyup="calcularMargenEdit()">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Precio de Venta ($) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="precio_unitario" id="edit_precio_unitario" class="form-control" required onkeyup="calcularMargenEdit()">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Margen Estimado</label>
                    <div id="edit_margen_preview" style="font-size: 0.95rem; font-weight: 700; color: var(--success); padding-top: 0.35rem;">
                        0.00% ($0.00)
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Impuesto IVA SRI <span class="text-danger">*</span></label>
                    <select name="codigo_iva" id="edit_codigo_iva" class="form-control" required>
                        <option value="2">IVA 15% (Tarifa General)</option>
                        <option value="0">IVA 0% (Exento)</option>
                        <option value="4">IVA 5% (Tarifa Reducida)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Stock Mínimo Alerta</label>
                    <input type="number" name="stock_minimo" id="edit_stock_minimo" class="form-control" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Estado Registro <span class="text-danger">*</span></label>
                    <select name="estado" id="edit_estado" class="form-control" required>
                        <option value="ACTIVO">ACTIVO</option>
                        <option value="INACTIVO">INACTIVO</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 0.65rem; margin-top: 1.25rem;">
                <button type="submit" class="btn-card-action btn-warning" style="flex: 1;">
                    <i class="fa-solid fa-arrows-rotate"></i> Actualizar Producto
                </button>
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalEditarProducto')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: FICHA / DETALLE DE PRODUCTO         -->
<!-- ========================================== -->
<div class="modal-backdrop" id="modalDetalleProducto">
    <div class="modal-content" style="max-width: 720px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-circle-info text-primary"></i> Ficha Técnica & Existencias</h2>
            <button class="btn-close" onclick="closeModal('modalDetalleProducto')">&times;</button>
        </div>
        <div id="detalleProductoContenido" style="padding: 0.5rem 0;">
            <div style="text-align: center; padding: 2rem;"><i class="fa-solid fa-circle-notch fa-spin text-primary" style="font-size: 2rem;"></i></div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: NUEVA CATEGORÍA (AJAX)              -->
<!-- ========================================== -->
<div class="modal-backdrop" id="modalNuevaCategoria">
    <div class="modal-content" style="max-width: 480px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-folder-plus text-primary"></i> Crear Nueva Categoría</h2>
            <button class="btn-close" onclick="closeModal('modalNuevaCategoria')">&times;</button>
        </div>
        <form id="formNuevaCategoria" onsubmit="guardarCategoriaAjax(event)">
            @csrf
            <div class="form-group">
                <label class="form-label">Nombre de la Categoría <span class="text-danger">*</span></label>
                <input type="text" name="nombre" id="cat_nombre" class="form-control" placeholder="Ej: Cosmética Natural" required>
            </div>
            <div class="form-group">
                <label class="form-label">Descripción (Opcional)</label>
                <textarea name="descripcion" id="cat_descripcion" class="form-control" rows="2" placeholder="Detalle de productos agrupados en esta categoría"></textarea>
            </div>
            <div style="display: flex; gap: 0.65rem; margin-top: 1.25rem;">
                <button type="submit" class="btn-card-action btn-primary" style="flex: 1;" id="btnGuardarCategoria">
                    <i class="fa-solid fa-check"></i> Guardar Categoría
                </button>
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalNuevaCategoria')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: IMPORTAR PRODUCTOS DESDE EXCEL      -->
<!-- ========================================== -->
<div class="modal-backdrop" id="modalImportarExcelProductos">
    <div class="modal-content" style="max-width: 560px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-file-excel" style="color: #107c41;"></i> Carga Masiva de Productos (Excel .xlsx)</h2>
            <button class="btn-close" onclick="closeModal('modalImportarExcelProductos')">&times;</button>
        </div>

        <form id="formImportarExcelProductos" onsubmit="ejecutarImportacionProductos(event)">
            @csrf

            <div style="background: var(--bg-muted); border-left: 4px solid #107c41; padding: 0.85rem 1.15rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem; font-size: 0.82rem; line-height: 1.45;">
                <strong><i class="fa-solid fa-circle-info text-primary"></i> Instrucciones de Carga:</strong>
                <ul style="margin: 0.35rem 0 0 1.2rem; padding: 0;">
                    <li>Descargue la <a href="{{ route('productos.plantillaExcel') }}" style="color: #107c41; font-weight: 700; text-decoration: underline;"><i class="fa-solid fa-download"></i> Plantilla Excel (.xlsx)</a> con el formato preconfigurado.</li>
                    <li>Columnas: <code>codigo_principal</code>, <code>codigo_auxiliar</code>, <code>nombre_producto</code>, <code>categoria</code>, <code>tipo_producto</code> (BIEN/SERVICIO), <code>costo_promedio</code>, <code>precio_unitario</code>, <code>tarifa_iva</code>, <code>stock_inicial</code> y <code>stock_minimo</code>.</li>
                    <li>Si una categoría no existe en el sistema, será creada automáticamente.</li>
                </ul>
            </div>

            <div class="form-group" style="margin-bottom: 1.15rem;">
                <label class="form-label">Archivo Excel (.xlsx / .xls) <span class="text-danger">*</span></label>
                <div style="border: 2px dashed var(--border-color); border-radius: var(--radius-sm); padding: 1.5rem; text-align: center; background: #fafafa;" id="dropzoneExcel">
                    <i class="fa-solid fa-file-excel text-success" style="font-size: 2.25rem; margin-bottom: 0.5rem; display: block;"></i>
                    <input type="file" name="excel_file" id="inputExcelProductos" accept=".xlsx,.xls,.csv" required style="display: block; margin: 0 auto 0.5rem auto; font-size: 0.85rem;">
                    <span style="font-size: 0.76rem; color: var(--text-muted);">Formato nativo de Microsoft Excel (.xlsx) soportado</span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; margin-bottom: 1.15rem;">
                <div class="form-group">
                    <label class="form-label">Bodega para Stock Inicial</label>
                    <select name="bodega_id" class="form-control">
                        @foreach($bodegas as $b)
                            <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="display: flex; flex-direction: column; justify-content: flex-end;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer; margin-bottom: 0.5rem;">
                        <input type="checkbox" name="actualizar_existentes" value="1" checked style="width: 1.1rem; height: 1.1rem;">
                        <span>Actualizar datos si el código ya existe</span>
                    </label>
                </div>
            </div>

            <div id="resultadoImportacionProductos" style="display: none; margin-bottom: 1.15rem; font-size: 0.85rem; padding: 0.85rem; border-radius: var(--radius-sm);"></div>

            <div style="display: flex; gap: 0.65rem; justify-content: flex-end; margin-top: 1rem;">
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalImportarExcelProductos')">Cancelar</button>
                <button type="submit" class="btn-card-action btn-success" id="btnEjecutarImportExcel">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Importar Productos
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function calcularMargenNuevo() {
        const costo = parseFloat(document.getElementById('nuevo_costo').value) || 0;
        const precio = parseFloat(document.getElementById('nuevo_precio').value) || 0;
        const diff = precio - costo;
        const pct = costo > 0 ? ((diff / costo) * 100).toFixed(1) : 100.0;
        const el = document.getElementById('nuevo_margen_preview');
        el.innerText = `${pct}% ($${diff.toFixed(2)})`;
        el.style.color = diff >= 0 ? 'var(--success)' : 'var(--danger)';
    }

    function calcularMargenEdit() {
        const costo = parseFloat(document.getElementById('edit_costo_promedio').value) || 0;
        const precio = parseFloat(document.getElementById('edit_precio_unitario').value) || 0;
        const diff = precio - costo;
        const pct = costo > 0 ? ((diff / costo) * 100).toFixed(1) : 100.0;
        const el = document.getElementById('edit_margen_preview');
        el.innerText = `${pct}% ($${diff.toFixed(2)})`;
        el.style.color = diff >= 0 ? 'var(--success)' : 'var(--danger)';
    }

    function abrirModalEditar(id) {
        fetch(`{{ url('/productos') }}/${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const p = data.producto;
                    document.getElementById('formEditarProducto').action = `{{ url('/productos') }}/${p.id}`;
                    document.getElementById('edit_codigo_principal').value = p.codigo_principal;
                    document.getElementById('edit_codigo_auxiliar').value = p.codigo_auxiliar || '';
                    document.getElementById('edit_nombre').value = p.nombre;
                    document.getElementById('edit_categoria_id').value = p.categoria_id;
                    document.getElementById('edit_tipo_producto').value = p.tipo_producto;
                    document.getElementById('edit_costo_promedio').value = p.costo_promedio;
                    document.getElementById('edit_precio_unitario').value = p.precio_unitario;
                    document.getElementById('edit_codigo_iva').value = p.codigo_iva;
                    document.getElementById('edit_stock_minimo').value = p.stock_minimo;
                    document.getElementById('edit_estado').value = p.estado;
                    calcularMargenEdit();
                    openModal('modalEditarProducto');
                }
            })
            .catch(err => alert('Error al cargar datos del producto.'));
    }

    function verFichaProducto(id) {
        const container = document.getElementById('detalleProductoContenido');
        container.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fa-solid fa-circle-notch fa-spin text-primary" style="font-size: 2rem;"></i></div>';
        openModal('modalDetalleProducto');

        fetch(`{{ url('/productos') }}/${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const p = data.producto;
                    let bodegasHtml = '';
                    p.bodegas.forEach(b => {
                        bodegasHtml += `
                            <tr>
                                <td><strong>${b.bodega_nombre}</strong></td>
                                <td style="text-align: right; font-weight: 700; color: ${b.stock <= b.minimo ? 'var(--warning)' : 'var(--success)'};">${b.stock} uds</td>
                                <td style="text-align: right; color: var(--text-muted);">${b.minimo} uds</td>
                            </tr>
                        `;
                    });

                    let movimientosHtml = '';
                    if (p.movimientos_recientes && p.movimientos_recientes.length > 0) {
                        p.movimientos_recientes.forEach(m => {
                            const badgeClass = m.naturaleza === 'INGRESO' ? 'badge-success' : 'badge-danger';
                            const badgeIcon = m.naturaleza === 'INGRESO' ? 'fa-arrow-down' : 'fa-arrow-up';
                            movimientosHtml += `
                                <tr>
                                    <td><small>${m.fecha}</small></td>
                                    <td><span class="badge ${badgeClass}"><i class="fa-solid ${badgeIcon}"></i> ${m.concepto}</span></td>
                                    <td style="font-family: monospace; font-size: 0.78rem;">${m.referencia}</td>
                                    <td style="text-align: right; font-weight: 700;">${m.cantidad}</td>
                                    <td style="text-align: right;">$${m.costo_unitario.toFixed(2)}</td>
                                </tr>
                            `;
                        });
                    } else {
                        movimientosHtml = '<tr><td colspan="5" style="text-align: center; color: var(--text-muted);">Sin movimientos registrados aún.</td></tr>';
                    }

                    container.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border-color); padding-bottom: 0.85rem; margin-bottom: 1rem;">
                            <div>
                                <span class="badge badge-info">${p.categoria}</span>
                                <span class="badge ${p.estado === 'ACTIVO' ? 'badge-success' : 'badge-secondary'}">${p.estado}</span>
                                <h2 style="margin: 0.35rem 0 0.15rem 0; font-size: 1.25rem;">${p.nombre}</h2>
                                <div style="font-family: monospace; color: var(--text-muted); font-size: 0.85rem;">SKU: <strong>${p.codigo_principal}</strong> ${p.codigo_auxiliar ? ' | Aux: ' + p.codigo_auxiliar : ''}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Stock Total</div>
                                <div style="font-size: 1.6rem; font-weight: 800; color: ${p.stock_actual <= 0 ? 'var(--danger)' : 'var(--success)'};">${p.stock_actual} uds</div>
                            </div>
                        </div>

                        <!-- Summary Cards -->
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.65rem; margin-bottom: 1rem;">
                            <div style="background: var(--bg-muted); padding: 0.65rem; border-radius: var(--radius-sm); text-align: center;">
                                <div style="font-size: 0.72rem; color: var(--text-muted);">Costo Promedio</div>
                                <div style="font-size: 1.05rem; font-weight: 700;">$${p.costo_promedio.toFixed(2)}</div>
                            </div>
                            <div style="background: var(--bg-muted); padding: 0.65rem; border-radius: var(--radius-sm); text-align: center;">
                                <div style="font-size: 0.72rem; color: var(--text-muted);">Precio Venta</div>
                                <div style="font-size: 1.05rem; font-weight: 700; color: var(--success);">$${p.precio_unitario.toFixed(2)}</div>
                            </div>
                            <div style="background: var(--bg-muted); padding: 0.65rem; border-radius: var(--radius-sm); text-align: center;">
                                <div style="font-size: 0.72rem; color: var(--text-muted);">Precio c/ IVA (${p.iva_texto})</div>
                                <div style="font-size: 1.05rem; font-weight: 700; color: var(--primary);">$${p.precio_con_iva.toFixed(2)}</div>
                            </div>
                            <div style="background: var(--bg-muted); padding: 0.65rem; border-radius: var(--radius-sm); text-align: center;">
                                <div style="font-size: 0.72rem; color: var(--text-muted);">Margen Utilidad</div>
                                <div style="font-size: 1.05rem; font-weight: 700; color: var(--success);">${p.margen_porcentaje}% ($${p.margen_ganancia.toFixed(2)})</div>
                            </div>
                        </div>

                        <!-- Warehouse Distribution -->
                        <h3 style="font-size: 0.95rem; margin-bottom: 0.45rem;"><i class="fa-solid fa-warehouse text-primary"></i> Existencias por Bodega</h3>
                        <table class="custom-table" style="margin-bottom: 1rem;">
                            <thead>
                                <tr>
                                    <th>Bodega</th>
                                    <th style="text-align: right;">Stock Actual</th>
                                    <th style="text-align: right;">Stock Mínimo</th>
                                </tr>
                            </thead>
                            <tbody>${bodegasHtml}</tbody>
                        </table>

                        <!-- Recent Movements -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.45rem;">
                            <h3 style="font-size: 0.95rem; margin: 0;"><i class="fa-solid fa-list-check text-primary"></i> Últimos Movimientos Kardex</h3>
                            <a href="{{ url('/kardex') }}?producto_id=${p.id}" class="btn-card-action btn-primary btn-sm" style="font-size: 0.75rem;">
                                Ver Kardex Completo <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th>Referencia</th>
                                    <th style="text-align: right;">Cant.</th>
                                    <th style="text-align: right;">Costo</th>
                                </tr>
                            </thead>
                            <tbody>${movimientosHtml}</tbody>
                        </table>
                    `;
                }
            })
            .catch(err => {
                container.innerHTML = '<div style="color: var(--danger); text-align: center; padding: 2rem;">Error al cargar detalles del producto.</div>';
            });
    }

    function guardarCategoriaAjax(event) {
        event.preventDefault();
        const form = document.getElementById('formNuevaCategoria');
        const btn = document.getElementById('btnGuardarCategoria');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Guardando...';

        const formData = new FormData(form);

        fetch('{{ route("productos.categoriaStore") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Guardar Categoría';

            if (data.success) {
                const selectNuevo = document.getElementById('selectCategoriaNuevo');
                const selectEdit = document.getElementById('edit_categoria_id');
                const option = new Option(data.categoria.nombre, data.categoria.id, true, true);
                selectNuevo.add(option);
                if (selectEdit) selectEdit.add(new Option(data.categoria.nombre, data.categoria.id));

                form.reset();
                closeModal('modalNuevaCategoria');
                alert('Categoría "' + data.categoria.nombre + '" creada con éxito.');
            } else {
                alert('Error al guardar categoría: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Guardar Categoría';
            alert('Error de conexión al guardar categoría.');
        });
    }

    function ejecutarImportacionProductos(event) {
        event.preventDefault();
        const form = document.getElementById('formImportarExcelProductos');
        const fileInput = document.getElementById('inputExcelProductos');
        const btn = document.getElementById('btnEjecutarImportExcel');
        const resBox = document.getElementById('resultadoImportacionProductos');

        if (!fileInput.files || !fileInput.files[0]) {
            alert('Por favor seleccione un archivo Excel (.xlsx)');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Procesando Excel...';
        resBox.style.display = 'none';

        const formData = new FormData(form);

        fetch('{{ route("productos.importExcel") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(async (res) => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Importar Productos';

            let body = null;
            const contentType = res.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                body = await res.json();
            } else {
                const text = await res.text();
                body = { success: false, message: 'Respuesta del servidor: ' + text.substring(0, 300) };
            }

            if (res.ok && body.success) {
                resBox.style.display = 'block';
                resBox.style.background = 'var(--success-light)';
                resBox.style.borderLeft = '4px solid var(--success)';
                resBox.style.color = '#065f46';
                resBox.innerHTML = `<i class="fa-solid fa-circle-check"></i> <strong>¡Importación exitosa!</strong> ${body.message}<br><small>Recargando listado...</small>`;

                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                let errorMsg = body.message || 'Error al procesar el archivo Excel.';
                if (body.errors) {
                    const errList = Object.values(body.errors).flat().join('<br>');
                    errorMsg += `<br><small>${errList}</small>`;
                }
                resBox.style.display = 'block';
                resBox.style.background = 'var(--danger-light)';
                resBox.style.borderLeft = '4px solid var(--danger)';
                resBox.style.color = '#991b1b';
                resBox.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> <strong>Error:</strong> ${errorMsg}`;
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Importar Productos';
            resBox.style.display = 'block';
            resBox.style.background = 'var(--danger-light)';
            resBox.style.borderLeft = '4px solid var(--danger)';
            resBox.style.color = '#991b1b';
            resBox.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> Error de conexión: ${err.message}`;
        });
    }
</script>
@endpush
