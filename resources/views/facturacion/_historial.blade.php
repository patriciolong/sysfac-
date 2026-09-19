<!-- _historial.blade.php -->
<div class="data-card" style="padding: 1rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-size: 1.1rem; margin: 0;">Historial de Facturas Emitidas</h2>
        <div style="display: flex; gap: 0.5rem;">
            <!-- Botón de reautorizar -->
            <button type="button" class="btn-card-action btn-primary btn-sm" onclick="reautorizarSeleccionadas()">
                <i class="fa-solid fa-paper-plane"></i> Reenviar al SRI
            </button>
            <a href="{{ route('facturacion.index', ['tab' => 'historial']) }}" class="btn-card-action btn-secondary btn-sm"><i class="fa-solid fa-rotate-right"></i> Actualizar</a>
        </div>
    </div>

    <!-- FILTROS -->
    <form method="GET" action="{{ route('facturacion.index') }}" style="background: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 1rem;">
        <input type="hidden" name="tab" value="historial">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
            <div>
                <label style="font-size: 0.8rem; font-weight: 600;">Núm. Factura</label>
                <input type="text" name="numero" class="form-control" placeholder="Ej: 123" value="{{ request('numero') }}">
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
                    <option value="EN_PROCESO" {{ request('estado') == 'EN_PROCESO' ? 'selected' : '' }}>En Proceso</option>
                    <option value="PENDIENTE" {{ request('estado') == 'PENDIENTE' ? 'selected' : '' }}>Pendiente</option>
                    <option value="ANULADA" {{ request('estado') == 'ANULADA' ? 'selected' : '' }}>Anulada</option>
                </select>
            </div>
        </div>
        <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem;">
            <a href="{{ route('facturacion.index', ['tab' => 'historial']) }}" class="btn-card-action btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;"><i class="fa-solid fa-eraser"></i> Limpiar</a>
            <button type="submit" class="btn-card-action btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;"><i class="fa-solid fa-magnifying-glass"></i> Filtrar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="custom-table" id="tablaHistorial">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;"><input type="checkbox" id="chkAll" onchange="toggleAllFacturas()"></th>
                    <th>Emisión</th>
                    <th>Comprobante</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Estado SRI</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($facturas as $factura)
                <tr>
                    <td style="text-align: center;">
                        @if(in_array($factura->estado_sri, ['DEVUELTO', 'DEVUELTA', 'RECHAZADO', 'PENDIENTE', 'CREADO', 'ENVIADO', 'NO AUTORIZADO']))
                            <input type="checkbox" class="chkFactura" value="{{ $factura->id }}">
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/Y') }}</td>
                    <td>
                        <strong>{{ $factura->numero_comprobante ?? $factura->establecimiento.'-'.$factura->punto_emision.'-'.$factura->secuencial }}</strong><br>
                        <small style="color: #64748b; font-family: monospace;">{{ $factura->clave_acceso }}</small>
                    </td>
                    <td>
                        {{ $factura->cliente->razon_social ?? 'Consumidor Final' }}<br>
                        <small>{{ $factura->cliente->identificacion ?? '9999999999999' }}</small>
                    </td>
                    <td style="font-weight: 600; color: var(--success);">${{ number_format($factura->importe_total, 2) }}</td>
                    <td>
                        @if($factura->estado_sri === 'AUTORIZADO')
                            <span class="badge badge-success"><i class="fa-solid fa-check"></i> {{ $factura->estado_sri }}</span>
                        @elseif($factura->estado_sri === 'RECHAZADO')
                            <span class="badge badge-danger"><i class="fa-solid fa-xmark"></i> {{ $factura->estado_sri }}</span>
                        @elseif($factura->estado_sri === 'DEVUELTO' || $factura->estado_sri === 'DEVUELTA')
                            <span class="badge badge-warning"><i class="fa-solid fa-rotate-left"></i> {{ $factura->estado_sri }}</span>
                        @else
                            <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> {{ $factura->estado_sri ?? 'PENDIENTE' }}</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <button type="button" class="btn-card-action btn-primary btn-sm" onclick="verDetalleFactura({{ $factura->id }})" title="Ver Detalles">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <a href="{{ route('facturacion.pdf', $factura->id) }}" target="_blank" class="btn-card-action btn-secondary btn-sm" title="Descargar RIDE PDF">
                            <i class="fa-solid fa-file-pdf"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem; color: #64748b;">
                        No hay facturas registradas en el historial.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($facturas) && $facturas->hasPages())
    <div style="margin-top: 1rem;">
        {{ $facturas->links() }}
    </div>
    @endif
</div>

