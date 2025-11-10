# Docker Management - DevFlow Pro

**Complete Guide to Advanced Docker Features**

Version 2.2+ | Last Updated: November 10, 2025

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Container Resource Management](#container-resource-management)
3. [Volume Management](#volume-management)
4. [Network Management](#network-management)
5. [Image Management](#image-management)
6. [Docker Compose](#docker-compose-management)
7. [Container Execution](#container-execution)
8. [Backup & Restore](#backup--restore)
9. [Registry Integration](#registry-integration)
10. [System Management](#system-management)
11. [Best Practices](#best-practices)
12. [Troubleshooting](#troubleshooting)

---

## 🎯 Overview

DevFlow Pro now includes comprehensive Docker management features that give you complete control over your containerized applications. From monitoring resource usage to managing networks and volumes, you can do it all from a single dashboard.

### What's New in v2.2

- ✅ **Container Stats** - Real-time CPU, Memory, Network monitoring
- ✅ **Resource Limits** - Set and manage container resource constraints
- ✅ **Volume Management** - Create, delete, and inspect Docker volumes
- ✅ **Network Management** - Manage Docker networks and container connectivity
- ✅ **Image Management** - List, delete, prune, and pull Docker images
- ✅ **Enhanced Compose Support** - Full docker-compose orchestration
- ✅ **Container Execution** - Run commands inside containers
- ✅ **Backup & Restore** - Export and import container snapshots
- ✅ **Registry Integration** - Push/pull from private registries
- ✅ **System Cleanup** - Automated cleanup of unused resources

---

## 📊 Container Resource Management

### View Container Statistics

Get real-time statistics for your running containers:

**Features:**
- CPU usage percentage
- Memory usage and limits
- Network I/O (bytes in/out)
- Disk I/O
- PIDs (process IDs)

**How to Use:**
1. Go to your project page
2. Click **"📊 Container Stats"** button
3. View real-time resource usage

**Example Output:**
```
Container: my-project
CPU: 15.5% 
Memory: 256MB / 512MB (50%)
Network I/O: 1.2MB / 850KB
Disk I/O: 450KB / 120KB
PIDs: 12
```

### Set Resource Limits

Prevent containers from consuming too many resources:

**Available Limits:**
- **Memory Limit** - Maximum RAM (e.g., 512MB, 1GB, 2GB)
- **CPU Shares** - Relative CPU priority (default: 1024)
- **CPU Quota** - CPU time limit per period

**How to Set:**
1. Go to project settings
2. Navigate to **"Resource Limits"** section
3. Set desired limits:
   - Memory: `512` (MB)
   - CPU Shares: `1024` (default)
4. Click **"Update Limits"**

**Example Usage:**
```php
// Set 1GB memory limit and high CPU priority
DockerService::setContainerResourceLimits($project, 1024, 2048);
```

**Best Practices:**
- Start with generous limits and adjust based on monitoring
- Production apps: 512MB - 2GB memory typical
- Development: 256MB - 512MB usually sufficient
- High-traffic apps: Increase CPU shares

---

## 💾 Volume Management

Docker volumes persist data outside containers, surviving container restarts and rebuilds.

### List All Volumes

**How to Access:**
1. Go to **Server Details** page
2. Click **"Docker Volumes"** tab
3. View all volumes on the server

**What You See:**
- Volume name
- Driver (usually `local`)
- Mount point
- Labels
- Creation date

### Create a Volume

**Use Cases:**
- Database data persistence
- Uploaded files storage
- Shared data between containers
- Configuration files

**How to Create:**
1. Server page → **"Docker Volumes"** tab
2. Click **"+ Create Volume"**
3. Fill in details:
   - **Name:** `my-project-db-data`
   - **Driver:** `local` (default)
   - **Labels:** (optional) `project=my-project`
4. Click **"Create"**

**Example with API:**
```php
DockerService::createVolume($server, 'my-project-db', [
    'driver' => 'local',
    'labels' => ['project' => 'my-project', 'type' => 'database']
]);
```

### Delete a Volume

**Warning:** This permanently deletes all data in the volume!

**How to Delete:**
1. Volumes tab → Find volume
2. Click **"🗑️ Delete"**
3. Confirm deletion

**Safety:** DevFlow Pro prevents deleting volumes attached to running containers.

### Volume Best Practices

**✅ DO:**
- Use volumes for database data
- Name volumes descriptively (`project-db-data`, not `vol1`)
- Label volumes for organization
- Back up important volumes regularly
- Use volumes for user uploads

**❌ DON'T:**
- Don't delete volumes with active data
- Don't share volumes between unrelated projects
- Don't store sensitive keys in volumes (use secrets)

### Attach Volume to Container

When starting a container with volumes:

```php
docker run -d \
  --name my-project \
  -v my-project-db:/var/lib/mysql \
  -v my-project-uploads:/app/storage/uploads \
  my-project:latest
```

---

## 🌐 Network Management

Docker networks enable communication between containers.

### Understanding Docker Networks

**Network Types:**

1. **Bridge** (default)
   - Isolated network for containers
   - Containers can communicate by name
   - Best for single-host deployments

2. **Host**
   - Uses host's network directly
   - Better performance, less isolation
   - Use for high-performance needs

3. **Overlay**
   - Multi-host networking
   - For Docker Swarm clusters
   - Advanced use cases

### List Networks

**How to View:**
1. Server page → **"Docker Networks"** tab
2. See all networks on server

**Default Networks:**
- `bridge` - Default bridge network
- `host` - Host network
- `none` - No networking

### Create a Network

**Use Cases:**
- Isolate project containers
- Enable service discovery
- Secure inter-container communication

**How to Create:**
1. Networks tab → **"+ Create Network"**
2. Enter details:
   - **Name:** `my-project-network`
   - **Driver:** `bridge`
3. Click **"Create"**

**Example:**
```php
DockerService::createNetwork($server, 'my-project-network', 'bridge');
```

### Connect Container to Network

**How to:**
1. Project page → **"Networks"** tab
2. Click **"Connect to Network"**
3. Select network from dropdown
4. Click **"Connect"**

**Example:**
```php
DockerService::connectContainerToNetwork($project, 'my-project-network');
```

**Result:**
- Container can communicate with other containers on same network
- Access by container name (e.g., `http://my-database:3306`)

### Network Best Practices

**Recommended Setup:**

```
Frontend Network (my-app-frontend)
  ├── nginx
  └── app-container

Backend Network (my-app-backend)
  ├── app-container
  ├── mysql
  └── redis
```

**Benefits:**
- App container on both networks
- Database isolated from frontend
- Better security and organization

---

## 🖼️ Image Management

Manage Docker images on your servers.

### List All Images

**How to View:**
1. Server page → **"Docker Images"** tab
2. See all images

**What You See:**
- Repository name
- Tag
- Image ID
- Size
- Created date

### Pull Image from Registry

**Common Images to Pull:**
- `nginx:latest` - Web server
- `mysql:8.0` - Database
- `redis:alpine` - Cache
- `node:20-alpine` - Node.js runtime
- `php:8.3-fpm-alpine` - PHP-FPM

**How to Pull:**
1. Images tab → **"Pull Image"**
2. Enter image name: `nginx:latest`
3. Click **"Pull"**
4. Wait for download

**Example:**
```php
DockerService::pullImage($server, 'nginx:alpine');
```

### Delete Images

**When to Delete:**
- Old project images no longer used
- Multiple versions of same image
- Failed builds (`<none>` images)

**How to Delete:**
1. Images tab → Find image
2. Click **"🗑️ Delete"**
3. Confirm

**Bulk Delete:**
Use **"Prune Images"** to remove:
- Dangling images (`<none>:<none>`)
- Unused images (no containers)

### Image Cleanup (Prune)

Free up disk space by removing unused images.

**Options:**

1. **Standard Prune** - Removes dangling images only
2. **Full Prune** - Removes all unused images

**How to Prune:**
1. Images tab → **"🧹 Cleanup"**
2. Choose prune type
3. Confirm action

**Saved Space Example:**
```
Deleted Images:
- my-project:old-version (450MB)
- node:16 (300MB)
- <none>:<none> (120MB)

Total Reclaimed: 870MB
```

### Image Best Practices

**Optimization Tips:**

1. **Use Alpine variants**
   - `node:20-alpine` vs `node:20` → 70% smaller!
   - `nginx:alpine` → 5MB vs 140MB

2. **Multi-stage builds**
   - Build in one stage, run in another
   - Keeps final image small

3. **Layer caching**
   - Put frequently changing code last
   - Copy dependencies first

4. **Regular cleanup**
   - Prune weekly
   - Remove old versions

---

## 🐳 Docker Compose Management

For projects with multiple containers (app, database, cache, etc.).

### Deploy with Docker Compose

**Prerequisites:**
- Project has `docker-compose.yml` file
- File is in project root

**How to Deploy:**
1. Project page → **"🚀 Deploy with Compose"**
2. DevFlow Pro will:
   - Find docker-compose.yml
   - Build all services
   - Start all containers
   - Connect networks
3. View service status

**Example docker-compose.yml:**
```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "8000:80"
    depends_on:
      - db
      - redis
    networks:
      - app-network

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: myapp
    volumes:
      - db-data:/var/lib/mysql
    networks:
      - app-network

  redis:
    image: redis:alpine
    networks:
      - app-network

volumes:
  db-data:

networks:
  app-network:
    driver: bridge
```

### View Compose Services

**How to View:**
1. Project page → **"Docker Compose"** tab
2. See all services:
   - Service name
   - State (running/stopped)
   - Ports
   - Health status

**Example Output:**
```
Services:
✅ app      - Running - 0.0.0.0:8000->80/tcp
✅ db       - Running - 3306/tcp
✅ redis    - Running - 6379/tcp
```

### Stop Compose Services

**How to Stop:**
1. Compose tab → **"Stop All Services"**
2. Confirm
3. All containers stopped and removed

**Note:** Volumes persist - data is safe!

### Compose Best Practices

**Production Tips:**

1. **Use specific image versions**
   ```yaml
   image: mysql:8.0.35  # Not mysql:latest
   ```

2. **Set resource limits**
   ```yaml
   deploy:
     resources:
       limits:
         cpus: '0.5'
         memory: 512M
   ```

3. **Health checks**
   ```yaml
   healthcheck:
     test: ["CMD", "curl", "-f", "http://localhost/health"]
     interval: 30s
     timeout: 3s
     retries: 3
   ```

4. **Named volumes**
   ```yaml
   volumes:
     - db-data:/var/lib/mysql  # Not ./data:/var/lib/mysql
   ```

---

## ⚡ Container Execution

Run commands inside containers without SSH.

### Execute Commands

**Use Cases:**
- Run database migrations
- Clear cache
- Run scripts
- Check logs
- Inspect files

**How to Execute:**
1. Project page → **"Terminal"** tab
2. Enter command
3. Click **"Run"**
4. View output

**Common Commands:**

**Laravel:**
```bash
php artisan migrate
php artisan cache:clear
php artisan queue:work
php artisan tinker
```

**Node.js:**
```bash
npm run build
npm test
node scripts/seed.js
```

**General:**
```bash
ls -la
cat .env
tail -f storage/logs/laravel.log
ps aux
```

### View Container Processes

See what's running inside your container:

**How to View:**
1. Project page → **"Processes"** tab
2. View all running processes

**Example Output:**
```
PID    USER     COMMAND
1      root     nginx: master process
12     www-data nginx: worker process
13     www-data php-fpm: master process
25     www-data php-fpm: pool www
26     www-data php-fpm: pool www
```

### Interactive Shell (Advanced)

Get a shell inside the container:

**Commands:**
```bash
# Bash shell
docker exec -it my-project bash

# Alpine sh shell
docker exec -it my-project sh

# As specific user
docker exec -u www-data -it my-project bash
```

**Use Cases:**
- Debugging
- Manual file editing
- System inspection
- Package installation

---

## 💾 Backup & Restore

Protect your containers and data.

### Export Container (Backup)

Create a snapshot of a running container.

**What Gets Backed Up:**
- Container filesystem
- Installed packages
- Configuration files
- Application code
- **Note:** Volumes are separate!

**How to Backup:**
1. Project page → **"Backup"** tab
2. Click **"Create Backup"**
3. Enter backup name (optional)
4. Backup created as Docker image

**Automatic Naming:**
```
my-project-backup-2025-11-10-14-30-00
```

### Save Image to File

Export backup as .tar file for:
- Offline storage
- Transfer to another server
- Disaster recovery

**How to Save:**
1. Backup tab → Select backup
2. Click **"💾 Save to File"**
3. Choose location: `/backups/my-project-2025-11-10.tar`
4. Download completes

**File Size:**
- Basic app: 100-300MB
- With dependencies: 500MB - 1GB
- Full stack: 1-2GB

### Restore from Backup

**How to Restore:**
1. Backup tab → Select backup image
2. Click **"Restore"**
3. New container created from backup
4. Old container replaced

**Use Cases:**
- Rollback after bad deployment
- Recover from corruption
- Clone production to staging

### Backup Best Practices

**Backup Strategy:**

1. **Before every deployment**
   ```
   Deploy → Auto-backup → New version
   ```

2. **Keep multiple versions**
   - Last 5 deployments
   - Weekly snapshots
   - Monthly archives

3. **Test restores regularly**
   - Restore to staging monthly
   - Verify data integrity

4. **Backup volumes separately**
   - `docker run --volumes-from` for data
   - Regular database dumps
   - File system snapshots

**Retention Policy Example:**
```
Last 5 versions: Keep indefinitely
> 5 versions old: Delete after 30 days
Weekly backups: Keep 3 months
Monthly backups: Keep 1 year
```

---

## 📦 Registry Integration

Push and pull images from Docker registries.

### Supported Registries

- **Docker Hub** (docker.io)
- **GitHub Container Registry** (ghcr.io)
- **GitLab Container Registry** (registry.gitlab.com)
- **AWS ECR**
- **Google Container Registry**
- **Azure Container Registry**
- **Private registries**

### Login to Registry

**How to Login:**
1. Server page → **"Registries"** tab
2. Click **"Add Registry"**
3. Enter credentials:
   - **Registry:** `docker.io` or `ghcr.io`
   - **Username:** your username
   - **Password:** access token
4. Click **"Login"**

**GitHub Example:**
```
Registry: ghcr.io
Username: yourusername
Password: ghp_your_personal_access_token
```

### Tag Image for Registry

Prepare image for pushing:

**Format:**
```
registry.com/username/image-name:tag
```

**Examples:**
```
docker.io/username/my-app:latest
ghcr.io/username/my-app:v1.2.0
registry.gitlab.com/username/project:prod
```

**How to Tag:**
1. Images tab → Select image
2. Click **"🏷️ Tag"**
3. Enter target name
4. Click **"Tag"**

### Push to Registry

**How to Push:**
1. Images tab → Tagged image
2. Click **"⬆️ Push"**
3. Image uploaded to registry

**Use Cases:**
- Share images across servers
- Backup images offsite
- CI/CD pipelines
- Team collaboration

### Pull from Private Registry

**Prerequisites:**
- Registry credentials added
- Logged in to registry

**How to Pull:**
1. Images tab → **"Pull Image"**
2. Enter full image name:
   ```
   ghcr.io/username/my-app:latest
   ```
3. DevFlow Pro authenticates automatically
4. Image downloaded

---

## 🧹 System Management

Manage Docker system resources.

### Docker System Info

View Docker engine information:

**How to View:**
1. Server page → **"Docker Info"** tab

**What You See:**
- Docker version
- Total containers (running/stopped)
- Total images
- Storage driver
- Logging driver
- Kernel version
- Operating system
- Architecture
- CPUs
- Total memory

### Docker Disk Usage

See how Docker uses disk space:

**Categories:**
- **Images** - Pulled and built images
- **Containers** - Container layer storage
- **Volumes** - Persistent data
- **Build Cache** - Cached layers

**Example Output:**
```
Images:       12      3.2GB
Containers:   5       450MB
Volumes:      8       1.8GB
Build Cache:  15      890MB
---
Total:        5.3GB
```

**How to View:**
1. Server page → **"Disk Usage"** tab

### System Cleanup (Prune)

**Free up disk space:**

**What Gets Removed:**
- Stopped containers
- Unused networks
- Dangling images
- Build cache

**Options:**

1. **Standard Prune**
   - Safe - removes only unused resources
   - Keeps volumes

2. **Aggressive Prune**
   - Removes everything unused
   - **Includes volumes** ⚠️

**How to Prune:**
1. Server page → **"Cleanup"** tab
2. Choose prune type
3. Review what will be deleted
4. Click **"Prune System"**
5. Confirm

**Typical Savings:**
- Small project: 500MB - 1GB
- Active development: 2-5GB
- Long-running server: 10-20GB

### Automated Cleanup

**Recommended Schedule:**

```bash
# Weekly cleanup (cron)
0 2 * * 0 docker system prune -f

# Monthly aggressive cleanup
0 3 1 * * docker system prune -af --volumes
```

**Configure in DevFlow Pro:**
1. Server settings → **"Automation"**
2. Enable **"Auto-cleanup"**
3. Set schedule: `Weekly`
4. Set aggressiveness: `Standard`

---

## ✅ Best Practices

### Container Design

**Single Responsibility:**
- One service per container
- Don't run multiple processes
- Use docker-compose for multi-service

**Immutable Infrastructure:**
- Don't modify running containers
- Make changes → rebuild → redeploy
- Containers are disposable

**Stateless Applications:**
- Store data in volumes, not containers
- Use databases for persistence
- Enable horizontal scaling

### Resource Management

**Set Limits Always:**
```yaml
deploy:
  resources:
    limits:
      memory: 512M
      cpus: '0.5'
    reservations:
      memory: 256M
```

**Monitor Resource Usage:**
- Check stats weekly
- Adjust limits based on actual usage
- Watch for memory leaks

### Security

**Non-Root User:**
```dockerfile
RUN adduser -D -u 1000 appuser
USER appuser
```

**Read-Only Filesystem:**
```yaml
services:
  app:
    read_only: true
    tmpfs:
      - /tmp
```

**Secrets Management:**
- Never hardcode secrets
- Use Docker secrets or env files
- Rotate credentials regularly

### Performance

**Layer Optimization:**
```dockerfile
# Good - dependencies cached
COPY package.json package-lock.json ./
RUN npm ci
COPY . .

# Bad - cache busted on code change
COPY . .
RUN npm install
```

**Multi-Stage Builds:**
```dockerfile
FROM node:20 AS builder
WORKDIR /app
COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=builder /app/dist /usr/share/nginx/html
```

### Backup Strategy

**3-2-1 Rule:**
- 3 copies of data
- 2 different media types
- 1 offsite backup

**What to Backup:**
- Database dumps (daily)
- Volume snapshots (weekly)
- Container images (before deployment)
- Configuration files (git)

---

## 🔧 Troubleshooting

### Container Won't Start

**Symptoms:**
- Container stops immediately after starting
- Status shows "Exited (1)"

**Diagnosis:**
```bash
# Check logs
docker logs my-project

# Inspect container
docker inspect my-project

# Check exit code
docker ps -a | grep my-project
```

**Common Causes:**
1. Port already in use
2. Volume mount path doesn't exist
3. Missing environment variables
4. Application crash on startup

**Solutions:**
- Change port mapping
- Create volume mount directories
- Check .env file
- Fix application code

### Network Issues

**Symptoms:**
- Containers can't communicate
- DNS resolution fails
- Connection refused errors

**Diagnosis:**
```bash
# List networks
docker network ls

# Inspect network
docker network inspect my-network

# Test connectivity
docker exec my-app ping my-database
```

**Solutions:**
- Connect containers to same network
- Use container names, not IPs
- Check firewall rules
- Restart Docker daemon

### Volume Permission Issues

**Symptoms:**
- "Permission denied" errors
- Can't write to volume
- Files owned by root

**Diagnosis:**
```bash
# Check volume ownership
docker exec my-app ls -la /app/storage

# Check container user
docker exec my-app whoami
```

**Solutions:**
```dockerfile
# In Dockerfile
RUN chown -R www-data:www-data /app/storage
USER www-data
```

Or:
```bash
# On host
sudo chown -R 1000:1000 /var/lib/docker/volumes/my-vol/_data
```

### Disk Space Issues

**Symptoms:**
- "No space left on device"
- Slow container performance
- Build failures

**Diagnosis:**
```bash
# Check disk usage
docker system df

# Check host disk
df -h
```

**Solutions:**
1. Prune unused resources
2. Remove old images
3. Clean build cache
4. Increase disk size

### Memory Issues

**Symptoms:**
- Container killed randomly
- Out of memory errors
- System slowdown

**Diagnosis:**
```bash
# Check memory usage
docker stats --no-stream

# Check container limits
docker inspect my-app | grep Memory
```

**Solutions:**
1. Increase memory limit
2. Fix memory leaks in app
3. Add swap space
4. Optimize application

---

## 📚 Additional Resources

### Documentation
- [Docker Official Docs](https://docs.docker.com/)
- [Docker Compose Reference](https://docs.docker.com/compose/)
- [Dockerfile Best Practices](https://docs.docker.com/develop/dev-best-practices/)

### DevFlow Pro Docs
- [Project Management](USER_GUIDE.md)
- [Deployment Guide](DEPLOYMENT.md)
- [API Documentation](API.md)
- [Troubleshooting](TROUBLESHOOTING.md)

### Community
- GitHub Issues
- Discord Community
- Stack Overflow (`#devflow-pro`)

---

## 🆘 Getting Help

**Need assistance?**

1. **Check documentation** - Most answers are here
2. **Search GitHub issues** - Someone may have had the same problem
3. **Ask in Discord** - Community support
4. **Open GitHub issue** - For bugs or feature requests

**Include in bug reports:**
- DevFlow Pro version
- Docker version
- Error messages
- Relevant logs
- Steps to reproduce

---

<div align="center">

**Master Docker Management with DevFlow Pro**

[Back to Main README](README.md) • [Features](FEATURES.md) • [User Guide](USER_GUIDE.md)

</div>

