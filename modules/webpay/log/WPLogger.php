<?php

class WPLogger {

    private static $logger;

    public static function logDebug($logText) {
        if( !isset($logger) ){
            $logger = new FileLogger(0);
            $logger->setFileName(_PS_ROOT_DIR_."/log/payment.log");
        }
        $logger->logDebug($logText);
    }

}