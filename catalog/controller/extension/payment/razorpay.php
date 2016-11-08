<?php

require_once __DIR__.'/../../razorpay-sdk/Razorpay.php';
use Razorpay\Api\Api;

class ControllerExtensionPaymentRazorpay extends Controller
{
    public function index()
    {
        $data['button_confirm'] = $this->language->get('button_confirm');
        
        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        // Orders API with payment autocapture
        $api = new Api($this->config->get('razorpay_key_id'), $this->config->get('razorpay_key_secret'));
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
        $data['return_url'] = $this->url->link('payment/razorpay/callback', '', 'true');
        $data['razorpay_order_id'] = $razorpay_order['id'];

        if (file_exists(DIR_TEMPLATE.$this->config->get('config_template').'/template/payment/razorpay.tpl')) {
            return $this->load->view($this->config->get('config_template').'/template/payment/razorpay.tpl', $data);
        } else {
            return $this->load->view('payment/razorpay', $data);
        }
    }

    function get_order_creation_data($order_id)
    {
        $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        
        switch($this->payment_action)
        {
            case 'authorize':
                $data = array(
                  'receipt' => $order_id,
                  'amount' => $this->currency->format($order['total'], $order['currency_code'], $order['currency_value'], false) * 100,
                  'currency' => $order['currency_code'],
                  'payment_capture' => 0
                );    
                break;

            default:
                $data = array(
                  'receipt' => $order_id,
                  'amount' => $this->currency->format($order['total'], $order['currency_code'], $order['currency_value'], false) * 100,
                  'currency' => $order['currency_code'],
                  'payment_capture' => 1
                );
                break;
        }

        return $data;
    }


    public function callback()
    {
        $this->load->model('checkout/order');

        if ($this->request->request['razorpay_payment_id']) {
            
            $razorpay_payment_id = $this->request->request['razorpay_payment_id'];
            $merchant_order_id = $this->request->request['merchant_order_id'];
            $razorpay_order_id = $this->session->data['razorpay_order_id']; 
            $razorpay_signature = $this->request->request['razorpay_signature'];

            $order_info = $this->model_checkout_order->getOrder($merchant_order_id);
            $amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100;

            $key_id = $this->config->get('razorpay_key_id');
            $key_secret = $this->config->get('razorpay_key_secret');

            $api = new Api($key_id, $key_secret);

            $success = false;
            $error = "";
            $captured = false;

            try 
            {
                if ($this->payment_action === 'authorize')
                {   
                    $payment = $api->payment->fetch($razorpay_payment_id);
                }
                else
                {   
                    $signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $key_secret);

                    if (hash_equals($signature , $razorpay_signature))
                    {
                        $captured = true;;
                    }
                }

                //Check success response
                if ($captured)
                {
                    $success = true;
                }

                else{
                    $success = false;

                    $error = "PAYMENT_ERROR = Payment failed";
                }
            }

            catch (Exception $e) 
            {
                $success = false;
                $error = 'OPENCART_ERROR:Request to Razorpay Failed';
            }

            if ($success === true) {
                if (!$order_info['order_status_id']) {
                    $this->model_checkout_order->confirm($merchant_order_id, $this->config->get('razorpay_order_status_id'), 'Payment Successful. Razorpay Payment Id:'.$razorpay_payment_id, true);
                } else {
                    $this->model_checkout_order->update($merchant_order_id, $this->config->get('razorpay_order_status_id'), 'Payment Successful. Razorpay Payment Id:'.$razorpay_payment_id, true);
                }

                echo '<html>'."\n";
                echo '<head>'."\n";
                echo '  <meta http-equiv="Refresh" content="0; url='.$this->url->link('checkout/success').'">'."\n";
                echo '</head>'."\n";
                echo '<body>'."\n";
                echo 'Payment Successful. <p>Please <a href="'.$this->url->link('checkout/success').'">click here to continue</a>!</p>'."\n";
                echo '</body>'."\n";
                echo '</html>'."\n";
                exit();
            } else {
                if (!$order_info['order_status_id']) {
                    $this->model_checkout_order->confirm($merchant_order_id, 10, $error.' Payment Failed! Check Razorpay dashboard for details of Payment Id:'.$razorpay_payment_id, true);
                } else {
                    $this->model_checkout_order->update($merchant_order_id, 10, $error.' Payment Failed! Check Razorpay dashboard for details of Payment Id:'.$razorpay_payment_id, true);
                }

                echo '<html>'."\n";
                echo '<head>'."\n";
                echo '</head>'."\n";
                echo '<body>'."\n";
                echo 'Payment Failed. <p>Please <a href="'.$this->url->link('checkout/checkout').'">click here to continue</a>!</p>'."\n";
                echo '</body>'."\n";
                echo '</html>'."\n";
                exit();
            }
        } else {
            echo 'An error occured. Contact site administrator, please!';
        }
    }
}
