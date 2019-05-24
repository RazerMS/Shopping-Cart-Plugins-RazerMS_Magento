## Magento 2.1.x and 2.2.x Release
- Previous plugins are available under folder `Archieve` (should be Archive)
- Latest plugin release can be found in `Latest_Release`

## Fixing Issue
On 2018/12/21
- Controller/Index/Index.php
  - restructure logic to accept status payment during Return/Notification/Callback URL
  - add Requery API in status payment return 'failed' to encounter issue bank result delay to MOLPay system during buyer redirect to merchant site 
  - turn on "CanSendOrderEmail" as success payment has been made and need to send email to customer

- etc/events.xml and Helper/Observer.php
  - add event after click Place Order to avoid Order Confirmation Email being sent out before payment made

On 2018/12/24
- Controller/Index/Index.php
  - revised coding logic on "failed" status payment
  - revised message display on pending payment

## Installation Guide
1. **Download** file [here](https://github.com/MOLPay/Magento_Plugin/blob/master/MOLPay%20Seamless%20Plugin%20for%20Magento%202.1.x%202.2.x/DefaultCheckout/Latest_Release/Magento2.2.x_RazerMSPlugin_20190524.zip) for Magento 2.2.x Extract/Unzip the files. You will get the * _**`app/code/MOLPay`**_ folder that consist plugin file.

2. Upload or copy those file and folder into Magento root directory (installed folder) <br>
  <i>This won't replace any of your Magento system core file</i>
  * _**`<MagentoRoot>`**_
3. After successful transfer, SSH to your installation directory and run _**`php bin/magento setup:upgrade`**_.

4. This process will then install the plugin into your system. Next, please run _**`php bin/magento setup:static-content:deploy`**_.

5. Finally,  _**`run php bin/magento cache:flush`**_ to flush all file caching in your system.

## Configuration
1. Login as Magento Store Admin and navigate to menu _**`Store`**_ > _**`Configuration`**_.

2. Now access the _**`Sales`**_ > _**`Payment Methods`**_ menu. 

3. Select _**`MOLPay Seamless Integration`**_ and click _**`Configure`**_.

4. Fill in your _**`Merchant ID`**_ , _**`Verify Key`**_ and _**`Secret Key`**_.

5. Then _**`Save`**_ to save the setting.

5. Login to _**`MOLPay Merchant Admin`**_, navigate to _**`Transaction`**_ >> _**`Transaction Settings`**_ >> find _**`End Point Setting`**_ . Scroll down until you see _**`Return URL`**_, _**`Notification URL`**_ and _**`Callback URL`**_. Fill in the value at textbox as below:
    * Return URL : http://_**`xxxxxxxxxxxxxx`**_/seamless/
    * Set enable Return IPN to YES.
    * Notification URL : http://_**`xxxxxxxxxxxxxx`**_/seamless/
    * Set enable Notification IPN to YES.
    * Callback URL : http://_**`xxxxxxxxxxxxxx`**_/seamless/
    * Set enable Callback IPN to YES.
