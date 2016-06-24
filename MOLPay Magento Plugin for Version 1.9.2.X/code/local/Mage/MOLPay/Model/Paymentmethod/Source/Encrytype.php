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

class Mage_MOLPay_Model_PaymentMethod_Source_Encrytype {
    public function toOptionArray() {
        return array(
            array(
                'value' => "md5",
                'label' => Mage::helper('molpay')->__('MD5')
            ),
            array(
                'value' => "sha1",
                'label' => Mage::helper('molpay')->__('SHA1')
            )
        );
    }
}