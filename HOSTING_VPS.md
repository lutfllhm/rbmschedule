# Panduan Hosting RBM Schedule ke VPS

Panduan ini untuk Ubuntu 22.04/24.04 dengan Nginx + PHP-FPM + MySQL.

## 1) Persiapan Server

Jalankan sebagai user dengan sudo:

```bash
sudo apt update
sudo apt install -y nginx mysql-server php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl unzip rsync
```

## 2) Upload Project ke VPS

Contoh ke folder `/var/www/rbmschedule`:

```bash
sudo mkdir -p /var/www/rbmschedule
sudo chown -R $USER:www-data /var/www/rbmschedule
rsync -av --exclude='.git' ./ /var/www/rbmschedule/
```

## 3) Konfigurasi Environment

Copy file contoh env:

```bash
cp /var/www/rbmschedule/.env.example /var/www/rbmschedule/.env
nano /var/www/rbmschedule/.env
```

Isi minimal:

```env
DEBUG_MODE=false
DB_HOST=127.0.0.1
DB_NAME=rbm_schedule
DB_USER=rbm_user
DB_PASS=password_kuat
```

## 4) Setup Database

Masuk MySQL:

```bash
sudo mysql
```

Lalu buat database + user:

```sql
CREATE DATABASE rbm_schedule CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'rbm_user'@'localhost' IDENTIFIED BY 'password_kuat';
GRANT ALL PRIVILEGES ON rbm_schedule.* TO 'rbm_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Import schema:

```bash
mysql -u rbm_user -p rbm_schedule < /var/www/rbmschedule/database.sql
```

## 5) Konfigurasi Web Server

### Opsi A (disarankan): Nginx

1. Copy template:

```bash
sudo cp /var/www/rbmschedule/deploy/nginx-rbmschedule.conf /etc/nginx/sites-available/rbmschedule.conf
```

2. Edit:

```bash
sudo nano /etc/nginx/sites-available/rbmschedule.conf
```

- Ubah `server_name` sesuai domain
- Pastikan `root /var/www/rbmschedule;`
- Jika PHP Anda bukan 8.2, ubah socket `php8.2-fpm.sock`

3. Aktifkan site:

```bash
sudo ln -s /etc/nginx/sites-available/rbmschedule.conf /etc/nginx/sites-enabled/rbmschedule.conf
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx php8.2-fpm
```

### Opsi B: Apache

Gunakan file `deploy/apache-vhost-rbmschedule.conf` sebagai template vhost.

## 6) Permission yang Dibutuhkan

```bash
sudo chown -R www-data:www-data /var/www/rbmschedule/logs
sudo chmod -R 775 /var/www/rbmschedule/logs
sudo find /var/www/rbmschedule -type d -exec chmod 755 {} \;
sudo find /var/www/rbmschedule -type f -exec chmod 644 {} \;
```

## 7) Aktifkan HTTPS (SSL)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d labelrbm.iwareid.com
```

## 8) Cek Aplikasi

- Buka `http://labelrbm.iwareid.com` (atau `https://`)
- Login dengan user default dari database
- Pastikan halaman dashboard, report, dan realtime update berjalan

## 9) Catatan Path URL Aplikasi

Project ini saat ini memakai path absolut seperti `/rbmschedule/...`.

- Jika deploy di root domain, paling aman letakkan aplikasi di subpath `/rbmschedule`
  (mis. `https://domain.com/rbmschedule`).
- Jika ingin full root domain (`https://domain.com`), Anda perlu refactor path hardcoded.

## 10) Deploy Otomatis (Opsional)

Sudah disediakan script:

```bash
chmod +x scripts/deploy_vps.sh
./scripts/deploy_vps.sh labelrbm.iwareid.com /var/www/rbmschedule
```

Script akan:
- Install package dasar
- Salin project
- Buat config Nginx
- Restart service terkait

## 11) Troubleshooting Cepat

- Cek log Nginx: `/var/log/nginx/rbmschedule_error.log`
- Cek log PHP-FPM: `sudo journalctl -u php8.2-fpm -n 200 --no-pager`
- Cek log aplikasi: `/var/www/rbmschedule/logs/application.log`
- Jika 403/404: pastikan `root`, permission, dan `try_files` sudah benar
