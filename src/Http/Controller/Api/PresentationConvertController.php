<?php

namespace App\Http\Controller\Api;

use App\Application\Service\PresentationConvertService;
use App\Http\Response\ApiResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class PresentationConvertController
{
    public function __construct(private PresentationConvertService $service)
    {
    }

    public function convert(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS') {
            return ApiResponse::success(['allow' => 'POST, OPTIONS']);
        }
        if ($request->getMethod() !== 'POST') {
            return ApiResponse::error('Alleen POST toegestaan', 405);
        }
        $data = json_decode((string)$request->getBody(), true);
        if (!isset($data['reactCode']) || !is_string($data['reactCode'])) {
            return ApiResponse::validationError(['reactCode' => 'reactCode (string) is verplicht']);
        }
        try {
            $result = $this->service->convert($data['reactCode']);
            return ApiResponse::success($result, 'Presentatie succesvol gegenereerd', 201);
        } catch (\Throwable $e) {
            return ApiResponse::serverError('Fout tijdens genereren presentatie', $e->getMessage());
        }
    }
}
