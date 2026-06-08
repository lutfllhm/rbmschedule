# 🚀 Deployment Guide - RBM Schedule

> Panduan deployment yang mudah untuk RBM Schedule menggunakan Docker

## 📖 Dokumentasi

- 📘 **[QUICK_START.md](QUICK_START.md)** - Panduan cepat 5 menit
- 📕 **[DEPLOYMENT.md](DEPLOYMENT.md)** - Dokumentasi lengkap dengan troubleshooting
- 📙 **[RESTORE_VPS.md](RESTORE_VPS.md)** - Panduan restore dari backup

## ⚡ Quick Deploy

### Metode 1: Menggunakan Script (Recommended)

```bash
# 1. Setup environment
cp .env.example .env
nano .env  # Edit password dan port

# 2. Deploy
chmod +x deploy.sh
./deploy.sh
```

### Metode 2: Menggunakan Makefile

```bash
# 1. Setup environment
cp .env.example .env
nano .env  # Edit password dan port

# 2. Deploy
make deploy

# Lihat semua perintah tersedia
make help
```

### Metode 3: Manual Docker Compose

```bash
# 1. Setup environment
cp .env.example .env
nano .env  # Edit password dan port

# 2. Deploy
docker-compose up -d --build
```

## 🔧 Port Configuration

Berdasarkan gambar yang Anda berikan, port-port berikut sudah digunakan di server:

| Port Range | Status | Keterangan |
|------------|--------|------------|
| 8080-8089 | ❌ Used | Digunakan aplikasi lain |
| 8090 | ✅ Available | **Port default aplikasi ini** |
| 3306, 3307 | ❌ Used | MySQL lain |
| 3308 | ✅ Available | **Port default MySQL eksternal** |

Jika port 8090 atau 3308 sudah digunakan, edit file `.env`:

```env
WEB_PORT=8095      # Ganti ke port lain yang tersedia
DB_PORT_EXTERNAL=3310   # Ganti ke port lain yang tersedia
```

Cek port yang tersedia:
```bash
sudo netstat -tulpn | grep LISTEN
```

## 🛠️ Perintah Penting

### Dengan Makefile (Mudah)

```bash
make deploy      # Deploy aplikasi
make status      # Cek status
make logs        # Lihat logs
make backup      # Backup database
make restart     # Restart aplikasi
make help        # Lihat semua perintah
```

### Dengan Script

```bash
./deploy.sh      # Deploy aplikasi
./status.sh      # Cek status
./backup.sh      # Backup database
./restore.sh <file>  # Restore database
```

### Docker Compose Langsung

```bash
docker-compose up -d          # Start
docker-compose down           # Stop
docker-compose restart        # Restart
docker-compose logs -f        # Logs
docker-compose ps             # Status
```

## 🔍 Verifikasi Deployment

### 1. Cek Status Container

```bash
docker-compose ps

# Output yang diharapkan:
#       Name                     Command               State           Ports
# -----------------------------------------------------------------------------------
# rbmschedule_app    docker-php-entrypoint apac ...   Up      0.0.0.0:8090->80/tcp
# rbmschedule_db     docker-entrypoint.sh --def ...   Up      0.0.0.0:3308->3306/tcp
```

### 2. Cek Logs

```bash
docker-compose logs app
# Tidak boleh ada error PHP
```

### 3. Test Web Access

```bash
curl http://localhost:8090

# Atau di browser:
# http://IP-SERVER:8090
```

### 4. Test Database

```bash
docker exec -it rbmschedule_db mysql -u rbm_user -p
# Masukkan password dari .env
```

## 💾 Backup & Restore

### Backup

```bash
# Auto backup dengan script
./backup.sh

# Atau manual
docker exec rbmschedule_db mysqldump -u rbm_user -p'PASSWORD' rbm_schedule | gzip > backup/backup_$(date +%Y%m%d).sql.gz
```

### Restore

```bash
# Dengan script (recommended)
./restore.sh backup/backup_20260605_120000.sql.gz

# Atau manual
gunzip < backup/backup_20260605_120000.sql.gz | docker exec -i rbmschedule_db mysql -u rbm_user -p'PASSWORD' rbm_schedule
```

### Backup Otomatis (Cron)

