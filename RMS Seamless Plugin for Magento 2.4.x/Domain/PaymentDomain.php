<?php

namespace RazerPay\Payment\Domain;

use RazerPay\Payment\Exception\SalesOrderPaymentHandledException;
use RazerPay\Payment\Exception\SalesOrderPaymentTransactionExistedException;

class PaymentDomain
{
    protected \Magento\Framework\App\CacheInterface $magentoCache;

    protected \Magento\Framework\DB\TransactionFactory $magentoDbTransactionFactory;

    protected \Magento\Sales\Model\Order\InvoiceRepository $magentoSalesInvoiceRepository;

    protected \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $magentoSalesInvoiceSender;

    protected \Magento\Sales\Model\Service\InvoiceService $magentoSalesInvoiceService;

    protected \RazerPay\Payment\Domain\DataDomain $paymentDataDomain;

    protected \RazerPay\Payment\Domain\Api\ReturnIpnRequestFactory $paymentReturnIpnRequestFactory;

    protected \RazerPay\Payment\Gateway\Config\Config $paymentGatewayConfig;

    public function __construct(
        \Magento\Framework\App\CacheInterface $magentoCache,
        \Magento\Framework\DB\TransactionFactory $magentoDbTransactionFactory,
        \Magento\Sales\Model\Order\InvoiceRepository $magentoSalesInvoiceRepository,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $magentoSalesInvoiceSender,
        \Magento\Sales\Model\Service\InvoiceService $magentoSalesInvoiceService,
        \RazerPay\Payment\Domain\Api\ReturnIpnRequestFactory $paymentReturnIpnRequestFactory,
        \RazerPay\Payment\Domain\Api\CaptureRequestFactory $paymentCaptureRequestFactory,
        \RazerPay\Payment\Domain\DataDomain $paymentDataDomain,
        \RazerPay\Payment\Gateway\Config\Config $paymentGatewayConfig
    ) {
        $this->magentoCache = $magentoCache;
        $this->magentoDbTransactionFactory = $magentoDbTransactionFactory;
        $this->magentoSalesInvoiceRepository = $magentoSalesInvoiceRepository;
        $this->magentoSalesInvoiceSender = $magentoSalesInvoiceSender;
        $this->magentoSalesInvoiceService = $magentoSalesInvoiceService;
        $this->paymentReturnIpnRequestFactory = $paymentReturnIpnRequestFactory;
        $this->paymentCaputreRequestFactory = $paymentCaptureRequestFactory;
        $this->paymentDataDomain = $paymentDataDomain;
        $this->paymentGatewayConfig = $paymentGatewayConfig;
    }

    public function normalizePaymentResponse(
        array $params
    ): array {
        return [
            'skey' => $params['skey'],
            'pay_date' => $params['paydate'],
            'tran_id' => $params['tranID'],
            'order_id' => $params['orderid'],
            'status' => $params['status'],
            'domain' => $params['domain'],
            'currency' => $params['currency'],
            'amount' => $params['amount'],
            'appcode' => $params['appcode'],
            'error_code' => $params['error_code'],
            'error_desc' => $params['error_desc'],
            'channel' => $params['channel'],
        ];
    }

    public function generatePaymentResponseSignature(
        array $paymentResponse
    ): string {
        $paymentHash = md5(
            $paymentResponse['tran_id'].
            $paymentResponse['order_id'].
            $paymentResponse['status'].
            $paymentResponse['domain'].
            $paymentResponse['amount'].
            $paymentResponse['currency']
        );

        $computedSKey = md5(
            $paymentResponse['pay_date'].
            $paymentResponse['domain'].
            $paymentHash.
            $paymentResponse['appcode'].
            $this->paymentGatewayConfig->getSecretKey()
        );

        return $computedSKey;
    }

    public function returnIpn(
        array $params
    ) {
        try {
            /**
             * @var \RazerPay\Payment\Domain\Api\ReturnIpnRequest $request
             */
            $request = $this->paymentReturnIpnRequestFactory->create([
                'bodyParams' => $params,
            ]);

            $request->send();
        } catch (\Exception $exception) {

        }
    }

