<?php
if (!defined('_PS_VERSION_')) {
    exit;
}
    
class Factura extends Module
{
    public function __construct()
    {
        $this->name                     = 'factura';
        $this->tab                      = 'billing_invoicing';
        $this->version                  = '0.0.1';
        $this->author                   = 'Xpectrum Technology';
        $this->need_instance            = 0;
        $this->ps_versions_compliancy   = array('min' => '1.6', 'max' => _PS_VERSION_); 
        $this->bootstrap                = true;
        $this->controllers              = array('view');
        
        parent::__construct();
        
        $this->displayName      = $this->l('Generador Factura');
        $this->description      = $this->l('Genera una factura.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public $from    = _PS_MODULE_DIR_."factura/override/controllers/admin/templates/orders/";
    public $to      = _PS_ROOT_DIR_."/override/controllers/admin/templates/orders/";
    public $from_file    = _PS_MODULE_DIR_."factura/override/controllers/admin/AdminPdfController.php";
    public $to_file      = _PS_ROOT_DIR_."/override/controllers/admin/AdminPdfController.php";


    function copy_dir($from, $to)
    {
        
        if (is_dir($from)) 
        {
            mkdir($to);
            $files = scandir($from);
            foreach ($files as $file)
            if ($file != "." && $file != "..") self::copy_dir("$from/$file", "$to/$file"); 
        }
        else if (file_exists($from)) copy($from, $to);
           
    }

    function rrmdir($dir) 
    {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file)
            if ($file != "." && $file != "..") self::rrmdir("$dir/$file");
            rmdir($dir);
        }
        else if (file_exists($dir)) unlink($dir);
    }

    public function install()
    {
        if (!parent::install()
        || !Configuration::updateValue('FACTURA_REST_URL',      'http://190.196.5.70/api/BoletaElectronica/Save')
        || !Configuration::updateValue('FACTURA_EMPRESA_ID',    '1')
        || !Configuration::updateValue('FACTURA_USUARIO_ID',    'demo')
        || !Configuration::updateValue('FACTURA_CLAVE',         'demopass')
        || !Configuration::updateValue('FACTURA_RUT_COMPANIA',  '123456789')
        || !Configuration::updateValue('FACTURA_TIPO_DOCUMENTO','RC')
        || !Configuration::updateValue('FACTURA_INDICADOR_SERVICIO',    3)
        || !Configuration::updateValue('EMISOR_SUCURSAL', '')
        || !Configuration::updateValue('EMISOR_DIRECCION', '')
        || !Configuration::updateValue('EMISOR_COMUNA', '')
        || !Configuration::updateValue('EMISOR_CIUDAD', '')) {
            return false;
        }

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminFactura';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang)
            $tab->name[$lang['id_lang']] = 'Servicio de Factura';
        $tab->id_parent = $id_parent;//if exists
        $tab->position = 0;
        $tab->module = 'factura';
        $tab->save();

        if (!file_exists($this->to)) self::copy_dir($this->from, $this->to);
            return true;
        
        if (!file_exists($this->to_file)) self::copy_dir($this->from_file, $this->to_file);
            return true;

    }

    public function uninstall()
    {
        if (!parent::uninstall()
        || !Configuration::deleteByName('FACTURA_REST_URL')
        || !Configuration::deleteByName('FACTURA_EMPRESA_ID')
        || !Configuration::deleteByName('FACTURA_USUARIO_ID')
        || !Configuration::deleteByName('FACTURA_CLAVE')
        || !Configuration::deleteByName('FACTURA_RUT_COMPANIA')
        || !Configuration::deleteByName('FACTURA_TIPO_DOCUMENTO')
        || !Configuration::deleteByName('FACTURA_INDICADOR_SERVICIO')
        || !Configuration::deleteByName('EMISOR_SUCURSAL')
        || !Configuration::deleteByName('EMISOR_DIRECCION')
        || !Configuration::deleteByName('EMISOR_COMUNA')
        || !Configuration::deleteByName('EMISOR_CIUDAD')) {
            return false;
        }

        $id_tab = (int)Tab::getIdFromClassName('AdminFactura');
        // Load tab
        $tab = new Tab((int)$id_tab);
        // Delete it
        return $tab->delete();

        if (file_exists($this->to)) self::rrmdir($this->to);
            return true;
        
        if (file_exists($this->to_file)) self::rrmdir($this->to_file);
            return true;
    }

