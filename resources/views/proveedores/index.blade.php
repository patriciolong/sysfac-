@extends('layouts.app')

@section('title', 'Gestión de Proveedores')

@section('content')

<!-- PROVEEDORES HEADER BAR -->
<div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1.15rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
        <div>
            <h1 style="margin-bottom: 0.15rem;"><i class="fa-solid fa-truck-field text-primary"></i> Directorio de Proveedores</h1>
            <p style="margin: 0; font-size: 0.85rem;">Administre y registre los datos de sus proveedores para la gestión de compras.</p>
        </div>
        <div style="display: flex; gap: 0.65rem;">
            <a href="{{ route('proveedores.export', ['search' => request('search')]) }}" class="btn-card-action btn-secondary btn-sm">
                <i class="fa-solid fa-file-excel"></i> Exportar
            </a>
            <button class="btn-card-action btn-success btn-sm" onclick="openModal('modalNuevoProveedor')">
                <i class="fa-solid fa-user-plus"></i> Crear Nuevo Proveedor
            </button>
        </div>
    </div>
</div>

<!-- ALERTS -->
@if(session('success'))
    <div style="padding: 1rem; margin-bottom: 1rem; background-color: #d1fae5; color: #065f46; border-radius: var(--radius-md); border-left: 4px solid var(--success);">
        <i class="fa-solid fa-check-circle"></i> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div style="padding: 1rem; margin-bottom: 1rem; background-color: #fee2e2; color: #991b1b; border-radius: var(--radius-md); border-left: 4px solid var(--danger);">
        <i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}
    </div>
@endif
@if($errors->any())
    <div style="padding: 1rem; margin-bottom: 1rem; background-color: #fee2e2; color: #991b1b; border-radius: var(--radius-md); border-left: 4px solid var(--danger);">
        <strong><i class="fa-solid fa-circle-exclamation"></i> Error de validación:</strong>
        <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- PROVEEDORES TABLE VIEW -->
<div class="data-card">
    <div class="data-card-header">
        <h2><i class="fa-solid fa-address-book text-primary"></i> Directorio y Registro de Proveedores</h2>
        
        <form action="{{ route('proveedores.index') }}" method="GET" style="display: flex; gap: 0.5rem;">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por RUC, nombre o correo..." style="width: 300px;">
            <button type="submit" class="btn-card-action btn-primary"><i class="fa-solid fa-search"></i> Buscar</button>
            @if(request('search'))
                <a href="{{ route('proveedores.index') }}" class="btn-card-action btn-secondary">Limpiar</a>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="custom-table" id="tablaProveedores">
            <thead>
                <tr>
                    <th>Doc.</th>
                    <th>Identificación</th>
                    <th>Razón Social</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($proveedores as $proveedor)
                <tr>
                    <td>
                        <span class="badge {{ $proveedor['tipo_identificacion'] == '04' ? 'badge-warning' : 'badge-info' }}">
                            {{ $proveedor['tipo_nombre'] }}
                        </span>
                    </td>
                    <td><strong style="font-family: monospace;">{{ $proveedor['identificacion'] }}</strong></td>
                    <td><strong>{{ $proveedor['razon_social'] }}</strong></td>
                    <td><i class="fa-solid fa-envelope text-primary"></i> {{ $proveedor['correo'] }}</td>
                    <td>{{ $proveedor['telefono'] }}</td>
                    <td style="color: var(--text-muted); font-size: 0.82rem;">{{ Str::limit($proveedor['direccion'], 30) }}</td>
                    <td>
                        <div style="display: flex; justify-content: center; gap: 0.4rem;">
                            <!-- Ver Compras -->
                            <a href="{{ route('proveedores.show', $proveedor['id']) }}" class="btn-card-action btn-secondary btn-sm" title="Ver Compras y Resumen" style="background-color: #6366f1; border-color: #6366f1; color: white;">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <!-- Exportar PDF (Abre Modal de Config) -->
                            <button type="button" onclick="openPdfModal({{ $proveedor['id'] }}, '{{ addslashes($proveedor['razon_social']) }}')" class="btn-card-action btn-secondary btn-sm" title="Exportar Reporte PDF" style="background-color: #ec4899; border-color: #ec4899; color: white;">
                                <i class="fa-solid fa-file-pdf"></i>
                            </button>
                            <!-- Editar -->
                            <button type="button" onclick="editProveedor({{ $proveedor['id'] }})" class="btn-card-action btn-primary btn-sm" title="Editar Proveedor">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <!-- Eliminar -->
                            <form action="{{ route('proveedores.destroy', $proveedor['id']) }}" method="POST" onsubmit="return confirm('¿Está seguro de eliminar este proveedor?');" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-card-action btn-danger btn-sm" title="Eliminar Proveedor" style="background-color: #ef4444; border-color: #ef4444;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem;">No se encontraron proveedores</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- PAGINATION -->
    <div style="margin-top: 1rem;">
        {{ $proveedores->appends(request()->query())->links() }}
    </div>
</div>

