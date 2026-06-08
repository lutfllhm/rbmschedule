# 🚀 Panduan Deployment RBM Schedule dengan Docker

Panduan lengkap untuk deploy aplikasi RBM Schedule menggunakan Docker dan Docker Compose di VPS.

---

## 📋 Prasyarat

Pastikan VPS Anda sudah terinstall:
- Docker (versi 20.10 atau lebih baru)
- Docker Compose (versi 2.0 atau lebih baru)
- Git (untuk clone repository)
- Minimal 2GB RAM dan 10GB disk space

### Cek Instalasi Docker
```bash
docker --version
docker compose version
```

Jika belum terinstall, install dengan:
```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose (sudah include di Docker versi terbaru)
sudo usermod -aG docker $USER
```

---

## 📁 Struktur File Docker

Setelah setup, struktur file Docker akan seperti ini:
```
rbmschedule/
├── Dockerfile                    # Image configuration untuk aplikasi
├── docker-compose.yml            # Orchestration untuk semua services
├── .env                          # Environment variables (WAJIB dibuat)
├── .env.docker                   # Template environment (copy ke .env)
├── .dockerignore                 # File yang diabaikan saat build
└── docker/
    ├── apache.conf               # Apache virtual host config
    ├── php.ini                   # PHP configuration
    └── mysql-custom.cnf          # MySQL configuration
```

---

## 🔧 Langkah-Langkah Deployment

### Step 1: Persiapan File di VPS

```bash
# Login ke VPS
ssh user@your-vps-ip

# Buat direktori untuk aplikasi (pilih salah satu)
mkdir -p ~/apps/rbmschedule
cd ~/apps/rbmschedule

# Clone atau upload project Anda
# Opsi 1: Jika menggunakan git
git clone https://github.com/your-repo/rbmschedule.git .

# Opsi 2: Jika upload manual via SCP/SFTP dari komputer lokal
# Jalankan dari komputer Anda:
# scp -r d:\project\rbmschedule user@your-vps-ip:~/apps/
```

### Step 2: Konfigurasi Environment

```bash
# Copy template environment
cp .env.docker .env

# Edit file .env dengan password yang aman
nano .env
```

**Isi file .env** (PENTING: Ganti semua password!):
```bash
# Application Settings
APP_ENV=production
DEBUG_MODE=false

# Database Configuration
# ⚠️ GANTI PASSWORD INI DENGAN PASSWORD YANG KUAT!
DB_ROOT_PASS=YourSecureRootPassword123!@#
DB_HOST=rbm-db
DB_PORT=3306
DB_NAME=rbm_schedule
DB_USER=rbm_user
DB_PASS=YourSecureUserPassword456!@#

# Web Server Port
# Port 8090 dipilih agar tidak konflik dengan aplikasi lain
# Jika port 8090 sudah digunakan, ganti dengan port lain (misal: 8091, 8092, dst)
WEB_PORT=8090

# MySQL Port External (untuk akses dari host jika diperlukan)
DB_PORT_EXTERNAL=3307
```

**Cara Cek Port yang Tersedia:**
```bash
# Cek port yang sedang digunakan
sudo netstat -tulpn | grep LISTEN

# Atau menggunakan ss
ss -tulpn | grep LISTEN

# Jika port 8090 sudah digunakan, ganti WEB_PORT ke port lain
```

### Step 3: Buat Direktori yang Diperlukan

```bash
# Buat direktori untuk logs dan sessions
mkdir -p logs
mkdir -p sessions

# Set permission
chmod 775 logs
chmod 775 sessions
chmod 775 backup
```

### Step 4: Build dan Start Container

```bash
# Build image Docker (pertama kali atau setelah ada perubahan code)
docker compose build

# Start semua services (database + web server)
docker compose up -d

# Cek status container
docker compose ps
```

**Output yang diharapkan:**
```
NAME                   STATUS          PORTS
rbm-schedule-db        Up (healthy)    0.0.0.0:3307->3306/tcp
rbm-schedule-web       Up (healthy)    0.0.0.0:8090->80/tcp
```

### Step 5: Verifikasi Deployment

