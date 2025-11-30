
#!/bin/bash

# CSSD Medical Distribution System - Auto Setup Script
# This script automatically sets up the entire CSSD system including:
# - Docker containers (MySQL, Laravel, Nginx, phpMyAdmin)
# - Database migrations and seeding
# - Frontend build
# - Mobile app setup
# - Final verification

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check system requirements
check_requirements() {
    print_status "Checking system requirements..."

    # Check Docker
    if ! command_exists docker; then
        print_error "Docker is not installed. Please install Docker first."
        exit 1
    fi

    # Check Docker Compose
    if ! command_exists docker-compose && ! docker compose version >/dev/null 2>&1; then
        print_error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi

    # Check Node.js
    if ! command_exists node; then
        print_error "Node.js is not installed. Please install Node.js first."
        exit 1
    fi

    # Check npm
    if ! command_exists npm; then
        print_error "npm is not installed. Please install npm first."
        exit 1
    fi

    # Check if ports are available
    if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null 2>&1; then
        print_error "Port 8000 is already in use. Please free it or change the port in docker-compose.yml"
        exit 1
    fi

    if lsof -Pi :8080 -sTCP:LISTEN -t >/dev/null 2>&1; then
        print_error "Port 8080 is already in use. Please free it or change the port in docker-compose.yml"
        exit 1
    fi

    if lsof -Pi :3307 -sTCP:LISTEN -t >/dev/null 2>&1; then
        print_error "Port 3307 is already in use. Please free it or change the port in docker-compose.yml"
        exit 1
    fi

    print_success "All system requirements met!"
}

# Function to setup environment variables
setup_environment() {
    print_status "Setting up environment variables..."

    # Create .env file for Laravel if it doesn't exist
    if [ ! -f "backend/.env" ]; then
        cp backend/.env.example backend/.env 2>/dev/null || cat > backend/.env << EOF
APP_NAME="CSSD Medical Distribution"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=cssd_distribution
DB_USERNAME=root
DB_PASSWORD=secure_root_password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="\${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="\${PUSHER_HOST}"
VITE_PUSHER_PORT="\${PUSHER_PORT}"
VITE_PUSHER_SCHEME="\${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="\${PUSHER_APP_CLUSTER}"

SANCTUM_STATEFUL_DOMAINS=localhost:8000,127.0.0.1:8000
EOF
        print_success "Created backend/.env file"
    else
        print_warning "backend/.env already exists, skipping..."
    fi

    # Create .env for frontend-web if it doesn't exist
    if [ ! -f "frontend-web/.env" ]; then
        cat > frontend-web/.env << EOF
VITE_API_URL=http://localhost:8000/api
VITE_APP_NAME=CSSD Medical Distribution
EOF
        print_success "Created frontend-web/.env file"
    else
        print_warning "frontend-web/.env already exists, skipping..."
    fi

    # Create .env for mobile if it doesn't exist
    if [ ! -f "mobile/.env" ]; then
        cat > mobile/.env << EOF
VITE_API_URL=http://localhost:8000/api
VITE_APP_NAME=CSSD Medical Distribution
EOF
        print_success "Created mobile/.env file"
    else
        print_warning "mobile/.env already exists, skipping..."
    fi
}

# Function to start Docker containers
start_containers() {
    print_status "Starting Docker containers..."

    # Stop any existing containers first
    docker-compose down >/dev/null 2>&1 || true

    # Start containers
    if command_exists docker-compose; then
        docker-compose up -d
    else
        docker compose up -d
    fi

    print_success "Docker containers started"

    # Wait for MySQL to be ready
    print_status "Waiting for MySQL to be ready..."
    sleep 30

    # Check if MySQL is ready
    max_attempts=30
    attempt=1
    while [ $attempt -le $max_attempts ]; do
        if docker-compose exec -T mysql mysqladmin ping -h localhost --silent >/dev/null 2>&1; then
            print_success "MySQL is ready!"
            break
        fi
        print_status "Waiting for MySQL... (attempt $attempt/$max_attempts)"
        sleep 5
        ((attempt++))
    done

    if [ $attempt -gt $max_attempts ]; then
        print_error "MySQL failed to start properly"
        exit 1
    fi
}

# Function to setup Laravel
setup_laravel() {
    print_status "Setting up Laravel application..."

    # Install PHP dependencies
    print_status "Installing PHP dependencies..."
    docker-compose exec -T app composer install --no-dev --optimize-autoloader

    # Generate application key
    print_status "Generating application key..."
    docker-compose exec -T app php artisan key:generate

    # Run migrations
    print_status "Running database migrations..."
    docker-compose exec -T app php artisan migrate --force

    # Seed database
    print_status "Seeding database..."
    docker-compose exec -T app php artisan db:seed --force

    # Create storage link
    print_status "Creating storage link..."
    docker-compose exec -T app php artisan storage:link

    # Clear and cache config
    print_status "Caching configuration..."
    docker-compose exec -T app php artisan config:cache
    docker-compose exec -T app php artisan route:cache
    docker-compose exec -T app php artisan view:cache

    print_success "Laravel setup completed!"
}

