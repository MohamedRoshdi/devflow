#!/bin/bash

# ==============================================================================
# DevFlow Pro - Comprehensive Test Runner
# Runs all test suites in parallel and collects results
# ==============================================================================

set -e

# Configuration
RESULTS_DIR="${1:-storage/test-results}"
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
RESULTS_PATH="${RESULTS_DIR}/${TIMESTAMP}"
MAX_PARALLEL_JOBS=${2:-4}
PHP_BINARY="${PHP_BINARY:-php}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# ==============================================================================
# Functions
# ==============================================================================

print_header() {
    echo ""
    echo -e "${PURPLE}══════════════════════════════════════════════════════════════════${NC}"
    echo -e "${PURPLE}  DevFlow Pro - Comprehensive Test Runner${NC}"
    echo -e "${PURPLE}══════════════════════════════════════════════════════════════════${NC}"
    echo ""
}

print_section() {
    echo ""
    echo -e "${CYAN}──────────────────────────────────────────────────────────────────${NC}"
    echo -e "${CYAN}  $1${NC}"
    echo -e "${CYAN}──────────────────────────────────────────────────────────────────${NC}"
    echo ""
}

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# ==============================================================================
# Setup
# ==============================================================================

print_header

log_info "Timestamp: ${TIMESTAMP}"
log_info "Results directory: ${RESULTS_PATH}"
log_info "Max parallel jobs: ${MAX_PARALLEL_JOBS}"

# Create results directory
mkdir -p "${RESULTS_PATH}"
mkdir -p "${RESULTS_PATH}/junit"
mkdir -p "${RESULTS_PATH}/logs"

# ==============================================================================
# Run Tests in Parallel
# ==============================================================================

print_section "Starting Parallel Test Execution"

# Create a function to run each test suite
run_test_suite() {
    local suite=$1
    local output_file="${RESULTS_PATH}/logs/${suite,,}_output.txt"
    local junit_file="${RESULTS_PATH}/junit/${suite,,}_results.xml"
    local status_file="${RESULTS_PATH}/.${suite,,}_status"

    echo "Starting ${suite} tests..." > "${output_file}"

    START_TIME=$(date +%s)

    if ${PHP_BINARY} artisan test --testsuite="${suite}" --log-junit="${junit_file}" >> "${output_file}" 2>&1; then
        echo "0" > "${status_file}"
    else
        echo "1" > "${status_file}"
    fi

    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    echo "Duration: ${DURATION}s" >> "${output_file}"
}

# Export function for parallel execution
export -f run_test_suite
export RESULTS_PATH
export PHP_BINARY

# Start time tracking
TOTAL_START=$(date +%s)

# Run Unit, Feature, Performance, and Security tests in parallel
log_info "Launching test suites in parallel..."

# Using background jobs for parallel execution
${PHP_BINARY} artisan test --testsuite=Unit --log-junit="${RESULTS_PATH}/junit/unit_results.xml" > "${RESULTS_PATH}/logs/unit_output.txt" 2>&1 &
PID_UNIT=$!
log_info "Unit tests started (PID: ${PID_UNIT})"

${PHP_BINARY} artisan test --testsuite=Feature --log-junit="${RESULTS_PATH}/junit/feature_results.xml" > "${RESULTS_PATH}/logs/feature_output.txt" 2>&1 &
PID_FEATURE=$!
log_info "Feature tests started (PID: ${PID_FEATURE})"

${PHP_BINARY} artisan test --testsuite=Performance --log-junit="${RESULTS_PATH}/junit/performance_results.xml" > "${RESULTS_PATH}/logs/performance_output.txt" 2>&1 &
PID_PERFORMANCE=$!
log_info "Performance tests started (PID: ${PID_PERFORMANCE})"

${PHP_BINARY} artisan test --testsuite=Security --log-junit="${RESULTS_PATH}/junit/security_results.xml" > "${RESULTS_PATH}/logs/security_output.txt" 2>&1 &
PID_SECURITY=$!
log_info "Security tests started (PID: ${PID_SECURITY})"

