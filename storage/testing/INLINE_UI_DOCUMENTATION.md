# DevFlow Pro - Inline UI Documentation System
**Brief Help Text Below Every Button, Checkbox, and Toggle**

---

## 🎯 CONCEPT: Contextual Inline Help

Every UI element should have:
1. **Brief** (1 line) - What it does
2. **Effect** - What changes
3. **Reflection** - Where you'll see the change
4. **Link** - "Learn more →" to detailed docs

---

## 📋 UI DOCUMENTATION PATTERNS

### Pattern 1: Action Buttons

```html
<!-- Deploy Button Example -->
<button wire:click="deploy" class="btn-primary">
    Deploy Project
</button>
<p class="help-text">
    📦 <strong>Pulls latest code from GitHub and makes it live</strong>
    <br>
    <span class="text-muted">
        • Affects: Project files, database, cache
        <br>
        • Changes reflect: Immediately (30-90 seconds)
        <br>
        • See results: Deployment logs, project status
        <br>
        <a href="#" wire:click="showHelp('deploy')" class="text-primary">
            Learn more about deployments →
        </a>
    </span>
</p>
```

**Visual Result:**
```
┌────────────────────────────────────────────────────────┐
│  [Deploy Project]                                      │
│                                                        │
│  📦 Pulls latest code from GitHub and makes it live    │
│     • Affects: Project files, database, cache         │
│     • Changes reflect: Immediately (30-90 seconds)    │
│     • See results: Deployment logs, project status    │
│     Learn more about deployments →                    │
└────────────────────────────────────────────────────────┘
```

---

### Pattern 2: Toggle Switches

```html
<!-- Auto-Deploy Toggle Example -->
<div class="form-check form-switch">
    <input type="checkbox" wire:model="autoDeployEnabled" id="autoDeploy">
    <label for="autoDeploy">Auto-Deploy on Git Push</label>
</div>
<p class="help-text">
    🔄 <strong>Automatically deploy when you push to GitHub</strong>
    <br>
    <span class="text-muted">
        • When ON: Every git push triggers deployment
        <br>
        • When OFF: You must click "Deploy" manually
        <br>
        • Affects: Deployment workflow
        <br>
        • Changes reflect: Next git push
        <br>
        • See status: Webhook indicator turns green
        <br>
        <a href="#" wire:click="showHelp('auto-deploy')" class="text-primary">
            Learn more about webhooks →
        </a>
    </span>
</p>
```

**Visual Result:**
```
┌────────────────────────────────────────────────────────┐
│  [●] Auto-Deploy on Git Push                          │
│                                                        │
│  🔄 Automatically deploy when you push to GitHub       │
│     • When ON: Every git push triggers deployment     │
│     • When OFF: You must click "Deploy" manually      │
│     • Affects: Deployment workflow                    │
│     • Changes reflect: Next git push                  │
│     • See status: Webhook indicator turns green       │
│     Learn more about webhooks →                       │
└────────────────────────────────────────────────────────┘
```

---

### Pattern 3: Checkboxes

```html
<!-- SSL Enabled Checkbox Example -->
<div class="form-check">
    <input type="checkbox" wire:model="sslEnabled" id="ssl">
    <label for="ssl">Enable SSL (HTTPS)</label>
</div>
<p class="help-text">
    🔒 <strong>Secures your domain with free HTTPS certificate</strong>
    <br>
    <span class="text-muted">
        • What happens: Let's Encrypt certificate auto-generated
        <br>
        • Affects: Domain security, SEO ranking
        <br>
        • Changes reflect: 5-10 minutes
        <br>
        • See results: Green padlock in browser, https:// URL
        <br>
        • Auto-renews: Every 90 days automatically
        <br>
        <a href="#" wire:click="showHelp('ssl')" class="text-primary">
            Learn more about SSL certificates →
        </a>
    </span>
</p>
```

---

### Pattern 4: Select Dropdowns

```html
<!-- PHP Version Selector Example -->
<select wire:model="phpVersion" class="form-select">
    <option value="8.4">PHP 8.4 (Latest)</option>
    <option value="8.3">PHP 8.3</option>
    <option value="8.2">PHP 8.2</option>
</select>
<p class="help-text">
    ⚙️ <strong>Choose PHP version for your project</strong>
    <br>
    <span class="text-muted">
        • Recommended: 8.4 (fastest, latest features)
        <br>
        • Affects: Application performance, available features
        <br>
        • Changes reflect: Next deployment
        <br>
        • See results: php -v in terminal, phpinfo()
        <br>
        • Note: Ensure your code is compatible
        <br>
        <a href="#" wire:click="showHelp('php-version')" class="text-primary">
            Learn more about PHP versions →
        </a>
    </span>
</p>
```

