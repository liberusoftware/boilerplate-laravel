#!/usr/bin/env bash
# Setup script for the boilerplate-laravel project.
#
# Provides installation options for Standalone, Docker, or Kubernetes deployments.
# Handles composer/npm installations with fallback logic and error checking.

set -e  # Exit on error

# Colors for output
RED='\e[91m'
GREEN='\e[92m'
YELLOW='\e[93m'
BLUE='\e[94m'
RESET='\e[39m'

# Function to print colored messages
print_message() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${RESET}"
}

print_header() {
    echo ""
    echo "=================================="
    echo "$1"
    echo "=================================="
    echo ""
}

print_error() {
    print_message "$RED" "❌ ERROR: $1"
}

print_success() {
    print_message "$GREEN" "✅ $1"
}

print_info() {
    print_message "$BLUE" "ℹ️  $1"
}

print_warning() {
    print_message "$YELLOW" "⚠️  $1"
}

# Check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check PHP version is >= required
check_php_version() {
    local required="${1:-8.5}"
    if ! command_exists php; then
        print_error "PHP is not installed. Please install PHP ${required}+ first."
        return 1
    fi
    local current
    current=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
    if php -r "exit(version_compare(PHP_VERSION, '${required}', '>=') ? 0 : 1);"; then
        print_success "PHP ${current} detected (>= ${required} required)"
        return 0
    else
        print_error "PHP ${current} is too old — PHP ${required}+ is required."
        return 1
    fi
}

# Download composer.phar if composer is not available
ensure_composer() {
    if command_exists composer; then
        print_success "Composer is already installed"
        COMPOSER_CMD="composer"
        return 0
    fi

    print_warning "Composer command not found. Attempting to download composer.phar..."

    if ! command_exists curl; then
        print_error "curl is required to download composer. Please install curl or composer manually."
        return 1
    fi

    if ! command_exists php; then
        print_error "PHP is required. Please install PHP first."
        return 1
    fi

    # Download composer installer
    print_info "Downloading Composer installer..."
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

    # Install Composer locally
    print_info "Installing Composer locally..."
    php composer-setup.php --quiet

    # Clean up installer
    php -r "unlink('composer-setup.php');"

    if [ -f "composer.phar" ]; then
        print_success "Composer.phar downloaded successfully"
        COMPOSER_CMD="php composer.phar"
        return 0
    else
        print_error "Failed to download composer.phar"
        return 1
    fi
}

# Install composer dependencies
install_composer_dependencies() {
    print_header "🎬 COMPOSER INSTALL"

    # Check if vendor directory exists
    if [ -d "vendor" ] && [ -f "vendor/autoload.php" ]; then
        print_info "Vendor directory already exists. Skipping composer install."
        read -p "Do you want to reinstall composer dependencies? (y/n) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_success "Skipping composer install"
            return 0
        fi
    fi

    # Ensure composer is available
    if ! ensure_composer; then
        print_error "Cannot proceed without Composer"
        return 1
    fi

    # Run composer install
    print_info "Running: $COMPOSER_CMD install"
    if eval "$COMPOSER_CMD install --no-interaction --prefer-dist"; then
        print_success "Composer dependencies installed successfully"
        return 0
    else
        print_error "Composer install failed"
        return 1
    fi
}

# Install npm dependencies
install_npm_dependencies() {
    print_header "🎬 NPM INSTALL"

    # Check if node_modules directory exists
    if [ -d "node_modules" ]; then
        print_info "node_modules directory already exists. Skipping npm install."
        read -p "Do you want to reinstall npm dependencies? (y/n) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_success "Skipping npm install"
            return 0
        fi
    fi

    # Check if npm is available
    if ! command_exists npm; then
        print_error "npm is not installed. Please install Node.js and npm first."
        print_info "Visit: https://nodejs.org/"
        return 1
    fi

    # Run npm install
    print_info "Running: npm install"
    if npm install; then
        print_success "NPM dependencies installed successfully"
        return 0
    else
        print_error "NPM install failed"
        return 1
    fi
}

