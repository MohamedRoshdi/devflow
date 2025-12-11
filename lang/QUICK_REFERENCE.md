# DevFlow Pro - Translation Quick Reference Card

## File Organization

| File | Purpose | Example Keys |
|------|---------|--------------|
| `status.php` | Status labels | `pending`, `approved`, `running`, `failed` |
| `labels.php` | UI labels & placeholders | `search`, `project`, `branch`, `commit` |
| `buttons.php` | Button text & actions | `save`, `cancel`, `approve`, `reject` |
| `messages.php` | Titles, descriptions, messages | `deployment_approvals_title`, `no_approvals_found` |

## Common Usage Patterns

### Status Badges
```blade
{{ __('status.pending') }}
{{ __('status.approved') }}
{{ __('status.rejected') }}
{{ __('status.running') }}
{{ __('status.failed') }}
```

### Buttons
```blade
{{ __('buttons.save') }}
{{ __('buttons.cancel') }}
{{ __('buttons.approve') }}
{{ __('buttons.reject') }}
{{ __('buttons.delete') }}
```

### Form Labels
```blade
{{ __('labels.search') }}
{{ __('labels.project') }}
{{ __('labels.status') }}
{{ __('labels.branch') }}
```

### Page Titles
```blade
{{ __('messages.deployment_approvals_title') }}
{{ __('messages.deployment_activity_title') }}
{{ __('messages.projects_management_title') }}
```

### Placeholders
```blade
placeholder="{{ __('labels.search_placeholder') }}"
placeholder="{{ __('labels.type_to_search') }}"
placeholder="{{ __('labels.approval_notes_placeholder') }}"
```

### Empty States
```blade
{{ __('messages.no_approvals_found') }}
{{ __('messages.no_deployments_found') }}
{{ __('messages.no_projects_found') }}
```

## Quick Syntax Reference

### Basic Translation
```blade
{{ __('category.key') }}
```

### With Parameters
```blade
{{ __('messages.welcome', ['name' => $user->name]) }}
```

### Alternative Syntax
```blade
@lang('category.key')
```

### In Attributes
```blade
<input placeholder="{{ __('labels.search_placeholder') }}">
<button title="{{ __('buttons.save') }}">
```

## Status Translation Matrix

| Status Value | Translation Key | English | Arabic |
|--------------|----------------|---------|--------|
| pending | `status.pending` | Pending | قيد الانتظار |
| approved | `status.approved` | Approved | موافق عليه |
| rejected | `status.rejected` | Rejected | مرفوض |
| expired | `status.expired` | Expired | منتهي الصلاحية |
| running | `status.running` | Running | قيد التشغيل |
| failed | `status.failed` | Failed | فشل |
| success | `status.success` | Success | نجح |

## Common Button Translations

| Action | Key | English | Arabic |
|--------|-----|---------|--------|
| Save | `buttons.save` | Save | حفظ |
| Cancel | `buttons.cancel` | Cancel | إلغاء |
| Delete | `buttons.delete` | Delete | حذف |
| Edit | `buttons.edit` | Edit | تعديل |
| Approve | `buttons.approve` | Approve | موافقة |
| Reject | `buttons.reject` | Reject | رفض |

## Change Language

### In Config (Permanent)
```php
// config/app.php
'locale' => 'ar', // or 'en'
```

### At Runtime (Temporary)
```php
App::setLocale('ar');
```

### Per User
```php
if (auth()->user()->language) {
    App::setLocale(auth()->user()->language);
}
```

## Testing Commands

```bash
# Clear config cache after changing locale
php artisan config:clear

# View current locale
php artisan tinker
>>> App::getLocale()

# Test translation
php artisan tinker
>>> __('status.pending')
```

## Best Practices Checklist

- [ ] Use `__('category.key')` format
- [ ] Never hardcode strings
- [ ] Add to both `en/` and `ar/` files
- [ ] Use descriptive key names
- [ ] Test in both languages
- [ ] Keep related keys together
- [ ] Document new keys

## Common Mistakes

❌ Hardcoded: `<button>Save</button>`
✅ Translated: `<button>{{ __('buttons.save') }}</button>`

❌ Wrong file: `{{ __('buttons.pending') }}`
✅ Correct file: `{{ __('status.pending') }}`

❌ Missing key: `{{ __('labels.xyz') }}`
✅ Add key first, then use

## Translation Key Pattern

```
category.specific_item
   ↓         ↓
status  . pending
labels  . search
buttons . save
messages. deployment_approvals_title
```

## Full Translation Example

```blade
<!-- Page Header -->
<h1>{{ __('messages.deployment_approvals_title') }}</h1>
<p>{{ __('messages.deployment_approvals_description') }}</p>

<!-- Search Form -->
<label>{{ __('labels.search') }}</label>
<input placeholder="{{ __('labels.search_placeholder') }}">

<!-- Status Filter -->
<select>
    <option>{{ __('labels.all_statuses') }}</option>
    <option value="pending">{{ __('status.pending') }}</option>
    <option value="approved">{{ __('status.approved') }}</option>
</select>

<!-- Action Buttons -->
<button>{{ __('buttons.approve') }}</button>
<button>{{ __('buttons.reject') }}</button>
<button>{{ __('buttons.cancel') }}</button>

<!-- Empty State -->
<h3>{{ __('messages.no_approvals_found') }}</h3>
<p>{{ __('messages.no_approvals_description') }}</p>
```

## Files Summary

| Language | Files | Keys | Lines |
|----------|-------|------|-------|
| English | 4 | 112 | 174 |
| Arabic | 4 | 112 | 174 |
| **Total** | **8** | **224** | **348** |

## Need Help?

1. **README.md** - Complete documentation
2. **USAGE_EXAMPLES.md** - Practical examples
3. **TRANSLATION_SUMMARY.md** - Overview & status
4. **This file** - Quick reference

---

**Last Updated:** 2025-12-11
**Version:** 1.0.0
