# DevFlow Pro - API Documentation

## Overview

DevFlow Pro provides a RESTful API for programmatic access to server metrics and deployment webhooks.

**Base URL:** `http://your-domain.com/api`

---

## Authentication

### Sanctum Token Authentication

Most API endpoints require authentication using Laravel Sanctum tokens.

#### Get API Token

```bash
# Login to get token
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}

# Response
{
  "token": "1|abc123...",
  "user": {...}
}
```

#### Use Token

```bash
# Include token in headers
Authorization: Bearer 1|abc123...
```

---

## Endpoints

### User

#### Get Current User

```http
GET /api/user
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "created_at": "2025-01-01T00:00:00.000000Z"
}
```

---

### Server Metrics

#### Get Server Metrics

```http
GET /api/servers/{server}/metrics
Authorization: Bearer {token}
```

**Parameters:**
- `server` (required): Server ID

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "server_id": 1,
      "cpu_usage": 45.5,
      "memory_usage": 62.3,
      "disk_usage": 35.8,
      "network_in": 1024000,
      "network_out": 2048000,
      "load_average": 1.5,
      "active_connections": 42,
      "recorded_at": "2025-01-01T12:00:00.000000Z"
    }
  ]
}
```

#### Store Server Metrics

```http
POST /api/servers/{server}/metrics
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "cpu_usage": 45.5,
  "memory_usage": 62.3,
  "disk_usage": 35.8,
  "network_in": 1024000,
  "network_out": 2048000,
  "load_average": 1.5,
  "active_connections": 42
}
```

**Response:**
```json
{
  "message": "Metrics stored successfully",
  "data": {
    "id": 1,
    "server_id": 1,
    "cpu_usage": 45.5,
    "memory_usage": 62.3,
    "disk_usage": 35.8,
    "recorded_at": "2025-01-01T12:00:00.000000Z"
  }
}
```

---

### Deployment Webhooks

#### Trigger Deployment

```http
POST /api/webhooks/deploy/{token}
Content-Type: application/json
```

**Parameters:**
- `token` (required): Project slug for webhook authentication

**GitHub Webhook Payload:**
```json
{
  "ref": "refs/heads/main",
  "after": "abc123def456",
  "head_commit": {
    "message": "Update feature X"
  }
}
```

**GitLab Webhook Payload:**
```json
{
  "object_kind": "push",
  "ref": "refs/heads/main",
  "checkout_sha": "abc123def456",
  "commits": [{
    "message": "Update feature X"
  }]
}
```

**Bitbucket Webhook Payload:**
```json
{
  "push": {
    "changes": [{
      "new": {
        "name": "main",
        "target": {
          "hash": "abc123def456",
          "message": "Update feature X"
        }
      }
    }]
  }
}
```

**Response:**
```json
{
  "message": "Deployment triggered successfully",
  "deployment_id": 42
}
```

---

## Webhook Setup

### GitHub

1. Go to repository **Settings** → **Webhooks**
2. Click **Add webhook**
3. Set Payload URL: `http://your-domain.com/api/webhooks/deploy/PROJECT-SLUG`
4. Content type: `application/json`
5. Select: **Just the push event**
6. Click **Add webhook**

### GitLab

1. Go to repository **Settings** → **Webhooks**
2. Set URL: `http://your-domain.com/api/webhooks/deploy/PROJECT-SLUG`
3. Select **Push events**
4. Click **Add webhook**

### Bitbucket

1. Go to repository **Settings** → **Webhooks**
2. Click **Add webhook**
3. Title: DevFlow Pro Deploy
4. URL: `http://your-domain.com/api/webhooks/deploy/PROJECT-SLUG`
5. Select **Repository push**
6. Click **Save**

---

## Monitoring Script Example

### Push Server Metrics

```bash
#!/bin/bash
# monitor.sh - Push server metrics to DevFlow Pro

API_URL="http://your-domain.com/api"
SERVER_ID="1"
TOKEN="your-api-token"

# Get metrics
CPU=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | sed 's/%us,//')
MEM=$(free | grep Mem | awk '{print ($3/$2) * 100.0}')
DISK=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
LOAD=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | sed 's/,//')

# Send to API
curl -X POST "${API_URL}/servers/${SERVER_ID}/metrics" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d "{
    \"cpu_usage\": ${CPU},
    \"memory_usage\": ${MEM},
    \"disk_usage\": ${DISK},
    \"load_average\": ${LOAD},
    \"network_in\": 0,
    \"network_out\": 0,
    \"active_connections\": 0
  }"
```

