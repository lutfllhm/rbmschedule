# Docker Configuration Files

Folder ini berisi file-file konfigurasi untuk Docker containers.

---

## 📁 File dalam Folder Ini

### 1. `apache.conf`
Konfigurasi Apache Virtual Host untuk web server.

**Fitur:**
- Security headers (X-Frame-Options, X-XSS-Protection, dll)
- Deny access ke file sensitif (.env, .md, .sql, .log)
- Compression (gzip)
- Browser caching untuk static files
- Disable directory listing

**Lokasi di Container:** `/etc/apache2/sites-available/000-default.conf`

---

### 2. `php.ini`
Konfigurasi PHP untuk aplikasi.

**Settings:**
- Memory limit: 256MB
- Execution time: 300 seconds
- Upload size: 64MB
- Timezone: Asia/Jakarta
- OPcache enabled
- Error logging
- Session configuration

**Lokasi di Container:** `/usr/local/etc/php/conf.d/custom.ini`

---

### 3. `mysql-custom.cnf`
Konfigurasi MySQL untuk database server.

**Settings:**
- Character set: utf8mb4
- Timezone: +07:00 (WIB)
- Performance tuning (buffer pool, connections)
- Binary logging untuk backup
- Slow query log

**Lokasi di Container:** `/etc/mysql/conf.d/custom.cnf`

---

## 🔧 Cara Edit Konfigurasi

### Jika Ingin Mengubah PHP Settings:

```bash
# Edit file
nano docker/php.ini

# Rebuild container
docker compose build rbm-web
docker compose up -d rbm-web
```

### Jika Ingin Mengubah Apache Settings:

```bash
# Edit file
nano docker/apache.conf

# Rebuild container
docker compose build rbm-web
docker compose up -d rbm-web
```

### Jika Ingin Mengubah MySQL Settings:

```bash
# Edit file
nano docker/mysql-custom.cnf

# Rebuild container
docker compose down
docker compose up -d
```

---

## ⚠️ Catatan Penting

1. **Backup dulu** sebelum edit konfigurasi
2. **Test** di environment development sebelum production
3. **Restart container** setelah mengubah konfigurasi
4. **Monitor logs** untuk memastikan tidak ada error setelah perubahan

---

## 📚 Referensi

- [PHP Configuration](https://www.php.net/manual/en/ini.core.php)
- [Apache Configuration](https://httpd.apache.org/docs/2.4/)
- [MySQL Configuration](https://dev.mysql.com/doc/refman/8.0/en/server-configuration.html)

---

**⚙️ Konfigurasi ini sudah dioptimasi untuk production use!**
