# Real-Time Deployment Progress Viewer Guide 📺

**DevFlow Pro v2.1+**

Complete guide to watching and monitoring deployments in real-time.

---

## 🎯 Overview

The Real-Time Progress Viewer lets you watch deployments happen live with visual progress indicators, step-by-step tracking, and auto-updating logs. Perfect for long builds (10-20 minutes) where you want to know it's working, not stuck.

---

## ✨ Features at a Glance

| Feature | Description | Benefit |
|---------|-------------|---------|
| **Progress Bar** | 0-100% animated bar with shimmer | Know completion percentage |
| **Step Indicators** | Visual circles showing status | See which step is active |
| **Live Logs** | Terminal-style with auto-scroll | Read output in real-time |
| **Auto-Refresh** | Updates every 3 seconds | No manual refresh needed |
| **Current Step** | Text showing active operation | Know what's happening |
| **Duration Counter** | Running time display | Track how long it's taking |
| **Estimated Time** | Expected completion | Plan your time |

---

## 🎨 Visual Experience

### Progress Bar

```
🚀 Deployment in Progress                    60%
▓▓▓▓▓▓▓▓▓▓▓▓▓▓░░░░░░░░░░░░
```

**Features:**
- Smooth transitions (500ms)
- Shimmer animation effect
- Percentage display
- Color: Blue (in progress) → Green (complete)

---

### Step Indicators

```
Deployment Steps:

✓ Clone Repository        [Complete]
✓ Record Commit Info      [Complete]
⊙ Build Docker Image      [In Progress] ← Current
○ Start Container         [Pending]
```

**States:**
- **○ Gray Circle** - Pending (not started)
- **⊙ Blue Spinner** - In Progress (currently running)
- **✓ Green Checkmark** - Complete (finished)

---

### Live Log Viewer

```
Deployment Logs                    ● Live updating
────────────────────────────────────────────────
=== Cloning Repository ===
Repository: git@github.com:user/repo.git
Branch: main
✓ Repository cloned successfully

=== Building Docker Container ===
This may take 10-20 minutes...
#1 Installing system packages
#2 Installing PHP extensions
#3 Running composer install
   Loading dependencies...
   Installing 120 packages...
#4 Running npm install ⏳
   [Currently here - auto-scrolling...]
```

**Features:**
- Terminal-style (green text on black)
- Auto-scrolls to bottom
- Pauses when you scroll up
- Resumes when you scroll down
- Fixed height with scrollbar
- Monospace font for readability

---

## 📊 Progress Calculation

The system analyzes deployment logs to calculate progress:

| Progress | Step | Typical Duration |
|----------|------|------------------|
| 0% | Deployment queued | Instant |
| 10% | Cloning repository | 30 seconds |
| 20% | Recording commit info | 10 seconds |
| 25% | Starting Docker build | Instant |
| 30% | Installing system packages | 1-2 minutes |
| 40% | Installing PHP extensions | 2-3 minutes |
| 50% | Installing Composer deps | 2-3 minutes |
| 60% | Installing Node deps | 3-5 minutes ⏳ |
| 75% | Building frontend assets | 2-4 minutes ⏳ |
| 85% | Optimizing Laravel | 30 seconds |
| 90% | Starting container | 10 seconds |
| 100% | Deployment complete | ✅ |

**Total Time:**
- **Simple Laravel:** 5-8 minutes
- **Laravel + npm:** 12-18 minutes
- **Complex multi-stage:** 15-20 minutes

---

## 🔄 Auto-Refresh Behavior

### How It Works

The page automatically refreshes every **3 seconds** when deployment is running.

```javascript
wire:poll.3s="refresh"
```

**What Gets Updated:**
1. Deployment status
2. Progress percentage
3. Current step
4. Log output
5. Duration counter
6. Step indicators

**When It Stops:**
- Deployment completes (success or failure)
- You navigate away from page
- Deployment reaches terminal state

---

### Manual Refresh

You can also manually refresh:
- Click "🔄 Refresh" button
- Keyboard: F5 or Ctrl+R
- Instant update

---

## 📋 Deployment Steps Explained

### Step 1: Clone Repository
**What Happens:**
- Removes old project directory
- Clones from GitHub (SSH or HTTPS)
- Checks out specified branch

