<?php

namespace RazerPay\Payment\Controller\Checkout;

class AjaxSeamlessParams extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\Response\HttpInterface
     */
    protected \Magento\Framework\App\ResponseInterface $magentoResponse;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected \Magento\Framework\App\Response\RedirectInterface $magentoResponseRedirect;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected \Magento\Framework\UrlInterface $magentoUrlBuilder;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected \Magento\Checkout\Model\Session $magentoCheckoutSession;

    /**
     * @var \RazerPay\Payment\Domain\DataDomain
     */
    protected \RazerPay\Payment\Domain\DataDomain $paymentDataDomain;

    /**
     * @var \RazerPay\Payment\Gateway\Config\Config
     */
    protected \RazerPay\Payment\Gateway\Config\Config $paymentGatewayConfig;

    /**
     * @var \RazerPay\Payment\Logger\Logger
     */
    protected \RazerPay\Payment\Logger\Logger $paymentLogger;

    /**
     * @param  \Magento\Framework\App\Action\Context  $context
     * @param  \Magento\Checkout\Model\Session  $magentoCheckoutSession
     * @param  \RazerPay\Payment\Domain\DataDomain  $paymentDataDomain
     * @param  \RazerPay\Payment\Gateway\Config\Config  $paymentGatewayConfig
     * @param  \RazerPay\Payment\Logger\Logger  $paymentLogger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $magentoCheckoutSession,
        \RazerPay\Payment\Domain\DataDomain $paymentDataDomain,
        \RazerPay\Payment\Gateway\Config\Config $paymentGatewayConfig,
        \RazerPay\Payment\Logger\Logger $paymentLogger
    ) {
        parent::__construct($context);

        $this->magentoResponse = $context->getResponse();
        $this->magentoResponseRedirect = $context->getRedirect();
        $this->magentoUrlBuilder = $context->getUrl();
        $this->magentoCheckoutSession = $magentoCheckoutSession;
        $this->paymentDataDomain = $paymentDataDomain;
        $this->paymentGatewayConfig = $paymentGatewayConfig;
        $this->paymentLogger = $paymentLogger;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json|void
     */
    public function execute()
    {
        $salesOrder = $this->magentoCheckoutSession->getLastRealOrder();
        if (empty($salesOrder)) {
            $this->redirectToCheckoutCartPage();
            return;
        }

        if ($salesOrder->getState() !== \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT) {
            $this->paymentLogger->info('[params] Unexpected order state', [
                'order' => $salesOrder->getIncrementId(),
                'state' => $salesOrder->getState(),
            ]);

            $this->redirectToCheckoutCartPage();
            return;
        }

        $salesOrderGrantTotalInDecimal = number_format($salesOrder->getGrandTotal(), 2, '.', '');

        $salesOrderItemsWithQuantity = array_map(
            function (\Magento\Sales\Api\Data\OrderItemInterface $salesOrderItem) {
                return sprintf("%s x %d", $salesOrderItem->getName(), $salesOrderItem->getQtyOrdered());
            },
            $salesOrder->getItems()
        );

        $responseBody = [
            'data' => [
                'mpsmerchantid' => $this->paymentGatewayConfig->getMerchantId(),
                'mpschannel' => $salesOrder->getPayment()->getAdditionalInformation('channel_code'),
                'mpsamount' => $salesOrderGrantTotalInDecimal,
                'mpsorderid' => $salesOrder->getIncrementId(),
                'mpsbill_name' => $salesOrder->getBillingAddress()->getFirstname(),
                'mpsbill_email' => $salesOrder->getBillingAddress()->getEmail(),
                'mpsbill_mobile' => $salesOrder->getBillingAddress()->getTelephone(),
                'mpsbill_desc' => implode(', ', $salesOrderItemsWithQuantity),
                'mpscountry' => $salesOrder->getBillingAddress()->getCountryId(),
                'mpscurrency' => $salesOrder->getOrderCurrencyCode(),
                'mpsvcode' => md5(implode('', [
                    $salesOrderGrantTotalInDecimal,
                    $this->paymentGatewayConfig->getMerchantId(),
                    $salesOrder->getIncrementId(),
                    $this->paymentGatewayConfig->getVerifyKey(),
                ])),
                'mpsreturnurl' => $this->magentoUrlBuilder->getUrl('razerpay_payment/checkout/return'),
                'mpsnotifyurl' => $this->magentoUrlBuilder->getUrl('razerpay_payment/checkout/notify'),
                'mpscallbackurl' => $this->magentoUrlBuilder->getUrl('razerpay_payment/checkout/callback'),
                'mpscancelurl' => $this->magentoUrlBuilder->getUrl('razerpay_payment/checkout/cancel'),
            ],
        ];

        if ($this->paymentDataDomain->checkChannelIsCC($responseBody['data']['mpschannel'])) {
            $responseBody['data']['mpstcctype'] = $this->paymentGatewayConfig->getCreditChannelTransactionType();
        }

        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        $result->setHeader('Content-Type', 'application/json');
        $result->setData($responseBody);

        return $result;
    }

    protected function redirectToCheckoutCartPage()
    {
        $this->magentoResponseRedirect->redirect($this->magentoResponse, 'checkout/cart');
    }
}
