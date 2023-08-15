<?php

namespace RazerPay\Payment\Block\Sales\Order;

class Info extends \Magento\Payment\Block\ConfigurableInfo
{
    protected $_template = 'RazerPay_Payment::sales/order/info.phtml';

    protected \RazerPay\Payment\Domain\DataDomain $paymentDataDomain;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Gateway\ConfigInterface $config,
        \RazerPay\Payment\Domain\DataDomain $paymentDataDomain,
        array $data = []
    ) {
        parent::__construct($context, $config, $data);

        $this->paymentDataDomain = $paymentDataDomain;
    }

    public function getLastTransId()
    {
        return $this->getInfo()->getLastTransId();
    }

    public function getChannel()
    {
        $channelCode = $this->getInfo()->getAdditionalInformation('channel_code');

        return $this->paymentDataDomain->getChannelTitle($channelCode) ?: $channelCode;
    }
}
