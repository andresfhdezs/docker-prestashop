
<body onload="">
<img src="{$img_icono|escape:'htmlall':'UTF-8'}" width="200" height="183"/>


<h2>{l s='Pago electronico con Tarjetas de Credito o Redcompra a traves de Webpay Plus' mod='webpay'}</h2>


<form action="{$post_url|escape:'htmlall':'UTF-8'}" method="post" style="clear: both; margin-top: 10px;">
    <fieldset>
        <legend><img src="../img/admin/contact.gif"/>{l s='Configuracion' mod='webpay'}</legend>
        {if isset($errors.merchantERR)}
            <div class="error">
                <p>{$errors.merchantERR|escape:'htmlall':'UTF-8'}</p>
            </div>
        {/if}
        

        <label for="storeID">{l s='Codigo de Comercio' mod='webpay'}</label>

        <div class="margin-form"><input type="text" size="90" id="storeID" name="storeID" value="{$data_storeid|escape:'htmlall':'UTF-8'}"/></div>
        <label for="secretCode">{l s='Llave privada' mod='webpay'}</label>
        <div class="margin-form"><textarea cols="90" rows="6" wrap="soft" placeholder="" name="secretCode" id="secretCode" value="{$data_secretcode|escape:'htmlall':'UTF-8'}">{$data_secretcode|escape:'htmlall':'UTF-8'}</textarea></div>
        <label for="certificate">{l s='Certificado' mod='webpay'}</label>
        <div class="margin-form"><textarea cols="90" rows="6" wrap="soft" id="certificate" name="certificate" value="{$data_certificate|escape:'htmlall':'UTF-8'}"/>{$data_certificate|escape:'htmlall':'UTF-8'}</textarea></div>
                                        
        <label for="certificateTransbank">{l s='Certificado Transbank' mod='webpay'}</label>
        <div class="margin-form"><textarea cols="90" rows="6" wrap="soft" id="certificateTransbank" name="certificateTransbank" value="{$data_certificatetransbank|escape:'htmlall':'UTF-8'}"/>{$data_certificatetransbank|escape:'htmlall':'UTF-8'}</textarea></div>                                        
                                        

        <label for="ambient">{l s='Ambiente' mod='webpay'}</label>
   
        <div class="margin-form">
            <select name="ambient" onChange="
                if(this.options[0].selected){ 
                    carga_datos_integracion();                                                                         
                }else if(this.options[1].selected){
                    carga_datos_certificacion();
                }else if(this.options[2].selected){ 
                    carga_datos_produccion();
                }" default="INTEGRACION">
                <option value="INTEGRACION" {if $data_ambient eq "INTEGRACION"}selected{/if}>Integracion</option>
                <option value="CERTIFICACION" {if $data_ambient eq "CERTIFICACION"}selected{/if}>Certificacion</option>
                <option value="PRODUCCION" {if $data_ambient eq "PRODUCCION"}selected{/if}>Produccion</option>
            </select>
        </div>

<div class="panel-footer">
    <div align="right"><button type="submit" value="1" id="webpay_updateSettings" name="webpay_updateSettings" class="btn btn-default pull-right">
            <i class="process-icon-save" value="{l s='Save Settings' mod='webpay'}"></i> Guardar
    </button>
    </div>
</div>
    </fieldset>
    <input id="_link" type="hidden" value="{Context::getContext()->link->getAdminLink('AdminWPLog')}"/>
    <fieldset>
        <legend><img src="../img/admin/contact.gif"/>{l s='Log' mod='webpay'}</legend>        
        <div id="logTable"></div>

        <div class="panel-footer">
        <div align="right">
            <button type="button" class="btn btn-default pull-right" onclick="loadLog()">
                <i class="process-icon-refresh" value="{l s='Load More' mod='webpay'}"></i> Cargar M&aacute;s
            </button>
        </div>
        </div>
    </fieldset>
</form>
    
<script type="text/javascript">
   {literal}
   var link = $("#_link").val();
   var from = 0;
   var to = 10;
   var tableContent = "";
   var loadLog = function(){
     $.ajax({
            type: 'POST',
            url: link,
            dataType: 'json',
            data: {
                from: from,
                to: to,
                controller : 'AdminWPLog',
                action : 'getLog',
                ajax : true,
                id_tab : current_id_tab
            },
            success: function(jsonData)
            {
                from = jsonData.from;
                to = jsonData.to;
                if( jsonData.logs ){
                    jsonData.logs.forEach(function(log){
                        tableContent += "<tr><td>"+log.id_order+"</td><td>"+log.id_cart+"</td><td>"+log.auth_code+
                        "</td><td>"+log.result_code+"</td><td>"+log.result_desc+"</td><td>"+parseInt(log.total)+
                        "</td><td>"+log.tx_datetime+"</td><td>"+log.card_fee+"</td><td>"+log.fee_type+
                        "</td><td>"+log.payment_type+"</td><td>"+log.card_number+"</td></tr>";
                    });
                    $("#logTable").html(
                        "<table width='100%'><tr><td><strong>Orden</strong></td><td><strong>Carro</strong></td><td><strong>Cod. Autorizaci&oacute;n</strong></td>"+
                        "<td><strong>Webpay Code</strong></td><td><strong>Webpay Desc.</strong></td>"+
                        "<td><strong>Total</strong></td><td><strong>Fecha</strong></td><td><strong>Coutas</strong></td><td><strong>Tipo Coutas</strong></td><td><strong>Tipo Pago</strong></td>"+
                        "<td><strong>Nro. Tarjeta</strong></td></tr>"+tableContent+"</table>"
                    );
                }            
            }
    });    
   }   
   loadLog();
   {/literal}
   function carga_datos_integracion(){
       
        var private_key_js = "{$data_secretcode_init}".replace(/<br\s*\/?>/mg,"\n");
        var public_cert_js = "{$data_certificate_init}".replace(/<br\s*\/?>/mg,"\n");
        var webpay_cert_js = "{$data_certificatetransbank_init}".replace(/<br\s*\/?>/mg,"\n");
       
        document.getElementById('storeID').value = "{$data_storeid_init|escape:'htmlall':'UTF-8'}";  
        document.getElementById('secretCode').value = private_key_js;
        document.getElementById('certificate').value = public_cert_js;    
        document.getElementById('certificateTransbank').value = webpay_cert_js;      
   }

   function carga_datos_certificacion(){        
        document.getElementById('secretCode').value = ''; 
        document.getElementById('certificate').value = ''; 
        document.getElementById('certificateTransbank').value = '';
        document.getElementById('storeID').value = '';  
   }

    function carga_datos_produccion(){        
        document.getElementById('secretCode').value = ''; 
        document.getElementById('certificate').value = ''; 
        document.getElementById('certificateTransbank').value = '';
        document.getElementById('storeID').value = '';  
   }   
   
</script>

</body>