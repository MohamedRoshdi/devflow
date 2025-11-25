# DevFlow Pro - API Documentation

## API Overview

DevFlow Pro provides a comprehensive RESTful API for managing projects, deployments, and infrastructure programmatically.

### Base URL
```
https://devflow.yourdomain.com/api/v1
```

### Authentication
All API requests require authentication using Bearer tokens.

```bash
curl -H "Authorization: Bearer YOUR_API_TOKEN" \
     https://devflow.yourdomain.com/api/v1/projects
```

### Rate Limiting
- 1000 requests per hour per API key
- 100 requests per minute per API key
- Rate limit headers included in responses

### Response Format
All responses are JSON formatted with the following structure:

```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful",
  "meta": {
    "timestamp": "2024-11-25T10:00:00Z",
    "version": "1.0"
  }
}
```

### Error Responses
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "The requested resource was not found",
    "details": { ... }
  },
  "meta": { ... }
}
```

---

## Authentication Endpoints

### Generate API Token
```http
POST /api/v1/auth/token
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "secure_password",
  "token_name": "My API Client",
  "expires_in_days": 365
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_at": "2025-11-25T10:00:00Z",
    "permissions": ["read", "write", "deploy"]
  }
}
```

### Revoke Token
```http
DELETE /api/v1/auth/token
```

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response:**
```json
{
  "success": true,
  "message": "Token revoked successfully"
}
```

### Verify Token
```http
GET /api/v1/auth/verify
```

**Response:**
```json
{
  "success": true,
  "data": {
    "valid": true,
    "user": {
      "id": 1,
      "email": "user@example.com",
      "name": "John Doe"
    },
    "expires_at": "2025-11-25T10:00:00Z"
  }
}
```

---

## Project Endpoints

### List All Projects
```http
GET /api/v1/projects
```

**Query Parameters:**
- `page` (integer): Page number for pagination
- `per_page` (integer): Items per page (max: 100)
- `status` (string): Filter by status (active, inactive, maintenance)
- `framework` (string): Filter by framework
- `search` (string): Search in project names

**Example Request:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     "https://devflow.yourdomain.com/api/v1/projects?status=active&page=1"
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "My Laravel App",
      "slug": "my-laravel-app",
      "status": "active",
      "framework": "laravel",
      "repository_url": "https://github.com/user/repo.git",
      "branch": "main",
      "last_deployment": {
        "id": 123,
        "status": "success",
        "deployed_at": "2024-11-25T09:30:00Z"
      },
      "created_at": "2024-01-15T10:00:00Z",
      "updated_at": "2024-11-25T09:30:00Z"
    }
  ],
  "meta": {
    "pagination": {
      "total": 42,
      "per_page": 20,
      "current_page": 1,
      "last_page": 3
    }
  }
}
```

### Get Project Details
```http
GET /api/v1/projects/{project_id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "My Laravel App",
    "slug": "my-laravel-app",
    "status": "active",
    "framework": "laravel",
    "php_version": "8.4",
    "repository_url": "https://github.com/user/repo.git",
    "branch": "main",
    "auto_deploy": true,
    "docker_compose_path": "docker-compose.yml",
    "environment_variables": {
      "APP_ENV": "production",
      "APP_DEBUG": "false"
    },
    "domains": [
      {
        "id": 1,
        "domain": "example.com",
        "ssl_enabled": true,
        "ssl_expires_at": "2025-02-15T00:00:00Z"
      }
    ],
    "server": {
      "id": 1,
      "name": "Production Server",
      "ip_address": "192.168.1.1",
      "status": "online"
    },
    "storage": {
      "driver": "s3",
      "usage_bytes": 1073741824,
      "limit_bytes": 10737418240
    },
    "statistics": {
      "total_deployments": 156,
      "successful_deployments": 150,
      "failed_deployments": 6,
      "average_deployment_time": 120,
      "uptime_percentage": 99.95
    }
  }
}
```

### Create Project
```http
POST /api/v1/projects
```

