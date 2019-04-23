<?php
    if (!defined('_PS_VERSION_')) {
        exit;
    }
    
    class PwaModule extends Module
    {
        public function __construct()
        {
            $this->name = 'pwamodule';
            $this->tab = 'front_office_features';
            $this->version = '0.1.0';
            $this->author = 'Xpectrum Technology';
            $this->need_instance = 0;
            $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_); 
            $this->bootstrap = true;
        
            parent::__construct();
        
            $this->displayName = $this->l('Generador Factura');
            $this->description = $this->l('Genera una factura.');
        
            $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        }
    }