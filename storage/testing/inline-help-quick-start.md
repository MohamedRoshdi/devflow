# Inline Help System - Quick Start Guide
**For Developers Using the Components**

---

## 🚀 Quick Usage

### 1. Dynamic Help (Livewire - Recommended)

```blade
<!-- In any Blade view -->
<button wire:click="deploy" class="btn btn-primary">
    🚀 Deploy Project
</button>

<livewire:inline-help
    help-key="deploy-button"
    wire:key="help-deploy"
/>
```

**Features:**
- ✅ Automatic view tracking
- ✅ Real-time feedback (thumbs up/down)
- ✅ Loading states
- ✅ Related help suggestions

---

### 2. Collapsible Help (Livewire)

```blade
<div class="form-check form-switch">
    <input type="checkbox" wire:model="autoDeployEnabled" id="autoDeploy">
    <label for="autoDeploy">Auto-Deploy on Push</label>
</div>

<livewire:inline-help
    help-key="auto-deploy-toggle"
    :collapsible="true"
    wire:key="help-auto-deploy"
/>
```

**Features:**
- ✅ Expandable/collapsible
- ✅ Smooth animations
- ✅ Saves screen space

---

### 3. Static Help (Blade Component)

```blade
<x-inline-help
    icon="🔒"
    brief="Secures your domain with free HTTPS certificate"
    :details="[
        'What happens' => 'Let\'s Encrypt certificate auto-generated',
        'Affects' => 'Domain security, SEO ranking',
        'Changes reflect' => '5-10 minutes'
    ]"
    docs-link="/docs/ssl"
/>
```

**Use When:**
- ❌ No database connection
- ❌ No analytics needed
- ✅ Simple static help text

---

## 📋 Component Props

### Livewire Component

| Prop | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `help-key` | string | ✅ Yes | - | Unique key in database |
| `collapsible` | bool | ❌ No | `false` | Enable expand/collapse |
| `wire:key` | string | ✅ Yes | - | Unique Livewire key |

### Blade Component

| Prop | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `icon` | string | ❌ No | `ℹ️` | Emoji or icon |
| `brief` | string | ✅ Yes | - | Short description |
| `details` | array | ✅ Yes | `[]` | Key-value details |
| `docs-link` | string | ❌ No | `#` | Documentation URL |
| `help-topic` | string | ❌ No | `''` | Topic for modal |
| `collapsible` | bool | ❌ No | `false` | Enable collapse |

---

## 🎯 Where to Use

### Projects Page
```blade
<!-- Deploy button -->
<livewire:inline-help help-key="deploy-button" wire:key="help-deploy" />

<!-- Rollback button -->
<livewire:inline-help help-key="rollback-button" wire:key="help-rollback" />

<!-- Delete project -->
<livewire:inline-help help-key="delete-project-button" wire:key="help-delete" />
```

### Settings Page
```blade
<!-- Auto-deploy toggle -->
<livewire:inline-help help-key="auto-deploy-toggle" :collapsible="true" wire:key="help-auto-deploy" />

<!-- Run migrations -->
<livewire:inline-help help-key="run-migrations-checkbox" wire:key="help-migrations" />

<!-- Clear cache -->
<livewire:inline-help help-key="clear-cache-checkbox" wire:key="help-cache" />
```

### Domains Page
```blade
<!-- SSL toggle -->
<livewire:inline-help help-key="ssl-toggle" wire:key="help-ssl" />

<!-- Force HTTPS -->
<livewire:inline-help help-key="force-https-toggle" wire:key="help-https" />

<!-- Primary domain -->
<livewire:inline-help help-key="primary-domain-toggle" wire:key="help-primary" />
```

---

## 🎨 Styling Classes

### Available CSS Classes

```css
.inline-help          /* Main container */
.help-icon            /* Icon styling */
.help-brief           /* Brief description */
.help-details         /* Details container */
.help-item            /* Individual detail item */
.help-toggle          /* Collapsible toggle */
.help-feedback        /* Feedback buttons */
.related-help         /* Related topics */
.help-loading         /* Loading state */
```

### Custom Styling Example

```blade
<div class="my-custom-help-wrapper">
    <livewire:inline-help help-key="deploy-button" wire:key="help-deploy" />
</div>

<style>
    .my-custom-help-wrapper .inline-help {
        background: #f0f9ff;
        padding: 1rem;
        border-radius: 0.5rem;
    }
</style>
```

---

## 🔧 Advanced Usage

### 1. Programmatically Show Help

```blade
<button wire:click="$dispatch('show-help', { key: 'deploy-button' })">
    Show Deploy Help
</button>
```

