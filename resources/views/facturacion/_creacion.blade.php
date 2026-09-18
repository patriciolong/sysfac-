<!-- _creacion.blade.php --><!-- POS HEADER INFO BAR -->
<div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1rem; border-left: 4px solid var(--primary);">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <h1 style="font-size: 1.25rem; margin-bottom: 0.15rem;">
                <i class="fa-solid fa-cash-register text-primary"></i> Punto de Venta (POS)
            </h1>
            <p style="margin: 0; font-size: 0.85rem;">
                Factura SRI Nº: <strong id="topSecuencial" style="color: var(--primary);">{{ $secuencial_siguiente }}</strong> &bull; Emisión: <strong>NORMAL</strong>
            </p>
        </div>
        <div style="display: flex; gap: 0.65rem; align-items: center;">
            <button class="btn-card-action btn-secondary btn-sm" onclick="openModal('modalCliente')">
                <i class="fa-solid fa-user-plus"></i> Nuevo Cliente
            </button>
            <span class="badge badge-success">
                <i class="fa-solid fa-wifi"></i> SRI CONECTADO
            </span>
        </div>
    </div>
</div>

<!-- POS LAYOUT (LEFT: CATALOG, RIGHT: CART) -->
<div class="pos-layout">

    <!-- LEFT COLUMN: PRODUCT CATALOG -->
    <div>
        <!-- SEARCH & CATEGORY FILTERS -->
        <div class="data-card" style="padding: 0.85rem 1rem; margin-bottom: 1rem;">
            <div style="display: flex; gap: 0.5rem; margin-bottom: 0.55rem;">
                <input type="text" id="inputSearch" class="form-control" placeholder="Buscar producto por nombre o código de barras (F2)..." onkeyup="filterProducts()" style="flex: 1;">
                <div style="display: flex; border: 1px solid var(--border-color); border-radius: var(--radius-sm); overflow: hidden;">
                    <button type="button" id="btnViewTable" class="btn-card-action btn-primary btn-sm" style="border-radius: 0;" onclick="switchPosView('table')">
                        <i class="fa-solid fa-table-list"></i> Tabla
                    </button>
                    <button type="button" id="btnViewGrid" class="btn-card-action btn-secondary btn-sm" style="border-radius: 0;" onclick="switchPosView('grid')">
                        <i class="fa-solid fa-grip"></i> Tarjetas
                    </button>
                </div>
            </div>
            
            <div class="category-chips-bar" id="categoryChips">
                <button type="button" class="chip-btn active" onclick="filterCategory(this, 'TODOS')">Todos los Productos</button>
                <button type="button" class="chip-btn" onclick="filterCategory(this, 'Suplementos')">Suplementos</button>
                <button type="button" class="chip-btn" onclick="filterCategory(this, 'Vitaminas')">Vitaminas</button>
                <button type="button" class="chip-btn" onclick="filterCategory(this, 'Naturales')">Naturales</button>
            </div>
        </div>

        <!-- PRODUCT TABLE VIEW (DEFAULT) -->
        <div class="data-card" id="posTableView" style="padding: 0; overflow: hidden;">
            <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                <table class="custom-table" id="posProductsTable">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th style="text-align: right;">Stock</th>
                            <th style="text-align: right;">Precio</th>
                            <th style="text-align: center; width: 80px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productos as $prod)
                        <tr class="pos-table-row" data-category="{{ $prod['categoria'] }}" data-name="{{ strtolower($prod['nombre']) }}" style="cursor: pointer;" onclick="addToCart({{ json_encode($prod) }})">
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 28px; height: 28px; background: var(--primary-light); color: var(--primary); border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;">
                                        <i class="fa-solid {{ $prod['icono'] }}"></i>
                                    </div>
                                    <div>
                                        <strong style="color: var(--text-main);">{{ $prod['nombre'] }}</strong>
                                        <div style="font-size: 0.725rem; color: #64748b; font-family: monospace;">SKU: {{ $prod['codigo'] ?? $prod['codigo_principal'] ?? 'PROD' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-info">{{ $prod['categoria'] }}</span></td>
                            <td style="text-align: right; font-weight: 600;">
                                @if($prod['stock'] > 5)
                                    <span style="color: var(--success);">{{ $prod['stock'] }} ud</span>
                                @else
                                    <span style="color: var(--warning); font-weight: 700;">{{ $prod['stock'] }} ud</span>
                                @endif
                            </td>
                            <td style="text-align: right; color: var(--success); font-weight: 700; font-size: 0.95rem;">
                                ${{ number_format($prod['precio'], 2) }}
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-card-action btn-primary btn-sm" style="padding: 0.25rem 0.6rem;" onclick="event.stopPropagation(); addToCart({{ json_encode($prod) }})">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PRODUCT CARDS GRID (ALTERNATIVE) -->
        <div class="pos-products-grid" id="posGridView" style="display: none;">
            @foreach($productos as $prod)
            <div class="product-item-card" data-category="{{ $prod['categoria'] }}" data-name="{{ strtolower($prod['nombre']) }}" onclick="addToCart({{ json_encode($prod) }})">
                <div class="product-item-img">
                    <i class="fa-solid {{ $prod['icono'] }}"></i>
                </div>
                <div class="product-item-name" title="{{ $prod['nombre'] }}">{{ $prod['nombre'] }}</div>
                <div class="product-item-stock">Stock: <strong>{{ $prod['stock'] }}</strong> ud</div>
                <div class="product-item-price">${{ number_format($prod['precio'], 2) }}</div>
                <button type="button" class="btn-card-action btn-primary btn-sm" style="width: 100%; margin-top: 0.5rem;" onclick="event.stopPropagation(); addToCart({{ json_encode($prod) }})">
                    <i class="fa-solid fa-plus"></i> AGREGAR
                </button>
            </div>
            @endforeach
        </div>
    </div>

    <!-- RIGHT COLUMN: BILLING CART PANEL -->
    <div>
        <div class="data-card pos-cart-panel" style="padding: 1rem;">
            <!-- CUSTOMER SELECTION -->
            <div style="margin-bottom: 0.85rem; padding-bottom: 0.65rem; border-bottom: 1px solid var(--border-color);">
                <label class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem;">
                    <i class="fa-solid fa-user-check text-primary"></i> Cliente de la Factura
                </label>
                <select id="selectCliente" class="form-control" style="font-weight: 600; background-color: #f8fafc; height: 36px; font-size: 0.84rem;">
                    @foreach($clientes as $cli)
                    <option value="{{ $cli['id'] }}" {{ request('cliente_id') == $cli['id'] ? 'selected' : '' }}>{{ $cli['razon_social'] }} ({{ $cli['identificacion'] }})</option>
                    @endforeach
                </select>
            </div>

            <!-- CART ITEMS TABLE -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.45rem;">
                <h3 style="font-size: 0.95rem; margin: 0;"><i class="fa-solid fa-cart-shopping text-primary"></i> Detalle de Venta</h3>
                <button type="button" class="btn-card-action btn-secondary btn-sm" style="padding: 0.15rem 0.5rem; font-size: 0.75rem;" onclick="clearCart()">
                    <i class="fa-solid fa-trash-can"></i> Vaciar
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
                    <tbody id="cartTableBody">
                        <tr id="emptyCartRow">
                            <td colspan="4" style="text-align: center; color: var(--text-light); padding: 1.75rem 0.5rem;">
                                <i class="fa-solid fa-basket-shopping" style="font-size: 1.8rem; margin-bottom: 0.4rem; color: #cbd5e1; display: block;"></i>
                                <p style="margin: 0; font-size: 0.82rem; font-weight: 600;">Haga clic en un producto para agregarlo</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TOTALS SUMMARY BREAKDOWN -->
            <div class="pos-totals-box">
                <div class="pos-total-row">
                    <span>Subtotal Sin Impuestos:</span>
                    <strong id="subtotalVal">$0.00</strong>
                </div>
                <div class="pos-total-row">
                    <span>IVA (15%):</span>
                    <strong id="ivaVal">$0.00</strong>
                </div>
                <div class="pos-total-grand">
                    <span>TOTAL:</span>
                    <span id="totalVal">$0.00</span>
                </div>
            </div>

            <!-- PAYMENT METHOD SELECTOR -->
            <div style="margin-bottom: 0.85rem;">
                <label class="form-label" style="font-size: 0.8rem; margin-bottom: 0.3rem;">
                    <i class="fa-solid fa-wallet text-primary"></i> Forma de Pago
                </label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.4rem;">
                    @foreach($metodos_pago as $idx => $met)
                    <button type="button" class="btn-card-action {{ $idx === 0 ? 'btn-primary' : 'btn-secondary' }} btn-sm" data-id="{{ $met['id'] }}" onclick="selectPaymentMethod(this, {{ $met['id'] }})">
                        <i class="fa-solid {{ $met['icono'] }}"></i> {{ $met['nombre'] }}
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- EMIT INVOICE BUTTON -->
            <button type="button" id="btnEmitirFactura" class="btn-card-action btn-success" style="width: 100%; padding: 0.75rem; font-size: 1rem;" onclick="showConfirmModal()">
                <i class="fa-solid fa-paper-plane"></i> EMITIR FACTURA (SRI)
            </button>
        </div>
    </div>
</div>

<!-- MODAL NUEVO CLIENTE -->
<div class="modal-backdrop" id="modalCliente">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-user-plus text-primary"></i> Registrar Nuevo Cliente</h2>
            <button class="btn-close" onclick="closeModal('modalCliente')">&times;</button>
        </div>
        <form id="formNuevoCliente" onsubmit="saveCustomer(event)">
            <div class="form-group">
                <label class="form-label">Tipo de Identificación SRI</label>
                <select name="tipo_identificacion" class="form-control">
                    <option value="05">Cédula de Identidad (10 dígitos)</option>
                    <option value="04">RUC (13 dígitos)</option>
                    <option value="06">Pasaporte</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Número de Cédula / RUC</label>
                <input type="text" name="identificacion" class="form-control" placeholder="Ej: 1723456789" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nombres y Apellidos / Razón Social</label>
                <input type="text" name="razon_social" class="form-control" placeholder="Ej: Carlos Eduardo Mendoza" required>
            </div>
            <div class="form-group">
                <label class="form-label">Correo Electrónico (Para envío de XML y PDF)</label>
                <input type="email" name="correo" class="form-control" placeholder="cliente@correo.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Dirección de Domicilio</label>
                <input type="text" name="direccion" class="form-control" placeholder="Av. Amazonas y Colón">
            </div>
            <div style="display: flex; gap: 0.65rem; margin-top: 1.25rem;">
                <button type="submit" class="btn-card-action btn-primary" style="flex: 1;">Guardar y Seleccionar</button>
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalCliente')">Cancelar</button>
            </div>
        </form>
    </div>
</div>


<!-- MODAL CONFIRMACION EMISION -->
<div class="modal-backdrop" id="modalConfirmacion">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-triangle-exclamation text-warning"></i> Confirmar Emisión</h2>
            <button class="btn-close" onclick="closeModal('modalConfirmacion')">&times;</button>
        </div>
        <p style="margin-bottom: 1rem;">¿Está seguro de enviar esta factura al SRI? Los datos no podrán ser modificados posteriormente.</p>
        <div style="background: #f8fafc; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
            <strong>Cliente:</strong> <span id="confirmCliente"></span><br>
            <strong>Total a cobrar:</strong> <span id="confirmTotal" style="color: var(--success); font-weight: bold; font-size: 1.1rem;"></span>
        </div>
        <div style="display: flex; gap: 0.65rem;">
            <button type="button" id="btnConfirmEmitir" class="btn-card-action btn-success" style="flex: 1;" onclick="processInvoice()" disabled>
                Esperar 2s...
            </button>
            <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalConfirmacion')">Cancelar</button>
        </div>
    </div>
</div>

<!-- MODAL FACTURA AUTORIZADA SRI EXITO -->
<div class="modal-backdrop" id="modalFacturaExitosa">
    <div class="modal-content" style="text-align: center; max-width: 480px;">
        <div style="width: 56px; height: 56px; background: #ecfdf5; color: #059669; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 0.85rem auto;">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <h2 style="font-size: 1.35rem; color: #059669; margin-bottom: 0.25rem;">¡Factura Autorizada con Éxito!</h2>
        <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1rem;">
            Comprobante Nº: <strong id="modalComprobanteNum">{{ $secuencial_siguiente }}</strong> registrado en el SRI.
        </p>
        
        <div style="background: #f8fafc; padding: 0.85rem 1rem; border-radius: 8px; border: 1px dashed #cbd5e1; margin-bottom: 1.15rem; text-align: left; font-size: 0.825rem;">
            <p style="margin-bottom: 0.3rem;">Clave Acceso: <span id="modalClaveAcceso" style="font-family: monospace; font-size: 0.775rem; word-break: break-all; color: var(--text-main);">1509202601179294820100110010010000001861234567819</span></p>
            <p style="margin-bottom: 0.3rem;">Estado SRI: <strong style="color: #059669;">AUTORIZADO</strong></p>
            <p style="margin: 0;">Mensaje: <span style="color: var(--text-muted);">AUTORIZACION REGISTRADA EN EL SRI EXITOSAMENTE.</span></p>
        </div>

        <div style="display: flex; gap: 0.65rem;">
            <button class="btn-card-action btn-primary" style="flex: 1;" onclick="alert('Imprimiendo comprobante térmico...'); closeModal('modalFacturaExitosa'); clearCart();">
                <i class="fa-solid fa-print"></i> Imprimir Ticket
            </button>
            <button class="btn-card-action btn-secondary" onclick="closeModal('modalFacturaExitosa'); clearCart();">
                Nueva Venta
            </button>
        </div>
    </div>
</div>



@push('scripts')
<script>
    
    function validarIdentificacion(numero, tipo) {
        if (!numero) return false;
        numero = numero.trim();
        
        if (tipo === '05' || tipo === 'Cédula') { // Cedula
            if (numero.length !== 10) return false;
            return validarCedulaEcuatoriana(numero);
        } else if (tipo === '04' || tipo === 'RUC') { // RUC
            if (numero.length !== 13) return false;
            if (!numero.endsWith('001')) return false;
            return validarRucEcuatoriano(numero);
        }
        return true; // Pasaporte u otro
    }

    function validarCedulaEcuatoriana(cedula) {
        if (cedula.length !== 10) return false;
        let prov = parseInt(cedula.substring(0, 2), 10);
        if (prov < 1 || prov > 24) return false;
        
        let digitoVerificador = parseInt(cedula.substring(9, 10), 10);
        let suma = 0;
        for (let i = 0; i < 9; i++) {
            let valor = parseInt(cedula.charAt(i), 10);
            if (i % 2 === 0) {
                valor = valor * 2;
                if (valor > 9) valor = valor - 9;
            }
            suma += valor;
        }
        let decenaSuperior = Math.ceil(suma / 10) * 10;
        let calculado = decenaSuperior - suma;
        if (calculado === 10) calculado = 0;
        return calculado === digitoVerificador;
    }

    function validarRucEcuatoriano(ruc) {
        if (ruc.length !== 13) return false;
        let prov = parseInt(ruc.substring(0, 2), 10);
        if (prov < 1 || prov > 24) return false;
        let tercerDigito = parseInt(ruc.substring(2, 3), 10);
        
        if (tercerDigito < 6) { // Natural
            return validarCedulaEcuatoriana(ruc.substring(0, 10));
        } else if (tercerDigito === 6) { // Publica
            let coeficientes = [3, 2, 7, 6, 5, 4, 3, 2];
            let digitoVerificador = parseInt(ruc.substring(8, 9), 10);
            let suma = 0;
            for (let i = 0; i < 8; i++) {
                suma += parseInt(ruc.charAt(i), 10) * coeficientes[i];
            }
            let residuo = suma % 11;
            let calculado = residuo === 0 ? 0 : 11 - residuo;
            return calculado === digitoVerificador;
        } else if (tercerDigito === 9) { // Privada
            let coeficientes = [4, 3, 2, 7, 6, 5, 4, 3, 2];
            let digitoVerificador = parseInt(ruc.substring(9, 10), 10);
            let suma = 0;
            for (let i = 0; i < 9; i++) {
                suma += parseInt(ruc.charAt(i), 10) * coeficientes[i];
            }
            let residuo = suma % 11;
            let calculado = residuo === 0 ? 0 : 11 - residuo;
            return calculado === digitoVerificador;
        }
        return false;
    }

    let cart = [];
    let selectedPaymentId = 1;
    let currentCategory = 'TODOS';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function switchPosView(viewType) {
        const tableView = document.getElementById('posTableView');
        const gridView = document.getElementById('posGridView');
        const btnTable = document.getElementById('btnViewTable');
        const btnGrid = document.getElementById('btnViewGrid');

        if (viewType === 'table') {
            tableView.style.display = 'block';
            gridView.style.display = 'none';
            btnTable.className = 'btn-card-action btn-primary btn-sm';
            btnGrid.className = 'btn-card-action btn-secondary btn-sm';
        } else {
            tableView.style.display = 'none';
            gridView.style.display = 'grid';
            btnTable.className = 'btn-card-action btn-secondary btn-sm';
            btnGrid.className = 'btn-card-action btn-primary btn-sm';
        }
    }

    function addToCart(product) {
        const existing = cart.find(item => item.id === product.id);
        if (existing) {
            existing.qty++;
        } else {
            cart.push({ ...product, qty: 1 });
        }
        renderCart();
    }

    function changeQty(index, delta) {
        cart[index].qty += delta;
        if (cart[index].qty <= 0) {
            cart.splice(index, 1);
        }
        renderCart();
    }

    function removeItem(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function renderCart() {
        const tbody = document.getElementById('cartTableBody');
        if (cart.length === 0) {
            tbody.innerHTML = `
                <tr id="emptyCartRow">
                    <td colspan="4" style="text-align: center; color: var(--text-light); padding: 1.75rem 0.5rem;">
                        <i class="fa-solid fa-basket-shopping" style="font-size: 1.8rem; margin-bottom: 0.4rem; color: #cbd5e1; display: block;"></i>
                        <p style="margin: 0; font-size: 0.82rem; font-weight: 600;">Haga clic en un producto para agregarlo</p>
                    </td>
                </tr>
            `;
            updateTotals(0, 0);
            return;
        }

        let html = '';
        let subtotal = 0;
        let totalIva = 0;

        cart.forEach((item, idx) => {
            const itemTotal = item.precio * item.qty;
            subtotal += itemTotal;
            const tarifaIva = item.tarifa_iva || (item.codigo_iva === '2' ? 15 : 0);
            if (tarifaIva > 0) {
                totalIva += itemTotal * (tarifaIva / 100);
            }

            html += `
                <tr>
                    <td>
                        <div style="font-weight: 700; line-height: 1.2;">${item.nombre}</div>
                        <small style="color: #64748b; font-size: 0.75rem;">$${item.precio.toFixed(2)} c/u</small>
                    </td>
                    <td style="text-align: center;">
                        <div class="cart-qty-ctrl">
                            <button type="button" class="cart-btn-qty" onclick="changeQty(${idx}, -1)">-</button>
                            <span style="font-weight: 700; min-width: 14px; text-align: center; font-size: 0.825rem;">${item.qty}</span>
                            <button type="button" class="cart-btn-qty" onclick="changeQty(${idx}, 1)">+</button>
                        </div>
                    </td>
                    <td style="text-align: right; font-weight: 700; color: var(--success); font-size: 0.85rem;">$${itemTotal.toFixed(2)}</td>
                    <td style="text-align: center;">
                        <button type="button" class="btn-close" style="width: 22px; height: 22px; font-size: 0.8rem;" onclick="removeItem(${idx})">&times;</button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        updateTotals(subtotal, totalIva);
    }

    function updateTotals(subtotal, iva) {
        const total = subtotal + iva;

        document.getElementById('subtotalVal').innerText = `$${subtotal.toFixed(2)}`;
        document.getElementById('ivaVal').innerText = `$${iva.toFixed(2)}`;
        document.getElementById('totalVal').innerText = `$${total.toFixed(2)}`;
    }

    function selectPaymentMethod(btn, methodId) {
        document.querySelectorAll('[onclick^="selectPaymentMethod"]').forEach(b => {
            b.className = 'btn-card-action btn-secondary btn-sm';
        });
        btn.className = 'btn-card-action btn-primary btn-sm';
        selectedPaymentId = methodId;
    }

    function showConfirmModal() {
        if (cart.length === 0) {
            alert('¡Atención! Debe agregar al menos un producto a la factura.');
            return;
        }
        
        // Populate modal data
        const clienteSelect = document.getElementById('selectCliente');
        const clienteText = clienteSelect.options[clienteSelect.selectedIndex].text;
        document.getElementById('confirmCliente').innerText = clienteText;
        document.getElementById('confirmTotal').innerText = document.getElementById('totalVal').innerText;
        
        const btnConfirm = document.getElementById('btnConfirmEmitir');
        btnConfirm.disabled = true;
        
        let secondsLeft = 2;
        btnConfirm.innerText = `Esperar ${secondsLeft}s...`;
        
        openModal('modalConfirmacion');
        
        const timer = setInterval(() => {
            secondsLeft--;
            if (secondsLeft <= 0) {
                clearInterval(timer);
                btnConfirm.disabled = false;
                btnConfirm.innerText = 'Sí, enviar al SRI';
            } else {
                btnConfirm.innerText = `Esperar ${secondsLeft}s...`;
            }
        }, 1000);
    }

    async function processInvoice() {
        closeModal('modalConfirmacion');
        const clienteId = document.getElementById('selectCliente').value;
        const btn = document.getElementById('btnEmitirFactura');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Emitiendo SRI...';

        try {
            const response = await fetch('/facturacion/emitir', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    cliente_id: clienteId,
                    metodo_pago_id: selectedPaymentId,
                    items: cart.map(i => ({ id: i.id, qty: i.qty }))
                })
            });

            const data = await response.json();

            if (data.success) {
                document.getElementById('modalComprobanteNum').innerText = data.comprobante || 'Factura Autorizada';
                document.getElementById('modalClaveAcceso').innerText = data.clave_acceso || 'N/A';
                if (data.siguiente_secuencial) {
                    const topSec = document.getElementById('topSecuencial');
                    if (topSec) topSec.innerText = data.siguiente_secuencial;
                }
                openModal('modalFacturaExitosa');
            } else {
                // Modificar alert normal por un modal o un mensaje grande
                alert('RECHAZADO SRI: ' + (data.message || 'Error desconocido') + '\\n\\nDetalles: ' + (data.error || 'Ninguno'));
            }
        } catch (err) {
            console.error(err);
            alert('Error al comunicarse con el servidor.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> EMITIR FACTURA (SRI)';
        }
    }

    function clearCart() {
        cart = [];
        renderCart();
    }

    async function saveCustomer(e) {
        e.preventDefault();
        const form = document.getElementById('formNuevoCliente');
        const formData = new FormData(form);
        const jsonData = Object.fromEntries(formData.entries());

        if (!validarIdentificacion(jsonData.identificacion, jsonData.tipo_identificacion)) {
            alert('El número de identificación no es válido para el tipo seleccionado según validación oficial del SRI.');
            return;
        }

        try {
            const response = await fetch('/clientes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(jsonData)
            });

            const data = await response.json();
            if (data.success) {
                const select = document.getElementById('selectCliente');
                const opt = document.createElement('option');
                opt.value = data.cliente.id;
                opt.text = `${data.cliente.razon_social} (${data.cliente.identificacion})`;
                opt.selected = true;
                select.appendChild(opt);

                closeModal('modalCliente');
                form.reset();
                alert('¡Cliente registrado correctamente!');
            } else {
                alert('Error al guardar cliente.');
            }
        } catch (err) {
            console.error(err);
            alert('Error al registrar cliente.');
        }
    }

    function filterProducts() {
        const query = document.getElementById('inputSearch').value.toLowerCase();
        
        // Filter table rows
        document.querySelectorAll('.pos-table-row').forEach(row => {
            const name = row.getAttribute('data-name');
            const cat = row.getAttribute('data-category');
            const matchName = name.includes(query);
            const matchCat = (currentCategory === 'TODOS' || cat === currentCategory);
            row.style.display = (matchName && matchCat) ? '' : 'none';
        });

        // Filter cards
        document.querySelectorAll('.product-item-card').forEach(card => {
            const name = card.getAttribute('data-name');
            const cat = card.getAttribute('data-category');
            const matchName = name.includes(query);
            const matchCat = (currentCategory === 'TODOS' || cat === currentCategory);
            card.style.display = (matchName && matchCat) ? 'flex' : 'none';
        });
    }

    function filterCategory(btn, cat) {
        document.querySelectorAll('#categoryChips .chip-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentCategory = cat;
        filterProducts();
    }
</script>
@endpush
