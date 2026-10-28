<?php
namespace Tests\Helpers;

use Tests\Mocks\MockRegistry;
use Tests\Mocks\MockConfig;
use Tests\Mocks\MockSession;
use Tests\Mocks\MockDB;
use Tests\Mocks\MockLanguage;
use Tests\Mocks\MockDocument;
use Tests\Mocks\MockURL;
use Tests\Mocks\MockRequest;
use Tests\Mocks\MockResponse;
use Tests\Mocks\MockCart;
use Tests\Mocks\MockCustomer;
use Tests\Mocks\MockLog;
use Tests\Mocks\MockLoad;

/**
 * Test Helper Class
 * Provides utility methods for setting up tests
 */
class TestHelper
{
    /**
     * Create a REAL Opencart\System\Engine\Registry populated with mock services.
     * Use this when instantiating the real extension classes under test.
     */
    public static function createEngineRegistry(): \Opencart\System\Engine\Registry
    {
        $registry = new \Opencart\System\Engine\Registry();

        $config   = new MockConfig();
        $session  = new MockSession();
        $db       = new MockDB();
        $language = new MockLanguage();
        $document = new MockDocument();
        $url      = new MockURL();
        $request  = new MockRequest();
        $response = new MockResponse();
        $cart     = new MockCart();
        $customer = new MockCustomer();
        $log      = new MockLog();

        $config->set('payment_razorpay_key_id',            getenv('RAZORPAY_KEY_ID')      ?: 'rzp_test_DUMMY_KEY_FOR_TESTING');
        $config->set('payment_razorpay_key_secret',        getenv('RAZORPAY_KEY_SECRET')   ?: 'dummy_secret_for_testing_only');
        $config->set('payment_razorpay_status',            1);
        $config->set('payment_razorpay_order_status_id',   2);
        $config->set('payment_razorpay_payment_action',    'authorize');
        $config->set('payment_razorpay_subscription_status', 1);
        $config->set('payment_razorpay_webhook_secret',    getenv('RAZORPAY_WEBHOOK_SECRET') ?: 'dummy_webhook_secret_for_testing');
        $config->set('payment_razorpay_sort_order',        1);

        $registry->set('config',   $config);
        $registry->set('session',  $session);
        $registry->set('db',       $db);
        $registry->set('language', $language);
        $registry->set('document', $document);
        $registry->set('url',      $url);
        $registry->set('request',  $request);
        $registry->set('response', $response);
        $registry->set('cart',     $cart);
        $registry->set('customer', $customer);
        $registry->set('log',      $log);

        return $registry;
    }

