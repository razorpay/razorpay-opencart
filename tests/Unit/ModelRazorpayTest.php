<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Opencart\System\Engine\Registry;
use Opencart\Admin\Controller\Extension\Razorpay\Payment\MPDO;
use Tests\Helpers\TestHelper;

// Load the REAL extension model
require_once __DIR__ . '/../../catalog/model/payment/razorpay.php';

/**
 * Unit Tests for the REAL Catalog Model
 * Imports Opencart\Catalog\Model\Extension\Razorpay\Payment\Razorpay directly
 */
class ModelRazorpayTest extends TestCase
{
    private Registry $registry;
    private \Opencart\Catalog\Model\Extension\Razorpay\Payment\Razorpay $model;
    private MPDO $pdo;

    protected function setUp(): void
    {
        $this->registry = TestHelper::createEngineRegistry();

        // Instantiate the REAL model — constructor builds mPDO internally
        $this->model = new \Opencart\Catalog\Model\Extension\Razorpay\Payment\Razorpay(
            $this->registry
        );

        // Grab the mPDO stub that was created inside the model
        $this->pdo = $this->model->rzpPdo;
    }

    // ── Constants ──────────────────────────────────────────────────────────

    /** @test */
    public function recurringStatusConstantsHaveCorrectValues(): void
    {
        $model = \Opencart\Catalog\Model\Extension\Razorpay\Payment\Razorpay::class;

        $this->assertSame(1, $model::RECURRING_ACTIVE);
        $this->assertSame(2, $model::RECURRING_INACTIVE);
        $this->assertSame(3, $model::RECURRING_CANCELLED);
        $this->assertSame(4, $model::RECURRING_SUSPENDED);
        $this->assertSame(5, $model::RECURRING_EXPIRED);
        $this->assertSame(6, $model::RECURRING_PENDING);
    }

    /** @test */
    public function planTypeMappingIsCorrect(): void
    {
        $model = \Opencart\Catalog\Model\Extension\Razorpay\Payment\Razorpay::class;

        $this->assertSame('daily',   $model::PLAN_TYPE['day']);
        $this->assertSame('weekly',  $model::PLAN_TYPE['week']);
        $this->assertSame('monthly', $model::PLAN_TYPE['month']);
        $this->assertSame('yearly',  $model::PLAN_TYPE['year']);
    }

    // ── getMethods ─────────────────────────────────────────────────────────

    /** @test */
    public function getMethodsReturnsCorrectStructure(): void
    {
        $result = $this->model->getMethods([]);

        $this->assertIsArray($result);
        $this->assertSame('razorpay', $result['code']);
        $this->assertArrayHasKey('option', $result);
        $this->assertArrayHasKey('razorpay', $result['option']);
        $this->assertSame('razorpay.razorpay', $result['option']['razorpay']['code']);
        $this->assertArrayHasKey('sort_order', $result);
    }

    /** @test */
    public function getMethodReturnsPaymentMethod(): void
    {
        $result = $this->model->getMethod([]);

        $this->assertIsArray($result);
        $this->assertSame('razorpay', $result['code']);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('sort_order', $result);
    }

    // ── recurringPayments ──────────────────────────────────────────────────

    /** @test */
    public function recurringPaymentsReturnsTrueWhenEnabled(): void
    {
        $this->registry->get('config')->set('payment_razorpay_subscription_status', 1);

        $this->assertTrue($this->model->recurringPayments());
    }

    /** @test */
    public function recurringPaymentsReturnsFalseWhenDisabled(): void
    {
        $this->registry->get('config')->set('payment_razorpay_subscription_status', 0);

        $this->assertFalse($this->model->recurringPayments());
    }

    // ── getSubscriptionById ────────────────────────────────────────────────

    /** @test */
    public function getSubscriptionByIdBuildsCorrectQuery(): void
    {
        $this->pdo->setNextRows([
            ['subscription_id' => 'sub_abc', 'status' => 'active', 'qty' => 1]
        ]);

        $result = $this->model->getSubscriptionById('sub_abc');

        $this->assertSame('sub_abc', $result['subscription_id']);
        $this->assertSame('active',  $result['status']);

        $queries = $this->pdo->getQueries();
        $lastQuery = end($queries);
        $this->assertStringContainsString('razorpay_subscriptions', $lastQuery);
        $this->assertStringContainsString('subscription_id', $lastQuery);
    }

