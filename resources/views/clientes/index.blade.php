@extends('layouts.app')

@section('title', 'Gestión de Clientes')

@section('content')

<!-- CLIENTES HEADER BAR -->
<div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1.15rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
        <div>
            <h1 style="margin-bottom: 0.15rem;"><i class="fa-solid fa-users text-primary"></i> Directorio de Clientes</h1>
            <p style="margin: 0; font-size: 0.85rem;">Administre y registre los datos de sus clientes para la emisión de facturas del SRI.</p>
        </div>
        <div style="display: flex; gap: 0.65rem;">
            <a href="{{ route('clientes.export', ['search' => request('search')]) }}" class="btn-card-action btn-secondary btn-sm">
                <i class="fa-solid fa-file-excel"></i> Exportar
            </a>
            @if(auth()->user()->hasPermission('Clientes', 'master'))
                <button class="btn-card-action btn-success btn-sm" onclick="openModal('modalNuevoCliente')">
                    <i class="fa-solid fa-user-plus"></i> Crear Nuevo Cliente
                </button>
            @else
                <button class="btn-card-action btn-secondary btn-sm" style="opacity: 0.6;" onclick="alert('No tiene permisos para crear clientes.')">
                    <i class="fa-solid fa-user-plus"></i> Crear Nuevo Cliente 🔒
                </button>
            @endif
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

<!-- CLIENTES TABLE VIEW -->
<div class="data-card">
    <div class="data-card-header">
        <h2><i class="fa-solid fa-address-book text-primary"></i> Directorio y Registro de Clientes</h2>
        <form action="{{ route('clientes.index') }}" method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por nombre, cédula, correo..." style="height: 32px; font-size: 0.82rem; width: 260px;">
            <button type="submit" class="btn-card-action btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
            @if(request('search'))
                <a href="{{ route('clientes.index') }}" class="btn-card-action btn-secondary btn-sm"><i class="fa-solid fa-xmark"></i> Limpiar</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="custom-table" id="tablaClientes">
            <thead>
                <tr>
                    <th>Tipo Documento</th>
                    <th>Identificación (Cédula/RUC)</th>
                    <th>Razón Social / Nombre Completo</th>
                    <th>Correo Electrónico</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Obligado Contabilidad</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clientes as $cli)
                <tr>
                    <td>
                        <span class="badge {{ $cli['tipo_identificacion'] == '04' ? 'badge-warning' : 'badge-info' }}">
                            {{ $cli['tipo_nombre'] }}
                        </span>
                    </td>
                    <td><strong style="font-family: monospace;">{{ $cli['identificacion'] }}</strong></td>
                    <td><strong>{{ $cli['razon_social'] }}</strong></td>
                    <td><i class="fa-solid fa-envelope text-primary"></i> {{ $cli['correo'] }}</td>
                    <td>{{ $cli['telefono'] }}</td>
                    <td style="color: var(--text-muted); font-size: 0.82rem;">{{ $cli['direccion'] }}</td>
                    <td>
                        @if($cli['obligado_contabilidad'] == 'SI')
                            <span class="badge badge-warning">SI</span>
                        @else
                            <span class="badge badge-info">NO</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <div style="display: inline-flex; gap: 0.35rem;">
                            <a href="{{ url('/facturacion?cliente_id=' . $cli['id']) }}" class="btn-card-action btn-primary btn-sm" title="Facturar a este cliente">
                                <i class="fa-solid fa-cash-register"></i> Facturar
                            </a>
                            @if(auth()->user()->hasPermission('Clientes', 'master'))
                                <button type="button" class="btn-card-action btn-secondary btn-sm" onclick="editCliente({{ $cli['id'] }})" title="Editar Cliente">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form action="{{ route('clientes.destroy', $cli['id']) }}" method="POST" onsubmit="return confirm('¿Está seguro de eliminar este cliente?');" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-card-action btn-danger btn-sm" title="Eliminar Cliente" style="background-color: #ef4444; border-color: #ef4444;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @else
                                <button type="button" class="btn-card-action btn-secondary btn-sm" style="opacity: 0.6;" onclick="alert('No tiene permisos para editar clientes.')" title="Editar Cliente 🔒">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button type="button" class="btn-card-action btn-danger btn-sm" style="background-color: #ef4444; border-color: #ef4444; opacity: 0.6;" onclick="alert('No tiene permisos para eliminar clientes.')" title="Eliminar Cliente 🔒">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2rem;">No se encontraron clientes</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- PAGINATION -->
    <div style="margin-top: 1rem;">
        {{ $clientes->appends(request()->query())->links() }}
    </div>
</div>

