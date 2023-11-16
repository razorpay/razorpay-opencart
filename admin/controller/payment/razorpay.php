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

    /* Subscription Modules */
    public function getPlan()
    {
        $this->load->language('extension/razorpay/payment/razorpay');
        $this->load->model('extension/razorpay/payment/razorpay');

        $filter_plan_id = '';
        $filter_plan_name = '';
        $filter_plan_status = '';
        $filter_date_created = '';
        $sort = 'p.entity_id';
        $order = 'DESC';
        $page = 1;

        if (isset($this->request->get['filter_plan_id']) === true)
        {
            $filter_plan_id = trim($this->request->get['filter_plan_id']);
        }

        if (isset($this->request->get['filter_plan_name']) === true)
        {
            $filter_plan_name = trim($this->request->get['filter_plan_name']);
        }

        if (isset($this->request->get['filter_plan_status']) === true)
        {
            $filter_plan_status = trim($this->request->get['filter_plan_status']);
        }

        if (isset($this->request->get['filter_date_created']) === true)
        {
            $filter_date_created = trim($this->request->get['filter_date_created']);
        }

        if (isset($this->request->get['sort']) === true)
        {
            $sort = $this->request->get['sort'];
        }

        if (isset($this->request->get['entity_id']) === true)
        {
            $order = $this->request->get['entity_id'];
        }

        if (isset($this->request->get['page']) === true)
        {
            $page = (int)$this->request->get['page'];
        }

        $url = '';

        if (isset($this->request->get['filter_plan_id']) === true)
        {
            $url .= '&filter_plan_id=' . trim($this->request->get['filter_plan_id']);
        }

        if (isset($this->request->get['filter_plan_name']) === true)
        {
            $url .= '&filter_plan_name=' . urlencode(html_entity_decode($this->request->get['filter_plan_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_plan_status']) === true)
        {
            $url .= '&filter_plan_status=' . $this->request->get['filter_plan_status'];
        }

        if (isset($this->request->get['filter_date_created']) === true)
        {
            $url .= '&filter_date_created=' . $this->request->get['filter_date_created'];
        }

        if (isset($this->request->get['sort']) === true)
        {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['plan']) === true)
        {
            $url .= '&plan=' . $this->request->get['plan'];
        }

        if (isset($this->request->get['page']) === true)
        {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('plan_title'),
            'href' => $this->url->link('extension/razorpay/payment/razorpay.getPlan', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['plans'] = array();

        $filter_data = array(
            'filter_plan_id'        => $filter_plan_id,
            'filter_plan_name'      => $filter_plan_name,
            'filter_plan_status'    => $filter_plan_status,
            'filter_date_created'   => $filter_date_created,
            'sort'                  => $sort,
            'order'                 => $order,
            'start'                 => ($page - 1) *10,
            'limit'                 =>10
        );
        $plan_total = $this->model_extension_razorpay_payment_razorpay->getTotalPlan($filter_data);

        $results = $this->model_extension_razorpay_payment_razorpay->getPlans($filter_data);

        foreach ($results as $result)
        {
            $data['plans'][] = [
                'entity_id'         => $result['entity_id'],
                'plan_id'           => $result['plan_id'],
                'plan_name'         => $result['plan_name'],
                'plan_desc'         => $result['plan_desc'],
                'name'              => $result['name'],
                'plan_type'         => $result['plan_type'],
                'plan_frequency'    => $result['plan_frequency'],
                'plan_bill_cycle'   => $result['plan_bill_cycle'],
                'plan_trial'        => $result['plan_trial'],
                'plan_bill_amount'  => $result['plan_bill_amount'],
                'plan_addons'       => $result['plan_addons'],
                'plan_status'       => $result['plan_status'],
                'created_at'        => date($this->language->get('date_format_short'), strtotime($result['created_at'])),
                'view'              => $this->url->link('extension/razorpay/payment/razorpay', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $result['entity_id'] . $url, true),
                'singleEnable'      => $this->url->link('extension/razorpay/payment/razorpay.singleEnable', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $result['entity_id'] . $url, true),
                'singleDisable'     => $this->url->link('extension/razorpay/payment/razorpay.singleDisable', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $result['entity_id'] . $url, true)
            ];
        }

        $data['user_token'] = $this->session->data['user_token'];
        $data['error_warning'] = '';
        $data['success'] = '';
        $data['selected'] = array();

        if (isset($this->error['warning']) === true)
        {
            $data['error_warning'] = $this->error['warning'];
        }

        if (isset($this->session->data['success']) === true)
        {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        }

        if (isset($this->request->post['selected']) === true)
        {
            $data['selected'] = (array)$this->request->post['selected'];
        }

        $url = '';

        if (isset($this->request->get['filter_plan_id']) === true)
        {
            $url .= '&filter_plan_id=' . trim($this->request->get['filter_plan_id']);
        }

        if (isset($this->request->get['filter_plan_name']) === true)
        {
            $url .= '&filter_plan_name=' . urlencode(html_entity_decode($this->request->get['filter_plan_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_plan_status']) === true)
        {
            $url .= '&filter_plan_status=' . trim($this->request->get['filter_plan_status']);
        }

        if (isset($this->request->get['filter_total']) === true)
        {
            $url .= '&filter_total=' . $this->request->get['filter_total'];
        }

        if (isset($this->request->get['filter_date_created']) === true)
        {
            $url .= '&filter_date_created=' . trim($this->request->get['filter_date_created']);
        }

        if ($order == 'ASC')
        {
            $url .= '&order=DESC';
        }
        else
        {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page']) === true)
        {
            $url .= '&page=' . $this->request->get['page'];
        }
        $path = 'extension/razorpay/payment/razorpay.plan_list';
        $data['sort_order'] = $this->url->link($path, 'user_token=' . $this->session->data['user_token'] . '&sort=p.plan_id' . $url, true);
        $data['sort_customer'] = $this->url->link($path, 'user_token=' . $this->session->data['user_token'] . '&sort=plan_name' . $url, true);
        $data['sort_status'] = $this->url->link($path, 'user_token=' . $this->session->data['user_token'] . '&sort=plan_status' . $url, true);

        $data['sort_date_added'] = $this->url->link($path, 'user_token=' . $this->session->data['user_token'] . '&sort=o.date_added' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_plan_id']) === true)
        {
            $url .= '&filter_plan_id=' . trim($this->request->get['filter_plan_id']);
        }

        if (isset($this->request->get['filter_plan_name']) === true)
        {
            $url .= '&filter_plan_name=' . urlencode(html_entity_decode($this->request->get['filter_plan_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_plan_status']) === true)
        {
            $url .= '&filter_plan_status=' . $this->request->get['filter_plan_status'];
        }

        if (isset($this->request->get['filter_date_created']) === true)
        {
            $url .= '&filter_date_created=' . $this->request->get['filter_date_created'];
        }

        if (isset($this->request->get['sort']) === true)
        {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order']) === true)
        {
            $url .= '&order=' . $this->request->get['order'];
        }

        $data['pagination'] = $this->load->controller('common/pagination', [
            'total' => $plan_total,
            'page'  => $page,
            'limit' =>10,
            'url'   => $this->url->link('extension/razorpay/payment/razorpay.getPlan', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true)
        ]);

        $data['results'] = sprintf($this->language->get('text_pagination'), ($plan_total) ? (($page - 1) *10) + 1 : 0, ((($page - 1) *10) > ($plan_total -10)) ? $plan_total : ((($page - 1) *10) +10), $plan_total, ceil($plan_total /10));

        $data['filter_plan_id'] = $filter_plan_id;
        $data['filter_plan_name'] = $filter_plan_name;
        $data['filter_plan_status'] = $filter_plan_status;
        $data['filter_date_created'] = $filter_date_created;
        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['add'] = $this->url->link('extension/razorpay/payment/razorpay.add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['status'] = $this->url->link('extension/razorpay/payment/razorpay.statusPlan', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/razorpay/payment/razorpay_subscription/razorpay_plan_list', $data));
    }

    //for status change
    public function statusPlan()
    {
        $this->load->language('extension/razorpay/payment/razorpay');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/razorpay/payment/razorpay');

        if ((isset($this->request->post['selected'])) and
            ($this->request->post['status']))
        {
            $status = $this->request->post['status'];

            if ($status === '1')
            {
                foreach ($this->request->post['selected'] as $entity_id)
                {
                    $this->model_extension_razorpay_payment_razorpay->enablePlan($entity_id);

                }

                $this->session->data['success'] = $this->language->get('text_enable_success');
            }
            else if ($status === '2')
            {
                foreach ($this->request->post['selected'] as $entity_id)
                {
                    $this->model_extension_razorpay_payment_razorpay->disablePlan($entity_id);
                }

                $this->session->data['success'] = $this->language->get('text_disable_success');
            }
            else
            {
                $this->session->data['warning'] = $this->language->get('text_select_warning');
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

            $this->response->redirect($this->url->link('extension/razorpay/payment/razorpay.getPlan', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getPlan();
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
