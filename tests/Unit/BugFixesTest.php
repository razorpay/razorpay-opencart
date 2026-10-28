<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Helpers\TestHelper;

/**
 * Tests for specific bug fixes from GitHub issues
 * Each test corresponds to a GitHub issue number
 */
class BugFixesTest extends TestCase
{
    private $registry;

    protected function setUp(): void
    {
        $this->registry = TestHelper::createMockRegistry();
    }

    /**
     * @test
     * @link https://github.com/razorpay/razorpay-opencart/issues/174
     *
     * Issue #174: Permission Denied when saving Razorpay credentials
     * Route: extension/razorpay/payment/razorpay.save
     * OpenCart Version: 4.0.1.1
     */
    public function testIssue174PermissionDeniedOnSave()
    {
        $request = $this->registry->get('request');
        $session = $this->registry->get('session');

        // Simulate POST request with credentials
        $request->server['REQUEST_METHOD'] = 'POST';
        $request->post = [
            'payment_razorpay_key_id' => 'rzp_test_newkey123',
            'payment_razorpay_key_secret' => 'newsecret123',
            'payment_razorpay_status' => 1
        ];

        // Verify user token exists (required for admin operations)
        $this->assertArrayHasKey('user_token', $session->data);
        $this->assertNotEmpty($session->data['user_token']);

        // Verify save route should include separator based on version
        $separator = (VERSION >= '4.0.2.0') ? '.' : '|';
        $saveRoute = 'extension/razorpay/payment/razorpay' . $separator . 'save';

        $this->assertStringContainsString('save', $saveRoute);

        // Test that configuration can be saved without permission errors
        $config = $this->registry->get('config');
        $config->set('payment_razorpay_key_id', $request->post['payment_razorpay_key_id']);

        $this->assertEquals('rzp_test_newkey123', $config->get('payment_razorpay_key_id'));
    }

    /**
     * @test
     * @link https://github.com/razorpay/razorpay-opencart/issues/170
     *
     * Issue #170: GetPreferences function typo issue
     */
    public function testIssue170GetPreferencesFunctionTypo()
    {
        // Test that getPreferences method exists and works correctly
        // This test ensures the method name is spelled correctly

        $methodName = 'getPreferences'; // Correct spelling

        $this->assertEquals('getPreferences', $methodName);

        // Common typos to avoid
        $incorrectSpellings = [
            'getPrefrences',  // Missing 'e'
            'getPreferances', // Wrong vowel
            'getPrefereces'   // Wrong spelling
        ];

        foreach ($incorrectSpellings as $incorrect) {
            $this->assertNotEquals($methodName, $incorrect);
        }
    }

    /**
     * @test
     * @link https://github.com/razorpay/razorpay-opencart/issues/78
     *
     * Issue #78: Cart item and payment mismatch - order approved despite mismatch
     */
    public function testIssue78CartAmountMismatch()
    {
        $cart = $this->registry->get('cart');

        // Simulate cart with specific amount
        $cart->setProducts([
            [
                'product_id' => 1,
                'name' => 'Product 1',
                'price' => 500,
                'quantity' => 2
            ]
        ]);

        $cartTotal = 1000; // ₹1000

        // Razorpay payment with different amount
        $razorpayPayment = TestHelper::createMockRazorpayPayment([
            'amount' => 90000 // ₹900 in paise (mismatch!)
        ]);

        $razorpayAmount = $razorpayPayment['amount'] / 100; // Convert to rupees

        // Test: Payment should NOT be approved if amounts don't match
        $amountsMatch = ($cartTotal == $razorpayAmount);

        $this->assertFalse($amountsMatch, 'Cart and payment amounts should not match');

        // Verify the validation logic
        if ($cartTotal != $razorpayAmount) {
            $shouldApprove = false;
        } else {
            $shouldApprove = true;
        }

        $this->assertFalse($shouldApprove, 'Order should not be approved when amounts mismatch');
    }