    public function handleSuccessPaymentResponse(
        \Magento\Sales\Model\Order $salesOrder,
        string $transactionId
    ): ?\Magento\Sales\Model\Order\Invoice {
        /**
         * @var \Magento\Sales\Model\Order\Payment $salesOrderPayment
         */
        $salesOrderPayment = $salesOrder->getPayment();
        if ($salesOrderPayment->getLastTransId()) {
            throw new SalesOrderPaymentTransactionExistedException();
        }

        $isProcessing = (bool) $this->magentoCache->load("razerpay_payment_processing_{$salesOrder->getIncrementId()}");
        if ($isProcessing) {
            // hold 1 second to give other handler has enough time to finish processing payment
            sleep(1);

            throw new SalesOrderPaymentHandledException();
        }

        $this->magentoCache->save(1, "razerpay_payment_processing_{$salesOrder->getIncrementId()}");

        $dbTransaction = $this->magentoDbTransactionFactory->create();

        $salesOrderPayment->setTransactionId($transactionId);
        $salesOrderPayment->setLastTransId($transactionId);

        $salesOrder->setIsInProcess(true);
        $salesOrder->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $salesOrder->setStatus($salesOrder->getConfig()->getStateDefaultStatus($salesOrder->getState()));
        $salesOrder->addStatusToHistory($salesOrder->getStatus(), "RazerPay transaction #{$salesOrderPayment->getTransactionId()} success.");
        $dbTransaction->addObject($salesOrder);

        $isAuthCreditTransactionType =
            $this->paymentDataDomain->checkChannelIsCC($salesOrderPayment->getAdditionalInformation('channel_code')) &&
            $salesOrderPayment->getAdditionalInformation('credit_channel_transaction_type') === \RazerPay\Payment\Gateway\Config\Config::CREDIT_CHANNEL_TRANSACTION_TYPE_AUTH;

        if ($isAuthCreditTransactionType) {
            $this->handleCreditAuthSuccessPaymentResponse($salesOrder, $salesOrderPayment);
        } else {
            $salesOrderInvoice = $this->handlePaidSuccessPaymentResponse($salesOrder, $salesOrderPayment);
            $dbTransaction->addObject($salesOrderInvoice);
        }

        $dbTransaction->save();

        $this->magentoCache->remove("razerpay_payment_processing_{$salesOrder->getIncrementId()}");

        if (empty($salesOrderInvoice)) {
            return null;
        }

        $this->magentoSalesInvoiceSender->send($salesOrderInvoice);

        return $salesOrderInvoice;
    }

    protected function handlePaidSuccessPaymentResponse(
        \Magento\Sales\Model\Order $salesOrder,
        \Magento\Sales\Model\Order\Payment $salesOrderPayment
    ): \Magento\Sales\Model\Order\Invoice {
        /**
         * @var \Magento\Sales\Model\Order\Invoice $salesOrderInvoice
         */
        $salesOrderInvoice = $this->magentoSalesInvoiceService->prepareInvoice($salesOrder);
        $salesOrderInvoice->setTransactionId($salesOrderPayment->getTransactionId());
        $salesOrderInvoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
        $salesOrderInvoice->register();

        $salesOrderPayment->addTransactionCommentsToOrder(
            $salesOrderPayment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_PAYMENT, $salesOrderInvoice),
            __(
                'Amount of %1 has been paid via %2 (%3) payment.',
                $salesOrder->getOrderCurrency()->formatTxt($salesOrder->getGrandTotal()),
                $salesOrderPayment->getAdditionalInformation('title'),
                $this->paymentDataDomain->getChannelTitle($channelCode = $salesOrderPayment->getAdditionalInformation('channel_code')) ?: $channelCode,
            )
        );

        return $salesOrderInvoice;
    }

    protected function handleCreditAuthSuccessPaymentResponse(
        \Magento\Sales\Model\Order $salesOrder,
        \Magento\Sales\Model\Order\Payment $salesOrderPayment
    ): void {
        $salesOrderPayment->setIsTransactionClosed(false);

        $salesOrderPayment->addTransactionCommentsToOrder(
            $salesOrderPayment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH),
            __(
                'Amount of %1 has been pre-authed via %2 (%3) payment.',
                $salesOrder->getOrderCurrency()->formatTxt($salesOrder->getGrandTotal()),
                $salesOrderPayment->getAdditionalInformation('title'),
                $this->paymentDataDomain->getChannelTitle($channelCode = $salesOrderPayment->getAdditionalInformation('channel_code')) ?: $channelCode,
            )
        );
    }

    public function captureCreditAuthPayment(
        \Magento\Sales\Model\Order\Payment $salesOrderPayment
    ) {
        $salesOrderPaymentAuthorizationTransaction = $salesOrderPayment->getAuthorizationTransaction();

        /**
         * @var \RazerPay\Payment\Domain\Api\CaptureRequest $request
         */
        $request = $this->paymentCaputreRequestFactory->create([
            'bodyParams' => [
                'tran_id' => $salesOrderPaymentAuthorizationTransaction->getTxnId(),
                'ref_id' => $salesOrderPayment->getOrder()->getIncrementId(),
                'amount' => number_format($salesOrderPayment->getAmountOrdered(), 2, '.', ''),
            ],
        ]);

        $response = $request->send();

        if ($response['StatCode'] === '00') {
            return;
        }

        throw new \RazerPay\Payment\Exception\CaptureCreditAuthPaymentException(
            \RazerPay\Payment\Domain\Api\CaptureRequest::ERRORS[$response['StatCode']],
            $response['StatCode']
        );
    }
}
