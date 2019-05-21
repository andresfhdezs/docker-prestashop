<?php
require_once(_PS_MODULE_DIR_ . 'factura/classes/ClassFactura.php');

class AdminMyFacturaController extends ModuleAdminController
{
    public function __construct()
    {
        // Set variables
        $this->table = 'servicio_factura';
        $this->className = 'ServicioFactura';
        $this->fields_list = array(
            'id_servicio_factura' => array('title' => $this->l('ID'), 'align' => 'center', 'width' => 25),
            'id_order' => array('title' => $this->l('Id Pedido'), 'width' => 120),
            'resp' => array('title' => $this->l('Respuesta'), 'width' => 140),
            'send' => array('title' => $this->l('Enviado?'), 'width' => 150),
            'date_add' => array('title' => $this->l('Date add'), 'type' => 'date'),
        );
        // Enable bootstrap
        $this->bootstrap = true;
        
        // Call of the parent constructor method
        parent::__construct();

        # Enable Actions
        $this->addRowAction('view');

        $admin_module_link = $this->context->link->getAdminLink('AdminModules') . '&configure=' . Tools::safeOutput($this->module->name);

        $this->page_header_toolbar_btn['view'] = array(
			'href' => $admin_module_link,
			'desc' => $this->l('Ir a configuración'),
			'icon' => 'process-icon-edit',
        );
        
        // Define meta and toolbar title
		$this->meta_title = $this->l('Servicios Enviados');
		if (Tools::getIsset('viewservicio_factura'))
			$this->meta_title = $this->l('Ver servicio').' #'. Tools::getValue('id_servicio_factura');
		$this->toolbar_title[] = $this->meta_title;
    }
    
    public function renderView()
    {
        $servicioFactura = new ClassFactura();
        $servicio_factura = $servicioFactura::selectService(Tools::getValue('id_servicio_factura'));
        #var_dump($servicio_factura);
        #die();
        
        $tpl = $this->context->smarty->createTemplate(_PS_MODULE_DIR_.'factura/views/templates/admin/view.tpl');
        $tpl->assign('serviciofactura', $servicio_factura);
        $tpl->assign('link', Context::getContext()->link);
        return $tpl->fetch();
    }
	
}