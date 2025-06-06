<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Http\Controller\Api\StripeController;
use App\Application\Service\StripeService;
use GuzzleHttp\Psr7\ServerRequest;

class StripeControllerTest extends TestCase
{
    private StripeController $controller;
    private $mockStripeService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->mockStripeService = $this->createMock(StripeService::class);
        
        // Create controller with mocked dependencies
        $this->controller = new StripeController(
            $this->mockStripeService
        );
    }

    public function testCreateCheckoutSessionWithValidItems()
    {
        // Mock successful session creation
        $this->mockStripeService
            ->method('createCheckoutSession')
            ->willReturn([
                'id' => 'cs_test_mock_123456',
                'url' => 'https://checkout.stripe.com/pay/cs_test_mock_123456'
            ]);

        $response = $this->controller->createSession();

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('session', $body['data']);
        $this->assertEquals('cs_test_mock_123456', $body['data']['session']['id']);
    }

    public function testCreateCheckoutSessionWithException()
    {
        // Mock service to throw exception
        $this->mockStripeService
            ->method('createCheckoutSession')
            ->willThrowException(new \Exception('Stripe API error'));

        $response = $this->controller->createSession();

        $this->assertEquals(500, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Stripe API error', $body['message']);
    }

    public function testStatusSuccess()
    {
        $sessionId = 'cs_test_123';
        $statusData = ['status' => 'complete'];

        $this->mockStripeService
            ->method('getPaymentStatus')
            ->with($sessionId)
            ->willReturn($statusData);

        $response = $this->controller->status($sessionId);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('status', $body['data']);
        $this->assertEquals($statusData, $body['data']['status']);
    }

    public function testWebhookSuccess()
    {
        $eventType = 'payment_intent.succeeded';

        $this->mockStripeService
            ->method('handleWebhook')
            ->willReturn($eventType);

        $response = $this->controller->webhook();

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('data', $body);
        $this->assertTrue($body['data']['received']);
        $this->assertEquals($eventType, $body['data']['event']);
    }

    public function testWebhookException()
    {
        $this->mockStripeService
            ->method('handleWebhook')
            ->willThrowException(new \Exception('Invalid signature'));

        $response = $this->controller->webhook();

        $this->assertEquals(400, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Invalid signature', $body['message']);
    }

    public function testConfigSuccess()
    {
        // Ensure environment variable is set for this test
        putenv('STRIPE_PUBLIC_KEY=pk_test_fake_key_for_testing');
        $_ENV['STRIPE_PUBLIC_KEY'] = 'pk_test_fake_key_for_testing';
        $_SERVER['STRIPE_PUBLIC_KEY'] = 'pk_test_fake_key_for_testing';
        
        // Force a fresh Config instance to pick up the new environment variable
        $config = new \App\Infrastructure\Config\Config();
        $config->set('stripe_public_key', 'pk_test_fake_key_for_testing');
        container()->set(\App\Infrastructure\Config\Config::class, $config);
        
        $response = $this->controller->config();

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('publishableKey', $body['data']);
        $this->assertArrayHasKey('currency', $body['data']);
        $this->assertArrayHasKey('locale', $body['data']);
    }
} 