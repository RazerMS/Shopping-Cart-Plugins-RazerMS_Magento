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

class Mage_MOLPay_PaymentMethodController extends Mage_Core_Controller_Front_Action {
    //Order instance
    protected $_order;

    /**
     * When a customer chooses MOLPay on Checkout/Payment page
     * 
     */
    public function redirectAction() { 
        //if( !$this->checklogin() ) return ;
        $this->getResponse()->setBody($this->getLayout()->createBlock('molpay/paymentmethod_redirect')->toHtml());
    }
  
    /**
     * When MOLPay return the order information at this point is in POST variables
     * 
     * @return boolean
     */
    public function successAction() {
        if( !$this->getRequest()->isPost() ) {
            $this->_redirect('');
            return;
        }
        $P = $this->getRequest()->getPost();
        $order = Mage::getModel('sales/order')->loadByIncrementId( $P['orderid'] );
        $orderId = $order->getId();
        if(!isset($orderId)){
            Mage::throwException($this->__('Order identifier is not valid!'));
            return false;
        }
        $N = Mage::getModel('molpay/paymentmethod');

        if( !$N->isOwner_or_Admin( $order->getCustomerId()))
            return false;
        
        // test the payment method
        $payment = $order->getPayment();
        if( $payment->getMethod() !== "molpay" ){
            Mage::throwException($this->__('Payment Method is not MOLPay !'));
            return false;
        }
        if( $P['status'] !== '00' ){

            if($P['status'] == '22')
            {
                //print_r("Fail");exit();
                $order->addStatusToHistory(
                $order->getStatus(),
                $this->__('Customer successfully returned from MOLPay. Awaiting Payment from customer.')
                        . "\n<br>Payment Channel: " . $P['channel']
                        . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt
                        . "\n<br>AppCode: " . $P['appcode']
                        . "\n<br>Skey: " . $P['skey']
                        . "\n<br>TransactionID: " . $P['tranID']
                        . "\n<br>Status: " . $P['status']
                        . "\n<br>Date: " . $P['paydate']
                );
                //$order->addStatusToHistory($order->getStatus(), $this->__('Awaiting Payment'));
                //$order->cancel();
                $order->setStatus('Pending');
                $order->save();
                //$this->cancelAction();
                //$this->_redirect('*/*/failure');
                //$this->_redirect('customer/account/');
                //Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure'=>true));
            }
            else
            {
                //print_r("Fail");exit();
                $order->addStatusToHistory($order->getStatus(), $this->__('Payment Fail'));
                $order->cancel();
                $order->setStatus('canceled');
                $order->save();
                //$this->cancelAction();
                //$this->_redirect('*/*/failure');
                $this->_redirect('customer/account/');
                //Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure'=>true));
            }
            return;
        }

        if( $P['status'] === '00' && $this->_matchkey( $N->getConfigData('encrytype') , $N->getConfigData('login') , $N->getConfigData('transkey'), $P )) {
            $etcAmt = '';
            $currency_code = $order->getOrderCurrencyCode();
            if( $currency_code !=="MYR" ) {
                $amount = $N->MYRtoXXX( $P['amount'] ,  $currency_code );
                //print_r("<h1>MYR $P[amount] to $currency_code $amount </h1>");
                $etcAmt = "  <b>( $currency_code $amount )</b>";
                if( $order->getBaseGrandTotal() > $amount ) {
                    //print_r( "Amount order is not valid!" );
                    $order->addStatusToHistory( $order->getStatus(), "Amount order is not valid!" );
                }
            } 
            $order->addStatusToHistory(
                $order->getStatus(),
                $this->__('Customer successfully returned from MOLPay')
                        . "\n<br>Payment Channel: " . $P['channel']
                        . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt
                        . "\n<br>AppCode: " . $P['appcode']
                        . "\n<br>Skey: " . $P['skey']
                        . "\n<br>TransactionID: " . $P['tranID']
                        . "\n<br>Status: " . $P['status']
                        . "\n<br>PaidDate: " . $P['paydate']
            );

            $order->getPayment()->setTransactionId( $P['tranID'] );
            // $order->getPayment()->setAmountCharged(9);
            // generate the invoice
            if ( !$this->_createInvoice($order,$N)  ) {
                $order->addStatusToHistory($order->getStatus(), $this->__('Cann\'t create invoice'));
                //$this->_redirect('*/*/failure');
                $order->save();
                // print_r("can't create invoice");exit();
                $this->_redirect('customer/account/');
                return;
            }
            $order->setState(
                Mage_Sales_Model_Order::STATE_PROCESSING,
                Mage_Sales_Model_Order::STATE_PROCESSING,
                'Payment Success' . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt . "\n<br>PaidDate: " . $P['paydate'],
                $notified = true
            );
            $order->save();
            $order->sendNewOrderEmail();
            //print_r("<hr>Pass");
            $this->_redirect('checkout/onepage/success' , array('_secure'=>true) );
            return;
        }
        else {
            //print("Key Fail");
            //exit();
            $order->addStatusToHistory($order->getStatus(), $this->__('Payment Error: Signature key not match'));
            $order->save();
            // $this->_redirect('*/*/failure');
            $this->_redirect('customer/account/');
            return;
        }
    }
  
