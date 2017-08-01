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

class Mage_MOLPaySeamless_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract {
  
    protected $_code  = 'molpayseamless';
    protected $_formBlockType = 'molpayseamless/paymentmethod_form';
    protected $_infoBlockType = 'molpayseamless/info_molpayseamless';

    // AVAILABILITY OPTIONS
    // Is this payment method a gateway (online auth/charge) ?
    protected $_isGateway               = true;
    // Can authorize online?
    protected $_canAuthorize            = false;
    // Can capture funds online?
    protected $_canCapture              = true; // true
    protected $_canCapturePartial       = false;
    // Can refund online?
    protected $_canRefund               = false;
    // Can void transactions online?
    protected $_canVoid                 = false;
    // Can use this payment method in administration panel?
    protected $_canUseInternal          = true;
    // Can show this payment method as an option on checkout payment page?
    protected $_canUseCheckout          = true;
    // Is this payment method suitable for multi-shipping checkout?
    protected $_canUseForMultishipping  = false;

     protected $_quote;
     protected $_order;
    // add MYR here or commented function validate()
    //protected $_allowCurrencyCode = array('MYR');


    public function isInitializeNeeded() {
        return true;
    }

    public function initialize($paymentAction, $stateObject) {
        $state = Mage_Sales_Model_Order::STATE_NEW;
        $stateObject->setState( $state );
        $stateObject->setStatus( Mage::getSingleton('sales/order_config')->getStateDefaultStatus( $state ) );
        $stateObject->setIsNotified( false );
    }


    /**
    * Get checkout session namespace
    * @return Mage_Checkout_Model_Session
    */
    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote() {
        return $this->getCheckout()->getQuote();
    }

    public function getOrder() {
        if (empty($this->_order)) {
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
            $this->_order = $order;
        }
        return $this->_order;
    }

     public function toMYR( $amount , $currency_code  ){
         if( $currency_code != "MYR"   ) {
             //if currency code is not allowed currency code, use USD as default
             $storeCurrency = Mage::getSingleton('directory/currency')->load(  $currency_code  );
             $amount = $storeCurrency->convert($amount, 'MYR');
         }   
         return $amount;
     }

     public function MYRtoXXX( $amount , $currency_code  ){
         if( $currency_code != "MYR"   ) {
             $storeCurrency = Mage::getSingleton('directory/currency')->load( $currency_code  );
             // may be that don't have  MYR -> XXX rate 
             // but must have  XXX -> MYR rate
             // so we can get rate by below way 
             $rate = $storeCurrency->getRate( "MYR" );
             $amount = round( ( $amount / $rate), 3 );
         }   
         return $amount;
     }
    
    
    public function getMOLPaySeamlessUrl() {
        return Mage::getUrl( 'molpayseamless/paymentmethod/success', array('_secure' => true));
    }
    
    public function assignData($data)
    {
        $info = $this->getInfoInstance();

        if ($data->getMolpayseamlessPaymentChannel())
        {
          $info->setMolpayseamlessPaymentChannel($data->getMolpayseamlessPaymentChannel());
          Mage::getSingleton('checkout/session')->setSessionPaymentChannel($info->getMolpayseamlessPaymentChannel()); 
        }

        return $this;
    }
    

    public function validate()
    {
//        parent::validate();
//        $info = $this->getInfoInstance();
//        
//        if (!$info->getMolpayseamlessPaymentChannel())
//        {
//          $errorCode = 'invalid_data';
//          $errorMsg = $this->_getHelper()->__("Molpayseamless Payment Channel is a required field.\n");
//        }else{
//            $errorMsg = '';
//            Mage::getSingleton('checkout/session')->setSessionPaymentChannel($info->getMolpayseamlessPaymentChannel()); 
//        }
//
//        if ($errorMsg) 
//        {
//          Mage::throwException($errorMsg);
//        }

        return $this;
    }
    


    public function isOwner_or_Admin( $order_uid ){
        if(  Mage::getSingleton('customer/session')->getId() == $order_uid || Mage::getSingleton('admin/session')->getUser()) {
            return true;
        }
        $this->_redirect('customer/account/login');
        return false;
    }
}  
