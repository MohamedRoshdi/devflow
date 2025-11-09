# Dockerfile Detection Guide 🐳

**DevFlow Pro v2.1+**

How DevFlow Pro intelligently detects and uses your Docker configurations.

---

## 🎯 The Philosophy

**"Trust the developer, respect their setup"**

DevFlow Pro v2.1 introduced a fundamental change: instead of forcing our Docker configurations, we now **check if you have your own** and use it. We only generate a Dockerfile when your project doesn't have one.

This matches how professional deployment platforms (Heroku, Render, Vercel, Railway) work.

---

## 🔍 Detection Priority

DevFlow Pro checks in this order:

### 1. Check for `Dockerfile` (Root)
```bash
/var/www/project/Dockerfile
```

**If found:** Uses it with:
```bash
docker build -t project-name .
```

---

### 2. Check for `Dockerfile.production`
```bash
/var/www/project/Dockerfile.production
```

**If found:** Uses it with:
```bash
docker build -f Dockerfile.production -t project-name .
```

This supports the common pattern of separating dev and production Docker configs.

---

### 3. Generate Dockerfile (Fallback)
**If neither exists:** Generates appropriate Dockerfile based on your framework.

```bash
echo 'GENERATED_DOCKERFILE' > Dockerfile
docker build -t project-name .
```

---

## 📁 Common Project Structures

### Pattern 1: Single Dockerfile
```
project/
├── Dockerfile              ← Used for deployment
├── package.json
├── composer.json
└── ...
```

**DevFlow Pro:** Uses your `Dockerfile` ✅

---

### Pattern 2: Dev/Prod Separation (Recommended!)
```
project/
├── Dockerfile.production   ← Used for deployment ✅
├── docker-compose.yml      ← Local development
├── docker/
│   └── php/
│       └── Dockerfile      ← docker-compose only
└── ...
```

**DevFlow Pro:** Uses your `Dockerfile.production` ✅

**Why This Pattern:**
- `Dockerfile.production` - Optimized for production (no dev tools, cached layers, minimal size)
- `docker/php/Dockerfile` - For local dev with docker-compose (hot reload, debugging, etc.)

---

### Pattern 3: No Dockerfile
```
project/
├── composer.json           ← Laravel project
├── package.json
└── ...
(No Dockerfile)
```

**DevFlow Pro:** Generates appropriate Dockerfile based on framework ✅

---

## 🎨 What DevFlow Pro Generates

### For Laravel Projects

```dockerfile
FROM php:8.3-fpm-alpine

WORKDIR /var/www

# Install system dependencies and PHP extensions
RUN apk add --no-cache nginx supervisor curl git unzip \
        libpng-dev libjpeg-turbo-dev freetype-dev \
        libzip-dev \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql pcntl gd zip \
    && apk del .build-deps

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Laravel optimization
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

EXPOSE 80

CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
```

**Includes:**
- Common PHP extensions: pdo, pdo_mysql, pcntl, gd, zip
- Composer and dependencies
- Laravel optimizations
- Nginx + Supervisor for production
- Alpine Linux for small size

---

### For Node.js Projects

```dockerfile
FROM node:20-alpine

WORKDIR /app

COPY package*.json ./
RUN npm ci --only=production

COPY . .

EXPOSE 3000

CMD ["node", "server.js"]
```

---

### For Static Sites

```dockerfile
FROM nginx:alpine

COPY . /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
```

---

## 🤔 When to Use Which

### Use Your Own Dockerfile When:
- ✅ You have specific requirements
- ✅ You need custom PHP extensions
- ✅ You have complex build steps
- ✅ You want full control
- ✅ You have multi-stage builds
- ✅ You need specific system packages

### Use Dockerfile.production When:
- ✅ You separate dev and production configs
- ✅ You use docker-compose for local dev
- ✅ You want production optimizations
- ✅ You follow Laravel Sail pattern
- ✅ You want minimal production images

### Let DevFlow Generate When:
- ✅ Simple project with standard needs
- ✅ Don't want to manage Docker yourself
- ✅ Standard Laravel/Node.js/React project
- ✅ Getting started quickly
- ✅ Learning Docker

---

## 📖 Example: ATS Pro Project

### Your Structure:
```
ats-pro/
├── Dockerfile.production   ← 1569 bytes, production-ready
├── docker-compose.yml      ← For local dev
└── docker/
    ├── mysql/
    │   └── my.cnf
    ├── nginx/
    │   └── default.conf
    └── php/
        └── Dockerfile      ← For docker-compose
```

### What DevFlow Does:
1. **Clones** repository
2. **Checks** for `Dockerfile` → Not found
3. **Checks** for `Dockerfile.production` → **FOUND!** ✅
4. **Uses** your `Dockerfile.production`
5. **Builds** with: `docker build -f Dockerfile.production -t ats-pro .`

### Your Dockerfile.production:
- PHP 8.4-fpm-alpine
- All required extensions: pdo, mysql, pcntl, gd, zip, redis, bcmath, intl
- Composer with optimized autoload
- Node/npm for frontend build
- Laravel optimizations
- Proper permissions
- **Perfect for production!** ✅

### Result:
- ✅ Uses ALL your extensions
- ✅ Uses ALL your optimizations
- ✅ Builds exactly how you designed
- ✅ DevFlow just orchestrates, you control the details

---

## 🔧 Customizing Your Dockerfile

### Best Practices for Production Dockerfiles

#### 1. Multi-Stage Build (Recommended)
```dockerfile
# Stage 1: Build assets
FROM node:20-alpine AS builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: Production
FROM php:8.3-fpm-alpine
WORKDIR /var/www
# ... install PHP extensions
COPY --from=builder /app/public/build ./public/build
# ... rest of setup
```

