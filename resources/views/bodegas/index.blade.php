@extends('layouts.app')

@section('title', 'Gestión de Bodegas y Almacenes')

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
        <strong><i class="fa-solid fa-circle-exclamation"></i> Verifique los siguientes errores:</strong>
        <ul style="margin: 0.35rem 0 0 1.25rem;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- HEADER -->
<div class="data-card" style="padding: 0.95rem 1.35rem; margin-bottom: 1.15rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
        <div>
            <h1 style="margin-bottom: 0.2rem; font-size: 1.35rem;"><i class="fa-solid fa-warehouse text-primary"></i> Control de Bodegas & Almacenes</h1>
            <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Administre las ubicaciones físicas de almacenamiento, controle existencias y consulte la valorización del inventario por bodega.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="{{ route('bodegas.export') }}" class="btn-card-action btn-secondary btn-sm" title="Exportar listado a CSV">
                <i class="fa-solid fa-file-csv text-success"></i> Exportar CSV
            </a>
            @if(auth()->user()->hasPermission('Productos', 'master') || auth()->user()->hasPermission('Bodegas', 'master'))
                <button class="btn-card-action btn-success btn-sm" onclick="abrirModalNuevaBodega()">
                    <i class="fa-solid fa-plus-circle"></i> Nueva Bodega
                </button>
            @endif
        </div>
    </div>
</div>

<!-- KPI SUMMARY -->
<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.15rem;">
    <div class="metric-card">
        <div>
            <div class="metric-label">Total Bodegas</div>
            <div class="metric-val">{{ $kpis['total_bodegas'] }} <small style="font-size: 0.75rem; font-weight: normal; color: var(--text-muted);">({{ $kpis['bodegas_activas'] }} activas)</small></div>
        </div>
        <div class="icon-box icon-blue"><i class="fa-solid fa-warehouse"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Unidades en Stock</div>
            <div class="metric-val" style="color: var(--primary);">{{ number_format($kpis['total_stock'], 0) }} <small style="font-size: 0.75rem; font-weight: normal; color: var(--text-muted);">uds</small></div>
        </div>
        <div class="icon-box icon-purple"><i class="fa-solid fa-boxes-stacked"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Valor Inv. (Al Costo)</div>
            <div class="metric-val" style="color: var(--success);">${{ number_format($kpis['valor_costo'], 2) }}</div>
        </div>
        <div class="icon-box icon-green"><i class="fa-solid fa-vault"></i></div>
    </div>
</div>

<!-- FILTROS & TOOLBAR -->
<div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1.15rem;">
    <form method="GET" action="{{ route('bodegas.index') }}" id="filterForm">
        <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 0.75rem; align-items: flex-end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-size: 0.78rem;">Buscar Bodega</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    <input type="text" name="search" class="form-control" placeholder="Código, nombre, ubicación o responsable..." value="{{ request('search') }}" style="padding-left: 2.1rem; height: 35px; font-size: 0.85rem;">
                </div>
            </div>

            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-size: 0.78rem;">Estado</label>
                <select name="estado" class="form-control" style="height: 35px; font-size: 0.85rem;" onchange="this.form.submit()">
                    <option value="todos" {{ request('estado') === 'todos' ? 'selected' : '' }}>Todos los Estados</option>
                    <option value="ACTIVO" {{ request('estado') === 'ACTIVO' ? 'selected' : '' }}>Activas</option>
                    <option value="INACTIVO" {{ request('estado') === 'INACTIVO' ? 'selected' : '' }}>Inactivas</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.4rem;">
                <button type="submit" class="btn-card-action btn-primary" style="height: 35px;">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
                @if(request()->hasAny(['search', 'estado']))
                    <a href="{{ route('bodegas.index') }}" class="btn-card-action btn-secondary" style="height: 35px;" title="Limpiar Filtros">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>
</div>