### 2. Listen for Feedback Events

```php
// In your Livewire component
#[On('help-feedback-received')]
public function handleFeedback($helpKey, $type)
{
    // Do something when user gives feedback
    $this->dispatch('notify', [
        'message' => "Thanks for feedback on {$helpKey}!"
    ]);
}
```

### 3. Refresh Help Content

```blade
<!-- Trigger help content refresh -->
<button wire:click="$dispatch('help-content-updated')">
    Refresh Help
</button>
```

---

## 📊 Help Content Keys

### Deployment Help Keys
- `deploy-button` - Deploy project button
- `rollback-button` - Rollback deployment
- `auto-deploy-toggle` - Auto-deploy toggle
- `run-migrations-checkbox` - Run migrations option
- `clear-cache-checkbox` - Clear cache option

### Domain Help Keys
- `ssl-toggle` - SSL/HTTPS checkbox
- `force-https-toggle` - Force HTTPS redirect
- `primary-domain-toggle` - Primary domain setting
- `domain-input` - Domain input field

### Server Help Keys
- `add-server-button` - Add server button
- `monitor-resources-toggle` - Resource monitoring
- `ssh-access-button` - SSH terminal access

### Notification Help Keys
- `slack-notifications-toggle` - Slack integration
- `email-on-failure-checkbox` - Email notifications

---

## ⚠️ Important Notes

### 1. Always Use Unique Wire Keys
```blade
<!-- ✅ GOOD -->
<livewire:inline-help help-key="deploy" wire:key="help-deploy" />
<livewire:inline-help help-key="rollback" wire:key="help-rollback" />

<!-- ❌ BAD (duplicate keys) -->
<livewire:inline-help help-key="deploy" wire:key="help-1" />
<livewire:inline-help help-key="rollback" wire:key="help-1" />
```

### 2. Database Required for Livewire
```blade
<!-- Livewire component needs database -->
<livewire:inline-help help-key="deploy-button" wire:key="help-deploy" />

<!-- Use Blade component if no database -->
<x-inline-help
    icon="🚀"
    brief="Deploy your project"
    :details="['Affects' => 'All files']"
/>
```

### 3. Performance Considerations
```php
// Help content is cached for 24 hours
// Clear cache after updating help content:
php artisan cache:clear
```

---

## 🐛 Troubleshooting

### Help Not Showing?

**Check 1:** Is help content in database?
```php
HelpContent::where('key', 'deploy-button')->exists()
```

**Check 2:** Is help content active?
```php
HelpContent::where('key', 'deploy-button')
    ->where('is_active', true)
    ->exists()
```

**Check 3:** Clear cache
```bash
php artisan cache:clear
```

### Feedback Not Recording?

**Check 1:** Verify HelpInteraction model exists
```bash
php artisan tinker
> App\Models\HelpInteraction::count()
```

**Check 2:** Check Livewire is working
```bash
php artisan route:list | grep livewire
```

### Styling Issues?

**Check 1:** CSS compiled?
```bash
npm run build
```

**Check 2:** CSS imported in app.css?
```css
/* resources/css/app.css */
@import './inline-help.css';
```

---

## 📈 Analytics Queries

### Most Viewed Help Topics
```php
HelpContent::orderBy('view_count', 'desc')
    ->limit(10)
    ->get(['key', 'title', 'view_count']);
```

### Most Helpful Content
```php
HelpContent::where('helpful_count', '>', 0)
    ->orderByRaw('helpful_count / (helpful_count + not_helpful_count + 1) DESC')
    ->limit(10)
    ->get(['key', 'title', 'helpful_count', 'not_helpful_count']);
```

### Content Needing Improvement
```php
HelpContent::where('not_helpful_count', '>', 5)
    ->orderBy('not_helpful_count', 'desc')
    ->get(['key', 'title', 'not_helpful_count']);
```

---

## 🎓 Best Practices

1. ✅ **Always use Livewire version** when database is available
2. ✅ **Keep help text brief** - max 2 lines
3. ✅ **Use emojis for visual appeal** but don't overdo it
4. ✅ **Provide docs links** for complex features
5. ✅ **Test on mobile** - help should be readable on small screens
6. ✅ **Use collapsible mode** for complex features with many details
7. ✅ **Track feedback** - improve content based on user responses

---

## 🚀 Next Steps

1. Add help to all critical UI elements
2. Monitor analytics to see what users need help with
3. Update help content based on feedback
4. Add video tutorials for complex features
5. Translate help content to other languages

---

**Created:** 2025-12-10
**For:** DevFlow Pro Project
**By:** Claude Code Assistant
