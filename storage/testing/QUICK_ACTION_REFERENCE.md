# DevFlow Pro - Quick Action Reference Card
**Common Tasks & How to Do Them**

---

## 🚀 DEPLOYMENT ACTIONS

### Deploy Latest Code
**What:** Update your live website with latest changes from GitHub
**Steps:**
1. Go to Projects
2. Click your project
3. Click "Deploy" button
4. Wait 30-60 seconds
5. Done! ✅

**Alternative:** Push to GitHub → Auto-deploys (if webhooks enabled)

---

### Rollback Bad Deployment
**What:** Undo last deployment and go back to previous working version
**When:** Site is broken after deployment
**Steps:**
1. Go to project → Deployments tab
2. Find last working deployment
3. Click "Rollback to this version"
4. Confirm
5. Site restored in 10 seconds ✅

---

### View Deployment Logs
**What:** See what happened during deployment (debug failures)
**Steps:**
1. Project → Deployments tab
2. Click on deployment
3. View logs (shows commands run, errors)

---

## 🖥️ SERVER ACTIONS

### Add New Server
**What:** Connect DevFlow to your VPS/cloud server
**Steps:**
1. Servers → Add Server
2. Enter:
   - Name (e.g., "Production Server 1")
   - IP address (e.g., 192.168.1.100)
   - SSH Port (usually 22)
   - SSH Key (paste your private key)
3. Click "Connect"
4. DevFlow tests connection ✅

---

### Monitor Server Resources
**What:** Check if server is running low on CPU/RAM/Disk
**Steps:**
1. Servers → Click server name
2. View Metrics tab
3. See graphs for:
   - CPU usage %
   - RAM usage GB
   - Disk space GB
   - Network traffic

**Red flags:**
- CPU constantly > 80% = Need bigger server
- Disk < 10% free = Clean up files
- RAM > 90% = Need more memory

---

### SSH into Server
**What:** Open terminal connection to server
**Steps:**
1. Server details page
2. Click "SSH" button
3. Terminal opens in browser
4. Run commands directly

---

## 🌐 DOMAIN ACTIONS

### Add Domain to Project
**What:** Make your project accessible at yourdomain.com
**Steps:**
1. Project → Domains tab
2. Click "Add Domain"
3. Enter domain (e.g., myapp.com)
4. Enable SSL (auto-generates HTTPS certificate)
5. Save
6. Point your DNS to server IP
7. Wait 5-10 minutes for DNS propagation ✅

---

### Enable HTTPS/SSL
**What:** Secure your website with HTTPS (green lock icon)
**Steps:**
1. Domain settings
2. Toggle "SSL Enabled"
3. DevFlow requests certificate from Let's Encrypt
4. Certificate installed automatically
5. Site accessible via https:// ✅

**Auto-renewal:** Certificate renews automatically every 90 days

---

## 🔐 SECURITY ACTIONS

### Set Environment Variables
**What:** Store API keys, passwords securely
**Steps:**
1. Project → Settings → Environment
2. Click "Add Variable"
3. Key: API_KEY
4. Value: your-secret-key
5. Save
6. Variable injected into .env during deployment ✅

---

### Enable Two-Factor Authentication
**What:** Extra security for your DevFlow account
**Steps:**
1. User menu → Account Settings
2. Security → Enable 2FA
3. Scan QR code with Google Authenticator app
4. Enter 6-digit code to verify
5. Save backup codes (for recovery) ✅

---

### View Audit Logs
**What:** See who did what and when
**Steps:**
1. Settings → Security → Audit Logs
2. Filter by:
   - User
   - Date range
   - Action type
3. See all actions with timestamps

---

## 📊 MONITORING ACTIONS

### Setup Health Checks
**What:** Get notified if your site goes down
**Steps:**
1. Project → Health Checks
2. Add Health Check:
   - URL: https://myapp.com
   - Interval: 5 minutes
   - Alert via: Email + Slack
3. Save
4. DevFlow pings your site every 5 min ✅

**Alerts:** Get email if site doesn't respond or returns error

---

### View Error Logs
**What:** Debug 500 errors and exceptions
**Steps:**
1. Project → Logs
2. Filter by "Error" level
3. See stack traces, timestamps
4. Click to expand full error details

**Search:** Type error message to find specific issue

---

## 🔔 NOTIFICATION ACTIONS

