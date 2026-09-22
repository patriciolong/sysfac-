<div class="panel">
    <div class="panel-heading">
        <i class="icon-bar-chart"></i> Reporte de Impresiones - Promoción: {$promo->title|escape:'html':'UTF-8'}
    </div>
    
    <div class="row">
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-heading">Resumen</div>
                <ul class="list-group">
                    <li class="list-group-item"><strong>Logo/Marca:</strong> {$promo->logo|escape:'html':'UTF-8'}</li>
                    <li class="list-group-item"><strong>Descuento:</strong> {$promo->title|escape:'html':'UTF-8'}</li>
                    <li class="list-group-item"><strong>Creador de Promo:</strong> {$promo->creator_name|escape:'html':'UTF-8'}</li>
                    <li class="list-group-item"><strong>Total Boletos Impresos Históricos:</strong> <span class="badge badge-success" style="font-size:14px;">{$total_printed}</span></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="panel mt-2">
        <div class="panel-heading">
            <i class="icon-list"></i> Historial de Impresiones por Vendedor
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th><span class="title_box">ID Registro</span></th>
                    <th><span class="title_box">Vendedor / Impresor</span></th>
                    <th><span class="title_box">Cantidad Impresa</span></th>
                    <th><span class="title_box">Fecha y Hora</span></th>
                </tr>
            </thead>
            <tbody>
                {if $prints}
                    {foreach from=$prints item=print}
                        <tr>
                            <td>{$print.id_print}</td>
                            <td><strong>{$print.seller_name|escape:'html':'UTF-8'}</strong></td>
                            <td><span class="badge badge-info">{$print.quantity} boletos</span></td>
                            <td>{$print.date_add}</td>
                        </tr>
                    {/foreach}
                {else}
                    <tr>
                        <td colspan="4" class="text-center">No hay registros de impresión para esta promoción.</td>
                    </tr>
                {/if}
            </tbody>
        </table>
    </div>
    
    <div class="panel-footer">
        <a href="{$link->getAdminLink('AdminGiftCardPromo')|escape:'html':'UTF-8'}" class="btn btn-default">
            <i class="process-icon-back"></i> Volver al listado
        </a>
    </div>
</div>
