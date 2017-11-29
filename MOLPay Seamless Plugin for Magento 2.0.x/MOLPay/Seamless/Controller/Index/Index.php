<?php

namespace MOLPay\Seamless\Controller\Index;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;

     /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
        ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;       
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
            $merchantid = $this->_objectManager->create('MOLPay\Seamless\Helper\Data')->getMerchantID();
            $vkey = $this->_objectManager->create('MOLPay\Seamless\Helper\Data')->getVerifyKey();
            $settimer = $this->_objectManager->create('MOLPay\Seamless\Helper\Data')->getTimerPayment();
            if( !$settimer ) $settimer = 0;
            
            $base_url = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseUrl();
            
            $installmonth = 0;
            $inst_channel = '';
            $tmp_paymentopt = $_POST['payment_options'];
            $returnValue = strpos($tmp_paymentopt, 'install');
            if( $returnValue !== false ){
                $arrPayOpt = explode('_',$tmp_paymentopt);
                $inst_channel = $arrPayOpt[1];
                $installmonth = $arrPayOpt[2];

                $_POST['payment_options'] = $inst_channel;
            }

            
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
                'mpsbill_mobile'  => $order->getBillingAddress()->getTelephone(),    // To Do - Change tuo customer mobile number
                'mpsbill_desc'    => "Payment for Order #".$order->getIncrementId(),
                'mpscountry'      => "MY",
                'mpsvcode'        => md5($order_amount.$merchantid.$order->getIncrementId().$vkey),
                'mpscurrency'     => $order->getOrderCurrencyCode(),
                'mpslangcode'     => "en",
                'mpsreturnurl'    => $base_url.'seamless',
                'mpstimer'	  => $settimer,
                'mpstimerbox'	  => "#counter",
                'mpscancelurl'	  => $base_url.'seamless',
        		'mpsinstallmonth' => $installmonth
            );

            $this->getResponse()->setBody(json_encode($params));

        } 
        else if( isset($_REQUEST) && $_REQUEST != "" ) { 
           //incase timer timeups return here
    	   if( isset($_REQUEST['mpsorderid']) ){
	        $order_id = $_REQUEST['mpsorderid'];
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
           elseeif( isset($_REQUEST['status'] ) ) //response from MOLPay 
	   { 

            $this->_ack($_REQUEST);
            $status = $_REQUEST['status'];
            $order_id = $_REQUEST['orderid'];
            $skey = $_REQUEST['skey'];
            
            if(isset($_REQUEST['nbcb']))
            {
	            $nbcb = $_REQUEST['nbcb'];
            }
            else
            {
                $nbcb = 0;
            }
            
            $gate_response = $_REQUEST;

            $om =   \Magento\Framework\App\ObjectManager::getInstance();
            
            $order = $om->create('Magento\Sales\Api\Data\OrderInterface');
            $order->loadByIncrementId($order_id);
            

            $vkey = $this->_objectManager->create('MOLPay\Seamless\Helper\Data')->getSecretKey();
            

            $key0 = md5($_REQUEST['tranID'].$order_id.$status.$_REQUEST['domain'].$_REQUEST['amount'].$_REQUEST['currency']);
            $key1 = md5($_REQUEST['paydate'].$_REQUEST['domain'].$key0.$_REQUEST['appcode'].$vkey);
              
            if($skey == $key1) {
                $checkoutSession = $om->create('\Magento\Checkout\Model\Session');              
                //clear all data and session
                $checkoutSession->clearStorage();
                                    
                if($status == '00') {   // Success Payment
                    //clear all data and session
                    $checkoutSession->clearStorage();
                    
                    $this->messageManager->addSuccess('Order has been successfully placed!');
                    $order->setState('processing',true);
                    $order->setStatus('processing',true); 
              
		    //transaction id and status
                    //$this->createTransaction($order, $gate_response);
		
                    //create invoice
                    $this->create_invoice($order_id,$_REQUEST['tranID']);
                
                    //transaction id and status
                    $this->createTransaction($order, $gate_response);

                    //create invoice
                    //$this->create_invoice($order_id,$_REQUEST['tranID']);

                    //page redirect
                    $url_checkoutredirection = 'checkout/onepage/success';
                    

                } else if($status == '22') {    // Pending Payment

                    $this->messageManager->addSuccess('Order has been successfully placed!');
                    $order->setState('pending',true);
                    $order->setStatus('pending',true);
                    $order->save();

                    $url_checkoutredirection = 'checkout/onepage/success';

                } else { // Fail Payment
                
                    $this->messageManager->addError('Fail to complete payment.');
		    		if( $nbcb == "1" )
		    		{
                        ////$order->addStatusToHistory('canceled', 'MOLPay Callback Status');
                    	$order->setState('canceled',true);
                    	$order->setStatus('canceled',true);
                    	$order->save();
		    		}

                    //$url_checkoutredirection = 'checkout/cart';
                    $url_checkoutredirection = 'sales/order/reorder/order_id/'.$order_id.'/';
                }
            } else {

                $this->messageManager->addError('Key is not valid.');
                $order->setState('fraud',true);
                $order->setStatus('fraud',true);
                $order->save();

                $url_checkoutredirection = 'checkout/cart';
            }


            if(isset($_REQUEST['nbcb']) && $_REQUEST['nbcb'] == 1)
            {
                echo 'CBTOKEN:MPSTATOK';
            } else {
                //$this->_redirect('sales/order/history/');
                $this->_redirect($url_checkoutredirection);
            }
          }
        } /**else { 
            echo 'Required parameter not exist';
        }**/
        
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



    public function create_invoice($order_id, $txn_id_gateway){

        //$checkoutSession = $om->create('\Magento\Checkout\Model\Session');
        //$checkoutSession->getData();
        
        $order = $this->_objectManager->create('Magento\Sales\Model\Order')->loadByAttribute('increment_id', $order_id);

        if ($order->canInvoice()) {
            // Create invoice for this order
            $invoice = $this->_objectManager->create('Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($order);

            // Make sure there is a qty on the invoice
            if (!$invoice->getTotalQty()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                            __('You can\'t create an invoice without products.')
                        );
            }

            // Register as invoice item
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);

            //$invoice->setTransactionId($txn_id_gateway);
            $invoice->register();

            // Save the invoice to the order
            $transaction = $this->_objectManager->create('Magento\Framework\DB\Transaction')
                 ->addObject($invoice)
                 ->addObject($invoice->getOrder());

            $transaction->save();

            // Magento\Sales\Model\Order\Email\Sender\InvoiceSender
            $this->invoiceSender = $this->_objectManager->create('Magento\Sales\Model\Order\Email\Sender\InvoiceSender');
            $this->invoiceSender->send($invoice);
            
            $order->addStatusHistoryComment(
                __('Notified customer about invoice #%1.', $invoice->getId())
            )
                ->setIsCustomerNotified(true)
                ->save();
        }
    } 


    public function createTransaction($order = null, $paymentData = array())
    {
        try {
            //get payment object from order object
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData['tranID']);
            $payment->setTransactionId($paymentData['tranID']);
            /*$payment->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]
            );*/
        
            $formatedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );
            
            $message = __('The authorized amount is %1.', $formatedPrice);
            //get the object of builder class
            $this->_transactionBuilder = $this->_objectManager->create('Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface');
            $trans = $this->_transactionBuilder;
            $transaction = $trans->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($paymentData['tranID'])
            /*->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]
            )*/
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
 
            $payment->addTransactionCommentsToOrder(
                $transaction,
                $message
            );
            
            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();
 
            return  $transaction->save()->getTransactionId();
        } catch (Exception $e) {
            //log errors here
        }
    }
}
