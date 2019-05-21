
{if ($WEBPAY_TX_ANULADA == "SI")}
    
    <p class="alert alert-danger">La Transaccion fue Anulada por el Cliente.</p>   
          
{else}
{if ($WEBPAY_RESULT_CODE == 0)}
<p class="alert alert-success">{l s='Your order on %s is complete.' sprintf=$shop_name mod='webpay'}</p>
        
<div class="box order-confirmation">
    <h3 class="page-subheading">Detalles del pago :</h3>
<p>     
                Respuesta de la Transaccion : {$WEBPAY_RESULT_DESC}
                <br />Tarjeta de credito: **********{$WEBPAY_VOUCHER_NROTARJETA}
                <br />Fecha de Transaccion :  {$WEBPAY_VOUCHER_TXDATE_FECHA}
                <br />Hora de Transaccion :  {$WEBPAY_VOUCHER_TXDATE_HORA}
                <br />Monto Compra :  {displayPrice price=$WEBPAY_VOUCHER_TOTALPAGO}                
                <br />Orden de Compra :  {$WEBPAY_VOUCHER_ORDENCOMPRA}
                <br />Codigo de Autorizacion :  {$WEBPAY_VOUCHER_AUTCODE}
                <br />Tipo de Pago :  {$WEBPAY_VOUCHER_TIPOPAGO}
                <br />Tipo de Cuotas :  {$WEBPAY_VOUCHER_TIPOCUOTAS}
                <br />Numero de cuotas :  {$WEBPAY_VOUCHER_NROCUOTAS}
                
  </p>
</div>
{else}
    <p class="alert alert-danger">Ha ocurrido un error con su pago. </p>  
   <div class="box order-confirmation">
      <h3 class="page-subheading">Detalles del pago :</h3>
      <p>  
                Respuesta de la Transaccion : {$WEBPAY_RESULT_DESC} 
                <br />Orden de Compra :  {$WEBPAY_VOUCHER_ORDENCOMPRA}
                <br />Fecha de Transaccion :  {$WEBPAY_VOUCHER_TXDATE_FECHA}
                <br />Hora de Transaccion :  {$WEBPAY_VOUCHER_TXDATE_HORA}
      </p>
   </div>
{/if}
{/if}


<p class="cart_navigation" id="cart_navigation2">
     <a class="button btn btn-default button-medium" href="{if isset($force_ssl) && $force_ssl}{$base_dir_ssl}{else}{$base_dir}{/if}">
        <span>
          <i class="icon-chevron-left"></i>{l s='Home'}
        </span>
      </a>
</p>