---

### Pattern 5: Input Fields

```html
<!-- Domain Input Example -->
<input type="text" 
       wire:model="domain" 
       placeholder="example.com"
       class="form-control">
<p class="help-text">
    🌐 <strong>Your website address (without http://)</strong>
    <br>
    <span class="text-muted">
        • Example: myapp.com or app.mycompany.com
        <br>
        • What happens: Nginx configured, SSL generated
        <br>
        • Affects: Where users access your site
        <br>
        • Changes reflect: After DNS propagation (5-10 min)
        <br>
        • Requirements: Point DNS to server IP
        <br>
        • See results: Visit domain in browser
        <br>
        <a href="#" wire:click="showHelp('domains')" class="text-primary">
            Learn more about domain setup →
        </a>
    </span>
</p>
```

---

## 📚 COMPLETE UI ELEMENT DOCUMENTATION

### PROJECT MANAGEMENT PAGE

#### 1. Create Project Button
```
[+ Create New Project]

📋 Register a new project to deploy and manage
   • What happens: Git repo cloned, dependencies installed, .env configured
   • Affects: Adds project to dashboard, creates database entries
   • Changes reflect: Immediately - appears in project list
   • See results: Projects page, new project card
   Learn more about projects →
```

#### 2. Deploy Button
```
[🚀 Deploy]

📦 Pull latest code from Git and make it live
   • What happens: Git pull, composer install, migrations, cache clear
   • Affects: Project files, database schema, running application
   • Changes reflect: 30-90 seconds
   • See results: Deployment logs, updated website
   • During deployment: Status shows "running" spinner
   Learn more about deployments →
```

#### 3. Rollback Button
```
[⏪ Rollback]

↩️ Revert to previous working deployment
   • What happens: Restore code and database to selected deployment
   • Affects: All project files, database (if migration rollback)
   • Changes reflect: 10-15 seconds
   • See results: Project status, rollback log entry
   • Warning: Can't be undone - backup recommended
   Learn more about rollbacks →
```

#### 4. Delete Project Button
```
[🗑️ Delete Project]

⚠️ Remove project from DevFlow management
   • What happens: Monitoring stopped, webhooks disabled
   • Affects: Project removed from dashboard
   • Changes reflect: Immediately
   • Optional: Can also delete files from server
   • Warning: Cannot be undone!
   • Files: Remain on server unless you check "Delete Files"
   Learn more about project deletion →
```

---

### SERVER MANAGEMENT PAGE

#### 5. Add Server Button
```
[+ Add Server]

🖥️ Connect a new server to DevFlow
   • What happens: SSH connection tested, server info collected
   • Affects: Adds server to monitoring, enables project deployment
   • Changes reflect: Immediately if SSH works
   • See results: Servers list, connection status
   • Requirements: Valid SSH key, accessible IP
   Learn more about servers →
```

#### 6. Monitor Resources Toggle
```
[●] Enable Resource Monitoring

📊 Track CPU, RAM, disk usage every 5 minutes
   • When ON: Metrics collected and graphed
   • When OFF: No monitoring (saves resources)
   • Affects: Server metrics dashboard, alerts
   • Changes reflect: Next 5-minute interval
   • See results: Server metrics charts
   • Storage: Metrics kept for 30 days
   Learn more about monitoring →
```

#### 7. SSH Access Button
```
[💻 SSH Terminal]

🔗 Open browser-based terminal to server
   • What happens: Secure SSH connection opened
   • Affects: Nothing (read-only by default)
   • Changes reflect: N/A (terminal session)
   • See results: Terminal window opens
   • Access level: Based on your role permissions
   • Session: Auto-closes after 30 min idle
   Learn more about SSH access →
```

---

### DEPLOYMENT SETTINGS

#### 8. Auto-Deploy Toggle
```
[○] Auto-Deploy on Push

🔄 Deploy automatically when you push to GitHub
   • When ON: Webhook triggers deployment on every push
   • When OFF: Manual deployment required
   • Affects: Development workflow
   • Changes reflect: Immediately (next push)
   • See results: Webhook status indicator (green)
   • Webhook URL: Copied to GitHub repository settings
   Learn more about auto-deploy →
```

