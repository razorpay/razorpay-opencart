<?php

require_once __DIR__.'/razorpay-sdk/Razorpay.php';
use Razorpay\Api\Api;

class ControllerPaymentRazorpay extends Controller
{
    protected function index()
    {
        $this->language->load('payment/razorpay');

        $this->data['button_confirm'] = $this->language->get('button_confirm');
        $this->data['text_title'] = $this->language->get('text_title');
        $this->data['version'] = $this->language->get('version');
        $this->data['text_wait'] = $this->language->get('text_wait');
        $this->data['text_attention'] = $this->language->get('text_attention');

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        // Orders API with payment autocapture
        $api = new Api($this->config->get('razorpay_key_id'), $this->config->get('razorpay_key_secret'));
        $data = $this->get_order_creation_data($this->session->data['order_id']);   
        $razorpay_order = $api->order->create($data);
        $this->session->data['razorpay_order_id'] = $razorpay_order['id'];

        $this->data['key_id'] = $this->config->get('razorpay_key_id');
        $this->data['currency_code'] = $order_info['currency_code'];
        $this->data['total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100;
        $this->data['merchant_order_id'] = $this->session->data['order_id'];
        $this->data['card_holder_name'] = $order_info['payment_firstname'].' '.$order_info['payment_lastname'];
        $this->data['email'] = $order_info['email'];
        $this->data['phone'] = $order_info['telephone'];
        $this->data['name'] = $this->config->get('config_name');
        $this->data['lang'] = $this->session->data['language'];
        $this->data['return_url'] = $this->url->link('payment/razorpay/callback', '', 'SSL');
        $this->data['razorpay_order_id'] = $razorpay_order['id'];

        if (file_exists(DIR_TEMPLATE.$this->config->get('config_template').'/template/payment/razorpay.tpl')) {
            $this->template = $this->config->get('config_template').'/template/payment/razorpay.tpl';
        } else {
            $this->template = 'default/template/payment/razorpay.tpl';
        }

        $this->render();
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
        $request_params = array_merge($_GET, $_POST);
        $this->load->model('checkout/order');
        if (isset($request_params['razorpay_payment_id']) and isset($request_params['merchant_order_id'])) {
            
            $razorpay_payment_id = $request_params['razorpay_payment_id'];
            $merchant_order_id = $request_params['merchant_order_id'];
            $razorpay_order_id = $this->session->data['razorpay_order_id']; // session variable
            $razorpay_signature = $request_params['razorpay_signature'];

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
        } else if (isset($request['error'])) {
            echo '<html>'."\n";
            echo '<head>'."\n";
            echo '</head>'."\n";
            echo '<body>'."\n";
            echo $request['error'] . "\n" . '. <p>Please <a href="'.$this->url->link('checkout/checkout').'">click here to go back</a>!</p>'."\n";
            echo '</body>'."\n";
            echo '</html>'."\n";
            exit();
        }  else {
            echo 'An error occured. Contact site administrator, please!';
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
