<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MOLPay\Seamless\Model\Source;

class Installment implements \Magento\Framework\Option\ArrayInterface
{
     /**
     * Returns array to be used in multiselect on back-end
     *
     * @return array
     */
    public function toOptionArray()
    {
 	$option = [];
        $option[] = [ 'value' => "install_mbbezpay_3" , 'label' => "Maybank 0% EzyPay Installment (3 months)" ];
        $option[] = [ 'value' => "install_mbbezpay_6" , 'label' => "Maybank 0% EzyPay Installment (6 months)" ];
        $option[] = [ 'value' => "install_mbbezpay_12" , 'label' => "Maybank 0% EzyPay Installment (12 months)" ];
        $option[] = [ 'value' => "install_mbbezpay_24" , 'label' => "Maybank 0% EzyPay Installment (24 months)" ];
		
	return $option;
    }
    
    
    /*
     * Get options in "key-value" format
      * @return array
       */
       public function toArray()
       {
           $choose = [
            "install_mbbezpay_3"  => "Maybank 0% EzyPay Installment (3 months)",
            "install_mbbezpay_6"  => "Maybank 0% EzyPay Installment (6 months)",
            "install_mbbezpay_12" => "Maybank 0% EzyPay Installment (12 months)",
            "install_mbbezpay_24" => "Maybank 0% EzyPay Installment (24 months)" 
           ];
           
           return $choose;
       }
}