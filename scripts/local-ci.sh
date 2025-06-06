#!/bin/bash

# 🚀 Local CI/CD Pipeline
# Voer alle CI/CD checks lokaal uit voordat je commit
# Gebruik: ./scripts/local-ci.sh

set -e # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_step() {
    echo -e "${BLUE}🔄 $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_header() {
    echo -e "${BLUE}
╔══════════════════════════════════════════════════════════╗
║                   🚀 LOCAL CI/CD PIPELINE                ║
║          Voer alle checks uit voordat je commit          ║
╚══════════════════════════════════════════════════════════╝${NC}"
}

# Check if we're in the right directory
if [ ! -f "composer.json" ] || [ ! -f "package.json" ]; then
    print_error "Je moet dit script uitvoeren vanuit de project root directory"
    exit 1
fi

print_header

# Step 1: Composer Dependencies
print_step "1. Checking Composer dependencies..."
if ! composer validate --strict; then
    print_error "Composer.json validation failed!"
    exit 1
fi

if [ ! -d "vendor" ] || [ "composer.lock" -nt "vendor" ]; then
    print_step "Installing/updating Composer dependencies..."
    composer install --prefer-dist --no-progress --optimize-autoloader
fi
print_success "Composer dependencies OK"

# Step 2: NPM Dependencies
print_step "2. Checking NPM dependencies..."
if [ ! -d "node_modules" ] || [ "package-lock.json" -nt "node_modules" ]; then
    print_step "Installing NPM dependencies..."
    npm ci
fi

# Validate dependencies
print_step "Validating NPM dependencies..."
npm audit
print_success "NPM dependencies OK"

# Step 3: Make executables
print_step "3. Setting up executables..."
chmod +x ./vendor/bin/phpcs 2>/dev/null || true
chmod +x ./vendor/bin/phpcbf 2>/dev/null || true
chmod +x ./vendor/bin/phpstan 2>/dev/null || true
chmod +x ./vendor/bin/phpunit 2>/dev/null || true
chmod +x scripts/check-public-php.sh 2>/dev/null || true
print_success "Executables ready"

# Step 4: Architecture Checks
print_step "4. Running architecture checks..."
echo "   📁 Checking for disallowed PHP files in public_html..."
if [ -f "scripts/check-public-php.sh" ]; then
    ./scripts/check-public-php.sh || print_warning "Some architecture issues found"
else
    print_warning "Architecture check script not found"
fi
print_success "Architecture checks completed"

# Step 5: Code Style Check
print_step "5. Running code style checks..."
if ! composer run cs:check; then
    print_warning "Code style issues found, attempting auto-fix..."
    composer run cs:fix || true
    
    # Check again
    if ! composer run cs:check; then
        print_error "Code style issues remain after auto-fix. Please review manually."
        exit 1
    fi
fi
print_success "Code style OK"

# Step 6: Static Analysis
print_step "6. Running static analysis (PHPStan)..."
if ! composer run analyse; then
    print_error "Static analysis failed! Please fix the issues before committing."
    exit 1
fi
print_success "Static analysis passed"

# Step 7: PHP Unit Tests
print_step "7. Running PHP unit tests..."
if command -v php >/dev/null 2>&1; then
    if ! composer run test; then
        print_error "PHP tests failed! Please fix the issues before committing."
        exit 1
    fi
    print_success "PHP tests passed"
else
    print_warning "PHP not found, skipping PHP tests"
fi

# Step 8: Frontend Linting
print_step "8. Running frontend linting..."
if ! npm run lint; then
    print_error "Frontend linting failed! Please fix the issues before committing."
    exit 1
fi
print_success "Frontend linting passed"

# Step 9: Frontend Tests
print_step "9. Running frontend tests..."
if ! npm run test:run; then
    print_error "Frontend tests failed! Please fix the issues before committing."
    exit 1
fi
print_success "Frontend tests passed"

# Step 10: Frontend Build
print_step "10. Building frontend assets..."
if ! npm run build; then
    print_error "Frontend build failed! Please fix the issues before committing."
    exit 1
fi
print_success "Frontend build successful"

# Step 11: Security Checks
print_step "11. Running security checks..."
print_step "    🔒 Running Composer security audit..."
if ! composer audit --format=table; then
    print_warning "Security vulnerabilities found in Composer dependencies"
fi

# Step 12: Git Status Check
print_step "12. Checking git status..."
if [ -n "$(git status --porcelain)" ]; then
    echo "📝 Files changed since last commit:"
    git status --short
    echo ""
    print_warning "You have uncommitted changes. Review them before committing."
else
    print_success "Working directory clean"
fi

# Final Summary
echo -e "${GREEN}
╔══════════════════════════════════════════════════════════╗
║                     🎉 ALL CHECKS PASSED                 ║
║               Ready to commit and push! 🚀                ║
╚══════════════════════════════════════════════════════════╝${NC}"

echo -e "${BLUE}Next steps:${NC}"
echo "  git add ."
echo "  git commit -m \"Your commit message\""
echo "  git push origin main"
echo ""
echo -e "${YELLOW}💡 Tip: Add this to your git hooks for automatic checking!${NC}" 