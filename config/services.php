<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as CI/CD providers, cloud services, and external APIs.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | GitHub Configuration
    |--------------------------------------------------------------------------
    */

    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'webhook_secret' => env('GITHUB_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | GitLab Configuration
    |--------------------------------------------------------------------------
    */

    'gitlab' => [
        'url' => env('GITLAB_URL', 'https://gitlab.com'),
        'token' => env('GITLAB_TOKEN'),
        'client_id' => env('GITLAB_CLIENT_ID'),
        'client_secret' => env('GITLAB_CLIENT_SECRET'),
        'webhook_secret' => env('GITLAB_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bitbucket Configuration
    |--------------------------------------------------------------------------
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
    | Jenkins Configuration
    |--------------------------------------------------------------------------
    */

    'jenkins' => [
        'url' => env('JENKINS_URL'),
        'user' => env('JENKINS_USER'),
        'token' => env('JENKINS_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Docker Registry Configuration
    |--------------------------------------------------------------------------
    */

    'docker_registry' => [
        'url' => env('DOCKER_REGISTRY_URL'),
        'username' => env('DOCKER_REGISTRY_USERNAME'),
        'password' => env('DOCKER_REGISTRY_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack Configuration
    |--------------------------------------------------------------------------
    */

    'slack' => [
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
        'bot_token' => env('SLACK_BOT_TOKEN'),
        'channel' => env('SLACK_CHANNEL', '#deployments'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Discord Configuration
    |--------------------------------------------------------------------------
    */

    'discord' => [
        'webhook_url' => env('DISCORD_WEBHOOK_URL'),
        'bot_token' => env('DISCORD_BOT_TOKEN'),
    ],

];
