@extends('layouts.app')

@section('title', 'Configuración SRI y Emisor')

@section('content')

<form action="/configuracion" method="POST" enctype="multipart/form-data">
    @csrf
    
    <!-- CONFIGURACION HEADER -->
    <div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1.15rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
            <div>
                <h1 style="margin-bottom: 0.15rem;"><i class="fa-solid fa-gears text-primary"></i> Configuración del Emisor y SRI</h1>
                <p style="margin: 0; font-size: 0.85rem;">Parámetros legales para la emisión de comprobantes electrónicos autorizados por el SRI.</p>
            </div>
            <button type="submit" class="btn-card-action btn-primary btn-sm">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success" style="padding: 1rem; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 1rem;">
        {{ session('success') }}
    </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.15rem;">

        <!-- CARD 1: DATOS DEL EMISOR -->
        <div class="data-card">
            <div class="data-card-header">
                <h2><i class="fa-solid fa-building text-primary"></i> Datos de la Empresa (RUC)</h2>
            </div>
            <div style="padding: 1rem;">
                <div class="form-group">
                    <label class="form-label">RUC de la Empresa</label>
                    <input type="text" name="ruc" class="form-control" value="{{ old('ruc', $emisor['ruc']) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Razón Social</label>
                    <input type="text" name="razon_social" class="form-control" value="{{ old('razon_social', $emisor['razon_social']) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre Comercial</label>
                    <input type="text" name="nombre_comercial" class="form-control" value="{{ old('nombre_comercial', $emisor['nombre_comercial']) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Dirección Matriz</label>
                    <input type="text" name="direccion_matriz" class="form-control" value="{{ old('direccion_matriz', $emisor['direccion_matriz']) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Régimen Tributario SRI</label>
                    <select name="regimen_rimpe" class="form-control">
                        <option value="CONTRIBUYENTE RÉGIMEN RIMPE" {{ $emisor['regimen_rimpe'] == 'CONTRIBUYENTE RÉGIMEN RIMPE' ? 'selected' : '' }}>CONTRIBUYENTE RÉGIMEN RIMPE</option>
                        <option value="CONTRIBUYENTE NEGOCIO POPULAR - RÉGIMEN RIMPE" {{ $emisor['regimen_rimpe'] == 'CONTRIBUYENTE NEGOCIO POPULAR - RÉGIMEN RIMPE' ? 'selected' : '' }}>CONTRIBUYENTE NEGOCIO POPULAR - RÉGIMEN RIMPE</option>
                        <option value="NO APLICA" {{ $emisor['regimen_rimpe'] == 'NO APLICA' ? 'selected' : '' }}>NO APLICA (Régimen General)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- CARD 2: FIRMA ELECTRÓNICA & AMBIENTE SRI -->
        <div>
            <div class="data-card" style="margin-bottom: 1.15rem;">
                <div class="data-card-header">
                    <h2><i class="fa-solid fa-key text-warning"></i> Firma Electrónica (.p12)</h2>
                </div>
                <div style="padding: 1rem;">
                    @if($emisor['firma_ruta'])
                    <div style="background: #fffbeb; padding: 0.65rem 0.85rem; border-radius: var(--radius-sm); border: 1px solid #fde68a; margin-bottom: 0.85rem;">
                        <p style="margin: 0; font-size: 0.8rem; color: #b45309;">
                            <i class="fa-solid fa-shield-halved"></i> Firma Digital: <strong>Cargada</strong>
                        </p>
                    </div>
                    @endif
                    <div class="form-group">
                        <label class="form-label">Archivo de Firma (.p12 / .pfx)</label>
                        <input type="file" name="firma_archivo" class="form-control" style="padding: 0.35rem 0.5rem; height: auto;" accept=".p12,.pfx">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contraseña de la Firma</label>
                        <input type="password" name="firma_clave" class="form-control" value="{{ $emisor['firma_clave'] }}">
                    </div>
                </div>
            </div>

            <div class="data-card">
                <div class="data-card-header">
                    <h2><i class="fa-solid fa-server text-primary"></i> Ambiente de Emisión SRI</h2>
                </div>
                <div style="padding: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Seleccionar Ambiente SRI</label>
                        <select name="ambiente_sri" class="form-control" style="font-weight: 700;">
                            <option value="1" {{ $emisor['ambiente_sri'] == 1 ? 'selected' : '' }}>1 - AMBIENTE DE PRUEBAS (Sandbox SRI)</option>
                            <option value="2" {{ $emisor['ambiente_sri'] == 2 ? 'selected' : '' }}>2 - AMBIENTE DE PRODUCCIÓN (Validez Legal)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

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

