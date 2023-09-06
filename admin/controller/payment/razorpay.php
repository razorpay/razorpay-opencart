<?php
namespace Opencart\Admin\Controller\Extension\Razorpay\Payment;

use Opencart\Admin\Controller\Extension\Razorpay\Payment\CreateWebhook;

class Razorpay extends \Opencart\System\Engine\Controller {
	const WEBHOOK_URL    = HTTP_CATALOG . 'index.php?route=extension/payment/razorpay/webhook';
	
	private $error = [];

	public function index(): void {
		try {
			$this->language->load('extension/razorpay/payment/razorpay');

			$this->document->setTitle($this->language->get('heading_title'));

			$this->load->model('setting/setting');

			if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate())
			{
				$createWebhook = new CreateWebhook(
					$this->request->post['payment_razorpay_key_id'],
					$this->request->post['payment_razorpay_key_secret'],
					$this->config->get('payment_razorpay_webhook_secret'),
					self::WEBHOOK_URL,
					$this->request->post['payment_razorpay_subscription_status']
				);

				$webhookConfigData = $createWebhook->autoCreateWebhook();

				if(array_key_exists('error', $webhookConfigData))
				{
					$this->error['warning'] = $this->language->get('enable_subscription_flag');
				}
				else
				{
					$configData = array_merge($this->request->post, $webhookConfigData);
					$this->model_setting_setting->editSetting('payment_razorpay', $configData);
					$this->session->data['success'] = $this->language->get('text_success');
					$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
				}
			}
			$data['heading_title'] = $this->language->get('heading_title');
			$data['text_edit'] = $this->language->get('text_edit');
			$data['text_enabled'] = $this->language->get('text_enabled');
			$data['text_disabled'] = $this->language->get('text_disabled');
			$data['text_all_zones'] = $this->language->get('text_all_zones');
			$data['text_yes'] = $this->language->get('text_yes');
			$data['text_no'] = $this->language->get('text_no');
			$data['entry_key_id'] = $this->language->get('entry_key_id');
			$data['entry_key_secret'] = $this->language->get('entry_key_secret');
			$data['entry_order_status'] = $this->language->get('entry_order_status');
			$data['entry_status'] = $this->language->get('entry_status');
			$data['entry_sort_order'] = $this->language->get('entry_sort_order');
			$data['entry_payment_action'] = $this->language->get('entry_payment_action');
			$data['entry_subscription_status'] = $this->language->get('entry_subscription_status');
			$data['button_save'] = $this->language->get('button_save');
			$data['button_cancel'] = $this->language->get('button_cancel');
			$data['help_key_id'] = $this->language->get('help_key_id');
			$data['help_order_status'] = $this->language->get('help_order_status');
			$data['help_webhook_url'] = $this->language->get('help_webhook_url');

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
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token='.$this->session->data['user_token'], 'SSL'),
				'separator' => false,
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_extension'),
				'href' => $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=payment', 'SSL'),
				'separator' => ' :: ',
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/razorpay/payment/razorpay', 'user_token='.$this->session->data['user_token'], 'SSL'),
				'separator' => ' :: ',
			);

            $data['save'] = $this->url->link('extension/razorpay/payment/razorpay.save', 'user_token=' . $this->session->data['user_token']);

			$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', 'SSL');

			if (isset($this->request->post['payment_razorpay_key_id']))
			{
				$data['razorpay_key_id'] = $this->request->post['payment_razorpay_key_id'];
			}
			else
			{
				$data['razorpay_key_id'] = $this->config->get('payment_razorpay_key_id');
			}

			if (isset($this->request->post['payment_razorpay_key_secret']))
			{
			$data['razorpay_key_secret'] = $this->request->post['payment_razorpay_key_secret'];
			}
			else
			{
			$data['razorpay_key_secret'] = $this->config->get('payment_razorpay_key_secret');
			}

			if (isset($this->request->post['payment_razorpay_order_status_id']))
			{
				$data['razorpay_order_status_id'] = $this->request->post['payment_razorpay_order_status_id'];
			}
			else
			{
				$data['razorpay_order_status_id'] = ($this->config->get('payment_razorpay_order_status_id')) ? $this->config->get('payment_razorpay_order_status_id') : 2;
			}
			$this->load->model('localisation/order_status');

			$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