### Setup Slack Notifications
**What:** Get deployment updates in Slack
**Steps:**
1. Settings → Notifications → Add Channel
2. Select "Slack"
3. Enter Slack Webhook URL:
   - Go to Slack → Apps → Incoming Webhooks
   - Create webhook → Copy URL
   - Paste in DevFlow
4. Select events:
   - ✅ Deployment succeeded
   - ✅ Deployment failed
   - ✅ Health check failed
5. Test notification
6. Save ✅

**Result:** "🚀 MyProject deployed successfully!" appears in Slack

---

### Setup Email Alerts
**What:** Get email when something goes wrong
**Steps:**
1. Notifications → Add Channel
2. Select "Email"
3. Enter email addresses (comma-separated)
4. Select events (deployment failed, disk full, etc.)
5. Save ✅

---

## 👥 TEAM ACTIONS

### Invite Team Member
**What:** Add developer to your DevFlow team
**Steps:**
1. Team → Invite Member
2. Enter email: john@company.com
3. Select role:
   - Owner (full control)
   - Admin (can deploy, configure)
   - Developer (can deploy, view)
   - Viewer (read-only)
4. Send invitation
5. They receive email → Click link → Join team ✅

---

### Change User Role
**What:** Update team member's permissions
**Steps:**
1. Team → Find user
2. Click "Edit"
3. Select new role
4. Save
5. Permissions updated immediately ✅

---

## 🗄️ DATABASE ACTIONS

### Setup Automated Backups
**What:** Auto-backup database daily (prevent data loss)
**Steps:**
1. Project → Database → Backups
2. Enable Auto-Backup
3. Configure:
   - Frequency: Daily at 2 AM
   - Keep: Last 7 days
   - Storage: Local + S3
4. Save
5. Backups run automatically ✅

---

### Restore from Backup
**What:** Recover database after data loss
**Steps:**
1. Database → Backups
2. Find backup (sorted by date)
3. Click "Restore"
4. ⚠️ **WARNING:** This will overwrite current data
5. Confirm
6. Database restored ✅

**Best Practice:** Download backup first as extra safety

---

### Run Database Migration
**What:** Update database schema (add tables/columns)
**Steps:**
1. Update migration files in code
2. Push to GitHub
3. Deploy project
4. Migrations run automatically during deployment ✅

**Manual:** Project → Database → Run Migrations

---

## 🐳 DOCKER ACTIONS

### Restart Docker Containers
**What:** Restart app containers (fixes many issues)
**Steps:**
1. Project → Docker
2. Click "Restart All Containers"
3. Containers restart in 5-10 seconds ✅

---

### View Container Logs
**What:** Debug issues in specific container
**Steps:**
1. Project → Docker
2. Find container (app, db, redis, etc.)
3. Click "Logs"
4. See real-time output

---

### Rebuild Containers
**What:** Rebuild Docker images from scratch
**Steps:**
1. Docker → Select containers
2. Click "Rebuild"
3. Fresh containers built from Dockerfile ✅

**When:** After updating Dockerfile or base image

---

## 🔧 TROUBLESHOOTING ACTIONS

### Site is Down (500 Error)
**Steps:**
1. Check Health Checks → See when it started
2. View Error Logs → Find exception
3. Check Recent Deployments → Was there a bad deploy?
4. If yes → Rollback to last working version
5. Fix bug in code
6. Deploy again ✅

---

### Deployment Failed
**Steps:**
1. View deployment logs
2. Find error message (usually at bottom)
3. Common issues:
   - **Composer error:** Missing dependency → Update composer.json
   - **Migration error:** Database issue → Check migration files
   - **Permission error:** SSH key issue → Check server connection
4. Fix issue
5. Try deployment again ✅

---

### Server Running Slow
**Steps:**
1. Check Server Metrics
2. Identify bottleneck:
   - **High CPU:** Optimize code, add caching
   - **High RAM:** Increase memory or optimize queries
   - **High Disk I/O:** Database queries too heavy
3. Scale server up (more CPU/RAM)
4. Or optimize application code ✅

---

### Can't Connect to Server
**Steps:**
1. Test SSH manually: `ssh user@server-ip`
2. If fails:
   - Check server is online
   - Check SSH key is correct
   - Check firewall allows port 22
3. Update SSH key in DevFlow
4. Test connection ✅

---

## ⚡ PERFORMANCE ACTIONS

### Clear All Caches
**What:** Clear Laravel caches (fixes many issues)
**Steps:**
1. Project → Settings
2. Click "Clear All Caches"
3. Clears:
   - Config cache
   - Route cache
   - View cache
   - Application cache
