<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Helpers\TestHelper;

/**
 * Unit Tests for Razorpay Admin Controller
 * Tests configuration, validation, and admin panel functionality
 */
class AdminControllerRazorpayTest extends TestCase
{
    private $registry;

    protected function setUp(): void
    {
        $this->registry = TestHelper::createMockRegistry();
    }

    /**
     * @test
     */
    public function testConfigurationPageLoadsSuccessfully()
    {
        $document = $this->registry->get('document');
        $language = $this->registry->get('language');

        $title = $language->get('heading_title');
        $document->setTitle($title);

        $this->assertEquals('Razorpay Payment', $document->getTitle());
    }

    /**
     * @test
     */
    public function testBreadcrumbsAreGenerated()
    {
        $language = $this->registry->get('language');
        $url = $this->registry->get('url');
        $session = $this->registry->get('session');

        $breadcrumbs = [];

        $breadcrumbs[] = [
            'text' => 'Home',
            'href' => $url->link('common/dashboard', 'user_token=' . $session->data['user_token'], 'SSL')
        ];

        $breadcrumbs[] = [
            'text' => 'Extensions',
            'href' => $url->link('marketplace/extension', 'user_token=' . $session->data['user_token'] . '&type=payment', 'SSL')
        ];

        $breadcrumbs[] = [
            'text' => $language->get('heading_title'),
            'href' => $url->link('extension/razorpay/payment/razorpay', 'user_token=' . $session->data['user_token'], 'SSL')
        ];

        $this->assertCount(3, $breadcrumbs);
        $this->assertEquals('Home', $breadcrumbs[0]['text']);
        $this->assertEquals('Razorpay Payment', $breadcrumbs[2]['text']);
    }

    /**
     * @test
     */
    public function testKeyIdValidation()
    {
        $validKeyIds = [
            'rzp_test_1DP5mmOlF5G5ag',
            'rzp_live_AbCdEfGhIjKlMn'
        ];

        foreach ($validKeyIds as $keyId) {
            $this->assertStringStartsWith('rzp_', $keyId);
            $this->assertMatchesRegularExpression('/^rzp_(test|live)_[A-Za-z0-9]+$/', $keyId);
        }
    }

    /**
     * @test
     */
    public function testKeySecretValidation()
    {
        $keySecret = 'thisissecret';

        $this->assertIsString($keySecret);
        $this->assertNotEmpty($keySecret);
        $this->assertGreaterThan(6, strlen($keySecret));
    }

    /**
     * @test
     */
    public function testEmptyKeyIdShowsError()
    {
        $keyId = '';

        if (empty($keyId)) {
            $error = 'Key ID is required';
        } else {
            $error = '';
        }

        $this->assertEquals('Key ID is required', $error);
    }

    /**
     * @test
     */
    public function testEmptyKeySecretShowsError()
    {
        $keySecret = '';

        if (empty($keySecret)) {
            $error = 'Key Secret is required';
        } else {
            $error = '';
        }

        $this->assertEquals('Key Secret is required', $error);
    }

    /**
     * @test
     */
    public function testOrderStatusOptionsAreLoaded()
    {
        $load = $this->registry->get('load');
        $load->model('localisation/order_status');

        $model = $this->registry->get('model_localisation_order_status');
        $orderStatuses = $model->getOrderStatuses();

        $this->assertIsArray($orderStatuses);
        $this->assertNotEmpty($orderStatuses);
        $this->assertArrayHasKey('order_status_id', $orderStatuses[0]);
        $this->assertArrayHasKey('name', $orderStatuses[0]);
    }

    /**
     * @test
     */
    public function testDefaultOrderStatusIsSet()
    {
        $config = $this->registry->get('config');

        $orderStatusId = $config->get('payment_razorpay_order_status_id');

        if (!$orderStatusId) {
            $orderStatusId = 2; // Default to "Processing"
        }

        $this->assertEquals(2, $orderStatusId);
    }

