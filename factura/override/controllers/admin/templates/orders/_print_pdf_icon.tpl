<span class="btn-group-action">
	<span class="btn-group">
	{if Configuration::get('PS_INVOICE') && $order->invoice_number}
		<a class="btn btn-default _blank" href="{$link->getAdminLink('AdminPdf')|escape:'html':'UTF-8'}&amp;submitAction=generateInvoicePDF&amp;id_order={$order->id}">
			<i class="icon-file-text"></i>
		</a>
	{/if}
	{* Generate HTML code for printing Delivery Icon with link *}
	{if $order->delivery_number}
		<a class="btn btn-default _blank" href="{$link->getAdminLink('AdminPdf')|escape:'html':'UTF-8'}&amp;submitAction=generateDeliverySlipPDF&amp;id_order={$order->id}">
			<i class="icon-truck"></i>
		</a>
	{/if}
	{if Configuration::get('PS_INVOICE') && $order->invoice_number}
        <a class="btn btn-default _blank" href="{$link->getModuleLink('factura', 'facturero')|escape:'html':'UTF-8'}&amp;id_order={$order->id}" onclick="return confirm('¿Esta seguro de enviar el servicio?');">
			<i class="icon-send"></i>
		</a>
	{/if}
	</span>
</span>