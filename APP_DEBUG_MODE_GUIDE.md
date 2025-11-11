# 🔧 APP_DEBUG and Environment Configuration Guide

## Understanding Two Different Environments

There are **TWO separate environments** in DevFlow Pro:

### 1. 🏗️ DevFlow Pro's Own Environment
**What:** The deployment manager application itself  
**Location:** `/var/www/devflow-pro/.env`  
**Controls:** How DevFlow Pro displays errors, caches, logs

### 2. 🚀 Individual Project Environments
**What:** Each deployed project (e.g., ATS Pro)  
**Location:** Project settings in database + Docker containers  
**Controls:** How YOUR applications run (not DevFlow Pro)

---

## The Confusion Explained

### What You Expected:
```
User: "I set project environment to development"
Expected: "Detailed errors should show"
Reality: "Still shows 500 page"
```

### What Actually Happens:
```
Project Environment Setting:
├─ Stored in database (projects.environment)
├─ Injected into Docker containers
├─ Affects: YOUR deployed applications
└─ Does NOT affect: DevFlow Pro itself

DevFlow Pro Environment:
├─ Stored in /var/www/devflow-pro/.env
├─ Controls: DevFlow Pro error display
├─ Affects: DevFlow Pro pages
└─ Independent of project settings
```

---

## Current Configuration

### DevFlow Pro (Now Fixed!)

**File:** `/var/www/devflow-pro/.env`

```ini
# NOW ENABLED:
APP_ENV=local
APP_DEBUG=true

# What this does:
✅ Shows detailed error pages (stack traces)
✅ Displays SQL queries in errors
✅ Shows file paths and line numbers
✅ Includes variable dumps
✅ Full debugging information
```

**Result:**
- When DevFlow Pro has an error → Shows detailed error page! ✓
- No more generic 500 pages ✓
- Can see exactly what went wrong ✓

### Project Environment (Database Setting)

**Table:** `projects.environment`

**Values:**
- `local` - For local development
- `development` - For development server
- `staging` - For staging server  
- `production` - For live production

**What this does:**
```bash
# When you deploy a project:
docker run -e APP_ENV=development ...  # Injected!

# Inside the container:
# .env file gets: APP_ENV=development
# Your app runs in dev mode
```

**Result:**
- Your deployed application runs in selected environment
- Gets injected into Docker containers
- Affects your app's behavior (not DevFlow Pro)

---

## When to Use Each

### DevFlow Pro Debug Mode

**Enable (APP_DEBUG=true) When:**
- ✅ You're developing/testing DevFlow Pro
- ✅ You want to see DevFlow Pro errors
- ✅ You're troubleshooting DevFlow Pro issues
- ✅ You need detailed stack traces

**Disable (APP_DEBUG=false) When:**
- ⚠️ DevFlow Pro is production (for clients)
- ⚠️ Don't want to expose error details
- ⚠️ Security concern (shows paths, code)

**Current:** ✅ Enabled (good for development!)

### Project Environment Setting

**Local/Development:**
- Your app shows detailed errors
- Caching disabled
- Query logging enabled
- Best for active development

**Staging:**
- Limited debugging
- Caching enabled
- Pre-production testing

**Production:**
- No debug output
- Full optimization
- Logging only
- Best for live users

---

## How It All Works Together

### Scenario 1: DevFlow Pro Error

```
You: Click a button in DevFlow Pro
↓
Error occurs in DevFlow Pro code
↓
DevFlow Pro checks: APP_DEBUG in /var/www/devflow-pro/.env
↓
APP_DEBUG=true → Shows detailed error page ✓
APP_DEBUG=false → Shows generic 500 page
```

### Scenario 2: Deployed App Error

```
You: Deploy ATS Pro with environment=development
↓
Docker starts with: -e APP_ENV=development
↓
Error occurs in ATS Pro code
↓
ATS Pro checks: APP_ENV in its container
↓
APP_ENV=development → ATS Pro shows detailed error
APP_ENV=production → ATS Pro shows generic error
```

### Independent Control:

```
DevFlow Pro: APP_DEBUG=true (shows detailed DevFlow Pro errors)
ATS Pro: APP_ENV=production (shows generic ATS Pro errors)

✅ You can debug DevFlow Pro while keeping projects in production mode!
```

---

## Error Display Examples

### Before Fix (APP_DEBUG=false):

**Error in DevFlow Pro:**
```
┌────────────────────────┐
│  500 | Server Error    │
│                        │
│  Something went wrong  │
└────────────────────────┘

❌ No details
❌ Can't debug
❌ Frustrating!
```

### After Fix (APP_DEBUG=true):

**Error in DevFlow Pro:**
```
┌──────────────────────────────────────────────┐
│  SQLSTATE[42S22]: Column not found           │
│                                              │
│  app/Livewire/Users/UserList.php:147        │
│                                              │
│  145: $roles = Role::all();                 │
│  146:                                       │
│→ 147: return view('livewire.users.user');  │
│                                              │
│  Stack Trace:                                │
│  #1 UserList.php:147                        │
│  #2 Component.php:123                       │
│  ...                                         │
│                                              │
│  Variables:                                  │
│  $this->search = ""                         │
│  $this->roleFilter = ""                     │
└──────────────────────────────────────────────┘

✅ Detailed error
✅ File and line number
✅ Stack trace
✅ Variable dump
✅ Easy to debug!
```

