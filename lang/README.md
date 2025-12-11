# DevFlow Pro - Translation Infrastructure

This directory contains the internationalization (i18n) files for DevFlow Pro. The translation system is built on Laravel's native localization features.

## Directory Structure

```
lang/
├── en/                      # English translations (default)
│   ├── status.php          # Status labels (pending, approved, rejected, etc.)
│   ├── labels.php          # UI labels (search, project, deployment, etc.)
│   ├── buttons.php         # Button text (save, cancel, approve, etc.)
│   └── messages.php        # Page titles, descriptions, and messages
├── ar/                      # Arabic translations
│   ├── status.php
│   ├── labels.php
│   ├── buttons.php
│   └── messages.php
└── README.md               # This file
```

## Translation Files

### 1. `status.php`
Contains all status-related labels used throughout the application:
- General statuses: `pending`, `approved`, `rejected`, `expired`, `active`, `inactive`
- Deployment statuses: `deployment_pending`, `deployment_running`, `deployment_success`
- Server statuses: `server_online`, `server_offline`, `server_maintenance`

**Usage in Blade:**
```blade
{{ __('status.pending') }}
{{ __('status.approved') }}
{{ __('status.deployment_success') }}
```

### 2. `labels.php`
Contains UI labels, field names, and placeholders:
- Common labels: `search`, `status`, `project`, `name`, `email`
- Filter labels: `all_projects`, `all_statuses`
- Approval labels: `requested_by`, `approval_notes`, `rejection_reason`
- Placeholders: `search_placeholder`, `type_to_search`

**Usage in Blade:**
```blade
<label>{{ __('labels.search') }}</label>
<input placeholder="{{ __('labels.search_placeholder') }}">
{{ __('labels.requested_by') }}
```

### 3. `buttons.php`
Contains all button text and action labels:
- Common actions: `save`, `cancel`, `delete`, `edit`, `create`
- Deployment actions: `approve`, `reject`, `deploy`, `rollback`
- Navigation: `back_to_deployments`, `back_to_projects`

**Usage in Blade:**
```blade
<button>{{ __('buttons.save') }}</button>
<button>{{ __('buttons.approve_deployment') }}</button>
<a href="#">{{ __('buttons.back_to_projects') }}</a>
```

### 4. `messages.php`
Contains page titles, descriptions, and user messages:
- Page titles: `deployment_approvals_title`, `projects_management_title`
- Descriptions: `deployment_approvals_description`
- Modal messages: `approve_deployment_confirmation`
- Empty states: `no_approvals_found`, `no_approvals_description`
- Success/Error messages: `deployment_approved`, `deployment_rejected`

**Usage in Blade:**
```blade
<h1>{{ __('messages.deployment_approvals_title') }}</h1>
<p>{{ __('messages.deployment_approvals_description') }}</p>
```

## How to Use Translations in Blade Templates

Laravel provides several helper functions for translations:

### Basic Translation
```blade
{{ __('messages.deployment_approvals_title') }}
```

### Translation with Parameters
```blade
{{ __('messages.projects_count', ['count' => 5]) }}
// Output: "5 Projects"
```

### Alternative Syntax
```blade
@lang('messages.deployment_approvals_title')
```

### Conditional Translation
```blade
{{ $status === 'pending' ? __('status.pending') : __('status.approved') }}
```

## Adding New Translations

### Step 1: Add to English File
Add your new translation key to the appropriate English file:

```php
// lang/en/labels.php
return [
    // ... existing translations
    'new_label' => 'New Label Text',
];
```

### Step 2: Add to Arabic File
Add the corresponding Arabic translation:

```php
// lang/ar/labels.php
return [
    // ... existing translations
    'new_label' => 'نص التسمية الجديد',
];
```

### Step 3: Use in Blade
```blade
{{ __('labels.new_label') }}
```

## Changing Application Language

### Method 1: Via Configuration
Update `config/app.php`:
```php
'locale' => 'en', // Change to 'ar' for Arabic
'fallback_locale' => 'en',
```

### Method 2: Runtime Change
In your controller or middleware:
```php
App::setLocale('ar');
```

### Method 3: Per-User Preference
Store user's language preference in database and set it on each request:
```php
// In middleware or service provider
if (auth()->check() && auth()->user()->language) {
    App::setLocale(auth()->user()->language);
}
```

## Best Practices

1. **Organize by Context**: Keep related translations in the same file
2. **Use Descriptive Keys**: Use clear, descriptive key names (e.g., `deployment_approvals_title` not `dat`)
3. **Avoid Hardcoded Strings**: Always use translation keys in blade files
4. **Consistent Naming**: Follow the existing naming conventions
5. **Fallback Language**: Always maintain English as the fallback language
6. **Parameters for Dynamic Content**: Use parameters for dynamic values:
   ```php
   'welcome_message' => 'Welcome, :name!',
   ```
   ```blade
   {{ __('messages.welcome_message', ['name' => $user->name]) }}
   ```

## Translation Coverage

Currently translated components:
- ✅ Deployment Approvals page (`deployment-approvals.blade.php`)
- ⏳ Other pages (to be translated as needed)

## Example: Fully Translated Component

```blade
<div>
    <h1>{{ __('messages.deployment_approvals_title') }}</h1>
    <p>{{ __('messages.deployment_approvals_description') }}</p>

    <div>
        <label>{{ __('labels.search') }}</label>
        <input placeholder="{{ __('labels.search_placeholder') }}">

        <select>
            <option value="pending">{{ __('status.pending') }}</option>
            <option value="approved">{{ __('status.approved') }}</option>
            <option value="rejected">{{ __('status.rejected') }}</option>
        </select>

        <button>{{ __('buttons.approve') }}</button>
        <button>{{ __('buttons.reject') }}</button>
    </div>
</div>
```

## Testing Translations

### Test English Translations
Ensure your locale is set to 'en' and verify all strings display correctly.

### Test Arabic Translations
1. Change locale to 'ar' in `config/app.php`
2. Clear config cache: `php artisan config:clear`
3. Verify Arabic translations appear correctly
4. Check RTL layout if using Arabic

### Test Fallback Behavior
1. Remove a key from Arabic translations
2. Verify it falls back to English version

## Future Enhancements

Potential improvements for the translation system:

1. **Database-driven Translations**: Store translations in database for dynamic updates
2. **Translation Management UI**: Admin panel to manage translations
3. **Language Switcher**: User-facing language selector in navigation
4. **RTL Support**: Enhanced right-to-left layout support for Arabic
5. **Additional Languages**: French, German, Spanish, etc.
6. **Translation Validation**: Automated tests to ensure all keys exist in all languages
7. **Export/Import**: CSV/JSON export for translators

## Contributing Translations

When adding new features:
1. Add English translations first
2. Add Arabic translations (or placeholder text)
3. Update this README if adding new translation files
4. Test both languages before committing

## Support

For questions or issues with translations, please contact the development team.