### Setup Cron Job

```bash
# Add to crontab
* * * * * /path/to/monitor.sh
```

---

## Rate Limiting

API endpoints are rate-limited:
- **Authenticated:** 60 requests per minute
- **Webhooks:** 30 requests per minute

**Rate limit headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
```

---

## Error Responses

### Standard Error Format

```json
{
  "error": "Error message",
  "message": "Detailed explanation",
  "status": 400
}
```

### Common HTTP Status Codes

- `200` - OK
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Too Many Requests
- `500` - Server Error

### Validation Errors

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "cpu_usage": [
      "The cpu usage field is required."
    ]
  }
}
```

---

## Testing with cURL

### Get Metrics

```bash
curl -X GET \
  http://your-domain.com/api/servers/1/metrics \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Post Metrics

```bash
curl -X POST \
  http://your-domain.com/api/servers/1/metrics \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "cpu_usage": 45.5,
    "memory_usage": 62.3,
    "disk_usage": 35.8,
    "network_in": 1024000,
    "network_out": 2048000,
    "load_average": 1.5,
    "active_connections": 42
  }'
```

### Test Webhook

```bash
curl -X POST \
  http://your-domain.com/api/webhooks/deploy/my-project \
  -H "Content-Type: application/json" \
  -d '{
    "ref": "refs/heads/main",
    "after": "abc123",
    "head_commit": {
      "message": "Test deployment"
    }
  }'
```

---

## SDK Examples

### JavaScript/Node.js

```javascript
const axios = require('axios');

const api = axios.create({
  baseURL: 'http://your-domain.com/api',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  }
});

// Get metrics
api.get('/servers/1/metrics')
  .then(response => console.log(response.data))
  .catch(error => console.error(error));

// Post metrics
api.post('/servers/1/metrics', {
  cpu_usage: 45.5,
  memory_usage: 62.3,
  disk_usage: 35.8,
  network_in: 1024000,
  network_out: 2048000,
  load_average: 1.5,
  active_connections: 42
})
  .then(response => console.log(response.data))
  .catch(error => console.error(error));
```

### Python

```python
import requests

API_URL = 'http://your-domain.com/api'
TOKEN = 'YOUR_TOKEN'

headers = {
    'Authorization': f'Bearer {TOKEN}',
    'Content-Type': 'application/json'
}

# Get metrics
response = requests.get(
    f'{API_URL}/servers/1/metrics',
    headers=headers
)
print(response.json())

# Post metrics
data = {
    'cpu_usage': 45.5,
    'memory_usage': 62.3,
    'disk_usage': 35.8,
    'network_in': 1024000,
    'network_out': 2048000,
    'load_average': 1.5,
    'active_connections': 42
}

response = requests.post(
    f'{API_URL}/servers/1/metrics',
    json=data,
    headers=headers
)
print(response.json())
```

### PHP

```php
<?php

$apiUrl = 'http://your-domain.com/api';
$token = 'YOUR_TOKEN';

$headers = [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
];

// Get metrics
$ch = curl_init($apiUrl . '/servers/1/metrics');
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
echo $response;

// Post metrics
$data = [
    'cpu_usage' => 45.5,
    'memory_usage' => 62.3,
    'disk_usage' => 35.8,
    'network_in' => 1024000,
    'network_out' => 2048000,
    'load_average' => 1.5,
    'active_connections' => 42
];