**Request Body:**
```json
{
  "name": "New Project",
  "slug": "new-project",
  "repository_url": "https://github.com/user/new-repo.git",
  "branch": "main",
  "framework": "laravel",
  "php_version": "8.4",
  "server_id": 1,
  "auto_deploy": true,
  "environment_variables": {
    "APP_ENV": "production",
    "APP_DEBUG": "false",
    "DB_CONNECTION": "mysql"
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "New Project",
    "slug": "new-project",
    ...
  },
  "message": "Project created successfully"
}
```

### Update Project
```http
PUT /api/v1/projects/{project_id}
```

**Request Body:**
```json
{
  "name": "Updated Project Name",
  "branch": "develop",
  "auto_deploy": false,
  "environment_variables": {
    "APP_ENV": "staging"
  }
}
```

### Delete Project
```http
DELETE /api/v1/projects/{project_id}
```

**Query Parameters:**
- `force` (boolean): Force delete without confirmation
- `backup` (boolean): Create backup before deletion

**Response:**
```json
{
  "success": true,
  "message": "Project deleted successfully",
  "data": {
    "backup_created": true,
    "backup_path": "/backups/project-backup-2024-11-25.tar.gz"
  }
}
```

---

## Deployment Endpoints

### Trigger Deployment
```http
POST /api/v1/projects/{project_id}/deploy
```

**Request Body (optional):**
```json
{
  "branch": "main",
  "commit_hash": "abc123def456",
  "force": false,
  "clear_cache": true,
  "run_migrations": true,
  "notify_on_completion": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "deployment_id": 124,
    "status": "queued",
    "estimated_time": 120,
    "queue_position": 1
  },
  "message": "Deployment queued successfully"
}
```

### Get Deployment Status
```http
GET /api/v1/deployments/{deployment_id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 124,
    "project_id": 1,
    "status": "running",
    "progress": 65,
    "current_step": "Building Docker images",
    "commit_hash": "abc123def456",
    "branch": "main",
    "triggered_by": "api",
    "started_at": "2024-11-25T10:00:00Z",
    "logs": [
      {
        "timestamp": "2024-11-25T10:00:01Z",
        "level": "info",
        "message": "Deployment started"
      },
      {
        "timestamp": "2024-11-25T10:00:05Z",
        "level": "info",
        "message": "Pulling latest changes from repository"
      }
    ]
  }
}
```

### Cancel Deployment
```http
POST /api/v1/deployments/{deployment_id}/cancel
```

**Response:**
```json
{
  "success": true,
  "message": "Deployment cancelled successfully",
  "data": {
    "deployment_id": 124,
    "status": "cancelled",
    "cancelled_at": "2024-11-25T10:05:00Z"
  }
}
```

### Rollback Deployment
```http
POST /api/v1/projects/{project_id}/rollback
```

**Request Body:**
```json
{
  "target_deployment_id": 123,
  "backup_current": true,
  "clear_cache": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "rollback_deployment_id": 125,
    "status": "running",
    "target_version": {
      "deployment_id": 123,
      "commit_hash": "xyz789",
      "deployed_at": "2024-11-24T15:00:00Z"
    }
  }
}
```

### List Deployment History
```http
GET /api/v1/projects/{project_id}/deployments
```

