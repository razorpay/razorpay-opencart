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

    // Subscription

    public function saveSubscriptionDetails($subscriptionData, $planData, $customerId)
    {
        $query = "INSERT INTO " . DB_PREFIX . "razorpay_subscriptions SET plan_entity_id = '" . (int)$planData['entity_id'] . "', subscription_id = '" . $subscriptionData['id'] . "',";
        $query = $query . " product_id = '" . (int)$planData['opencart_product_id'] . "', razorpay_customer_id = '" . $customerId . "', qty = '" . $subscriptionData['quantity'] . "',";
        $query = $query . " status = '" . $subscriptionData['status'] . "', opencart_user_id = '" . (int)$this->customer->getId() . "', total_count = '" . (int)$subscriptionData['total_count'] . "',";
        $query = $query . "  paid_count = '" . (int)$subscriptionData['paid_count'] . "', remaining_count = '" . (int)$subscriptionData['remaining_count'] . "',";
        $query = $query . "  start_at = '" . date("Y-m-d h:i:sa", $subscriptionData['start_at'] ). "', subscription_created_at = '" . date("Y-m-d h:i:sa",$subscriptionData['created_at']) . "', next_charge_at = '" . date("Y-m-d h:i:sa", $subscriptionData['charge_at']) . "'";

        $this->db->query($query);
    }

    public function updateSubscription($subscriptionData, $subscriptionId)
    {
        $query = "UPDATE " . DB_PREFIX . "razorpay_subscriptions SET  qty = '" . $subscriptionData['quantity'] . "',";
        $query = $query . " status = '" . $subscriptionData['status'] . "', total_count = '" . (int)$subscriptionData['total_count'] . "',";
        $query = $query . "  paid_count = '" . (int)$subscriptionData['paid_count'] . "', remaining_count = '" . (int)$subscriptionData['remaining_count'] . "',";
        $query = $query . "  start_at = '" . date("Y-m-d h:i:sa", $subscriptionData['start_at'] ) . "', next_charge_at = '" . date("Y-m-d h:i:sa",$subscriptionData['charge_at'] ). "'";
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
        $query = "UPDATE " . DB_PREFIX . "razorpay_subscriptions SET plan_entity_id = '".$planData['plan_id'] . "'";

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

    public function getPlanByRecurringIdAndFrequencyAndProductId($recurringId, $frequency, $productId)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "recurring WHERE recurring_id = '".$recurringId. "' AND frequency = '".$frequency."' AND opencart_product_id = '".$productId."'");

        return $query->rows;
    }

    public function fetchPlanById($planId)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "razorpay_plans WHERE `plan_status` = 1 AND `entity_id` = $planId");

        return $query->row;
    }

    public function recurringPayments()
    {
        return (bool)$this->config->get('payment_razorpay_subscription_status');

    }
}