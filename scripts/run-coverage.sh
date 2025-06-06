#!/bin/bash
# Standalone Coverage Runner
# Voert alleen coverage tests uit zonder alle CI/CD checks
# Gebruik: ./scripts/run-coverage.sh [--php-only] [--frontend-only] [--open]

# Parse command line arguments
PHP_ONLY=false
FRONTEND_ONLY=false
OPEN=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --php-only)
            PHP_ONLY=true
            shift
            ;;
        --frontend-only)
            FRONTEND_ONLY=true
            shift
            ;;
        --open)
            OPEN=true
            shift
            ;;
        *)
            echo "Unknown option: $1"
            echo "Usage: $0 [--php-only] [--frontend-only] [--open]"
            exit 1
            ;;
    esac
done

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
echo -e "${MAGENTA}🧪 COVERAGE ANALYSIS RUNNER 🧪${NC}"
echo -e "${MAGENTA}===============================${NC}"
echo ""

# Ensure directories exist
mkdir -p coverage

# PHP Coverage
if [[ "$FRONTEND_ONLY" != "true" ]]; then
    echo -e "${CYAN}📊 Running PHP Coverage Analysis...${NC}"
    
    composer run test:coverage
    if [[ $? -ne 0 ]]; then
        echo -e "${RED}❌ PHP coverage analysis failed!${NC}"
        echo ""
        echo -e "${YELLOW}💡 Common fixes:${NC}"
        echo -e "${BLUE}   - Check that all tests pass first: composer run test${NC}"
        echo -e "${BLUE}   - Verify PHPUnit configuration in phpunit.xml${NC}"
        echo -e "${BLUE}   - Ensure coverage directories are writable${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✅ PHP coverage analysis completed!${NC}"
    
    # Show coverage summary
    if [[ -f "coverage/coverage.txt" ]]; then
        echo ""
        echo -e "${MAGENTA}📈 PHP COVERAGE SUMMARY:${NC}"
        tail -n 15 coverage/coverage.txt
    fi
    
    if [[ -f "coverage/html/index.html" ]]; then
        echo ""
        echo -e "${GREEN}🌐 Detailed HTML report: coverage/html/index.html${NC}"
        
        if [[ "$OPEN" == "true" ]]; then
            echo -e "${CYAN}Opening HTML report in browser...${NC}"
            if command -v xdg-open &> /dev/null; then
                xdg-open coverage/html/index.html
            elif command -v open &> /dev/null; then
                open coverage/html/index.html
            else
                echo -e "${YELLOW}Cannot auto-open browser. Please open coverage/html/index.html manually${NC}"
            fi
        fi
    fi
fi

# Frontend Coverage  
if [[ "$PHP_ONLY" != "true" ]]; then
    echo ""
    echo -e "${CYAN}📊 Running Frontend Coverage Analysis...${NC}"
    
    npm run test:coverage
    if [[ $? -ne 0 ]]; then
        echo -e "${RED}❌ Frontend coverage analysis failed!${NC}"
        echo ""
        echo -e "${YELLOW}💡 Common fixes:${NC}"
        echo -e "${BLUE}   - Check that frontend tests pass first: npm run test:run${NC}"
        echo -e "${BLUE}   - Verify Vitest configuration in vitest.config.js${NC}"
        echo -e "${BLUE}   - Install dependencies: npm ci${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✅ Frontend coverage analysis completed!${NC}"
    
    # Check for frontend coverage files
    if [[ -f "coverage/index.html" ]]; then
        echo ""
        echo -e "${GREEN}🌐 Frontend HTML report: coverage/index.html${NC}"
        
        if [[ "$OPEN" == "true" ]]; then
            echo -e "${CYAN}Opening frontend HTML report in browser...${NC}"
            if command -v xdg-open &> /dev/null; then
                xdg-open coverage/index.html
            elif command -v open &> /dev/null; then
                open coverage/index.html
            else
                echo -e "${YELLOW}Cannot auto-open browser. Please open coverage/index.html manually${NC}"
            fi
        fi
    fi
fi

# Final Summary
echo ""
echo -e "${GREEN}🎉 COVERAGE ANALYSIS COMPLETE!${NC}"
echo -e "${GREEN}===============================${NC}"
echo ""

echo -e "${BLUE}📁 Generated Files:${NC}"
if [[ -f "coverage/html/index.html" ]]; then
    echo -e "${WHITE}   📊 PHP Coverage: coverage/html/index.html${NC}"
fi
if [[ -f "coverage/clover.xml" ]]; then
    echo -e "${WHITE}   📄 PHP Clover: coverage/clover.xml${NC}"
fi
if [[ -f "coverage/index.html" ]]; then
    echo -e "${WHITE}   📊 Frontend Coverage: coverage/index.html${NC}"
fi

echo ""
echo -e "${YELLOW}💡 Tips:${NC}"
echo -e "${CYAN}   - Use --open flag to automatically open reports in browser${NC}"
echo -e "${CYAN}   - Use --php-only or --frontend-only to run specific coverage${NC}"
echo -e "${CYAN}   - Integrate this into your CI/CD with: ./scripts/local-ci.sh --coverage${NC}" 