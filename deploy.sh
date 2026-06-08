#!/bin/bash

###############################################################################
# RBM Schedule - Automated Deployment Script
# 
# Script ini membantu proses deployment aplikasi RBM Schedule dengan Docker
# Usage: ./deploy.sh [command]
# 
# Commands:
#   setup     - First time setup (create .env, directories)
#   start     - Start containers
#   stop      - Stop containers
#   restart   - Restart containers
#   logs      - Show logs
#   status    - Show container status
#   backup    - Backup database
#   update    - Update and rebuild
#   clean     - Remove containers and volumes (⚠️  DANGER!)
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_info() {
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

check_docker() {
    if ! command -v docker &> /dev/null; then
        print_error "Docker tidak terinstall. Install Docker terlebih dahulu."
        exit 1
    fi
    
    if ! docker compose version &> /dev/null; then
        print_error "Docker Compose tidak terinstall. Install Docker Compose terlebih dahulu."
        exit 1
    fi
    
    print_success "Docker dan Docker Compose sudah terinstall"
}

setup() {
    print_info "Starting setup process..."
    
    check_docker
    
    # Create .env if not exists
    if [ ! -f .env ]; then
        print_warning ".env file tidak ditemukan, membuat dari template..."
        cp .env.docker .env
        print_success ".env file dibuat"
        print_warning "⚠️  PENTING: Edit file .env dan ganti semua password!"
        print_warning "Jalankan: nano .env"
    else
        print_info ".env file sudah ada"
    fi
    
    # Create directories
    print_info "Membuat direktori yang diperlukan..."
    mkdir -p logs
    mkdir -p sessions
    mkdir -p backup
    
    chmod 775 logs sessions backup
    print_success "Direktori berhasil dibuat"
    
    # Check port availability
    print_info "Checking port availability..."
    source .env
    
    if netstat -tuln 2>/dev/null | grep -q ":${WEB_PORT:-8090} "; then
        print_warning "Port ${WEB_PORT:-8090} sudah digunakan! Ganti WEB_PORT di .env"
    else
        print_success "Port ${WEB_PORT:-8090} tersedia"
    fi
    
    print_success "Setup selesai!"
    print_info "Langkah selanjutnya:"
    echo "  1. Edit .env: nano .env"
    echo "  2. Ganti semua password"
    echo "  3. Jalankan: ./deploy.sh start"
}

start() {
    print_info "Starting containers..."
    check_docker
    
    if [ ! -f .env ]; then
        print_error ".env file tidak ditemukan. Jalankan: ./deploy.sh setup"
        exit 1
    fi
    
    # Build and start
    docker compose build
    docker compose up -d
    
    print_success "Containers started!"
    
    # Wait for health check
    print_info "Waiting for containers to be healthy..."
    sleep 10
    
    docker compose ps
    
    source .env
    print_success "Aplikasi sudah berjalan!"
    echo ""
    echo "📝 URL Akses: http://localhost:${WEB_PORT:-8090}"
    echo "📊 Lihat logs: ./deploy.sh logs"
    echo "📊 Status: ./deploy.sh status"
}

stop() {
    print_info "Stopping containers..."
    docker compose stop
    print_success "Containers stopped"
}

restart() {
    print_info "Restarting containers..."
    docker compose restart
    print_success "Containers restarted"
    docker compose ps
}

logs() {
    print_info "Showing logs (Ctrl+C to exit)..."
    docker compose logs -f --tail=100
}

status() {
    print_info "Container status:"
    docker compose ps
    echo ""
    print_info "Resource usage:"
    docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}"
}

backup() {
    print_info "Creating database backup..."
    
    BACKUP_FILE="backup/backup_$(date +%Y%m%d_%H%M%S).sql"
    
    docker compose exec -T rbm-db mysqldump -u root -p"${DB_ROOT_PASS}" rbm_schedule > "${BACKUP_FILE}"
    
    print_success "Backup created: ${BACKUP_FILE}"
    
    # Show backup size
    du -h "${BACKUP_FILE}"
}

update() {
    print_info "Updating application..."
    
    # Pull latest code (if using git)
    if [ -d .git ]; then
        print_info "Pulling latest code..."
        git pull
    fi
    
    # Backup database before update
    print_warning "Creating backup before update..."
    backup
    
    # Rebuild and restart
    print_info "Rebuilding containers..."
    docker compose build
    docker compose up -d
    
    print_success "Update completed!"
    docker compose ps
}

clean() {
    print_warning "⚠️  WARNING: This will remove all containers and volumes!"
    print_warning "All data will be LOST unless you have backups!"
    read -p "Are you sure? (yes/no): " confirm
    
    if [ "$confirm" = "yes" ]; then
        print_info "Removing containers and volumes..."
        docker compose down -v
        print_success "Cleanup completed"
    else
        print_info "Cleanup cancelled"
    fi
}

# Main script
case "${1:-}" in
    setup)
        setup
        ;;
    start)
        start
        ;;
    stop)
        stop
        ;;
    restart)
        restart
        ;;
    logs)
        logs
        ;;
    status)
        status
        ;;
    backup)
        backup
        ;;
    update)
        update
        ;;
    clean)
        clean
        ;;
    *)
        echo "RBM Schedule - Deployment Script"
        echo ""
        echo "Usage: ./deploy.sh [command]"
        echo ""
        echo "Commands:"
        echo "  setup     - First time setup (create .env, directories)"
        echo "  start     - Start containers"
        echo "  stop      - Stop containers"
        echo "  restart   - Restart containers"
        echo "  logs      - Show logs"
        echo "  status    - Show container status"
        echo "  backup    - Backup database"
        echo "  update    - Update and rebuild"
        echo "  clean     - Remove containers and volumes (⚠️  DANGER!)"
        echo ""
        echo "Example:"
        echo "  ./deploy.sh setup    # First time setup"
        echo "  ./deploy.sh start    # Start application"
        echo "  ./deploy.sh logs     # View logs"
        exit 1
        ;;
esac
