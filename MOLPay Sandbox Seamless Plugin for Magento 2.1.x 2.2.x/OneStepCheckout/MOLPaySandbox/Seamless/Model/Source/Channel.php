<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MOLPaySandbox\Seamless\Model\Source;

class Channel implements \Magento\Framework\Option\ArrayInterface
{
     /**
     * Returns array to be used in multiselect on back-end
     *
     * @return array
     */
    public function toOptionArray()
    {
 		$option = [];
		$option[] = [ 'value' => "affinonline" , 'label' => "Affin Bank(Affin Online)" ];
		$option[] = [ 'value' => "amb" , 'label' => "Am Bank (Am Online)"  ];
		$option[] = [ 'value' => "bankislam" , 'label' => "Bank Islam"  ];
		$option[] = [ 'value' => "cimbclicks" , 'label' => "CIMB Bank(CIMB Clicks)"  ];
		$option[] = [ 'value' => "hlb" , 'label' => "Hong Leong Bank(HLB Connect)"  ];
		$option[] = [ 'value' => "maybank2u" , 'label' => "Maybank(Maybank2u)"  ];
		$option[] = [ 'value' => "pbb" , 'label' => "PublicBank (PBB Online)"  ];
		$option[] = [ 'value' => "rhb" , 'label' => "RHB Bank(RHB Now)"  ];
		$option[] = [ 'value' => "fpx" , 'label' => "MyClear FPX B2C (Maybank2u, CIMB Clicks, HLB Connect, RHB Now, PBB Online, Bank Islam)"  ];
		$option[] = [ 'value' => "fpx_amb" , 'label' => "FPX Am Bank (Am Online)"  ];
		$option[] = [ 'value' => "fpx_bimb" , 'label' => "FPX Bank Islam"  ];
		$option[] = [ 'value' => "fpx_cimbclicks" , 'label' => "FPX CIMB Bank(CIMB Clicks)"  ];
		$option[] = [ 'value' => "fpx_hlb" , 'label' => "FPX Hong Leong Bank(HLB Connect)"  ];
		$option[] = [ 'value' => "fpx_mb2u" , 'label' => "FPX Maybank(Maybank2u)"  ];
		$option[] = [ 'value' => "fpx_pbb" , 'label' => "FPX PublicBank (PBB Online)"  ];
		$option[] = [ 'value' => "fpx_rhb" , 'label' => "FPX RHB Bank(RHB Now)"  ];
		$option[] = [ 'value' => "fpx_abmb" , 'label' => "FPX Alliance Bank (Alliance Online)"  ];
		$option[] = [ 'value' => "fpx_uob" , 'label' => "FPX United Overseas Bank (UOB)"  ];
		$option[] = [ 'value' => "fpx_bsn" , 'label' => "FPX Bank Simpanan Nasional (myBSN)"  ];
		$option[] = [ 'value' => "FPX_OCBC" , 'label' => "FPX OCBC Ban"  ];
		$option[] = [ 'value' => "FPX_SCB" , 'label' => "FPX Standard Chartered Bank"  ];
		$option[] = [ 'value' => "FPX_ABB" , 'label' => "FPX Affin Bank Berha"  ];
		$option[] = [ 'value' => "FPX_B2B" , 'label' => "MyClear FPX B2B (Maybank2e, BizChannel@CIMB, RHB Reflex, BizSmart, Bank Islam"  ];
		$option[] = [ 'value' => "FPX_B2B_ABB" , 'label' => "FPX B2B Affin Bank"  ];
		$option[] = [ 'value' => "FPX_B2B_AMB" , 'label' => "FPX B2B Ambank Berhad"  ];
		$option[] = [ 'value' => "FPX_B2B_BIMB" , 'label' => "FPX B2B Bank Islam Malaysia Berhad (BIMB)"  ];
		$option[] = [ 'value' => "FPX_B2B_CIMB" , 'label' => "FPX B2B BizChannel@CIMB (CIMB)"  ];
		$option[] = [ 'value' => "FPX_B2B_HLB" , 'label' => "FPX B2B HongLeong Connect"  ];
		$option[] = [ 'value' => "FPX_B2B_HSBC" , 'label' => "FPX B2B HSBC"  ];
		$option[] = [ 'value' => "FPX_B2B_PBB" , 'label' => "FPX B2B Public Bank"  ];
		$option[] = [ 'value' => "FPX_B2B_RHB" , 'label' => "FPX B2B RHB Reflex"  ];
		$option[] = [ 'value' => "FPX_B2B_UOB" , 'label' => "FPX B2B United Overseas Bank"  ];
		$option[] = [ 'value' => "FPX_M2E" , 'label' => "FPX Maybank2e"  ];
		$option[] = [ 'value' => "molwallet" , 'label' => "MOLWallet"  ];
		$option[] = [ 'value' => "cash-711" , 'label' => "7-Eleven(MOLPay Cash)"  ];
		$option[] = [ 'value' => "credit" , 'label' => "Credit Card/ Debit Card"  ];
		$option[] = [ 'value' => "cash-epay" , 'label' => "E-Pay"  ];
		$option[] = [ 'value' => "WEBCASH" , 'label' => "WEBCASH"  ];
		$option[] = [ 'value' => "PEXPLUS" , 'label' => "PEx"  ];
		$option[] = [ 'value' => "jompay" , 'label' => "JOMPay"  ];
		$option[] = [ 'value' => "ATMVA" , 'label' => "ATM Transfer via Permata Bank"  ];
		$option[] = [ 'value' => "dragonpay" , 'label' => "Dragonpay"  ];
		$option[] = [ 'value' => "paysbuy" , 'label' => "PaysBuy"  ];
		$option[] = [ 'value' => "Point-BCard" , 'label' => "Bcard points"  ];
		$option[] = [ 'value' => "NGANLUONG" , 'label' => "NGANLUONG"  ];
		$option[] = [ 'value' => "crossborder" , 'label' => "Credit Card/ Debit Card"  ];
		$option[] = [ 'value' => "paypal" , 'label' => "PayPal"  ];
		$option[] = [ 'value' => "enetsD" , 'label' => "eNETS"  ];
		$option[] = [ 'value' => "UPOP" , 'label' => "China Union pay"  ];
		$option[] = [ 'value' => "alipay" , 'label' => "Alipay.com "  ];
		$option[] = [ 'value' => "polipayment" , 'label' => "POLi Payment"  ];
		$option[] = [ 'value' => "TH_PB_SCBPN" , 'label' => "Paysbuy SCBPN"  ];
		$option[] = [ 'value' => "TH_PB_KTBPN" , 'label' => "Paysbuy KTBPN"  ];
		$option[] = [ 'value' => "TH_PB_BBLPN" , 'label' => "Paysbuy BBLPN"  ];
		$option[] = [ 'value' => "TH_PB_BAYPN" , 'label' => "Paysbuy BAYPN"  ];
		$option[] = [ 'value' => "TH_PB_CASH" , 'label' => "Paysbuy CASH"  ];
		$option[] = [ 'value' => "vtcpay-ewallet" , 'label' => "VTC Pay Channels (E-Wallet)"  ];
		$option[] = [ 'value' => "vtcpay-bank" , 'label' => "VTC Pay Channels (Banks)"  ];
		$option[] = [ 'value' => "vtcpay-credit" , 'label' => "VTC Pay Channels (Credit Card)"  ];
		$option[] = [ 'value' => "vtcpay-ewalletbank" , 'label' => "VTC Pay Channels (E-Wallet & Banks)"  ];
		$option[] = [ 'value' => "vtcpay-ewalletcredit" , 'label' => "VTC Pay Channels (E-Wallet & Credit Card)"  ];
		$option[] = [ 'value' => "vtcpay-bankcredit" , 'label' => "VTC Pay Channels (Banks & Credit Car)"  ];
		$option[] = [ 'value' => "vtcpay-vietcombank" , 'label' => "VTC Pay Channels (Vietcombank)"  ];
		$option[] = [ 'value' => "vtcpay-techcombank" , 'label' => "VTC Pay Channels (Techcom Bank)"  ];
		$option[] = [ 'value' => "vtcpay-mb" , 'label' => "VTC Pay Channels (MB)"  ];
		$option[] = [ 'value' => "vtcpay-vietinbank" , 'label' => "VTC Pay Channels (Vietin Bank)"  ];
		$option[] = [ 'value' => "vtcpay-agribank" , 'label' => "VTC Pay Channels (Agribank)"  ];
		$option[] = [ 'value' => "vtcpay-dongabank" , 'label' => "VTC Pay Channels (Dong A Bank)"  ];
		$option[] = [ 'value' => "vtcpay-oceanbank" , 'label' => "VTC Pay Channels (Ocean Bank)"  ];
		$option[] = [ 'value' => "vtcpay-bidv" , 'label' => "VTC Pay Channels (BIDV)"  ];
		$option[] = [ 'value' => "vtcpay-shb" , 'label' => "VTC Pay Channels (SHB)"  ];
		$option[] = [ 'value' => "vtcpay-vib" , 'label' => "VTC Pay Channels (VIB)"  ];
		$option[] = [ 'value' => "vtcpay-maritimebank" , 'label' => "VTC Pay Channels (Maritime Bank)"  ];
		$option[] = [ 'value' => "vtcpay-eximbank" , 'label' => "VTC Pay Channels (Eximbank)"  ];
		$option[] = [ 'value' => "vtcpay-acb" , 'label' => "VTC Pay Channels (ACB)"  ];
		$option[] = [ 'value' => "vtcpay-hdbank" , 'label' => "VTC Pay Channels (HD Bank)"  ];
		$option[] = [ 'value' => "vtcpay-namabank" , 'label' => "VTC Pay Channels (Nam A Bank)"  ];
		$option[] = [ 'value' => "vtcpay-saigonbank" , 'label' => "VTC Pay Channels (Saigon Bank)"  ];
		$option[] = [ 'value' => "vtcpay-sacombank" , 'label' => "VTC Pay Channels (Sacombank)"  ];
		$option[] = [ 'value' => "vtcpay-vietabank" , 'label' => "VTC Pay Channels (Viet A Bank)"  ];
		$option[] = [ 'value' => "vtcpay-vpbank" , 'label' => "VTC Pay Channels (VP Bank)"  ];
		$option[] = [ 'value' => "vtcpay-tienphongbank" , 'label' => "VTC Pay Channels (TP Bank)"  ];
		$option[] = [ 'value' => "vtcpay-seaabank" , 'label' => "VTC Pay Channels (Sea Bank)"  ];
		$option[] = [ 'value' => "vtcpay-pgbank" , 'label' => "VTC Pay Channels (PG Bank)"  ];
		$option[] = [ 'value' => "vtcpay-navibank" , 'label' => "VTC Pay Channels (Navi Bank)"  ];
		$option[] = [ 'value' => "vtcpay-gpbank" , 'label' => "VTC Pay Channels (GP Bank)"  ];
		$option[] = [ 'value' => "vtcpay-bacabank" , 'label' => "VTC Pay Channels (Bac A Bank)"  ];
		$option[] = [ 'value' => "vtcpay-phuongdong" , 'label' => "VTC Pay Channels (Local Bank)"  ];
		$option[] = [ 'value' => "vtcpay-abbank" , 'label' => "VTC Pay Channels (AB Bank)"  ];
		$option[] = [ 'value' => "vtcpay-lienvietpostbank" , 'label' => "VTC Pay Channels (Lienviet Bank)"  ];
		$option[] = [ 'value' => "vtcpay-bvb" , 'label' => "VTC Pay Channels (Baoviet Bank)"  ];
		$option[] = [ 'value' => "singpost" , 'label' => "Cash-SAM"  ];
		
	return $option;
    }
    
    
    /*
     * Get options in "key-value" format
      * @return array
       */
       public function toArray()
       {
           $choose = [
"affinonline" => "Affin Bank(Affin Online)",
"amb" => "Am Bank (Am Online)",
"bankislam" => "Bank Islam",
"cimbclicks" => "CIMB Bank(CIMB Clicks)",
"hlb" => "Hong Leong Bank(HLB Connect)",
"maybank2u" => "Maybank(Maybank2u)",
"pbb" => "PublicBank (PBB Online)",
"rhb" => "RHB Bank(RHB Now)",
"fpx" => "MyClear FPX B2C (Maybank2u, CIMB Clicks, HLB Connect, RHB Now, PBB Online, Bank Islam)",
"fpx_amb" => "FPX Am Bank (Am Online)",
"fpx_bimb" => "FPX Bank Islam",
"fpx_cimbclicks" => "FPX CIMB Bank(CIMB Clicks)",
"fpx_hlb" => "FPX Hong Leong Bank(HLB Connect)",
"fpx_mb2u" => "FPX Maybank(Maybank2u)",
"fpx_pbb" => "FPX PublicBank (PBB Online)",
"fpx_rhb" => "FPX RHB Bank(RHB Now)",
"fpx_abmb" => "FPX Alliance Bank (Alliance Online)",
"fpx_uob" => "FPX United Overseas Bank (UOB)",
"fpx_bsn" => "FPX Bank Simpanan Nasional (myBSN)",
"FPX_OCBC" => "FPX OCBC Ban",
"FPX_SCB" => "FPX Standard Chartered Bank",
"FPX_ABB" => "FPX Affin Bank Berha",
"FPX_B2B" => "MyClear FPX B2B (Maybank2e, BizChannel@CIMB, RHB Reflex, BizSmart, Bank Islam",
"FPX_B2B_ABB" => "FPX B2B Affin Bank",
"FPX_B2B_AMB" => "FPX B2B Ambank Berhad",
"FPX_B2B_BIMB" => "FPX B2B Bank Islam Malaysia Berhad (BIMB)",
"FPX_B2B_CIMB" => "FPX B2B BizChannel@CIMB (CIMB)",
"FPX_B2B_HLB" => "FPX B2B HongLeong Connect",
"FPX_B2B_HSBC" => "FPX B2B HSBC",
"FPX_B2B_PBB" => "FPX B2B Public Bank",
"FPX_B2B_RHB" => "FPX B2B RHB Reflex",
"FPX_B2B_UOB" => "FPX B2B United Overseas Bank",
"FPX_M2E" => "FPX Maybank2e",
"molwallet" => "MOLWallet",
"cash-711" => "7-Eleven(MOLPay Cash)",
"credit" => "Credit Card/ Debit Card",
"cash-epay" => "E-Pay",
"WEBCASH" => "WEBCASH",
"PEXPLUS" => "PEx",
"jompay" => "JOMPay",
"ATMVA" => "ATM Transfer via Permata Bank",
"dragonpay" => "Dragonpay",
"paysbuy" => "PaysBuy",
"Point-BCard" => "Bcard points",
"NGANLUONG" => "NGANLUONG",
"crossborder" => "Credit Card/ Debit Card",
"paypal" => "PayPal",
"enetsD" => "eNETS",
"UPOP" => "China Union pay",
"alipay" => "Alipay.com ",
"polipayment" => "POLi Payment",
"TH_PB_SCBPN" => "Paysbuy SCBPN",
"TH_PB_KTBPN" => "Paysbuy KTBPN",
"TH_PB_BBLPN" => "Paysbuy BBLPN",
"TH_PB_BAYPN" => "Paysbuy BAYPN",
"TH_PB_CASH" => "Paysbuy CASH",
"vtcpay-ewallet" => "VTC Pay Channels (E-Wallet)",
"vtcpay-bank" => "VTC Pay Channels (Banks)",
"vtcpay-credit" => "VTC Pay Channels (Credit Card)",
"vtcpay-ewalletbank" => "VTC Pay Channels (E-Wallet & Banks)",
"vtcpay-ewalletcredit" => "VTC Pay Channels (E-Wallet & Credit Card)",
"vtcpay-bankcredit" => "VTC Pay Channels (Banks & Credit Car)",
"vtcpay-vietcombank" => "VTC Pay Channels (Vietcombank)",
"vtcpay-techcombank" => "VTC Pay Channels (Techcom Bank)",
"vtcpay-mb" => "VTC Pay Channels (MB)",
"vtcpay-vietinbank" => "VTC Pay Channels (Vietin Bank)",
"vtcpay-agribank" => "VTC Pay Channels (Agribank)",
"vtcpay-dongabank" => "VTC Pay Channels (Dong A Bank)",
"vtcpay-oceanbank" => "VTC Pay Channels (Ocean Bank)",
"vtcpay-bidv" => "VTC Pay Channels (BIDV)",
"vtcpay-shb" => "VTC Pay Channels (SHB)",
"vtcpay-vib" => "VTC Pay Channels (VIB)",
"vtcpay-maritimebank" => "VTC Pay Channels (Maritime Bank)",
"vtcpay-eximbank" => "VTC Pay Channels (Eximbank)",
"vtcpay-acb" => "VTC Pay Channels (ACB)",
"vtcpay-hdbank" => "VTC Pay Channels (HD Bank)",
"vtcpay-namabank" => "VTC Pay Channels (Nam A Bank)",
"vtcpay-saigonbank" => "VTC Pay Channels (Saigon Bank)",
"vtcpay-sacombank" => "VTC Pay Channels (Sacombank)",
"vtcpay-vietabank" => "VTC Pay Channels (Viet A Bank)",
"vtcpay-vpbank" => "VTC Pay Channels (VP Bank)",
"vtcpay-tienphongbank" => "VTC Pay Channels (TP Bank)",
"vtcpay-seaabank" => "VTC Pay Channels (Sea Bank)",
"vtcpay-pgbank" => "VTC Pay Channels (PG Bank)",
"vtcpay-navibank" => "VTC Pay Channels (Navi Bank)",
"vtcpay-gpbank" => "VTC Pay Channels (GP Bank)",
"vtcpay-bacabank" => "VTC Pay Channels (Bac A Bank)",
"vtcpay-phuongdong" => "VTC Pay Channels (Local Bank)",
"vtcpay-abbank" => "VTC Pay Channels (AB Bank)",
"vtcpay-lienvietpostbank" => "VTC Pay Channels (Lienviet Bank)",
"vtcpay-bvb" => "VTC Pay Channels (Baoviet Bank)",
"singpost" => "Cash-SAM" ];
           
           return $choose;
       }
}