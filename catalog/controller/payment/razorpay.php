<?php

class ControllerPaymentRazorpay extends Controller
{
    public function index()
    {
        $data['button_confirm'] = $this->language->get('button_confirm');

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

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

        if (file_exists(DIR_TEMPLATE.$this->config->get('config_template').'/template/payment/razorpay.tpl')) {
            return $this->load->view($this->config->get('config_template').'/template/payment/razorpay.tpl', $data);
        } else {
            return $this->load->view('default/template/payment/razorpay.tpl', $data);
        }
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
        $this->load->model('checkout/order');
        if (isset($this->request->request['razorpay_payment_id']) and isset($this->request->request['merchant_order_id'])) {
            $razorpay_payment_id = $this->request->request['razorpay_payment_id'];
            $merchant_order_id = $this->request->request['merchant_order_id'];

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
        } else {
            echo 'An error occured. Contact site administrator, please!';
        }
    }
}
