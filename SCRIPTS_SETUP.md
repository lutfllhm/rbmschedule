# 📜 Setup Deployment Scripts

File ini berisi instruksi untuk membuat deployment scripts di server Linux.

## ⚠️ Catatan Penting

File `.sh` (bash scripts) yang dibuat di Windows mungkin tidak compatible dengan Linux. Ikuti instruksi berikut untuk membuat scripts langsung di server Linux.

## 🚀 Cara Membuat Scripts

Setelah upload project ke server, jalankan perintah berikut untuk membuat semua scripts:

### 1. Masuk ke Folder Project

```bash
cd /opt/rbmschedule  # atau path project Anda
```

### 2. Create Deploy Script

```bash
cat > deploy.sh << 'EOF'
#!/bin/bash
# RBM Schedule - Deployment Script

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}========================================${NC}"
echo -e "${CYAN}  RBM Schedule - Deployment${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""

# Check Docker
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker not installed!${NC}"
    exit 1
fi

# Check .env
if [ ! -f .env ]; then
    echo -e "${YELLOW}⚠️  Creating .env from .env.example${NC}"
    cp .env.example .env
    echo -e "${RED}Edit .env and set your passwords!${NC}"
    exit 1
fi

# Deploy
echo -e "${YELLOW}🚀 Deploying application...${NC}"
docker-compose down
docker-compose up -d --build

echo ""
echo -e "${GREEN}✅ Deployment complete!${NC}"
echo "Access: http://$(hostname -I | awk '{print $1}'):${WEB_PORT:-8090}"
EOF

chmod +x deploy.sh
```

### 3. Create Backup Script

```bash
cat > backup.sh << 'EOF'
#!/bin/bash
# RBM Schedule - Backup Script

if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

DB_USER="${DB_USER:-rbm_user}"
DB_PASS="${DB_PASS:-rbm_password}"
DB_NAME="${DB_NAME:-rbm_schedule}"
BACKUP_DIR="./backup"
DATE=$(date +"%Y%m%d_%H%M%S")

mkdir -p $BACKUP_DIR

echo "🔄 Creating backup..."
docker exec rbmschedule_db mysqldump -u $DB_USER -p$DB_PASS $DB_NAME 2>/dev/null | gzip > $BACKUP_DIR/backup_${DATE}.sql.gz

if [ $? -eq 0 ]; then
    echo "✅ Backup created: $BACKUP_DIR/backup_${DATE}.sql.gz"
    find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +7 -delete
    echo "✅ Old backups cleaned"
else
    echo "❌ Backup failed!"
    exit 1
fi
EOF

chmod +x backup.sh
```

### 4. Create Restore Script

```bash
cat > restore.sh << 'EOF'
#!/bin/bash
# RBM Schedule - Restore Script

if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

DB_USER="${DB_USER:-rbm_user}"
DB_PASS="${DB_PASS:-rbm_password}"
DB_NAME="${DB_NAME:-rbm_schedule}"

if [ -z "$1" ]; then
    echo "Usage: ./restore.sh <backup_file>"
    echo ""
    echo "Available backups:"
    ls -lh backup/backup_*.sql* 2>/dev/null
    exit 1
fi

BACKUP_FILE=$1

if [ ! -f "$BACKUP_FILE" ]; then
    echo "❌ File not found: $BACKUP_FILE"
    exit 1
fi

echo "⚠️  This will replace all data in database '$DB_NAME'"
read -p "Continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Cancelled."
    exit 0
fi

echo "🔄 Creating safety backup..."
docker exec rbmschedule_db mysqldump -u $DB_USER -p$DB_PASS $DB_NAME 2>/dev/null | gzip > backup/safety_backup_$(date +%Y%m%d_%H%M%S).sql.gz

echo "🔄 Restoring database..."
if [[ $BACKUP_FILE == *.gz ]]; then
    gunzip < $BACKUP_FILE | docker exec -i rbmschedule_db mysql -u $DB_USER -p$DB_PASS $DB_NAME 2>/dev/null
else
    docker exec -i rbmschedule_db mysql -u $DB_USER -p$DB_PASS $DB_NAME < $BACKUP_FILE 2>/dev/null
fi

if [ $? -eq 0 ]; then
    echo "✅ Restore successful!"
else
    echo "❌ Restore failed!"
    exit 1
fi
EOF

chmod +x restore.sh
```

### 5. Create Status Script