#### 9. Run Migrations Checkbox
```
[✓] Run Database Migrations

🗄️ Update database schema during deployment
   • When ON: php artisan migrate runs automatically
   • When OFF: Migrations skipped (manual run needed)
   • Affects: Database structure
   • Changes reflect: During deployment
   • See results: Deployment logs, new tables/columns
   • Rollback: Available if migration fails
   Learn more about migrations →
```

#### 10. Clear Cache Checkbox
```
[✓] Clear Caches After Deploy

🧹 Remove old cached data after deployment
   • When ON: Config, route, view caches cleared
   • When OFF: Old cache remains (may cause issues)
   • Affects: Application performance temporarily
   • Changes reflect: Immediately after deployment
   • See results: Fresh config loaded, templates recompiled
   • Recommended: Always keep ON
   Learn more about caching →
```

---

### DOMAIN SETTINGS

#### 11. SSL Enabled Checkbox
```
[✓] Enable SSL/HTTPS

🔒 Secure domain with free Let's Encrypt certificate
   • What happens: Certificate requested and installed
   • Affects: Site security, browser trust, SEO
   • Changes reflect: 5-10 minutes
   • See results: https:// URL, green padlock
   • Auto-renews: 30 days before expiration
   • Requirements: Domain must point to server
   Learn more about SSL →
```

#### 12. Force HTTPS Toggle
```
[●] Force HTTPS Redirect

🔐 Redirect all HTTP traffic to HTTPS
   • When ON: http:// → https:// automatic
   • When OFF: Both HTTP and HTTPS accessible
   • Affects: All site visitors
   • Changes reflect: Immediately
   • See results: HTTP URLs redirect to HTTPS
   • Recommended: ON (for security)
   Learn more about HTTPS →
```

#### 13. Primary Domain Toggle
```
[○] Set as Primary Domain

⭐ Main domain (others redirect here)
   • When ON: All other domains redirect to this one
   • When OFF: Domain accessible normally
   • Affects: SEO, canonical URLs
   • Changes reflect: Immediately
   • See results: Other domains → 301 redirect
   • Use case: myapp.com primary, www.myapp.com redirects
   Learn more about primary domains →
```

---

### NOTIFICATION SETTINGS

#### 14. Slack Notifications Toggle
```
[●] Send to Slack

💬 Post deployment updates to Slack channel
   • When ON: Success/failure messages sent
   • When OFF: No Slack notifications
   • Affects: Team awareness
   • Changes reflect: Next deployment
   • See results: Message in configured Slack channel
   • Setup: Webhook URL required
   Learn more about Slack integration →
```

#### 15. Email on Failure Checkbox
```
[✓] Email on Deployment Failure

📧 Get notified when deployments fail
   • When ON: Email sent on errors only
   • When OFF: No failure emails
   • Affects: Error awareness
   • Changes reflect: Next failed deployment
   • See results: Email inbox
   • Recipients: Project team members
   Learn more about notifications →
```

---

### SECURITY SETTINGS

#### 16. Two-Factor Auth Toggle
```
[○] Enable 2FA

🔐 Require phone code + password to login
   • When ON: 6-digit code required after password
   • When OFF: Password only
   • Affects: Your account security
   • Changes reflect: Next login
   • See results: 2FA prompt during login
   • Setup: Scan QR code with authenticator app
   Learn more about 2FA →
```

#### 17. IP Whitelist Toggle
```
[○] Enable IP Whitelist

🛡️ Allow access only from specific IP addresses
   • When ON: Only listed IPs can access DevFlow
   • When OFF: Any IP can access (with login)
   • Affects: Access security
   • Changes reflect: Immediately
   • See results: Unauthorized IPs get 403 error
   • Warning: Ensure your IP is whitelisted!
   Learn more about IP whitelisting →
```

---

### BACKUP SETTINGS

#### 18. Auto-Backup Toggle
```
[●] Automatic Backups

💾 Backup database daily at 2 AM
   • When ON: Daily backups created automatically
   • When OFF: Manual backups only
   • Affects: Data safety, recovery options
   • Changes reflect: Next 2 AM
   • See results: Backups list, file sizes
   • Storage: Last 7 daily, 4 weekly, 12 monthly
   Learn more about backups →
```

#### 19. Backup to S3 Checkbox
```
[✓] Upload to Amazon S3

☁️ Store backups in cloud (off-server)
   • When ON: Backups copied to S3 bucket
   • When OFF: Local storage only
   • Affects: Backup redundancy, disaster recovery
   • Changes reflect: After each backup
   • See results: S3 bucket file list
   • Cost: S3 storage fees apply
   Learn more about cloud backups →
```

