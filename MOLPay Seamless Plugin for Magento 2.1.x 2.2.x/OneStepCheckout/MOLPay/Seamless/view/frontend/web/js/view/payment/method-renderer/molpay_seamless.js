/**
* Copyright © 2015 Magento. All rights reserved.
* See COPYING.txt for license details.
*/
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/action/place-order'

    ],
    function ( $, Component, additionalValidators, fullScreenLoader, quote, customer, placeOrderAction){

            var payloadmps;

            payloadmps = {
                cartId: quote.getQuoteId(),
                billingAddress: quote.billingAddress()                
            };

            $(document).on('change', "#payment_options_molpayseamless", function() {
                if( $("#mod_msg").length > 0 ) $("#mod_msg").remove();
                $('input[name="payment_options"]').val($(this).val());
            });
            
        return Component.extend({
            defaults: {
                template: 'MOLPay_Seamless/payment/form'
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
                var customerEmail = '';
                if(quote.guestEmail) customerEmail = quote.guestEmail;
                else customerEmail = window.checkoutConfig.customerData.email;
                return customerEmail;
            },
            
            getCurrentCustomerCountryId: function() {
                return quote.billingAddress().countryId;
            },
            
            getActiveChannels: function(){
                return window.checkoutConfig.payment.molpay_seamless.channels_payment;            
            },

            placeOrder: function(){  
                var paymentOptionsSelect = $("#payment_options_molpayseamless").val();
                var myForm = $("#seamless");

                if( paymentOptionsSelect != '' ){
                    if (myForm[0].checkValidity()) {
                        myForm.trigger("submit");
                    }
                }
                else{
                     $("#payment_options_molpayseamless")
                         .focus()
                         .select()
                         .after("<div id='mod_msg' style='color:red;'>Please select payment options</div>");
                }
                                
            }         

        });
        
    }
);