# Function to setup frontend
setup_frontend() {
    print_status "Setting up frontend application..."

    # Install dependencies
    print_status "Installing frontend dependencies..."
    cd frontend-web
    npm install

    # Build for production
    print_status "Building frontend for production..."
    npm run build

    cd ..
    print_success "Frontend setup completed!"
}

# Function to setup mobile app
setup_mobile() {
    print_status "Setting up mobile application..."

    # Install dependencies
    print_status "Installing mobile dependencies..."
    cd mobile
    npm install

    # Build mobile app
    print_status "Building mobile app..."
    npm run build

    cd ..
    print_success "Mobile setup completed!"
}

# Function to run final verification
verify_setup() {
    print_status "Running final verification..."

    # Test API health
    print_status "Testing API health..."
    if curl -s -f http://localhost:8000/api/health >/dev/null 2>&1; then
        print_success "API is responding correctly"
    else
        print_warning "API health check failed - this may be normal if health endpoint doesn't exist"
    fi

    # Test web interface
    print_status "Testing web interface..."
    if curl -s -f http://localhost:8000/ | grep -q "DOCTYPE html"; then
        print_success "Web interface is accessible"
    else
        print_error "Web interface is not accessible"
        exit 1
    fi

    # Test phpMyAdmin
    print_status "Testing phpMyAdmin..."
    if curl -s -f http://localhost:8080/ | grep -q "phpMyAdmin"; then
        print_success "phpMyAdmin is accessible"
    else
        print_error "phpMyAdmin is not accessible"
        exit 1
    fi

    # Check container status
    print_status "Checking container status..."
    if command_exists docker-compose; then
        if docker-compose ps | grep -q "Up"; then
            print_success "All containers are running"
        else
            print_error "Some containers are not running"
            docker-compose ps
            exit 1
        fi
    else
        if docker compose ps | grep -q "Up"; then
            print_success "All containers are running"
        else
            print_error "Some containers are not running"
            docker compose ps
            exit 1
        fi
    fi
}

# Function to display usage information
show_usage() {
    echo "CSSD Medical Distribution System - Auto Setup"
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --help          Show this help message"
    echo "  --skip-frontend Skip frontend setup"
    echo "  --skip-mobile   Skip mobile setup"
    echo "  --dev           Development mode (keep debug enabled)"
    echo ""
    echo "Examples:"
    echo "  $0                           # Full setup"
    echo "  $0 --skip-mobile            # Skip mobile setup"
    echo "  $0 --dev                    # Development setup"
    echo ""
}

# Function to cleanup on error
cleanup() {
    print_error "Setup failed! Cleaning up..."
    if command_exists docker-compose; then
        docker-compose down >/dev/null 2>&1 || true
    else
        docker compose down >/dev/null 2>&1 || true
    fi
    exit 1
}

# Parse command line arguments
SKIP_FRONTEND=false
SKIP_MOBILE=false
DEV_MODE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --help)
            show_usage
            exit 0
            ;;
        --skip-frontend)
            SKIP_FRONTEND=true
            shift
            ;;
        --skip-mobile)
            SKIP_MOBILE=true
            shift
            ;;
        --dev)
            DEV_MODE=true
            shift
            ;;
        *)
            print_error "Unknown option: $1"
            show_usage
            exit 1
            ;;
    esac
done

# Trap errors and cleanup
trap cleanup ERR

# Main setup process
main() {
    echo ""
    echo "=================================================="
    echo "  CSSD Medical Distribution System - Auto Setup"
    echo "=================================================="
    echo ""

    # Check requirements
    check_requirements

    # Setup environment
    setup_environment

    # Start containers
    start_containers

    # Setup Laravel
    setup_laravel

    # Setup frontend (if not skipped)
    if [ "$SKIP_FRONTEND" = false ]; then
        setup_frontend
    else
        print_warning "Skipping frontend setup as requested"
    fi

    # Setup mobile (if not skipped)
    if [ "$SKIP_MOBILE" = false ]; then
        setup_mobile
    else
        print_warning "Skipping mobile setup as requested"
    fi

    # Run verification
    verify_setup

    echo ""
    echo "=================================================="
    print_success "CSSD System Setup Completed Successfully!"
    echo "=================================================="
    echo ""
    echo "Access URLs:"
    echo "  🌐 Web Admin:    http://localhost:8000"
    echo "  🔌 API:          http://localhost:8000/api"
    echo "  🗄️  phpMyAdmin:   http://localhost:8080"
    echo "  🗃️  MySQL:        localhost:3307"
    echo ""
    echo "Default Admin Credentials:"
    echo "  Username: admin@cssd.local"
    echo "  Password: password"
    echo ""
    echo "Next Steps:"
    echo "  1. Access the web admin interface"
    echo "  2. Create hospital units and instruments"
    echo "  3. Set up user accounts for staff"
    echo "  4. Test QR code scanning workflows"
    echo ""
    echo "For mobile development:"
    echo "  cd mobile && ionic serve"
    echo ""
    echo "=================================================="
    echo ""
}

# Run main function
main "$@"
