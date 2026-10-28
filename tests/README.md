# Razorpay OpenCart Extension - Test Suite

Comprehensive unit and integration tests for the Razorpay Payment Gateway extension for OpenCart.

## Table of Contents

- [Overview](#overview)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Running Tests](#running-tests)
- [Test Structure](#test-structure)
- [Writing Tests](#writing-tests)
- [Code Coverage](#code-coverage)
- [Continuous Integration](#continuous-integration)
- [Troubleshooting](#troubleshooting)

## Overview

This test suite provides comprehensive testing for the Razorpay OpenCart extension, including:

- **Unit Tests**: Test individual components in isolation
- **Integration Tests**: Test payment flows, webhooks, and external API interactions
- **Mock Framework**: OpenCart framework mocks for isolated testing

### Test Coverage

- ✅ Payment controller logic
- ✅ Admin configuration
- ✅ Subscription management
- ✅ Webhook handling and signature verification
- ✅ Database operations
- ✅ Payment flow (success/failure scenarios)
- ✅ Currency validation
- ✅ Order status updates

## Prerequisites

- PHP 7.3 or higher
- Composer
- PHPUnit 9.5+
- OpenCart 4.x (for reference)

## Installation

### Step 1: Install Dependencies

```bash
cd /path/to/razorpay-opencart
composer install
```

This will install:
- PHPUnit (testing framework)
- Mockery (mocking library)
- PHPStan (static analysis)
- PHP_CodeSniffer (code style checker)

### Step 2: Verify Installation

```bash
composer test -- --version
```

You should see the PHPUnit version information.

## Running Tests

### Run All Tests

```bash
composer test
```

Or directly with PHPUnit:

```bash
./vendor/bin/phpunit
```

### Run Specific Test Suites

**Unit Tests Only:**
```bash
composer test:unit
```

**Integration Tests Only:**
```bash
composer test:integration
```

### Run Specific Test Files

```bash
./vendor/bin/phpunit tests/Unit/ModelRazorpayTest.php
./vendor/bin/phpunit tests/Integration/WebhookHandlingTest.php
```

### Run Specific Test Methods

```bash
./vendor/bin/phpunit --filter testWebhookSignatureVerification
```

### Run Tests with Detailed Output

```bash
./vendor/bin/phpunit --testdox --colors
```

## Test Structure

```
tests/
├── bootstrap.php                          # Test bootstrap file
├── Helpers/
│   └── TestHelper.php                     # Test utility functions
├── Mocks/
│   └── OpenCartMocks.php                  # Mock OpenCart components
├── Unit/
│   ├── ModelRazorpayTest.php              # Model unit tests
│   ├── ControllerRazorpayTest.php         # Catalog controller tests
│   └── AdminControllerRazorpayTest.php    # Admin controller tests
├── Integration/
│   ├── WebhookHandlingTest.php            # Webhook tests
│   └── PaymentFlowTest.php                # Payment flow tests
└── README.md                              # This file
```

## Writing Tests

### Example Unit Test

```php
<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Helpers\TestHelper;

class MyFeatureTest extends TestCase
{
    private $registry;

    protected function setUp(): void
    {
        $this->registry = TestHelper::createMockRegistry();
    }

    /**
     * @test
     */
    public function testMyFeature()
    {
        $config = $this->registry->get('config');
        $this->assertEquals('rzp_test_123', $config->get('payment_razorpay_key_id'));
    }
}
```

### Example Integration Test

```php
<?php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Helpers\TestHelper;

class MyIntegrationTest extends TestCase
{
    /**
     * @test
     */
    public function testPaymentFlow()
    {
        $order = TestHelper::createMockOrder();
        $razorpayOrder = TestHelper::createMockRazorpayOrder([
            'amount' => $order['total'] * 100
        ]);

        $this->assertEquals($order['total'] * 100, $razorpayOrder['amount']);
    }
}
```

### Using Test Helpers

The `TestHelper` class provides useful utilities:

```php
// Create mock registry with all dependencies
$registry = TestHelper::createMockRegistry();

// Create mock Razorpay responses
$order = TestHelper::createMockRazorpayOrder();
$payment = TestHelper::createMockRazorpayPayment();
$subscription = TestHelper::createMockRazorpaySubscription();

// Create webhook payloads
$webhook = TestHelper::createMockWebhookPayload('payment.authorized');

// Generate webhook signatures
$signature = TestHelper::generateWebhookSignature($payload, $secret);

// Get test cards
$cards = TestHelper::getTestCards();
```

## Code Coverage

### Generate HTML Coverage Report

```bash
composer test:coverage
```

Open `tests/coverage/index.html` in your browser to view the coverage report.

### View Coverage in Terminal

```bash
./vendor/bin/phpunit --coverage-text
```

### Coverage Requirements

- **Target**: 80%+ code coverage
- **Critical Paths**: Payment flow and webhook handling should have 90%+ coverage

## Code Quality

### Run Static Analysis

```bash
composer phpstan
```

### Check Code Style

```bash
composer cs
```

### Fix Code Style Issues

```bash
composer cs:fix
```

## Environment Variables

You can configure tests using environment variables:

```bash
export DB_HOSTNAME=localhost
export DB_USERNAME=root
export DB_PASSWORD=secret
export DB_DATABASE=opencart_test
export RAZORPAY_KEY_ID=rzp_test_123
export RAZORPAY_KEY_SECRET=secret_key
```

Or create a `.env` file in the project root.

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.0'
          extensions: json, mbstring

      - name: Install Dependencies
        run: composer install

      - name: Run Tests
        run: composer test

      - name: Generate Coverage
        run: composer test:coverage

      - name: Upload Coverage
        uses: codecov/codecov-action@v2
```

## Test Data

### Test Cards

Use these Razorpay test cards:

| Card Number          | Result  | Description                |
|---------------------|---------|----------------------------|
| 4111111111111111    | Success | Standard Visa test card    |
| 4000000000000002    | Failure | Payment declined           |
| 5555555555554444    | Success | MasterCard test card       |

### Test Credentials

- **Test Key ID**: `rzp_test_1DP5mmOlF5G5ag`
- **Test Key Secret**: `thisissecret`
- **Webhook Secret**: `webhook_secret_123`

## Troubleshooting

### Common Issues

**Issue: "Class not found" errors**
```bash
composer dump-autoload
```

**Issue: Tests fail with database errors**
- Check database credentials in `tests/bootstrap.php`
- Ensure test database exists
- Or use mock database (default)

**Issue: Permission denied**
```bash
chmod +x vendor/bin/phpunit
```

**Issue: Memory limit**
```bash
php -d memory_limit=512M vendor/bin/phpunit
```

### Debug Mode

Run tests with verbose output:

```bash
./vendor/bin/phpunit --debug --verbose
```

## Best Practices

1. **One assertion per test** (when possible)
2. **Use descriptive test names** (`testWebhookSignatureVerification` not `testWebhook`)
3. **Follow AAA pattern**: Arrange, Act, Assert
4. **Mock external dependencies** (API calls, database)
5. **Keep tests fast** (< 100ms per test)
6. **Test edge cases** (empty values, null, invalid data)
7. **Clean up after tests** (use `tearDown()` method)

## Contributing

When adding new features:

1. Write tests first (TDD approach)
2. Ensure all tests pass
3. Maintain 80%+ code coverage
4. Follow existing test structure
5. Update this README if needed

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Razorpay API Documentation](https://razorpay.com/docs/api/)
- [OpenCart Documentation](https://docs.opencart.com/)
- [PHP Testing Best Practices](https://phpunit.de/best-practices.html)

## Support

For issues or questions:

- Open an issue on [GitHub](https://github.com/razorpay/razorpay-opencart/issues)
- Contact: contact@razorpay.com

## License

MIT License - Same as the main extension
