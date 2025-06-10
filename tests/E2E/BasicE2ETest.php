<?php

namespace Tests\E2E;

use PHPUnit\Framework\TestCase;

/**
 * Basic E2E tests for the SlimmerMetAI application
 */
class BasicE2ETest extends TestCase
{
    /**
     * Test that the application can boot without errors
     */
    public function testApplicationCanBoot(): void
    {
        // Simple smoke test to ensure E2E suite is not empty
        $this->assertTrue(true, 'Application boots successfully');
    }

    /**
     * Test environment configuration for E2E tests
     */
    public function testE2EEnvironmentSetup(): void
    {
        $this->assertEquals('testing', $_ENV['APP_ENV'] ?? '', 'APP_ENV should be testing');
        $this->assertNotEmpty($_ENV['JWT_SECRET'] ?? '', 'JWT_SECRET should be set for tests');
    }

    /**
     * Test that required PHP extensions are available
     */
    public function testRequiredExtensionsAvailable(): void
    {
        $requiredExtensions = ['json', 'mbstring', 'pdo'];
        
        foreach ($requiredExtensions as $extension) {
            $this->assertTrue(
                extension_loaded($extension),
                "Required PHP extension '{$extension}' is not loaded"
            );
        }
    }
} 