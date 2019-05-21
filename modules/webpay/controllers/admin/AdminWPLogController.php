<?php
require_once(_PS_MODULE_DIR_.'webpay/funcs/functions.php');

class AdminWPLogController extends ModuleAdminController{
    public function __construct()
	{
		parent::__construct();
	}
    public function ajaxProcessGetLog(){
        $from = (int)$_POST["from"];
        $to = (int)$_POST["to"];
        $logs = getTransactionPaginate($from, $to);
        $json = array(
            "from" => $from+$to,
            "to" => $to,
            "logs" => $logs
        );
        die(Tools::jsonEncode($json));
    }    
}