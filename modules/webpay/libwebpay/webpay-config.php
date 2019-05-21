<?php

class WebPayConfig{
	private $params = array();

	function __construct($params){
		$this->params = $params;
	}

	public function getParams(){        
        return $this->params;
    }

	public function getParam($name){        
        return $this->params[$name];
    }

	public function getModo(){
		$modo = $this->params["MODO"];
        if (!isset($modo) || $modo == ""){
            $modo = "INTEGRACION";
        }
		return $modo;
	}
}


?>
