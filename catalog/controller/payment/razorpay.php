<?php

require_once __DIR__.'/../razorpay-sdk/Razorpay.php';
use Razorpay\Api\Api;

class ControllerPaymentRazorpay extends Controller
{
    protected function index()
    {
        $data['button_confirm'] = $this->language->get('button_confirm');
    
        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        // Orders API with payment autocapture
        $api = new Api($this->config->get('razorpay_key_id'), $this->config->get('razorpay_key_secret'));
        $data = $this->get_order_creation_data($this->session->data['order_id']);   
        $razorpay_order = $api->order->create($data);
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
        $data['return_url'] = $this->url->link('payment/razorpay/callback', '', 'SSL');
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
                    $this->model_checkout_order->addOrderHistory($merchant_order_id, $this->config->get('razorpay_order_status_id'), 'Payment Successful. Razorpay Payment Id:'.$razorpay_payment_id);
                } else {
                    $this->model_checkout_order->addOrderHistory($merchant_order_id, $this->config->get('razorpay_order_status_id'), 'Payment Successful. Razorpay Payment Id:'.$razorpay_payment_id);
                }
                echo '<html>'."\n";
                echo '<head>'."\n";
                echo '  <meta http-equiv="Refresh" content="0; url='.$this->url->link('checkout/success').'">'."\n";
                echo '</head>'."\n";
                echo '<body>'."\n";
                echo '  <p>Please follow <a href="'.$this->url->link('checkout/success').'">link</a>!</p>'."\n";
                echo '</body>'."\n";
                echo '</html>'."\n";
                exit();
            } else {
                $this->model_checkout_order->addOrderHistory($this->request->request['merchant_order_id'], 10, $error.' Payment Failed! Check Razorpay dashboard for details of Payment Id:'.$razorpay_payment_id);
                echo '<html>'."\n";
                echo '<head>'."\n";
                echo '  <meta http-equiv="Refresh" content="0; url='.$this->url->link('checkout/failure').'">'."\n";
                echo '</head>'."\n";
                echo '<body>'."\n";
                echo '  <p>Please follow <a href="'.$this->url->link('checkout/failure').'">link</a>!</p>'."\n";
                echo '</body>'."\n";
                echo '</html>'."\n";
                exit();
            }
            
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