<!-- CARD 4: SERVIDOR DE IMPRESIÓN TÉRMICA SYSFACT_PRINTER -->
<div class="data-card" style="margin-top: 1.15rem; border-left: 4px solid #2563eb;">
    <div class="data-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <h2><i class="fa-solid fa-print text-primary"></i> Servidor de Impresión Térmica (SysFact_Printer)</h2>
            <p style="margin: 0; font-size: 0.825rem; color: var(--text-muted);">Servicio de impresión directa por puerto 8080 para ticketeras térmicas USB, Red y Bluetooth (ESC/POS / GDI).</p>
        </div>
        <div>
            <a href="{{ route('facturacion.descargarServidor') }}" class="btn-card-action btn-primary btn-sm">
                <i class="fa-solid fa-download"></i> Descargar SysFact_Printer.exe
            </a>
        </div>
    </div>

    <div style="padding: 1.25rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
            
            <!-- Estado del servicio -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem;">
                <div style="font-size: 0.85rem; font-weight: 700; margin-bottom: 0.5rem; color: #334155;">
                    <i class="fa-solid fa-network-wired text-primary"></i> Estado de Conexión Local
                </div>
                <div id="cfgPrinterStatusBox" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                    <span id="cfgPrinterBadge" class="badge badge-warning" style="font-size: 0.82rem; padding: 0.4rem 0.75rem;">
                        <i class="fa-solid fa-spinner fa-spin"></i> Verificando servidor (127.0.0.1:8080)...
                    </span>
                </div>
                <div style="font-size: 0.8rem; color: #64748b;">
                    <div>Impresora seleccionada: <strong id="cfgPrinterName">Buscando...</strong></div>
                    <div>Puerto HTTP: <strong>8080</strong> &bull; Protocolo: <strong>REST JSON</strong></div>
                </div>
            </div>

            <!-- Acciones de prueba -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem;">
                <div style="font-size: 0.85rem; font-weight: 700; margin-bottom: 0.5rem; color: #334155;">
                    <i class="fa-solid fa-bolt text-warning"></i> Pruebas de Hardware POS
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    <button type="button" class="btn-card-action btn-secondary btn-sm" onclick="checkConfigPrinterStatus()">
                        <i class="fa-solid fa-rotate"></i> Probar Conexión
                    </button>
                    <button type="button" class="btn-card-action btn-primary btn-sm" onclick="cfgTestPrint()">
                        <i class="fa-solid fa-receipt"></i> Test Imprimir Ticket
                    </button>
                    <button type="button" class="btn-card-action btn-success btn-sm" onclick="cfgOpenDrawer()">
                        <i class="fa-solid fa-cash-register"></i> Abrir Gaveta
                    </button>
                </div>
            </div>

        </div>

        <!-- Instrucciones de Uso -->
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1rem; font-size: 0.825rem; color: #1e40af;">
            <div style="font-weight: 700; margin-bottom: 0.35rem;"><i class="fa-solid fa-circle-info"></i> ¿Cómo funciona el servidor de impresión SysFact_Printer?</div>
            <ol style="margin-left: 1.25rem; margin-bottom: 0;">
                <li>Descargue el archivo <strong>SysFact_Printer.exe</strong> y ejecútelo en la computadora que tiene conectada la ticketera.</li>
                <li>Seleccione su impresora térmica en la ventana y el modo de impresión (ESC/POS directo o Driver de Windows).</li>
                <li>El programa se minimizará en la barra de tareas (junto al reloj). A partir de ese momento, todas las facturas y arqueos de caja se imprimirán de forma instantánea y silenciosa.</li>
            </ol>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const PRINT_SERVER_URL = 'http://127.0.0.1:8080';

    async function checkConfigPrinterStatus() {
        const badge = document.getElementById('cfgPrinterBadge');
        const nameEl = document.getElementById('cfgPrinterName');

        badge.className = 'badge badge-secondary';
        badge.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Consultando...';

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 2000);

            const res = await fetch(`${PRINT_SERVER_URL}/status`, { signal: controller.signal });
            clearTimeout(timeoutId);
            const data = await res.json();

            if (data && data.status === 'online') {
                badge.className = 'badge badge-success';
                badge.style.background = '#dcfce7';
                badge.style.color = '#15803d';
                badge.innerHTML = '<i class="fa-solid fa-check"></i> Servidor SysFact_Printer En Línea';
                nameEl.innerText = data.printer || 'Predeterminada de Windows';
            } else {
                throw new Error('Offline');
            }
        } catch (e) {
            badge.className = 'badge badge-warning';
            badge.style.background = '#fef3c7';
            badge.style.color = '#b45309';
            badge.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Servidor Desconectado';
            nameEl.innerText = 'No conectado';
        }
    }

    async function cfgTestPrint() {
        try {
            const res = await fetch(`${PRINT_SERVER_URL}/test_print`, { method: 'POST' });
            const data = await res.json();
            if (data.status === 'ok') {
                alert('✅ Ticket de prueba enviado exitosamente a la ticketera.');
            } else {
                alert('⚠️ Error al imprimir: ' + (data.message || 'Desconocido'));
            }
        } catch (e) {
            alert('⚠️ No se pudo conectar con SysFact_Printer en http://127.0.0.1:8080. Verifique que el programa esté abierto.');
        }
    }

    async function cfgOpenDrawer() {
        try {
            const res = await fetch(`${PRINT_SERVER_URL}/open_drawer`, { method: 'POST' });
            const data = await res.json();
            if (data.status === 'ok') {
                alert('✅ Pulso de apertura enviado a la gaveta de dinero.');
            } else {
                alert('⚠️ Error: ' + (data.message || 'Desconocido'));
            }
        } catch (e) {
            alert('⚠️ No se pudo conectar con SysFact_Printer en http://127.0.0.1:8080.');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        checkConfigPrinterStatus();
    });
</script>
@endpush
