<?php

namespace RazerPay\Payment\Block\Adminhtml\System\Config;

class CancelEndpoint extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        $element->setReadonly(true, true);
        $element->setValue($this->getBaseUrl() . 'razerpay_payment/checkout/cancel');

        return parent::_getElementHtml($element);
    }
}
