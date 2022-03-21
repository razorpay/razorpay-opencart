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

    public function getPlanByRecurringIdAndFrequency($recurringId, $frequency)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "razorpay_plans WHERE `recurring_id` = $recurringId AND `frequency` = $frequency");

        return $query->rows;
    }

    public function saveSubscriptionDetails($subscriptionData, $planData, $customerId)
    {
        $query = "INSERT INTO " . DB_PREFIX . "razorpay_subscriptions SET plan_entity_id = '" . (int)$planData['entity_id'] . "', subscription_id = '" . $subscriptionData['id'] . "',";
        $query = $query . " product_id = '" . (int)$planData['opencart_product_id'] . "', razorpay_customer_id = '" . $customerId . "',', qty = '" . $subscriptionData['quantity'] . "'";
        $query = $query . " status = '" . $subscriptionData['status'] . "', opencart_user_id = '" . (int)$this->customer->getId() . "', total_count = '" . (int)$subscriptionData['total_count'] . "',";
        $query = $query . "  paid_count = '" . (int)$subscriptionData['paid_count'] . "', remaining_count = '" . (int)$subscriptionData['remaining_count'] . "',";
        $query = $query . "  start_at = '" . $subscriptionData['start_at'] . "', subscription_created_at = '" . $subscriptionData['created_at'] . "', next_charge_at = '" . $subscriptionData['next_charge_at'] . "'";

        $this->db->query($query);
    }

    public function updateSubscription($subscriptionData, $subscriptionId)
    {
        $query = "UPDATE" . DB_PREFIX . "razorpay_subscriptions SET  qty = '" . $subscriptionData['quantity'] . "'";
        $query = $query . " status = '" . $subscriptionData['status'] . "', total_count = '" . (int)$subscriptionData['total_count'] . "',";
        $query = $query . "  paid_count = '" . (int)$subscriptionData['paid_count'] . "', remaining_count = '" . (int)$subscriptionData['remaining_count'] . "',";
        $query = $query . "  start_at = '" . $subscriptionData['start_at'] . "', next_charge_at = '" . $subscriptionData['next_charge_at'] . "'";
        $query = $query ." WHERE subscription_id = '" . $subscriptionId . "'";

        $this->db->query($query);
    }
}
