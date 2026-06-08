# 🚀 Commands untuk Deploy di VPS

Jalankan command ini **DI VPS**, bukan di komputer lokal!

---

## 📋 Step-by-Step Deployment di VPS

### 1️⃣ Login ke VPS
```bash
# Dari komputer Windows Anda
ssh user@your-vps-ip

# Contoh:
# ssh root@103.175.22.45
```

---

### 2️⃣ Upload Project ke VPS

**Opsi A: Via SCP (dari komputer Windows)**
```bash
# Compress project dulu (di Windows)
tar -czf rbmschedule.tar.gz rbmschedule/

# Upload ke VPS
scp rbmschedule.tar.gz user@your-vps-ip:~/

# Login ke VPS dan extract
ssh user@your-vps-ip
cd ~
tar -xzf rbmschedule.tar.gz
cd rbmschedule
```

**Opsi B: Via Git (di VPS)**
```bash
# Di VPS
cd ~
git clone https://github.com/lutfllhm/rbmschedule.git
cd rbmschedule
```

---

### 3️⃣ Clean Docker yang Error (DI VPS!)

```bash
# Stop containers yang error
docker compose down

# Hapus container yang stuck
docker container prune -f

# Hapus network yang error
docker network prune -f

# Cek tidak ada container rbm yang jalan
docker ps -a | grep rbm

# Jika ada, hapus manual
docker rm -f rbm-schedule-db rbm-schedule-web
```

---

### 4️⃣ Cek Port yang Tersedia (DI VPS!)

```bash
# Cek port 8090 (web)
netstat -tulpn | grep 8090

# Cek port 3307 (database - sudah disabled tapi cek aja)
netstat -tulpn | grep 3307

# Cek port 3308 (alternative database port)
netstat -tulpn | grep 3308

# Lihat semua port yang digunakan
docker ps --format "table {{.Names}}\t{{.Ports}}"
```

---

### 5️⃣ Pastikan File .env Benar (DI VPS!)

```bash
# Lihat isi .env
cat .env

# Pastikan ada:
# - WEB_PORT=8090
# - DB_PORT_EXTERNAL=3308 (atau comment jika tidak perlu)
# - DB_HOST=rbm-db
# - Password sudah diganti
```

---

### 6️⃣ Build dan Start (DI VPS!)

```bash
# Build image
docker compose build

# Jika build error, rebuild tanpa cache
docker compose build --no-cache

# Start containers
docker compose up -d

# Lihat progress
docker compose logs -f
```

---

### 7️⃣ Verifikasi (DI VPS!)

```bash
# Cek status containers
docker compose ps

# Expected output:
# NAME                   STATUS
# rbm-schedule-db        Up (healthy)
# rbm-schedule-web       Up (healthy)

# Cek logs
docker compose logs --tail=50

# Test web dari dalam VPS
curl http://localhost:8090

# Cek resource usage
docker stats --no-stream
```

---

### 8️⃣ Test dari Browser

Dari komputer Anda, buka browser:
```
http://IP-VPS-ANDA:8090
```

Contoh:
```
http://103.175.22.45:8090
```

---

## 🔧 Troubleshooting di VPS

### Error: "port 3307 is already allocated"

**Solusi 1: Disable Port Expose (Recommended)**

File `docker-compose.yml` sudah saya update. Port database sudah di-disable (comment out).

Ini **LEBIH AMAN** karena database hanya bisa diakses dari internal Docker network.

**Solusi 2: Ganti Port**

Jika tetap mau expose port database:

```bash
# Edit .env di VPS
nano .env

# Ganti DB_PORT_EXTERNAL ke port yang kosong
DB_PORT_EXTERNAL=3308  # atau 33070, 33071, dll

# Edit docker-compose.yml
nano docker-compose.yml

# Uncomment baris ports di service rbm-db:
#    ports:
#      - "${DB_PORT_EXTERNAL:-3308}:3306"

# Restart
docker compose down
docker compose up -d
```

