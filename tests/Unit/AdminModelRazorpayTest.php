<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Opencart\Admin\Controller\Extension\Razorpay\Payment\MPDO;
use Tests\Helpers\TestHelper;

// Load the REAL admin model
require_once __DIR__ . '/../../admin/model/payment/razorpay.php';

/**
 * Unit Tests for the REAL Admin Model
 * Imports Opencart\Admin\Model\Extension\Razorpay\Payment\Razorpay directly
 */
class AdminModelRazorpayTest extends TestCase
{
    private \Opencart\System\Engine\Registry $registry;
    private \Opencart\Admin\Model\Extension\Razorpay\Payment\Razorpay $model;
    private MPDO $pdo;

    protected function setUp(): void
    {
        $this->registry = TestHelper::createEngineRegistry();
        $this->model    = new \Opencart\Admin\Model\Extension\Razorpay\Payment\Razorpay(
            $this->registry
        );
        $this->pdo = $this->model->rzpPdo;
    }

    // ── createTables ───────────────────────────────────────────────────────

    /** @test */
    public function createTablesIssuesCreateTableStatements(): void
    {
        $db = $this->registry->get('db');
        $this->model->createTables();

        $queries = $db->getQueries();
        $this->assertCount(2, $queries);
        $this->assertStringContainsString('razorpay_plans',         $queries[0]);
        $this->assertStringContainsString('razorpay_subscriptions', $queries[1]);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS', $queries[0]);
    }

    // ── dropTables ─────────────────────────────────────────────────────────

    /** @test */
    public function dropTablesIssuesDropStatements(): void
    {
        $this->model->dropTables();

        $queries = $this->pdo->getQueries();
        $this->assertStringContainsString('DROP TABLE IF EXISTS', $queries[0]);
        $this->assertStringContainsString('razorpay_plans',         $queries[0]);
        $this->assertStringContainsString('DROP TABLE IF EXISTS', $queries[1]);
        $this->assertStringContainsString('razorpay_subscriptions', $queries[1]);
    }

    // ── getPlans ───────────────────────────────────────────────────────────

    /** @test */
    public function getPlansReturnsAllPlans(): void
    {
        $result = $this->model->getPlans();

        // getPlans() uses rzpPdo internally
        $queries = $this->pdo->getQueries();
        $lastQuery = end($queries);

        $this->assertStringContainsString('razorpay_plans', $lastQuery);
        $this->assertStringContainsString('SELECT', $lastQuery);
    }
}

// Helper: filter queries containing a substring
function collect_queries_containing(array $queries, string $needle): array
{
    return array_values(array_filter($queries, fn($q) => str_contains($q, $needle)));
}