<!-- MODAL NUEVO PROVEEDOR -->
<div class="modal-backdrop" id="modalNuevoProveedor">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-user-plus text-primary"></i> Registrar Nuevo Proveedor</h2>
            <button class="btn-close" onclick="closeModal('modalNuevoProveedor')">&times;</button>
        </div>
        <form action="{{ route('proveedores.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Tipo de Identificación SRI</label>
                <select name="tipo_identificacion" class="form-control">
                    <option value="04">RUC (13 dígitos)</option>
                    <option value="05">Cédula de Identidad (10 dígitos)</option>
                    <option value="06">Pasaporte</option>
                    <option value="08">Identificación del Exterior</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Número de Cédula o RUC</label>
                <input type="text" name="identificacion" class="form-control" placeholder="Ej: 1723456789001" required>
            </div>
            <div class="form-group">
                <label class="form-label">Razón Social Completa / Nombres</label>
                <input type="text" name="razon_social" class="form-control" placeholder="Ej: Importadora Comercial Cía. Ltda." required>
            </div>
            <div class="form-group">
                <label class="form-label">Correo Electrónico</label>
                <input type="email" name="correo" class="form-control" placeholder="correo@ejemplo.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Teléfono de Contacto</label>
                <input type="text" name="telefono" class="form-control" placeholder="Ej: 0991234567">
            </div>
            <div class="form-group">
                <label class="form-label">Dirección</label>
                <input type="text" name="direccion" class="form-control" placeholder="Ej: Av. 6 de Diciembre y Orellana">
            </div>
            <div style="display: flex; gap: 0.65rem; margin-top: 1.25rem;">
                <button type="submit" class="btn-card-action btn-success" style="flex: 1;">Guardar Proveedor</button>
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalNuevoProveedor')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR PROVEEDOR -->
<div class="modal-backdrop" id="modalEditarProveedor">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-pen text-primary"></i> Editar Proveedor</h2>
            <button class="btn-close" onclick="closeModal('modalEditarProveedor')">&times;</button>
        </div>
        <form id="formEditarProveedor" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label class="form-label">Tipo de Identificación SRI</label>
                <select name="tipo_identificacion" id="edit_tipo_identificacion" class="form-control">
                    <option value="04">RUC (13 dígitos)</option>
                    <option value="05">Cédula de Identidad (10 dígitos)</option>
                    <option value="06">Pasaporte</option>
                    <option value="08">Identificación del Exterior</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Número de Cédula o RUC</label>
                <input type="text" name="identificacion" id="edit_identificacion" class="form-control" placeholder="Ej: 1723456789001" required>
            </div>
            <div class="form-group">
                <label class="form-label">Razón Social Completa / Nombres</label>
                <input type="text" name="razon_social" id="edit_razon_social" class="form-control" placeholder="Ej: Importadora Comercial Cía. Ltda." required>
            </div>
            <div class="form-group">
                <label class="form-label">Correo Electrónico</label>
                <input type="email" name="correo" id="edit_correo" class="form-control" placeholder="correo@ejemplo.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Teléfono de Contacto</label>
                <input type="text" name="telefono" id="edit_telefono" class="form-control" placeholder="Ej: 0991234567">
            </div>
            <div class="form-group">
                <label class="form-label">Dirección</label>
                <input type="text" name="direccion" id="edit_direccion" class="form-control" placeholder="Ej: Av. 6 de Diciembre y Orellana">
            </div>
            <div style="display: flex; gap: 0.65rem; margin-top: 1.25rem;">
                <button type="submit" class="btn-card-action btn-success" style="flex: 1;">Actualizar Proveedor</button>
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalEditarProveedor')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EXPORTAR PDF -->
<div class="modal-backdrop" id="modalPdfProveedor">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-file-pdf text-primary"></i> Generar Reporte de Proveedor</h2>
            <button class="btn-close" onclick="closeModal('modalPdfProveedor')">&times;</button>
        </div>
        <form id="formPdfProveedor" method="POST" target="_blank">
            @csrf
            
            <p><strong>Proveedor:</strong> <span id="pdf_proveedor_nombre"></span></p>
            
            <div class="form-group" style="margin-top: 1rem;">
                <label class="form-label">Período de Facturas:</label>
                <select name="periodo" class="form-control">
                    <option value="5">Últimas 5 facturas</option>
                    <option value="10">Últimas 10 facturas</option>
                    <option value="20">Últimas 20 facturas</option>
                    <option value="50">Últimas 50 facturas</option>
                    <option value="all">Todas las facturas</option>
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
                        <span>Detalle de productos de las facturas (Puede generar un archivo muy grande)</span>
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
    window.proveedoresData = {!! json_encode($proveedores->items()) !!};

    function editProveedor(id) {
        let proveedor = window.proveedoresData.find(c => c.id === id);
        if (!proveedor) return;
        
        document.getElementById('formEditarProveedor').action = '/proveedores/' + proveedor.id;
        document.getElementById('edit_tipo_identificacion').value = proveedor.tipo_identificacion;
        document.getElementById('edit_identificacion').value = proveedor.identificacion;
        document.getElementById('edit_razon_social').value = proveedor.razon_social;
        document.getElementById('edit_correo').value = proveedor.correo;
        document.getElementById('edit_telefono').value = proveedor.telefono;
        document.getElementById('edit_direccion').value = proveedor.direccion;
        
        openModal('modalEditarProveedor');
    }
    
    function openPdfModal(id, nombre) {
        document.getElementById('pdf_proveedor_nombre').textContent = nombre;
        document.getElementById('formPdfProveedor').action = '/proveedores/' + id + '/export-pdf';
        openModal('modalPdfProveedor');
    }
</script>
@endpush
