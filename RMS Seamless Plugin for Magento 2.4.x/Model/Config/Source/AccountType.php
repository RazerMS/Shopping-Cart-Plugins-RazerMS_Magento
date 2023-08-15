<?php

namespace RazerPay\Payment\Model\Config\Source;

class AccountType implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => \RazerPay\Payment\Gateway\Config\Config::ACCOUNT_TYPE_PRODUCTION,
                'label' => __('Production'),
            ],
            [
                'value' => \RazerPay\Payment\Gateway\Config\Config::ACCOUNT_TYPE_SANDBOX,
                'label' => __('Sandbox'),
            ],
        ];
    }
}