			if (isset($this->request->post['payment_razorpay_status']))
			{
				$data['razorpay_status'] = $this->request->post['payment_razorpay_status'];
			}
			else
			{
				$data['razorpay_status'] = $this->config->get('payment_razorpay_status');
			}

			if (isset($this->request->post['payment_razorpay_sort_order']))
			{
				$data['razorpay_sort_order'] = $this->request->post['payment_razorpay_sort_order'];
			}
			else
			{
				$data['razorpay_sort_order'] = $this->config->get('payment_razorpay_sort_order');
			}

			if (isset($this->request->post['payment_razorpay_payment_action']))
			{
				$data['razorpay_payment_action'] = $this->request->post['payment_razorpay_payment_action'];
			}
			else
			{
				$data['razorpay_payment_action'] = $this->config->get('payment_razorpay_payment_action');
			}

			if (isset($this->request->post['payment_razorpay_max_capture_delay']))
			{
				$data['razorpay_max_capture_delay'] = $this->request->post['payment_razorpay_max_capture_delay'];
			}
			else
			{
				$data['razorpay_max_capture_delay'] = $this->config->get('payment_razorpay_max_capture_delay');
			}
		
			//Subscription Status
			if (isset($this->request->post['payment_razorpay_subscription_status']))
			{
				$data['razorpay_subscription_status'] = $this->request->post['payment_razorpay_subscription_status'];
			}
			else
			{
				$data['razorpay_subscription_status'] = $this->config->get('payment_razorpay_subscription_status');
			}

			// $this->template = 'extension/razorpay/payment/razorpay';
			// $this->children = array(
			// 	'common/header',
			// 	'common/footer',
			// );
			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');

			$this->response->setOutput($this->load->view('extension/razorpay/payment/razorpay', $data));
		}
		catch(\Exception $e) {
			echo(json_encode($e->getMessage()));
			echo(json_encode($e->getTrace()));
		}
	}

	protected function validate()
    {
        // if (!$this->user->hasPermission('modify', 'extension/razorpay/payment/razorpay'))
        // {
        //     $this->error['warning'] = $this->language->get('error_permission');
        // }

        if (!$this->request->post['payment_razorpay_key_id'])
        {
            $this->error['payment_razorpay_key_id'] = $this->language->get('error_key_id');
        }

        if (!$this->request->post['payment_razorpay_key_secret'])
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

		$json = [];
		// if (!$this->user->hasPermission('modify', 'extension/razorpay/payment/razorpay')) {
		// 	$json['error'] = $this->language->get('error_permission');
		// }

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('payment_razorpay', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		try {
			// if ($this->user->hasPermission('modify', 'extension/payment')) {
			// 	$this->load->model('extension/razorpay/payment/credit_card');
	
			// 	$this->model_extension_razorpay_payment_credit_card->install();
			// }
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

	public function report(): void {
		try {
			$this->load->language('extension/razorpay/payment/razorpay');

			$this->response->setOutput($this->getReport());
		}
		catch(\Exception $e) {
			echo(json_encode($e->getMessage()));
			echo(json_encode($e->getTrace()));
		}
	}

	public function getReport(): string {
		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['reports'] = [];

		$this->load->model('extension/razorpay/payment/razorpay');

		$results = $this->model_extension_razorpay_payment_razorpay->getReports(($page - 1) * 10, 10);

		foreach ($results as $result) {
			$data['reports'][] = [
				'order_id'   => $result['order_id'],
				'card'       => $result['card'],
				'amount'     => $this->curency->format($result['amount'], $this->config->get('config_currency')),
				'response'   => $result['response'],
				'status'     => $result['order_status'],
				'date_added' => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
				'order'      => $this->url->link('sale/order.info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'])
			];
		}

		$report_total = $this->model_extension_razorpay_payment_razorpay->getTotalReports();

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $report_total,
			'page'  => $page,
			'limit' => $this->config->get('config_pagination_admin'),
			'url'   => $this->url->link('extension/razorpay/payment/razorpay.report', 'user_token=' . $this->session->data['user_token'] . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($report_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($report_total - 10)) ? $report_total : ((($page - 1) * 10) + 10), $report_total, ceil($report_total / 10));

		return $this->load->view('extension/razorpay/payment/razorpay_report', $data);
	}
}