```bash
cat > status.sh << 'EOF'
#!/bin/bash
# RBM Schedule - Status Script

if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

echo "========================================="
echo "  RBM Schedule - Status"
echo "========================================="
echo ""

echo "📦 Container Status:"
docker-compose ps
echo ""

echo "💻 Resource Usage:"
docker stats --no-stream rbmschedule_app rbmschedule_db 2>/dev/null
echo ""

echo "🌐 Ports:"
echo "Web: ${WEB_PORT:-8090}"
echo "DB: ${DB_PORT_EXTERNAL:-3308}"
echo ""

echo "🗄️  Database Info:"
DB_USER=${DB_USER:-rbm_user}
DB_PASS=${DB_PASS:-rbm_password}
DB_NAME=${DB_NAME:-rbm_schedule}

docker exec rbmschedule_db mysql -u $DB_USER -p$DB_PASS -e "
SELECT COUNT(*) as 'Users' FROM $DB_NAME.users;
SELECT COUNT(*) as 'Schedules' FROM $DB_NAME.schedules;
" 2>/dev/null

echo ""
echo "💾 Backups:"
ls -lh backup/backup_*.sql* 2>/dev/null | tail -5
EOF

chmod +x status.sh
```

### 6. Create Test Script

```bash
cat > test-deployment.sh << 'EOF'
#!/bin/bash
# RBM Schedule - Test Script

if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

WEB_PORT=${WEB_PORT:-8090}
PASSED=0
FAILED=0

echo "Testing deployment..."
echo ""

# Test containers
if docker ps | grep -q rbmschedule_app; then
    echo "✅ App container running"
    ((PASSED++))
else
    echo "❌ App container not running"
    ((FAILED++))
fi

if docker ps | grep -q rbmschedule_db; then
    echo "✅ DB container running"
    ((PASSED++))
else
    echo "❌ DB container not running"
    ((FAILED++))
fi

# Test web
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:$WEB_PORT 2>/dev/null)
if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    echo "✅ Web server responding (HTTP $HTTP_CODE)"
    ((PASSED++))
else
    echo "❌ Web server not responding (HTTP $HTTP_CODE)"
    ((FAILED++))
fi

echo ""
echo "Tests: $((PASSED + FAILED))"
echo "Passed: $PASSED"
echo "Failed: $FAILED"

if [ $FAILED -eq 0 ]; then
    echo ""
    echo "✅ All tests passed!"
    exit 0
else
    echo ""
    echo "❌ Some tests failed!"
    exit 1
fi
EOF

chmod +x test-deployment.sh
```

## ✅ Verify Scripts Created

```bash
ls -lh *.sh
```

Output yang diharapkan:
```
-rwxr-xr-x 1 user user  deploy.sh
-rwxr-xr-x 1 user user  backup.sh
-rwxr-xr-x 1 user user  restore.sh
-rwxr-xr-x 1 user user  status.sh
-rwxr-xr-x 1 user user  test-deployment.sh
```

## 🚀 Usage

Setelah scripts dibuat:

```bash
# Deploy aplikasi
./deploy.sh

# Check status
./status.sh

# Backup database
./backup.sh

# Restore database
./restore.sh backup/backup_20260605_120000.sql.gz

# Test deployment
./test-deployment.sh
```

## 💡 Alternative: Download from GitHub

Jika scripts sudah di-commit ke GitHub:

```bash
# Download individual script
wget https://raw.githubusercontent.com/username/rbmschedule/main/deploy.sh
chmod +x deploy.sh

# Or clone entire repo
git clone https://github.com/username/rbmschedule.git
cd rbmschedule
chmod +x *.sh
```

## 🔧 Troubleshooting

### Script Permission Denied

```bash
chmod +x deploy.sh backup.sh restore.sh status.sh test-deployment.sh
```

### Line Ending Issues

Jika script error karena line endings (^M), fix dengan:

```bash
sed -i 's/\r$//' deploy.sh
sed -i 's/\r$//' backup.sh
sed -i 's/\r$//' restore.sh
sed -i 's/\r$//' status.sh
sed -i 's/\r$//' test-deployment.sh
```

## 📝 Notes

- Scripts ini sudah compatible dengan Ubuntu/Debian/CentOS
- Colored output mungkin tidak bekerja di semua terminal
- Test di staging environment dulu sebelum production

---

**Setelah membuat semua scripts, lanjutkan ke [QUICK_START.md](QUICK_START.md) untuk deployment!**
