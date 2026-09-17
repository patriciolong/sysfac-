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
        <button class="btn-card-action btn-success btn-sm" onclick="openModal('modalNuevoCliente')">
            <i class="fa-solid fa-user-plus"></i> Crear Nuevo Cliente
        </button>
    </div>
</div>

<!-- CLIENTES TABLE VIEW -->
<div class="data-card">
    <div class="data-card-header">
        <h2><i class="fa-solid fa-address-book text-primary"></i> Directorio y Registro de Clientes</h2>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <input type="text" id="searchClientes" class="form-control" placeholder="Buscar por nombre, cédula o RUC..." style="height: 32px; font-size: 0.82rem; width: 260px;" onkeyup="filterClientesTable()">
        </div>
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
                @foreach($clientes as $cli)
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
                            <a href="{{ url('/facturacion') }}" class="btn-card-action btn-primary btn-sm">
                                <i class="fa-solid fa-cash-register"></i> Facturar
                            </a>
                            <button class="btn-card-action btn-secondary btn-sm" onclick="alert('Modificar datos de {{ $cli['razon_social'] }}')">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVO CLIENTE -->
<div class="modal-backdrop" id="modalNuevoCliente">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-user-plus text-primary"></i> Registrar Nuevo Cliente</h2>
            <button class="btn-close" onclick="closeModal('modalNuevoCliente')">&times;</button>
        </div>
        <form action="{{ url('/clientes') }}" method="POST">
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

@endsection

@push('scripts')
<script>
    function filterClientesTable() {
        const input = document.getElementById('searchClientes').value.toLowerCase();
        const rows = document.querySelectorAll('#tablaClientes tbody tr');
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(input) ? '' : 'none';
        });
    }
</script>
@endpush
