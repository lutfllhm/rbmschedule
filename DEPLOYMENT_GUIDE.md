# Panduan Deploy RBM Schedule di VPS Hostinger dengan Docker

## Prasyarat
- VPS Hostinger sudah aktif
- Akses SSH ke VPS
- Domain/subdomain sudah disiapkan (labelschedule.iwareid.com)

## Langkah 1: Konfigurasi DNS di Hostinger

1. Login ke hPanel Hostinger
2. Masuk ke menu **Domain** → Pilih domain **iwareid.com**
3. Klik **DNS/Nameserver**
4. Klik **Tambah Record**
5. Isi data berikut:
   - **Tipe**: A
   - **Nama**: labelschedule
   - **Mengarah ke**: 148.230.100.44 (IP VPS Anda)
   - **TTL**: 3306 (default)
6. Klik **Tambah Record**
7. Tunggu 5-15 menit untuk propagasi DNS

## Langkah 2: Koneksi ke VPS via SSH

```bash
ssh root@148.230.100.44
```

Masukkan password VPS Anda.

## Langkah 3: Install Docker & Docker Compose

```bash
# Update sistem
apt update && apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Install Docker Compose
apt install docker-compose -y

# Verifikasi instalasi
docker --version
docker-compose --version
```

## Langkah 4: Install Nginx sebagai Reverse Proxy

```bash
# Install Nginx
apt install nginx -y

# Start dan enable Nginx
systemctl start nginx
systemctl enable nginx
```

## Langkah 5: Upload Aplikasi ke VPS

```bash
# Buat direktori untuk aplikasi
mkdir -p /var/www/labelschedule
cd /var/www/labelschedule

# Upload file aplikasi (gunakan salah satu cara):
```

**Cara A: Menggunakan Git**
```bash
git clone <repository-url> .
```

**Cara B: Menggunakan SCP dari komputer lokal**
```bash
# Jalankan di komputer lokal (bukan di VPS)
scp -r /path/to/rbmschedule/* root@148.230.100.44:/var/www/labelschedule/
```

**Cara C: Menggunakan FTP/SFTP**
- Gunakan FileZilla atau WinSCP
- Upload semua file ke `/var/www/labelschedule/`

## Langkah 6: Konfigurasi Environment

```bash
cd /var/www/labelschedule

# Copy file environment
cp .env.example .env

# Edit file .env
nano .env
```

Pastikan isi `.env` seperti ini:
```env
APP_ENV=production
DEBUG_MODE=false

DB_HOST=mysql
DB_PORT=3306
DB_NAME=rbm_schedule
DB_USER=rbm_user
DB_PASS=rbm_secure_password_2024

MYSQL_ROOT_PASSWORD=root_secure_password_2024
```

Simpan dengan `Ctrl+X`, tekan `Y`, lalu `Enter`.

## Langkah 7: Konfigurasi Nginx Reverse Proxy

```bash
# Buat file konfigurasi nginx
nano /etc/nginx/sites-available/labelschedule
```

Isi dengan:
```nginx
server {
    listen 80;
    server_name labelschedule.iwareid.com;

    location / {
        proxy_pass http://localhost:8083;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Simpan file, lalu:
```bash
# Aktifkan konfigurasi
ln -s /etc/nginx/sites-available/labelschedule /etc/nginx/sites-enabled/

# Test konfigurasi nginx
nginx -t

# Reload nginx
systemctl reload nginx
```

## Langkah 8: Jalankan Aplikasi dengan Docker

```bash
cd /var/www/labelschedule

# Build dan jalankan container
docker-compose up -d

# Cek status container
docker-compose ps

# Lihat log jika ada masalah
docker-compose logs -f

# Jika ada error "port already allocated", cek port yang digunakan
netstat -tulpn | grep 8083
netstat -tulpn | grep 3307

# Jika port bentrok, stop aplikasi lain atau ubah port di docker-compose.yml
```

## Langkah 8.1: Verifikasi Database

```bash
# Masuk ke container MySQL
docker exec -it rbmschedule-mysql mysql -u rbm_user -prbm_secure_password_2024 rbm_schedule

# Di dalam MySQL, cek tabel
SHOW TABLES;

# Jika tabel kosong, import manual
exit

# Import database manual
docker exec -i rbmschedule-mysql mysql -u rbm_user -prbm_secure_password_2024 rbm_schedule < database.sql

# Verifikasi lagi
docker exec -it rbmschedule-mysql mysql -u rbm_user -prbm_secure_password_2024 rbm_schedule -e "SHOW TABLES;"
```

## Langkah 9: Setup SSL dengan Certbot (Opsional tapi Direkomendasikan)

```bash
# Install Certbot
apt install certbot python3-certbot-nginx -y

# Generate SSL certificate
certbot --nginx -d labelschedule.iwareid.com

# Ikuti instruksi, pilih:
# - Email Anda
# - Agree to terms
# - Redirect HTTP to HTTPS (pilih 2)
```

## Langkah 10: Verifikasi

1. Buka browser dan akses: `http://labelschedule.iwareid.com`
2. Jika SSL sudah diinstall: `https://labelschedule.iwareid.com`
3. Login dengan kredensial default (lihat dokumentasi aplikasi)

## Troubleshooting

### Cek status container
```bash
docker-compose ps
```

### Lihat log aplikasi
```bash
docker-compose logs -f app
```

### Lihat log nginx
```bash
docker-compose logs -f nginx
```

### Lihat log mysql
```bash
docker-compose logs -f mysql
```

### Restart semua container
```bash
docker-compose restart
```

### Stop dan hapus container
```bash
docker-compose down
```

### Rebuild container
```bash
docker-compose down
docker-compose up -d --build
```

### Cek port yang digunakan
```bash
netstat -tulpn | grep LISTEN
```

### Cek nginx error log
```bash
tail -f /var/log/nginx/error.log
```

## Maintenance

### Update aplikasi
```bash
cd /var/www/labelschedule
git pull  # jika menggunakan git
docker-compose down
docker-compose up -d --build
```

### Backup database
```bash
docker exec rbmschedule-mysql mysqldump -u rbm_user -prbm_secure_password_2024 rbm_schedule > backup_$(date +%Y%m%d).sql
```

### Restore database
```bash
docker exec -i rbmschedule-mysql mysql -u rbm_user -prbm_secure_password_2024 rbm_schedule < backup_20240101.sql
```

## Keamanan Tambahan

1. Ubah password database di file `.env`
2. Setup firewall:
```bash
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

3. Disable root login SSH (setelah membuat user biasa)
4. Update sistem secara berkala:
```bash
apt update && apt upgrade -y
```

## Kontak & Support

Jika ada masalah, cek:
- Log aplikasi: `docker-compose logs`
- Log nginx: `/var/log/nginx/error.log`
- Status container: `docker-compose ps`
