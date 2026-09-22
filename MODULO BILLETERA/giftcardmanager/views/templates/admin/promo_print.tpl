<div class="panel">
    <div class="panel-heading">
        <i class="icon-print"></i> Impresión de Promociones
    </div>
    
    {if isset($confirmations) && $confirmations}
        <div class="alert alert-success">
            {foreach from=$confirmations item=conf}
                {$conf}<br/>
            {/foreach}
        </div>
    {/if}

    {if isset($errors) && $errors}
        <div class="alert alert-danger">
            {foreach from=$errors item=error}
                {$error}<br/>
            {/foreach}
        </div>
    {/if}

    <form action="{$submit_url}" method="post" class="form-horizontal">
        <div class="form-group">
            <label class="control-label col-lg-3">Promoción a Imprimir</label>
            <div class="col-lg-6">
                <select name="id_promo" class="form-control" required="required">
                    <option value="">-- Seleccione una promoción --</option>
                    {foreach from=$promos item=promo}
                        <option value="{$promo.id_promo}">{$promo.title} (Creado por: {$promo.creator_name})</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">Nombre del Vendedor (Impresor)</label>
            <div class="col-lg-6">
                <input type="text" name="seller_name" value="" required="required" class="form-control" placeholder="Ej. Juan Pérez" />
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">Cantidad de Boletos</label>
            <div class="col-lg-3">
                <input type="number" name="quantity" value="1" min="1" max="100" required="required" class="form-control" />
            </div>
        </div>
        <div class="panel-footer">
            <button type="submit" name="submitPrintPromos" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> Enviar a Imprimir
            </button>
        </div>
    </form>
</div>

{if isset($print_data)}
<script type="text/javascript">
{literal}
$(document).ready(function() {
    var printData = {/literal}{$print_data|@json_encode}{literal};
    var quantity = printData.quantity;
    var base_code = printData.base_code;
    
    var printsDone = 0;
    
    function sendNextPrint() {
        if (printsDone >= quantity) {
            alert('Se han enviado ' + quantity + ' boletos a la impresora local.');
            return;
        }
        
        // Asignamos un número único de boleto
        var currentData = JSON.parse(JSON.stringify(printData));
        currentData.coupon_no = base_code + '-' + (printsDone + 1);
        
        $.ajax({
            url: 'http://127.0.0.1:8080/print_receipt',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(currentData),
            success: function(response) {
                printsDone++;
                // Pequeña pausa para no saturar el spooler de windows
                setTimeout(sendNextPrint, 500);
            },
            error: function(xhr) {
                var errorMsg = 'Error al imprimir boleto ' + (printsDone + 1) + '. Asegúrese de que el servidor esté abierto.';
                alert(errorMsg);
            }
        });
    }
    
    // Iniciar el loop de impresión
    sendNextPrint();
});
{/literal}
</script>
{/if}
