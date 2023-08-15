<?php

namespace RazerPay\Payment\Observer;

use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserver extends \Magento\Payment\Observer\AbstractDataAssignObserver
{
    protected \RazerPay\Payment\Domain\DataDomain $paymentDataDomain;

    protected \RazerPay\Payment\Gateway\Config\Config $paymentGatewayConfig;

    public function __construct(
        \RazerPay\Payment\Domain\DataDomain $paymentDataDomain,
        \RazerPay\Payment\Gateway\Config\Config $paymentGatewayConfig
    ) {
        $this->paymentDataDomain = $paymentDataDomain;
        $this->paymentGatewayConfig = $paymentGatewayConfig;
    }

    /**
     * @var array
     */
    protected $additionalInformationList = [
        'channel_code',
    ];

    /**
     * @param  \Magento\Framework\Event\Observer  $observer
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $dataObject = $this->readDataArgument($observer);

        $additionalData = $dataObject->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $paymentModel = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentModel->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }

        if ($this->paymentDataDomain->checkChannelIsCC($paymentModel->getAdditionalInformation('channel_code'))) {
            $paymentModel->setAdditionalInformation(
                'credit_channel_transaction_type',
                $this->paymentGatewayConfig->getCreditChannelTransactionType()
            );
        }
    }
}
