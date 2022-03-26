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

    public function setWebhookFlag($order_id,$flag) {
        
        $this->db->query("UPDATE " . DB_PREFIX . "order SET razorpay_webhook_count = '" . (int)$flag . "' WHERE order_id = '" . (int)$order_id . "'");
            
    }

    public function getWebhookFlag($order_id) {

             $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order WHERE order_id = '" . (int)$order_id . "'");
              
             if(isset($query->rows[0]['razorpay_webhook_count'])){
                return $query->rows[0]['razorpay_webhook_count']; 
             }
            
    }
}
