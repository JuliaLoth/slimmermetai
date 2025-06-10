# CI/CD Pipeline met Tests
# Handige wrapper voor volledige CI/CD met unit tests
# Gebruik: .\scripts\ci-with-tests.ps1

Write-Host "🚀 Running FULL CI/CD Pipeline with Tests..." -ForegroundColor Green
Write-Host ""

# Set environment variable to run tests
$env:CI_FULL_TESTS = "1"

# Run the local CI pipeline
& .\scripts\local-ci.ps1

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "❌ CI/CD met tests gefaald!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "🎉 VOLLEDIGE CI/CD met tests succesvol!" -ForegroundColor Green
Write-Host "✅ Code style: OK" -ForegroundColor Green  
Write-Host "✅ Unit tests: PASSED" -ForegroundColor Green
Write-Host "✅ Frontend build: OK" -ForegroundColor Green
Write-Host "✅ Dependencies: OK" -ForegroundColor Green 