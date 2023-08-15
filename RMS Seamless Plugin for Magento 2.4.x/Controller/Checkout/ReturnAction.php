<?php

namespace RazerPay\Payment\Controller\Checkout;

use RazerPay\Payment\Exception\SalesOrderPaymentHandledException;
use RazerPay\Payment\Exception\SalesOrderPaymentTransactionExistedException;

class ReturnAction extends \Magento\Framework\App\Action\Action implements
    \Magento\Framework\App\Action\HttpPostActionInterface,
    \Magento\Framework\App\CsrfAwareActionInterface
{
    protected \Magento\Framework\App\RequestInterface $magentoRequest;

    protected \Magento\Framework\App\ResponseInterface $magentoResponse;

    protected \Magento\Framework\App\Response\RedirectInterface $magentoResponseRedirect;

    protected \Magento\Framework\Message\ManagerInterface $magentoMessageManager;

    protected \Magento\Framework\Api\SearchCriteriaBuilder $magentoApiSearchCriteriaBuilder;

    protected \Magento\Framework\DB\TransactionFactory $magentoDbTransactionFactory;

    protected \Magento\Checkout\Model\Session $magentoCheckoutSession;

    protected \Magento\Sales\Api\OrderRepositoryInterface $magentoSalesOrderRepository;

    protected \Magento\Sales\Api\OrderManagementInterface $magentoSalesOrderManagement;

    protected \RazerPay\Payment\Domain\DataDomain $paymentDataDomain;

    protected \RazerPay\Payment\Domain\PaymentDomain $paymentDomain;

    protected \RazerPay\Payment\Logger\Logger $paymentLogger;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $magentoCheckoutSession,
        \Magento\Sales\Api\OrderRepositoryInterface $magentoSalesOrderRepository,
        \Magento\Sales\Api\OrderManagementInterface $magentoSalesOrderManagement,
        \RazerPay\Payment\Domain\DataDomain $paymentDataDomain,
        \RazerPay\Payment\Domain\PaymentDomain $paymentDomain,
        \RazerPay\Payment\Logger\Logger $paymentLogger
    ) {
        parent::__construct($context);

        $this->magentoRequest = $context->getRequest();
        $this->magentoResponse = $context->getResponse();
        $this->magentoResponseRedirect = $context->getRedirect();
        $this->magentoMessageManager = $context->getMessageManager();
        $this->magentoCheckoutSession = $magentoCheckoutSession;
        $this->magentoSalesOrderManagement = $magentoSalesOrderManagement;
        $this->paymentDataDomain = $paymentDataDomain;
        $this->paymentDomain = $paymentDomain;
        $this->paymentLogger = $paymentLogger;
    }

    public function execute()
    {
        $this->paymentLogger->info('[return] http', [
            'request' => $this->magentoRequest->toString(),
        ]);

        $this->paymentLogger->info('[return] params', $this->magentoRequest->getParams());

        $this->paymentDomain->returnIpn($this->magentoRequest->getParams());

        $paymentResponse = $this->paymentDomain->normalizePaymentResponse($this->magentoRequest->getParams());

        $salesOrder = $this->magentoCheckoutSession->getLastRealOrder();
//        $salesOrder = $this->paymentDataDomain->getSalesOrderByIncrementId($paymentResponse['order_id']);
        if ($salesOrder->getIncrementId() !== $paymentResponse['order_id']) {
            $this->handleCheckoutSessionMismatchReturn($salesOrder, $paymentResponse);

            return;
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

    protected function handleCheckoutSessionMismatchReturn(
        \Magento\Sales\Model\Order $salesOrder,
        array $paymentResponse
    ): void {
        $this->paymentLogger->info('[return] order session mismatch', [
            'order' => $salesOrder->getIncrementId(),
            'response' => $paymentResponse,
        ]);

        $this->redirectToCheckoutCartPage(__("The order #{$paymentResponse['order_id']} session is not longer exist."));
    }

    protected function handleSignatureMismatchReturn(
        string $paymentResponseSignature,
        array $paymentResponse
    ): void {
        $this->paymentLogger->info('[return] signature mismatch', [
            'signature' => $paymentResponseSignature,
            'response' => $paymentResponse,
        ]);

        $this->redirectToCheckoutSuccessPage(__('We have received and will process your order once payment is confirmed.'));
    }

    protected function handleFailReturn(
        \Magento\Sales\Model\Order $salesOrder,
        array $paymentResponse
    ): void {
        $isQuoteRestored = $this->magentoCheckoutSession->restoreQuote();

        $isSalesOrderCancelled = $this->magentoSalesOrderManagement->cancel($salesOrder->getId());

        $this->paymentLogger->error("[return] fail", [
            'quote' => $salesOrder->getQuoteId(),
            'restored' => $isQuoteRestored ? 'yes' : 'no',
            'order' => $salesOrder->getIncrementId(),
            'cancelled' => $isSalesOrderCancelled ? 'yes' : 'no',
            'response' => $paymentResponse,
        ]);

        $this->redirectToCheckoutCartPage(sprintf(
            __('Payment failed: [%s] %s'),
            $paymentResponse['error_code'],
            $paymentResponse['error_desc']
        ));
    }

    protected function handlePendingReturn(
        array $paymentResponse
    ): void {
        $this->paymentLogger->info('[return] pending', [
            'response' => $paymentResponse,
        ]);

        $this->redirectToCheckoutSuccessPage(__('We have received and will process your order once payment is confirmed.'));
    }

    protected function handleSuccessReturn(
        \Magento\Sales\Model\Order $salesOrder,
        array $paymentResponse
    ): void {
        try {
            $salesOrderInvoice = $this->paymentDomain->handleSuccessPaymentResponse($salesOrder, $paymentResponse['tran_id']);
        } catch (SalesOrderPaymentTransactionExistedException $exception) {
            $this->paymentLogger->info('[return] payment existed', [
                'order' => $salesOrder->getIncrementId(),
                'response' => $paymentResponse,
            ]);

            $this->redirectToCheckoutSuccessPage();

            return;
        } catch (SalesOrderPaymentHandledException $exception) {
            $this->paymentLogger->info('[return] payment handled', [
                'order' => $salesOrder->getIncrementId(),
                'response' => $paymentResponse,
            ]);

            $this->redirectToCheckoutSuccessPage();

            return;
        }

        $this->paymentLogger->info('[return] success', array_filter([
            'order' => $salesOrder->getIncrementId(),
            'invoice' => $salesOrderInvoice ? $salesOrderInvoice->getIncrementId() : null,
            'response' => $paymentResponse,
        ]));

        $this->redirectToCheckoutSuccessPage();
    }

    protected function handleUnknownStatusReturn(
        array $paymentResponse
    ) {
        $this->paymentLogger->info('[return] unknown status', [
            'response' => $paymentResponse,
        ]);

        $this->redirectToCheckoutCartPage();
    }

    protected function redirectToCheckoutSuccessPage(
        string $message = null
    ): void {
        if ($message) {
            $this->magentoMessageManager->addSuccessMessage($message);
        }

        $this->magentoResponseRedirect->redirect($this->magentoResponse, 'checkout/onepage/success');
    }

    protected function redirectToCheckoutCartPage(
        string $message = null
    ): void {
        if ($message) {
            $this->magentoMessageManager->addErrorMessage($message);
        }

        $this->magentoResponseRedirect->redirect($this->magentoResponse, 'checkout/cart');
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
