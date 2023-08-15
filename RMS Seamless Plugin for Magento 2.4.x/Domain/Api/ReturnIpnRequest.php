<?php

namespace RazerPay\Payment\Domain\Api;

class ReturnIpnRequest extends Request
{
    protected string $method = \Magento\Framework\App\Request\Http::METHOD_POST;

    protected string $productionEndpoint = 'https://pay.merchant.razer.com/RMS/API/chkstat/returnipn.php';

    protected string $sandboxEndpoint = 'https://sandbox.merchant.razer.com/RMS/API/chkstat/returnipn.php';

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
                'treq' => 1,
                ...$this->bodyParams,
            ],
            '',
            '&'
        );

        return \GuzzleHttp\Psr7\Utils::streamFor($form);
    }
}
