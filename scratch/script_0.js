
    
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
