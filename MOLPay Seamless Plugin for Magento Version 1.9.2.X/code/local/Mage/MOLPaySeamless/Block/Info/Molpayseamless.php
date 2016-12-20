<?php
/**
 * MOLPay Sdn. Bhd.
 *
 * @package     MOLPay Magento Seamless Plugin
 * @author      netbuilder <code@netbuilder.com.my>
 * @copyright   Copyright (c) 2012 - 2014, MOLPaySeamless
 * @link        http://molpay.com
 * @since       Version 1.8.x.x
 * @update      MOLPay <technical@molpay.com>
 * @filesource  https://github.com/MOLPay/Magento_Seamless_Plugin
*/

// app/code/local/Envato/Custompaymentmethod/Block/Info/Custompaymentmethod.php
class Mage_MOLPaySeamless_Block_Info_Molpayseamless extends Mage_Payment_Block_Info
{
  protected function _prepareSpecificInformation($transport = null)
  {
    if (null !== $this->_paymentSpecificInformation) 
    {
      return $this->_paymentSpecificInformation;
    }
     
    $data = array();
    if ($this->getInfo()->getMolpayseamlessPaymentChannel()) 
    {
      $data[Mage::helper('payment')->__('Payment Channel')] = $this->getInfo()->getMolpayseamlessPaymentChannel();
    }
     
 
    $transport = parent::_prepareSpecificInformation($transport);
     
    return $transport->setData(array_merge($data, $transport->getData()));
  }
}
