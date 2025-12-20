#!/bin/bash

# DevFlow Pro - Dashboard Tests Runner
# This script makes it easy to run dashboard browser tests

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   DevFlow Pro - Dashboard Tests Runner   ║${NC}"
echo -e "${BLUE}╔════════════════════════════════════════════╗${NC}"
echo ""

# Check if Dusk is installed
if [ ! -f "vendor/bin/phpunit" ]; then
    echo -e "${RED}❌ Error: Dependencies not installed${NC}"
    echo -e "${YELLOW}Run: composer install${NC}"
    exit 1
fi

# Check if ChromeDriver exists
if [ ! -f "vendor/laravel/dusk/bin/chromedriver-linux" ] && [ ! -f "vendor/laravel/dusk/bin/chromedriver-mac" ]; then
    echo -e "${YELLOW}⚠️  ChromeDriver not found. Installing...${NC}"
    php artisan dusk:chrome-driver --detect
fi

# Function to display menu
show_menu() {
    echo -e "${GREEN}Select test mode:${NC}"
    echo "1) Run all dashboard tests"
    echo "2) Run specific test"
    echo "3) Run in visible browser mode (for debugging)"
    echo "4) Run with screenshots"
    echo "5) Check ChromeDriver version"
    echo "6) Update ChromeDriver"
    echo "7) Exit"
    echo ""
}

# Function to run all tests
run_all_tests() {
    echo -e "${BLUE}🧪 Running all dashboard tests...${NC}"
    php artisan dusk tests/Browser/DashboardTest.php
}

# Function to list and run specific test
run_specific_test() {
    echo -e "${GREEN}Available tests:${NC}"
    echo "1) test_dashboard_page_loads_successfully_for_authenticated_user"
    echo "2) test_stats_cards_are_visible_with_correct_data"
    echo "3) test_quick_actions_panel_is_visible_with_all_buttons"
    echo "4) test_deploy_all_button_shows_confirmation_and_works"
    echo "5) test_clear_caches_button_works_and_shows_notification"
    echo "6) test_activity_feed_section_loads_with_recent_activities"
    echo "7) test_server_health_section_shows_server_status"
    echo "8) test_deployment_timeline_chart_is_visible"
    echo "9) test_dashboard_responds_to_dark_light_mode_toggle"
    echo "10) test_dashboard_widgets_can_be_collapsed_expanded"
    echo "11) test_dashboard_auto_refreshes_poll_functionality"
    echo "12) test_navigation_links_work_correctly"
    echo "13) test_user_dropdown_menu_works"
    echo "14) test_mobile_responsiveness_at_different_viewport_sizes"
    echo "15) test_quick_action_links_navigate_to_correct_pages"
    echo "16) test_stats_cards_show_correct_online_offline_counts"
    echo "17) test_hero_section_displays_correct_stats"
    echo "18) test_customize_layout_button_toggles_edit_mode"
    echo "19) test_activity_feed_shows_load_more_button"
    echo "20) test_dashboard_handles_no_data_gracefully"
    echo "21) test_ssl_expiring_warning_is_displayed"
    echo "22) test_deployment_timeline_shows_correct_status_colors"
    echo ""
    echo -n "Enter test number (1-22): "
    read test_number

    case $test_number in
        1) test_name="test_dashboard_page_loads_successfully_for_authenticated_user" ;;
        2) test_name="test_stats_cards_are_visible_with_correct_data" ;;
        3) test_name="test_quick_actions_panel_is_visible_with_all_buttons" ;;
        4) test_name="test_deploy_all_button_shows_confirmation_and_works" ;;
        5) test_name="test_clear_caches_button_works_and_shows_notification" ;;
        6) test_name="test_activity_feed_section_loads_with_recent_activities" ;;
        7) test_name="test_server_health_section_shows_server_status" ;;
        8) test_name="test_deployment_timeline_chart_is_visible" ;;
        9) test_name="test_dashboard_responds_to_dark_light_mode_toggle" ;;
        10) test_name="test_dashboard_widgets_can_be_collapsed_expanded" ;;
        11) test_name="test_dashboard_auto_refreshes_poll_functionality" ;;
        12) test_name="test_navigation_links_work_correctly" ;;
        13) test_name="test_user_dropdown_menu_works" ;;
        14) test_name="test_mobile_responsiveness_at_different_viewport_sizes" ;;
        15) test_name="test_quick_action_links_navigate_to_correct_pages" ;;
        16) test_name="test_stats_cards_show_correct_online_offline_counts" ;;
        17) test_name="test_hero_section_displays_correct_stats" ;;
        18) test_name="test_customize_layout_button_toggles_edit_mode" ;;
        19) test_name="test_activity_feed_shows_load_more_button" ;;
        20) test_name="test_dashboard_handles_no_data_gracefully" ;;
        21) test_name="test_ssl_expiring_warning_is_displayed" ;;
        22) test_name="test_deployment_timeline_shows_correct_status_colors" ;;
        *)
            echo -e "${RED}Invalid test number${NC}"
            return
            ;;
    esac

    echo -e "${BLUE}🧪 Running test: ${test_name}${NC}"
    php artisan dusk --filter $test_name
}

