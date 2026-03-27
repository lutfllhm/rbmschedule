# Panduan Hosting RBM Schedule dengan Docker (Lengkap)

Panduan ini khusus untuk deploy RBM Schedule ke VPS menggunakan Docker Compose, dengan domain:
- `labelrbm.iwareid.com`

Karena source saat ini masih memakai path absolut `/rbmschedule/...`, URL aplikasi yang benar adalah:
- `https://labelrbm.iwareid.com/rbmschedule`

## Arsitektur Deployment

Stack yang dipakai:
- `nginx` container sebagai web server
- `app` container (PHP-FPM 8.2)
- `mysql` container (MySQL 8)
- volume Docker untuk data database (`mysql_data`)

Alur request:
1. User akses domain
2. Nginx container menerima request
3. File PHP diproses ke PHP-FPM container
4. Aplikasi terhubung ke MySQL container

## 1) Prasyarat

- VPS Ubuntu 22.04/24.04 (disarankan 2 vCPU, RAM 2 GB)
- Domain `labelrbm.iwareid.com` sudah dimiliki
- Akses SSH ke VPS
- Port `80` dan `443` terbuka

## 2) Setup DNS Domain

Di DNS manager domain:
- Buat `A` record untuk `labelrbm.iwareid.com` menuju IP publik VPS

Verifikasi:

```bash
nslookup labelrbm.iwareid.com
```

Pastikan IP hasil sama dengan IP VPS.

## 3) Install Docker Engine + Compose

```bash
sudo apt update
sudo apt install -y ca-certificates curl gnupg lsb-release

sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo $VERSION_CODENAME) stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

sudo systemctl enable docker
sudo systemctl start docker
docker --version
docker compose version
```

Opsional agar user bisa pakai docker tanpa sudo:

```bash
sudo usermod -aG docker $USER
newgrp docker
```

## 4) Persiapan Folder Deploy

Di VPS:

```bash
sudo mkdir -p /opt/rbmschedule
sudo chown -R $USER:$USER /opt/rbmschedule
```

Upload dari lokal:

```bash
rsync -av --delete --exclude='.git' --exclude='.kombai' ./ username@IP_VPS:/opt/rbmschedule/
```

Masuk folder project:

```bash
cd /opt/rbmschedule
```

## 5) Konfigurasi Environment Docker

Buat `.env` dari template:

```bash
cp .env.docker.example .env
nano .env
```

Contoh nilai production:

```env
APP_ENV=production
DEBUG_MODE=false

DB_HOST=mysql
DB_PORT=3306
DB_NAME=rbm_schedule
DB_USER=rbm_user
DB_PASS=GantiPasswordKuat123!

MYSQL_ROOT_PASSWORD=RootPasswordSuperKuat123!
```

Catatan:
- `DB_HOST` wajib `mysql` (nama service di `docker-compose.yml`)
- jangan pakai password contoh

## 6) Jalankan Container Pertama Kali

```bash
docker compose up -d --build
docker compose ps
```

Service yang harus `Up`:
- `rbmschedule-nginx`
- `rbmschedule-app`
- `rbmschedule-mysql`

Cek log jika ada error:

```bash
docker compose logs -f --tail=100 nginx
docker compose logs -f --tail=100 app
docker compose logs -f --tail=100 mysql
```

## 7) Verifikasi Aplikasi via IP

```bash
curl -I http://IP_VPS/rbmschedule
curl -I http://IP_VPS/rbmschedule/pages/login.php
```

Lalu buka browser:
- `http://IP_VPS/rbmschedule`

Jika berhasil, lanjut ke SSL/domain.

## 8) Setup Reverse Proxy Host Nginx + SSL

Cara paling aman: pakai Nginx host untuk terminasi SSL, lalu proxy ke container di port lokal.

### 8.1 Ubah mapping port container agar tidak bentrok