    /** @test */
    public function getSubscriptionByIdReturnsEmptyWhenNotFound(): void
    {
        $this->pdo->setNextRows([]);

        $result = $this->model->getSubscriptionById('sub_nonexistent');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ── updateSubscriptionStatus ───────────────────────────────────────────

    /** @test */
    public function updateSubscriptionStatusBuildsUpdateQuery(): void
    {
        $this->model->updateSubscriptionStatus('sub_abc', 'cancelled');

        $queries = $this->pdo->getQueries();
        $lastQuery = end($queries);

        $this->assertStringContainsString('UPDATE', $lastQuery);
        $this->assertStringContainsString('razorpay_subscriptions', $lastQuery);
        $this->assertStringContainsString('status', $lastQuery);
    }

    /** @test */
    public function updateSubscriptionStatusIncludesUpdatedByWhenUserProvided(): void
    {
        $this->model->updateSubscriptionStatus('sub_abc', 'cancelled', 'admin');

        $queries = $this->pdo->getQueries();
        $lastQuery = end($queries);

        $this->assertStringContainsString('updated_by', $lastQuery);
    }

    // ── getSubscriptionDetails ─────────────────────────────────────────────

    /** @test */
    public function getSubscriptionDetailsJoinsPlansAndProducts(): void
    {
        $this->pdo->setNextRows([
            ['subscription_id' => 'sub_xyz', 'plan_name' => 'Monthly', 'productName' => 'Product A']
        ]);

        $result = $this->model->getSubscriptionDetails('sub_xyz');

        $this->assertSame('sub_xyz', $result['subscription_id']);

        $queries = $this->pdo->getQueries();
        $lastQuery = end($queries);
        $this->assertStringContainsString('razorpay_subscriptions', $lastQuery);
        $this->assertStringContainsString('razorpay_plans', $lastQuery);
        $this->assertStringContainsString('product_description', $lastQuery);
    }

    // ── getProductBasedPlans ───────────────────────────────────────────────

    /** @test */
    public function getProductBasedPlansFiltersActivePlans(): void
    {
        $this->pdo->setNextRows([
            ['plan_id' => 'plan_1', 'plan_status' => 1, 'opencart_product_id' => 10],
            ['plan_id' => 'plan_2', 'plan_status' => 1, 'opencart_product_id' => 10],
        ]);

        $result = $this->model->getProductBasedPlans(10);

        $this->assertCount(2, $result);

        $queries = $this->pdo->getQueries();
        $lastQuery = end($queries);
        $this->assertStringContainsString('plan_status = 1', $lastQuery);
        $this->assertStringContainsString('razorpay_plans', $lastQuery);
    }

    // ── getOrderProductId ──────────────────────────────────────────────────

    /** @test */
    public function getOrderProductIdQueriesOrderProductTable(): void
    {
        $this->pdo->setNextRows([
            ['order_product_id' => 55]
        ]);

        $result = $this->model->getOrderProductId(1001, 10);

        $this->assertSame(55, $result['order_product_id']);

        $queries = $this->pdo->getQueries();
        $lastQuery = end($queries);
        $this->assertStringContainsString('order_product', $lastQuery);
    }

    // ── updateOCSubscriptionStatus ─────────────────────────────────────────

    /** @test */
    public function updateOCSubscriptionStatusBuildsCorrectQuery(): void
    {
        $this->model->updateOCSubscriptionStatus(1001, 1);

        $queries = $this->pdo->getQueries();
        $lastQuery = end($queries);

        $this->assertStringContainsString('UPDATE', $lastQuery);
        $this->assertStringContainsString('order_subscription', $lastQuery);
    }

    // ── getOCSubscriptionStatus ────────────────────────────────────────────

    /** @test */
    public function getOCSubscriptionStatusQueriesOrderSubscription(): void
    {
        $this->pdo->setNextRows([['order_id' => 1001, 'status' => 1]]);

        $result = $this->model->getOCSubscriptionStatus(1001);

        $this->assertSame(1001, $result['order_id']);

        $queries = $this->pdo->getQueries();
        $lastQuery = end($queries);
        $this->assertStringContainsString('order_subscription', $lastQuery);
    }

    // ── editSetting ────────────────────────────────────────────────────────

    /** @test */
    public function editSettingDeletesThenInsertsForScalarValues(): void
    {
        $data = [
            'payment_razorpay_status' => 1,
            'payment_razorpay_key_id' => 'rzp_test_abc123',
        ];

        $this->model->editSetting('payment_razorpay', $data);

        $queries = $this->pdo->getQueries();
        $deleteCount = count(array_filter($queries, fn($q) => str_contains($q, 'DELETE')));
        $insertCount = count(array_filter($queries, fn($q) => str_contains($q, 'INSERT')));

        $this->assertSame(count($data), $deleteCount);
        $this->assertSame(count($data), $insertCount);
    }

    /** @test */
    public function editSettingSerializesArrayValues(): void
    {
        $data = [
            'payment_razorpay_webhook_events' => ['payment.authorized', 'order.paid'],
        ];

        $this->model->editSetting('payment_razorpay', $data);

        $queries = $this->pdo->getQueries();
        $insertQueries = array_values(array_filter($queries, fn($q) => str_contains($q, 'INSERT')));

        $this->assertNotEmpty($insertQueries);
        $this->assertStringContainsString("serialized = '1'", $insertQueries[0]);
    }
}