# Wait for all non-browser tests to complete
log_info "Waiting for test suites to complete..."

wait ${PID_UNIT} 2>/dev/null
UNIT_EXIT=$?
if [ ${UNIT_EXIT} -eq 0 ]; then
    log_success "Unit tests completed"
else
    log_error "Unit tests failed (exit code: ${UNIT_EXIT})"
fi

wait ${PID_FEATURE} 2>/dev/null
FEATURE_EXIT=$?
if [ ${FEATURE_EXIT} -eq 0 ]; then
    log_success "Feature tests completed"
else
    log_error "Feature tests failed (exit code: ${FEATURE_EXIT})"
fi

wait ${PID_PERFORMANCE} 2>/dev/null
PERFORMANCE_EXIT=$?
if [ ${PERFORMANCE_EXIT} -eq 0 ]; then
    log_success "Performance tests completed"
else
    log_error "Performance tests failed (exit code: ${PERFORMANCE_EXIT})"
fi

wait ${PID_SECURITY} 2>/dev/null
SECURITY_EXIT=$?
if [ ${SECURITY_EXIT} -eq 0 ]; then
    log_success "Security tests completed"
else
    log_error "Security tests failed (exit code: ${SECURITY_EXIT})"
fi

# Browser tests (run separately as they need special setup)
print_section "Running Browser Tests"
log_info "Browser tests starting (this may take a while)..."

# Check if Chrome/Chromium is available
if command -v google-chrome &> /dev/null || command -v chromium-browser &> /dev/null; then
    ${PHP_BINARY} artisan dusk --log-junit="${RESULTS_PATH}/junit/browser_results.xml" > "${RESULTS_PATH}/logs/browser_output.txt" 2>&1 &
    PID_BROWSER=$!

    wait ${PID_BROWSER} 2>/dev/null
    BROWSER_EXIT=$?
    if [ ${BROWSER_EXIT} -eq 0 ]; then
        log_success "Browser tests completed"
    else
        log_warning "Browser tests failed or skipped (exit code: ${BROWSER_EXIT})"
    fi
else
    log_warning "Chrome/Chromium not found, skipping browser tests"
    BROWSER_EXIT=0
    echo "Browser tests skipped - Chrome not available" > "${RESULTS_PATH}/logs/browser_output.txt"
fi

# Calculate total duration
TOTAL_END=$(date +%s)
TOTAL_DURATION=$((TOTAL_END - TOTAL_START))

# ==============================================================================
# Generate Summary Report
# ==============================================================================

print_section "Generating Summary Report"

SUMMARY_FILE="${RESULTS_PATH}/SUMMARY.md"
ISSUES_FILE="${RESULTS_PATH}/ISSUES.md"

# Function to extract test counts from output
extract_test_info() {
    local file=$1
    local passed=0
    local failed=0
    local errors=0
    local skipped=0

    if [ -f "$file" ]; then
        passed=$(grep -oP 'Tests:\s*\K\d+(?=\s*passed)' "$file" 2>/dev/null | tail -1 || echo "0")
        failed=$(grep -oP '\K\d+(?=\s*failed)' "$file" 2>/dev/null | tail -1 || echo "0")
        errors=$(grep -oP '\K\d+(?=\s*errors?)' "$file" 2>/dev/null | tail -1 || echo "0")
        skipped=$(grep -oP '\K\d+(?=\s*skipped)' "$file" 2>/dev/null | tail -1 || echo "0")
    fi

    echo "${passed:-0}|${failed:-0}|${errors:-0}|${skipped:-0}"
}

# Extract results from each suite
UNIT_INFO=$(extract_test_info "${RESULTS_PATH}/logs/unit_output.txt")
FEATURE_INFO=$(extract_test_info "${RESULTS_PATH}/logs/feature_output.txt")
PERFORMANCE_INFO=$(extract_test_info "${RESULTS_PATH}/logs/performance_output.txt")
SECURITY_INFO=$(extract_test_info "${RESULTS_PATH}/logs/security_output.txt")
BROWSER_INFO=$(extract_test_info "${RESULTS_PATH}/logs/browser_output.txt")

