MOLPay Magento Plugin
=====================
![MOLPay Technical Teams](https://github.com/MOLPay/Magento_Plugin/wiki/images/molpay-developer.jpg)

The MOLPay Magento Plugin makes it easy to add MOLPay payment gateway to your Magento shopping cart.

# Supported version
- Magento 1.9.2.X
- Magento 1.9.2.X with [One Page Checkout Module - IWD](https://www.magentocommerce.com/magento-connect/one-page-checkout.html)

# Notes
MOLPay Sdn. Bhd. is not responsible for any problems that might arise from the use of this module. Use at your own risk. Please backup any critical data before proceeding. For any query or assistance, please email support@molpay.com

# System Requirements
- PHP (at least 5.2) 
- Curl

# Installations
1. Download the plugin below. Copy all the file and paste it at your Magento root directory.
<MAGENTO_DIR>/app/*

2. Login to Magento Administration, go to menu, System > Configuration > Advanced > Advanced. Make sure <b>Mage_MOLPay</b>  is Enable.

3. In current page, go to submenu, Sales > Payments Method, You will see list of payment method available on your Magento. Click on <b>MOLPay</b> to set up your merchant account details. 

4. Fill in your <b>MOLPay Merchant ID</b>  & <b>MOLPay Verify Key</b>  into the respective fields. Then, Select Payment Channel(s), that you want to use. Set New order status as Pending. Save Config! That's all for your Magento App's setting.

5. Next, login to Merchant Account and go to Merchant Profile, update the following fill:

 Return URL : http://xxxxxxxxxxxxxx/molpayseamless/paymentmethod/success

 Notification URL : http://xxxxxxxxxxxxxx/molpayseamless/paymentmethod/notification

 Callback URL : http://xxxxxxxxxxxxxx/molpayseamless/paymentmethod/callback

 Replace xxxxxxxxxxxxxx with your shoppingcart domain

6. Save the configuration and done.


Support
-------

Merchant Technical Support / Customer Care : support@molpay.com <br>
Marketing Campaign : marketing@molpay.com <br>
Channel/Partner Enquiry : channel@molpay.com <br>
Media Contact : media@molpay.com <br>
R&D and Tech-related Suggestion : technical@molpay.com <br>
Abuse Reporting : abuse@molpay.com
