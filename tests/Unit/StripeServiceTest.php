<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Application\Service\StripeService;
use App\Infrastructure\Config\Config;
use App\Infrastructure\Logging\ErrorLogger;

class StripeServiceTest extends TestCase
{
    private StripeService $stripeService;
    private $mockConfig;
    private $mockErrorLogger;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->mockConfig = $this->createMock(Config::class);
        $this->mockErrorLogger = $this->createMock(ErrorLogger::class);
        
        // Create StripeService with mocked dependencies
        $this->stripeService = new StripeService(
            $this->mockConfig,
            $this->mockErrorLogger
        );
    }

    public function testGetPublishableKeyInDevelopmentMode()
    {
        // Mock development environment
        $this->mockConfig
            ->method('get')
            ->willReturnMap([
                ['stripe.publishable_key', null, 'pk_test_development_key'],
                ['app.env', 'production', 'development']
            ]);

        $key = $this->stripeService->getPublishableKey();

        $this->assertEquals('pk_test_development_key', $key);
        $this->assertStringStartsWith('pk_test_', $key);
    }

    public function testGetPublishableKeyInProductionMode()
    {
        // Mock production environment
        $this->mockConfig
            ->method('get')
            ->willReturnMap([
                ['stripe.publishable_key', null, 'pk_live_production_key'],
                ['app.env', 'production', 'production']
            ]);

        $key = $this->stripeService->getPublishableKey();

        $this->assertEquals('pk_live_production_key', $key);
        $this->assertStringStartsWith('pk_live_', $key);
    }

    public function testCreateCheckoutSessionInMockMode()
    {
        // Mock development mode (no valid API keys)
        $this->mockConfig
            ->method('get')
            ->willReturnMap([
                ['stripe.secret_key', null, null],
                ['app.env', 'production', 'development']
            ]);

        $items = [
            [
                'id' => '1',
                'name' => 'AI Email Assistant',
                'price' => 29.99,
                'quantity' => 1
            ]
        ];

        $result = $this->stripeService->createCheckoutSession($items, 29.99);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('session', $result);
        $this->assertStringStartsWith('cs_test_mock_', $result['session']['id']);
        $this->assertTrue($result['session']['mock']);
    }

    public function testCreateCheckoutSessionWithValidItems()
    {
        // Mock valid configuration
        $this->mockConfig
            ->method('get')
            ->willReturnMap([
                ['stripe.secret_key', null, 'sk_test_valid_key'],
                ['stripe.success_url', null, 'http://localhost:8000/betaling-succes'],
                ['stripe.cancel_url', null, 'http://localhost:8000/winkelwagen'],
                ['app.env', 'production', 'testing']
            ]);

        $items = [
            [
                'id' => '1',
                'name' => 'AI Email Assistant',
                'price' => 29.99,
                'quantity' => 1
            ],
            [
                'id' => '2',
                'name' => 'AI Basics Course',
                'price' => 97.00,
                'quantity' => 1
            ]
        ];

        $total = 126.99;

        // Since we can't mock Stripe SDK easily, test the mock path
        $result = $this->stripeService->createCheckoutSession($items, $total);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('session', $result);
    }

    public function testCreateCheckoutSessionWithEmptyItems()
    {
        $items = [];
        $total = 0;

        $result = $this->stripeService->createCheckoutSession($items, $total);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('empty', strtolower($result['error']));
    }

    public function testCreateCheckoutSessionWithInvalidTotal()
    {
        $items = [
            [
                'id' => '1',
                'name' => 'Test Product',
                'price' => 29.99,
                'quantity' => 1
            ]
        ];
        $total = -10.00; // Invalid negative total

        $result = $this->stripeService->createCheckoutSession($items, $total);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('invalid', strtolower($result['error']));
    }

    public function testCreatePaymentIntentInMockMode()
    {
        // Mock development mode
        $this->mockConfig
            ->method('get')
            ->willReturnMap([
                ['stripe.secret_key', null, null],
                ['app.env', 'production', 'development']
            ]);

        $result = $this->stripeService->createPaymentIntent(2999, 'eur', 'Test Payment');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('payment_intent', $result);
        $this->assertStringStartsWith('pi_test_mock_', $result['payment_intent']['id']);
        $this->assertEquals(2999, $result['payment_intent']['amount']);
        $this->assertEquals('eur', $result['payment_intent']['currency']);
    }

    public function testCreatePaymentIntentWithInvalidAmount()
    {
        $result = $this->stripeService->createPaymentIntent(-100, 'eur', 'Invalid Payment');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('amount', strtolower($result['error']));
    }

    public function testCreatePaymentIntentWithInvalidCurrency()
    {
        $result = $this->stripeService->createPaymentIntent(2999, 'invalid', 'Test Payment');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('currency', strtolower($result['error']));
    }

    public function testGetSessionStatusInMockMode()
    {
        $sessionId = 'cs_test_mock_123456';

        $result = $this->stripeService->getSessionStatus($sessionId);

        $this->assertTrue($result['success']);
        $this->assertEquals('complete', $result['status']);
        $this->assertEquals('paid', $result['payment_status']);
        $this->assertArrayHasKey('session', $result);
    }

    public function testGetSessionStatusWithInvalidId()
    {
        $sessionId = 'invalid_session_id';

        $result = $this->stripeService->getSessionStatus($sessionId);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', strtolower($result['error']));
    }

    public function testVerifyWebhookSignatureInMockMode()
    {
        $payload = json_encode(['type' => 'checkout.session.completed']);
        $signature = 'valid_signature';

        // Mock development mode
        $this->mockConfig
            ->method('get')
            ->willReturnMap([
                ['stripe.webhook_secret', null, null],
                ['app.env', 'production', 'development']
            ]);

        $result = $this->stripeService->verifyWebhookSignature($payload, $signature);

        // In mock mode, always return true
        $this->assertTrue($result);
    }

    public function testProcessWebhookInMockMode()
    {
        $webhookData = [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_mock_123456',
                    'payment_status' => 'paid',
                    'amount_total' => 12699
                ]
            ]
        ];

        $result = $this->stripeService->processWebhook($webhookData);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('processed', strtolower($result['message']));
    }

    public function testProcessWebhookWithUnsupportedEvent()
    {
        $webhookData = [
            'type' => 'unsupported.event.type',
            'data' => [
                'object' => []
            ]
        ];

        $result = $this->stripeService->processWebhook($webhookData);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('ignored', strtolower($result['message']));
    }

    public function testAmountConversion()
    {
        // Test euros to cents conversion
        $this->assertEquals(2999, $this->stripeService->convertToCents(29.99));
        $this->assertEquals(10000, $this->stripeService->convertToCents(100.00));
        $this->assertEquals(50, $this->stripeService->convertToCents(0.50));
    }

    public function testCurrencyValidation()
    {
        // Test supported currencies
        $supportedCurrencies = ['eur', 'usd', 'gbp'];
        
        foreach ($supportedCurrencies as $currency) {
            $isValid = $this->stripeService->isValidCurrency($currency);
            $this->assertTrue($isValid, "Currency '$currency' should be supported");
        }

        // Test unsupported currencies
        $unsupportedCurrencies = ['invalid', 'xyz', ''];
        
        foreach ($unsupportedCurrencies as $currency) {
            $isValid = $this->stripeService->isValidCurrency($currency);
            $this->assertFalse($isValid, "Currency '$currency' should not be supported");
        }
    }

    public function testItemValidation()
    {
        // Test valid items
        $validItems = [
            [
                'id' => '1',
                'name' => 'Valid Product',
                'price' => 29.99,
                'quantity' => 1
            ]
        ];

        $isValid = $this->stripeService->validateItems($validItems);
        $this->assertTrue($isValid);

        // Test invalid items
        $invalidItems = [
            [
                'id' => '', // Missing ID
                'name' => 'Invalid Product',
                'price' => -10.00, // Negative price
                'quantity' => 0 // Zero quantity
            ]
        ];

        $isValid = $this->stripeService->validateItems($invalidItems);
        $this->assertFalse($isValid);
    }

    public function testConfigurationValidation()
    {
        // Test with valid configuration
        $this->mockConfig
            ->method('get')
            ->willReturnMap([
                ['stripe.secret_key', null, 'sk_test_valid_key'],
                ['stripe.publishable_key', null, 'pk_test_valid_key']
            ]);

        $isConfigured = $this->stripeService->isProperlyConfigured();
        $this->assertTrue($isConfigured);
    }

    public function testConfigurationValidationWithMissingKeys()
    {
        // Test with missing configuration
        $this->mockConfig
            ->method('get')
            ->willReturnMap([
                ['stripe.secret_key', null, null],
                ['stripe.publishable_key', null, null]
            ]);

        $isConfigured = $this->stripeService->isProperlyConfigured();
        $this->assertFalse($isConfigured);
    }

    public function testErrorLogging()
    {
        // Mock error logger expectation
        $this->mockErrorLogger
            ->expects($this->once())
            ->method('log')
            ->with(
                $this->stringContains('Stripe'),
                $this->anything()
            );

        // Trigger an error condition
        $this->stripeService->createCheckoutSession([], 0);
    }

    public function testGetAllowedCurrencies()
    {
        $currencies = $this->stripeService->getAllowedCurrencies();

        $this->assertIsArray($currencies);
        $this->assertContains('eur', $currencies);
        $this->assertContains('usd', $currencies);
        $this->assertGreaterThan(0, count($currencies));
    }

    public function testFormatAmount()
    {
        // Test amount formatting for different currencies
        $this->assertEquals('€29.99', $this->stripeService->formatAmount(2999, 'eur'));
        $this->assertEquals('$29.99', $this->stripeService->formatAmount(2999, 'usd'));
        $this->assertEquals('£29.99', $this->stripeService->formatAmount(2999, 'gbp'));
    }

    public function testCalculateTaxAmount()
    {
        $subtotal = 10000; // €100.00 in cents
        $taxRate = 0.21; // 21% BTW

        $taxAmount = $this->stripeService->calculateTax($subtotal, $taxRate);

        $this->assertEquals(2100, $taxAmount); // €21.00 in cents
    }

    public function testGenerateUniqueSessionId()
    {
        $sessionId1 = $this->stripeService->generateMockSessionId();
        $sessionId2 = $this->stripeService->generateMockSessionId();

        $this->assertStringStartsWith('cs_test_mock_', $sessionId1);
        $this->assertStringStartsWith('cs_test_mock_', $sessionId2);
        $this->assertNotEquals($sessionId1, $sessionId2); // Should be unique
    }
} 