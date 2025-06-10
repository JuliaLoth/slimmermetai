<?php

namespace Tests\Integration;

use App\Application\Service\StripeService;
use App\Infrastructure\Config\Config;
use App\Infrastructure\Logging\ErrorLogger;
use App\Infrastructure\Repository\StripeSessionRepository;

/**
 * StripeService Integration Tests
 * 
 * Test echte Stripe service operaties met mock/test mode
 */
class StripeServiceIntegrationTest extends BaseIntegrationTest
{
    private StripeService $stripeService;
    private Config $config;
    private ErrorLogger $errorLogger;
    private StripeSessionRepository $sessionRepository;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test database with adapter for proper Database interface
        $testDb = new \Tests\Support\TestDatabase($this->getTestDatabase());
        $database = new \Tests\Support\DatabaseAdapter($testDb);
        
        // Create mock config for Stripe testing
        $config = $this->createMockConfigWithValues([
            'STRIPE_SECRET_KEY' => 'sk_test_mock_stripe_key_for_testing',
            'STRIPE_PUBLISHABLE_KEY' => 'pk_test_mock_stripe_key_for_testing',
            'STRIPE_WEBHOOK_SECRET' => 'whsec_test_webhook_secret_for_testing',
            'APP_ENV' => 'testing' // Force test mode
        ]);
        
        // Create repository with proper Database interface
        $sessionRepository = new StripeSessionRepository($database);
        
