<?php
namespace Opencart\Catalog\Controller\Extension\Razorpay\Payment;

require_once __DIR__.'/../../../../system/library/razorpay/razorpay-sdk/Razorpay.php';
require_once __DIR__.'/../../../../system/library/razorpay/razorpay-lib/createwebhook.php';
use Razorpay\Api\Api;
use Razorpay\Api\Errors;

class Razorpay extends \Opencart\System\Engine\Controller {
	public function index(): string {
		// echo(json_encode($this->load->language('extension/razorpay/payment/razorpay')));
		$this->load->language('extension/razorpay/payment/razorpay');

		if (isset($this->session->data['payment_method'])) {
			$data['logged'] = $this->customer->isLogged();
			$data['subscription'] = $this->cart->hasSubscription();

			$data['months'] = [];

			foreach (range(1, 12) as $month) {
				$data['months'][] = date('m', mktime(0, 0, 0, $month, 1));
			}

			$data['years'] = [];

			foreach (range(date('Y'), date('Y', strtotime('+10 year'))) as $year) {
				$data['years'][] = $year;
			}

			$data['language'] = $this->config->get('config_language');

			// Card storage
			if ($this->session->data['payment_method']['code'] == 'razorpay.razorpay') {
				return $this->load->view('extension/razorpay/payment/razorpay', $data);
			} else {
				return $this->load->view('extension/razorpay/payment/stored', $data);
			}
		}

		return '';
	}

