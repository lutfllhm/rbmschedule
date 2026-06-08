# 🔧 Troubleshooting - Docker Deployment

Panduan mengatasi masalah saat deployment Docker.

---

## ❌ Error: "port is already allocated"

### Gejala:
```
Bind for 0.0.0.0:3306 failed: port is already allocated
```

### Penyebab:
Port yang akan digunakan sudah terpakai oleh aplikasi lain.

### Solusi:

#### 1. Cek Port yang Digunakan (Windows)
```bash
# Cek port 3306
netstat -ano | findstr :3306

# Cek port 3307
netstat -ano | findstr :3307

# Cek port 8090
netstat -ano | findstr :8090
```

#### 2. Ganti Port di `.env`
```bash
# Edit .env
# Ganti WEB_PORT jika 8090 sudah digunakan
WEB_PORT=8091

# Ganti DB_PORT_EXTERNAL jika 3307 sudah digunakan
DB_PORT_EXTERNAL=3308
```

#### 3. Clean dan Restart
```bash
# Stop semua
docker compose down

# Clean
docker network prune -f
docker container prune -f

# Start ulang
docker compose up -d
```

---

## ❌ Error: "failed to set up container networking"

### Gejala:
```
failed to set up container networking: driver failed programming external connectivity
```

### Solusi:

#### Opsi 1: Restart Docker Desktop
```bash
# Di Windows, restart Docker Desktop dari System Tray
# Klik kanan icon Docker > Restart
```

#### Opsi 2: Clean Network
```bash
# Stop semua containers
docker compose down

# Hapus network
docker network prune -f

# Restart Docker service (Windows PowerShell as Admin)
Restart-Service docker

# Start ulang
docker compose up -d
```

---

## ❌ Error: "cannot access '/var/www/html/backup': No such file or directory"

### Gejala:
Error saat build Docker image.

### Solusi:
Dockerfile sudah diperbaiki. Rebuild:
```bash
docker compose build --no-cache
docker compose up -d
```

---

## ❌ Error: "The attribute "version" is obsolete"

### Gejala:
Warning saat `docker compose up`.

### Solusi:
Warning ini bisa diabaikan. Docker Compose v2 tidak butuh `version` tapi tetap compatible.

Jika mau hilangkan warning, hapus baris pertama di `docker-compose.yml`:
```yaml
version: '3.8'  # Hapus baris ini
```

---

## ❌ Container Status: "Unhealthy"

### Gejala:
```
rbm-schedule-web   Up (unhealthy)
```

### Solusi:

#### 1. Cek Logs
```bash
docker compose logs rbm-web
docker compose logs rbm-db
```

#### 2. Cek Health Status
```bash
docker inspect rbm-schedule-web | findstr Health
```

#### 3. Masuk ke Container
```bash
# Test dari dalam container
docker compose exec rbm-web curl -f http://localhost/

# Cek Apache
docker compose exec rbm-web service apache2 status
```

---

## ❌ Database Connection Failed

### Gejala:
Aplikasi tidak bisa connect ke database.

### Solusi:

#### 1. Cek Environment Variables
```bash
# Cek di web container
docker compose exec rbm-web env | findstr DB_
```

#### 2. Pastikan DB_HOST Benar
Di `.env` harus:
```bash
DB_HOST=rbm-db  # BUKAN "mysql" atau "localhost"
```

#### 3. Test Koneksi
```bash
# Test dari web container
docker compose exec rbm-web ping -c 3 rbm-db

# Test MySQL
docker compose exec rbm-db mysql -u rbm_user -p
```

---

## ❌ Permission Denied on Logs/Backup

### Gejala:
```
Permission denied: /var/www/html/logs/error.log
```

### Solusi:

#### Di Windows (Host):
```bash
# Di direktori project
mkdir -p logs sessions backup
```

#### Di Container:
```bash
# Fix permission
docker compose exec rbm-web chown -R www-data:www-data /var/www/html/logs
docker compose exec rbm-web chown -R www-data:www-data /var/www/html/sessions
docker compose exec rbm-web chown -R www-data:www-data /var/www/html/backup
```

---

## ❌ Changes in Code Not Reflected

### Gejala:
Edit code tapi tidak berubah di aplikasi.

### Solusi:

#### 1. Restart Container
```bash
docker compose restart rbm-web
```

#### 2. Rebuild Image (Jika Edit Dockerfile)
```bash
docker compose build rbm-web
docker compose up -d rbm-web
```

#### 3. Clear OPcache
```bash
docker compose exec rbm-web rm -rf /tmp/opcache-*
docker compose restart rbm-web
```

---

## 🔧 Common Commands

### Cek Status
```bash
docker compose ps
docker stats --no-stream
```

### Lihat Logs
```bash
# All logs
docker compose logs -f

# Specific service
docker compose logs -f rbm-web
docker compose logs -f rbm-db

# Last 100 lines
docker compose logs --tail=100
```

### Restart Services
```bash
# Restart semua
docker compose restart

# Restart specific
docker compose restart rbm-web
docker compose restart rbm-db
```

### Masuk ke Container
```bash
# Web container
docker compose exec rbm-web bash

# Database container
docker compose exec rbm-db bash

# MySQL shell
docker compose exec rbm-db mysql -u root -p
```

### Clean Up
```bash
# Stop dan hapus containers
docker compose down

# Stop, hapus containers + volumes (⚠️ DATA HILANG!)
docker compose down -v

# Clean unused resources
docker system prune -a -f
```

---

## 🔍 Diagnostic Commands

### Cek Port Binding
```bash
# Windows
netstat -ano | findstr :8090
netstat -ano | findstr :3307

# Check container ports
docker compose port rbm-web 80
docker compose port rbm-db 3306
```

### Cek Network
```bash
# List networks
docker network ls

# Inspect network
docker network inspect rbm-schedule-network

# Test connectivity
docker compose exec rbm-web ping rbm-db
```

### Cek Volumes
```bash
# List volumes
docker volume ls

# Inspect volume
docker volume inspect rbm-schedule-db-data

# Check volume size
docker system df -v
```

---

## 📞 Masih Error?

### Langkah Nuclear (Reset Semua)

⚠️ **WARNING: Akan menghapus semua data!**

```bash
# 1. Stop semua
docker compose down -v

# 2. Hapus containers
docker container prune -f

# 3. Hapus networks
docker network prune -f

# 4. Hapus volumes
docker volume prune -f

# 5. Hapus images (optional)
docker image prune -a -f

# 6. Restart Docker Desktop

# 7. Build dan start dari awal
docker compose build --no-cache
docker compose up -d
```

---

## ✅ Health Check

Setelah deployment, cek ini:

```bash
# 1. Container running dan healthy
docker compose ps
# Status: Up (healthy)

# 2. Logs tidak ada error
docker compose logs --tail=50

# 3. Web accessible
curl http://localhost:8090

# 4. Database accessible
docker compose exec rbm-db mysql -u rbm_user -p -e "SHOW DATABASES;"

# 5. Resource usage normal
docker stats --no-stream
```

---

**Last Updated:** 8 Juni 2026  
**For:** RBM Schedule Docker Deployment
