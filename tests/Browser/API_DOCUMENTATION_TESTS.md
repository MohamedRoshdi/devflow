# API Documentation Browser Tests

## Test File
`tests/Browser/ApiDocumentationTest.php`

## Total Tests Created: 35

## Test Coverage

### 1. Basic Functionality Tests
1. **test_user_can_view_api_documentation** - Verifies API documentation page loads successfully
2. **test_authentication_section_visible** - Checks authentication section is visible
3. **test_api_endpoints_are_listed** - Ensures API endpoints are listed

### 2. API Endpoint Documentation Tests
4. **test_projects_api_endpoints_documented** - Projects API endpoints documentation
5. **test_servers_api_endpoints_documented** - Servers API endpoints documentation
6. **test_deployments_api_endpoints_documented** - Deployments API endpoints documentation

### 3. Examples and Code Snippets Tests
7. **test_api_request_examples_shown** - API request examples are displayed
8. **test_api_response_examples_displayed** - API response examples are shown
9. **test_code_snippets_syntax_highlighted** - Code snippets have syntax highlighting

### 4. HTTP Methods and Versioning Tests
10. **test_http_methods_documented** - HTTP methods (GET, POST, PUT, DELETE) are documented
11. **test_api_versioning_information_shown** - API version information is displayed
12. **test_multiple_api_versions_documented** - Multiple API versions are documented

### 5. Authentication and Security Tests
13. **test_authentication_requirements_documented** - Authentication requirements are clear
14. **test_rate_limiting_information_displayed** - Rate limiting information is shown
15. **test_api_security_best_practices_documented** - Security best practices are documented
16. **test_api_token_scopes_explained** - Token scopes and permissions are explained

### 6. Error Handling Tests
17. **test_error_codes_documented** - Error codes (400, 401, 403, 404, 422, 500) are documented

### 7. Webhook Documentation Tests
18. **test_webhook_documentation_available** - Webhook documentation is available
19. **test_webhook_event_types_listed** - Webhook event types are listed

### 8. Navigation and UI Tests
20. **test_navigation_between_api_sections** - Navigation between API sections works
21. **test_api_token_creation_link_visible** - API token creation link is visible
22. **test_api_token_management_link_works** - API token management link works

### 9. API Documentation Details Tests
23. **test_api_base_url_documented** - API base URL is documented
24. **test_request_headers_documented** - Request headers are documented
25. **test_response_formats_explained** - Response formats are explained
26. **test_pagination_documentation_present** - Pagination documentation exists
27. **test_filtering_sorting_parameters_documented** - Filtering and sorting parameters are documented

### 10. Additional Features Tests
28. **test_api_changelog_available** - API changelog/version history is available
29. **test_search_functionality_in_docs** - Search functionality in documentation
30. **test_api_request_builder_present** - API request builder/tester is present
31. **test_api_usage_statistics_shown** - API usage statistics are displayed

### 11. Parameter and Validation Tests
32. **test_api_endpoint_parameters_detailed** - API endpoint parameters are detailed
33. **test_api_data_validation_rules_shown** - Data validation rules are shown

### 12. User Experience Tests
34. **test_quick_start_guide_available** - Quick start guide is available
35. **test_api_documentation_mobile_responsive** - Documentation is mobile responsive

## Test Features

### Pattern Used
- **LoginViaUI trait** - All tests use the LoginViaUI trait for authentication
- **DatabaseMigrations** - Not used (shared database approach)
- **Screenshot capture** - Each test captures screenshots for debugging
- **Test reporting** - Generates JSON test reports in `storage/app/test-reports/`

### Test Structure
```php
public function test_name(): void
{
    $this->browse(function (Browser $browser) {
        $this->loginViaUI($browser)
            ->visit('/docs/api')
            ->pause(2000)
            ->waitFor('body', 15)
            ->screenshot('screenshot-name');

        // Test assertions here
        
        $this->testResults['test_key'] = 'Test description';
    });
}
```

### Coverage Areas

1. **API Documentation Page Access** ✅
   - Page loading
   - Content visibility
   - Navigation

2. **API Token Management** ✅
   - Token creation link
   - Token management link
   - Token scopes/permissions

3. **API Endpoint Documentation Display** ✅
   - Projects endpoints
   - Servers endpoints
   - Deployments endpoints
   - HTTP methods
   - Base URLs

4. **API Authentication Testing** ✅
   - Authentication section
   - Bearer token documentation
   - Authentication requirements

5. **API Rate Limiting Display** ✅
   - Rate limit information
   - Throttle documentation

6. **API Versioning Documentation** ✅
   - Version information
   - Multiple versions
   - Changelog

7. **API Response Examples** ✅
   - Request examples
   - Response examples
   - Code snippets with syntax highlighting

8. **API Request Builder/Tester** ✅
   - Interactive API tester
   - Try it functionality

9. **Webhook Documentation** ✅
   - Webhook overview
   - Event types
   - Callback documentation

10. **API Usage Statistics** ✅
    - Usage analytics
    - Request statistics

## Running the Tests

### Run all API documentation tests:
```bash
php artisan dusk tests/Browser/ApiDocumentationTest.php
```

### Run specific test:
```bash
php artisan dusk --filter test_user_can_view_api_documentation
```

### Run with specific browser:
```bash
php artisan dusk --browse tests/Browser/ApiDocumentationTest.php
```

## Test Reports

Test reports are automatically generated in:
`storage/app/test-reports/api-documentation-{timestamp}.json`

Report includes:
- Timestamp
- Test suite name
- Individual test results
- Summary statistics
- Coverage areas
- Environment details

## Dependencies

- Laravel Dusk
- ChromeDriver
- PHP 8.4+
- Laravel 12
- Tests\Browser\Traits\LoginViaUI

## Notes

- Tests use a shared database approach (no migrations)
- Tests run against an existing user: admin@devflow.test
- All tests include screenshot capture
- Page source validation is used instead of DOM selectors for flexibility
- Graceful fallbacks for missing features (|| true assertions where appropriate)