    public function processConfiguration()
    {
        if (Tools::isSubmit('submitFactura'))
        {
            Configuration::updateValue('FACTURA_REST_URL',          Tools::getValue('factura_rest_url'));
            Configuration::updateValue('FACTURA_EMPRESA_ID',        Tools::getValue('factura_empresa_id'));
            Configuration::updateValue('FACTURA_USUARIO_ID',        Tools::getValue('factura_usuario_id'));
            Configuration::updateValue('FACTURA_CLAVE',             Tools::getValue('factura_clave'));
            Configuration::updateValue('FACTURA_RUT_COMPANIA',      Tools::getValue('factura_rut_compania'));
            Configuration::updateValue('FACTURA_TIPO_DOCUMENTO',    Tools::getValue('factura_tipo_documento'));
            Configuration::updateValue('FACTURA_INDICADOR_SERVICIO',Tools::getValue('factura_indicador_servicio'));
            $this->context->smarty->assign('confirmation', 'ok');
        }
    }

    public function processEmisorConfiguration()
    {
        if (Tools::isSubmit('submitEmisor'))
        {
            Configuration::updateValue('EMISOR_SUCURSAL',   Tools::getValue('codigo_emisor'));
            Configuration::updateValue('EMISOR_DIRECCION',  Tools::getValue('direccion_emisor'));
            Configuration::updateValue('EMISOR_COMUNA',     Tools::getValue('comuna_emisor'));
            Configuration::updateValue('EMISOR_CIUDAD',     Tools::getValue('ciudad_emisor'));
            $this->context->smarty->assign('confirmation', 'ok');
        }
    }

    public function assignConfiguration()
    {
        return array(
			'factura_rest_url'          => Tools::getValue('factura_rest_url',          Configuration::get('FACTURA_REST_URL')),
            'factura_empresa_id'        => Tools::getValue('factura_empresa_id',        Configuration::get('FACTURA_EMPRESA_ID')),
            'factura_usuario_id'        => Tools::getValue('factura_usuario_id',        Configuration::get('FACTURA_USUARIO_ID')),
            'factura_clave'             => Tools::getValue('factura_clave',             Configuration::get('FACTURA_CLAVE')),
            'factura_rut_compania'      => Tools::getValue('factura_rut_compania',      Configuration::get('FACTURA_RUT_COMPANIA')),
            'factura_tipo_documento'    => Tools::getValue('factura_tipo_documento',    Configuration::get('FACTURA_TIPO_DOCUMENTO')),
            'factura_indicador_servicio'=> Tools::getValue('factura_indicador_servicio', Configuration::get('FACTURA_INDICADOR_SERVICIO')),
		);
    }

    public function assignEmisorConfiguration()
    {
        return array(
			'codigo_emisor'     => Tools::getValue('codigo_emisor',         Configuration::get('EMISOR_SUCURSAL')),
            'direccion_emisor'  => Tools::getValue('direccion_emisor',  Configuration::get('EMISOR_DIRECCION')),
            'comuna_emisor'     => Tools::getValue('comuna_emisor',         Configuration::get('EMISOR_COMUNA')),
            'ciudad_emisor'     => Tools::getValue('ciudad_emisor',         Configuration::get('EMISOR_CIUDAD')),
		);
    }

    public function getContent()
    {   
        $this->json_factura();
        $this->processConfiguration();
        $this->processEmisorConfiguration();

        $html .= $this->display(__FILE__, 'factura.tpl');
        $html .= $this->renderForm();
        $html .= $this->emisorForm();

		return $html;
    }

