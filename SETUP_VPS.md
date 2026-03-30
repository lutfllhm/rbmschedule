iooxz\# Setup Guide untuk VPS

## Langkah-langkah Setup di VPS (148.230.100.44)

### 1. Koneksi ke VPS
```bash
ssh root@148.230.100.44
```

### 2. Install Dependencies (jika belum)
```bash
# Update package list
apt update

# Install Apache, PHP, MySQL
apt install -y apache2 php php-mysqli php-json php-mbstring mysql-server

# Enable Apache modules
a2enmod rewrite
systemctl restart apache2
```

### 3. Setup Database

```bash
# Login ke MySQL
mysql -u root -p

# Jalankan perintah SQL berikut:
```

```sql
-- Buat database
CREATE DATABASE IF NOT EXISTS rbm_schedule;

-- Buat user database (ganti password!)
CREATE USER 'rbm_user'@'localhost' IDENTIFIED BY 'password_kuat_anda';
GRANT ALL PRIVILEGES ON rbm_schedule.* TO 'rbm_user'@'localhost';
FLUSH PRIVILEGES;

-- Gunakan database
USE rbm_schedule;

-- Import struktur tabel (copy dari database.sql)
-- Atau jalankan: source /path/to/database.sql
```

Atau import langsung dari file:
```bash
mysql -u root -p rbm_schedule < /path/to/rbmschedule/database.sql
```

### 4. Konfigurasi Environment

```bash
# Masuk ke direktori aplikasi
cd /var/www/html/rbmschedule  # atau path aplikasi Anda

# Copy file .env.example ke .env
cp .env.example .env

# Edit file .env
nano .env
```

Isi file `.env`:
```env
APP_ENV=production
DEBUG_MODE=false

DB_HOST=localhost
DB_PORT=3306
DB_NAME=rbm_schedule
DB_USER=rbm_user
DB_PASS=password_kuat_anda
```

### 5. Set Permissions

```bash
# Set ownership ke www-data (user Apache)
chown -R www-data:www-data /var/www/html/rbmschedule

# Set permissions untuk logs directory
chmod 755 /var/www/html/rbmschedule/logs
chmod 644 /var/www/html/rbmschedule/logs/.htaccess

# Set permissions untuk config
chmod 644 /var/www/html/rbmschedule/.env
```

### 6. Konfigurasi Apache

Jika aplikasi di subdirektori `/rbmschedule`:
```bash
nano /etc/apache2/sites-available/000-default.conf
```

Tambahkan:
```apache
<Directory /var/www/html/rbmschedule>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

Atau jika ingin di root domain, buat virtual host baru:
```bash
nano /etc/apache2/sites-available/rbmschedule.conf
```

```apache
<VirtualHost *:80>
    ServerName 148.230.100.44
    DocumentRoot /var/www/html/rbmschedule
    
    <Directory /var/www/html/rbmschedule>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/rbmschedule_error.log
    CustomLog ${APACHE_LOG_DIR}/rbmschedule_access.log combined
</VirtualHost>
```

Enable site dan restart:
```bash
a2ensite rbmschedule.conf
systemctl restart apache2
```

### 7. Test Koneksi Database

Akses di browser:
```
http://148.230.100.44/check_login.php
```

File ini akan mengecek:
- ✅ PHP version
- ✅ PHP extensions
- ✅ .env configuration
- ✅ Database connection
- ✅ Users table
- ✅ File permissions
- ✅ Session
- ✅ Password hash

### 8. Login ke Aplikasi

Jika semua check ✅, akses:
```
http://148.230.100.44/pages/login.php
```

Kredensial default:
- **Admin**: username `admin`, password `admin123`
- **Operator**: username `operator`, password `operator123`

### 9. Troubleshooting

#### Error: "Terjadi kesalahan pada sistem"
- Cek koneksi database di `.env`
- Pastikan database sudah dibuat
- Pastikan user database punya akses

#### Error: "CSRF token tidak valid"
- Cek session PHP: `php -i | grep session`
- Pastikan direktori session writable: `ls -la /var/lib/php/sessions`

#### Error 404 atau CSS tidak load
- Cek path di `.htaccess`
- Pastikan `mod_rewrite` enabled: `a2enmod rewrite`
- Restart Apache: `systemctl restart apache2`

#### Cek Error Log
```bash
# Apache error log
tail -f /var/log/apache2/error.log

# Application log
tail -f /var/www/html/rbmschedule/logs/application.log
```

### 10. Security Checklist

- [ ] Ganti password database default
- [ ] Set `DEBUG_MODE=false` di production
- [ ] Hapus file `check_login.php` setelah setup
- [ ] Ganti password user admin/operator
- [ ] Setup firewall (UFW)
- [ ] Setup SSL/HTTPS (Let's Encrypt)
- [ ] Backup database secara berkala

### 11. Ganti Password User

Login sebagai admin, lalu jalankan query:
```sql
-- Ganti password admin
UPDATE users SET password = '$2y$10$NEW_HASH_HERE' WHERE username = 'admin';
```

Atau buat script PHP untuk generate hash:
```php
<?php
echo password_hash('password_baru_anda', PASSWORD_DEFAULT);
?>
```

## Quick Setup Script

Buat file `setup.sh`:
```bash
#!/bin/bash

echo "=== RBM Schedule Setup ==="

# 1. Copy .env
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✅ .env file created"
fi

# 2. Set permissions
chown -R www-data:www-data .
chmod 755 logs
echo "✅ Permissions set"

# 3. Import database
read -p "MySQL root password: " -s MYSQL_PASS
echo ""
mysql -u root -p$MYSQL_PASS < database.sql
echo "✅ Database imported"

# 4. Restart Apache
systemctl restart apache2
echo "✅ Apache restarted"

echo ""
echo "Setup complete! Visit http://148.230.100.44/check_login.php"
```

Jalankan:
```bash
chmod +x setup.sh
./setup.sh
```
