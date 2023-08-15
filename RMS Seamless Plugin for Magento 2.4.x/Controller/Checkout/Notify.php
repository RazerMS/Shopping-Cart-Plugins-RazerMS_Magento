<?php

namespace RazerPay\Payment\Controller\Checkout;

use RazerPay\Payment\Exception\SalesOrderPaymentHandledException;
use RazerPay\Payment\Exception\SalesOrderPaymentTransactionExistedException;

class Notify extends \Magento\Framework\App\Action\Action implements
    \Magento\Framework\App\Action\HttpPostActionInterface,
    \Magento\Framework\App\CsrfAwareActionInterface
{
    protected \Magento\Framework\App\RequestInterface $magentoRequest;

    protected \Magento\Sales\Api\OrderManagementInterface $magentoSalesOrderManagement;

    protected \RazerPay\Payment\Domain\DataDomain $paymentDataDomain;

    protected \RazerPay\Payment\Domain\PaymentDomain $paymentDomain;

    protected \RazerPay\Payment\Logger\Logger $paymentLogger;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Api\OrderManagementInterface $magentoSalesOrderManagement,
        \RazerPay\Payment\Domain\DataDomain $paymentDataDomain,
        \RazerPay\Payment\Domain\PaymentDomain $paymentDomain,
        \RazerPay\Payment\Logger\Logger $paymentLogger
    ) {
        parent::__construct($context);

        $this->magentoRequest = $context->getRequest();
        $this->magentoSalesOrderManagement = $magentoSalesOrderManagement;
        $this->paymentDataDomain = $paymentDataDomain;
        $this->paymentDomain = $paymentDomain;
        $this->paymentLogger = $paymentLogger;
    }

    public function execute()
    {
        $this->paymentLogger->info('[notify] http', [
            'request' => $this->magentoRequest->toString(),
        ]);

        $this->paymentLogger->info('[notify] params', $this->magentoRequest->getParams());

        $this->paymentDomain->returnIpn($this->magentoRequest->getParams());

        $paymentResponse = $this->paymentDomain->normalizePaymentResponse($this->magentoRequest->getParams());

        $salesOrder = $this->paymentDataDomain->getSalesOrderByIncrementId($paymentResponse['order_id']);
        if (empty($salesOrder)) {
            $this->handleEmptySaleOrderReturn($paymentResponse);
        }

        $paymentResponseSignature = $this->paymentDomain->generatePaymentResponseSignature($paymentResponse);
        if ($paymentResponseSignature !== $paymentResponse['skey']) {
            $this->handleSignatureMismatchReturn($paymentResponseSignature, $paymentResponse);

            return;
        }

        if ($paymentResponse['status'] === \RazerPay\Payment\Gateway\Config\Config::PAYMENT_STATUS_FAIL) {
            $this->handleFailReturn($salesOrder, $paymentResponse);

            return;
        }

        if ($paymentResponse['status'] === \RazerPay\Payment\Gateway\Config\Config::PAYMENT_STATUS_PENDING) {
            $this->handlePendingReturn($paymentResponse);

            return;
        }

        if ($paymentResponse['status'] === \RazerPay\Payment\Gateway\Config\Config::PAYMENT_STATUS_SUCCESS) {
            $this->handleSuccessReturn($salesOrder, $paymentResponse);

            return;
        }

        $this->handleUnknownStatusReturn($paymentResponse);
    }

    protected function handleEmptySaleOrderReturn(
        array $paymentResponse
    ) {
        $this->paymentLogger->info('[notify] order not found', [
            'response' => $paymentResponse,
        ]);
    }

    protected function handleSignatureMismatchReturn(
        string $paymentResponseSignature,
        array $paymentResponse
    ): void {
        $this->paymentLogger->info('[notify] signature mismatch', [
            'signature' => $paymentResponseSignature,
            'response' => $paymentResponse,
        ]);
    }

    protected function handleFailReturn(
        \Magento\Sales\Model\Order $salesOrder,
        array $paymentResponse
    ): void {
        $isSalesOrderCancelled = $this->magentoSalesOrderManagement->cancel($salesOrder->getId());

        $this->paymentLogger->error("[notify] fail", [
            'quote' => $salesOrder->getQuoteId(),
            'order' => $salesOrder->getIncrementId(),
            'cancelled' => $isSalesOrderCancelled ? 'yes' : 'no',
            'response' => $paymentResponse,
        ]);
    }

    protected function handlePendingReturn(
        array $paymentResponse
    ): void {
        $this->paymentLogger->info('[notify] pending', [
            'response' => $paymentResponse,
        ]);
    }

    protected function handleSuccessReturn(
        \Magento\Sales\Model\Order $salesOrder,
        array $paymentResponse
    ): void {
        try {
            $salesOrderInvoice = $this->paymentDomain->handleSuccessPaymentResponse($salesOrder, $paymentResponse['tran_id']);
        } catch (SalesOrderPaymentTransactionExistedException $exception) {
            $this->paymentLogger->info('[notify] payment existed', [
                'order' => $salesOrder->getIncrementId(),
                'response' => $paymentResponse,
            ]);

            return;
        } catch (SalesOrderPaymentHandledException $exception) {
            $this->paymentLogger->info('[notify] payment handled', [
                'order' => $salesOrder->getIncrementId(),
                'response' => $paymentResponse,
            ]);

            return;
        }

        $this->paymentLogger->info('[notify] success', array_filter([
            'order' => $salesOrder->getIncrementId(),
            'invoice' => $salesOrderInvoice ? $salesOrderInvoice->getIncrementId() : null,
            'response' => $paymentResponse,
        ]));
    }

    protected function handleUnknownStatusReturn(
        array $paymentResponse
    ) {
        $this->paymentLogger->info('[notify] unknown status', [
            'response' => $paymentResponse,
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
