<?php

namespace RazerPay\Payment\Controller\Checkout;

use RazerPay\Payment\Exception\SalesOrderPaymentHandledException;
use RazerPay\Payment\Exception\SalesOrderPaymentTransactionExistedException;

class Callback extends \Magento\Framework\App\Action\Action implements
    \Magento\Framework\App\Action\HttpPostActionInterface,
    \Magento\Framework\App\CsrfAwareActionInterface
{
    protected const SCOPE_PAYMENT = 'payment';
    protected const SCOPE_REFUND = 'refund';

    /**
     * @var \Magento\Framework\App\Request\Http|\Magento\Framework\App\RequestInterface
     */
    protected \Magento\Framework\App\RequestInterface $magentoRequest;

    protected \Magento\Sales\Api\OrderManagementInterface $magentoSalesOrderManagement;

    protected \RazerPay\Payment\Domain\DataDomain $paymentDataDomain;

    protected \RazerPay\Payment\Domain\PaymentDomain $paymentDomain;

    protected \RazerPay\Payment\Domain\RefundDomain $paymentRefundDomain;

    protected \RazerPay\Payment\Logger\Logger $paymentLogger;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Api\OrderManagementInterface $magentoSalesOrderManagement,
        \RazerPay\Payment\Domain\DataDomain $paymentDataDomain,
        \RazerPay\Payment\Domain\PaymentDomain $paymentDomain,
        \RazerPay\Payment\Domain\RefundDomain $paymentRefundDomain,
        \RazerPay\Payment\Logger\Logger $paymentLogger
    ) {
        parent::__construct($context);

        $this->magentoRequest = $context->getRequest();
        $this->magentoSalesOrderManagement = $magentoSalesOrderManagement;
        $this->paymentDataDomain = $paymentDataDomain;
        $this->paymentDomain = $paymentDomain;
        $this->paymentRefundDomain = $paymentRefundDomain;
        $this->paymentLogger = $paymentLogger;
    }

    public function execute()
    {
        $this->paymentLogger->info('[callback] http', [
            'request' => $this->magentoRequest->toString(),
        ]);

        if ($this->checkIfRefundCallback()) {
            $this->handleRefundReturn(json_decode($this->magentoRequest->getContent(), true));
            return;
        }

        $this->paymentLogger->info('[callback][payment] params', $this->magentoRequest->getParams());

        if ($this->magentoRequest->getParam('nbcb') === '1') {
            echo 'CBTOKEN:MPSTATOK';
        } else {
            $this->paymentDomain->returnIpn($this->magentoRequest->getParams());
        }

        $paymentResponse = $this->paymentDomain->normalizePaymentResponse($this->magentoRequest->getParams());

        $salesOrder = $this->paymentDataDomain->getSalesOrderByIncrementId($paymentResponse['order_id']);
        if (empty($salesOrder)) {
            $this->handleEmptySaleOrderReturn($paymentResponse, static::SCOPE_PAYMENT);
        }

        $paymentResponseSignature = $this->paymentDomain->generatePaymentResponseSignature($paymentResponse);
        if ($paymentResponseSignature !== $paymentResponse['skey']) {
            $this->handleSignatureMismatchReturn($paymentResponseSignature, $paymentResponse, static::SCOPE_PAYMENT);

            return;
        }

        if ($paymentResponse['status'] === \RazerPay\Payment\Gateway\Config\Config::PAYMENT_STATUS_FAIL) {
            $this->handlePaymentFailReturn($salesOrder, $paymentResponse);

            return;
        }

        if ($paymentResponse['status'] === \RazerPay\Payment\Gateway\Config\Config::PAYMENT_STATUS_PENDING) {
            $this->handlePaymentPendingReturn($paymentResponse);

            return;
        }

        if ($paymentResponse['status'] === \RazerPay\Payment\Gateway\Config\Config::PAYMENT_STATUS_SUCCESS) {
            $this->handlePaymentSuccessReturn($salesOrder, $paymentResponse);

            return;
        }

        $this->handleUnknownStatusReturn($paymentResponse, static::SCOPE_PAYMENT);
    }

    protected function handleEmptySaleOrderReturn(
        array $response,
        string $scope
    ) {
        $this->paymentLogger->info("[callback][{$scope}] order not found", [
            'response' => $response,
        ]);
    }

    protected function handleSignatureMismatchReturn(
        string $signature,
        array $response,
        string $scope
    ): void {
        $this->paymentLogger->info("[callback][{$scope}] signature mismatch", [
            'signature' => $signature,
            'response' => $response,
        ]);
    }

    protected function handlePaymentFailReturn(
        \Magento\Sales\Model\Order $salesOrder,
        array $paymentResponse
    ): void {
        $isSalesOrderCancelled = $this->magentoSalesOrderManagement->cancel($salesOrder->getId());

        $this->paymentLogger->error("[callback][payment] fail", [
            'quote' => $salesOrder->getQuoteId(),
            'order' => $salesOrder->getIncrementId(),
            'cancelled' => $isSalesOrderCancelled ? 'yes' : 'no',
            'response' => $paymentResponse,
        ]);
    }

    protected function handlePaymentPendingReturn(
        array $paymentResponse
    ): void {
        $this->paymentLogger->info('[callback][payment] pending', [
            'response' => $paymentResponse,
        ]);
    }

    protected function handlePaymentSuccessReturn(
        \Magento\Sales\Model\Order $salesOrder,
        array $paymentResponse
    ): void {
        try {
            $salesOrderInvoice = $this->paymentDomain->handleSuccessPaymentResponse($salesOrder, $paymentResponse['tran_id']);
        } catch (SalesOrderPaymentTransactionExistedException $exception) {
            $this->paymentLogger->info('[callback][payment] payment existed', [
                'order' => $salesOrder->getIncrementId(),
                'response' => $paymentResponse,
            ]);

            return;
        } catch (SalesOrderPaymentHandledException $exception) {
            $this->paymentLogger->info('[callback][payment] payment handled', [
                'order' => $salesOrder->getIncrementId(),
                'response' => $paymentResponse,
            ]);

            return;
        }

        $this->paymentLogger->info('[callback][payment] success', array_filter([
            'order' => $salesOrder->getIncrementId(),
            'invoice' => $salesOrderInvoice ? $salesOrderInvoice->getIncrementId() : null,
            'response' => $paymentResponse,
        ]));
    }

    protected function handleUnknownStatusReturn(
        array $paymentResponse,
        string $scope
    ) {
        $this->paymentLogger->info("[callback][{$scope}] unknown status", [
            'response' => $paymentResponse,
        ]);
    }

    protected function checkIfRefundCallback(): bool
    {
        if ($this->magentoRequest->getHeader('Content-Type') !== 'application/json') {
            return false;
        }

        $refundParams = json_decode($this->magentoRequest->getContent(), true);

        $refundParamKeys = [
            'RefundType',
            'MerchantID',
            'RefID',
            'RefundID',
            'RefundFee',
            'TxnID',
            'Amount',
            'Status',
            'Signature',
//            'reason',
//            'FPXTxnID',
        ];

        foreach ($refundParamKeys as $refundParamKey) {
            if (array_key_exists($refundParamKey, $refundParams) === false) {
                return false;
            }
        }

        return true;
    }

    protected function handleRefundReturn(
        array $params
    ) {
        $this->paymentLogger->info('[callback][refund] params', $params);

        $refundResponse = $this->paymentRefundDomain->normalizeRefundResponse($params);

        $salesOrder = $this->paymentDataDomain->getSalesOrderByIncrementId($refundResponse['ref_id']);
        if (empty($salesOrder)) {
            $this->handleEmptySaleOrderReturn($refundResponse, static::SCOPE_REFUND);
        }

        $refundResponseSignature = $this->paymentRefundDomain->generateRefundResponseSignature($refundResponse);
        if ($refundResponseSignature !== $refundResponse['signature']) {
            $this->handleSignatureMismatchReturn($refundResponseSignature, $refundResponse, static::SCOPE_REFUND);
            return;
        }

        if ($refundResponse['status'] === \RazerPay\Payment\Gateway\Config\Config::REFUND_STATUS_REJECTED) {
            $this->handleRefundRejectedReturn($salesOrder, $refundResponse);

            return;
        }

        if ($refundResponse['status'] === \RazerPay\Payment\Gateway\Config\Config::REFUND_STATUS_PENDING) {
            $this->handleRefundPendingReturn($salesOrder, $refundResponse);

            return;
        }

        if ($refundResponse['status'] === \RazerPay\Payment\Gateway\Config\Config::REFUND_STATUS_SUCCESS) {
            $this->handleRefundSuccessReturn($salesOrder, $refundResponse);

            return;
        }

        $this->handleUnknownStatusReturn($refundResponse, static::SCOPE_REFUND);
    }

    protected function handleRefundRejectedReturn(
        \Magento\Sales\Model\Order $salesOrder,
        array $refundResponse
    ): void {
        $this->paymentRefundDomain->markSalesOrderRefundRejected($salesOrder, $refundResponse['txn_id']);

        $this->paymentLogger->error("[callback][refund] rejected", [
            'order' => $salesOrder->getIncrementId(),
            'response' => $refundResponse,
        ]);
    }

    protected function handleRefundPendingReturn(
        \Magento\Sales\Model\Order $salesOrder,
        array $refundResponse
    ): void {
        $this->paymentRefundDomain->markSalesOrderRefundPending($salesOrder, $refundResponse['txn_id']);

        $this->paymentLogger->info('[callback][refund] pending', [
            'order' => $salesOrder->getIncrementId(),
            'response' => $refundResponse,
        ]);
    }

    protected function handleRefundSuccessReturn(
        \Magento\Sales\Model\Order $salesOrder,
        array $refundResponse
    ): void {

        $this->paymentRefundDomain->markSalesOrderRefundSuccess($salesOrder, $refundResponse['txn_id']);

        $this->paymentLogger->info('[callback][refund] success', [
            'order' => $salesOrder->getIncrementId(),
            'response' => $refundResponse,
        ]);
    }

    public function createCsrfValidationException(
        \Magento\Framework\App\RequestInterface $request
    ): ?\Magento\Framework\App\Request\InvalidRequestException {
        return null;
    }

    public function validateForCsrf(
        \Magento\Framework\App\RequestInterface $request
    ): ?bool {
        return true;
    }
}
