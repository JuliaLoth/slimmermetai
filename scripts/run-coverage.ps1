# Standalone Coverage Runner
# Voert alleen coverage tests uit zonder alle CI/CD checks
# Gebruik: .\scripts\run-coverage.ps1

param(
    [switch]$PhpOnly = $false,
    [switch]$FrontendOnly = $false,
    [switch]$Open = $false
)

# Colors
$Red = "Red"
$Green = "Green"
$Yellow = "Yellow"
$Blue = "Blue"
$Cyan = "Cyan"
$Magenta = "Magenta"

# Check if we're in the right directory
if (-not (Test-Path "composer.json") -or -not (Test-Path "package.json")) {
    Write-Host "Je moet dit script uitvoeren vanuit de project root directory" -ForegroundColor $Red
    exit 1
}

Write-Host ""
Write-Host "🧪 COVERAGE ANALYSIS RUNNER 🧪" -ForegroundColor $Magenta
Write-Host "===============================" -ForegroundColor $Magenta
Write-Host ""

try {
    # Ensure directories exist
    if (-not (Test-Path "coverage")) {
        New-Item -ItemType Directory -Path "coverage" -Force | Out-Null
    }

    # PHP Coverage
    if (-not $FrontendOnly) {
        Write-Host "📊 Running PHP Coverage Analysis..." -ForegroundColor $Cyan
        
        & composer run test:coverage
        if ($LASTEXITCODE -ne 0) {
            Write-Host "❌ PHP coverage analysis failed!" -ForegroundColor $Red
            Write-Host ""
            Write-Host "💡 Common fixes:" -ForegroundColor $Yellow
            Write-Host "   - Check that all tests pass first: composer run test" -ForegroundColor $Blue
            Write-Host "   - Verify PHPUnit configuration in phpunit.xml" -ForegroundColor $Blue
            Write-Host "   - Ensure coverage directories are writable" -ForegroundColor $Blue
            exit 1
        }
        
        Write-Host "✅ PHP coverage analysis completed!" -ForegroundColor $Green
        
        # Show coverage summary
        if (Test-Path "coverage/coverage.txt") {
            Write-Host ""
            Write-Host "📈 PHP COVERAGE SUMMARY:" -ForegroundColor $Magenta
            Get-Content "coverage/coverage.txt" | Select-Object -Last 15 | Write-Host -ForegroundColor White
        }
        
        if (Test-Path "coverage/html/index.html") {
            Write-Host ""
            Write-Host "🌐 Detailed HTML report: coverage/html/index.html" -ForegroundColor $Green
            
            if ($Open) {
                Write-Host "Opening HTML report in browser..." -ForegroundColor $Cyan
                Start-Process "coverage/html/index.html"
            }
        }
    }

    # Frontend Coverage  
    if (-not $PhpOnly) {
        Write-Host ""
        Write-Host "📊 Running Frontend Coverage Analysis..." -ForegroundColor $Cyan
        
        & npm run test:coverage
        if ($LASTEXITCODE -ne 0) {
            Write-Host "❌ Frontend coverage analysis failed!" -ForegroundColor $Red
            Write-Host ""
            Write-Host "💡 Common fixes:" -ForegroundColor $Yellow
            Write-Host "   - Check that frontend tests pass first: npm run test:run" -ForegroundColor $Blue
            Write-Host "   - Verify Vitest configuration in vitest.config.js" -ForegroundColor $Blue
            Write-Host "   - Install dependencies: npm ci" -ForegroundColor $Blue
            exit 1
        }
        
        Write-Host "✅ Frontend coverage analysis completed!" -ForegroundColor $Green
        
        # Check for frontend coverage files
        if (Test-Path "coverage/index.html") {
            Write-Host ""
            Write-Host "🌐 Frontend HTML report: coverage/index.html" -ForegroundColor $Green
            
            if ($Open) {
                Write-Host "Opening frontend HTML report in browser..." -ForegroundColor $Cyan
                Start-Process "coverage/index.html"
            }
        }
    }

    # Final Summary
    Write-Host ""
    Write-Host "🎉 COVERAGE ANALYSIS COMPLETE!" -ForegroundColor $Green
    Write-Host "===============================" -ForegroundColor $Green
    Write-Host ""
    
    Write-Host "📁 Generated Files:" -ForegroundColor $Blue
    if (Test-Path "coverage/html/index.html") {
        Write-Host "   📊 PHP Coverage: coverage/html/index.html" -ForegroundColor $White
    }
    if (Test-Path "coverage/clover.xml") {
        Write-Host "   📄 PHP Clover: coverage/clover.xml" -ForegroundColor $White  
    }
    if (Test-Path "coverage/index.html") {
        Write-Host "   📊 Frontend Coverage: coverage/index.html" -ForegroundColor $White
    }
    
    Write-Host ""
    Write-Host "💡 Tips:" -ForegroundColor $Yellow
    Write-Host "   - Use -Open flag to automatically open reports in browser" -ForegroundColor $Cyan
    Write-Host "   - Use -PhpOnly or -FrontendOnly to run specific coverage" -ForegroundColor $Cyan
    Write-Host "   - Integrate this into your CI/CD with: .\scripts\local-ci.ps1 -Coverage" -ForegroundColor $Cyan

} catch {
    $errorMessage = $_.Exception.Message
    Write-Host "Coverage analysis failed: $errorMessage" -ForegroundColor $Red
    exit 1
} 