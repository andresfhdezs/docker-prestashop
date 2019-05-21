<?php
require_once(_PS_MODULE_DIR_.'webpay/log/WPLogger.php');

function createTransaction($wp_token, $id_cart, $id_order){
    WPLogger::logDebug("createTransaction: called with wptoken: ".$wp_token);
    Db::getInstance()->insert(
        'xpec_webpay_payment',
        array(
            'id_order' => $id_order,
            'wp_token' => pSQL($wp_token),
            'id_cart' => $id_cart
        )
    );
}

function amountInconsistency($wp_token){
    Db::getInstance()->update(
        'xpec_webpay_payment',
        array(
            'result_code' => -101,
            'result_desc' => "Anulado, inconsistencia de montos"
        ),
        'wp_token=\''.$wp_token.'\''
    );
}

function ackFailed($wp_token){
    Db::getInstance()->update(
        'xpec_webpay_payment',
        array(
            'result_code' => -102,
            'result_desc' => "Anulado, imposible notificar transacción a Webpay"
        ),
        'wp_token=\''.$wp_token.'\''
    );
}

function updateOrderId($wp_token, $id_order){
    Db::getInstance()->update(
        'xpec_webpay_payment',
        array(
            'id_order' => (int)$id_order
        ),
        'wp_token=\''.$wp_token.'\''
    );
}

function getTransactionPaginate($from, $to){
    return Db::getInstance()->executeS(
        "select *, STR_TO_DATE(CONCAT(tx_date,' ',tx_hour), '%d-%m-%Y %H:%i:%s') as tx_datetime from "
        ._DB_PREFIX_."xpec_webpay_payment order by tx_datetime desc limit ".$from.",".$to
    );
}

function existsTransaction($wp_token){
    return Db::getInstance()->getRow(
        'SELECT COUNT(*) FROM '._DB_PREFIX_.'xpec_webpay_payment WHERE wp_token = \''.$wp_token.'\''
    );
}

function getTransactionByCartId($id_cart){
    return Db::getInstance()->getRow(
        'SELECT * FROM '._DB_PREFIX_.'xpec_webpay_payment WHERE id_cart = '.(int)$id_cart
    );
}

function getTransactionByOrderId($id_order){
    return Db::getInstance()->getRow(
        'SELECT * FROM '._DB_PREFIX_.'xpec_webpay_payment WHERE id_order = '.(int)$id_order
    );
}

function getTransactionByToken($wp_token){
    return Db::getInstance()->getRow(
        'SELECT * FROM '._DB_PREFIX_.'xpec_webpay_payment WHERE wp_token = \''.$wp_token.'\''
    );
}

function setTransactionAsViewed($wp_token){
    Db::getInstance()->update(
        'xpec_webpay_payment',
        array(
            'rcode' => 'SUCCESS_VIEWED'
        ),
        'wp_token=\''.$wp_token.'\''
    );
}

function fillTransaction($wp_token, $result){
    WPLogger::logDebug("Result: ".json_encode($result));
    $paymentTypeCodearray = array(
        "VD" => "Venta Debito",
        "VN" => "Venta Normal", 
        "VC" => "Venta en cuotas", 
        "SI" => "3 cuotas sin interés", 
        "S2" => "2 cuotas sin interés", 
        "NC" => "N cuotas sin interés", 
    );
    $fee_type = "Sin cuotas";
    $transactionResponse = "Aceptado";
    if($result->detailOutput->paymentTypeCode == "SI" || $result->detailOutput->paymentTypeCode == "S2" || 
       $result->detailOutput->paymentTypeCode == "NC" || $result->detailOutput->paymentTypeCode == "VC" ){
        $fee_type = $paymentTypeCodearray[$result->detailOutput->paymentTypeCode];
    } 
    if ( $result->detailOutput->responseCode != 0){        
        $transactionResponse = $result->detailOutput->responseDescription;
    }
    $date_tmp = strtotime($result->transactionDate);
    Db::getInstance()->update(
        'xpec_webpay_payment',
        array(
            'result_code' => (int)$result->detailOutput->responseCode,
            'result_desc' => $transactionResponse,
            'total' => $result->detailOutput->amount,
            'acc_date' => $result->accountingDate,
            'oc' => $result->buyOrder,
            'tx_hour' => date('H:i:s',$date_tmp),
            'tx_date' => date('d-m-Y',$date_tmp),
            'card_number' => $result->cardDetail->cardNumber,
            'card_brand' => '',
            'card_exp' => $result->cardDetail->cardExpirationDate,
            'card_fee' => $result->detailOutput->sharesNumber,
            'auth_code' => $result->detailOutput->authorizationCode,
            'payment_type' => $paymentTypeCodearray[$result->detailOutput->paymentTypeCode],
            'fee_type' => $fee_type,
            'rcode' => $result->detailOutput->responseCode == 0 ? 'SUCCESS_NOT_VIEWED' : 'PAYMENT_ERROR'
        ),
        'wp_token=\''.$wp_token.'\''
    );
}