**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page
- `status` (string): Filter by status
- `from_date` (string): Start date (ISO 8601)
- `to_date` (string): End date (ISO 8601)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "commit_hash": "abc123",
      "branch": "main",
      "status": "success",
      "triggered_by": "webhook",
      "deployed_at": "2024-11-25T09:00:00Z",
      "duration_seconds": 120,
      "deployed_by": {
        "id": 1,
        "name": "John Doe"
      }
    }
  ],
  "meta": {
    "pagination": {
      "total": 156,
      "per_page": 20,
      "current_page": 1
    }
  }
}
```

---

## Docker Management Endpoints

### List Containers
```http
GET /api/v1/projects/{project_id}/containers
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "abc123def456",
      "name": "app",
      "image": "php:8.4-fpm",
      "status": "running",
      "created_at": "2024-11-25T08:00:00Z",
      "ports": ["9000"],
      "resources": {
        "cpu_usage": 15.5,
        "memory_usage_mb": 256,
        "memory_limit_mb": 512
      }
    },
    {
      "id": "def456ghi789",
      "name": "nginx",
      "image": "nginx:alpine",
      "status": "running",
      "ports": ["80", "443"]
    }
  ]
}
```

### Container Operations
```http
POST /api/v1/projects/{project_id}/containers/{container_id}/action
```

**Request Body:**
```json
{
  "action": "restart",
  "force": false
}
```

**Available Actions:**
- `start`
- `stop`
- `restart`
- `rebuild`
- `remove`

**Response:**
```json
{
  "success": true,
  "message": "Container restarted successfully",
  "data": {
    "container_id": "abc123def456",
    "status": "running",
    "action_completed_at": "2024-11-25T10:10:00Z"
  }
}
```

### Get Container Logs
```http
GET /api/v1/projects/{project_id}/containers/{container_id}/logs
```

**Query Parameters:**
- `lines` (integer): Number of lines to retrieve (default: 100)
- `since` (string): Show logs since timestamp
- `until` (string): Show logs until timestamp
- `follow` (boolean): Stream logs in real-time (WebSocket)

**Response:**
```json
{
  "success": true,
  "data": {
    "container_id": "abc123def456",
    "logs": [
      {
        "timestamp": "2024-11-25T10:00:00Z",
        "stream": "stdout",
        "message": "Server started on port 9000"
      }
    ]
  }
}
```

---

## Domain Management Endpoints

### Add Domain
```http
POST /api/v1/projects/{project_id}/domains
```

**Request Body:**
```json
{
  "domain": "example.com",
  "subdomain": "app",
  "ssl_enabled": true,
  "auto_renew_ssl": true,
  "redirect_to": null
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "domain": "example.com",
    "subdomain": "app",
    "full_domain": "app.example.com",
    "ssl_enabled": true,
    "ssl_status": "pending",
    "dns_configured": false
  }
}
```

### Verify Domain
```http
POST /api/v1/domains/{domain_id}/verify
```

**Response:**
```json
{
  "success": true,
  "data": {
    "dns_configured": true,
    "ssl_issued": true,
    "ssl_expires_at": "2025-02-25T00:00:00Z",
    "verification_status": "verified"
  }
}
```

### Generate SSL Certificate
```http
POST /api/v1/domains/{domain_id}/ssl/generate
```

**Request Body (optional):**
```json
{
  "provider": "letsencrypt",
  "force_renewal": false
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "certificate_issued": true,
    "expires_at": "2025-02-25T00:00:00Z",
    "issuer": "Let's Encrypt",
    "auto_renewal_enabled": true
  }
}
```

---

## Storage Management Endpoints

### Get Storage Usage
```http
GET /api/v1/projects/{project_id}/storage
```

**Response:**
```json
{
  "success": true,
  "data": {
    "driver": "s3",
    "total_usage_bytes": 1073741824,
    "limit_bytes": 10737418240,
    "usage_percentage": 10,
    "breakdown": {
      "uploads": 536870912,
      "logs": 268435456,
      "cache": 134217728,
      "backups": 134217728
    },
    "file_count": 15234
  }
}
```

### Clear Cache
```http
POST /api/v1/projects/{project_id}/cache/clear
```

**Request Body (optional):**
```json
{
  "cache_types": ["application", "view", "route", "config"],
  "clear_redis": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "cleared": {
      "application": true,
      "view": true,
      "route": true,
      "config": true,
      "redis": true
    },
    "space_freed_bytes": 134217728
  }
}
```

### Cleanup Storage
```http
POST /api/v1/projects/{project_id}/storage/cleanup
```

**Request Body:**
```json
{
  "remove_old_logs": true,
  "logs_older_than_days": 30,
  "remove_temp_files": true,
  "remove_old_backups": true,
  "backups_to_keep": 5
}
```

---

## Multi-Tenant Endpoints

### List Tenants
```http
GET /api/v1/projects/{project_id}/tenants
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "tenant-1",
      "name": "Acme Corp",
      "subdomain": "acme",
      "status": "active",
      "created_at": "2024-01-15T10:00:00Z",
      "database": "tenant_acme",
      "storage_usage_bytes": 104857600,
      "user_count": 25
    }
  ]
}
```

### Create Tenant
```http
POST /api/v1/projects/{project_id}/tenants
```

**Request Body:**
```json
{
  "name": "New Company",
  "subdomain": "newcompany",
  "admin_email": "admin@newcompany.com",
  "admin_password": "secure_password",
  "plan": "premium",
  "custom_config": {
    "theme": "blue",
    "features": ["api_access", "advanced_reports"]
  }
}
```

### Deploy to Tenants
```http
POST /api/v1/projects/{project_id}/tenants/deploy
```

**Request Body:**
```json
{
  "tenant_ids": ["tenant-1", "tenant-2"],
  "deployment_type": "code_and_migrations",
  "clear_cache": true,
  "maintenance_mode": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "deployment_id": 126,
    "tenants_queued": 2,
    "estimated_time": 300,
    "status": "running"
  }
}
```

---

## Monitoring Endpoints

### Get Project Health
```http
GET /api/v1/projects/{project_id}/health
```

**Response:**
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "uptime_percentage": 99.95,
    "response_time_ms": 125,
    "error_rate": 0.1,
    "checks": {
      "application": {
        "status": "healthy",
        "last_check": "2024-11-25T10:00:00Z"
      },
      "database": {
        "status": "healthy",
        "connections": 5,
        "max_connections": 100
      },
      "redis": {
        "status": "healthy",
        "memory_usage_mb": 64
      },
      "storage": {
        "status": "healthy",
        "free_space_gb": 450
      }
    }
  }
}
```

