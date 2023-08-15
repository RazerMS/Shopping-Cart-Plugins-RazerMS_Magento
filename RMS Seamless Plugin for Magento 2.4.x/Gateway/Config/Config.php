<?php

namespace RazerPay\Payment\Gateway\Config;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    public const KEY_MERCHANT_ID = 'merchant_id';
    public const KEY_VERIFY_KEY = 'verify_key';
    public const KEY_SECRET_KEY = 'secret_key';
    public const KEY_ACCOUNT_TYPE = 'account_type';
    public const KEY_CHANNELS = 'channels';
    public const KEY_CREDIT_CHANNEL_TRANSACTION_TYPE = 'credit_channel_transaction_type';

    public const ACCOUNT_TYPE_PRODUCTION = 'production';
    public const ACCOUNT_TYPE_SANDBOX = 'sandbox';

    public const CREDIT_CHANNEL_TYPE_CODE = 'CC';
    public const CREDIT_CHANNEL_TRANSACTION_TYPE_SALS = 'SALS';
    public const CREDIT_CHANNEL_TRANSACTION_TYPE_AUTH = 'AUTH';

    public const PRODUCTION_PAYMENT_BASE_URL = 'https://pay.merchant.razer.com/RMS/pay/';
    public const SANDBOX_PAYMENT_BASE_URL = 'https://sandbox.merchant.razer.com/RMS/pay/';

    public const PRODUCTION_INQUIRY_URL = 'https://api.merchant.razer.com/';
    public const SANDBOX_INQUIRY_URL = 'https://sandbox.merchant.razer.com/';

    public const PRODUCTION_SEAMLESS_JS_URL = 'https://pay.merchant.razer.com/RMS/API/seamless/3.28/js/MOLPay_seamless.deco.js';
    public const SANDBOX_SEAMLESS_JS_URL = 'https://sandbox.merchant.razer.com/RMS/API/seamless/3.28/js/MOLPay_seamless.deco.js';

    public const PAYMENT_STATUS_SUCCESS = '00';
    public const PAYMENT_STATUS_FAIL = '11';
    public const PAYMENT_STATUS_PENDING = '22';

    public const REFUND_STATUS_SUCCESS = '00';
    public const REFUND_STATUS_REJECTED = '11';
    public const REFUND_STATUS_PENDING = '22';

    protected \Magento\Framework\Encryption\EncryptorInterface $magentoEncryptor;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $magentoEncryptor,
        $methodCode = null,
        $pathPattern = \Magento\Payment\Gateway\Config\Config::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);

        $this->magentoEncryptor = $magentoEncryptor;
    }

    public function getMerchantId(): ?string
    {
        return $this->getValue(self::KEY_MERCHANT_ID);
    }

    public function getVerifyKey(): ?string
    {
        return $this->magentoEncryptor->decrypt($this->getValue(self::KEY_VERIFY_KEY));
    }

    public function getSecretKey(): ?string
    {
        return $this->magentoEncryptor->decrypt($this->getValue(self::KEY_SECRET_KEY));
    }

    public function getAccountType(): ?string
    {
        return $this->getValue(self::KEY_ACCOUNT_TYPE);
    }

    public function getEnabledChannelCodes(): array
    {
        return explode(',', $this->getValue(self::KEY_CHANNELS) ?: '');
    }

    public function getCreditChannelTransactionType(): ?string
    {
        return $this->getValue(self::KEY_CREDIT_CHANNEL_TRANSACTION_TYPE);
    }

    public function isProduction(): bool
    {
        return $this->getAccountType() === self::ACCOUNT_TYPE_PRODUCTION;
    }

    public function isSandbox(): bool
    {
        return $this->getAccountType() === self::ACCOUNT_TYPE_SANDBOX;
    }

    public function getPaymentUrl(): string
    {
        if ($this->isProduction()) {
            return self::PRODUCTION_PAYMENT_BASE_URL.$this->getMerchantId();
        }

        if ($this->isSandbox()) {
            return self::SANDBOX_PAYMENT_BASE_URL.$this->getMerchantId();
        }

        throw new \Exception("Unknown account type [{$this->getAccountType()}].");
    }

    public function getInquiryUrl(): string
    {
        if ($this->isProduction()) {
            return self::PRODUCTION_INQUIRY_URL;
        }

        if ($this->isSandbox()) {
            return self::SANDBOX_INQUIRY_URL;
        }

        throw new \Exception("Unknown account type [{$this->getAccountType()}].");
    }

    public function getSeamlessJsUrl(): string
    {
        if ($this->isProduction()) {
            return self::PRODUCTION_SEAMLESS_JS_URL;
        }

        if ($this->isSandbox()) {
            return self::SANDBOX_SEAMLESS_JS_URL;
        }

        throw new \Exception("Unknown account type [{$this->getAccountType()}].");
    }

    public function isMerchantInfoFilled(): bool
    {
        return $this->getMerchantId() && $this->getVerifyKey() && $this->getSecretKey();
    }
}