---

### Error: "port 8090 is already allocated"

```bash
# Cek port yang digunakan
netstat -tulpn | grep 8090

# Ganti WEB_PORT di .env
nano .env
# Ganti: WEB_PORT=8091

# Restart
docker compose down
docker compose up -d
```

---

### Container Status: "Unhealthy"

```bash
# Lihat logs untuk cari error
docker compose logs rbm-web
docker compose logs rbm-db

# Cek health check
docker inspect rbm-schedule-web | grep -A 10 Health
docker inspect rbm-schedule-db | grep -A 10 Health

# Restart
docker compose restart
```

---

### Database Connection Error

```bash
# Cek environment variables
docker compose exec rbm-web env | grep DB_

# Pastikan DB_HOST=rbm-db (bukan "mysql" atau "localhost")
nano .env

# Test koneksi
docker compose exec rbm-web ping -c 3 rbm-db
docker compose exec rbm-db mysql -u rbm_user -p
```

---

## 🛠️ Perintah Berguna di VPS

### Monitoring
```bash
# Lihat logs real-time
docker compose logs -f

# Lihat logs service tertentu
docker compose logs -f rbm-web
docker compose logs -f rbm-db

# Lihat status
docker compose ps

# Lihat resource usage
docker stats
```

### Management
```bash
# Restart semua
docker compose restart

# Restart service tertentu
docker compose restart rbm-web

# Stop semua
docker compose stop

# Start semua
docker compose start

# Stop dan hapus containers (data tetap ada)
docker compose down

# Stop dan hapus containers + volumes (DATA HILANG!)
docker compose down -v
```

### Masuk ke Container
```bash
# Masuk ke web container
docker compose exec rbm-web bash

# Masuk ke database container
docker compose exec rbm-db bash

# MySQL shell
docker compose exec rbm-db mysql -u rbm_user -p
```

### Backup Database
```bash
# Backup
docker compose exec rbm-db mysqldump -u root -p rbm_schedule > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore
docker compose exec -T rbm-db mysql -u root -p rbm_schedule < backup_20260608.sql
```

---

## 🔒 Security Checklist di VPS

- [ ] Password di `.env` sudah diganti (tidak pakai default)
- [ ] Database port **TIDAK** di-expose (comment di docker-compose.yml)
- [ ] Firewall allow port 8090 saja
- [ ] Setup SSL/HTTPS dengan Nginx reverse proxy
- [ ] Backup database otomatis dengan cron

---

## 📝 Quick Reference

| Action | Command |
|--------|---------|
| Start | `docker compose up -d` |
| Stop | `docker compose stop` |
| Restart | `docker compose restart` |
| Status | `docker compose ps` |
| Logs | `docker compose logs -f` |
| Rebuild | `docker compose build && docker compose up -d` |
| Clean | `docker compose down && docker system prune -f` |

---

## 🎯 Expected Final Result

Setelah semua berhasil:

```bash
$ docker compose ps
NAME                   STATUS
rbm-schedule-db        Up (healthy)
rbm-schedule-web       Up (healthy)

$ curl http://localhost:8090
# Output: HTML dari aplikasi RBM Schedule
```

Dari browser: `http://your-vps-ip:8090` → Aplikasi berjalan! ✅

---

## 💡 Tips

### Akses tanpa Port (Optional)

Setup Nginx reverse proxy:

```bash
# Install Nginx
sudo apt install nginx -y

# Buat config
sudo nano /etc/nginx/sites-available/rbmschedule
```

Isi:
```nginx
server {
    listen 80;
    server_name rbm.yourdomain.com;

    location / {
        proxy_pass http://localhost:8090;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

Aktifkan:
```bash
sudo ln -s /etc/nginx/sites-available/rbmschedule /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

SSL gratis:
```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d rbm.yourdomain.com
```

---

**🚀 Semua command di atas dijalankan DI VPS, bukan di Windows lokal!**

**Good Luck!** 💪
