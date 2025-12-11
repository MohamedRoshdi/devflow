# Translation Usage Examples for DevFlow Pro

This document provides practical examples of how to use the translation system in different scenarios.

## Quick Reference

### Status Translations
```blade
{{ __('status.pending') }}       // "Pending" or "قيد الانتظار"
{{ __('status.approved') }}      // "Approved" or "موافق عليه"
{{ __('status.rejected') }}      // "Rejected" or "مرفوض"
{{ __('status.running') }}       // "Running" or "قيد التشغيل"
{{ __('status.failed') }}        // "Failed" or "فشل"
```

### Button Translations
```blade
{{ __('buttons.save') }}         // "Save" or "حفظ"
{{ __('buttons.cancel') }}       // "Cancel" or "إلغاء"
{{ __('buttons.approve') }}      // "Approve" or "موافقة"
{{ __('buttons.reject') }}       // "Reject" or "رفض"
{{ __('buttons.delete') }}       // "Delete" or "حذف"
```

### Label Translations
```blade
{{ __('labels.search') }}        // "Search" or "بحث"
{{ __('labels.status') }}        // "Status" or "الحالة"
{{ __('labels.project') }}       // "Project" or "المشروع"
{{ __('labels.branch') }}        // "Branch" or "الفرع"
```

## Common Patterns

### 1. Page Headers
```blade
<h1>{{ __('messages.deployment_approvals_title') }}</h1>
<p>{{ __('messages.deployment_approvals_description') }}</p>
```

### 2. Form Labels and Inputs
```blade
<label>{{ __('labels.search') }}</label>
<input type="text" placeholder="{{ __('labels.search_placeholder') }}">
```

### 3. Status Badges
```blade
<span class="badge">
    {{ __('status.' . strtolower($approval->status)) }}
</span>
```

### 4. Dropdown Options
```blade
<select>
    <option value="">{{ __('labels.all_projects') }}</option>
    <option value="pending">{{ __('status.pending') }}</option>
    <option value="approved">{{ __('status.approved') }}</option>
</select>
```

### 5. Action Buttons
```blade
<button wire:click="approve">
    {{ __('buttons.approve') }}
</button>
<button wire:click="reject">
    {{ __('buttons.reject') }}
</button>
```

### 6. Navigation Links
```blade
<a href="{{ route('projects.index') }}">
    {{ __('buttons.back_to_projects') }}
</a>
```

### 7. Empty States
```blade
@if($items->isEmpty())
    <h3>{{ __('messages.no_approvals_found') }}</h3>
    <p>{{ __('messages.no_approvals_description') }}</p>
@endif
```

### 8. Modal Titles and Messages
```blade
<h3>{{ __('messages.approve_deployment_title') }}</h3>
<p>{{ __('messages.approve_deployment_confirmation') }}</p>
```

## Advanced Usage

### 1. Dynamic Status Translation
```blade
@foreach($deployments as $deployment)
    <span>{{ __('status.' . $deployment->status) }}</span>
@endforeach
```

### 2. Conditional Labels
```blade
{{ $isEdit ? __('buttons.update') : __('buttons.create') }}
```

### 3. Combining Translations with Data
```blade
<p>
    {{ __('labels.requested_by') }}:
    <strong>{{ $approval->requester->name }}</strong>
</p>
```

### 4. Using in Livewire Components
```php
// In Livewire component
public function approve()
{
    // ... approval logic

    $this->dispatch('notification',
        type: 'success',
        message: __('messages.deployment_approved')
    );
}
```

### 5. Translation with Pluralization
```php
// In messages.php
'items_count' => '{0} No items|{1} One item|[2,*] :count items',

// In blade
{{ trans_choice('messages.items_count', $count, ['count' => $count]) }}
```

### 6. Required Field Indicator
```blade
<label>
    {{ __('labels.rejection_reason') }}
    <span class="text-red-600">*</span>
</label>
```

