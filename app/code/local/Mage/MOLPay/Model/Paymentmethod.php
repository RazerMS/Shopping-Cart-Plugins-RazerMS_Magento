<?php
/**
 * MOLPay Sdn. Bhd.
 *
 * @package     MOLPay Magento Plugin
 * @author      netbuilder <code@netbuilder.com.my>
 * @copyright   Copyright (c) 2012 - 2014, MOLPay
 * @link        http://molpay.com
 * @since       Version 1.8.x.x
 * @update      MOLPay <technical@molpay.com>
 * @filesource  https://github.com/MOLPay/Magento_Plugin
 */

class Mage_MOLPay_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract {
  
    protected $_code  = 'molpay';
    protected $_formBlockType = 'molpay/paymentmethod_form';

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
    protected $_allowCurrencyCode = array('MYR');


    public function isInitializeNeeded() {
        return true;
    }

    public function initialize($paymentAction, $stateObject) {
        $state = Mage_Sales_Model_Order::STATE_NEW;
        $stateObject->setState( $state );
        $stateObject->setStatus( Mage::getSingleton('sales/order_config')->getStateDefaultStatus( $state ) );
        $stateObject->setIsNotified( false );
    }

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('molpay/paymentmethod/redirect', array('_secure' => true));
    }

    public function getPaymentmethodCheckoutFormFields( $orderid=0 ) {
        if( !$orderid  ){
            $orderid=$this->getCheckout()->getLastRealOrderId();      	
        }
        $order = Mage::getModel('sales/order')->loadByIncrementId( $orderid );
        $orderId = $order->getId();
        if(!isset($orderId)){
            Mage::throwException($this->__('Order identifier is not valid!'));
            return false;
        }
        //if( !$this->isOwner_or_Admin( $order->getCustomerId()  )   )  return false;

        $address = $order->getBillingAddress();
        $shippingaddress = $order->getShippingAddress();

        //getQuoteCurrencyCode
        $currency_code = $order->getBaseCurrencyCode();
        $amount = $order->getBaseGrandTotal();
        $amount = $this->toMYR(  $amount ,  $currency_code );
        $amount = number_format( round(  $amount, 2 ) , 2, '.', '');

        $email = $address->getEmail(); 
        if( $email == '' ) {
            $email = $order->getCustomerEmail();
        }

        $sArr = array(
            'returnurl' => Mage::getUrl( 'molpay/paymentmethod/success', array('_secure' => true)),
            'orderid' => $orderid, // $this->getOrder()->getRealOrderId()
            'amount' => $amount ,
            'currency_code' => "MYR",
            'bill_name' => $address->getFirstname() . ' ' . $address->getLastname(),
            'bill_email' => $email,
            'bill_mobile' => $address->getTelephone(),
            'vcode' => '',
            'bill_desc' => "\n-- Order Detail --"
        );

        $ven = $this->getConfigData('encrytype');
        $vk = $sArr['amount'] . $this->getConfigData('login') . $sArr['orderid'] . $this->getConfigData('transkey'); 

        //print_r("<li>".$vk);exit();
        $sArr['vcode'] = ( $ven =="sha1" )? sha1( $vk ) : md5( $vk );

        // $items = $this->getQuote()->getAllItems();
        $items = $order->getAllItems();
        if ($items) {
            $i = 1;
            foreach($items as $item) {
                if ($item->getParentItem()) {
                    continue;          
                }
                $sArr['bill_desc'] .= "\n$i. Name: ".$item->getName() . '  Sku: '.$item->getSku() . ' Qty: ' . $item->getQtyOrdered() * 1;
                $i++;
            }   
        }

        $foundit=0;
        foreach ($order->getAllStatusHistory() as $_history) {
            if(  strpos($_history->getComment(), "/paymentmethod/pay/") !== false   ){$foundit=1;break;}
        }
        if( !$foundit ) {
            /* quick fix only */
            /* update notification by farid 13th oct 2014 */
            $url = Mage::getUrl('sales/order/reorder/', array("order_id" => $order->getId() ));
            //$url = Mage::getUrl( "*/*/pay",  array("order_id" => $order->getRealOrderId() )  );
            $order->addStatusToHistory(
                      $order->getStatus(),
                      //quick fix for temporary only
                      //"If you not complete payment yet, please <a href='$url' >Click here to pay (MOLPay Malaysia Online Payment)</a> .",
                      "If the customer has not complete a payment yet, please provide the customer the following link to use the following link :\m '$url'  .",
                      true );
            $order->save(); 
        }
        return $sArr;
    }

    public function getMOLPayUrl() {
        return 'https://www.onlinepayment.com.my/MOLPay/pay/'.$this->getConfigData('login')."/";
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

    public function isOwner_or_Admin( $order_uid ){
        if(  Mage::getSingleton('customer/session')->getId() == $order_uid || Mage::getSingleton('admin/session')->getUser()) {
            return true;
        }
        $this->_redirect('customer/account/login');
        return false;
    }
}  