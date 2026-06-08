# 🚀 Panduan Deployment RBM Schedule

Panduan mudah untuk deploy aplikasi RBM Schedule ke server production menggunakan Docker.

## 📋 Daftar Isi

1. [Quick Start dengan Docker](#-quick-start-dengan-docker)
2. [Setup Domain & SSL Certificate](#-setup-domain--ssl-certificate)
3. [Konfigurasi Port](#-konfigurasi-port)
4. [Backup & Restore](#-backup--restore-database)
5. [Update Aplikasi](#-update-aplikasi)
6. [Troubleshooting](#-troubleshooting)

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

---

## 🌐 Setup Domain & SSL Certificate

### Persiapan Domain

Sebelum memulai, pastikan:
- Domain sudah di-pointing ke IP server VPS
- Record DNS sudah propagasi (bisa dicek dengan: `nslookup label.rbmlogistics.id`)

### Langkah 1: Install Nginx

```bash
# Update package list
sudo apt update

# Install Nginx
sudo apt install -y nginx

# Verifikasi instalasi
nginx -v

# Start dan enable Nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# Cek status
sudo systemctl status nginx
```

### Langkah 2: Setup Nginx Configuration

```bash
# Masuk ke direktori project
cd /opt/rbmschedule

# Copy file nginx config untuk domain label
sudo cp nginx-label.conf /etc/nginx/sites-available/label-rbmschedule

# Atau jika belum punya file, buat manual
sudo nano /etc/nginx/sites-available/label-rbmschedule
# Lalu copy isi dari nginx-label.conf
```

**Edit konfigurasi jika port berbeda:**
```bash
sudo nano /etc/nginx/sites-available/label-rbmschedule

# Cari baris ini dan sesuaikan port dengan WEB_PORT di .env
# proxy_pass http://127.0.0.1:8090;
# Ganti 8090 dengan port yang Anda gunakan
```

### Langkah 3: Enable Site

```bash
# Buat symbolic link ke sites-enabled
sudo ln -s /etc/nginx/sites-available/label-rbmschedule /etc/nginx/sites-enabled/

# Hapus default site (opsional)
sudo rm /etc/nginx/sites-enabled/default

# Test konfigurasi Nginx
sudo nginx -t

# Output yang diharapkan:
# nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
# nginx: configuration file /etc/nginx/nginx.conf test is successful
```

**Jika ada error saat nginx -t:**
```bash
# Cek detail error
sudo nginx -t

# Cek log nginx
sudo tail -f /var/log/nginx/error.log
```

### Langkah 4: Reload Nginx (Tanpa SSL dulu)

```bash
# Sebelum setup SSL, comment dulu bagian SSL di config
sudo nano /etc/nginx/sites-available/label-rbmschedule

# Comment baris SSL certificate (tambahkan # di depan):
# ssl_certificate /etc/letsencrypt/live/label.rbmlogistics.id/fullchain.pem;
# ssl_certificate_key /etc/letsencrypt/live/label.rbmlogistics.id/privkey.pem;

# Dan ubah server block HTTPS menjadi HTTP dulu:
# Ganti:
#   listen 443 ssl http2;
# Menjadi:
#   listen 80;

# Comment juga redirect server block (yang pertama)

# Test dan reload
sudo nginx -t
sudo systemctl reload nginx
```

### Langkah 5: Install Certbot (Let's Encrypt)

```bash
# Install Certbot dan plugin Nginx
sudo apt install -y certbot python3-certbot-nginx

# Verifikasi instalasi
certbot --version
```

### Langkah 6: Setup SSL Certificate

**Cara Otomatis (Direkomendasikan):**

```bash
# Jalankan Certbot untuk domain label.rbmlogistics.id
sudo certbot --nginx -d label.rbmlogistics.id

# Certbot akan menanyakan beberapa hal:
# 1. Email address (untuk notifikasi renewal): masukkan email Anda
# 2. Agree to Terms of Service: Y (yes)
# 3. Share email with EFF: N (no) atau Y (terserah)
# 4. Redirect HTTP to HTTPS: 2 (redirect - direkomendasikan)

# Tunggu hingga selesai (biasanya 1-2 menit)
```

**Output yang berhasil:**
```
Congratulations! You have successfully enabled HTTPS on https://label.rbmlogistics.id

IMPORTANT NOTES:
 - Congratulations! Your certificate and chain have been saved at:
   /etc/letsencrypt/live/label.rbmlogistics.id/fullchain.pem
   Your key file has been saved at:
   /etc/letsencrypt/live/label.rbmlogistics.id/privkey.pem
   Your certificate will expire on 2026-09-08.
```

**Cara Manual (jika otomatis gagal):**

```bash
# Generate certificate saja tanpa auto-configure
sudo certbot certonly --nginx -d label.rbmlogistics.id

# Lalu edit nginx config manual
sudo nano /etc/nginx/sites-available/label-rbmschedule

# Uncomment baris SSL:
ssl_certificate /etc/letsencrypt/live/label.rbmlogistics.id/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/label.rbmlogistics.id/privkey.pem;

# Ubah kembali listen port menjadi HTTPS:
listen 443 ssl http2;
listen [::]:443 ssl http2;

# Uncomment redirect HTTP to HTTPS (server block pertama)

# Test dan reload
sudo nginx -t
sudo systemctl reload nginx
```

### Langkah 7: Verifikasi SSL

```bash
# Test akses HTTPS
curl -I https://label.rbmlogistics.id

# Cek certificate details
openssl s_client -connect label.rbmlogistics.id:443 -servername label.rbmlogistics.id < /dev/null

# Cek SSL rating (opsional)
# Buka browser: https://www.ssllabs.com/ssltest/
# Masukkan domain: label.rbmlogistics.id
```

**Buka di browser:**
```
https://label.rbmlogistics.id
```

Pastikan:
- ✅ Ada icon gembok (🔒) di address bar
- ✅ Tidak ada warning certificate
- ✅ HTTP otomatis redirect ke HTTPS

### Langkah 8: Setup Auto-Renewal SSL

Certbot sudah otomatis setup cron job untuk renewal, tapi kita perlu test:

```bash
# Test renewal
sudo certbot renew --dry-run

# Output yang diharapkan:
# Congratulations, all simulated renewals succeeded

# Cek cron job (Ubuntu/Debian)
sudo systemctl list-timers | grep certbot

# Atau cek service
sudo systemctl status certbot.timer
```

**Manual renewal (jika diperlukan):**
```bash
# Renew semua certificate
sudo certbot renew

# Renew certificate tertentu
sudo certbot renew --cert-name label.rbmlogistics.id

# Reload nginx setelah renewal
sudo systemctl reload nginx
```

### Langkah 9: Setup Firewall (UFW)

```bash
# Install UFW (jika belum ada)
sudo apt install -y ufw

# Allow SSH (PENTING! Agar tidak terkunci)
sudo ufw allow 22/tcp
sudo ufw allow OpenSSH

# Allow HTTP dan HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Allow port aplikasi Docker (jika perlu akses langsung)
# sudo ufw allow 8090/tcp

# Enable firewall
sudo ufw enable

# Konfirmasi dengan: y (yes)

# Cek status
sudo ufw status verbose

# Output yang diharapkan:
# Status: active
# To                         Action      From
# --                         ------      ----
# 22/tcp                     ALLOW       Anywhere
# 80/tcp                     ALLOW       Anywhere
# 443/tcp                    ALLOW       Anywhere
```

### Troubleshooting SSL

#### Problem: Certbot gagal dengan error "Unable to find a virtual host"

**Solusi:**
```bash
# Pastikan config nginx sudah benar
sudo nginx -t

# Pastikan server_name sudah benar
sudo grep -r "server_name" /etc/nginx/sites-enabled/

# Reload nginx
sudo systemctl reload nginx

# Coba lagi certbot
sudo certbot --nginx -d label.rbmlogistics.id
```

#### Problem: Domain tidak bisa diakses dari luar

**Solusi:**
```bash
# Cek DNS propagation
nslookup label.rbmlogistics.id
dig label.rbmlogistics.id

# Cek firewall
sudo ufw status

# Pastikan port 80 dan 443 terbuka
sudo netstat -tlnp | grep nginx

# Test dari server sendiri
curl -I http://localhost:8090
curl -I http://label.rbmlogistics.id
```

#### Problem: "ERR_SSL_PROTOCOL_ERROR" di browser

**Solusi:**
```bash
# Cek certificate path
sudo ls -la /etc/letsencrypt/live/label.rbmlogistics.id/

# Cek nginx config
sudo nginx -t

# Cek nginx error log
sudo tail -f /var/log/nginx/error.log

# Restart nginx
sudo systemctl restart nginx
```

#### Problem: Certificate akan expire

**Setup email notification:**
```bash
# Edit certbot renewal config
sudo nano /etc/letsencrypt/renewal/label.rbmlogistics.id.conf

# Pastikan ada email address

# Test renewal
sudo certbot renew --dry-run
```

### Checklist Setup SSL ✅

- [ ] Domain sudah pointing ke IP server
- [ ] Nginx terinstall dan berjalan
- [ ] Nginx config sudah di-setup dengan benar
- [ ] Site sudah di-enable di sites-enabled
- [ ] `nginx -t` tidak ada error
- [ ] Certbot terinstall
- [ ] SSL certificate berhasil di-generate
- [ ] HTTPS bisa diakses dan ada icon gembok
- [ ] HTTP auto-redirect ke HTTPS
- [ ] Auto-renewal sudah di-test
- [ ] Firewall sudah di-setup (port 80, 443, 22)
- [ ] SSLLabs rating A atau A+ (opsional)

---

**🎉 Selamat! Aplikasi RBM Schedule sudah berhasil di-deploy dengan SSL Certificate!**

**Dokumentasi dibuat untuk RBM Schedule v1.0.0**  
**Last updated: Juni 2026**