$ch = curl_init($apiUrl . '/servers/1/metrics');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
echo $response;
```

---

## Docker Management API ⭐ NEW!

### Container Statistics

#### Get Container Stats

```http
GET /api/projects/{project}/docker/stats
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "stats": {
    "Container": "my-project",
    "CPUPerc": "15.5%",
    "MemUsage": "256MB / 512MB",
    "MemPerc": "50%",
    "NetIO": "1.2MB / 850KB",
    "BlockIO": "450KB / 120KB",
    "PIDs": "12"
  }
}
```

#### Set Resource Limits

```http
POST /api/projects/{project}/docker/limits
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "memory_mb": 512,
  "cpu_shares": 1024
}
```

**Response:**
```json
{
  "success": true,
  "message": "Resource limits updated"
}
```

---

### Volume Management

#### List Volumes

```http
GET /api/servers/{server}/docker/volumes
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "volumes": [
    {
      "Name": "my-project-db-data",
      "Driver": "local",
      "Mountpoint": "/var/lib/docker/volumes/my-project-db-data/_data",
      "CreatedAt": "2025-11-10T10:00:00Z",
      "Labels": {
        "project": "my-project",
        "type": "database"
      }
    }
  ]
}
```

#### Create Volume

```http
POST /api/servers/{server}/docker/volumes
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "name": "my-project-uploads",
  "driver": "local",
  "labels": {
    "project": "my-project",
    "type": "storage"
  }
}
```

**Response:**
```json
{
  "success": true,
  "volume_name": "my-project-uploads"
}
```

#### Delete Volume

```http
DELETE /api/servers/{server}/docker/volumes/{volume_name}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Volume deleted successfully"
}
```

---

### Network Management

#### List Networks

```http
GET /api/servers/{server}/docker/networks
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "networks": [
    {
      "Name": "my-project-network",
      "ID": "abc123...",
      "Driver": "bridge",
      "Scope": "local"
    }
  ]
}
```

#### Create Network

```http
POST /api/servers/{server}/docker/networks
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "name": "my-app-network",
  "driver": "bridge"
}
```

**Response:**
```json
{
  "success": true,
  "network_id": "abc123def456..."
}
```

#### Connect Container to Network

```http
POST /api/projects/{project}/docker/networks/connect
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "network_name": "my-app-network"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Container connected to network"
}
```

---

### Image Management

#### List Images

```http
GET /api/servers/{server}/docker/images
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "images": [
    {
      "Repository": "nginx",
      "Tag": "alpine",
      "ID": "abc123...",
      "Size": "24.1MB",
      "CreatedAt": "2025-11-01T10:00:00Z"
    }
  ]
}
```

#### Pull Image

```http
POST /api/servers/{server}/docker/images/pull
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "image": "nginx:alpine"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Image pulled successfully",
  "output": "latest: Pulling from library/nginx..."
}
```

#### Delete Image

```http
DELETE /api/servers/{server}/docker/images/{image_id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Image deleted successfully"
}
```

#### Prune Images

```http
POST /api/servers/{server}/docker/images/prune
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "all": false
}
```

**Response:**
```json
{
  "success": true,
  "output": "Total reclaimed space: 850MB"
}
```

---

### Docker Compose

#### Deploy with Compose

```http
POST /api/projects/{project}/docker/compose/up
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "output": "Creating network...\nCreating app...\nCreating db...\nCreating redis..."
}
```

#### Get Compose Status

```http
GET /api/projects/{project}/docker/compose/status
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "services": [
    {
      "Name": "app",
      "State": "running",
      "Publishers": [
        {
          "URL": "0.0.0.0",
          "TargetPort": 80,
          "PublishedPort": 8000
        }
      ]
    },
    {
      "Name": "db",
      "State": "running"
    }
  ]
}
```

---

### Container Execution

#### Execute Command

```http
POST /api/projects/{project}/docker/exec
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "command": "php artisan migrate",
  "interactive": false
}
```

**Response:**
```json
{
  "success": true,
  "output": "Migration table created successfully.\nMigrating: 2024_01_01_000000_create_users_table\nMigrated:  2024_01_01_000000_create_users_table (45.23ms)"
}
```

#### Get Container Processes

```http
GET /api/projects/{project}/docker/processes
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "processes": "PID    USER     COMMAND\n1      root     nginx: master\n12     www-data nginx: worker"
}
```

---

### Backup & Restore

#### Export Container

```http
POST /api/projects/{project}/docker/backup
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "backup_name": "my-project-backup-v1"
}
```

**Response:**
```json
{
  "success": true,
  "backup_name": "my-project-backup-v1",
  "image_id": "sha256:abc123..."
}
```

#### Save Image to File

```http
POST /api/servers/{server}/docker/images/save
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "image_name": "my-project-backup-v1",
  "file_path": "/backups/my-project-2025-11-10.tar"
}
```

**Response:**
```json
{
  "success": true,
  "file_path": "/backups/my-project-2025-11-10.tar"
}
```

---

### Registry Operations

#### Registry Login

```http
POST /api/servers/{server}/docker/registry/login
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "registry": "ghcr.io",
  "username": "yourusername",
  "password": "your_token"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login Succeeded"
}
```

#### Push Image

```http
POST /api/servers/{server}/docker/images/push
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "image_name": "ghcr.io/username/my-app:latest"
}
```

**Response:**
```json
{
  "success": true,
  "output": "The push refers to repository [ghcr.io/username/my-app]..."
}
```

#### Tag Image

```http
POST /api/servers/{server}/docker/images/tag
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "source_image": "my-app:latest",
  "target_image": "ghcr.io/username/my-app:v1.0.0"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Image tagged successfully"
}
```

---

### System Management

#### Get Docker System Info

```http
GET /api/servers/{server}/docker/info
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "info": {
    "ServerVersion": "28.5.2",
    "Containers": 5,
    "ContainersRunning": 3,
    "ContainersStopped": 2,
    "Images": 12,
    "Driver": "overlay2",
    "NCPU": 4,
    "MemTotal": 8589934592,
    "OperatingSystem": "Ubuntu 22.04.3 LTS"
  }
}
```

#### Get Disk Usage

```http
GET /api/servers/{server}/docker/disk-usage
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "usage": [
    {
      "Type": "Images",
      "TotalCount": 12,
      "Active": 8,
      "Size": "3.2GB",
      "Reclaimable": "450MB"
    },
    {
      "Type": "Containers",
      "TotalCount": 5,
      "Active": 3,
      "Size": "450MB",
      "Reclaimable": "120MB"
    },
    {
      "Type": "Volumes",
      "TotalCount": 8,
      "Active": 6,
      "Size": "1.8GB",
      "Reclaimable": "200MB"
    }
  ]
}
```

#### System Prune

```http
POST /api/servers/{server}/docker/prune
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "volumes": false
}
```

**Response:**
```json
{
  "success": true,
  "output": "Deleted Containers:\nabc123...\ndef456...\n\nDeleted Networks:\nmy-old-network\n\nTotal reclaimed space: 1.2GB"
}
```

---

## Webhook Automation Examples

### Automated Deployment + Backup

```bash
#!/bin/bash
# deploy-with-backup.sh

