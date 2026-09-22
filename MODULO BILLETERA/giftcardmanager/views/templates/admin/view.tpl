<div class="panel">
    <div class="panel-heading">
        <i class="icon-file-text"></i> Movimientos de la Gift Card: {$giftcard->code}
    </div>
    
    <div class="row">
        <div class="col-lg-12">
            <p><strong>Saldo Actual:</strong> ${$giftcard->balance|number_format:2}</p>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Vendedor</th>
                <th>Monto Consumido</th>
                <th>Nro Factura</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$movements item=movement}
                <tr>
                    <td>{$movement.id_giftcard_movement}</td>
                    <td>{$movement.date_add}</td>
                    <td>
                        {$movement.seller_name}
                        {if isset($movement.firstname) && $movement.firstname}
                            <br><small class="text-muted">({$movement.firstname} {$movement.lastname})</small>
                        {/if}
                    </td>
                    <td>${$movement.amount|number_format:2}</td>
                    <td>{$movement.invoice_number}</td>
                    <td>
                        <button type="button" class="btn btn-default" onclick='printAdminReceipt({ldelim}
                            "title": "VERSSATO",
                            "subtitle": "COPIA DE RECIBO",
                            "content": "================================\nFecha: {$movement.date_add}\nFactura: {$movement.invoice_number|escape:'javascript'}\nVendedor: {$movement.seller_name|escape:'javascript'}\n================================\nGIFT CARD: {$giftcard->code}\n--------------------------------\nCONSUMO:      ${$movement.amount|number_format:2}\nSALDO ACTUAL: ${$giftcard->balance|number_format:2}\n================================\n    ¡Gracias por su visita!     \n"
                        {rdelim})'>
                            <i class="icon-print"></i> Imprimir
                        </button>
                    </td>
                </tr>
            {foreachelse}
                <tr>
                    <td colspan="6" class="text-center">No hay movimientos registrados.</td>
                </tr>
            {/foreach}
        </tbody>
    </table>
</div>

<script type="text/javascript">
{literal}
function printAdminReceipt(data) {
    $.ajax({
        url: 'http://127.0.0.1:8080/print_receipt',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            alert('Copia de recibo enviada a la impresora.');
        },
        error: function(xhr) {
            var errorMsg = 'Error al imprimir. Asegúrese de que el servidor de impresión esté ejecutándose.';
            if (xhr.status === 0) {
                errorMsg = 'Error de red: No se pudo conectar a la aplicación de impresión local. Asegúrate de que esté abierta.';
            } else if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = 'Error del servidor: ' + xhr.responseJSON.message;
            } else if (xhr && xhr.responseText) {
                errorMsg = 'Error del servidor: ' + xhr.responseText;
            }
            alert(errorMsg);
        }
    });
}
{/literal}
</script>
