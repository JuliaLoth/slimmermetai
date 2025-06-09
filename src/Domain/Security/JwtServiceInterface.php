<?php

namespace App\Domain\Security;

interface JwtServiceInterface
{
    public function generate(array $payload, ?int $exp = null): string;
    
    public function generateToken(array $payload, ?int $exp = null): string;
    
    public function verify(string $token): ?array;
    
    public function validateToken(string $token): ?array;
} 