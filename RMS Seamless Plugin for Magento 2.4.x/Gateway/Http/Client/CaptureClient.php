<?php

namespace RazerPay\Payment\Gateway\Http\Client;

class CaptureClient implements \Magento\Payment\Gateway\Http\ClientInterface
{
    protected \RazerPay\Payment\Domain\PaymentDomain $paymentDomain;

    public function __construct(
        \RazerPay\Payment\Domain\PaymentDomain $paymentDomain
    ) {
        $this->paymentDomain = $paymentDomain;
    }

    /**
     * @param  \Magento\Payment\Gateway\Http\TransferInterface  $paymentGatewayHttpTransfer
     *
     * @return \Exception|\RazerPay\Payment\Exception\CaptureCreditAuthPaymentException|void
     */
    public function placeRequest(
        \Magento\Payment\Gateway\Http\TransferInterface $paymentGatewayHttpTransfer
    ) {
        /**
         * @var \Magento\Sales\Model\Order\Payment $salesOrderPayment
         */
        $salesOrderPayment = $paymentGatewayHttpTransfer->getBody()['payment'];

        try {
            $this->paymentDomain->captureCreditAuthPayment($salesOrderPayment);
        } catch (\RazerPay\Payment\Exception\CaptureCreditAuthPaymentException $exception) {
            return $exception;
        }
    }
}
