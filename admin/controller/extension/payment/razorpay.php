<?php
class ControllerExtensionPaymentRazorpay extends Controller {
    private $error = array();

    public function index() {
        $this->language->load('extension/payment/razorpay');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_razorpay', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['payment_razorpay_key_id'])) {
            $data['error_key_id'] = $this->error['payment_razorpay_key_id'];
        } else {
            $data['error_key_id'] = '';
        }

        if (isset($this->error['payment_razorpay_key_secret'])) {
            $data['error_key_secret'] = $this->error['payment_razorpay_key_secret'];
        } else {
            $data['error_key_secret'] = '';
        }

        if (isset($this->error['payment_razorpay_webhook_secret'])) {
            $data['error_webhook_secret'] = $this->error['payment_razorpay_webhook_secret'];
        } else {
            $data['error_webhook_secret'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token='.$this->session->data['user_token'],true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=payment',true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/razorpay', 'user_token='.$this->session->data['user_token'],true),
        );

        $data['action'] = $this->url->link('extension/payment/razorpay', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        if (isset($this->request->post['payment_razorpay_key_id'])) {
            $data['payment_razorpay_key_id'] = $this->request->post['payment_razorpay_key_id'];
        } else {
            $data['payment_razorpay_key_id'] = $this->config->get('payment_razorpay_key_id');
        }

        if (isset($this->request->post['payment_razorpay_key_secret'])) {
            $data['payment_razorpay_key_secret'] = $this->request->post['payment_razorpay_key_secret'];
        } else {
            $data['payment_razorpay_key_secret'] = $this->config->get('payment_razorpay_key_secret');
        }

        if (isset($this->request->post['payment_razorpay_order_status_id'])) {
            $data['payment_razorpay_order_status_id'] = $this->request->post['payment_razorpay_order_status_id'];
        } else {
            $data['payment_razorpay_order_status_id'] = ($this->config->get('payment_razorpay_order_status_id')) ? $this->config->get('payment_razorpay_order_status_id') : 2;
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_razorpay_status'])) {
            $data['payment_razorpay_status'] = $this->request->post['payment_razorpay_status'];
        } else {
            $data['payment_razorpay_status'] = $this->config->get('payment_razorpay_status');
        }

        if (isset($this->request->post['payment_razorpay_sort_order'])) {
            $data['payment_razorpay_sort_order'] = $this->request->post['payment_razorpay_sort_order'];
        } else {
            $data['payment_razorpay_sort_order'] = $this->config->get('payment_razorpay_sort_order');
        }

        if (isset($this->request->post['payment_razorpay_payment_action'])) {
            $data['payment_razorpay_payment_action'] = $this->request->post['payment_razorpay_payment_action'];
        } else {
            $data['payment_razorpay_payment_action'] = $this->config->get('payment_razorpay_payment_action');
        }

        if (isset($this->request->post['payment_razorpay_max_capture_delay'])) {
            $data['payment_razorpay_max_capture_delay'] = $this->request->post['payment_razorpay_max_capture_delay'];
        } else {
            $data['payment_razorpay_max_capture_delay'] = $this->config->get('payment_razorpay_max_capture_delay');
        }

        if (isset($this->request->post['payment_razorpay_webhook_status'])) {
            $data['payment_razorpay_webhook_status'] = $this->request->post['payment_razorpay_webhook_status'];
        } else {
            $data['payment_razorpay_webhook_status'] = $this->config->get('payment_razorpay_webhook_status');
        }

        if (isset($this->request->post['payment_razorpay_webhook_secret'])) {
            $data['payment_razorpay_webhook_secret'] = $this->request->post['payment_razorpay_webhook_secret'];
        } else {
            $data['payment_razorpay_webhook_secret'] = $this->config->get('payment_razorpay_webhook_secret');
        }
        
        $data['payment_razorpay_webhook_url'] = HTTPS_CATALOG . 'index.php?route=extension/payment/razorpay/webhook';

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/razorpay', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/razorpay')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_razorpay_key_id']) {
            $this->error['payment_razorpay_key_id'] = $this->language->get('error_key_id');
        }

        if (!$this->request->post['payment_razorpay_key_secret']) {
            $this->error['payment_razorpay_key_secret'] = $this->language->get('error_key_secret');
        }

        if ($this->request->post['payment_razorpay_webhook_status'] and !$this->request->post['payment_razorpay_webhook_secret']) {
            $this->error['payment_razorpay_webhook_secret'] = $this->language->get('error_webhook_secret');
        }

		return !$this->error;
    }
}
