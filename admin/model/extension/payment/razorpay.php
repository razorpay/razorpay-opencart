<?php
class ModelExtensionPaymentRazorpay extends Model
{
    public function createTables()
    {   
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."razorpay_plans` (
            `entity_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `plan_id` varchar(40) NOT NULL,
            `recurring_id` int(11) NOT NULL,
            `opencart_product_id` int(11) NOT NULL,
            `plan_name` varchar(255) NOT NULL,
            `plan_desc` varchar(255) NOT NULL,
            `plan_type` varchar(30) NOT NULL,
            `plan_frequency` int(11) NOT NULL DEFAULT 1,
            `plan_bill_cycle` varchar(255) NOT NULL,
            `plan_trial` decimal(10,0) NOT NULL DEFAULT 0,
            `plan_bill_amount` decimal(10,0) NOT NULL DEFAULT 0,
            `plan_addons` decimal(10,0) NOT NULL DEFAULT 0,
            `plan_status` int(11) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`entity_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
    
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."razorpay_subscriptions` (
                `entity_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `plan_entity_id` int(11) NOT NULL,
                `subscription_id` varchar(30) NOT NULL,
                `product_id` int(11) NOT NULL,
                `order_id` int(11) NOT NULL,
                `razorpay_customer_id` varchar(30) NOT NULL,
                `opencart_user_id` int(11) NOT NULL,
                `status` varchar(30) NOT NULL,
                `updated_by` varchar(30) NOT NULL,
                `qty` int(11) NOT NULL DEFAULT '0',
                `total_count` int(11) NOT NULL DEFAULT '0',
                `paid_count` int(11) NOT NULL DEFAULT '0',
                `remaining_count` int(11) NOT NULL DEFAULT '0',
                `auth_attempts` int(11) NOT NULL DEFAULT '0',
                `start_at` timestamp NULL,
                `end_at` timestamp NULL,
                `subscription_created_at` timestamp NULL,
                `next_charge_at` timestamp NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`entity_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
 
    }
    public function dropTables()
    {
        $this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."razorpay_plans`");
        $this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."razorpay_subscriptions`");
 
    }

    public function getPlans($data = array())
    {
        $sql = "SELECT * FROM `" . DB_PREFIX . "razorpay_plans` p";
        $sql .=" LEFT JOIN " . DB_PREFIX . "product_description op ON (op.product_id = p.opencart_product_id)";
        $sql .= " WHERE entity_id > '0'";
        if (!empty($data['filter_plan_id'])) {
            $sql .= " AND p.plan_id = '" . $data['filter_plan_id'] . "'";
        }
        if (!empty($data['filter_plan_status'])) {
            $sql .= " AND p.plan_status = '" . $data['filter_plan_status'] . "'";
        }
        if (!empty($data['filter_plan_name'])) {
            $sql .= " AND p.plan_name LIKE '%" . $this->db->escape($data['filter_plan_name']) . "%'";
        }

        if (!empty($data['filter_date_created'])) {
            $sql .= " AND DATE(p.created_at) = DATE('" . $this->db->escape($data['filter_date_created']) . "')";
        }

        $sort_data = array(
        'p.plan_id',
        'p.created_at',
        'p.plan_status'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY p.entity_id";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }
    public function getTotalPlan($data = array())
    {
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "razorpay_plans` p";
        
        $sql .=" LEFT JOIN " . DB_PREFIX . "product_description op ON (op.product_id = p.opencart_product_id)";

        if (!empty($data['filter_plan_id'])) {
            $implode = array();

            $sub_statuses = explode(',', $data['filter_plan_id']);

            foreach ($sub_statuses as $sub_id) {
                $implode[] = "plan_id = '" . $sub_id . "'";
            }

            if ($implode) {
                $sql .= " WHERE (" . implode(" OR ", $implode) . ")";
            }
        } elseif (isset($data['filter_plan_name']) && $data['filter_plan_name'] !== '') {
            $sql .= " WHERE plan_name = '" . $data['filter_plan_name'] . "'";
        } else {
            $sql .= " WHERE plan_name > '0'";
        }

        if (!empty($data['filter_plan_status'])) {
            $sql .= " AND plan_status = '" . $data['filter_plan_status'] . "'";
        }
        if (!empty($data['filter_date_created'])) {
            $sql .= " AND DATE(p.created_at) = DATE('" . $this->db->escape($data['filter_date_created']) . "')";
        }

        

        $query = $this->db->query($sql);

        return $query->row['total'];
    }
    public function addPlan($data,$plan_id)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "razorpay_plans SET plan_name = '" . $this->db->escape($data['plan_name']) . "', plan_desc = '" . $this->db->escape($data['plan_desc']) . "', plan_id = '" . $this->db->escape($plan_id) . "',opencart_product_id = '" . $this->db->escape($data['product_id']) . "', plan_type = '" . $this->db->escape($data['plan_type']) . "', 	plan_frequency = '" . $this->db->escape($data['billing_frequency']) . "', 	plan_bill_cycle = '" . (int)$data['billing_cycle'] . "', plan_trial = '" . $this->db->escape($data['plan_trial']) . "', plan_bill_amount = '" . $data['billing_amount'] . "',plan_addons = '" . $data['plan_addons'] . "',plan_status = '" . (int)$data['plan_status'] . "', 	created_at = NOW()");

        return $this->db->getLastId();
    }


    public function enablePlan($entity_id)
    {
        $this->load->model('localisation/language');
        //fetch and add in recurring table
        $planData= $this->db->query("SELECT * FROM `" . DB_PREFIX . "razorpay_plans` WHERE entity_id='" . (int)$entity_id . "'")->row;
        //INSERT INTO `" . DB_PREFIX . "recurring` SET `status` = " . $data['status'] . ", `price` = " . (float)$data['price'] . ", `frequency` = '" . $this->db->escape($data['frequency']) . "', `duration` = " . (int)$data['duration'] . ", `cycle` = " . (int)$data['cycle'] . ", `trial_status` = " . (int)$data['trial_status'] . ", `trial_price` = " . (float)$data['trial_price'] . ", `trial_frequency` = '" . $this->db->escape($data['trial_frequency']) . "', `trial_duration` = " . (int)$data['trial_duration'] . ", `trial_cycle` = '" . (int)$data['trial_cycle'] . "'"
        $planType = $planData['plan_type'];
        if($planType=="daily") {$frequency = "day";
        }
        else if ($planType=="weekly") {$frequency = "week";
        }
        else if ($planType=="monthly") {$frequency = "month";
        }
        else {$frequency = "yearly";
        }
      $data = array(
          'plan_name'=>$planData['plan_name'],
          'plan_entity_id'=>$entity_id,
          'status'=>1,
          'price'=>$planData['plan_bill_amount'],
          'frequency'=>$frequency,
          'duration'=>$planData['plan_frequency'],
          'cycle'=>$planData['plan_bill_cycle'],
          'trial_status'=>0,
          'trial_price'=>$planData['plan_trial'],
          'trial_frequency'=>'day',
          'trial_duration'=>0,
          'trial_cycle'=>0,
          'product_id'=>$planData['opencart_product_id'],
        'customer_group_id'=>$this->config->get('config_customer_group_id'),
        'languages'=>$this->model_localisation_language->getLanguages()
      );
       $this->addRecurring($data);
// update status 
        $this->db->query("UPDATE " . DB_PREFIX . "razorpay_plans SET plan_status = '" . 1 . "'WHERE entity_id = '" .$entity_id . "'");
    }

    public function disablePlan($entity_id)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "razorpay_plans SET plan_status = '" . 2 . "' WHERE entity_id = '" .$entity_id . "'");
        //delete from recurring table;
        $this->deleteRecurring($entity_id);
    }
    public function deleteRecurring($entity_id)
    {

        $planData= $this->db->query("SELECT * FROM `" . DB_PREFIX . "razorpay_plans` WHERE entity_id='" . (int)$entity_id . "'")->row;
        $recurring_id = $planData['recurring_id'];
        $this->db->query("Delete FROM `" . DB_PREFIX . "product_recurring` WHERE recurring_id='" . (int)$recurring_id . "'")->row;
        $this->db->query("Delete FROM `" . DB_PREFIX . "recurring` WHERE recurring_id='" . (int)$recurring_id . "'")->row;
        $this->db->query("Delete FROM `" . DB_PREFIX . "recurring_description` WHERE recurring_id='" . (int)$recurring_id . "'")->row;


    }
    public function getSubscription($data = array())
    {
        $sql = "SELECT s.*,p.plan_id,op.name,c.firstname,c.lastname FROM `" . DB_PREFIX . "razorpay_subscriptions` s";
        $sql .=" LEFT JOIN " . DB_PREFIX . "razorpay_plans p ON (p.entity_id = s.plan_entity_id)";
        $sql .=" LEFT JOIN " . DB_PREFIX . "product_description op ON (op.product_id = p.opencart_product_id)";
        $sql .=" LEFT JOIN " . DB_PREFIX . "customer c ON (s.opencart_user_id = c.customer_id )";
        
        $sql .= " WHERE s.entity_id > '0'";
       
        if (!empty($data['filter_subscription_id'])) {
            $sql .= " AND s.subscription_id LIKE '%" . $this->db->escape($data['filter_subscription_id']) . "%'";
        }
        if (!empty($data['filter_plan_name'])) {
            $sql .= " AND p.plan_id LIKE '%" . $this->db->escape($data['filter_plan_name']) . "%'";
        }
        if (!empty($data['filter_subscription_status'])) {
            $sql .= " AND s.status LIKE '%" . $this->db->escape($data['filter_subscription_status']) . "%'";
        }
        if (!empty($data['filter_date_created'])) {
            $sql .= " AND DATE(s.created_at) = DATE('" . $this->db->escape($data['filter_date_created']) . "')";
        }

        $sort_data = array(
        's.subscription_id',
        's.created_at',
        's.status'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY p.entity_id";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }
    public function getTotalSubscriptions($data = array())
    {
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "razorpay_subscriptions` s";
        
        $sql .=" LEFT JOIN " . DB_PREFIX . "razorpay_plans p ON (p.entity_id = s.plan_entity_id)";
        $sql .=" LEFT JOIN " . DB_PREFIX . "product_description op ON (op.product_id = p.opencart_product_id)";
        $sql .=" LEFT JOIN " . DB_PREFIX . "customer c ON (s.opencart_user_id = c.customer_id )";

        if (!empty($data['filter_subscription_id'])) {
            $implode = array();

            $sub_statuses = explode(',', $data['filter_subscription_id']);

            foreach ($sub_statuses as $sub_id) {
                $implode[] = "subscription_id = '" . $sub_id . "'";
            }

            if ($implode) {
                $sql .= " WHERE (" . implode(" OR ", $implode) . ")";
            }
        } elseif (isset($data['filter_plan_name']) && $data['filter_plan_name'] !== '') {
            $sql .= " WHERE plan_id = '" . $data['filter_plan_name'] . "'";
        } else {
            $sql .= " WHERE plan_id > '0'";
        }

        if (!empty($data['filter_date_created'])) {
            $sql .= " AND DATE(s.created_at) = DATE('" . $this->db->escape($data['filter_date_created']) . "')";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }
    public function getSubscriptionInfo($entity_id)
    {
        
        $sql = "SELECT s.*,s.entity_id as sub_id,s.status as sub_status,s.created_at as sub_created,p.*,op.name,c.firstname,c.lastname FROM `" . DB_PREFIX . "razorpay_subscriptions` s";
        $sql .=" LEFT JOIN " . DB_PREFIX . "razorpay_plans p ON (p.entity_id = s.plan_entity_id)";
        $sql .=" LEFT JOIN " . DB_PREFIX . "product_description op ON (op.product_id = p.opencart_product_id)";
        $sql .=" LEFT JOIN " . DB_PREFIX . "customer c ON (s.opencart_user_id = c.customer_id )";
        
        $sql .= " WHERE s.entity_id= '" .$entity_id . "'";
        $query = $this->db->query($sql);

        return $query->row;
    }
    public function resumeSubscription($entity_id,$updated_by)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "razorpay_subscriptions SET status = 'active',updated_by = '".$updated_by. "' WHERE entity_id = '" .$entity_id . "'");
    }
    public function pauseSubscription($entity_id,$updated_by)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "razorpay_subscriptions SET status = 'paused',updated_by = '".$updated_by. "' WHERE entity_id = '" .$entity_id . "'");
    }
    public function cancelSubscription($entity_id,$updated_by)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "razorpay_subscriptions SET status = 'cancelled' ,updated_by = '".$updated_by. "' WHERE entity_id = '" .$entity_id . "'");
    }
    public function getSingleSubscription($entity_id)
    {

        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "razorpay_subscriptions` WHERE entity_id='" . (int)$entity_id . "'")->row;
            
    }

    public function addRecurring($data)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "recurring` SET `status` = " . $data['status'] . ", `price` = " . (float)$data['price'] . ", `frequency` = '" . $this->db->escape($data['frequency']) . "', `duration` = " . (int)$data['duration'] . ", `cycle` = " . (int)$data['cycle'] . ", `trial_status` = " . (int)$data['trial_status'] . ", `trial_price` = " . (float)$data['trial_price'] . ", `trial_frequency` = '" . $this->db->escape($data['trial_frequency']) . "', `trial_duration` = " . (int)$data['trial_duration'] . ", `trial_cycle` = '" . (int)$data['trial_cycle'] . "'");

        $recurring_id = $this->db->getLastId();

        foreach ($data['languages'] as $language_id => $recurring_description) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "recurring_description` (`recurring_id`, `language_id`, `name`) VALUES (" . (int)$recurring_id . ", " . (int)$recurring_description['language_id']. ", '" . $this->db->escape($data['plan_name']) . "')");
            

        }
        //product recurring mapping
        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_recurring` (`product_id`, `recurring_id`, `customer_group_id`) VALUES (" . (int)$data['product_id'] . ", " . (int)$recurring_id . ", '" . (int)$data['customer_group_id'] . "')");
        // update plan table with recurring id
        $this->db->query("UPDATE " . DB_PREFIX . "razorpay_plans SET recurring_id = '".(int)$recurring_id. "' WHERE entity_id = '" .(int)$data['plan_entity_id'] . "'");

        //update plan price in product table
        $this->db->query("UPDATE " . DB_PREFIX . "product SET price = '".(float)$data['price'] . "' WHERE product_id = '" . (int)$data['product_id'] . "'");

        return $recurring_id;
    }

    public function addLayout()
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "layout SET name = 'razorpay' ");

        $layout_id = $this->db->getLastId();

        $this->db->query("INSERT INTO " . DB_PREFIX . "layout_route SET layout_id = '" . (int)$layout_id . "', store_id = 0, route = 'extension/payment/razorpay/subscriptions'");

        $this->db->query("INSERT INTO " . DB_PREFIX . "layout_module SET layout_id = '" . (int)$layout_id . "', code = 'category', position = 'column_right', sort_order = 0 ");
    }

    public function updateOCRecurringStatus( $orderId, $status)
    {
        $query = "UPDATE " . DB_PREFIX . "order_recurring SET status = '".$status. "' ";
        $query = $query ." WHERE order_id = '" . $orderId . "';" ;

        $this->db->query($query);
    }
}
