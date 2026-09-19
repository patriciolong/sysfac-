<!-- _creacion.blade.php - NOTAS DE CREDITO -->
<!-- HEADER INFO BAR -->
<div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1rem; border-left: 4px solid var(--primary);">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <h1 style="font-size: 1.25rem; margin-bottom: 0.15rem;">
                <i class="fa-solid fa-file-circle-minus text-primary"></i> Emisión de Nota de Crédito
            </h1>
            <p style="margin: 0; font-size: 0.85rem;">
                Nota de Crédito <strong id="topSecuencial" style="color: var(--primary);">{{ $secuencial_siguiente }}</strong> &bull; Emisión: <strong>NORMAL</strong> &bull; Afecta directamente a una Factura
            </p>
        </div>
        <div style="display: flex; gap: 0.65rem; align-items: center;">
            <button class="btn-card-action btn-secondary btn-sm" onclick="openModal('modalBuscarFactura')">
                <i class="fa-solid fa-magnifying-glass"></i> Buscar Otra Factura
            </button>
        </div>
    </div>
</div>

<!-- POS LAYOUT (LEFT: INVOICE & ITEMS, RIGHT: CREDIT NOTE SUMMARY) -->
<div class="pos-layout">

    <!-- LEFT COLUMN: FACTURA SELECTION & PRODUCTS -->
    <div>
        <!-- SELECTOR DE FACTURA A MODIFICAR (BUSCADOR INTERACTIVO CON TECLADO) -->
        <div class="data-card" style="padding: 1rem; margin-bottom: 1rem; position: relative;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <label class="form-label" style="font-size: 0.9rem; font-weight: 700; margin: 0;">
                    <i class="fa-solid fa-receipt text-primary"></i> Factura a Modificar (Buscar con Teclado)
                </label>
                <span class="badge badge-info" id="badgeEstadoFac" style="display: none;">AUTORIZADA</span>
            </div>

            <div style="position: relative;">
                <div style="display: flex; gap: 0.5rem;">
                    <div style="position: relative; flex: 1;">
                        <input type="text" 
                               id="inputBuscarFactura" 
                               class="form-control" 
                               style="height: 38px; font-size: 0.88rem; padding-right: 2.2rem;" 
                               placeholder="Escriba número de factura, cliente o cédula/RUC para buscar..." 
                               autocomplete="off"
                               oninput="onInputBuscarFactura(event)"
                               onkeydown="onKeydownBuscarFactura(event)"
                               onfocus="onFocusBuscarFactura()">
                        <span id="spinnerBuscarFac" style="position: absolute; right: 10px; top: 10px; display: none; color: var(--primary);">
                            <i class="fa-solid fa-spinner fa-spin"></i>
                        </span>
                    </div>
                    <button type="button" class="btn-card-action btn-secondary btn-sm" onclick="limpiarSeleccionFactura()" title="Limpiar y buscar otra">
                        <i class="fa-solid fa-eraser"></i> Limpiar
                    </button>
                    <button type="button" class="btn-card-action btn-primary btn-sm" onclick="openModal('modalBuscarFactura'); buscarFacturasModal();" title="Búsqueda avanzada">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>

                <!-- Dropdown interactivo con navegación por teclado -->
                <div id="dropdownResultadosFactura" 
                     style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 1050; max-height: 260px; overflow-y: auto; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); padding: 0.25rem 0;">
                </div>
            </div>

            <!-- DATOS DE LA FACTURA SELECCIONADA -->
            <div id="infoFacturaSeleccionada" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.75rem 1rem; margin-top: 0.75rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.5rem; font-size: 0.85rem;">
                    <div>
                        <span style="color: #64748b;">Comprobante:</span><br>
                        <strong id="dispFacNumero" style="color: var(--primary);">---</strong>
                    </div>
                    <div>
                        <span style="color: #64748b;">Fecha Emisión:</span><br>
                        <strong id="dispFacFecha">---</strong>
                    </div>
                    <div>
                        <span style="color: #64748b;">Cliente:</span><br>
                        <strong id="dispFacCliente">---</strong>
                    </div>
                    <div>
                        <span style="color: #64748b;">Total Facturado:</span><br>
                        <strong id="dispFacTotal" style="color: var(--success); font-size: 0.95rem;">$0.00</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- MOTIVO Y TIPO DE MODIFICACION -->
        <div class="data-card" style="padding: 1rem; margin-bottom: 1rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 0.75rem;">
                <div>
                    <label class="form-label" style="font-size: 0.85rem;">
                        <i class="fa-solid fa-tags text-primary"></i> Tipo de Modificación
                    </label>
                    <select id="selectTipoModificacion" class="form-control" onchange="onTipoModificacionChange()">
                        <option value="DEVOLUCION" selected>DEVOLUCIÓN DE MERCADERÍA (Reingresa a Stock)</option>
                        <option value="DESCUENTO">DESCUENTO O AJUSTE DE VALOR (Sin afectar Stock)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-size: 0.85rem;">
                        <i class="fa-solid fa-comment-dots text-primary"></i> Motivo Predefinido
                    </label>
                    <select id="selectMotivoPredef" class="form-control" onchange="setMotivoTexto(this.value)">
                        <option value="Devolución de mercadería">Devolución de mercadería</option>
                        <option value="Descuento comercial post-venta">Descuento comercial post-venta</option>
                        <option value="Error en digitación de precios o cantidades">Error en digitación de precios o cantidades</option>
                        <option value="Devolución por garantía o defecto">Devolución por garantía o defecto</option>
                        <option value="Anulación total de la venta">Anulación total de la venta</option>
                        <option value="OTRO">Otro motivo específico...</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="form-label" style="font-size: 0.85rem;">
                    Razón de Modificación (Texto legal SRI - Máx 300 caract.)
                </label>
                <input type="text" id="inputMotivo" class="form-control" maxlength="300" value="Devolución de mercadería" placeholder="Ingrese el motivo de la Nota de Crédito..." required>
            </div>
        </div>

        <!-- LISTA DE PRODUCTOS DE LA FACTURA -->
        <div class="data-card" style="padding: 0; overflow: hidden;">
            <div style="padding: 0.75rem 1rem; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 0.95rem; margin: 0;">
                    <i class="fa-solid fa-boxes-stacked text-primary"></i> Productos Facturados Disponibles
                </h3>
                <button type="button" id="btnDevolverTodo" class="btn-card-action btn-secondary btn-sm" style="display: none;" onclick="agregarTodosLosItems()">
                    <i class="fa-solid fa-cart-arrow-down"></i> Acreditar Factura Completa
                </button>
            </div>

            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="custom-table" id="tablaItemsFactura">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th style="text-align: center; width: 80px;">Facturado</th>
                            <th style="text-align: right; width: 90px;">Precio Unit.</th>
                            <th style="text-align: center; width: 100px;">Cant. Acreditar</th>
                            <th style="text-align: center; width: 90px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyItemsFactura">
                        <tr>
                            <td colspan="5" style="text-align: center; color: #64748b; padding: 2rem 1rem;">
                                <i class="fa-solid fa-receipt" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 0.5rem; display: block;"></i>
                                Seleccione primero una factura en la parte superior para cargar sus productos
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: CREDIT NOTE SUMMARY (CART PANEL) -->
    <div>
        <div class="data-card pos-cart-panel" style="padding: 1rem;">
            <!-- CUSTOMER INFO BAR -->
            <div style="margin-bottom: 0.85rem; padding-bottom: 0.65rem; border-bottom: 1px solid var(--border-color);">
                <label class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem;">
                    <i class="fa-solid fa-user-check text-primary"></i> Cliente Afectado
                </label>
                <div id="clienteBox" style="font-size: 0.85rem; font-weight: 600; color: var(--text-main); background: #f8fafc; padding: 0.5rem 0.75rem; border-radius: 4px; border: 1px solid var(--border-color);">
                    <span id="clienteNombre">Ninguna factura seleccionada</span><br>
                    <small id="clienteDoc" style="color: #64748b; font-weight: normal;">Identificación: ---</small>
                </div>
            </div>

            <!-- CART ITEMS TABLE -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.45rem;">
                <h3 style="font-size: 0.95rem; margin: 0;">
                    <i class="fa-solid fa-list-check text-primary"></i> Detalle a Acreditar
                </h3>
                <button type="button" class="btn-card-action btn-secondary btn-sm" style="padding: 0.15rem 0.5rem; font-size: 0.75rem;" onclick="vaciarItemsNC()">
                    <i class="fa-solid fa-trash-can"></i> Limpiar
                </button>
            </div>
            
            <div class="cart-table-wrapper">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th style="width: 75px; text-align: center;">Cant.</th>
                            <th style="text-align: right; width: 65px;">Total</th>
                            <th style="width: 28px;"></th>
                        </tr>
                    </thead>
                    <tbody id="cartTableBodyNC">
                        <tr id="emptyCartRowNC">
                            <td colspan="4" style="text-align: center; color: var(--text-light); padding: 1.75rem 0.5rem;">
                                <i class="fa-solid fa-file-circle-minus" style="font-size: 1.8rem; margin-bottom: 0.4rem; color: #cbd5e1; display: block;"></i>
                                <p style="margin: 0; font-size: 0.82rem; font-weight: 600;">Agregue los ítems a devolver o acreditar</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TOTALS SUMMARY BREAKDOWN -->
            <div class="pos-totals-box">
                <div class="pos-total-row">
                    <span>Subtotal Sin Impuestos:</span>
                    <strong id="subtotalValNC">$0.00</strong>
                </div>
                <div class="pos-total-row">
                    <span>IVA ({{ $iva_defecto ?? 15 }}%):</span>
                    <strong id="ivaValNC">$0.00</strong>
                </div>
                <div class="pos-total-grand">
                    <span>TOTAL A ACREDITAR:</span>
                    <span id="totalValNC">$0.00</span>
                </div>
            </div>

            <!-- EMIT CREDIT NOTE BUTTON -->
            <button type="button" id="btnEmitirNC" class="btn-card-action btn-success" style="width: 100%; padding: 0.75rem; font-size: 1rem;" onclick="showConfirmModalNC()">
                <i class="fa-solid fa-paper-plane"></i> EMITIR NOTA DE CRÉDITO (SRI)
            </button>
        </div>
    </div>
