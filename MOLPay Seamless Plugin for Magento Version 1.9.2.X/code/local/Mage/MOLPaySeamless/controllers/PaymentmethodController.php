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

class Mage_MOLPaySeamless_PaymentMethodController extends Mage_Core_Controller_Front_Action {
    //Order instance
    protected $_order;

    
    /**
     * FrontEnd Location: Onepage Checkout/Order Review Tab
     * Function: To create order before redirecting customer to payment gateway
    */
    public function createOrderAction(){
        try {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $quote->collectTotals()->getPayment()->getMethod();
            $service = Mage::getModel('sales/service_quote', $quote);
            $service->submitAll();
            $order = $service->getOrder();
            $order->save();
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
        }
        
        try {
            $pm = Mage::getModel('molpayseamless/paymentmethod');

            $quote = $pm->getQuote();
            $quoteid = $quote->getId();
            if(!isset($quoteid)){
                Mage::throwException($this->__('Quote identifier is not valid!'));
                return false;
            }

            $address = $quote->getBillingAddress();
            $shippingaddress = $quote->getShippingAddress();

            //getQuoteCurrencyCode
            $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
            $amount = $quote->getGrandTotal();
            
            /* 
            * No need to convert to MYR as we already accept multicurrency
            */
            //$currency_code = $quote->getBaseCurrencyCode();
            //$amount = $quote->getBaseGrandTotal();
            //$amount = $pm->toMYR(  $amount ,  $currency_code ); 
            $amount = number_format( round(  $amount, 2 ) , 2, '.', '');

            $email = $address->getEmail(); 
            if( $email == '' ) {
                $email = $quote->getCustomerEmail();
            }
            

            $sArr = array(
				'status' => true,
                'mpsmerchantid' => $pm->getConfigData('login'),
                'mpschannel' => $pm->getCheckout()->getSessionPaymentChannel(),
                'mpsreturnurl' => Mage::getUrl( 'molpayseamless/paymentmethod/success', array('_secure' => true)),
                'mpsorderid' => $quote->getReservedOrderId(),
                'mpsamount' => $amount ,
                'mpscurrency' => $currency_code,
                'mpsbill_name' => $address->getFirstname() . ' ' . $address->getLastname(),
                'mpsbill_email' => $email,
                'mpsbill_mobile' => $address->getTelephone(),
                'mpsvcode' => '',
                'mpsbill_desc' => "\n-- Order Detail --"
            );

            $ven = $pm->getConfigData('encrytype');
            $vk = $sArr['mpsamount'] . $pm->getConfigData('login') . $quote->getReservedOrderId() . $pm->getConfigData('transkey'); 

            $sArr['mpsvcode'] = ( $ven =="sha1" )? sha1( $vk ) : md5( $vk );

            $items = $quote->getAllItems();
            if ($items) { 
                $i = 1;
                foreach($items as $item) {
                    if ($item->getParentItem()) {
                        continue;          
                    }
                    $sArr['mpsbill_desc'] .= "\n$i. Name: ".$item->getName() . '  Sku: '.$item->getSku() . ' Qty: ' . $item->getQty() * 1;
                    $i++;
                }   
            }
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
        }
        
        $jsonData = json_encode($sArr);  
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody($jsonData);
    }
    
