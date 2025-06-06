<?php
namespace App\Http\Controller\Api;

use App\Application\Service\PresentationConvertService;
use App\Infrastructure\Http\ApiResponse;
use Psr\Http\Message\ServerRequestInterface;

final class PresentationConvertController
{
    public function __construct(private PresentationConvertService $service) {}

    public function convert(ServerRequestInterface $request): void
    {
        if ($request->getMethod()==='OPTIONS') {
            ApiResponse::success(['allow'=>'POST, OPTIONS']);
        }
        if ($request->getMethod()!=='POST') {
            ApiResponse::methodNotAllowed('Alleen POST toegestaan', ['POST']);
        }
        $data = json_decode((string)$request->getBody(), true);
        if (!isset($data['reactCode']) || !is_string($data['reactCode'])) {
            ApiResponse::validationError(['reactCode'=>'reactCode (string) is verplicht']);
        }
        try {
            $result = $this->service->convert($data['reactCode']);
            ApiResponse::success($result, 'Presentatie succesvol gegenereerd', 201);
        } catch (\Throwable $e) {
            ApiResponse::serverError('Fout tijdens genereren presentatie', $e->getMessage());
        }
    }
} 