</div>

<!-- MODAL BUSCAR FACTURA -->
<div class="modal-backdrop" id="modalBuscarFactura">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-magnifying-glass text-primary"></i> Buscar Factura a Modificar</h2>
            <button class="btn-close" onclick="closeModal('modalBuscarFactura')">&times;</button>
        </div>
        <div style="margin-bottom: 1rem; position: relative;">
            <input type="text" id="inputBuscarFacModal" class="form-control" placeholder="Escriba número de factura, cliente o cédula/RUC..." oninput="onInputBuscarFacModal(this.value)">
            <span id="spinnerBuscarFacModal" style="position: absolute; right: 12px; top: 11px; display: none; color: var(--primary);">
                <i class="fa-solid fa-spinner fa-spin"></i>
            </span>
        </div>
        <div style="max-height: 350px; overflow-y: auto;">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Comprobante</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th style="text-align: right;">Total</th>
                        <th style="text-align: center; width: 110px;">Acción</th>
                    </tr>
                </thead>
                <tbody id="tbodyModalFacturas">
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 1.5rem; color: #64748b;">
                            <i class="fa-solid fa-spinner fa-spin text-primary"></i> Cargando sugerencias...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL CONFIRMACION EMISION -->
<div class="modal-backdrop" id="modalConfirmacionNC">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-triangle-exclamation text-warning"></i> Confirmar Nota de Crédito</h2>
            <button class="btn-close" onclick="closeModal('modalConfirmacionNC')">&times;</button>
        </div>
        <p style="margin-bottom: 1rem;">¿Está seguro de emitir esta Nota de Crédito ante el SRI? Afectará legalmente a la factura seleccionada y no podrá ser revertida.</p>
        <div style="background: #f8fafc; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem;">
            <strong>Factura Afectada:</strong> <span id="confirmNCFactura">---</span><br>
            <strong>Cliente:</strong> <span id="confirmNCCliente">---</span><br>
            <strong>Motivo:</strong> <span id="confirmNCMotivo">---</span><br>
            <strong>Total a Acreditar:</strong> <span id="confirmNCTotal" style="color: var(--success); font-weight: bold; font-size: 1.1rem;">$0.00</span>
        </div>
        <div style="display: flex; gap: 0.65rem;">
            <button type="button" id="btnConfirmEmitirNC" class="btn-card-action btn-success" style="flex: 1;" onclick="procesarNotaCredito()" disabled>
                Esperar 2s...
            </button>
            <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalConfirmacionNC')">Cancelar</button>
        </div>
    </div>
