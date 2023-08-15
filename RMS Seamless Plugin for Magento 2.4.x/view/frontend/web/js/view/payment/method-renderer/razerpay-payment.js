define([
  'jquery',
  'mage/url',
  'mage/storage',
  'Magento_Checkout/js/view/payment/default',
  'Magento_Checkout/js/action/redirect-on-success',
  'Magento_Checkout/js/model/full-screen-loader'
], function (
  $,
  MagentoUrl,
  MagentoStorage,
  MagentoCheckoutPaymentComponent,
  MagentoCheckoutRedirectOnSuccessAction,
  MagentoFullScreenLoader,
) {
  'use strict'

  return MagentoCheckoutPaymentComponent.extend({
    defaults: {
      template: 'RazerPay_Payment/payment/form'
    },

    redirectAfterPlaceOrder: false,

    initObservable: function () {
      this._super()
        .observe({
          selectedChannelCode: '',
        })

      return this
    },

    /**
     * Get payment method data
     */
    getData: function () {
      return {
        'method': this.item.method,
        'additional_data': {
          'channel_code': this.selectedChannelCode(),
        }
      }
    },

    getInstructions () {
      return window.checkoutConfig.payment.instructions[this.item.method]
    },

    getConfig () {
      return window.checkoutConfig.payment.razerpay_payment
    },

    getChannels () {
      return this.getConfig().channels || []
    },

    async afterPlaceOrder () {
      try {
        MagentoFullScreenLoader.startLoader()

        const response = await MagentoStorage.get('razerpay_payment/checkout/ajaxSeamlessParams')

        const seamlessOptions = response.data

        const $toggler = $('#razerpay-payment-seamless-toggler')
        $toggler.MOLPaySeamless(seamlessOptions)
        $toggler.trigger('click')
      } catch (e) {
        console.log('request seamless error', e)
      } finally {
        MagentoFullScreenLoader.stopLoader()
      }
    },

    loadSeamlessScript () {
      require([
        this.getConfig().seamlessJsUrl
      ], () => {
        console.log('seamless script loaded')
      })
    },
  })
})
