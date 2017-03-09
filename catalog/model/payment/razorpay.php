<?php

class ModelPaymentRazorpay extends Model
{
    public function getMethod($address, $total)
    {
        $this->language->load('payment/razorpay');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('razorpay_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
 
        if (!$this->config->get('razorpay_geo_zone_id')) {
            $status = true;
        } else if ($query->num_rows) {
            $status = true; 
        } else {
            $status = false;
        }     
        
        $method_data = array();
    
        if ($status) {  
            $method_data = array(
                'code' => 'razorpay',
                'title' => $this->language->get('text_title'),
                'terms' => '',
                'sort_order' => $this->config->get('razorpay_sort_order'),
            );
        }

        return $method_data;
    }
}
