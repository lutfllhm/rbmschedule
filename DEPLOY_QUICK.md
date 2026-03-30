# Panduan Deploy Cepat - RBM Schedule

## Untuk Memperbaiki Error "Connection Timed Out"

### Opsi 1: Jalankan Script Otomatis (Tercepat)

```bash
cd /opt/rbmschedule
bash scripts/fix_connection.sh
```

Script ini akan otomatis:
- Cek Docker terinstall
- Stop dan rebuild container
- Buka firewall port 80 dan 443
- Test koneksi
- Tampilkan status dan URL akses

### Opsi 2: Manual Step-by-Step

#### 1. Masuk ke folder project

```bash
cd /opt/rbmschedule
```

#### 2. Pastikan file .env ada

```bash
# Jika belum ada, copy dari example
cp .env.docker.example .env

# Edit password jika perlu
nano .env
```

#### 3. Stop dan rebuild container

```bash
docker compose down
docker compose up -d --build
```

#### 4. Tunggu 30 detik dan cek status

```bash
sleep 30
docker compose ps
```

Semua container harus status `Up`.

#### 5. Buka firewall

```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw reload
```

#### 6. Test dari dalam VPS

```bash
curl -I http://localhost/
```

Harus dapat response HTTP 200/301/302.

#### 7. Cek IP publik VPS

```bash
curl ifconfig.me
```

#### 8. Test dari browser

Buka browser, akses:
```
http://IP_PUBLIK_VPS/
```

Jika berhasil, lanjut setup domain.

### Opsi 3: Cek Security Group Cloud Provider

Jika langkah di atas sudah dilakukan tapi masih gagal:

#### AWS EC2
1. Masuk AWS Console
2. EC2 → Security Groups
3. Pilih security group VPS Anda
4. Inbound Rules → Edit
5. Tambahkan:
   - Type: HTTP, Port: 80, Source: 0.0.0.0/0
   - Type: HTTPS, Port: 443, Source: 0.0.0.0/0

#### DigitalOcean
1. Masuk DigitalOcean Dashboard
2. Networking → Firewalls
3. Pilih firewall VPS Anda
4. Inbound Rules → Add Rule
5. Tambahkan:
   - Type: HTTP, Port: 80, Sources: All IPv4, All IPv6
   - Type: HTTPS, Port: 443, Sources: All IPv4, All IPv6

#### Google Cloud
1. Masuk GCP Console
2. VPC Network → Firewall
3. Create Firewall Rule
4. Targets: All instances
5. Source IP ranges: 0.0.0.0/0
6. Protocols and ports: tcp:80,443

#### Vultr / Linode / Other
Cek dokumentasi provider untuk membuka port 80 dan 443.

## Setup Domain (Setelah IP Bisa Diakses)

### 1. Update DNS

Di domain provider (Namecheap, Cloudflare, dll):
- Buat A record: `labelrbm.iwareid.com` → IP_PUBLIK_VPS

Tunggu 5-15 menit untuk propagasi DNS.

### 2. Verifikasi DNS

```bash
nslookup labelrbm.iwareid.com
```

IP harus sama dengan IP VPS.

### 3. Test akses domain

```bash
curl -I http://labelrbm.iwareid.com/
```

### 4. Setup SSL (Opsional tapi disarankan)

Ikuti panduan di `HOSTING_DOCKER.md` bagian 8 untuk setup:
- Nginx reverse proxy
- Let's Encrypt SSL
- Auto-redirect HTTP ke HTTPS

## Troubleshooting

Jika masih gagal, jalankan diagnostic:

```bash
# Cek semua container
docker compose ps

# Cek log error
docker compose logs --tail=100 nginx
docker compose logs --tail=100 app
docker compose logs --tail=100 mysql

# Cek port listening
sudo netstat -tlnp | grep :80

# Cek firewall
sudo ufw status

# Cek koneksi dari dalam
curl -v http://localhost/

# Cek IP publik
curl ifconfig.me
```

Kirim output dari perintah di atas jika butuh bantuan lebih lanjut.

## URL Akses Akhir

Setelah semua setup selesai:

- Via IP: `http://IP_PUBLIK_VPS/`
- Via Domain (HTTP): `http://labelrbm.iwareid.com/`
- Via Domain (HTTPS): `https://labelrbm.iwareid.com/` (setelah setup SSL)

Login default:
- Username: admin
- Password: (cek di database atau dokumentasi)

## Catatan Penting

1. Aplikasi sekarang berjalan di root domain, bukan `/rbmschedule`
2. Pastikan semua link internal sudah menggunakan relative path
3. Jika ada hardcoded path `/rbmschedule`, perlu diupdate
4. Backup database secara berkala
5. Ganti password default di `.env`
6. Set `DEBUG_MODE=false` untuk production

## Bantuan Lebih Lanjut

Baca dokumentasi lengkap:
- `HOSTING_DOCKER.md` - Panduan lengkap Docker deployment
- `TROUBLESHOOTING.md` - Panduan troubleshooting detail
- `README.md` - Dokumentasi aplikasi

Atau hubungi tim development.
