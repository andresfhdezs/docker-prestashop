<?php

require_once _PS_MODULE_DIR_.'webpay/webpay.php';
require_once _PS_MODULE_DIR_.'webpay/libwebpay/webpay-soap.php';
require_once(_PS_MODULE_DIR_.'webpay/log/WPLogger.php');
require_once(_PS_MODULE_DIR_.'webpay/funcs/functions.php');

class WebPayValidateModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        $this->display_column_left = false;
        $this->display_column_right = false;
       	parent::initContent();        
        $this->confirm();        
    }
    
    public function confirm() {
    	$privatekey = Configuration::get('WEBPAY_SECRETCODE');
    	$comercio = Configuration::get('WEBPAY_STOREID');        
        WPLogger::logDebug("confirm: comercio -> ".$comercio);
    	$errorResponse = array('status' => 'RECHAZADO', 'c' => $comercio);
    	$acceptResponse = array('status' => 'ACEPTADO', 'c' => $comercio);        
        WPLogger::logDebug("confirm: Respuesta del servicio -> ".json_encode($_POST));
        $token = isset($_POST['token_ws']) ? $_POST['token_ws'] : $_POST['TBK_TOKEN'];
        $transaction = getTransactionByToken($token);
        WPLogger::logDebug("confirm: TRANS -> ".json_encode($transaction));        
        WPLogger::logDebug("confirm: RCODE -> ".$transaction['rcode']);
        if ( (!isset($transaction['rcode']) || $transaction['rcode'] == NULL) && isset($_POST) && sizeof($_POST)==1) {
            $data = $this->process_response( $_POST, $transaction );
            WPLogger::logDebug("confirm: data después de llamar a process_response con la data -> ".json_encode($data));
        } else {
            WPLogger::logDebug("confirm: Redireccionando a la página de payment_return");
            $this->handleGET($transaction);
        }
    }           

    private function paymentAuthorized(){        
        foreach (Module::getPaymentModules() as $module){
            if ($module['name'] == 'webpay'){
                return true;
            }
        }
        return false;
    }    

    private function changeOrderStatus($status, $cart_id){        
        $order = new Order(Order::getOrderByCartId((int)$cart_id));
        WPLogger::logDebug("process_response: Cambiando a estado ".$status." la orden  -> ".(int)$order->id);
        $order->setCurrentState((int)$status);
    }

    private function createOrder(){
        $cart = Context::getContext()->cart;
        $customer = new Customer($cart->id_customer);
	//Casting agregado
        $this->module->validateOrder(
            (int)$cart->id, 
            Configuration::get('PS_OS_PAYMENT'), 
            $cart->getOrderTotal(true, Cart::BOTH), 
            $this->module->displayName, 
            NULL, NULL, 
            (int)Context::getContext()->currency->id,
            false, 
            $customer->secure_key
        );
        $order = new Order(Order::getOrderByCartId((int)$cart->id));
        return $order;
    }

    public function showPaymentReturnPage($transaction){
        $result_code = (int)$transaction['result_code'];
        Context::getContext()->smarty->assign(array(
            'WEBPAY_TX_ANULADA' => $result_code == 0 ? "NO" : "SI",
            'WEBPAY_RESULT_CODE' => $result_code,
            'WEBPAY_RESULT_DESC' => $transaction['result_desc'],
            'WEBPAY_VOUCHER_NROTARJETA' => $transaction['card_number'],
            'WEBPAY_VOUCHER_TXDATE_FECHA' => $transaction['tx_date'],
            'WEBPAY_VOUCHER_TXDATE_HORA' => $transaction['tx_hour'],
            'WEBPAY_VOUCHER_TOTALPAGO' => (int)$transaction['total'],
            'WEBPAY_VOUCHER_ORDENCOMPRA' => $transaction['oc'],
            'WEBPAY_VOUCHER_AUTCODE' => $transaction['auth_code'],
            'WEBPAY_VOUCHER_TIPOPAGO' => $transaction['payment_type'],
            'WEBPAY_VOUCHER_TIPOCUOTAS' => $transaction['fee_type'],
            'WEBPAY_VOUCHER_NROCUOTAS' => $transaction['card_fee']
        ));        
           
    }

    public function process_response($data, $transaction) {
        if (isset($data["token_ws"])) {
            $token_ws = $data["token_ws"];
        } else {
            $token_ws = 0;
        }        
        if( !existsTransaction($token_ws) ){
            WPLogger::logDebug("process_response: La transacción ".$token_ws." no existe");
            die($this->module->l('This payment method is not available.', 'validation'));
        }
        $error_transbank = false;
        $config = array(
            "MODO"            => Configuration::get('WEBPAY_AMBIENT'),             
            "PRIVATE_KEY"     => Configuration::get('WEBPAY_SECRETCODE'),
            "PUBLIC_CERT"     => Configuration::get('WEBPAY_CERTIFICATE'),
            "WEBPAY_CERT"     => Configuration::get('WEBPAY_CERTIFICATETRANSBANK'),            
            "CODIGO_COMERCIO" => Configuration::get('WEBPAY_STOREID'),
            "URL_FINAL"       => Context::getContext()->link->getModuleLink('webpay', 'validate', array(), true),//Configuration::get('WEBPAY_NOTIFYURL'),
            "URL_RETURN"      => Context::getContext()->link->getModuleLink('webpay', 'validate', array(), true)//Configuration::get('WEBPAY_POSTBACKURL')
        );           
        try{
            $webpay = new WebPaySOAP($config);
            WPLogger::logDebug("process_response: Llamando a servicio WebPaySOAP.webpayNormal.getTransactionResult");
            $result = $webpay->webpayNormal->getTransactionResult($token_ws);
            WPLogger::logDebug("process_response: Respuesta del servicio WebPaySOAP.webpayNormal.getTransactionResult".json_encode($result));
        }catch(Exception $e){
            WPLogger::logDebug("process_response: Error en la transacción -> ".print_r($e, TRUE));
            $result["error"] = "Error conectando a Webpay";
            $result["detail"] = $e->getMessage();
            $error_transbank = true;
        }        
        fillTransaction($token_ws, $result);
        if ($result->buyOrder && !$error_transbank) {
            if ( $result->detailOutput->responseCode == 0 ){
                WPLogger::logDebug("process_response: Ha sido aceptada la orden con buyOrder: ".$result->buyOrder);

  $cart = new Cart($result->buyOrder);
                WPLogger::logDebug('Valor Carro'.$cart->getOrderTotal(true, Cart::BOTH));
                WPLogger::logDebug('Carrito'.json_encode($cart));
                $amount = (int)$cart->getOrderTotal(true, Cart::BOTH);
                
                if( $result->detailOutput->amount == $amount ){                    
                    if ($webpay->webpayNormal->acknowledgeTransaction($token_ws)){
                        $order = $this->createOrder();
                        updateOrderId($token_ws, $order->id);
                        $transaction = getTransactionByToken($token_ws);
                        $this->updateOrderPaymentDetails($transaction);
                        WPLogger::logDebug("process_response: Redireccionando a  -> ".$result->urlRedirection." -- buyOrder: ".$result->buyOrder);
                        WebPaySOAP::redirect($result->urlRedirection, array("token_ws" => $token_ws));   
                    }else{
                        WPLogger::logDebug("process_response: El servicio de ACK de webpay ha fallado !! -- buyOrder: ".$result->buyOrder);
                        ackFailed($token_ws);
                        Tools::redirect(
                            Context::getContext()->link->getModuleLink('webpay', 'paymente', array(
                                "wp_token" => $transaction["wp_token"]
                            ), true)
                        );  
                    }
                }else{
                    amountInconsistency($token_ws);
                    WPLogger::logDebug("process_response: Inconsistencia entre el monto pagado y el monto del carro -> ".$result->detailOutput->amount." != ".$amount." -- buyOrder: ".$result->buyOrder);
                    Tools::redirect(
                        Context::getContext()->link->getModuleLink('webpay', 'paymente', array(
                            "wp_token" => $transaction["wp_token"]
                        ), true)
                    );                    
                }
            } else {
                $responseDescription = htmlentities($result->detailOutput->responseDescription);
                WPLogger::logDebug("process_response: no se generó voucher -- buyOrder: ".$result->buyOrder);
                $cart = Context::getContext()->cart;
                $customer = new Customer($cart->id_customer);
                if (!Validate::isLoadedObject($customer)){
                    WPLogger::logDebug("process_response: Redireccionando a -> index.php?controller=order&step=1 -- buyOrder: ".$result->buyOrder);
                    Tools::redirect('index.php?controller=order&step=1');
                }
                WPLogger::logDebug("process_response: Orden marcada como error, redireccionando -- buyOrder: ".$result->buyOrder);              
                Tools::redirect(
                    Context::getContext()->link->getModuleLink('webpay', 'paymente', array(
                        "wp_token" => $token_ws
                    ), true)
                );
            }
        }else{
            WPLogger::logDebug("process_response: Error, no hay buyOrder o hubo un error al conectar con transbank !!");
        }
    }    
    
    private function handleGET($transaction){
        $cart = Context::getContext()->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active){
            Tools::redirect('index.php?controller=order&step=1');        
        }
        $customer = new Customer($cart->id_customer);
        WPLogger::logDebug("handleGET: CustomerId -> ".$cart->id_customer);
        if (!Validate::isLoadedObject($customer)){
            Tools::redirect('index.php?controller=order&step=1');
        }
        WPLogger::logDebug("handleGET: Transacción -> ".json_encode($transaction));
        WPLogger::logDebug("handleGET: Module name -> ".$this->module->displayName);        
        if( isset($transaction['rcode']) && ($transaction['rcode'] == "SUCCESS_NOT_VIEWED" || $transaction-['rcode'] == "SUCCESS_VIEWED") ){
            WPLogger::logDebug("handleGET: Cambiando transacción a visto por cliente...");
            setTransactionAsViewed($transaction['wp_token']);
            WPLogger::logDebug("handleGET: Transacción cambiada a visto por cliente!!!"); 
            $this->showPaymentReturnPage($transaction);
        }
        Tools::redirect(
            Context::getContext()->link->getModuleLink('webpay', 'paymente', array(
                "wp_token" => $transaction["wp_token"]
            ), true)
        );
    }

    private function updateOrderPaymentDetails($transaction){
        /* Xpec - Register order details */
        $sql = 'UPDATE ps_order_payment pop INNER JOIN ps_orders po ON po.reference = pop.order_reference SET pop.transaction_id = "' . $transaction['auth_code'] . '", card_number= "'. $transaction['card_number'] .'",  card_expiration="'. $transaction['card_exp']  .'" WHERE po.id_order = '.((int)$transaction['id_order']);
        Db::getInstance()->execute($sql);
    }
    
}
