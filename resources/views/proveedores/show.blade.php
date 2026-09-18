@extends('layouts.app')

@section('title', 'Detalle de Proveedor')

@section('content')

<!-- HEADER -->
<div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1.15rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
        <div>
            <h1 style="margin-bottom: 0.15rem;">
                <i class="fa-solid fa-building text-primary"></i> {{ $proveedor->razon_social }}
            </h1>
            <p style="margin: 0; font-size: 0.85rem;">
                <strong>{{ $proveedor->tipo_nombre }}:</strong> {{ $proveedor->identificacion }} |
                <strong>Teléfono:</strong> {{ $proveedor->telefono ?? 'N/A' }} |
                <strong>Correo:</strong> {{ $proveedor->correo ?? 'N/A' }}
            </p>
        </div>
        <div style="display: flex; gap: 0.65rem;">
            <a href="{{ route('proveedores.index') }}" class="btn-card-action btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
            <button class="btn-card-action btn-primary btn-sm" onclick="openPdfModal()">
                <i class="fa-solid fa-file-pdf"></i> Exportar PDF
            </button>
        </div>
    </div>
</div>

<!-- FINANCIAL SUMMARY -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="data-card" style="padding: 1rem; text-align: center; border-left: 4px solid var(--primary);">
        <h3 style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #64748b;">Total Comprado</h3>
        <p style="margin: 0; font-size: 1.5rem; font-weight: bold; color: var(--primary);">${{ number_format($resumen->total_comprado ?? 0, 2) }}</p>
    </div>
    <div class="data-card" style="padding: 1rem; text-align: center; border-left: 4px solid var(--success);">
        <h3 style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #64748b;">Nro. Facturas</h3>
        <p style="margin: 0; font-size: 1.5rem; font-weight: bold; color: var(--success);">{{ $resumen->total_facturas ?? 0 }}</p>
    </div>
    <div class="data-card" style="padding: 1rem; text-align: center; border-left: 4px solid var(--warning);">
        <h3 style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #64748b;">Total IVA Pagado</h3>
        <p style="margin: 0; font-size: 1.5rem; font-weight: bold; color: var(--warning);">${{ number_format($resumen->total_iva ?? 0, 2) }}</p>
    </div>
    <div class="data-card" style="padding: 1rem; text-align: center; border-left: 4px solid #8b5cf6;">
        <h3 style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #64748b;">Promedio por Factura</h3>
        <p style="margin: 0; font-size: 1.5rem; font-weight: bold; color: #8b5cf6;">${{ number_format($resumen->promedio_factura ?? 0, 2) }}</p>
    </div>
    <div class="data-card" style="padding: 1rem; text-align: center; border-left: 4px solid #64748b;">
        <h3 style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #64748b;">Última Compra</h3>
        <p style="margin: 0; font-size: 1.1rem; font-weight: bold; color: #334155; margin-top: 0.4rem;">{{ $resumen->ultima_compra ? \Carbon\Carbon::parse($resumen->ultima_compra)->format('d/m/Y') : 'N/A' }}</p>
    </div>
</div>

<!-- HISTORIAL DE COMPRAS -->
<div class="data-card">
    <div class="data-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        <h2><i class="fa-solid fa-list text-primary"></i> Historial de Compras</h2>
    </div>
    
    <!-- FILTROS -->
    <div style="padding: 1rem; background-color: #f8fafc; border-bottom: 1px solid var(--border-color);">
        <form action="{{ route('proveedores.show', $proveedor->id) }}" method="GET" style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 150px;">
                <label class="form-label" style="font-size: 0.8rem;">Fecha Desde</label>
                <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="form-control">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label class="form-label" style="font-size: 0.8rem;">Fecha Hasta</label>
                <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="form-control">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label class="form-label" style="font-size: 0.8rem;">Nro. Factura</label>
                <input type="text" name="numero_factura" value="{{ request('numero_factura') }}" class="form-control" placeholder="Ej: 001-001...">
            </div>
            <div>
                <button type="submit" class="btn-card-action btn-primary"><i class="fa-solid fa-filter"></i> Filtrar</button>
                @if(request('fecha_desde') || request('fecha_hasta') || request('numero_factura'))
                    <a href="{{ route('proveedores.show', $proveedor->id) }}" class="btn-card-action btn-secondary">Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="custom-table" style="margin: 0;">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Factura Nro.</th>
                    <th>Registrado Por</th>
                    <th style="text-align: right;">Subtotal</th>
                    <th style="text-align: right;">IVA</th>
                    <th style="text-align: right;">Total</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($compras as $compra)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($compra->fecha_emision)->format('d/m/Y') }}</td>
                    <td><strong style="font-family: monospace;">{{ $compra->numero_factura }}</strong></td>
                    <td>{{ $compra->usuario->nombres ?? 'Sistema' }}</td>
                    <td style="text-align: right;">${{ number_format($compra->subtotal_sin_impuestos, 2) }}</td>
                    <td style="text-align: right;">${{ number_format($compra->iva, 2) }}</td>
                    <td style="text-align: right; color: var(--primary);"><strong>${{ number_format($compra->total, 2) }}</strong></td>
                    <td style="text-align: center;">
                        <button type="button" onclick="showFacturaDetalle({{ $compra->id }})" class="btn-card-action btn-secondary btn-sm" title="Ver Detalle">
                            <i class="fa-solid fa-eye"></i> Detalle
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem;">No se encontraron compras en el historial.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- PAGINATION -->
    <div style="margin-top: 1rem;">
        {{ $compras->appends(request()->query())->links() }}
    </div>
