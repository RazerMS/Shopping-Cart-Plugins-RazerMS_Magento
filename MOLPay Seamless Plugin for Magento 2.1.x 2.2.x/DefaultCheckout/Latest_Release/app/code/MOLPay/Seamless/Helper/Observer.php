<?php

namespace MOLPay\Seamless\Helper;

class Observer implements \Magento\Framework\Event\ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
     $order = $observer->getEvent()->getOrder();
	 $order->setCanSendNewEmailFlag(false);
    }
}

?>