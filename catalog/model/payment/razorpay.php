<?php
namespace Opencart\Catalog\Model\Extension\Razorpay\Payment;

use Opencart\Admin\Controller\Extension\Razorpay\Payment\MPDO;

if(class_exists('mPDO')  === false)
{
    require_once __DIR__ . "../../../../system/library/db/mPDO.php";
}

class Razorpay extends \Opencart\System\Engine\Model {
	const RECURRING_ACTIVE      = 1;
    const RECURRING_INACTIVE    = 2;
    const RECURRING_CANCELLED   = 3;
    const RECURRING_SUSPENDED   = 4;
    const RECURRING_EXPIRED     = 5;
    const RECURRING_PENDING     = 6;

    const PLAN_TYPE = [
        'day' => "daily",
        'week' => "weekly",
        'month' => "monthly",
        'year' => "yearly"
    ];

	public function __construct(\Opencart\System\Engine\Registry $registry)
    {
        parent::__construct($registry);
        $this->rzpPdo = new mPDO(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
    }

	public function getMethods($address)
    {
        $this->language->load('extension/razorpay/payment/razorpay');

        $option_data['razorpay'] = [
            'code' => 'razorpay.razorpay',
            'name' => $this->language->get('text_title')
        ];

        $method_data = array(
            'code'          => 'razorpay',
            'option'        => $option_data,
            'name'          => $this->language->get('heading_title'),
            'sort_order'    => $this->config->get('payment_razorpay_sort_order'),
        );

        // $method_data = [
            // 			'code'       => 'credit_card',
            // 			'name'       => $this->language->get('heading_title'),
            // 			'option'     => $option_data,
            // 			'sort_order' => $this->config->get('payment_credit_card_sort_order')
            // 		];
        return $method_data;
    }

	public function editSetting($code, $data, $store_id = 0)
    {
        foreach ($data as $key => $value)
        {
            $this->rzpPdo->prepare("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = :store_id AND `code` = :code AND `key` = :key");
            $this->rzpPdo->bindParam(':store_id', (int)$store_id);
            $this->rzpPdo->bindParam(':code', $this->db->escape($code));
            $this->rzpPdo->bindParam(':key', $this->db->escape($key));
            $this->rzpPdo->execute();

            if (!is_array($value))
            {
                $this->rzpPdo->prepare("INSERT INTO " . DB_PREFIX . "setting SET store_id = :store_id, `code` = :code, `key` = :key, `value` = :value");
                $this->rzpPdo->bindParam(':store_id', (int)$store_id);
                $this->rzpPdo->bindParam(':code', $this->db->escape($code));
                $this->rzpPdo->bindParam(':key', $this->db->escape($key));
                $this->rzpPdo->bindParam(':value', $this->db->escape($value));
                $this->rzpPdo->execute();
            }
            else
            {
                $this->rzpPdo->prepare("INSERT INTO " . DB_PREFIX . "setting SET store_id = :store_id, `code` = :code, `key` = :key, `value` = :value, serialized = '1'");
                $this->rzpPdo->bindParam(':store_id', (int)$store_id);
                $this->rzpPdo->bindParam(':code', $this->db->escape($code));
                $this->rzpPdo->bindParam(':key', $this->db->escape($key));
                $this->rzpPdo->bindParam(':value', $this->db->escape(json_encode($value, true)));
                $this->rzpPdo->execute();
            }
        }
    }

	// Subscription
    public function saveSubscriptionDetails($subscriptionData, $planData, $customerId, $order_id)
    {
        $query = "INSERT INTO " . DB_PREFIX . "razorpay_subscriptions SET plan_entity_id = :entity_id, subscription_id = :subscription_id,";
        $query = $query . " product_id = :product_id, razorpay_customer_id = :customerId, qty = :quantity,";
        $query = $query . " status = :status, opencart_user_id = :opencart_user_id, total_count = :total_count,";
        $query = $query . " paid_count = :paid_count, remaining_count = :remaining_count, order_id = :order_id";

        if (isset($subscriptionData['start_at']))
        {
            $query = $query . ", start_at = :start_at";
        }

        if (isset($subscriptionData['created_at']))
        {
            $query = $query . ", subscription_created_at = :subscription_created_at";
        }

        if (isset($subscriptionData['charge_at']))
        {
            $query = $query . ", next_charge_at = :next_charge_at";
        }

        $this->rzpPdo->prepare($query);
        $this->rzpPdo->bindParam(':entity_id', (int)$planData['entity_id']);
        $this->rzpPdo->bindParam(':subscription_id', $this->db->escape($subscriptionData['id']));
        $this->rzpPdo->bindParam(':product_id', (int)$planData['opencart_product_id']);
        $this->rzpPdo->bindParam(':customerId', $this->db->escape($customerId));
        $this->rzpPdo->bindParam(':quantity', (int)$subscriptionData['quantity']);
        $this->rzpPdo->bindParam(':status', $subscriptionData['status']);
        $this->rzpPdo->bindParam(':opencart_user_id', (int)$this->customer->getId());
        $this->rzpPdo->bindParam(':total_count', (int)$subscriptionData['total_count']);
        $this->rzpPdo->bindParam(':paid_count', (int)$subscriptionData['paid_count']);
        $this->rzpPdo->bindParam(':remaining_count', (int)$subscriptionData['remaining_count']);
        $this->rzpPdo->bindParam(':order_id', (int)$order_id );

        if (isset($subscriptionData['start_at']))
        {
            $this->rzpPdo->bindParam(':start_at', date("Y-m-d h:i:sa", $subscriptionData['start_at']));
        }

        if (isset($subscriptionData['created_at']))
        {
            $this->rzpPdo->bindParam(':subscription_created_at', date("Y-m-d h:i:sa", $subscriptionData['created_at']));
        }

        if (isset($subscriptionData['charge_at']))
        {
            $this->rzpPdo->bindParam(':next_charge_at', date("Y-m-d h:i:sa", $subscriptionData['charge_at']));
        }

        $this->rzpPdo->execute();
    }

	public function updateSubscription($subscriptionData, $subscriptionId)
    {
        $query = "UPDATE " . DB_PREFIX . "razorpay_subscriptions SET  qty = :quantity,";
        $query = $query . " status = :status, total_count = :total_count,";
        $query = $query . "  paid_count = :paid_count, remaining_count = :remaining_count";

        if(isset($subscriptionData['start_at']))
        {
            $query = $query . ", start_at = :start_at";
        }

        if(isset($subscriptionData['charge_at']))
        {
            $query = $query . ", next_charge_at = :next_charge_at";
        }

        if(isset($subscriptionData['end_at']))
        {
            $query = $query . ", end_at = :end_at";
        }

        $query = $query ." WHERE subscription_id = '" . $subscriptionId . "'";
        $this->rzpPdo->prepare($query);
        $this->rzpPdo->bindParam(':quantity', (int)$subscriptionData['quantity']);
        $this->rzpPdo->bindParam(':status', $this->db->escape($subscriptionData['status']));
        $this->rzpPdo->bindParam(':total_count', (int)$subscriptionData['total_count']);
        $this->rzpPdo->bindParam(':paid_count', (int)$subscriptionData['paid_count']);
        $this->rzpPdo->bindParam(':remaining_count', (int)$subscriptionData['remaining_count']);

        if (isset($subscriptionData['start_at']))
        {
            $this->rzpPdo->bindParam(':start_at', date("Y-m-d h:i:sa", $subscriptionData['start_at']));
        }

        if (isset($subscriptionData['charge_at']))
        {
            $this->rzpPdo->bindParam(':next_charge_at', date("Y-m-d h:i:sa", $subscriptionData['charge_at']));
        }

        if (isset($subscriptionData['end_at']))
        {
            $this->rzpPdo->bindParam(':end_at', date("Y-m-d h:i:sa", $subscriptionData['end_at']));
        }

        $this->rzpPdo->execute();
    }

	public function getTotalOrderRecurring()
    {
        $this->rzpPdo->prepare("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "razorpay_subscriptions` WHERE `opencart_user_id` = :opencart_user_id");
        $this->rzpPdo->bindParam(':opencart_user_id', (int)$this->customer->getId());
        $query = $this->rzpPdo->execute();

        return $query->row['total'];
    }

	public function getSubscriptionByUserId($start = 0, $limit = 20)
    {
        if ($start < 0)
        {
            $start = 0;
        }

        if ($limit < 1)
        {
            $limit = 1;
        }

        $this->rzpPdo->prepare("SELECT rs.*, pd.name AS productName  FROM `" . DB_PREFIX . "razorpay_subscriptions` rs LEFT JOIN `" . DB_PREFIX . "product_description` pd on pd.product_id = rs.product_id WHERE rs.opencart_user_id = :opencart_user_id ORDER BY rs.entity_id DESC LIMIT ". (int)$start . "," . (int)$limit);
        $this->rzpPdo->bindParam(':opencart_user_id', (int)$this->customer->getId());
        $query = $this->rzpPdo->execute();

        return $query->rows;
    }	

	public function getSubscriptionDetails($subscriptionId)
    {
        $this->rzpPdo->prepare("SELECT rs.*, pd.name AS productName, rpln.plan_name, rpln.plan_type, rpln.plan_id FROM " . DB_PREFIX . "razorpay_subscriptions rs LEFT JOIN " . DB_PREFIX . "razorpay_plans rpln on rs.plan_entity_id = rpln.entity_id  LEFT JOIN " . DB_PREFIX . "product_description pd on pd.product_id = rs.product_id WHERE `subscription_id` = :subscriptionId");
        $this->rzpPdo->bindParam(':subscriptionId', $this->db->escape($subscriptionId));
        $query = $this->rzpPdo->execute();

        return $query->row;
    }

	public function getSubscriptionById($subscriptionId)
    {
        $this->rzpPdo->prepare("SELECT *  FROM " . DB_PREFIX . "razorpay_subscriptions WHERE `subscription_id` = :subscriptionId");
        $this->rzpPdo->bindParam(':subscriptionId', $this->db->escape($subscriptionId));
        $query = $this->rzpPdo->execute();

        return $query->row;
    }

	public function updateSubscriptionStatus($subscriptionId, $status, $user = null)
    {
        $query = "UPDATE " . DB_PREFIX . "razorpay_subscriptions SET status = :status";

        if($user)
        {
            $query = $query . ", updated_by = :updated_by " ;
        }
        $query = $query ." WHERE subscription_id = :subscriptionId ";

        $this->rzpPdo->prepare($query);
        $this->rzpPdo->bindParam(':status', $this->db->escape($status));

        if ($user)
        {
            $this->rzpPdo->bindParam(':updated_by', $this->db->escape($user));
        }
        $this->rzpPdo->bindParam(':subscriptionId', $this->db->escape($subscriptionId));
        $this->rzpPdo->execute();
    }

	public function updateSubscriptionPlan($planData)
    {
        $query = "UPDATE " . DB_PREFIX . "razorpay_subscriptions SET plan_entity_id = :plan_entity_id";

        if($planData["qty"])
        {
            $query = $query .", qty = :qty" ;
        }
        $query = $query ." WHERE subscription_id = '" . $this->db->escape($planData["subscriptionId"]) . "'";
        $this->rzpPdo->prepare($query);
        $this->rzpPdo->bindParam(':plan_entity_id', (int)$planData['plan_entity_id']);
        if ($planData["qty"])
        {
            $this->rzpPdo->bindParam(':qty', (int)$planData["qty"]);
        }

        $this->rzpPdo->execute();
    }

    public function getProductBasedPlans($productId)
    {
        $this->rzpPdo->prepare("SELECT * FROM " . DB_PREFIX . "razorpay_plans WHERE plan_status = 1 AND opencart_product_id = :productId");
        $this->rzpPdo->bindParam(':productId', (int)$productId);
        $query = $this->rzpPdo->execute();

        return $query->rows;
    }

    public function getPlanByRecurringIdAndFrequencyAndProductId($recurringId, $planType, $productId)
    {
        $this->rzpPdo->prepare("SELECT * FROM " . DB_PREFIX
            . "razorpay_plans WHERE recurring_id = :recurringId AND plan_type = '"
            . self::PLAN_TYPE[$planType] . "' AND opencart_product_id = :productId");
        $this->rzpPdo->bindParam(':recurringId', (int)$recurringId);
        $this->rzpPdo->bindParam(':productId', (int)$productId);
        $query = $this->rzpPdo->execute();

        return $query->row;
    }

    public function fetchPlanByEntityId($planEntityId)
    {
        $this->rzpPdo->prepare("SELECT * FROM " . DB_PREFIX
            . "razorpay_plans WHERE `plan_status` = 1 AND `entity_id` = :planEntityId");
        $this->rzpPdo->bindParam(':planEntityId', (int)$planEntityId);
        $query = $this->rzpPdo->execute();

        return $query->row;
    }

    public function fetchRZPPlanById($planId)
    {
        $this->rzpPdo->prepare("SELECT * FROM " . DB_PREFIX . "razorpay_plans WHERE `plan_status` = 1 AND `plan_id` = :planId");
        $this->rzpPdo->bindParam(':planId', $this->db->escape($planId));
        $query = $this->rzpPdo->execute();

        return $query->row;
    }

    public function recurringPayments()
    {
        return (bool)$this->config->get('payment_razorpay_subscription_status');
    }

    public function createOCRecurring($recurringData)
    {
        $query = "INSERT INTO `" . DB_PREFIX . "order_recurring` SET `order_id` = :order_id, `date_added` = NOW(), `status` = '" . self::RECURRING_PENDING . "',";
        $query = $query . " `product_id` = :product_id, `product_name` = :product_name,";
        $query = $query . " `product_quantity` = :product_quantity, `recurring_id` = :recurring_id,";
        $query = $query . " `recurring_name` = :recurring_name, `recurring_description` = :recurring_description,";
        $query = $query . " `recurring_frequency` = :recurring_frequency, `recurring_cycle` = :recurring_cycle,";
        $query = $query . " `recurring_duration` = :recurring_duration, `recurring_price` = :recurring_price,";
        $query = $query . " `trial` = :trial, `trial_frequency` = :trial_frequency,";
        $query = $query . " `trial_cycle` = :trial_cycle, `trial_duration` = :trial_duration,";
        $query = $query . " `trial_price` = :trial_price, `reference` = :reference";

        $this->rzpPdo->prepare($query);
        $this->rzpPdo->bindParam(':order_id', (int)$recurringData['order_id']);
        $this->rzpPdo->bindParam(':product_id', (int)$recurringData['product_id']);
        $this->rzpPdo->bindParam(':product_name', $this->db->escape($recurringData['product_name']));
        $this->rzpPdo->bindParam(':product_quantity', $this->db->escape($recurringData['product_quantity']));
        $this->rzpPdo->bindParam(':recurring_id', (int)$recurringData['recurring_id']);
        $this->rzpPdo->bindParam(':recurring_name', $this->db->escape($recurringData['recurring_name']));
        $this->rzpPdo->bindParam(':recurring_description', $this->db->escape($recurringData['recurring_description']));
        $this->rzpPdo->bindParam(':recurring_frequency', $this->db->escape($recurringData['recurring_frequency']));
        $this->rzpPdo->bindParam(':recurring_cycle', (int)$recurringData['recurring_cycle']);
        $this->rzpPdo->bindParam(':recurring_duration', (int)$recurringData['recurring_duration']);
        $this->rzpPdo->bindParam(':recurring_price', (float)$recurringData['recurring_price']);
        $this->rzpPdo->bindParam(':trial', (int)$recurringData['trial']);
        $this->rzpPdo->bindParam(':trial_frequency', $this->db->escape($recurringData['trial_frequency']));
        $this->rzpPdo->bindParam(':trial_cycle', (int)$recurringData['trial_cycle']);
        $this->rzpPdo->bindParam(':trial_duration', (int)$recurringData['trial_duration']);
        $this->rzpPdo->bindParam(':trial_price', (float)$recurringData['trial_price']);
        $this->rzpPdo->bindParam(':reference', $this->db->escape($recurringData['reference']));

        return $this->rzpPdo->execute();
    }

    public function updateOCRecurringStatus( $orderId, $status)
    {
        $this->rzpPdo->prepare("UPDATE " . DB_PREFIX . "order_recurring SET status = :status WHERE order_id =:order_id");
        $this->rzpPdo->bindParam(':status', (int)$status);
        $this->rzpPdo->bindParam(':order_id', (int)$orderId);
        $this->rzpPdo->execute();
    }

    public function getOCRecurringStatus($orderId)
    {
        $this->rzpPdo->prepare("SELECT * FROM " . DB_PREFIX . "order_recurring WHERE order_id = :orderId");
        $this->rzpPdo->bindParam(':orderId', (int)$orderId);
        $query = $this->rzpPdo->execute();

        return $query->row;
    }

    public function addOCRecurringTransaction($orderRecurringId, $subscriptionId, $amount, $status)
    {
        $this->rzpPdo->prepare("INSERT INTO `" . DB_PREFIX . "order_recurring_transaction` SET order_recurring_id=:orderRecurringId , reference=:subscriptionId, type=:status, amount=:amount, date_added=NOW()");
        $this->rzpPdo->bindParam(':orderRecurringId', (int)$orderRecurringId);
        $this->rzpPdo->bindParam(':subscriptionId', $this->db->escape($subscriptionId));
        $this->rzpPdo->bindParam(':status', $this->db->escape($status));
        $this->rzpPdo->bindParam(':amount', (float)$amount);
        $this->rzpPdo->execute();
    }

	/* example payment extension functions */
	public function getMethod(array $address): array {
		// echo(json_encode($this->load->language('extension/razorpay/payment/razorpay')));
		$this->load->language('extension/razorpay/payment/razorpay');

		if (!$this->config->get('config_checkout_payment_address')) {
			$status = true;
		} elseif (!$this->config->get('payment_credit_card_geo_zone_id')) {
			$status = true;
		} else {
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_credit_card_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

			if ($query->num_rows) {
				$status = true;
			} else {
				$status = false;
			}
		}

		$method_data = [];

		if ($status) {
			$option_data = [];

			$option_data['credit_card'] = [
				'code' => 'credit_card.credit_card',
				'name' => $this->language->get('text_card_use')
			];

			$results = $this->getCreditCards($this->customer->getId());

			foreach ($results as $result) {
				$option_data[$result['credit_card_id']] = [
					'code' => 'credit_card.' . $result['credit_card_id'],
					'name' => $this->language->get('text_card_use') . ' ' . $result['card_number']
				];
			}

			$method_data = [
				'code'       => 'credit_card',
				'name'       => $this->language->get('heading_title'),
				'option'     => $option_data,
				'sort_order' => $this->config->get('payment_credit_card_sort_order')
			];
		}

		return $method_data;
	}

	public function getCreditCard(int $customer_id, int $credit_card_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "credit_card` WHERE `customer_id` = '" . (int)$customer_id . "' AND `credit_card_id` = '" . (int)$credit_card_id . "'");

		return $query->row;
	}

	public function getCreditCards(int $customer_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "credit_card` WHERE `customer_id` = '" . (int)$customer_id . "'");

		return $query->rows;
	}

	public function addCreditCard(int $customer_id, array $data): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "credit_card` SET `customer_id` = '" . (int)$customer_id . "', `card_name` = '" . $this->db->escape($data['card_name']) . "', `card_number` = '" . $this->db->escape($data['card_number']) . "', `card_expire_month` = '" . $this->db->escape($data['card_expire_month']) . "', `card_expire_year` = '" . $this->db->escape($data['card_expire_year']) . "', `card_cvv` = '" . $this->db->escape($data['card_cvv']) . "', `date_added` = NOW()");
	}

	public function deleteCreditCard(int $customer_id, int $credit_card_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "credit_card` WHERE `customer_id` = '" . (int)$customer_id . "' AND `credit_card_id` = '" . (int)$credit_card_id . "'");
	}

	public function charge(int $customer_id, int $order_id, float $amount, int $credit_card_id = 0): string {
		//$this->db->query("INSERT INTO `" . DB_PREFIX . "credit_card` SET `customer_id` = '" . (int)$customer_id . "', `card_name` = '" . $this->db->escape($data['card_name']) . "', `card_number` = '" . $this->db->escape($data['card_number']) . "', `card_expire_month` = '" . $this->db->escape($data['card_expire_month']) . "', `card_expire_year` = '" . $this->db->escape($data['card_expire_year']) . "', `card_cvv` = '" . $this->db->escape($data['card_cvv']) . "', `date_added` = NOW()");

		return $this->config->get('payment_credit_card_response');
	}
}
