<?php

namespace RazerPay\Payment\Controller\Checkout;

class Cancel extends \Magento\Framework\App\Action\Action implements
    \Magento\Framework\App\Action\HttpPostActionInterface,
    \Magento\Framework\App\CsrfAwareActionInterface
{
    protected \Magento\Framework\App\RequestInterface $magentoRequest;

    protected \RazerPay\Payment\Logger\Logger $paymentLogger;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \RazerPay\Payment\Logger\Logger $paymentLogger
    ) {
        parent::__construct($context);

        $this->magentoRequest = $context->getRequest();
        $this->paymentLogger = $paymentLogger;
    }

    public function execute()
    {
        $this->paymentLogger->info('[cancel] http', [
            'request' => $this->magentoRequest->toString(),
        ]);

        $this->paymentLogger->info('[cancel] params', $this->magentoRequest->getParams());
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