<!-- MODAL NUEVO CLIENTE -->
<div class="modal-backdrop" id="modalNuevoCliente">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-user-plus text-primary"></i> Registrar Nuevo Cliente</h2>
            <button class="btn-close" onclick="closeModal('modalNuevoCliente')">&times;</button>
        </div>
        <form action="{{ route('clientes.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Tipo de Identificación SRI</label>
                <select name="tipo_identificacion" class="form-control">
                    <option value="05">Cédula de Identidad (10 dígitos)</option>
                    <option value="04">RUC (13 dígitos)</option>
                    <option value="06">Pasaporte</option>
                    <option value="08">Identificación del Exterior</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Número de Cédula o RUC</label>
                <input type="text" name="identificacion" class="form-control" placeholder="Ej: 1723456789" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nombres y Apellidos / Razón Social Completa</label>
                <input type="text" name="razon_social" class="form-control" placeholder="Ej: María Fernanda López" required>
            </div>
            <div class="form-group">
                <label class="form-label">Correo Electrónico (Para envío de Facturas)</label>
                <input type="email" name="correo" class="form-control" placeholder="correo@ejemplo.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Teléfono de Contacto</label>
                <input type="text" name="telefono" class="form-control" placeholder="Ej: 0991234567">
            </div>
            <div class="form-group">
                <label class="form-label">Dirección Domiciliaria</label>
                <input type="text" name="direccion" class="form-control" placeholder="Ej: Av. 6 de Diciembre y Orellana">
            </div>
            <div class="form-group">
                <label class="form-label">¿Obligado a Llevar Contabilidad?</label>
                <select name="obligado_contabilidad" class="form-control">
                    <option value="NO">NO</option>
                    <option value="SI">SI</option>
                </select>
            </div>
            <div style="display: flex; gap: 0.65rem; margin-top: 1.25rem;">
                <button type="submit" class="btn-card-action btn-success" style="flex: 1;">Guardar Cliente</button>
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalNuevoCliente')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR CLIENTE -->
<div class="modal-backdrop" id="modalEditarCliente">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-pen text-primary"></i> Editar Cliente</h2>
            <button class="btn-close" onclick="closeModal('modalEditarCliente')">&times;</button>
        </div>
        <form id="formEditarCliente" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label class="form-label">Tipo de Identificación SRI</label>
                <select name="tipo_identificacion" id="edit_tipo_identificacion" class="form-control">
                    <option value="05">Cédula de Identidad (10 dígitos)</option>
                    <option value="04">RUC (13 dígitos)</option>
                    <option value="06">Pasaporte</option>
                    <option value="08">Identificación del Exterior</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Número de Cédula o RUC</label>
                <input type="text" name="identificacion" id="edit_identificacion" class="form-control" placeholder="Ej: 1723456789" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nombres y Apellidos / Razón Social Completa</label>
                <input type="text" name="razon_social" id="edit_razon_social" class="form-control" placeholder="Ej: María Fernanda López" required>
            </div>
            <div class="form-group">
                <label class="form-label">Correo Electrónico (Para envío de Facturas)</label>
                <input type="email" name="correo" id="edit_correo" class="form-control" placeholder="correo@ejemplo.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Teléfono de Contacto</label>
                <input type="text" name="telefono" id="edit_telefono" class="form-control" placeholder="Ej: 0991234567">
            </div>
            <div class="form-group">
                <label class="form-label">Dirección Domiciliaria</label>
                <input type="text" name="direccion" id="edit_direccion" class="form-control" placeholder="Ej: Av. 6 de Diciembre y Orellana">
            </div>
            <div class="form-group">
                <label class="form-label">¿Obligado a Llevar Contabilidad?</label>
                <select name="obligado_contabilidad" id="edit_obligado_contabilidad" class="form-control">
                    <option value="NO">NO</option>
                    <option value="SI">SI</option>
                </select>
            </div>
            <div style="display: flex; gap: 0.65rem; margin-top: 1.25rem;">
                <button type="submit" class="btn-card-action btn-success" style="flex: 1;">Actualizar Cliente</button>
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalEditarCliente')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    window.clientesData = {!! json_encode($clientes->items()) !!};

    function editCliente(id) {
        let cliente = window.clientesData.find(c => c.id === id);
        if (!cliente) return;
        
        document.getElementById('formEditarCliente').action = '/clientes/' + cliente.id;
        document.getElementById('edit_tipo_identificacion').value = cliente.tipo_identificacion;
        document.getElementById('edit_identificacion').value = cliente.identificacion;
        document.getElementById('edit_razon_social').value = cliente.razon_social;
        document.getElementById('edit_correo').value = cliente.correo;
        document.getElementById('edit_telefono').value = cliente.telefono;
        document.getElementById('edit_direccion').value = cliente.direccion;
        document.getElementById('edit_obligado_contabilidad').value = cliente.obligado_contabilidad;
        
        openModal('modalEditarCliente');
    }
</script>
@endpush
