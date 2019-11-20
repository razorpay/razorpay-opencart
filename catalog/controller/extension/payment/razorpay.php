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

    public function index()
    {
        $data['button_confirm'] = $this->language->get('button_confirm');
        
        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        // Orders API with payment autocapture
        $api = $this->getApiIntance();
        $order_data = $this->get_order_creation_data($this->session->data['order_id']);   
        $razorpay_order = $api->order->create($order_data);

        $this->session->data['razorpay_order_id'] = $razorpay_order['id'];

        $data['key_id'] = $this->config->get('razorpay_key_id');
        $data['currency_code'] = $order_info['currency_code'];
        $data['total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100;
        $data['merchant_order_id'] = $this->session->data['order_id'];
        $data['card_holder_name'] = $order_info['payment_firstname'].' '.$order_info['payment_lastname'];
        $data['email'] = $order_info['email'];
        $data['phone'] = $order_info['telephone'];
        $data['name'] = $this->config->get('config_name');
        $data['lang'] = $this->session->data['language'];
        $data['return_url'] = $this->url->link('extension/payment/razorpay/callback', '', 'true');
        $data['razorpay_order_id'] = $razorpay_order['id'];

        if (file_exists(DIR_TEMPLATE.$this->config->get('config_template').'/template/extension/payment/razorpay.tpl')) 
        {
            return $this->load->view($this->config->get('config_template').'/template/extension/payment/razorpay.tpl', $data);
        } 
        else 
        {
            return $this->load->view('extension/payment/razorpay.tpl', $data);
        }
    }

    function get_order_creation_data($order_id)
    {
        $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $data = [
            'receipt' => $order_id,
            'amount' => $this->currency->format($order['total'], $order['currency_code'], $order['currency_value'], false) * 100,
            'currency' => $order['currency_code'],
            'payment_capture' => 1
        ];

        return $data;
    }


    public function callback()
    {
        $this->load->model('checkout/order');

        if ($this->request->request['razorpay_payment_id']) 
        {    
            $razorpay_payment_id = $this->request->request['razorpay_payment_id'];
            $merchant_order_id = $this->session->data['order_id'];
            $razorpay_order_id = $this->session->data['razorpay_order_id']; 
            $razorpay_signature = $this->request->request['razorpay_signature'];

            $order_info = $this->model_checkout_order->getOrder($merchant_order_id);
            $amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100;
            
            //validate Rzp signature
            $api = $this->getApiIntance();
            try
            {                
                $attributes = array(
                    'razorpay_order_id' => $razorpay_order_id,
                    'razorpay_payment_id' => $razorpay_payment_id,
                    'razorpay_signature' => $razorpay_signature
                );

                $api->utility->verifyPaymentSignature($attributes);
                
                $this->model_checkout_order->addOrderHistory($merchant_order_id, $this->config->get('razorpay_order_status_id'), 'Payment Successful. Razorpay Payment Id:'.$razorpay_payment_id);                

                $this->response->redirect($this->url->link('checkout/success', '', true));
            }
            catch(Errors\SignatureVerificationError $e)
            {
                $this->model_checkout_order->addOrderHistory($this->request->request['merchant_order_id'], 10, $e->getMessage() .' Payment Failed! Check Razorpay dashboard for details of Payment Id:'.$razorpay_payment_id);
                
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

            echo $message;
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
        $enabled = $this->config->get('razorpay_webhook_status');

        if (($enabled === '1') and
            (empty($data['event']) === false))
        {
            
            if (isset($_SERVER['HTTP_X_RAZORPAY_SIGNATURE']) === true)
            {                
                try
                {
                    $this->validateSignature($post , $_SERVER['HTTP_X_RAZORPAY_SIGNATURE']);       
                }
                catch (Errors\SignatureVerificationError $e)
                {
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

            if($order_info['payment_code'] === 'razorpay')
            {
                if (!$order_info['order_status_id']) 
                {
                    $this->model_checkout_order->addOrderHistory($merchant_order_id, $this->config->get('razorpay_order_status_id'), 'Payment Successful. Razorpay Payment Id:'.$razorpay_payment_id);
                } 
            }
        }
        // Graceful exit since payment is now processed.
        $this->response->addHeader('HTTP/1.1 200 OK');
        $this->response->addHeader('Content-Type: application/json');
    }

    /**
     * Handling order.paid event    
     * @param array $data Webook Data
     */
    protected function paymentFailed(array $data)
    {
        exit;
    }

    /**
     * Handling order.paid event    
     * @param array $data Webook Data
     */
    protected function paymentAuthorized(array $data)
    {
        exit;
    }

    /**
     * @param $payloadRawData
     * @param $actualSignature
     */
    public function validateSignature($payloadRawData, $actualSignature)
    {
        $api = $this->getApiIntance();

        $webhookSecret = $this->config->get('razorpay_webhook_secret');

        if (empty($webhookSecret) === false)
        {
            $api->utility
                 ->verifyWebhookSignature($payloadRawData, $actualSignature, $webhookSecret);
        }

    }

    protected function getApiIntance()
    {
        return new Api($this->config->get('razorpay_key_id'), $this->config->get('razorpay_key_secret'));
    }

}
