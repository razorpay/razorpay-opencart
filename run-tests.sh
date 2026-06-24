#!/bin/bash

# Razorpay OpenCart Extension - Test Runner Script
# This script helps you run tests easily

set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Razorpay OpenCart Extension Test Suite${NC}"
echo -e "${BLUE}========================================${NC}\n"

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo -e "${RED}Error: Composer is not installed${NC}"
    echo "Please install composer from https://getcomposer.org/"
    exit 1
fi

# Install dependencies if vendor directory doesn't exist
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}Installing dependencies...${NC}"
    composer install
    echo -e "${GREEN}✓ Dependencies installed${NC}\n"
fi

# Parse command line arguments
case "${1:-all}" in
    "all")
        echo -e "${BLUE}Running all tests...${NC}"
        ./vendor/bin/phpunit
        ;;
    "unit")
        echo -e "${BLUE}Running unit tests...${NC}"
        ./vendor/bin/phpunit --testsuite "Unit Tests"
        ;;
    "integration")
        echo -e "${BLUE}Running integration tests...${NC}"
        ./vendor/bin/phpunit --testsuite "Integration Tests"
        ;;
    "coverage")
        echo -e "${BLUE}Generating coverage report...${NC}"
        ./vendor/bin/phpunit --coverage-html tests/coverage
        echo -e "${GREEN}✓ Coverage report generated at tests/coverage/index.html${NC}"
        ;;
    "watch")
        echo -e "${BLUE}Watching for changes...${NC}"
        while true; do
            ./vendor/bin/phpunit
            echo -e "${YELLOW}Waiting for changes... (Ctrl+C to stop)${NC}"
            sleep 5
        done
        ;;
    "fix")
        echo -e "${BLUE}Fixing code style issues...${NC}"
        ./vendor/bin/phpcbf --standard=PSR12 admin catalog || true
        echo -e "${GREEN}✓ Code style fixed${NC}"
        ;;
    "check")
        echo -e "${BLUE}Running all checks...${NC}"
        echo -e "${YELLOW}1. Running PHPUnit tests...${NC}"
        ./vendor/bin/phpunit
        echo -e "${YELLOW}2. Running PHPStan analysis...${NC}"
        ./vendor/bin/phpstan analyse admin catalog --level=5 || true
        echo -e "${YELLOW}3. Checking code style...${NC}"
        ./vendor/bin/phpcs --standard=PSR12 admin catalog || true
        echo -e "${GREEN}✓ All checks completed${NC}"
        ;;
    "clean")
        echo -e "${YELLOW}Cleaning up...${NC}"
        rm -rf vendor/
        rm -rf tests/coverage/
        rm -rf tests/logs/
        rm -f .phpunit.result.cache
        echo -e "${GREEN}✓ Cleanup completed${NC}"
        ;;
    "help"|*)
        echo "Usage: ./run-tests.sh [command]"
        echo ""
        echo "Commands:"
        echo "  all         - Run all tests (default)"
        echo "  unit        - Run unit tests only"
        echo "  integration - Run integration tests only"
        echo "  coverage    - Generate HTML coverage report"
        echo "  watch       - Run tests on every change (continuous)"
        echo "  fix         - Fix code style issues"
        echo "  check       - Run all checks (tests + static analysis + code style)"
        echo "  clean       - Remove vendor and test artifacts"
        echo "  help        - Show this help message"
        echo ""
        echo "Examples:"
        echo "  ./run-tests.sh"
        echo "  ./run-tests.sh unit"
        echo "  ./run-tests.sh coverage"
        ;;
esac

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}Done!${NC}"
echo -e "${BLUE}========================================${NC}"
