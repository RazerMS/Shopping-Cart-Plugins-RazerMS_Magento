/**
 * Copyright © 2019 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        
        var payment_method_select = 'molpay_seamless';
        
        var config = window.checkoutConfig.payment,
            molpayType = payment_method_select;
        
        if ( config[molpayType].sandbox_environment == "1" ) {
            rendererList.push(
                {
                    type: payment_method_select,
                    component: 'MOLPay_Seamless/js/view/payment/method-renderer/molpay_seamless_sandbox'
                }
            );
        }

        if ( config[molpayType].sandbox_environment == "0" ) {
            rendererList.push(
                {
                    type: payment_method_select,
                    component: 'MOLPay_Seamless/js/view/payment/method-renderer/molpay_seamless'
                }
            );
        }
        
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
