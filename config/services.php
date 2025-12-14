<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as CI/CD providers, cloud services, and external APIs used by DevFlow Pro.
    |
    | Security Note: Never commit actual credentials to version control.
    | All values should be configured via environment variables.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Git Provider Integrations
    |--------------------------------------------------------------------------
    |
    | Configure Git providers for automated deployments, webhook handling,
    | and repository management. DevFlow Pro supports GitHub, GitLab, and
    | Bitbucket out of the box.
    |
    */

    /*
    | GitHub Configuration
    |
    | To set up GitHub integration:
    | 1. Go to Settings > Developer settings > GitHub Apps
    | 2. Create a new GitHub App with repository permissions
    | 3. Generate a client secret and personal access token
    | 4. Configure webhook URL: https://your-domain.com/webhooks/github
    | 5. Set webhook secret for signature verification
    |
    | Required permissions:
    | - Repository contents: Read & Write
    | - Repository webhooks: Read & Write
    | - Repository deployments: Read & Write
    */

    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'webhook_secret' => env('GITHUB_WEBHOOK_SECRET'),
        'api_url' => env('GITHUB_API_URL', 'https://api.github.com'),
    ],

    /*
    | GitLab Configuration
    |
    | To set up GitLab integration:
    | 1. Go to User Settings > Applications (or Admin Area for self-hosted)
    | 2. Create a new application with api and read_repository scopes
    | 3. Generate a personal access token with api scope
    | 4. Configure webhook URL: https://your-domain.com/webhooks/gitlab
    | 5. Set webhook secret for signature verification
    |
    | For self-hosted GitLab, update GITLAB_URL to your instance URL.
    |
    | Required scopes:
    | - api: Full API access
    | - read_repository: Read repository data
    | - write_repository: Push to repository
    */

    'gitlab' => [
        'url' => env('GITLAB_URL', 'https://gitlab.com'),
        'token' => env('GITLAB_TOKEN'),
        'client_id' => env('GITLAB_CLIENT_ID'),
        'client_secret' => env('GITLAB_CLIENT_SECRET'),
        'webhook_secret' => env('GITLAB_WEBHOOK_SECRET'),
    ],

    /*
    | Bitbucket Configuration
    |
    | To set up Bitbucket integration:
    | 1. Go to Settings > OAuth > Add consumer (for OAuth)
    | 2. Create an App Password for API access
    | 3. Configure webhook URL: https://your-domain.com/webhooks/bitbucket
    | 4. Set webhook secret for signature verification
    |
    | Required permissions:
    | - Repositories: Read & Write
    | - Webhooks: Read & Write
    | - Pull requests: Read & Write
    */

    'bitbucket' => [
        'url' => env('BITBUCKET_URL', 'https://api.bitbucket.org/2.0'),
        'username' => env('BITBUCKET_USERNAME'),
        'app_password' => env('BITBUCKET_APP_PASSWORD'),
        'client_id' => env('BITBUCKET_CLIENT_ID'),
        'client_secret' => env('BITBUCKET_CLIENT_SECRET'),
        'webhook_secret' => env('BITBUCKET_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | CI/CD Platform Integrations
    |--------------------------------------------------------------------------
    |
    | Configure external CI/CD platforms for advanced deployment workflows.
    |
    */

    /*
    | Jenkins Configuration
    |
    | To set up Jenkins integration:
    | 1. Install Jenkins and ensure it's accessible
    | 2. Create a Jenkins user with appropriate permissions
    | 3. Generate an API token from user settings
    | 4. Configure Jenkins jobs for your projects
    |
    | Usage: Trigger Jenkins builds from DevFlow Pro deployments
    */

    'jenkins' => [
        'url' => env('JENKINS_URL'),
        'user' => env('JENKINS_USER'),
        'token' => env('JENKINS_TOKEN'),
        'verify_ssl' => env('JENKINS_VERIFY_SSL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Container Registry Integrations
    |--------------------------------------------------------------------------
    |
    | Configure private Docker registries for custom container images.
    |
    */

    /*
    | Docker Registry Configuration
    |
    | To set up Docker Registry integration:
    | 1. Set up a private Docker registry (e.g., Harbor, GitLab Registry)
    | 2. Create registry credentials
    | 3. Configure registry URL and authentication
    |
    | Supported registries:
    | - Docker Hub: https://registry.hub.docker.com
    | - GitLab Registry: https://registry.gitlab.com
    | - GitHub Container Registry: https://ghcr.io
    | - Harbor: https://your-harbor-instance.com
    | - Self-hosted registry: https://your-registry.com
    */

    'docker_registry' => [
        'url' => env('DOCKER_REGISTRY_URL'),
        'username' => env('DOCKER_REGISTRY_USERNAME'),
        'password' => env('DOCKER_REGISTRY_PASSWORD'),
        'verify_ssl' => env('DOCKER_REGISTRY_VERIFY_SSL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Service Integrations
    |--------------------------------------------------------------------------
    |
    | Configure notification channels for deployment alerts and system events.
    |
    */

    /*
    | Slack Configuration
    |
    | To set up Slack integration:
    | 1. Go to https://api.slack.com/messaging/webhooks
    | 2. Create a new webhook for your workspace
    | 3. Choose the default channel for notifications
    | 4. Copy the webhook URL
    |
    | Optional: Create a Slack Bot for interactive features
    | 1. Go to https://api.slack.com/apps
    | 2. Create a new app
    | 3. Add Bot Token Scopes: chat:write, channels:read
    | 4. Install app to workspace and copy Bot Token
    |
    | Notification types:
    | - Deployment success/failure
    | - Health check alerts
    | - SSL certificate expiry warnings
    | - System resource alerts
    */

    'slack' => [
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
        'bot_token' => env('SLACK_BOT_TOKEN'),
        'channel' => env('SLACK_CHANNEL', '#deployments'),
        'username' => env('SLACK_USERNAME', 'DevFlow Pro'),
        'icon' => env('SLACK_ICON', ':rocket:'),
    ],

    /*
    | Discord Configuration
    |
    | To set up Discord integration:
    | 1. Open your Discord server
    | 2. Go to Server Settings > Integrations
    | 3. Create a new webhook
    | 4. Choose the channel for notifications
    | 5. Copy the webhook URL
    |
    | Optional: Create a Discord Bot for interactive features
    | 1. Go to https://discord.com/developers/applications
    | 2. Create a new application
    | 3. Add a bot and copy the bot token
    | 4. Invite bot to your server
    |
    | Notification types:
    | - Deployment notifications
    | - Error alerts
    | - System health updates
    */

    'discord' => [
        'webhook_url' => env('DISCORD_WEBHOOK_URL'),
        'bot_token' => env('DISCORD_BOT_TOKEN'),
        'username' => env('DISCORD_USERNAME', 'DevFlow Pro'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Service Providers
    |--------------------------------------------------------------------------
    |
    | Configure transactional email services for system notifications.
    |
    */

    /*
    | Mailgun Configuration (Optional)
    |
    | To set up Mailgun:
    | 1. Create account at https://www.mailgun.com
    | 2. Add and verify your domain
    | 3. Copy API key from settings
    | 4. Configure in .env file
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    /*
    | Postmark Configuration (Optional)
    |
    | To set up Postmark:
    | 1. Create account at https://postmarkapp.com
    | 2. Create a server and get API token
    | 3. Configure in .env file
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    /*
    | AWS SES Configuration (Optional)
    |
    | To set up AWS SES:
    | 1. Configure AWS credentials
    | 2. Verify email domains in SES
    | 3. Configure in .env file
    */

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Error Tracking
    |--------------------------------------------------------------------------
    |
    | Configure monitoring and error tracking services.
    |
    */

    /*
    | Sentry Configuration (Optional)
    |
    | To set up Sentry:
    | 1. Create account at https://sentry.io
    | 2. Create a new Laravel project
    | 3. Copy the DSN from project settings
    | 4. Configure in .env file
    |
    | Features:
    | - Real-time error tracking
    | - Performance monitoring
    | - Release tracking
    | - Issue alerts
    */

    'sentry' => [
        'dsn' => env('SENTRY_LARAVEL_DSN'),
        'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 1.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloud Storage Providers
    |--------------------------------------------------------------------------
    |
    | Configure cloud storage for backups and file storage.
    |
    */

    /*
    | Amazon S3 Configuration (Optional)
    |
    | Used for backup storage and file uploads
    */

    's3' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Infrastructure Management
    |--------------------------------------------------------------------------
    |
    | Configure infrastructure management tools.
    |
    */

    /*
    | Nginx Proxy Manager Configuration
    |
    | To set up Nginx Proxy Manager:
    | 1. Install Nginx Proxy Manager via Docker
    | 2. Access admin panel (default: http://localhost:81)
    | 3. Create admin user
    | 4. Configure API access
    |
    | Features:
    | - Automatic SSL certificate management
    | - Domain and subdomain creation
    | - Reverse proxy configuration
    | - SSL certificate renewal
    */

    'nginx_proxy_manager' => [
        'url' => env('NGINX_PROXY_MANAGER_URL', 'http://localhost:81'),
        'email' => env('NGINX_PROXY_MANAGER_EMAIL'),
        'password' => env('NGINX_PROXY_MANAGER_PASSWORD'),
        'verify_ssl' => env('NGINX_PROXY_MANAGER_VERIFY_SSL', false),
    ],

];
