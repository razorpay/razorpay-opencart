<?php
/**
 * PHPUnit Bootstrap File
 * Sets up the testing environment for Razorpay OpenCart Extension
 */

// Define OpenCart constants if not already defined
if (!defined('DB_PREFIX')) {
    define('DB_PREFIX', 'oc_');
}

if (!defined('VERSION')) {
    define('VERSION', '4.0.2.3');
}

if (!defined('HTTP_CATALOG')) {
    define('HTTP_CATALOG', 'http://localhost/opencart/');
}

if (!defined('HTTP_SERVER')) {
    define('HTTP_SERVER', 'http://localhost/opencart/');
}

if (!defined('DIR_SYSTEM')) {
    define('DIR_SYSTEM', __DIR__ . '/../system/');
}

if (!defined('DIR_STORAGE')) {
    define('DIR_STORAGE', __DIR__ . '/../storage/');
}

// Database constants for testing
if (!defined('DB_HOSTNAME')) {
    define('DB_HOSTNAME', getenv('DB_HOSTNAME') ?: 'localhost');
}

if (!defined('DB_USERNAME')) {
    define('DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
}

if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
}

if (!defined('DB_DATABASE')) {
    define('DB_DATABASE', getenv('DB_DATABASE') ?: 'opencart_test');
}

// Load .env file if exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!getenv($name)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load OpenCart engine stubs FIRST — real extension classes extend these
require_once __DIR__ . '/Stubs/OpenCartEngine.php';

// Load external dependency stubs (mPDO, Razorpay SDK) before extension classes load them
require_once __DIR__ . '/Stubs/ExternalStubs.php';

// Load test helpers and mocks
require_once __DIR__ . '/Mocks/OpenCartMocks.php';
require_once __DIR__ . '/Helpers/TestHelper.php';
require_once __DIR__ . '/Helpers/KeyValidator.php';

use Tests\Helpers\KeyValidator;

echo "Razorpay OpenCart Extension Test Suite\n";
echo "========================================\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PHPUnit Version: " . PHPUnit\Runner\Version::id() . "\n";
echo "OpenCart Version: " . VERSION . "\n";
echo "========================================\n";

// Validate configured Razorpay keys
$keyId = getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_DUMMY_KEY_FOR_UNIT_TESTS';
$keySecret = getenv('RAZORPAY_KEY_SECRET') ?: 'dummy_secret_for_unit_tests_only';

echo "\nRazorpay Configuration:\n";
echo "------------------------\n";

$validationResult = KeyValidator::validateConfig([
    'key_id' => $keyId,
    'key_secret' => $keySecret
]);

echo "Key ID: " . KeyValidator::maskKey($keyId) . "\n";
echo "Type: " . $validationResult['key_id_type'] . "\n";
echo "Safe for Testing: " . ($validationResult['safe_for_testing'] ? 'Yes' : 'No') . "\n";

if (!empty($validationResult['issues'])) {
    echo "\nWarnings:\n";
    foreach ($validationResult['issues'] as $issue) {
        $prefix = (stripos($issue, 'warning') !== false || stripos($issue, 'danger') !== false) ? '⚠️  ' : 'ℹ️  ';
        echo "$prefix $issue\n";
    }
}

echo "\n========================================\n\n";
