<?php

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

        if (file_exists(DIR_TEMPLATE.$this->config->get('config_template').'/template/payment/razorpay.tpl')) {
            $this->template = $this->config->get('config_template').'/template/payment/razorpay.tpl';
        } else {
            $this->template = 'default/template/payment/razorpay.tpl';
        }

        $this->render();
    }

    private function get_curl_handle($payment_id, $amount)
    {
        $url = 'https://api.razorpay.com/v1/payments/'.$payment_id.'/capture';
        $key_id = $this->config->get('razorpay_key_id');
        $key_secret = $this->config->get('razorpay_key_secret');
        $fields_string = "amount=$amount";

        //cURL Request
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, $key_id.':'.$key_secret);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__).'/ca-bundle.crt');

        return $ch;
    }

    public function callback()
    {
        $request_params = array_merge($_GET, $_POST);
        $this->load->model('checkout/order');
        if (isset($request_params['razorpay_payment_id']) and isset($request_params['merchant_order_id'])) {
            $razorpay_payment_id = $request_params['razorpay_payment_id'];
            $merchant_order_id = $request_params['merchant_order_id'];

            $order_info = $this->model_checkout_order->getOrder($merchant_order_id);
            $amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100;

            $success = false;
            $error = '';

            try {
                $ch = $this->get_curl_handle($razorpay_payment_id, $amount);

                //execute post
                $result = curl_exec($ch);
                $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($result === false) {
                    $success = false;
                    $error = 'Curl error: '.curl_error($ch);

                } else {
                    $response_array = json_decode($result, true);

                    //Check success response
                    if ($http_status === 200 and isset($response_array['error']) === false) {
                        $success = true;
                    } else {
                        $success = false;

                        if (!empty($response_array['error']['code'])) {
                            $error = $response_array['error']['code'].':'.$response_array['error']['description'];
                        } else {
                            $error = 'RAZORPAY_ERROR:Invalid Response <br/>'.$result;
                        }
                    }
                }

                    //close connection
                    curl_close($ch);
            } catch (Exception $e) {
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
