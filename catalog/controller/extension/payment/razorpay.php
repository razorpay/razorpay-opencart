<?php

require_once __DIR__.'/../../../../system/library/razorpay/razorpay-sdk/Razorpay.php';
require_once __DIR__.'/../../../../system/library/razorpay/razorpay-lib/createwebhook.php';
use Razorpay\Api\Api;
use Razorpay\Api\Errors;

class ControllerExtensionPaymentRazorpay extends Controller
{
    /**
     * Event constants
     */
    const PAYMENT_AUTHORIZED        = 'payment.authorized';
    const PAYMENT_FAILED            = 'payment.failed';
    const ORDER_PAID                = 'order.paid';
    const WEBHOOK_URL               = HTTPS_SERVER . 'index.php?route=extension/payment/razorpay/webhook';
    const SUBSCRIPTION_PAUSED       = 'subscription.paused';
    const SUBSCRIPTION_RESUMED      = 'subscription.resumed';
    const SUBSCRIPTION_CANCELLED    = 'subscription.cancelled';
    const SUBSCRIPTION_CHARGED      = 'subscription.charged';
    const WEBHOOK_WAIT_TIME         = 120;
    const HTTP_CONFLICT_STATUS      = 409;
    const CURRENCY_NOT_ALLOWED  = [
        'KWD',
        'OMR',
        'BHD',
    ];

    // Set RZP plugin version
    private $version = '5.1.8';