PROJECT_ID="my-project"
API_URL="https://devflow.example.com/api"
TOKEN="your_token_here"

# Create backup before deployment
echo "Creating backup..."
curl -X POST "${API_URL}/projects/${PROJECT_ID}/docker/backup" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"backup_name": "pre-deploy-'$(date +%Y%m%d-%H%M%S)'"}'

# Trigger deployment
echo "Deploying..."
curl -X POST "${API_URL}/webhooks/deploy/${PROJECT_ID}" \
  -H "Authorization: Bearer ${TOKEN}"

echo "Deployment initiated with backup!"
```

### Automated Cleanup Script

```bash
#!/bin/bash
# weekly-cleanup.sh

SERVER_ID="1"
API_URL="https://devflow.example.com/api"
TOKEN="your_token_here"

echo "Starting weekly cleanup..."

# Prune unused images
curl -X POST "${API_URL}/servers/${SERVER_ID}/docker/images/prune" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"all": false}'

# System prune (no volumes)
curl -X POST "${API_URL}/servers/${SERVER_ID}/docker/prune" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"volumes": false}'

echo "Cleanup completed!"
```

---

## Best Practices

1. **Secure your tokens** - Store API tokens securely
2. **Use HTTPS** - Always use HTTPS in production
3. **Handle rate limits** - Implement exponential backoff
4. **Validate webhooks** - Verify webhook signatures
5. **Log errors** - Keep track of API errors
6. **Monitor usage** - Track API usage patterns
7. **Backup before operations** - Always backup before destructive operations ⭐ NEW!
8. **Set resource limits** - Prevent containers from consuming excessive resources ⭐ NEW!
9. **Regular cleanup** - Schedule automated cleanup to free disk space ⭐ NEW!
10. **Use named volumes** - Better management and portability ⭐ NEW!

---

**Full API Reference:** Check `routes/api.php` for all available endpoints.

**Need custom endpoints?** Open an issue or submit a pull request!

