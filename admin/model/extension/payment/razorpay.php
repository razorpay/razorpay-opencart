<?php
use DB\mPDO;

if(class_exists('mPDO')  === false)
{
    require_once __DIR__ . "/../../../../system/library/db/mPDO.php";
}

class ModelExtensionPaymentRazorpay extends Model
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->rzpPdo = new mPDO(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
    }
    
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

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."rzp_webhook_triggers` (
                `entity_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `order_id` int(11) NOT NULL,
                `rzp_order_id` varchar(25) NOT NULL,
                `rzp_webhook_data` text,
                `rzp_webhook_notified_at` varchar(30),
                `rzp_update_order_cron_status` int(11) DEFAULT 0,
            PRIMARY KEY (`entity_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
    }

    public function dropTables()
    {
        $this->rzpPdo->prepare("DROP TABLE IF EXISTS `" . DB_PREFIX . "razorpay_plans`");
        $this->rzpPdo->execute();

        $this->rzpPdo->prepare("DROP TABLE IF EXISTS `" . DB_PREFIX . "razorpay_subscriptions`");
        $this->rzpPdo->execute();
    }

    public function getPlans($data = array())
    {
        $sql = "SELECT * FROM `" . DB_PREFIX . "razorpay_plans` p";
        $sql .=" LEFT JOIN " . DB_PREFIX . "product_description op ON (op.product_id = p.opencart_product_id)";
        $sql .= " WHERE entity_id > '0'";

        if (!empty($data['filter_plan_id']))
        {
            $sql .= " AND p.plan_id = :filter_plan_id";
        }
        if (!empty($data['filter_plan_status']))
        {
            $sql .= " AND p.plan_status = :filter_plan_status";
        }
        if (!empty($data['filter_plan_name'])) {
            $sql .= " AND p.plan_name LIKE :filter_plan_name";
        }

        if (!empty($data['filter_date_created']))
        {
            $sql .= " AND DATE(p.created_at) = :filter_date_created";
        }

        $sort_data = array(
            'p.plan_id',
            'p.created_at',
            'p.plan_status'
        );

        if (isset($data['sort']) and
            in_array($data['sort'], $sort_data))
        {
            $sql .= " ORDER BY " . $data['sort'];
        }
        else
        {
            $sql .= " ORDER BY p.entity_id";
        }

        if (isset($data['order']) and
            ($data['order'] == 'DESC'))
        {
            $sql .= " DESC";
        }
        else
        {
            $sql .= " ASC";
        }

        if (isset($data['start']) or isset($data['limit']))
        {
            if ($data['start'] < 0)
            {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1)
            {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $this->rzpPdo->prepare($sql);

        if (empty($data['filter_plan_id']) === false)
        {
            $this->rzpPdo->bindParam(':filter_plan_id', $this->db->escape($data['filter_plan_id']));
        }
        if (empty($data['filter_plan_status']) === false)
        {
            $this->rzpPdo->bindParam(':filter_plan_status', (int)$data['filter_plan_status']);
        }
        if (empty($data['filter_plan_name']) === false)
        {
            $this->rzpPdo->bindParam(':filter_plan_name', '%' . $this->db->escape($data['filter_plan_name']) . '%');
        }

        if (empty($data['filter_date_created']) === false)
        {
            $this->rzpPdo->bindParam(':filter_date_created', DATE($this->db->escape($data['filter_date_created'])));
        }
        $query = $this->rzpPdo->execute();

        return $query->rows;
    }

    public function getTotalPlan($data = array())
    {
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "razorpay_plans` p";

        $sql .=" LEFT JOIN " . DB_PREFIX . "product_description op ON (op.product_id = p.opencart_product_id)";

        if (!empty($data['filter_plan_id']))
        {
            $implode = array();

            $sub_statuses = explode(',', $data['filter_plan_id']);

            foreach ($sub_statuses as $sub_id)
            {
                $implode[] = "plan_id = '" . $sub_id . "'";
            }

            if ($implode)
            {
                $sql .= " WHERE (" . implode(" OR ", $implode) . ")";
            }
        }
        elseif (isset($data['filter_plan_name']) and
            $data['filter_plan_name'] !== '')
        {
            $sql .= " WHERE plan_name = :filter_plan_name";
        }
        else
        {
            $sql .= " WHERE plan_name > '0'";
        }

        if (!empty($data['filter_plan_status']))
        {
            $sql .= " AND plan_status = :filter_plan_status";
        }
        if (!empty($data['filter_date_created']))
        {
            $sql .= " AND DATE(p.created_at) = :filter_date_created";
        }

        $this->rzpPdo->prepare($sql);

        if (isset($data['filter_plan_name']) and
            $data['filter_plan_name'] !== '')
        {
            $this->rzpPdo->bindParam(':filter_plan_name', $this->db->escape($data['filter_plan_name']));
        }
        if (empty($data['filter_plan_status']) === false)
        {
            $this->rzpPdo->bindParam(':filter_plan_status', (int)$data['filter_plan_status']);
        }
        if (empty($data['filter_date_created']) === false)
        {
            $this->rzpPdo->bindParam(':filter_date_created', DATE($this->db->escape($data['filter_date_created'])));
        }
        $query = $this->rzpPdo->execute();

        return $query->row['total'];
    }

    public function addPlan($data, $plan_id)
    {
        $this->rzpPdo->prepare("INSERT INTO " . DB_PREFIX . "razorpay_plans SET plan_name = :plan_name, plan_desc = :plan_desc, plan_id = :plan_id, opencart_product_id = :product_id, plan_type = :plan_type, plan_frequency = :billing_frequency, plan_bill_cycle = :billing_cycle, plan_trial = :plan_trial, plan_bill_amount = :billing_amount, plan_addons = :plan_addons, plan_status = :plan_status, created_at = NOW()");

        $this->rzpPdo->bindParam(':plan_name', $this->db->escape($data['plan_name']));
        $this->rzpPdo->bindParam(':plan_desc', $this->db->escape($data['plan_desc']));
        $this->rzpPdo->bindParam(':plan_id', $this->db->escape($plan_id));
        $this->rzpPdo->bindParam(':product_id', $this->db->escape($data['product_id']));
        $this->rzpPdo->bindParam(':plan_type', $this->db->escape($data['plan_type']));
        $this->rzpPdo->bindParam(':billing_frequency', $this->db->escape($data['billing_frequency']));
        $this->rzpPdo->bindParam(':billing_cycle', (int)$data['billing_cycle']);
        $this->rzpPdo->bindParam(':plan_trial', $this->db->escape($data['plan_trial']));
        $this->rzpPdo->bindParam(':billing_amount', $this->db->escape($data['billing_amount']));
        $this->rzpPdo->bindParam(':plan_addons', $this->db->escape($data['plan_addons']));
        $this->rzpPdo->bindParam(':plan_status', (int)$data['plan_status']);

        $this->rzpPdo->execute();

        return $this->rzpPdo->getLastId();
    }

    public function enablePlan($entity_id)
    {
        $this->load->model('localisation/language');

        //fetch and add in recurring table
        $this->rzpPdo->prepare("SELECT * FROM `" . DB_PREFIX . "razorpay_plans` WHERE entity_id= :entity_id and plan_status = '2'");
        $this->rzpPdo->bindParam(':entity_id', (int)$entity_id);
        $query = $this->rzpPdo->execute();
        $planData = $query->row;

        if (empty($planData) === true)
        {
            return;
        }
        
        $planType = $planData['plan_type'];

        if ($planType === "daily")
        {
            $frequency = "day";
        }
        else if ($planType === "weekly")
        {
            $frequency = "week";
        }
        else if ($planType === "monthly")
        {
            $frequency = "month";
        }
        else
        {
            $frequency = "yearly";
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
        $this->rzpPdo->prepare("UPDATE " . DB_PREFIX . "razorpay_plans SET plan_status = '" . 1 . "'WHERE entity_id = :entity_id");
        $this->rzpPdo->bindParam(':entity_id', (int)$entity_id);
        $this->rzpPdo->execute();
    }

    public function disablePlan($entity_id)
    {
        $this->rzpPdo->prepare("UPDATE " . DB_PREFIX . "razorpay_plans SET plan_status = '" . 2 . "' WHERE entity_id = :entity_id");
        $this->rzpPdo->bindParam(':entity_id', (int)$entity_id);
        $this->rzpPdo->execute();
        
        //delete from recurring table;
        $this->deleteRecurring($entity_id);
    }

    public function deleteRecurring($entity_id)
    {
        $this->rzpPdo->prepare("SELECT * FROM `" . DB_PREFIX . "razorpay_plans` WHERE entity_id = :entity_id");
        $this->rzpPdo->bindParam(':entity_id', (int)$entity_id);
        $query = $this->rzpPdo->execute();
        $planData = $query->row;
        $recurring_id = $planData['recurring_id'];

        $this->rzpPdo->prepare("Delete FROM `" . DB_PREFIX . "product_recurring` WHERE recurring_id = :recurring_id");
        $this->rzpPdo->bindParam(':recurring_id', (int)$recurring_id);
        $this->rzpPdo->execute();

        $this->rzpPdo->prepare("Delete FROM `" . DB_PREFIX . "recurring` WHERE recurring_id = :recurring_id");
        $this->rzpPdo->bindParam(':recurring_id', (int)$recurring_id);
        $this->rzpPdo->execute();

        $this->rzpPdo->prepare("Delete FROM `" . DB_PREFIX . "recurring_description` WHERE recurring_id = :recurring_id");
        $this->rzpPdo->bindParam(':recurring_id', (int)$recurring_id);
        $this->rzpPdo->execute();
    }

    public function getSubscription($data = array())
    {
        $sql = "SELECT s.*,p.plan_id,op.name,c.firstname,c.lastname FROM `" . DB_PREFIX . "razorpay_subscriptions` s";
        $sql .=" LEFT JOIN " . DB_PREFIX . "razorpay_plans p ON (p.entity_id = s.plan_entity_id)";
        $sql .=" LEFT JOIN " . DB_PREFIX . "product_description op ON (op.product_id = p.opencart_product_id)";
        $sql .=" LEFT JOIN " . DB_PREFIX . "customer c ON (s.opencart_user_id = c.customer_id )";

        $sql .= " WHERE s.entity_id > '0'";

        if (!empty($data['filter_subscription_id']))
        {
            $sql .= " AND s.subscription_id LIKE :filter_subscription_id";
        }
        if (!empty($data['filter_plan_name']))
        {
            $sql .= " AND p.plan_id LIKE :filter_plan_name";
        }
        if (!empty($data['filter_subscription_status']))
        {
            $sql .= " AND s.status LIKE :filter_subscription_status";
        }
        if (!empty($data['filter_date_created']))
        {
            $sql .= " AND DATE(s.created_at) = DATE('" . $this->db->escape($data['filter_date_created']) . "')";
        }

        $sort_data = array(
            's.subscription_id',
            's.created_at',
            's.status'
        );

        if (isset($data['sort']) and
            in_array($data['sort'], $sort_data))
        {
            $sql .= " ORDER BY " . $data['sort'];
        }
        else
        {
            $sql .= " ORDER BY p.entity_id";
        }

        if (isset($data['order']) and
            ($data['order'] == 'DESC'))
        {
            $sql .= " DESC";
        }
        else
        {
            $sql .= " ASC";
        }

        $this->rzpPdo->prepare($sql);
        if (empty($data['filter_subscription_id']) === false)
        {
            $this->rzpPdo->bindParam(':filter_subscription_id', '%' . $this->db->escape($data['filter_subscription_id']) . '%');
        }
        if (empty($data['filter_plan_name']) === false)
        {
            $this->rzpPdo->bindParam(':filter_plan_name', '%' . $this->db->escape($data['filter_plan_name']) . '%');
        }
        if (empty($data['filter_subscription_status']) === false)
        {
            $this->rzpPdo->bindParam(':filter_subscription_status', '%' . $this->db->escape($data['filter_subscription_status']) . '%');
        }
        $query = $this->rzpPdo->execute();
        
        return $query->rows;
    }

    public function getTotalSubscriptions($data = array())
    {
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "razorpay_subscriptions` s";

        $sql .=" LEFT JOIN " . DB_PREFIX . "razorpay_plans p ON (p.entity_id = s.plan_entity_id)";
        $sql .=" LEFT JOIN " . DB_PREFIX . "product_description op ON (op.product_id = p.opencart_product_id)";
        $sql .=" LEFT JOIN " . DB_PREFIX . "customer c ON (s.opencart_user_id = c.customer_id )";

        if (!empty($data['filter_subscription_id']))
        {
            $implode = array();

            $sub_statuses = explode(',', $data['filter_subscription_id']);

            foreach ($sub_statuses as $sub_id)
            {
                $implode[] = "subscription_id = '" . $sub_id . "'";
            }

            if ($implode)
            {
                $sql .= " WHERE (" . implode(" OR ", $implode) . ")";
            }
        }
        elseif (isset($data['filter_plan_name']) and
            $data['filter_plan_name'] !== '')
        {
            $sql .= " WHERE plan_id = :filter_plan_name";
        }
        else
        {
            $sql .= " WHERE plan_id > '0'";
        }

        if (!empty($data['filter_date_created']))
        {
            $sql .= " AND DATE(s.created_at) = DATE('" . $this->db->escape($data['filter_date_created']) . "')";
        }

        $this->rzpPdo->prepare($sql);

        if (isset($data['filter_plan_name']) and
            $data['filter_plan_name'] !== '')
        {
            $this->rzpPdo->bindParam(':filter_plan_name', $this->db->escape($data['filter_plan_name']));
        }
        $query = $this->rzpPdo->execute();
        
        return $query->row['total'];
    }

    public function getSubscriptionInfo($entity_id)
    {
        $sql = "SELECT s.*,s.entity_id as sub_id,s.status as sub_status,s.created_at as sub_created,p.*,op.name,c.firstname,c.lastname FROM `" . DB_PREFIX . "razorpay_subscriptions` s";
        $sql .=" LEFT JOIN " . DB_PREFIX . "razorpay_plans p ON (p.entity_id = s.plan_entity_id)";
        $sql .=" LEFT JOIN " . DB_PREFIX . "product_description op ON (op.product_id = p.opencart_product_id)";
        $sql .=" LEFT JOIN " . DB_PREFIX . "customer c ON (s.opencart_user_id = c.customer_id )";
        $sql .= " WHERE s.entity_id = :entity_id";
        
        $this->rzpPdo->prepare($sql);
        $this->rzpPdo->bindParam(':entity_id', (int)$entity_id);
        $query = $this->rzpPdo->execute();

        return $query->row;
    }

    public function resumeSubscription($entity_id,$updated_by)
    {
        $this->rzpPdo->prepare("UPDATE " . DB_PREFIX . "razorpay_subscriptions SET status = 'active', updated_by = :updated_by WHERE entity_id = :entity_id");
        $this->rzpPdo->bindParam(':updated_by', $updated_by);
        $this->rzpPdo->bindParam(':entity_id', (int)$entity_id);
        $this->rzpPdo->execute();
    }

    public function pauseSubscription($entity_id,$updated_by)
    {
        $this->rzpPdo->prepare("UPDATE " . DB_PREFIX . "razorpay_subscriptions SET status = 'paused', updated_by = :updated_by WHERE entity_id = :entity_id");
        $this->rzpPdo->bindParam(':updated_by', $updated_by);
        $this->rzpPdo->bindParam(':entity_id', (int)$entity_id);
        $this->rzpPdo->execute();
    }

    public function cancelSubscription($entity_id,$updated_by)
    {
        $this->rzpPdo->prepare("UPDATE " . DB_PREFIX . "razorpay_subscriptions SET status = 'cancelled', updated_by = :updated_by WHERE entity_id = :entity_id");
        $this->rzpPdo->bindParam(':updated_by', $updated_by);
        $this->rzpPdo->bindParam(':entity_id', $entity_id);
        $this->rzpPdo->execute();
    }

    public function getSingleSubscription($entity_id)
    {
        $this->rzpPdo->prepare("SELECT * FROM `" . DB_PREFIX . "razorpay_subscriptions` WHERE entity_id=:entity_id");
        $this->rzpPdo->bindParam(':entity_id', $entity_id);
        $query = $this->rzpPdo->execute();
        
        return $query->row;
    }

    public function addRecurring($data)
    {
        $this->rzpPdo->prepare("INSERT INTO `" . DB_PREFIX . "recurring` SET `status` = :status, `price` = :price, `frequency` = :frequency, `duration` = :duration, `cycle` = :cycle, `trial_status` = :trial_status, `trial_price` = :trial_price, `trial_frequency` = :trial_frequency, `trial_duration` = :trial_duration, `trial_cycle` = :trial_cycle");
        $this->rzpPdo->bindParam(':status', (int)$data['status']);
        $this->rzpPdo->bindParam(':price', (float)$data['price']);
        $this->rzpPdo->bindParam(':frequency', $this->db->escape($data['frequency']));
        $this->rzpPdo->bindParam(':duration', (int)$data['duration']);
        $this->rzpPdo->bindParam(':cycle', (int)$data['cycle']);
        $this->rzpPdo->bindParam(':trial_status', (int)$data['trial_status']);
        $this->rzpPdo->bindParam(':trial_price', (float)$data['trial_price']);
        $this->rzpPdo->bindParam(':trial_frequency', $this->db->escape($data['trial_frequency']));
        $this->rzpPdo->bindParam(':trial_duration', (int)$data['trial_duration']);
        $this->rzpPdo->bindParam(':trial_cycle', (int)$data['trial_cycle']);
        $this->rzpPdo->execute();
        
        $recurring_id = $this->rzpPdo->getLastId();

        foreach ($data['languages'] as $language_id => $recurring_description)
        {
            $this->rzpPdo->prepare("INSERT INTO `" . DB_PREFIX . "recurring_description` SET `recurring_id` = :recurring_id, `language_id` = :language_id, `name` = :name");
            $this->rzpPdo->bindParam(':recurring_id', (int)$recurring_id);
            $this->rzpPdo->bindParam(':language_id', (int)$recurring_description['language_id']);
            $this->rzpPdo->bindParam(':name', $this->db->escape($data['plan_name']));
            $this->rzpPdo->execute();
        }

        //product recurring mapping
        $this->rzpPdo->prepare("INSERT INTO `" . DB_PREFIX . "product_recurring` SET `product_id` = :product_id, `recurring_id` = :recurring_id, `customer_group_id` = :customer_group_id");
        $this->rzpPdo->bindParam(':product_id', (int)$data['product_id']);
        $this->rzpPdo->bindParam(':recurring_id', (int)$recurring_id);
        $this->rzpPdo->bindParam(':customer_group_id', (int)$data['customer_group_id']);
        $this->rzpPdo->execute();
        
        // update plan table with recurring id
        $update_plan = "UPDATE " . DB_PREFIX . "razorpay_plans SET recurring_id = :recurring_id WHERE entity_id = :entity_id";
        $this->rzpPdo->prepare($update_plan);
        $this->rzpPdo->bindParam(':recurring_id', (int)$recurring_id);
        $this->rzpPdo->bindParam(':entity_id', (int)$data['plan_entity_id']);
        $this->rzpPdo->execute();
        
        //update plan price in product table
        $update_price = "UPDATE " . DB_PREFIX . "product SET price = :price WHERE product_id = :product_id";
        $this->rzpPdo->prepare($update_price);
        $this->rzpPdo->bindParam(':price', (float)$data['price']);
        $this->rzpPdo->bindParam(':product_id', (int)$data['product_id']);
        $this->rzpPdo->execute();
        
        return $recurring_id;
    }

    public function addLayout()
    {
        $this->rzpPdo->prepare("INSERT INTO " . DB_PREFIX . "layout SET name = :name");
        $this->rzpPdo->bindParam(':name', 'razorpay');
        $this->rzpPdo->execute();

        $layout_id = $this->db->getLastId();

        $this->rzpPdo->prepare("INSERT INTO " . DB_PREFIX . "layout_route SET layout_id = :layout_id, store_id = 0, route = :route");
        $this->rzpPdo->bindParam(':layout_id', (int)$layout_id);
        $this->rzpPdo->bindParam(':route', 'extension/payment/razorpay/subscriptions');
        $this->rzpPdo->execute();

        $this->rzpPdo->prepare("INSERT INTO " . DB_PREFIX . "layout_module SET layout_id = :layout_id, code = :code, position = :position, sort_order = 0 ");
        $this->rzpPdo->bindParam(':layout_id', (int)$layout_id);
        $this->rzpPdo->bindParam(':code', 'category');
        $this->rzpPdo->bindParam(':position', 'column_right');
        $this->rzpPdo->execute();
    }

    public function updateOCRecurringStatus( $orderId, $status)
    {
        $query = "UPDATE " . DB_PREFIX . "order_recurring SET status = :status";
        $query = $query ." WHERE order_id = :order_id" ;

        $this->rzpPdo->prepare($query);
        $this->rzpPdo->bindParam(':status', (int)$status);
        $this->rzpPdo->bindParam(':order_id', (int)$orderId);
        $this->rzpPdo->execute();
    }
}
