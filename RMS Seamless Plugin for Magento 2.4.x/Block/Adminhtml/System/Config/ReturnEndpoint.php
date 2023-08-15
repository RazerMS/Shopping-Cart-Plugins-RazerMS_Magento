<?php

namespace RazerPay\Payment\Block\Adminhtml\System\Config;

class ReturnEndpoint extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        $element->setReadonly('readonly', true);
        $element->setValue($this->getBaseUrl() . 'razerpay_payment/checkout/return');

        return parent::_getElementHtml($element);
    }
}