---

## Configuration Reference

### DevFlow Pro .env Settings:

```ini
# Debug Mode
APP_ENV=local              # local/production
APP_DEBUG=true             # true/false

# When to enable:
- Testing DevFlow Pro
- Developing features
- Troubleshooting issues

# When to disable:
- Production deployment
- Client-facing instance
- Security requirement
```

### Project Environment Settings:

**In DevFlow Pro UI:**
```
Navigate to: Project → Environment Tab
Select: Local/Development/Staging/Production
```

**Effect:**
```bash
# Gets injected into Docker:
docker run \
  -e APP_ENV=development \  ← From your selection
  -e APP_DEBUG=true \       ← Auto-set based on environment
  your-project
```

---

## Automatic Debug Sync (Future Enhancement)

### Idea:
Automatically set APP_DEBUG based on project environment:

```php
// In DockerService.php
$appDebug = in_array($project->environment, ['local', 'development']) 
    ? 'true' 
    : 'false';

$envVars .= " -e APP_DEBUG={$appDebug}";
```

**Result:**
- local/development → APP_DEBUG=true (detailed errors)
- staging/production → APP_DEBUG=false (generic errors)

---

## Security Considerations

### Why APP_DEBUG=false in Production?

**Security Risks with APP_DEBUG=true:**
```
Exposed Information:
- ❌ Full file paths (/var/www/...)
- ❌ Database credentials (in stack trace)
- ❌ Code snippets
- ❌ Environment variables
- ❌ Internal structure
- ❌ Potential attack vectors
```

**Best Practice:**
```
Development Server: APP_DEBUG=true ✓
Staging Server: APP_DEBUG=false (log errors)
Production Server: APP_DEBUG=false (log errors)

Client/Public Access: NEVER APP_DEBUG=true!
```

### For DevFlow Pro:

**Current Setup:**
```
DevFlow Pro: APP_DEBUG=true
Access: Only you (via IP)
Security: OK (not public)
```

**If You Share Access:**
```
Consider:
- Create admin/dev toggle
- IP whitelist for debug mode
- Environment-based auto-config
```

---

## Testing

### Test DevFlow Pro Errors:

**1. Trigger an Error:**
```
Visit: http://31.220.90.121/users
Click: Edit on a user
Change email to: invalid-email (no @)
Save
```

**Expected with APP_DEBUG=true:**
```
Detailed error page showing:
✅ Validation error details
✅ File and line number
✅ Stack trace
✅ Request data
```

**2. Check Console:**
```
F12 → Console
Look for errors
Should see detailed information
```

### Test Project Environment:

**1. Deploy with Development:**
```
1. Set project environment to: development
2. Deploy project
3. Container gets: -e APP_ENV=development
4. Your app shows detailed errors
```

**2. Deploy with Production:**
```
1. Set project environment to: production
2. Deploy project
3. Container gets: -e APP_ENV=production
4. Your app shows generic errors (secure)
```

---

## Quick Reference

### To Enable DevFlow Pro Debug:
```bash
ssh root@31.220.90.121
cd /var/www/devflow-pro
nano .env

# Change:
APP_ENV=local
APP_DEBUG=true

# Save and:
php artisan config:clear
systemctl restart php8.2-fpm
```

### To Disable DevFlow Pro Debug:
```bash
# In .env:
APP_ENV=production
APP_DEBUG=false

# Clear cache:
php artisan config:clear
systemctl restart php8.2-fpm
```

### To Change Project Environment:
```
1. Visit: http://31.220.90.121/projects/{id}
2. Go to: Environment tab
3. Select: Local/Development/Staging/Production
4. Deploy: To apply to container
```

---

## Current Status

### DevFlow Pro:
```
✅ APP_ENV=local
✅ APP_DEBUG=true
✅ Detailed errors enabled
✅ Ready for debugging
```

### Projects:
```
✅ Environment setting in database
✅ Injected into Docker containers
✅ Per-project configuration
✅ Independent of DevFlow Pro
```

---

## Summary

### Two Separate Environments:

**1. DevFlow Pro (Manager App):**
- Controls: How DevFlow Pro shows errors
- Setting: /var/www/devflow-pro/.env
- Now: APP_DEBUG=true ✅
- Result: Detailed error pages ✓

**2. Individual Projects (Deployed Apps):**
- Controls: How YOUR apps show errors
- Setting: Database + Docker injection
- Config: Via Environment tab
- Result: Per-project control ✓

### Problem Solved:
✅ **DevFlow Pro debug mode enabled**  
✅ **You'll now see detailed 500 errors**  
✅ **Stack traces visible**  
✅ **Can debug issues easily**  

### Test It:
Try triggering an error in DevFlow Pro - you'll see a beautiful detailed error page instead of generic 500!

---

**Status:** ✅ FIXED

**DevFlow Pro:** Debug mode ON

**Projects:** Environment configurable per project

**Ready:** Detailed errors now visible! 🔍

