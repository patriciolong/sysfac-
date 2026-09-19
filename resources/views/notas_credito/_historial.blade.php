<!-- _historial.blade.php - NOTAS DE CREDITO -->
<div class="data-card" style="padding: 1rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-size: 1.1rem; margin: 0;">Historial de Notas de Crédito Emitidas</h2>
        <div style="display: flex; gap: 0.5rem;">
            <!-- Botón de reautorizar masivo -->
            <button type="button" class="btn-card-action btn-primary btn-sm" onclick="reautorizarNCSeleccionadas()">
                <i class="fa-solid fa-paper-plane"></i> Reenviar al SRI
            </button>
            <a href="{{ route('notas-credito.index', ['tab' => 'historial']) }}" class="btn-card-action btn-secondary btn-sm">
                <i class="fa-solid fa-rotate-right"></i> Actualizar
            </a>
        </div>
    </div>

    <!-- FILTROS -->
    <form method="GET" action="{{ route('notas-credito.index') }}" style="background: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 1rem;">
        <input type="hidden" name="tab" value="historial">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem;">
            <div>
                <label style="font-size: 0.8rem; font-weight: 600;">Núm. Nota Crédito</label>
                <input type="text" name="numero" class="form-control" placeholder="Ej: 12" value="{{ request('numero') }}">
            </div>
            <div>
                <label style="font-size: 0.8rem; font-weight: 600;">Núm. Factura</label>
                <input type="text" name="factura" class="form-control" placeholder="Ej: 001-001-000000005" value="{{ request('factura') }}">
            </div>
            <div>
                <label style="font-size: 0.8rem; font-weight: 600;">Cliente (Nombre/ID)</label>
                <input type="text" name="cliente" class="form-control" placeholder="Nombre o Cédula" value="{{ request('cliente') }}">
            </div>
            <div>
                <label style="font-size: 0.8rem; font-weight: 600;">Fecha Desde</label>
                <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
            </div>
            <div>
                <label style="font-size: 0.8rem; font-weight: 600;">Fecha Hasta</label>
                <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
            </div>
            <div>
                <label style="font-size: 0.8rem; font-weight: 600;">Estado SRI</label>
                <select name="estado" class="form-control">
                    <option value="">Todos</option>
                    <option value="AUTORIZADO" {{ request('estado') == 'AUTORIZADO' ? 'selected' : '' }}>Autorizado</option>
                    <option value="RECHAZADO" {{ request('estado') == 'RECHAZADO' ? 'selected' : '' }}>Rechazado</option>
                    <option value="DEVUELTO" {{ request('estado') == 'DEVUELTO' ? 'selected' : '' }}>Devuelto</option>
                    <option value="ENVIADO" {{ request('estado') == 'ENVIADO' ? 'selected' : '' }}>Enviado / En Proceso</option>
                    <option value="CREADO" {{ request('estado') == 'CREADO' ? 'selected' : '' }}>Pendiente</option>
                    <option value="ANULADO" {{ request('estado') == 'ANULADO' ? 'selected' : '' }}>Anulada</option>
                </select>
            </div>
        </div>
        <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem;">
            <a href="{{ route('notas-credito.index', ['tab' => 'historial']) }}" class="btn-card-action btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;"><i class="fa-solid fa-eraser"></i> Limpiar</a>
            <button type="submit" class="btn-card-action btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;"><i class="fa-solid fa-magnifying-glass"></i> Filtrar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="custom-table" id="tablaHistorialNC">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;"><input type="checkbox" id="chkAllNC" onchange="toggleAllNC()"></th>
                    <th>Emisión</th>
                    <th>Comprobante</th>
                    <th>Factura Afectada</th>
                    <th>Cliente</th>
                    <th>Total Acreditado</th>
                    <th>Estado SRI</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notasCredito as $nc)
                <tr>
                    <td style="text-align: center;">
                        @if(in_array($nc->estado_sri, ['DEVUELTO', 'DEVUELTA', 'RECHAZADO', 'PENDIENTE', 'CREADO', 'ENVIADO', 'NO AUTORIZADO']))
                            <input type="checkbox" class="chkNC" value="{{ $nc->id }}">
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($nc->fecha_emision)->format('d/m/Y') }}</td>
                    <td>
                        <strong>{{ $nc->numero_comprobante }}</strong><br>
                        <small style="color: #64748b; font-family: monospace;">{{ $nc->clave_acceso }}</small>
                    </td>
                    <td>
                        <strong>Factura: {{ $nc->numero_factura_modificada }}</strong><br>
                        <small style="color: #64748b;">Motivo: {{ \Illuminate\Support\Str::limit($nc->motivo, 35) }}</small>
                    </td>
                    <td>
                        {{ $nc->cliente->razon_social ?? 'Consumidor Final' }}<br>
                        <small>{{ $nc->cliente->identificacion ?? '9999999999999' }}</small>
                    </td>
                    <td style="font-weight: 600; color: var(--success);">${{ number_format($nc->valor_modificacion, 2) }}</td>
                    <td>
                        @if($nc->estado_sri === 'AUTORIZADO')
                            <span class="badge badge-success"><i class="fa-solid fa-check"></i> {{ $nc->estado_sri }}</span>
                        @elseif($nc->estado_sri === 'RECHAZADO')
                            <span class="badge badge-danger"><i class="fa-solid fa-xmark"></i> {{ $nc->estado_sri }}</span>
                        @elseif($nc->estado_sri === 'DEVUELTO' || $nc->estado_sri === 'DEVUELTA')
                            <span class="badge badge-warning"><i class="fa-solid fa-rotate-left"></i> {{ $nc->estado_sri }}</span>
                        @else
                            <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> {{ $nc->estado_sri ?? 'PENDIENTE' }}</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <button type="button" class="btn-card-action btn-primary btn-sm" onclick="verDetalleNC({{ $nc->id }})" title="Ver Detalles">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <a href="{{ route('notas-credito.pdf', $nc->id) }}" target="_blank" class="btn-card-action btn-secondary btn-sm" title="Descargar RIDE PDF">
                            <i class="fa-solid fa-file-pdf"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2rem; color: #64748b;">
                        No hay notas de crédito registradas en el historial.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($notasCredito) && $notasCredito->hasPages())
    <div style="margin-top: 1rem;">
        {{ $notasCredito->links() }}
    </div>
    @endif
