<?php

namespace RazerPay\Payment\Gateway\Request;

class InitializationRequest implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * Builds ENV request
     *
     * @param  array  $subject
     *
     * @return array
     */
    public function build(
        array $subject
    ): array {
        /**
         * @var \Magento\Framework\DataObject $stateObject
         */
        $stateObject = $subject['stateObject'];
        $stateObject->setData('state', \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setData('status', \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setData('is_notified', false);

        return [];
    }
}