# Build frontend assets
build_frontend_assets() {
    print_header "🎬 NPM BUILD"

    # Check if npm is available
    if ! command_exists npm; then
        print_error "npm is not installed. Cannot build assets."
        return 1
    fi

    # Run npm build
    print_info "Running: npm run build"
    if npm run build; then
        print_success "Frontend assets built successfully"
        return 0
    else
        print_error "NPM build failed"
        return 1
    fi
}

# Standalone installation
install_standalone() {
    print_header "STANDALONE INSTALLATION"

    echo "=================================="
    echo "===== USER: [$(whoami)]"
    echo "===== [PHP $(php -r 'echo phpversion();')]"
    echo "=================================="
    echo ""

    # PHP version gate
    if ! check_php_version "8.5"; then
        exit 1
    fi

    # Setup the .env file
    if [ -f ".env" ]; then
        print_info ".env already exists — skipping copy."
        copy=false
    else
        copy=true
        while true; do
            read -p "🎬 DEV ---> COPY .ENV.EXAMPLE TO .ENV? (y/n) " yn
            case $yn in
                [Yy]* )
                    print_success "Copying .env.example to .env"
                    cp .env.example .env
                    break
                    ;;
                [Nn]* )
                    print_warning "No .env file — some steps may fail."
                    copy=false
                    break
                    ;;
                * )
                    print_warning "Please answer yes or no."
                    ;;
            esac
        done
    fi

    echo ""

    # Ask user to confirm credentials only when a fresh .env was just created
    if [ "$copy" = true ]; then
        while true; do
            read -p "🎬 DEV ---> HAVE YOU SET DATABASE CREDENTIALS IN .ENV? (y/n) " cond
            case $cond in
                [Yy]* )
                    print_success "Perfect, continuing with setup"
                    break
                    ;;
                [Nn]* )
                    print_warning "Please edit .env and re-run this script."
                    exit 0
                    ;;
                * )
                    print_warning "Please answer yes or no."
                    ;;
            esac
        done
    fi

    echo ""
    echo "=================================="
    echo ""

    # Install composer dependencies
    if ! install_composer_dependencies; then
        print_error "Installation failed at composer install step"
        exit 1
    fi

    echo ""
    echo "=================================="
    echo ""

    # Install npm dependencies
    if ! install_npm_dependencies; then
        print_warning "NPM install failed, but continuing..."
    fi

    echo ""
    echo "=================================="
    echo ""

    # Build frontend assets
    if ! build_frontend_assets; then
        print_warning "NPM build failed, but continuing..."
    fi

    echo ""
    echo "=================================="
    echo ""

    # Generate Laravel key
    print_header "🎬 PHP ARTISAN KEY:GENERATE"
    if php artisan key:generate; then
        print_success "Application key generated"
    else
        print_error "Failed to generate application key"
        exit 1
    fi

    echo ""
    echo "=================================="
    echo ""

    # Run database migrations and seed in one pass
    print_header "🎬 PHP ARTISAN MIGRATE:FRESH --SEED"
    if php artisan migrate:fresh --seed; then
        print_success "Database migrated and seeded successfully"
    else
        print_error "Database migration/seed failed"
        exit 1
    fi

    echo ""
    echo "=================================="
    echo ""

    # Create storage symlink
    print_header "🎬 PHP ARTISAN STORAGE:LINK"
    if php artisan storage:link; then
        print_success "Storage symlink created"
    else
        print_warning "storage:link failed (symlink may already exist)"
    fi

    echo ""
    echo "=================================="
    echo ""

    # Generate Filament Shield permissions
    print_header "🎬 PHP ARTISAN SHIELD:GENERATE"
    if php artisan shield:generate --all --ignore-config-policies 2>/dev/null; then
        print_success "Filament Shield permissions generated"
    else
        print_warning "Shield generate skipped (not configured or no resources found)"
    fi

    echo ""
    echo "=================================="
    echo ""

    # Run test suite (Pest preferred, falls back to PHPUnit)
    print_header "🎬 RUNNING TESTS"
    if [ -f "vendor/bin/pest" ]; then
        if vendor/bin/pest; then
            print_success "Tests passed"
        else
            print_warning "Tests failed. Please review the errors."
        fi
    elif [ -f "vendor/bin/phpunit" ]; then
        if vendor/bin/phpunit; then
            print_success "PHPUnit tests passed"
        else
            print_warning "PHPUnit tests failed. Please review the errors."
        fi
    else
        print_warning "No test runner found. Skipping tests."
    fi

    echo ""
    echo "=================================="
    echo ""

    # Run optimization commands for Laravel
    print_header "🎬 PHP ARTISAN OPTIMIZE:CLEAR"
    php artisan optimize:clear
    php artisan route:clear

    echo ""
    print_success "=================================="
    print_success "     INSTALLATION COMPLETE        "
    print_success "=================================="
    echo ""
    echo "Useful commands:"
    echo "  php artisan serve          - Start development server"
    echo "  php artisan horizon        - Start queue worker dashboard"
    echo "  php artisan reverb:start   - Start WebSocket server"
    echo "  php artisan octane:start   - Start Octane server (production)"
    echo "  npm run dev                - Start Vite dev server"
    echo ""

    # Ask if user wants to start the server
    while true; do
        read -p "🎬 DEV ---> START THE DEV SERVER NOW? (y/n) " cond
        case $cond in
            [Yy]* )
                print_success "Starting server at http://localhost:8000 ..."
                php artisan serve
                break
                ;;
            [Nn]* )
                print_success "Start manually with: php artisan serve"
                exit 0
                ;;
            * )
                print_warning "Please answer yes or no."
                ;;
        esac
    done
}

