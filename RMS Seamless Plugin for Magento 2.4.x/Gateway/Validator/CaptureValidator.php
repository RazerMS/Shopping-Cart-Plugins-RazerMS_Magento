<?php

namespace RazerPay\Payment\Gateway\Validator;

class CaptureValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
    public function validate(
        array $subject
    ) {
        if ($subject['response'] instanceof \RazerPay\Payment\Exception\CaptureCreditAuthPaymentException) {
            return $this->createResult(false, [
                $subject['response']->getMessage(),
            ]);
        }

        return $this->createResult(true);
    }
}
