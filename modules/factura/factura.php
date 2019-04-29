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
        if (!parent::install()) {
            return false;
        }

        if (!file_exists($this->to)) self::copy_dir($this->from, $this->to);;
            return true;

        // Install admin tab
        if (!$this->installTab('AdminMyModFacturas', 'MyMod Facturas'))
            return false;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        if (file_exists($this->to)) self::rrmdir($this->to);
            return true;
    }

    public function getContent()
    {   
        $output = null; 
        $id_order = Tools::getValue('id_order');

        $order = new Order($id_order);
        echo '<pre>',print_r($order),'</pre>';
            die();
        $this->context->smarty->assign(
            array(
                'factura_message' => $this->l('Este es un mensaje de prueba, para el panel de configuración'),
                'order' => $order,

            )
        );
        return $this->display(__FILE__, 'factura.tpl');
    }
}