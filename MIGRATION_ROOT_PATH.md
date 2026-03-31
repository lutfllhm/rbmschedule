# Migrasi dari /rbmschedule ke Root Domain

## Perubahan yang Sudah Dilakukan

Aplikasi sekarang sudah dikonfigurasi untuk berjalan di root domain (bukan `/rbmschedule`).

### File yang Sudah Diupdate:

1. **config/paths.php** (BARU)
   - File konfigurasi terpusat untuk semua path
   - `BASE_URL` diset ke `''` (root domain)
   - Helper functions: `url()`, `asset()`, `page()`, `api()`

2. **config/autoload.php**
   - Sudah include `paths.php` otomatis

3. **docker/nginx/default.conf**
   - Root path diubah ke `/var/www/html/rbmschedule`
   - Routing langsung ke root, bukan `/rbmschedule/`

4. **index.php**
   - Redirect path sudah menggunakan relative path

## Cara Menggunakan Helper Functions

### Sebelum (Hardcoded):
```php
<link rel="stylesheet" href="/rbmschedule/assets/css/style.css">
<img src="/rbmschedule/assets/img/rbm.png">
<a href="/rbmschedule/pages/dashboard.php">Dashboard</a>
<form action="/rbmschedule/api/schedule_create.php">
```

### Sesudah (Dynamic):
```php
<link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
<img src="<?php echo asset('img/rbm.png'); ?>">
<a href="<?php echo page('dashboard.php'); ?>">Dashboard</a>
<form action="<?php echo api('schedule_create.php'); ?>">
```

### Atau lebih singkat:
```php
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<img src="<?= asset('img/rbm.png') ?>">
<a href="<?= page('dashboard.php') ?>">Dashboard</a>
<form action="<?= api('schedule_create.php') ?>">
```

## File yang Perlu Diupdate Manual

Karena ada banyak file dengan hardcoded path, berikut daftar file yang perlu diupdate:

### Priority 1 (Critical - Harus diupdate):
- [ ] pages/dashboard.php
- [ ] pages/manage.php
- [ ] pages/report.php
- [ ] pages/display.php
- [ ] pages/display_32.php
- [ ] pages/display_view.php
- [ ] includes/header.php
- [ ] includes/footer.php

### Priority 2 (API endpoints):
- [ ] api/schedule_create.php
- [ ] api/schedule_update.php
- [ ] api/schedule_delete.php
- [ ] api/schedule_ajax.php
- [ ] api/login_process.php
- [ ] api/logout.php

### Priority 3 (Diagnostic/Demo):
- [ ] diagnostic-realtime.php
- [ ] demo-sync.php

## Contoh Update File

### Contoh 1: Update CSS/JS Links

**Sebelum:**
```php
<link rel="stylesheet" href="/rbmschedule/assets/css/style.css">
<script src="/rbmschedule/assets/js/script.js"></script>
```

**Sesudah:**
```php
<?php require_once __DIR__ . '/../config/autoload.php'; ?>
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<script src="<?= asset('js/script.js') ?>"></script>
```

### Contoh 2: Update Image Paths

**Sebelum:**
```php
<img src="/rbmschedule/assets/img/rbm.png" alt="RBM Logo">
```

**Sesudah:**
```php
<img src="<?= asset('img/rbm.png') ?>" alt="RBM Logo">
```

### Contoh 3: Update Links

**Sebelum:**
```php
<a href="/rbmschedule/pages/dashboard.php">Dashboard</a>
```

**Sesudah:**
```php
<a href="<?= page('dashboard.php') ?>">Dashboard</a>
```

### Contoh 4: Update Form Actions

**Sebelum:**
```php
<form action="/rbmschedule/api/schedule_create.php" method="POST">
```

**Sesudah:**
```php
<form action="<?= api('schedule_create.php') ?>" method="POST">
```

### Contoh 5: Update JavaScript Fetch

**Sebelum:**
```javascript
fetch('/rbmschedule/api/check_updates.php')
```

**Sesudah:**
```javascript
fetch('<?= api("check_updates.php") ?>')
```

Atau jika di file .js murni, buat variable global di HTML:
```php
<script>
const API_BASE = '<?= API_URL ?>';
</script>
<script src="<?= asset('js/script.js') ?>"></script>
```

Lalu di script.js:
```javascript
fetch(`${API_BASE}/check_updates.php`)
```

## Cara Deploy Setelah Update

### 1. Update semua file yang perlu diubah

### 2. Test di local/development:
```bash
docker compose down
docker compose up -d --build
```

### 3. Akses via browser:
```
http://localhost/
```

Bukan `http://localhost/rbmschedule/`

### 4. Deploy ke production:
```bash
rsync -av --delete --exclude='.git' ./ user@vps:/opt/rbmschedule/
ssh user@vps "cd /opt/rbmschedule && docker compose up -d --build"
```

### 5. Test production:
```
https://labelrbm.iwareid.com/
```

## Rollback ke /rbmschedule (Jika Diperlukan)

Jika ingin kembali ke path `/rbmschedule`:

1. Edit `config/paths.php`:
```php
define('BASE_URL', '/rbmschedule');
```

2. Edit `docker/nginx/default.conf`:
```nginx
root /var/www/html;

location = / {
    return 301 /rbmschedule/;
}

location /rbmschedule/ {
    try_files $uri $uri/ /rbmschedule/index.php?$query_string;
}
```

3. Rebuild container:
```bash
docker compose up -d --build
```

## Keuntungan Migrasi ke Root

1. URL lebih bersih: `labelrbm.iwareid.com` vs `labelrbm.iwareid.com/rbmschedule`
2. Lebih profesional
3. Lebih mudah diingat user
4. SEO lebih baik
5. Konfigurasi SSL lebih sederhana

## Catatan Penting

- Pastikan semua file sudah include `config/autoload.php` atau `config/paths.php`
- Test semua fitur setelah update (login, CRUD, realtime, export)
- Backup database sebelum deploy ke production
- Update bookmark/shortcut user jika ada

## Bantuan

Jika ada masalah setelah migrasi:
1. Cek log: `docker compose logs -f nginx app`
2. Cek browser console untuk error JavaScript
3. Cek network tab untuk failed requests
4. Rollback ke `/rbmschedule` jika critical

## Status Update

- [x] Config paths.php dibuat
- [x] Autoload updated
- [x] Nginx config updated
- [x] index.php updated
- [x] Pages updated (dashboard, manage, report, display, display_32, display_view)
- [x] API updated (schedule_create, schedule_update, schedule_delete)
- [x] JavaScript updated (BASE_URL variable added)
- [x] Header/Footer updated
- [x] All hardcoded /rbmschedule paths replaced with dynamic helpers

## Update Terakhir

Semua file sudah diupdate untuk menggunakan root domain. Perubahan yang dilakukan:

1. **config/paths.php**: BASE_URL diubah dari `/rbmschedule` ke `''` (root domain)
2. **Semua file pages/**: Menggunakan helper functions (IMG_URL, CSS_URL, JS_URL, getPath)
3. **Semua file api/**: Redirect menggunakan getPath() helper
4. **JavaScript files**: Menambahkan BASE_URL variable untuk fetch API
5. **Display files**: Menambahkan require paths.php dan BASE_URL JavaScript variable