---

### TEAM MANAGEMENT

#### 20. Admin Role Radio
```
(•) Admin Role

👔 Can deploy, configure, and manage projects
   • Permissions: Deploy, view logs, edit settings
   • Cannot: Delete projects, manage billing
   • Affects: User's capabilities in DevFlow
   • Changes reflect: Immediately
   • See results: Menu options, available actions
   • Use case: Senior developers
   Learn more about roles →
```

#### 21. Developer Role Radio
```
( ) Developer Role

👨‍💻 Can deploy and view logs only
   • Permissions: Deploy projects, view logs/metrics
   • Cannot: Edit settings, add domains, manage team
   • Affects: Limited access to features
   • Changes reflect: Immediately
   • See results: Reduced menu options
   • Use case: Junior developers, contractors
   Learn more about permissions →
```

---

### PERFORMANCE SETTINGS

#### 22. CDN Enabled Toggle
```
[○] Enable CDN

🌍 Serve static files from global edge locations
   • When ON: JS/CSS/images served from nearest CDN
   • When OFF: Files served from your server
   • Affects: Page load speed worldwide
   • Changes reflect: Next deployment
   • See results: Faster load times, reduced bandwidth
   • Providers: Cloudflare, CloudFront supported
   Learn more about CDN →
```

#### 23. Asset Minification Checkbox
```
[✓] Minify Assets

📦 Compress CSS and JavaScript files
   • When ON: Files minified during build
   • When OFF: Full-size files served
   • Affects: File sizes, load speed
   • Changes reflect: Next npm build
   • See results: 30-50% smaller file sizes
   • Build time: Adds 10-20 seconds
   Learn more about optimization →
```

---

### MONITORING SETTINGS

#### 24. Health Check Interval
```
[Dropdown: 5 minutes ▼]

⏱️ How often to ping your site
   • Options: 1, 5, 10, 15, 30 minutes
   • Affects: Alert speed vs server load
   • Changes reflect: Next check cycle
   • See results: Health check dashboard
   • Recommended: 5 min (balance of both)
   • Cost: More frequent = more requests
   Learn more about health checks →
```

#### 25. Send Alert After
```
[Input: 3] consecutive failures

🚨 Alert only after multiple failures
   • Example: 3 failures = 15 min downtime (5min interval)
   • Affects: False positive prevention
   • Changes reflect: Next failure
   • See results: Fewer false alarms
   • Recommended: 2-3 failures
   • Use case: Prevents alerts during restarts
   Learn more about alerting →
```

---

### DOCKER SETTINGS

#### 26. Use Docker Compose Toggle
```
[●] Docker Compose Deployment

🐳 Deploy as containerized application
   • When ON: docker-compose up runs
   • When OFF: Traditional deployment (PHP-FPM)
   • Affects: Deployment process, isolation
   • Changes reflect: Next deployment
   • See results: Container list, docker ps
   • Requirements: docker-compose.yml file
   Learn more about Docker →
```

#### 27. Restart Policy Dropdown
```
[Dropdown: always ▼]

🔄 Container restart behavior
   • always: Restart on crash or server reboot
   • unless-stopped: Restart except manual stop
   • on-failure: Restart only on error
   • Affects: Uptime, recovery
   • Changes reflect: Next container start
   • See results: Container stays running
   • Recommended: always (for production)
   Learn more about restart policies →
```

---

## 🎨 STYLING GUIDE FOR HELP TEXT

### CSS Classes

```css
/* Help text container */
.help-text {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.25rem;
    line-height: 1.5;
}

/* Strong emphasis */
.help-text strong {
    color: #495057;
    font-weight: 600;
}

/* Bullet points */
.help-text ul {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
}

/* Learn more link */
.help-text a {
    font-size: 0.875rem;
    text-decoration: none;
}

.help-text a:hover {
    text-decoration: underline;
}

/* Icon */
.help-text-icon {
    font-size: 1rem;
    margin-right: 0.25rem;
}

/* Muted details */
.text-muted {
    color: #868e96 !important;
}

/* Collapsible advanced help */
.help-text-advanced {
    display: none;
    margin-top: 0.5rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-left: 3px solid #007bff;
    border-radius: 0.25rem;
}

.help-text-advanced.show {
    display: block;
}
```

---

