

{capture name=path}{l s='Pago a través de WebPay' mod='webpay'}{/capture}
<h1 class="page-heading"><span>{l s='Order summary' mod='webpay'}</span></h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}
{include file="$tpl_dir./errors.tpl"}



<form method="post" action="{$url_token}" id="formWpEnel">
<input type="hidden" name="token_ws" value="{$token_webpay}" />

{if ({$token_webpay} == '0')}
    
    <p class="alert alert-danger">Ocurrio un error al intentar conectar con WebPay o los datos de conexion son incorrectos.</p>   
          
    <p class="cart_navigation clearfix" id="cart_navigation">
			<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button-exclusive btn btn-default"><i class="icon-chevron-left"></i>{l s='Other payment methods' mod='webpay'}</a>

    </p>
{else}
<h3 class="page-subheading">Pago por WebPay</h3>
<div class="box cheque-box clearfix content-wp-enel">
		<div class="col-sm-8 col-xs-12">
			<p class="infowpenel">
	        Se realizará la compra a través de WebPay por un total de {*$total*} 
	        <br><br>
	        <span>{displayPrice price=$total}</span>
			</p>
		</div>
		<div class="custom-webpay-logo col-sm-4 col-xs-12">
			<img src="https://www.transbank.cl/public/img/LogoWebpay.png" title="Webpay" alt="Webpay">
		</div>
</div>

	<p class="cart_navigation clearfix" id="cart_navigation">
			
			{if false}
			<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button-exclusive btn btn-default"><i class="icon-chevron-left"></i>{l s='Other payment methods' mod='webpay'}</a>
			{/if}

			<button type="submit" class="button btn btn-default button-medium">
				<span>Pagar<i class="icon-chevron-right right"></i></span>
			</button>
	</p>
{/if}
</form>
      