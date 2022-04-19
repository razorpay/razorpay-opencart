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

    public function editSetting($code, $data, $store_id = 0)
    {
        foreach ($data as $key => $value)
        {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "' AND `key` = '" . $this->db->escape($key) . "'");

            if (!is_array($value))
            {
                $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
            } else {
                $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape(json_encode($value, true)) . "', serialized = '1'");
            }
        }
    }
}