</div>

<!-- MODAL DETALLE FACTURA -->
<div class="modal-backdrop" id="modalDetalleFactura">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-file-invoice text-primary"></i> Detalle de Factura <span id="detalle_num_factura"></span></h2>
            <button class="btn-close" onclick="closeModal('modalDetalleFactura')">&times;</button>
        </div>
        <div style="padding: 1rem 0;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.9rem;">
                <div><strong>Fecha:</strong> <span id="detalle_fecha"></span></div>
                <div><strong>Total:</strong> $<span id="detalle_total"></span></div>
            </div>
            
            <table class="custom-table" style="font-size: 0.85rem;">
                <thead style="background-color: #f8fafc;">
                    <tr>
                        <th>Producto</th>
                        <th style="text-align: right;">Cantidad</th>
                        <th style="text-align: right;">Costo Unit.</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody id="detalle_productos_tbody">
                    <!-- JS rellena esto -->
                </tbody>
            </table>
            
            <div style="margin-top: 1rem; background-color: #f8fafc; padding: 1rem; border-radius: var(--radius-md); font-size: 0.9rem;">
                <strong>Observaciones:</strong>
                <p id="detalle_observaciones" style="margin: 0.5rem 0 0 0; color: #475569;"></p>
            </div>
        </div>
        <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
            <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalDetalleFactura')">Cerrar</button>
        </div>
    </div>
</div>

<!-- MODAL EXPORTAR PDF -->
<div class="modal-backdrop" id="modalPdfProveedor">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-file-pdf text-primary"></i> Generar Reporte de Proveedor</h2>
            <button class="btn-close" onclick="closeModal('modalPdfProveedor')">&times;</button>
        </div>
        <form id="formPdfProveedor" action="{{ route('proveedores.exportPdf', $proveedor->id) }}" method="POST" target="_blank">
            @csrf
            
            <div class="form-group" style="margin-top: 1rem;">
                <label class="form-label">Período de Facturas:</label>
                <select name="periodo" class="form-control">
                    <option value="5">Últimas 5 facturas</option>
                    <option value="10">Últimas 10 facturas</option>
                    <option value="20">Últimas 20 facturas</option>
                    <option value="50">Últimas 50 facturas</option>
                    <option value="all" selected>Todas las facturas</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-top: 1rem;">
                <label class="form-label">Información a incluir:</label>
                <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="incluir_general" value="1" checked>
                        <span>Información general del proveedor</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="incluir_resumen" value="1" checked>
                        <span>Resumen financiero</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="incluir_facturas" value="1" checked>
                        <span>Listado de facturas</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="incluir_detalles" value="1">
                        <span>Detalle de productos de las facturas (Puede ser muy extenso)</span>
                    </label>
                </div>
            </div>
            
            <div style="display: flex; gap: 0.65rem; margin-top: 1.5rem;">
                <button type="submit" class="btn-card-action btn-primary" style="flex: 1;" onclick="setTimeout(() => closeModal('modalPdfProveedor'), 1000)">Generar PDF</button>
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalPdfProveedor')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Pasar los datos con las relaciones ya cargadas para JS
    window.comprasData = {!! json_encode($compras->items()) !!};

    function showFacturaDetalle(id) {
        let compra = window.comprasData.find(c => c.id === id);
        if (!compra) return;
        
        document.getElementById('detalle_num_factura').textContent = compra.numero_factura;
        
        let fecha = new Date(compra.fecha_emision + 'T00:00:00');
        document.getElementById('detalle_fecha').textContent = fecha.toLocaleDateString();
        
        document.getElementById('detalle_total').textContent = parseFloat(compra.total).toFixed(2);
        document.getElementById('detalle_observaciones').textContent = compra.observaciones || 'Sin observaciones.';
        
        let tbody = document.getElementById('detalle_productos_tbody');
        tbody.innerHTML = '';
        
        if (compra.detalles && compra.detalles.length > 0) {
            compra.detalles.forEach(d => {
                let tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${d.producto ? d.producto.nombre : 'Producto ' + d.producto_id}</td>
                    <td style="text-align: right;">${parseFloat(d.cantidad).toFixed(2)}</td>
                    <td style="text-align: right;">$${parseFloat(d.costo_unitario).toFixed(4)}</td>
                    <td style="text-align: right;"><strong>$${parseFloat(d.costo_total).toFixed(2)}</strong></td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No hay detalles registrados.</td></tr>';
        }
        
        openModal('modalDetalleFactura');
    }
    
    function openPdfModal() {
        openModal('modalPdfProveedor');
    }
</script>
@endpush
