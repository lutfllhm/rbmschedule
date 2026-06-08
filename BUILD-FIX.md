# 🔧 Docker Build Error - Fixed!

## ❌ Error yang Terjadi

```
chmod: cannot access '/var/www/html/backup': No such file or directory
```

## 🔍 Penyebab

Dockerfile mencoba set permission ke folder `/var/www/html/backup` yang belum ada saat build.

## ✅ Solusi

Dockerfile sudah diperbaiki! Sekarang folder dibuat dulu sebelum set permission.

### Perubahan:

**Sebelum:**
```dockerfile
# Set proper permissions (folder backup belum ada!)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/backup  # ❌ Error di sini

# Create logs directory
RUN mkdir -p /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/logs
```

**Sesudah:**
```dockerfile
# Create required directories first
RUN mkdir -p /var/www/html/backup \
    && mkdir -p /var/www/html/logs \
    && mkdir -p /var/www/html/sessions

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/backup \
    && chmod -R 775 /var/www/html/logs \
    && chmod -R 775 /var/www/html/sessions
```

## 🚀 Build Ulang

Sekarang build lagi:

```bash
# Build image
docker compose build

# Atau build langsung
docker build -t rbm-schedule .

# Start containers
docker compose up -d

# Cek status
docker compose ps
```

## ✅ Seharusnya Berhasil!

Build sekarang akan berhasil karena:
1. ✅ Folder `backup` dibuat dulu
2. ✅ Folder `logs` dibuat dulu
3. ✅ Folder `sessions` dibuat dulu
4. ✅ Baru kemudian set permissions

---

**Status:** ✅ Fixed  
**Action Required:** Build ulang dengan `docker compose build`
