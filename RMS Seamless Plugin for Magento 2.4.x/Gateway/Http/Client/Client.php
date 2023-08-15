<?php

namespace RazerPay\Payment\Gateway\Http\Client;

class Client implements \Magento\Payment\Gateway\Http\ClientInterface
{
    public function placeRequest(
        \Magento\Payment\Gateway\Http\TransferInterface $paymentGatewayHttpTransfer
    ): array {
        return [];
    }
}
