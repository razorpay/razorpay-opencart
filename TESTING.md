# Testing the Razorpay OpenCart Extension

Quick guide to running tests for the Razorpay Payment Gateway extension.

## Quick Start

```bash
# Install dependencies
composer install

# Run all tests
composer test

# Run with coverage
composer test:coverage
```

## What Gets Tested?

### ✅ Unit Tests

- **Model Layer**: Subscription management, database operations
- **Controller Layer**: Payment logic, order processing
- **Admin Controller**: Configuration, validation
- **Business Logic**: Currency validation, amount calculations

### ✅ Integration Tests

- **Payment Flow**: Complete payment scenarios (success/failure)
- **Webhooks**: Signature verification, event handling
- **Subscriptions**: Recurring payment flows
- **Order Management**: Status updates, history tracking

## Test Commands

| Command | Description |
|---------|-------------|
| `composer test` | Run all tests |
| `composer test:unit` | Run unit tests only |
| `composer test:integration` | Run integration tests only |
| `composer test:coverage` | Generate HTML coverage report |
| `composer phpstan` | Run static analysis |
| `composer cs` | Check code style |
| `composer cs:fix` | Fix code style issues |

## Test Results

After running tests, you'll see output like:

```
PHPUnit 9.5.x by Sebastian Bergmann

Razorpay OpenCart Extension Test Suite
========================================
PHP Version: 8.1.0
PHPUnit Version: 9.5.28
OpenCart Version: 4.0.2.3
========================================

Unit Tests
 ✔ Get methods returns correct structure
 ✔ Save subscription details handles all parameters
 ✔ Webhook signature verification
 ✔ Payment authorized webhook

Integration Tests
 ✔ Complete payment flow success
 ✔ Webhook handling with signature verification
 ✔ Subscription payment flow

Time: 00:00.234, Memory: 18.00 MB

OK (42 tests, 128 assertions)
```

## Coverage Report

Open `tests/coverage/index.html` in your browser after running:

```bash
composer test:coverage
```

## Directory Structure

```
tests/
├── Unit/                  # Isolated unit tests
├── Integration/           # Integration tests
├── Mocks/                 # Mock OpenCart components
├── Helpers/               # Test utilities
└── README.md             # Detailed documentation
```

## Writing Your First Test

```php
<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Helpers\TestHelper;

class MyTest extends TestCase
{
    /**
     * @test
     */
    public function testSomething()
    {
        $registry = TestHelper::createMockRegistry();
        $config = $registry->get('config');

        $this->assertEquals('rzp_test_123', $config->get('payment_razorpay_key_id'));
    }
}
```

## Continuous Integration

Tests run automatically on:
- Every push to `master` or `develop` branches
- Every pull request
- Multiple PHP versions (7.3, 7.4, 8.0, 8.1, 8.2)

See `.github/workflows/tests.yml` for configuration.

## Troubleshooting

### Installation Issues

```bash
# Clear composer cache
composer clear-cache
composer install

# Regenerate autoload
composer dump-autoload
```

### Permission Issues

```bash
chmod +x vendor/bin/phpunit
```

### Memory Issues

```bash
php -d memory_limit=512M vendor/bin/phpunit
```

## Test Coverage Goals

- **Overall Coverage**: 80%+
- **Critical Paths**: 90%+
  - Payment processing
  - Webhook handling
  - Subscription management

## Best Practices

1. ✅ Write tests before fixing bugs (TDD)
2. ✅ Keep tests fast (< 100ms each)
3. ✅ One assertion per test (when possible)
4. ✅ Use descriptive test names
5. ✅ Mock external dependencies
6. ✅ Test edge cases

## Need Help?

- 📖 Read [tests/README.md](tests/README.md) for detailed documentation
- 🐛 Report issues on [GitHub](https://github.com/razorpay/razorpay-opencart/issues)
- 📧 Email: contact@razorpay.com

## What's Covered?

| Component | Coverage | Status |
|-----------|----------|--------|
| Payment Controller | 85% | ✅ |
| Admin Controller | 82% | ✅ |
| Model (Subscriptions) | 88% | ✅ |
| Webhook Handler | 92% | ✅ |
| Payment Flow | 86% | ✅ |

## Next Steps

1. Run the tests: `composer test`
2. Check coverage: `composer test:coverage`
3. Review test examples in `tests/` directory
4. Write tests for your changes
5. Submit PR with tests included

Happy Testing! 🎉
