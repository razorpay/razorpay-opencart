<?php

class ModelExtensionPaymentRazorpay extends Model
{
    const RECURRING_ACTIVE = 1;
    const RECURRING_INACTIVE = 2;
    const RECURRING_CANCELLED = 3;
    const RECURRING_SUSPENDED = 4;
    const RECURRING_EXPIRED = 5;
    const RECURRING_PENDING = 6;

    const PLAN_TYPE = [
        'day' => "daily",
        'week' => "weekly",
        'month' => "monthly",
        'year' => "yearly"
    ];

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

    // Subscription

    public function saveSubscriptionDetails($subscriptionData, $planData, $customerId, $order_id)
    {
        $query = "INSERT INTO " . DB_PREFIX . "razorpay_subscriptions SET plan_entity_id = '" . (int)$planData['entity_id'] . "', subscription_id = '" . $subscriptionData['id'] . "',";
        $query = $query . " product_id = '" . (int)$planData['opencart_product_id'] . "', razorpay_customer_id = '" . $customerId . "', qty = '" . $subscriptionData['quantity'] . "',";
        $query = $query . " status = '" . $subscriptionData['status'] . "', opencart_user_id = '" . (int)$this->customer->getId() . "', total_count = '" . (int)$subscriptionData['total_count'] . "',";
        $query = $query . "  paid_count = '" . (int)$subscriptionData['paid_count'] . "', remaining_count = '" . (int)$subscriptionData['remaining_count'] . "', order_id = '" . (int)$order_id . "'";

        if(isset($subscriptionData['start_at'])){
            $query = $query . ",  start_at = '" . date("Y-m-d h:i:sa", $subscriptionData['start_at']  ). "'";
        }

        if(isset($subscriptionData['created_at'])){
            $query = $query . ",  subscription_created_at = '" . date("Y-m-d h:i:sa",$subscriptionData['created_at'] ) . "'";
        }

        if(isset($subscriptionData['charge_at'])){
            $query = $query . ",  next_charge_at = '" . date("Y-m-d h:i:sa",$subscriptionData['charge_at'] ) . "'";
        }

        $this->db->query($query);
    }

    public function updateSubscription($subscriptionData, $subscriptionId)
    {
        $query = "UPDATE " . DB_PREFIX . "razorpay_subscriptions SET  qty = '" . $subscriptionData['quantity'] . "',";
        $query = $query . " status = '" . $subscriptionData['status'] . "', total_count = '" . (int)$subscriptionData['total_count'] . "',";
        $query = $query . "  paid_count = '" . (int)$subscriptionData['paid_count'] . "', remaining_count = '" . (int)$subscriptionData['remaining_count'] . "'";

        if(isset($subscriptionData['start_at'])){
            $query = $query . ",  start_at = '" . date("Y-m-d h:i:sa", $subscriptionData['start_at']  ). "'";
        }

        if(isset($subscriptionData['charge_at'])){
            $query = $query . ",  next_charge_at = '" . date("Y-m-d h:i:sa",$subscriptionData['charge_at'] ) . "'";
        }

        if(isset($subscriptionData['end_at'])){
            $query = $query . ",  end_at = '" . date("Y-m-d h:i:sa",$subscriptionData['end_at'] ) . "'";
        }

        $query = $query ." WHERE subscription_id = '" . $subscriptionId . "'";

        $this->db->query($query);
    }

