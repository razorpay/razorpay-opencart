<?php
require_once __DIR__ . '/../../../../system/library/razorpay-sdk/Razorpay.php';
use Razorpay\Api\Api;
use Razorpay\Api\Errors;

class ControllerExtensionRecurringRazorpaySubscription extends Controller {

    private $api;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->api = $this->getApiIntance();
    }

    public function index()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('extension/recurring/razorpay_subscription', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }

        $this->load->language('extension/recurring/razorpay_subscription');
        $this->document->setTitle($this->language->get('heading_title'));

        $url = '';

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_account'),
            'href' => $this->url->link('account/account', '', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/recurring/razorpay_subscription', $url, true)
        );

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $this->load->model('extension/payment/razorpay_subscription');
        $recurring_total = $this->model_extension_payment_razorpay_subscription->getTotalOrderRecurrings();
        $results = $this->model_extension_payment_razorpay_subscription->getSubscriptionByUserId(($page - 1) * 10, 10);

        foreach ($results as $result) {
            $data['subscriptions'][] = [
                'id' => $result['entity_id'],
                'subscription_id' => $result['subscription_id'],
                'productName' => $result['productName'],
                'status' => ucfirst($result["status"]),
                'total_count' => $result["total_count"],
                'paid_count' => $result["paid_count"],
                'remaining_count' => $result["remaining_count"],
                'start_at' => isset($result['start_at'])?date($this->language->get('date_format_short'), strtotime($result['start_at'])):"",
                'subscription_created_at' => isset($result['subscription_created_at'])?date($this->language->get('date_format_short'), strtotime($result['subscription_created_at'])):"",
                'next_charge_at' => isset($result['next_charge_at'])?date($this->language->get('date_format_short'), strtotime($result['next_charge_at'])):"",
                'view' => $this->url->link('extension/recurring/razorpay_subscription/info', "subscription_id={$result['subscription_id']}", true),
            ];
        }
        
        $pagination = new Pagination();
        $pagination->total = $recurring_total;
        $pagination->page = $page;
        $pagination->limit = 10;
        $pagination->text = $this->language->get('text_pagination');
        $pagination->url = $this->url->link('extension/recurring/razorpay_subscription', 'page={page}', true);
        $data['pagination'] = $pagination->render();

        $data['continue'] = $this->url->link('account/account', '', true);
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        return $this->response->setOutput($this->load->view('extension/recurring/razorpay_subscription', $data));
    }

    public function info() {

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('extension/recurring/razorpay_subscription', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }
        $this->load->language('extension/recurring/razorpay_subscription');

        if (!empty($this->request->get['subscription_id'])) {
            $subscription_id = $this->request->get['subscription_id'];
        } else {
            $subscription_id = 0;
        }

        $this->load->model('extension/payment/razorpay_subscription');
        $recurring_info = $this->model_extension_payment_razorpay_subscription->getsubscriptionDetails($this->request->get['subscription_id']);

        if (!empty($recurring_info)) {
            $this->document->setTitle($this->language->get('heading_title_subscription'));

            $url = '';

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/home'),
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_account'),
                'href' => $this->url->link('account/account', '', true),
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/recurring/razorpay_subscription', $url, true),
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_heading_title_subscription'),
                'href' => $this->url->link('extension/recurring/razorpay_subscription/info', 'subscription_id=' . $this->request->get['subscription_id'] . $url, true),
            );
            $data['subscription_details'] = $recurring_info;

            $subscriptionInvoice = $this->api->invoice->all(['subscription_id'=> $this->request->get['subscription_id']])->toArray();
            $data["items"] = $subscriptionInvoice["items"];

            if($recurring_info["status"] == "active"){
                $data['pauseurl'] = $this->url->link('extension/recurring/razorpay_subscription/pause', 'subscription_id=' . $this->request->get['subscription_id'], true);
            } else if ($recurring_info["status"] == "paused"){
                $data['resumeurl'] = $this->url->link('extension/recurring/razorpay_subscription/resume', 'subscription_id=' . $this->request->get['subscription_id'], true);
            }
            $data['cancelurl'] = $this->url->link('extension/recurring/razorpay_subscription/cancel', 'subscription_id=' . $this->request->get['subscription_id'], true);

            $data["plan_data"] = $this->model_extension_payment_razorpay_subscription->getProductBasedPlans($recurring_info["product_id"]);
            $data["updateUrl"] = $this->url->link('extension/recurring/razorpay_subscription/update');


            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            return $this->response->setOutput($this->load->view('extension/recurring/razorpay_subscription_info', $data));
        } else {
            $this->document->setTitle($this->language->get('text_heading_title_subscription'));

            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/home')
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_account'),
                'href' => $this->url->link('account/account', '', true)
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/recurring/razorpay_subscription', '', true)
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_heading_title_subscription'),
                'href' => $this->url->link('extension/recurring/razorpay_subscription/info', 'subscription_id=' . $subscription_id, true)
            );

            $data['continue'] = $this->url->link('extension/recurring/razorpay_subscription', '', true);

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            return $this->response->setOutput($this->load->view('error/not_found', $data));
        }
    }

    public function resume()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('extension/recurring/razorpay_subscription', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }
        $this->load->language('extension/recurring/razorpay_subscription');

        if (!empty($this->request->get['subscription_id'])) {
            $subscription_id = $this->request->get['subscription_id'];
        } else {
            $subscription_id = 0;
        }
        try{
            $subscriptionData = $this->api->subscription->fetch($subscription_id)->resume(array('pause_at'=>'now'));
            $this->load->model('extension/payment/razorpay_subscription');

            $this->model_extension_payment_razorpay_subscription->updateSubscriptionStatus($this->request->get['subscription_id'],$subscriptionData->status );

            $this->session->data['success'] = $this->language->get('subscription_resumed_message');

            return $this->response->redirect($this->url->link('extension/recurring/razorpay_subscription/info', 'subscription_id=' . $subscription_id, true));
        } catch(\Razorpay\Api\Errors\Error $e)
        {
            $this->log->write($e->getMessage());
            header('Status: 400 Subscription pausing failed', true, 400);
            exit;
        }
    }

    public function pause()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('extension/recurring/razorpay_subscription', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }
        $this->load->language('extension/recurring/razorpay_subscription');


        if (!empty($this->request->get['subscription_id'])) {
            $subscription_id = $this->request->get['subscription_id'];
        } else {
            $subscription_id = 0;
        }
        try{
            $subscriptionData = $this->api->subscription->fetch($subscription_id)->pause(array('pause_at'=>'now'));
            $this->load->model('extension/payment/razorpay_subscription');

            $this->model_extension_payment_razorpay_subscription->updateSubscriptionStatus($this->request->get['subscription_id'],$subscriptionData->status);

            $this->session->data['success'] = $this->language->get('subscription_paused_message');

            return $this->response->redirect($this->url->link('extension/recurring/razorpay_subscription/info', 'subscription_id=' . $subscription_id, true));

        } catch(\Razorpay\Api\Errors\Error $e)
        {
            $this->log->write($e->getMessage());
            header('Status: 400 Subscription pausing failed', true, 400);
            exit;
        }
    }

    public function cancel()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('extension/recurring/razorpay_subscription', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }
        $this->load->language('extension/recurring/razorpay_subscription');

        if (!empty($this->request->get['subscription_id'])) {
            $subscription_id = $this->request->get['subscription_id'];
        } else {
            $subscription_id = 0;
        }
        try{
            $subscriptionData = $this->api->subscription->fetch($subscription_id)->cancel(array('cancel_at_cycle_end'=>0));
            $this->load->model('extension/payment/razorpay_subscription');

            $this->model_extension_payment_razorpay_subscription->updateSubscriptionStatus($this->request->get['subscription_id'],$subscriptionData->status, "user" );

            $this->session->data['success'] = $this->language->get('subscription_paused_message');

            return $this->response->redirect($this->url->link('extension/recurring/razorpay_subscription/info', 'subscription_id=' . $subscription_id, true));
        } catch(\Razorpay\Api\Errors\Error $e)
        {
            $this->log->write($e->getMessage());
            header('Status: 400 Subscription pausing failed', true, 400);
            exit;
        }
    }

    public function update()
    {
        try{
        $postData = $this->request->post;
        $this->load->model('extension/payment/razorpay_subscription');
        $planData = $this->model_extension_payment_razorpay_subscription->fetchPlanById($postData["plan_id"]);

        $planUpdateData['plan_id'] = $planData['plan_id'];

        if($postData['qty']){
            $planUpdateData['qty'] = $postData['qty'];
        }

        $this->api->subscription->fetch($postData["subscriptionId"])->update($planUpdateData);

        //Update plan in razorpay subscription table
        $this->model_extension_payment_razorpay_subscription->updateSubscription($postData);

        $this->session->data['success'] = $this->language->get('subscription_updated_message');

        return $this->response->redirect($this->url->link('extension/recurring/razorpay_subscription/info', 'subscription_id=' . $postData['subscriptionId'], true));

        } catch(\Razorpay\Api\Errors\Error $e)
        {
            $this->log->write($e->getMessage());
            header('Status: 400 Subscription pausing failed', true, 400);
            exit;
        }

    }

    protected function getApiIntance()
    {
        return new Api($this->config->get('payment_razorpay_key_id'), $this->config->get('payment_razorpay_key_secret'));
    }


}