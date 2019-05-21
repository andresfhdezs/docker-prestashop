<?php
class facturaMyFacturaModuleFrontController extends ModuleFrontController
{
    public function initContent()
	{
		parent::initContent();
		$this->context->smarty->assign(array(
			'hello' => 'Hello World!!!',
		));
		$this->setTemplate('myfactura.tpl');
	}
}

