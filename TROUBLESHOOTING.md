# 🔍 Troubleshooting Guide - RBM Schedule

Panduan menyelesaikan masalah umum saat deployment dan operasional RBM Schedule.

## 📋 Daftar Isi

1. [Port Conflicts](#-port-conflicts)
2. [Database Issues](#-database-issues)
3. [Container Issues](#-container-issues)
4. [Application Errors](#-application-errors)
5. [Performance Issues](#-performance-issues)
6. [Backup & Restore Issues](#-backup--restore-issues)

---

## 🔌 Port Conflicts

### Problem: Port sudah digunakan

**Error:**
```
ERROR: for rbmschedule_app  Cannot start service app: 
Bind for 0.0.0.0:8090 failed: port is already allocated
```

**Penyebab:**
Port yang digunakan di `.env` sudah dipakai aplikasi lain.

**Solusi:**

1. **Cek port yang digunakan:**
```bash
# Lihat semua port yang listening
sudo netstat -tulpn | grep LISTEN

# Atau dengan ss
sudo ss -tulpn | grep LISTEN

# Cek port spesifik
sudo lsof -i :8090
```

2. **Ganti port di .env:**
```bash
nano .env
```

Edit:
```env
# Ganti ke port yang tersedia
WEB_PORT=8095
DB_PORT_EXTERNAL=3310
```

3. **Restart container:**
```bash
docker-compose down
docker-compose up -d
```

### Problem: Port tidak bisa diakses dari luar

**Penyebab:**
Firewall memblokir port.

**Solusi:**

```bash
# Ubuntu/Debian dengan UFW
sudo ufw allow 8090/tcp
sudo ufw reload

# CentOS/RHEL dengan firewalld
sudo firewall-cmd --permanent --add-port=8090/tcp
sudo firewall-cmd --reload

# Atau disable firewall (tidak disarankan untuk production)
sudo ufw disable
```

---

## 🗄️ Database Issues

### Problem: Database connection failed

**Error di log:**
```
Connection refused
SQLSTATE[HY000] [2002] Connection refused
```

**Solusi:**

1. **Cek container database berjalan:**
```bash
docker-compose ps

# Output harus menunjukkan rbmschedule_db dalam status "Up"
```

2. **Cek log database:**
```bash
docker-compose logs db

# Cari error seperti:
# - "Can't connect to MySQL server"
# - "Access denied"
# - "Unknown database"
```

3. **Cek kredensial di .env:**
```bash
cat .env | grep DB_
```

Pastikan `DB_HOST=rbm-db` (bukan `localhost`)

4. **Test koneksi database:**
```bash
docker exec -it rbmschedule_db mysql -u rbm_user -p
# Masukkan password dari .env (DB_PASS)
```

5. **Restart database:**
```bash
docker-compose restart rbm-db
```

### Problem: Database tidak ter-inisialisasi

**Gejala:**
- Error "Table doesn't exist"
- Database kosong

**Solusi:**

1. **Cek apakah file database.sql ada:**
```bash
ls -lh database.sql
```

2. **Import manual:**
```bash
# Copy file ke container
docker cp database.sql rbmschedule_db:/tmp/

# Import
docker exec rbmschedule_db mysql -u rbm_user -p'PASSWORD' rbm_schedule < /tmp/database.sql
```

3. **Atau rebuild container:**
```bash
docker-compose down -v  # HATI-HATI: Ini akan hapus data!
docker-compose up -d --build
```

### Problem: "Too many connections"

**Error:**
```
SQLSTATE[HY000] [1040] Too many connections
```

**Solusi:**

```bash
# Masuk ke MySQL
docker exec -it rbmschedule_db mysql -u root -p

# Tambah max connections
SET GLOBAL max_connections = 200;
FLUSH PRIVILEGES;
EXIT;

# Atau restart database
docker-compose restart rbm-db
```

---

## 📦 Container Issues

### Problem: Container terus restart

**Gejala:**
```bash
docker-compose ps
# Status menunjukkan "Restarting"
```

**Solusi:**

1. **Cek log error:**
```bash
docker-compose logs app
docker-compose logs db
```

2. **Cek health check:**
```bash
docker inspect rbmschedule_app --format='{{json .State.Health}}'
docker inspect rbmschedule_db --format='{{json .State.Health}}'
```

3. **Rebuild container:**
```bash
docker-compose down
docker-compose up -d --build
```

### Problem: Container tidak bisa start

**Error:**
```
ERROR: Container rbmschedule_app exited with code 1
```

**Solusi:**

1. **Lihat log lengkap:**
```bash
docker logs rbmschedule_app
```

2. **Cek permission:**
```bash
docker exec rbmschedule_app ls -la /var/www/html/logs
```

3. **Fix permission:**
```bash
docker exec rbmschedule_app chown -R www-data:www-data /var/www/html
docker exec rbmschedule_app chmod -R 755 /var/www/html/logs
```

4. **Cek disk space:**
```bash
df -h
docker system df
```

5. **Bersihkan disk:**
```bash
# Backup dulu!
./backup.sh

# Bersihkan docker
docker system prune -a
```

### Problem: Container lambat

**Gejala:**
- Aplikasi loading lama
- Response time tinggi

**Solusi:**

1. **Cek resource usage:**
```bash
docker stats
```

2. **Cek memory:**
```bash
free -h
```

3. **Restart container:**
```bash
docker-compose restart
```

4. **Optimize database:**
```bash
docker exec rbmschedule_db mysql -u rbm_user -p -e "
OPTIMIZE TABLE rbm_schedule.schedules;
OPTIMIZE TABLE rbm_schedule.users;
ANALYZE TABLE rbm_schedule.schedules;
ANALYZE TABLE rbm_schedule.users;
"
```

---

## 🐛 Application Errors

### Problem: 500 Internal Server Error

**Solusi:**

1. **Cek log aplikasi:**
```bash
docker-compose logs app

# Atau di folder logs
cat logs/error.log
```

2. **Cek PHP error log:**
```bash
docker exec rbmschedule_app cat /var/www/html/logs/php_error.log
```

3. **Enable debug mode (temporary):**
```bash
nano .env
# Ubah: DEBUG_MODE=true

docker-compose restart app
```

**PENTING:** Jangan lupa set kembali ke `false` di production!

4. **Cek permission:**
```bash
docker exec rbmschedule_app ls -la /var/www/html
```

### Problem: "Class not found"

**Solusi:**

```bash
# Cek apakah file class ada
docker exec rbmschedule_app ls -la /var/www/html/classes/

# Cek autoload
docker exec rbmschedule_app cat /var/www/html/config/autoload.php
```

### Problem: Session expired terus

**Solusi:**

```bash
# Buat folder session jika belum ada
docker exec rbmschedule_app mkdir -p /tmp/sessions
docker exec rbmschedule_app chmod 777 /tmp/sessions

# Restart
docker-compose restart app
```

### Problem: Real-time sync tidak jalan

**Gejala:**
- Data tidak update otomatis
- SSE tidak connect

**Solusi:**

1. **Test SSE endpoint:**
```bash
curl http://localhost:8090/api/updates_stream.php
```

2. **Cek browser console:**
- Buka Developer Tools (F12)
- Cek Network tab untuk error
- Cek Console untuk JavaScript error

3. **Cek Nginx config (jika pakai nginx):**
```nginx
location /api/updates_stream.php {
    proxy_buffering off;
    proxy_cache off;
    proxy_read_timeout 86400s;
}
```

---

## 🚀 Performance Issues

### Problem: Aplikasi lambat

**Diagnosis:**

```bash
# Cek resource
docker stats

# Cek database slow queries
docker exec rbmschedule_db mysql -u rbm_user -p -e "
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
SHOW VARIABLES LIKE 'slow_query%';
"

# Cek database size
docker exec rbmschedule_db mysql -u rbm_user -p -e "
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.tables
WHERE table_schema = 'rbm_schedule'
ORDER BY (data_length + index_length) DESC;
"
```

**Optimasi:**

1. **Optimize database:**
```bash
docker exec rbmschedule_db mysql -u rbm_user -p -e "
OPTIMIZE TABLE rbm_schedule.schedules;
OPTIMIZE TABLE rbm_schedule.users;
OPTIMIZE TABLE rbm_schedule.audit_logs;
"
```

2. **Bersihkan log lama:**
```bash
docker exec rbmschedule_app find /var/www/html/logs -name "*.log" -mtime +7 -delete
```

3. **Bersihkan audit log lama:**
```bash
docker exec rbmschedule_db mysql -u rbm_user -p -e "
DELETE FROM rbm_schedule.audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
"
```

### Problem: Database penuh

**Solusi:**

```bash
# Cek size
docker exec rbmschedule_db mysql -u rbm_user -p -e "
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables
WHERE table_schema = 'rbm_schedule'
GROUP BY table_schema;
"

# Truncate log tables (HATI-HATI!)
docker exec rbmschedule_db mysql -u rbm_user -p -e "
TRUNCATE TABLE rbm_schedule.audit_logs;
"

# Atau hapus data lama
docker exec rbmschedule_db mysql -u rbm_user -p -e "
DELETE FROM rbm_schedule.audit_logs WHERE created_at < '2024-01-01';
"
```

---

## 💾 Backup & Restore Issues

### Problem: Backup failed

**Error:**
```
mysqldump: Got error: 1045: Access denied
```

**Solusi:**

1. **Cek kredensial:**
```bash
cat .env | grep DB_
```

2. **Test manual:**
```bash
docker exec rbmschedule_db mysqldump -u rbm_user -p'PASSWORD' rbm_schedule > test_backup.sql
```

3. **Cek disk space:**
```bash
df -h
```

### Problem: Restore failed

**Solusi:**

1. **Cek file backup:**
```bash
# Cek file ada dan tidak corrupt
ls -lh backup/

# Test extract (untuk .gz)
gunzip -t backup/backup_20260605.sql.gz
```

2. **Import manual:**
```bash
# Untuk .sql
docker exec -i rbmschedule_db mysql -u rbm_user -p'PASSWORD' rbm_schedule < backup/backup_20260605.sql

# Untuk .sql.gz
gunzip < backup/backup_20260605.sql.gz | docker exec -i rbmschedule_db mysql -u rbm_user -p'PASSWORD' rbm_schedule
```

3. **Cek database setelah restore:**
```bash
docker exec rbmschedule_db mysql -u rbm_user -p -e "
USE rbm_schedule;
SHOW TABLES;
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM schedules;
"
```

---

## 🔧 Reset Complete

Jika semua cara di atas gagal dan Anda ingin reset total:

**⚠️ WARNING: Ini akan menghapus SEMUA data!**

```bash
# Backup dulu!
./backup.sh

# Stop dan hapus semua
docker-compose down -v

# Hapus image
docker rmi rbmschedule_app

# Deploy ulang
./deploy.sh
```

---

## 📞 Diagnostic Commands

### Quick Check

```bash
# All-in-one check
./test-deployment.sh
```

### Manual Check

```bash
# Container status
docker-compose ps

# Logs
docker-compose logs --tail=50

# Resource
docker stats --no-stream

# Disk
df -h
du -sh *

# Network
netstat -tulpn | grep LISTEN

# Database
docker exec rbmschedule_db mysql -u rbm_user -p -e "SHOW PROCESSLIST;"
```

---

## 💡 Tips Mencegah Masalah

1. **Backup rutin:**
```bash
# Setup cron
crontab -e
# Tambahkan: 0 2 * * * cd /opt/rbmschedule && ./backup.sh
```

2. **Monitor disk space:**
```bash
df -h
docker system df
```

3. **Monitor logs:**
```bash
tail -f logs/error.log
docker-compose logs -f
```

4. **Update berkala:**
```bash
# Backup dulu
./backup.sh

# Update
git pull origin main
docker-compose restart
```

5. **Test sebelum deploy:**
```bash
./test-deployment.sh
```

---

Jika masalah masih berlanjut setelah mencoba solusi di atas, cek:
- Docker logs: `docker-compose logs`
- System logs: `sudo journalctl -u docker`
- Application logs: `logs/error.log`
