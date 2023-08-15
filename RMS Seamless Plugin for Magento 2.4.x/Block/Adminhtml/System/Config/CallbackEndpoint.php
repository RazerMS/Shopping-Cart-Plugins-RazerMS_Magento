<?php

namespace RazerPay\Payment\Block\Adminhtml\System\Config;

class CallbackEndpoint extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        $element->setReadonly(true, true);
        $element->setValue($this->getBaseUrl() . 'razerpay_payment/checkout/callback');

        return parent::_getElementHtml($element);
    }
}
