<?php

require_once __DIR__.'/../razorpay-sdk/Razorpay.php';
require_once __DIR__.'/../../log.php';
use Razorpay\Api\Api;
use Razorpay\Api\Errors;

class CreateWebhook
{
    protected $keyId = null;
    protected $keySecret = null;

    protected $webhookId = null;
    protected $webhookUrl = null;
    protected $webhookEnable = '1';
    protected $webhookSecret = null;
    protected $subscriptionStatus = null;

    protected $log = null;

    protected $webhookSupportedEvents = [
        'payment.authorized',
        'payment.failed',
        'order.paid',
        'subscription.paused',
        'subscription.resumed',
        'subscription.cancelled',
        'subscription.charged'
    ];

    protected $webhookEvents = [
        'payment.authorized' => true,
        'payment.failed'     => true,
        'order.paid'         => true,
    ];

    protected $featureEvents = [
        'subscription' => [
            'subscription.paused' => true,
            'subscription.resumed' => true,
            'subscription.cancelled' => true,
            'subscription.charged' =>true
        ]
    ];

    function __construct($keyId, $keySecret, $webhookSecret, $webhookUrl,$subscriptionStatus)
    {
        $this->keyId = $keyId;
        $this->keySecret = $keySecret;
        $this->webhookUrl = $webhookUrl;
        $this->subscriptionStatus = $subscriptionStatus;

        if (empty($webhookSecret) === true)
        {
            $this->webhookSecret = $this->createWebhookSecret();
        }
        else
        {
            $this->webhookSecret = $webhookSecret;
        }

        $this->log = new Log('error.log');
    }

    public function autoCreateWebhook()
    {
        $api = $this->getApiIntance();

        $domain = parse_url($this->webhookUrl, PHP_URL_HOST);
        $domainIp = gethostbyname($domain);

        if (!filter_var($domainIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
        {
            $this->webhookEnable = '0';
            $this->log->write('Cannot enable/disable webhook on $domain or private ip($domainIp).');

            return $this->returnWebhookConfigData();
        }

        try
        {
            $webhookPresent = $this->getExistingWebhook();

            if ($this->subscriptionStatus === '1')
            {
                if ($this->getMerchantFeatureFlagStatus('subscriptions') === false)
                {
                    $this->subscriptionStatus = '0';
                    $this->log->write('Enable subscription in razorpay dashboard');

                    return [
                        'error' => 'error_enable_subscription'
                    ];
                }

                $this->webhookEvents = array_merge(
                    $this->webhookEvents,
                    $this->featureEvents['subscription']
                );
            }

            if (empty($this->webhookId) === false)
            {
                $webhook = $api->webhook->edit(
                    [
                        'url'    => $this->webhookUrl,
                        'events' => $this->webhookEvents,
                        'secret' => $this->webhookSecret,
                        'active' => true,
                    ],
                    $this->webhookId
                );

                $this->log->write('Razorpay Webhook Updated by Admin.');
            }
            else
            {
                $webhook = $api->webhook->create(
                    [
                        'url'    => $this->webhookUrl,
                        'events' => $this->webhookEvents,
                        'secret' => $this->webhookSecret,
                        'active' => true,
                    ]
                );

                $this->log->write('Razorpay Webhook Created by Admin');
            }

            $this->webhookEnable = '1';

            return $this->returnWebhookConfigData();
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $this->webhookEnable = '0';
            $this->log->write($e->getMessage());

            return $this->returnWebhookConfigData();
        }
    }

    protected function getExistingWebhook()
    {
        $api = $this->getApiIntance();

        try
        {
            $skip = 0;
            $count = 10;

            do {
                $webhooks = $api->request->request('GET', 'webhooks?count='.$count.'&skip='.$skip);
                $skip += 10;

                if ($webhooks['count'] > 0)
                {
                    foreach ($webhooks['items'] as $key => $webhook)
                    {
                        if ($webhook['url'] === $this->webhookUrl)
                        {
                            $this->webhookId = $webhook['id'];

                            foreach ($webhook['events'] as $event => $status)
                            {
                                if (($status === true) and
                                    (in_array($event, $this->webhookSupportedEvents) === true))
                                {
                                    $this->webhookEvents[$event] = true;
                                }
                            }

                            return ['id' => $webhook['id']];
                        }
                    }
                }
            }
            while ( $webhooks['count'] >= 10);
        }
        catch(\Razorpay\Api\Errors\Error $e)
        {
            $this->webhookEnable = '0';
            $this->log->write($e->getMessage());

            return ['id' => null];
        }

        return ['id' => null];
    }

    protected function createWebhookSecret()
    {
        $alphanumericString = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-=~!@#$%^&*()_+,./<>?;:[]{}|abcdefghijklmnopqrstuvwxyz';

        return substr(str_shuffle($alphanumericString), 0, 20);
    }

    protected function returnWebhookConfigData()
    {
        return [
            'payment_razorpay_webhook_status'     => $this->webhookEnable,
            'payment_razorpay_webhook_secret'     => $this->webhookSecret,
            'payment_razorpay_webhook_updated_at' => time()
        ];
    }

    protected function getMerchantFeatureFlagStatus($flag)
    {
        $api = $this->getApiIntance();

        $features = $api->request->request('GET', 'accounts/me/features');

        foreach ($features['assigned_features'] as $feature)
        {
            if($feature['name'] === $flag and
                $feature['entity_type'] === 'merchant')
            {
                return true;
            }
        }

        return false;
    }

    protected function getApiIntance()
    {
        return new Api($this->keyId, $this->keySecret);
    }
}