    public function getTotalOrderRecurring()
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "razorpay_subscriptions` WHERE `opencart_user_id` = '" . (int)$this->customer->getId() . "'");

        return $query->row['total'];
    }

    public function getSubscriptionByUserId($start = 0, $limit = 20)
    {
        if ($start < 0) {
            $start = 0;
        }

        if ($limit < 1) {
            $limit = 1;
        }

        $query = $this->db->query("SELECT rs.*, pd.name AS productName  FROM `" . DB_PREFIX . "razorpay_subscriptions` rs LEFT JOIN `" . DB_PREFIX . "product_description` pd on pd.product_id = rs.product_id WHERE rs.opencart_user_id = '" . (int)$this->customer->getId() . "' ORDER BY rs.entity_id DESC LIMIT ". (int)$start . "," . (int)$limit);

        return $query->rows;
    }

    public function getSubscriptionDetails($subscriptionId)
    {
        $query = $this->db->query("SELECT rs.*, pd.name AS productName, rpln.plan_name, rpln.plan_type, rpln.plan_id   FROM " . DB_PREFIX . "razorpay_subscriptions rs LEFT JOIN " . DB_PREFIX . "razorpay_plans rpln on rs.plan_entity_id = rpln.entity_id  LEFT JOIN " . DB_PREFIX . "product_description pd on pd.product_id = rs.product_id WHERE `subscription_id` = '" . $subscriptionId. "'");

        return $query->row;
    }

    public function getSubscriptionById($subscriptionId)
    {
        $query = $this->db->query("SELECT *  FROM " . DB_PREFIX . "razorpay_subscriptions WHERE `subscription_id` = '" . $subscriptionId. "'");

        return $query->row;
    }

    public function updateSubscriptionStatus($subscriptionId, $status, $user = null)
    {
        $query = "UPDATE " . DB_PREFIX . "razorpay_subscriptions SET status = '".$status . "'";

        if($user){
            $query = $query .",updated_by = '" . $user . "'" ;
        }
        $query = $query ." WHERE subscription_id = '" . $subscriptionId . "'";

        $this->db->query($query);
    }

    public function updateSubscriptionPlan($planData)
    {
        $query = "UPDATE " . DB_PREFIX . "razorpay_subscriptions SET plan_entity_id = '".$planData['plan_entity_id'] . "'";

        if($planData["qty"]){
            $query = $query .",qty = '" . $planData["qty"] . "'" ;
        }
        $query = $query ." WHERE subscription_id = '" . $planData["subscriptionId"] . "'";

        $this->db->query($query);
    }

    public function getProductBasedPlans($productId)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "razorpay_plans WHERE plan_status = 1 AND opencart_product_id = '". $productId ."'");

        return $query->rows;
    }

    public function getPlanByRecurringIdAndFrequencyAndProductId($recurringId, $planType, $productId)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "razorpay_plans WHERE recurring_id = '".$recurringId. "' AND plan_type = '".self::PLAN_TYPE[$planType]."' AND opencart_product_id = '".$productId."'");

        return $query->row;
    }

    public function fetchPlanByEntityId($planEntityId)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "razorpay_plans WHERE `plan_status` = 1 AND `entity_id` = $planEntityId");

        return $query->row;
    }

    public function fetchRZPPlanById($planId)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "razorpay_plans WHERE `plan_status` = 1 AND `plan_id` = '".$planId."'");

        return $query->row;
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

    public function recurringPayments()
    {
        return (bool)$this->config->get('payment_razorpay_subscription_status');

    }

    public function createOCRecurring($recurringData) {
        $query = "INSERT INTO `" . DB_PREFIX . "order_recurring` SET `order_id` = '" . (int)$recurringData['order_id'] . "', `date_added` = NOW(), `status` = '" . self::RECURRING_PENDING . "',";
        $query = $query . " `product_id` = '" . (int)$recurringData['product_id'] . "', `product_name` = '" . $this->db->escape($recurringData['product_name']) . "',";
        $query = $query . " `product_quantity` = '" . $this->db->escape($recurringData['product_quantity']) . "', `recurring_id` = '" . (int)$recurringData['recurring_id'] . "',";
        $query = $query . " `recurring_name` = '" . $this->db->escape($recurringData['recurring_name']) . "', `recurring_description` = '" . $this->db->escape($recurringData['recurring_description']) . "',";
        $query = $query . " `recurring_frequency` = '" . $this->db->escape($recurringData['recurring_frequency']) . "', `recurring_cycle` = '" . (int)$recurringData['recurring_cycle'] . "',";
        $query = $query . " `recurring_duration` = '" . (int)$recurringData['recurring_duration'] . "', `recurring_price` = '" . (float)$recurringData['recurring_price'] . "',";
        $query = $query . " `trial` = '" . (int)$recurringData['trial'] . "', `trial_frequency` = '" . $this->db->escape($recurringData['trial_frequency']) . "',";
        $query = $query . " `trial_cycle` = '" . (int)$recurringData['trial_cycle'] . "', `trial_duration` = '" . (int)$recurringData['trial_duration'] . "',";
        $query = $query . " `trial_price` = '" . (float)$recurringData['trial_price'] . "', `reference` = '" . $this->db->escape($recurringData['reference']) . "'";
        return $this->db->query($query);
    }

    public function updateOCRecurringStatus( $orderId, $status)
    {
        $query = "UPDATE " . DB_PREFIX . "order_recurring SET status = '".$status. "' ";
        $query = $query ." WHERE order_id = '" . $orderId . "';" ;

        $this->db->query($query);

    }

    public function getOCRecurringStatus($orderId)
    {
        $query = "SELECT * FROM " . DB_PREFIX . "order_recurring WHERE order_id = '" . $orderId . "';" ;

        return $this->db->query($query)->row;
    }

    public function addOCRecurringTransaction($orderRecurringId, $subscriptionId, $amount, $status) {

        $this->db->query("INSERT INTO `" . DB_PREFIX . "order_recurring_transaction` SET order_recurring_id='" . (int)$orderRecurringId . "', reference='" . $this->db->escape($subscriptionId) . "', type='" . $status . "', amount='" . (float)$amount . "', date_added=NOW()");
    }
}