# 🧪 Coverage Setup Guide voor SlimmerMetAI

## PHP Coverage Driver Installatie (Windows/XAMPP)

### Optie 1: Xdebug Installatie (Aanbevolen voor development)

1. **Download Xdebug voor PHP 8.0:**
   ```bash
   # Ga naar: https://xdebug.org/download
   # Download: php_xdebug-3.3.1-8.0-vs16-x86_64.dll voor PHP 8.0 x64
   ```

2. **Installeer Xdebug:**
   ```bash
   # Kopieer het bestand naar: C:\xampp\php\ext\
   # Hernoem naar: php_xdebug.dll
   ```

3. **Configureer php.ini:**
   ```ini
   # Voeg toe aan C:\xampp\php\php.ini
   [XDebug]
   zend_extension=php_xdebug.dll
   xdebug.mode=coverage
   xdebug.start_with_request=yes
   xdebug.coverage_enable=1
   ```

4. **Herstart Apache:**
   ```bash
   # In XAMPP Control Panel: Stop en Start Apache
   ```

5. **Verificatie:**
   ```bash
   php -m | findstr xdebug
   ```

### Optie 2: PCOV Installatie (Lichter voor CI/CD)

```bash
# Voor Windows met pre-compiled DLL:
# Download van: https://windows.php.net/downloads/pecl/releases/pcov/
# Plaats in C:\xampp\php\ext\

# Voeg toe aan php.ini:
extension=pcov
pcov.enabled=1
```

### Quick Test na Installatie

```bash
# Test of coverage werkt:
composer run test:coverage

# Als succesvol, zou je dit moeten zien:
# ✅ HTML report: coverage/html/index.html
# ✅ Clover XML: coverage/clover.xml
```

## Integration Tests Setup

### Frontend Integration Tests

Ik ga echte module imports toevoegen in plaats van alleen mocks:

```javascript
// tests/js/integration/cart.integration.test.js
import { describe, it, expect, beforeEach } from 'vitest'
import '../../../resources/js/cart/cart.js'  // Echte module import

describe('Cart Integration Tests', () => {
  beforeEach(() => {
    // Setup DOM en localStorage
    document.body.innerHTML = '<div id="cart-count">0</div>'
    localStorage.clear()
  })

  it('echte cart module moet laden en functioneren', () => {
    // Test echte cart functionaliteit
    expect(window.Cart).toBeDefined()
    expect(typeof window.Cart.addItem).toBe('function')
  })
})
```

## Controller Tests Setup

### AuthController Tests

```php
<?php
// tests/Feature/AuthControllerTest.php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Http\Controller\Api\AuthController;
use GuzzleHttp\Psr7\ServerRequest;

class AuthControllerTest extends TestCase
{
    private AuthController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock dependencies setup
        $this->controller = new AuthController(
            $this->createMock(\App\Application\Service\AuthService::class)
        );
    }

    public function testLoginValidation()
    {
        $request = new ServerRequest('POST', '/api/auth/login');
        $request = $request->withParsedBody([
            'email' => '',
            'password' => ''
        ]);

        $response = $this->controller->login($request);
        
        $this->assertEquals(422, $response->getStatusCode());
    }
}
```

## Repository Tests Setup

```php
<?php
// tests/Unit/AuthRepositoryTest.php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Infrastructure\Repository\AuthRepository;

class AuthRepositoryTest extends TestCase
{
    public function testUserCreation()
    {
        $mockDatabase = $this->createMock(\App\Infrastructure\Database\Database::class);
        $repository = new AuthRepository($mockDatabase);
        
        // Test repository methods
        $this->assertTrue(method_exists($repository, 'createUser'));
    }
}
```

## End-to-End Tests Setup

```php
<?php
// tests/E2E/FullWorkflowTest.php

namespace Tests\E2E;

use PHPUnit\Framework\TestCase;

class FullWorkflowTest extends TestCase
{
    public function testCompleteUserRegistrationFlow()
    {
        // Test volledige workflow van registratie tot dashboard
        $this->markTestSkipped('Vereist test database setup');
    }
}
```

## Coverage Targets Configuratie

### PHPUnit Coverage Targets

```xml
<!-- phpunit.xml -->
<coverage processUncoveredFiles="true">
    <include>
        <directory suffix=".php">src</directory>
    </include>
    <report>
        <clover outputFile="coverage/clover.xml"/>
        <html outputDirectory="coverage/html"/>
        <text outputFile="coverage/coverage.txt"/>
    </report>
    <!-- Coverage Targets -->
    <whitelist>
        <directory suffix=".php">src/</directory>
    </whitelist>
</coverage>
```

### Vitest Coverage Targets

```javascript
// vitest.config.js
export default {
  test: {
    coverage: {
      provider: 'v8',
      reporter: ['text', 'html', 'clover'],
      include: ['resources/js/**/*.js'],
      exclude: ['tests/**', 'node_modules/**'],
      thresholds: {
        global: {
          branches: 80,
          functions: 80,
          lines: 80,
          statements: 80
        }
      }
    }
  }
}
```

## Coverage Scripts Update

### Composer Scripts

```json
{
  "scripts": {
    "test:coverage": "phpunit --coverage-html coverage/html --coverage-clover coverage/clover.xml --coverage-text=coverage/coverage.txt",
    "test:coverage-check": "phpunit --coverage-text --coverage-clover coverage/clover.xml && php scripts/check-coverage-threshold.php",
    "test:unit": "phpunit --testsuite=Unit",
    "test:feature": "phpunit --testsuite=Feature",
    "test:e2e": "phpunit --testsuite=E2E"
  }
}
```

### NPM Scripts

```json
{
  "scripts": {
    "test:coverage": "vitest run --coverage",
    "test:coverage-threshold": "vitest run --coverage --coverage.thresholds.lines=80",
    "test:integration": "vitest run tests/js/integration/",
    "test:unit": "vitest run tests/js/unit/",
    "test:watch": "vitest"
  }
}
```

## Troubleshooting

### Xdebug Niet Werkend?

```bash
# Check PHP configuratie:
php --ini

# Check of Xdebug geladen is:
php -m | findstr xdebug

# Check Xdebug versie:
php -v
```

### Coverage Rapport Leeg?

1. Controleer of coverage driver werkt
2. Verificeer include/exclude paths
3. Check file permissions op coverage/ directory

### CI/CD Integratie

```yaml
# .github/workflows/coverage.yml
- name: Install Xdebug
  run: |
    sudo apt-get update
    sudo apt-get install php-xdebug
    
- name: Run Coverage
  run: |
    composer run test:coverage
    npm run test:coverage
    
- name: Upload Coverage
  uses: codecov/codecov-action@v3
  with:
    file: coverage/clover.xml
```

## Volgende Stappen

1. ✅ Installeer Xdebug/PCOV
2. ✅ Voer coverage tests uit
3. ✅ Bekijk coverage rapporten
4. ✅ Implementeer ontbrekende tests
5. ✅ Stel coverage targets in
6. ✅ Integreer in CI/CD pipeline 