4. Done ✅

**When:** After changing config, deployment issues

---

### Enable CDN
**What:** Serve static files faster worldwide
**Steps:**
1. Project → Performance → CDN
2. Select provider (Cloudflare, CloudFront)
3. Enter CDN URL
4. Enable
5. Static assets served from CDN ✅

---

## 📱 MOBILE ACTIONS

### View on Mobile
**What:** Access DevFlow from phone
**Steps:**
1. Open browser on phone
2. Go to devflow.yourcompany.com
3. Login
4. Full dashboard on mobile ✅

**Install as App:**
1. Chrome → Menu → "Add to Home Screen"
2. Icon appears on home screen
3. Opens like native app

---

## 🎨 CUSTOMIZATION

### Enable Dark Mode
**Steps:**
1. User menu (top right)
2. Toggle "Dark Mode"
3. Interface switches to dark theme ✅

---

### Customize Dashboard
**Steps:**
1. Dashboard → Customize
2. Drag widgets to rearrange
3. Add/remove widgets
4. Save layout ✅

---

## 🔍 SEARCH ACTIONS

### Search Everything
**Keyboard:** Cmd/Ctrl + K
**Steps:**
1. Type search query
2. See results from:
   - Projects
   - Servers
   - Deployments
   - Logs
   - Team members
3. Click result to open ✅

---

## 📊 REPORTING ACTIONS

### Generate Deployment Report
**What:** Weekly summary of all deployments
**Steps:**
1. Reports → Deployments
2. Select date range (Last 7 days)
3. Click "Generate Report"
4. View or download PDF ✅

**Contains:**
- Total deployments
- Success rate
- Average time
- Top deployers
- Failure reasons

---

## 🆘 EMERGENCY ACTIONS

### Disaster Recovery
**When:** Production is completely broken
**Steps:**
1. **Immediate:** Rollback to last working deployment
2. Switch to backup server (if configured)
3. Restore database from backup
4. Notify team via Slack
5. Investigate root cause
6. Deploy fix
7. Document incident ✅

---

### Emergency Rollback
**When:** Need to revert IMMEDIATELY
**Steps:**
1. Project → Deployments
2. Find last known good deployment (green checkmark)
3. Click "Emergency Rollback"
4. Skip confirmation (dangerous but fast)
5. Previous version live in 5 seconds ✅

⚠️ **Use only in emergencies!**

---

## 📚 LEARNING ACTIONS

### View Documentation
**Steps:**
1. Help menu (?) → Documentation
2. Browse topics or search
3. Code examples included ✅

---

### Watch Video Tutorials
**Steps:**
1. Help → Video Tutorials
2. Watch step-by-step guides:
   - Getting Started (5 min)
   - First Deployment (10 min)
   - Team Setup (8 min)
   - Advanced Features (15 min)

---

## 🔗 INTEGRATION ACTIONS

### Connect GitHub
**Steps:**
1. Settings → Integrations → GitHub
2. Click "Connect GitHub"
3. Authorize DevFlow
4. Repositories auto-sync ✅

---

### Setup Auto-Deploy
**Steps:**
1. Project → Settings → CI/CD
2. Enable "Auto-Deploy"
3. Select branch (main)
4. Copy webhook URL
5. Go to GitHub → Repository Settings → Webhooks
6. Paste webhook URL
7. Save
8. Now pushes to GitHub auto-deploy ✅

---

## 💡 PRO TIPS

**Keyboard Shortcuts:**
- `Cmd/Ctrl + K` - Search
- `Cmd/Ctrl + D` - Deploy selected project
- `Cmd/Ctrl + L` - View logs
- `Cmd/Ctrl + T` - Terminal

**Bulk Actions:**
- Select multiple projects
- Click "Bulk Actions"
- Deploy all, restart all, etc.

**Quick Filters:**
- Click status badges to filter (Active, Failed, etc.)
- Date pickers support natural language ("last week", "yesterday")

**Favorites:**
- Star frequently used projects
- Appear at top of list
- Quick access from any page

---

## 📞 GET HELP

**In-App:**
- Click "?" icon → Help Center
- Live chat (bottom right)

**Documentation:**
- https://docs.devflow.com

**Community:**
- Discord: https://discord.gg/devflow
- Forum: https://community.devflow.com

**Support:**
- Email: support@devflow.com
- Emergency: +1-800-DEVFLOW

---

**Printed Reference Card:**
Print this page and keep near your desk for quick lookup!

**Last Updated:** 2025-12-10
