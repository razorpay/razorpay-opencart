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

        if (isset($this->request->get['filter_subscription_id']))
        {
            $filter_subscription_id = trim($this->request->get['filter_subscription_id']);
        }
        else
        {
            $filter_subscription_id = '';
        }

        if (isset($this->request->get['filter_plan_name']))
        {
            $filter_plan_name = trim($this->request->get['filter_plan_name']);
        }
        else
        {
            $filter_plan_name = '';
        }

        if (isset($this->request->get['filter_subscription_status']))
        {
            $filter_subscription_status = $this->request->get['filter_subscription_status'];
        }
        else
        {
            $filter_subscription_status = '';
        }

        if (isset($this->request->get['filter_date_created']))
        {
            $filter_date_created = $this->request->get['filter_date_created'];
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
            $sort = 's.entity_id';
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

        if (isset($this->request->get['filter_subscription_id']))
        {
            $url .= '&filter_subscription_id=' . trim($this->request->get['filter_subscription_id']);
        }

        if (isset($this->request->get['filter_plan_name']))
        {
            $url .= '&filter_plan_name=' . urlencode(html_entity_decode($this->request->get['filter_plan_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_subscription_status']))
        {
            $url .= '&filter_subscription_status=' . $this->request->get['filter_subscription_status'];
        }

        if (isset($this->request->get['filter_date_created']))
        {
            $url .= '&filter_date_created=' . $this->request->get['filter_date_created'];
        }

        if (isset($this->request->get['sort']))
        {
            $url .= '&sort=' . $this->request->get['sort'];
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
            'text' => $this->language->get('subscription_title'),
            'href' => $this->url->link('extension/razorpay/payment/razorpay.getSubscription', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['subscriptions'] = array();

        $filter_data = array(
            'filter_subscription_id'        => $filter_subscription_id,
            'filter_plan_name'              => $filter_plan_name,
            'filter_subscription_status'    => $filter_subscription_status,
            'filter_date_created'           => $filter_date_created,
            'sort'                          => $sort,
            'order'                         => $order,
            'start'                         => ($page - 1) *10,
            'limit'                         =>10
        );

        $subscription_total = $this->model_extension_razorpay_payment_razorpay->getTotalSubscriptions($filter_data);

        $results = $this->model_extension_razorpay_payment_razorpay->getSubscription($filter_data);

        foreach ($results as $result)
        {
            $data['subscriptions'][] = array(
                'entity_id'             => $result['entity_id'],
                'subscription_id'      => $result['subscription_id'],
                'plan_id'               =>$result['plan_id'],
                'customer_fname'    => $result['firstname'],
                'customer_lname'     => $result['lastname'],
                'product_name'          => $result['name'],
                'status'     => ucfirst($result['status']),
                'cancel_by'     => $result['updated_by'],
                'total_count'     => $result['total_count'],
                'paid_count'     => $result['paid_count'],
                'remaining_count'     => $result['remaining_count'],
                'start_at'     => isset($result['start_at']) ? date($this->language->get('date_format_short'), strtotime($result['start_at'])) : "",
                'end_at'     => isset($result['end_at']) ? date($this->language->get('date_format_short'), strtotime($result['end_at'])) : "",
                'subscription_created_at'     => $result['subscription_created_at'],
                'next_charge_at'     =>  isset($result['next_charge_at']) ? date($this->language->get('date_format_short'), strtotime($result['next_charge_at'])) : "",
                'created_at'   => isset($result['created_at']) ? date($this->language->get('date_format_short'), strtotime($result['created_at'])) : "",
                'view'          => $this->url->link('extension/razorpay/payment/razorpay.subscriptionInfo', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $result['entity_id'] . $url, true),
                'singleResume' => $this->url->link('extension/razorpay/payment/razorpay.changeSingleStatus', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $result['entity_id'] . '&status=1'. $url, true),
                'singlePause' => $this->url->link('extension/razorpay/payment/razorpay.changeSingleStatus', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $result['entity_id'] . '&status=2'. $url, true),
                'singleCancel' => $this->url->link('extension/razorpay/payment/razorpay.changeSingleStatus', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $result['entity_id'] . '&status=3'. $url, true)
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

        if (isset($this->request->get['filter_subscription_status']))
        {
            $url .= '&filter_subscription_status=' . $this->request->get['filter_subscription_status'];
        }

        if (isset($this->request->get['filter_subscription_id']))
        {
            $url .= '&filter_subscription_id=' . urlencode(html_entity_decode($this->request->get['filter_subscription_id'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_plan_name']))
        {
            $url .= '&filter_plan_name=' . $this->request->get['filter_plan_name'];
        }

        if (isset($this->request->get['filter_date_created']))
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

        if (isset($this->request->get['filter_subscription_id']))
        {
            $url .= '&filter_subscription_id=' . $this->request->get['filter_subscription_id'];
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

        // $pagination = new Pagination();
        // $pagination->total = $subscription_total;
        // $pagination->page = $page;
        // $pagination->limit = $this->config->get('config_limit_admin');
        // $pagination->url = $this->url->link('extension/razorpay/payment/razorpay.getSubscription', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        // $data['pagination'] = $pagination->render();

        $data['pagination'] = $this->load->controller('common/pagination', [
            'total' => $subscription_total,
            'page'  => $page,
            'limit' =>10,
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

        // single status change
        if (isset($this->request->get['status']))
        {
            $status = $this->request->get['status'];
        }
        else
        {
            $status = 0;
        }

        if ((isset($this->request->post['selected'])) && ($this->request->post['status']))
        {
            $status = $this->request->post['status'];
            if($status==1)
            {
                $this->log->write('Status 1');
                $this->resumeSubscription($this->request->post['selected']);
            }
            else if($status==2)
            {
                $this->log->write('Status 2');
                $this->pauseSubscription($this->request->post['selected']);
            }
            else if($status==3)
            {
                $this->log->write('Status 3');
                $this->cancelSubscription($this->request->post['selected']);
            }
            else
            {
                $this->log->write('Status 4');
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
        $eid= str_split($this->request->get['entity_id']);

        if($status==1)
        {
            $this->resumeSubscription($eid);
            $this->session->data['success'] = $this->language->get('text_resume_success');

            return;
        }
        else if($status==2)
        {
            $this->pauseSubscription($eid);
            $this->session->data['success'] = $this->language->get('text_pause_success');

            return;
        }
        else if($status==3)
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
                $data['invoiceDetails'][] = array(
                    'id'                => $result['id'],
                    'recurring_amt'     => $result['line_items'][0]['net_amount']/100,
                    'addons'            =>$result['line_items'][0]['unit_amount']/100,
                    'status'            =>ucfirst($result['status']),
                    'total_amt'         =>$result['amount']/100,
                    'date'              =>date('M d, Y', $result['billing_start']),
                    'short_url'         =>$result['short_url']
                );
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
