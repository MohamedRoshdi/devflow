# DevFlow Pro v2.1 - Quick Reference Card 📋

**Cheat sheet for all new features**

---

## 🚀 Git Commit Tracking

### View Commit History
```
Project Page → Git Commits Section
See: Last 10 commits from your branch
```

### Check for Updates
```
Project Page → Click "🔄 Check for Updates"
Result: "X new commits available" or "Up-to-date ✅"
```

### Deploy Latest
```
When behind → Click "🚀 Deploy Latest"
Deploys newest code from GitHub
```

---

## 📺 Real-Time Progress

### Watch Deployment Live
```
After clicking Deploy → Auto-redirected to deployment page
Or: Deployments → Click deployment ID
Updates automatically every 3 seconds
```

### Progress Indicators
```
Gray ○ = Pending
Blue ⊙ = In Progress (with spinner)
Green ✓ = Complete
```

### Auto-Scroll Control
```
Scroll UP → Pauses auto-scroll (read old logs)
Scroll DOWN to bottom → Resumes auto-scroll
```

---

## 🐳 Dockerfile Detection

### Priority Order
```
1. Dockerfile → Use it
2. Dockerfile.production → Use it
3. None → Generate one
```

### Check What's Being Used
```
Deployment Logs will show:
"Using existing Dockerfile: Dockerfile.production"
or
"No Dockerfile found, generating one..."
```

---

## ⏱️ Timeouts

### Deployment Timeout
```
Old: 10 minutes
New: 20 minutes
Reason: npm builds can take 12-18 min
```

### Expected Durations
```
Simple Laravel (no npm): 5-8 minutes
Laravel + npm build: 12-18 minutes
Multi-stage complex: 15-20 minutes
```

---

## 🎯 Common Tasks

### Deploy a Project
```
1. Projects → Click project name
2. Click "🚀 Deploy"
3. Watch progress in real-time
4. Wait 5-18 minutes (depends on project)
5. Success! ✅
```

### Check What's Deployed
```
Project Page → Look for:
"Currently Deployed: abc1234 - Commit message"
Know exactly what code is running
```

### Update to Latest
```
1. Click "🔄 Check for Updates"
2. If behind, click "🚀 Deploy Latest"
3. Watch progress
4. Done!
```

### Monitor Long Deployment
```
1. Start deployment
2. Go to deployment page
3. Watch:
   - Progress bar (0-100%)
   - Step indicators
   - Live logs
4. Grab coffee if it's npm build time! ☕
```

---

## 📊 Progress Percentages

```
10%  - Clone repository
20%  - Record commit
25%  - Start build
30%  - System packages
40%  - PHP extensions
50%  - Composer install
60%  - npm install (SLOW! 3-5 min)
75%  - npm build (SLOW! 2-4 min)
85%  - Laravel optimize
90%  - Start container
100% - Complete! 🎉
```

---

## 🐛 Quick Troubleshooting

### Deployment Stuck?
```
Check progress bar:
- At 60%? → npm install (normal, wait 5 min)
- At 75%? → npm build (normal, wait 4 min)
- At 90%? → Docker start issue
- Not moving 10+ min? → Check logs for errors
```

### Build Failed?
```
Check deployment logs for:
- "composer: not found" → Dockerfile issue
- "npm: not found" → Dockerfile issue  
- "ext-xyz required" → Missing PHP extension
- "Permission denied" → File permissions issue
```

### No Commit History?
```
Reason: Project not deployed yet
Solution: Deploy once to clone repository
```

---

## 🎨 UI Locations

### Project Page
```
/projects/{id}

Sections:
- Project Details (top)
- Git Commits (left column)
- Recent Deployments (right column)  
- Actions: Deploy, Edit, Start/Stop
```

### Deployment Page
```
/deployments/{id}

Sections:
- Progress Bar (top, during deployment)
- Deployment Steps (visual tracker)
- Status Cards (stats)
- Deployment Logs (bottom, auto-scrolling)
```

---

## ⌨️ Keyboard Shortcuts

### During Deployment
```
F5 / Ctrl+R → Manual refresh
Esc → Stop auto-scroll (future)
```

### On Project Page
```
N → New deployment (future)
R → Refresh (future)
```

---

## 📱 Mobile Support

### All Features Work on Mobile:
- ✅ View projects
- ✅ Start deployments
- ✅ Watch progress
- ✅ Read logs
- ✅ Check for updates

### Optimized for Mobile:
- Responsive layout
- Touch-friendly buttons
- Swipe to refresh (future)

---

## 💡 Pro Tips

### Tip 1: Watch First Deployment
First deployment of a project takes longest (downloads everything).
Subsequent deployments can use cached layers (if Dockerfile unchanged).

### Tip 2: Monitor Critical Deployments
For production deploys:
- Watch the progress live
- Verify each step completes
- Check logs for warnings
- Don't close browser until 100%

### Tip 3: Use Commit Messages
Write clear commit messages:
```
✅ "Fix authentication bug in UserController"
❌ "fix stuff"
```
They appear in your deployment UI!

### Tip 4: Check Before You Deploy
Click "Check for Updates" first to see:
- How many commits you'll deploy
- What changes are included
- If it's worth deploying now

---

## 📈 Version Comparison

### v2.0 vs v2.1

| Feature | v2.0 | v2.1 |
|---------|------|------|
| Git tracking | ❌ | ✅ |
| Progress viewer | ❌ | ✅ |
| Dockerfile detection | ❌ | ✅ |
| Auto-refresh | ❌ | ✅ |
| Timeout | 10 min | 20 min |
| Step indicators | ❌ | ✅ |
| Update checking | ❌ | ✅ |
| Live logs | Static | Live |

---

## 🔗 Quick Links

- [Full Release Notes](V2.1_RELEASE_NOTES.md)
- [Git Features Guide](GIT_FEATURES.md)
- [Progress Viewer Guide](REAL_TIME_PROGRESS.md)
- [Docker Detection Guide](DOCKER_DETECTION_GUIDE.md)
- [Main README](README.md)

---

<div align="center">

**DevFlow Pro v2.1** - Smarter, More Transparent, Better UX

Keep this handy while you work! 📋

</div>

