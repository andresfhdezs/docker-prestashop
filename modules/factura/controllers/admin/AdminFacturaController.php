<?php
class AdminFacturaController extends ModuleAdminController
{
    public function initContent()
	{
		parent::initContent();
		$this->context->smarty->assign(array(
			'hello' => 'Hello World!!!',
		));
		$this->setTemplate('view.tpl');
	}
}