## 📱 RESPONSIVE CONSIDERATIONS

### Mobile Devices
```html
<!-- Collapsible help on mobile -->
<div class="help-text">
    <span class="d-md-none">
        <a href="#" @click="showHelp = !showHelp">
            ℹ️ What does this do?
        </a>
    </span>
    <div class="d-none d-md-block" x-show="showHelp">
        📦 <strong>Full help text here</strong>
        <!-- Full details -->
    </div>
</div>
```

### Tablet
```html
<!-- Show brief, expand for details -->
<div class="help-text">
    <div>
        📦 <strong>Brief explanation</strong>
    </div>
    <details class="mt-2">
        <summary class="text-primary cursor-pointer">
            Show details ▼
        </summary>
        <div class="mt-2">
            • Affects: ...
            • Changes reflect: ...
        </div>
    </details>
</div>
```

---

## 🔧 LIVEWIRE COMPONENT INTEGRATION

### Component Method
```php
<?php

namespace App\Livewire\Projects;

use Livewire\Component;

class ProjectSettings extends Component
{
    public bool $autoDeployEnabled = false;
    public bool $showHelp = false;
    public string $currentHelpTopic = '';
    
    public function showHelp(string $topic): void
    {
        $this->currentHelpTopic = $topic;
        $this->showHelp = true;
        
        // Optionally dispatch to modal
        $this->dispatch('show-help-modal', topic: $topic);
    }
    
    public function render()
    {
        return view('livewire.projects.project-settings', [
            'helpContent' => $this->getHelpContent(),
        ]);
    }
    
    private function getHelpContent(): array
    {
        return [
            'auto-deploy' => [
                'title' => 'Auto-Deploy on Git Push',
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
            'ssl' => [
                'title' => 'SSL/HTTPS Certificates',
                'brief' => 'Secures your domain with free HTTPS certificate',
                'details' => [
                    'What happens' => 'Let\'s Encrypt certificate auto-generated',
                    'Affects' => 'Domain security, SEO ranking',
                    'Changes reflect' => '5-10 minutes',
                    'See results' => 'Green padlock in browser',
                    'Auto-renews' => 'Every 90 days automatically',
                ],
                'docs_url' => '/docs/ssl',
            ],
            // ... more topics
        ];
    }
}
```

### Blade Template
```blade
<div class="form-check form-switch">
    <input type="checkbox" 
           wire:model.live="autoDeployEnabled" 
           id="autoDeploy"
           class="form-check-input">
    <label for="autoDeploy" class="form-check-label">
        Auto-Deploy on Git Push
    </label>
</div>

<x-inline-help 
    icon="🔄"
    brief="Automatically deploy when you push to GitHub"
    :details="[
        'When ON' => 'Every git push triggers deployment',
        'When OFF' => 'You must click \'Deploy\' manually',
        'Affects' => 'Deployment workflow',
        'Changes reflect' => 'Next git push',
        'See status' => 'Webhook indicator turns green',
    ]"
    docs-link="/docs/webhooks"
    help-topic="auto-deploy"
/>
```

---

## 🧩 REUSABLE BLADE COMPONENT

### File: `resources/views/components/inline-help.blade.php`

