# Deploy RBM Schedule with Docker

Panduan ini menjelaskan cara menjalankan aplikasi `rbmschedule` di VPS menggunakan Docker dan Docker Compose dengan satu file markdown.

## 1. File yang disiapkan
- `Dockerfile`
- `docker-compose.yml`
- `.dockerignore`
- `.env.example`

## 2. Struktur dasar deployment
- Aplikasi PHP berjalan di service `app` berdasarkan image `php:8.1-apache`
- Database MySQL berjalan di service `db` menggunakan image `mysql:8.0`
- `database.sql` di-mount ke `/docker-entrypoint-initdb.d/` agar database otomatis terinisialisasi saat pertama kali container dijalankan

## 3. Persiapan VPS
1. Login ke VPS.
2. Pastikan Docker dan Docker Compose sudah terpasang.
   - Cek Docker:
     ```bash
     docker --version
     ```
   - Cek Docker Compose:
     ```bash
     docker compose version
     ```
3. Copy seluruh folder `rbmschedule` ke direktori kerja di VPS, misalnya `/srv/rbmschedule`.

## 4. Konfigurasi environment
1. Buat salinan file `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   ```
2. Sesuaikan nilai jika diperlukan.
   - `DB_HOST=db`
   - `DB_USER=rbm_user`
   - `DB_PASS=rbm_password`
   - `DB_NAME=rbm_schedule`
   - `DEBUG_MODE=false`
3. Jangan commit file `.env` ke Git.

## 5. Menjalankan Docker Compose
Jalankan perintah berikut dari direktori root aplikasi (`dalam folder rbmschedule`):

```bash
docker compose up -d --build
```

Perintah ini akan:
- membangun image PHP + Apache
- menjalankan container aplikasi
- menjalankan container MySQL
- menginisialisasi database dari `database.sql` pada run pertama

## 6. Akses aplikasi
Buka browser dan akses alamat VPS Anda pada port 80, misalnya:

```text
http://<IP_VPS>
```

## 7. Verifikasi dan troubleshooting
- Cek container berjalan:
  ```bash
  docker compose ps
  ```
- Lihat log aplikasi:
  ```bash
  docker compose logs -f app
  ```
- Lihat log database:
  ```bash
  docker compose logs -f db
  ```

## 8. Menyegarkan deploy / update aplikasi
Jika ada perubahan kode baru:
1. Tarik perubahan ke folder aplikasi di VPS.
2. Restart service:
   ```bash
   docker compose up -d --build
   ```

## 9. Keamanan production
- Pastikan `DEBUG_MODE=false`.
- Gunakan password yang kuat untuk `MYSQL_ROOT_PASSWORD` dan `DB_PASS`.
- Batasi akses port MySQL jika tidak dibutuhkan publik.
- Aktifkan firewall untuk port 80/443.

## 10. Catatan tambahan
- Jika ingin menambahkan SSL, letakkan reverse proxy Nginx atau gunakan Cloudflare di depan service Docker.
- `logs/` akan tetap disimpan di dalam container jika tidak di-mount ke host. Jika perlu, tambahkan volume mapping pada `docker-compose.yml`.
