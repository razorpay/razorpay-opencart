<?php

class ModelPaymentRazorpay extends Model
{
    public function getMethod($address, $total)
    {
        $this->language->load('payment/razorpay');

        $method_data = array(
            'code' => 'razorpay',
            'title' => $this->language->get('text_title'),
            'terms' => '',
            'sort_order' => $this->config->get('razorpay_sort_order'),
        );

        return $method_data;
    }
}
