<?php

// Heading
$_['heading_title'] = 'Razorpay';

// Text 
$_['text_payment'] = 'Payment';
$_['text_extension'] = 'Extensions';
$_['text_edit'] = 'Edit Razorpay';
$_['text_success'] = 'Success: You have modified Razorpay account details!';
$_['text_razorpay'] = '<a href="https://www.razorpay.com" target="_blank"><img src="view/image/payment/razorpay.png" alt="Razorpay" title="Razorpay" style="border: 1px solid #EEEEEE;" /></a>';
$_['text_authorize'] = 'Authorize Only';
$_['text_capture'] = 'Authorize and Capture';
$_['text_order_paid'] = 'Order Paid';
$_['text_payment_authorized'] = 'Payment Authorized';

// Entry
$_['entry_key_id'] = 'Razorpay Key Id';
$_['entry_key_secret'] = 'Razorpay Key Secret';
$_['entry_order_status'] = 'Order Status';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Sort Order';
$_['entry_webhook_secret'] = 'Razorpay Webhook Secret';
$_['entry_webhook_status'] = 'Webhook Status';
$_['entry_webhook_events'] = 'Webhook Events';
$_['entry_webhook_url'] = 'Webhook URL:';
$_['entry_payment_action'] = 'Payment Action';
$_['entry_max_capture_delay'] = 'Max Delay in Payment Capture';
$_['entry_max_capture_delay1'] = 'Max Delay in Payment Capture in minutes';

//tooltips
$_['help_key_id'] = 'The Api Key Id and Key Secret you will recieve from the API keys section of Razorpay Dashboard. Use test Key for testing purposes.';
$_['help_order_status'] = 'The status of the order to be marked on completion of payment.';
$_['help_webhook_url'] = 'Set Razorpay \'order.paid\' webhooks to call this URL with the below secret.';
$_['help_max_delay'] = 'It will gets used by \'payment.authorized\' webhooks to capture the payment after this much time, in case of Authorize Only Pament Action.';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify payment Razorpay!';
$_['error_key_id'] = 'Key Id Required!';
$_['error_key_secret'] = 'Key Secret Required!';
$_['error_webhook_localhost'] = 'Could not enable webhook for localhost.';
$_['error_webhook_events'] = 'At least one webhook event needs to be subscribed to enable webhook.';
$_['error_webhook_secret'] = 'Webhook Secret Required!';
