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
	
    public function getSubscription()
    {
        $this->load->language('extension/razorpay/payment/razorpay');
        $this->load->model('extension/razorpay/payment/razorpay');

        $filter_subscription_id = '';
        $filter_plan_name = '';
        $filter_subscription_status = '';
        $filter_date_created = '';
        $sort = 's.entity_id';
        $order = 'DESC';
        $page = 1;

        if (isset($this->request->get['filter_subscription_id']) === true)
        {
            $filter_subscription_id = trim($this->request->get['filter_subscription_id']);
        }

        if (isset($this->request->get['filter_plan_name']) === true)
        {
            $filter_plan_name = trim($this->request->get['filter_plan_name']);
        }

        if (isset($this->request->get['filter_subscription_status']) === true)
        {
            $filter_subscription_status = $this->request->get['filter_subscription_status'];
        }

        if (isset($this->request->get['filter_date_created']) === true)
        {
            $filter_date_created = $this->request->get['filter_date_created'];
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

        if (isset($this->request->get['filter_subscription_id']) === true)
        {
            $url .= '&filter_subscription_id=' . trim($this->request->get['filter_subscription_id']);
        }

        if (isset($this->request->get['filter_plan_name']) === true)
        {
            $url .= '&filter_plan_name=' . urlencode(html_entity_decode($this->request->get['filter_plan_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_subscription_status']) === true)
        {
            $url .= '&filter_subscription_status=' . $this->request->get['filter_subscription_status'];
        }

        if (isset($this->request->get['filter_date_created']) === true)
        {
            $url .= '&filter_date_created=' . $this->request->get['filter_date_created'];
        }

        if (isset($this->request->get['sort']) === true)
        {
            $url .= '&sort=' . $this->request->get['sort'];
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
            'text' => $this->language->get('subscription_title'),
            'href' => $this->url->link('extension/razorpay/payment/razorpay.getSubscription', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['subscriptions'] = [];

        $filter_data = array(
            'filter_subscription_id'        => $filter_subscription_id,
            'filter_plan_name'              => $filter_plan_name,
            'filter_subscription_status'    => $filter_subscription_status,
            'filter_date_created'           => $filter_date_created,
            'sort'                          => $sort,
            'order'                         => $order,
            'start'                         => ($page - 1) * 10,
            'limit'                         => 10
        );

        $subscription_total = $this->model_extension_razorpay_payment_razorpay->getTotalSubscriptions($filter_data);

        $results = $this->model_extension_razorpay_payment_razorpay->getSubscription($filter_data);

        foreach ($results as $result)
        {
            $data['subscriptions'][] = [
                'entity_id'                 => $result['entity_id'],
                'subscription_id'           => $result['subscription_id'],
                'plan_id'                   => $result['plan_id'],
                'customer_fname'            => $result['firstname'],
                'customer_lname'            => $result['lastname'],
                'product_name'              => $result['name'],
                'status'                    => ucfirst($result['status']),
                'cancel_by'                 => $result['updated_by'],
                'total_count'               => $result['total_count'],
                'paid_count'                => $result['paid_count'],
                'remaining_count'           => $result['remaining_count'],
                'start_at'                  => isset($result['start_at']) ? date($this->language->get('date_format_short'), strtotime($result['start_at'])) : "",
                'end_at'                    => isset($result['end_at']) ? date($this->language->get('date_format_short'), strtotime($result['end_at'])) : "",
                'subscription_created_at'   => $result['subscription_created_at'],
                'next_charge_at'            =>  isset($result['next_charge_at']) ? date($this->language->get('date_format_short'), strtotime($result['next_charge_at'])) : "",
                'created_at'                => isset($result['created_at']) ? date($this->language->get('date_format_short'), strtotime($result['created_at'])) : "",
                'view'                      => $this->url->link('extension/razorpay/payment/razorpay.subscriptionInfo', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $result['entity_id'] . $url, true),
                'singleResume'              => $this->url->link('extension/razorpay/payment/razorpay.changeSingleStatus', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $result['entity_id'] . '&status=1'. $url, true),
                'singlePause'               => $this->url->link('extension/razorpay/payment/razorpay.changeSingleStatus', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $result['entity_id'] . '&status=2'. $url, true),
                'singleCancel'              => $this->url->link('extension/razorpay/payment/razorpay.changeSingleStatus', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $result['entity_id'] . '&status=3'. $url, true)
            ];
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['error_warning'] = '';
        $data['success'] = '';
        $data['selected'] = [];

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

        if (isset($this->request->get['filter_subscription_status']) === true)
        {
            $url .= '&filter_subscription_status=' . $this->request->get['filter_subscription_status'];
        }

        if (isset($this->request->get['filter_subscription_id']) === true)
        {
            $url .= '&filter_subscription_id=' . urlencode(html_entity_decode($this->request->get['filter_subscription_id'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_plan_name']) === true)
        {
            $url .= '&filter_plan_name=' . $this->request->get['filter_plan_name'];
        }

        if (isset($this->request->get['filter_date_created']) === true)
        {
            $url .= '&filter_date_created=' . $this->request->get['filter_date_created'];
        }

        if ($order == 'ASC')
        {
            $url .= '&order=DESC';
        }
        else
        {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page']))
        {
            $url .= '&page=' . $this->request->get['page'];
        }

        $path='extension/razorpay/payment/razorpay.getSubscription';
        $data['sort_order'] = $this->url->link($path, 'user_token=' . $this->session->data['user_token'] . '&sort=s.entity_id' . $url, true);
        $data['sort_customer'] = $this->url->link($path, 'user_token=' . $this->session->data['user_token'] . '&sort=plan_name' . $url, true);
        $data['sort_status'] = $this->url->link($path, 'user_token=' . $this->session->data['user_token'] . '&sort=plan_status' . $url, true);

        $data['sort_date_added'] = $this->url->link($path, 'user_token=' . $this->session->data['user_token'] . '&sort=o.date_added' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_subscription_id']) === true)
        {
            $url .= '&filter_subscription_id=' . $this->request->get['filter_subscription_id'];
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

        $data['pagination'] = $this->load->controller('common/pagination', [
            'total' => $subscription_total,
            'page'  => $page,
            'limit' => 10,
            'url'   => $this->url->link('extension/razorpay/payment/razorpay.getSubscription', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true)
        ]);

        $data['results'] = sprintf($this->language->get('text_pagination'), ($subscription_total) ? (($page - 1) *10) + 1 : 0, ((($page - 1) *10) > ($subscription_total -10)) ? $subscription_total : ((($page - 1) *10) +10), $subscription_total, ceil($subscription_total /10));

        $data['filter_subscription_id'] = $filter_subscription_id;
        $data['filter_plan_name'] = $filter_plan_name;
        $data['filter_subscription_status'] = $filter_subscription_status;
        $data['filter_date_created'] = $filter_date_created;
        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['status'] = $this->url->link('extension/razorpay/payment/razorpay.changeStatus', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/razorpay/payment/razorpay_subscription/razorpay_subscription_list', $data));
    }

    //for Subscription status change
    public function changeStatus()
    {
        $this->load->language('extension/razorpay/payment/razorpay');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/razorpay/payment/razorpay');

        $status = 0;

        // single status change
        if (isset($this->request->get['status']) === true)
        {
            $status = $this->request->get['status'];
        }

        if ((isset($this->request->post['selected'])) && ($this->request->post['status']))
        {
            $status = $this->request->post['status'];
            if ($status == 1)
            {
                $this->log->write('Resume Subscription: Status 1');
                $this->resumeSubscription($this->request->post['selected']);
            }
            else if($status == 2)
            {
                $this->log->write('Pause Subscription: Status 2');
                $this->pauseSubscription($this->request->post['selected']);
            }
            else if($status == 3)
            {
                $this->log->write('Cancel Subscription: Status 3');
                $this->cancelSubscription($this->request->post['selected']);
            }
            else
            {
                $this->log->write('Subscription Status: '. $status);
                return;
            }
        }
        else
        {
            return $this->response->redirect($this->url->link('extension/razorpay/payment/razorpay.getSubscription', 'user_token=' . $this->session->data['user_token'] . $url, true));

            return;
        }
    }

    public function changeSingleStatus()
    {
        $status = $this->request->get['status'];
        $eid= [
            $this->request->get['entity_id']
        ];

        if ($status == 1)
        {
            $this->resumeSubscription($eid);
            $this->session->data['success'] = $this->language->get('text_resume_success');

            return;
        }
        else if($status == 2)
        {
            $this->pauseSubscription($eid);
            $this->session->data['success'] = $this->language->get('text_pause_success');

            return;
        }
        else if($status == 3)
        {
            $this->cancelSubscription($eid);
            $this->session->data['success'] = $this->language->get('text_pause_success');

            return $this->response->redirect($this->url->link('extension/razorpay/payment/razorpay.getSubscription', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        else
        {
            return;
        }
    }

    public function resumeSubscription($entity_id)
    {
        $this->load->language('extension/razorpay/payment/razorpay');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/razorpay/payment/razorpay');
        $url = '';

        try {
            foreach ($entity_id as $entityId)
            {
                $subscriptionData = $this->model_extension_razorpay_payment_razorpay->getSingleSubscription($entityId);

                if ($subscriptionData['status'] == "paused")
                {
                    $api = $this->getApiIntance();

                    $api->subscription->fetch($subscriptionData['subscription_id'])->resume(array('resume_at' => 'now'));

                    $this->model_extension_razorpay_payment_razorpay->resumeSubscription($entityId, "admin");
                    $this->model_extension_razorpay_payment_razorpay->updateOCRecurringStatus($subscriptionData['order_id'], 1);
                }

            }
            $this->session->data['success'] = $this->language->get('text_resume_success');

            return $this->response->redirect($this->url->link('extension/razorpay/payment/razorpay.getSubscription', 'user_token=' . $this->session->data['user_token'] . $url, true));

        }
        catch (\Razorpay\Api\Errors\Error $e)
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

            return;
        }
    }

    public function pauseSubscription($entity_id)
    {
        $this->load->language('extension/razorpay/payment/razorpay');
        $url = '';
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/razorpay/payment/razorpay');

        try {
            foreach ($entity_id as $entityId)
            {
                $subscriptionData = $this->model_extension_razorpay_payment_razorpay->getSingleSubscription($entityId);

                if ($subscriptionData['status'] == "active")
                {
                    $api = $this->getApiIntance();
                    $api->subscription->fetch($subscriptionData['subscription_id'])->pause(["pause_at" => "now"]);
                    $this->model_extension_razorpay_payment_razorpay->pauseSubscription($entityId, "admin");
                    $this->model_extension_razorpay_payment_razorpay->updateOCRecurringStatus($subscriptionData['order_id'], 2);
                }

            }

            $this->session->data['success'] = $this->language->get('text_pause_success');

            return $this->response->redirect($this->url->link('extension/razorpay/payment/razorpay.getSubscription', 'user_token=' . $this->session->data['user_token'] . $url, true));

        }
        catch (\Razorpay\Api\Errors\Error $e)
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

            return;
        }
    }

    public function cancelSubscription($entity_id)
    {
        $this->load->language('extension/razorpay/payment/razorpay');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/razorpay/payment/razorpay');

        try {
            foreach ($entity_id as $entityId)
            {
                $subscriptionData = $this->model_extension_razorpay_payment_razorpay->getSingleSubscription($entityId);

                if (($subscriptionData['status'] == "active") || ($subscriptionData['status'] == "paused"))
                {
                    $api = $this->getApiIntance();
                    $api->subscription->fetch($subscriptionData['subscription_id'])->cancel(["cancel_at_cycle_end" => 0]);
                    $this->model_extension_razorpay_payment_razorpay->cancelSubscription($entityId, "admin");
                    $this->model_extension_razorpay_payment_razorpay->updateOCRecurringStatus($subscriptionData['order_id'], 3);
                }

            }

            $this->session->data['success'] = $this->language->get('text_cancel_success');

            return $this->response->redirect($this->url->link('extension/razorpay/payment/razorpay.getSubscription', 'user_token=' . $this->session->data['user_token'] . $url, true));

        }
        catch (\Razorpay\Api\Errors\Error $e)
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

            return;
        }
    }

    public function subscriptionInfo()
    {
        $this->load->language('extension/razorpay/payment/razorpay');
        $this->load->model('extension/razorpay/payment/razorpay');

        if (isset($this->request->get['entity_id']))
        {
            $entity_id = $this->request->get['entity_id'];
        }
        else
        {
            $entity_id = 0;
        }

        $data['results'] =  $results=$this->model_extension_razorpay_payment_razorpay->getSubscriptionInfo($entity_id);

        if ($results)
        {
            $url = '';
            $data['firstname'] = $results['firstname'];
            $data['lastname'] = $results['lastname'];
            $data['subscription_id'] = $results['subscription_id'];
            $data['plan_id'] = $results['plan_id'];
            $data['plan_name'] = $results['plan_name'];
            $data['product_name'] = $results['name'];
            $data['status'] = ucfirst($results['sub_status']);
            $data['plan_bill_amount'] = $results['plan_bill_amount'];
            $data['plan_frequency'] = $results['plan_frequency'];
            $data['plan_bill_cycle'] = $results['plan_bill_cycle'];
            $data['total_count'] = $results['total_count'];
            $data['paid_count'] = $results['paid_count'];
            $data['remaining_count'] = $results['remaining_count'];
            $data['start_at'] = isset($results['start_at']) ? date($this->language->get('date_format_short'), strtotime($results['start_at'])) : "";
            $data['end_at'] = isset($results['end_at']) ? date($this->language->get('date_format_short'), strtotime($results['end_at'])) : "";
            $data['next_charge_at'] = isset($results['next_charge_at']) ? date($this->language->get('date_format_short'), strtotime($results['next_charge_at'])) : "";
            $data['sub_created'] = isset($results['sub_created']) ? date($this->language->get('date_format_short'), strtotime($results['sub_created'])) : "";

            if (isset($this->request->get['filter_entity_id']))
            {
                $url .= '&filter_entity_id=' . trim($this->request->get['filter_entity_id']);
            }
            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('subscription_title'),
                'href' => $this->url->link('extension/razorpay/payment/razorpay.getSubscription', 'user_token=' . $this->session->data['user_token'] . $url, true)
            );

            //Invoice
            $api = $this->getApiIntance();
            $data['invoiceDetails'] = array();
            $data['invoiceResult'] = $invoiceResult = $api->invoice->all(["subscription_id"=>$results['subscription_id']]);

            foreach ($invoiceResult['items'] as $result)
            {
                $data['invoiceDetails'][] = [
                    'id'                => $result['id'],
                    'recurring_amt'     => $result['line_items'][0]['net_amount']/100,
                    'addons'            => $result['line_items'][0]['unit_amount']/100,
                    'status'            => ucfirst($result['status']),
                    'total_amt'         => $result['amount']/100,
                    'date'              => date('M d, Y', $result['billing_start']),
                    'short_url'         => $result['short_url']
                ];
            }

            $data['singleResume'] = $this->url->link('extension/razorpay/payment/razorpay.changeSingleStatus', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $results['sub_id'] . '&status=1'. $url, true);
            $data['singlePause'] = $this->url->link('extension/razorpay/payment/razorpay.changeSingleStatus', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $results['sub_id'] . '&status=2'. $url, true);

            $data['singleCancel'] = $this->url->link('extension/razorpay/payment/razorpay.changeSingleStatus', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $results['sub_id'] . '&status=3'. $url, true);
            $data['back'] = $this->url->link('extension/razorpay/payment/razorpay.getSubscription', 'user_token=' . $this->session->data['user_token'] . $url, true);
            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view('extension/razorpay/payment/razorpay_subscription/razorpay_subscription_info', $data));

        }
        else
        {
            return new Action('error/not_found');
        }
    }

	public function rzpAdminMenu(string $route = '', array &$data = []): void
    {
        if ($this->config->get('payment_razorpay_subscription_status') !== '1')
        {
            return;
        }

        $rzpNav = [];

        $this->load->language('extension/razorpay/payment/razorpay');

        $rzpNav[] = [
            'name'      => "plan",
            'href'      => $this->url->link('extension/razorpay/payment/razorpay.getPlan', 'user_token=' . $this->session->data['user_token'], true),
            'children'  => []
        ];
        $rzpNav[] = [
            'name'      => "subscription",
            'href'      => $this->url->link('extension/razorpay/payment/razorpay.getSubscription', 'user_token=' . $this->session->data['user_token'], true),
            'children'  => []
        ];

        $data['menus'][] = [
            'id'        => 'menu-catalog',
            'icon'      => 'fa-solid fa-tag',
            'name'      => 'Razorpay Subscription',
            'href'      => '',
            'children'  => $rzpNav
        ];
    }

    public function install(): void
    {
        try
        {
            $this->load->model('setting/event');
            $this->model_setting_event->deleteEventByCode('razorpay_admin_menu');

            $this->model_setting_event->addEvent([
                'code'          => 'razorpay_admin_menu',
                'description'   => 'Razorpay Plans and Subscriptions',
                'trigger'       => 'admin/view/common/column_left/before',
                'action'        => 'extension/razorpay/payment/razorpay.rzpAdminMenu',
                'status'        => true,
                'sort_order'    => 1
            ]);
            
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

        if (isset($this->request->get['filter_plan_id']))
        {
            $filter_plan_id = trim($this->request->get['filter_plan_id']);
        }
        else
        {
            $filter_plan_id = '';
        }

        if (isset($this->request->get['filter_plan_name']))
        {
            $filter_plan_name = trim($this->request->get['filter_plan_name']);
        }
        else
        {
            $filter_plan_name = '';
        }

        if (isset($this->request->get['filter_plan_status']))
        {
            $filter_plan_status = trim($this->request->get['filter_plan_status']);
        }
        else
        {
            $filter_plan_status = '';
        }

        if (isset($this->request->get['filter_date_created']))
        {
            $filter_date_created = trim($this->request->get['filter_date_created']);
        }
        else
        {
            $filter_date_created = '';
        }

        if (isset($this->request->get['sort']))
        {
            $sort = $this->request->get['sort'];
        }
        else
        {
            $sort = 'p.entity_id';
        }

        if (isset($this->request->get['entity_id']))
        {
            $order = $this->request->get['entity_id'];
        }
        else
        {
            $order = 'DESC';
        }

        if (isset($this->request->get['page']))
        {
            $page = (int)$this->request->get['page'];
        }
        else
        {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_plan_id']))
        {
            $url .= '&filter_plan_id=' . trim($this->request->get['filter_plan_id']);
        }

        if (isset($this->request->get['filter_plan_name']))
        {
            $url .= '&filter_plan_name=' . urlencode(html_entity_decode($this->request->get['filter_plan_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_plan_status']))
        {
            $url .= '&filter_plan_status=' . $this->request->get['filter_plan_status'];
        }

        if (isset($this->request->get['filter_date_created']))
        {
            $url .= '&filter_date_created=' . $this->request->get['filter_date_created'];
        }

        if (isset($this->request->get['sort']))
        {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['plan']))
        {
            $url .= '&plan=' . $this->request->get['plan'];
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
            'text' => $this->language->get('plan_title'),
            'href' => $this->url->link('extension/razorpay/payment/razorpay.getPlan', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['plans'] = array();

        $filter_data = array(
            'filter_plan_id'        => $filter_plan_id,
            'filter_plan_name'         => $filter_plan_name,
            'filter_plan_status'    => $filter_plan_status,
            'filter_date_created'      => $filter_date_created,
            'sort'                   => $sort,
            'order'                  => $order,
            'start'                  => ($page - 1) *10,
            'limit'                  =>10
        );
        $plan_total = $this->model_extension_razorpay_payment_razorpay->getTotalPlan($filter_data);

        $results = $this->model_extension_razorpay_payment_razorpay->getPlans($filter_data);

        foreach ($results as $result)
        {
            $data['plans'][] = array(
                'entity_id'      => $result['entity_id'],
                'plan_id'      => $result['plan_id'],
                'plan_name'    => $result['plan_name'],
                'plan_desc'     => $result['plan_desc'],
                'name'          => $result['name'],
                'plan_type'     => $result['plan_type'],
                'plan_frequency'     => $result['plan_frequency'],
                'plan_bill_cycle'     => $result['plan_bill_cycle'],
                'plan_trial'     => $result['plan_trial'],
                'plan_bill_amount'     => $result['plan_bill_amount'],
                'plan_addons'     => $result['plan_addons'],
                'plan_status'     => $result['plan_status'],
                'created_at'    => date($this->language->get('date_format_short'), strtotime($result['created_at'])),
                'view'          => $this->url->link('extension/razorpay/payment/razorpay', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $result['entity_id'] . $url, true),
                'singleEnable'          => $this->url->link('extension/razorpay/payment/razorpay.singleEnable', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $result['entity_id'] . $url, true),
                'singleDisable'          => $this->url->link('extension/razorpay/payment/razorpay.singleDisable', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $result['entity_id'] . $url, true)
            );
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->error['warning']))
        {
            $data['error_warning'] = $this->error['warning'];
        }
        else
        {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success']))
        {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        }
        else
        {
            $data['success'] = '';
        }

        if (isset($this->request->post['selected']))
        {
            $data['selected'] = (array)$this->request->post['selected'];
        }
        else
        {
            $data['selected'] = array();
        }

        $url = '';

        if (isset($this->request->get['filter_plan_id']))
        {
            $url .= '&filter_plan_id=' . trim($this->request->get['filter_plan_id']);
        }

        if (isset($this->request->get['filter_plan_name']))
        {
            $url .= '&filter_plan_name=' . urlencode(html_entity_decode($this->request->get['filter_plan_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_plan_status']))
        {
            $url .= '&filter_plan_status=' . trim($this->request->get['filter_plan_status']);
        }

        if (isset($this->request->get['filter_total']))
        {
            $url .= '&filter_total=' . $this->request->get['filter_total'];
        }

        if (isset($this->request->get['filter_date_created']))
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

        if (isset($this->request->get['page']))
        {
            $url .= '&page=' . $this->request->get['page'];
        }
        $path = 'extension/razorpay/payment/razorpay.plan_list';
        $data['sort_order'] = $this->url->link($path, 'user_token=' . $this->session->data['user_token'] . '&sort=p.plan_id' . $url, true);
        $data['sort_customer'] = $this->url->link($path, 'user_token=' . $this->session->data['user_token'] . '&sort=plan_name' . $url, true);
        $data['sort_status'] = $this->url->link($path, 'user_token=' . $this->session->data['user_token'] . '&sort=plan_status' . $url, true);

        $data['sort_date_added'] = $this->url->link($path, 'user_token=' . $this->session->data['user_token'] . '&sort=o.date_added' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_plan_id']))
        {
            $url .= '&filter_plan_id=' . trim($this->request->get['filter_plan_id']);
        }

        if (isset($this->request->get['filter_plan_name']))
        {
            $url .= '&filter_plan_name=' . urlencode(html_entity_decode($this->request->get['filter_plan_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_plan_status']))
        {
            $url .= '&filter_plan_status=' . $this->request->get['filter_plan_status'];
        }

        if (isset($this->request->get['filter_date_created']))
        {
            $url .= '&filter_date_created=' . $this->request->get['filter_date_created'];
        }

        if (isset($this->request->get['sort']))
        {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order']))
        {
            $url .= '&order=' . $this->request->get['order'];
        }

        // $pagination = new Pagination();
        // $pagination->total = $plan_total;
        // $pagination->page = $page;
        // $pagination->limit = $this->config->get('config_limit_admin');
        // $pagination->url = $this->url->link('extension/razorpay/payment/razorpay.getSubscription', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);


        // $data['pagination'] = $pagination->render();

        $data['pagination'] = $this->load->controller('common/pagination', [
            'total' => $plan_total,
            'page'  => $page,
            'limit' =>10,
            'url'   => $this->url->link('extension/razorpay/payment/razorpay.getPlan', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true)
        ]);

        // $data['results'] = sprintf($this->language->get('text_pagination'), ($report_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($report_total - 10)) ? $report_total : ((($page - 1) * 10) + 10), $report_total, ceil($report_total / 10));

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

    protected function getApiIntance()
    {
        return new Api($this->config->get('payment_razorpay_key_id'), $this->config->get('payment_razorpay_key_secret'));
    }
}