<!-- LISTADO DE BODEGAS -->
<div class="data-card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table class="custom-table" style="margin: 0;">
            <thead>
                <tr>
                    <th style="width: 110px;">Código</th>
                    <th>Bodega / Ubicación</th>
                    <th>Responsable</th>
                    <th style="text-align: right;">Stock Almacenado</th>
                    <th style="text-align: right;">Valor Inv. Costo</th>
                    <th style="text-align: center; width: 100px;">Estado</th>
                    <th style="text-align: center; width: 160px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bodegas as $b)
                    <tr>
                        <td>
                            <strong style="font-family: monospace; font-size: 0.85rem; color: var(--primary);">{{ $b['codigo'] }}</strong>
                            @if($b['es_principal'])
                                <div><span class="badge badge-warning" style="font-size: 0.65rem; padding: 2px 5px;"><i class="fa-solid fa-star"></i> Principal</span></div>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-main);">
                                <i class="fa-solid fa-warehouse" style="color: {{ $b['es_principal'] ? '#d97706' : 'var(--primary)' }}; margin-right: 4px;"></i>
                                {{ $b['nombre'] }}
                            </div>
                            <div style="font-size: 0.78rem; color: var(--text-muted);"><i class="fa-solid fa-location-dot"></i> {{ $b['ubicacion'] }}</div>
                            @if(!empty($b['descripcion']))
                                <div style="font-size: 0.72rem; color: var(--text-muted); font-style: italic;">{{ $b['descripcion'] }}</div>
                            @endif
                        </td>
                        <td>
                            <div><strong>{{ $b['responsable'] }}</strong></div>
                            @if(!empty($b['telefono']))
                                <div style="font-size: 0.76rem; color: var(--text-muted);"><i class="fa-solid fa-phone"></i> {{ $b['telefono'] }}</div>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <div style="font-weight: 700; font-size: 0.95rem; color: {{ $b['total_stock'] > 0 ? 'var(--primary)' : 'var(--text-muted)' }};">
                                {{ number_format($b['total_stock'], 0) }} uds
                            </div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $b['total_productos'] }} prods con stock</div>
                        </td>
                        <td style="text-align: right;">
                            <div style="font-weight: 700; color: var(--success); font-size: 0.95rem;">${{ number_format($b['valor_costo'], 2) }}</div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">PVP: ${{ number_format($b['valor_venta'], 2) }}</div>
                        </td>
                        <td style="text-align: center;">
                            @if($b['estado'] === 'ACTIVO')
                                <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> ACTIVO</span>
                            @else
                                <span class="badge badge-secondary"><i class="fa-solid fa-circle-pause"></i> INACTIVO</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 0.35rem; justify-content: center;">
                                <button class="btn-card-action btn-secondary btn-sm" onclick="verFichaBodega({{ $b['id'] }})" title="Ver existencias y movimientos de esta bodega">
                                    <i class="fa-solid fa-eye text-primary"></i>
                                </button>
                                @if(auth()->user()->hasPermission('Productos', 'master') || auth()->user()->hasPermission('Bodegas', 'master'))
                                    <button class="btn-card-action btn-secondary btn-sm" onclick="abrirModalEditarBodega({{ $b['id'] }})" title="Editar datos de la bodega">
                                        <i class="fa-solid fa-pen-to-square text-warning"></i>
                                    </button>
                                    @if(!$b['es_principal'])
                                        <button class="btn-card-action btn-secondary btn-sm" onclick="toggleEstadoBodega({{ $b['id'] }})" title="{{ $b['estado'] === 'ACTIVO' ? 'Desactivar' : 'Activar' }}">
                                            <i class="fa-solid {{ $b['estado'] === 'ACTIVO' ? 'fa-toggle-on text-success' : 'fa-toggle-off text-muted' }}"></i>
                                        </button>
                                        @if($b['is_deletable'])
                                            <form action="{{ route('bodegas.destroy', $b['id']) }}" method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar la bodega \'{{ $b['nombre'] }}\'?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-card-action btn-secondary btn-sm" title="Eliminar bodega sin movimientos" style="color: var(--danger);">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            <i class="fa-solid fa-warehouse text-muted" style="font-size: 2.5rem; margin-bottom: 0.75rem; display: block;"></i>
                            <strong>No se encontraron bodegas registradas con los filtros seleccionados.</strong>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: NUEVA BODEGA                                     -->
