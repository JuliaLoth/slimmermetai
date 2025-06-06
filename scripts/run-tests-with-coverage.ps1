# SlimmerMetAI - Run Tests with Coverage
# Dit script voert alle tests uit en genereert coverage rapporten

param(
    [switch]$PhpOnly,
    [switch]$FrontendOnly,
    [switch]$Open,
    [switch]$Verbose
)

Write-Host "🧪 SlimmerMetAI - Running Tests with Coverage" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

# Check if Xdebug is installed
$xdebugInstalled = php -m | Select-String "xdebug"
if (-not $xdebugInstalled) {
    Write-Host "⚠️  WARNING: Xdebug is not installed - PHP coverage will not work" -ForegroundColor Yellow
    Write-Host "   Install Xdebug to get PHP code coverage reports" -ForegroundColor Yellow
    Write-Host ""
}

# Change to project root
Set-Location $PSScriptRoot\..

if (-not $FrontendOnly) {
    Write-Host "🔍 Running PHP Tests with Coverage..." -ForegroundColor Green
    
    # Ensure coverage directory exists
    if (-not (Test-Path "coverage")) {
        New-Item -ItemType Directory -Path "coverage" | Out-Null
    }
    
    # Run PHPUnit with coverage
    if ($Verbose) {
        php vendor/bin/phpunit --configuration phpunit.xml --coverage-html coverage/html --coverage-clover coverage/coverage.xml --verbose
    } else {
        php vendor/bin/phpunit --configuration phpunit.xml --coverage-html coverage/html --coverage-clover coverage/coverage.xml
    }
    
    $phpTestResult = $LASTEXITCODE
    
    if ($phpTestResult -eq 0) {
        Write-Host "✅ PHP Tests PASSED" -ForegroundColor Green
    } else {
        Write-Host "❌ PHP Tests FAILED" -ForegroundColor Red
    }
    
    # Show coverage summary if available
    if (Test-Path "coverage/coverage.xml") {
        Write-Host ""
        Write-Host "📊 PHP Coverage Summary:" -ForegroundColor Cyan
        
        # Parse coverage XML for basic stats
        [xml]$coverageXml = Get-Content "coverage/coverage.xml"
        $metrics = $coverageXml.coverage.project.metrics
        
        if ($metrics) {
            $totalLines = [int]$metrics.statements
            $coveredLines = [int]$metrics.coveredstatements
            $percentage = if ($totalLines -gt 0) { [math]::Round(($coveredLines / $totalLines) * 100, 2) } else { 0 }
            
            Write-Host "   Lines Covered: $coveredLines / $totalLines ($percentage%)" -ForegroundColor White
            
            if ($percentage -ge 80) {
                Write-Host "   🎯 Excellent coverage! (>= 80%)" -ForegroundColor Green
            } elseif ($percentage -ge 70) {
                Write-Host "   ✅ Good coverage (>= 70%)" -ForegroundColor Yellow
            } else {
                Write-Host "   ⚠️  Coverage below target (< 70%)" -ForegroundColor Red
            }
        }
    }
}

if (-not $PhpOnly) {
    Write-Host ""
    Write-Host "🔍 Running Frontend Tests with Coverage..." -ForegroundColor Green
    
    # Run frontend tests with coverage
    if ($Verbose) {
        npm run test:coverage -- --reporter=verbose
    } else {
        npm run test:coverage
    }
    
    $frontendTestResult = $LASTEXITCODE
    
    if ($frontendTestResult -eq 0) {
        Write-Host "✅ Frontend Tests PASSED" -ForegroundColor Green
    } else {
        Write-Host "❌ Frontend Tests FAILED" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "📁 Coverage Reports Generated:" -ForegroundColor Cyan

if (-not $FrontendOnly -and (Test-Path "coverage/html/index.html")) {
    Write-Host "   📄 PHP Coverage: coverage/html/index.html" -ForegroundColor White
}

if (-not $PhpOnly -and (Test-Path "coverage/index.html")) {
    Write-Host "   📄 Frontend Coverage: coverage/index.html" -ForegroundColor White
}

# Open coverage reports if requested
if ($Open) {
    Write-Host ""
    Write-Host "🌐 Opening Coverage Reports..." -ForegroundColor Green
    
    if (-not $FrontendOnly -and (Test-Path "coverage/html/index.html")) {
        Start-Process "coverage/html/index.html"
    }
    
    if (-not $PhpOnly -and (Test-Path "coverage/index.html")) {
        Start-Process "coverage/index.html"
    }
}

Write-Host ""

# Calculate overall exit code
$overallResult = 0
if (-not $FrontendOnly -and $phpTestResult -ne 0) {
    $overallResult = 1
}
if (-not $PhpOnly -and $frontendTestResult -ne 0) {
    $overallResult = 1
}

if ($overallResult -eq 0) {
    Write-Host "🎉 ALL TESTS PASSED!" -ForegroundColor Green
    Write-Host "Coverage reports are available in the coverage/ directory" -ForegroundColor Cyan
} else {
    Write-Host "💥 SOME TESTS FAILED!" -ForegroundColor Red
    Write-Host "Check the output above for details" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Usage Examples:" -ForegroundColor Cyan
Write-Host "  .\scripts\run-tests-with-coverage.ps1                 # Run all tests"
Write-Host "  .\scripts\run-tests-with-coverage.ps1 -PhpOnly        # PHP tests only"
Write-Host "  .\scripts\run-tests-with-coverage.ps1 -FrontendOnly   # Frontend tests only"
Write-Host "  .\scripts\run-tests-with-coverage.ps1 -Open           # Open reports in browser"
Write-Host "  .\scripts\run-tests-with-coverage.ps1 -Verbose        # Verbose output"

exit $overallResult 