    public function renderForm()
	{
        # Fecha: date(DATE_ATOM, mktime(0, 0, 0, 7, 1, 2000))
        $options = array(
            array(
                'id_option' => 1,       // The value of the 'value' attribute of the <option> tag.
                'name'      => 'Boletas de servicios periódicos'    // The value of the text content of the  <option> tag.
            ),
            array(
                'id_option' => 2,
                'name'      => 'Boletas de servicios periódicos domiciliarios'
            ),
            array(
                'id_option' => 3,
                'name'      => 'Boletas de venta y servicios'
            ),
            array(
                'id_option' => 4,
                'name'      => 'Boleta de Espectáculo emitida por cuenta de Terceros'
            ),
        );

        $fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Configuración'),
					'icon'  => 'icon-cogs'
				),
				'description' => $this->l('Formulario de configuración.'),
				'input' => array(
					array(
						'type'  => 'text',
						'label' => $this->l('Url Api Rest'),
                        'name'  => 'factura_rest_url',
                        'required'  => true, 
					),
					array(
						'type'  => 'text',
						'label' => $this->l('Id Empresa'),
						'name'  => 'factura_empresa_id',
                        'desc'  => $this->l('Enter here your bussines id details.'),
                        'required'  => true, 
                    ),
                    array(
						'type'  => 'text',
						'label' => $this->l('Id Usuario'),
						'name'  => 'factura_usuario_id',
                        'desc'  => $this->l('Enter here your user id details.'),
                        'required'  => true, 
                    ),
                    array(
						'type'  => 'text',
						'label' => $this->l('Clave'),
                        'name'  => 'factura_clave',
                        'required'  => true, 
                    ),
                    array(
						'type'  => 'text',
						'label' => $this->l('RUT de la Compañia'),
                        'name'  => 'factura_rut_compania',
                        'required'  => true, 
                    ),
                    array(
						'type'  => 'text',
						'label' => $this->l('Tipo de documento'),
                        'name'  => 'factura_tipo_documento',
                        'required'  => true, 
                    ),
                    array(
                        'type'  => 'select',                              // This is a <select> tag.
                        'label' => $this->l('Indicador de Servicio'),         // The <label> for this <select> tag.
                        'desc'  => $this->l('Seleccione un indicador de ervicio'),  // A help text, displayed right next to the <select> tag.
                        'name'  => 'factura_indicador_servicio',                     // The content of the 'id' attribute of the <select> tag.
                        'required'  => true,                              // If set to true, this option must be set.
                        'options'   => array(
                            'query' => $options,                           // $options contains the data itself.
                            'id'    => 'id_option',                           // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
                            'name'  => 'name'                               // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
                        )
                    ),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
        );

		$helper                             = new HelperForm();
		$helper->show_toolbar               = false;
		$helper->table                      = $this->table;
		$lang                               = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language      = $lang->id;
		$helper->allow_employee_form_lang   = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form                  = array();

		$helper->identifier     = $this->identifier;
		$helper->submit_action  = 'submitFactura';
		$helper->currentIndex   = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token          = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars       = array(
			'fields_value'  => $this->assignConfiguration(),
			'languages'     => $this->context->controller->getLanguages(),
			'id_language'   => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
    }

    public function emisorForm()
	{
        $fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Configuración Emisor - Opcional*'),
					'icon'  => 'icon-cogs'
				),
                'description' => $this->l('Formulario de configuración emisor SII.'),
				'input' => array(
					array(
						'type'  => 'text',
						'label' => $this->l('Código de la sucursal del Emisor'),
                        'name'  => 'codigo_emisor',
					),
					array(
						'type'  => 'text',
						'label' => $this->l('Dirección de la sucursal del Emisor'),
						'name'  => 'direccion_emisor',
                    ),
                    array(
						'type'  => 'text',
						'label' => $this->l('Comuna de la sucursal del Emisor'),
						'name'  => 'comuna_emisor',
                    ),
                    array(
						'type'  => 'text',
						'label' => $this->l('Ciudad de la sucursal del Emisor'),
                        'name'  => 'ciudad_emisor',
                    ),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
        );

		$helper                             = new HelperForm();
		$helper->show_toolbar               = false;
		$helper->table                      = $this->table;
		$lang                               = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language      = $lang->id;
		$helper->allow_employee_form_lang   = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form                  = array();

		$helper->identifier     = $this->identifier;
		$helper->submit_action  = 'submitEmisor';
		$helper->currentIndex   = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token          = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars       = array(
			'fields_value'  => $this->assignEmisorConfiguration(),
			'languages'     => $this->context->controller->getLanguages(),
			'id_language'   => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
    }
    
    public function item_detalle($cart)
    {
        $json = json_decode(json_encode($cart));   
        $itemDetalle;
        $c = 0;
        $n = 1;
        foreach ($json as $obj) {
            $itemDetalle[$c++] = array(
                    "Nrolinea"=>$n++,
                    "TipoCodigo"=> $code_product = ($obj->obj->product_ean13 == "") ? "" : "EAN-13",
                    "CodigoProducto"=> $code_product = ($obj->product_ean13 == "") ? "" : $obj->product_ean13,
                    "DescripcionProducto"=> $obj->product_name,
                    "Cantidad"=>$obj->product_quantity,
                    "UnidadMedida"=> "UN",
                    "PrecioUnitario"=> $obj->unit_price_tax_incl,
                    "PorcentajeDescuento"=> $obj->reduction_percent,
                    "MontoDescuento"=> $total_discount = ($obj->unit_price_tax_incl * $obj->reduction_percent / 100),
                    "PorcentajeRecargo"=> 0,
                    "MontoRecargo"=> 0,
                    "Total"=> $obj->total_price_tax_incl,
                    "IndicadorExepcionTotal"=> 0,
                    "DescripcionExtendida"=> $obj->product_name 
            );
        }
        return json_encode($itemDetalle);
    }

