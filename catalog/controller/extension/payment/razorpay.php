<?php

require_once __DIR__.'/../../../../system/library/razorpay-sdk/Razorpay.php';
use Razorpay\Api\Api;
use Razorpay\Api\Errors;

class ControllerExtensionPaymentRazorpay extends Controller
{
    /**
     * Event constants
     */
    const PAYMENT_AUTHORIZED    = 'payment.authorized';
    const PAYMENT_FAILED        = 'payment.failed';
    const ORDER_PAID            = 'order.paid';

    // Set RZP plugin version
    private $version = '4.0.2';

    public function index()
    {
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['is_recurring'] = "false";

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        try
        {
            $api = $this->getApiIntance();

            if($this->cart->hasRecurringProducts()){
                $this->load->model('extension/payment/razorpay');

                //validate for non-subscription product and if recurring is product for more than 1
                $this->validate_non_recurring_products();

                if($this->cart->hasRecurringProducts() > 1){
                    $this->log->write("Cart has more than 1 recurring product");
                    echo "<div class='alert alert-danger alert-dismissible'>Cannot checkout having more than 1 recurring product </div>";
                    exit;
                }

                $subscriptionData = $this->get_subscription_order_creation_data($this->session->data['order_id']);

                if(empty($this->session->data["razorpay_subscription_id_" . $this->session->data['order_id']]) === true)
                {
                    $subscription_order = $api->subscription->create($subscriptionData['subscriptionData'])->toArray();
                    $subscription_order['id'] = $subscription_order["id"];
                    // Save subscription details to DB
                    $this->model_extension_payment_razorpay->saveSubscriptionDetails($subscription_order, $subscriptionData["planData"],$subscriptionData['subscriptionData']['customer_id'] );

                    $this->session->data["razorpay_subscription_order_id_" . $this->session->data['order_id']] = $subscription_order['id'];
                    $data['razorpay_order_id'] = $this->session->data["razorpay_subscription_order_id_" . $this->session->data['order_id']];
                    $data['is_recurring'] = "true";

                    $this->log->write("RZP subscriptionID (:" . $subscription_order['id'] . ") created for Opencart OrderID (:" . $this->session->data['order_id'] . ")");
                }

            } else {
                // Orders API with payment autocapture
                $order_data = $this->get_order_creation_data($this->session->data['order_id']);

                if(empty($this->session->data["razorpay_order_id_" . $this->session->data['order_id']]) === true)
                {
                    $razorpay_order = $api->order->create($order_data);

                    $this->session->data["razorpay_order_id_" . $this->session->data['order_id']] = $razorpay_order['id'];
                    $data['razorpay_order_id'] = $this->session->data["razorpay_order_id_" . $this->session->data['order_id']];

                    $this->log->write("RZP orderID (:" . $razorpay_order['id'] . ") created for Opencart OrderID (:" . $this->session->data['order_id'] . ")");
                }
            }
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $this->log->write($e->getMessage());
            $this->session->data['error'] = $e->getMessage();
            echo "<div class='alert alert-danger alert-dismissible'> Something went wrong. Unable to create Razorpay Order Id.</div>";
            exit;
        }


        $data['key_id'] = $this->config->get('payment_razorpay_key_id');
        $data['currency_code'] = $order_info['currency_code'];
        $data['total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100;
        $data['merchant_order_id'] = $this->session->data['order_id'];
        $data['card_holder_name'] = $order_info['payment_firstname'].' '.$order_info['payment_lastname'];
        $data['email'] = $order_info['email'];
        $data['phone'] = $order_info['telephone'];
        $data['name'] = $this->config->get('config_name');
        $data['lang'] = $this->session->data['language'];
        $data['return_url'] = $this->url->link('extension/payment/razorpay/callback', '', 'true');
        $data['version'] = $this->version;
        $data['oc_version'] = VERSION;

        //varify if 'hosted' checkout required and set related data
        $this->getMerchantPreferences($data);

        $data['api_url']    = $api->getBaseUrl();
        $data['cancel_url'] =  $this->url->link('checkout/checkout', '', 'true');

        if (file_exists(DIR_TEMPLATE.$this->config->get('config_template').'/template/extension/payment/razorpay'))
        {
            return $this->load->view($this->config->get('config_template').'/template/extension/payment/razorpay', $data);
        }
        else
        {
            return $this->load->view('extension/payment/razorpay', $data);
        }
    }

    private function get_order_creation_data($order_id)
    {
        $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $data = [
            'receipt' => $order_id,
            'amount' => $this->currency->format($order['total'], $order['currency_code'], $order['currency_value'], false) * 100,
            'currency' => $order['currency_code'],
            'payment_capture' => ($this->config->get('payment_razorpay_payment_action') === 'authorize') ? 0 : 1
        ];

        return $data;
    }

    public function validate_non_recurring_products()
    {
        $nonRecurringProduct = array_filter($this->cart->getProducts(),function ($product){
            return array_filter($product, function ($value,$key) {
                return $key == "recurring" && empty($value);
            }, ARRAY_FILTER_USE_BOTH);
        });

        if(!empty($nonRecurringProduct)){
            $this->log->write("Cart has recurring product and non recurring product");
            echo "<div class='alert alert-danger alert-dismissible'>You cannot have non-recurring and recurring product in your shopping cart </div>";
            exit;
        }
    }

    private function get_subscription_order_creation_data($order_id){
        $this->load->model('extension/payment/razorpay');

        $order = $this->model_checkout_order->getOrder($order_id);
        $recurringPlanData = $this->cart->getProducts()[0]["recurring"];

        $planData = $this->model_extension_payment_razorpay->getPlanByRecurringIdAndFrequency($recurringPlanData['recurring_id'], $recurringPlanData['frequency']);
        $subscriptionData = [
            "customer_id" => $this->getRazorpayCustomerData($order),
            "plan_id" => $planData['plan_id'],
            "total_count" => $planData['plan_bill_cycle'],
            "quantity" => $this->cart->getProducts()[0]['quantity'],
            "customer_notify" => 0,
            "notes" => [
                "source" => "opencart-subscription",
                "merchant_order_id" => $order_id,
            ],
            "source" => "opencart-subscription",
        ];
        if ($planData['plan_trial']) {
            $subscriptionData["start_at"] = strtotime("+{$planData['plan_trial']} days");
        }

        if ($planData['plan_addons']) {
            $item["item"] = [
                "name" => "Addon amount",
                "amount" => (int)(number_format($planData["plan_addons"] * 100, 0, ".", "")),
                "currency" => $this->session->data['currency'],
                "description" => "Addon amount"
            ];
            $subscriptionData["addons"] = $item;
        }

        return ["subscriptionData" => $subscriptionData, "planData" => $planData];

    }


    public function callback()
    {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/razorpay');


        if (isset($this->request->request['razorpay_payment_id']) === true)
        {
            $razorpay_payment_id = $this->request->request['razorpay_payment_id'];
            $razorpay_signature = $this->request->request['razorpay_signature'];
            $merchant_order_id = $this->session->data['order_id'];
            $isSubscriptionCallBack = false;
            if(array_key_exists($this->session->data["razorpay_subscription_order_id_" . $this->session->data['order_id']],$this->session->data )) {
                $razorpay_subscription_id = $this->session->data["razorpay_subscription_order_id" . $this->session->data['order_id']];
                $isSubscriptionCallBack = true;
                $attributes = array(
                    'razorpay_subscription_id' => $razorpay_subscription_id,
                    'razorpay_payment_id' => $razorpay_payment_id,
                    'razorpay_signature' => $razorpay_signature
                );
            } else {
                $razorpay_order_id = $this->session->data["razorpay_order_id_" . $this->session->data['order_id']];
                $attributes = array(
                    'razorpay_order_id' => $razorpay_order_id,
                    'razorpay_payment_id' => $razorpay_payment_id,
                    'razorpay_signature' => $razorpay_signature
                );
            }

            $order_info = $this->model_checkout_order->getOrder($merchant_order_id);
            $amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100;

            //validate Rzp signature
            $api = $this->getApiIntance();
            try
            {
                $api->utility->verifyPaymentSignature($attributes);
                if($isSubscriptionCallBack){
                    $subscriptionData = $api->subscription->fetch($razorpay_subscription_id)->toArray();
                    $this->model_extension_payment_razorpay->updateSubscription($subscriptionData, $razorpay_subscription_id);
                }

                $this->model_checkout_order->addOrderHistory($merchant_order_id, $this->config->get('payment_razorpay_order_status_id'), 'Payment Successful. Razorpay Payment Id:'.$razorpay_payment_id, true);
                $this->response->redirect($this->url->link('checkout/success', '', true));
            }
            catch(\Razorpay\Api\Errors\SignatureVerificationError $e)
            {
                $this->model_checkout_order->addOrderHistory($merchant_order_id, 10, $e->getMessage() .' Payment Failed! Check Razorpay dashboard for details of Payment Id:'.$razorpay_payment_id);

                $this->session->data['error'] = $e->getMessage() .' Payment Failed! Check Razorpay dashboard for details of Payment Id:'.$razorpay_payment_id;
                $this->response->redirect($this->url->link('checkout/checkout', '', true));
            }
        }
        else
        {
            if (isset($_POST['error']) === true)
            {
                $error = $_POST['error'];

                $message = 'An error occured. Description : ' . $error['description'] . '. Code : ' . $error['code'];

                if (isset($error['field']) === true)
                {
                    $message .= 'Field : ' . $error['field'];
                }
            }
            else
            {
                $message = 'An error occured. Please contact administrator for assistance';
            }
            $this->session->data['error'] = $message;
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
        }
    }


    public function webhook()
    {
        $post = file_get_contents('php://input');
        $data = json_decode($post, true);

        if (json_last_error() !== 0)
        {
            return;
        }
        $this->load->model('checkout/order');
        $enabled = $this->config->get('payment_razorpay_webhook_status');

        if (($enabled === '1') and
            (empty($data['event']) === false))
        {

            if (isset($_SERVER['HTTP_X_RAZORPAY_SIGNATURE']) === true)
            {
                try
                {
                    $this->validateSignature($post , $_SERVER['HTTP_X_RAZORPAY_SIGNATURE']);
                }
                catch (\Razorpay\Api\Errors\SignatureVerificationError $e)
                {
                    $this->log->write($e->getMessage());
                    header('Status: 400 Signature Verification failed', true, 400);
                    exit;
                }

                switch ($data['event'])
                {
                    case self::PAYMENT_AUTHORIZED:
                        return $this->paymentAuthorized($data);

                    case self::PAYMENT_FAILED:
                        return $this->paymentFailed($data);

                    case self::ORDER_PAID:
                        return $this->orderPaid($data);

                    default:
                        return;
                }
            }
        }
    }

    /**
     * Handling order.paid event
     * @param array $data Webook Data
     */
    protected function orderPaid(array $data)
    {
        // reference_no (opencart_order_id) should be passed in payload
        $merchant_order_id = $data['payload']['payment']['entity']['notes']['opencart_order_id'];
        $razorpay_payment_id = $data['payload']['payment']['entity']['id'];
        if(isset($merchant_order_id) === true)
        {
            $order_info = $this->model_checkout_order->getOrder($merchant_order_id);

            if($order_info['payment_code'] === 'razorpay' and
                !$order_info['order_status_id'])
            {

                $this->model_checkout_order->addOrderHistory($merchant_order_id, $this->config->get('payment_razorpay_order_status_id'), 'Payment Successful. Razorpay Payment Id:'.$razorpay_payment_id);
            }
        }
        // Graceful exit since payment is now processed.
        $this->response->addHeader('HTTP/1.1 200 OK');
        $this->response->addHeader('Content-Type: application/json');
    }

    /**
     * Handling payment.failed event
     * @param array $data Webook Data
     */
    protected function paymentFailed(array $data)
    {
        exit;
    }

    /**
     * Handling payment.authorized event
     * @param array $data Webook Data
     */
    protected function paymentAuthorized(array $data)
    {
        //verify if we need to consume it as late authorized
        $max_capture_delay = $this->config->get('payment_razorpay_max_capture_delay') * 60;
        $payment_created_time = $data['payload']['payment']['entity']['created_at'];

        $api = $this->getApiIntance();

        if((time() - $payment_created_time) < $max_capture_delay)
        {
            // reference_no (opencart_order_id) should be passed in payload
            $merchant_order_id = $data['payload']['payment']['entity']['notes']['opencart_order_id'];
            $razorpay_payment_id = $data['payload']['payment']['entity']['id'];

            //update the order
            if(isset($merchant_order_id) === true)
            {
                $order_info = $this->model_checkout_order->getOrder($merchant_order_id);

                if($order_info['payment_code'] === 'razorpay' and
                    !$order_info['order_status_id'])
                {
                    try
                    {
                        $capture_amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100;

                        //fetch the payment
                        $payment = $api->payment->fetch($razorpay_payment_id);

                        //capture only if payment status is 'authorized'
                        if($payment->status === 'authorized')
                        {
                            $payment->capture(array('amount' => $capture_amount,
                                'currency' => $order_info['currency_code']
                            ));
                        }

                        //update the order status in store
                        $this->model_checkout_order->addOrderHistory($merchant_order_id, $this->config->get('payment_razorpay_order_status_id'), 'Payment Successful. Razorpay Payment Id:'.$razorpay_payment_id);
                    }
                    catch(\Razorpay\Api\Errors\Error $e)
                    {
                        $this->log->write($e->getMessage());
                        header('Status: 400 Payment Capture failed', true, 400);
                        exit;
                    }

                }
            }
        }
        // Graceful exit since payment is now processed.
        $this->response->addHeader('HTTP/1.1 200 OK');
        $this->response->addHeader('Content-Type: application/json');
        exit;
    }


    /**
     * @param $payloadRawData
     * @param $actualSignature
     */
    public function validateSignature($payloadRawData, $actualSignature)
    {
        $api = $this->getApiIntance();

        $webhookSecret = $this->config->get('payment_razorpay_webhook_secret');

        if (empty($webhookSecret) === false)
        {
            $api->utility
                ->verifyWebhookSignature($payloadRawData, $actualSignature, $webhookSecret);
        }

    }

    public function getMerchantPreferences(array &$preferences)
    {
        $api = $this->getApiIntance();

        try
        {
            $response = Requests::get($api->getBaseUrl() . 'preferences?key_id=' . $api->getKey());
        }
        catch (Exception $e)
        {
            $this->log->write($e->getMessage());
            throw new Exception($e->getMessage(), $e->getHttpCode());
        }

        $preferences['is_hosted'] = false;

        if($response->status_code === 200)
        {

            $jsonResponse = json_decode($response->body, true);

            $preferences['image'] = $jsonResponse['options']['image'];
            if(empty($jsonResponse['options']['redirect']) === false)
            {
                $preferences['is_hosted'] = $jsonResponse['options']['redirect'];
            }
        }

    }

    protected function getApiIntance()
    {
        return new Api($this->config->get('payment_razorpay_key_id'), $this->config->get('payment_razorpay_key_secret'));
    }

    /**
     * This line of code tells api that if a customer is already created,
     * return the created customer instead of throwing an exception
     * https://docs.razorpay.com/v1/page/customers-api
     * @param $order
     * @return void
     */
    protected function getRazorpayCustomerData($order)
    {
        try {

            $api = $this->getApiIntance();
            $customerData = [
                'email' => $order['email'],
                'name' => $order['firstname']. " ". $order['lastname'],
                'contact' => $order['telephone'],
                'fail_existing' => 0
            ];
            $customerResponse = $api->customer->create($customerData);

            return $customerResponse->id;
        } catch (\Exception $e) {
            $this->log->write("Razopray exception: {$e->getMessage()}");
            $this->session->data['error'] = $e->getMessage();
            echo "<div class='alert alert-danger alert-dismissible'> Something went wrong</div>";
            exit;
        }
    }

}