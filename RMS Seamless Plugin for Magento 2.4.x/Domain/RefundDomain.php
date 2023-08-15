<?php

namespace RazerPay\Payment\Domain;

class RefundDomain
{
    protected \Magento\Sales\Model\OrderRepository $salesOrderRepository;

    protected \RazerPay\Payment\Gateway\Config\Config $paymentGatewayConfig;

    public function __construct(
        \Magento\Sales\Model\OrderRepository $salesOrderRepository,
        \RazerPay\Payment\Gateway\Config\Config $paymentGatewayConfig
    ) {
        $this->salesOrderRepository = $salesOrderRepository;
        $this->paymentGatewayConfig = $paymentGatewayConfig;
    }

    public function normalizeRefundResponse(
        array $params
    ): array {
        return [
            'refund_type' => $params['RefundType'],
            'merchant_id' => $params['MerchantID'],
            'ref_id' => $params['RefID'],
            'refund_id' => $params['RefundID'],
            'refund_fee' => $params['RefundFee'],
            'txn_id' => $params['TxnID'],
            'amount' => $params['Amount'],
            'status' => $params['Status'],
            'signature' => $params['Signature'],
        ];
    }

    public function generateRefundResponseSignature(
        array $refundResponse
    ): string {
        return md5(
            $refundResponse['refund_type'].
            $refundResponse['merchant_id'].
            $refundResponse['ref_id'].
            $refundResponse['refund_id'].
            $refundResponse['txn_id'].
            $refundResponse['amount'].
            $refundResponse['status'].
            $this->paymentGatewayConfig->getSecretKey()
        );
    }

    public function markSalesOrderRefundPending(
        \Magento\Sales\Model\Order $salesOrder,
        string $paymentTransactionId
    ): \Magento\Sales\Model\Order {
        $salesOrder->setData('razerpay_payment_refund_status', 'pending');
        $salesOrder->addCommentToStatusHistory("RazerPay transaction #{$paymentTransactionId} refund is pending.");

        $this->salesOrderRepository->save($salesOrder);

        return $salesOrder;
    }

    public function markSalesOrderRefundRejected(
        \Magento\Sales\Model\Order $salesOrder,
        string $paymentTransactionId
    ): \Magento\Sales\Model\Order {
        $salesOrder->setData('razerpay_payment_refund_status', 'rejected');
        $salesOrder->addCommentToStatusHistory("RazerPay transaction #{$paymentTransactionId} refund is rejected.");

        $this->salesOrderRepository->save($salesOrder);

        return $salesOrder;
    }

    public function markSalesOrderRefundSuccess(
        \Magento\Sales\Model\Order $salesOrder,
        string $paymentTransactionId
    ): \Magento\Sales\Model\Order {
        $salesOrder->setData('razerpay_payment_refund_status', 'success');

        $salesOrder->addCommentToStatusHistory("RazerPay transaction #{$paymentTransactionId} refund is success.");

        $this->salesOrderRepository->save($salesOrder);

        return $salesOrder;
    }
}
