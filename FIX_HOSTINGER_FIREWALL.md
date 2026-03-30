# Cara Membuka Firewall Hostinger VPS KVM 2

## IP VPS Anda: 148.230.100.44

## Masalah Teridentifikasi

✅ Aplikasi berjalan normal di VPS
✅ Firewall UFW di VPS sudah benar
✅ Port 80 dan 443 sudah listening
❌ **Firewall Hostinger di control panel yang block akses dari luar**

## Solusi: Buka Firewall di Hostinger Control Panel

### Langkah 1: Login ke Hostinger hPanel

1. Buka: https://hpanel.hostinger.com/
2. Login dengan akun Hostinger Anda
3. Pilih menu **VPS** di sidebar kiri

### Langkah 2: Masuk ke VPS Management

1. Klik VPS Anda (yang IP-nya `148.230.100.44`)
2. Atau cari berdasarkan nama VPS

### Langkah 3: Buka Menu Firewall

Ada 2 kemungkinan lokasi menu firewall di Hostinger:

#### Opsi A: Menu Firewall Langsung
1. Di dashboard VPS, cari menu **Firewall** atau **Security**
2. Klik untuk masuk ke firewall settings

#### Opsi B: Menu Advanced/Settings
1. Klik tab **Settings** atau **Advanced**
2. Scroll ke bawah cari **Firewall Configuration**
3. Klik **Manage Firewall** atau **Configure**

### Langkah 4: Tambah Firewall Rules

Di halaman Firewall Configuration:

#### Rule 1: Allow HTTP (Port 80)
- **Action**: Allow / Accept
- **Protocol**: TCP
- **Port**: 80
- **Source**: 0.0.0.0/0 atau "Anywhere" atau "All"
- **Description**: Allow HTTP traffic
- Klik **Add Rule** atau **Save**

#### Rule 2: Allow HTTPS (Port 443)
- **Action**: Allow / Accept
- **Protocol**: TCP
- **Port**: 443
- **Source**: 0.0.0.0/0 atau "Anywhere" atau "All"
- **Description**: Allow HTTPS traffic
- Klik **Add Rule** atau **Save**

#### Rule 3: Allow SSH (Port 22) - Jika belum ada
- **Action**: Allow / Accept
- **Protocol**: TCP
- **Port**: 22
- **Source**: 0.0.0.0/0 atau "Anywhere"
- **Description**: Allow SSH
- Klik **Add Rule** atau **Save**

### Langkah 5: Apply Changes

1. Setelah semua rule ditambahkan, klik **Apply** atau **Save Changes**
2. Tunggu 1-2 menit untuk propagasi rules

### Langkah 6: Test Akses

#### Test dari browser:
```
http://148.230.100.44/
```

Harus redirect ke halaman login!

#### Test dari command line (komputer lain):
```bash
curl -I http://148.230.100.44/
```

Harus dapat HTTP 302 Found

## Jika Tidak Ada Menu Firewall di hPanel

Beberapa VPS Hostinger tidak punya firewall di control panel. Jika tidak ketemu menu firewall:

### Alternatif 1: Hubungi Support Hostinger

1. Di hPanel, klik **Help** atau **Support**
2. Buka Live Chat atau Submit Ticket
3. Katakan:
   ```
   Hi, saya tidak bisa akses VPS saya dari luar (IP: 148.230.100.44).
   Dari dalam VPS sudah bisa akses, tapi dari internet tidak bisa.
   Mohon bantu cek apakah ada firewall yang block port 80 dan 443.
   Tolong buka port 80 (HTTP) dan 443 (HTTPS) untuk IP saya.
   ```

### Alternatif 2: Cek di VPS Panel (Bukan hPanel)

Hostinger kadang punya panel terpisah untuk VPS:

1. Cek email dari Hostinger saat pertama beli VPS
2. Biasanya ada link ke VPS Control Panel (bukan hPanel)
3. Login ke panel tersebut
4. Cari menu Firewall/Security