        // Create StripeService with all dependencies
        $this->stripeService = new StripeService($config, $sessionRepository);
    }
    
    /**
     * Test checkout session creatie in test mode
     */
    public function testCreateCheckoutSessionInTestMode(): void
    {
        // ARRANGE: Test line items
        $lineItems = [
            [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => ['name' => 'Test Product'],
                    'unit_amount' => 2999 // €29.99
                ],
                'quantity' => 1
            ]
        ];
        
        $options = [
            'success_url' => 'https://test.com/success',
            'cancel_url' => 'https://test.com/cancel'
        ];
        
        // ACT: Create checkout session
        $result = $this->stripeService->createCheckoutSession($lineItems, $options);
        
        // ASSERT: Session created successfully (mock mode)
        $this->assertIsArray($result, 'Create checkout session moet array returnen');
        $this->assertArrayHasKey('session', $result, 'Result moet session key hebben');
        $this->assertArrayHasKey('success', $result, 'Result moet success key hebben');
        
        if ($result['success']) {
            $session = $result['session'];
            $this->assertArrayHasKey('id', $session, 'Session moet id hebben');
            $this->assertArrayHasKey('url', $session, 'Session moet url hebben');
            
            // In test mode, should be mock session
            $this->assertStringStartsWith('cs_test_mock_', $session['id'], 'Test mode moet mock session ID gebruiken');
        }
    }
    
    /**
     * Test payment intent creatie
     */
    public function testCreatePaymentIntentInTestMode(): void
    {
        // ARRANGE: Payment intent data
        $amount = 2999; // €29.99
        $currency = 'eur';
        $metadata = [
            'product_id' => 'test-product-123',
            'user_id' => $this->getTestFixture('user_id')
        ];
        
        // ACT: Create payment intent
        $result = $this->stripeService->createPaymentIntent($amount, $currency, $metadata);
        
        // ASSERT: Payment intent created successfully
        $this->assertIsArray($result, 'Create payment intent moet array returnen');
        $this->assertArrayHasKey('success', $result, 'Result moet success key hebben');
        
        if ($result['success']) {
            $this->assertArrayHasKey('client_secret', $result, 'Result moet client_secret hebben');
            $this->assertArrayHasKey('payment_intent_id', $result, 'Result moet payment_intent_id hebben');
            
            // In test mode, should be mock payment intent
            $this->assertStringStartsWith('pi_test_mock_', $result['payment_intent_id'], 'Test mode moet mock payment intent ID gebruiken');
        }
    }
    
    /**
     * Test payment status ophalen
     */
    public function testGetPaymentStatus(): void
    {
        // ARRANGE: Test payment intent ID
        $paymentIntentId = 'pi_test_mock_' . uniqid();
        
        // ACT: Get payment status
        $status = $this->stripeService->getPaymentStatus($paymentIntentId);
        
        // ASSERT: Status returned
        $this->assertIsArray($status, 'Payment status moet array returnen');
        $this->assertArrayHasKey('status', $status, 'Status moet status key hebben');
        $this->assertArrayHasKey('amount', $status, 'Status moet amount key hebben');
        
        // In test mode, should return mock status
        $validStatuses = ['succeeded', 'pending', 'failed', 'canceled'];
        $this->assertContains($status['status'], $validStatuses, 'Status moet geldige waarde hebben');
    }
    
    /**
     * Test webhook handling
     */
    public function testWebhookHandling(): void
    {
        // ARRANGE: Mock webhook payload
        $webhookPayload = [
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_mock_' . uniqid(),
                    'amount' => 2999,
                    'currency' => 'eur',
                    'status' => 'succeeded',
                    'metadata' => [
                        'user_id' => $this->getTestFixture('user_id'),
                        'product_id' => 'test-product'
                    ]
                ]
            ]
        ];
        
        $webhookSignature = 'mock_signature_for_testing';
        
        // ACT: Handle webhook
        $result = $this->stripeService->handleWebhook($webhookPayload, $webhookSignature);
        
        // ASSERT: Webhook handled successfully
        $this->assertIsArray($result, 'Webhook handling moet array returnen');
        $this->assertArrayHasKey('success', $result, 'Result moet success key hebben');
        $this->assertArrayHasKey('message', $result, 'Result moet message key hebben');
        
        // In test mode, webhook should be processed
        $this->assertTrue($result['success'], 'Webhook handling moet succesvol zijn in test mode');
    }
    
    /**
     * Test error handling bij ongeldige data
     */
    public function testErrorHandlingWithInvalidData(): void
    {
        // Test verschillende error scenarios
        $errorScenarios = [
            'empty_line_items' => [
                'line_items' => [],
                'options' => ['success_url' => 'test.com', 'cancel_url' => 'test.com']
            ],
            'invalid_amount' => [
                'amount' => -100,
                'currency' => 'eur'
            ],
            'missing_currency' => [
                'amount' => 1000,
                'currency' => ''
            ]
        ];
        
        foreach ($errorScenarios as $scenarioName => $data) {
            if ($scenarioName === 'empty_line_items') {
                // ACT: Try to create session with empty line items
                $result = $this->stripeService->createCheckoutSession($data['line_items'], $data['options']);
                
                // ASSERT: Should handle error gracefully
                $this->assertIsArray($result, "Scenario '$scenarioName' moet array returnen");
                $this->assertArrayHasKey('success', $result, "Scenario '$scenarioName' moet success key hebben");
                
                if (!$result['success']) {
                    $this->assertArrayHasKey('error', $result, "Scenario '$scenarioName' moet error key hebben bij failure");
                }
            } elseif ($scenarioName === 'invalid_amount' || $scenarioName === 'missing_currency') {
                // ACT: Try to create payment intent with invalid data
                $result = $this->stripeService->createPaymentIntent($data['amount'], $data['currency']);
                
                // ASSERT: Should handle error gracefully
                $this->assertIsArray($result, "Scenario '$scenarioName' moet array returnen");
                $this->assertArrayHasKey('success', $result, "Scenario '$scenarioName' moet success key hebben");
            }
        }
    }
    
    /**
     * Test session repository integratie
     */
    public function testSessionRepositoryIntegration(): void
    {
        // ARRANGE: Test session data
        $sessionData = [
            'stripe_session_id' => 'cs_test_mock_' . uniqid(),
            'user_id' => $this->getTestFixture('user_id'),
            'amount' => 2999,
            'currency' => 'eur',
            'status' => 'pending',
            'metadata' => json_encode(['product_id' => 'test-product'])
        ];
        
        // ACT: Save session via repository
        $sessionId = $this->sessionRepository->save($sessionData);
        
        // ASSERT: Session saved successfully
        $this->assertIsInt($sessionId, 'Session repository moet session ID returnen');
        $this->assertGreaterThan(0, $sessionId, 'Session ID moet positief zijn');
        
        // ACT: Retrieve session
        $retrievedSession = $this->sessionRepository->findByStripeSessionId($sessionData['stripe_session_id']);
        
        // ASSERT: Session retrieved correctly
        $this->assertIsArray($retrievedSession, 'Retrieved session moet array zijn');
        $this->assertEquals($sessionData['stripe_session_id'], $retrievedSession['stripe_session_id']);
        $this->assertEquals($sessionData['user_id'], $retrievedSession['user_id']);
        $this->assertEquals($sessionData['amount'], $retrievedSession['amount']);
        $this->assertEquals($sessionData['status'], $retrievedSession['status']);
        
        // ACT: Update session status
        $updateResult = $this->sessionRepository->updateStatus($sessionData['stripe_session_id'], 'completed');
        
        // ASSERT: Status updated
        $this->assertTrue($updateResult, 'Session status update moet succesvol zijn');
        
        // Verify update
        $updatedSession = $this->sessionRepository->findByStripeSessionId($sessionData['stripe_session_id']);
        $this->assertEquals('completed', $updatedSession['status'], 'Session status moet bijgewerkt zijn');
    }
    
    /**
     * Test refund functionaliteit
     */
    public function testRefundFunctionality(): void
    {
        // ARRANGE: Test payment intent for refund
        $paymentIntentId = 'pi_test_mock_' . uniqid();
        $refundAmount = 1500; // Partial refund
        $reason = 'requested_by_customer';
        
        // ACT: Create refund
        $result = $this->stripeService->createRefund($paymentIntentId, $refundAmount, $reason);
        
        // ASSERT: Refund created successfully
        $this->assertIsArray($result, 'Create refund moet array returnen');
        $this->assertArrayHasKey('success', $result, 'Result moet success key hebben');
        
        if ($result['success']) {
            $this->assertArrayHasKey('refund_id', $result, 'Result moet refund_id hebben');
            $this->assertArrayHasKey('amount', $result, 'Result moet amount hebben');
            $this->assertArrayHasKey('status', $result, 'Result moet status hebben');
            
            // In test mode, should be mock refund
            $this->assertStringStartsWith('re_test_mock_', $result['refund_id'], 'Test mode moet mock refund ID gebruiken');
            $this->assertEquals($refundAmount, $result['amount'], 'Refund amount moet matchen');
        }
    }
    
    /**
     * Test customer management
     */
    public function testCustomerManagement(): void
    {
        // ARRANGE: Test customer data
        $customerData = [
            'email' => $this->getTestFixture('user_email'),
            'name' => $this->getTestFixture('user_name'),
            'metadata' => [
                'user_id' => $this->getTestFixture('user_id')
            ]
        ];
        
        // ACT: Create customer
        $createResult = $this->stripeService->createCustomer($customerData);
        
        // ASSERT: Customer created successfully
        $this->assertIsArray($createResult, 'Create customer moet array returnen');
        $this->assertArrayHasKey('success', $createResult, 'Result moet success key hebben');
        
        if ($createResult['success']) {
            $this->assertArrayHasKey('customer_id', $createResult, 'Result moet customer_id hebben');
            
            $customerId = $createResult['customer_id'];
            $this->assertStringStartsWith('cus_test_mock_', $customerId, 'Test mode moet mock customer ID gebruiken');
            
            // ACT: Update customer
            $updateData = ['name' => 'Updated Test User'];
            $updateResult = $this->stripeService->updateCustomer($customerId, $updateData);
            
            // ASSERT: Customer updated successfully
            $this->assertIsArray($updateResult, 'Update customer moet array returnen');
            $this->assertArrayHasKey('success', $updateResult, 'Update result moet success key hebben');
            
            if ($updateResult['success']) {
                $this->assertArrayHasKey('customer', $updateResult, 'Update result moet customer data hebben');
            }
        }
    }
} 