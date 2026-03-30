# Cara Membuka Security Group / Firewall Cloud Provider

## Masalah Teridentifikasi

✅ Aplikasi berjalan normal di VPS
✅ Firewall UFW sudah benar
✅ Port 80 dan 443 sudah listening
❌ **Akses dari luar GAGAL karena Security Group cloud provider**

## IP VPS Anda: 148.230.100.44

## Solusi Berdasarkan Provider

### 1. AWS EC2

1. Login ke AWS Console: https://console.aws.amazon.com/
2. Pilih region yang sesuai (lihat di pojok kanan atas)
3. Services → EC2 → Instances
4. Pilih instance dengan IP `148.230.100.44`
5. Tab "Security" (di bawah)
6. Klik nama Security Group (contoh: sg-xxxxx)
7. Tab "Inbound rules" → Edit inbound rules
8. Add rule:
   - Type: HTTP
   - Protocol: TCP
   - Port range: 80
   - Source: 0.0.0.0/0
   - Description: Allow HTTP
9. Add rule:
   - Type: HTTPS
   - Protocol: TCP
   - Port range: 443
   - Source: 0.0.0.0/0
   - Description: Allow HTTPS
10. Save rules
11. Tunggu 1-2 menit
12. Test: http://148.230.100.44/

### 2. DigitalOcean

1. Login ke DigitalOcean: https://cloud.digitalocean.com/
2. Networking → Firewalls
3. Pilih firewall yang attach ke droplet Anda
   - Atau klik droplet → Networking → Firewalls
4. Inbound Rules → New rule
5. Tambahkan:
   - Type: HTTP
   - Protocol: TCP
   - Port Range: 80
   - Sources: All IPv4, All IPv6
6. Tambahkan:
   - Type: HTTPS
   - Protocol: TCP
   - Port Range: 443
   - Sources: All IPv4, All IPv6
7. Save
8. Test: http://148.230.100.44/

### 3. Google Cloud Platform (GCP)

1. Login ke GCP Console: https://console.cloud.google.com/
2. Navigation menu → VPC Network → Firewall
3. Create Firewall Rule
4. Name: allow-http-https
5. Targets: All instances in the network
6. Source IP ranges: 0.0.0.0/0
7. Protocols and ports:
   - Specified protocols and ports
   - tcp: 80,443
8. Create
9. Test: http://148.230.100.44/

### 4. Vultr

1. Login ke Vultr: https://my.vultr.com/
2. Products → Pilih server Anda
3. Settings → Firewall
4. Add Firewall Group (jika belum ada)
5. Add Rule:
   - Protocol: TCP
   - Port: 80
   - Source: Anywhere (0.0.0.0/0)
6. Add Rule:
   - Protocol: TCP
   - Port: 443
   - Source: Anywhere (0.0.0.0/0)
7. Apply to server
8. Test: http://148.230.100.44/

### 5. Contabo

1. Login ke Contabo: https://my.contabo.com/
2. Your Services → VPS
3. Pilih VPS Anda
4. Firewall Settings
5. Add Rule:
   - Port: 80
   - Protocol: TCP
   - Source: 0.0.0.0/0
6. Add Rule:
   - Port: 443
   - Protocol: TCP
   - Source: 0.0.0.0/0
7. Apply
8. Test: http://148.230.100.44/

### 6. Linode (Akamai)

1. Login ke Linode: https://cloud.linode.com/
2. Linodes → Pilih Linode Anda
3. Firewalls tab
4. Add a Firewall (jika belum ada)
5. Inbound Rules → Add Rule:
   - Type: HTTP
   - Protocol: TCP
   - Port: 80
   - Sources: All IPv4 / All IPv6
6. Add Rule:
   - Type: HTTPS
   - Protocol: TCP
   - Port: 443
   - Sources: All IPv4 / All IPv6
7. Save
8. Test: http://148.230.100.44/

### 7. Hetzner Cloud

1. Login ke Hetzner: https://console.hetzner.cloud/
2. Pilih project Anda
3. Firewalls (menu kiri)
4. Pilih firewall atau Create Firewall
5. Inbound Rules → Add Rule:
   - Protocol: TCP
   - Port: 80
   - Source IPs: 0.0.0.0/0, ::/0
6. Add Rule:
   - Protocol: TCP
   - Port: 443
   - Source IPs: 0.0.0.0/0, ::/0
7. Apply to server
8. Test: http://148.230.100.44/

### 8. OVH

1. Login ke OVH: https://www.ovh.com/manager/
2. Bare Metal Cloud → VPS
3. Pilih VPS Anda
4. Firewall → Configure
5. Add Rule:
   - Action: Allow
   - Protocol: TCP
   - Port: 80
   - Source: 0.0.0.0/0
6. Add Rule:
   - Action: Allow
   - Protocol: TCP
   - Port: 443
   - Source: 0.0.0.0/0
7. Apply
8. Test: http://148.230.100.44/

### 9. Azure

1. Login ke Azure Portal: https://portal.azure.com/
2. Virtual machines → Pilih VM Anda
3. Networking (menu kiri)
4. Inbound port rules → Add inbound port rule
5. Tambahkan:
   - Source: Any
   - Source port ranges: *
   - Destination: Any
   - Service: HTTP
   - Action: Allow
   - Priority: 100
   - Name: Allow-HTTP
6. Tambahkan:
   - Source: Any
   - Source port ranges: *
   - Destination: Any
   - Service: HTTPS
   - Action: Allow
   - Priority: 101
   - Name: Allow-HTTPS
7. Save
8. Test: http://148.230.100.44/

### 10. Provider Lain / Tidak Tahu Provider

Cari menu dengan nama:
- Security Group
- Firewall
- Network Security
- Access Control
- Port Management

Lalu buka port:
- Port 80 (HTTP) - Source: 0.0.0.0/0 atau Anywhere
- Port 443 (HTTPS) - Source: 0.0.0.0/0 atau Anywhere

## Cara Test Setelah Buka Security Group

### Test dari VPS (sudah pasti berhasil):
```bash
curl -I http://localhost/
```

### Test dari komputer lain:
```bash
curl -I http://148.230.100.44/
```

### Test dari browser:
```
http://148.230.100.44/
```

Harus redirect ke login page!

### Test dari online tool:
1. Buka: https://tools.keycdn.com/curl
2. Masukkan: http://148.230.100.44/
3. Klik Test
4. Harus dapat HTTP 302 atau 200

## Jika Sudah Buka Security Group Tapi Masih Gagal

1. Tunggu 2-5 menit (propagasi firewall rules)
2. Restart Docker:
   ```bash
   cd /var/www/rbmschedule
   docker compose restart
   ```
3. Clear browser cache (Ctrl+Shift+Delete)
4. Test pakai browser incognito/private
5. Test pakai HP/device lain

## Setelah Berhasil

1. Setup domain DNS:
   - A record: labelrbm.iwareid.com → 148.230.100.44
   
2. Setup SSL (opsional):
   - Ikuti panduan di HOSTING_DOCKER.md bagian 8

3. Ganti password di .env:
   ```bash
   nano /var/www/rbmschedule/.env
   ```

## Bantuan Lebih Lanjut

Jika masih gagal setelah buka Security Group:
1. Screenshot halaman Security Group/Firewall rules
2. Hasil dari: `curl -I http://148.230.100.44/` (dari komputer lain)
3. Hasil dari online tool test
4. Nama provider VPS Anda

Kirim ke tim support atau developer.

---

**PENTING**: Masalah Anda 100% di Security Group cloud provider. Setelah dibuka, website langsung bisa diakses!
