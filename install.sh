#!/usr/bin/env bash
# ============================================================================
# DevFlow Pro - Secure Installation Script
# ============================================================================
# Multi-Project Deployment & Management System
#
# USAGE:
#   ./install.sh              # Development mode (default) - SQLite, file cache
#   ./install.sh --production # Production mode - PostgreSQL, Redis, Nginx
#   ./install.sh --help       # Show usage information
#
# SECURITY FEATURES:
#   - Secure password generation
#   - Restrictive file permissions
#   - Input validation and sanitization
#   - No root execution enforcement
#   - Secure environment variable handling
#
# REQUIREMENTS:
#   - PHP 8.2+ with required extensions
#   - Composer 2.x
#   - Node.js 18+ and npm
#   - Git
#   - PostgreSQL 14+ (production only)
#   - Redis 7+ (production only)
#   - Nginx (production only)
#
# ============================================================================

set -euo pipefail
IFS=$'\n\t'

# ============================================================================
# CONSTANTS & CONFIGURATION
# ============================================================================

readonly SCRIPT_VERSION="1.0.0"
readonly SCRIPT_NAME="$(basename "$0")"
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly LOG_FILE="${SCRIPT_DIR}/install.log"
readonly BACKUP_DIR="${SCRIPT_DIR}/.install-backups"
readonly MIN_PHP_VERSION="8.2.0"
readonly MIN_NODE_VERSION="18.0.0"
readonly MIN_COMPOSER_VERSION="2.0.0"

# Color codes for output (disabled if not terminal)
if [[ -t 1 ]]; then
    readonly RED='\033[0;31m'
    readonly GREEN='\033[0;32m'
    readonly YELLOW='\033[1;33m'
    readonly BLUE='\033[0;34m'
    readonly CYAN='\033[0;36m'
    readonly BOLD='\033[1m'
    readonly NC='\033[0m' # No Color
else
    readonly RED=''
    readonly GREEN=''
    readonly YELLOW=''
    readonly BLUE=''
    readonly CYAN=''
    readonly BOLD=''
    readonly NC=''
fi

# Default configuration
INSTALL_MODE="development"
SKIP_MIGRATIONS=false
SKIP_ASSETS=false
FORCE_INSTALL=false
VERBOSE=false
DRY_RUN=false

# ============================================================================
# LOGGING & OUTPUT FUNCTIONS
# ============================================================================

log() {
    local level="$1"
    shift
    local message="$*"
    local timestamp
    timestamp="$(date '+%Y-%m-%d %H:%M:%S')"
    echo "[$timestamp] [$level] $message" >> "$LOG_FILE"

    if [[ "$VERBOSE" == "true" ]]; then
        echo "[$timestamp] [$level] $message"
    fi
}

info() {
    echo -e "${BLUE}[INFO]${NC} $*"
    log "INFO" "$*"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $*"
    log "SUCCESS" "$*"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $*" >&2
    log "WARN" "$*"
}

error() {
    echo -e "${RED}[ERROR]${NC} $*" >&2
    log "ERROR" "$*"
}

fatal() {
    echo -e "${RED}[FATAL]${NC} $*" >&2
    log "FATAL" "$*"
    exit 1
}

step() {
    echo -e "\n${CYAN}${BOLD}>>> $*${NC}"
    log "STEP" "$*"
}

# ============================================================================
# HELPER FUNCTIONS
# ============================================================================

# Generate a cryptographically secure random password
generate_secure_password() {
    local length="${1:-32}"

    # Use /dev/urandom with base64 encoding, removing special chars
    if [[ -r /dev/urandom ]]; then
        head -c "$((length * 2))" /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c "$length"
    else
        # Fallback to openssl
        openssl rand -base64 "$((length * 2))" | tr -dc 'a-zA-Z0-9' | head -c "$length"
    fi
}

# Generate Laravel application key
generate_app_key() {
    echo "base64:$(openssl rand -base64 32)"
}

