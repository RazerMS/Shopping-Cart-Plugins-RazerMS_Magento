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


        $P['payment']['molpayseamless_payment_channel'] = $this->getRequest()->getPost('molpayseamless_payment_channel');

        $error = false;
        $error_msg = "";
        $is_order_success = false;

        $pm = Mage::getModel('molpayseamless/paymentmethod');

        $paymentMethod = 'molpayseamless';
        Mage::getSingleton('checkout/type_onepage')->savePayment( array('method' => $paymentMethod) );
        $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
        $quoteid = $quote->getId();
        $quote->collectTotals()->reserveOrderId()->save();

        $address = $quote->getBillingAddress();

        //getQuoteCurrencyCode
        $currency_code = $quote->getQuoteCurrencyCode();
        $amount = $quote->getGrandTotal();

        $amount = number_format( round(  $amount, 2 ) , 2, '.', '');

        $email = $address->getEmail();
        if( $email == '' ) {
            $email = $quote->getCustomerEmail();
        }

        // Exception Handler -----------------------------------------------------------------------
        if(!isset($quoteid)){
            $error = true;
            $error_msg = "Quote identifier is not valid!";
        }else if($email == ''){
            $error = true;
            $error_msg = "E-mail is empty.";
        }else if($quote->getReservedOrderId() == ''){
            $error = true;
            $error_msg = "Can't create order. ";
        }else if($pm->getConfigData('login') == ''){
            $error = true;
            $error_msg = "Merchant ID is empty.";
        }else if($P['payment']['molpayseamless_payment_channel'] == ''){
            $error = true;
            $error_msg = "Payment channel is empty. ";
        }else if($amount == ''){
            $error = true;
            $error_msg = "Amount is empty.";
        }else if($address->getFirstname() == ''){
            $error = true;
            $error_msg = "Firstname or Lastname is empty.";
        }else if($address->getTelephone() == ''){
            $error = true;
            $error_msg = "Telephone is empty.";
        }else{
            $sArr = array(
                'status' => true,
                'mpsmerchantid' => $pm->getConfigData('login'),
                'mpschannel' => $P['payment']['molpayseamless_payment_channel'],
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

            //Order ID created in Sales_Servie_Quote
            $quote_pre = Mage::getModel('sales/quote')->load($quote->getReservedOrderId(), 'reserved_order_id');
            $quote_pre->setTotalsCollectedFlag(true);

            $service = Mage::getModel('sales/service_quote', $quote);
            $service->submitAll();

        }

        if($error){

            if($is_order_success){
                $this->updateOrderStatus($order, $P, $etcAmt, $TypeOfReturn, "FAILED");
                $order->save();

                $core_session = Mage::getSingleton('core/session');
                Mage::getSingleton('core/session')->getMessages(true);
                $core_session->addError('Payment Failed. Please proceed with checkout to try again.');
            }

            $sArr = array(
                'status'          => false,      // Set False to show an error message.
                'error_code'      => 'Error:',
                'error_desc'      => $error_msg,
                'failureurl'      => Mage::getUrl('checkout/cart')
            );
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

        $P = $this->getRequest()->getPost();

        $this->_ack($P);
        $TypeOfReturn = "ReturnURL";
        $etcAmt = '';

        $N = Mage::getModel('molpayseamless/paymentmethod');
        $core_session = Mage::getSingleton('core/session');

        Mage::log('Input bellow', null, 'molpay.log');
        Mage::log($P, null, 'molpay.log');

        $order = Mage::getModel('sales/order')->loadByIncrementId( $P['orderid'] );
        $orderId = $order->getId();
        $order_status = $order->getStatus();

        $orderId = $P['orderid'];

        if( $P['status'] !== '00' ) {
            if($P['status'] == '22') {
                $this->updateOrderStatus($order, $P, $etcAmt, $TypeOfReturn, "PENDING");
                $order->save();

                $session = Mage::getSingleton('checkout/session');

                $quoteid = $N->getQuote()->getId();
                $session->setLastSuccessQuoteId($quoteid);
                $session->setLastQuoteId($quoteid);
                $session->setLastOrderId($orderId);

                $this->_redirect('checkout/onepage/success');
            }
            if($P['status'] == '11') {
                /*User Case: Buyer redirect to merchant website (return URL)*/
                /*
                *   Case 1: After buyer cancal payment at online banking, MOLPay get direct result of payment from Bank, and will
                *   send status 11 to merchant website(Normal Case)
                *
                *   Case 2: Buyer successfully makes payment on onlinebanking, but bank has late to return the result 
                *   of payment to MOLPay and at the same time MOLPay has to bring buyer redirect 
                *   to merchant website with status 11 ( this status does not mean actual failed payment )
                *   - In buyer view, buyer will see page as "Order has been placed"
                *   - MOLPay will send lastest status of payment to merchant website through callbakc URL.
                *
                *   To identify this is actual failed payment or pending status from Bank site, solution is below:
                *   Added function query API to MOLPay to get actual status of transaction at MOLPay site.
                *   query resulting :
                *   1- StatCode = 11, means transaction is absolutely failed payment at bank
                *   2- StatCode = 22, means transaction is pending and waiting for latest result from bank
                */

                //Query status at MOLPay thru API
                $qtxn = $this->queryStatusTransaction($P);

                if( !empty($qtxn) ){
                    if( !empty($qtxn) && $qtxn['StatCode'] === "11") {

                        Mage::getSingleton('core/session')->getMessages(true);
                        $core_session->addError('Payment Failed. Please proceed with checkout to try again.');
                        Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));

                    }
                    elseif(!empty($qtxn) && $qtxn['StatCode'] === "22") {

                        $this->updateOrderStatus($order, $P, $etcAmt, $TypeOfReturn, "PENDING");
                        $order->save();

                        $session = Mage::getSingleton('checkout/session');

                        $quoteid = $N->getQuote()->getId();
                        $session->setLastSuccessQuoteId($quoteid);
                        $session->setLastQuoteId($quoteid);
                        $session->setLastOrderId($P['orderid']);

                        $this->_redirect('checkout/onepage/success');
                    }
                }

            }
            return;

        }else if( $P['status'] === '00' && $this->_matchkey( $N->getConfigData('encrytype') , $N->getConfigData('login') , $N->getConfigData('transkey'), $P ) ) {

            //If order status is not set to processing yet
            if(ucfirst($order_status)!="Processing") {
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
            }
            //otherwise, skip to this (as order status already set to processiong during notification)
            else{
               $session = Mage::getSingleton('checkout/session');

               $quoteid = $N->getQuote()->getId();
               $session->setLastSuccessQuoteId($quoteid);
               $session->setLastQuoteId($quoteid);
               $session->setLastOrderId($P['orderid']);
            }

            $this->_redirect('checkout/onepage/success');
            return;
        } else {
            $order->setState(
                Mage_Sales_Model_Order::STATUS_FRAUD,
                Mage_Sales_Model_Order::STATUS_FRAUD,
                'Payment Error: Signature key not match'
                . "\n<br>TransactionID: " . $P['tranID']
                . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt
                . "\n<br>PaidDate: " . $P['paydate'],
                $notified = true
            );
            $order->save();
            $this->_redirect('checkout/cart');
            return;
        }
    }

    public function notificationAction() {
        $P = $this->getRequest()->getPost();

        $TypeOfReturn = "NotificationURL";
        $etcAmt = '';

        if($P['nbcb'] == 2) {
            $order = Mage::getModel('sales/order')->loadByIncrementId( $P['orderid'] );
            $orderId = $order->getId();
            $order_status = $order->getStatus();
            $N = Mage::getModel('molpayseamless/paymentmethod');

            if(!isset($orderId)){
                Mage::throwException($this->__('Order identifier is not valid!'));
                return false;
            }else if( $order->getPayment()->getMethod() !=="molpayseamless" ) {
                Mage::throwException($this->__('Payment Method is not MOLPaySeamless !'));
                return false;
            }else if(ucfirst($order_status)=="Processing"){
                // Order has been placed. To avoid duplicate order
                return false;
            }else{
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
                    if(ucfirst($order_status)!="Processing") { 
                        $order->getPayment()->setTransactionId( $P['tranID'] );
                        try{
                            if($this->_createInvoice($order,$N,$P,$TypeOfReturn)) {
                                $order->sendNewOrderEmail();
                            }
                        }
                        catch (Mage_Core_Exception $e){
                            Mage::logException($e);
                        }

                        $order->save();
                    }

                    return;
                } else {
                    $order->setState(
                            Mage_Sales_Model_Order::STATUS_FRAUD,
                            Mage_Sales_Model_Order::STATUS_FRAUD,
                            'Payment Error: Signature key not match'
                            . "\n<br>TransactionID: " . $P['tranID']
                            . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt
                            . "\n<br>PaidDate: " . $P['paydate'],
                            $notified = true );
                    $order->save();
                    return;
                }
            }
        }
        exit;
    }

    public function callbackAction() {

        $P = $this->getRequest()->getPost();

        Mage::log($P, null, 'molpay_callback.log');

        echo "CBTOKEN:MPSTATOK";
        $TypeOfReturn = "CallbackURL";
        $etcAmt = '';

        if($P['nbcb'] == 1) {
            $order = Mage::getModel('sales/order')->loadByIncrementId( $P['orderid'] );
            $orderId = $order->getId();
            $order_status = $order->getStatus();
            $N = Mage::getModel('molpayseamless/paymentmethod');

            if(!isset($orderId)){
                Mage::throwException($this->__('Order identifier is not valid!'));
                return false;
            }else if( $order->getPayment()->getMethod() !=="molpayseamless" ) {
                Mage::throwException($this->__('Payment Method is not MOLPaySeamless !'));
                return false;
            }
            else if(ucfirst($order_status)=="Processing"){
                // Order has been placed. To avoid duplicate order
                return false;
            }
            else{
                if( ($P['status'] === '00' && $this->_matchkey( $N->getConfigData('encrytype') , $N->getConfigData('login') , $N->getConfigData('transkey'), $P )) && ucfirst($order_status)!="Processing")
                {
                    $order->getPayment()->setTransactionId( $P['tranID'] );

                    if($order->hasInvoices() && ($order->getStatus() === Mage_Sales_Model_Order::STATUS_FRAUD)){
                        $order->setState(
                                    Mage_Sales_Model_Order::STATE_PROCESSING,
                                    Mage_Sales_Model_Order::STATE_PROCESSING
                                );
                    }
                    elseif($this->_createInvoice($order,$N,$P,$TypeOfReturn)) {
                        $order->sendNewOrderEmail();
                    }

                    $order->save();
                    return;

                } else if( $P['status'] !== '00' ) {

                    if($P['status'] == '22') {
                        $this->updateOrderStatus($order, $P, $etcAmt, $TypeOfReturn, "PENDING");
                        $order->save();
                    } else {
                        $this->updateOrderStatus($order, $P, $etcAmt, $TypeOfReturn, "FAILED");
                        $order->save();
                    }
                    return;

                } else {
                    $order->setState(
                            Mage_Sales_Model_Order::STATUS_FRAUD,
                            Mage_Sales_Model_Order::STATUS_FRAUD,
                            'Payment Error: Signature key not match'
                            . "\n<br>TransactionID: " . $P['tranID']
                            . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt
                            . "\n<br>PaidDate: " . $P['paydate'],
                            $notified = true );
                    $order->save();
                    return;
                }

            }
        }
        exit;
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

        if($order->hasInvoices() < 1){
            $invoice =  Mage::getModel('sales/service_order', $order)->prepareInvoice();
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            $invoice->register();
            Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();
        }


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

        $status_update = "";
        if($status == "PENDING"){
            $status_update = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        }elseif($status == "FAILED"){
            $status_update = Mage_Sales_Model_Order::STATE_CANCELED;
        }else{
            $status_update = "";
        }

        $order->setState(
                $status_update,
                $status_update,
                'Customer Redirect from MOLPAY - ' .$TypeOfReturn. ' (' .$status. ')'
                . "\n<br>TransactionID: " . $P['tranID']
                . "\n<br>Amount: " . $P['currency'] . " " . $P['amount'] . $etcAmt
                . "\n<br>PaidDate: " . $P['paydate']
                ,
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

    protected function queryStatusTransaction($P){
        $result  = '';
        $res     = array();

        //get merchant verify key
        $pm2     = Mage::getModel('molpayseamless/paymentmethod');
        $mpverifykey  =  $pm2->getConfigData('transkey');

        //skey formula : skey = md5( txID & domain & verify_key & amount )
        $rawkey  = $P['tranID'].$P['domain'].$mpverifykey.$P['amount'];
        $skey    = md5($rawkey);

        $dataq   = array(
                       "amount" => $P['amount'],
                       "txID"   => $P['tranID'],
                       "domain" => $P['domain'],
                       "skey"   => $skey,
                       "type"   => "0"
                 );
        $postdata = http_build_query($dataq);

        try{
            $url        = "https://api.molpay.com/MOLPay/q_by_tid.php";
            $ch         = curl_init();
            curl_setopt($ch, CURLOPT_POST           , 1     );
            curl_setopt($ch, CURLOPT_POSTFIELDS     , $postdata );
            curl_setopt($ch, CURLOPT_URL            , $url );
            curl_setopt($ch, CURLOPT_HEADER         , 1  );
            curl_setopt($ch, CURLINFO_HEADER_OUT    , TRUE   );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1  );
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , FALSE);
            $result     = curl_exec( $ch );
            curl_close( $ch );

            $dataRes    = trim(strstr($result,"StatCode"));
            $dataRes    = explode("\n",$dataRes);

            $res = array();
            foreach($dataRes as $dt){
                list($k,$v) = explode(': ',$dt);
                $res[$k]    = $v;
            }
        }catch (Exception $e) {
            //$res = array('error' => $e->getMessage(), 'code' => $e->getCode());
            $res ='';
        }
        return $res;
    }
}
