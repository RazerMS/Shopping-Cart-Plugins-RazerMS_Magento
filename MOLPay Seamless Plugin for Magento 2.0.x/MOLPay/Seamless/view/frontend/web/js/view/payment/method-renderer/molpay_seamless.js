/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [	
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'molpayseamlessdeco'                                       
    ],
    function (Component,quote,customer,ms) {
        'use strict';

        var payloadmps;

            payloadmps = {
                cartId: quote.getQuoteId(),
                billingAddress: quote.billingAddress()
            };

            if (customer.isLoggedIn()) {
              //nothing todo
            } else {
                payloadmps.email = quote.guestEmail;
            }

        
        return Component.extend({
            defaults: {
                template: 'MOLPay_Seamless/payment/form',
                transactionResult: ''
            },

            initObservable: function () {

                this._super()
                    .observe([
                        'transactionResult'
                    ]);
                return this;
            },

            getCode: function() {
                return 'molpay_seamless';
            },

	    getData: function() {
                return {
                    'method': this.item.method
                };
            },

            getCurrentCartId: function(){
                return payloadmps.cartId;
            },

            getCurrentCustomerEmail: function(){
                return payloadmps.email;
            },

            getActiveChannels: function(){
                return window.checkoutConfig.payment.molpay_seamless.channels_payment;
            },

            getActiveInstallment: function(){
                return window.checkoutConfig.payment.molpay_seamless.installment_payment;
                //return "test123";
            },
                                                                
            
            
        });
    }
);