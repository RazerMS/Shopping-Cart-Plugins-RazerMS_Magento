<?php

namespace RazerPay\Payment\Model\Config\Source;

class Channel implements \Magento\Framework\Data\OptionSourceInterface
{
    protected \RazerPay\Payment\Domain\DataDomain $paymentDataDomain;

    public function __construct(
        \RazerPay\Payment\Domain\DataDomain $paymentDataDomain
    ) {
        $this->paymentDataDomain = $paymentDataDomain;
    }

    public function toOptionArray(): array
    {
        return array_map(function (array $channel) {
            return [
                'label' => __($channel['title']),
                'value' => $channel['request_code'],
            ];
        }, $this->paymentDataDomain->getAvailableChannels());
    }
}
