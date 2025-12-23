<?php

declare(strict_types=1);

namespace App\Services\Docker;

use App\Models\Project;

/**
 * Service for generating Dockerfiles based on project framework.
 *
 * Generates appropriate Dockerfiles for different frameworks:
 * - Laravel/PHP projects with nginx + PHP-FPM
 * - Node.js applications
 * - React/Vue static site builds
 * - Generic containers
 */
class DockerfileGenerator
{
    /**
     * Generate Dockerfile content based on project framework
     */
    public function generate(Project $project): string
    {
        return match ($project->framework) {
            'Laravel', 'laravel' => $this->getLaravelDockerfile($project),
            'Node.js', 'nodejs' => $this->getNodeDockerfile($project),
            'React', 'react' => $this->getReactDockerfile($project),
            'Vue', 'vue' => $this->getVueDockerfile($project),
            default => $this->getGenericDockerfile($project),
        };
    }

    /**
     * Generate Laravel/PHP Dockerfile with nginx and PHP-FPM
     */
    public function getLaravelDockerfile(Project $project): string
    {
        $phpVersion = $project->php_version ?? '8.2';

        return <<<DOCKERFILE
FROM php:{$phpVersion}-fpm-alpine

WORKDIR /var/www

# Install system dependencies and PHP extensions
RUN apk add --no-cache nginx supervisor curl git unzip \\
        libpng-dev libjpeg-turbo-dev freetype-dev \\
        libzip-dev \\
    && apk add --no-cache --virtual .build-deps \\
        \$PHPIZE_DEPS \\
    && docker-php-ext-configure gd --with-freetype --with-jpeg \\
    && docker-php-ext-install -j\$(nproc) pdo pdo_mysql pcntl gd zip \\
    && apk del .build-deps

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Laravel optimization
RUN php artisan config:cache && \\
    php artisan route:cache && \\
    php artisan view:cache

EXPOSE 80

CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
DOCKERFILE;
    }

    /**
     * Generate Node.js Dockerfile
     */
    public function getNodeDockerfile(Project $project): string
    {
        $nodeVersion = $project->node_version ?? '20';

        return <<<DOCKERFILE
FROM node:{$nodeVersion}-alpine

WORKDIR /app

COPY package*.json ./
RUN npm ci --only=production

COPY . .

EXPOSE 3000

CMD ["node", "server.js"]
DOCKERFILE;
    }

    /**
     * Generate React Dockerfile with multi-stage build
     */
    public function getReactDockerfile(Project $project): string
    {
        return <<<'DOCKERFILE'
FROM node:20-alpine as build

WORKDIR /app
COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=build /app/build /usr/share/nginx/html
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
DOCKERFILE;
    }

    /**
     * Generate Vue Dockerfile with multi-stage build
     */
    public function getVueDockerfile(Project $project): string
    {
        return <<<'DOCKERFILE'
FROM node:20-alpine as build

WORKDIR /app
COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
DOCKERFILE;
    }

    /**
     * Generate generic Dockerfile as fallback
     */
    public function getGenericDockerfile(Project $project): string
    {
        return <<<'DOCKERFILE'
FROM alpine:latest

WORKDIR /app
COPY . .

EXPOSE 80

CMD ["sh", "-c", "while true; do sleep 3600; done"]
DOCKERFILE;
    }
}
