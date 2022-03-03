<?php

require_once __DIR__.'/../../../../system/library/razorpay-sdk/Razorpay.php';
use Razorpay\Api\Api;
use Razorpay\Api\Errors;

class ControllerExtensionPaymentRazorpaySubscription extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('extension/payment/razorpay_subscription');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/payment/razorpay_subscription');

        $this->getList();
       
    }

    protected function getList()
    {
        $this->load->language('extension/payment/razorpay_subscription');
        if (isset($this->request->get['filter_plan_id'])) {
            $filter_plan_id = $this->request->get['filter_plan_id'];
        } else {
            $filter_plan_id = '';
        }

        if (isset($this->request->get['filter_plan_name'])) {
            $filter_plan_name = $this->request->get['filter_plan_name'];
        } else {
            $filter_plan_name = '';
        }

        if (isset($this->request->get['filter_plan_status'])) {
            $filter_plan_status = $this->request->get['filter_plan_status'];
        } else {
            $filter_plan_status = '';
        }
        
        if (isset($this->request->get['filter_date_created'])) {
            $filter_date_created = $this->request->get['filter_date_created'];
        } else {
            $filter_date_created = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'p.entity_id';
        }

        if (isset($this->request->get['entity_id'])) {
            $order = $this->request->get['entity_id'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_plan_id'])) {
            $url .= '&filter_plan_id=' . $this->request->get['filter_plan_id'];
        }

        if (isset($this->request->get['filter_plan_name'])) {
            $url .= '&filter_plan_name=' . urlencode(html_entity_decode($this->request->get['filter_plan_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_plan_status'])) {
            $url .= '&filter_plan_status=' . $this->request->get['filter_plan_status'];
        }
   
        if (isset($this->request->get['filter_date_created'])) {
            $url .= '&filter_date_created=' . $this->request->get['filter_date_created'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['plan'])) {
            $url .= '&plan=' . $this->request->get['plan'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
        'text' => $this->language->get('text_home'),
        'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
        'text' => $this->language->get('plan_title'),
        'href' => $this->url->link('extension/payment/razorpay_subscription', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

     

        $data['plans'] = array();

        $filter_data = array(
        'filter_plan_id'        => $filter_plan_id,
        'filter_plan_name'         => $filter_plan_name,
        'filter_plan_status'    => $filter_plan_status,
        'filter_date_created'      => $filter_date_created,
        'sort'                   => $sort,
        'order'                  => $order,
        'start'                  => ($page - 1) * $this->config->get('config_limit_admin'),
        'limit'                  => $this->config->get('config_limit_admin')
        );

        $results = $this->model_extension_payment_razorpay_subscription->getPlans($filter_data);

        foreach ($results as $result) {
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
            'view'          => $this->url->link('extension/payment/razorpay_subscription', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $result['entity_id'] . $url, true),
            'singleEnable'          => $this->url->link('extension/payment/razorpay_subscription/singleEnable', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $result['entity_id'] . $url, true),
            'singleDisable'          => $this->url->link('extension/payment/razorpay_subscription/singleDisable', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $result['entity_id'] . $url, true)
            );
        }


        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } else {
            $data['selected'] = array();
        }

        $url = '';

        if (isset($this->request->get['filter_plan_id'])) {
            $url .= '&filter_plan_id=' . $this->request->get['filter_plan_id'];
        }

        if (isset($this->request->get['filter_plan_name'])) {
            $url .= '&filter_plan_name=' . urlencode(html_entity_decode($this->request->get['filter_plan_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_plan_status'])) {
            $url .= '&filter_plan_status=' . $this->request->get['filter_plan_status'];
        }
                    
        if (isset($this->request->get['filter_total'])) {
            $url .= '&filter_total=' . $this->request->get['filter_total'];
        }

        if (isset($this->request->get['filter_date_created'])) {
            $url .= '&filter_date_created=' . $this->request->get['filter_date_created'];
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }
        $path='extension/payment/razorpay_subscription/plan_list';
        $data['sort_order'] = $this->url->link($path, 'user_token=' . $this->session->data['user_token'] . '&sort=p.plan_id' . $url, true);
        $data['sort_customer'] = $this->url->link($path, 'user_token=' . $this->session->data['user_token'] . '&sort=plan_name' . $url, true);
        $data['sort_status'] = $this->url->link($path, 'user_token=' . $this->session->data['user_token'] . '&sort=plan_status' . $url, true);
       
        $data['sort_date_added'] = $this->url->link($path, 'user_token=' . $this->session->data['user_token'] . '&sort=o.date_added' . $url, true);
       
        $url = '';

        if (isset($this->request->get['filter_plan_id'])) {
            $url .= '&filter_plan_id=' . $this->request->get['filter_plan_id'];
        }

        if (isset($this->request->get['filter_plan_name'])) {
            $url .= '&filter_plan_name=' . urlencode(html_entity_decode($this->request->get['filter_plan_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_plan_status'])) {
            $url .= '&filter_plan_status=' . $this->request->get['filter_plan_status'];
        }
      
        if (isset($this->request->get['filter_date_created'])) {
            $url .= '&filter_date_created=' . $this->request->get['filter_date_created'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        // $pagination = new Pagination();
        // $pagination->total = $order_total;
        // $pagination->page = $page;
        // $pagination->limit = $this->config->get('config_limit_admin');
        // $pagination->url = $this->url->link($path, 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        // $data['pagination'] = $pagination->render();

        // $data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($order_total - $this->config->get('config_limit_admin'))) ? $order_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $order_total, ceil($order_total / $this->config->get('config_limit_admin')));

        $data['filter_plan_id'] = $filter_plan_id;
        $data['filter_plan_name'] = $filter_plan_name;
        $data['filter_plan_status'] = $filter_plan_status;
        $data['filter_date_created'] = $filter_date_created;
        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['add'] = $this->url->link('extension/payment/razorpay_subscription/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['status'] = $this->url->link('extension/payment/razorpay_subscription/statusPlan', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/razorpay_plan_list', $data));
    }
    public function add()
    {
        $this->load->language('extension/payment/razorpay_subscription');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/payment/razorpay_subscription');
       
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
           $planName=$this->request->post['plan_name'];
            $planDesc=$this->request->post['plan_desc'];
            $productId=$this->request->post['product_id'];
            $planType=$this->request->post['plan_type'];
            $frequency=$this->request->post['billing_frequency'];
            $billCycle=$this->request->post['billing_cycle'];
            $amount=$this->request->post['billing_amount'];
            $trial=$this->request->post['plan_trial'];
            $addons=$this->request->post['plan_addons'];
             $status=$this->request->post['plan_status'];
              // Create Plan API
            try
            { 
                $api = $this->getApiIntance();

                $plan_data =  array('period' => $planType, 
                'interval' => $frequency,
                 'item' => array('name' => $planName, 'description' => $planDesc, 'amount' => $amount * 100, 'currency' => 'INR'),
                 'notes'=> array('trial'=> 'test','Addons'=> 'addons')
            
                );

                $razorpay_plan = $api->plan->create($plan_data);
                           
                $this->log->write("RZP PlanID (:" . $razorpay_plan['id'] . ") created");
            
            }
            catch(\Razorpay\Api\Errors\Error $e)
            {
                $this->log->write($e->getMessage());
                $this->session->data['error'] = $e->getMessage();
                echo "<div class='alert alert-danger alert-dismissible'> Something went wrong. Unable to create Razorpay Plan Id.</div>";
                exit;
            }
            $this->model_extension_payment_razorpay_subscription->addPlan($this->request->post, $razorpay_plan['id']);
           

            $this->session->data['success'] = $this->language->get('text_plan_success');

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('extension/payment/razorpay_subscription', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }
   
    //for status change
    public function statusPlan()
    {
       
        $this->load->language('extension/payment/razorpay_subscription');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/payment/razorpay_subscription');

        if ((isset($this->request->post['selected'])) && ($this->request->post['status'])) {
            $status = $this->request->post['status'];
            if($status==1) {
                foreach ($this->request->post['selected'] as $entity_id) {
                    $this->model_extension_payment_razorpay_subscription->enablePlan($entity_id);
                   
                }

                $this->session->data['success'] = $this->language->get('text_enable_success');
            } else if($status==2) {
                foreach ($this->request->post['selected'] as $entity_id) {
                    $this->model_extension_payment_razorpay_subscription->disablePlan($entity_id);
                }
                 $this->session->data['success'] = $this->language->get('text_disable_success');
            } else {
                $this->session->data['warning'] = $this->language->get('text_select_warning');
            }
            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('extension/payment/razorpay_subscription', 'user_token=' . $this->session->data['user_token'] . $url, true));
        } 

        $this->getList();

       
    }
      protected function validateForm()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/razorpay_subscription')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['plan_name']) < 1) || (utf8_strlen($this->request->post['plan_name']) > 64)) {
            $this->error['plan_name'] = $this->language->get('error_plan_name');
        }

        if ((utf8_strlen($this->request->post['plan_desc']) < 1) || (utf8_strlen($this->request->post['plan_desc']) > 64)) {
            $this->error['plan_desc'] = $this->language->get('error_plan_desc');
        }
              if ((!isset($this->request->get['billing_frequency'])) && ($this->request->post['billing_frequency'] < 1)) {
            $this->error['billing_frequency'] = $this->language->get('error_billing_frequency');
        }
        if ((!isset($this->request->get['billing_cycle'])) && ($this->request->post['billing_cycle'] < 1)) {
            $this->error['billing_cycle'] = $this->language->get('error_billing_cycle');
        }
        if ((!isset($this->request->get['billing_amount'])) && ($this->request->post['billing_amount'] < 1)) {
            $this->error['billing_amount'] = $this->language->get('error_billing_amount');
        }

        return !$this->error;
    }
    protected function getForm()
    {
        $data['text_form'] = !isset($this->request->get['entity_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->request->get['entity_id'])) {
            $data['entity_id'] = (int)$this->request->get['entity_id'];
        } else {
            $data['entity_id'] = 0;
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        //fields
        if (isset($this->error['plan_name'])) {
            $data['error_plan_name'] = $this->error['plan_name'];
        } else {
            $data['error_plan_name'] = '';
        }

        if (isset($this->error['plan_desc'])) {
            $data['error_plan_desc'] = $this->error['plan_desc'];
        } else {
            $data['error_plan_desc'] = '';
        }

        if (isset($this->error['product-name'])) {
            $data['error_product_name'] = $this->error['product-name'];
        } else {
            $data['error_product_name'] = '';
        }

        if (isset($this->error['billing_frequency'])) {
            $data['error_billing_frequency'] = $this->error['billing_frequency'];
        } else {
            $data['error_billing_frequency'] = '';
        }

        if (isset($this->error['billing_cycle'])) {
            $data['error_billing_cycle'] = $this->error['billing_cycle'];
        } else {
            $data['error_billing_cycle'] = '';
        }

        if (isset($this->error['billing_amount'])) {
            $data['error_billing_amount'] = $this->error['billing_amount'];
        } else {
            $data['error_billing_amount'] = '';
        }

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
        'text' => $this->language->get('text_home'),
        'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
        'text' => $this->language->get('heading_title'),
        'href' => $this->url->link('extension/payment/razorpay_subscription', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['entity_id'])) {
            $data['action'] = $this->url->link('extension/payment/razorpay_subscription/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('extension/payment/razorpay_subscription/edit', 'user_token=' . $this->session->data['user_token'] . '&entity_id=' . $this->request->get['entity_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('extension/payment/razorpay_subscription', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['user_token'] = $this->session->data['user_token'];
        //fields
        if (isset($this->request->post['code'])) {
            $data['code'] = $this->request->post['code'];
        } elseif (!empty($voucher_info)) {
            $data['code'] = $voucher_info['code'];
        } else {
            $data['code'] = '';
        }

        if (isset($this->request->post['from_name'])) {
            $data['from_name'] = $this->request->post['from_name'];
        } elseif (!empty($voucher_info)) {
            $data['from_name'] = $voucher_info['from_name'];
        } else {
            $data['from_name'] = '';
        }

        if (isset($this->request->post['from_email'])) {
            $data['from_email'] = $this->request->post['from_email'];
        } elseif (!empty($voucher_info)) {
            $data['from_email'] = $voucher_info['from_email'];
        } else {
            $data['from_email'] = '';
        }

        if (isset($this->request->post['to_name'])) {
            $data['to_name'] = $this->request->post['to_name'];
        } elseif (!empty($voucher_info)) {
            $data['to_name'] = $voucher_info['to_name'];
        } else {
            $data['to_name'] = '';
        }

        if (isset($this->request->post['to_email'])) {
            $data['to_email'] = $this->request->post['to_email'];
        } elseif (!empty($voucher_info)) {
            $data['to_email'] = $voucher_info['to_email'];
        } else {
            $data['to_email'] = '';
        }

        $this->load->model('sale/voucher_theme');

        $data['voucher_themes'] = $this->model_sale_voucher_theme->getVoucherThemes();

        if (isset($this->request->post['voucher_theme_id'])) {
            $data['voucher_theme_id'] = $this->request->post['voucher_theme_id'];
        } elseif (!empty($voucher_info)) {
            $data['voucher_theme_id'] = $voucher_info['voucher_theme_id'];
        } else {
            $data['voucher_theme_id'] = '';
        }

        if (isset($this->request->post['message'])) {
            $data['message'] = $this->request->post['message'];
        } elseif (!empty($voucher_info)) {
            $data['message'] = $voucher_info['message'];
        } else {
            $data['message'] = '';
        }

        if (isset($this->request->post['amount'])) {
            $data['amount'] = $this->request->post['amount'];
        } elseif (!empty($voucher_info)) {
            $data['amount'] = $voucher_info['amount'];
        } else {
            $data['amount'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($voucher_info)) {
            $data['status'] = $voucher_info['status'];
        } else {
            $data['status'] = true;
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/razorpay_plan_form', $data));
    }

    
    public function install()
    {
        $this->load->model('extension/payment/razorpay_subscription');
        
        $this->model_extension_payment_razorpay_subscription->createTables();
    }

    public function uninstall()
    {
        $this->load->model('extension/payment/razorpay_subscription');

        $this->model_extension_payment_razorpay_subscription->dropTables();
    }


    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/razorpay_subscription')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }


        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
    protected function getApiIntance()
    {
        return new Api($this->config->get('payment_razorpay_key_id'), $this->config->get('payment_razorpay_key_secret'));
    }


   
}
?>