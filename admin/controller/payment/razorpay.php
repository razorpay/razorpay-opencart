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

            $this->db->query('DELETE FROM '.DB_PREFIX."url_alias WHERE query = 'callback=1' AND keyword = 'payment-callback'");
            $this->db->query('INSERT INTO '.DB_PREFIX."url_alias SET query = 'callback=1', keyword = 'payment-callback'");

            $this->session->data['success'] = $this->language->get('text_success');

            $this->redirect($this->url->link('extension/payment', 'token='.$this->session->data['token'], 'SSL'));
        }

        $this->data['heading_title'] = $this->language->get('heading_title');

        $this->data['text_enabled'] = $this->language->get('text_enabled');
        $this->data['text_disabled'] = $this->language->get('text_disabled');

        $this->data['entry_key_id'] = $this->language->get('entry_key_id');
        $this->data['entry_key_secret'] = $this->language->get('entry_key_secret');
        $this->data['entry_order_status'] = $this->language->get('entry_order_status');
        $this->data['entry_status'] = $this->language->get('entry_status');
        $this->data['entry_sort_order'] = $this->language->get('entry_sort_order');

        $this->data['entry_geo_zone'] = $this->language->get('entry_geo_zone');

        $this->data['button_save'] = $this->language->get('button_save');
        $this->data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }

        if (isset($this->error['razorpay_key_id'])) {
            $this->data['error_key_id'] = $this->error['razorpay_key_id'];
        } else {
            $this->data['error_key_id'] = '';
        }

        if (isset($this->error['razorpay_key_secret'])) {
            $this->data['error_key_secret'] = $this->error['razorpay_key_secret'];
        } else {
            $this->data['error_key_secret'] = '';
        }

        $this->data['breadcrumbs'] = array();

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'token='.$this->session->data['token'], 'SSL'),
            'separator' => false,
        );

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment', 'token='.$this->session->data['token'], 'SSL'),
            'separator' => ' :: ',
        );

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('payment/razorpay', 'token='.$this->session->data['token'], 'SSL'),
            'separator' => ' :: ',
        );

        $this->data['action'] = $this->url->link('payment/razorpay', 'token='.$this->session->data['token'], 'SSL');

        $this->data['cancel'] = $this->url->link('extension/payment', 'token='.$this->session->data['token'], 'SSL');

        if (isset($this->request->post['razorpay_key_id'])) {
            $this->data['razorpay_key_id'] = $this->request->post['razorpay_key_id'];
        } else {
            $this->data['razorpay_key_id'] = $this->config->get('razorpay_key_id');
        }

        if (isset($this->request->post['razorpay_key_secret'])) {
            $this->data['razorpay_key_secret'] = $this->request->post['razorpay_key_secret'];
        } else {
            $this->data['razorpay_key_secret'] = $this->config->get('razorpay_key_secret');
        }

        if (isset($this->request->post['razorpay_order_status_id'])) {
            $this->data['razorpay_order_status_id'] = $this->request->post['razorpay_order_status_id'];
        } else {
            $this->data['razorpay_order_status_id'] = $this->config->get('razorpay_order_status_id');
        }

        $this->load->model('localisation/order_status');

        $this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['razorpay_geo_zone_id'])) {
            $data['razorpay_geo_zone_id'] = $this->request->post['razorpay_geo_zone_id'];
        } else {
            $data['razorpay_geo_zone_id'] = $this->config->get('razorpay_geo_zone_id'); 
        } 

        $this->load->model('localisation/geo_zone');
                                        
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['razorpay_status'])) {
            $this->data['razorpay_status'] = $this->request->post['razorpay_status'];
        } else {
            $this->data['razorpay_status'] = $this->config->get('razorpay_status');
        }

        if (isset($this->request->post['razorpay_sort_order'])) {
            $this->data['razorpay_sort_order'] = $this->request->post['razorpay_sort_order'];
        } else {
            $this->data['razorpay_sort_order'] = $this->config->get('razorpay_sort_order');
        }

        $this->template = 'payment/razorpay.tpl';
        $this->children = array(
            'common/header',
            'common/footer',
        );

        $this->response->setOutput($this->render());
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

    private function is_serialized($value, &$result = null)
    {
        // Bit of a give away this one
        if (!is_string($value)) {
            return false;
        }
        if (empty($value)) {
            return false;
        }
        // Serialized false, return true. unserialize() returns false on an
        // invalid string or it could return false if the string is serialized
        // false, eliminate that possibility.
        if ($value === 'b:0;') {
            $result = false;

            return true;
        }

        $length = strlen($value);
        $end = '';

        switch ($value[0]) {
            case 's':
                if ($value[$length - 2] !== '"') {
                    return false;
                }
            case 'b':
            case 'i':
            case 'd':
                // This looks odd but it is quicker than isset()ing
                $end .= ';';
            case 'a':
            case 'O':
                $end .= '}';

                if ($value[1] !== ':') {
                    return false;
                }

                switch ($value[2]) {
                    case 0:
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                    case 9:
                    break;

                    default:
                        return false;
                }
            case 'N':
                $end .= ';';

                if ($value[$length - 1] !== $end[0]) {
                    return false;
                }
            break;

            default:
                return false;
        }

        if (($result = @unserialize($value)) === false) {
            $result = null;

            return false;
        }

        return true;
    }
}
