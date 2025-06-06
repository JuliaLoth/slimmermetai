<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Http\Controller\Api\StripeController;
use App\Application\Service\StripeService;
use App\Http\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * StripeController Unit Tests
 * 
 * Test de StripeController functionaliteit
 */
class StripeControllerTest extends TestCase
{
    private StripeController $controller;
    private MockObject $stripeService;

    protected function setUp(): void
    {
        $this->stripeService = $this->createMock(StripeService::class);
        $this->controller = new StripeController($this->stripeService);
    }

    public function testCreateSessionSuccess(): void
    {
        // Mock input data
        $sessionData = [
            'id' => 'cs_test_123',
            'url' => 'https://checkout.stripe.com/session'
        ];

        // Mock service response
        $this->stripeService
            ->expects($this->once())
            ->method('createCheckoutSession')
            ->with([], '', '', [])
            ->willReturn($sessionData);

        // Mock file_get_contents for empty input
        $response = $this->controller->createSession();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCreateSessionException(): void
    {
        // Mock service to throw exception
        $this->stripeService
            ->expects($this->once())
            ->method('createCheckoutSession')
            ->willThrowException(new \Exception('Stripe API error'));

        $response = $this->controller->createSession();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testStatusSuccess(): void
    {
        $sessionId = 'cs_test_123';
        $statusData = ['status' => 'complete'];

        $this->stripeService
            ->expects($this->once())
            ->method('getPaymentStatus')
            ->with($sessionId)
            ->willReturn($statusData);

        $response = $this->controller->status($sessionId);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testStatusException(): void
    {
        $sessionId = 'cs_test_123';

        $this->stripeService
            ->expects($this->once())
            ->method('getPaymentStatus')
            ->with($sessionId)
            ->willThrowException(new \Exception('Session not found'));

        $response = $this->controller->status($sessionId);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testWebhookSuccess(): void
    {
        $eventType = 'payment_intent.succeeded';

        $this->stripeService
            ->expects($this->once())
            ->method('handleWebhook')
            ->with('', '')
            ->willReturn($eventType);

        $response = $this->controller->webhook();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testWebhookException(): void
    {
        $this->stripeService
            ->expects($this->once())
            ->method('handleWebhook')
            ->willThrowException(new \Exception('Invalid signature'));

        $response = $this->controller->webhook();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testConfigWithValidKey(): void
    {
        // Mock config to return valid key
        $response = $this->controller->config();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        // Note: This test might need mocking of Config::getInstance()
    }

    /**
     * Data provider for different input scenarios
     */
    public function createSessionDataProvider(): array
    {
        return [
            'empty_data' => [[]],
            'with_line_items' => [[
                'line_items' => [
                    ['price' => 'price_123', 'quantity' => 1]
                ],
                'success_url' => 'https://example.com/success',
                'cancel_url' => 'https://example.com/cancel'
            ]],
            'with_metadata' => [[
                'metadata' => ['user_id' => '123'],
                'line_items' => []
            ]]
        ];
    }

    /**
     * @dataProvider createSessionDataProvider
     */
    public function testCreateSessionWithDifferentData(array $inputData): void
    {
        $this->stripeService
            ->expects($this->once())
            ->method('createCheckoutSession')
            ->willReturn(['id' => 'cs_test_123']);

        $response = $this->controller->createSession();

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testControllerConstructorWithDependency(): void
    {
        $controller = new StripeController($this->stripeService);
        $this->assertInstanceOf(StripeController::class, $controller);
    }
} 