Edit `docker-compose.yml` service `nginx`:
- dari `80:80`
- menjadi `127.0.0.1:8080:80`

Lalu apply:

```bash
docker compose up -d
```

### 8.2 Install Nginx host + Certbot

```bash
sudo apt install -y nginx certbot python3-certbot-nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

### 8.3 Buat config host Nginx untuk domain

```bash
sudo nano /etc/nginx/sites-available/labelrbm.iwareid.com
```

Isi:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name labelrbm.iwareid.com;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Aktifkan site:

```bash
sudo ln -sf /etc/nginx/sites-available/labelrbm.iwareid.com /etc/nginx/sites-enabled/labelrbm.iwareid.com
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

### 8.4 Pasang SSL Let's Encrypt

```bash
sudo certbot --nginx -d labelrbm.iwareid.com
```

Pilih redirect HTTP ke HTTPS saat diminta.

Tes auto-renew:

```bash
sudo certbot renew --dry-run
```

## 9) Konfigurasi Firewall (UFW)

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

## 10) Checklist Go-Live

Uji endpoint:

```bash
curl -I https://labelrbm.iwareid.com/rbmschedule
curl -I https://labelrbm.iwareid.com/rbmschedule/pages/login.php
```

Checklist manual:
- Login admin/operator sukses
- Dashboard menampilkan data
- CRUD schedule berjalan
- Realtime update berjalan
- Report/export berfungsi
- Logout berfungsi

## 11) Operasional Harian

### Start / Stop / Restart

```bash
docker compose up -d
docker compose down
docker compose restart
```

### Lihat log real-time

```bash
docker compose logs -f nginx
docker compose logs -f app
docker compose logs -f mysql
```

### Update aplikasi (redeploy)

```bash
rsync -av --delete --exclude='.git' --exclude='.kombai' ./ username@IP_VPS:/opt/rbmschedule/
cd /opt/rbmschedule
docker compose up -d --build
```

## 12) Backup dan Restore Database

Backup:

```bash
docker exec rbmschedule-mysql sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" rbm_schedule' > backup_rbm_schedule_$(date +%F).sql
```

Restore:

```bash
cat backup_rbm_schedule.sql | docker exec -i rbmschedule-mysql sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" rbm_schedule'
```

## 13) Hardening Dasar (Disarankan)

- Ganti semua password default di `.env`
- Set `DEBUG_MODE=false`
- Batasi akses SSH (non-root + key auth)
- Rutin `apt update && apt upgrade`
- Backup `mysql_data` secara berkala
- Pantau log container dan host

## 14) Troubleshooting Lengkap

### A. Container tidak jalan

```bash
docker compose ps
docker compose logs --tail=200
```

### B. `502 Bad Gateway`

Penyebab:
- service `app` crash
- port/fcgi salah di `docker/nginx/default.conf`

Cek:

```bash
docker compose logs -f app
docker compose logs -f nginx
```

### C. Koneksi database gagal

Pastikan:
- `.env` berisi `DB_HOST=mysql`
- `DB_USER`, `DB_PASS`, `DB_NAME` sesuai

Cek:

```bash
docker compose exec app printenv | rg "DB_HOST|DB_NAME|DB_USER"
docker compose logs -f mysql
```

### D. CSS/JS tidak termuat

Pastikan akses lewat path:
- `/rbmschedule`

bukan root `/`.

### E. SSL gagal issue certbot

Pastikan:
- DNS sudah mengarah ke VPS
- port 80 terbuka
- config Nginx valid (`sudo nginx -t`)

## 15) Catatan Penting Path Aplikasi

Project saat ini masih hardcoded ke `/rbmschedule`.

Jadi URL final yang benar:
- `https://labelrbm.iwareid.com/rbmschedule`

Jika Anda ingin langsung root domain:
- `https://labelrbm.iwareid.com`

maka perlu refactor path menjadi dinamis (`BASE_URL`). Saya bisa bantu kerjakan jika Anda mau.
