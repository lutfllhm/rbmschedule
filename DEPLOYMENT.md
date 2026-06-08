# 🚀 Panduan Deployment RBM Schedule

Panduan lengkap untuk deploy aplikasi RBM Schedule ke production server (VPS/Cloud).

## 📋 Daftar Isi

1. [Persiapan](#persiapan)
2. [Deployment dengan Docker](#deployment-dengan-docker)
3. [Deployment Manual (Non-Docker)](#deployment-manual-non-docker)
4. [Backup & Restore Database](#backup--restore-database)
5. [SSL Certificate](#ssl-certificate)
6. [Monitoring & Maintenance](#monitoring--maintenance)
7. [Troubleshooting](#troubleshooting)

---

## 🎯 Persiapan

### Persyaratan Server

**Minimum Requirements:**
- OS: Ubuntu 20.04+ / CentOS 7+ / Debian 10+
- CPU: 1 Core
- RAM: 1 GB
- Storage: 10 GB
- PHP: 8.1+
- MySQL: 8.0+
- Apache/Nginx

**Recommended:**
- CPU: 2 Cores
- RAM: 2 GB
- Storage: 20 GB SSD

### Tools yang Diperlukan

```bash
# Update sistem
sudo apt update && sudo apt upgrade -y

# Install tools dasar
sudo apt install -y git curl wget unzip nano
```

---

## 🐳 Deployment dengan Docker

### 1. Install Docker & Docker Compose

```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verifikasi instalasi
docker --version
docker-compose --version

# Tambahkan user ke grup docker (opsional)
sudo usermod -aG docker $USER
newgrp docker
```

### 2. Clone Repository

```bash
# Clone project
git clone https://github.com/your-username/rbmschedule.git
cd rbmschedule

# Atau upload via SCP/FTP
```

### 3. Konfigurasi Environment

```bash
# Copy file environment
cp .env.example .env

# Edit konfigurasi
nano .env
```

**Isi file .env:**

```env
# Database Configuration
DB_HOST=db
DB_USER=rbm_user
DB_PASS=RBM_SecureP@ss2024!
DB_NAME=rbm_schedule
DEBUG_MODE=false

# MySQL Root Password
MYSQL_ROOT_PASSWORD=Root_SecureP@ss2024!
```

> ⚠️ **PENTING:** Ganti password dengan password yang kuat di production!

### 4. Setup Database SQL

Pastikan file `database.sql` ada di root folder untuk inisialisasi database otomatis.

### 5. Deploy dengan Docker Compose

```bash
# Build dan jalankan container
docker-compose up -d --build

# Cek status container
docker-compose ps

# Lihat logs
docker-compose logs -f app
docker-compose logs -f db
```

### 6. Verifikasi Deployment

```bash
# Cek container berjalan
docker ps

# Test akses aplikasi
curl http://localhost:8091

# Test koneksi database
docker exec -it rbmschedule_db mysql -u rbm_user -p rbm_schedule
```

### 7. Akses Aplikasi

Buka browser dan akses:
```
http://your-server-ip:8091
```

Login dengan kredensial default:
- Username: `admin`
- Password: `admin123`

> ⚠️ Segera ubah password setelah login pertama!

---

## 🔧 Deployment Manual (Non-Docker)

### 1. Install LAMP Stack

#### Ubuntu/Debian:

```bash
# Install Apache
sudo apt install -y apache2

# Install MySQL
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Install PHP 8.1
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y php8.1 php8.1-cli php8.1-mysql php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip

# Install ekstensi tambahan
sudo apt install -y libapache2-mod-php8.1

# Enable Apache modules
sudo a2enmod rewrite
sudo a2enmod headers
sudo systemctl restart apache2
```

#### CentOS/RHEL:

```bash
# Install Apache
sudo yum install -y httpd

# Install MySQL 8
sudo yum install -y mysql-server
sudo systemctl start mysqld
sudo mysql_secure_installation

# Install PHP 8.1
sudo yum install -y epel-release
sudo yum install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm
sudo yum module reset php
sudo yum module enable php:remi-8.1
sudo yum install -y php php-cli php-mysqlnd php-xml php-mbstring php-curl php-zip

# Start services
sudo systemctl start httpd
sudo systemctl enable httpd
sudo systemctl enable mysqld
```

### 2. Setup Aplikasi

```bash
# Buat direktori
sudo mkdir -p /var/www/rbmschedule
cd /var/www/rbmschedule

# Clone/Upload project
sudo git clone https://github.com/your-username/rbmschedule.git .

# Set ownership
sudo chown -R www-data:www-data /var/www/rbmschedule
# Atau untuk CentOS:
# sudo chown -R apache:apache /var/www/rbmschedule

# Set permissions
sudo find /var/www/rbmschedule -type d -exec chmod 755 {} \;
sudo find /var/www/rbmschedule -type f -exec chmod 644 {} \;
sudo chmod -R 755 /var/www/rbmschedule/logs
```

### 3. Konfigurasi Database

```bash
# Login ke MySQL
sudo mysql -u root -p

# Buat database dan user
CREATE DATABASE rbm_schedule CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'rbm_user'@'localhost' IDENTIFIED BY 'RBM_SecureP@ss2024!';
GRANT ALL PRIVILEGES ON rbm_schedule.* TO 'rbm_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import database
mysql -u rbm_user -p rbm_schedule < /var/www/rbmschedule/database.sql
```

### 4. Konfigurasi Apache Virtual Host

```bash
# Buat file vhost
sudo nano /etc/apache2/sites-available/rbmschedule.conf
```

**Isi file konfigurasi:**

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    ServerAdmin admin@your-domain.com
    
    DocumentRoot /var/www/rbmschedule
    
    <Directory /var/www/rbmschedule>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/rbmschedule-error.log
    CustomLog ${APACHE_LOG_DIR}/rbmschedule-access.log combined
    
    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</VirtualHost>
```

```bash
# Enable site dan restart Apache
sudo a2ensite rbmschedule.conf
sudo a2dissite 000-default.conf
sudo systemctl restart apache2

# Untuk CentOS/RHEL:
# sudo systemctl restart httpd
```

### 5. Konfigurasi PHP

```bash
# Edit php.ini
sudo nano /etc/php/8.1/apache2/php.ini
```

**Setting yang direkomendasikan:**

```ini
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
date.timezone = Asia/Jakarta
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
```

```bash
# Buat folder log PHP
sudo mkdir -p /var/log/php
sudo chown www-data:www-data /var/log/php

# Restart Apache
sudo systemctl restart apache2
```

### 6. Update Konfigurasi Database

```bash
# Edit file konfigurasi
nano /var/www/rbmschedule/config/database.php
```

**Update kredensial:**

```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'rbm_user');
define('DB_PASS', 'RBM_SecureP@ss2024!');
define('DB_NAME', 'rbm_schedule');
define('DEBUG_MODE', false);
```

---

## 💾 Backup & Restore Database

### Backup Database

#### Script Backup Otomatis

Buat file `scripts/backup_db.sh`:

```bash
#!/bin/bash

# Konfigurasi
DB_USER="rbm_user"
DB_PASS="RBM_SecureP@ss2024!"
DB_NAME="rbm_schedule"
BACKUP_DIR="/var/backups/rbmschedule"
DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="$BACKUP_DIR/backup_${DATE}.sql"
RETENTION_DAYS=7

# Buat direktori backup jika belum ada
mkdir -p $BACKUP_DIR

# Backup database
echo "Starting backup at $(date)"
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_FILE

# Compress backup
gzip $BACKUP_FILE
echo "Backup completed: ${BACKUP_FILE}.gz"

# Hapus backup lama (lebih dari RETENTION_DAYS)
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +$RETENTION_DAYS -delete
echo "Old backups cleaned up"

# Kirim notifikasi (opsional)
# mail -s "Database Backup Success" admin@example.com <<< "Backup completed: ${BACKUP_FILE}.gz"
```

```bash
# Buat executable
chmod +x scripts/backup_db.sh

# Test backup
./scripts/backup_db.sh
```

#### Backup Manual

```bash
# Backup database
mysqldump -u rbm_user -p rbm_schedule > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup dengan kompresi
mysqldump -u rbm_user -p rbm_schedule | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz

# Backup dengan Docker
docker exec rbmschedule_db mysqldump -u rbm_user -prbm_password rbm_schedule > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restore Database

#### Script Restore

Buat file `scripts/restore_db.sh`:

```bash
#!/bin/bash

# Konfigurasi
DB_USER="rbm_user"
DB_PASS="RBM_SecureP@ss2024!"
DB_NAME="rbm_schedule"
DB_HOST="localhost"

# Cek argument
if [ -z "$1" ]; then
    echo "Usage: $0 <backup_file.sql atau backup_file.sql.gz>"
    echo "Example: $0 backup_20240605_062551.sql"
    echo "Example: $0 backup_20240605_062551.sql.gz"
    exit 1
fi

BACKUP_FILE=$1

# Cek file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo "Error: File $BACKUP_FILE tidak ditemukan!"
    exit 1
fi

# Backup database saat ini sebelum restore
echo "Creating safety backup of current database..."
SAFETY_BACKUP="safety_backup_$(date +%Y%m%d_%H%M%S).sql"
mysqldump -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME > $SAFETY_BACKUP
echo "Safety backup created: $SAFETY_BACKUP"

# Konfirmasi restore
echo ""
echo "⚠️  WARNING: This will replace all data in database '$DB_NAME'"
echo "Backup file: $BACKUP_FILE"
read -p "Are you sure you want to continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Restore cancelled."
    exit 0
fi

echo ""
echo "Starting restore process..."

# Drop dan create database baru
echo "Dropping existing database..."
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS -e "DROP DATABASE IF EXISTS $DB_NAME;"
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Restore berdasarkan tipe file
if [[ $BACKUP_FILE == *.gz ]]; then
    echo "Restoring from compressed backup..."
    gunzip < $BACKUP_FILE | mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME
else
    echo "Restoring from SQL file..."
    mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME < $BACKUP_FILE
fi

# Cek status
if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Database restore completed successfully!"
    echo "Safety backup saved at: $SAFETY_BACKUP"
else
    echo ""
    echo "❌ Error: Database restore failed!"
    echo "Restoring from safety backup..."
    mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME < $SAFETY_BACKUP
    echo "Original database restored from safety backup."
    exit 1
fi

# Verifikasi
echo ""
echo "Verifying restore..."
TABLES=$(mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "SHOW TABLES;" | wc -l)
echo "Total tables found: $((TABLES - 1))"

USERS=$(mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "SELECT COUNT(*) FROM users;" | tail -1)
echo "Total users: $USERS"

SCHEDULES=$(mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "SELECT COUNT(*) FROM schedules;" | tail -1)
echo "Total schedules: $SCHEDULES"

echo ""
echo "✅ Restore process completed!"
```

```bash
# Buat executable
chmod +x scripts/restore_db.sh
```

#### Restore Manual

```bash
# Restore dari file SQL
mysql -u rbm_user -p rbm_schedule < backup_20240605_062551.sql

# Restore dari file compressed
gunzip < backup_20240605_062551.sql.gz | mysql -u rbm_user -p rbm_schedule

# Restore dengan Docker
docker exec -i rbmschedule_db mysql -u rbm_user -prbm_password rbm_schedule < backup_20240605_062551.sql

# Atau copy file ke container dulu
docker cp backup_20240605_062551.sql rbmschedule_db:/tmp/
docker exec rbmschedule_db mysql -u rbm_user -prbm_password rbm_schedule < /tmp/backup_20240605_062551.sql
```

### Automated Backup dengan Cron

```bash
# Edit crontab
crontab -e
```

**Tambahkan jadwal backup:**

```bash
# Backup setiap hari jam 2 pagi
0 2 * * * /var/www/rbmschedule/scripts/backup_db.sh

# Backup setiap 6 jam
0 */6 * * * /var/www/rbmschedule/scripts/backup_db.sh

# Backup setiap hari jam 23:00
0 23 * * * /var/www/rbmschedule/scripts/backup_db.sh
```

### Backup ke Remote Server (rsync)

Buat file `scripts/backup_remote.sh`:

```bash
#!/bin/bash

BACKUP_DIR="/var/backups/rbmschedule"
REMOTE_USER="backup-user"
REMOTE_HOST="backup-server.com"
REMOTE_DIR="/backups/rbmschedule"

# Jalankan backup lokal dulu
./scripts/backup_db.sh

# Sync ke remote server
rsync -avz --delete $BACKUP_DIR/ $REMOTE_USER@$REMOTE_HOST:$REMOTE_DIR/

echo "Backup synced to remote server"
```

---

## 🔒 SSL Certificate

### Dengan Let's Encrypt (Certbot)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-apache

# Dapatkan SSL certificate
sudo certbot --apache -d your-domain.com -d www.your-domain.com

# Test auto-renewal
sudo certbot renew --dry-run

# Certbot akan otomatis update konfigurasi Apache
```

### Manual SSL Configuration

Jika sudah punya certificate:

```bash
# Edit vhost
sudo nano /etc/apache2/sites-available/rbmschedule-ssl.conf
```

```apache
<VirtualHost *:443>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    
    DocumentRoot /var/www/rbmschedule
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/your-cert.crt
    SSLCertificateKeyFile /etc/ssl/private/your-key.key
    SSLCertificateChainFile /etc/ssl/certs/your-chain.crt
    
    <Directory /var/www/rbmschedule>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/rbmschedule-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/rbmschedule-ssl-access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    Redirect permanent / https://your-domain.com/
</VirtualHost>
```

```bash
# Enable SSL module dan site
sudo a2enmod ssl
sudo a2ensite rbmschedule-ssl.conf
sudo systemctl restart apache2
```

---

## 📊 Monitoring & Maintenance

### 1. Setup Monitoring Script

Buat file `scripts/monitor.sh`:

```bash
#!/bin/bash

# Cek service status
echo "=== Service Status ==="
systemctl status apache2 | grep Active
systemctl status mysql | grep Active

# Cek disk usage
echo ""
echo "=== Disk Usage ==="
df -h | grep -E "Filesystem|/dev/"

# Cek memory
echo ""
echo "=== Memory Usage ==="
free -h

# Cek database size
echo ""
echo "=== Database Size ==="
mysql -u rbm_user -pRBM_SecureP@ss2024! -e "
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables
WHERE table_schema = 'rbm_schedule'
GROUP BY table_schema;
"

# Cek error logs
echo ""
echo "=== Recent Errors (Last 10) ==="
tail -n 10 /var/www/rbmschedule/logs/error.log
```

### 2. Log Rotation

```bash
# Buat konfigurasi logrotate
sudo nano /etc/logrotate.d/rbmschedule
```

```
/var/www/rbmschedule/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    missingok
    create 0640 www-data www-data
    sharedscripts
    postrotate
        /usr/sbin/apachectl graceful > /dev/null 2>&1 || true
    endscript
}
```

### 3. Database Optimization

Buat file `scripts/optimize_db.sh`:

```bash
#!/bin/bash

DB_USER="rbm_user"
DB_PASS="RBM_SecureP@ss2024!"
DB_NAME="rbm_schedule"

echo "Optimizing database tables..."

mysql -u $DB_USER -p$DB_PASS $DB_NAME -e "
OPTIMIZE TABLE schedules;
OPTIMIZE TABLE users;
OPTIMIZE TABLE audit_logs;
"

echo "Database optimization completed!"
```

### 4. Update Aplikasi

```bash
# Pull update terbaru
cd /var/www/rbmschedule
sudo git pull origin main

# Backup database sebelum update
./scripts/backup_db.sh

# Jalankan migration jika ada
mysql -u rbm_user -p rbm_schedule < update_database.php

# Clear cache jika ada
sudo rm -rf logs/cache/*

# Restart Apache
sudo systemctl restart apache2
```

---

## 🔍 Troubleshooting

### Problem: Database Connection Failed

```bash
# Cek MySQL service
sudo systemctl status mysql

# Cek kredensial database
mysql -u rbm_user -p rbm_schedule

# Cek error log
tail -f /var/www/rbmschedule/logs/error.log
tail -f /var/log/mysql/error.log
```

### Problem: Permission Denied

```bash
# Fix permission
sudo chown -R www-data:www-data /var/www/rbmschedule
sudo chmod -R 755 /var/www/rbmschedule/logs
```

### Problem: 500 Internal Server Error

```bash
# Cek Apache error log
sudo tail -f /var/log/apache2/rbmschedule-error.log

# Cek PHP error log
sudo tail -f /var/log/php/error.log

# Enable display_errors sementara untuk debugging
sudo nano /etc/php/8.1/apache2/php.ini
# Set: display_errors = On
sudo systemctl restart apache2
```

### Problem: Docker Container Failed

```bash
# Cek logs
docker-compose logs app
docker-compose logs db

# Restart container
docker-compose restart

# Rebuild container
docker-compose down
docker-compose up -d --build

# Cek resource
docker stats
```

### Problem: Real-time Sync Tidak Berfungsi

```bash
# Cek SSE endpoint
curl http://localhost/api/updates_stream.php

# Cek browser console untuk error
# Cek file permissions
sudo chmod 755 /var/www/rbmschedule/api/updates_stream.php
```

---

## 📝 Checklist Deployment

### Pre-Deployment

- [ ] Backup database production (jika ada)
- [ ] Test aplikasi di staging environment
- [ ] Persiapkan rollback plan
- [ ] Siapkan kredensial database yang aman
- [ ] Domain sudah pointing ke server
- [ ] SSL certificate sudah siap

### During Deployment

- [ ] Clone/upload source code
- [ ] Install dependencies
- [ ] Setup database
- [ ] Import database.sql
- [ ] Konfigurasi environment variables
- [ ] Set proper permissions
- [ ] Setup virtual host
- [ ] Enable SSL
- [ ] Test basic functionality

### Post-Deployment

- [ ] Ubah password default
- [ ] Test semua fitur utama
- [ ] Setup automated backup
- [ ] Setup monitoring
- [ ] Setup log rotation
- [ ] Dokumentasi credentials
- [ ] Inform team

---

## 📞 Support

Jika ada masalah saat deployment:

1. Cek dokumentasi troubleshooting di atas
2. Review logs di `/var/www/rbmschedule/logs/`
3. Cek Apache/Nginx error logs
4. Hubungi tim support

---

**Dokumentasi ini dibuat untuk RBM Schedule v1.0.0**  
**Last updated: 2024**
