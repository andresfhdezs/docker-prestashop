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
        $this->version                  = '0.1.2';
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

    public $from = _PS_MODULE_DIR_ . "factura/override/controllers/admin/templates/orders/";
    public $to = _PS_ROOT_DIR_ . "/override/controllers/admin/templates/orders/";

    public function install()
    {
        if (!parent::install()
        || !Configuration::updateValue('FACTURA_REST_URL',      'http://127.0.0.1/api/BoletaElectronica/Save')
        || !Configuration::updateValue('FACTURA_EMPRESA_ID',    '1')
        || !Configuration::updateValue('FACTURA_USUARIO_ID',    'user@mail.com')
        || !Configuration::updateValue('FACTURA_CLAVE',         'xxxx-xxxx-xxxx-xxxx')
        || !Configuration::updateValue('FACTURA_RUT_COMPANIA',  '999999999')
        || !Configuration::updateValue('FACTURA_TIPO_DOCUMENTO','RC')
        || !Configuration::updateValue('FACTURA_INDICADOR_SERVICIO',    3)
        || !Configuration::updateValue('EMISOR_SUCURSAL', '')
        || !Configuration::updateValue('EMISOR_DIRECCION', '')
        || !Configuration::updateValue('EMISOR_COMUNA', '')
        || !Configuration::updateValue('EMISOR_CIUDAD', '')) {
            return false;
        }

        if (!file_exists($this->to));
            $this->copy_dir($this->from, $this->to);
        
        if (!$this->installTab());
        
        if (!$this->loadSQL());

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

        if (!$this->uninstallTab());

        if (file_exists($this->to)) $this->rrmdir($this->to);
        
        if (!$this->unloadSQL());
        
        return true;
        
    }

    public function installTab(){
        $tab = new Tab();
        $tab->id_parent = (int)Tab::getIdFromClassName('');
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang)
            $tab->name[$lang['id_lang']] = 'Servicio de Factura';
        $tab->class_name = 'AdminMyFactura';
        $tab->position = 0;
        $tab->active = 1;
        $tab->module = 'factura';
        return $tab->save();
    }

    public function uninstallTab(){
        $id_tab = (int)Tab::getIdFromClassName('AdminMyFactura');
        // Load tab
        $tab = new Tab((int)$id_tab);
        // Delete it
        return $tab->delete();
    }

    public function copy_dir($from, $to){
        
        if (is_dir($from)) 
        {
            mkdir($to);
            $files = scandir($from);
            foreach ($files as $file)
            if ($file != "." && $file != "..") $this->copy_dir("$from/$file", "$to/$file"); 
        }
        else if (file_exists($from)) copy($from, $to);
           
    }
    
    public function rrmdir($dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file)
            if ($file != "." && $file != "..") $this->rrmdir("$dir/$file");
            rmdir($dir);
        }
        else if (file_exists($dir)) unlink($dir);
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
        $html = null;

        $this->processConfiguration();
        $this->processEmisorConfiguration();

        #$html .= Context::getContext()->link->getModuleLink('factura', 'facturero', array('id_order' => 10));
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

    public function loadSQL()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'servicio_factura` ( 
            `id_servicio_factura` INT(11) NOT NULL AUTO_INCREMENT , 
            `id_order` INT(11) NOT NULL , 
            `resp` INT(11) NOT NULL ,
            `send` BOOLEAN NOT NULL,
            `date_add` DATETIME NOT NULL , 
            PRIMARY KEY (`id_servicio_factura`), 
            INDEX `id_order` (`id_order`) ) 
            ENGINE = InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1';
        
        $result = Db::getInstance()->execute($sql);
        // Return result
        return $result;
    }

    public function unloadSQL()
    {
        $sql = 'DROP TABLE `'._DB_PREFIX_.'servicio_factura`;';
        
        $result = Db::getInstance()->execute($sql);
        // Return result
        return $result;
    }
    
    
}