<?php

namespace RazerPay\Payment\Block\Adminhtml\System\Config;

class NotifyEndpoint extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        $element->setReadonly(true, true);
        $element->setValue($this->getBaseUrl() . 'razerpay_payment/checkout/notify');

        return parent::_getElementHtml($element);
    }
}
