<?php

class ModelExtensionPaymentRazorpay extends Model
{
    public function getMethod($address, $total)
    {
        $this->language->load('extension/payment/razorpay');

        $method_data = array(
            'code' => 'razorpay',
            'title' => $this->language->get('text_title'),
            'terms' => '',
            'sort_order' => $this->config->get('payment_razorpay_sort_order'),
        );

        return $method_data;
    }
}
