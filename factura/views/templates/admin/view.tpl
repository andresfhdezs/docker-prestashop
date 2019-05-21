<fieldset>
    <div class="panel">
        <div class="panel-heading">
            <legend><i class="icon-info"></i>{l s=' Detalle de la respuesta' mod='factura'}</legend>
        </div>
        <div class="form-group clearfix">
            <label class="col-lg-3">{l s='ID:' mod='factura'}</label>
            <div class="col-lg-9">{$serviciofactura->id_servicio_factura}</div>
        </div>
        <div class="form-group clearfix">
            <label class="col-lg-3">{l s='Id factura:' mod='factura'}</label>
            <div class="col-lg-9">{$serviciofactura->id_order}</div>
        </div>
        <div class="form-group clearfix">
            <label class="col-lg-3">{l s='Respuesta del Servicio:' mod='factura'}</label>
            <div class="col-lg-9">{$serviciofactura->resp}</div>
        </div>
        <div class="form-group clearfix">
            <label class="col-lg-3">{l s='Estado de envió:' mod='factura'}</label>
            {if $serviciofactura->send }
                Enviado
            {else}
                No enviado
            {/if}
            <div class="col-lg-9>"></div>
        </div>
        <a class="btn btn-default _blank" href="{$link->getModuleLink('factura', 'facturero')|escape:'html':'UTF-8'}&amp;id_order={$serviciofactura->id_order}" onclick="return confirm('¿Esta seguro de enviar el servicio?')">
			<i class="icon-send"></i>
		</a>
    </div>
</fieldset>