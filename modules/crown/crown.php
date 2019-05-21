<?php
/*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_'))
	exit;

class Crown extends Module
{
	public function __construct()
	{
		$this->name = 'crown';
		$this->tab = 'front_office_features';
		$this->version = '0.0.2';
		$this->author = 'Xpectrum Technology';
		$this->need_instance = 0;

		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Crown Cyber Day');
		$this->description = $this->l('Imagen categoria CyberDay.');

		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.99.99');
	}

	public function install()
	{
		Configuration::updateValue('CROWN_IMG', 'payment-logo.png');

		$this->_clearCache('crown.tpl');

		return parent::install() && $this->registerHook('displayCrown') && $this->registerHook('header');
	}

	public function uninstall()
	{
		Configuration::deleteByName('CROWN_IMG');

		return parent::uninstall();
	}

	public function hookDisplayCrown($params)
	{
		if (Configuration::get('PS_CATALOG_MODE'))
			return;
		if (!$this->isCached('crown.tpl', $this->getCacheId()))
		{
			$this->smarty->assign(array(
				'crown_img' => 'img/'.Configuration::get('CROWN_IMG')
			));
		}
		return $this->display(__FILE__, 'crown.tpl', $this->getCacheId());
	}

	public function postProcess()
	{
		if (Tools::isSubmit('submitImageCrown'))
		{
			Configuration::updateValue('CROWN_IMG', Tools::getValue('CROWN_IMG'));
			if (isset($_FILES['CROWN_IMG']) && isset($_FILES['CROWN_IMG']['tmp_name']) && !empty($_FILES['CROWN_IMG']['tmp_name']))
			{
				if (ImageManager::validateUpload($_FILES['CROWN_IMG'], 4000000))
					return $this->displayError($this->l('Invalid image'));
				else
				{
					$ext = Tools::substr($_FILES['CROWN_IMG']['name'], Tools::strrpos($_FILES['CROWN_IMG']['name'], '.') + 1);
					$file_name = md5($_FILES['CROWN_IMG']['name']).'.'.$ext;
					if (!move_uploaded_file($_FILES['CROWN_IMG']['tmp_name'], dirname(__FILE__).'/img/'.$file_name))
						return $this->displayError($this->l('An error occurred while attempting to upload the file.'));
					else
					{
						$file_path = dirname(__FILE__).'/img/'.Configuration::get('CROWN_IMG');

						if (Configuration::hasContext('CROWN_IMG', null, Shop::getContext()) &&
							Configuration::get('CROWN_IMG') != $file_name &&
							file_exists($file_path)
						)
							unlink($file_path);

						Configuration::updateValue('CROWN_IMG', $file_name);
						$this->_clearCache('crown.tpl');

						Tools::redirectAdmin('index.php?tab=AdminModules&conf=6&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'));
					}
				}
			}
			$this->_clearCache('crown.tpl');
		}

		return '';
	}

	public function getContent()
	{
		return $this->postProcess().$this->renderForm();
	}

	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings'),
					'icon' => 'icon-cogs'
				),
				'input' => array(
					array(
						'type' => 'file',
						'label' => $this->l('Block image'),
						'name' => 'CROWN_IMG',
						'thumb' => '../modules/'.$this->name.'/img/'.Configuration::get('CROWN_IMG'),
					)
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitImageCrown';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues()
	{
		return array(
			'PRODUCTPAYMENTLOGOS_IMG' => Tools::getValue('CROWN_IMG', Configuration::get('CROWN_IMG')),
		);
	}
}
