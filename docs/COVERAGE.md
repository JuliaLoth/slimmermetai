# 🧪 Coverage Analysis Guide

Deze guide legt uit hoe je coverage analysis kunt uitvoeren voor de SlimmerMetAI website.

## 📊 Wat is Coverage?

Coverage analysis laat zien welk deel van je code wordt getest door unit tests. Het helpt om:
- Ongeteste code te identificeren
- Test kwaliteit te verbeteren
- Kritieke bugs te voorkomen
- Code review proces te verbeteren

## 🚀 Quick Start

### Volledige Coverage (PHP + Frontend)
```bash
# PowerShell (Windows)
.\scripts\run-coverage.ps1 -Open

# Bash (Linux/macOS)
./scripts/run-coverage.sh --open
```

### Alleen PHP Coverage
```bash
# PowerShell
.\scripts\run-coverage.ps1 -PhpOnly -Open

# Bash  
./scripts/run-coverage.sh --php-only --open
```

### Coverage in CI/CD Pipeline
```bash
# PowerShell
.\scripts\local-ci.ps1 -Coverage

# Bash
./scripts/local-ci.sh --coverage
```

## 📁 Coverage Bestanden

Na het uitvoeren van coverage analysis vind je de rapporten in:

```
coverage/
├── html/
│   └── index.html          # 📊 PHP Coverage HTML rapport
├── clover.xml              # 📄 PHP Clover XML voor CI/CD
├── coverage.txt            # 📝 PHP Text summary
└── junit.xml               # 🧪 PHPUnit test resultaten
```

## 🛠️ Setup Vereisten

### PHP Coverage (Xdebug of PCOV)

**Optie 1: Xdebug (Aanbevolen voor development)**
```bash
# Via PECL
pecl install xdebug

# Via package manager (Ubuntu/Debian)
sudo apt-get install php-xdebug

# Via package manager (macOS met Homebrew)
brew install php@8.0-xdebug
```

**Optie 2: PCOV (Sneller voor CI/CD)**
```bash
pecl install pcov
```

### Frontend Coverage (Vitest)

Frontend coverage werkt out-of-the-box met Vitest en v8 coverage provider:
```bash
npm run test:coverage
```

## 📈 Coverage Interpreteren

### PHP Coverage Metrics

- **Line Coverage**: Percentage van uitgevoerde regels
- **Function Coverage**: Percentage van aangeroepen functies  
- **Branch Coverage**: Percentage van uitgevoerde code branches
- **Path Coverage**: Percentage van uitgevoerde code paden

### Coverage Doelen

| Type | Minimum | Goed | Excellent |
|------|---------|------|-----------|
| Line Coverage | 70% | 80% | 90%+ |
| Function Coverage | 80% | 90% | 95%+ |
| Branch Coverage | 60% | 75% | 85%+ |

### HTML Rapport Gebruik

1. Open `coverage/html/index.html` in browser
2. Klik op bestanden om details te zien
3. Rode regels = niet getest
4. Groene regels = getest
5. Gele regels = gedeeltelijk getest

## 🔧 Troubleshooting

### "No code coverage driver available"

**Probleem**: PHPUnit kan geen coverage driver vinden
**Oplossing**: Installeer Xdebug of PCOV (zie Setup sectie)

### Coverage rapporten niet gegenereerd

**Probleem**: Coverage directory bestaat niet of geen schrijfrechten
**Oplossing**: 
```bash
mkdir -p coverage
chmod 755 coverage
```

### Tests falen tijdens coverage

**Probleem**: Tests die normaal slagen, falen tijdens coverage
**Oplossing**: 
1. Controleer of alle dependencies geïnstalleerd zijn
2. Test eerst zonder coverage: `composer run test`
3. Controleer .env.testing configuratie

### Frontend coverage leeg

**Probleem**: Geen frontend test coverage
**Oplossing**:
1. Controleer Vitest configuratie in `vitest.config.js`
2. Zorg dat tests bestaan in `tests/js/`
3. Run tests eerst: `npm run test:run`

## 🎯 Coverage Best Practices

### 1. Focus op Kritieke Code
Prioriteer coverage voor:
- Business logic
- Security-gevoelige functies
- Data processing
- API endpoints

### 2. Schrijf Meaningvolle Tests
```php
// ❌ Slechte test - test implementation details
public function testConfigHasProperty()
{
    $this->assertTrue(property_exists($this->config, 'data'));
}

// ✅ Goede test - test behavior
public function testConfigCanRetrieveValues()
{
    $this->config->set('key', 'value');
    $this->assertEquals('value', $this->config->get('key'));
}
```

### 3. Test Edge Cases
```php
public function testConfigHandlesNullValues()
{
    $this->config->set('key', null);
    $this->assertNull($this->config->get('key'));
    $this->assertEquals('default', $this->config->get('key', 'default'));
}
```

### 4. Mock External Dependencies
```php
public function testEmailServiceSendsEmail()
{
    $mockMailer = $this->createMock(Mailer::class);
    $mockMailer->expects($this->once())
               ->method('send')
               ->willReturn(true);
    
    $emailService = new EmailService($mockMailer);
    $result = $emailService->sendWelcomeEmail('test@example.com');
    
    $this->assertTrue($result);
}
```

## 🔄 CI/CD Integratie

### GitHub Actions

Coverage wordt automatisch uitgevoerd in GitHub Actions:

```yaml
- name: Run PHP Tests with Coverage
  run: composer run test:coverage
  
- name: Upload Coverage to Codecov
  uses: codecov/codecov-action@v3
  with:
    file: ./coverage/clover.xml
```

### Pre-commit Hooks

Voeg coverage checks toe aan pre-commit:

```bash
# In .husky/pre-commit
if [[ "$CI" != "true" ]]; then
    ./scripts/local-ci.sh --coverage
fi
```

## 📚 Gerelateerde Documentatie

- [LOCAL_CI_CD.md](LOCAL_CI_CD.md) - Lokale CI/CD setup
- [TESTING.md](TESTING.md) - Test guidelines
- [PHPUnit Documentatie](https://phpunit.de/documentation.html)
- [Vitest Coverage](https://vitest.dev/guide/coverage.html)

## 💡 Tips & Tricks

### Quick Coverage Check
```bash
# Snelle coverage check zonder HTML
composer run test:coverage | grep "Lines:"
```

### Coverage voor Specifieke Bestanden
```bash
# Test alleen Config klasse
vendor/bin/phpunit tests/Unit/ConfigTest.php --coverage-text
```

### Coverage Verschillen Bekijken
```bash
# Vergelijk coverage tussen branches
git checkout main
composer run test:coverage
cp coverage/clover.xml coverage-main.xml

git checkout feature-branch  
composer run test:coverage
# Gebruik tools zoals coverage-diff om verschillen te zien
```

### Performance Tips
- Gebruik PCOV in plaats van Xdebug voor snellere coverage
- Run coverage alleen voor gewijzigde bestanden in CI
- Cache coverage rapporten tussen CI runs

## 🆘 Support

Voor vragen over coverage analysis:
1. Check deze documentatie
2. Bekijk bestaande tests in `tests/Unit/`
3. Run `.\scripts\run-coverage.ps1 -Verbose` voor debug info 