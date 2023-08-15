<?php

namespace RazerPay\Payment\Domain\Api;

class CaptureRequest extends Request
{
    protected string $method = \Magento\Framework\App\Request\Http::METHOD_POST;

    protected string $productionEndpoint = 'https://api.merchant.razer.com/RMS/API/capstxn/index.php';

    protected string $sandboxEndpoint = 'https://sandbox.merchant.razer.com/RMS/API/capstxn/index.php';

    public const ERRORS = [
        '11' => 'Failure',
        '12' => 'Invalid or unmatched security hash string',
        '13' => 'Not a credit card transaction',
        '15' => 'Requested day is on settlement day',
        '16' => 'Forbidden transaction',
        '17' => 'Transaction not found',
        '18' => 'Missing required parameter',
        '19' => 'Domain not found',
        '20' => 'Temporary out of service',
        '21' => 'Authorization expired',
        '23' => 'Not allowed to perform partial capture',
        '99' => 'General Error(Please check with RMS Support)',
    ];

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
                'domain' => $mechantID = $this->paymentGatewayConfig->getMerchantId(),
                'tranID' => $this->bodyParams['tran_id'],
                'amount' => $this->bodyParams['amount'],
                'RefID' => $this->bodyParams['ref_id'],
                'skey' => $this->skey($this->bodyParams['tran_id'], $this->bodyParams['amount']),
            ],
            '',
            '&'
        );

        return \GuzzleHttp\Psr7\Utils::streamFor($form);
    }

    protected function skey(
        string $tranId,
        string $amount
    ): string {
        return md5(
            $tranId.
            $amount.
            $this->paymentGatewayConfig->getMerchantId().
            $this->paymentGatewayConfig->getVerifyKey()
        );
    }
}