### 7. Optional Field Indicator
```blade
<label>
    {{ __('labels.approval_notes') }}
    ({{ __('messages.optional_field') }})
</label>
```

## Complete Component Example

Here's a fully translated form component:

```blade
<div class="card">
    <div class="card-header">
        <h2>{{ __('messages.deployment_approvals_title') }}</h2>
        <p>{{ __('messages.deployment_approvals_description') }}</p>
    </div>

    <div class="card-body">
        <!-- Filters -->
        <div class="filters">
            <div>
                <label>{{ __('labels.search') }}</label>
                <input
                    type="text"
                    placeholder="{{ __('labels.search_placeholder') }}"
                    wire:model.live="search"
                >
            </div>

            <div>
                <label>{{ __('labels.status') }}</label>
                <select wire:model.live="statusFilter">
                    <option value="">{{ __('labels.all_statuses') }}</option>
                    <option value="pending">{{ __('status.pending') }}</option>
                    <option value="approved">{{ __('status.approved') }}</option>
                    <option value="rejected">{{ __('status.rejected') }}</option>
                </select>
            </div>
        </div>

        <!-- Results -->
        @if($approvals->count())
            @foreach($approvals as $approval)
                <div class="approval-item">
                    <span class="status-badge">
                        {{ __('status.' . $approval->status) }}
                    </span>

                    <div class="details">
                        <p>{{ __('labels.requested_by') }}: {{ $approval->requester->name }}</p>
                        <p>{{ __('labels.branch') }}: {{ $approval->deployment->branch }}</p>
                    </div>

                    @if($approval->status === 'pending')
                        <div class="actions">
                            <button wire:click="approve({{ $approval->id }})">
                                {{ __('buttons.approve') }}
                            </button>
                            <button wire:click="reject({{ $approval->id }})">
                                {{ __('buttons.reject') }}
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        @else
            <div class="empty-state">
                <h3>{{ __('messages.no_approvals_found') }}</h3>
                <p>{{ __('messages.no_approvals_description') }}</p>
            </div>
        @endif
    </div>
</div>
```

## Testing Translations

### Test in English
```bash
# config/app.php
'locale' => 'en',
```

### Test in Arabic
```bash
# config/app.php
'locale' => 'ar',
```

Clear cache after changing locale:
```bash
php artisan config:clear
```

## Migration Guide

### Before (Hardcoded)
```blade
<h1>Deployment Approvals</h1>
<button>Approve</button>
<p>No approvals found</p>
```

### After (Translated)
```blade
<h1>{{ __('messages.deployment_approvals_title') }}</h1>
<button>{{ __('buttons.approve') }}</button>
<p>{{ __('messages.no_approvals_found') }}</p>
```

## Common Mistakes to Avoid

❌ **Don't hardcode strings:**
```blade
<button>Save</button>
```

✅ **Use translation keys:**
```blade
<button>{{ __('buttons.save') }}</button>
```

❌ **Don't use wrong file:**
```blade
{{ __('buttons.pending') }}  // Status should be in status.php
```

✅ **Use correct file:**
```blade
{{ __('status.pending') }}
```

❌ **Don't mix languages:**
```blade
{{ __('labels.search') }} Projects  // Mix of translated and hardcoded
```

✅ **Translate everything:**
```blade
{{ __('labels.search') }} {{ __('labels.projects') }}
```

## Tips for Developers

1. **Always check both files:** When adding a key to `en/`, add it to `ar/` too
2. **Use descriptive keys:** `deployment_approvals_title` not `title1`
3. **Group related keys:** Keep all deployment-related messages together
4. **Test both languages:** Switch locale and verify the UI looks correct
5. **Use existing keys:** Before adding new keys, check if one already exists
6. **Keep it simple:** One translation file per category (status, labels, buttons, messages)

## IDE Support

### PhpStorm / VS Code
Install Laravel plugins for autocomplete of translation keys:
- PhpStorm: Laravel Plugin
- VS Code: Laravel Extra Intellisense

This will give you autocomplete for `__('status.pending')` syntax.