# Parse values
IFS='|' read -r UNIT_PASSED UNIT_FAILED UNIT_ERRORS UNIT_SKIPPED <<< "$UNIT_INFO"
IFS='|' read -r FEATURE_PASSED FEATURE_FAILED FEATURE_ERRORS FEATURE_SKIPPED <<< "$FEATURE_INFO"
IFS='|' read -r PERF_PASSED PERF_FAILED PERF_ERRORS PERF_SKIPPED <<< "$PERFORMANCE_INFO"
IFS='|' read -r SEC_PASSED SEC_FAILED SEC_ERRORS SEC_SKIPPED <<< "$SECURITY_INFO"
IFS='|' read -r BROWSER_PASSED BROWSER_FAILED BROWSER_ERRORS BROWSER_SKIPPED <<< "$BROWSER_INFO"

# Calculate totals
TOTAL_PASSED=$((UNIT_PASSED + FEATURE_PASSED + PERF_PASSED + SEC_PASSED + BROWSER_PASSED))
TOTAL_FAILED=$((UNIT_FAILED + FEATURE_FAILED + PERF_FAILED + SEC_FAILED + BROWSER_FAILED))
TOTAL_ERRORS=$((UNIT_ERRORS + FEATURE_ERRORS + PERF_ERRORS + SEC_ERRORS + BROWSER_ERRORS))
TOTAL_SKIPPED=$((UNIT_SKIPPED + FEATURE_SKIPPED + PERF_SKIPPED + SEC_SKIPPED + BROWSER_SKIPPED))

# Determine overall status
if [ ${TOTAL_FAILED} -eq 0 ] && [ ${TOTAL_ERRORS} -eq 0 ]; then
    OVERALL_STATUS="✅ ALL TESTS PASSED"
    OVERALL_COLOR="${GREEN}"
else
    OVERALL_STATUS="❌ FAILURES DETECTED"
    OVERALL_COLOR="${RED}"
fi

# Write summary file
cat > "${SUMMARY_FILE}" << EOF
# DevFlow Pro - Test Results Summary

**Date:** $(date '+%Y-%m-%d %H:%M:%S')
**Duration:** ${TOTAL_DURATION} seconds
**Status:** ${OVERALL_STATUS}

## Overview

| Metric | Value |
|--------|-------|
| Total Tests | $((TOTAL_PASSED + TOTAL_FAILED)) |
| Passed | ${TOTAL_PASSED} |
| Failed | ${TOTAL_FAILED} |
| Errors | ${TOTAL_ERRORS} |
| Skipped | ${TOTAL_SKIPPED} |
| Duration | ${TOTAL_DURATION}s |

## Suite Results

| Suite | Status | Passed | Failed | Errors | Skipped |
|-------|--------|--------|--------|--------|---------|
| Unit | $([ ${UNIT_EXIT} -eq 0 ] && echo "✅" || echo "❌") | ${UNIT_PASSED} | ${UNIT_FAILED} | ${UNIT_ERRORS} | ${UNIT_SKIPPED} |
| Feature | $([ ${FEATURE_EXIT} -eq 0 ] && echo "✅" || echo "❌") | ${FEATURE_PASSED} | ${FEATURE_FAILED} | ${FEATURE_ERRORS} | ${FEATURE_SKIPPED} |
| Performance | $([ ${PERFORMANCE_EXIT} -eq 0 ] && echo "✅" || echo "❌") | ${PERF_PASSED} | ${PERF_FAILED} | ${PERF_ERRORS} | ${PERF_SKIPPED} |
| Security | $([ ${SECURITY_EXIT} -eq 0 ] && echo "✅" || echo "❌") | ${SEC_PASSED} | ${SEC_FAILED} | ${SEC_ERRORS} | ${SEC_SKIPPED} |
| Browser | $([ ${BROWSER_EXIT} -eq 0 ] && echo "✅" || echo "⚠️") | ${BROWSER_PASSED} | ${BROWSER_FAILED} | ${BROWSER_ERRORS} | ${BROWSER_SKIPPED} |