# Docker installation
install_docker() {
    print_header "DOCKER INSTALLATION"
    print_info "Starting Docker installation process..."

    # Check if Docker is installed
    if ! command_exists docker; then
        print_error "Docker is not installed. Please install Docker first."
        print_info "Visit: https://docs.docker.com/get-docker/"
        exit 1
    fi

    print_success "Docker is installed"

    # Check for docker-compose
    if ! command_exists docker-compose && ! docker compose version >/dev/null 2>&1; then
        print_error "Docker Compose is not installed. Please install Docker Compose first."
        print_info "Visit: https://docs.docker.com/compose/install/"
        exit 1
    fi

    print_success "Docker Compose is available"

    # Setup .env file
    if [ ! -f ".env" ]; then
        print_info "Copying .env.example to .env"
        cp .env.example .env
        print_warning "Please edit .env file to configure your Docker environment"
        read -p "Press Enter to continue after editing .env..."
    fi

    # Resolve docker compose command
    local DOCKER_CMD="docker compose"
    if command_exists docker-compose; then
        DOCKER_CMD="docker-compose"
    fi

    # Build and start containers — capture exit code without relying on set -e
    print_info "Building and starting Docker containers..."
    local docker_exit=0
    $DOCKER_CMD up -d --build || docker_exit=$?

    if [ $docker_exit -eq 0 ]; then
        print_success "Docker containers started successfully"

        # Wait briefly for containers to be healthy, then run migrations
        print_info "Waiting for services to be ready..."
        sleep 5

        print_info "Running migrations inside app container..."
        if $DOCKER_CMD exec app php artisan migrate --force; then
            print_success "Database migrations completed"
        else
            print_warning "Migrations failed or app container not ready; run manually:"
            print_warning "  $DOCKER_CMD exec app php artisan migrate --force"
        fi

        print_info "Creating storage symlink inside app container..."
        $DOCKER_CMD exec app php artisan storage:link 2>/dev/null \
            && print_success "Storage symlink created" \
            || print_warning "storage:link skipped (may already exist)"

        print_info "Your application should be available at http://localhost:8000"
    else
        print_error "Failed to start Docker containers (exit code: $docker_exit)"
        exit 1
    fi
}

