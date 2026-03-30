# Panduan Troubleshooting - ERR_CONNECTION_TIMED_OUT

## Masalah: Website tidak bisa diakses (Connection Timed Out)

### Langkah 1: Cek Status Container Docker

```bash
cd /opt/rbmschedule
docker compose ps
```

Pastikan semua container status `Up`:
- rbmschedule-nginx
- rbmschedule-app  
- rbmschedule-mysql

Jika ada yang `Exit` atau `Restarting`:

```bash
docker compose logs --tail=100 nginx
docker compose logs --tail=100 app
docker compose logs --tail=100 mysql
```

### Langkah 2: Restart Container

```bash
docker compose down
docker compose up -d --build
```

Tunggu 30 detik, lalu cek lagi:

```bash
docker compose ps
```

### Langkah 3: Cek Port Terbuka di VPS

```bash
# Cek apakah nginx listening di port 80
sudo netstat -tlnp | grep :80

# Atau pakai ss
sudo ss -tlnp | grep :80
```

Harus ada output seperti:
```
tcp   0   0 0.0.0.0:80   0.0.0.0:*   LISTEN   12345/docker-proxy
```

### Langkah 4: Cek Firewall UFW

```bash
sudo ufw status
```

Pastikan port 80 dan 443 terbuka:

```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw reload
```

### Langkah 5: Cek Security Group Cloud Provider

Jika VPS di cloud (AWS, DigitalOcean, dll):
- Masuk ke dashboard cloud provider
- Cek Security Group / Firewall rules
- Pastikan port 80 (HTTP) dan 443 (HTTPS) terbuka untuk 0.0.0.0/0

### Langkah 6: Test Koneksi dari Dalam VPS

```bash
# Test dari dalam VPS
curl -I http://localhost/
curl -I http://127.0.0.1/

# Test ke container langsung
curl -I http://localhost:80/
```

Jika berhasil dari dalam tapi gagal dari luar = masalah firewall/security group.

### Langkah 7: Cek DNS Domain

```bash
nslookup labelrbm.iwareid.com
```

Pastikan IP yang muncul = IP publik VPS Anda.

Jika salah, update DNS A record di domain provider.

### Langkah 8: Test Akses Langsung via IP

Buka browser, akses:
```
http://IP_PUBLIK_VPS/
```

Jika berhasil = masalah di DNS.
Jika gagal = masalah di firewall/container.

### Langkah 9: Cek Log Nginx Container

```bash
docker compose exec nginx cat /var/log/nginx/error.log
docker compose exec nginx cat /var/log/nginx/access.log
```

### Langkah 10: Restart Nginx Host (jika pakai reverse proxy)

Jika sudah setup nginx host + SSL:

```bash
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl status nginx
```

## Solusi Cepat (Quick Fix)

Jalankan perintah ini secara berurutan:

```bash
# 1. Masuk ke folder project
cd /opt/rbmschedule

# 2. Stop semua container
docker compose down

# 3. Rebuild dan start ulang
docker compose up -d --build

# 4. Tunggu 30 detik
sleep 30

# 5. Cek status
docker compose ps

# 6. Cek log jika ada error
docker compose logs --tail=50

# 7. Buka firewall
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# 8. Test dari dalam VPS
curl -I http://localhost/

# 9. Test dari luar (ganti dengan IP VPS Anda)
curl -I http://IP_VPS_ANDA/
```

## Kontak Darurat

Jika masih gagal, kirim output dari perintah ini:

```bash
docker compose ps
docker compose logs --tail=100
sudo ufw status
sudo netstat -tlnp | grep :80
nslookup labelrbm.iwareid.com
curl -I http://localhost/
```