**Benefits:**
- Smaller final image
- Build tools not in production
- Faster deployments after first build

---

#### 2. Layer Caching
```dockerfile
# Copy dependency files first
COPY composer.json composer.lock ./
RUN composer install --no-dev

# Then copy code (changes more often)
COPY . .
```

**Benefits:**
- Dependencies cached between builds
- Faster subsequent deployments
- Only rebuilds when dependencies change

---

#### 3. Production Optimizations
```dockerfile
# Laravel
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan optimize

# Remove dev files
RUN rm -rf tests/ .git/ .env.example

# Set proper permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 storage bootstrap/cache
```

---

#### 4. Health Checks
```dockerfile
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s \
  CMD curl -f http://localhost/api/health || exit 1
```

---

## 🎯 Troubleshooting

### "Build using wrong Dockerfile"

**Check:**
1. Is your Dockerfile in project root?
2. Is it named exactly `Dockerfile` or `Dockerfile.production`?
3. Check deployment logs - shows which file it's using

**Logs will show:**
```
Using existing Dockerfile: Dockerfile.production
```
or
```
No Dockerfile found, generating one...
```

---

### "Extensions missing in container"

**If using your Dockerfile:**
- Check your Dockerfile has `docker-php-ext-install` commands
- Ensure system libraries are installed first (e.g., libzip-dev before zip)

**If using generated Dockerfile:**
- Generated ones include: pdo, pdo_mysql, pcntl, gd, zip
- For other extensions, create your own Dockerfile

---

### "Want different Dockerfile for deployment"

**Solution:** Use `Dockerfile.production`

1. Create `Dockerfile.production` in project root
2. Optimize for production (no dev tools)
3. Keep your `docker-compose.yml` for local dev
4. DevFlow will automatically use `Dockerfile.production`

---

## 📊 Comparison: Before vs After v2.1

### Before v2.1 (Bad Approach):
```
DevFlow: "I'll create a Dockerfile for you"
User: "But I already have one!"
DevFlow: "Too bad, using mine"
User's Dockerfile: [OVERWRITTEN]
Result: ❌ User's extensions missing
        ❌ User's config ignored
        ❌ Build failures
```

### After v2.1 (Good Approach):
```
DevFlow: "Do you have a Dockerfile?"
User: "Yes, Dockerfile.production"
DevFlow: "Great! I'll use yours"
User's Dockerfile: [RESPECTED]
Result: ✅ User's extensions present
        ✅ User's config used
        ✅ Build succeeds
```

---

## 🌟 Real-World Examples

### Example 1: Laravel + Vue (ATS Pro)
```
Project has:
- Dockerfile.production (complete setup)
- docker-compose.yml (local dev)

DevFlow Pro:
✅ Detects Dockerfile.production
✅ Builds with user's configuration
✅ All extensions present (pcntl, gd, zip)
✅ npm build works perfectly
✅ Deployment succeeds!
```

---

### Example 2: Simple Laravel API
```
Project has:
- composer.json
- No Dockerfile

DevFlow Pro:
✅ Detects no Dockerfile
✅ Generates Laravel Dockerfile
✅ Installs common extensions
✅ Runs composer install
✅ Deployment succeeds!
```

---

### Example 3: React SPA
```
Project has:
- Dockerfile (nginx + built static files)
- package.json

DevFlow Pro:
✅ Detects Dockerfile
✅ Uses user's configuration
✅ Multi-stage build preserved
✅ Optimizations maintained
✅ Deployment succeeds!
```

---

## 💡 Pro Tips

### Tip 1: Always Use Dockerfile.production
Keep your dev and prod configs separate:
- `Dockerfile.production` - Lean and optimized
- `docker-compose.yml` - Feature-rich for dev

### Tip 2: Test Dockerfile Locally First
```bash
docker build -f Dockerfile.production -t test-build .
docker run -p 8000:80 test-build
```

If it works locally, it'll work on DevFlow Pro!

### Tip 3: Document Your Dockerfile
```dockerfile
# ATS Pro Production Dockerfile
# Includes: PHP 8.4, Laravel Horizon, PHPOffice
# Build time: ~15 minutes (includes npm build)
# Maintainer: Your Name

FROM php:8.4-fpm-alpine
# ...
```

### Tip 4: Version Your Dockerfile
Commit changes with clear messages:
```bash
git commit -m "Dockerfile: Add redis extension for caching"
```

---

## 📈 Statistics

### Build Time Comparison:

**Simple Generated Dockerfile:**
- Composer install: 2-3 min
- Laravel optimize: 30 sec
- **Total:** ~5 minutes

**Custom Dockerfile with npm:**
- System packages: 1-2 min
- PHP extensions: 2-3 min
- Composer install: 2-3 min
- npm install: 3-5 min
- npm build: 2-4 min
- **Total:** 12-18 minutes

**Why Custom is Slower:**
- More complete (includes frontend build)
- More extensions (better functionality)
- Production optimizations
- Worth the wait! Quality > Speed

---

## 🆘 Getting Help

### Questions?
- Read the [main README](README.md)
- Check [troubleshooting guide](TROUBLESHOOTING.md)
- Ask in [GitHub Discussions](https://github.com/yourusername/devflow-pro/discussions)

### Issues?
- Open [GitHub Issue](https://github.com/yourusername/devflow-pro/issues)
- Include your Dockerfile
- Include deployment logs
- Describe expected vs actual behavior

---

<div align="center">

**Smart Dockerfile Detection** - Your config, your way!

[Back to README](README.md) • [Real-Time Progress](REAL_TIME_PROGRESS.md) • [Git Features](GIT_FEATURES.md)

</div>

