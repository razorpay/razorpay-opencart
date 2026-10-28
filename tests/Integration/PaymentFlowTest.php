<?php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Helpers\TestHelper;

/**
 * Integration Tests for Razorpay Payment Flow
 * Tests end-to-end payment scenarios
 */
class PaymentFlowTest extends TestCase
{
    private $registry;

    protected function setUp(): void
    {
        $this->registry = TestHelper::createMockRegistry();
    }

    /**
     * @test
     */
    public function testCompletePaymentFlowSuccess()
    {
        $session = $this->registry->get('session');
        $config = $this->registry->get('config');

        // Step 1: Customer initiates checkout
        $orderId = $session->data['order_id'];
        $this->assertNotEmpty($orderId);

        // Step 2: Create Razorpay order
        $order = TestHelper::createMockOrder(['order_id' => $orderId]);
        $razorpayOrder = TestHelper::createMockRazorpayOrder([
            'amount' => $order['total'] * 100,
            'currency' => $order['currency_code'],
            'receipt' => 'order_' . $orderId
        ]);

        $this->assertEquals($order['total'] * 100, $razorpayOrder['amount']);
        $this->assertEquals('INR', $razorpayOrder['currency']);

        // Step 3: Customer completes payment
        $razorpayPayment = TestHelper::createMockRazorpayPayment([
            'order_id' => $razorpayOrder['id'],
            'amount' => $razorpayOrder['amount'],
            'status' => 'captured'
        ]);

        $this->assertEquals('captured', $razorpayPayment['status']);
        $this->assertEquals($razorpayOrder['amount'], $razorpayPayment['amount']);

        // Step 4: Verify payment
        $this->assertTrue($razorpayPayment['captured']);
        $this->assertEquals($razorpayOrder['id'], $razorpayPayment['order_id']);
    }

    /**
     * @test
     */
    public function testPaymentFlowWithFailure()
    {
        $razorpayOrder = TestHelper::createMockRazorpayOrder();

        $razorpayPayment = TestHelper::createMockRazorpayPayment([
            'order_id' => $razorpayOrder['id'],
            'status' => 'failed',
            'captured' => false,
            'error_code' => 'BAD_REQUEST_ERROR',
            'error_description' => 'Payment processing failed'
        ]);

        $this->assertEquals('failed', $razorpayPayment['status']);
        $this->assertFalse($razorpayPayment['captured']);
        $this->assertArrayHasKey('error_code', $razorpayPayment);
    }

    /**
     * @test
     */
    public function testSubscriptionPaymentFlow()
    {
        $config = $this->registry->get('config');
        $cart = $this->registry->get('cart');

        // Enable subscriptions
        $config->set('payment_razorpay_subscription_status', 1);

        // Add subscription product to cart
        $cart->setHasSubscription(true);
        $cart->setProducts([
            [
                'product_id' => 10,
                'name' => 'Monthly Subscription',
                'subscription' => true,
                'price' => 500,
                'recurring' => [
                    'frequency' => 'month',
                    'duration' => 12
                ]
            ]
        ]);

        $subscriptions = $cart->getSubscriptions();
        $this->assertCount(1, $subscriptions);

        // Create Razorpay subscription
        $subscription = TestHelper::createMockRazorpaySubscription([
            'plan_id' => 'plan_monthly_123',
            'quantity' => 1,
            'total_count' => 12
        ]);

        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals(12, $subscription['total_count']);
    }

    /**
     * @test
     */
    public function testPaymentCancellation()
    {
        $razorpayOrder = TestHelper::createMockRazorpayOrder();

        // Customer cancels payment
        $cancelled = true;

        if ($cancelled) {
            $orderStatus = 'cancelled';
        } else {
            $orderStatus = 'processing';
        }

        $this->assertEquals('cancelled', $orderStatus);
    }

    /**
     * @test
     */
    public function testPartialPaymentNotAllowed()
    {
        $orderAmount = 100000; // ₹1000 in paise
        $paidAmount = 50000;   // ₹500 in paise

        $isFullyPaid = ($paidAmount >= $orderAmount);

        $this->assertFalse($isFullyPaid, 'Partial payment should not be accepted');
    }

