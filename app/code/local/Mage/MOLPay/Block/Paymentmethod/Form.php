<?php
/**
 * MOLPay Sdn. Bhd.
 *
 * @package	MOLPay Magento Plugin
 * @author      netbuilder <code@netbuilder.com.my>
 * @copyright	Copyright (c) 2012 - 2014, MOLPay
 * @link	http://molpay.com
 * @since	Version 1.8.x.x
 * @update	MOLPay <technical@molpay.com>
 * @filesource  https://github.com/MOLPay/Magento_Plugin
 */

class Mage_MOLPay_Block_PaymentMethod_Form extends Mage_Payment_Block_Form {
    
    protected function _construct() {   
        parent::_construct();
    }
    
    protected function _toHtml() {  

    $skeleton = Mage::getSingleton("molpay/paymentmethod")->getConfigData('paymentdescription');
    if( $skeleton == "" ) {
        $skeleton = "You will be redirected to MOLPay website when you place an order.<br>
                    Supported Secured Online Payment: <br>
                    <img src='http://molpay.com/home/pic/molpay/molpayhor01.gif' ><br>
                    Supported Banks: <br>
                    <img src='http://molpay.com/home/pic/molpay/molpayhor02.gif' >";
    }
    return "<fieldset class=\"form-list\">
              <ul id=\"payment_form_molpay\" style=\"display: none;\">
               <li>" . $skeleton . "</li>
              </ul>
            </fieldset>";
    }  
}
