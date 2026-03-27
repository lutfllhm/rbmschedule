# RBM Schedule Management System

Sistem manajemen jadwal produksi yang profesional untuk mengelola dan memantau jadwal produksi label dengan fitur real-time synchronization.

## 📋 Fitur Utama

- ✅ **Dashboard Interaktif** - Tampilan dashboard yang modern dengan statistik real-time
- ✅ **Manajemen Schedule** - CRUD lengkap untuk schedule produksi
- ✅ **Real-time Sync** - Sinkronisasi data real-time antar perangkat menggunakan Server-Sent Events (SSE) dan polling
- ✅ **Role-based Access Control** - Sistem akses berdasarkan role (Admin & Operator)
- ✅ **Audit Trail** - Logging aktivitas untuk tracking perubahan data
- ✅ **Responsive Design** - Tampilan yang optimal di desktop dan mobile
- ✅ **Airport Board View** - Tampilan board seperti airport display untuk monitoring
- ✅ **Export Report** - Export laporan ke Excel/CSV
- ✅ **Session Management** - Auto logout setelah 30 menit idle
- ✅ **CSRF Protection** - Perlindungan dari Cross-Site Request Forgery

## 🚀 Teknologi

- **Backend:** PHP 7.4+ dengan MySQLi
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Database:** MySQL 5.7+
- **Real-time:** Server-Sent Events (SSE) & AJAX Polling
- **Icons:** Font Awesome 6.5.1
- **Fonts:** Inter, Orbitron

## 📦 Instalasi

### Persyaratan

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Apache/Nginx web server
- Extension PHP: mysqli, json, session

### Langkah Instalasi

1. **Clone atau download repository**
   ```bash
   git clone <repository-url>
   cd rbmschedule
   ```

2. **Setup database**
   - Buat database baru di MySQL
   - Import file `database.sql` ke database
   ```bash
   mysql -u root -p rbm_schedule < database.sql
   ```

3. **Konfigurasi database**
   - Edit file `config/database.php`
   - Sesuaikan kredensial database:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'rbm_schedule');
   ```

4. **Set permissions**
   ```bash
   chmod -R 755 logs/
   chmod -R 755 assets/
   ```

5. **Akses aplikasi**
   - Buka browser dan akses: `http://localhost/rbmschedule`
   - Login dengan kredensial default (lihat bagian User Default)

## ☁️ Hosting ke VPS

Untuk deployment production di VPS, gunakan panduan dan template berikut:

- Panduan lengkap: `HOSTING_VPS.md`
- Panduan Docker: `HOSTING_DOCKER.md`
- Template Nginx: `deploy/nginx-rbmschedule.conf`
- Template Apache vHost: `deploy/apache-vhost-rbmschedule.conf`
- Script deploy cepat: `scripts/deploy_vps.sh`
- Contoh environment: `.env.example`
- Docker compose: `docker-compose.yml`

## 👤 User Default

Setelah import database, gunakan kredensial berikut untuk login:

**Admin:**
- Username: `admin`
- Password: `admin123` (ubah setelah login pertama!)

**Operator:**
- Username: `operator`
- Password: `operator123` (ubah setelah login pertama!)

> **⚠️ PENTING:** Segera ubah password default setelah login pertama untuk keamanan!

## 📁 Struktur Folder

```
rbmschedule/
├── api/                 # API endpoints
│   ├── check_updates.php
│   ├── get_schedules.php
│   ├── get_stats.php
│   ├── login_process.php
│   ├── logout.php
│   ├── schedule_ajax.php
│   └── ...
├── assets/              # Static assets
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   └── img/            # Images
├── classes/             # PHP Classes
│   ├── Logger.php      # Logging system
│   ├── Validator.php   # Input validation
│   └── Response.php    # JSON response handler
├── config/              # Configuration files
│   ├── database.php    # Database config
│   └── autoload.php    # Autoloader
├── helpers/             # Helper functions
│   └── functions.php   # Common functions
├── includes/            # Include files
│   ├── auth.php        # Authentication
│   ├── csrf.php        # CSRF protection
│   ├── header.php      # Page header
│   ├── footer.php      # Page footer
│   └── audit.php       # Audit logging
├── logs/                # Log files (auto-generated)
├── pages/               # Page files
│   ├── dashboard.php
│   ├── login.php
│   ├── manage.php
│   └── ...
└── README.md           # Dokumentasi
```

## 🔐 Keamanan

### Fitur Keamanan yang Diimplementasikan

1. **CSRF Protection** - Semua form dilindungi dengan CSRF token
2. **Prepared Statements** - Semua query menggunakan prepared statements untuk mencegah SQL injection
3. **Input Validation** - Validasi input di frontend dan backend
4. **Password Hashing** - Password di-hash menggunakan `password_hash()`
5. **Session Timeout** - Auto logout setelah 30 menit idle
6. **XSS Protection** - Output di-escape menggunakan `htmlspecialchars()`
7. **Role-based Access** - Akses dibatasi berdasarkan role user

### Best Practices

- Jangan expose kredensial database di production
- Gunakan HTTPS di production
- Set `DEBUG_MODE = false` di production
- Rutin backup database
- Monitor log files di folder `logs/`

## 🎨 Customization

### Mengubah Warna Tema

Edit file `assets/css/style.css` dan ubah CSS variables:

```css
:root {
    --primary-color: #4f46e5;
    --secondary-color: #64748b;
    /* ... */
}
```

### Menambah Operator

Edit file `pages/manage.php` atau `pages/dashboard.php` dan tambahkan option di select:

```html
<option value="Nama Operator">Nama Operator</option>
```

## 📊 API Endpoints

### Authentication
- `POST /api/login_process.php` - Login user
- `GET /api/logout.php` - Logout user

### Schedule Management
- `POST /api/schedule_ajax.php` - Create/Update/Delete schedule
- `GET /api/get_schedules.php` - Get list schedules
- `GET /api/get_stats.php` - Get statistics

### Real-time
- `GET /api/check_updates.php` - Check for updates
- `GET /api/updates_stream.php` - SSE stream for real-time updates

## 🐛 Troubleshooting

### Database Connection Error
- Pastikan MySQL service berjalan
- Cek kredensial di `config/database.php`
- Pastikan database sudah dibuat

### Real-time Sync Tidak Berfungsi
- Cek browser console untuk error
- Pastikan SSE supported di browser
- Cek file `logs/error.log` untuk detail error

### Session Expired
- Session timeout adalah 30 menit
- Login ulang jika session expired
- Cek `includes/auth.php` untuk konfigurasi timeout

## 📝 Changelog

### Version 1.0.0
- Initial release
- Dashboard dengan statistik
- CRUD schedule
- Real-time synchronization
- Role-based access control
- Audit trail
- Export report

## 👥 Kontribusi

Kontribusi sangat diterima! Silakan:
1. Fork repository
2. Buat feature branch
3. Commit perubahan
4. Push ke branch
5. Buat Pull Request

## 📄 License

Copyright © 2024 RBM. All rights reserved.

## 🆘 Support

Untuk bantuan dan support, silakan hubungi:
- Email: support@rbm.com
- Phone: +62-xxx-xxxx-xxxx

---

**Dikembangkan dengan ❤️ oleh Tim RBM**


