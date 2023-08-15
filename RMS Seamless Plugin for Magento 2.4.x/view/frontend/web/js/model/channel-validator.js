define([
  'jquery',
  'mage/translate',
  'Magento_Ui/js/model/messageList',
  'Magento_Checkout/js/model/quote'
], function (
  $,
  $t,
  MagentoMessageList,
  MagentoCheckoutQuote
) {
  'use strict'
  return {
    validate () {
      if (MagentoCheckoutQuote.paymentMethod() && MagentoCheckoutQuote.paymentMethod().method !== 'razerpay_payment') {
        return true
      }

      const hasSelectedChannel = !!jQuery(':radio[name="razerpay_payment_channel_code"]:checked').length
      if (hasSelectedChannel) {
        return true
      }

      MagentoMessageList.addErrorMessage({
        message: $t('Please select your preferred RazerPay payment channel before placing the order.')
      })

      return false
    }
  }
})