    private $api;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->api = $this->getApiIntance();
    }

    public function index()
    {
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['is_recurring'] = "false";

        $this->load->model('checkout/order');
        $this->load->model('extension/payment/razorpay');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        if (in_array($order_info['currency_code'],  self::CURRENCY_NOT_ALLOWED) === true)
        {
            $this->log->write("Order creation failed, because currency (" . $order_info['currency_code'] . ") not supported");
            echo "<div class='alert alert-danger alert-dismissible'>Order creation failed, because currency (" . $order_info['currency_code'] . ") not supported.</div>";
            exit ;
        }

        try
        {
            if ($this->cart->hasRecurringProducts() and
                $this->config->get('payment_razorpay_subscription_status'))
            {
                //validate for non-subscription product and if recurring is product for more than 1
                $this->validate_non_recurring_products();

                if ($this->cart->hasRecurringProducts() > 1)
                {
                    $this->log->write("Cart has more than 1 recurring product");
                    echo "<div class='alert alert-danger alert-dismissible'>We do not support payment of two different subscription products at once. Please remove one of the products from your cart to proceed.</div>";
                    exit ;
                }

                $subscriptionData = $this->get_subscription_order_creation_data($this->session->data['order_id']);

                if(empty($this->session->data["razorpay_subscription_id_" . $this->session->data['order_id']]) === true)
                {
                    $subscription_order = $this->api->subscription->create($subscriptionData['subscriptionData'])->toArray();

                    // Save subscription details to DB
                    $this->model_extension_payment_razorpay->saveSubscriptionDetails($subscription_order, $subscriptionData["planData"], $subscriptionData['subscriptionData']['customer_id'], $this->session->data['order_id']);

                    $this->session->data["razorpay_subscription_order_id_" . $this->session->data['order_id']] = $subscription_order['id'];
                    $data['razorpay_order_id'] = $this->session->data["razorpay_subscription_order_id_" . $this->session->data['order_id']];
                    $data['is_recurring'] = "true";
                    $recurring_description = "Recurring order ";
                    $cartDetails = $this->cart->getProducts();

                    $recurringData = [
                        "order_id" => $this->session->data['order_id'],
                        "product_id" => $cartDetails[0]["product_id"],
                        "product_name" => $cartDetails[0]["name"],
                        "product_quantity" =>$cartDetails[0]["quantity"],
                        "recurring_id" => $cartDetails[0]["recurring"]["recurring_id"],
                        "recurring_name" =>$cartDetails[0]["recurring"]["name"],
                        "recurring_description" => $cartDetails[0]["recurring"]["frequency"] . "ly recurring with SubscriptionId ".$subscription_order['id'],
                        "recurring_frequency" => $cartDetails[0]["recurring"]["frequency"] . "ly",
                        "recurring_cycle" => $cartDetails[0]["recurring"]["cycle"],
                        "recurring_duration" => $cartDetails[0]["recurring"]["duration"],
                        "recurring_price" => $cartDetails[0]["recurring"]["price"],
                        "trial" => $cartDetails[0]["recurring"]["trial"],
                        "trial_frequency" => $cartDetails[0]["recurring"]["trial_frequency"],
                        "trial_cycle" => $cartDetails[0]["recurring"]["trial_cycle"],
                        "trial_duration" => $cartDetails[0]["recurring"]["trial_duration"],
                        "trial_price" => $cartDetails[0]["recurring"]["trial_price"],
                        "reference" => "Subscription Id ". $subscription_order['id']
                    ];

                    $this->model_extension_payment_razorpay->createOCRecurring($recurringData);

                    $this->log->write("RZP subscriptionID (:" . $subscription_order['id'] . ") created for Opencart OrderID (:" . $this->session->data['order_id'] . ")");
                }

            }
            else
            {
                // Orders API with payment autocapture
                $order_data = $this->get_order_creation_data($this->session->data['order_id']);

                if (isset($this->session->data["razorpay_order_amount"]) === false)
                {
                    $this->session->data["razorpay_order_amount"] = 0;
                }

                if ((isset($this->session->data["razorpay_order_id_" . $this->session->data['order_id']]) === false) or
                    ((isset($this->session->data["razorpay_order_id_" . $this->session->data['order_id']]) === true) and
                    (($this->session->data["razorpay_order_amount"] === 0) or
                    ($this->session->data["razorpay_order_amount"] !== $order_data["amount"]))))
                {
                    $razorpay_order = $this->api->order->create($order_data);

                    $this->session->data["razorpay_order_amount"] = $order_data["amount"];
                    $this->session->data["razorpay_order_id_" . $this->session->data['order_id']] = $razorpay_order['id'];
                    $data['razorpay_order_id'] = $this->session->data["razorpay_order_id_" . $this->session->data['order_id']];
                    $this->model_extension_payment_razorpay->addOrderForWebhook($this->session->data['order_id'], $razorpay_order['id'], 0);

                    $this->log->write("RZP orderID (:" . $razorpay_order['id'] . ") created for Opencart OrderID (:" . $this->session->data['order_id'] . ")");
                }
            }

        }
        catch (\Razorpay\Api\Errors\Error $e)
        {
            $this->log->write($e->getMessage());
            $this->session->data['error'] = $e->getMessage();
            echo "<div class='alert alert-danger alert-dismissible'> Something went wrong. Unable to create Razorpay Order Id.</div>";
            exit;
        }

        try
        {
            $webhookUpdatedAt = $this->config->get('payment_razorpay_webhook_updated_at');

            if ($webhookUpdatedAt + 86400 < time())
            {
                $createWebhook = new CreateWebhook(
                    $this->config->get('payment_razorpay_key_id'),
                    $this->config->get('payment_razorpay_key_secret'),
                    $this->config->get('payment_razorpay_webhook_secret'),
                    self::WEBHOOK_URL,
                    $this->config->get('payment_razorpay_subscription_status')
                );

                $webhookConfigData = $createWebhook->autoCreateWebhook();

                $this->load->model('extension/payment/razorpay');
                $this->model_extension_payment_razorpay->editSetting('payment_razorpay', $webhookConfigData);
            }
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $this->log->write('Unable to update webhook status');
            $this->log->write($e->getMessage());
        }

        $data['key_id'] = $this->config->get('payment_razorpay_key_id');
        $data['currency_code'] = $order_info['currency_code'];
        $data['total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100;
        $data['merchant_order_id'] = $this->session->data['order_id'];
        $data['card_holder_name'] = $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'];
        $data['email'] = $order_info['email'];
        $data['phone'] = $order_info['telephone'];
        $data['name'] = $this->config->get('config_name');
        $data['lang'] = $this->session->data['language'];
        $data['return_url'] = $this->url->link('extension/payment/razorpay/callback', '', 'true');
        $data['version'] = $this->version;
        $data['oc_version'] = VERSION;

        //verify if 'hosted' checkout required and set related data
        $this->getMerchantPreferences($data);

        $data['api_url']    = $this->api->getBaseUrl();
        $data['cancel_url'] =  $this->url->link('checkout/checkout', '', 'true');

        header('Set-Cookie: ' . $this->config->get('session_name') . '=' . $this->session->getId() . '; HttpOnly; SameSite=None; Secure; HttpOnly;');

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/razorpay'))
        {
            return $this->load->view($this->config->get('config_template') . '/template/extension/payment/razorpay', $data);
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
            'receipt' => (string)$order_id,
            'amount' => $this->currency->format($order['total'], $order['currency_code'], $order['currency_value'], false) * 100,
            'currency' => $order['currency_code'],
            'payment_capture' => ($this->config->get('payment_razorpay_payment_action') === 'authorize') ? 0 : 1
        ];

        return $data;
    }

    public function validate_non_recurring_products()
    {
        $nonRecurringProduct = array_filter($this->cart->getProducts(), function ($product)
        {
            return array_filter($product, function ($value, $key) {
                return $key == "recurring" and empty($value);
            }, ARRAY_FILTER_USE_BOTH);
        });

        if (!empty($nonRecurringProduct))
        {
            $this->log->write("Cart has recurring product and non recurring product");
            echo "<div class='alert alert-danger alert-dismissible'>You have a one-time payment product and a subscription payment product in your cart. Please remove one of the products from the cart to proceed.</div>";
            exit;
        }
    }

    private function get_subscription_order_creation_data($order_id)
    {
        $this->load->model('extension/payment/razorpay');

        $order = $this->model_checkout_order->getOrder($order_id);
        $recurringPlanData = $this->cart->getProducts()[0]["recurring"];
        $productId = $this->cart->getProducts()[0]['product_id'];

        $planData = $this->model_extension_payment_razorpay->getPlanByRecurringIdAndFrequencyAndProductId($recurringPlanData['recurring_id'], $recurringPlanData['frequency'], $productId);

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

        if ($planData['plan_trial'])
        {
            $subscriptionData["start_at"] = strtotime("+{$planData['plan_trial']} days");
        }

        if ($planData['plan_addons'])
        {
            $item["item"] = [
                "name" => "Addon amount",
                "amount" => (int)(number_format($planData["plan_addons"] * 100, 0, ".", "")),
                "currency" => $this->session->data['currency'],
                "description" => "Addon amount"
            ];
            $subscriptionData["addons"][] = $item;
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

            if (array_key_exists("razorpay_subscription_order_id_" . $this->session->data['order_id'], $this->session->data))
            {
                $razorpay_subscription_id = $this->session->data["razorpay_subscription_order_id_" . $this->session->data['order_id']];
                $isSubscriptionCallBack = true;

                $attributes = array(
                    'razorpay_subscription_id' => $razorpay_subscription_id,
                    'razorpay_payment_id' => $razorpay_payment_id,
                    'razorpay_signature' => $razorpay_signature
                );
            }
            else
            {
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
            try
            {
                $this->api->utility->verifyPaymentSignature($attributes);
                if ($isSubscriptionCallBack)
                {
                    $subscriptionData = $this->api->subscription->fetch($razorpay_subscription_id)->toArray();

                    $planData = $this->model_extension_payment_razorpay->fetchRZPPlanById($subscriptionData['plan_id']);
                    $this->model_extension_payment_razorpay->updateSubscription($subscriptionData, $razorpay_subscription_id);

                    // Update oC recurring table and OC recurring transaction
                    $this->model_extension_payment_razorpay->updateOCRecurringStatus($this->session->data['order_id'], 1);

                    // Creating OC Recurring Transaction
                    $ocRecurringData = $this->model_extension_payment_razorpay->getOCRecurringStatus($this->session->data['order_id']);
                    $this->model_extension_payment_razorpay->addOCRecurringTransaction($ocRecurringData['order_recurring_id'], $razorpay_subscription_id, $planData['plan_bill_amount'], "success");
                }

                if ($order_info['payment_code'] === 'razorpay' and
                    $order_info['order_status_id'] === '0')
                {
                    $this->model_checkout_order->addOrderHistory($merchant_order_id, $this->config->get('payment_razorpay_order_status_id'), 'Payment Successful. Razorpay Payment Id:' . $razorpay_payment_id, true);
                    $this->model_extension_payment_razorpay->updateOrderForWebhook($merchant_order_id, $razorpay_order_id, $this->config->get('payment_razorpay_order_status_id'));
                }
                $this->response->redirect($this->url->link('checkout/success', '', true));
            }
            catch (\Razorpay\Api\Errors\SignatureVerificationError $e)
            {
                if ($isSubscriptionCallBack)
                {
                    // Update oC recurring table for failed payment
                    $this->model_extension_payment_razorpay->updateOCRecurringStatus($this->session->data['order_id'], 4);
                }
                $this->model_checkout_order->addOrderHistory($merchant_order_id, 10, $e->getMessage() . ' Payment Failed! Check Razorpay dashboard for details of Payment Id:' . $razorpay_payment_id);

                $this->session->data['error'] = $e->getMessage() . ' Payment Failed! Check Razorpay dashboard for details of Payment Id:' . $razorpay_payment_id;
                $this->response->redirect($this->url->link('checkout/checkout', '', true));
            }
        }
        else
        {
            if (isset($_POST['error']) === true)
            {
                $error = $_POST['error'];

                $message = 'An error occured. Description : ' . $error['description'] . '. Code : ' . $error['code'];

                if (isset($error['field']) === true) {
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
        $this->load->model('extension/payment/razorpay');
        $enabled = $this->config->get('payment_razorpay_webhook_status');

        if (($enabled === '1') and
            (empty($data['event']) === false))
        {

            if (isset($_SERVER['HTTP_X_RAZORPAY_SIGNATURE']) === true)
            {
                try
                {
                    $this->validateSignature($post, $_SERVER['HTTP_X_RAZORPAY_SIGNATURE']);
                }
                catch (\Razorpay\Api\Errors\SignatureVerificationError $e)
                {
                    $this->log->write($e->getMessage());
                    return;
                }

                if (in_array($data['event'], [self::ORDER_PAID, self::PAYMENT_AUTHORIZED]) === true)
                {
                    $webhookFilteredData = [
                        "id"                => $data['payload']['payment']['entity']['id'],
                        "event"             => $data['event'],
                        "opencart_order_id" => $data['payload']['payment']['entity']['notes']['opencart_order_id']
                    ];

                    if ($data['event'] === self::ORDER_PAID)
                    {
                        $webhookFilteredData['invoice_id'] = $data['payload']['payment']['entity']['invoice_id'];
                        sleep(3);
                    }
                    $this->model_extension_payment_razorpay->addWebhookEvent(
                        $data['payload']['payment']['entity']['notes']['opencart_order_id'],
                        $data['payload']['payment']['entity']['order_id'],
                        $webhookFilteredData
                    );
                }
                else
                {
                    switch ($data['event'])
                    {
                        case self::PAYMENT_FAILED:
                            return $this->paymentFailed($data);
                        case self::SUBSCRIPTION_PAUSED:
                        case self::SUBSCRIPTION_RESUMED:
                        case self::SUBSCRIPTION_CANCELLED:
                            return $this->updateOcSubscriptionStatus($data);
                        case self::SUBSCRIPTION_CHARGED:
                            return $this->processSubscriptionCharged($data);
                        default:
                            return;
                    }
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
        // Do not process if order is subscription type
        if (isset($data['invoice_id']) === true)
        {
            $rzpInvoiceId = $data['invoice_id'];
            $invoice = $this->api->invoice->fetch($rzpInvoiceId);
            if (isset($invoice->subscription_id))
            {
                return;
            }
        }

        // reference_no (opencart_order_id) should be passed in payload
        $merchant_order_id = $data['opencart_order_id'];
        $razorpay_payment_id = $data['id'];

        if (isset($merchant_order_id) === true)
        {
            $order_info = $this->model_checkout_order->getOrder($merchant_order_id);
            if ($order_info['payment_code'] === 'razorpay' and
                $order_info['order_status_id'] === '0')
            {
                $this->model_checkout_order->addOrderHistory($merchant_order_id, $this->config->get('payment_razorpay_order_status_id'), 'Payment Successful. Razorpay Payment Id:' . $razorpay_payment_id);
                $this->log->write("order:$merchant_order_id updated by razorpay order.paid event");
            }
        }
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
        if ($this->config->get('payment_razorpay_payment_action') === "capture")
        {
            return;
        }

        // reference_no (opencart_order_id) should be passed in payload
        $merchant_order_id = $data['opencart_order_id'];
        $razorpay_payment_id = $data['id'];

        //update the order
        if (isset($merchant_order_id) === true)
        {
            $order_info = $this->model_checkout_order->getOrder($merchant_order_id);

            if ($order_info['payment_code'] === 'razorpay' and
                $order_info['order_status_id'] === '0')
            {
                try {
                    $capture_amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100;

                    //fetch the payment
                    $payment = $this->api->payment->fetch($razorpay_payment_id);

                    //capture only if payment status is 'authorized'
                    if ($payment->status === 'authorized'
                        and $this->config->get('payment_razorpay_payment_action') === 'capture')
                    {
                        $payment->capture(
                            array(
                                'amount' => $capture_amount,
                                'currency' => $order_info['currency_code']
                            ));
                    }
                    //update the order status in store
                    $this->model_checkout_order->addOrderHistory($merchant_order_id, $this->config->get('payment_razorpay_order_status_id'), 'Payment Successful. Razorpay Payment Id:' . $razorpay_payment_id);
                    $this->log->write("order:$merchant_order_id updated by razorpay payment.authorized event");
                }
                catch (\Razorpay\Api\Errors\Error $e)
                {
                    $this->log->write($e->getMessage());
                    return;
                }
            }
        }
    }


    /**
     * @param $payloadRawData
     * @param $actualSignature
     */
    public function validateSignature($payloadRawData, $actualSignature)
    {
        $webhookSecret = $this->config->get('payment_razorpay_webhook_secret');

        if (empty($webhookSecret) === false)
        {
            $this->api->utility->verifyWebhookSignature($payloadRawData, $actualSignature, $webhookSecret);
        }

    }

    public function getMerchantPreferences(array &$preferences)
    {
        try
        {
            $response = Requests::get($this->api->getBaseUrl() . '/preferences?key_id=' . $this->api->getKey());
        }
        catch (Exception $e)
        {
            $this->log->write($e->getMessage());
            throw new Exception($e->getMessage(), $e->getCode());
        }

        $preferences['is_hosted'] = false;

        if ($response->status_code === 200) {

            $jsonResponse = json_decode($response->body, true);

            $preferences['image'] = $jsonResponse['options']['image'];

            if (empty($jsonResponse['options']['redirect']) === false) {
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
        try
        {
            $customerData = [
                'email' => $order['email'],
                'name' => $order['firstname'] . " " . $order['lastname'],
                'contact' => $order['telephone'],
                'fail_existing' => 0
            ];

            $customerResponse = $this->api->customer->create($customerData);

            return $customerResponse->id;
        }
        catch (\Exception $e)
        {
            $this->log->write("Razopray exception Customer: {$e->getMessage()}");
            $this->session->data['error'] = $e->getMessage();
            echo "<div class='alert alert-danger alert-dismissible'> Something went wrong</div>";

            return;
        }
    }

    /**
     * Fetch subscription list
     */
    public function subscriptions()
    {
        if (!$this->customer->isLogged())
        {
            $this->session->data['redirect'] = $this->url->link('extension/payment/razorpay/subscriptions', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }

        $this->load->language('extension/payment/razorpay');
        $this->document->setTitle($this->language->get('heading_title'));

        $url = '';

        if (isset($this->request->get['page']))
        {
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
            'href' => $this->url->link('extension/payment/razorpay/subscriptions', $url, true)
        );

        if (isset($this->request->get['page']))
        {
            $page = (int)$this->request->get['page'];
        }
        else
        {
            $page = 1;
        }

        $this->load->model('extension/payment/razorpay');
        $recurring_total = $this->model_extension_payment_razorpay->getTotalOrderRecurring();
        $results = $this->model_extension_payment_razorpay->getSubscriptionByUserId(($page - 1) * 10, 10);

        foreach ($results as $result)
        {
            $data['subscriptions'][] = [
                'id' => $result['entity_id'],
                'subscription_id' => $result['subscription_id'],
                'productName' => $result['productName'],
                'status' => ucfirst($result["status"]),
                'total_count' => $result["total_count"],
                'paid_count' => $result["paid_count"],
                'remaining_count' => $result["remaining_count"],
                'start_at' => isset($result['start_at']) ? date($this->language->get('date_format_short'), strtotime($result['start_at'])) : "",
                'end_at' => isset($result['start_at']) ? date($this->language->get('date_format_short'), strtotime($result['end_at'])) : "",
                'subscription_created_at' => isset($result['subscription_created_at']) ? date($this->language->get('date_format_short'), strtotime($result['subscription_created_at'])) : "",
                'next_charge_at' => isset($result['next_charge_at']) ? date($this->language->get('date_format_short'), strtotime($result['next_charge_at'])) : "",
                'view' => $this->url->link('extension/payment/razorpay/info', "subscription_id={$result['subscription_id']}", true),
            ];
        }

        $pagination = new Pagination();
        $pagination->total = $recurring_total;
        $pagination->page = $page;
        $pagination->limit = 10;
        $pagination->text = $this->language->get('text_pagination');
        $pagination->url = $this->url->link('extension/payment/razorpay/subscriptions', 'page={page}', true);
        $data['pagination'] = $pagination->render();

        $data['continue'] = $this->url->link('account/account', '', true);
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        return $this->response->setOutput($this->load->view('extension/payment/razorpay_subscription/razorpay_subscription', $data));
    }

    /**
     * Subscription details
     * @return mixed
     */
    public function info()
    {
        if (!$this->customer->isLogged())
        {
            $this->session->data['redirect'] = $this->url->link('extension/payment/razorpay/subscriptions', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }
        $this->load->language('extension/payment/razorpay');

        if (!empty($this->request->get['subscription_id']))
        {
            $subscription_id = $this->request->get['subscription_id'];
        }
        else
        {
            $subscription_id = 0;
        }

        $this->load->model('extension/payment/razorpay');
        $recurring_info = $this->model_extension_payment_razorpay->getSubscriptionDetails($subscription_id);

        if(isset($this->session->data['error']))
        {
            $data['error'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }

        if(isset($this->session->data['success']))
        {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        if (!empty($recurring_info))
        {
            $this->document->setTitle($this->language->get('text_heading_title_subscription'));

            $url = '';

            if (isset($this->request->get['page']))
            {
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
                'href' => $this->url->link('extension/payment/razorpay/subscriptions', $url, true),
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_heading_title_subscription'),
                'href' => $this->url->link('extension/payment/razorpay/info', 'subscription_id=' . $subscription_id . $url, true),
            );
            $data['subscription_details'] = $recurring_info;

            $subscriptionInvoice = $this->api->invoice->all(['subscription_id' => $subscription_id])->toArray();
            $data["items"] = $subscriptionInvoice["items"];

            if ($recurring_info["status"] == "active")
            {
                $data['pauseurl'] = $this->url->link('extension/payment/razorpay/pause', 'subscription_id=' . $subscription_id, true);
            }
            else if ($recurring_info["status"] == "paused")
            {
                $data['resumeurl'] = $this->url->link('extension/payment/razorpay/resume', 'subscription_id=' . $subscription_id, true);
            }

            $data['cancelurl'] = $this->url->link('extension/payment/razorpay/cancel', 'subscription_id=' . $subscription_id, true);

            $data["plan_data"] = $this->model_extension_payment_razorpay->getProductBasedPlans($recurring_info["product_id"]);
            $data["updateUrl"] = $this->url->link('extension/payment/razorpay/update');


            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            return $this->response->setOutput($this->load->view('extension/payment/razorpay_subscription/razorpay_subscription_info', $data));
        }
        else
        {
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
                'href' => $this->url->link('extension/payment/razorpay/subscriptions', '', true)
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_heading_title_subscription'),
                'href' => $this->url->link('extension/payment/razorpay/subscriptions/info', 'subscription_id=' . $subscription_id, true)
            );

            $data['continue'] = $this->url->link('extension/payment/razorpay/subscriptions', '', true);

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            return $this->response->setOutput($this->load->view('error/not_found', $data));
        }
    }

    /**
     * Resume subscription
     */
    public function resume()
    {
        if (!$this->customer->isLogged())
        {
            $this->session->data['redirect'] = $this->url->link('extension/payment/razorpay/subscriptions', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }
        $this->load->language('extension/payment/razorpay');

        if (!empty($this->request->get['subscription_id']))
        {
            $subscription_id = $this->request->get['subscription_id'];
        }
        else
        {
            $subscription_id = 0;
        }

        try
        {
            $subscriptionData = $this->api->subscription->fetch($subscription_id)->resume(array('pause_at' => 'now'));
            $this->load->model('extension/payment/razorpay');

            $this->model_extension_payment_razorpay->updateSubscriptionStatus($this->request->get['subscription_id'], $subscriptionData->status);

            $subscriptionData = $this->model_extension_payment_razorpay->getSubscriptionById($subscription_id);
            $this->model_extension_payment_razorpay->updateOCRecurringStatus($subscriptionData['order_id'], 1);

            $this->session->data['success'] = $this->language->get('subscription_resumed_message');

            return $this->response->redirect($this->url->link('extension/payment/razorpay/info', 'subscription_id=' . $subscription_id, true));
        }
        catch (\Razorpay\Api\Errors\Error $e)
        {
            $this->log->write($e->getMessage());
            $this->session->data['error'] = ucfirst($e->getMessage());

            return  $this->response->redirect($this->url->link('extension/payment/razorpay/info', 'subscription_id=' . $this->request->get['subscription_id'], true));
        }
    }

    /**
     * Pause subscription
     */
    public function pause()
    {
        if (!$this->customer->isLogged())
        {
            $this->session->data['redirect'] = $this->url->link('extension/payment/razorpay/subscriptions', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }
        $this->load->language('extension/payment/razorpay');

        if (!empty($this->request->get['subscription_id']))
        {
            $subscription_id = $this->request->get['subscription_id'];
        }
        else
        {
            $subscription_id = 0;
        }

        try
        {
            $subscriptionData = $this->api->subscription->fetch($subscription_id)->pause(array('pause_at' => 'now'));
            $this->load->model('extension/payment/razorpay');

            $this->model_extension_payment_razorpay->updateSubscriptionStatus($subscription_id, $subscriptionData->status);

            $subscriptionData = $this->model_extension_payment_razorpay->getSubscriptionById($subscription_id);
            $this->model_extension_payment_razorpay->updateOCRecurringStatus($subscriptionData['order_id'], 2);

            $this->session->data['success'] = $this->language->get('subscription_paused_message');

            return $this->response->redirect($this->url->link('extension/payment/razorpay/info', 'subscription_id=' . $subscription_id, true));

        }
        catch (\Razorpay\Api\Errors\Error $e)
        {
            $this->log->write($e->getMessage());
            $this->session->data['error'] = ucfirst($e->getMessage());
            return  $this->response->redirect($this->url->link('extension/payment/razorpay/info', 'subscription_id=' . $this->request->get['subscription_id'], true));
        }
    }

    /**
     * Cancel Subscription
     */
    public function cancel()
    {
        if (!$this->customer->isLogged())
        {
            $this->session->data['redirect'] = $this->url->link('extension/payment/razorpay/subscriptions', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }
        $this->load->language('extension/payment/razorpay');

        if (!empty($this->request->get['subscription_id']))
        {
            $subscription_id = $this->request->get['subscription_id'];
        }
        else
        {
            $subscription_id = 0;
        }
        try
        {
            $subscriptionData = $this->api->subscription->fetch($subscription_id)->cancel(array('cancel_at_cycle_end'=>0));
            $this->load->model('extension/payment/razorpay');

            $this->model_extension_payment_razorpay->updateSubscriptionStatus($subscription_id,$subscriptionData->status, "user" );

            $subscriptionData = $this->model_extension_payment_razorpay->getSubscriptionById($subscription_id);
            $this->model_extension_payment_razorpay->updateOCRecurringStatus($subscriptionData['order_id'], 3);

            $this->session->data['success'] = $this->language->get('subscription_cancelled_message');

            return $this->response->redirect($this->url->link('extension/payment/razorpay/info', 'subscription_id=' . $subscription_id, true));
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $this->log->write($e->getMessage());
            $this->session->data['error'] = ucfirst($e->getMessage());

            return  $this->response->redirect($this->url->link('extension/payment/razorpay/info', 'subscription_id=' . $this->request->get['subscription_id'], true));
        }
    }

    /**
     * Update subscription
     */
    public function update()
    {
        try
        {
            $postData = $this->request->post;

            $this->load->language('extension/payment/razorpay');
            $this->load->model('extension/payment/razorpay');
            $planData = $this->model_extension_payment_razorpay->fetchPlanByEntityId($postData["plan_entity_id"]);

            $planUpdateData['plan_id'] = $planData['plan_id'];

            if($postData['qty'])
            {
                $planUpdateData['quantity'] = $postData['qty'];
            }

            $this->api->subscription->fetch($postData["subscriptionId"])->update($planUpdateData)->toArray();

            //Update plan in razorpay subscription table
            $this->model_extension_payment_razorpay->updateSubscriptionPlan($postData);

            $this->session->data['success'] = $this->language->get('subscription_updated_message');

            return $this->response->redirect($this->url->link('extension/payment/razorpay/info', 'subscription_id=' . $postData['subscriptionId'], true));

        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $this->log->write($e->getMessage());
            $this->session->data['error'] = ucfirst($e->getMessage());
            return  $this->response->redirect($this->url->link('extension/payment/razorpay/info', 'subscription_id=' . $postData['subscriptionId'], true));
        }
    }

    /**
     * Handling subscription.paused, subscription.resumed, subscription.cancelled events
     * @param array $data Webook Data
     */
    protected function updateOcSubscriptionStatus($data)
    {
        $subscriptionId = $data['payload']['subscription']['entity']['id'];

        if (empty($subscriptionId) === false)
        {
            $merchant_order_id = $data['payload']['subscription']['entity']['notes']['merchant_order_id'];

            if(isset($merchant_order_id) === true)
            {
                switch ($data['event'])
                {
                    case 'subscription.paused':
                        $status = 'paused';
                        $oc_status = 2;
                        break;

                    case 'subscription.resumed':
                        $status = 'active';
                        $oc_status = 1;
                        break;

                    case 'subscription.cancelled':
                        $status = 'cancelled';
                        $oc_status = 3;
                        break;
                }

                $this->load->model('extension/payment/razorpay');
                $rzpSubscription = $this->model_extension_payment_razorpay->getSubscriptionById($subscriptionId);

                if($rzpSubscription['status'] != $status)
                {
                    $this->model_extension_payment_razorpay->updateSubscriptionStatus($subscriptionId, $status, "Webhook" );
                    $this->model_extension_payment_razorpay->updateOCRecurringStatus($merchant_order_id, $oc_status);
                    $this->log->write("Subscription ".$status." webhook event processed for Opencart OrderID (:" . $merchant_order_id . ")");
                }

                return;
            }
        }
    }

    /**
     * Handling subscription.charged event
     * @param array $data Webook Data
     */
    protected function processSubscriptionCharged($data)
    {
        $paymentId = $data['payload']['payment']['entity']['id'];
        $subscriptionId = $data['payload']['subscription']['entity']['id'];
        $merchant_order_id = $data['payload']['subscription']['entity']['notes']['merchant_order_id'];
        $webhookSource = $data['payload']['subscription']['entity']['source'];
        $amount = number_format($data['payload']['payment']['entity']['amount'] / 100, 4, ".", "");

        $this->load->model('extension/payment/razorpay');

        // Process only if its from opencart subscription source
        if ($webhookSource == "opencart-subscription")
        {
            $subscription = $this->api->subscription->fetch($subscriptionId)->toArray();
            $rzpSubscription = $this->model_extension_payment_razorpay->getSubscriptionById($subscriptionId);

            if ($subscription['paid_count'] == 1)
            {
                if (in_array($rzpSubscription['status'], ['created', 'authenticated']) and
                    $rzpSubscription['paid_count'] == 0)
                {
                    $this->model_extension_payment_razorpay->updateSubscription($subscription, $subscriptionId);
                    $this->model_extension_payment_razorpay->updateOCRecurringStatus($merchant_order_id, 1);

                    $this->model_checkout_order->addOrderHistory($merchant_order_id, $this->config->get('payment_razorpay_order_status_id'), trim("Subscription charged Successfully. Razorpay Payment Id:" . $paymentId));
                }

                return;
            }
            else
            {
                $this->log->write("Subscription charged webhook event initiated for Opencart OrderID (:" . $merchant_order_id . ")");

                // Creating OC Recurring Transaction
                $ocRecurringData = $this->model_extension_payment_razorpay->getOCRecurringStatus($merchant_order_id);
                $this->model_extension_payment_razorpay->addOCRecurringTransaction($ocRecurringData['order_recurring_id'], $subscriptionId, $amount, "success");

                // Update RZP Subscription and OC subscription
                $this->model_extension_payment_razorpay->updateSubscription($subscription, $subscriptionId);
                $this->model_extension_payment_razorpay->updateOCRecurringStatus($merchant_order_id, 1);

                $this->model_checkout_order->addOrderHistory($merchant_order_id, $this->config->get('payment_razorpay_order_status_id'), trim("Subscription charged Successfully. Razorpay Payment Id:" . $paymentId));
                $this->log->write("Subscription charged webhook event finished for Opencart OrderID (:" . $merchant_order_id . ")");

                return;
            }
        }
    }

    /**
     * executing payment.authorized and order.paid webhook event using cron
     */
    public function rzpWebhookCron()
    {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/razorpay');

        $webhookEvents = $this->model_extension_payment_razorpay->getWebhookEvents(self::WEBHOOK_WAIT_TIME);
        foreach ($webhookEvents as $row)
        {
            $events = json_decode($row['rzp_webhook_data']);
            $rzpOrderId = $row['rzp_order_id'];
            foreach ($events as $event)
            {
                $event = (array) $event;
                switch ($event['event'])
                {
                    case self::PAYMENT_AUTHORIZED:
                        $this->paymentAuthorized($event);
                        break;
                    case self::ORDER_PAID:
                        $this->orderPaid($event);
                        break;
                    default:
                        return;
                }
                $this->model_extension_payment_razorpay->updateOrderForWebhook($event['opencart_order_id'], $rzpOrderId, 2);
            }
        }
    }
}
