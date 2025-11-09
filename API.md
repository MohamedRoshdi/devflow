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

## Best Practices

1. **Secure your tokens** - Store API tokens securely
2. **Use HTTPS** - Always use HTTPS in production
3. **Handle rate limits** - Implement exponential backoff
4. **Validate webhooks** - Verify webhook signatures
5. **Log errors** - Keep track of API errors
6. **Monitor usage** - Track API usage patterns

---

**Need more endpoints? Check the source code in `routes/api.php` or request new features!**

