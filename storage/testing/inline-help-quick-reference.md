# Inline Help System - Quick Reference Card

## 🚀 Quick Start

### 1. Run Migration
```bash
php artisan migrate
```

### 2. Use in Blade Templates
```blade
<!-- Simple inline help -->
<livewire:inline-help help-key="deploy-button" />

<!-- Collapsible help -->
<livewire:inline-help help-key="ssl-toggle" :collapsible="true" />
```

### 3. Use in Livewire Components
```php
use App\Services\HelpContentService;

public function boot(HelpContentService $helpService)
{
    $this->helpService = $helpService;
}

// Get help content
$help = $this->helpService->getByKey('deploy-button');

// Record interactions
$this->helpService->recordView('deploy-button', auth()->id());
$this->helpService->recordHelpful('ssl-toggle', auth()->id());
```

---

## 📦 Service Methods Cheat Sheet

### Content Retrieval
```php
// Get by key (cached 24h)
$help = $helpService->getByKey('deploy-button');

// Get by category (cached 1h)
$categoryHelp = $helpService->getByCategory('deployment');

// Search (no cache)
$results = $helpService->search('deploy');
```

### Interaction Recording
```php
// Record view
$helpService->recordView('key', auth()->id());

// Mark helpful
$helpService->recordHelpful('key', auth()->id());

// Mark not helpful
$helpService->recordNotHelpful('key', auth()->id());
```

### Analytics
```php
// Most popular (cached 1h)
$popular = $helpService->getPopularHelp(10);

// Most helpful (cached 1h)
$helpful = $helpService->getMostHelpful(10);

// Related content
$related = $helpService->getRelatedHelp('key', 5);
```

---

## 🗄️ Database Tables

### help_contents
Main storage for help content with analytics counters

### help_content_translations
Multi-language translations (en, ar, es, etc.)

### help_interactions
User interaction tracking (views, votes)

### help_content_related
Related content linking with relevance scores

---

## 📝 Creating Help Content

```php
use App\Models\HelpContent;

HelpContent::create([
    'key' => 'deploy-button',
    'category' => 'deployment',
    'ui_element_type' => 'button',
    'icon' => '🚀',
    'title' => 'Deploy Project',
    'brief' => 'Pulls latest code from GitHub and makes it live',
    'details' => [
        'Affects' => 'Project files, database, cache',
        'Changes reflect' => 'Immediately (30-90 seconds)',
        'See results' => 'Deployment logs, project status',
    ],
    'docs_url' => '/docs/deployments',
    'video_url' => null,
    'is_active' => true,
]);
```

---

## 🌐 Adding Translations

```php
$helpContent = HelpContent::where('key', 'deploy-button')->first();

$helpContent->translations()->create([
    'locale' => 'ar',
    'brief' => 'سحب أحدث كود من GitHub ونشره مباشرة',
    'details' => [
        'يؤثر على' => 'ملفات المشروع، قاعدة البيانات',
        'تظهر التغييرات' => 'فوراً (30-90 ثانية)',
    ],
]);
```

---

## 🔗 Model Relationships

```php
// HelpContent
$helpContent->translations;      // HasMany
$helpContent->interactions;      // HasMany
$helpContent->relatedContents;   // HasMany

// HelpContentTranslation
$translation->helpContent;       // BelongsTo

// HelpInteraction
$interaction->user;              // BelongsTo
$interaction->helpContent;       // BelongsTo

// HelpContentRelated
$related->helpContent;           // BelongsTo
$related->relatedHelpContent;   // BelongsTo
```

---

## 📊 Analytics Queries

```php
// Most viewed help topics
HelpContent::orderBy('view_count', 'desc')->limit(10)->get();

// Most helpful topics
HelpContent::orderByRaw('helpful_count / (helpful_count + not_helpful_count + 1) DESC')
    ->limit(10)
    ->get();

// Get helpfulness percentage
$helpContent->getHelpfulnessPercentage(); // 0-100
```

---

## 🔍 Scopes

```php
// Active help only
HelpContent::active()->get();

// By category
HelpContent::byCategory('deployment')->get();

// Search
HelpContent::search('deploy')->get();

// Combined
HelpContent::active()
    ->byCategory('deployment')
    ->search('auto')
    ->get();
```

---

## 🎯 Common Use Cases

### Deploy Button Help
```blade
<button wire:click="deploy" class="btn btn-primary">
    Deploy Project
</button>
<livewire:inline-help help-key="deploy-button" />
```

### SSL Toggle Help
```blade
<div class="form-check form-switch">
    <input type="checkbox" wire:model="sslEnabled" id="ssl">
    <label for="ssl">Enable SSL</label>
</div>
<livewire:inline-help help-key="ssl-toggle" :collapsible="true" />
```

### Notification Settings Help
```blade
<div class="form-group">
    <label>Email Notifications</label>
    <input type="email" wire:model="email">
</div>
<livewire:inline-help help-key="email-notifications" />
```

---

## 🛠️ Maintenance

### Clear Cache
```php
// In service
$helpService->clearCache();

// Or directly
Cache::flush();
```

### Update Help Content
```php
$help = HelpContent::where('key', 'deploy-button')->first();
$help->update([
    'brief' => 'New description',
    'details' => ['New' => 'details'],
]);
```

### Disable Help Content
```php
HelpContent::where('key', 'old-feature')->update(['is_active' => false]);
```

---

## 📄 Files Created

| File | Path | Size |
|------|------|------|
| Migration | `database/migrations/2025_12_10_160551_create_help_contents_tables.php` | 3.4 KB |
| HelpContent | `app/Models/HelpContent.php` | 2.7 KB |
| HelpContentTranslation | `app/Models/HelpContentTranslation.php` | 488 B |
| HelpInteraction | `app/Models/HelpInteraction.php` | 552 B |
| HelpContentRelated | `app/Models/HelpContentRelated.php` | 695 B |
| HelpContentService | `app/Services/HelpContentService.php` | 3.8 KB |

---

**Status:** ✅ Ready for use
**Documentation:** `storage/testing/inline-help-database-created.md`
**Reference:** `docs/inline-help/database-system.md`