```bash
# Edit crontab
crontab -e

# Tambahkan (backup setiap hari jam 2 pagi):
0 2 * * * cd /opt/rbmschedule && ./backup.sh >> logs/backup.log 2>&1
```

## 🔄 Update Aplikasi

### Dari Git

```bash
# Backup dulu!
./backup.sh

# Pull update
git pull origin main

# Restart
docker-compose restart app
```

### Update Manual

```bash
# Backup
./backup.sh

# Stop containers
docker-compose down

# Upload file baru ke server

# Start
docker-compose up -d --build
```

## 🐛 Troubleshooting

### Port Conflict

```
Error: Bind for 0.0.0.0:8090 failed: port is already allocated
```

**Solusi:**
```bash
# Cek aplikasi yang menggunakan port
sudo netstat -tulpn | grep 8090

# Edit .env dan ganti WEB_PORT
nano .env

# Restart
docker-compose down && docker-compose up -d
```

### Database Connection Failed

```bash
# Cek log database
docker-compose logs db

# Cek password di .env
cat .env | grep DB_PASS

# Restart database
docker-compose restart rbm-db
```

### Container Tidak Start

```bash
# Lihat error
docker-compose logs

# Rebuild
docker-compose down
docker-compose up -d --build
```

### 500 Internal Server Error

```bash
# Cek log aplikasi
docker-compose logs app

# Cek permission
docker exec rbmschedule_app ls -la /var/www/html/logs

# Fix permission
docker exec rbmschedule_app chown -R www-data:www-data /var/www/html/logs
```

## 📊 Monitoring

### Cek Status Lengkap

```bash
./status.sh
```

### Resource Usage

```bash
docker stats
```

### Logs Real-time

```bash
# Semua logs
docker-compose logs -f

# App only
docker-compose logs -f app

# Database only
docker-compose logs -f rbm-db
```

## 🔐 Keamanan

### Checklist Keamanan

- [ ] Ganti password di `.env`
- [ ] File `.env` tidak ter-commit ke Git
- [ ] Ubah password admin setelah login
- [ ] Setup firewall (UFW)
- [ ] Backup database rutin
- [ ] Update aplikasi berkala

### Setup Firewall

```bash
# Install UFW
sudo apt install -y ufw

# Allow SSH (PENTING!)
sudo ufw allow 22/tcp

# Allow aplikasi
sudo ufw allow 8090/tcp

# Enable
sudo ufw enable

# Status
sudo ufw status
```

## 📁 Struktur File Penting

```
rbmschedule/
├── .env                    # Konfigurasi (jangan commit!)
├── .env.example            # Template konfigurasi
├── docker-compose.yml      # Docker configuration
├── Dockerfile              # Docker image build
├── database.sql            # Database schema
├── deploy.sh              # Script deployment
├── backup.sh              # Script backup
├── restore.sh             # Script restore
├── status.sh              # Script monitoring
├── Makefile               # Shortcut commands
├── QUICK_START.md         # Quick start guide
├── DEPLOYMENT.md          # Full deployment guide
└── backup/                # Folder backup database
```

## 🌐 Akses Aplikasi

Setelah deployment berhasil:

```
URL: http://IP-SERVER:8090
```

Login default:
- Username: `admin`
- Password: `admin123`

**⚠️ PENTING: Ubah password setelah login pertama!**

## 📞 Support

Jika ada masalah:

1. Cek [DEPLOYMENT.md](DEPLOYMENT.md) untuk troubleshooting lengkap
2. Lihat logs: `docker-compose logs`
3. Cek status: `./status.sh`
4. Cek port conflict: `sudo netstat -tulpn | grep LISTEN`

---

## 🎯 Summary Perintah

| Aksi | Perintah |
|------|----------|
| Deploy | `./deploy.sh` atau `make deploy` |
| Status | `./status.sh` atau `make status` |
| Logs | `docker-compose logs -f` atau `make logs` |
| Backup | `./backup.sh` atau `make backup` |
| Restore | `./restore.sh <file>` atau `make restore` |
| Restart | `docker-compose restart` atau `make restart` |
| Stop | `docker-compose down` atau `make stop` |

---

**🎉 Selamat! Aplikasi RBM Schedule siap digunakan!**