**Duration:** 30 seconds - 2 minutes (depends on repo size)

**Logs Show:**
```
=== Cloning Repository ===
Repository: git@github.com:user/project.git
Branch: main
Path: /var/www/project-name
Cloning repository...
✓ Repository cloned successfully
```

---

### Step 2: Record Commit Info
**What Happens:**
- Gets current commit hash
- Extracts commit message and author
- Updates database
- Records in deployment history

**Duration:** 10 seconds

**Logs Show:**
```
=== Recording Commit Information ===
Commit: abc1234
Author: John Doe
Message: Fix authentication bug
✓ Commit information recorded
```

---

### Step 3: Build Docker Image
**What Happens:**
- Checks for Dockerfile/Dockerfile.production
- Uses project's Dockerfile if exists
- Or generates one based on framework
- Runs docker build command
- Installs all dependencies

**Duration:** 8-15 minutes (longest step!)

**Sub-Steps:**
1. **System Packages** (1-2 min)
   ```
   Installing nginx, supervisor, git, curl...
   ```

2. **PHP Extensions** (2-3 min)
   ```
   Configuring and installing pdo, gd, zip, pcntl...
   ```

3. **Composer Install** (2-3 min)
   ```
   Installing dependencies from lock file
   Installing 120 packages...
   ```

4. **npm install** (3-5 min) ⏳
   ```
   Installing node dependencies
   Downloading 1000+ packages...
   ```

5. **npm run build** (2-4 min) ⏳
   ```
   Building frontend assets with Vite...
   Compiling JavaScript, CSS...
   Optimizing and bundling...
   ```

6. **Laravel Optimization** (30 sec)
   ```
   php artisan config:cache
   php artisan route:cache  
   php artisan view:cache
   ```

**Logs Show:**
```
=== Building Docker Container ===
This may take 10-20 minutes...
Please be patient!

#1 [internal] load build definition
#2 [1/7] FROM php:8.3-fpm-alpine
#3 [2/7] Installing system packages
#4 [3/7] Installing PHP extensions
#5 [4/7] COPY . .
#6 [5/7] RUN composer install
   ... 120 packages installed
#7 [6/7] RUN npm install && npm run build
   ... npm packages installed
   ... vite building...
#8 [7/7] Laravel optimizations
✓ Build successful
```

---

### Step 4: Start Container
**What Happens:**
- Stops old container (if running)
- Starts new container with built image
- Exposes ports
- Connects to network

**Duration:** 10 seconds

**Logs Show:**
```
=== Stopping Old Container ===
✓ Old container stopped (if any)

=== Starting Container ===
Starting new container...
Container started successfully with ID: abc123def456
```

---

## 💡 Understanding Progress

### Fast Steps (Green Quickly):
- ✓ Clone Repository (30s)
- ✓ Record Commit (10s)
- ✓ Start Container (10s)

### Slow Steps (Stay Blue Longer):
- ⊙ Install Composer deps (2-3 min)
- ⊙ Install Node deps (3-5 min) ⏳
- ⊙ Build frontend (2-4 min) ⏳

**Don't worry if progress stays at 60-75% for several minutes!**
npm install and Vite build are genuinely slow. The system is working, not stuck.

---

## 🎓 Tips for Monitoring

### Tip 1: Let It Auto-Refresh
The page updates automatically every 3 seconds. Just leave it open and watch!

### Tip 2: Scroll to Read, Scroll Back for Auto
If you want to read earlier logs:
1. Scroll up to read
2. Auto-scroll pauses
3. Scroll to bottom when done
4. Auto-scroll resumes

### Tip 3: Watch the Step Indicators
If a step shows the spinner for a long time, it's normal for:
- npm install (3-5 min)
- npm run build (2-4 min)

If stuck for 10+ minutes, there may be an issue.

### Tip 4: Use Multiple Tabs
Open deployment in one tab, continue working in another:
- Tab 1: Deployment progress (auto-updating)
- Tab 2: Continue coding
- Check back periodically

### Tip 5: Mobile Monitoring
The progress viewer works on mobile:
- Start deployment on desktop
- Monitor progress on phone
- Get notified when complete