## Result Files

- **Summary:** \`${SUMMARY_FILE}\`
- **Issues:** \`${ISSUES_FILE}\`
- **Unit Logs:** \`${RESULTS_PATH}/logs/unit_output.txt\`
- **Feature Logs:** \`${RESULTS_PATH}/logs/feature_output.txt\`
- **Performance Logs:** \`${RESULTS_PATH}/logs/performance_output.txt\`
- **Security Logs:** \`${RESULTS_PATH}/logs/security_output.txt\`
- **Browser Logs:** \`${RESULTS_PATH}/logs/browser_output.txt\`
- **JUnit XML:** \`${RESULTS_PATH}/junit/\`

## Next Steps

1. Review failed tests in the ISSUES.md file
2. Check individual log files for detailed error messages
3. Run \`php artisan test --filter=TestName\` to debug specific tests
EOF

log_success "Summary written to: ${SUMMARY_FILE}"

# ==============================================================================
# Extract and Compile Issues
# ==============================================================================

print_section "Extracting Test Issues"

cat > "${ISSUES_FILE}" << EOF
# DevFlow Pro - Test Issues Report

**Generated:** $(date '+%Y-%m-%d %H:%M:%S')

This file contains all failed tests and errors that need to be fixed.

---

EOF

# Function to extract failures from output
extract_failures() {
    local suite=$1
    local file="${RESULTS_PATH}/logs/${suite}_output.txt"

    if [ -f "$file" ]; then
        echo "## ${suite^} Test Failures" >> "${ISSUES_FILE}"
        echo "" >> "${ISSUES_FILE}"

        # Extract FAILED lines
        if grep -q "FAILED" "$file"; then
            echo "### Failed Tests:" >> "${ISSUES_FILE}"
            echo "\`\`\`" >> "${ISSUES_FILE}"
            grep "FAILED\|Error\|Exception\|⨯" "$file" | head -100 >> "${ISSUES_FILE}"
            echo "\`\`\`" >> "${ISSUES_FILE}"
            echo "" >> "${ISSUES_FILE}"

            # Try to extract error details
            echo "### Error Details:" >> "${ISSUES_FILE}"
            echo "\`\`\`" >> "${ISSUES_FILE}"
            grep -A 10 "FAILED" "$file" | head -200 >> "${ISSUES_FILE}"
            echo "\`\`\`" >> "${ISSUES_FILE}"
        else
            echo "No failures detected." >> "${ISSUES_FILE}"
        fi
        echo "" >> "${ISSUES_FILE}"
        echo "---" >> "${ISSUES_FILE}"
        echo "" >> "${ISSUES_FILE}"
    fi
}

# Extract failures from each suite
extract_failures "unit"
extract_failures "feature"
extract_failures "performance"
extract_failures "security"
extract_failures "browser"

log_success "Issues extracted to: ${ISSUES_FILE}"

# ==============================================================================
# Final Summary
# ==============================================================================

print_section "Test Run Complete"

echo -e "${OVERALL_COLOR}${OVERALL_STATUS}${NC}"
echo ""
echo "Results saved to: ${RESULTS_PATH}"
echo ""
echo "Quick stats:"
echo "  - Total Tests: $((TOTAL_PASSED + TOTAL_FAILED))"
echo "  - Passed: ${TOTAL_PASSED}"
echo "  - Failed: ${TOTAL_FAILED}"
echo "  - Duration: ${TOTAL_DURATION}s"
echo ""
echo "To view results:"
echo "  cat ${SUMMARY_FILE}"
echo ""
echo "To view issues:"
echo "  cat ${ISSUES_FILE}"
echo ""

# Exit with appropriate code
if [ ${TOTAL_FAILED} -eq 0 ] && [ ${TOTAL_ERRORS} -eq 0 ]; then
    exit 0
else
    exit 1
fi
