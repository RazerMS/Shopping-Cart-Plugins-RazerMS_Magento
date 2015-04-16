<?php
/**
 * MOLPay Sdn. Bhd.
 *
 * @package     MOLPay Magento Plugin
 * @author      netbuilder <code@netbuilder.com.my>
 * @copyright   Copyright (c) 2012 - 2015, MOLPay
 * @link        http://molpay.com
 * @since       Version 1.9.x.x
 * @update      MOLPay <technical@molpay.com>
 * @filesource  https://github.com/MOLPay/Magento_Plugin
 */

class Mage_Checkout_Block_Notification extends Mage_Core_Block_Template
{
    public function getRealOrderId() {
        $order = Mage::getModel('sales/order')->load($this->getLastOrderId());
        #print_r($order->getData());
        return $order->getIncrementId();
    }
}
