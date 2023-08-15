<?php

namespace RazerPay\Payment\Domain\Api;

class GetChannelStatusRequest extends Request
{
    protected string $method = \Magento\Framework\App\Request\Http::METHOD_POST;

    protected string $productionEndpoint = 'https://pay.merchant.razer.com/RMS/API/chkstat/channel_status.php';

    protected string $sandboxEndpoint = 'https://sandbox.merchant.razer.com/RMS/API/chkstat/channel_status.php';

    protected function headers(): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
    }

    protected function body(): ?\Psr\Http\Message\StreamInterface
    {
        $form = http_build_query(
            [
                'merchantID' => $mechantID = $this->paymentGatewayConfig->getMerchantId(),
                'datetime' => $datetime = date('YmdHis'),
                'skey' => $this->skey($mechantID, $datetime),
            ],
            '',
            '&'
        );

        return \GuzzleHttp\Psr7\Utils::streamFor($form);
    }

    protected function skey(
        string $merchantId,
        string $datetime
    ): string {
        return hash_hmac(
            'SHA256',
            $datetime.$merchantId,
            $this->paymentGatewayConfig->getVerifyKey()
        );
    }
}
