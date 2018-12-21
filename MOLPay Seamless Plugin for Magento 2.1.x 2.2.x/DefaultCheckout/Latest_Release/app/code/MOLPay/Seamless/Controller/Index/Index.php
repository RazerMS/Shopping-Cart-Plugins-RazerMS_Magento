<?php

namespace MOLPay\Seamless\Controller\Index;

use Magento\Framework\Controller\ResultFactory; 
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;

     /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    
     /**
     * @var OrderSender
     */
    protected $orderSender;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        OrderSender $orderSender,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
        
    ) 
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->invoiceSender = $invoiceSender;
        $this->transactionFactory = $transactionFactory;        
        $this->checkoutSession = $checkoutSession;
        $this->orderSender = $orderSender;
        $this->quoteRepository = $quoteRepository;
    }

    public function execute()
    {

        if( isset($_POST['payment_options']) && $_POST['payment_options'] != "" ) {
            // Attempt to store the cart into magento system
            // This function should be execute during MOLPay selection page AFTER the address selection
            // Begin calling Magento API

            $om =   \Magento\Framework\App\ObjectManager::getInstance();

            ### At first time, create quote and order
            $cartData = $om->create('\Magento\Checkout\Model\Cart')->getQuote();
            $quote = $om->create('\Magento\Quote\Model\Quote');
            $quote->load($cartData->getId());
            $quote->getPayment()->setMethod('molpay_seamless'); // Todo: Will Appear MOLPay Seamless

            $customerSess = $om->create('\Magento\Customer\Model\Session');
            $checkoutHelperData = $om->create('\Magento\Checkout\Helper\Data');

            //Get customer email
            if( $_POST['current_email'] == ''){	//if the case is empty
                $quote_extra = $this->quoteRepository->getActive($cartData->getId());	
                $_POST['current_email'] = $quote_extra->getBillingAddress()->getEmail();	
            }
    
            $customerType = '';
            if ($customerSess->isLoggedIn()) {
                $customerType = \Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER;
            }
            if (!$quote->getCheckoutMethod()) {
                if ($checkoutHelperData->isAllowedGuestCheckout($quote)) {
                    $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
                } else {
                    $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER);
                }

                $customerType = $quote->getCheckoutMethod();
            }

            if ( $customerType == \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST) {

                $quote->setCustomerId(null)
                    ->setCustomerEmail($_POST['current_email'])
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
            }

            
            if( $quote ){
                $orderData = [];
                $orderDate = [
                    'is_send_email' => null,
                    'is_email_sent' => null
                ];
                $cartManagement = $om->create('\Magento\Quote\Model\QuoteManagement');
                $order = $cartManagement->submit($quote,$orderData);

                
                if( $order ){
                    $orderArr = [];
                    $orderArr = [
                        'oid' => $order->getId(),
                        "flname" => $order->getCustomerFirstName()." ".$order->getCustomerLastName(),
                        'lastorderid' => $order->getIncrementId() ];

                    $order_step2 = $om->create('\Magento\Sales\Model\Order')
                                     ->load($order->getId());

                        $order_step2->setState("pending_payment")->setStatus("pending_payment");

                                $order_step2->save();

                }
                
            }

            ### Begin to save quote and order in session
            $checkoutSession = $om->create('\Magento\Checkout\Model\Session');

            ### initial order created, save their data in session
            if( $order ){
                    $checkoutSession->setLastQuoteId($cartData->getId())->setLastSuccessQuoteId($cartData->getId());
                    $checkoutSession->setLastOrderId($order->getId())
                        ->setLastRealOrderId($order->getIncrementId())
                        ->setLastOrderStatus('pending_payment');
            }

            ### When 2nd attempt to make payment but above order create is error then use the session
            if( !$order ){
                $sess_quotedata = $checkoutSession->getData();

                if( isset($sess_quotedata['last_real_order_id']) && $sess_quotedata['last_real_order_id'] != null){

                    $lastOId = $sess_quotedata['last_real_order_id'];

                    $order = $om->create('\Magento\Sales\Api\Data\OrderInterface');
                    $order->loadByIncrementId($lastOId);
                    $orderArr = [];
                    $orderArr = [
                        'orderid'       => $lastOId,
                        'customer_name' => $order->getBillingAddress()->getFirstname()." ".$order->getBillingAddress()->getLastname(),
                        'customer_email'=> $order->getCustomerEmail(),
                        'customer_tel'  => $order->getBillingAddress()->getTelephone(),
                        'amount'        => $order->getGrandTotal(),
                        'currency'      => $order->getOrderCurrencyCode()

                    ];
                }

            }
            
            //Get customer country id
            if( $quote->getShippingAddress()->getCountryId() === null ){
                $customer_countryid = ''; //leave empty for Collect at Store
            }
            else{
                $customer_countryid = $quote->getShippingAddress()->getCountryId();
            }

            $merchantid = $this->_objectManager->create('MOLPay\Seamless\Helper\Data')->getMerchantID();
            $vkey = $this->_objectManager->create('MOLPay\Seamless\Helper\Data')->getVerifyKey();

            $base_url = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseUrl();

            ### Make sure amount is always same format
            $order_amount = number_format(floatval($order->getGrandTotal()),2,'.','');

            ### End calling Magento API and set parameter for seamless button
            $params = array(
                'status'          => true,  // Set True to proceed with MOLPay
                'mpsmerchantid'   => $merchantid,
                'mpschannel'      => $_POST['payment_options'],
                'mpsamount'       => $order_amount,
                'mpsorderid'      => $order->getIncrementId(),
                'mpsbill_name'    => $order->getBillingAddress()->getFirstname()." ".$order->getBillingAddress()->getLastname(),
                'mpsbill_email'   => $order->getCustomerEmail(),
                'mpsbill_mobile'  => $order->getBillingAddress()->getTelephone(),    // To Do - Change to customer mobile number
                'mpsbill_desc'    => "Payment for Order #".$order->getIncrementId(), 
                'mpscountry'      => $customer_countryid, //tested and passed when empty value
                'mpsvcode'        => md5($order_amount.$merchantid.$order->getIncrementId().$vkey),
                'mpscurrency'     => $order->getOrderCurrencyCode(),
                'mpslangcode'     => "en",
                'mpsreturnurl'    => $base_url.'seamless/',
                'mpstimer'        => '8',
                'mpstimerbox'     => '#counter',
                'mpscancelurl'    => $base_url.'seamless/'
            );

            $this->getResponse()->setBody(json_encode($params));
            
        }
        else if( isset($_POST[ 'mpsorderid' ]) && $_POST != "" ) {   
                // Get the return from MOLPay ; case using timer on payment page
                // Case: usually used on credit card payment form (pop up window) and no transaction Id created if buyer not click 'Pay Now'
                
                $order_id = $_POST['mpsorderid'];
                $om =   \Magento\Framework\App\ObjectManager::getInstance();
            
                $order = $om->create('Magento\Sales\Api\Data\OrderInterface');
                $order->loadByIncrementId($order_id);
                            
                $this->messageManager->addError('Fail to complete payment.');
                               
                $order->setState('canceled',true);
                $order->setStatus('canceled',true);
                $order->save();
                                
                $url_checkoutredirection = 'sales/order/reorder/order_id/'.$order_id.'/';
  
                $this->_redirect($url_checkoutredirection);
        }
        else if( isset($_POST['status'] ) ) //response from MOLPay 
        {             
            $this->_ack($_POST);
            $status = $_POST['status'];
            $order_id = $_POST['orderid'];
            $skey = $_POST['skey'];

            if(isset($_POST['nbcb']))
            {
                $nbcb = $_POST['nbcb'];
            }
            else
            {
                $nbcb = 0;
            }

            $nbcb_type = '';
            if( $nbcb == 1 ) $nbcb_type = 'Callback';
            elseif( $nbcb == 2 ) $nbcb_type = 'Notification';
            else $nbcb_type = 'Return';

            $gate_response = $_POST;

            $om = \Magento\Framework\App\ObjectManager::getInstance();

            $order = $om->create('Magento\Sales\Api\Data\OrderInterface');
            $order->loadByIncrementId($order_id);

            $vkey = $this->_objectManager->create('MOLPay\Seamless\Helper\Data')->getSecretKey();
            
            $key0 = md5($_POST['tranID'].$order_id.$status.$_POST['domain'].$_POST['amount'].$_POST['currency']);
            $key1 = md5($_POST['paydate'].$_POST['domain'].$key0.$_POST['appcode'].$vkey);

            if($skey == $key1) {

                if($status == '00') {   // Success Payment
                   $quoteId = $order->getQuoteId();
                   if ($order->getId() && $order->getState() != 'processing') {
                        
                        $order->setState('processing',true);
                        $order->setStatus('processing',true);

                        $order->addStatusHistoryComment(__('Response from MOLPay - '. $nbcb_type . ' (Transaction Status : CAPTURED).<br/>You have confirmed the order to the customer via email.' ))
                              ->setIsCustomerNotified(true);
                    
                        $payment = $order->getPayment();
                        $mp_amount = $_POST['amount'];
                        $mp_txnid = $_POST['tranID'];

                        //Create New Invoice and Transaction functions
                        $this->update_invoice_transaction( $order, $payment, $mp_txnid );
                    }          
                    
                    $this->messageManager->addSuccess('Order has been successfully placed!');
                    
                    $this->checkoutSession->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);
                    $this->checkoutSession->setLastOrderId($order->getId());
                   
                    //page redirect
                    $url_checkoutredirection = 'checkout/onepage/success';


                } else if($status == '22') {    // Pending Payment
                    
                    if ( $order->getId() && $order->getState() != 'pending' ) {
                        $this->messageManager->addSuccess('Order has been successfully placed!');
                        $order->setState('pending',true);
                        $order->setStatus('pending',true);

                        $order->addStatusHistoryComment(__('Response from MOLPay - '. $nbcb_type . ' (Transaction Status : PENDING)'))
                              ->setIsCustomerNotified(false);
                    }

                    $quoteId = $order->getQuoteId();
                    $this->checkoutSession->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);
                    $this->checkoutSession->setLastOrderId($order->getId());
                    
                    $url_checkoutredirection = 'checkout/onepage/success';

                } 
                else { // Fail Payment
                    if ($order->getId() && $order->getState() != 'canceled') {
                        if( $nbcb == "1" || $nbcb == "2" ) //Callback; nbcb=1 : possible differ update from return URL status / Notification; nbcb=2 : confirm status from bank
                        {
                            $order->setState('canceled',true);
                            $order->setStatus('canceled',true);

                            $order->addStatusHistoryComment(__('Response from MOLPay - '. $nbcb_type . ' (Transaction Status : FAILED)'))
                                  ->setIsCustomerNotified(false);
                            $order->save();
                        }
                        else //During buyer return to merchnar site; nbcb empty
                        {
                            /************************************************************************************************************************************************
                            
                               Case: Buyer return to merchant website (MOLPay to merchant in Return URL)
                               There are 2 possibilities that Status Txn 11 during return/redirect to merchant website :
                               1- During return to merchant website, MOLPay get result from bank and directly give status of transaction ( This confirm txn status failed )
                               2- MOLPay not yet get result from bank during buyer redirect to merchant page. At this time, merchant will get txn status failed.
                                  But, txn status can be changed, as MOLPay requery status of payment at Bank and return the status to merchant website through callback
                            
                            *************************************************************************************************************************************************/

                            //requery status at MOLPay
                            $qtxn = $this->queryStatusTransaction($gate_response);

                            if( !empty($qtxn) ){
                                if( !empty($qtxn) && $qtxn['StatCode'] === "11") { //StatName = failed
                                    $this->messageManager->addError('Fail to complete payment.');

                                    $order->setState('canceled',true);
                                    $order->setStatus('canceled',true);
                                    $order->addStatusHistoryComment(__('Response from MOLPay - '. $nbcb_type . ' (Transaction Status : FAILED)'))
                                          ->setIsCustomerNotified(false);
                                    $order->save();

                                    $url_checkoutredirection = 'checkout/cart';
                                }
                                elseif( (!empty($qtxn) && $qtxn['StatCode'] === "22") ) { //Statname = pending
                                    if ( $order->getId() && $order->getState() != 'pending' ) {
                                        //Buyer will see this page as Order Being Placed
                                        $this->messageManager->addSuccess('Order has been successfully placed!');
                                        //advisable to not change order status to canceled due to Magento business flow
                                        $order->setState('pending',true);
                                        $order->setStatus('pending',true);
                                        //But status from MOLPay at this time failed. need to wait latest status from MOLPay thru callback
                                        $order->addStatusHistoryComment(__('Response from MOLPay - '. $nbcb_type . ' (Transaction Status : FAILED). <br>Note: Possible status change. Waiting callback response'))
                                              ->setIsCustomerNotified(false);
                                        $order->save();

                                        $quoteId = $order->getQuoteId();
                                        $this->checkoutSession->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);
                                        $this->checkoutSession->setLastOrderId($order->getId());

                                        $url_checkoutredirection = 'checkout/onepage/success';
                                    }
                                }
                            }
                        }
                    }
                    
                    //Case: Order status already set canceled during notification. Must return buyer to merchant page
                    if($order->getStatus() == 'canceled'){ 
                         $this->messageManager->addError('Fail to complete payment.');
                         $url_checkoutredirection = 'checkout/cart';
                    }
                }
            } else {

                $this->messageManager->addError('Key is not valid.');
                $order->setState('fraud',true);
                $order->setStatus('fraud',true);

                $history_msg = '';
                $history_msg = 'Payment Error: Signature key not match';

                $order->addStatusHistoryComment(__( $history_msg ))
                      ->setIsCustomerNotified(false);

                $url_checkoutredirection = 'checkout/cart';
            }
            $order->save();
  
            if(isset($_POST['nbcb']) && $_POST['nbcb'] == 1)
            {
                echo 'CBTOKEN:MPSTATOK';
            }elseif($nbcb == 0) {
                $this->_redirect($url_checkoutredirection);
            }

        }
        
        else if( empty($_POST) ){
           $this->_redirect('/');
        }
        
        
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
    
    public function update_invoice_transaction($order, $payment, $e){ //$a:$order_id, $b:$order, $c:$payment, $d:$mp_amount, $e:$mp_txnid
        if($order->canInvoice()) {
            $payment
                    ->setTransactionId($e)
                    ->setShouldCloseParentTransaction(1)
                    ->setIsTransactionClosed(0);
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->register();
                
                $transaction = $this->transactionFactory->create();
                
                $transaction->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();
                
        }
    
        try {
            if($order->getCanSendNewEmailFlag() == false ){
                $order->setCanSendNewEmailFlag(true);   
            }
            $this->orderSender->send($order);
            $quote = $this->quoteRepository->get($order->getQuoteId())->setIsActive(false);
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We cannot send the new order email.'));
        }
    
    }

    protected function queryStatusTransaction($P){
        $result  = '';
        $res     = array();

        //get merchant verify key
        $mpverifykey  = $this->_objectManager->create('MOLPay\Seamless\Helper\Data')->getVerifyKey();

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
            $res ='';
        }

        return $res;
    }

}
