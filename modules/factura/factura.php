<?php
if (!defined('_PS_VERSION_')) {
    exit;
}
    
class Factura extends Module
{
    public function __construct()
    {
        $this->name = 'factura';
        $this->tab = 'billing_invoicing';
        $this->version = '0.0.1';
        $this->author = 'Xpectrum Technology';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_); 
        $this->bootstrap = true;
        $this->controllers = array('view');
        
        parent::__construct();
        
        $this->displayName = $this->l('Generador Factura');
        $this->description = $this->l('Genera una factura.');
        
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public $from = _PS_MODULE_DIR_."factura/override/controllers/admin/templates/orders/";
    public $to = _PS_ROOT_DIR_."/override/controllers/admin/templates/orders/";


    function copy_dir($from, $to){
        
        if (is_dir($from)) 
        {
            mkdir($to);
            $files = scandir($from);
            foreach ($files as $file)
            if ($file != "." && $file != "..") self::copy_dir("$from/$file", "$to/$file"); 
        }
        else if (file_exists($from)) copy($from, $to);
           
    }

    function rrmdir($dir) {
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
        || !Configuration::updateValue('FACTURA_REST_URL', 'http://190.196.5.70/api/BoletaElectronica/Save')
        || !Configuration::updateValue('FACTURA_EMPRESA_ID', '1')
        || !Configuration::updateValue('FACTURA_USUARIO_ID', 'demo')
        || !Configuration::updateValue('FACTURA_CLAVE', 'demopass')) {
            return false;
        }

        if (!file_exists($this->to)) self::copy_dir($this->from, $this->to);;
            return true;

        // Install admin tab
        $this->installTab('AdminFactura', 'Genera Factura');
    }

    public function uninstall()
    {
        if (!parent::uninstall()
        || !Configuration::deleteByName('FACTURA_REST_URL')
        || !Configuration::deleteByName('FACTURA_EMPRESA_ID')
        || !Configuration::deleteByName('FACTURA_USUARIO_ID')
        || !Configuration::deleteByName('FACTURA_CLAVE')) {
            return false;
        }

        if (file_exists($this->to)) self::rrmdir($this->to);
            return true;

        // Uninstall admin tab
        if (!$this->uninstallTab())
            return false;
    }

    public function processConfiguration()
    {
        if (Tools::isSubmit('submitfactura'))
        {
            $factura_rest_url = strval(Tools::getValue('FACTURA_REST_URL'));
            $factura_empresa_id = strval(Tools::getValue('FACTURA_EMPRESA_ID'));
            $factura_usuario_id = strval(Tools::getValue('FACTURA_USUARIO_ID'));
            $factura_clave = strval(Tools::getValue('FACTURA_CLAVE'));

            if (!$factura_rest_url || empty($factura_rest_url) || !Validate::isGenericName($factura_rest_url)
                || !$factura_empresa_id || empty($factura_empresa_id) || !Validate::isGenericName($factura_empresa_id)
                || !$factura_usuario_id || empty($factura_usuario_id) || !Validate::isGenericName($factura_usuario_id)
                || !$factura_clave || empty($factura_clave) || !Validate::isGenericName($factura_clave)) 
                $this->context->smarty->assign('confirmation', 'error');
            else
            {
                Configuration::updateValue('FACTURA_REST_URL', $factura_rest_url);
                Configuration::updateValue('FACTURA_EMPRESA_ID', $factura_empresa_id);
                Configuration::updateValue('FACTURA_USUARIO_ID', $factura_usuario_id);
                Configuration::updateValue('FACTURA_CLAVE', $factura_clave);
                $this->context->smarty->assign('confirmation', 'ok');
            }
        }
    }

    public function assignConfiguration()
    {
        $factura_rest_url   = Configuration::get('FACTURA_REST_URL');
        $factura_empresa_id = Configuration::get('FACTURA_EMPRESA_ID');
        $factura_usuario_id = Configuration::get('FACTURA_USUARIO_ID');
        $factura_clave      = Configuration::get('FACTURA_CLAVE');

        $this->context->smarty->assign('factura_rest_url', $factura_rest_url);
        $this->context->smarty->assign('factura_empresa_id', $factura_empresa_id);
        $this->context->smarty->assign('factura_usuario_id', $factura_usuario_id);
        $this->context->smarty->assign('factura_clave', $factura_clave);
    }

    public function getContent()
    {   
        $output = null; 
        $id_order = Tools::getValue('id_order');
        $this->processConfiguration();
        $this->assignConfiguration();
        
        $order = new Order($id_order);
        #echo '<pre>',print_r($order),'</pre>';
            #die();

        return $this->display(__FILE__, 'factura.tpl');
    }
    
    public function installTab($className, $tabName, $tabParentName = false)
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $className;
        $tab->name = array();
        
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tabName;
        }
        if ($tabParentName) {
            $tab->id_parent = (int) Tab::getIdFromClassName($tabParentName);
        } else {
            $tab->id_parent = 0;
        }
        $tab->module = $this->name;
        return $tab->add();
    }


    public function uninstallTab()
    {
        // Retrieve Tab ID
        $id_tab = (int)Tab::getIdFromClassName('AdminFactura');
        // Load tab
        $tab = new Tab((int)$id_tab);
        // Delete it
        return $tab->delete();
    }
}