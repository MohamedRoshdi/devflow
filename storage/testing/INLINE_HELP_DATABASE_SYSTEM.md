# Inline Help System - Database Implementation
**Dynamic, Searchable, Multi-language Help Content**

---

## 🗄️ DATABASE SCHEMA

### Migration: Create Help Content Tables

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Main help content table
        Schema::create('help_contents', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., 'deploy-button', 'ssl-toggle'
            $table->string('category'); // 'deployment', 'security', 'domain'
            $table->string('ui_element_type'); // 'button', 'toggle', 'checkbox', 'input'
            $table->string('icon', 10)->default('ℹ️');
            $table->string('title');
            $table->text('brief'); // Short 1-line description
            $table->json('details'); // Array of key-value pairs
            $table->string('docs_url')->nullable();
            $table->string('video_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('view_count')->default(0);
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            $table->timestamps();
            
            $table->index('key');
            $table->index('category');
            $table->index('is_active');
        });
        
        // Help content translations
        Schema::create('help_content_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_content_id')->constrained()->onDelete('cascade');
            $table->string('locale', 5); // 'en', 'ar', 'es', etc.
            $table->text('brief');
            $table->json('details');
            $table->timestamps();
            
            $table->unique(['help_content_id', 'locale']);
            $table->index('locale');
        });
        
        // User help interactions (what they clicked, found useful)
        Schema::create('help_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('help_content_id')->constrained()->onDelete('cascade');
            $table->string('interaction_type'); // 'view', 'helpful', 'not_helpful', 'docs_click'
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['help_content_id', 'interaction_type']);
            $table->index('user_id');
        });
        
        // Help content relationships (related help topics)
        Schema::create('help_content_related', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_content_id')->constrained()->onDelete('cascade');
            $table->foreignId('related_help_content_id')->constrained('help_contents')->onDelete('cascade');
            $table->integer('relevance_score')->default(1); // 1-10
            $table->timestamps();
            
            $table->index('help_content_id');
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('help_content_related');
        Schema::dropIfExists('help_interactions');
        Schema::dropIfExists('help_content_translations');
        Schema::dropIfExists('help_contents');
    }
};
```

---

## 📦 MODELS

### HelpContent Model

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

class HelpContent extends Model
{
    protected $fillable = [
        'key',
        'category',
        'ui_element_type',
        'icon',
        'title',
        'brief',
        'details',
        'docs_url',
        'video_url',
        'is_active',
    ];
    
    protected $casts = [
        'details' => 'array',
        'is_active' => 'boolean',
    ];
    
    // Relationships
    
    public function translations(): HasMany
    {
        return $this->hasMany(HelpContentTranslation::class);
    }
    
    public function interactions(): HasMany
    {
        return $this->hasMany(HelpInteraction::class);
    }
    
    public function relatedContents(): HasMany
    {
        return $this->hasMany(HelpContentRelated::class);
    }
    
    // Scopes
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
    
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('brief', 'like', "%{$search}%")
              ->orWhere('key', 'like', "%{$search}%");
        });
    }
    
    // Accessors & Methods
    
    public function getLocalizedBrief(): string
    {
        $locale = App::getLocale();
        
        if ($locale === 'en') {
            return $this->brief;
        }
        
        $translation = $this->translations()
            ->where('locale', $locale)
            ->first();
        
        return $translation?->brief ?? $this->brief;
    }
    
    public function getLocalizedDetails(): array
    {
        $locale = App::getLocale();
        
        if ($locale === 'en') {
            return $this->details;
        }
        
        $translation = $this->translations()
            ->where('locale', $locale)
            ->first();
        
        return $translation?->details ?? $this->details;
    }
    
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }
    
    public function markHelpful(): void
    {
        $this->increment('helpful_count');
    }
    
    public function markNotHelpful(): void
    {
        $this->increment('not_helpful_count');
    }
    
    public function getHelpfulnessPercentage(): float
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        
        if ($total === 0) {
            return 0;
        }
        
        return round(($this->helpful_count / $total) * 100, 2);
    }
}
```