### Alternatif 3: Disable Firewall Sementara (Untuk Testing)

**PERINGATAN**: Ini hanya untuk testing, jangan dipakai production!

SSH ke VPS, jalankan:
```bash
# Disable UFW sementara
sudo ufw disable

# Test akses dari browser
# http://148.230.100.44/

# Jika berhasil, berarti masalah di UFW
# Enable lagi dan konfigurasi ulang
sudo ufw enable
```

## Konfigurasi Firewall yang Benar untuk Hostinger VPS

Jika Anda bisa akses firewall settings, pastikan rules seperti ini:

```
Priority  Action  Protocol  Port    Source      Description
--------------------------------------------------------------
1         ALLOW   TCP       22      0.0.0.0/0   SSH
2         ALLOW   TCP       80      0.0.0.0/0   HTTP
3         ALLOW   TCP       443     0.0.0.0/0   HTTPS
4         ALLOW   TCP       3306    127.0.0.1   MySQL (localhost only)
5         DROP    ALL       ALL     0.0.0.0/0   Drop all other
```

## Troubleshooting Khusus Hostinger

### Issue 1: Firewall Rules Tidak Apply

Jika sudah tambah rules tapi masih tidak bisa:
1. Restart VPS dari hPanel
2. Tunggu 5 menit
3. Test lagi

### Issue 2: Port Masih Blocked Setelah Buka Firewall

Kemungkinan ada network-level firewall:
1. Hubungi Hostinger Support
2. Minta mereka cek network firewall
3. Kadang ada DDoS protection yang block traffic

### Issue 3: Tidak Bisa Login hPanel

1. Reset password di: https://www.hostinger.com/forgot-password
2. Atau hubungi support via email

## Setelah Firewall Dibuka dan Website Bisa Diakses

### 1. Setup Domain

Login ke hPanel → Domains → DNS Management:

**Tambah A Record:**
- Type: A
- Name: labelrbm (atau @ untuk root domain)
- Points to: 148.230.100.44
- TTL: 14400 (atau default)

Tunggu 5-30 menit untuk propagasi DNS.

### 2. Test Domain

```bash
nslookup labelrbm.iwareid.com
```

Harus mengarah ke 148.230.100.44

### 3. Setup SSL (Opsional)

Setelah domain bisa diakses, setup SSL:

```bash
cd /var/www/rbmschedule
# Ikuti panduan di HOSTING_DOCKER.md bagian 8
```

### 4. Ganti Password Default

```bash
nano /var/www/rbmschedule/.env
```

Ganti:
```env
DB_PASS=password_kuat_anda
MYSQL_ROOT_PASSWORD=password_root_kuat
```

Restart:
```bash
docker compose down
docker compose up -d
```

## Kontak Support Hostinger

Jika masih gagal setelah semua langkah:

**Live Chat**: https://www.hostinger.com/contact
**Email**: support@hostinger.com
**Phone**: Cek di hPanel → Help → Contact

Katakan:
```
VPS IP: 148.230.100.44
Masalah: Port 80 dan 443 tidak bisa diakses dari internet
Sudah dicoba: UFW sudah allow port 80/443, aplikasi berjalan normal
Request: Mohon cek dan buka firewall untuk port 80 dan 443
```

## Catatan Penting

1. Hostinger VPS KVM biasanya tidak ada firewall di control panel
2. Firewall dikelola langsung di VPS via UFW/iptables
3. Jika sudah benar semua tapi masih gagal = hubungi support
4. Support Hostinger biasanya fast response (< 5 menit via live chat)

---

**Update**: Berdasarkan diagnostic, firewall UFW di VPS sudah benar. Kemungkinan besar ada network-level firewall di Hostinger yang perlu dibuka oleh support mereka.

**Rekomendasi**: Hubungi Hostinger Support via Live Chat, mereka bisa langsung cek dan fix dalam beberapa menit.
