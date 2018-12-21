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
  - add event before click Place Order to avoid Order Confirmation Email being sent out before payment made