### HelpContentTranslation Model

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpContentTranslation extends Model
{
    protected $fillable = [
        'help_content_id',
        'locale',
        'brief',
        'details',
    ];
    
    protected $casts = [
        'details' => 'array',
    ];
    
    public function helpContent(): BelongsTo
    {
        return $this->belongsTo(HelpContent::class);
    }
}
```

### HelpInteraction Model

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpInteraction extends Model
{
    protected $fillable = [
        'user_id',
        'help_content_id',
        'interaction_type',
        'ip_address',
        'user_agent',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function helpContent(): BelongsTo
    {
        return $this->belongsTo(HelpContent::class);
    }
}
```

---

## 🔧 SERVICE LAYER

### HelpContentService

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HelpContent;
use App\Models\HelpInteraction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HelpContentService
{
    public function getByKey(string $key): ?HelpContent
    {
        return Cache::remember(
            "help_content_{$key}",
            now()->addDay(),
            fn() => HelpContent::active()
                ->where('key', $key)
                ->first()
        );
    }
    
    public function getByCategory(string $category): Collection
    {
        return Cache::remember(
            "help_content_category_{$category}",
            now()->addHour(),
            fn() => HelpContent::active()
                ->byCategory($category)
                ->orderBy('title')
                ->get()
        );
    }
    
    public function search(string $query): Collection
    {
        return HelpContent::active()
            ->search($query)
            ->orderBy('view_count', 'desc')
            ->limit(10)
            ->get();
    }
    
    public function recordView(string $key, ?int $userId = null): void
    {
        $helpContent = $this->getByKey($key);
        
        if (!$helpContent) {
            return;
        }
        
        $helpContent->incrementViewCount();
        
        HelpInteraction::create([
            'user_id' => $userId,
            'help_content_id' => $helpContent->id,
            'interaction_type' => 'view',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
    
    public function recordHelpful(string $key, ?int $userId = null): void
    {
        $helpContent = $this->getByKey($key);
        
        if (!$helpContent) {
            return;
        }
        
        $helpContent->markHelpful();
        
        HelpInteraction::create([
            'user_id' => $userId,
            'help_content_id' => $helpContent->id,
            'interaction_type' => 'helpful',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
    
    public function recordNotHelpful(string $key, ?int $userId = null): void
    {
        $helpContent = $this->getByKey($key);
        
        if (!$helpContent) {
            return;
        }
        
        $helpContent->markNotHelpful();
        
        HelpInteraction::create([
            'user_id' => $userId,
            'help_content_id' => $helpContent->id,
            'interaction_type' => 'not_helpful',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
    
    public function getPopularHelp(int $limit = 10): Collection
    {
        return Cache::remember(
            "popular_help_{$limit}",
            now()->addHour(),
            fn() => HelpContent::active()
                ->orderBy('view_count', 'desc')
                ->limit($limit)
                ->get()
        );
    }
    
    public function getMostHelpful(int $limit = 10): Collection
    {
        return Cache::remember(
            "most_helpful_help_{$limit}",
            now()->addHour(),
            fn() => HelpContent::active()
                ->where('helpful_count', '>', 0)
                ->orderByRaw('(helpful_count / (helpful_count + not_helpful_count + 1)) DESC')
                ->limit($limit)
                ->get()
        );
    }
    
    public function getRelatedHelp(string $key, int $limit = 5): Collection
    {
        $helpContent = $this->getByKey($key);
        
        if (!$helpContent) {
            return collect();
        }
        
        return $helpContent->relatedContents()
            ->with('relatedHelpContent')
            ->orderBy('relevance_score', 'desc')
            ->limit($limit)
            ->get()
            ->pluck('relatedHelpContent');
    }
}
```

---

## 🌐 LIVEWIRE COMPONENT

### InlineHelp Component

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Services\HelpContentService;
use Livewire\Component;

class InlineHelp extends Component
{
    public string $helpKey;
    public bool $collapsible = false;
    public bool $showDetails = false;
    
    protected HelpContentService $helpService;
    
    public function boot(HelpContentService $helpService): void
    {
        $this->helpService = $helpService;
    }
    
    public function mount(string $helpKey, bool $collapsible = false): void
    {
        $this->helpKey = $helpKey;
        $this->collapsible = $collapsible;
        
        // Record view
        $this->helpService->recordView(
            $this->helpKey,
            auth()->id()
        );
    }
    
    public function toggleDetails(): void
    {
        $this->showDetails = !$this->showDetails;
    }
    
    public function markHelpful(): void
    {
        $this->helpService->recordHelpful(
            $this->helpKey,
            auth()->id()
        );
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Thanks for your feedback!',
        ]);
    }
    
    public function markNotHelpful(): void
    {
        $this->helpService->recordNotHelpful(
            $this->helpKey,
            auth()->id()
        );
        
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Thanks! We\'ll improve this help content.',
        ]);
    }
    
    public function render()
    {
        $helpContent = $this->helpService->getByKey($this->helpKey);
        $relatedHelp = $this->helpService->getRelatedHelp($this->helpKey);
        
        return view('livewire.components.inline-help', [
            'helpContent' => $helpContent,
            'relatedHelp' => $relatedHelp,
        ]);
    }
}
```

### Blade View: `livewire/components/inline-help.blade.php`

```blade
@if($helpContent)
    <div class="inline-help mt-2" 
         wire:key="help-{{ $helpContent->key }}"
         x-data="{ expanded: @entangle('showDetails') }">
        
        @if($collapsible)
            <!-- Collapsible Version -->
            <div class="help-toggle" 
                 @click="expanded = !expanded"
                 role="button"
                 tabindex="0">
                <span class="help-icon">{{ $helpContent->icon }}</span>
                <strong class="help-title">{{ $helpContent->getLocalizedBrief() }}</strong>
                <span class="toggle-indicator ms-2" x-show="!expanded">▼</span>
                <span class="toggle-indicator ms-2" x-show="expanded">▲</span>
            </div>
            
            <div class="help-details ms-4 mt-2" 
                 x-show="expanded"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100">
                @include('components.help-details')
            </div>
        @else
            <!-- Always Visible Version -->
            <div class="help-content">
                <div class="help-brief mb-1">
                    <span class="help-icon">{{ $helpContent->icon }}</span>
                    <strong>{{ $helpContent->getLocalizedBrief() }}</strong>
                </div>
                
                <div class="help-details ms-4">
                    @include('components.help-details')
                </div>
            </div>
        @endif
        
        <!-- Feedback Buttons -->
        <div class="help-feedback ms-4 mt-2">
            <small class="text-muted me-2">Was this helpful?</small>
            <button wire:click="markHelpful" 
                    class="btn btn-sm btn-outline-success"
                    title="Yes, helpful">
                👍
            </button>
            <button wire:click="markNotHelpful" 
                    class="btn btn-sm btn-outline-danger"
                    title="No, not helpful">
                👎
            </button>
        </div>
        
        <!-- Related Help (if expanded) -->
        @if($showDetails && $relatedHelp->isNotEmpty())
            <div class="related-help ms-4 mt-3 p-2 bg-light border-start border-primary border-3">
                <small class="text-muted d-block mb-2">
                    <strong>Related Help:</strong>
                </small>
                @foreach($relatedHelp as $related)
                    <div class="related-item mb-1">
                        <a href="#" 
                           wire:click.prevent="$dispatch('show-help', { key: '{{ $related->key }}' })"
                           class="text-primary text-decoration-none">
                            {{ $related->icon }} {{ $related->title }}
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@else
    <!-- Fallback if help content not found -->
    <div class="inline-help mt-2 text-muted">
        <small>ℹ️ Help content coming soon...</small>
    </div>
@endif
```

### Partial: `components/help-details.blade.php`

```blade
@foreach($helpContent->getLocalizedDetails() as $label => $value)
    <div class="help-item text-muted small mb-1">
        • <span class="fw-semibold">{{ $label }}:</span> 
        <span class="text-secondary">{{ $value }}</span>
    </div>
@endforeach

@if($helpContent->docs_url)
    <div class="help-links mt-2">
        <a href="{{ $helpContent->docs_url }}" 
           target="_blank"
           class="text-primary text-decoration-none small">
            📚 Read full documentation →
        </a>
    </div>
@endif

@if($helpContent->video_url)
    <div class="help-links mt-1">
        <a href="{{ $helpContent->video_url }}" 
           target="_blank"
           class="text-primary text-decoration-none small">
            🎥 Watch video tutorial →
        </a>
    </div>
@endif
```

---

## 📥 SEEDER

### HelpContentSeeder

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HelpContent;
use Illuminate\Database\Seeder;

class HelpContentSeeder extends Seeder
{
    public function run(): void
    {
        $helpContents = [
            // Deployment Actions
            [
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
                    'During deployment' => 'Status shows "running" spinner',
                ],
                'docs_url' => '/docs/deployments',
            ],
            
            [
                'key' => 'rollback-button',
                'category' => 'deployment',
                'ui_element_type' => 'button',
                'icon' => '⏪',
                'title' => 'Rollback Deployment',
                'brief' => 'Revert to previous working deployment',
                'details' => [
                    'What happens' => 'Restore code and database to selected deployment',
                    'Affects' => 'All project files, database (if migration rollback)',
                    'Changes reflect' => '10-15 seconds',
                    'See results' => 'Project status, rollback log entry',
                    'Warning' => 'Cannot be undone - backup recommended',
                ],
                'docs_url' => '/docs/rollbacks',
            ],
            
            // Toggles
            [
                'key' => 'auto-deploy-toggle',
                'category' => 'deployment',
                'ui_element_type' => 'toggle',
                'icon' => '🔄',
                'title' => 'Auto-Deploy',
                'brief' => 'Automatically deploy when you push to GitHub',
                'details' => [
                    'When ON' => 'Every git push triggers deployment',
                    'When OFF' => 'You must click "Deploy" manually',
                    'Affects' => 'Deployment workflow',
                    'Changes reflect' => 'Next git push',
                    'See status' => 'Webhook indicator turns green',
                ],
                'docs_url' => '/docs/webhooks',
            ],
            
            [
                'key' => 'ssl-toggle',
                'category' => 'domain',
                'ui_element_type' => 'checkbox',
                'icon' => '🔒',
                'title' => 'Enable SSL',
                'brief' => 'Secures your domain with free HTTPS certificate',
                'details' => [
                    'What happens' => 'Let\'s Encrypt certificate auto-generated',
                    'Affects' => 'Domain security, SEO ranking',
                    'Changes reflect' => '5-10 minutes',
                    'See results' => 'Green padlock in browser, https:// URL',
                    'Auto-renews' => 'Every 90 days automatically',
                ],
                'docs_url' => '/docs/ssl',
                'video_url' => 'https://youtube.com/watch?v=ssl-setup',
            ],
            
            // Add more...
        ];
        
        foreach ($helpContents as $content) {
            HelpContent::create($content);
        }
        
        // Add translations for Arabic
        $this->addArabicTranslations();
    }
    
    private function addArabicTranslations(): void
    {
        $deployButton = HelpContent::where('key', 'deploy-button')->first();
        
        if ($deployButton) {
            $deployButton->translations()->create([
                'locale' => 'ar',
                'brief' => 'سحب أحدث كود من GitHub ونشره مباشرة',
                'details' => [
                    'يؤثر على' => 'ملفات المشروع، قاعدة البيانات، الذاكرة المؤقتة',
                    'تظهر التغييرات' => 'فوراً (30-90 ثانية)',
                    'شاهد النتائج' => 'سجلات النشر، حالة المشروع',
                ],
            ]);
        }
    }
}
```

---

## 🎨 USAGE IN BLADE

### Simple Usage
```blade
<button wire:click="deploy" class="btn btn-primary">
    Deploy Project
</button>

<livewire:inline-help help-key="deploy-button" />
```

### Collapsible Usage
```blade
<div class="form-check form-switch">
    <input type="checkbox" wire:model="autoDeployEnabled" id="autoDeploy">
    <label for="autoDeploy">Auto-Deploy on Git Push</label>
</div>

<livewire:inline-help 
    help-key="auto-deploy-toggle" 
    :collapsible="true" 
/>
```

---

## 📊 ANALYTICS DASHBOARD

Track which help topics users view most:

```php
// In HelpContentController
public function analytics()
{
    $mostViewed = HelpContent::orderBy('view_count', 'desc')
        ->limit(20)
        ->get();
    
    $mostHelpful = HelpContent::where('helpful_count', '>', 0)
        ->orderByRaw('helpful_count / (helpful_count + not_helpful_count + 1) DESC')
        ->limit(20)
        ->get();
    
    $leastHelpful = HelpContent::where('not_helpful_count', '>', 5)
        ->orderBy('not_helpful_count', 'desc')
        ->limit(20)
        ->get(); // These need improvement!
    
    return view('admin.help-analytics', compact(
        'mostViewed',
        'mostHelpful',
        'leastHelpful'
    ));
}
```

---

**File saved:** `storage/testing/INLINE_HELP_DATABASE_SYSTEM.md`
**Complete database-driven help system ready!**
