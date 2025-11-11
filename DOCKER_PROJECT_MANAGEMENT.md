# Docker Project Management Feature

## Overview
Added Docker management functionality to individual projects, showing only Docker images and containers related to each specific project.

## What's New

### 1. **Project-Specific Docker Service Methods**
Location: `app/Services/DockerService.php`

Added new methods:
- `listProjectImages(Project $project)` - Lists only Docker images related to a specific project (filtered by project slug)
- `getContainerStatus(Project $project)` - Gets the status of a project's container

### 2. **Project Docker Management Component**
Location: `app/Livewire/Projects/ProjectDockerManagement.php`

A new Livewire component that provides:
- **Overview Tab**: Container status, stats (CPU, Memory, Network I/O, Disk I/O), and quick actions
- **Images Tab**: List of Docker images related to the project with ability to delete
- **Logs Tab**: Real-time container logs with configurable line limits (50-500 lines)

### 3. **Docker Management View**
Location: `resources/views/livewire/projects/project-docker-management.blade.php`

Features:
- 🐳 **Container Status Card**: Shows current container state, ports, and image info
- 📊 **Real-time Stats**: CPU, Memory, Network I/O, and Disk I/O monitoring
- 🔨 **Build Actions**: Build new Docker images for the project
- ▶️ **Container Controls**: Start, Stop, Restart container
- 💾 **Backup**: Export container as a backup image
- 📝 **Live Logs**: View container logs with refresh capability
- 🗑️ **Image Management**: Delete unused Docker images

### 4. **Integration with Project Show Page**
Location: `resources/views/livewire/projects/project-show.blade.php`

The Docker management component is now integrated into each project's detail page, displayed prominently above the project details.

## Features

### Container Management
- **Start/Stop/Restart**: Full control over container lifecycle
- **Status Monitoring**: Real-time container status display
- **Resource Stats**: Live CPU, Memory, Network, and Disk I/O metrics
- **Backup**: Create backup images of running containers

### Image Management
- **Filtered Images**: Shows only images related to the specific project
- **Build Images**: Build new Docker images from the project
- **Delete Images**: Clean up unused images
- **Image Details**: View repository, tag, ID, creation date, and size

### Log Management
- **Real-time Logs**: View container logs in a terminal-style interface
- **Configurable Lines**: Choose between 50, 100, 200, or 500 log lines
- **Auto-refresh**: Refresh logs on demand

## How It Works

### Image Filtering
The system filters Docker images by matching the project slug against:
1. Image repository name
2. Image tags
3. Exact project slug match

This ensures that each project only shows its related Docker images, not all images on the server.

### Container Detection
Containers are detected by filtering Docker containers by the project's slug name, ensuring accurate project-to-container mapping.

## Usage

1. **Navigate to Project**: Go to any project detail page
2. **Docker Management Section**: Scroll to the Docker Management section (appears below project stats)
3. **Choose Tab**:
   - **Overview**: View container status and stats
   - **Images**: Manage Docker images
   - **Logs**: View container logs

### Common Actions

**Building a New Image:**
1. Click "🔨 Build New Image" button
2. System will build a Docker image using project's Dockerfile or auto-generate one
3. Image will appear in the Images tab

**Starting a Container:**
1. If no container exists, click "▶️ Start Container"
2. Container will start on the configured port
3. Access URL will be displayed on the project page

**Viewing Logs:**
1. Switch to "Logs" tab
2. Select number of lines to display
3. Click "🔄 Refresh Logs" to update

**Creating a Backup:**
1. Click "💾 Backup Container"
2. System creates a timestamped backup image
3. Backup appears in the Images list

## Benefits

✅ **Project Isolation**: Each project shows only its own Docker resources
✅ **Simplified Management**: All Docker operations in one place
✅ **Real-time Monitoring**: Live stats and logs for debugging
✅ **Easy Backups**: One-click container backups
✅ **Clean Interface**: Tabbed layout for organized access to features

## Technical Details

### Security
- Only project owners can access Docker management for their projects
- All Docker operations are executed on the project's assigned server
- SSH key authentication for remote servers

### Performance
- Lightweight Docker commands using JSON output
- Filtered queries to reduce data transfer
- Cached container stats for quick display

## Future Enhancements

Potential future features:
- Docker Compose support per project
- Volume management for projects
- Network configuration per project
- Resource limit configuration
- Scheduled container backups
- Log export functionality

## Files Modified/Created

**New Files:**
- `app/Livewire/Projects/ProjectDockerManagement.php`
- `resources/views/livewire/projects/project-docker-management.blade.php`
- `DOCKER_PROJECT_MANAGEMENT.md` (this file)

**Modified Files:**
- `app/Services/DockerService.php` - Added project-specific methods
- `resources/views/livewire/projects/project-show.blade.php` - Integrated Docker management component

## Notes

- Docker must be installed on the server for this feature to work
- Container names are based on project slugs
- Images are tagged with project slugs for easy filtering
- All operations support both local and remote servers via SSH

---

**Created:** 2025-11-11
**Version:** 1.0.0

