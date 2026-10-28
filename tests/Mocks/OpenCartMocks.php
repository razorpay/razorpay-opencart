<?php
namespace Tests\Mocks;

/**
 * Mock OpenCart Framework Components for Testing
 */

// Mock Registry Class
class MockRegistry
{
    private $data = [];

    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function has($key)
    {
        return isset($this->data[$key]);
    }
}

// Mock Config Class
class MockConfig
{
    private $data = [];

    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }
}

// Mock Session Class
class MockSession
{
    public $data = [];

    public function __construct()
    {
        $this->data = [
            'order_id' => 1001,
            'user_token' => 'test_token_123',
            'customer_id' => 1
        ];
    }
}

// Mock DB Class
class MockDB
{
    private $queries = [];
    private $results = [];
    private $nextRows = [];

    public function query($sql)
    {
        $this->queries[] = $sql;
        $rows = $this->nextRows ?: $this->results;
        $this->nextRows = [];
        return new MockQueryResult($rows);
    }

    public function escape($value)
    {
        return addslashes($value);
    }

    public function setResult($result)
    {
        $this->results = $result;
    }

    public function setNextRows(array $rows): void
    {
        $this->nextRows = $rows;
    }

    public function getQueries()
    {
        return $this->queries;
    }
}

// Mock Query Result
class MockQueryResult
{
    public $row = [];
    public $rows = [];
    public $num_rows = 0;

    public function __construct($results = [])
    {
        if (!empty($results)) {
            $this->row = $results[0] ?? [];
            $this->rows = $results;
            $this->num_rows = count($results);
        }
    }
}

// Mock Language Class
class MockLanguage
{
    private $data = [];

    public function load($filename)
    {
        return true;
    }

    public function get($key)
    {
        $defaults = [
            'heading_title' => 'Razorpay Payment',
            'text_title' => 'Credit/Debit Card, NetBanking, UPI',
            'text_edit' => 'Edit Razorpay Settings',
            'text_enabled' => 'Enabled',
            'text_disabled' => 'Disabled',
            'button_confirm' => 'Confirm Order',
            'button_save' => 'Save',
            'button_cancel' => 'Cancel'
        ];

        return isset($this->data[$key]) ? $this->data[$key] : ($defaults[$key] ?? $key);
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }
}

// Mock Document Class
class MockDocument
{
    private $title = '';

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
}

// Mock URL Class
class MockURL
{
    public function link($route, $args = '', $secure = false)
    {
        $url = 'index.php?route=' . $route;
        if ($args) {
            $url .= '&' . $args;
        }
        return HTTP_CATALOG . $url;
    }
}

// Mock Request Class
class MockRequest
{
    public $get = [];
    public $post = [];
    public $cookie = [];
    public $server = [];

    public function __construct()
    {
        $this->server = [
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1'
        ];
    }
}

// Mock Response Class
class MockResponse
{
    private $output = '';
    private $headers = [];

    public function setOutput($output)
    {
        $this->output = $output;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function addHeader($header)
    {
        $this->headers[] = $header;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function redirect($url)
    {
        $this->addHeader('Location: ' . $url);
    }
}

// Mock Cart Class
class MockCart
{
    private $products = [];
    private $hasSubscription = false;

    public function getProducts()
    {
        return $this->products;
    }

    public function setProducts($products)
    {
        $this->products = $products;
    }

    public function hasSubscription()
    {
        return $this->hasSubscription;
    }

    public function setHasSubscription($value)
    {
        $this->hasSubscription = $value;
    }

    public function getSubscriptions()
    {
        return array_filter($this->products, function($product) {
            return isset($product['subscription']);
        });
    }
}

// Mock Customer Class
class MockCustomer
{
    private $id = 1;
    private $email = 'test@example.com';
    private $firstName = 'Test';
    private $lastName = 'User';
    private $telephone = '9876543210';

    public function getId()
    {
        return $this->id;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function getTelephone()
    {
        return $this->telephone;
    }

    public function isLogged()
    {
        return true;
    }
}

// Mock Log Class
class MockLog
{
    private $logs = [];

    public function write($message)
    {
        $this->logs[] = [
            'timestamp' => time(),
            'message' => $message
        ];
    }

    public function getLogs()
    {
        return $this->logs;
    }
}

// Mock Load Class
class MockLoad
{
    private $registry;
    private $models = [];

    public function __construct($registry)
    {
        $this->registry = $registry;
    }

    public function model($route)
    {
        $class = 'Model' . str_replace(['/', '_'], ['', ''], ucwords($route, '/_'));

        if (!isset($this->models[$route])) {
            $this->models[$route] = new MockModel($this->registry);
            $key = 'model_' . str_replace('/', '_', $route);
            $this->registry->set($key, $this->models[$route]);
        }

        return $this->models[$route];
    }

    public function language($route)
    {
        return true;
    }

    public function view($route, $data = [])
    {
        return '';
    }
}

// Mock Model Class
class MockModel
{
    protected $registry;

    public function __construct($registry)
    {
        $this->registry = $registry;
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    // Generic method handler for dynamic model methods
    public function __call($method, $args)
    {
        return null;
    }

    public function getOrder($orderId)
    {
        return [
            'order_id' => $orderId,
            'currency_code' => 'INR',
            'total' => 1000.00,
            'customer_id' => 1,
            'email' => 'test@example.com',
            'telephone' => '9876543210',
            'firstname' => 'Test',
            'lastname' => 'User'
        ];
    }

    public function getOrderStatuses()
    {
        return [
            ['order_status_id' => 1, 'name' => 'Pending'],
            ['order_status_id' => 2, 'name' => 'Processing'],
            ['order_status_id' => 5, 'name' => 'Complete']
        ];
    }

    public function addHistory($orderId, $statusId, $comment = '', $notify = false)
    {
        return true;
    }
}
