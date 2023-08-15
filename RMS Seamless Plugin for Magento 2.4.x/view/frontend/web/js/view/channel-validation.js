define([
  'uiComponent',
  'Magento_Checkout/js/model/payment/additional-validators',
  'RazerPay_Payment/js/model/channel-validator'
], function (
  MagentoComponent,
  MagentoCheckoutPaymentAdditionalValidators,
  RazerPayPaymentChannelValidator
) {
  MagentoCheckoutPaymentAdditionalValidators.registerValidator(RazerPayPaymentChannelValidator)

  return MagentoComponent.extend({})
})
