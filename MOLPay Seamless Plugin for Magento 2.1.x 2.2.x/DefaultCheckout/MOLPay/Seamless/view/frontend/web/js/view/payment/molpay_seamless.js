/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'molpayseamlessdeco',
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        $,
        ms,
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'molpay_seamless',
                component: 'MOLPay_Seamless/js/view/payment/method-renderer/molpay_seamless'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
