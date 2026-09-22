<div class="panel">
    <div class="panel-heading">
        <i class="icon-shopping-cart"></i> Consumo de Gift Card
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
            <label class="control-label col-lg-3">Código de la Gift Card</label>
            <div class="col-lg-6">
                <input type="text" name="code" id="gc_code" value="" required="required" class="form-control" />
            </div>
            <div class="col-lg-3">
                <button type="button" id="btn_check_balance" class="btn btn-primary">Consultar Saldo</button>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3"></label>
            <div class="col-lg-9">
                <div id="balance_result" style="font-weight: bold; font-size: 1.2em; color: #2e6b30;"></div>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">Nombre del Vendedor</label>
            <div class="col-lg-9">
                <input type="text" name="seller_name" value="" required="required" class="form-control" placeholder="Ej. Juan Pérez" />
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">Número de Factura</label>
            <div class="col-lg-9">
                <input type="text" name="invoice_number" value="" required="required" class="form-control" />
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">Monto a Consumir</label>
            <div class="col-lg-9">
                <div class="input-group">
                    <span class="input-group-addon">$</span>
                    <input type="number" name="amount" id="gc_amount" value="" step="0.01" min="0.01" required="required" class="form-control" />
                </div>
            </div>
        </div>
        <div class="panel-footer">
            <button type="submit" name="submitConsumeGiftCard" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> Registrar Consumo
            </button>
        </div>
    </form>
</div>

{if isset($print_data)}
<div class="modal fade" id="printReceiptModal" tabindex="-1" role="dialog" aria-labelledby="printReceiptModalLabel" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="printReceiptModalLabel"><i class="icon-check"></i> Consumo Registrado</h4>
            </div>
            <div class="modal-body text-center" style="padding: 30px;">
                <h3 class="text-success" style="margin-top: 0;">¡Transacción Exitosa!</h3>
                <p>El saldo de la Gift Card ha sido actualizado correctamente.</p>
                <br/>
                <button type="button" class="btn btn-success btn-lg" onclick='printReceiptAndClose({$print_data|@json_encode})'>
                    <i class="icon-print icon-2x"></i> IMPRIMIR RECIBO
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
{/if}

<script type="text/javascript">
{if isset($print_data)}
    $(document).ready(function() {
        $('#printReceiptModal').modal('show');
    });
{/if}
{literal}
$(document).ready(function() {
    $('#btn_check_balance').on('click', function(e) {
        e.preventDefault();
        var code = $('#gc_code').val();
        var resultDiv = $('#balance_result');
        
        if (code.trim() === '') {
            resultDiv.html('<span style="color:red;">Por favor, ingrese un código.</span>');
            return;
        }

        resultDiv.html('Consultando...');

        $.ajax({
            type: 'POST',
            url: '{/literal}{$submit_url}{literal}',
            data: {
                ajax: 1,
                action: 'checkBalance',
                code: code
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    resultDiv.html('<span style="color:green;">' + response.message + '</span>');
                    // Opcionalmente auto-completar el maximo del monto a consumir
                    // $('#gc_amount').attr('max', response.balance);
                } else {
                    resultDiv.html('<span style="color:red;">' + response.message + '</span>');
                }
            },
            error: function() {
                resultDiv.html('<span style="color:red;">Error al consultar el saldo.</span>');
            }
        });
    });
});

function printReceiptAndClose(data) {
    $.ajax({
        url: 'http://127.0.0.1:8080/print_receipt',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            $('#printReceiptModal').modal('hide');
        },
        error: function(xhr) {
            var errorMsg = 'Error al imprimir. Asegúrese de que el servidor de impresión esté en ejecución.';
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
