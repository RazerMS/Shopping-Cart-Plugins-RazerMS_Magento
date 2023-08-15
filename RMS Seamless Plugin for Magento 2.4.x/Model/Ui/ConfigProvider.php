<?php

namespace RazerPay\Payment\Model\Ui;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    const CODE = 'razerpay_payment';

    /**
     * @var \RazerPay\Payment\Gateway\Config\Config
     */
    protected $paymentGatewayConfig;

    /**
     * @var \RazerPay\Payment\Domain\DataDomain
     */
    protected \RazerPay\Payment\Domain\DataDomain $paymentDataDomain;

    /**
     * ConfigProvider constructor.
     *
     * @param  \RazerPay\Payment\Gateway\Config\Config  $paymentGatewayConfig
     * @param  \RazerPay\Payment\Domain\DataDomain  $paymentDataDomain
     */
    public function __construct(
        \RazerPay\Payment\Gateway\Config\Config $paymentGatewayConfig,
        \RazerPay\Payment\Domain\DataDomain $paymentDataDomain
    ) {
        $this->paymentGatewayConfig = $paymentGatewayConfig;
        $this->paymentDataDomain = $paymentDataDomain;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'seamlessJsUrl' => $this->paymentGatewayConfig->getSeamlessJsUrl(),
                    'channels' => $this->paymentDataDomain->getEnabledChannels(),
                ],
            ],
        ];
    }
}
