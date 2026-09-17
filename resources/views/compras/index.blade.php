@extends('layouts.app')

@section('title', 'Compras y Registro de Entradas')

@section('content')

<!-- COMPRAS HEADER -->
<div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1.15rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
        <div>
            <h1 style="margin-bottom: 0.15rem;"><i class="fa-solid fa-cart-shopping text-primary"></i> Registro de Compras & Entradas</h1>
            <p style="margin: 0; font-size: 0.85rem;">Ingrese facturas de compra de sus proveedores para aumentar el stock automáticamente en su Kardex.</p>
        </div>
        <button class="btn-card-action btn-success btn-sm" onclick="openModal('modalNuevaCompra')">
            <i class="fa-solid fa-cart-plus"></i> Registrar Factura de Compra
        </button>
    </div>
</div>

<!-- COMPRAS TABLE VIEW -->
<div class="data-card">
    <div class="data-card-header">
        <h2><i class="fa-solid fa-truck-ramp-box text-primary"></i> Historial de Compras y Entradas a Bodega</h2>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <input type="text" id="searchCompras" class="form-control" placeholder="Buscar por proveedor o factura..." style="height: 32px; font-size: 0.82rem; width: 240px;" onkeyup="filterComprasTable()">
        </div>
    </div>

    <div class="table-responsive">
        <table class="custom-table" id="tablaCompras">
            <thead>
                <tr>
                    <th>Fecha Ingreso</th>
                    <th>Nº Factura Proveedor</th>
                    <th>Proveedor</th>
                    <th>Bodega Destino</th>
                    <th style="text-align: center;">Ítems</th>
                    <th style="text-align: right;">Total Compra</th>
                    <th>Tipo Registro</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($compras as $compra)
                <tr>
                    <td><strong>{{ $compra['fecha'] }}</strong></td>
                    <td><strong style="font-family: monospace;">{{ $compra['referencia_factura'] }}</strong></td>
                    <td><strong>{{ $compra['proveedor'] }}</strong></td>
                    <td><span class="badge badge-info"><i class="fa-solid fa-warehouse"></i> {{ $compra['bodega'] }}</span></td>
                    <td style="text-align: center; font-weight: 600;">{{ $compra['items_count'] }} productos</td>
                    <td style="text-align: right; color: var(--success); font-weight: 700;">${{ number_format($compra['total_compra'], 2) }}</td>
                    <td>
                        <span class="badge badge-success"><i class="fa-solid fa-arrow-down"></i> INGRESO BODEGA</span>
                    </td>
                    <td style="text-align: center;">
                        <button class="btn-card-action btn-secondary btn-sm" onclick="alert('Viendo detalle de compra {{ $compra['referencia_factura'] }}')">
                            <i class="fa-solid fa-eye"></i> Detalle
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVA COMPRA -->
<div class="modal-backdrop" id="modalNuevaCompra">
    <div class="modal-content" style="max-width: 650px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-cart-plus text-primary"></i> Ingresar Comprobante de Compra</h2>
            <button class="btn-close" onclick="closeModal('modalNuevaCompra')">&times;</button>
        </div>
        <form action="{{ url('/compras') }}" method="POST">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Seleccionar Proveedor</label>
                    <select name="proveedor_id" class="form-control">
                        @foreach($proveedores as $idx => $prov)
                        <option value="{{ $idx + 1 }}">{{ $prov }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nº Factura de Proveedor</label>
                    <input type="text" name="numero_factura" class="form-control" placeholder="Ej: 002-005-00012486" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label">Bodega de Destino</label>
                    <select name="bodega_id" class="form-control">
                        @foreach($bodegas as $idx => $bod)
                        <option value="{{ $idx + 1 }}">{{ $bod }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha del Comprobante</label>
                    <input type="date" name="fecha_emision" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>

            <h3 style="margin: 0.85rem 0 0.55rem 0; font-size: 0.95rem;"><i class="fa-solid fa-list-check text-primary"></i> Ítems a Ingresar al Inventario</h3>

            <div style="background: #f8fafc; padding: 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); margin-bottom: 1rem;">
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 0.5rem; align-items: end;">
                    <div>
                        <label class="form-label" style="font-size: 0.775rem;">Producto</label>
                        <select name="producto_id" class="form-control" style="font-size: 0.825rem;">
                            <option value="1">Colágeno Hidrolizado 500g</option>
                            <option value="2">Vitamina C 1000mg</option>
                            <option value="3">Jarabe de Totumo 250ml</option>
                            <option value="4">Miel de Abeja Orgánica 1Kg</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.775rem;">Cantidad</label>
                        <input type="number" name="cantidad" class="form-control" value="10" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.775rem;">Costo Unit. ($)</label>
                        <input type="number" step="0.01" name="costo_unitario" class="form-control" value="15.00" required>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 0.65rem; margin-top: 1.25rem;">
                <button type="submit" class="btn-card-action btn-success" style="flex: 1;">Guardar y Aumentar Stock</button>
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalNuevaCompra')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function filterComprasTable() {
        const input = document.getElementById('searchCompras').value.toLowerCase();
        const rows = document.querySelectorAll('#tablaCompras tbody tr');
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(input) ? '' : 'none';
        });
    }
</script>
@endpush
