<?php
/**
 * MOLPay Sdn. Bhd.
 *
 * @package     MOLPay Magento Seamless Plugin
 * @author      netbuilder <code@netbuilder.com.my>
 * @copyright   Copyright (c) 2012 - 2014, MOLPaySeamless
 * @link        http://molpay.com
 * @since       Version 1.8.x.x
 * @update      MOLPay <technical@molpay.com>
 * @filesource  https://github.com/MOLPay/Magento_Seamless_Plugin
*/

class Mage_MOLPaySeamless_Block_PaymentMethod_Form extends Mage_Payment_Block_Form {
    
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('MOLPaySeamless/form/payment.phtml');
    }
}
