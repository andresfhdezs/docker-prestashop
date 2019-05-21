<?php
class WebPayPaymenteModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        $this->display_column_left = false;
        $this->display_column_right = false;
        parent::initContent();
        $transaction = getTransactionByToken($_GET['wp_token']);
        $result_code = isset($transaction['result_code']) ? (int)$transaction['result_code'] : -100;
        Context::getContext()->smarty->assign(array(
            'WEBPAY_TX_ANULADA' => $result_code == -100 ? "SI" : "NO",
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
        $cart = new Cart((int)$transaction['oc']);
        $losProductos=$cart->getProducts();
            $JsonProducts = array();
            foreach ($losProductos as $elProducto) {

                $elArray= array("sku"=>$elProducto['reference'],
                                "name"=>$elProducto['name'],
                                "category"=>$elProducto['category'],
                                "price"=>$elProducto['price_with_reduction'],
                                "quantity"=>$elProducto['quantity']);

                array_push($JsonProducts, $elArray);
            }    
            $transactionProducts = json_encode($JsonProducts);
            $idOrden = Order::getOrderByCartId((int)$transaction['oc']);
            $laOrden = new Order((int)$idOrden);
            $valorTransporte=round($laOrden->total_shipping_tax_incl);
            Context::getContext()->smarty->assign(
                array('transactionShipping'=>$valorTransporte,
                    'transactionProducts'=>$transactionProducts));

        //return $this->display(_PS_MODULE_DIR_, 'webpay/views/templates/front/paymente.tpl');
        $this->setTemplate('paymente.tpl');
    }    

}
