<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MOLPay\Seamless\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use MOLPay\Seamless\Gateway\Http\Client\ClientMock;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'molpay_seamless';

    /**
     * Payment ConfigProvider constructor.
     * @param \Magento\Payment\Helper\Data $paymentHelper
     */
    public function __construct(
        \MOLPay\Seamless\Helper\Data $paymentHelper,
        \MOLPay\Seamless\Model\Source\Channel $channelList
    ) {
        $this->method = $paymentHelper;
        $this->channel = $channelList;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'transactionResults' => [
                        ClientMock::SUCCESS => __('Success')
                    ],
		    'channels_payment' => $this->getActiveChannels()
                ]
            ]
        ];
    }


    //Get activated channel
    protected function getActiveChannels()
    {
        $activeConfigChannels = explode(",",$this->method->getActiveChannels());
        $allChannel = $this->channel->toArray();
        
        $activeChannel = [];
        foreach( $allChannel as $k => $v ){
            if( in_array( $k, $activeConfigChannels ) ){
                $activeChannel[] = [ "value" => $k, "label" =>$v ]; 
            }
        }
        return $activeChannel;
    }	
}
