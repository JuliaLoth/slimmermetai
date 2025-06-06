#!/bin/bash
# Local CI/CD Pipeline voor Unix/Linux/macOS
# Voer alle CI/CD checks lokaal uit voordat je commit
# Gebruik: ./scripts/local-ci.sh [--skip-tests] [--coverage] [--verbose]

# Parse command line arguments
SKIP_TESTS=false
COVERAGE=false
VERBOSE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --skip-tests)
            SKIP_TESTS=true
            shift
            ;;
        --coverage)
            COVERAGE=true
            shift
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        *)
            echo "Unknown option: $1"
            echo "Usage: $0 [--skip-tests] [--coverage] [--verbose]"
            exit 1
            ;;
    esac
done

set -e # Exit on any error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [[ ! -f "composer.json" ]] || [[ ! -f "package.json" ]]; then
    echo -e "${RED}Je moet dit script uitvoeren vanuit de project root directory${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}==================================================================================${NC}"
echo -e "${BLUE}                           LOCAL CI/CD PIPELINE                                  ${NC}"
echo -e "${BLUE}                   Voer alle checks uit voordat je commit                       ${NC}"
if [[ "$COVERAGE" == "true" ]]; then
    echo -e "${MAGENTA}                           🧪 WITH COVERAGE 🧪                                  ${NC}"
fi
echo -e "${BLUE}==================================================================================${NC}"
echo ""

# Step 1: Composer Dependencies
echo -e "${CYAN}1. Checking Composer dependencies...${NC}"

composer validate --strict
if [[ $? -ne 0 ]]; then
    echo -e "${RED}Composer.json validation failed!${NC}"
    exit 1
fi

if [[ ! -d "vendor" ]]; then
    echo -e "${CYAN}Installing Composer dependencies...${NC}"
    composer install --prefer-dist --no-progress --optimize-autoloader
    if [[ $? -ne 0 ]]; then
        echo -e "${RED}Composer install failed${NC}"
        exit 1
    fi
fi
echo -e "${GREEN}Composer dependencies OK${NC}"

# Step 2: NPM Dependencies
echo -e "${CYAN}2. Checking NPM dependencies...${NC}"

if [[ ! -d "node_modules" ]]; then
    echo -e "${CYAN}Installing NPM dependencies...${NC}"
    npm ci
    if [[ $? -ne 0 ]]; then
        echo -e "${RED}NPM install failed${NC}"
        exit 1
    fi
fi

echo -e "${CYAN}Validating NPM dependencies...${NC}"
npm audit
if [[ $? -ne 0 ]]; then
    echo -e "${YELLOW}NPM security vulnerabilities found, but continuing...${NC}"
fi
echo -e "${GREEN}NPM dependencies OK${NC}"

# Step 3: Architecture Checks
echo -e "${CYAN}3. Running architecture checks...${NC}"
echo -e "${CYAN}   Checking for disallowed PHP files in public_html...${NC}"

if [[ -f "scripts/check-public-php.sh" ]]; then
    bash scripts/check-public-php.sh
    if [[ $? -ne 0 ]]; then
        echo -e "${YELLOW}Some architecture issues found${NC}"
    fi
else
    echo -e "${YELLOW}Architecture check script not found${NC}"
fi
echo -e "${GREEN}Architecture checks completed${NC}"

# Step 4: Code Style Check
echo -e "${CYAN}4. Running code style checks...${NC}"

composer run cs:check
if [[ $? -ne 0 ]]; then
    echo -e "${YELLOW}Code style issues found, attempting auto-fix...${NC}"
    composer run cs:fix
    
    # Check again
    composer run cs:check
    if [[ $? -ne 0 ]]; then
        echo -e "${RED}Code style issues remain after auto-fix. Please review manually.${NC}"
        exit 1
    fi
fi
echo -e "${GREEN}Code style OK${NC}"

# Step 5: Static Analysis
echo -e "${CYAN}5. Running static analysis (PHPStan)...${NC}"

if [[ "$VERBOSE" == "true" ]]; then
    composer run analyse -- --verbose
else
    composer run analyse
fi

if [[ $? -ne 0 ]]; then
    echo -e "${RED}Static analysis failed! Please fix the issues before committing.${NC}"
    echo ""
    echo -e "${YELLOW}💡 Common fixes for typehint errors:${NC}"
    echo -e "${CYAN}   - Add array typehints: array<string> instead of array${NC}"
    echo -e "${CYAN}   - Add generic typehints: Collection<User> instead of Collection${NC}"
    echo -e "${CYAN}   - Update phpstan.neon to ignore specific errors temporarily${NC}"
    exit 1
fi
echo -e "${GREEN}Static analysis passed${NC}"