```bash
# Cek logs untuk memastikan tidak ada error
docker compose logs -f rbm-web
docker compose logs -f rbm-db

# Tekan Ctrl+C untuk keluar dari logs

# Test akses database
docker compose exec rbm-db mysql -u rbm_user -p rbm_schedule
# Masukkan password DB_PASS yang Anda set di .env
# Ketik 'exit' untuk keluar dari MySQL

# Test akses web (dari dalam VPS)
curl http://localhost:8090
```

### Step 6: Import Database (Jika Ada Backup)

Jika Anda punya file SQL backup:

```bash
# Copy file backup ke dalam container
docker compose cp backup/backup_20260605_062551.sql rbm-db:/tmp/

# Import database
docker compose exec rbm-db mysql -u rbm_user -p rbm_schedule < /tmp/backup_20260605_062551.sql

# Atau dengan cara alternatif:
docker compose exec -T rbm-db mysql -u rbm_user -p"$DB_PASS" rbm_schedule < backup/backup_20260605_062551.sql
```

### Step 7: Akses Aplikasi

Buka browser dan akses:
```
http://your-vps-ip:8090
```

Contoh:
- `http://103.123.45.67:8090`
- `http://your-domain.com:8090`

---

## 🔒 Konfigurasi Domain & Reverse Proxy (Opsional)

Jika Anda ingin mengakses tanpa port (misal: `https://rbm.yourdomain.com`), gunakan Nginx sebagai reverse proxy:

### Install Nginx di VPS
```bash
sudo apt update
sudo apt install nginx certbot python3-certbot-nginx -y
```

### Konfigurasi Nginx
```bash
sudo nano /etc/nginx/sites-available/rbmschedule
```

Isi dengan:
```nginx
server {
    listen 80;
    server_name rbm.yourdomain.com;

    location / {
        proxy_pass http://localhost:8090;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Aktifkan dan restart:
```bash
sudo ln -s /etc/nginx/sites-available/rbmschedule /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Install SSL Certificate (HTTPS)
```bash
sudo certbot --nginx -d rbm.yourdomain.com
```

---

## 🔧 Perintah-Perintah Penting

### Mengelola Container

```bash
# Start containers
docker compose up -d

# Stop containers (tanpa menghapus data)
docker compose stop

# Start lagi setelah stop
docker compose start

# Restart containers
docker compose restart

# Stop dan hapus containers (data di volume tetap ada)
docker compose down

# Stop dan hapus containers + volumes (⚠️ HAPUS SEMUA DATA!)
docker compose down -v

# Lihat logs
docker compose logs -f              # Semua services
docker compose logs -f rbm-web      # Hanya web server
docker compose logs -f rbm-db       # Hanya database

# Lihat status
docker compose ps

# Masuk ke container
docker compose exec rbm-web bash    # Masuk ke web container
docker compose exec rbm-db bash     # Masuk ke database container
```

### Backup Database

```bash
# Backup database
docker compose exec rbm-db mysqldump -u rbm_user -p rbm_schedule > backup/backup_$(date +%Y%m%d_%H%M%S).sql

# Atau dengan password langsung (less secure, tapi praktis untuk script)
docker compose exec rbm-db mysqldump -u rbm_user -p"$DB_PASS" rbm_schedule > backup/backup_$(date +%Y%m%d_%H%M%S).sql

# Restore database
docker compose exec -T rbm-db mysql -u rbm_user -p rbm_schedule < backup/your_backup.sql
```

### Update Aplikasi

```bash
# Jika ada perubahan code
git pull  # atau upload file baru

# Rebuild image
docker compose build

# Restart dengan image baru
docker compose up -d
```

### Monitor Resource

```bash
# Monitor CPU, Memory, Network
docker stats

# Lihat disk usage
docker system df

# Cleanup unused images/containers
docker system prune -a
```

---

## 🛡️ Keamanan

### 1. Ganti Password Default
Pastikan semua password di `.env` sudah diganti dengan password yang kuat!

