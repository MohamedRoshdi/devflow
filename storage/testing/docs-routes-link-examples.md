# Documentation Routes - Link Examples

This file provides ready-to-use examples for linking to documentation from anywhere in the application.

## Basic Link Syntax

```blade
<!-- Link to category index -->
<a href="{{ route('docs.show', ['category' => 'deployments']) }}">
    View deployment docs
</a>

<!-- Link to specific section -->
<a href="{{ route('docs.show', ['category' => 'deployments']) }}#deploy-button">
    Learn about deploy button
</a>
```

## All Available Documentation Links

### Deployments
- `{{ route('docs.show', ['category' => 'deployments']) }}#deploy-button`
- `{{ route('docs.show', ['category' => 'deployments']) }}#auto-deploy`
- `{{ route('docs.show', ['category' => 'deployments']) }}#rollback`

### Domains
- `{{ route('docs.show', ['category' => 'domains']) }}#add-domain`
- `{{ route('docs.show', ['category' => 'domains']) }}#primary-domain`
- `{{ route('docs.show', ['category' => 'domains']) }}#ssl-management`

### SSL
- `{{ route('docs.show', ['category' => 'ssl']) }}#automatic-ssl`
- `{{ route('docs.show', ['category' => 'ssl']) }}#ssl-renewal`

### Servers
- `{{ route('docs.show', ['category' => 'servers']) }}#add-server`
- `{{ route('docs.show', ['category' => 'servers']) }}#ssh-key-management`

### Monitoring
- `{{ route('docs.show', ['category' => 'monitoring']) }}#health-checks`
- `{{ route('docs.show', ['category' => 'monitoring']) }}#log-aggregation`

### Security
- `{{ route('docs.show', ['category' => 'security']) }}#two-factor-authentication-2fa`
- `{{ route('docs.show', ['category' => 'security']) }}#api-tokens`

### Docker
- `{{ route('docs.show', ['category' => 'docker']) }}#container-management`
- `{{ route('docs.show', ['category' => 'docker']) }}#docker-compose`

### Kubernetes
- `{{ route('docs.show', ['category' => 'kubernetes']) }}#cluster-connection`
- `{{ route('docs.show', ['category' => 'kubernetes']) }}#pod-management`

### Pipelines
- `{{ route('docs.show', ['category' => 'pipelines']) }}#pipeline-builder`
- `{{ route('docs.show', ['category' => 'pipelines']) }}#pipeline-stages`

### Teams
- `{{ route('docs.show', ['category' => 'teams']) }}#team-management`
- `{{ route('docs.show', ['category' => 'teams']) }}#role-permissions`

### Database
- `{{ route('docs.show', ['category' => 'database']) }}#database-backups`
- `{{ route('docs.show', ['category' => 'database']) }}#database-migrations`

### Backups
- `{{ route('docs.show', ['category' => 'backups']) }}#automatic-backups`
- `{{ route('docs.show', ['category' => 'backups']) }}#one-click-restore`

### Multi-Tenancy
- `{{ route('docs.show', ['category' => 'multi-tenancy']) }}#tenant-management`
- `{{ route('docs.show', ['category' => 'multi-tenancy']) }}#tenant-deployment`