<!-- ======================================================= -->
<div class="modal-backdrop" id="modalNuevaBodega">
    <div class="modal-content" style="max-width: 540px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-warehouse text-primary"></i> Registrar Nueva Bodega</h2>
            <button class="btn-close" onclick="closeModal('modalNuevaBodega')">&times;</button>
        </div>
        <form action="{{ route('bodegas.store') }}" method="POST">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Código</label>
                    <input type="text" name="codigo" class="form-control" placeholder="Ej: BOD-03" style="font-family: monospace;">
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre de Bodega <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Bodega Sucursal Sur" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Ubicación / Dirección</label>
                <input type="text" name="ubicacion" class="form-control" placeholder="Ej: Av. Maldonado y El Beaterio N12-45">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Responsable / Encargado</label>
                    <input type="text" name="responsable" class="form-control" placeholder="Ej: Juan Pérez">
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono Contacto</label>
                    <input type="text" name="telefono" class="form-control" placeholder="Ej: 0991234567">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Descripción / Observaciones</label>
                <textarea name="descripcion" class="form-control" rows="2" placeholder="Detalles adicionales sobre el tipo de almacenamiento"></textarea>
            </div>

            <div class="form-group" style="margin-top: 0.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer;">
                    <input type="checkbox" name="es_principal" value="1" style="width: 1.1rem; height: 1.1rem;">
                    <span><strong>Establecer como Bodega Principal</strong> (Recibirá inventario por defecto)</span>
                </label>
            </div>

            <div style="display: flex; gap: 0.65rem; margin-top: 1.25rem;">
                <button type="submit" class="btn-card-action btn-success" style="flex: 1;">
                    <i class="fa-solid fa-save"></i> Guardar Bodega
                </button>
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalNuevaBodega')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: EDITAR BODEGA                                    -->
<!-- ======================================================= -->
<div class="modal-backdrop" id="modalEditarBodega">
    <div class="modal-content" style="max-width: 540px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-pen-to-square text-warning"></i> Editar Bodega</h2>
            <button class="btn-close" onclick="closeModal('modalEditarBodega')">&times;</button>
        </div>
        <form id="formEditarBodega" method="POST">
            @csrf
            @method('PUT')
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Código</label>
                    <input type="text" name="codigo" id="edit_codigo" class="form-control" style="font-family: monospace;">
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre de Bodega <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Ubicación / Dirección</label>
                <input type="text" name="ubicacion" id="edit_ubicacion" class="form-control">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Responsable / Encargado</label>
                    <input type="text" name="responsable" id="edit_responsable" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono Contacto</label>
                    <input type="text" name="telefono" id="edit_telefono" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Descripción / Observaciones</label>
                <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="2"></textarea>
            </div>

            <div class="form-group" style="margin-top: 0.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer;">
                    <input type="checkbox" name="es_principal" id="edit_es_principal" value="1" style="width: 1.1rem; height: 1.1rem;">
                    <span><strong>Bodega Principal del Sistema</strong></span>
                </label>
            </div>

            <div style="display: flex; gap: 0.65rem; margin-top: 1.25rem;">
                <button type="submit" class="btn-card-action btn-primary" style="flex: 1;">
                    <i class="fa-solid fa-save"></i> Guardar Cambios
                </button>
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalEditarBodega')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL: DETALLE DE EXISTENCIAS DE BODEGA                 -->
<!-- ======================================================= -->
<div class="modal-backdrop" id="modalDetalleBodega">
    <div class="modal-content" style="max-width: 820px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-warehouse text-primary"></i> Existencias en Bodega</h2>
            <button class="btn-close" onclick="closeModal('modalDetalleBodega')">&times;</button>
        </div>
        <div id="detalleBodegaContenido" style="padding: 0.5rem 0;">
            <div style="text-align: center; padding: 2rem;"><i class="fa-solid fa-circle-notch fa-spin text-primary" style="font-size: 2rem;"></i></div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function abrirModalNuevaBodega() {
        openModal('modalNuevaBodega');
    }

    function abrirModalEditarBodega(id) {
        fetch(`{{ url('/bodegas') }}/${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const b = data.bodega;
                    document.getElementById('formEditarBodega').action = `{{ url('/bodegas') }}/${b.id}`;
                    document.getElementById('edit_codigo').value = b.codigo || '';
                    document.getElementById('edit_nombre').value = b.nombre || '';
                    document.getElementById('edit_ubicacion').value = b.ubicacion || '';
                    document.getElementById('edit_responsable').value = b.responsable || '';
                    document.getElementById('edit_telefono').value = b.telefono || '';
                    document.getElementById('edit_descripcion').value = b.descripcion || '';
                    document.getElementById('edit_es_principal').checked = b.es_principal;
                    openModal('modalEditarBodega');
                }
            })
            .catch(err => alert('Error al cargar datos de la bodega.'));
    }

    function verFichaBodega(id) {
        const container = document.getElementById('detalleBodegaContenido');
        container.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fa-solid fa-circle-notch fa-spin text-primary" style="font-size: 2rem;"></i></div>';
        openModal('modalDetalleBodega');

        fetch(`{{ url('/bodegas') }}/${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const b = data.bodega;
                    let prodsHtml = '';

                    if (b.productos && b.productos.length > 0) {
                        b.productos.forEach(p => {
                            const badgeColor = p.stock <= 0 ? 'badge-danger' : (p.stock <= p.minimo ? 'badge-warning' : 'badge-success');
                            prodsHtml += `
                                <tr>
                                    <td><strong style="font-family: monospace; font-size: 0.8rem;">${p.codigo}</strong></td>
                                    <td>
                                        <strong>${p.nombre}</strong>
                                        <div style="font-size: 0.72rem; color: var(--text-muted);">${p.categoria}</div>
                                    </td>
                                    <td style="text-align: right; font-weight: 700;">
                                        <span class="badge ${badgeColor}">${p.stock} uds</span>
                                    </td>
                                    <td style="text-align: right; color: var(--text-muted);">${p.minimo} uds</td>
                                    <td style="text-align: right;">$${p.costo_unitario.toFixed(2)}</td>
                                    <td style="text-align: right; font-weight: 700; color: var(--success);">$${p.valor_costo.toFixed(2)}</td>
                                </tr>
                            `;
                        });
                    } else {
                        prodsHtml = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No hay productos registrados en esta bodega.</td></tr>';
                    }

                    container.innerHTML = `
                        <div style="background: var(--bg-muted); padding: 0.85rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Bodega:</div>
                                <strong style="font-size: 1.05rem; color: var(--text-main);">${b.nombre}</strong>
                                <div style="font-family: monospace; font-size: 0.8rem; color: var(--primary);">Código: ${b.codigo} ${b.es_principal ? '★ Principal' : ''}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><i class="fa-solid fa-location-dot"></i> ${b.ubicacion}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Responsable:</div>
                                <strong>${b.responsable}</strong>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><i class="fa-solid fa-phone"></i> ${b.telefono}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Stock Total Almacenado:</div>
                                <div style="font-size: 1.35rem; font-weight: 800; color: var(--primary);">${b.total_stock} uds</div>
                                <div style="font-size: 0.8rem; color: var(--success); font-weight: 700;">Valor: $${b.valor_costo.toFixed(2)}</div>
                            </div>
                        </div>

                        <!-- BUSCADOR DE PRODUCTOS EN ESTA BODEGA -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <h3 style="font-size: 0.95rem; margin: 0;"><i class="fa-solid fa-boxes-stacked text-primary"></i> Existencias Físicas</h3>
                                <span id="contadorProdsBodega" class="badge badge-secondary" style="font-size: 0.75rem;">${b.productos.length} ítems</span>
                            </div>
                            <div style="position: relative; width: 230px;">
                                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); font-size: 0.75rem; color: var(--text-muted);"></i>
                                <input type="text" id="buscadorProdsBodega" class="form-control form-control-sm" placeholder="Buscar por código o nombre..." oninput="filtrarProdsBodega(this.value)" style="padding-left: 1.85rem; font-size: 0.78rem; height: 30px;">
                            </div>
                        </div>

                        <div class="table-responsive" style="max-height: 280px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                            <table class="custom-table" style="margin: 0;" id="tablaProdsBodega">
                                <thead>
                                    <tr style="background: var(--bg-muted);">
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th style="text-align: right;">Stock Actual</th>
                                        <th style="text-align: right;">Stock Mín.</th>
                                        <th style="text-align: right;">Costo Unit.</th>
                                        <th style="text-align: right;">Total Costo</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyProdsBodega">${prodsHtml}</tbody>
                            </table>
                        </div>
                    `;
                }
            })
            .catch(err => {
                container.innerHTML = '<div style="color: var(--danger); text-align: center; padding: 2rem;">Error al cargar detalle de la bodega.</div>';
            });
    }

    function filtrarProdsBodega(query) {
        query = (query || '').toLowerCase().trim();
        const rows = document.querySelectorAll('#tbodyProdsBodega tr');
        let visibles = 0;
        let totalValidas = 0;

        rows.forEach(tr => {
            if (tr.id === 'noResultsProdsBodega') return;
            totalValidas++;
            const text = tr.innerText.toLowerCase();
            const coincide = !query || text.includes(query);
            tr.style.display = coincide ? '' : 'none';
            if (coincide) visibles++;
        });

        const badge = document.getElementById('contadorProdsBodega');
        if (badge) {
            badge.innerText = query ? `${visibles} de ${totalValidas} ítems` : `${totalValidas} ítems`;
        }

        let noResults = document.getElementById('noResultsProdsBodega');
        if (visibles === 0 && totalValidas > 0) {
            if (!noResults) {
                noResults = document.createElement('tr');
                noResults.id = 'noResultsProdsBodega';
                noResults.innerHTML = `<td colspan="6" style="text-align: center; color: var(--text-muted); padding: 1.5rem;"><i class="fa-solid fa-magnifying-glass"></i> No se encontraron productos que coincidan con "<strong>${query}</strong>".</td>`;
                document.getElementById('tbodyProdsBodega').appendChild(noResults);
            }
        } else if (noResults) {
            noResults.remove();
        }
    }

    function toggleEstadoBodega(id) {
        if (!confirm('¿Desea cambiar el estado activo/inactivo de esta bodega?')) return;

        fetch(`{{ url('/bodegas') }}/${id}/toggle-estado`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'No se pudo cambiar el estado de la bodega.');
            }
        })
        .catch(err => alert('Error de conexión al cambiar el estado.'));
    }
</script>
@endpush