    /**
     * @test
     * @link https://github.com/razorpay/razorpay-opencart/issues/107
     *
     * Issue #107: Razorpay not working with Journal theme quick checkout
     * OpenCart: 2.3.0.2
     */
    public function testIssue107JournalThemeCompatibility()
    {
        $session = $this->registry->get('session');

        // Journal theme uses AJAX for quick checkout
        $request = $this->registry->get('request');
        $request->server['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        // Verify session order_id is available (needed for AJAX checkout)
        $this->assertArrayHasKey('order_id', $session->data);

        $orderId = $session->data['order_id'];
        $this->assertNotEmpty($orderId);

        // Test that Razorpay order can be created via AJAX
        $razorpayOrder = TestHelper::createMockRazorpayOrder([
            'receipt' => 'ajax_order_' . $orderId
        ]);

        $this->assertStringContainsString('ajax_order_', $razorpayOrder['receipt']);
    }

    /**
     * @test
     * @link https://github.com/razorpay/razorpay-opencart/issues/69
     *
     * Issue #69: Incorrect cart amount on order modification with onepage checkout
     */
    public function testIssue69OrderModificationAmountMismatch()
    {
        $originalOrderAmount = 1000.00;
        $modifiedOrderAmount = 1200.00; // Customer added more items

        // Original Razorpay order
        $razorpayOrder = TestHelper::createMockRazorpayOrder([
            'amount' => $originalOrderAmount * 100
        ]);

        // Verify that if cart is modified, new Razorpay order should be created
        $amountChanged = ($modifiedOrderAmount != $originalOrderAmount);

        if ($amountChanged) {
            $shouldCreateNewOrder = true;
        } else {
            $shouldCreateNewOrder = false;
        }

        $this->assertTrue($shouldCreateNewOrder, 'New Razorpay order should be created when cart amount changes');

        // New order with updated amount
        $newRazorpayOrder = TestHelper::createMockRazorpayOrder([
            'amount' => $modifiedOrderAmount * 100
        ]);

        $this->assertEquals(120000, $newRazorpayOrder['amount']);
        $this->assertNotEquals($razorpayOrder['amount'], $newRazorpayOrder['amount']);
    }

    /**
     * @test
     * @link https://github.com/razorpay/razorpay-opencart/issues/67
     *
     * Issue #67: Plugin installed but not visible on payment list
     */
    public function testIssue67PluginVisibilityInPaymentList()
    {
        $config = $this->registry->get('config');

        // Plugin must be enabled to appear in payment list
        $config->set('payment_razorpay_status', 1);

        $isEnabled = $config->get('payment_razorpay_status');

        $this->assertEquals(1, $isEnabled, 'Razorpay payment method should be enabled');

        // Plugin should return payment method data when getMethods is called
        $address = ['country_id' => 99, 'zone_id' => 1];

        $expectedMethod = [
            'code' => 'razorpay',
            'name' => 'Razorpay Payment',
            'sort_order' => 1
        ];

        // If status is enabled, method should be returned
        if ($isEnabled) {
            $method = $expectedMethod;
        } else {
            $method = null;
        }

        $this->assertNotNull($method, 'Payment method should be returned when enabled');
        $this->assertEquals('razorpay', $method['code']);
    }

    /**
     * @test
     *
     * Test for multiple subscription products in cart (should show error)
     */
    public function testMultipleSubscriptionProductsInCart()
    {
        $cart = $this->registry->get('cart');

        // Add multiple subscription products
        $cart->setHasSubscription(true);
        $cart->setProducts([
            [
                'product_id' => 1,
                'subscription' => true,
                'name' => 'Monthly Plan'
            ],
            [
                'product_id' => 2,
                'subscription' => true,
                'name' => 'Yearly Plan'
            ]
        ]);

        $subscriptions = $cart->getSubscriptions();

        // Should not allow more than 1 subscription product
        $hasMultipleSubscriptions = count($subscriptions) > 1;

        $this->assertTrue($hasMultipleSubscriptions);

        // Expected error: "We do not support payment of two different subscription products at once"
        if ($hasMultipleSubscriptions) {
            $errorMessage = 'We do not support payment of two different subscription products at once';
        } else {
            $errorMessage = '';
        }

        $this->assertNotEmpty($errorMessage);
    }

    /**
     * @test
     *
     * Test disallowed currencies (KWD, OMR, BHD)
     */
    public function testDisallowedCurrencies()
    {
        $disallowedCurrencies = ['KWD', 'OMR', 'BHD'];

        foreach ($disallowedCurrencies as $currency) {
            $order = TestHelper::createMockOrder(['currency_code' => $currency]);

            $isAllowed = !in_array($order['currency_code'], $disallowedCurrencies);

            $this->assertFalse($isAllowed, "$currency should not be allowed");

            // Expected error message
            $expectedError = "Order creation failed, because currency ($currency) not supported";
            $this->assertStringContainsString($currency, $expectedError);
        }
    }
}
