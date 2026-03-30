# Solusi Error "Connection Timed Out" - RBM Schedule

## Ringkasan Masalah

Website `labelrbm.iwareid.com/rbmschedule` tidak bisa diakses dengan error:
- **ERR_CONNECTION_TIMED_OUT**
- "This site can't be reached"
- "took too long to respond"

## Penyebab Umum

1. Container Docker tidak berjalan
2. Port 80/443 tidak terbuka di firewall VPS
3. Security Group cloud provider memblokir akses
4. DNS belum mengarah ke IP VPS yang benar
5. Nginx configuration error

## Solusi yang Sudah Disiapkan

### ✅ File Baru yang Dibuat:

1. **scripts/fix_connection.sh**
   - Script otomatis untuk fix connection issue
   - Cek Docker, rebuild container, buka firewall
   - Test koneksi dan tampilkan status

2. **DEPLOY_QUICK.md**
   - Panduan deploy cepat step-by-step
   - 3 opsi: Script otomatis, manual, cloud provider
   - Troubleshooting lengkap

3. **TROUBLESHOOTING.md**
   - Panduan troubleshooting detail
   - 10 langkah diagnostic
   - Solusi untuk berbagai error

4. **.env**
   - File environment untuk Docker
   - Sudah dikonfigurasi dengan nilai default
   - GANTI PASSWORD sebelum production!

5. **config/paths.php**
   - Konfigurasi path terpusat
   - Helper functions untuk URL dinamis
   - Mudah switch antara root dan subdirectory

6. **MIGRATION_ROOT_PATH.md**
   - Panduan migrasi dari /rbmschedule ke root
   - Contoh update code
   - Daftar file yang perlu diupdate

### ✅ File yang Sudah Diperbaiki:

1. **docker/nginx/default.conf**
   - Root path diperbaiki
   - Routing disederhanakan
   - Security headers ditambahkan

2. **index.php**
   - Path redirect menggunakan relative path
   - Tidak hardcode /rbmschedule lagi

3. **config/autoload.php**
   - Include paths.php otomatis

## Langkah Cepat untuk Fix (JALANKAN INI!)

### Opsi 1: Script Otomatis (TERCEPAT)

```bash
# 1. SSH ke VPS
ssh user@IP_VPS

# 2. Masuk ke folder project
cd /opt/rbmschedule

# 3. Upload file terbaru dari local
# (Jalankan di komputer local, bukan di VPS)
rsync -av --delete --exclude='.git' --exclude='.kombai' ./ user@IP_VPS:/opt/rbmschedule/

# 4. Kembali ke VPS, jalankan script fix
cd /opt/rbmschedule
bash scripts/fix_connection.sh
```

Script akan otomatis:
- ✅ Cek Docker terinstall
- ✅ Stop dan rebuild container
- ✅ Buka firewall port 80 dan 443
- ✅ Test koneksi
- ✅ Tampilkan URL akses

### Opsi 2: Manual (Jika script gagal)

```bash
# 1. Masuk folder project
cd /opt/rbmschedule

# 2. Stop container
docker compose down

# 3. Rebuild dan start
docker compose up -d --build

# 4. Tunggu 30 detik
sleep 30

# 5. Cek status
docker compose ps

# 6. Buka firewall
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# 7. Test dari dalam VPS
curl -I http://localhost/

# 8. Cek IP publik
curl ifconfig.me

# 9. Test dari browser
# Buka: http://IP_PUBLIK_VPS/
```

### Opsi 3: Cek Security Group (Jika masih gagal)

Jika VPS di cloud provider (AWS, DigitalOcean, Google Cloud, dll):

1. Masuk ke dashboard cloud provider
2. Cari menu Security Group / Firewall
3. Tambahkan inbound rules:
   - Port 80 (HTTP) - Source: 0.0.0.0/0
   - Port 443 (HTTPS) - Source: 0.0.0.0/0
4. Save dan tunggu 1-2 menit
5. Test lagi dari browser

## Setelah Berhasil Diakses

### 1. Setup Domain

```bash
# Cek DNS sudah benar
nslookup labelrbm.iwareid.com

# Harus mengarah ke IP VPS
```

Jika belum:
- Masuk ke domain provider (Namecheap, Cloudflare, dll)
- Buat A record: `labelrbm.iwareid.com` → `IP_VPS`
- Tunggu 5-15 menit

### 2. Setup SSL (Opsional tapi disarankan)

Ikuti panduan di `HOSTING_DOCKER.md` bagian 8:
- Install Certbot
- Generate SSL certificate
- Auto-redirect HTTP ke HTTPS

### 3. Ganti Password Default

Edit file `.env`:
```bash
nano .env
```

Ganti:
```env
DB_PASS=rbm_secure_password_2024
MYSQL_ROOT_PASSWORD=root_secure_password_2024
```

Dengan password yang kuat!

Lalu restart:
```bash
docker compose down
docker compose up -d
```

### 4. Set Production Mode

Edit `.env`:
```env
APP_ENV=production
DEBUG_MODE=false
```

## URL Akses Akhir

Setelah semua setup:

- **Via IP**: `http://IP_PUBLIK_VPS/`
- **Via Domain**: `http://labelrbm.iwareid.com/`
- **Via HTTPS**: `https://labelrbm.iwareid.com/` (setelah setup SSL)

## Catatan Penting

1. ⚠️ Aplikasi sekarang berjalan di **root domain**, bukan `/rbmschedule`
2. ⚠️ Jika ada error 404 di asset/link, baca `MIGRATION_ROOT_PATH.md`
3. ⚠️ Backup database secara berkala
4. ⚠️ Ganti semua password default
5. ⚠️ Set `DEBUG_MODE=false` untuk production

## Jika Masih Gagal

Jalankan diagnostic dan kirim output:

```bash
# Cek semua container
docker compose ps

# Cek log
docker compose logs --tail=100 nginx
docker compose logs --tail=100 app
docker compose logs --tail=100 mysql

# Cek port
sudo netstat -tlnp | grep :80

# Cek firewall
sudo ufw status

# Cek koneksi
curl -v http://localhost/

# Cek IP publik
curl ifconfig.me

# Cek DNS
nslookup labelrbm.iwareid.com
```

## Dokumentasi Lengkap

- **DEPLOY_QUICK.md** - Panduan deploy cepat
- **HOSTING_DOCKER.md** - Panduan lengkap Docker deployment
- **TROUBLESHOOTING.md** - Panduan troubleshooting detail
- **MIGRATION_ROOT_PATH.md** - Panduan migrasi path
- **README.md** - Dokumentasi aplikasi

## Kontak

Jika butuh bantuan lebih lanjut, kirim:
1. Output dari perintah diagnostic di atas
2. Screenshot error
3. Log dari `docker compose logs`

---

**Dibuat**: 30 Maret 2026
**Untuk**: labelrbm.iwareid.com deployment
**Status**: Ready to deploy
