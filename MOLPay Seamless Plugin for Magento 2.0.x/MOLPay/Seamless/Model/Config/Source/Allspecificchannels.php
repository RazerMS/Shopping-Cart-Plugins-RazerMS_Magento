<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Used in creating options for Yes|No config value selection
 *
 */
namespace MOLPay\Seamless\Model\Config\Source;

class Allspecificchannels implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
			['value' => 'affinonline', 'label' => __('Affin Bank')], 
			['value' => 'amb', 'label' => __('Am Bank (Am Online')], 
			['value' => 'bankislam', 'label' => __('Bank Islam')], 
			['value' => 'cimbclicks', 'label' => __('CIMB Bank (CIMBClicks')], 
			['value' => 'hlb', 'label' => __('Hong Leong Bank (HLB Connect')], 
			['value' => 'maybank2u', 'label' => __('Maybank (Maybank2u)')], 
			['value' => 'pbb', 'label' => __('PublicBank (PBB Online')], 
			['value' => 'rhb', 'label' => __('RHB Bank (RHB Now)')], 
			['value' => 'fpx', 'label' => __('MyClear FPX')], 
			['value' => 'fpx_amb', 'label' => __('FPX Am Bank')], 
			['value' => 'credit', 'label' => __('Credit Card/ Debit Card')]
		];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
			'affinonline' 	=> __('Affin Bank'), 
			'amb'	 		=> __('Am Bank (Am Online'), 
			'bankislam' 	=> __('Bank Islam'), 
			'cimbclicks'	=> __('CIMB Bank (CIMBClicks'), 
			'hlb'			=> __('Hong Leong Bank (HLB Connect)'), 
			'maybank2u'		=> __('Maybank (Maybank2u)'), 
			'pbb'			=> __('PublicBank (PBB Online)'), 
			'rhb'			=> __('RHB Bank (RHB Now)'), 
			'fpx'			=> __('MyClear FPX'), 
			'fpx_amb'		=> __('FPX Am Bank'), 
			'credit'		=> __('Credit Card/ Debit Card')
		];
    }
}

