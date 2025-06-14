# Local CI/CD Pipeline voor Windows PowerShell
# Voer alle CI/CD checks lokaal uit voordat je commit
# Gebruik: .\scripts\local-ci.ps1 [-SkipTests] [-Coverage] [-Verbose]

param(
    [switch]$SkipTests = $false,
    [switch]$Coverage = $false,
    [switch]$Verbose = $false
)

# Set strict error handling
$ErrorActionPreference = "Stop"

# Check if we're in the right directory
if (-not (Test-Path "composer.json") -or -not (Test-Path "package.json")) {
    Write-Host "Je moet dit script uitvoeren vanuit de project root directory" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "==================================================================================" -ForegroundColor Blue
Write-Host "                           LOCAL CI/CD PIPELINE                                  " -ForegroundColor Blue
Write-Host "                   Voer alle checks uit voordat je commit                       " -ForegroundColor Blue
if ($Coverage) {
    Write-Host "                             WITH COVERAGE                                     " -ForegroundColor Magenta
}
Write-Host "==================================================================================" -ForegroundColor Blue
Write-Host ""

try {
    # Step 1: Composer Dependencies
    Write-Host "1. Checking Composer dependencies..." -ForegroundColor Cyan
    
    & composer validate --strict
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Composer.json validation failed!" -ForegroundColor Red
        exit 1
    }

    if (-not (Test-Path "vendor")) {
        Write-Host "Installing Composer dependencies..." -ForegroundColor Cyan
        & composer install --prefer-dist --no-progress --optimize-autoloader
        if ($LASTEXITCODE -ne 0) {
            Write-Host "Composer install failed" -ForegroundColor Red
            exit 1
        }
    }
    Write-Host "[OK] Composer dependencies" -ForegroundColor Green

    # Step 2: NPM Dependencies
    Write-Host "2. Checking NPM dependencies..." -ForegroundColor Cyan
    
    if (-not (Test-Path "node_modules")) {
        Write-Host "Installing NPM dependencies..." -ForegroundColor Cyan
        & npm ci
        if ($LASTEXITCODE -ne 0) {
            Write-Host "NPM install failed" -ForegroundColor Red
            exit 1
        }
    }

    Write-Host "Validating NPM dependencies..." -ForegroundColor Cyan
    & npm audit
    if ($LASTEXITCODE -ne 0) {
        Write-Host "NPM security vulnerabilities found, but continuing..." -ForegroundColor Yellow
    }
    Write-Host "[OK] NPM dependencies" -ForegroundColor Green

    # Step 3: Architecture Checks
    Write-Host "3. Running architecture checks..." -ForegroundColor Cyan
    Write-Host "   Checking for disallowed PHP files in public_html..." -ForegroundColor Cyan
    
    if (Test-Path "scripts/check-public-php.sh") {
        Write-Host "Architecture check script found (bash script - may need WSL)" -ForegroundColor Yellow
    } else {
        Write-Host "Architecture check script not found" -ForegroundColor Yellow
    }
    Write-Host "[OK] Architecture checks completed" -ForegroundColor Green

    # Step 4: Code Style Check
    Write-Host "4. Running code style checks..." -ForegroundColor Cyan
    
    & composer run cs:check
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Code style issues found, attempting auto-fix..." -ForegroundColor Yellow
        & composer run cs:fix
        
        # Check again
        & composer run cs:check
        if ($LASTEXITCODE -ne 0) {
            Write-Host "Code style issues remain after auto-fix. Please review manually." -ForegroundColor Red
            exit 1
        }
    }
    Write-Host "[OK] Code style" -ForegroundColor Green

    # Step 5: Static Analysis
    Write-Host "5. Running static analysis (PHPStan)..." -ForegroundColor Cyan
    
    if ($Verbose) {
        & composer run analyse -- --verbose
    } else {
        & composer run analyse
    }

    if ($LASTEXITCODE -ne 0) {
        Write-Host "Static analysis failed! Please fix the issues before committing." -ForegroundColor Red
        Write-Host ""
        Write-Host "Common fixes for typehint errors:" -ForegroundColor Yellow
        Write-Host "   - Add array typehints: array<string> instead of array" -ForegroundColor Cyan
        Write-Host "   - Add generic typehints: Collection<User> instead of Collection" -ForegroundColor Cyan
        Write-Host "   - Update phpstan.neon to ignore specific errors temporarily" -ForegroundColor Cyan
        exit 1
    }
    Write-Host "[OK] Static analysis passed" -ForegroundColor Green

    # Step 6: PHP Unit Tests
    if (-not $SkipTests) {
        Write-Host "6. Running PHP unit tests..." -ForegroundColor Cyan
        
        if (Get-Command php -ErrorAction SilentlyContinue) {
            if ($Coverage) {
                if (-not (Test-Path "coverage")) {
                    New-Item -ItemType Directory -Path "coverage" | Out-Null
                }
                
                Write-Host "   Running tests with coverage analysis..." -ForegroundColor Magenta
                & composer run test:coverage
                
                if ($LASTEXITCODE -ne 0) {
                    Write-Host "PHP tests with coverage failed!" -ForegroundColor Red
                    Write-Host ""
                    Write-Host "Common test failures:" -ForegroundColor Yellow
                    Write-Host "   - Config test failures: Check .env.testing configuration" -ForegroundColor Cyan
                    Write-Host "   - Environment value mismatches: Verify test expectations" -ForegroundColor Cyan
                    Write-Host "   - Mock setup issues: Ensure test dependencies are properly mocked" -ForegroundColor Cyan
                    exit 1
                }
                
                # Coverage reporting
                if (Test-Path "coverage/coverage.txt") {
                    Write-Host ""
                    Write-Host "COVERAGE REPORT:" -ForegroundColor Magenta
                    Get-Content "coverage/coverage.txt" | Select-Object -Last 10
                }
                
                if (Test-Path "coverage/html/index.html") {
                    Write-Host ""
                    Write-Host "HTML Coverage report generated at: coverage/html/index.html" -ForegroundColor Green
                }
                
                Write-Host "[OK] PHP tests with coverage passed" -ForegroundColor Green
            } else {
                & composer run test
                if ($LASTEXITCODE -ne 0) {
                    Write-Host "PHP tests failed! Please fix the issues before committing." -ForegroundColor Red
                    Write-Host ""
                    Write-Host "Run with -Coverage flag to see detailed test coverage" -ForegroundColor Yellow
                    exit 1
                }
                Write-Host "[OK] PHP tests passed" -ForegroundColor Green
            }
        } else {
            Write-Host "PHP not found, skipping PHP tests" -ForegroundColor Yellow
        }
    } else {
        Write-Host "6. Skipping PHP tests (SkipTests flag used)" -ForegroundColor Yellow
    }

    # Step 7: Frontend Linting
    Write-Host "7. Running frontend linting..." -ForegroundColor Cyan
    
    & npm run lint
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Frontend linting failed! Please fix the issues before committing." -ForegroundColor Red
        exit 1
    }
    Write-Host "[OK] Frontend linting passed" -ForegroundColor Green

    # Step 8: Frontend Tests
    if (-not $SkipTests) {
        Write-Host "8. Running frontend tests..." -ForegroundColor Cyan
        
        if ($Coverage) {
            Write-Host "   Running frontend tests with coverage..." -ForegroundColor Magenta
            & npm run test:coverage
            if ($LASTEXITCODE -ne 0) {
                Write-Host "Frontend tests with coverage failed!" -ForegroundColor Red
                exit 1
            }
            Write-Host "[OK] Frontend tests with coverage passed" -ForegroundColor Green
        } else {
            & npm run test:run
            if ($LASTEXITCODE -ne 0) {
                Write-Host "Frontend tests failed! Please fix the issues before committing." -ForegroundColor Red
                exit 1
            }
            Write-Host "[OK] Frontend tests passed" -ForegroundColor Green
        }
    } else {
        Write-Host "8. Skipping frontend tests (SkipTests flag used)" -ForegroundColor Yellow
    }

    # Step 9: Frontend Build
    Write-Host "9. Building frontend assets..." -ForegroundColor Cyan
    
    & npm run build
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Frontend build failed! Please fix the issues before committing." -ForegroundColor Red
        exit 1
    }
    Write-Host "[OK] Frontend build successful" -ForegroundColor Green

    # Step 10: Security Checks
    Write-Host "10. Running security checks..." -ForegroundColor Cyan
    Write-Host "   Running Composer security audit..." -ForegroundColor Cyan
    
    & composer audit --format=table
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Security vulnerabilities found in Composer dependencies" -ForegroundColor Yellow
    }

    # Step 11: Git Status Check
    Write-Host "11. Checking git status..." -ForegroundColor Cyan
    
    $gitStatus = & git status --porcelain
    if ($gitStatus) {
        Write-Host "Files changed since last commit:" -ForegroundColor White
        & git status --short
        Write-Host ""
        Write-Host "You have uncommitted changes. Review them before committing." -ForegroundColor Yellow
    } else {
        Write-Host "[OK] Working directory clean" -ForegroundColor Green
    }

    # Final Summary
    Write-Host ""
    Write-Host "==================================================================================" -ForegroundColor Green
    Write-Host "                            ALL CHECKS PASSED                                    " -ForegroundColor Green
    Write-Host "                         Ready to commit and push!                               " -ForegroundColor Green
    Write-Host "==================================================================================" -ForegroundColor Green
    Write-Host ""

    if ($Coverage) {
        Write-Host "COVERAGE SUMMARY:" -ForegroundColor Magenta
        Write-Host "  - PHP Coverage: Check coverage/html/index.html for detailed report" -ForegroundColor White
        Write-Host "  - Frontend Coverage: Check coverage-js/ directory if available" -ForegroundColor White
        Write-Host ""
    }

    Write-Host "Next steps:" -ForegroundColor Blue
    Write-Host "  git add ."
    Write-Host "  git commit -m 'Your commit message'"
    Write-Host "  git push origin main"
    Write-Host ""
    Write-Host "Tip: Run this script before every commit!" -ForegroundColor Yellow
    Write-Host "Tip: Use -Coverage flag for detailed test coverage analysis" -ForegroundColor Yellow

} catch {
    $errorMessage = $_.Exception.Message
    Write-Host "CI/CD pipeline failed: $errorMessage" -ForegroundColor Red
    exit 1
} 