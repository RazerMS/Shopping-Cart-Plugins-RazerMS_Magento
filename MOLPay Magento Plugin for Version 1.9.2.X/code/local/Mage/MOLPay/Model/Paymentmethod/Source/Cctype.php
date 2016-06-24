<?php
/**
 * MOLPay Sdn. Bhd.
 *
 * @package     MOLPay Magento Plugin
 * @author      netbuilder <code@netbuilder.com.my>
 * @copyright   Copyright (c) 2012 - 2014, MOLPay
 * @link        http://molpay.com
 * @since       Version 1.8.x.x
 * @update      MOLPay <technical@molpay.com>
 * @filesource  https://github.com/MOLPay/Magento_Plugin
 */

class Mage_MOLPay_Model_PaymentMethod_Source_Cctype extends Mage_Payment_Model_Source_Cctype {
    public function getAllowedTypes() {
        return array('VI', 'MC', 'AE', 'DI', 'OT');
    }
}