</div>

<!-- MODAL DETALLE NOTA CREDITO -->
<div class="modal-backdrop" id="modalDetalleNC">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-file-circle-minus text-primary"></i> Detalle de Nota de Crédito</h2>
            <button class="btn-close" onclick="closeModal('modalDetalleNC')">&times;</button>
        </div>
        <div id="detalleNCBody" style="font-size: 0.9rem;">
            <p>Cargando detalles...</p>
        </div>
        <div style="margin-top: 1.5rem; border-top: 1px solid #e2e8f0; padding-top: 1rem; display: flex; justify-content: space-between;">
            <div>
                 <button type="button" id="btnReenviarCorreoNC" class="btn-card-action btn-primary" style="display: none;" onclick="reenviarCorreoNCActiva()">
                     <i class="fa-solid fa-envelope"></i> Reenviar Correo
                 </button>
            </div>
            <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalDetalleNC')">Cerrar</button>
        </div>
    </div>
</div>

<script>
    let currentNCId = null;

    function toggleAllNC() {
        const isChecked = document.getElementById('chkAllNC').checked;
        const checkboxes = document.querySelectorAll('.chkNC');
        checkboxes.forEach(chk => chk.checked = isChecked);
    }

    async function reautorizarNCSeleccionadas() {
        const checkboxes = document.querySelectorAll('.chkNC:checked');
        const ids = Array.from(checkboxes).map(chk => chk.value);
        
        if (ids.length === 0) {
            alert('Seleccione al menos una nota de crédito para reautorizar.');
            return;
        }

        if (!confirm(`¿Está seguro de reautorizar ${ids.length} nota(s) de crédito? La fecha de emisión se actualizará al día de hoy.`)) {
            return;
        }

        const btn = document.querySelector('button[onclick="reautorizarNCSeleccionadas()"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';
        btn.disabled = true;

        try {
            const response = await fetch('{{ route("notas-credito.reautorizar") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ ids: ids })
            });

            const data = await response.json();
            
            if (data.success) {
                let msj = 'Proceso completado:\n';
                data.resultados.forEach(r => {
                    msj += `- ${r.comprobante}: ${r.success ? 'EXITO' : 'ERROR'} - ${r.message}\n`;
                });
                alert(msj);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (err) {
            console.error(err);
            alert('Ocurrió un error al procesar la solicitud.');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    async function verDetalleNC(id) {
        currentNCId = id;
        openModal('modalDetalleNC');
        const body = document.getElementById('detalleNCBody');
        body.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i><p>Cargando...</p></div>';
        
        try {
            const response = await fetch('/notas-credito/' + id);
            const data = await response.json();
            
            if (data.success) {
                const nc = data.notaCredito;
                const cli = nc.cliente;
                const fac = nc.factura;
                let html = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div style="background: #f8fafc; padding: 0.75rem; border-radius: 6px;">
                            <strong>Cliente:</strong> ${cli ? cli.razon_social : 'N/A'}<br>
                            <strong>Identificación:</strong> ${cli ? cli.identificacion : 'N/A'}<br>
                            <strong>Correo:</strong> ${cli ? cli.correo : 'N/A'}<br>
                            <strong>Tipo Modificación:</strong> <span class="badge badge-info">${nc.tipo_modificacion}</span>
                        </div>
                        <div style="background: #f8fafc; padding: 0.75rem; border-radius: 6px;">
                            <strong>Emisión NC:</strong> ${nc.fecha_emision ? nc.fecha_emision.substring(0, 10) : 'N/A'}<br>
                            <strong>Comprobante NC:</strong> ${nc.establecimiento}-${nc.punto_emision}-${nc.secuencial}<br>
                            <strong>Factura Modificada:</strong> ${nc.numero_factura_modificada || (fac ? (fac.numero_comprobante || fac.numero_documento || `${fac.establecimiento}-${fac.punto_emision}-${fac.secuencial}`) : 'N/A')}<br>
                            <strong>Estado SRI:</strong> <span class="badge ${nc.estado_sri === 'AUTORIZADO' ? 'badge-success' : (nc.estado_sri === 'ENVIADO' ? 'badge-warning' : 'badge-danger')}">${nc.estado_sri}</span>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 0.75rem;">
                        <strong>Motivo de Modificación:</strong>
                        <div style="background: #f8fafc; padding: 0.5rem; border-radius: 4px; font-size: 0.85rem; border: 1px solid #e2e8f0;">
                            ${nc.motivo || 'Devolución de mercadería'}
                        </div>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <strong>Mensaje SRI:</strong>
                        <div style="background: #f1f5f9; padding: 0.5rem; border-radius: 4px; font-family: monospace; font-size: 0.8rem; word-wrap: break-word; max-height: 80px; overflow-y: auto;">
                            ${nc.mensajes_sri || 'Ninguno'}
                        </div>
                    </div>
                    
                    <h3 style="font-size: 0.95rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.25rem;">Productos Acreditados</h3>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem;">
                        <thead>
                            <tr style="background: #e2e8f0;">
                                <th style="padding: 0.25rem; text-align: left;">Producto</th>
                                <th style="padding: 0.25rem; text-align: center;">Cant.</th>
                                <th style="padding: 0.25rem; text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                if (nc.detalles && nc.detalles.length > 0) {
                    nc.detalles.forEach(d => {
                        html += `
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 0.25rem;">${d.descripcion || d.producto_id}</td>
                                <td style="padding: 0.25rem; text-align: center;">${parseFloat(d.cantidad).toFixed(2)}</td>
                                <td style="padding: 0.25rem; text-align: right;">$${parseFloat(d.precio_total_sin_impuestos).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                }
                
                html += `
                        </tbody>
                    </table>
                    
                    <div style="text-align: right; margin-top: 1rem;">
                        <div>Subtotal Sin Impuestos: <strong>$${parseFloat(nc.total_sin_impuestos).toFixed(2)}</strong></div>
                        <div>IVA: <strong>$${parseFloat(nc.valor_iva).toFixed(2)}</strong></div>
                        <div style="font-size: 1.1rem; margin-top: 0.25rem;">Total Acreditado: <strong style="color: var(--success);">$${parseFloat(nc.valor_modificacion).toFixed(2)}</strong></div>
                    </div>
                `;
                
                body.innerHTML = html;
                
                const btnReenviar = document.getElementById('btnReenviarCorreoNC');
                btnReenviar.style.display = 'inline-block';
            } else {
                body.innerHTML = `<p style="color: red;">Error: ${data.message}</p>`;
                document.getElementById('btnReenviarCorreoNC').style.display = 'none';
            }
        } catch (err) {
            body.innerHTML = '<p style="color: red;">Ocurrió un error al cargar los datos.</p>';
            document.getElementById('btnReenviarCorreoNC').style.display = 'none';
        }
    }

    async function reenviarCorreoNCActiva() {
        if (!currentNCId) return;
        if (!confirm('¿Estás seguro que deseas reenviar la Nota de Crédito por correo al cliente?')) return;
        
        const btn = document.getElementById('btnReenviarCorreoNC');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';
        btn.disabled = true;

        try {
            const response = await fetch(`/notas-credito/${currentNCId}/reenviar-correo`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const data = await response.json();
            
            if (data.success) {
                alert('Correo reenviado exitosamente.');
            } else {
                alert('Error: ' + data.message);
            }
        } catch (err) {
            alert('Ocurrió un error al intentar enviar el correo.');
        }

        btn.innerHTML = originalText;
        btn.disabled = false;
    }
</script>
