# 🚀 Quick Start - RBM Schedule

Panduan singkat untuk deployment RBM Schedule dalam 5 menit!

## 📦 Yang Anda Butuhkan

- Server dengan Ubuntu 20.04+ atau Debian 10+
- Minimal 1GB RAM
- Docker dan Docker Compose

## ⚡ Langkah Cepat

### 1. Install Docker

```bash
# Install Docker
curl -fsSL https://get.docker.com | sh

# Install Docker Compose
sudo apt update && sudo apt install -y docker-compose

# (Opsional) Tambahkan user ke grup docker
sudo usermod -aG docker $USER
newgrp docker
```

### 2. Upload Project ke Server

```bash
# Via SCP dari komputer lokal
scp -r rbmschedule/ user@server-ip:/opt/

# Atau clone via Git
cd /opt
git clone https://github.com/username/rbmschedule.git
cd rbmschedule
```

### 3. Konfigurasi

```bash
# Copy dan edit file .env
cp .env.example .env
nano .env
```

**Edit bagian ini di file .env:**
```env
# GANTI PASSWORD INI!
DB_ROOT_PASS=PasswordRootAnda123!
DB_PASS=PasswordUserAnda123!

# Ganti port jika sudah digunakan
WEB_PORT=8090
DB_PORT_EXTERNAL=3308
```

### 4. Deploy

```bash
# Jalankan script deployment
chmod +x deploy.sh
./deploy.sh
```

### 5. Akses Aplikasi

Buka browser:
```
http://IP-SERVER:8090
```

Login dengan:
- Username: `admin`
- Password: `admin123`

**⚠️ Ubah password setelah login!**

---

## 🛠️ Perintah Penting

```bash
# Cek status aplikasi
chmod +x status.sh
./status.sh

# Backup database
chmod +x backup.sh
./backup.sh

# Restore database
chmod +x restore.sh
./restore.sh backup/backup_20260605_120000.sql.gz

# Lihat log
docker-compose logs -f

# Restart aplikasi
docker-compose restart

# Stop aplikasi
docker-compose down
```

---

## 🔧 Troubleshooting Cepat

### Port sudah digunakan?

1. Cek port yang digunakan:
```bash
sudo netstat -tulpn | grep LISTEN
```

2. Edit `.env` dan ganti `WEB_PORT` atau `DB_PORT_EXTERNAL`

3. Restart:
```bash
docker-compose down
docker-compose up -d
```

### Container error?

```bash
# Lihat log error
docker-compose logs

# Rebuild dari awal
docker-compose down
docker-compose up -d --build
```

### Database tidak connect?

```bash
# Test koneksi database
docker exec -it rbmschedule_db mysql -u rbm_user -p

# Cek log database
docker-compose logs db
```

---

## 📚 Dokumentasi Lengkap

Lihat file [DEPLOYMENT.md](DEPLOYMENT.md) untuk dokumentasi lengkap.

---

**Selamat mencoba! 🎉**
