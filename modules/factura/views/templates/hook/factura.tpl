<fieldset>
{if isset($confirmation)}
    <div class="alert alert-success">Settings updated</div>
{/if}

    <form id="configuration_form" class="defaultForm form-horizontal factura" action="" method="post" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="submitfactura" value="1" />
        <div class="panel" id="fieldset_0">
            <div class="panel-heading">Configuración Rest Api</div>
            <div class="form-wrapper">
                <div class="form-group">
                    <label class="control-label col-lg-3 required">URL API REST</label>
                    <div class="col-lg-9"> 
                        <input type="text" name="FACTURA_REST_URL" id="FACTURA_REST_URL" value="{$factura_rest_url}" class="" size="10" required="required" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 required">ID Empresa</label>
                    <div class="col-lg-9"> 
                        <input type="text" name="FACTURA_EMPRESA_ID" id="FACTURA_EMPRESA_ID" value="{$factura_empresa_id}" class="" size="10" required="required" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 required">ID Usuario</label>
                    <div class="col-lg-9"> 
                        <input type="text" name="FACTURA_USUARIO_ID" id="FACTURA_USUARIO_ID" value="{$factura_usuario_id}" class="" size="10" required="required" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3 required">Clave Usuario</label>
                    <div class="col-lg-9"> 
                        <input type="text" name="FACTURA_CLAVE" id="FACTURA_CLAVE" value="{$factura_clave}" class="" size="10" required="required" />
                    </div>
                </div>
            </div><!-- /.form-wrapper -->
            <div class="panel-footer">
                <button type="submit" value="1"	id="configuration_form_submit_btn" name="submitfactura" class="btn btn-default pull-right"><i class="process-icon-save"></i> Guardar</button>
            </div>
        </div>
    </form>

</fieldset>