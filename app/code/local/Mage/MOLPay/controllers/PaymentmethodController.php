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
        $this->_ack($P);
        $TypeOfReturn = "ReturnURL";

        $order = Mage::getModel('sales/order')->loadByIncrementId( $P['orderid'] );
        $orderId = $order->getId();
        if(!isset($orderId)){
            Mage::throwException($this->__('Order identifier is not valid!'));
            return false;
        }
        $N = Mage::getModel('molpay/paymentmethod');
        
        if( $order->getPayment()->getMethod() !=="molpay" ) {
            Mage::throwException($this->__('Payment Method is not MOLPay !'));
            return false;               
        }

        if( $P['status'] !== '00' ) {
            if($P['status'] == '22') {
                $order->setState(
                    Mage_Sales_Model_Order::STATE_NEW,
                    Mage_Sales_Model_Order::STATE_NEW,
                    'Customer Redirect from MOLPAY - ReturnURL (PENDING)' . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt . "\n<br>PaidDate: " . $P['paydate'],
                    $notified = true );
                $order->save();
                $this->_redirect('checkout/onepage/success');
            } else {
                if($order->canCancel()) {
                    foreach($order->getAllItems() as $item){
                        $item->cancel();
                        $item->save();
                    }
                }
                $order->setState(
                    Mage_Sales_Model_Order::STATE_CANCELED,
                    Mage_Sales_Model_Order::STATE_CANCELED,
                    'Customer Redirect from MOLPAY - ReturnURL (FAILED)' . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt . "\n<br>PaidDate: " . $P['paydate'],
                    $notified = true );
                $order->save();
                $this->_redirect('checkout/cart');
            }
            return;
        }

        if( $P['status'] === '00' && $this->_matchkey( $N->getConfigData('encrytype') , $N->getConfigData('login') , $N->getConfigData('transkey'), $P )) {
            $etcAmt = '';
            $currency_code = $order->getOrderCurrencyCode();
            if( $currency_code !=="MYR" ) {
                $amount = $N->MYRtoXXX( $P['amount'] ,  $currency_code );
                $etcAmt = "  <b>( $currency_code $amount )</b>";
                if( $order->getBaseGrandTotal() > $amount ) {
                    $order->addStatusToHistory( $order->getStatus(), "Amount order is not valid!" );
                }
            }

            $order->getPayment()->setTransactionId( $P['tranID'] );

            if($this->_createInvoice($order,$N,$P,$TypeOfReturn)) {
                $order->sendNewOrderEmail();
            }
            
            $order->save();
            $this->_redirect('checkout/onepage/success');
            return;

        } else {
            $order->setState(
                Mage_Sales_Model_Order::STATUS_FRAUD,
                Mage_Sales_Model_Order::STATUS_FRAUD,
                'Payment Error: Signature key not match' . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt . "\n<br>PaidDate: " . $P['paydate'],
                $notified = true
            );
            $order->save();
            $this->_redirect('checkout/cart');
            return;
        }
    }
    
    public function notificationAction() {
        $P = $_REQUEST;
        $this->_ack($_REQUEST);
        $TypeOfReturn = "NotificationURL";

        if($P['nbcb'] == 2) {
            $order = Mage::getModel('sales/order')->loadByIncrementId( $P['orderid'] );
            $orderId = $order->getId();
            if(!isset($orderId)){
                Mage::throwException($this->__('Order identifier is not valid!'));
                return false;
            }
            $N = Mage::getModel('molpay/paymentmethod');

            if( $order->getPayment()->getMethod() !=="molpay" ) {
                Mage::throwException($this->__('Payment Method is not MOLPay !'));
                return false;               
            }

            if( $P['status'] !== '00' ) {
                if($P['status'] == '22') {
                    $order->setState(
                        Mage_Sales_Model_Order::STATE_NEW,
                        Mage_Sales_Model_Order::STATE_NEW,
                        'Customer Redirect from MOLPAY - Notification (PENDING)' . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt . "\n<br>PaidDate: " . $P['paydate'],
                        $notified = true
                    );
                    $order->save();
                } else {
                    if($order->canCancel()) {
                        foreach($order->getAllItems() as $item){
                            $item->cancel();
                            $item->save();
                        }
                    }
                    $order->setState(
                        Mage_Sales_Model_Order::STATE_CANCELED,
                        Mage_Sales_Model_Order::STATE_CANCELED,
                        'Customer Redirect from MOLPAY - ReturnURL (FAILED)' . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt . "\n<br>PaidDate: " . $P['paydate'],
                        $notified = true
                    );
                    $order->save();
                }
                return;
            }

            if( $P['status'] === '00' && $this->_matchkey( $N->getConfigData('encrytype') , $N->getConfigData('login') , $N->getConfigData('transkey'), $P )) {
                $etcAmt='';
                $currency_code = $order->getOrderCurrencyCode();
                if($currency_code !=="MYR") {
                    $amount= $N->MYRtoXXX( $P['amount'] ,  $currency_code );
                    $etcAmt = "  <b>( $currency_code $amount )</b>";
                    if( $order->getBaseGrandTotal() > $amount ) {
                        $order->addStatusToHistory($order->getStatus(), "Amount order is not valid!");
                    }
                }

                $order->getPayment()->setTransactionId( $P['tranID'] );   

                if($this->_createInvoice($order,$N,$P,$TypeOfReturn)) {
                    $order->sendNewOrderEmail();
                }
                
                $order->save();
                return;
            } else {
                $order->setState(
                    Mage_Sales_Model_Order::STATUS_FRAUD,
                    Mage_Sales_Model_Order::STATUS_FRAUD,
                    'Payment Error: Signature key not match' . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt . "\n<br>PaidDate: " . $P['paydate'],
                    $notified = true
                );
                $order->save();
                return;
            }
        }
    }
  
    public function callbackAction() { 
        $P = $_REQUEST;
        $this->_ack($_REQUEST);
        $TypeOfReturn = "CallbackURL";
        
        if($P['nbcb'] == 1) {
            $order = Mage::getModel('sales/order')->loadByIncrementId( $P['orderid'] );
            $orderId = $order->getId();
            if(!isset($orderId)){
                Mage::throwException($this->__('Order identifier is not valid!'));
                return false;
            }
            $N = Mage::getModel('molpay/paymentmethod');

            if( $order->getPayment()->getMethod() !=="molpay" ) {
                Mage::throwException($this->__('Payment Method is not MOLPay !'));
                return false;               
            }

       if( $P['status'] !== '00' ) {
            if($P['status'] == '22') {
                $order->setState(
                    Mage_Sales_Model_Order::STATE_NEW,
                    Mage_Sales_Model_Order::STATE_NEW,
                    'Customer Redirect from MOLPAY - CallbackURL (PENDING)' . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt . "\n<br>PaidDate: " . $P['paydate'],
                    $notified = true );
                $order->save();
            } else {
                if($order->canCancel()) {
                    foreach($order->getAllItems() as $item){
                        $item->cancel();
                        $item->save();
                    }
                }
                $order->setState(
                    Mage_Sales_Model_Order::STATE_CANCELED,
                    Mage_Sales_Model_Order::STATE_CANCELED,
                    'Customer Redirect from MOLPAY - CallbackURL (FAILED)' . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt . "\n<br>PaidDate: " . $P['paydate'],
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

                $order->getPayment()->setTransactionId( $P['tranID'] );            

                if($this->_createInvoice($order,$N,$P,$TypeOfReturn)) {
                    $order->sendNewOrderEmail();
                }

                $order->save();
                return;

            } else {
                $order->setState(
                        Mage_Sales_Model_Order::STATUS_FRAUD,
                        Mage_Sales_Model_Order::STATUS_FRAUD,
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
    protected function _createInvoice(Mage_Sales_Model_Order $order,$N,$P,$TypeOfReturn) {
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

        
        $order->setState(
            Mage_Sales_Model_Order::STATE_PROCESSING,
            Mage_Sales_Model_Order::STATE_PROCESSING,
                "Response from MOLPAY - ".$TypeOfReturn." (CAPTURED)"
                . "\n<br>Invoice #".$invoice->getIncrementId().""
                . "\n<br>Amount: ".$P['currency']." ".$P['amount'].$etcAmt
                . "\n<br>AppCode: " .$P['appcode']
                . "\n<br>Skey: " . $P['skey']
                . "\n<br>TransactionID: " . $P['tranID']
                . "\n<br>Status: " . $P['status']
                . "\n<br>PaidDate: " . $P['paydate']
                ,
                true
        );
        return true;               
    }

    public function _ack($P) {
        $P['treq'] = 1;
        while ( list($k,$v) = each($P) ) {
          $postData[]= $k."=".$v;
        }
        $postdata   = implode("&",$postData);
        $url        = "https://www.onlinepayment.com.my/MOLPay/API/chkstat/returnipn.php";
        $ch         = curl_init();
        curl_setopt($ch, CURLOPT_POST           , 1     );
        curl_setopt($ch, CURLOPT_POSTFIELDS     , $postdata );
        curl_setopt($ch, CURLOPT_URL            , $url );
        curl_setopt($ch, CURLOPT_HEADER         , 1  );
        curl_setopt($ch, CURLINFO_HEADER_OUT    , TRUE   );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1  );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , FALSE);
        $result = curl_exec( $ch );
        curl_close( $ch );
        return;
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
        $this->getResponse()->setBody( $this->getLayout()->createBlock('molpay/paymentmethod_redirect')->toHtml() );
    }
}
