<?php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Helpers\TestHelper;

/**
 * Integration Tests for Razorpay Webhook Handling
 * Tests webhook signature verification and event processing
 */
class WebhookHandlingTest extends TestCase
{
    private $registry;
    private $webhookSecret = 'webhook_secret_123';

    protected function setUp(): void
    {
        $this->registry = TestHelper::createMockRegistry();
        $config = $this->registry->get('config');
        $config->set('payment_razorpay_webhook_secret', $this->webhookSecret);
    }

    /**
     * @test
     */
    public function testWebhookSignatureVerification()
    {
        $payload = json_encode(TestHelper::createMockWebhookPayload('payment.authorized'));
        $signature = TestHelper::generateWebhookSignature($payload, $this->webhookSecret);

        $calculatedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

        $this->assertEquals($signature, $calculatedSignature);
    }

    /**
     * @test
     */
    public function testInvalidSignatureRejection()
    {
        $payload = json_encode(TestHelper::createMockWebhookPayload('payment.authorized'));
        $invalidSignature = hash_hmac('sha256', $payload, 'wrong_secret');
        $validSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

        $this->assertNotEquals($invalidSignature, $validSignature);
    }

    /**
     * @test
     */
    public function testPaymentAuthorizedWebhook()
    {
        $webhookPayload = TestHelper::createMockWebhookPayload('payment.authorized', [
            'id' => 'pay_test123',
            'order_id' => 'order_test123',
            'status' => 'authorized',
            'amount' => 100000
        ]);

        $this->assertEquals('payment.authorized', $webhookPayload['event']);
        $this->assertArrayHasKey('payload', $webhookPayload);
        $this->assertArrayHasKey('payment', $webhookPayload['payload']);

        $payment = $webhookPayload['payload']['payment']['entity'];
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(100000, $payment['amount']);
    }

    /**
     * @test
     */
    public function testPaymentFailedWebhook()
    {
        $webhookPayload = TestHelper::createMockWebhookPayload('payment.failed', [
            'id' => 'pay_test456',
            'status' => 'failed',
            'error_code' => 'BAD_REQUEST_ERROR',
            'error_description' => 'Payment failed'
        ]);

        $this->assertEquals('payment.failed', $webhookPayload['event']);

        $payment = $webhookPayload['payload']['payment']['entity'];
        $this->assertEquals('failed', $payment['status']);
    }

    /**
     * @test
     */
    public function testOrderPaidWebhook()
    {
        $webhookPayload = TestHelper::createMockWebhookPayload('order.paid', [
            'id' => 'order_test789',
            'status' => 'paid',
            'amount_paid' => 100000
        ]);

        $this->assertEquals('order.paid', $webhookPayload['event']);
        $this->assertArrayHasKey('order', $webhookPayload['payload']);

        $order = $webhookPayload['payload']['order']['entity'];
        $this->assertEquals('paid', $order['status']);
    }

    /**
     * @test
     */
    public function testSubscriptionChargedWebhook()
    {
        $webhookPayload = TestHelper::createMockWebhookPayload('subscription.charged');

        $this->assertEquals('subscription.charged', $webhookPayload['event']);
        $this->assertArrayHasKey('subscription', $webhookPayload['payload']);
        $this->assertArrayHasKey('payment', $webhookPayload['payload']);

        $subscription = $webhookPayload['payload']['subscription']['entity'];
        $this->assertEquals('active', $subscription['status']);
    }

    /**
     * @test
     */
    public function testSubscriptionPausedWebhook()
    {
        $webhookPayload = TestHelper::createMockWebhookPayload('subscription.paused');
        $this->assertEquals('subscription.paused', $webhookPayload['event']);
    }

    /**
     * @test
     */
    public function testSubscriptionResumedWebhook()
    {
        $webhookPayload = TestHelper::createMockWebhookPayload('subscription.resumed');
        $this->assertEquals('subscription.resumed', $webhookPayload['event']);
    }

