<?php

class ControllerPaymentRazorpay extends Controller
{
    private $error = array();

    public function index()
    {
        $this->language->load('payment/razorpay');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('razorpay', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/payment', 'token='.$this->session->data['token'], 'SSL'));
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

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['help_key_id'] = $this->language->get('help_key_id');
        $data['help_order_status'] = $this->language->get('help_order_status');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['razorpay_key_id'])) {
            $data['error_key_id'] = $this->error['razorpay_key_id'];
        } else {
            $data['error_key_id'] = '';
        }

        if (isset($this->error['razorpay_key_secret'])) {
            $data['error_key_secret'] = $this->error['razorpay_key_secret'];
        } else {
            $data['error_key_secret'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'token='.$this->session->data['token'], 'SSL'),
            'separator' => false,
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment', 'token='.$this->session->data['token'], 'SSL'),
            'separator' => ' :: ',
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('payment/razorpay', 'token='.$this->session->data['token'], 'SSL'),
            'separator' => ' :: ',
        );

        $data['action'] = $this->url->link('payment/razorpay', 'token='.$this->session->data['token'], 'SSL');

        $data['cancel'] = $this->url->link('extension/payment', 'token='.$this->session->data['token'], 'SSL');

        if (isset($this->request->post['razorpay_key_id'])) {
            $data['razorpay_key_id'] = $this->request->post['razorpay_key_id'];
        } else {
            $data['razorpay_key_id'] = $this->config->get('razorpay_key_id');
        }

        if (isset($this->request->post['razorpay_key_secret'])) {
            $data['razorpay_key_secret'] = $this->request->post['razorpay_key_secret'];
        } else {
            $data['razorpay_key_secret'] = $this->config->get('razorpay_key_secret');
        }

        if (isset($this->request->post['razorpay_order_status_id'])) {
            $data['razorpay_order_status_id'] = $this->request->post['razorpay_order_status_id'];
        } else {
            $data['razorpay_order_status_id'] = $this->config->get('razorpay_order_status_id');
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['razorpay_status'])) {
            $data['razorpay_status'] = $this->request->post['razorpay_status'];
        } else {
            $data['razorpay_status'] = $this->config->get('razorpay_status');
        }

        if (isset($this->request->post['razorpay_sort_order'])) {
            $data['razorpay_sort_order'] = $this->request->post['razorpay_sort_order'];
        } else {
            $data['razorpay_sort_order'] = $this->config->get('razorpay_sort_order');
        }

        $this->template = 'payment/razorpay.tpl';
        $this->children = array(
            'common/header',
            'common/footer',
        );
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('payment/razorpay.tpl', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'payment/razorpay')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['razorpay_key_id']) {
            $this->error['razorpay_key_id'] = $this->language->get('error_key_id');
        }

        if (!$this->request->post['razorpay_key_secret']) {
            $this->error['razorpay_key_secret'] = $this->language->get('error_key_secret');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
}