</div>

<!-- MODAL NOTA CREDITO AUTORIZADA SRI EXITO -->
<div class="modal-backdrop" id="modalNCExitosa">
    <div class="modal-content" style="text-align: center; max-width: 480px;">
        <div style="width: 56px; height: 56px; background: #ecfdf5; color: #059669; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 0.85rem auto;">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <h2 style="font-size: 1.35rem; color: #059669; margin-bottom: 0.25rem;">¡Nota de Crédito Autorizada con Éxito!</h2>
        <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1rem;">
            Comprobante Nº: <strong id="modalNCComprobanteNum">{{ $secuencial_siguiente }}</strong> registrado en el SRI.
        </p>
        
        <div style="background: #f8fafc; padding: 0.85rem 1rem; border-radius: 8px; border: 1px dashed #cbd5e1; margin-bottom: 1.15rem; text-align: left; font-size: 0.825rem;">
            <p style="margin-bottom: 0.3rem;">Clave Acceso: <span id="modalNCClaveAcceso" style="font-family: monospace; font-size: 0.775rem; word-break: break-all; color: var(--text-main);">---</span></p>
            <p style="margin-bottom: 0.3rem;">Estado SRI: <strong style="color: #059669;">AUTORIZADO</strong></p>
            <p style="margin: 0;">Mensaje: <span style="color: var(--text-muted);">AUTORIZACION REGISTRADA EN EL SRI EXITOSAMENTE.</span></p>
        </div>

        <div style="display: flex; gap: 0.65rem;">
            <a id="btnDescargarRideExito" href="#" target="_blank" class="btn-card-action btn-primary" style="flex: 1; text-align: center; text-decoration: none;">
                <i class="fa-solid fa-file-pdf"></i> Ver / Imprimir RIDE PDF
            </a>
            <button class="btn-card-action btn-secondary" onclick="closeModal('modalNCExitosa'); window.location.reload();">
                Nueva Nota de Crédito
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.IVA_DEFECTO = {{ $iva_defecto ?? 15 }};
    let facturaActual = null;
    let itemsFactura = [];
    let itemsNC = [];

    function onTipoModificacionChange() {
        const tipo = document.getElementById('selectTipoModificacion').value;
        const selMotivo = document.getElementById('selectMotivoPredef');
        if (tipo === 'DESCUENTO') {
            selMotivo.value = 'Descuento comercial post-venta';
            document.getElementById('inputMotivo').value = 'Descuento comercial post-venta';
        } else {
            selMotivo.value = 'Devolución de mercadería';
            document.getElementById('inputMotivo').value = 'Devolución de mercadería';
        }
    }

    function setMotivoTexto(val) {
        if (val === 'OTRO') {
            document.getElementById('inputMotivo').value = '';
            document.getElementById('inputMotivo').focus();
        } else {
            document.getElementById('inputMotivo').value = val;
        }
    }

    let searchDebounceTimeout = null;
    let facturasEncontradas = [];
    let activeResultIndex = -1;

    function onInputBuscarFactura(e) {
        const q = e.target.value.trim();
        clearTimeout(searchDebounceTimeout);
        searchDebounceTimeout = setTimeout(() => {
            ejecutarBusquedaFacturas(q);
        }, 220);
    }

    function onFocusBuscarFactura() {
        const q = document.getElementById('inputBuscarFactura').value.trim();
        if (facturasEncontradas.length === 0) {
            ejecutarBusquedaFacturas(q);
        } else {
            document.getElementById('dropdownResultadosFactura').style.display = 'block';
        }
    }

    async function ejecutarBusquedaFacturas(q) {
        const spinner = document.getElementById('spinnerBuscarFac');
        const dropdown = document.getElementById('dropdownResultadosFactura');
        if (spinner) spinner.style.display = 'inline-block';

        try {
            const res = await fetch(`/notas-credito/buscar-facturas?q=${encodeURIComponent(q)}`);
            const data = await res.json();
            if (spinner) spinner.style.display = 'none';

            if (data.success) {
                facturasEncontradas = data.facturas || [];
                renderDropdownFacturas(facturasEncontradas);
            }
        } catch (err) {
            if (spinner) spinner.style.display = 'none';
            console.error('Error buscando facturas:', err);
        }
    }

    function renderDropdownFacturas(facturas) {
        const dropdown = document.getElementById('dropdownResultadosFactura');
        activeResultIndex = -1;

        if (facturas.length === 0) {
            dropdown.innerHTML = `
                <div style="padding: 0.75rem 1rem; color: #64748b; font-size: 0.85rem; text-align: center;">
                    No se encontraron facturas autorizadas con ese criterio.
                </div>
            `;
            dropdown.style.display = 'block';
            return;
        }

        let html = '';
        facturas.forEach((f, idx) => {
            html += `
                <div class="item-resultado-fac" id="itemFac_${idx}" 
                     style="padding: 0.55rem 0.85rem; cursor: pointer; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;"
                     onclick="seleccionarFacturaDesdeDropdown(${idx})"
                     onmouseenter="setActiveResultIndex(${idx})">
                    <div>
                        <strong style="color: var(--primary); font-size: 0.88rem;">Factura #${f.comprobante}</strong>
                        <div style="font-size: 0.75rem; color: #475569;">
                            ${f.cliente_nombre} &bull; <span style="font-family: monospace;">${f.cliente_identificacion}</span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="color: var(--success); font-weight: 700; font-size: 0.9rem;">$${f.total.toFixed(2)}</span>
                        <div style="font-size: 0.72rem; color: #94a3b8;">${f.fecha_emision}</div>
                    </div>
                </div>
            `;
        });

        dropdown.innerHTML = html;
        dropdown.style.display = 'block';
    }

    function setActiveResultIndex(idx) {
        activeResultIndex = idx;
        const items = document.querySelectorAll('.item-resultado-fac');
        items.forEach((it, i) => {
            if (i === idx) {
                it.style.backgroundColor = '#f1f5f9';
                it.style.borderLeft = '3px solid var(--primary)';
            } else {
                it.style.backgroundColor = 'transparent';
                it.style.borderLeft = 'none';
            }
        });
    }

    function onKeydownBuscarFactura(e) {
        const dropdown = document.getElementById('dropdownResultadosFactura');
        if (dropdown.style.display !== 'block' || facturasEncontradas.length === 0) {
            if (e.key === 'ArrowDown' || e.key === 'Enter') {
                onFocusBuscarFactura();
            }
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeResultIndex = Math.min(activeResultIndex + 1, facturasEncontradas.length - 1);
            setActiveResultIndex(activeResultIndex);
            scrollActiveIntoView();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeResultIndex = Math.max(activeResultIndex - 1, 0);
            setActiveResultIndex(activeResultIndex);
            scrollActiveIntoView();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeResultIndex >= 0 && activeResultIndex < facturasEncontradas.length) {
                seleccionarFacturaDesdeDropdown(activeResultIndex);
            } else if (facturasEncontradas.length === 1) {
                seleccionarFacturaDesdeDropdown(0);
            }
        } else if (e.key === 'Escape') {
            dropdown.style.display = 'none';
        }
    }

    function scrollActiveIntoView() {
        const activeElem = document.getElementById(`itemFac_${activeResultIndex}`);
        if (activeElem) {
            activeElem.scrollIntoView({ block: 'nearest' });
        }
    }

    function seleccionarFacturaDesdeDropdown(idx) {
        const f = facturasEncontradas[idx];
        if (!f) return;
        document.getElementById('inputBuscarFactura').value = `Factura #${f.comprobante} - ${f.cliente_nombre}`;
        document.getElementById('dropdownResultadosFactura').style.display = 'none';
        cargarFacturaPorId(f.id);
    }

    function limpiarSeleccionFactura() {
        facturaActual = null;
        itemsFactura = [];
        itemsNC = [];
        document.getElementById('inputBuscarFactura').value = '';
        document.getElementById('dropdownResultadosFactura').style.display = 'none';
        document.getElementById('infoFacturaSeleccionada').style.display = 'none';
        document.getElementById('badgeEstadoFac').style.display = 'none';
        document.getElementById('btnDevolverTodo').style.display = 'none';
        document.getElementById('clienteNombre').innerText = 'Ninguna factura seleccionada';
        document.getElementById('clienteDoc').innerText = 'Identificación: ---';
        document.getElementById('tbodyItemsFactura').innerHTML = '<tr><td colspan="5" style="text-align: center; color: #64748b; padding: 2rem 1rem;"><i class="fa-solid fa-receipt" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 0.5rem; display: block;"></i>Seleccione primero una factura en la parte superior para cargar sus productos</td></tr>';
        renderItemsNC();
    }

    // Modal búsqueda avanzada
    let modalDebounceTimeout = null;
    function onInputBuscarFacModal(q) {
        clearTimeout(modalDebounceTimeout);
        modalDebounceTimeout = setTimeout(() => {
            buscarFacturasModal(q);
        }, 250);
    }

    async function buscarFacturasModal(q = '') {
        const tbody = document.getElementById('tbodyModalFacturas');
        const spinner = document.getElementById('spinnerBuscarFacModal');
        if (spinner) spinner.style.display = 'inline-block';

        try {
            const res = await fetch(`/notas-credito/buscar-facturas?q=${encodeURIComponent(q)}`);
            const data = await res.json();
            if (spinner) spinner.style.display = 'none';

            if (data.success) {
                const facturas = data.facturas || [];
                if (facturas.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 1.5rem; color: #64748b;">No se encontraron facturas autorizadas.</td></tr>';
                    return;
                }

                let html = '';
                facturas.forEach(f => {
                    html += `
                        <tr>
                            <td><strong>${f.comprobante}</strong></td>
                            <td>${f.fecha_emision}</td>
                            <td>${f.cliente_nombre}<br><small style="color: #64748b;">${f.cliente_identificacion}</small></td>
                            <td style="text-align: right; color: var(--success); font-weight: 600;">$${f.total.toFixed(2)}</td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-card-action btn-primary btn-sm" onclick="seleccionarFacturaDesdeModal(${f.id}, '${f.comprobante}', '${f.cliente_nombre.replace(/'/g, "\\'")}')">
                                    <i class="fa-solid fa-check"></i> Seleccionar
                                </button>
                            </td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            }
        } catch (e) {
            if (spinner) spinner.style.display = 'none';
            console.error(e);
        }
    }

    function seleccionarFacturaDesdeModal(id, comprobante, clienteNombre) {
        document.getElementById('inputBuscarFactura').value = `Factura #${comprobante} - ${clienteNombre}`;
        closeModal('modalBuscarFactura');
        cargarFacturaPorId(id);
    }

    // Cerrar dropdown si se hace clic fuera
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('dropdownResultadosFactura');
        const input = document.getElementById('inputBuscarFactura');
        if (dropdown && !dropdown.contains(event.target) && event.target !== input) {
            dropdown.style.display = 'none';
        }
    });

    async function cargarFacturaPorId(id) {
        if (!id) return;

        // Visual feedback
        const tbody = document.getElementById('tbodyItemsFactura');
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem;"><i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i><p>Cargando datos de la factura...</p></td></tr>';

        try {
            const res = await fetch(`/notas-credito/factura/${id}`);
            const data = await res.json();

            if (!data.success) {
                alert(data.message || 'Error al cargar la factura');
                return;
            }

            facturaActual = data.factura;
            itemsFactura = facturaActual.detalles || [];
            itemsNC = []; // reset NC items

            // Mostrar bloque de información de la factura
            document.getElementById('dispFacNumero').innerText = facturaActual.numero_comprobante || `${facturaActual.establecimiento}-${facturaActual.punto_emision}-${facturaActual.secuencial}`;
            document.getElementById('dispFacFecha').innerText = facturaActual.fecha_emision;
            document.getElementById('dispFacCliente').innerText = facturaActual.cliente ? facturaActual.cliente.razon_social : 'Consumidor Final';
            document.getElementById('dispFacTotal').innerText = '$' + parseFloat(facturaActual.importe_total).toFixed(2);
            document.getElementById('infoFacturaSeleccionada').style.display = 'block';
            document.getElementById('badgeEstadoFac').style.display = 'inline-block';
            document.getElementById('btnDevolverTodo').style.display = 'inline-block';

            // Actualizar panel de cliente a la derecha
            if (facturaActual.cliente) {
                document.getElementById('clienteNombre').innerText = facturaActual.cliente.razon_social;
                document.getElementById('clienteDoc').innerText = `ID: ${facturaActual.cliente.identificacion} (${facturaActual.cliente.correo || 'Sin correo'})`;
            } else {
                document.getElementById('clienteNombre').innerText = 'Consumidor Final';
                document.getElementById('clienteDoc').innerText = 'ID: 9999999999999';
            }

            renderItemsFactura();
            renderItemsNC();

        } catch (e) {
            console.error(e);
            alert('Ocurrió un error al obtener la factura.');
        }
    }

    function renderItemsFactura() {
        const tbody = document.getElementById('tbodyItemsFactura');
        if (!itemsFactura || itemsFactura.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #64748b; padding: 1.5rem;">La factura no tiene detalles registrados.</td></tr>';
            return;
        }

        let html = '';
        itemsFactura.forEach((det, idx) => {
            const cantMax = parseFloat(det.cantidad);
            const precio = parseFloat(det.precio_unitario);
            html += `
                <tr>
                    <td>
                        <strong>${det.descripcion}</strong>
                        <div style="font-size: 0.725rem; color: #64748b; font-family: monospace;">SKU: ${det.codigo_principal || det.producto_id}</div>
                    </td>
                    <td style="text-align: center; font-weight: 600;">${cantMax.toFixed(2)}</td>
                    <td style="text-align: right; color: var(--success); font-weight: 600;">$${precio.toFixed(2)}</td>
                    <td style="text-align: center;">
                        <input type="number" id="inputQty_${idx}" class="form-control" style="width: 75px; text-align: center; padding: 0.2rem 0.4rem; margin: 0 auto;" min="0.01" max="${cantMax}" step="any" value="${cantMax}">
                    </td>
                    <td style="text-align: center;">
                        <button type="button" class="btn-card-action btn-primary btn-sm" onclick="agregarItemFacturaANC(${idx})" title="Acreditar este producto">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }

    function agregarItemFacturaANC(idx) {
        const det = itemsFactura[idx];
        const input = document.getElementById(`inputQty_${idx}`);
        const qty = parseFloat(input.value);
        const maxQty = parseFloat(det.cantidad);

        if (isNaN(qty) || qty <= 0) {
            alert('Ingrese una cantidad válida mayor a 0.');
            return;
        }

        if (qty > maxQty) {
            alert(`La cantidad a acreditar no puede ser mayor que la facturada (${maxQty}).`);
            return;
        }

        // Check if already in NC items
        const existing = itemsNC.find(item => item.producto_id === det.producto_id);
        if (existing) {
            existing.qty = qty;
        } else {
            itemsNC.push({
                producto_id: det.producto_id,
                nombre: det.descripcion,
                precio: parseFloat(det.precio_unitario),
                tarifa_iva: parseFloat(det.tarifa_iva || window.IVA_DEFECTO),
                codigo_iva: det.codigo_impuesto_iva,
                qty: qty,
                maxQty: maxQty
            });
        }

        renderItemsNC();
    }

    function agregarTodosLosItems() {
        if (!itemsFactura || itemsFactura.length === 0) return;

        itemsNC = itemsFactura.map(det => ({
            producto_id: det.producto_id,
            nombre: det.descripcion,
            precio: parseFloat(det.precio_unitario),
            tarifa_iva: parseFloat(det.tarifa_iva || window.IVA_DEFECTO),
            codigo_iva: det.codigo_impuesto_iva,
            qty: parseFloat(det.cantidad),
            maxQty: parseFloat(det.cantidad)
        }));

        renderItemsNC();
    }

    function vaciarItemsNC() {
        itemsNC = [];
        renderItemsNC();
    }

    function eliminarItemNC(index) {
        itemsNC.splice(index, 1);
        renderItemsNC();
    }

    function actualizarCantItemNC(index, newQty) {
        const qty = parseFloat(newQty);
        if (isNaN(qty) || qty <= 0) return;
        if (qty > itemsNC[index].maxQty) {
            alert(`No puede superar la cantidad facturada (${itemsNC[index].maxQty}).`);
            itemsNC[index].qty = itemsNC[index].maxQty;
        } else {
            itemsNC[index].qty = qty;
        }
        renderItemsNC();
    }

    function renderItemsNC() {
        const tbody = document.getElementById('cartTableBodyNC');
        if (itemsNC.length === 0) {
            tbody.innerHTML = `
                <tr id="emptyCartRowNC">
                    <td colspan="4" style="text-align: center; color: var(--text-light); padding: 1.75rem 0.5rem;">
                        <i class="fa-solid fa-file-circle-minus" style="font-size: 1.8rem; margin-bottom: 0.4rem; color: #cbd5e1; display: block;"></i>
                        <p style="margin: 0; font-size: 0.82rem; font-weight: 600;">Agregue los ítems a devolver o acreditar</p>
                    </td>
                </tr>
            `;
            calcularTotalesNC();
            return;
        }

        let html = '';
        itemsNC.forEach((item, index) => {
            const lineTotal = item.precio * item.qty;
            html += `
                <tr>
                    <td style="vertical-align: middle;">
                        <div style="font-weight: 600; font-size: 0.82rem; color: var(--text-main);">${item.nombre}</div>
                        <div style="font-size: 0.72rem; color: #64748b;">$${item.precio.toFixed(2)} c/u</div>
                    </td>
                    <td style="text-align: center; vertical-align: middle;">
                        <input type="number" class="form-control" style="width: 60px; height: 26px; padding: 0.1rem; text-align: center; font-size: 0.8rem; margin: 0 auto;" min="0.01" max="${item.maxQty}" step="any" value="${item.qty}" onchange="actualizarCantItemNC(${index}, this.value)">
                    </td>
                    <td style="text-align: right; font-weight: 700; color: var(--text-main); vertical-align: middle; font-size: 0.85rem;">
                        $${lineTotal.toFixed(2)}
                    </td>
                    <td style="text-align: center; vertical-align: middle;">
                        <button type="button" style="border: none; background: transparent; color: var(--danger); cursor: pointer; padding: 2px;" onclick="eliminarItemNC(${index})">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        calcularTotalesNC();
    }

    function calcularTotalesNC() {
        let subtotal = 0;
        let iva = 0;

        itemsNC.forEach(item => {
            const line = item.precio * item.qty;
            subtotal += line;
            if (item.tarifa_iva > 0) {
                iva += line * (item.tarifa_iva / 100);
            }
        });

        const total = subtotal + iva;

        document.getElementById('subtotalValNC').innerText = '$' + subtotal.toFixed(2);
        document.getElementById('ivaValNC').innerText = '$' + iva.toFixed(2);
        document.getElementById('totalValNC').innerText = '$' + total.toFixed(2);
    }

    function showConfirmModalNC() {
        if (!facturaActual) {
            alert('Debe seleccionar primero una Factura a modificar.');
            return;
        }

        if (itemsNC.length === 0) {
            alert('Debe agregar al menos un producto para emitir la Nota de Crédito.');
            return;
        }

        const motivo = document.getElementById('inputMotivo').value.trim();
        if (!motivo) {
            alert('Debe ingresar el motivo de la Nota de Crédito.');
            document.getElementById('inputMotivo').focus();
            return;
        }

        const numFac = facturaActual.numero_comprobante || facturaActual.numero_documento || `${facturaActual.establecimiento}-${facturaActual.punto_emision}-${facturaActual.secuencial}`;
        document.getElementById('confirmNCFactura').innerText = numFac;
        document.getElementById('confirmNCCliente').innerText = facturaActual.cliente ? facturaActual.cliente.razon_social : 'Consumidor Final';
        document.getElementById('confirmNCMotivo').innerText = motivo;
        document.getElementById('confirmNCTotal').innerText = document.getElementById('totalValNC').innerText;

        const btnConfirm = document.getElementById('btnConfirmEmitirNC');
        btnConfirm.disabled = true;
        btnConfirm.innerHTML = 'Esperar 2s...';

        openModal('modalConfirmacionNC');

        let seconds = 2;
        const interval = setInterval(() => {
            seconds--;
            if (seconds > 0) {
                btnConfirm.innerHTML = `Esperar ${seconds}s...`;
            } else {
                clearInterval(interval);
                btnConfirm.disabled = false;
                btnConfirm.innerHTML = '<i class="fa-solid fa-check"></i> Confirmar y Enviar al SRI';
            }
        }, 1000);
    }

    async function procesarNotaCredito() {
        const btn = document.getElementById('btnConfirmEmitirNC');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Firmando y autorizando con SRI...';
        btn.disabled = true;

        const payload = {
            factura_id: facturaActual.id,
            motivo: document.getElementById('inputMotivo').value.trim(),
            tipo_modificacion: document.getElementById('selectTipoModificacion').value,
            items: itemsNC.map(i => ({
                id: i.producto_id,
                qty: i.qty
            }))
        };

        try {
            const res = await fetch('{{ route("notas-credito.emitir") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            });

            let data;
            const textResponse = await res.text();
            try {
                data = JSON.parse(textResponse);
            } catch (jsonErr) {
                console.error("Respuesta no JSON:", textResponse);
                alert('Ocurrió un error en el servidor (HTTP ' + res.status + '). Revise los logs.');
                btn.innerHTML = originalText;
                btn.disabled = false;
                return;
            }

            closeModal('modalConfirmacionNC');

            if (res.ok && data.success) {
                document.getElementById('modalNCComprobanteNum').innerText = data.comprobante;
                document.getElementById('modalNCClaveAcceso').innerText = data.clave_acceso;
                
                // Actualizar enlace RIDE PDF
                const btnPdf = document.getElementById('btnDescargarRideExito');
                if (data.id) {
                    btnPdf.href = `/notas-credito/${data.id}/pdf`;
                }

                if (data.siguiente_secuencial) {
                    document.getElementById('topSecuencial').innerText = data.siguiente_secuencial;
                }

                openModal('modalNCExitosa');
            } else {
                alert('Atención al procesar en SRI: ' + (data.message || 'Error') + (data.error ? ('\n\nDetalle: ' + data.error) : ''));
                btn.innerHTML = originalText;
                btn.disabled = false;
            }

        } catch (e) {
            console.error(e);
            alert('Error de red o comunicación al procesar la Nota de Crédito: ' + e.message);
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
</script>
@endpush