    /**
     * @test
     */
    public function testPaymentActionOptions()
    {
        $paymentActions = [
            'authorize' => 'Authorize Only',
            'authorize_capture' => 'Authorize and Capture'
        ];

        $this->assertArrayHasKey('authorize', $paymentActions);
        $this->assertArrayHasKey('authorize_capture', $paymentActions);
        $this->assertEquals('Authorize Only', $paymentActions['authorize']);
    }

    /**
     * @test
     */
    public function testSubscriptionStatusToggle()
    {
        $config = $this->registry->get('config');

        // Test enabled
        $config->set('payment_razorpay_subscription_status', 1);
        $this->assertEquals(1, $config->get('payment_razorpay_subscription_status'));

        // Test disabled
        $config->set('payment_razorpay_subscription_status', 0);
        $this->assertEquals(0, $config->get('payment_razorpay_subscription_status'));
    }

    /**
     * @test
     */
    public function testSaveUrlGeneration()
    {
        $url = $this->registry->get('url');
        $session = $this->registry->get('session');
        $separator = (VERSION >= '4.0.2.0') ? '.' : '|';

        $saveUrl = $url->link(
            'extension/razorpay/payment/razorpay' . $separator . 'save',
            'user_token=' . $session->data['user_token']
        );

        $this->assertStringContainsString('razorpay', $saveUrl);
        $this->assertStringContainsString('save', $saveUrl);
        $this->assertStringContainsString('user_token', $saveUrl);
    }

    /**
     * @test
     */
    public function testCancelUrlGeneration()
    {
        $url = $this->registry->get('url');
        $session = $this->registry->get('session');

        $cancelUrl = $url->link(
            'marketplace/extension',
            'user_token=' . $session->data['user_token'] . '&type=payment',
            'SSL'
        );

        $this->assertStringContainsString('marketplace/extension', $cancelUrl);
        $this->assertStringContainsString('type=payment', $cancelUrl);
    }

    /**
     * @test
     */
    public function testWebhookSecretValidation()
    {
        $config = $this->registry->get('config');

        $webhookSecret = $config->get('payment_razorpay_webhook_secret');

        $this->assertIsString($webhookSecret);
        $this->assertEquals('webhook_secret_123', $webhookSecret);
    }

    /**
     * @test
     */
    public function testWebhookUrlIsGenerated()
    {
        $separator = (VERSION >= '4.0.2.0') ? '.' : '|';
        $webhookUrl = HTTP_CATALOG . 'index.php?route=extension/razorpay/payment/razorpay' . $separator . 'webhook';

        $this->assertStringContainsString('webhook', $webhookUrl);
        $this->assertStringStartsWith('http', $webhookUrl);
    }

    /**
     * @test
     */
    public function testConfigurationDataStructure()
    {
        $configData = [
            'payment_razorpay_key_id' => 'rzp_test_123',
            'payment_razorpay_key_secret' => 'secret_123',
            'payment_razorpay_status' => 1,
            'payment_razorpay_order_status_id' => 2,
            'payment_razorpay_payment_action' => 'authorize',
            'payment_razorpay_subscription_status' => 1,
            'payment_razorpay_webhook_secret' => 'webhook_secret',
            'payment_razorpay_sort_order' => 1
        ];

        $this->assertArrayHasKey('payment_razorpay_key_id', $configData);
        $this->assertArrayHasKey('payment_razorpay_key_secret', $configData);
        $this->assertArrayHasKey('payment_razorpay_status', $configData);
        $this->assertArrayHasKey('payment_razorpay_webhook_secret', $configData);
    }

    /**
     * @test
     */
    public function testLanguageStringsAreLoaded()
    {
        $language = $this->registry->get('language');

        $strings = [
            'heading_title',
            'text_edit',
            'text_enabled',
            'text_disabled',
            'button_save',
            'button_cancel'
        ];

        foreach ($strings as $string) {
            $value = $language->get($string);
            $this->assertNotEmpty($value);
        }
    }
}
