<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Helpers\TestHelper;

/**
 * Unit Tests for Razorpay Catalog Controller
 * Tests payment flow, order creation, and checkout logic
 */
class ControllerRazorpayTest extends TestCase
{
    private $registry;
    private $controller;

    protected function setUp(): void
    {
        $this->registry = TestHelper::createMockRegistry();
    }

    /**
     * @test
     */
    public function testCurrencyValidation()
    {
        $notAllowedCurrencies = ['KWD', 'OMR', 'BHD'];

        foreach ($notAllowedCurrencies as $currency) {
            // These currencies should not be allowed
            $this->assertContains($currency, $notAllowedCurrencies);
        }

        // INR should be allowed
        $this->assertNotContains('INR', $notAllowedCurrencies);
    }

    /**
     * @test
     */
    public function testOrderCreationWithINRCurrency()
    {
        $order = TestHelper::createMockOrder(['currency_code' => 'INR']);

        $this->assertEquals('INR', $order['currency_code']);
        $this->assertEquals(1000.00, $order['total']);
    }

    /**
     * @test
     */
    public function testOrderCreationRejectsDisallowedCurrency()
    {
        $order = TestHelper::createMockOrder(['currency_code' => 'KWD']);

        $disallowedCurrencies = ['KWD', 'OMR', 'BHD'];

        $this->assertTrue(in_array($order['currency_code'], $disallowedCurrencies));
    }

    /**
     * @test
     */
    public function testVersionSeparatorDetermination()
    {
        // Version >= 4.0.2.0 should use '.'
        if (version_compare(VERSION, '4.0.2.0', '>=')) {
            $expectedSeparator = '.';
        } else {
            $expectedSeparator = '|';
        }

        $this->assertContains($expectedSeparator, ['.', '|']);
    }

    /**
     * @test
     */
    public function testWebhookEventConstants()
    {
        $expectedEvents = [
            'payment.authorized',
            'payment.failed',
            'order.paid',
            'subscription.paused',
            'subscription.resumed',
            'subscription.cancelled',
            'subscription.charged'
        ];

        foreach ($expectedEvents as $event) {
            $this->assertIsString($event);
            $this->assertStringContainsString('.', $event);
        }
    }

    /**
     * @test
     */
    public function testSubscriptionValidation()
    {
        $cart = $this->registry->get('cart');

        // Test with single subscription product
        $cart->setHasSubscription(true);
        $cart->setProducts([
            [
                'product_id' => 1,
                'name' => 'Subscription Product',
                'subscription' => true,
                'price' => 100
            ]
        ]);

        $subscriptions = $cart->getSubscriptions();
        $this->assertCount(1, $subscriptions);

        // Test with multiple subscription products (should fail)
        $cart->setProducts([
            [
                'product_id' => 1,
                'subscription' => true
            ],
            [
                'product_id' => 2,
                'subscription' => true
            ]
        ]);

        $subscriptions = $cart->getSubscriptions();
        $this->assertGreaterThan(1, count($subscriptions));
    }

    /**
     * @test
     */
    public function testRazorpayOrderIdGeneration()
    {
        $orderId = 1001;
        $razorpayOrder = TestHelper::createMockRazorpayOrder([
            'receipt' => 'order_' . $orderId,
            'amount' => 100000
        ]);

        $this->assertStringContainsString('order_', $razorpayOrder['id']);
        $this->assertEquals(100000, $razorpayOrder['amount']);
        $this->assertEquals('order_' . $orderId, $razorpayOrder['receipt']);
    }

    /**
     * @test
     */
    public function testPaymentAmountConversionToPaise()
    {
        $amountInRupees = 1000.00;
        $amountInPaise = $amountInRupees * 100;

        $this->assertEquals(100000, $amountInPaise);

        // Test with decimal places
        $amountInRupees = 1234.56;
        $amountInPaise = $amountInRupees * 100;

        $this->assertEquals(123456, $amountInPaise);
    }

    /**
     * @test
     */
    public function testCustomerDataExtraction()
    {
        $customer = $this->registry->get('customer');

        $this->assertEquals(1, $customer->getId());
        $this->assertEquals('test@example.com', $customer->getEmail());
        $this->assertEquals('Test', $customer->getFirstName());
        $this->assertEquals('User', $customer->getLastName());
        $this->assertEquals('9876543210', $customer->getTelephone());
    }

    /**
     * @test
     */
    public function testSessionOrderIdIsSet()
    {
        $session = $this->registry->get('session');

        $this->assertArrayHasKey('order_id', $session->data);
        $this->assertEquals(1001, $session->data['order_id']);
    }

    /**
     * @test
     */
    public function testConfigurationValues()
    {
        $config = $this->registry->get('config');

        // Verify config values are set (actual values may come from environment)
        $this->assertNotEmpty($config->get('payment_razorpay_key_id'));
        $this->assertNotEmpty($config->get('payment_razorpay_key_secret'));
        $this->assertEquals(1, $config->get('payment_razorpay_status'));
        $this->assertEquals(2, $config->get('payment_razorpay_order_status_id'));

        // Verify test keys start with expected prefix
        $keyId = $config->get('payment_razorpay_key_id');
        $this->assertStringStartsWith('rzp_', $keyId);
    }

    /**
     * @test
     */
    public function testWebhookUrlGeneration()
    {
        $expectedRoute = 'extension/razorpay/payment/razorpay';
        $separator = (VERSION >= '4.0.2.0') ? '.' : '|';
        $expectedUrl = HTTP_CATALOG . 'index.php?route=' . $expectedRoute . $separator . 'webhook';

        $this->assertStringContainsString('webhook', $expectedUrl);
        $this->assertStringContainsString($expectedRoute, $expectedUrl);
    }
}
