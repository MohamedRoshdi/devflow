# DevFlow Pro - Docker Quick Start Guide

## Automated Setup

```bash
# Make the setup script executable and run it
chmod +x docker-setup.sh
./docker-setup.sh
```

The script will guide you through:
- Development or Production environment selection
- .env file creation
- Directory structure setup
- Docker container building
- Database initialization
- Application optimization

## Manual Setup

### Development (5 minutes)

```bash
# 1. Copy configuration files
cp .env.example .env
cp docker-compose.override.yml.example docker-compose.override.yml

# 2. Build and start
docker-compose up -d

# 3. Initialize application
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app php artisan storage:link

# 4. Access application
# http://localhost:8080
```

### Production (10 minutes)

```bash
# 1. Configure environment
cp .env.example .env
nano .env  # Update with production values

# 2. Create required directories
sudo mkdir -p /opt/devflow/{projects,backups,logs,ssl}

# 3. Build and deploy
docker-compose build --no-cache
docker-compose up -d

# 4. Initialize application
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan optimize

# 5. Configure SSL (recommended)
# Place SSL certificates in /opt/devflow/ssl/
# Update docker/nginx/conf.d/default.conf
# Restart nginx: docker-compose restart nginx
```

## Using Makefile (Easiest)

```bash
# Development setup
make -f Makefile.docker install

# Start development environment
make -f Makefile.docker dev

# View help
make -f Makefile.docker help
```

## Common Commands

```bash
# Service management
docker-compose up -d          # Start all services
docker-compose down           # Stop all services
docker-compose restart app    # Restart app
docker-compose ps             # Check status
docker-compose logs -f app    # View logs

# Application commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan cache:clear
docker-compose exec app composer install
docker-compose exec app bash  # Shell access

# Database
docker-compose exec postgres psql -U devflow devflow_pro

# Redis
docker-compose exec redis redis-cli
```

## Access Points (Development)

- **Application**: http://localhost:8080
- **Mailhog** (Email testing): http://localhost:8025
- **pgAdmin** (Database): http://localhost:5050
  - Email: admin@devflow.local
  - Password: admin
- **Redis Commander**: http://localhost:8081

## File Structure Created

```
DEVFLOW_PRO/
├── docker/
│   ├── nginx/
│   │   ├── conf.d/default.conf          # Main site config
│   │   ├── snippets/laravel.conf        # Laravel rules
│   │   └── snippets/ssl-params.conf     # SSL settings
│   ├── php/
│   │   ├── php.ini                      # Production PHP
│   │   ├── php-dev.ini                  # Development PHP
│   │   ├── opcache.ini                  # Production OPcache
│   │   └── php-fpm.conf                 # PHP-FPM pool
│   ├── postgres/
│   │   └── postgresql.conf              # PostgreSQL tuning
│   ├── entrypoint.sh                    # App initialization
│   └── scheduler-entrypoint.sh          # Cron scheduler
├── docker-compose.yml                   # Main configuration
├── docker-compose.override.yml.example  # Dev overrides
├── Dockerfile                           # Multi-stage build
├── .dockerignore                        # Build exclusions
├── docker-setup.sh                      # Automated setup
├── Makefile.docker                      # Convenience commands
└── DOCKER_README.md                     # Full documentation
```

## Troubleshooting

### Port conflicts
```bash
# Change ports in docker-compose.override.yml:
services:
  nginx:
    ports:
      - "8080:80"  # Changed from 80
```

### Permission errors
```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Container won't start
```bash
docker-compose logs -f app  # View error logs
docker-compose down -v      # Remove everything
docker-compose up -d        # Start fresh
```

### Database connection failed
```bash
# Verify PostgreSQL is ready
docker-compose exec postgres pg_isready -U devflow
```

## Next Steps

1. **Read full documentation**: See [DOCKER_README.md](DOCKER_README.md)
2. **Configure your environment**: Update `.env` file
3. **Set up SSL** (production): Place certificates in `/opt/devflow/ssl/`
4. **Configure backups**: Set up automated database backups
5. **Monitor logs**: `docker-compose logs -f`

## Support

- Full documentation: `DOCKER_README.md`
- Makefile help: `make -f Makefile.docker help`
- Laravel docs: https://laravel.com/docs
- Docker docs: https://docs.docker.com
