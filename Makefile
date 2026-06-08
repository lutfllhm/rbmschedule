# ==============================================
# RBM Schedule - Makefile
# Simplified deployment commands
# ==============================================

.PHONY: help deploy start stop restart status logs backup restore clean

# Default target
help:
	@echo "╔════════════════════════════════════════════╗"
	@echo "║   RBM Schedule - Available Commands        ║"
	@echo "╚════════════════════════════════════════════╝"
	@echo ""
	@echo "Deployment:"
	@echo "  make deploy      - Deploy aplikasi (build & start)"
	@echo "  make start       - Start containers"
	@echo "  make stop        - Stop containers"
	@echo "  make restart     - Restart containers"
	@echo ""
	@echo "Monitoring:"
	@echo "  make status      - Lihat status aplikasi"
	@echo "  make logs        - Lihat logs (Ctrl+C untuk keluar)"
	@echo "  make logs-app    - Lihat logs aplikasi saja"
	@echo "  make logs-db     - Lihat logs database saja"
	@echo ""
	@echo "Database:"
	@echo "  make backup      - Backup database"
	@echo "  make restore     - Restore database (interaktif)"
	@echo "  make db-connect  - Connect ke database MySQL"
	@echo ""
	@echo "Maintenance:"
	@echo "  make clean       - Bersihkan cache & temp files"
	@echo "  make rebuild     - Rebuild containers dari awal"
	@echo "  make update      - Update aplikasi dari Git"
	@echo ""

# Deploy aplikasi
deploy:
	@echo "🚀 Deploying RBM Schedule..."
	@chmod +x deploy.sh
	@./deploy.sh

# Start containers
start:
	@echo "▶️  Starting containers..."
	@docker-compose up -d
	@echo "✅ Containers started!"
	@echo "Run 'make status' to check status"

# Stop containers
stop:
	@echo "⏸️  Stopping containers..."
	@docker-compose down
	@echo "✅ Containers stopped!"

# Restart containers
restart:
	@echo "🔄 Restarting containers..."
	@docker-compose restart
	@echo "✅ Containers restarted!"

# Rebuild containers
rebuild:
	@echo "🔨 Rebuilding containers..."
	@docker-compose down
	@docker-compose up -d --build
	@echo "✅ Containers rebuilt!"

# Check status
status:
	@chmod +x status.sh
	@./status.sh

# View logs
logs:
	@echo "📝 Viewing logs (Ctrl+C to exit)..."
	@docker-compose logs -f

# View app logs only
logs-app:
	@echo "📝 Viewing application logs (Ctrl+C to exit)..."
	@docker-compose logs -f app

# View database logs only
logs-db:
	@echo "📝 Viewing database logs (Ctrl+C to exit)..."
	@docker-compose logs -f rbm-db

# Backup database
backup:
	@echo "💾 Creating database backup..."
	@chmod +x backup.sh
	@./backup.sh

# Restore database
restore:
	@echo "🔄 Restore database..."
	@echo "Available backups:"
	@ls -lh backup/backup_*.sql* 2>/dev/null | awk '{print "  - "$$9, "("$$5")"}'
	@echo ""
	@read -p "Enter backup filename: " filename; \
	chmod +x restore.sh; \
	./restore.sh $$filename

# Connect to database
db-connect:
	@echo "🔌 Connecting to database..."
	@docker exec -it rbmschedule_db mysql -u rbm_user -p rbm_schedule

# Clean temporary files
clean:
	@echo "🧹 Cleaning temporary files..."
	@docker exec rbmschedule_app find /var/www/html/logs -name "*.log" -mtime +7 -delete 2>/dev/null || true
	@docker system prune -f
	@echo "✅ Cleanup completed!"

# Update from git
update:
	@echo "📥 Pulling latest changes..."
	@./backup.sh
	@git pull origin main
	@docker-compose restart app
	@echo "✅ Update completed!"

# Quick check
check:
	@echo "🔍 Quick health check..."
	@docker-compose ps
	@echo ""
	@curl -f http://localhost:$${WEB_PORT:-8090} > /dev/null 2>&1 && echo "✅ Web server: OK" || echo "❌ Web server: ERROR"
	@docker exec rbmschedule_db mysqladmin ping -h localhost -u rbm_user -p$${DB_PASS:-rbm_password} > /dev/null 2>&1 && echo "✅ Database: OK" || echo "❌ Database: ERROR"