	public function confirm(): void {
		// echo(json_encode($this->load->language('extension/razorpay/payment/razorpay')));
		$this->load->language('extension/razorpay/payment/razorpay');

		$json = [];

		if (isset($this->session->data['order_id'])) {
			$order_id = $this->session->data['order_id'];
		} else {
			$order_id = 0;
		}

		$keys = [
			'card_name',
			'card_number',
			'card_expire_month',
			'card_expire_year',
			'card_cvv',
			'store'
		];

		foreach ($keys as $key) {
			if (!isset($this->request->post[$key])) {
				$this->request->post[$key] = '';
			}
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if (!$order_info) {
			$json['error']['warning'] = $this->language->get('error_order');
		}

		if (!$this->config->get('payment_razorpay_status') || !isset($this->session->data['payment_method']) || $this->session->data['payment_method']['code'] != 'razorpay.razorpay') {
			$json['error']['warning'] = $this->language->get('error_payment_method');
		}

		if (!$this->request->post['card_name']) {
			$json['error']['card_name'] = $this->language->get('error_card_name');
		}

		if (!preg_match('/[0-9\s]{8,19}/', $this->request->post['card_number'])) {
			$json['error']['card_number'] = $this->language->get('error_card_number');
		}

		if (strtotime((int)$this->request->post['card_expire_year'] . '-' . $this->request->post['card_expire_month'] . '-01') < time()) {
			$json['error']['card_expire'] = $this->language->get('error_card_expired');
		}

		if (strlen($this->request->post['card_cvv']) != 3) {
			$json['error']['card_cvv'] = $this->language->get('error_card_cvv');
		}

		if (!$json) {
			/*
			*
			* Credit Card charge code goes here
			*
			*/

			$response = $this->config->get('payment_razorpay_response');

			// Card storage
			if ($this->customer->isLogged() && ($this->request->post['store'] || $this->cart->hasSubscription())) {
				$razorpay_data = [
					'card_name'         => $this->request->post['card_name'],
					'card_number'       => '**** **** **** ' . substr($this->request->post['card_number'], -4),
					'card_expire_month' => $this->request->post['card_expire_month'],
					'card_expire_year'  => $this->request->post['card_expire_year'],
					'card_cvv'          => $this->request->post['card_cvv'],
					'date_expire'       => $this->request->post['card_expire_year'] . '-' . $this->request->post['card_expire_month'] . '-01'
				];

				$this->load->model('extension/razorpay/payment/razorpay');

				$this->model_extension_razorpay_payment_razorpay->addCreditCard($this->customer->getId(), $razorpay_data);
			}

			// Set Credit Card response
			if ($response) {
				$this->load->model('checkout/order');

				$this->model_checkout_order->addHistory($this->session->data['order_id'], $this->config->get('payment_razorpay_approved_status_id'), '', true);

				$json['redirect'] = $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true);
			} else {
				$this->load->model('checkout/order');

				$this->model_checkout_order->addHistory($this->session->data['order_id'], $this->config->get('payment_razorpay_failed_status_id'), '', true);

				$json['redirect'] = $this->url->link('checkout/failure', 'language=' . $this->config->get('config_language'), true);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function stored(): void {
		// echo(json_encode($this->load->language('extension/razorpay/payment/razorpay')));
		$this->load->language('extension/razorpay/payment/razorpay');

		$json = [];

		if (isset($this->session->data['order_id'])) {
			$order_id = $this->session->data['order_id'];
		} else {
			$order_id = 0;
		}

		if (isset($this->session->data['payment_method'])) {
			$payment = explode('.', $this->session->data['payment_method']['code']);
		} else {
			$payment = [];
		}

		if (isset($payment[0])) {
			$payment_method = $payment[0];
		} else {
			$payment_method = '';
		}

		if (isset($payment[1])) {
			$razorpay_id = $payment[1];
		} else {
			$razorpay_id = 0;
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if (!$order_info) {
			$json['error']['warning'] = $this->language->get('error_order');
		}

		if (!$this->customer->isLogged()) {
			$json['error']['warning'] = $this->language->get('error_login');
		}

		if (!$this->config->get('payment_razorpay_status') || $payment_method != 'razorpay') {
			$json['error']['warning'] = $this->language->get('error_payment_method');
		}

		$this->load->model('extension/razorpay/payment/razorpay');

		$razorpay_info = $this->model_extension_razorpay_payment_razorpay->getCreditCard($this->customer->getId(), $razorpay_id);

		if (!$razorpay_info) {
			$json['error']['warning'] = $this->language->get('error_razorpay');
		}

		if (!$json) {
			/*
			 *
			 * Credit Card validation code goes here
			 *
			 */

			// Charge
			$response = $this->model_extension_razorpay_payment_razorpay->charge($this->customer->getId(), $this->session->data['order_id'], $order_info['total'], $razorpay_id);

			// Set Credit Card response
			if ($response) {
				$this->load->model('checkout/order');

				$this->model_checkout_order->addHistory($this->session->data['order_id'], $this->config->get('payment_razorpay_approved_status_id'), '', true);

				$json['redirect'] = $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true);
			} else {
				$this->load->model('checkout/order');

				$this->model_checkout_order->addHistory($this->session->data['order_id'], $this->config->get('payment_razorpay_failed_status_id'), '', true);

				$json['redirect'] = $this->url->link('checkout/failure', 'language=' . $this->config->get('config_language'), true);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function delete(): void {
		// echo(json_encode($this->load->language('extension/razorpay/payment/razorpay')));
		$this->load->language('extension/razorpay/payment/razorpay');

		$json = [];

		if (isset($this->request->get['razorpay_id'])) {
			$razorpay_id = (int)$this->request->get['razorpay_id'];
		} else {
			$razorpay_id = 0;
		}

		if (!$this->customer->isLogged()) {
			$json['error'] = $this->language->get('error_logged');
		}

		$this->load->model('extension/razorpay/payment/razorpay');

		$razorpay_info = $this->model_extension_razorpay_payment_razorpay->getCreditCard($this->customer->getId(), $razorpay_id);

		if (!$razorpay_info) {
			$json['error'] = $this->language->get('error_razorpay');
		}

		if (!$json) {
			$this->model_extension_razorpay_payment_razorpay->deleteCreditCard($this->customer->getId(), $razorpay_id);

			$json['success'] = $this->language->get('text_delete');

			// Clear payment and shipping methods
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
