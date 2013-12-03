<?php
/**
 * MOLPay Sdn. Bhd.
 *
 * @package	MOLPay Magento Plugin
 * @author    netbuilder <code@netbuilder.com.my>
 * @copyright	Copyright (c) 2012 - 2013, Edi Abdul Rahman.
 * @license	https://github.com/eddy03/molpay_magento_plugin/blob/master/LICENSE
 * @link	https://github.com/eddy03/molpay_magento_plugin
 * @since	Version 1.7.x.x
 * @update	MOLPay <technical@molpay.com>
 * @filesource
 */

class Mage_Checkout_Block_Callback extends Mage_Core_Block_Template
{
  public function getRealOrderId()
  {
    $order = Mage::getModel('sales/order')->load($this->getLastOrderId());
    #print_r($order->getData());
    return $order->getIncrementId();
  }
}
