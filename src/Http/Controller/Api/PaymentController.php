<?php

declare(strict_types=1);

namespace App\Http\Controller\Api;

use App\Domain\Repository\PaymentRepositoryInterface;
use App\Infrastructure\Database\DatabasePerformanceMonitor;
use App\Http\Response\ApiResponse;
use App\Http\Middleware\AuthenticationMiddleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class PaymentController
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private ?DatabasePerformanceMonitor $performanceMonitor = null
    ) {
    }

    public function handle(RequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        // Parse the path to determine the action
        $pathParts = explode('/', trim($path, '/'));
        $action = $pathParts[2] ?? 'index'; // /api/payments/{action}

        try {
            // Route to appropriate method
            return match ($action) {
                'create' => $this->createPaymentSession($request),
                'status' => $this->getPaymentStatus($request),
                'history' => $this->getPaymentHistory($request),
                'analytics' => $this->getPaymentAnalytics($request),
                'webhook' => $this->processWebhook($request),
                'refund' => $this->createRefund($request),
                default => ApiResponse::error('Invalid payment action', 404)
            };
        } catch (\Throwable $e) {
            $this->performanceMonitor?->logQuery([
                'query' => 'Payment API Error',
                'action' => $action,
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error('Payment operation failed', 500);
        }
    }

    private function createPaymentSession(RequestInterface $request): ResponseInterface
    {
        $body = json_decode($request->getBody()->getContents(), true);

        if (!isset($body['user_id'], $body['items'], $body['total_amount'])) {
            return ApiResponse::error('Missing required fields: user_id, items, total_amount', 400);
        }

        $sessionId = $this->paymentRepository->createPaymentSession(
            $body['user_id'],
            $body['items'],
            $body['total_amount'],
            $body['currency'] ?? 'EUR'
        );

        return ApiResponse::success([
            'session_id' => $sessionId,
            'message' => 'Payment session created successfully'
        ]);
    }

    private function getPaymentStatus(RequestInterface $request): ResponseInterface
    {
        $query = $request->getUri()->getQuery();
        parse_str($query, $params);

        if (!isset($params['session_id'])) {
            return ApiResponse::error('Missing session_id parameter', 400);
        }

        $payment = $this->paymentRepository->findPaymentBySessionId($params['session_id']);

        if (!$payment) {
            return ApiResponse::error('Payment session not found', 404);
        }

        return ApiResponse::success([
            'session_id' => $payment->getId(),
            'status' => $payment->getStatus(),
            'payment_status' => $payment->getPaymentStatus(),
            'amount' => $payment->getAmountTotal(),
            'currency' => $payment->getCurrency()
        ]);
    }

    private function getPaymentHistory(RequestInterface $request): ResponseInterface
    {
        $query = $request->getUri()->getQuery();
        parse_str($query, $params);

        if (!isset($params['user_id'])) {
            return ApiResponse::error('Missing user_id parameter', 400);
        }

        $limit = (int)($params['limit'] ?? 10);
        $history = $this->paymentRepository->getUserPaymentHistory((int)$params['user_id'], $limit);

        return ApiResponse::success([
            'payments' => $history,
            'count' => count($history)
        ]);
    }

    private function getPaymentAnalytics(RequestInterface $request): ResponseInterface
    {
        $query = $request->getUri()->getQuery();
        parse_str($query, $params);

        $fromDate = isset($params['from']) ? new \DateTime($params['from']) : new \DateTime('-30 days');
        $toDate = isset($params['to']) ? new \DateTime($params['to']) : new \DateTime();

        $analytics = $this->paymentRepository->getPaymentAnalytics($fromDate, $toDate);
        $revenue = $this->paymentRepository->getTotalRevenue($fromDate, $toDate);
        $trends = $this->paymentRepository->getPaymentTrends($params['period'] ?? 'month');

        return ApiResponse::success([
            'period' => [
                'from' => $fromDate->format('Y-m-d'),
                'to' => $toDate->format('Y-m-d')
            ],
            'analytics' => $analytics,
            'revenue' => $revenue,
            'trends' => $trends
        ]);
    }

    private function processWebhook(RequestInterface $request): ResponseInterface
    {
        $body = json_decode($request->getBody()->getContents(), true);

        if (!$body) {
            return ApiResponse::error('Invalid webhook payload', 400);
        }

        $processed = $this->paymentRepository->processWebhookPayment($body);

        if (!$processed) {
            return ApiResponse::error('Failed to process webhook', 500);
        }

        return ApiResponse::success([
            'message' => 'Webhook processed successfully',
            'event_id' => $body['id'] ?? null
        ]);
    }

    private function createRefund(RequestInterface $request): ResponseInterface
    {
        $body = json_decode($request->getBody()->getContents(), true);

        if (!isset($body['session_id'], $body['amount'], $body['reason'])) {
            return ApiResponse::error('Missing required fields: session_id, amount, reason', 400);
        }

        $refundCreated = $this->paymentRepository->createRefund(
            $body['session_id'],
            $body['amount'],
            $body['reason']
        );

        if (!$refundCreated) {
            return ApiResponse::error('Failed to create refund', 500);
        }

        return ApiResponse::success([
            'message' => 'Refund created successfully',
            'session_id' => $body['session_id']
        ]);
    }
}
