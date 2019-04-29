<?php
class AdminFacturaController extends ModuleAdminController
{

    public function init()
    {
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();
        $this->setTemplate('view.tpl');
    }
}