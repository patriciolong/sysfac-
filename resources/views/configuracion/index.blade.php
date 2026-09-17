@extends('layouts.app')

@section('title', 'Configuración SRI y Emisor')

@section('content')

<!-- CONFIGURACION HEADER -->
<div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1.15rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
        <div>
            <h1 style="margin-bottom: 0.15rem;"><i class="fa-solid fa-gears text-primary"></i> Configuración del Emisor y SRI</h1>
            <p style="margin: 0; font-size: 0.85rem;">Parámetros legales para la emisión de comprobantes electrónicos autorizados por el SRI.</p>
        </div>
        <button class="btn-card-action btn-primary btn-sm" onclick="alert('¡Parámetros de configuración guardados correctamente!')">
            <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
        </button>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.15rem;">

    <!-- CARD 1: DATOS DEL EMISOR -->
    <div class="data-card">
        <div class="data-card-header">
            <h2><i class="fa-solid fa-building text-primary"></i> Datos de la Empresa (RUC)</h2>
        </div>
        <form>
            <div class="form-group">
                <label class="form-label">RUC de la Empresa</label>
                <input type="text" class="form-control" value="{{ $emisor['ruc'] }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Razón Social</label>
                <input type="text" class="form-control" value="{{ $emisor['razon_social'] }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nombre Comercial</label>
                <input type="text" class="form-control" value="{{ $emisor['nombre_comercial'] }}">
            </div>
            <div class="form-group">
                <label class="form-label">Dirección Matriz</label>
                <input type="text" class="form-control" value="{{ $emisor['direccion_matriz'] }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Régimen Tributario SRI</label>
                <select class="form-control">
                    <option value="CONTRIBUYENTE RÉGIMEN RIMPE" selected>CONTRIBUYENTE RÉGIMEN RIMPE</option>
                    <option value="CONTRIBUYENTE NEGOCIO POPULAR - RÉGIMEN RIMPE">CONTRIBUYENTE NEGOCIO POPULAR - RÉGIMEN RIMPE</option>
                    <option value="NO APLICA">NO APLICA (Régimen General)</option>
                </select>
            </div>
        </form>
    </div>

    <!-- CARD 2: FIRMA ELECTRÓNICA & AMBIENTE SRI -->
    <div>
        <div class="data-card" style="margin-bottom: 1.15rem;">
            <div class="data-card-header">
                <h2><i class="fa-solid fa-key text-warning"></i> Firma Electrónica (.p12)</h2>
            </div>
            <div style="background: #fffbeb; padding: 0.65rem 0.85rem; border-radius: var(--radius-sm); border: 1px solid #fde68a; margin-bottom: 0.85rem;">
                <p style="margin: 0; font-size: 0.8rem; color: #b45309;">
                    <i class="fa-solid fa-shield-halved"></i> Firma Digital: <strong>{{ $emisor['firma_ruta'] }}</strong> (Válida hasta Nov 2027).
                </p>
            </div>
            <div class="form-group">
                <label class="form-label">Archivo de Firma (.p12 / .pfx)</label>
                <input type="file" class="form-control" style="padding: 0.35rem 0.5rem; height: auto;">
            </div>
            <div class="form-group">
                <label class="form-label">Contraseña de la Firma</label>
                <input type="password" class="form-control" value="••••••••••••">
            </div>
        </div>

        <div class="data-card">
            <div class="data-card-header">
                <h2><i class="fa-solid fa-server text-primary"></i> Ambiente de Emisión SRI</h2>
            </div>
            <div class="form-group">
                <label class="form-label">Seleccionar Ambiente SRI</label>
                <select class="form-control" style="font-weight: 700;">
                    <option value="1" selected>1 - AMBIENTE DE PRUEBAS (Sandbox SRI)</option>
                    <option value="2">2 - AMBIENTE DE PRODUCCIÓN (Validez Legal)</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- CARD 3: MÉTODOS DE PAGO Y PUNTOS DE EMISIÓN -->
<div class="data-card" style="margin-top: 1.15rem;">
    <div class="data-card-header">
        <h2><i class="fa-solid fa-credit-card text-primary"></i> Formas de Pago Catálogo SRI (`configuracion_metodos_pago`)</h2>
    </div>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Código SRI</th>
                    <th>Nombre de la Forma de Pago</th>
                    <th>Estado en Sistema</th>
                    <th style="text-align: center;">Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($metodos_pago_sri as $met)
                <tr>
                    <td><strong>{{ $met['codigo'] }}</strong></td>
                    <td>{{ $met['nombre'] }}</td>
                    <td>
                        @if($met['estado'] == 'ACTIVO')
                            <span class="badge badge-success">ACTIVO</span>
                        @else
                            <span class="badge badge-danger">INACTIVO</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <button class="btn-card-action btn-secondary btn-sm" onclick="alert('Cambiado estado de {{ $met['nombre'] }}')">
                            Cambiar Estado
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
