import re

file_path = "resources/views/facturacion/_creacion.blade.php"
with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

# 1. Add validation functions
validation_js = """
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
"""

if "validarIdentificacion" not in content:
    content = content.replace("let cart = [];", validation_js + "\n    let cart = [];")

# 2. Modify saveCustomer
save_customer_new = """
    async function saveCustomer(e) {
        e.preventDefault();
        const form = document.getElementById('formNuevoCliente');
        const formData = new FormData(form);
        const jsonData = Object.fromEntries(formData.entries());

        if (!validarIdentificacion(jsonData.identificacion, jsonData.tipo_identificacion)) {
            alert('El número de identificación no es válido para el tipo seleccionado según validación oficial del SRI.');
            return;
        }

"""
content = re.sub(r'async function saveCustomer\(e\) \{\s*e\.preventDefault\(\);\s*const form = document\.getElementById\(\'formNuevoCliente\'\);\s*const formData = new FormData\(form\);\s*const jsonData = Object\.fromEntries\(formData\.entries\(\)\);', save_customer_new.strip(), content)

# 3. Modify processInvoice to use Modal Confirmacion Timer
process_invoice_new = """
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
"""

content = re.sub(r'async function processInvoice\(\) \{.*?(?=function clearCart\(\))', process_invoice_new.strip() + '\n\n    ', content, flags=re.DOTALL)

content = content.replace("onclick=\"processInvoice()\"", "onclick=\"showConfirmModal()\"")

# 4. Add Confirm Modal HTML
modal_confirm = """
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
"""

if "MODAL CONFIRMACION EMISION" not in content:
    content = content.replace("<!-- MODAL FACTURA AUTORIZADA SRI EXITO -->", modal_confirm + "\n<!-- MODAL FACTURA AUTORIZADA SRI EXITO -->")


with open(file_path, "w", encoding="utf-8") as f:
    f.write(content)

print("Modificaciones completadas!")
