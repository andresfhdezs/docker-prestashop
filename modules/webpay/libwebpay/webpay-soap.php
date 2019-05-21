<?php
require_once('webpay-normal.php');
require_once('webpay-config.php');


class WebPaySOAP{	

	var $config;
	var $webpayNormal;

	function __construct($params){
		$this->config = new WebPayConfig($params);
		$this->webpayNormal = new WebPayNormal($this->config);
    }
    

	public function redirect($url, $data){
    	echo  "<form action='" . $url . "' method='POST' name='webpayForm'>";
      	foreach ($data as $name => $value) {
			echo "<input type='hidden' name='".htmlentities($name)."' value='".htmlentities($value)."'>";
		}
		echo  "</form>"
			 ."<script language='JavaScript'>"
             ."document.webpayForm.submit();"
             ."</script>";
   }
}

?>
