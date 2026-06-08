# 🚀 Panduan Deployment RBM Schedule

Panduan mudah untuk deploy aplikasi RBM Schedule ke server production menggunakan Docker.

## 📋 Daftar Isi

1. [Quick Start dengan Docker](#-quick-start-dengan-docker)
2. [Konfigurasi Port](#-konfigurasi-port)
3. [Backup & Restore](#-backup--restore-database)
4. [Update Aplikasi](#-update-aplikasi)
5. [Troubleshooting](#-troubleshooting)

---

## ⚡ Quick Start dengan Docker

### Langkah 1: Persiapan Server

**Persyaratan Minimum:**
- OS: Ubuntu 20.04+ atau Debian 10+
- RAM: 1 GB (Rekomendasi: 2 GB)
- Storage: 10 GB
- Docker & Docker Compose terinstall

### Langkah 2: Install Docker

```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo apt update
sudo apt install -y docker-compose

# Verifikasi instalasi
docker --version
docker-compose --version

# (Opsional) Tambahkan user ke grup docker agar tidak perlu sudo
sudo usermod -aG docker $USER
newgrp docker
```

### Langkah 3: Upload/Clone Project ke Server

**Cara 1: Via Git**
```bash
cd /opt
sudo git clone https://github.com/username/rbmschedule.git
cd rbmschedule
```

**Cara 2: Via SCP/FTP**
```bash
# Dari komputer lokal
scp -r rbmschedule/ user@server-ip:/opt/

# Lalu di server
cd /opt/rbmschedule
```

### Langkah 4: Konfigurasi Environment

```bash
# Copy file .env.example ke .env
cp .env.example .env

# Edit file .env
nano .env
```

**Sesuaikan konfigurasi di file .env:**

```env
# Ganti dengan password yang kuat!
DB_ROOT_PASS=PasswordRootAnda123!
DB_PASS=PasswordUserAnda123!

# Port aplikasi (pastikan tidak konflik!)
WEB_PORT=8090

# Port MySQL eksternal (untuk akses dari host)
DB_PORT_EXTERNAL=3308
```

> ⚠️ **PENTING:** 
> - Ganti semua password default!
> - Pastikan port `WEB_PORT` dan `DB_PORT_EXTERNAL` tidak digunakan aplikasi lain
> - Cek port yang tersedia dengan: `sudo netstat -tulpn | grep LISTEN`

### Langkah 5: Deploy Aplikasi

```bash
# Build dan jalankan container
docker-compose up -d --build

# Tunggu beberapa saat hingga selesai (biasanya 1-2 menit)
```

### Langkah 6: Verifikasi Deployment

```bash
# Cek status container (pastikan status "Up")
docker-compose ps

# Output yang diharapkan:
#       Name                     Command               State           Ports
# -----------------------------------------------------------------------------------
# rbmschedule_app    docker-php-entrypoint apac ...   Up      0.0.0.0:8090->80/tcp
# rbmschedule_db     docker-entrypoint.sh --def ...   Up      0.0.0.0:3308->3306/tcp, 33060/tcp

# Cek log aplikasi
docker-compose logs -f app

# Tekan Ctrl+C untuk keluar dari log

# Test koneksi ke aplikasi
curl http://localhost:8090
```

### Langkah 7: Akses Aplikasi

Buka browser dan akses:
```
http://IP-SERVER-ANDA:8090
```

**Login default:**
- Username: `admin`
- Password: `admin123`

> ⚠️ **Segera ubah password setelah login pertama!**

---

## 🔧 Konfigurasi Port

### Melihat Port yang Digunakan di Server

```bash
# Cek semua port yang sedang digunakan
sudo netstat -tulpn | grep LISTEN

# Atau dengan ss command
sudo ss -tulpn | grep LISTEN

# Atau dengan lsof
sudo lsof -i -P -n | grep LISTEN
```

### Mengganti Port Aplikasi

Jika port default (8090 dan 3308) sudah digunakan, ikuti langkah berikut:

1. **Edit file .env:**
```bash
nano .env
```

2. **Ubah nilai port:**
```env
# Contoh: ganti ke port lain yang tersedia
WEB_PORT=8095      # Port untuk akses web aplikasi
DB_PORT_EXTERNAL=3310   # Port untuk akses MySQL dari host
```

3. **Restart container:**
```bash
docker-compose down
docker-compose up -d
```

### Port yang Digunakan Aplikasi

| Service | Port Internal | Port External (Default) | Keterangan |
|---------|---------------|-------------------------|------------|
| Web App | 80 | 8090 (WEB_PORT) | Akses aplikasi web |
| MySQL | 3306 | 3308 (DB_PORT_EXTERNAL) | Akses database dari host |

> 💡 **Tips:** Port internal tidak perlu diubah, hanya ubah port external di file .env

---

## 💾 Backup & Restore Database

### Backup Database

#### Backup Otomatis (Cepat)

```bash
# Backup database ke folder backup/
docker exec rbmschedule_db mysqldump -u rbm_user -p'PASSWORD_ANDA' rbm_schedule > backup/backup_$(date +%Y%m%d_%H%M%S).sql

# Dengan kompresi (lebih kecil)
docker exec rbmschedule_db mysqldump -u rbm_user -p'PASSWORD_ANDA' rbm_schedule | gzip > backup/backup_$(date +%Y%m%d_%H%M%S).sql.gz
```

Ganti `PASSWORD_ANDA` dengan password di file .env (nilai `DB_PASS`)

#### Script Backup Otomatis

Buat file `backup.sh`:

```bash
#!/bin/bash
# File: backup.sh

# Konfigurasi (sesuaikan dengan .env Anda)
DB_USER="rbm_user"
DB_PASS="PASSWORD_ANDA"  # Ganti dengan password asli
DB_NAME="rbm_schedule"
BACKUP_DIR="./backup"
DATE=$(date +"%Y%m%d_%H%M%S")

# Buat folder backup jika belum ada
mkdir -p $BACKUP_DIR

# Backup database
echo "Memulai backup database..."
docker exec rbmschedule_db mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/backup_${DATE}.sql.gz

if [ $? -eq 0 ]; then
    echo "✅ Backup berhasil: $BACKUP_DIR/backup_${DATE}.sql.gz"
    
    # Hapus backup lebih dari 7 hari
    find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +7 -delete
    echo "✅ Backup lama sudah dibersihkan"
else
    echo "❌ Backup gagal!"
    exit 1
fi
```

**Cara menggunakan:**
```bash
# Buat file executable
chmod +x backup.sh

# Jalankan backup
./backup.sh
```

#### Jadwal Backup Otomatis dengan Cron

```bash
# Edit crontab
crontab -e

# Tambahkan baris berikut untuk backup setiap hari jam 2 pagi
0 2 * * * cd /opt/rbmschedule && ./backup.sh >> logs/backup.log 2>&1

# Atau setiap 6 jam
0 */6 * * * cd /opt/rbmschedule && ./backup.sh >> logs/backup.log 2>&1
```

### Restore Database

#### Restore dari Backup

```bash
# Restore dari file .sql
docker exec -i rbmschedule_db mysql -u rbm_user -p'PASSWORD_ANDA' rbm_schedule < backup/backup_20260605_120000.sql

# Restore dari file .sql.gz (compressed)
gunzip < backup/backup_20260605_120000.sql.gz | docker exec -i rbmschedule_db mysql -u rbm_user -p'PASSWORD_ANDA' rbm_schedule
```

#### Script Restore Aman

Buat file `restore.sh`:

```bash
#!/bin/bash
# File: restore.sh

# Konfigurasi
DB_USER="rbm_user"
DB_PASS="PASSWORD_ANDA"  # Ganti dengan password asli
DB_NAME="rbm_schedule"

# Cek argumen
if [ -z "$1" ]; then
    echo "Usage: ./restore.sh <backup_file>"
    echo "Example: ./restore.sh backup/backup_20260605_120000.sql"
    echo "Example: ./restore.sh backup/backup_20260605_120000.sql.gz"
    exit 1
fi

BACKUP_FILE=$1

# Cek file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo "❌ Error: File $BACKUP_FILE tidak ditemukan!"
    exit 1
fi

# Konfirmasi
echo "⚠️  WARNING: Ini akan mengganti semua data di database '$DB_NAME'"
echo "Backup file: $BACKUP_FILE"
read -p "Lanjutkan? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Restore dibatalkan."
    exit 0
fi

# Backup data saat ini dulu (safety backup)
echo "Membuat safety backup..."
SAFETY_BACKUP="backup/safety_backup_$(date +%Y%m%d_%H%M%S).sql.gz"
docker exec rbmschedule_db mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $SAFETY_BACKUP
echo "✅ Safety backup dibuat: $SAFETY_BACKUP"

# Restore
echo "Memulai restore..."

if [[ $BACKUP_FILE == *.gz ]]; then
    # Restore dari file compressed
    gunzip < $BACKUP_FILE | docker exec -i rbmschedule_db mysql -u $DB_USER -p$DB_PASS $DB_NAME
else
    # Restore dari file SQL biasa
    docker exec -i rbmschedule_db mysql -u $DB_USER -p$DB_PASS $DB_NAME < $BACKUP_FILE
fi

if [ $? -eq 0 ]; then
    echo "✅ Restore berhasil!"
    echo "Safety backup tersimpan di: $SAFETY_BACKUP"
else
    echo "❌ Restore gagal!"
    echo "Database tidak diubah. Safety backup: $SAFETY_BACKUP"
    exit 1
fi
```

**Cara menggunakan:**
```bash
# Buat file executable
chmod +x restore.sh

# Jalankan restore
./restore.sh backup/backup_20260605_120000.sql
```

---

## 🔄 Update Aplikasi

### Update dari Git

```bash
# Masuk ke folder aplikasi
cd /opt/rbmschedule

# Backup database dulu!
./backup.sh

# Pull update terbaru
git pull origin main

# Restart container
docker-compose restart app

# Cek log untuk memastikan tidak ada error
docker-compose logs -f app
```

### Update Manual

```bash
# Backup database
./backup.sh

# Stop container
docker-compose down

# Upload file-file baru ke server (via SCP/FTP)

# Start container lagi
docker-compose up -d --build

# Cek status
docker-compose ps
```

---

## 🔍 Troubleshooting

### Problem: Port sudah digunakan

**Error:**
```
ERROR: for rbmschedule_app  Cannot start service app: driver failed programming external connectivity on endpoint rbmschedule_app: Bind for 0.0.0.0:8090 failed: port is already allocated
```

**Solusi:**
1. Cek aplikasi yang menggunakan port:
```bash
sudo netstat -tulpn | grep 8090
# atau
sudo lsof -i :8090
```

2. Edit file `.env` dan ganti port:
```bash
nano .env
# Ubah WEB_PORT=8090 menjadi port lain yang tersedia
# Misalnya: WEB_PORT=8095
```

3. Restart container:
```bash
docker-compose down
docker-compose up -d
```

### Problem: Container tidak bisa start

**Cek log error:**
```bash
# Lihat log aplikasi
docker-compose logs app

# Lihat log database
docker-compose logs db

# Lihat semua log
docker-compose logs
```

**Solusi umum:**
```bash
# Restart semua container
docker-compose restart

# Atau rebuild dari awal
docker-compose down
docker-compose up -d --build
```

### Problem: Database connection failed

**Cek koneksi database:**
```bash
# Test koneksi ke MySQL
docker exec -it rbmschedule_db mysql -u rbm_user -p

# Masukkan password dari .env (DB_PASS)
```

**Jika koneksi gagal:**
1. Pastikan password di `.env` benar
2. Cek log database:
```bash
docker-compose logs db
```

3. Restart container database:
```bash
docker-compose restart db
```

### Problem: 500 Internal Server Error

**Cek error log:**
```bash
# Lihat log aplikasi
docker-compose logs app

# Atau masuk ke container dan cek log
docker exec -it rbmschedule_app cat /var/www/html/logs/error.log
```

**Fix permission:**
```bash
docker exec rbmschedule_app chown -R www-data:www-data /var/www/html/logs
docker exec rbmschedule_app chmod -R 755 /var/www/html/logs
```

### Problem: Aplikasi lambat

**Cek resource usage:**
```bash
# Cek penggunaan resource container
docker stats

# Cek disk space
df -h

# Cek memory
free -h
```

**Optimize:**
```bash
# Bersihkan log lama
docker exec rbmschedule_app find /var/www/html/logs -name "*.log" -mtime +7 -delete

# Optimize database
docker exec rbmschedule_db mysql -u rbm_user -p -e "OPTIMIZE TABLE rbm_schedule.schedules; OPTIMIZE TABLE rbm_schedule.users;"
```

---

## 📝 Perintah Docker Berguna

### Mengelola Container

```bash
# Lihat status semua container
docker-compose ps

# Start container
docker-compose up -d

# Stop container
docker-compose down

# Restart container
docker-compose restart

# Restart service tertentu
docker-compose restart app
docker-compose restart db

# Rebuild container
docker-compose up -d --build

# Lihat log
docker-compose logs -f app
docker-compose logs -f db
docker-compose logs -f  # Semua service
```

### Masuk ke Container

```bash
# Masuk ke container aplikasi
docker exec -it rbmschedule_app bash

# Masuk ke MySQL
docker exec -it rbmschedule_db mysql -u rbm_user -p

# Jalankan command di container
docker exec rbmschedule_app ls -la /var/www/html
```

### Membersihkan Docker

```bash
# Hapus container yang tidak digunakan
docker container prune

# Hapus image yang tidak digunakan
docker image prune

# Hapus volume yang tidak digunakan (HATI-HATI!)
docker volume prune

# Bersihkan semua (HATI-HATI!)
docker system prune -a
```

---

## 🔐 Keamanan

### Checklist Keamanan

- [ ] Ganti semua password default
- [ ] Password minimal 12 karakter dengan huruf besar, kecil, angka, dan simbol
- [ ] File `.env` tidak ter-commit ke Git
- [ ] Ubah password user admin setelah login pertama
- [ ] Setup firewall untuk membatasi akses port
- [ ] Backup database secara rutin
- [ ] Update aplikasi secara berkala
- [ ] Monitor log secara rutin

### Setup Firewall (UFW)

```bash
# Install UFW
sudo apt install -y ufw

# Allow SSH (PENTING! Agar tidak terkunci)
sudo ufw allow 22/tcp

# Allow port aplikasi
sudo ufw allow 8090/tcp

# Allow port lain yang diperlukan
# sudo ufw allow 80/tcp
# sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable

# Cek status
sudo ufw status
```

---

## 📞 Support

Jika mengalami masalah:

1. ✅ Cek log dengan: `docker-compose logs`
2. ✅ Cek dokumentasi troubleshooting di atas
3. ✅ Pastikan semua port tidak konflik
4. ✅ Pastikan password di `.env` benar
5. ✅ Restart container: `docker-compose restart`

---

**🎉 Selamat! Aplikasi RBM Schedule sudah berhasil di-deploy!**

**Dokumentasi dibuat untuk RBM Schedule v1.0.0**  
**Last updated: Juni 2024**