# Step 6: PHP Unit Tests
if [[ "$SKIP_TESTS" != "true" ]]; then
    echo -e "${CYAN}6. Running PHP unit tests...${NC}"
    
    if command -v php &> /dev/null; then
        # Ensure coverage directory exists
        if [[ "$COVERAGE" == "true" ]]; then
            mkdir -p coverage
            
            echo -e "${MAGENTA}   📊 Running tests with coverage analysis...${NC}"
            composer run test:coverage
            
            if [[ $? -ne 0 ]]; then
                echo -e "${RED}PHP tests with coverage failed!${NC}"
                echo ""
                echo -e "${YELLOW}💡 Common test failures:${NC}"
                echo -e "${CYAN}   - Config test failures: Check .env.testing configuration${NC}"
                echo -e "${CYAN}   - Environment value mismatches: Verify test expectations${NC}"
                echo -e "${CYAN}   - Mock setup issues: Ensure test dependencies are properly mocked${NC}"
                exit 1
            fi
            
            # Coverage reporting
            if [[ -f "coverage/coverage.txt" ]]; then
                echo ""
                echo -e "${MAGENTA}📈 COVERAGE REPORT:${NC}"
                tail -n 10 coverage/coverage.txt
            fi
            
            if [[ -f "coverage/html/index.html" ]]; then
                echo ""
                echo -e "${GREEN}🌐 HTML Coverage report generated at: coverage/html/index.html${NC}"
            fi
            
            echo -e "${GREEN}PHP tests with coverage passed${NC}"
        else
            composer run test
            if [[ $? -ne 0 ]]; then
                echo -e "${RED}PHP tests failed! Please fix the issues before committing.${NC}"
                echo ""
                echo -e "${YELLOW}💡 Run with --coverage flag to see detailed test coverage${NC}"
                exit 1
            fi
            echo -e "${GREEN}PHP tests passed${NC}"
        fi
    else
        echo -e "${YELLOW}PHP not found, skipping PHP tests${NC}"
    fi
else
    echo -e "${YELLOW}6. Skipping PHP tests (skip-tests flag used)${NC}"
fi

# Step 7: Frontend Linting
echo -e "${CYAN}7. Running frontend linting...${NC}"

npm run lint
if [[ $? -ne 0 ]]; then
    echo -e "${RED}Frontend linting failed! Please fix the issues before committing.${NC}"
    exit 1
fi
echo -e "${GREEN}Frontend linting passed${NC}"

# Step 8: Frontend Tests
if [[ "$SKIP_TESTS" != "true" ]]; then
    echo -e "${CYAN}8. Running frontend tests...${NC}"
    
    if [[ "$COVERAGE" == "true" ]]; then
        echo -e "${MAGENTA}   📊 Running frontend tests with coverage...${NC}"
        npm run test:coverage
        if [[ $? -ne 0 ]]; then
            echo -e "${RED}Frontend tests with coverage failed!${NC}"
            exit 1
        fi
        echo -e "${GREEN}Frontend tests with coverage passed${NC}"
    else
        npm run test:run
        if [[ $? -ne 0 ]]; then
            echo -e "${RED}Frontend tests failed! Please fix the issues before committing.${NC}"
            exit 1
        fi
        echo -e "${GREEN}Frontend tests passed${NC}"
    fi
else
    echo -e "${YELLOW}8. Skipping frontend tests (skip-tests flag used)${NC}"
fi

# Step 9: Frontend Build
echo -e "${CYAN}9. Building frontend assets...${NC}"

npm run build
if [[ $? -ne 0 ]]; then
    echo -e "${RED}Frontend build failed! Please fix the issues before committing.${NC}"
    exit 1
fi
echo -e "${GREEN}Frontend build successful${NC}"

# Step 10: Security Checks
echo -e "${CYAN}10. Running security checks...${NC}"
echo -e "${CYAN}   Running Composer security audit...${NC}"

composer audit --format=table
if [[ $? -ne 0 ]]; then
    echo -e "${YELLOW}Security vulnerabilities found in Composer dependencies${NC}"
fi

# Step 11: Git Status Check
echo -e "${CYAN}11. Checking git status...${NC}"

if [[ -n $(git status --porcelain) ]]; then
    echo -e "${WHITE}Files changed since last commit:${NC}"
    git status --short
    echo ""
    echo -e "${YELLOW}You have uncommitted changes. Review them before committing.${NC}"
else
    echo -e "${GREEN}Working directory clean${NC}"
fi

# Final Summary
echo ""
echo -e "${GREEN}==================================================================================${NC}"
echo -e "${GREEN}                            ALL CHECKS PASSED                                    ${NC}"
echo -e "${GREEN}                         Ready to commit and push!                               ${NC}"
echo -e "${GREEN}==================================================================================${NC}"
echo ""

if [[ "$COVERAGE" == "true" ]]; then
    echo -e "${MAGENTA}📊 COVERAGE SUMMARY:${NC}"
    echo -e "${WHITE}  - PHP Coverage: Check coverage/html/index.html for detailed report${NC}"
    echo -e "${WHITE}  - Frontend Coverage: Check coverage-js/ directory if available${NC}"
    echo ""
fi

echo -e "${BLUE}Next steps:${NC}"
echo "  git add ."
echo "  git commit -m 'Your commit message'"
echo "  git push origin main"
echo ""
echo -e "${YELLOW}Tip: Run this script before every commit!${NC}"
echo -e "${YELLOW}Tip: Use --coverage flag for detailed test coverage analysis${NC}" 