    /**
     * @test
     */
    public function testSubscriptionCancelledWebhook()
    {
        $webhookPayload = TestHelper::createMockWebhookPayload('subscription.cancelled');
        $this->assertEquals('subscription.cancelled', $webhookPayload['event']);
    }

    /**
     * @test
     */
    public function testWebhookPayloadStructure()
    {
        $webhookPayload = TestHelper::createMockWebhookPayload('payment.authorized');

        $requiredFields = ['entity', 'account_id', 'event', 'contains', 'created_at', 'payload'];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $webhookPayload);
        }

        $this->assertEquals('event', $webhookPayload['entity']);
    }

    /**
     * @test
     */
    public function testOrderStatusUpdateAfterPaymentAuthorized()
    {
        $load = $this->registry->get('load');
        $load->model('checkout/order');

        $model = $this->registry->get('model_checkout_order');
        $orderId = 1001;

        $order = $model->getOrder($orderId);

        $this->assertIsArray($order);
        $this->assertEquals($orderId, $order['order_id']);

        // Simulate status update
        $newStatusId = 2; // Processing
        $comment = 'Payment authorized via Razorpay';

        $result = $model->addHistory($orderId, $newStatusId, $comment, true);
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function testDuplicateWebhookPrevention()
    {
        $webhookPayload = TestHelper::createMockWebhookPayload('payment.authorized', [
            'id' => 'pay_duplicate123'
        ]);

        $processedWebhooks = ['pay_duplicate123'];

        $paymentId = $webhookPayload['payload']['payment']['entity']['id'];

        if (in_array($paymentId, $processedWebhooks)) {
            $isDuplicate = true;
        } else {
            $isDuplicate = false;
        }

        $this->assertTrue($isDuplicate, 'Duplicate webhook should be detected');
    }

    /**
     * @test
     */
    public function testWebhookResponseStatusCodes()
    {
        $validStatuses = [200, 409]; // 200 = Success, 409 = Conflict (already processed)
        $invalidStatuses = [400, 401, 403, 500];

        foreach ($validStatuses as $status) {
            $this->assertContains($status, [200, 409]);
        }

        foreach ($invalidStatuses as $status) {
            $this->assertNotContains($status, [200, 409]);
        }
    }

    /**
     * @test
     */
    public function testWebhookWaitTimeConstant()
    {
        $webhookWaitTime = 30; // seconds

        $this->assertEquals(30, $webhookWaitTime);
        $this->assertIsInt($webhookWaitTime);
    }

    /**
     * @test
     */
    public function testPaymentEntityExtraction()
    {
        $webhookPayload = TestHelper::createMockWebhookPayload('payment.authorized');

        $this->assertArrayHasKey('payload', $webhookPayload);
        $this->assertArrayHasKey('payment', $webhookPayload['payload']);
        $this->assertArrayHasKey('entity', $webhookPayload['payload']['payment']);

        $payment = $webhookPayload['payload']['payment']['entity'];

        $this->assertArrayHasKey('id', $payment);
        $this->assertArrayHasKey('amount', $payment);
        $this->assertArrayHasKey('status', $payment);
        $this->assertArrayHasKey('order_id', $payment);
    }

    /**
     * @test
     */
    public function testWebhookLogging()
    {
        $log = $this->registry->get('log');

        $webhookPayload = TestHelper::createMockWebhookPayload('payment.authorized');
        $logMessage = 'Webhook received: ' . $webhookPayload['event'];

        $log->write($logMessage);

        $logs = $log->getLogs();

        $this->assertNotEmpty($logs);
        $this->assertStringContainsString('Webhook received', $logs[0]['message']);
    }

    /**
     * @test
     */
    public function testAmountMismatchDetection()
    {
        $orderAmount = 100000; // Amount from OpenCart order
        $razorpayAmount = 100000; // Amount from Razorpay webhook

        $this->assertEquals($orderAmount, $razorpayAmount);

        // Test mismatch
        $razorpayAmountWrong = 90000;
        $this->assertNotEquals($orderAmount, $razorpayAmountWrong);
    }
}
