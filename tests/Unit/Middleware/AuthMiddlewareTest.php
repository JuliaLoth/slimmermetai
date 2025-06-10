<?php

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;

// DISABLED: JwtService is final and cannot be mocked
// Use AuthMiddlewareIntegrationTest instead
class AuthMiddlewareTest extends TestCase
{
    public function testDisabledBecauseJwtServiceIsFinal()
    {
        $this->markTestSkipped('JwtService is final and cannot be mocked - use AuthMiddlewareIntegrationTest instead');
    }
} 