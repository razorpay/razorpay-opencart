<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Helpers\KeyValidator;

/**
 * Tests for Razorpay Key Validator
 * Ensures we can detect test vs live vs dummy keys
 */
class KeyValidatorTest extends TestCase
{
    /**
     * @test
     */
    public function testRecognizesValidTestKey()
    {
        $testKey = 'rzp_test_1DP5mmOlF5G5ag';

        $this->assertTrue(KeyValidator::isTestKey($testKey));
        $this->assertFalse(KeyValidator::isLiveKey($testKey));
        $this->assertTrue(KeyValidator::isValidKey($testKey));
        $this->assertEquals('test', KeyValidator::getKeyType($testKey));
    }

    /**
     * @test
     */
    public function testRecognizesValidLiveKey()
    {
        $liveKey = 'rzp_live_AbCdEfGhIjKlMn';

        $this->assertTrue(KeyValidator::isLiveKey($liveKey));
        $this->assertFalse(KeyValidator::isTestKey($liveKey));
        $this->assertTrue(KeyValidator::isValidKey($liveKey));
        $this->assertEquals('live', KeyValidator::getKeyType($liveKey));
    }

    /**
     * @test
     */
    public function testRecognizesDummyKey()
    {
        $dummyKeys = [
            'rzp_test_DUMMY_KEY_FOR_TESTING',
            'rzp_test_dummy_key_123',
            'rzp_test_FAKE_KEY',
            'rzp_test_test_key_only'
        ];

        foreach ($dummyKeys as $key) {
            $this->assertTrue(KeyValidator::isDummyKey($key), "Failed to detect dummy key: $key");
            $this->assertEquals('dummy', KeyValidator::getKeyType($key));
        }
    }

    /**
     * @test
     */
    public function testRecognizesInvalidKey()
    {
        $invalidKeys = [
            'invalid_key',
            'rzp_prod_123',
            'test_key_123',
            '',
            'rzp_test_',
            'rzp_live_SHORT'
        ];

        foreach ($invalidKeys as $key) {
            $this->assertFalse(KeyValidator::isValidKey($key), "Failed to detect invalid key: $key");
            $this->assertEquals('invalid', KeyValidator::getKeyType($key));
        }
    }

    /**
     * @test
     */
    public function testIsSafeForTesting()
    {
        $this->assertTrue(KeyValidator::isSafeForTesting('rzp_test_1DP5mmOlF5G5ag'));
        $this->assertTrue(KeyValidator::isSafeForTesting('rzp_test_DUMMY_KEY'));
        $this->assertFalse(KeyValidator::isSafeForTesting('rzp_live_AbCdEfGhIjKlMn'));
    }

    /**
     * @test
     */
    public function testMaskKey()
    {
        $testKey = 'rzp_test_1DP5mmOlF5G5ag';
        $masked = KeyValidator::maskKey($testKey);

        $this->assertStringStartsWith('rzp_test', $masked);
        $this->assertStringEndsWith('G5ag', $masked);
        $this->assertStringContainsString('*', $masked);
        $this->assertNotEquals($testKey, $masked);
    }

    /**
     * @test
     */
    public function testGetKeyInfo()
    {
        $testKey = 'rzp_test_1DP5mmOlF5G5ag';
        $info = KeyValidator::getKeyInfo($testKey);

        $this->assertEquals('test', $info['type']);
        $this->assertTrue($info['is_valid']);
        $this->assertTrue($info['is_safe_for_testing']);
        $this->assertFalse($info['is_live']);
        $this->assertFalse($info['is_dummy']);
        $this->assertNull($info['warning']);
    }

    /**
     * @test
     */
    public function testGetKeyInfoForLiveKey()
    {
        $liveKey = 'rzp_live_AbCdEfGhIjKlMn';
        $info = KeyValidator::getKeyInfo($liveKey);

        $this->assertEquals('live', $info['type']);
        $this->assertTrue($info['is_valid']);
        $this->assertFalse($info['is_safe_for_testing']);
        $this->assertTrue($info['is_live']);
        $this->assertStringContainsString('DANGER', $info['warning']);
    }

    /**
     * @test
     */
    public function testValidateConfigWithValidTestKeys()
    {
        $config = [
            'key_id' => 'rzp_test_1DP5mmOlF5G5ag',
            'key_secret' => 'valid_secret_key_here'
        ];

        $result = KeyValidator::validateConfig($config);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['issues']);
        $this->assertEquals('test', $result['key_id_type']);
        $this->assertTrue($result['safe_for_testing']);
    }

    /**
     * @test
     */
    public function testValidateConfigWithDummyKeys()
    {
        $config = [
            'key_id' => 'rzp_test_DUMMY_KEY',
            'key_secret' => 'dummy_secret_for_testing'
        ];

        $result = KeyValidator::validateConfig($config);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['issues']);
        $this->assertEquals('dummy', $result['key_id_type']);
        $this->assertTrue($result['safe_for_testing']);
    }

    /**
     * @test
     */
    public function testValidateConfigWithLiveKeys()
    {
        $config = [
            'key_id' => 'rzp_live_AbCdEfGhIjKlMn',
            'key_secret' => 'live_secret_key'
        ];

        $result = KeyValidator::validateConfig($config);

        $this->assertFalse($result['valid']);
        $this->assertContains('WARNING: Live Key ID detected in test environment!', $result['issues']);
        $this->assertEquals('live', $result['key_id_type']);
        $this->assertFalse($result['safe_for_testing']);
    }

    /**
     * @test
     */
    public function testValidateConfigWithMissingKeys()
    {
        $config = [
            'key_id' => '',
            'key_secret' => ''
        ];

        $result = KeyValidator::validateConfig($config);

        $this->assertFalse($result['valid']);
        $this->assertContains('Razorpay Key ID is not configured', $result['issues']);
        $this->assertContains('Razorpay Key Secret is not configured', $result['issues']);
    }

    /**
     * @test
     */
    public function testValidateKeyThrowsExceptionForInvalidKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Razorpay key format');

        KeyValidator::validateKey('invalid_key');
    }

    /**
     * @test
     */
    public function testValidateKeyThrowsExceptionForDummyWhenNotAllowed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dummy/placeholder keys are not allowed');

        KeyValidator::validateKey('rzp_test_DUMMY_KEY', false);
    }

    /**
     * @test
     */
    public function testValidateKeyAllowsDummyWhenExplicitlyAllowed()
    {
        // Should not throw exception
        KeyValidator::validateKey('rzp_test_DUMMY_KEY', true);
        $this->assertTrue(true); // If we get here, validation passed
    }
}