```blade
@props([
    'icon' => 'ℹ️',
    'brief' => '',
    'details' => [],
    'docsLink' => '#',
    'helpTopic' => '',
    'collapsible' => false,
])

<div class="help-text mt-2">
    @if($collapsible)
        <details class="help-details">
            <summary class="help-summary cursor-pointer">
                <span class="help-text-icon">{{ $icon }}</span>
                <strong>{{ $brief }}</strong>
            </summary>
            <div class="help-content mt-2 ms-4">
                @foreach($details as $label => $value)
                    <div class="help-item">
                        <span class="text-muted">• {{ $label }}:</span>
                        <span>{{ $value }}</span>
                    </div>
                @endforeach
                
                @if($docsLink !== '#')
                    <div class="mt-2">
                        <a href="{{ $docsLink }}" 
                           class="text-primary text-decoration-none"
                           target="_blank">
                            Learn more →
                        </a>
                    </div>
                @endif
                
                @if($helpTopic)
                    <div class="mt-2">
                        <a href="#" 
                           wire:click.prevent="showHelp('{{ $helpTopic }}')"
                           class="text-primary text-decoration-none">
                            View detailed guide →
                        </a>
                    </div>
                @endif
            </div>
        </details>
    @else
        <div class="help-content">
            <div class="help-brief mb-1">
                <span class="help-text-icon">{{ $icon }}</span>
                <strong>{{ $brief }}</strong>
            </div>
            <div class="help-details ms-4">
                @foreach($details as $label => $value)
                    <div class="help-item text-muted">
                        • {{ $label }}: <span class="text-secondary">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
            
            @if($docsLink !== '#' || $helpTopic)
                <div class="help-links ms-4 mt-1">
                    @if($docsLink !== '#')
                        <a href="{{ $docsLink }}" 
                           class="text-primary text-decoration-none me-3"
                           target="_blank">
                            📚 Learn more →
                        </a>
                    @endif
                    
                    @if($helpTopic)
                        <a href="#" 
                           wire:click.prevent="$dispatch('show-help-modal', { topic: '{{ $helpTopic }}' })"
                           class="text-primary text-decoration-none">
                            📖 View detailed guide →
                        </a>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>

<style>
    .help-text {
        font-size: 0.875rem;
        line-height: 1.6;
    }
    
    .help-text-icon {
        font-size: 1rem;
        margin-right: 0.25rem;
    }
    
    .help-item {
        margin-bottom: 0.25rem;
    }
    
    .help-summary {
        list-style: none;
        cursor: pointer;
        user-select: none;
    }
    
    .help-summary::-webkit-details-marker {
        display: none;
    }
    
    .help-summary::before {
        content: '▶';
        display: inline-block;
        margin-right: 0.5rem;
        transition: transform 0.2s;
    }
    
    details[open] .help-summary::before {
        transform: rotate(90deg);
    }
</style>
```

---

## 📖 USAGE EXAMPLES

### Example 1: Simple Button Help
```blade
<button wire:click="deploy" class="btn btn-primary">
    🚀 Deploy Project
</button>

<x-inline-help
    icon="📦"
    brief="Pulls latest code from GitHub and makes it live"
    :details="[
        'Affects' => 'Project files, database, cache',
        'Changes reflect' => 'Immediately (30-90 seconds)',
        'See results' => 'Deployment logs, project status',
    ]"
    docs-link="/docs/deployments"
    help-topic="deploy"
/>
```

### Example 2: Toggle with Collapsible Help
```blade
<div class="form-check form-switch">
    <input type="checkbox" wire:model="sslEnabled" id="ssl">
    <label for="ssl">Enable SSL (HTTPS)</label>
</div>

<x-inline-help
    icon="🔒"
    brief="Secures your domain with free HTTPS certificate"
    :details="[
        'What happens' => 'Let\'s Encrypt certificate auto-generated',
        'Affects' => 'Domain security, SEO ranking',
        'Changes reflect' => '5-10 minutes',
        'See results' => 'Green padlock in browser, https:// URL',
        'Auto-renews' => 'Every 90 days automatically',
    ]"
    docs-link="/docs/ssl"
    help-topic="ssl-certificates"
    :collapsible="true"
/>
```

### Example 3: Input Field Help
```blade
<input type="text" 
       wire:model="domain" 
       placeholder="example.com"
       class="form-control">

<x-inline-help
    icon="🌐"
    brief="Your website address (without http://)"
    :details="[
        'Example' => 'myapp.com or app.mycompany.com',
        'What happens' => 'Nginx configured, SSL generated',
        'Affects' => 'Where users access your site',
        'Changes reflect' => 'After DNS propagation (5-10 min)',
        'Requirements' => 'Point DNS to server IP',
    ]"
    docs-link="/docs/domains"
/>
```

---

## 🎯 IMPLEMENTATION CHECKLIST

### Phase 1: Core Components (Week 1)
- [ ] Create `inline-help.blade.php` component
- [ ] Add CSS styling
- [ ] Create help content array in Livewire components
- [ ] Implement `showHelp()` method

### Phase 2: Main Features (Week 2)
- [ ] Add help to all project management actions
- [ ] Add help to all server management toggles
- [ ] Add help to deployment settings
- [ ] Add help to domain settings

### Phase 3: Advanced Features (Week 3)
- [ ] Add help to notification settings
- [ ] Add help to security settings
- [ ] Add help to backup settings
- [ ] Add help to team management

### Phase 4: Polish (Week 4)
- [ ] Mobile responsive help
- [ ] Collapsible help for complex features
- [ ] Help modal for detailed guides
- [ ] Link to full documentation

---

**File saved:** `storage/testing/INLINE_UI_DOCUMENTATION.md`
**Lines:** 800+
**Ready to implement!**
