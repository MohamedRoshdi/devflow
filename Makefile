.PHONY: help test-db-start test-db-stop test-db-reset test test-unit test-feature test-browser test-all test-coverage

# Default target
help:
	@echo "DevFlow Pro - Test Management"
	@echo ""
	@echo "Database Management:"
	@echo "  make test-db-start    - Start PostgreSQL test database"
	@echo "  make test-db-stop     - Stop PostgreSQL test database"
	@echo "  make test-db-reset    - Reset and migrate test database"
	@echo "  make test-db-shell    - Open PostgreSQL shell"
	@echo "  make test-db-logs     - Show PostgreSQL logs"
	@echo ""
	@echo "Testing:"
	@echo "  make test             - Run all tests"
	@echo "  make test-unit        - Run unit tests only"
	@echo "  make test-feature     - Run feature tests only"
	@echo "  make test-browser     - Run browser tests only"
	@echo "  make test-performance - Run performance tests only"
	@echo "  make test-security    - Run security tests only"
	@echo "  make test-coverage    - Run tests with coverage report"
	@echo ""
	@echo "Quick Workflows:"
	@echo "  make test-all         - Start DB, reset, run all tests"
	@echo "  make test-quick       - Run tests (assumes DB is running)"

# Database Management
test-db-start:
	@./run-tests.sh start

test-db-stop:
	@./run-tests.sh stop

test-db-reset:
	@./run-tests.sh reset

test-db-shell:
	@./run-tests.sh shell

test-db-logs:
	@./run-tests.sh logs

# Individual Test Suites
test:
	@./run-tests.sh test

test-unit:
	@./run-tests.sh unit

test-feature:
	@./run-tests.sh feature

test-browser:
	@./run-tests.sh browser

test-performance:
	@./run-tests.sh performance

test-security:
	@./run-tests.sh security

# Test with Coverage
test-coverage:
	@echo "Running tests with coverage report..."
	@./run-tests.sh start
	@vendor/bin/phpunit --coverage-html coverage-report
	@echo "Coverage report generated in coverage-report/"

# Complete Test Workflow
test-all:
	@echo "Starting complete test workflow..."
	@./run-tests.sh start
	@./run-tests.sh reset
	@vendor/bin/phpunit

# Quick Test (assumes DB is running)
test-quick:
	@vendor/bin/phpunit
