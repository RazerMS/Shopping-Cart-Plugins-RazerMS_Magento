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
            $current_customer_email = $quote->getBillingAddress()->getEmail();
            
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
                    ->setCustomerEmail($current_customer_email)
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
            }

            
            if( $quote ){
                $cartManagement = $om->create('\Magento\Quote\Model\QuoteManagement');
                $order = $cartManagement->submit($quote);

                
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
        else if( isset($_POST[ 'mpsorderid' ]) && $_POST != "" ) {   // Get the return from MOLPay ; case using timer on payment page
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

            $om =   \Magento\Framework\App\ObjectManager::getInstance();

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

                } else { // Fail Payment
                    if( $nbcb != "1" ){
                        $commentMsg = "Fail to complete payment.";
                        if ($order->getId() && $order->getState() != 'canceled') {
                            $order->registerCancellation($commentMsg)->save();
                        }                    

                        $this->checkoutSession->restoreQuote();   
                        //$this->messageManager->addError($commentMsg);
                    }
                    
                    if( $nbcb == "1" ) //Callback : possible differ update when return URL (e.g pending payment to fail) 
                    {
                        $order->setState('canceled',true);
                        $order->setStatus('canceled',true);

                        $order->addStatusHistoryComment(__('Response from MOLPay - '. $nbcb_type . ' (Transaction Status : FAILED)'))
                              ->setIsCustomerNotified(false);
                    }

                    $url_checkoutredirection = 'checkout/cart';
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
            $this->orderSender->send($order);
            $quote = $this->quoteRepository->get($order->getQuoteId())->setIsActive(false);
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We cannot send the new order email.'));
        }
    
    }

}