# Function to run in visible mode
run_visible_mode() {
    echo -e "${BLUE}🔍 Running tests in visible browser mode...${NC}"
    echo -e "${YELLOW}Note: Browser window will be visible during test execution${NC}"
    DUSK_HEADLESS_DISABLED=true php artisan dusk tests/Browser/DashboardTest.php
}

# Function to run with screenshots
run_with_screenshots() {
    echo -e "${BLUE}📸 Running tests with screenshot capture...${NC}"
    php artisan dusk tests/Browser/DashboardTest.php

    if [ -d "tests/Browser/screenshots" ]; then
        screenshot_count=$(ls -1 tests/Browser/screenshots/*.png 2>/dev/null | wc -l)
        if [ $screenshot_count -gt 0 ]; then
            echo -e "${GREEN}✅ $screenshot_count screenshot(s) captured in tests/Browser/screenshots/${NC}"
            echo -e "${YELLOW}Opening screenshots directory...${NC}"
            xdg-open tests/Browser/screenshots/ 2>/dev/null || open tests/Browser/screenshots/ 2>/dev/null || echo "Please check: tests/Browser/screenshots/"
        else
            echo -e "${GREEN}✅ Tests passed - no screenshots captured${NC}"
        fi
    fi
}

# Function to check ChromeDriver
check_chromedriver() {
    echo -e "${BLUE}🔍 Checking ChromeDriver version...${NC}"

    if [ -f "vendor/laravel/dusk/bin/chromedriver-linux" ]; then
        vendor/laravel/dusk/bin/chromedriver-linux --version
    elif [ -f "vendor/laravel/dusk/bin/chromedriver-mac" ]; then
        vendor/laravel/dusk/bin/chromedriver-mac --version
    else
        echo -e "${RED}❌ ChromeDriver not found${NC}"
    fi

    echo ""
    echo -e "${BLUE}Chrome/Chromium version:${NC}"
    google-chrome --version 2>/dev/null || chromium-browser --version 2>/dev/null || echo "Chrome/Chromium not found in PATH"
}

# Function to update ChromeDriver
update_chromedriver() {
    echo -e "${BLUE}⬇️  Updating ChromeDriver...${NC}"
    php artisan dusk:chrome-driver --detect
    echo -e "${GREEN}✅ ChromeDriver updated successfully${NC}"
}

# Main menu loop
while true; do
    show_menu
    echo -n "Enter your choice [1-7]: "
    read choice

    case $choice in
        1)
            run_all_tests
            ;;
        2)
            run_specific_test
            ;;
        3)
            run_visible_mode
            ;;
        4)
            run_with_screenshots
            ;;
        5)
            check_chromedriver
            ;;
        6)
            update_chromedriver
            ;;
        7)
            echo -e "${GREEN}👋 Goodbye!${NC}"
            exit 0
            ;;
        *)
            echo -e "${RED}Invalid option. Please select 1-7${NC}"
            ;;
    esac

    echo ""
    echo -e "${YELLOW}Press Enter to continue...${NC}"
    read
    clear
done
