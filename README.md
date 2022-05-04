## Razorpay Payment Extension for Opencart

This extension utilizes Razorpay API and provides seamless integration with OpenCart, allowing payments for Indian merchants via Credit Cards, Debit Cards, Net Banking, Wallets, etc.  without redirecting away from the OpenCart site.

Razorpay Payment Extension for Opencart now enabled with Subscription. Allows you automatically charge customers on a recurring basis, based on a billing cycle that you control.

### Installation

Copy all files/folders recursively to opencart installation directory.

Go to Admin Panel, Extensions->Payments and install the Razorpay gateway extension.

Click on Edit next to Razorpay and do the following:

- Add your Razorpay Key Id
- Add your Razorpay Key Secret
- Change plugin status to Enabled

Save the plugin settings

### Installation via Extension Installer

1. Download Razorpay Payment gateway extension from Opencart
2. Login to the OpenCart Admin Panel
3. Navigate to Extensions -> Installer and click on button Upload and choose the zip file razorpay-opencart.ocmod.zip
4. Navigate to Extensions -> Payments and click install on Razorpay
5. After installing, click on Edit
6. Enable the extension and set the Razorpay Key Id and Secret.
7. Enable Subscription status to accept recurring payments on opencart.
8. Please make sure that you have Webhooks setup on the [Razorpay Dashboard](https://dashboard.razorpay.com/app/webhooks) to ensure that recurring payments are marked as paid on Opencart.
9. This extension supports the following webhook events:
    - payment.authorized
    - payment.failed
    - order.paid
    - subscription.charged
    - subscription.paused
    - subscription.resumed
    - subscription.cancelled
    

### Development

- The `master` branch holds the plugin for OpenCart 3
- The `opencart-2.x` branch holds the plugin for Opencart 2
- The `opencart1.5` branch holds the plugin for Opencart 1.5
- Tags are in either of these three series: `opencart3-x.y.z` or `opencart2-x.y.z` or `opencart1.5-1.x.y`
- Subscription is only available with Opencart 3

### Support

Visit [https://razorpay.com](https://razorpay.com) for support requests or email contact@razorpay.com.
