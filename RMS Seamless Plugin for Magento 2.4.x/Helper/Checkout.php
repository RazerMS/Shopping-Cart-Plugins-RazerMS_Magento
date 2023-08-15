<?php

namespace RazerPay\Payment\Helper;

class Checkout extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected \RazerPay\Payment\Gateway\Config\Config $paymentGatewayConfig;

    protected \RazerPay\Payment\Logger\Logger $paymentLogger;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \RazerPay\Payment\Gateway\Config\Config $paymentGatewayConfig,
        \RazerPay\Payment\Logger\Logger $paymentLogger
    ) {
        parent::__construct($context);

        $this->paymentGatewayConfig = $paymentGatewayConfig;
        $this->paymentLogger = $paymentLogger;
    }

    public function generateRequestData(
        \Magento\Sales\Model\Order $order
    ): array {
        $grandTotalIn2Decimal = number_format($order->getGrandTotal(), 2, '.', '');

        $productDescriptions = array_map(function (\Magento\Sales\Model\Order\Item $item) {
            return $item->getName();
        }, $order->getAllItems());

        $channel = $order->getPayment()->getAdditionalInformation()['channel_code'] ?? '';

        $vcode = md5(
            $grandTotalIn2Decimal.
            $this->paymentGatewayConfig->getMerchantId().
            $order->getIncrementId().
            $this->paymentGatewayConfig->getVerifyKey()
        );

        return array_filter([
            'amount' => $grandTotalIn2Decimal,
            'orderid' => $order->getIncrementId(),
            'bill_name' => "{$order->getBillingAddress()->getFirstname()} {$order->getBillingAddress()->getLastname()}",
            'bill_email' => $order->getBillingAddress()->getEmail(),
            'bill_mobile' => $order->getBillingAddress()->getTelephone(),
            'bill_desc' => implode(', ', $productDescriptions),
            'vcode' => $vcode,
            'currency' => $order->getOrderCurrencyCode(),
            'channel' => $channel,
            'returnurl' => $this->_getUrl('razerpay_payment/checkout/return'),
            'callbackurl' => $this->_getUrl('razerpay_payment/checkout/callback'),
            'cancelurl' => $this->_getUrl('razerpay_payment/checkout/cancel'),
            'tcctype' => $channel === 'credit' ? $this->paymentGatewayConfig->getCreditChannelTransactionType() : null,
        ]);
    }
}
