<?php
namespace Tests\Helpers;

/**
 * Razorpay Key Validator
 * Validates and identifies Razorpay API keys
 */
class KeyValidator
{
    // Key patterns
    const TEST_KEY_PATTERN = '/^rzp_test_[A-Za-z0-9]{14}$/';
    const LIVE_KEY_PATTERN = '/^rzp_live_[A-Za-z0-9]{14}$/';
    const DUMMY_KEY_PATTERN = '/^rzp_test_(DUMMY|dummy|test|fake)/i';

    /**
     * Check if key is a valid Razorpay key
     */
    public static function isValidKey(string $key): bool
    {
        return self::isTestKey($key) || self::isLiveKey($key);
    }

    /**
     * Check if key is a test key
     */
    public static function isTestKey(string $key): bool
    {
        return preg_match(self::TEST_KEY_PATTERN, $key) === 1;
    }

    /**
     * Check if key is a live/production key
     */
    public static function isLiveKey(string $key): bool
    {
        return preg_match(self::LIVE_KEY_PATTERN, $key) === 1;
    }

    /**
     * Check if key is a dummy/placeholder key
     */
    public static function isDummyKey(string $key): bool
    {
        return preg_match(self::DUMMY_KEY_PATTERN, $key) === 1;
    }

    /**
     * Get key type
     */
    public static function getKeyType(string $key): string
    {
        if (self::isDummyKey($key)) {
            return 'dummy';
        }

        if (self::isLiveKey($key)) {
            return 'live';
        }

        if (self::isTestKey($key)) {
            return 'test';
        }

        return 'invalid';
    }

    /**
     * Validate key format and throw exception if invalid
     */
    public static function validateKey(string $key, bool $allowDummy = true): void
    {
        $keyType = self::getKeyType($key);

        if ($keyType === 'invalid') {
            throw new \InvalidArgumentException(
                "Invalid Razorpay key format. Expected format: rzp_test_XXXXXXXXXXXXXX or rzp_live_XXXXXXXXXXXXXX"
            );
        }

        if ($keyType === 'dummy' && !$allowDummy) {
            throw new \InvalidArgumentException(
                "Dummy/placeholder keys are not allowed. Please configure real Razorpay test keys."
            );
        }

        if ($keyType === 'live') {
            trigger_error(
                "WARNING: Live Razorpay keys detected! Never use live keys in testing environments.",
                E_USER_WARNING
            );
        }
    }

    /**
     * Check if we're using safe keys for testing
     */
    public static function isSafeForTesting(string $keyId): bool
    {
        $keyType = self::getKeyType($keyId);
        return in_array($keyType, ['dummy', 'test']);
    }

    /**
     * Get key information
     */
    public static function getKeyInfo(string $key): array
    {
        $type = self::getKeyType($key);

        return [
            'key' => substr($key, 0, 15) . '...',  // Masked key
            'type' => $type,
            'is_valid' => $type !== 'invalid',
            'is_safe_for_testing' => in_array($type, ['dummy', 'test']),
            'is_live' => $type === 'live',
            'is_dummy' => $type === 'dummy',
            'warning' => $type === 'live' ? 'DANGER: Live key detected!' : null
        ];
    }

    /**
     * Mask sensitive key for display
     */
    public static function maskKey(string $key): string
    {
        if (strlen($key) < 20) {
            return str_repeat('*', strlen($key));
        }

        $prefix = substr($key, 0, 8);  // "rzp_test" or "rzp_live"
        $suffix = substr($key, -4);     // Last 4 characters
        $masked = str_repeat('*', strlen($key) - 12);

        return $prefix . $masked . $suffix;
    }

    /**
     * Validate configuration keys
     */
    public static function validateConfig(array $config): array
    {
        $issues = [];

        // Check Key ID
        if (empty($config['key_id'])) {
            $issues[] = 'Razorpay Key ID is not configured';
        } else {
            $keyIdType = self::getKeyType($config['key_id']);

            if ($keyIdType === 'invalid') {
                $issues[] = 'Invalid Razorpay Key ID format';
            } elseif ($keyIdType === 'live') {
                $issues[] = 'WARNING: Live Key ID detected in test environment!';
            } elseif ($keyIdType === 'dummy') {
                $issues[] = 'Using dummy Key ID - integration tests may fail';
            }
        }

        // Check Key Secret
        if (empty($config['key_secret'])) {
            $issues[] = 'Razorpay Key Secret is not configured';
        } elseif (strlen($config['key_secret']) < 10) {
            $issues[] = 'Key Secret appears to be invalid (too short)';
        } elseif (stripos($config['key_secret'], 'dummy') !== false ||
                  stripos($config['key_secret'], 'test') !== false ||
                  stripos($config['key_secret'], 'fake') !== false) {
            $issues[] = 'Using dummy Key Secret - integration tests may fail';
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'key_id_type' => isset($config['key_id']) ? self::getKeyType($config['key_id']) : 'not_set',
            'masked_key_id' => isset($config['key_id']) ? self::maskKey($config['key_id']) : 'N/A',
            'safe_for_testing' => isset($config['key_id']) ? self::isSafeForTesting($config['key_id']) : false
        ];
    }
}
