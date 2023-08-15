<?php

namespace RazerPay\Payment\Gateway\Http;

class TransferFactory implements \Magento\Payment\Gateway\Http\TransferFactoryInterface
{
    /**
     * @var \Magento\Payment\Gateway\Http\TransferBuilder
     */
    private $transferBuilder;

    /**
     * TransferFactory constructor.
     *
     * @param  \Magento\Payment\Gateway\Http\TransferBuilder  $transferBuilder
     */
    public function __construct(
        \Magento\Payment\Gateway\Http\TransferBuilder $transferBuilder
    ) {
        $this->transferBuilder = $transferBuilder;
    }

    /**
     * Builds gateway transfer object
     *
     * @param  array  $request
     *
     * @return \Magento\Payment\Gateway\Http\TransferInterface
     */
    public function create(array $request)
    {
        return $this->transferBuilder
            ->setBody($request)
            ->build();
    }
}