    /**
    * When MOLPaySeamless return the order information at this point is in POST variables
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
        $etcAmt = '';

        $order = Mage::getModel('sales/order')->loadByIncrementId( $P['orderid'] );
        $orderId = $order->getId();
        $N = Mage::getModel('molpayseamless/paymentmethod');
        $core_session = Mage::getSingleton('core/session');
        
        if(!isset($orderId)){
            $this->_redirect('checkout/cart');
			return;
        }else if( $order->getPayment()->getMethod() !=="molpayseamless" ) {
            $this->_redirect('checkout/cart');
			return;                
        }else if(ucfirst($order_status)=="Processing"){ 
			$this->_redirect('checkout/onepage/success');
            return;
		}else if(ucfirst($order_status)=="Canceled"){
			$this->_redirect('checkout/cart'); 
            return;
		}else{
            if( $P['status'] !== '00' ) {
                if($P['status'] == '22') {
                    $this->updateOrderStatus($order, $P, $etcAmt, $TypeOfReturn, "PENDING");
                    $order->save();
                    $this->_redirect('checkout/onepage/success');
                } else {
                    $this->updateOrderStatus($order, $P, $etcAmt, $TypeOfReturn, "FAILED");
                    $order->save();
					
					Mage::getSingleton('core/session')->getMessages(true);
                    $core_session->addError('Payment Failed. Please proceed with checkout to try again.');
                    Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
                }
                return;
            }else if( $P['status'] === '00' && $this->_matchkey( $N->getConfigData('encrytype') , $N->getConfigData('login') , $N->getConfigData('transkey'), $P )) {

                $order->getPayment()->setTransactionId( $P['tranID'] );
                try{
                    if($this->_createInvoice($order,$N,$P,$TypeOfReturn)) {
                        $order->sendNewOrderEmail();
                    }
                }catch (Mage_Core_Exception $e){
                    Mage::logException($e);
                }
            
                $order->save();

                $session = Mage::getSingleton('checkout/session');

                $quoteid = $N->getQuote()->getId();
                $session->setLastSuccessQuoteId($quoteid);
                $session->setLastQuoteId($quoteid);
                $session->setLastOrderId($orderId);

                foreach( $session->getQuote()->getItemsCollection() as $item ){
                    Mage::getSingleton('checkout/cart')->removeItem( $item->getId() )->save();
                }

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
    }
    
    
    public function notificationAction() {
        $P = $_REQUEST;
        echo "CBTOKEN:MPSTATOK";
        $TypeOfReturn = "NotificationURL";
        $etcAmt = '';

        if($P['nbcb'] == 2) {
            $order = Mage::getModel('sales/order')->loadByIncrementId( $P['orderid'] );
            $orderId = $order->getId();
            $N = Mage::getModel('molpayseamless/paymentmethod');
            
            if(!isset($orderId)){
                Mage::throwException($this->__('Order identifier is not valid!'));
                return false;
            }
            
            if( $order->getPayment()->getMethod() !=="molpayseamless" ) {
                Mage::throwException($this->__('Payment Method is not MOLPaySeamless !'));
                return false;               
            }

            if( $P['status'] !== '00' ) {
                if($P['status'] == '22') {
                    $this->updateOrderStatus($order, $P, $etcAmt, $TypeOfReturn, "PENDING");
                    $order->save();
                } else {
                    $this->updateOrderStatus($order, $P, $etcAmt, $TypeOfReturn, "FAILED");
                    $order->save();
                }
                return;
            } else if( $P['status'] === '00' && $this->_matchkey( $N->getConfigData('encrytype') , $N->getConfigData('login') , $N->getConfigData('transkey'), $P )) {

                $order->getPayment()->setTransactionId( $P['tranID'] );
                try{
                    if($this->_createInvoice($order,$N,$P,$TypeOfReturn)) {
                        $order->sendNewOrderEmail();
                    }
                }catch (Mage_Core_Exception $e){
                    Mage::logException($e);
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
        
        $this->loadLayout(); 
        $this->renderLayout();
    }
  
    public function callbackAction() { 
        $P = $_REQUEST;
        echo "CBTOKEN:MPSTATOK";
        $TypeOfReturn = "CallbackURL";
        $etcAmt = '';
        
        if($P['nbcb'] == 1) {
            $order = Mage::getModel('sales/order')->loadByIncrementId( $P['orderid'] );
            $orderId = $order->getId();
            if(!isset($orderId)){
                Mage::throwException($this->__('Order identifier is not valid!'));
                return false;
            }
            $N = Mage::getModel('molpayseamless/paymentmethod');

            if( $order->getPayment()->getMethod() !=="molpayseamless" ) {
                Mage::throwException($this->__('Payment Method is not MOLPaySeamless !'));
                return false;               
            }

            if( $P['status'] !== '00' ) {
                if($P['status'] == '22') {
                    $this->updateOrderStatus($order, $P, $etcAmt, $TypeOfReturn, "PENDING");
                    $order->save();
                } else {
                    $this->updateOrderStatus($order, $P, $etcAmt, $TypeOfReturn, "FAILED");
                    $order->save();
                }
                return;
            } else if( $P['status'] === '00' && $this->_matchkey( $N->getConfigData('encrytype') , $N->getConfigData('login') , $N->getConfigData('transkey'), $P )) {
                
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
        
        $this->loadLayout(); 
        $this->renderLayout();
    }
    
    public function failureAction() {       
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function payAction() {
        $this->getResponse()->setBody( $this->getLayout()->createBlock('molpayseamless/paymentmethod_redirect')->toHtml() );
    }
    
    
    
    
    
    
    /* Function -------------------------------------------------------------------------------------------------------- */
    protected function _matchkey( $entype, $merchantID , $vkey , $P ) {
        $enf = ( $entype == "sha1" )? "sha1" : "md5";           
        $skey = $enf( $P['tranID'].$P['orderid'].$P['status'].$merchantID.$P['amount'].$P['currency'] );
        $skey = $enf( $P['paydate'].$merchantID.$skey.$P['appcode'].$vkey   );
        return ( $skey === $P['skey'] )? 1 : 0;
    }
  
    // Creating Invoice : Convert order into invoice
    protected function _createInvoice(Mage_Sales_Model_Order $order,$N,$P,$TypeOfReturn) {
        if( $order->canInvoice() && ($order->hasInvoices() < 1));
            else 
        return false;
        
        $invoice =  Mage::getModel('sales/service_order', $order)->prepareInvoice();
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
        $invoice->register();
        Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();

        
        $order->setState(
            Mage_Sales_Model_Order::STATE_PROCESSING,
            Mage_Sales_Model_Order::STATE_PROCESSING,
                "Response from MOLPAY - ".$TypeOfReturn." (CAPTURED)"
                . "\n<br>Invoice #".$invoice->getIncrementId().""
                . "\n<br>Amount: ".$P['currency']." ".$P['amount']
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
    
    // Send acknowlodge to MOLPay server
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
    
    // Update order status 
    public function updateOrderStatus($order, $P, $etcAmt, $TypeOfReturn, $status){
        
        if($status == "PENDING"){
            $status_update = Mage_Sales_Model_Order::STATE_NEW;
        }elseif($status == "FAILED"){
            $status_update = Mage_Sales_Model_Order::STATE_CANCELED;
        }else{
            $status_update = "";
        }
        
        $order->setState(
                $status_update,
                $status_update,
                'Customer Redirect from MOLPAY - ' .$TypeOfReturn. ' (' .$status. ')' . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt . "\n<br>PaidDate: " . $P['paydate'],
                $notified = true ); 
        return;
    }
  
    public function checklogin() {
        $U = Mage::getSingleton('customer/session');
        if( !$U->isLoggedIn() ) {
            $this->_redirect('customer/account/login');
            return false;
        }       
        return true;
    }  
    
}