### Get Metrics
```http
GET /api/v1/projects/{project_id}/metrics
```

**Query Parameters:**
- `period` (string): hour, day, week, month
- `metrics` (array): Specific metrics to retrieve

**Response:**
```json
{
  "success": true,
  "data": {
    "period": "day",
    "metrics": {
      "requests": {
        "total": 125000,
        "per_minute_avg": 86.8,
        "peak": 250
      },
      "response_times": {
        "avg_ms": 125,
        "p50_ms": 100,
        "p95_ms": 250,
        "p99_ms": 500
      },
      "errors": {
        "total": 125,
        "rate": 0.1,
        "types": {
          "404": 100,
          "500": 20,
          "503": 5
        }
      },
      "resources": {
        "cpu_avg": 25.5,
        "memory_avg_mb": 512,
        "disk_io_mb": 1024
      }
    }
  }
}
```

---

## Webhook Endpoints

### Register Webhook
```http
POST /api/v1/webhooks
```

**Request Body:**
```json
{
  "url": "https://your-app.com/webhook",
  "events": ["deployment.started", "deployment.completed", "deployment.failed"],
  "secret": "webhook_secret_key",
  "active": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "url": "https://your-app.com/webhook",
    "events": ["deployment.started", "deployment.completed", "deployment.failed"],
    "active": true,
    "created_at": "2024-11-25T10:00:00Z"
  }
}
```

### Webhook Events
Available events:
- `deployment.started`
- `deployment.completed`
- `deployment.failed`
- `deployment.rolled_back`
- `project.created`
- `project.updated`
- `project.deleted`
- `domain.added`
- `ssl.expiring`
- `ssl.renewed`
- `health.degraded`
- `health.recovered`

### Webhook Payload Example
```json
{
  "event": "deployment.completed",
  "timestamp": "2024-11-25T10:15:00Z",
  "data": {
    "project_id": 1,
    "deployment_id": 124,
    "status": "success",
    "commit_hash": "abc123def456",
    "duration_seconds": 120
  },
  "signature": "sha256=abcdef1234567890..."
}
```

---

