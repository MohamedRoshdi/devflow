# Git Integration Features Guide 📊

**DevFlow Pro v2.1+**

Complete guide to Git commit tracking and update checking features.

---

## 🎯 Overview

DevFlow Pro now tracks Git commits and helps you stay up-to-date with your repositories. You can see exactly what code is deployed, view commit history, and get notified when new updates are available.

---

## ✨ Features

### 1. Commit History Display

**See recent commits from your GitHub repository**

On any project page, you'll see:
- Last 10 commits from your branch
- Commit hash (short form)
- Commit message
- Author name
- Time ago (relative)

Example:
```
Recent Commits on main:
┌────────────────────────────────────────┐
│ abc1234  Fix authentication bug        │
│          John Doe • 2 hours ago        │
├────────────────────────────────────────┤
│ def5678  Add new feature               │
│          Jane Smith • 5 hours ago      │
├────────────────────────────────────────┤
│ xyz7890  Update dependencies           │
│          John Doe • 1 day ago          │
└────────────────────────────────────────┘
```

---

### 2. Currently Deployed Commit

**Know exactly what code is running**

After deployment, you'll see:
- Full commit hash
- Commit message
- When it was committed
- How long ago it was deployed

Example:
```
Currently Deployed:
┌────────────────────────────────────────┐
│ abc1234                                │
│ Fix authentication bug                 │
│ Deployed 2 hours ago                   │
└────────────────────────────────────────┘
```

---

### 3. Check for Updates

**Compare your deployed version with GitHub**

Click the "🔄 Check for Updates" button to:
- Fetch latest commits from GitHub
- Compare with your deployed version
- See how many commits behind you are

#### Results:

**When Up-to-Date:**
```
✅ Up-to-date with latest commit
```

**When Behind:**
```
⚠️ 3 new commit(s) available

Current: abc1234
Latest:  xyz7890

[🚀 Deploy Latest]
```

---

### 4. Update Notifications

**Visual alerts when new commits are available**

- Yellow warning badge
- Shows number of commits behind
- Displays current vs latest commit
- Quick "Deploy Latest" action button

---

## 📖 How to Use

### View Commit History

1. Navigate to any project page
2. Look for the "Git Commits" section
3. See recent commits automatically loaded

**URL:** `http://your-server/projects/{id}`

---

### Check for Updates

1. On project page, find "Git Commits" section
2. Click "🔄 Check for Updates" button
3. Wait 2-3 seconds for result
4. See if you're up-to-date or behind

**What Happens:**
- Fetches latest from GitHub (no changes to your server)
- Compares commit hashes
- Counts commits between deployed and latest
- Shows result with visual indicator

---

### Deploy Latest Updates

**If updates are available:**

1. Update notification shows commits behind
2. Click "🚀 Deploy Latest" button
3. Deployment starts with latest code
4. New commit is recorded automatically

**Workflow:**
```
Check Updates → See "3 commits behind" → Deploy Latest → Up-to-date!
```

---

## 🔧 Technical Details

### How It Works

#### 1. During Deployment:
```php
// After git clone:
$gitService->getCurrentCommit($project);
// Returns: hash, message, author, timestamp

$project->update([
    'current_commit_hash' => $commitInfo['hash'],
    'current_commit_message' => $commitInfo['message'],
    'last_commit_at' => $timestamp,
]);
```

#### 2. Fetching Commits:
```bash
git fetch origin main
git log origin/main --pretty=format:'%H|%an|%ae|%at|%s' -n 10
```

#### 3. Checking for Updates:
```bash
# Get local commit
git rev-parse HEAD

# Get remote commit
git rev-parse origin/main

# Count commits behind
git rev-list --count HEAD..origin/main
```

---

### Database Schema

**New columns in `projects` table:**
```sql
current_commit_hash VARCHAR(40) NULL
current_commit_message TEXT NULL
last_commit_at TIMESTAMP NULL
```

**Updated `deployments` table:**
```sql
commit_hash VARCHAR(40) NULL
commit_message TEXT NULL
```

---

### GitService Methods

```php
// Fetch latest commits from repository
getLatestCommits(Project $project, int $perPage = 10, int $page = 1): array

// Get currently deployed commit
getCurrentCommit(Project $project): ?array

// Check if project is up-to-date
checkForUpdates(Project $project): array

// Get commits between two hashes
getCommitDiff(Project $project, string $from, string $to): array

// Update project with commit info
updateProjectCommitInfo(Project $project): bool
```

---

## 🎨 UI Components

### Commit List
- Compact display with hash + message
- Author and time information
- Responsive design
- Clickable (future: link to GitHub)
- Compact display with hash + message
- Author, email, and relative time information
- Copy-hash action for quick clipboard access
- Responsive design with hover highlighting
- Pagination controls (prev/next, per-page selector, range summary)

### Update Status Badge
- **Local vs Remote Cards:** Show hash, author, and timestamp for both ends
- **Green:** Up-to-date ✅
- **Amber:** Updates available (displays commits-behind count)
- **Gray:** Repository metadata unavailable (fallback state)

### Currently Deployed Card
- Blue background
- Prominent display
- Shows commit details
- Relative time

---

## Git History UI

### Repository Overview Panel
- Shows remote URL, active branch, and deploy root directory
- Displays auto-deploy status to clarify whether pushes are automatic or manual
- Quick link opens repository in a new tab

### Sync Insight Cards
- Local and remote commits each contain hash, author, message, and human-friendly timestamp
- Sync status card highlights whether the project is up to date and how many commits behind when outdated

