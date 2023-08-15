define(
  [
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
  ],
  function (
    MagentoUiComponent,
    MagentoCheckoutPaymentRendererList
  ) {
    'use strict';
    MagentoCheckoutPaymentRendererList.push(
      {
        type: 'razerpay_payment',
        component: 'RazerPay_Payment/js/view/payment/method-renderer/razerpay-payment'
      }
    );

    return MagentoUiComponent.extend({});
  }
);
