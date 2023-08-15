<?php

namespace RazerPay\Payment\Logger\Handler;

class Base extends \Magento\Framework\Logger\Handler\Base
{
    public function __construct(
        \Magento\Framework\Filesystem\DriverInterface $filesystem
    ) {
        $date = date('Y-m-d');

        $fileName = "/var/log/razerpay-payment-{$date}.log";

        parent::__construct($filesystem, null, $fileName);
    }
}
