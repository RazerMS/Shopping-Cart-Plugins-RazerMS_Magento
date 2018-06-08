<?php
 
namespace MOLPaySandbox\Seamless\Helper;
 
 
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const MER_GATE_ID = 'payment/molpaysandbox_seamless/mpsb_merchant_gateway_id';
    const MER_GATE_KEY = 'payment/molpaysandbox_seamless/mpsb_merchant_gateway_key';
    const MER_GATE_SECRETKEY ='payment/molpaysandbox_seamless/mpsb_merchant_gateway_secretkey';
    const MOLPAY_CHANNELS ='payment/molpaysandbox_seamless/mpsb_channels_payment';
 
    public function getMerchantID()
    {
        return $this->scopeConfig->getValue(
            self::MER_GATE_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getVerifyKey()
    {
        return $this->scopeConfig->getValue(
            self::MER_GATE_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSecretKey()
    {
        return $this->scopeConfig->getValue(
            self::MER_GATE_SECRETKEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    public function getActiveChannels(){
        return $this->scopeConfig->getValue(
            self::MOLPAY_CHANNELS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
}