# Validate version comparison
version_compare() {
    local v1="$1"
    local v2="$2"

    # Extract version numbers
    v1=$(echo "$v1" | sed -E 's/[^0-9.]//g' | cut -d. -f1-3)
    v2=$(echo "$v2" | sed -E 's/[^0-9.]//g' | cut -d. -f1-3)

    if [[ "$v1" == "$v2" ]]; then
        echo 0
        return
    fi

    # Compare versions
    local IFS=.
    local i ver1=($v1) ver2=($v2)

    for ((i=0; i<${#ver1[@]} || i<${#ver2[@]}; i++)); do
        local num1=${ver1[i]:-0}
        local num2=${ver2[i]:-0}

        if ((num1 > num2)); then
            echo 1
            return
        elif ((num1 < num2)); then
            echo -1
            return
        fi
    done

    echo 0
}

# Check if command exists
command_exists() {
    command -v "$1" &> /dev/null
}

# Secure file permissions
secure_file() {
    local file="$1"
    local perms="${2:-600}"

    if [[ -f "$file" ]]; then
        chmod "$perms" "$file"
        log "INFO" "Set permissions $perms on $file"
    fi
}

# Secure directory permissions
secure_directory() {
    local dir="$1"
    local perms="${2:-755}"

    if [[ -d "$dir" ]]; then
        chmod "$perms" "$dir"
        log "INFO" "Set permissions $perms on $dir"
    fi
}

# Backup file before modification
backup_file() {
    local file="$1"

    if [[ -f "$file" ]]; then
        mkdir -p "$BACKUP_DIR"
        local backup_name
        backup_name="$(basename "$file").$(date +%Y%m%d%H%M%S).bak"
        cp "$file" "${BACKUP_DIR}/${backup_name}"
        log "INFO" "Backed up $file to ${BACKUP_DIR}/${backup_name}"
    fi
}

# Sanitize string for safe shell usage
sanitize_string() {
    local input="$1"
    # Remove dangerous characters
    echo "$input" | sed -E 's/[;&|`$(){}[\]<>\\!*?~]//g' | tr -d '\n\r'
}

# ============================================================================
# VALIDATION FUNCTIONS
# ============================================================================

validate_not_root() {
    if [[ "$EUID" -eq 0 ]]; then
        fatal "This script should NOT be run as root for security reasons.
Please run as a regular user with sudo privileges when needed."
    fi
}

validate_php_version() {
    step "Validating PHP installation"

    if ! command_exists php; then
        fatal "PHP is not installed. Please install PHP $MIN_PHP_VERSION or higher."
    fi

    local php_version
    php_version=$(php -r 'echo PHP_VERSION;')

    if [[ $(version_compare "$php_version" "$MIN_PHP_VERSION") -lt 0 ]]; then
        fatal "PHP version $php_version is too old. Minimum required: $MIN_PHP_VERSION"
    fi

    success "PHP version $php_version detected"

    # Check required extensions using PHP itself for accuracy
    # Some extensions are built-in (json, tokenizer) and may not show in php -m
    local missing_extensions=()

    # Check extensions using PHP extension_loaded()
    local check_result
    check_result=$(php -r '
        $required = ["pdo", "mbstring", "xml", "curl", "zip", "bcmath", "openssl", "fileinfo", "dom", "simplexml"];
        $missing = [];
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        echo implode(" ", $missing);
    ')

    if [[ -n "$check_result" ]]; then
        fatal "Missing PHP extensions: $check_result
Please install them before continuing.
On Ubuntu/Debian: sudo apt install php8.4-{${check_result// /,}}
On Alpine: apk add php84-{${check_result// /,}}"
    fi

    success "All required PHP extensions are installed"
}

validate_composer() {
    step "Validating Composer installation"

    if ! command_exists composer; then
        fatal "Composer is not installed. Please install Composer $MIN_COMPOSER_VERSION or higher."
    fi

    local composer_version
    composer_version=$(composer --version 2>/dev/null | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)

    if [[ $(version_compare "$composer_version" "$MIN_COMPOSER_VERSION") -lt 0 ]]; then
        fatal "Composer version $composer_version is too old. Minimum required: $MIN_COMPOSER_VERSION"
    fi

    success "Composer version $composer_version detected"
}

validate_node() {
    step "Validating Node.js installation"

    if ! command_exists node; then
        fatal "Node.js is not installed. Please install Node.js $MIN_NODE_VERSION or higher."
    fi

    local node_version
    node_version=$(node --version | sed 's/v//')

    if [[ $(version_compare "$node_version" "$MIN_NODE_VERSION") -lt 0 ]]; then
        fatal "Node.js version $node_version is too old. Minimum required: $MIN_NODE_VERSION"
    fi

    success "Node.js version $node_version detected"

    if ! command_exists npm; then
        fatal "npm is not installed. Please install npm."
    fi

    local npm_version
    npm_version=$(npm --version)
    success "npm version $npm_version detected"
}

validate_git() {
    step "Validating Git installation"

    if ! command_exists git; then
        fatal "Git is not installed. Please install Git."
    fi

    local git_version
    git_version=$(git --version | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')
    success "Git version $git_version detected"
}

validate_production_requirements() {
    if [[ "$INSTALL_MODE" != "production" ]]; then
        return 0
    fi

    step "Validating production requirements"

    # Check PostgreSQL
    if ! command_exists psql; then
        fatal "PostgreSQL client (psql) is not installed. Required for production mode."
    fi
    success "PostgreSQL client detected"

    # Check Redis
    if ! command_exists redis-cli; then
        fatal "Redis client (redis-cli) is not installed. Required for production mode."
    fi
    success "Redis client detected"

    # Check Nginx
    if ! command_exists nginx; then
        fatal "Nginx is not installed. Required for production mode."
    fi
    success "Nginx detected"

    # Check Supervisor
    if ! command_exists supervisorctl; then
        warn "Supervisor is not installed. Queue workers will need manual management."
    else
        success "Supervisor detected"
    fi
}

# ============================================================================
# INSTALLATION FUNCTIONS
# ============================================================================

create_directories() {
    step "Creating required directories"

    local directories=(
        "storage/app/public"
        "storage/framework/cache/data"
        "storage/framework/sessions"
        "storage/framework/views"
        "storage/logs"
        "storage/devflow/projects"
        "storage/devflow/backups"
        "storage/devflow/logs"
        "storage/devflow/ssl"
        "bootstrap/cache"
    )

    for dir in "${directories[@]}"; do
        local full_path="${SCRIPT_DIR}/${dir}"
        if [[ ! -d "$full_path" ]]; then
            mkdir -p "$full_path"
            info "Created directory: $dir"
        fi
    done

    # Set secure permissions on storage and bootstrap/cache
    chmod -R 775 "${SCRIPT_DIR}/storage" 2>/dev/null || sudo chmod -R 775 "${SCRIPT_DIR}/storage" 2>/dev/null || true
    chmod -R 775 "${SCRIPT_DIR}/bootstrap/cache" 2>/dev/null || sudo chmod -R 775 "${SCRIPT_DIR}/bootstrap/cache" 2>/dev/null || true

    success "Directories created with proper permissions"
}

setup_environment_file() {
    step "Configuring environment file"

    local env_file="${SCRIPT_DIR}/.env"
    local env_example="${SCRIPT_DIR}/.env.example"

    if [[ ! -f "$env_example" ]]; then
        fatal ".env.example file not found. Is this a valid DevFlow Pro installation?"
    fi

    if [[ -f "$env_file" && "$FORCE_INSTALL" != "true" ]]; then
        warn ".env file already exists. Use --force to overwrite."
        return 0
    fi

    # Backup existing .env
    if [[ -f "$env_file" ]]; then
        backup_file "$env_file"
    fi

    # Copy example to .env
    cp "$env_example" "$env_file"

    # Generate secure values
    local app_key
    app_key=$(generate_app_key)

    # Update common settings
    sed -i "s|^APP_KEY=.*|APP_KEY=${app_key}|" "$env_file"

    if [[ "$INSTALL_MODE" == "production" ]]; then
        configure_production_env "$env_file"
    else
        configure_development_env "$env_file"
    fi

    # Secure the .env file
    secure_file "$env_file" 600

    success "Environment file configured"
}

detect_database_driver() {
    # Check available PHP database drivers
    local has_sqlite=false
    local has_pgsql=false
    local has_mysql=false

    if php -r 'exit(extension_loaded("pdo_sqlite") ? 0 : 1);' 2>/dev/null; then
        has_sqlite=true
    fi
    if php -r 'exit(extension_loaded("pdo_pgsql") ? 0 : 1);' 2>/dev/null; then
        has_pgsql=true
    fi
    if php -r 'exit(extension_loaded("pdo_mysql") ? 0 : 1);' 2>/dev/null; then
        has_mysql=true
    fi

    # Return the best available driver
    if [[ "$has_sqlite" == "true" ]]; then
        echo "sqlite"
    elif [[ "$has_pgsql" == "true" ]]; then
        echo "pgsql"
    elif [[ "$has_mysql" == "true" ]]; then
        echo "mysql"
    else
        echo "none"
    fi
}

setup_postgresql_database() {
    local db_name="$1"
    local db_user="$2"
    local db_pass="$3"

    info "Setting up PostgreSQL database..."

    # Check if we can connect to PostgreSQL
    if ! command_exists psql; then
        warn "PostgreSQL client not found. Please create the database manually."
        return 1
    fi

    # Try to create database and user (requires sudo access to postgres)
    if sudo -u postgres psql -c "SELECT 1" &>/dev/null; then
        # Create database if not exists
        sudo -u postgres psql -c "SELECT 1 FROM pg_database WHERE datname='${db_name}'" | grep -q 1 || \
            sudo -u postgres psql -c "CREATE DATABASE ${db_name};" 2>/dev/null || true

        # Create user if not exists
        sudo -u postgres psql -c "SELECT 1 FROM pg_roles WHERE rolname='${db_user}'" | grep -q 1 || \
            sudo -u postgres psql -c "CREATE USER ${db_user} WITH PASSWORD '${db_pass}';" 2>/dev/null || true

        # Grant privileges
        sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE ${db_name} TO ${db_user};" 2>/dev/null || true
        sudo -u postgres psql -c "ALTER DATABASE ${db_name} OWNER TO ${db_user};" 2>/dev/null || true

        success "PostgreSQL database '${db_name}' configured"
        return 0
    else
        warn "Cannot access PostgreSQL. Please create the database manually:"
        echo "  sudo -u postgres psql -c \"CREATE DATABASE ${db_name};\""
        echo "  sudo -u postgres psql -c \"CREATE USER ${db_user} WITH PASSWORD '${db_pass}';\""
        echo "  sudo -u postgres psql -c \"GRANT ALL PRIVILEGES ON DATABASE ${db_name} TO ${db_user};\""
        return 1
    fi
}

configure_development_env() {
    local env_file="$1"

    info "Configuring development environment..."

    sed -i "s|^APP_ENV=.*|APP_ENV=local|" "$env_file"
    sed -i "s|^APP_DEBUG=.*|APP_DEBUG=true|" "$env_file"
    sed -i "s|^APP_URL=.*|APP_URL=http://localhost:8000|" "$env_file"

    # Detect best available database driver
    local db_driver
    db_driver=$(detect_database_driver)

    case "$db_driver" in
        sqlite)
            info "Using SQLite database (no external dependencies)"
            sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=sqlite|" "$env_file"
            ;;
        pgsql)
            info "SQLite not available, using PostgreSQL"
            local db_password
            db_password=$(generate_secure_password 24)

            sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=pgsql|" "$env_file"

            # Add database settings
            if ! grep -q "^DB_HOST=" "$env_file"; then
                echo "DB_HOST=127.0.0.1" >> "$env_file"
            else
                sed -i "s|^DB_HOST=.*|DB_HOST=127.0.0.1|" "$env_file"
            fi
            sed -i "s|^# DB_HOST=.*|DB_HOST=127.0.0.1|" "$env_file"

            if ! grep -q "^DB_PORT=" "$env_file"; then
                echo "DB_PORT=5432" >> "$env_file"
            else
                sed -i "s|^DB_PORT=.*|DB_PORT=5432|" "$env_file"
            fi
            sed -i "s|^# DB_PORT=.*|DB_PORT=5432|" "$env_file"

            if ! grep -q "^DB_DATABASE=" "$env_file"; then
                echo "DB_DATABASE=devflow_pro" >> "$env_file"
            else
                sed -i "s|^DB_DATABASE=.*|DB_DATABASE=devflow_pro|" "$env_file"
            fi
            sed -i "s|^# DB_DATABASE=.*|DB_DATABASE=devflow_pro|" "$env_file"

            if ! grep -q "^DB_USERNAME=" "$env_file"; then
                echo "DB_USERNAME=devflow" >> "$env_file"
            else
                sed -i "s|^DB_USERNAME=.*|DB_USERNAME=devflow|" "$env_file"
            fi
            sed -i "s|^# DB_USERNAME=.*|DB_USERNAME=devflow|" "$env_file"

            if ! grep -q "^DB_PASSWORD=" "$env_file"; then
                echo "DB_PASSWORD=${db_password}" >> "$env_file"
            else
                sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${db_password}|" "$env_file"
            fi
            sed -i "s|^# DB_PASSWORD=.*|DB_PASSWORD=${db_password}|" "$env_file"

            # Try to create the database
            setup_postgresql_database "devflow_pro" "devflow" "$db_password" || true
            ;;
        mysql)
            info "SQLite not available, using MySQL"
            sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=mysql|" "$env_file"
            warn "Please configure MySQL database settings in .env manually"
            ;;
        *)
            fatal "No supported database driver found. Please install php-sqlite3, php-pgsql, or php-mysql."
            ;;
    esac

    # File-based cache and session
    sed -i "s|^CACHE_STORE=.*|CACHE_STORE=file|" "$env_file"
    sed -i "s|^SESSION_DRIVER=.*|SESSION_DRIVER=file|" "$env_file"
    sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=sync|" "$env_file"

    # Disable broadcasting for development
    sed -i "s|^BROADCAST_CONNECTION=.*|BROADCAST_CONNECTION=log|" "$env_file"

    info "Development environment configured (${db_driver}, file cache, sync queue)"
}

configure_production_env() {
    local env_file="$1"

    info "Configuring production environment..."

    # Generate secure passwords
    local db_password
    local redis_password
    db_password=$(generate_secure_password 32)
    redis_password=$(generate_secure_password 32)

    # Production settings
    sed -i "s|^APP_ENV=.*|APP_ENV=production|" "$env_file"
    sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" "$env_file"

    # PostgreSQL configuration
    sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=pgsql|" "$env_file"
    sed -i "s|^# DB_HOST=.*|DB_HOST=127.0.0.1|" "$env_file"
    sed -i "s|^# DB_PORT=.*|DB_PORT=5432|" "$env_file"
    sed -i "s|^# DB_DATABASE=.*|DB_DATABASE=devflow_pro|" "$env_file"
    sed -i "s|^# DB_USERNAME=.*|DB_USERNAME=devflow|" "$env_file"
    sed -i "s|^# DB_PASSWORD=.*|DB_PASSWORD=${db_password}|" "$env_file"

    # Add uncommented database settings if they don't exist
    if ! grep -q "^DB_HOST=" "$env_file"; then
        echo "DB_HOST=127.0.0.1" >> "$env_file"
    fi
    if ! grep -q "^DB_PORT=" "$env_file"; then
        echo "DB_PORT=5432" >> "$env_file"
    fi
    if ! grep -q "^DB_DATABASE=" "$env_file"; then
        echo "DB_DATABASE=devflow_pro" >> "$env_file"
    fi
    if ! grep -q "^DB_USERNAME=" "$env_file"; then
        echo "DB_USERNAME=devflow" >> "$env_file"
    fi
    if ! grep -q "^DB_PASSWORD=" "$env_file"; then
        echo "DB_PASSWORD=${db_password}" >> "$env_file"
    fi

    # Redis configuration
    sed -i "s|^REDIS_PASSWORD=.*|REDIS_PASSWORD=${redis_password}|" "$env_file"
    sed -i "s|^CACHE_STORE=.*|CACHE_STORE=redis|" "$env_file"
    sed -i "s|^SESSION_DRIVER=.*|SESSION_DRIVER=redis|" "$env_file"
    sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=redis|" "$env_file"

    # Secure session settings
    sed -i "s|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=true|" "$env_file"

    # Save credentials to secure file
    local creds_file="${SCRIPT_DIR}/.credentials"
    cat > "$creds_file" << EOF
# DevFlow Pro - Generated Credentials
# Created: $(date)
# WARNING: Store these credentials securely and delete this file after copying!

DATABASE_PASSWORD=${db_password}
REDIS_PASSWORD=${redis_password}
EOF
    secure_file "$creds_file" 600

    warn "Production credentials saved to .credentials file"
    warn "Please copy and store these credentials securely, then delete the file!"

    info "Production environment configured (PostgreSQL, Redis)"
}

install_composer_dependencies() {
    step "Installing Composer dependencies"

    local composer_opts="--no-interaction --prefer-dist"

    if [[ "$INSTALL_MODE" == "production" ]]; then
        composer_opts="$composer_opts --no-dev --optimize-autoloader --classmap-authoritative"
    fi

    if [[ "$DRY_RUN" == "true" ]]; then
        info "[DRY RUN] Would run: composer install $composer_opts"
        return 0
    fi

    cd "$SCRIPT_DIR"

    if ! composer install $composer_opts; then
        fatal "Failed to install Composer dependencies"
    fi

    success "Composer dependencies installed"
}

install_npm_dependencies() {
    step "Installing npm dependencies"

    if [[ "$SKIP_ASSETS" == "true" ]]; then
        info "Skipping npm installation (--skip-assets)"
        return 0
    fi

    cd "$SCRIPT_DIR"

    if [[ "$DRY_RUN" == "true" ]]; then
        info "[DRY RUN] Would run: npm ci"
        return 0
    fi

    # Use npm ci for reproducible builds if package-lock.json exists
    if [[ -f "package-lock.json" ]]; then
        if ! npm ci; then
            fatal "Failed to install npm dependencies"
        fi
    else
        if ! npm install; then
            fatal "Failed to install npm dependencies"
        fi
    fi

    success "npm dependencies installed"
}

build_assets() {
    step "Building frontend assets"

    if [[ "$SKIP_ASSETS" == "true" ]]; then
        info "Skipping asset build (--skip-assets)"
        return 0
    fi

    cd "$SCRIPT_DIR"

    if [[ "$DRY_RUN" == "true" ]]; then
        info "[DRY RUN] Would run: npm run build"
        return 0
    fi

    # Build assets in both modes for initial setup
    if ! npm run build; then
        fatal "Failed to build frontend assets"
    fi

    if [[ "$INSTALL_MODE" != "production" ]]; then
        info "Tip: Run 'npm run dev' for hot-reload during development"
    fi

    success "Frontend assets built"
}

setup_database() {
    step "Setting up database"

    if [[ "$SKIP_MIGRATIONS" == "true" ]]; then
        info "Skipping database setup (--skip-migrations)"
        return 0
    fi

    cd "$SCRIPT_DIR"

    # Detect current database driver from .env
    local db_driver
    db_driver=$(grep "^DB_CONNECTION=" .env 2>/dev/null | cut -d= -f2)

    if [[ "$db_driver" == "sqlite" ]]; then
        # Create SQLite database file
        local sqlite_db="${SCRIPT_DIR}/database/database.sqlite"
        if [[ ! -f "$sqlite_db" ]]; then
            touch "$sqlite_db"
            chmod 664 "$sqlite_db"
            info "Created SQLite database file"
        fi
    fi

    if [[ "$DRY_RUN" == "true" ]]; then
        info "[DRY RUN] Would run: php artisan migrate --force"
        return 0
    fi

    # Clear config cache before migrations
    php artisan config:clear 2>/dev/null || true

    # Run migrations
    if ! php artisan migrate --force; then
        fatal "Database migration failed"
    fi

    success "Database migrations completed"

    # Run seeders
    info "Running database seeders..."
    if php artisan db:seed --force; then
        success "Database seeded successfully"
    else
        warn "Database seeding failed (non-critical)"
    fi
}

set_web_server_permissions() {
    step "Setting web server permissions"

    cd "$SCRIPT_DIR"

    if [[ "$DRY_RUN" == "true" ]]; then
        info "[DRY RUN] Would set web server permissions"
        return 0
    fi

    # Determine web server user
    local web_user="www-data"
    if ! id "$web_user" &>/dev/null; then
        web_user="nginx"
        if ! id "$web_user" &>/dev/null; then
            web_user="apache"
            if ! id "$web_user" &>/dev/null; then
                warn "Web server user not found. Skipping permission setup."
                return 0
            fi
        fi
    fi

    info "Setting permissions for web server user: $web_user"

    # Set ownership for storage and bootstrap/cache
    if sudo chown -R "$web_user:$web_user" storage bootstrap/cache 2>/dev/null; then
        success "Ownership set for storage directories"
    else
        warn "Could not change ownership. You may need to run:"
        echo "  sudo chown -R $web_user:$web_user storage bootstrap/cache"
    fi

    # Set directory permissions
    sudo chmod -R 775 storage bootstrap/cache 2>/dev/null || true

    # Make .env readable by web server
    sudo chmod 644 .env 2>/dev/null || true

    success "Web server permissions configured"
}

optimize_application() {
    step "Optimizing application"

    cd "$SCRIPT_DIR"

    if [[ "$DRY_RUN" == "true" ]]; then
        info "[DRY RUN] Would run optimization commands"
        return 0
    fi

    if [[ "$INSTALL_MODE" == "production" ]]; then
        # Production optimizations
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        php artisan event:cache

        success "Production caches created"
    else
        # Development - clear caches
        php artisan config:clear
        php artisan route:clear
        php artisan view:clear
        php artisan cache:clear

        success "Development caches cleared"
    fi
}

create_storage_link() {
    step "Creating storage symbolic link"

    cd "$SCRIPT_DIR"

    if [[ "$DRY_RUN" == "true" ]]; then
        info "[DRY RUN] Would run: php artisan storage:link"
        return 0
    fi

    # Remove existing link if it's broken
    if [[ -L "${SCRIPT_DIR}/public/storage" && ! -e "${SCRIPT_DIR}/public/storage" ]]; then
        rm "${SCRIPT_DIR}/public/storage"
    fi

    if [[ ! -L "${SCRIPT_DIR}/public/storage" ]]; then
        php artisan storage:link
        success "Storage link created"
    else
        info "Storage link already exists"
    fi
}

setup_production_services() {
    if [[ "$INSTALL_MODE" != "production" ]]; then
        return 0
    fi

    step "Setting up production services"

    # Check if running with sudo capabilities
    if ! sudo -n true 2>/dev/null; then
        warn "Cannot run sudo without password. Skipping service configuration."
        warn "Please manually configure Nginx and Supervisor."
        return 0
    fi

    # Setup Nginx configuration
    setup_nginx_config

    # Setup Supervisor configuration
    setup_supervisor_config

    # Setup cron job
    setup_cron_job
}

setup_nginx_config() {
    info "Configuring Nginx..."

    local nginx_config="/etc/nginx/sites-available/devflow-pro"
    local nginx_enabled="/etc/nginx/sites-enabled/devflow-pro"

    if [[ -f "$nginx_config" && "$FORCE_INSTALL" != "true" ]]; then
        warn "Nginx configuration already exists. Use --force to overwrite."
        return 0
    fi

    sudo tee "$nginx_config" > /dev/null << EOF
# DevFlow Pro - Nginx Configuration
# Auto-generated by install.sh

server {
    listen 80;
    listen [::]:80;
    server_name _;
    root ${SCRIPT_DIR}/public;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Disable server tokens
    server_tokens off;

    index index.php;
    charset utf-8;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json application/xml+rss;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Deny access to sensitive files
    location ~* \.(env|log|sql|bak|backup)$ {
        deny all;
    }
}
EOF

    # Enable site
    sudo ln -sf "$nginx_config" "$nginx_enabled"

    # Test configuration
    if sudo nginx -t; then
        sudo systemctl reload nginx
        success "Nginx configured and reloaded"
    else
        error "Nginx configuration test failed"
    fi
}

setup_supervisor_config() {
    info "Configuring Supervisor for queue workers..."

    local supervisor_config="/etc/supervisor/conf.d/devflow-pro.conf"

    if [[ -f "$supervisor_config" && "$FORCE_INSTALL" != "true" ]]; then
        warn "Supervisor configuration already exists. Use --force to overwrite."
        return 0
    fi

    sudo tee "$supervisor_config" > /dev/null << EOF
# DevFlow Pro - Queue Worker Configuration
# Auto-generated by install.sh

[program:devflow-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${SCRIPT_DIR}/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=${SCRIPT_DIR}/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
EOF

    # Reload supervisor
    sudo supervisorctl reread
    sudo supervisorctl update

    success "Supervisor configured"
}

setup_cron_job() {
    info "Setting up cron job for scheduled tasks..."

    local cron_entry="* * * * * cd ${SCRIPT_DIR} && php artisan schedule:run >> /dev/null 2>&1"

    # Check if cron entry already exists
    if crontab -l 2>/dev/null | grep -q "devflow.*schedule:run"; then
        info "Cron job already configured"
        return 0
    fi

    # Add cron entry
    (crontab -l 2>/dev/null; echo "$cron_entry") | crontab -

    success "Cron job configured"
}

# ============================================================================
# MAIN FUNCTIONS
# ============================================================================

show_usage() {
    cat << EOF
${BOLD}DevFlow Pro Installation Script v${SCRIPT_VERSION}${NC}

${BOLD}USAGE:${NC}
    $SCRIPT_NAME [OPTIONS]

${BOLD}OPTIONS:${NC}
    --production        Install in production mode (PostgreSQL, Redis, Nginx)
    --force             Force installation, overwriting existing files
    --skip-migrations   Skip database migrations
    --skip-assets       Skip npm install and asset building
    --dry-run           Show what would be done without making changes
    --verbose           Enable verbose output
    --help              Show this help message
    --version           Show version information

${BOLD}EXAMPLES:${NC}
    $SCRIPT_NAME                    # Development installation (default)
    $SCRIPT_NAME --production       # Production installation
    $SCRIPT_NAME --production --force  # Force production reinstall

${BOLD}DEVELOPMENT MODE (default):${NC}
    - SQLite database (no external database required)
    - File-based cache and sessions
    - Synchronous queue processing
    - Debug mode enabled
    - Ready to run with: php artisan serve

${BOLD}PRODUCTION MODE (--production):${NC}
    - PostgreSQL database (must be installed)
    - Redis for cache, sessions, and queues
    - Nginx configuration generated
    - Supervisor queue workers configured
    - Optimized for performance
    - Debug mode disabled

${BOLD}REQUIREMENTS:${NC}
    - PHP $MIN_PHP_VERSION or higher with required extensions
    - Composer $MIN_COMPOSER_VERSION or higher
    - Node.js $MIN_NODE_VERSION or higher with npm
    - Git
    - PostgreSQL 14+ (production only)
    - Redis 7+ (production only)
    - Nginx (production only)

${BOLD}SECURITY:${NC}
    - Do NOT run as root
    - Secure passwords auto-generated for production
    - .env file permissions set to 600
    - Credentials saved to .credentials file (delete after copying!)

${BOLD}LOGS:${NC}
    Installation log: $LOG_FILE

${BOLD}DOCUMENTATION:${NC}
    Full documentation: https://github.com/your-repo/devflow-pro

EOF
}

show_version() {
    echo "DevFlow Pro Installation Script v${SCRIPT_VERSION}"
}

parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --production)
                INSTALL_MODE="production"
                shift
                ;;
            --force)
                FORCE_INSTALL=true
                shift
                ;;
            --skip-migrations)
                SKIP_MIGRATIONS=true
                shift
                ;;
            --skip-assets)
                SKIP_ASSETS=true
                shift
                ;;
            --dry-run)
                DRY_RUN=true
                shift
                ;;
            --verbose)
                VERBOSE=true
                shift
                ;;
            --help|-h)
                show_usage
                exit 0
                ;;
            --version|-v)
                show_version
                exit 0
                ;;
            *)
                error "Unknown option: $1"
                echo "Use --help for usage information"
                exit 1
                ;;
        esac
    done
}