    public function callbackAction() { 
        $P = $_REQUEST;

        if($P['nbcb'] == 1) {
            $order = Mage::getModel('sales/order')->loadByIncrementId( $P['orderid'] );
            $orderId = $order->getId();
            if(!isset($orderId)){
                Mage::throwException($this->__('Order identifier is not valid!'));
                return false;
            }
            $N = Mage::getModel('molpay/paymentmethod');

            // test the payment method
            $payment = $order->getPayment();

            if( $payment->getMethod() !=="molpay" ){
                Mage::throwException($this->__('Payment Method is not MOLPay !'));
                return false;              	
            }
            if( $P['status'] !=='00'  ){
                if($P['status'] == '22')
                    {
                        $order->addStatusToHistory($order->getStatus(), $this->__('Awaiting Payment'));
                                $order->setState(
                                    Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW,
                                    Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW,
                                    'Awaiting Payment' . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt . "\n<br>PaidDate: " . $P['paydate'],
                                    $notified = true );
                        $order->save();
                    }
                    else
                    {
                        $order->addStatusToHistory($order->getStatus(), $this->__('Payment Fail'));
                                $order->setState(
                                    Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW,
                                    Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW,
                                    'Payment Fail' . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt . "\n<br>PaidDate: " . $P['paydate'],
                                    $notified = true );
                        $order->save();
                    }
                return;
            }

            if( $P['status'] === '00' && $this->_matchkey( $N->getConfigData('encrytype') , $N->getConfigData('login') , $N->getConfigData('transkey'), $P )) {
                $etcAmt='';
                $currency_code = $order->getOrderCurrencyCode();
                if( $currency_code !=="MYR" ){
                    $amount= $N->MYRtoXXX( $P['amount'] ,  $currency_code );
                    $etcAmt = "  <b>( $currency_code $amount )</b>";
                    if( $order->getBaseGrandTotal() > $amount ) {
                        $order->addStatusToHistory($order->getStatus(), "Amount order is not valid!");
                    }
                } 

                $order->addStatusToHistory(
                        $order->getStatus(),
                        $this->__('Customer successfully returned from MOLPay')
                        . "\n<br>Payment Channel: " .$P['channel']
                        . "\n<br>Amount: ".$P['currency']." ".$P['amount'].$etcAmt
                        . "\n<br>AppCode: " .$P['appcode']
                        . "\n<br>Skey: " . $P['skey']
                        . "\n<br>TransactionID: " . $P['tranID']
                        . "\n<br>Status: " . $P['status']
                        . "\n<br>PaidDate: " . $P['paydate'] );

                $order->getPayment()->setTransactionId( $P['tranID'] );			   

                if ( !$this->_createInvoice($order,$N)  ) {
                    $order->addStatusToHistory($order->getStatus(), $this->__('Can\'t create invoice'));
                    $order->save();
                    return ;
                }

                $order->setState(
                    Mage_Sales_Model_Order::STATE_PROCESSING,
                    Mage_Sales_Model_Order::STATE_PROCESSING,
                    'Payment Success' . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt . "\n<br>PaidDate: " . $P['paydate'],
                    $notified = true );
                $order->save();
                $order->sendNewOrderEmail();  
                return;

            }
            else {
                $order->addStatusToHistory($order->getStatus(), $this->__('Payment Error: Signature key not match'));
                $order->setState(
                        Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW,
                        Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW,
                        'Payment Error: Signature key not match' . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt . "\n<br>PaidDate: " . $P['paydate'],
                        $notified = true );
                $order->save();
                return;
            }
        }
    }
  
    protected function _matchkey( $entype, $merchantID , $vkey , $P ) {
        $enf = ( $entype == "sha1" )? "sha1" : "md5";    	    
        $skey = $enf( $P['tranID'].$P['orderid'].$P['status'].$merchantID.$P['amount'].$P['currency'] );
        $skey = $enf( $P['paydate'].$merchantID.$skey.$P['appcode'].$vkey   );
        return ( $skey === $P['skey'] )? 1 : 0;
    }
  
    /**
     * Creating Invoice
     * 
     * @param Mage_Sales_Model_Order $order
     * @return Boolean
     */
    protected function _createInvoice(Mage_Sales_Model_Order $order,$N) {
        if( $order->canInvoice() && ($order->hasInvoices() < 1));
        else 
            return false;
        //---------------------------------------------
        // convert order into invoice
        //---------------------------------------------
        // print_r( "INVOCE ".$newOrderStatus );           
        //need to convert from order into invoice
        $invoice = $order->prepareInvoice();
        $invoice->register()->capture();
        Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();

        $newOrderStatus = $N->getConfigData('order_status', $order->getStoreId());
        if( empty($newOrderStatus) )
          $newOrderStatus = $order->getStatus();

        $order->setState( Mage_Sales_Model_Order::STATE_PROCESSING, $newOrderStatus, $this->__('Invoice #%s created', $invoice->getIncrementId()), true );
        return true;               
    }
  
    public function failureAction() {    	
        $this->loadLayout();
        $this->renderLayout();
    }
  
    public function checklogin() {
        $U = Mage::getSingleton('customer/session');
        if( !$U->isLoggedIn() ) {
            $this->_redirect('customer/account/login');
            return false;
        }		
        return true;
    }
    
    public function payAction() {
        //if( !$this->checklogin() ) return ;
        $this->getResponse()->setBody( $this->getLayout()->createBlock('molpay/paymentmethod_redirect')->toHtml() );
    }
}