### 2. Firewall
```bash
# Jika menggunakan ufw
sudo ufw allow 8090/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 3. Restrict Database Port
Database port (3307) hanya untuk lokal. Jangan expose ke public:
```yaml
# Di docker-compose.yml, comment atau hapus baris ini jika tidak perlu:
# ports:
#   - "3307:3306"
```

### 4. Update Reguler
```bash
# Update base images
docker compose pull
docker compose up -d
```

---

## 🐛 Troubleshooting

### Container Tidak Start

```bash
# Cek logs untuk error
docker compose logs rbm-web
docker compose logs rbm-db

# Cek container status
docker compose ps
```

### Port Sudah Digunakan

Error: `bind: address already in use`

Solusi:
1. Cek proses yang menggunakan port:
   ```bash
   sudo netstat -tulpn | grep 8090
   ```
2. Ganti `WEB_PORT` di `.env` ke port lain
3. Restart: `docker compose down && docker compose up -d`

### Database Connection Failed

```bash
# Cek database container healthy
docker compose ps

# Cek logs database
docker compose logs rbm-db

# Test koneksi manual
docker compose exec rbm-db mysql -u rbm_user -p
```

### Permission Denied

```bash
# Fix permission untuk logs dan sessions
sudo chown -R www-data:www-data logs sessions backup
sudo chmod -R 775 logs sessions backup
```

### Container Crash dengan Aplikasi Lain

Pastikan:
- Port berbeda (8090 untuk RBM, lihat gambar Anda: portainer=9443, jaas=8888, dll)
- Network name unik (`rbm-schedule-network`)
- Container name unik (`rbm-schedule-web`, `rbm-schedule-db`)

---

## 📊 Monitoring

### Health Check
Aplikasi sudah dilengkapi health check otomatis:
- Web: `http://localhost:8090/` setiap 30 detik
- Database: MySQL ping setiap 10 detik

```bash
# Cek health status
docker compose ps
```

### Log Monitoring
```bash
# Real-time logs
docker compose logs -f --tail=100

# Logs dalam container
docker compose exec rbm-web tail -f /var/www/html/logs/php_error.log
```

---

## 🔄 Maintenance

### Backup Rutin (Gunakan Crontab)

```bash
# Edit crontab
crontab -e

# Tambahkan baris ini untuk backup setiap hari jam 2 pagi
0 2 * * * cd ~/apps/rbmschedule && docker compose exec rbm-db mysqldump -u rbm_user -p"YourPassword" rbm_schedule > backup/auto_backup_$(date +\%Y\%m\%d).sql

# Hapus backup lama (lebih dari 7 hari)
0 3 * * * find ~/apps/rbmschedule/backup -name "auto_backup_*.sql" -mtime +7 -delete
```

### Update Aplikasi
```bash
# Pull code terbaru
git pull

# Rebuild dan restart
docker compose build
docker compose up -d

# Verify
docker compose ps
docker compose logs -f
```

---

## 📞 Support

Jika mengalami masalah:

1. Cek logs: `docker compose logs -f`
2. Cek status: `docker compose ps`
3. Restart: `docker compose restart`
4. Rebuild: `docker compose build && docker compose up -d`

---

## ✅ Checklist Deployment

- [ ] Docker dan Docker Compose terinstall
- [ ] File `.env` sudah dibuat dan password diganti
- [ ] Port yang dipilih tidak konflik (cek dengan `netstat`)
- [ ] Direktori `logs`, `sessions`, `backup` sudah dibuat
- [ ] Container berhasil start (`docker compose ps` show healthy)
- [ ] Database bisa diakses
- [ ] Web bisa diakses via browser
- [ ] Backup database sudah di-import (jika ada)
- [ ] Firewall sudah dikonfigurasi
- [ ] (Opsional) Domain dan SSL sudah dikonfigurasi
- [ ] (Opsional) Cron job backup sudah dibuat

---

## 🎉 Selesai!

Aplikasi RBM Schedule Anda sekarang sudah berjalan di VPS dengan Docker!

**URL Akses:** `http://your-vps-ip:8090`

Container ini sudah isolated dengan:
- Network: `rbm-schedule-network`
- Port: `8090` (web), `3307` (database external)
- Volumes: `rbm-schedule-db-data`, `rbm-schedule-sessions`

Tidak akan crash dengan aplikasi Docker lain di VPS Anda! 🚀