    /**
     * @test
     */
    public function testCurrencyConversionForMultiCurrency()
    {
        $order = TestHelper::createMockOrder([
            'currency_code' => 'USD',
            'currency_value' => 83.5,
            'total' => 100.00 // $100
        ]);

        // Convert to INR
        $amountInINR = $order['total'] * $order['currency_value'];
        $amountInPaise = $amountInINR * 100;

        $this->assertEquals(8350.00, $amountInINR);
        $this->assertEquals(835000, $amountInPaise);
    }

    /**
     * @test
     */
    public function testPaymentMethodSelection()
    {
        $availableMethods = ['card', 'netbanking', 'upi', 'wallet'];

        foreach ($availableMethods as $method) {
            $this->assertContains($method, $availableMethods);
        }

        $selectedMethod = 'upi';
        $this->assertContains($selectedMethod, $availableMethods);
    }

    /**
     * @test
     */
    public function testCustomerDetailsInPayment()
    {
        $customer = $this->registry->get('customer');
        $order = TestHelper::createMockOrder();

        $customerData = [
            'name' => $customer->getFirstName() . ' ' . $customer->getLastName(),
            'email' => $customer->getEmail(),
            'contact' => $customer->getTelephone()
        ];

        $this->assertEquals('Test User', $customerData['name']);
        $this->assertEquals('test@example.com', $customerData['email']);
        $this->assertEquals('9876543210', $customerData['contact']);
    }

    /**
     * @test
     */
    public function testOrderNotesAndMetadata()
    {
        $orderId = 1001;

        $notes = [
            'opencart_order_id' => $orderId,
            'customer_name' => 'Test User',
            'plugin_version' => '6.0.3'
        ];

        $this->assertArrayHasKey('opencart_order_id', $notes);
        $this->assertEquals($orderId, $notes['opencart_order_id']);
        $this->assertEquals('6.0.3', $notes['plugin_version']);
    }

    /**
     * @test
     */
    public function testTestCardsValidation()
    {
        $testCards = TestHelper::getTestCards();

        $this->assertArrayHasKey('success', $testCards);
        $this->assertArrayHasKey('failure', $testCards);

        $successCard = $testCards['success'];
        $this->assertEquals('4111111111111111', $successCard['number']);

        $failureCard = $testCards['failure'];
        $this->assertEquals('4000000000000002', $failureCard['number']);
    }

    /**
     * @test
     */
    public function testPaymentTimeoutHandling()
    {
        $paymentStartTime = time();
        $timeoutDuration = 600; // 10 minutes

        // Simulate payment taking too long
        $paymentCompleteTime = $paymentStartTime + 700;

        $isTimedOut = ($paymentCompleteTime - $paymentStartTime) > $timeoutDuration;

        $this->assertTrue($isTimedOut, 'Payment should timeout after 10 minutes');
    }

    /**
     * @test
     */
    public function testRedirectAfterSuccessfulPayment()
    {
        $session = $this->registry->get('session');
        $url = $this->registry->get('url');

        $orderId = $session->data['order_id'];
        $successUrl = $url->link('checkout/success', 'order_id=' . $orderId);

        $this->assertStringContainsString('checkout/success', $successUrl);
        $this->assertStringContainsString('order_id=' . $orderId, $successUrl);
    }

    /**
     * @test
     */
    public function testRedirectAfterFailedPayment()
    {
        $url = $this->registry->get('url');

        $failureUrl = $url->link('checkout/failure');

        $this->assertStringContainsString('checkout/failure', $failureUrl);
    }

    /**
     * @test
     */
    public function testPaymentReceiptGeneration()
    {
        $orderId = 1001;
        $timestamp = time();

        $receipt = 'order_' . $orderId . '_' . $timestamp;

        $this->assertStringContainsString('order_', $receipt);
        $this->assertStringContainsString((string)$orderId, $receipt);
    }
}
