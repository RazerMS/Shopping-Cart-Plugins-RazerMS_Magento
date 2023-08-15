<?php

namespace RazerPay\Payment\Gateway\Request;

class CaptureRequest implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    public function build(
        array $subject
    ): array {
        /**
         * @var \Magento\Payment\Gateway\Data\PaymentDataObject $paymentGatewayPaymentDataObject
         */
        $paymentGatewayPaymentDataObject = $subject['payment'];

        return [
            'payment' => $paymentGatewayPaymentDataObject->getPayment(),
        ];
    }
}