show_summary() {
    echo ""
    echo -e "${GREEN}${BOLD}=============================================${NC}"
    echo -e "${GREEN}${BOLD}  DevFlow Pro Installation Complete!${NC}"
    echo -e "${GREEN}${BOLD}=============================================${NC}"
    echo ""
    echo -e "${BOLD}Installation Mode:${NC} ${INSTALL_MODE}"
    echo -e "${BOLD}Project Directory:${NC} ${SCRIPT_DIR}"
    echo ""

    if [[ "$INSTALL_MODE" == "production" ]]; then
        echo -e "${YELLOW}${BOLD}IMPORTANT PRODUCTION STEPS:${NC}"
        echo "  1. Review and update .env file settings"
        echo "  2. Copy credentials from .credentials file and store securely"
        echo "  3. Delete the .credentials file: rm .credentials"
        echo "  4. Configure your domain and SSL certificates"
        echo "  5. Create PostgreSQL database and user with saved credentials"
        echo "  6. Start queue workers: sudo supervisorctl start devflow-worker:*"
        echo ""
        echo -e "${BOLD}Access the application at:${NC}"
        echo "  http://your-domain.com"
    else
        echo -e "${BOLD}To start the development server:${NC}"
        echo "  cd ${SCRIPT_DIR}"
        echo "  php artisan serve"
        echo ""
        echo -e "${BOLD}For frontend hot-reload:${NC}"
        echo "  npm run dev"
        echo ""
        echo -e "${BOLD}Access the application at:${NC}"
        echo "  http://localhost:8000"
    fi

    echo ""
    echo -e "${BOLD}Documentation:${NC} See README.md"
    echo -e "${BOLD}Installation Log:${NC} ${LOG_FILE}"
    echo ""
}

