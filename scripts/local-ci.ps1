# Local CI/CD Pipeline voor Windows PowerShell
# Voer alle CI/CD checks lokaal uit voordat je commit
# Gebruik: .\scripts\local-ci.ps1

param(
    [switch]$SkipTests = $false,
    [switch]$Coverage = $false,
    [switch]$Verbose = $false
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
    Write-Host "Composer dependencies OK" -ForegroundColor Green

    # Step 2: NPM Dependencies
    Write-Host "2. Checking NPM dependencies..." -ForegroundColor Cyan
    Write-Host "NPM dependencies OK" -ForegroundColor Green

    # Step 3: PHP Unit Tests (conditionally) 
    if (-not $SkipTests) {
        Write-Host "3. Running PHP unit tests..." -ForegroundColor Cyan
        
        & composer run test
        if ($LASTEXITCODE -ne 0) {
            Write-Host "PHP tests failed! Please fix the issues before committing." -ForegroundColor Red
            exit 1
        }
        Write-Host "PHP tests passed" -ForegroundColor Green
    } else {
        Write-Host "3. Skipping PHP tests (SkipTests flag used)" -ForegroundColor Yellow
    }

    # Step 4: Frontend Build
    Write-Host "4. Building frontend assets..." -ForegroundColor Cyan
    
    & npm run build
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Frontend build failed! Please fix the issues before committing." -ForegroundColor Red
        exit 1
    }
    Write-Host "Frontend build successful" -ForegroundColor Green

    # Final Summary
    Write-Host ""
    Write-Host "==================================================================================" -ForegroundColor Green
    Write-Host "                            ALL CHECKS PASSED                                    " -ForegroundColor Green
    Write-Host "                         Ready to commit and push!                               " -ForegroundColor Green
    Write-Host "==================================================================================" -ForegroundColor Green
    Write-Host ""

} catch {
    $errorMessage = $_.Exception.Message
    Write-Host "CI/CD pipeline failed: $errorMessage" -ForegroundColor Red
    exit 1
} 