---

## 🐛 Troubleshooting

### "Deployment stuck at X%"

**Common Causes:**

**At 60% (npm install):**
- Normal! npm install is slow (3-5 min)
- Large frontend projects take time
- Network speed affects download
- Wait 5-10 minutes before worrying

**At 75% (npm run build):**
- Normal! Vite build is slow (2-4 min)
- Compiling and bundling assets
- Optimizing for production
- This is the longest sub-step

**At 90% (Starting container):**
- Usually quick (10 seconds)
- If stuck, Docker may have issues
- Check Docker status on server

### "Progress not updating"

**Solutions:**
1. Click manual "🔄 Refresh" button
2. Check browser console for errors
3. Ensure JavaScript is enabled
4. Clear browser cache

### "Logs not auto-scrolling"

**Causes:**
- You scrolled up (intentional pause)
- Auto-scroll is smart - scroll to bottom to resume

**Solution:**
- Scroll to the very bottom of logs
- Auto-scroll will resume automatically

### "Deployment successful but shows 90%"

**Cause:** Final progress update may not have saved
**Impact:** None - deployment completed successfully
**Solution:** Refresh page to see 100%

---

## 📖 API / Programmatic Access

### Livewire Methods

```php
// In DeploymentShow component:

// Manually refresh deployment
$this->refresh()

// Get current progress
$this->progress // 0-100

// Get current step
$this->currentStep // "Building frontend assets"

// Analyze progress from logs
$this->analyzeProgress()
```

### Alpine.js Integration

```html
<!-- Auto-scroll control -->
<div x-data="{ autoScroll: true }"
     @scroll="autoScroll = isAtBottom()">
    <!-- Logs here -->
</div>
```

---

## 🎯 Best Practices

### For Long Deployments (15+ minutes):

1. **Start deployment**
2. **Open deployment page** (auto-redirects usually)
3. **Watch progress bar** fill to 25% (clone + commit)
4. **See "Building Docker Image"** step start
5. **Go do other work** (it'll take 10-15 min)
6. **Check back periodically** or leave tab open
7. **Get visual confirmation** when complete (100%)

### For Quick Deployments (5 minutes):

1. **Start deployment**
2. **Stay on page** and watch
3. **See each step complete** quickly
4. **Success in minutes!**

### For Debugging Failed Deployments:

1. **Look at progress** - where did it stop?
2. **Check step indicators** - which step failed?
3. **Read error logs** - what went wrong?
4. **Common failures:**
   - 50% = Composer issue (missing dependencies)
   - 60% = npm install issue (package not found)
   - 75% = npm build error (syntax error in code)
   - 90% = Docker start issue (port conflict, etc.)

---

## 📊 Performance

### Refresh Efficiency
- **Polling Interval:** 3 seconds
- **Data Transferred:** ~2-10KB per refresh
- **Impact:** Minimal (optimized queries)
- **Battery:** Negligible impact

### Browser Resources
- **CPU:** Low (Livewire handles updates)
- **Memory:** ~10MB per deployment page
- **Network:** ~1KB/second during active deployment

---

## 🔮 Future Enhancements

### Planned Features:
- **Real-time WebSocket streaming** - Sub-second updates
- **Deployment progress notifications** - Browser notifications
- **Video playback** - Replay deployment like a video
- **Step timing breakdown** - See exactly how long each step took
- **Concurrent deployment viewer** - Monitor multiple at once
- **Export logs** - Download deployment logs
- **Share deployment link** - Send to team members

---

## 🆘 Support

### Need Help?
- [Main Documentation](README.md)
- [Troubleshooting Guide](TROUBLESHOOTING.md)
- [GitHub Issues](https://github.com/yourusername/devflow-pro/issues)

### Found a Bug?
- Check [Known Issues](#troubleshooting) first
- Open GitHub issue with:
  - Deployment ID
  - Progress percentage when stuck
  - Browser console logs
  - Steps to reproduce

---

<div align="center">

**Real-Time Progress Viewer** - Never wonder if your deployment is working again!

[Back to README](README.md) • [Git Features](GIT_FEATURES.md) • [v2.1 Release Notes](V2.1_RELEASE_NOTES.md)

</div>

