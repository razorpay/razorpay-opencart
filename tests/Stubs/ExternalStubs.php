<?php
/**
 * Stubs for external dependencies:
 *  - mPDO  (database layer used by models)
 *  - Razorpay SDK classes (used by controllers/models)
 *
 * PHP requires all namespace declarations when mixed with the global namespace
 * to use the bracketed namespace {} syntax.
 */

// ── Global namespace: mPDO ───────────────────────────────────────────────────
namespace {
    if (!class_exists('mPDO')) {
        class mPDO
        {
            public array  $queries  = [];
            public array  $bindings = [];
            private array $nextRows = [];
            private string $currentSql = '';

            public function __construct($host = '', $user = '', $pass = '', $db = '') {}

            public function prepare(string $sql): void
            {
                $this->currentSql = $sql;
                $this->queries[]  = $sql;
                $this->bindings   = [];
            }

            public function bindParam(string $param, $value): void
            {
                $this->bindings[$param] = $value;
            }

            public function execute(): object
            {
                $rows = $this->nextRows;
                $this->nextRows = [];

                return new class($rows) {
                    public array $rows;
                    public array $row;
                    public int   $num_rows;

                    public function __construct(array $rows)
                    {
                        $this->rows     = $rows;
                        $this->row      = $rows[0] ?? [];
                        $this->num_rows = count($rows);
                    }
                };
            }

            public function setNextRows(array $rows): void { $this->nextRows = $rows; }
            public function getQueries(): array            { return $this->queries; }
            public function getBindings(): array           { return $this->bindings; }
            public function lastInsertedId(): int          { return 1; }
        }
    }
}

// ── Opencart MPDO stub (used by admin/catalog models) ─────────────────────────
namespace Opencart\Admin\Controller\Extension\Razorpay\Payment {
    if (!class_exists('Opencart\Admin\Controller\Extension\Razorpay\Payment\MPDO')) {
        final class MPDO
        {
            public array  $queries  = [];
            public array  $bindings = [];
            private array $nextRows = [];

            public function __construct($host = '', $user = '', $pass = '', $db = '', $port = '3306') {}

            public function prepare(string $sql): void
            {
                $this->queries[]  = $sql;
                $this->bindings   = [];
            }

            public function bindParam(string $param, $value): void
            {
                $this->bindings[$param] = $value;
            }

            public function execute(): object
            {
                $rows = $this->nextRows;
                $this->nextRows = [];
                return new class($rows) {
                    public array $rows; public array $row; public int $num_rows;
                    public function __construct(array $rows) {
                        $this->rows = $rows; $this->row = $rows[0] ?? []; $this->num_rows = count($rows);
                    }
                };
            }

            public function setNextRows(array $rows): void { $this->nextRows = $rows; }
            public function getQueries(): array            { return $this->queries; }
            public function getBindings(): array           { return $this->bindings; }
            public function lastInsertedId(): int          { return 1; }
        }
    }
}

// ── Razorpay\Api namespace ────────────────────────────────────────────────────
namespace Razorpay\Api {

    if (!class_exists('Razorpay\Api\Api')) {

        class Api
        {
            public string $keyId;
            public string $keySecret;
            public Order        $order;
            public Payment      $payment;
            public Subscription $subscription;

            public function __construct(string $keyId = '', string $keySecret = '')
            {
                $this->keyId        = $keyId;
                $this->keySecret    = $keySecret;
                $this->order        = new Order();
                $this->payment      = new Payment();
                $this->subscription = new Subscription();
            }

            public function getBaseUrl(): string
            {
                return 'https://api.razorpay.com/v1/';
            }
        }

        class Entity
        {
            protected array $attributes = [];

            public function __construct(array $attributes = [])
            {
                $this->attributes = $attributes;
            }

            public function toArray(): array { return $this->attributes; }
            public function __get($key)      { return $this->attributes[$key] ?? null; }
            public function __set($key, $v)  { $this->attributes[$key] = $v; }
            public function __isset($key): bool { return isset($this->attributes[$key]); }
        }

        class Order extends Entity
        {
            private array $nextResponse = [];

            public function create(array $data): self
            {
                $response = array_merge([
                    'id'         => 'order_' . uniqid(),
                    'entity'     => 'order',
                    'amount'     => $data['amount'] ?? 0,
                    'currency'   => $data['currency'] ?? 'INR',
                    'receipt'    => $data['receipt'] ?? '',
                    'status'     => 'created',
                    'created_at' => time(),
                ], $this->nextResponse);

                $this->nextResponse = [];
                return new self($response);
            }

            public function fetch(string $id): self
            {
                return new self(array_merge(['id' => $id, 'status' => 'created'], $this->nextResponse));
            }

            public function setNextResponse(array $r): void { $this->nextResponse = $r; }
        }

        class Payment extends Entity
        {
            private array $nextResponse = [];

            public function fetch(string $id): self
            {
                return new self(array_merge(['id' => $id, 'status' => 'captured'], $this->nextResponse));
            }

            public function capture(string $id, int $amount): self
            {
                return new self(['id' => $id, 'status' => 'captured', 'amount' => $amount]);
            }

            public function setNextResponse(array $r): void { $this->nextResponse = $r; }
        }

        class Subscription extends Entity
        {
            private array $nextResponse = [];

            public function create(array $data): self
            {
                $response = array_merge([
                    'id'              => 'sub_' . uniqid(),
                    'entity'          => 'subscription',
                    'plan_id'         => $data['plan_id'] ?? '',
                    'status'          => 'created',
                    'quantity'        => $data['quantity'] ?? 1,
                    'total_count'     => $data['total_count'] ?? 1,
                    'paid_count'      => 0,
                    'remaining_count' => $data['total_count'] ?? 1,
                    'created_at'      => time(),
                ], $this->nextResponse);

                $this->nextResponse = [];
                return new self($response);
            }

            public function fetch(string $id): self
            {
                return new self(array_merge(['id' => $id, 'status' => 'active'], $this->nextResponse));
            }

            public function setNextResponse(array $r): void { $this->nextResponse = $r; }
        }

        class Utility
        {
            public static function verifyWebhookSignature(
                string $payload,
                string $signature,
                string $secret
            ): bool {
                return hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
            }
        }
    }
}

// ── Razorpay\Api\Errors namespace ─────────────────────────────────────────────
namespace Razorpay\Api\Errors {
    if (!class_exists('Razorpay\Api\Errors\Error')) {
        class Error extends \Exception {}
        class SignatureVerificationError extends Error {}
    }
}
