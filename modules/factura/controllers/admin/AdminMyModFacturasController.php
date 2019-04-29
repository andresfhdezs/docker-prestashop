<?php
class AdminMyModFacturasController extends ModuleAdminController
{
    public function installTab($parent, $class_name, $name)
    {
    // Create new admin tab
    $tab = new Tab();
    #$tab->id_parent = (int)Tab::getIdFromClassName($parent);
    $tab->name = array();
    foreach (Language::getLanguages(true) as $lang)
    $tab->name[$lang['id_lang']] = $name;
    $tab->class_name = $class_name;
    $tab->module = $this->name;
    $tab->active = 1;
    return $tab->add();
    }
}