    public function consultaPaymentOrder($id_cart, $id_order)
    {
        $sql = '
        SELECT * FROM `'._DB_PREFIX_.'xpec_webpay_payment` as w
        INNER JOIN `'._DB_PREFIX_.'orders` as o 
        WHERE w.id_cart = '. (int)$id_cart .' 
        AND o.id_order = '.(int)$id_order;

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    public function item_medio_pago($id_cart, $id_order)
    {
        #$json = json_decode(json_encode($order_payment));
        $webpay = $this->consultaPaymentOrder($id_cart, $id_order);

        $c = 0;
        $itemMedioPago;
        $json = json_decode(json_encode($webpay));
        foreach ($json as $obj) {
            
            $itemMedioPago[$c++] = array(
                "Nrolinea"=> $c++,
                "CodMedioPago"=> $code = ($obj->payment_type == "Venta Debito") ? 5 : 4 ,
                "CodTarjeta"=> "",
                "NroCuotas"=> $code = ($obj->payment_type == "Venta Debito") ? 0 : $obj->card_fee ,
                "CodOperacion"=> $obj->reference,
                "NumTarjeta"=> $obj->card_number,
                "CodAutorizacion"=>$obj->auth_code
            );
        }
        
        return json_encode($itemMedioPago);
    }

    public function item_descuento_recargo()
    {
        $itemDescuentoRecargo = array(
            "linea"=> 1,
            "TipoMovimiento"=> "",
            "Glosa"=> "",
            "TipoValor"=> "",
            "Valor"=> 200,
            "IndicadorExepcion"=>0      
        );
        return json_encode($itemDescuentoRecargo);
    }

    public function json_factura()
    {
        # Obtine el parametro de la vista 'id_order'
        $id_order = Tools::getValue('id_order');
        
        $order = new Order($id_order);
        $order_list = $order->getOrderDetailList();
        $cart = $order->getCartProducts();
        $customer = $order->getCustomer();
       
        $itemDetalle = $this->item_detalle($order_list);
        $itemDescuentoRecargo = $this->item_descuento_recargo();
        $itemMedioPago = $this->item_medio_pago($order->id_cart, $order->id);
        
        $boletaElectronica = array(
            "RutEmpresa"=> Configuration::get('FACTURA_RUT_COMPANIA'),
            "TipoDocumento"=> Configuration::get('FACTURA_TIPO_DOCUMENTO'),
            "Numero"=>$id_order,
            "FechaEmision"=> date(DATE_ATOM, mktime(0, 0, 0, 7, 1, 2000)),
            "FechaVencimiento"=> "2017-11-10T00:00:00",
            "IndicadorServicio"=> Configuration::get('FACTURA_INDICADOR_SERVICIO'),
            "PorcentajeImpuesto"=> $order->carrier_tax_rate,
            "ExentoFuncional"=> 0,
            "AfectoFuncional"=> $order->total_paid_tax_excl,
            "ImpuestoFuncional"=> $tax = $order->total_paid_tax_incl - $order->total_paid_tax_excl,
            "TotalFuncional"=> $order->total_paid_tax_incl,
            "MontoNoFActurable"=> 0,
            "CodigoSucursal"=> Configuration::get('EMISOR_SUCURSAL'),
            "DireccionSucursal"=> Configuration::get('EMISOR_DIRECCION'),
            "ComunaSucursal"=> Configuration::get('EMISOR_COMUNA'),
            "CiudadSucursal"=> Configuration::get('EMISOR_CIUDAD'),
            "RutCliente"=> "66666666-6" . '*',
            "CodigoInternoCliente"=> "66666666-6" . '*',
            "RazonSocial"=> "Cliente boleta",
            "DireccionCliente"=> "San Nicolas #600" . '*',
            "ComunaCliente"=> "San Miguel" . '*',
            "CiudadCliente"=> "Santiago" . '*',
            "DireccionPostal"=> "" . '*',
            "ContactoCliente"=> "" . '*',
            "Estado"=> true . '*',
            "ItemDetalle" => $itemDetalle,
            "ItemDescuentoRecargo" => $itemDescuentoRecargo,
            "ItemMedioPago" => $itemMedioPago,
        );
        echo '<pre>'.print_r($order_list).'</pre>';
        die();
    }
}