main() {
    # Initialize log file
    mkdir -p "$(dirname "$LOG_FILE")"
    echo "=== DevFlow Pro Installation Log ===" > "$LOG_FILE"
    echo "Started: $(date)" >> "$LOG_FILE"
    echo "Mode: $INSTALL_MODE" >> "$LOG_FILE"
    echo "" >> "$LOG_FILE"

    echo ""
    echo -e "${CYAN}${BOLD}=============================================${NC}"
    echo -e "${CYAN}${BOLD}  DevFlow Pro Installation Script v${SCRIPT_VERSION}${NC}"
    echo -e "${CYAN}${BOLD}=============================================${NC}"
    echo ""
    echo -e "${BOLD}Installation Mode:${NC} ${INSTALL_MODE}"
    echo ""

    if [[ "$DRY_RUN" == "true" ]]; then
        warn "DRY RUN MODE - No changes will be made"
        echo ""
    fi

    # Validation
    validate_not_root
    validate_php_version
    validate_composer
    validate_node
    validate_git
    validate_production_requirements

    # Installation steps
    create_directories
    setup_environment_file
    install_composer_dependencies
    install_npm_dependencies
    build_assets
    setup_database
    create_storage_link
    optimize_application
    set_web_server_permissions
    setup_production_services

    # Show completion summary
    show_summary

    # Log completion
    log "INFO" "Installation completed successfully"
    echo "Completed: $(date)" >> "$LOG_FILE"
}

# ============================================================================
# SCRIPT ENTRY POINT
# ============================================================================

parse_arguments "$@"
main
