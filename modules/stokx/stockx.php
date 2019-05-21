<?php
if (!defined('_PS_VERSION_')) {
  exit;
}

class StockX extends Module 
{
    public function __construct()
    {
        $this->name = 'stockx';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Andres Hdez';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_); 
        $this->bootstrap = true;

        parent::__construct();
 
        $this->displayName = $this->l('StockX');
        $this->description = $this->l('Muestra el stock en página de detalles de producto.');
    
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    
        if (!Configuration::get('STOCKX_NAME')) {
        $this->warning = $this->l('No name provided');
        }
    }
    
    
}