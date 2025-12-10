---
title: CI/CD Pipelines
description: Build and manage deployment pipelines
---

# CI/CD Pipelines

Create automated deployment pipelines for your projects.

## Pipeline Builder

Visual pipeline builder for creating workflows.

**Features:**

- Drag-and-drop interface
- Multiple stages
- Parallel execution
- Conditional logic
- Manual approvals

**Stage types:**

- Build
- Test
- Deploy
- Notification
- Custom script

## Pipeline Stages

Configure individual pipeline stages.

**Build stage:**

- Install dependencies
- Compile code
- Build assets
- Create artifacts

**Test stage:**

- Run unit tests
- Run integration tests
- Code quality checks
- Security scans

**Deploy stage:**

- Deploy to server
- Run migrations
- Clear caches
- Health checks

## Pipeline Triggers

Configure when pipelines run.

**Trigger types:**

- Git push
- Git tag
- Pull request
- Manual trigger
- Scheduled (cron)
- API trigger

**Trigger conditions:**

- Specific branches
- File path filters
- Commit message patterns

## Environment Variables

Configure pipeline environment variables.

**Variable types:**

- Plain text
- Secret (encrypted)
- File
- Dynamic (computed)

**Variable scopes:**

- Global
- Per pipeline
- Per stage
- Per environment

## Pipeline Artifacts

Store build artifacts.

**Artifact types:**

- Compiled binaries
- Built assets
- Test reports
- Code coverage reports
- Docker images

**Artifact storage:**

- Local storage
- S3 storage
- Artifact registry
- Custom storage

## Pipeline Notifications

Get notified about pipeline status.

**Notification triggers:**

- Pipeline started
- Pipeline succeeded
- Pipeline failed
- Stage failed
- Manual approval needed

**Notification channels:**

- Email
- Slack
- Discord
- Microsoft Teams
- Webhook

## Pipeline Matrix

Run pipeline with multiple configurations.

**Matrix dimensions:**

- PHP versions (8.1, 8.2, 8.3)
- OS (Ubuntu, Debian, Alpine)
- Database (MySQL, PostgreSQL)
- Node versions

**Example:**

```yaml
matrix:
  php: [8.1, 8.2, 8.3]
  database: [mysql, postgresql]
```

## Pipeline Caching

Cache dependencies for faster builds.

**Cacheable items:**

- Composer dependencies
- NPM packages
- Docker layers
- Build artifacts

**Cache strategies:**

- Per branch
- Per project
- Global cache

## Pipeline Secrets

Securely store pipeline secrets.

**Secret types:**

- API keys
- Deployment keys
- Service credentials
- Signing certificates

**Security:**

- Encrypted storage
- Masked in logs
- Access control
- Audit logging

## Pipeline History

View pipeline execution history.

**Information shown:**

- Execution time
- Duration
- Trigger source
- Commit hash
- Status
- Logs

**Features:**

- Filter by status
- Search by commit
- Download logs
- Retry failed pipeline