<!-- MODAL DETALLE FACTURA -->
<div class="modal-backdrop" id="modalDetalleFactura">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-file-invoice text-primary"></i> Detalle de Factura</h2>
            <button class="btn-close" onclick="closeModal('modalDetalleFactura')">&times;</button>
        </div>
        <div id="detalleFacturaBody" style="font-size: 0.9rem;">
            <p>Cargando detalles...</p>
        </div>
        <div style="margin-top: 1.5rem; border-top: 1px solid #e2e8f0; padding-top: 1rem; display: flex; justify-content: space-between;">
            <div>
                 <button type="button" id="btnReenviarCorreo" class="btn-card-action btn-primary" style="display: none;" onclick="reenviarCorreoFactura()">
                     <i class="fa-solid fa-envelope"></i> Reenviar Correo
                 </button>
            </div>
            <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalDetalleFactura')">Cerrar</button>
        </div>
    </div>
</div>

<script>
    function toggleAllFacturas() {
        const isChecked = document.getElementById('chkAll').checked;
        const checkboxes = document.querySelectorAll('.chkFactura');
        checkboxes.forEach(chk => chk.checked = isChecked);
    }

    async function reautorizarSeleccionadas() {
        const checkboxes = document.querySelectorAll('.chkFactura:checked');
        const ids = Array.from(checkboxes).map(chk => chk.value);
        
        if (ids.length === 0) {
            alert('Seleccione al menos una factura para reautorizar.');
            return;
        }

        if (!confirm(`¿Está seguro de reautorizar ${ids.length} factura(s)? La fecha de emisión se actualizará a la fecha de hoy.`)) {
            return;
        }

        // Show loading state
        const btn = document.querySelector('button[onclick="reautorizarSeleccionadas()"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';
        btn.disabled = true;

        try {
            const response = await fetch('{{ route("facturacion.reautorizar") }}', {
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

    async function verDetalleFactura(id) {
        openModal('modalDetalleFactura');
        const body = document.getElementById('detalleFacturaBody');
        body.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i><p>Cargando...</p></div>';
        
        try {
            const response = await fetch('/facturacion/' + id);
            const data = await response.json();
            
            if (data.success) {
                const fac = data.factura;
                const cli = fac.cliente;
                let html = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div style="background: #f8fafc; padding: 0.75rem; border-radius: 6px;">
                            <strong>Cliente:</strong> ${cli ? cli.razon_social : 'N/A'}<br>
                            <strong>Identificación:</strong> ${cli ? cli.identificacion : 'N/A'}<br>
                            <strong>Correo:</strong> ${cli ? cli.correo : 'N/A'}<br>
                        </div>
                        <div style="background: #f8fafc; padding: 0.75rem; border-radius: 6px;">
                            <strong>Emisión:</strong> ${fac.fecha_emision}<br>
                            <strong>Comprobante:</strong> ${fac.establecimiento}-${fac.punto_emision}-${fac.secuencial}<br>
                            <strong>Estado SRI:</strong> <span class="badge ${fac.estado_sri === 'AUTORIZADO' ? 'badge-success' : (fac.estado_sri === 'EN_PROCESO' ? 'badge-warning' : 'badge-danger')}">${fac.estado_sri}</span>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong>Mensaje SRI:</strong>
                        <div style="background: #f1f5f9; padding: 0.5rem; border-radius: 4px; font-family: monospace; font-size: 0.8rem; word-wrap: break-word; max-height: 100px; overflow-y: auto;">
                            ${fac.mensajes_sri || 'Ninguno'}
                        </div>
                    </div>
                    
                    <h3 style="font-size: 1rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.25rem;">Productos</h3>
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
                
                if (fac.detalles && fac.detalles.length > 0) {
                    fac.detalles.forEach(d => {
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
                        <div>Subtotal Sin Impuestos: <strong>$${parseFloat(fac.total_sin_impuestos).toFixed(2)}</strong></div>
                        <div>IVA: <strong>$${parseFloat(fac.valor_iva).toFixed(2)}</strong></div>
                        <div style="font-size: 1.1rem; margin-top: 0.25rem;">Total a Pagar: <strong style="color: var(--success);">$${parseFloat(fac.importe_total).toFixed(2)}</strong></div>
                    </div>
                `;
                
                body.innerHTML = html;
                
                // Show resend email button
                const btnReenviar = document.getElementById('btnReenviarCorreo');
                btnReenviar.style.display = 'inline-block';
                btnReenviar.setAttribute('onclick', `reenviarCorreoFactura(${id})`);
            } else {
                body.innerHTML = `<p style="color: red;">Error: ${data.message}</p>`;
                document.getElementById('btnReenviarCorreo').style.display = 'none';
            }
        } catch (err) {
            body.innerHTML = '<p style="color: red;">Ocurrió un error al cargar los datos.</p>';
            document.getElementById('btnReenviarCorreo').style.display = 'none';
        }
    }

    async function reenviarCorreoFactura(id) {
        if (!confirm('¿Estás seguro que deseas reenviar la factura por correo al cliente?')) return;
        
        const btn = document.getElementById('btnReenviarCorreo');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';
        btn.disabled = true;

        try {
            const response = await fetch(`/facturacion/${id}/reenviar-correo`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const data = await response.json();
            
            if (data.success) {
                alert('Correo enviado exitosamente.');
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
