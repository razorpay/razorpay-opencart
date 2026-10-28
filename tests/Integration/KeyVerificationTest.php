<?php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Helpers\KeyValidator;

/**
 * Real API Key Verification Test
 * Makes an actual HTTP call to Razorpay to verify if configured keys work.
 *
 * ⚠️  This test is SKIPPED automatically if dummy keys are configured.
 *     Replace .env keys with real Razorpay test keys to run this test.
 */
class KeyVerificationTest extends TestCase
{
    private string $keyId;
    private string $keySecret;
    private string $baseUrl = 'https://api.razorpay.com/v1';

    protected function setUp(): void
    {
        $this->keyId     = getenv('RAZORPAY_KEY_ID')     ?: '';
        $this->keySecret = getenv('RAZORPAY_KEY_SECRET') ?: '';

        // Auto-skip if keys are dummy/not set
        if (empty($this->keyId) || empty($this->keySecret)) {
            $this->markTestSkipped('RAZORPAY_KEY_ID or RAZORPAY_KEY_SECRET not set in .env');
        }

        $keyType = KeyValidator::getKeyType($this->keyId);

        if ($keyType === 'dummy' || $keyType === 'invalid') {
            $this->markTestSkipped(
                "Skipping: Key is $keyType. Set real Razorpay test keys in .env to run this test."
            );
        }
    }

    /**
     * @test
     * Calls real Razorpay API to verify keys are authentic
     */
    public function testKeysAreAuthenticWithRazorpayApi(): void
    {
        $response = $this->callRazorpayApi('/payments?count=1');

        $this->assertNotEquals(401, $response['status_code'],
            "❌ Keys are INVALID — Razorpay returned 401 Unauthorized"
        );

        $this->assertNotEquals(400, $response['status_code'],
            "❌ Bad request — check key format"
        );

        $this->assertContains($response['status_code'], [200, 404],
            "❌ Unexpected response: HTTP {$response['status_code']}"
        );

        echo "\n✅ Keys verified successfully with Razorpay API\n";
        echo "   Key ID: " . KeyValidator::maskKey($this->keyId) . "\n";
        echo "   Type: " . KeyValidator::getKeyType($this->keyId) . "\n";
        echo "   HTTP Status: " . $response['status_code'] . "\n";
    }

    /**
     * @test
     * Creates a real test order on Razorpay to fully verify keys work
     */
    public function testCanCreateRealOrderWithKeys(): void
    {
        $payload = json_encode([
            'amount'   => 100,   // ₹1 in paise
            'currency' => 'INR',
            'receipt'  => 'ut_verify_' . time(),
            'notes'    => ['purpose' => 'unit_test_key_verification']
        ]);

        $response = $this->callRazorpayApi('/orders', 'POST', $payload);

        if ($response['status_code'] === 401) {
            $this->fail(
                "❌ Keys are INVALID — Razorpay rejected them (401 Unauthorized)\n" .
                "   Key ID: " . KeyValidator::maskKey($this->keyId) . "\n" .
                "   Update .env with real Razorpay test keys."
            );
        }

        $this->assertEquals(200, $response['status_code'],
            "❌ Order creation failed. HTTP {$response['status_code']}: {$response['body']}"
        );

        $body = json_decode($response['body'], true);

        $this->assertArrayHasKey('id', $body,       "❌ No order ID in response");
        $this->assertStringStartsWith('order_', $body['id'], "❌ Invalid order ID format");
        $this->assertEquals('created', $body['status'], "❌ Order not in created state");
        $this->assertEquals(100, $body['amount'],        "❌ Amount mismatch");

        echo "\n✅ Real Razorpay order created successfully!\n";
        echo "   Order ID: " . $body['id'] . "\n";
        echo "   Amount: ₹" . ($body['amount'] / 100) . "\n";
        echo "   Status: " . $body['status'] . "\n";
    }

    /**
     * Helper: Make HTTP call to Razorpay API using Basic Auth
     */
    private function callRazorpayApi(string $endpoint, string $method = 'GET', string $body = ''): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $this->keyId . ':' . $this->keySecret,
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response   = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error      = curl_error($ch);

        curl_close($ch);

        if ($error) {
            $this->fail("cURL error: $error");
        }

        return [
            'status_code' => $statusCode,
            'body'        => $response
        ];
    }
}