    /**
     * Create a mock OpenCart registry with all dependencies
     */
    public static function createMockRegistry(): MockRegistry
    {
        $registry = new MockRegistry();

        // Create and register all mock components
        $config = new MockConfig();
        $session = new MockSession();
        $db = new MockDB();
        $language = new MockLanguage();
        $document = new MockDocument();
        $url = new MockURL();
        $request = new MockRequest();
        $response = new MockResponse();
        $cart = new MockCart();
        $customer = new MockCustomer();
        $log = new MockLog();
        $load = new MockLoad($registry);

        // Set default Razorpay config from environment variables
        // Use dummy values for tests if environment variables are not set
        $config->set('payment_razorpay_key_id', getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_DUMMY_KEY_FOR_TESTING');
        $config->set('payment_razorpay_key_secret', getenv('RAZORPAY_KEY_SECRET') ?: 'dummy_secret_for_testing_only');
        $config->set('payment_razorpay_status', 1);
        $config->set('payment_razorpay_order_status_id', 2);
        $config->set('payment_razorpay_payment_action', 'authorize');
        $config->set('payment_razorpay_subscription_status', 1);
        $config->set('payment_razorpay_webhook_secret', getenv('RAZORPAY_WEBHOOK_SECRET') ?: 'dummy_webhook_secret_for_testing');

        // Register all components
        $registry->set('config', $config);
        $registry->set('session', $session);
        $registry->set('db', $db);
        $registry->set('language', $language);
        $registry->set('document', $document);
        $registry->set('url', $url);
        $registry->set('request', $request);
        $registry->set('response', $response);
        $registry->set('cart', $cart);
        $registry->set('customer', $customer);
        $registry->set('log', $log);
        $registry->set('load', $load);

        return $registry;
    }

    /**
     * Generate a mock Razorpay order response
     */
    public static function createMockRazorpayOrder(array $override = []): array
    {
        $defaults = [
            'id' => 'order_' . uniqid(),
            'entity' => 'order',
            'amount' => 100000, // in paise
            'amount_paid' => 0,
            'amount_due' => 100000,
            'currency' => 'INR',
            'receipt' => 'receipt_' . time(),
            'status' => 'created',
            'attempts' => 0,
            'notes' => [],
            'created_at' => time()
        ];

        return array_merge($defaults, $override);
    }

    /**
     * Generate a mock Razorpay payment response
     */
    public static function createMockRazorpayPayment(array $override = []): array
    {
        $defaults = [
            'id' => 'pay_' . uniqid(),
            'entity' => 'payment',
            'amount' => 100000,
            'currency' => 'INR',
            'status' => 'captured',
            'order_id' => 'order_' . uniqid(),
            'method' => 'card',
            'captured' => true,
            'email' => 'test@example.com',
            'contact' => '9876543210',
            'created_at' => time()
        ];

        return array_merge($defaults, $override);
    }

    /**
     * Generate a mock Razorpay subscription response
     */
    public static function createMockRazorpaySubscription(array $override = []): array
    {
        $defaults = [
            'id' => 'sub_' . uniqid(),
            'entity' => 'subscription',
            'plan_id' => 'plan_' . uniqid(),
            'customer_id' => 'cust_' . uniqid(),
            'status' => 'active',
            'quantity' => 1,
            'total_count' => 12,
            'paid_count' => 0,
            'remaining_count' => 12,
            'current_start' => time(),
            'current_end' => time() + (30 * 24 * 60 * 60),
            'created_at' => time()
        ];

        return array_merge($defaults, $override);
    }

    /**
     * Generate a mock webhook payload
     */
    public static function createMockWebhookPayload(string $event, array $payloadOverride = []): array
    {
        $defaults = [
            'entity' => 'event',
            'account_id' => 'acc_' . uniqid(),
            'event' => $event,
            'contains' => ['payment'],
            'created_at' => time()
        ];

        switch ($event) {
            case 'payment.authorized':
            case 'payment.captured':
                $defaults['payload'] = [
                    'payment' => [
                        'entity' => self::createMockRazorpayPayment($payloadOverride)
                    ]
                ];
                break;

            case 'payment.failed':
                $defaults['payload'] = [
                    'payment' => [
                        'entity' => self::createMockRazorpayPayment(
                            array_merge(['status' => 'failed'], $payloadOverride)
                        )
                    ]
                ];
                break;

            case 'order.paid':
                $defaults['payload'] = [
                    'order' => [
                        'entity' => self::createMockRazorpayOrder(
                            array_merge(['status' => 'paid'], $payloadOverride)
                        )
                    ]
                ];
                break;

            case 'subscription.charged':
                $defaults['payload'] = [
                    'subscription' => [
                        'entity' => self::createMockRazorpaySubscription($payloadOverride)
                    ],
                    'payment' => [
                        'entity' => self::createMockRazorpayPayment()
                    ]
                ];
                break;
        }

        return $defaults;
    }

    /**
     * Generate Razorpay webhook signature
     */
    public static function generateWebhookSignature(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Create a mock OpenCart order
     */
    public static function createMockOrder(array $override = []): array
    {
        $defaults = [
            'order_id' => 1001,
            'invoice_no' => 0,
            'invoice_prefix' => 'INV-2024-00',
            'store_id' => 0,
            'store_name' => 'Test Store',
            'store_url' => 'http://localhost/',
            'customer_id' => 1,
            'customer_group_id' => 1,
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
            'telephone' => '9876543210',
            'currency_code' => 'INR',
            'currency_value' => 1.0,
            'payment_method' => 'Razorpay',
            'payment_code' => 'razorpay',
            'total' => 1000.00,
            'order_status_id' => 1,
            'date_added' => date('Y-m-d H:i:s'),
            'date_modified' => date('Y-m-d H:i:s')
        ];

        return array_merge($defaults, $override);
    }

    /**
     * Assert that a specific query was executed
     */
    public static function assertQueryContains(MockDB $db, string $needle): bool
    {
        $queries = $db->getQueries();
        foreach ($queries as $query) {
            if (strpos($query, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get test card details for payment testing
     */
    public static function getTestCards(): array
    {
        return [
            'success' => [
                'number' => '4111111111111111',
                'cvv' => '123',
                'expiry_month' => '12',
                'expiry_year' => '2025',
                'name' => 'Test Card'
            ],
            'failure' => [
                'number' => '4000000000000002',
                'cvv' => '123',
                'expiry_month' => '12',
                'expiry_year' => '2025',
                'name' => 'Failed Card'
            ]
        ];
    }
}
