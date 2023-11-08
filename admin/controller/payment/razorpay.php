<?php
namespace Opencart\Admin\Controller\Extension\Razorpay\Payment;

require_once __DIR__.'../../../../system/library/razorpay/razorpay-sdk/Razorpay.php';
require_once __DIR__.'../../../../system/library/razorpay/razorpay-lib/createwebhook.php';
use Razorpay\Api\Api;
use Razorpay\Api\Errors;

use Opencart\Admin\Controller\Extension\Razorpay\Payment\CreateWebhook;

class Razorpay extends \Opencart\System\Engine\Controller {
	const WEBHOOK_URL    = HTTP_CATALOG . 'index.php?route=extension/razorpay/payment/razorpay.webhook';
	
	private $error = [];

	public function index(): void {
		try {
			$post = $this->getKeyValueArray(file_get_contents('php://input'));
			$this->language->load('extension/razorpay/payment/razorpay');

			$this->document->setTitle($this->language->get('heading_title'));

			$this->load->model('setting/setting');

			$data['heading_title'] 				= $this->language->get('heading_title');
			$data['text_edit'] 					= $this->language->get('text_edit');
			$data['text_enabled'] 				= $this->language->get('text_enabled');
			$data['text_disabled'] 				= $this->language->get('text_disabled');
			$data['text_all_zones'] 			= $this->language->get('text_all_zones');
			$data['text_yes'] 					= $this->language->get('text_yes');
			$data['text_no'] 					= $this->language->get('text_no');
			$data['entry_key_id'] 				= $this->language->get('entry_key_id');
			$data['entry_key_secret'] 			= $this->language->get('entry_key_secret');
			$data['entry_order_status'] 		= $this->language->get('entry_order_status');
			$data['entry_status'] 				= $this->language->get('entry_status');
			$data['entry_sort_order'] 			= $this->language->get('entry_sort_order');
			$data['entry_payment_action'] 		= $this->language->get('entry_payment_action');
			$data['entry_subscription_status'] 	= $this->language->get('entry_subscription_status');
			$data['button_save'] 				= $this->language->get('button_save');
			$data['button_cancel'] 				= $this->language->get('button_cancel');
			$data['help_key_id'] 				= $this->language->get('help_key_id');
			$data['help_order_status'] 			= $this->language->get('help_order_status');

			if (isset($this->error['warning']))
			{
				$data['error_warning'] = $this->error['warning'];
			}
			else
			{
				$data['error_warning'] = '';
			}

			if (isset($this->error['payment_razorpay_key_id']))
			{
				$data['error_key_id'] = $this->error['payment_razorpay_key_id'];
			}
			else
			{
				$data['error_key_id'] = '';
			}

			if (isset($this->error['payment_razorpay_key_secret']))
			{
				$data['error_key_secret'] = $this->error['payment_razorpay_key_secret'];
			}
			else
			{
				$data['error_key_secret'] = '';
			}

			if (isset($this->error['payment_razorpay_webhook_secret']))
			{
				$data['error_webhook_secret'] = $this->error['payment_razorpay_webhook_secret'];
			}
			else
			{
				$data['error_webhook_secret'] = '';
			}

			$data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' 		=> $this->language->get('text_home'),
				'href' 		=> $this->url->link('common/dashboard', 'user_token='.$this->session->data['user_token'], 'SSL'),
				'separator' => false,
			);

			$data['breadcrumbs'][] = array(
				'text' 		=> $this->language->get('text_extension'),
				'href' 		=> $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=payment', 'SSL'),
				'separator' => ' :: ',
			);

			$data['breadcrumbs'][] = array(
				'text' 		=> $this->language->get('heading_title'),
				'href' 		=> $this->url->link('extension/razorpay/payment/razorpay', 'user_token='.$this->session->data['user_token'], 'SSL'),
				'separator' => ' :: ',
			);

            $data['save'] = $this->url->link('extension/razorpay/payment/razorpay.save', 'user_token=' . $this->session->data['user_token']);

			$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', 'SSL');

			if (isset($post['payment_razorpay_key_id']))
			{
				$data['razorpay_key_id'] = $post['payment_razorpay_key_id'];
			}
			else
			{
				$data['razorpay_key_id'] = $this->config->get('payment_razorpay_key_id');
			}

			if (isset($post['payment_razorpay_key_secret']))
			{
			$data['razorpay_key_secret'] = $post['payment_razorpay_key_secret'];
			}
			else
			{
			$data['razorpay_key_secret'] = $this->config->get('payment_razorpay_key_secret');
			}

			if (isset($post['payment_razorpay_order_status_id']))
			{
				$data['razorpay_order_status_id'] = $post['payment_razorpay_order_status_id'];
			}
			else
			{
				$data['razorpay_order_status_id'] = ($this->config->get('payment_razorpay_order_status_id')) ? $this->config->get('payment_razorpay_order_status_id') : 2;
			}
			$this->load->model('localisation/order_status');

			$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

			if (isset($post['payment_razorpay_status']))
			{
				$data['razorpay_status'] = $post['payment_razorpay_status'];
			}
			else
			{
				$data['razorpay_status'] = $this->config->get('payment_razorpay_status');
			}

			if (isset($post['payment_razorpay_sort_order']))
			{
				$data['razorpay_sort_order'] = $post['payment_razorpay_sort_order'];
			}
			else
			{
				$data['razorpay_sort_order'] = $this->config->get('payment_razorpay_sort_order');
			}

			if (isset($post['payment_razorpay_payment_action']))
			{
				$data['razorpay_payment_action'] = $post['payment_razorpay_payment_action'];
			}
			else
			{
				$data['razorpay_payment_action'] = $this->config->get('payment_razorpay_payment_action');
			}
		
			//Subscription Status
			if (isset($post['payment_razorpay_subscription_status']))
			{
				$data['razorpay_subscription_status'] = $post['payment_razorpay_subscription_status'];
			}
			else
			{
				$data['razorpay_subscription_status'] = $this->config->get('payment_razorpay_subscription_status');
			}

			$data['header'] 		= $this->load->controller('common/header');
			$data['column_left'] 	= $this->load->controller('common/column_left');
			$data['footer'] 		= $this->load->controller('common/footer');

			$this->response->setOutput($this->load->view('extension/razorpay/payment/razorpay', $data));
		}
		catch(\Exception $e) {
			echo(json_encode($e->getMessage()));
			echo(json_encode($e->getTrace()));
		}
	}

	protected function validate()
    {
		$postStr = explode("&", file_get_contents('php://input'));
		$post = [];
		foreach ($postStr as $ele) {
			$row = explode("=", $ele);
			$post[$row[0]] = $row[1];
		}
		
        if (!$this->user->hasPermission('modify', 'extension/razorpay/payment/razorpay'))
        {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!isset($post['payment_razorpay_key_id']))
        {
            $this->error['payment_razorpay_key_id'] = $this->language->get('error_key_id');
        }

        if (!isset($post['payment_razorpay_key_secret']))
        {
            $this->error['payment_razorpay_key_secret'] = $this->language->get('error_key_secret');
        }

        if (!$this->error)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

	public function save(): void {
		$this->load->language('extension/razorpay/payment/razorpay');
		$configData = [];
		$json = [];
		$post = $this->getKeyValueArray(file_get_contents('php://input'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate())
		{
			$keyIdSecretValidationResult = ( 
												substr($post['payment_razorpay_key_id'], 0, 4) === 'rzp_' and 
												(
													substr($post['payment_razorpay_key_id'], 4, 4) === 'test' or
													substr($post['payment_razorpay_key_id'], 4, 4) === 'live'
												)
											);
			
			if ($keyIdSecretValidationResult) 
			{
				$createWebhook = new CreateWebhook(
					$post['payment_razorpay_key_id'],
					$post['payment_razorpay_key_secret'],
					$this->config->get('payment_razorpay_webhook_secret'),
					self::WEBHOOK_URL,
					$post['payment_razorpay_subscription_status']
				);
	
				$webhookConfigData = $createWebhook->autoCreateWebhook();
	
				if(array_key_exists('error', $webhookConfigData))
				{
					$this->error['warning'] = $this->language->get('enable_subscription_flag');
				}
				else if($webhookConfigData['payment_razorpay_webhook_status'] == 0) 
				{
					$json['error'] = 'Error: Couldn\'t create webhook. Please try again';
				}
				else
				{
					$configData = array_merge($post, $webhookConfigData);
					$this->model_setting_setting->editSetting('payment_razorpay', $configData);
					$this->session->data['success'] = $this->language->get('text_success');
				}
			}
			else 
			{
				$json['error'] = 'Error: Please enter valid Razorpay Key id';
			}
		}

		if (!$this->user->hasPermission('modify', 'extension/razorpay/payment/razorpay')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			if (count($configData) !== 0) {
				$this->model_setting_setting->editSetting('payment_razorpay', $configData);
			}
			else {
				$this->model_setting_setting->editSetting('payment_razorpay', $post);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		try {
			$this->load->model('extension/razorpay/payment/razorpay');
		
			/* Rzp subscriptions tables */
			$this->model_extension_razorpay_payment_razorpay->createTables();
			$this->model_extension_razorpay_payment_razorpay->addLayout();
		}
		catch(\Exception $e) {
			echo(json_encode($e->getMessage()));
			echo(json_encode($e->getTrace()));
		}
	}

	public function uninstall(): void {
		try{ 
			$this->load->model('extension/razorpay/payment/razorpay');
    	    
			/* Rzp subscriptions tables */
			$this->model_extension_razorpay_payment_razorpay->dropTables();
		}
		catch(\Exception $e) {
			echo(json_encode($e->getMessage()));
			echo(json_encode($e->getTrace()));
		}
	}

    protected function getForm()
    {
        $data['text_form'] = !isset($this->request->get['entity_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->request->get['entity_id']))
        {
            $data['entity_id'] = (int)$this->request->get['entity_id'];
        }
        else
        {
            $data['entity_id'] = 0;
        }

        if (isset($this->error['warning']))
        {
            $data['error_warning'] = $this->error['warning'];
        }
        else
        {
            $data['error_warning'] = '';
        }

        //fields
        if (isset($this->error['plan_name']))
        {
            $data['error_plan_name'] = $this->error['plan_name'];
        }
        else
        {
            $data['error_plan_name'] = '';
        }

        if (isset($this->error['plan_desc']))
        {
            $data['error_plan_desc'] = $this->error['plan_desc'];
        }
        else
        {
            $data['error_plan_desc'] = '';
        }

        if (isset($this->error['product-name']))
        {
            $data['error_product_name'] = $this->error['product-name'];
        }
        else
        {
            $data['error_product_name'] = '';
        }

        if (isset($this->error['billing_frequency']))
        {
            $data['error_billing_frequency'] = $this->error['billing_frequency'];
        }
        else
        {
            $data['error_billing_frequency'] = '';
        }

        if (isset($this->error['billing_cycle']))
        {
            $data['error_billing_cycle'] = $this->error['billing_cycle'];
        }
        else
        {
            $data['error_billing_cycle'] = '';
        }

        if (isset($this->error['billing_amount']))
        {
            $data['error_billing_amount'] = $this->error['billing_amount'];
        }
        else
        {
            $data['error_billing_amount'] = '';
        }

        $url = '';

        if (isset($this->request->get['sort']))
        {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order']))
        {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page']))
        {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/razorpay/payment/razorpay.getPlan', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['entity_id']))
        {
            $data['action'] = $this->url->link('extension/razorpay/payment/razorpay.add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        }
        else
        {
            $data['action'] = $this->url->link('extension/razorpay/payment/razorpay.edit', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $this->request->get['entity_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('extension/razorpay/payment/razorpay.getPlan', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['user_token'] = $this->session->data['user_token'];

        //fields
        if (isset($this->request->post['code']))
        {
            $data['code'] = $this->request->post['code'];
        }
        elseif (!empty($voucher_info))
        {
            $data['code'] = $voucher_info['code'];
        }
        else
        {
            $data['code'] = '';
        }

        if (isset($this->request->post['from_name']))
        {
            $data['from_name'] = $this->request->post['from_name'];
        }
        elseif (!empty($voucher_info))
        {
            $data['from_name'] = $voucher_info['from_name'];
        }
        else
        {
            $data['from_name'] = '';
        }

        if (isset($this->request->post['from_email']))
        {
            $data['from_email'] = $this->request->post['from_email'];
        }
        elseif (!empty($voucher_info))
        {
            $data['from_email'] = $voucher_info['from_email'];
        }
        else
        {
            $data['from_email'] = '';
        }

        if (isset($this->request->post['to_name']))
        {
            $data['to_name'] = $this->request->post['to_name'];
        }
        elseif (!empty($voucher_info))
        {
            $data['to_name'] = $voucher_info['to_name'];
        }
        else
        {
            $data['to_name'] = '';
        }

        if (isset($this->request->post['to_email']))
        {
            $data['to_email'] = $this->request->post['to_email'];
        }
        elseif (!empty($voucher_info))
        {
            $data['to_email'] = $voucher_info['to_email'];
        }
        else
        {
            $data['to_email'] = '';
        }

        $this->load->model('sale/voucher_theme');

        $data['voucher_themes'] = $this->model_sale_voucher_theme->getVoucherThemes();

        if (isset($this->request->post['voucher_theme_id']))
        {
            $data['voucher_theme_id'] = $this->request->post['voucher_theme_id'];
        }
        elseif (!empty($voucher_info))
        {
            $data['voucher_theme_id'] = $voucher_info['voucher_theme_id'];
        }
        else
        {
            $data['voucher_theme_id'] = '';
        }

        if (isset($this->request->post['message']))
        {
            $data['message'] = $this->request->post['message'];
        }
        elseif (!empty($voucher_info))
        {
            $data['message'] = $voucher_info['message'];
        }
        else
        {
            $data['message'] = '';
        }

        if (isset($this->request->post['amount']))
        {
            $data['amount'] = $this->request->post['amount'];
        }
        elseif (!empty($voucher_info))
        {
            $data['amount'] = $voucher_info['amount'];
        }
        else
        {
            $data['amount'] = '';
        }

        if (isset($this->request->post['status']))
        {
            $data['status'] = $this->request->post['status'];
        }
        elseif (!empty($voucher_info))
        {
            $data['status'] = $voucher_info['status'];
        }
        else
        {
            $data['status'] = true;
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/razorpay/payment/razorpay_subscription/razorpay_plan_form', $data));
    }

    protected function validateForm()
    {
        if (!$this->user->hasPermission('modify', 'extension/razorpay/payment/razorpay'))
        {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((strlen($this->request->post['plan_name']) < 1) or
            (strlen($this->request->post['plan_name']) > 64))
        {
            $this->error['plan_name'] = $this->language->get('error_plan_name');
        }
        if ((strlen($this->request->post['product_id']) < 1) or
            (strlen($this->request->post['product_id']) > 64))
        {
            $this->error['product-name'] = $this->language->get('error_product_name');
        }
        if ((strlen($this->request->post['plan_desc']) < 1) or
            (strlen($this->request->post['plan_desc']) > 64))
        {
            $this->error['plan_desc'] = $this->language->get('error_plan_desc');
        }

        if ((!isset($this->request->get['billing_cycle'])) and
            ($this->request->post['billing_cycle'] < 1))
        {
            $this->error['billing_cycle'] = $this->language->get('error_billing_cycle');
        }
        if ((!isset($this->request->get['billing_amount'])) and
            ($this->request->post['billing_amount'] < 1))
        {
            $this->error['billing_amount'] = $this->language->get('error_billing_amount');
        }
        //validate for daily plan type
        if ((($this->request->post['plan_type']) =='daily') and
            ($this->request->post['billing_frequency'] < 7))
        {
            $this->error['billing_frequency'] = $this->language->get('error_billing_frequency_daily');
        }

        if (((!isset($this->request->get['billing_frequency'])) and
            ($this->request->post['billing_frequency'] < 1) and
            ($this->request->post['plan_type']) !='daily'))
        {
            $this->error['billing_frequency'] = $this->language->get('error_billing_frequency');
        }

        return !$this->error;
    }

    public function add()
    {
        $this->load->language('extension/razorpay/payment/razorpay');
        $this->load->model('extension/razorpay/payment/razorpay');

        $this->document->setTitle($this->language->get('heading_title'));

        $subscription_status= $this->config->get('payment_razorpay_subscription_status');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') and
            $this->validateForm())
        {
            $data['plan_name'] = $planName=$this->request->post['plan_name'];
            $planDesc=$this->request->post['plan_desc'];
            $data['product_id'] = $productId=$this->request->post['product_id'];
            $planType=$this->request->post['plan_type'];
            $data['duration'] = $frequency=$this->request->post['billing_frequency'];
            $data['cycle'] =  $billCycle=$this->request->post['billing_cycle'];
            $data['price'] = $amount=$this->request->post['billing_amount'];
            $data['trial_price'] =  $trial=$this->request->post['plan_trial'];
            $addons = $this->request->post['plan_addons'];
            $data['status']  = $status= $this->request->post['plan_status'];
            $data['trial_cycle'] =  $data['trial_duration'] = 0;
            $data['trial_frequency'] = "day";
            $data['sort_order'] = 0;

            if ($planType === "daily")
            {
                $post[$key] = isset($val) ? $val : "";
                $data['frequency'] = "day";
            }
            else if ($planType === "weekly")
            {
                $data['frequency'] = "week";
            }
            else if ($planType === "monthly")
            {
                $data['frequency'] = "month";
            }
            else
            {
                $data['frequency'] = "yearly";
            }

            if (!empty($trial))
            {
                $data['trial_status'] = 1;
            }
            else
            {
                $data['trial_status'] = 0;
            }

            //customer group
            $this->load->model('customer/customer_group');

            $data['customer_group_id'] =  $this->config->get('config_customer_group_id');
            $currency = $this->config->get('config_currency');

            //check subscription status
            if($subscription_status === '0')
            {
                $this->error['warning'] = $this->language->get('text_subscription_status');

                if (isset($this->error['warning']))
                {
                    $this->session->data['error_warning'] = $this->error['warning'];
                }
                else
                {
                    $this->session->data['error_warning'] = '';
                }
                $this->getForm();
            }
            else
            {
                // Create Plan API
                try
                {
                    $api = $this->getApiIntance();

                    $plan_data =  array(
                        'period' => $planType,
                        'interval' => $frequency,
                        'item' => array('name' => $planName, 'description' => $planDesc, 'amount' => $amount * 100, 'currency' => $currency),
                        'notes'=> array('trial'=> 'test', 'Addons'=> 'addons')
                    );
                    $razorpay_plan = $api->plan->create($plan_data);

                    $this->log->write("RZP PlanID (:" . $razorpay_plan['id'] . ") created");
                }
                catch(\Razorpay\Api\Errors\Error $e)
                {
                    $this->log->write($e->getMessage());
                    $this->error['warning'] = $e->getMessage();

                    if (isset($this->error['warning']))
                    {
                        $this->session->data['error_warning'] = $this->error['warning'];
                    }
                    else
                    {
                        $this->session->data['error_warning'] = '';
                    }
                    $this->getForm();

                    return;
                }

                $data['plan_entity_id'] = $this->model_extension_razorpay_payment_razorpay->addPlan($this->request->post, $razorpay_plan['id']);

                $this->load->model('localisation/language');

                $languages = $data['languages'] = $this->model_localisation_language->getLanguages();

                $this->model_extension_razorpay_payment_razorpay->addSubscription($data);

                $this->session->data['success'] = $this->language->get('text_plan_success');

                $url = '';

                if (isset($this->request->get['sort']))
                {
                    $url .= '&sort=' . $this->request->get['sort'];
                }

                if (isset($this->request->get['order']))
                {
                    $url .= '&order=' . $this->request->get['order'];
                }

                if (isset($this->request->get['page']))
                {
                    $url .= '&page=' . $this->request->get['page'];
                }

                $this->response->redirect($this->url->link('extension/razorpay/payment/razorpay.getPlan', 'user_token=' . $this->session->data['user_token'] . $url, true));
            }
            // subscription status ends
        }

        if ($subscription_status === '0')
        {
            $this->error['warning'] = $this->language->get('text_subscription_status');
            if (isset($this->error['warning']))
            {
                $this->session->data['error_warning'] = $this->error['warning'];
            }
            else
            {
                $this->session->data['error_warning'] = '';
            }
        }

        $this->getForm();
    }

    protected function getApiIntance()
    {
        return new Api($this->config->get('payment_razorpay_key_id'), $this->config->get('payment_razorpay_key_secret'));
    }

	protected function getKeyValueArray($inputString) {
		$postStr = explode("&", $inputString);
		$post = [];
		
		foreach ($postStr as $ele) {
			$row = explode("=", $ele);
			$key = isset($row[0]) ? $row[0] : "";
			$val = isset($row[1]) ? $row[1] : "";
			if ($row[0] !== "") 
			{
				$post[$key] = isset($val) ? $val : "";
			}
		}

		return $post;
	}
}
