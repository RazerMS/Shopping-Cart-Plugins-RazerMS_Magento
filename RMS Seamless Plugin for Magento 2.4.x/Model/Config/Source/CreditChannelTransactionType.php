<?php

namespace RazerPay\Payment\Model\Config\Source;

class CreditChannelTransactionType implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => \RazerPay\Payment\Gateway\Config\Config::CREDIT_CHANNEL_TRANSACTION_TYPE_SALS,
                'label' => __('SALS'),
            ],
            [
                'value' => \RazerPay\Payment\Gateway\Config\Config::CREDIT_CHANNEL_TRANSACTION_TYPE_AUTH,
                'label' => __('AUTH'),
            ],
        ];
    }
}