# Kubernetes installation
install_kubernetes() {
    print_header "KUBERNETES INSTALLATION"
    print_info "Starting Kubernetes installation process..."

    # Check if kubectl is installed
    if ! command_exists kubectl; then
        print_error "kubectl is not installed. Please install kubectl first."
        print_info "Visit: https://kubernetes.io/docs/tasks/tools/"
        exit 1
    fi

    print_success "kubectl is installed"

    # Check for k8s config files
    if [ ! -d "k8s" ] && [ ! -d "kubernetes" ]; then
        print_error "No Kubernetes configuration directory found (k8s/ or kubernetes/)"
        print_warning "Kubernetes installation requires configuration files."
        print_info "Please create Kubernetes manifests in a k8s/ or kubernetes/ directory"
        exit 1
    fi

    # Determine config directory
    local K8S_DIR="k8s"
    if [ ! -d "$K8S_DIR" ] && [ -d "kubernetes" ]; then
        K8S_DIR="kubernetes"
    fi

    print_info "Using Kubernetes configurations from: $K8S_DIR/"

    # Prefer the dedicated deploy script when it exists
    if [ -f "$K8S_DIR/deploy.sh" ]; then
        print_info "Found $K8S_DIR/deploy.sh — using it for deployment."
        print_info "Required env vars: APP_KEY, DB_PASSWORD, DB_ROOT_PASSWORD"
        print_info "Optional env vars: NAMESPACE, ENVIRONMENT, DOMAIN"
        bash "$K8S_DIR/deploy.sh"
        return $?
    fi

    # Warn if secret.yaml still has placeholder values
    local SECRET_FILE="$K8S_DIR/base/secret.yaml"
    if [ -f "$SECRET_FILE" ] && grep -q "REPLACE_WITH" "$SECRET_FILE"; then
        print_warning "Secret placeholders detected in $SECRET_FILE."
        print_warning "Edit it or set env vars before deploying."
        read -p "Continue anyway? (y/N) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_info "Update secrets and re-run."
            exit 0
        fi
    fi

    # Choose overlay environment
    local OVERLAY="${KUBE_ENV:-production}"
    local KUBE_TARGET
    if [ -d "$K8S_DIR/overlays/$OVERLAY" ]; then
        KUBE_TARGET="$K8S_DIR/overlays/$OVERLAY"
        print_info "Using overlay: $OVERLAY"
    elif [ -d "$K8S_DIR/base" ]; then
        KUBE_TARGET="$K8S_DIR/base"
        print_warning "Overlay '$OVERLAY' not found, applying base configuration"
    else
        KUBE_TARGET="$K8S_DIR"
    fi

    # Check if kustomization.yaml exists (Kustomize vs plain manifests)
    local KUBE_APPLY_CMD
    if [ -f "$KUBE_TARGET/kustomization.yaml" ]; then
        KUBE_APPLY_CMD="kubectl apply -k $KUBE_TARGET"
    else
        KUBE_APPLY_CMD="kubectl apply -f $KUBE_TARGET/"
    fi

    # Apply Kubernetes configurations
    print_info "Applying Kubernetes configurations: $KUBE_APPLY_CMD"
    if eval "$KUBE_APPLY_CMD"; then
        print_success "Kubernetes resources applied successfully"
        print_info "Check pod status:   kubectl get pods -n boilerplate-laravel"
        print_info "Watch deployment:   kubectl rollout status deployment/boilerplate-laravel -n boilerplate-laravel"
        # Suggest validation script when present
        if [ -f "$K8S_DIR/validate.sh" ]; then
            print_info "Validate cluster:   bash $K8S_DIR/validate.sh"
        fi
    else
        print_error "Failed to apply Kubernetes configurations"
        exit 1
    fi
}

# Main installation menu
main() {
    clear
    print_header "LIBERU BOILERPLATE LARAVEL - INSTALLER"

    echo "Please select installation type:"
    echo ""
    echo "  1) Standalone (Local development/production)"
    echo "  2) Docker (Containerized deployment)"
    echo "  3) Kubernetes (K8s cluster deployment)"
    echo "  4) Exit"
    echo ""

    while true; do
        read -p "Enter your choice (1-4): " choice
        case $choice in
            1)
                install_standalone
                break
                ;;
            2)
                install_docker
                break
                ;;
            3)
                install_kubernetes
                break
                ;;
            4)
                print_info "Installation cancelled"
                exit 0
                ;;
            *)
                print_warning "Invalid choice. Please enter 1, 2, 3, or 4."
                ;;
        esac
    done
}

# Run main function
main