## Error Codes

### HTTP Status Codes
- `200` - Success
- `201` - Created
- `204` - No Content
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `409` - Conflict
- `422` - Unprocessable Entity
- `429` - Too Many Requests
- `500` - Internal Server Error
- `503` - Service Unavailable

### Application Error Codes
```json
{
  "AUTHENTICATION_FAILED": "Invalid credentials provided",
  "AUTHORIZATION_FAILED": "You don't have permission to perform this action",
  "RESOURCE_NOT_FOUND": "The requested resource was not found",
  "VALIDATION_FAILED": "The provided data failed validation",
  "DEPLOYMENT_IN_PROGRESS": "Another deployment is already in progress",
  "INSUFFICIENT_STORAGE": "Not enough storage space available",
  "RATE_LIMIT_EXCEEDED": "API rate limit exceeded",
  "SERVER_OFFLINE": "The target server is offline",
  "INVALID_REPOSITORY": "The repository URL is invalid or inaccessible",
  "BUILD_FAILED": "Docker build process failed",
  "DATABASE_ERROR": "Database operation failed",
  "EXTERNAL_SERVICE_ERROR": "External service is unavailable"
}
```

---

## SDK Examples

### PHP SDK
```php
<?php
use DevFlowPro\\Client;

$client = new Client([
    'api_key' => 'YOUR_API_KEY',
    'base_url' => 'https://devflow.yourdomain.com/api/v1'
]);

// List projects
$projects = $client->projects()->list();

// Deploy project
$deployment = $client->projects()
    ->find(1)
    ->deploy([
        'branch' => 'main',
        'clear_cache' => true
    ]);

// Get deployment status
$status = $client->deployments()
    ->find($deployment->id)
    ->status();
```

### Node.js SDK
```javascript
const DevFlowClient = require('@devflow-pro/client');

const client = new DevFlowClient({
    apiKey: 'YOUR_API_KEY',
    baseUrl: 'https://devflow.yourdomain.com/api/v1'
});

// Deploy project
const deployment = await client.projects
    .get(1)
    .deploy({
        branch: 'main',
        clearCache: true
    });

// Watch deployment progress
deployment.on('progress', (data) => {
    console.log(`Progress: ${data.percentage}% - ${data.message}`);
});

deployment.on('completed', (result) => {
    console.log('Deployment completed:', result);
});
```

### Python SDK
```python
from devflow_pro import Client

client = Client(
    api_key='YOUR_API_KEY',
    base_url='https://devflow.yourdomain.com/api/v1'
)

# List projects
projects = client.projects.list()

# Deploy project
deployment = client.projects.get(1).deploy(
    branch='main',
    clear_cache=True
)

# Monitor deployment
for log in deployment.stream_logs():
    print(f"{log.timestamp}: {log.message}")
```

### Go SDK
```go
package main

import (
    "fmt"
    "github.com/devflow-pro/go-client"
)

func main() {
    client := devflow.NewClient(
        "YOUR_API_KEY",
        "https://devflow.yourdomain.com/api/v1",
    )

    // Deploy project
    deployment, err := client.Projects.Deploy(1, &devflow.DeployOptions{
        Branch:     "main",
        ClearCache: true,
    })

    if err != nil {
        panic(err)
    }

    // Check status
    status, _ := client.Deployments.GetStatus(deployment.ID)
    fmt.Printf("Deployment status: %s\n", status.Status)
}
```

---

## API Versioning

The API uses URL versioning. The current version is `v1`.

### Version Header
You can also specify the version via header:
```
X-API-Version: 1.0
```

### Deprecation Policy
- Deprecated endpoints will return a `Deprecation` header
- Minimum 6 months notice before removing endpoints
- Migration guides provided for breaking changes

---

## Support

### API Status
Check API status at: https://status.devflow.pro

### Contact
- Email: api-support@devflow.pro
- Discord: discord.gg/devflow-api
- GitHub: github.com/devflow-pro/api-issues

---

*API Version: 1.0*
*Last Updated: November 2024*