### Commit Timeline
- Vertical timeline layout with gradient markers for each commit
- Copy full hash action for clipboard-friendly workflows
- Message, author, email, and timestamps grouped for readability
- Hover elevation for the commit card you are focused on

### Pagination Controls
- Range summary (e.g., “Showing 1 to 8 of 42 commits”)
- Per-page selector (5/8/10/15) instantly reloads history
- First/Previous/Next/Last buttons with disabled states
- Page indicators show current vs. total pages

---

## 💡 Best Practices

### For Developers

1. **Deploy Regularly**
   - Check for updates frequently
   - Deploy small incremental changes
   - Keep production close to latest

2. **Use Descriptive Commits**
   - Commit messages appear in deployment UI
   - Help team understand what's deployed
   - Make debugging easier

3. **Branch Strategy**
   - Use `main` or `master` for production
   - Test in `develop` or `staging` first
   - Use feature branches for development

### For Teams

1. **Communication**
   - Team can see what's deployed without asking
   - Know when updates are available
   - Coordinate deployments better

2. **Debugging**
   - Know exactly which commit has a bug
   - Easy to identify when issue was introduced
   - Faster problem resolution

3. **Deployment Planning**
   - See commit count before deploying
   - Review changes in GitHub first
   - Make informed deployment decisions

---

## 🐛 Troubleshooting

### "No commit history available"

**Cause:** Project not deployed yet
**Solution:** Deploy the project first to clone repository

### "Failed to fetch from remote"

**Possible Causes:**
1. Repository is private and SSH key not added to GitHub
2. Network connectivity issues
3. Invalid repository URL

**Solutions:**
1. Add server's SSH key to GitHub
2. Check server internet connection
3. Verify repository URL in project settings

### "Repository not cloned yet"

**Cause:** Project created but never deployed
**Solution:** Click "🚀 Deploy" to clone and build

### Updates not showing

**Cause:** Need to fetch from GitHub
**Solution:** Click "🔄 Check for Updates" to fetch latest

---

## 📊 Use Cases

### Scenario 1: Bug in Production

**Before Git Tracking:**
```
User: "When did this bug appear?"
Dev: "Let me check production... SSH into server... 
      check git log... cross-reference with deployments..."
Time: 10+ minutes
```

**With Git Tracking:**
```
User: "When did this bug appear?"
Dev: Opens project page
     Sees: Currently deployed: abc1234 - "Add payment feature"
     Deployed: 2 hours ago
     Checks GitHub: Bug in that commit!
Time: 30 seconds
```

---

### Scenario 2: Update Available

**Before:**
```
Dev: "Is production up-to-date?"
     SSH into server
     cd /var/www/project
     git fetch
     git status
     git log HEAD..origin/main
Time: 5 minutes
```

**With Check for Updates:**
```
Dev: Opens project page
     Clicks "Check for Updates"
     Sees: "3 new commits available"
     Clicks: "Deploy Latest"
Time: 10 seconds
```

---

### Scenario 3: Team Coordination

**Before:**
```
Dev 1: "Did you deploy the latest changes?"
Dev 2: "I'm not sure, let me check..."
       Confusion, duplicate deploys, wasted time
```

**With Git Tracking:**
```
Dev 1: Opens project page
       Sees: "Currently deployed: xyz7890 (latest)"
       Knows: Yes, it's deployed!
Time: 5 seconds
```

---

## 🎯 Tips & Tricks

### Tip 1: Regular Update Checks
Add checking for updates to your routine:
- Before fixing bugs - ensure you're on latest
- Before deploying - see what's new
- After team commits - know when to update

### Tip 2: Use Descriptive Commits
Your commit messages show in the UI:
```
❌ Bad: "fix stuff"
✅ Good: "Fix authentication bug in login controller"
```

### Tip 3: Monitor After Deploy
After deployment:
- Verify commit hash matches what you expect
- Check commit message is correct
- Confirm timestamp is recent

### Tip 4: Leverage History
Use commit history for:
- Quick reference to recent changes
- Debugging timeline
- Team awareness
- Change documentation

---

## 🔐 Security Notes

### What Git Commands Are Run:
```bash
# Read-only operations (safe):
git fetch origin {branch}     # Get latest refs
git log                       # Read commit history
git rev-parse                 # Get commit hashes
git rev-list --count          # Count commits

# No destructive operations!
# No git push, pull, reset, or checkout
```

### Data Stored:
- Commit hash (40 characters)
- Commit message (text)
- Commit timestamp
- No sensitive data
- No code stored in database

### Permissions:
- Only fetches from read-only remote
- Doesn't modify local repository
- No write access needed
- Safe to use on production

---

## 📈 Future Enhancements

### Planned for v2.2+:
- **Click to View on GitHub** - Direct links to commits
- **Commit Diff Viewer** - See changes between commits
- **Release Notes** - Auto-generate from commit messages
- **Tag Support** - Deploy specific tags/versions
- **Commit Search** - Search through commit history
- **Author Avatars** - Show GitHub avatars for commits

---

## 🆘 Getting Help

### Documentation:
- [README.md](README.md) - Main documentation
- [USER_GUIDE.md](USER_GUIDE.md) - Complete user manual
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Common issues

### Support:
- GitHub Issues for bugs
- GitHub Discussions for questions
- Email support (coming soon)

---

<div align="center">

**Git Features in DevFlow Pro v2.1** 🎉

Know exactly what's deployed • Check for updates easily • Deploy with confidence

[Back to README](README.md) • [View Releases](https://github.com/yourusername/devflow-pro/releases)

</div>

