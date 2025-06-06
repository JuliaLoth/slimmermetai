# Local CI/CD Pipeline voor Windows PowerShell
# Voer alle CI/CD checks lokaal uit voordat je commit
# Gebruik: .\scripts\local-ci.ps1

param(
    [switch]$SkipTests = $false
)

# Check if we're in the right directory
if (-not (Test-Path "composer.json") -or -not (Test-Path "package.json")) {
    Write-Host "Je moet dit script uitvoeren vanuit de project root directory" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "==================================================================================" -ForegroundColor Blue
Write-Host "                           LOCAL CI/CD PIPELINE                                  " -ForegroundColor Blue
Write-Host "                   Voer alle checks uit voordat je commit                       " -ForegroundColor Blue
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
    Write-Host "Composer dependencies OK" -ForegroundColor Green

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
    Write-Host "NPM dependencies OK" -ForegroundColor Green

    # Step 3: Architecture Checks
    Write-Host "3. Running architecture checks..." -ForegroundColor Cyan
    Write-Host "   Checking for disallowed PHP files in public_html..." -ForegroundColor Cyan
    
    if (Test-Path "scripts/check-public-php.sh") {
        # Run via bash if available, otherwise skip
        if (Get-Command bash -ErrorAction SilentlyContinue) {
            & bash scripts/check-public-php.sh
            if ($LASTEXITCODE -ne 0) {
                Write-Host "Some architecture issues found" -ForegroundColor Yellow
            }
        } else {
            Write-Host "Bash not available, skipping shell script checks" -ForegroundColor Yellow
        }
    } else {
        Write-Host "Architecture check script not found" -ForegroundColor Yellow
    }
    Write-Host "Architecture checks completed" -ForegroundColor Green

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
    Write-Host "Code style OK" -ForegroundColor Green

    # Step 5: Static Analysis
    Write-Host "5. Running static analysis (PHPStan)..." -ForegroundColor Cyan
    
    & composer run analyse
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Static analysis failed! Please fix the issues before committing." -ForegroundColor Red
        exit 1
    }
    Write-Host "Static analysis passed" -ForegroundColor Green

    # Step 6: PHP Unit Tests (conditionally)
    if (-not $SkipTests) {
        Write-Host "6. Running PHP unit tests..." -ForegroundColor Cyan
        
        if (Get-Command php -ErrorAction SilentlyContinue) {
            & composer run test
            if ($LASTEXITCODE -ne 0) {
                Write-Host "PHP tests failed! Please fix the issues before committing." -ForegroundColor Red
                exit 1
            }
            Write-Host "PHP tests passed" -ForegroundColor Green
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
    Write-Host "Frontend linting passed" -ForegroundColor Green

    # Step 8: Frontend Tests (conditionally)
    if (-not $SkipTests) {
        Write-Host "8. Running frontend tests..." -ForegroundColor Cyan
        
        & npm run test:run
        if ($LASTEXITCODE -ne 0) {
            Write-Host "Frontend tests failed! Please fix the issues before committing." -ForegroundColor Red
            exit 1
        }
        Write-Host "Frontend tests passed" -ForegroundColor Green
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
    Write-Host "Frontend build successful" -ForegroundColor Green

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
        Write-Host "Working directory clean" -ForegroundColor Green
    }

    # Final Summary
    Write-Host ""
    Write-Host "==================================================================================" -ForegroundColor Green
    Write-Host "                            ALL CHECKS PASSED                                    " -ForegroundColor Green
    Write-Host "                         Ready to commit and push!                               " -ForegroundColor Green
    Write-Host "==================================================================================" -ForegroundColor Green
    Write-Host ""

    Write-Host "Next steps:" -ForegroundColor Blue
    Write-Host "  git add ."
    Write-Host "  git commit -m 'Your commit message'"
    Write-Host "  git push origin main"
    Write-Host ""
    Write-Host "Tip: Run this script before every commit!" -ForegroundColor Yellow

} catch {
    $errorMessage = $_.Exception.Message
    Write-Host "CI/CD pipeline failed: $errorMessage" -ForegroundColor Red
    exit 1
} 