@extends('layouts.app')

@section('title', 'Inventario y Productos')

@section('content')

<!-- PRODUCTOS HEADER -->
<div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1.15rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
        <div>
            <h1 style="margin-bottom: 0.15rem;"><i class="fa-solid fa-boxes-stacked text-primary"></i> Inventario & Catálogo de Productos</h1>
            <p style="margin: 0; font-size: 0.85rem;">Consulte existencias en bodega, precios de venta y administre su catálogo fácilmente.</p>
        </div>
        @if(auth()->user()->hasPermission('Productos', 'master'))
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

<!-- INVENTORY KPI SUMMARY -->
<div class="metrics-grid">
    <div class="metric-card">
        <div>
            <div class="metric-label">Total en Catálogo</div>
            <div class="metric-val">{{ count($productos) }} Ítems</div>
        </div>
        <div class="icon-box icon-blue"><i class="fa-solid fa-boxes-packing"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Stock Normal</div>
            <div class="metric-val" style="color: var(--success);">3 Productos</div>
        </div>
        <div class="icon-box icon-green"><i class="fa-solid fa-circle-check"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Stock Bajo</div>
            <div class="metric-val" style="color: var(--warning);">2 Productos</div>
        </div>
        <div class="icon-box icon-amber"><i class="fa-solid fa-triangle-exclamation"></i></div>
    </div>

    <div class="metric-card">
        <div>
            <div class="metric-label">Agotados</div>
            <div class="metric-val" style="color: var(--danger);">1 Producto</div>
        </div>
        <div class="icon-box icon-rose"><i class="fa-solid fa-ban"></i></div>
    </div>
</div>

<!-- PRODUCTOS TABLE -->
<div class="data-card">
    <div class="data-card-header">
        <h2><i class="fa-solid fa-table-list text-primary"></i> Lista de Productos en Inventario</h2>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <input type="text" id="searchTable" class="form-control" placeholder="Filtrar por nombre o código..." style="height: 32px; font-size: 0.82rem; width: 220px;" onkeyup="filterProductTable()">
        </div>
    </div>

    <div class="table-responsive">
        <table class="custom-table" id="tablaProductos">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre del Producto</th>
                    <th>Categoría</th>
                    <th style="text-align: right;">Stock Actual</th>
                    <th style="text-align: right;">Precio Venta</th>
                    <th style="text-align: right;">Costo Promedio</th>
                    <th>IVA SRI</th>
                    <th>Estado</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productos as $prod)
                <tr>
                    <td><strong style="font-family: monospace;">{{ $prod['codigo_principal'] }}</strong></td>
                    <td>
                        <strong style="color: var(--text-main);">{{ $prod['nombre'] }}</strong>
                    </td>
                    <td><span class="badge badge-info">{{ $prod['categoria'] }}</span></td>
                    <td style="text-align: right; font-weight: 700;">{{ $prod['stock_actual'] }}</td>
                    <td style="text-align: right; color: var(--success); font-weight: 700;">${{ number_format($prod['precio_unitario'], 2) }}</td>
                    <td style="text-align: right; color: var(--text-muted);">${{ number_format($prod['costo_promedio'], 2) }}</td>
                    <td>{{ $prod['iva'] }}</td>
                    <td>
                        @if($prod['estado_stock'] == 'EN_STOCK')
                            <span class="badge badge-success"><i class="fa-solid fa-check"></i> NORMAL</span>
                        @elseif($prod['estado_stock'] == 'STOCK_BAJO')
                            <span class="badge badge-warning"><i class="fa-solid fa-triangle-exclamation"></i> BAJO</span>
                        @else
                            <span class="badge badge-danger"><i class="fa-solid fa-circle-xmark"></i> AGOTADO</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <div style="display: inline-flex; gap: 0.35rem;">
                            @if(auth()->user()->hasPermission('Productos', 'master'))
                                <button class="btn-card-action btn-secondary btn-sm" onclick="alert('Modificar {{ $prod['nombre'] }}')">
                                    <i class="fa-solid fa-pen"></i> Editar
                                </button>
                            @else
                                <button class="btn-card-action btn-secondary btn-sm" style="opacity: 0.6;" onclick="alert('No tiene permisos para editar productos.')">
                                    <i class="fa-solid fa-pen"></i> Editar 🔒
                                </button>
                            @endif
                            <a href="{{ url('/kardex') }}" class="btn-card-action btn-primary btn-sm">
                                <i class="fa-solid fa-chart-line"></i> Kardex
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVO PRODUCTO -->
<div class="modal-backdrop" id="modalNuevoProducto">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-box text-primary"></i> Registrar Nuevo Producto</h2>
            <button class="btn-close" onclick="closeModal('modalNuevoProducto')">&times;</button>
        </div>
        <form action="{{ url('/productos') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Código Principal (Código de Barras / SKU)</label>
                <input type="text" name="codigo_principal" class="form-control" placeholder="Ej: PROD-009" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nombre Comercial del Producto</label>
                <input type="text" name="nombre" class="form-control" placeholder="Ej: Aceite de Eucalipto 100ml" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Categoría</label>
                    <select name="categoria" class="form-control">
                        @foreach($categorias as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo de Producto</label>
                    <select name="tipo_producto" class="form-control">
                        <option value="BIEN">BIEN (Físico con Stock)</option>
                        <option value="SERVICIO">SERVICIO (Sin Stock)</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Precio de Venta ($)</label>
                    <input type="number" step="0.01" name="precio_unitario" class="form-control" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Costo Promedio ($)</label>
                    <input type="number" step="0.01" name="costo_promedio" class="form-control" placeholder="0.00" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Impuesto IVA SRI</label>
                    <select name="codigo_iva" class="form-control">
                        <option value="2">IVA 15% (Tarifa General)</option>
                        <option value="0">IVA 0% (Exento / No Objeto)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Stock Mínimo Alerta</label>
                    <input type="number" name="stock_minimo" class="form-control" placeholder="5" value="5">
                </div>
            </div>

            <div style="display: flex; gap: 0.65rem; margin-top: 1.25rem;">
                <button type="submit" class="btn-card-action btn-success" style="flex: 1;">Guardar Producto</button>
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalNuevoProducto')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function filterProductTable() {
        const input = document.getElementById('searchTable').value.toLowerCase();
        const rows = document.querySelectorAll('#tablaProductos tbody tr');
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(input) ? '' : 'none';
        });
    }
</script>
@endpush
