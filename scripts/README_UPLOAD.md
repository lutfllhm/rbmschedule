# 📤 Panduan Upload Database ke VPS

Panduan lengkap upload database dari Windows lokal ke VPS Linux.

---

## 🚀 Cara Tercepat (Otomatis)

### Option 1: Upload + Restore Otomatis

```cmd
cd d:\project\rbmschedule\scripts
.\upload_and_restore.bat
```

Script akan:
1. ✅ Upload file backup ke VPS
2. ✅ Verifikasi file berhasil di-upload
3. ✅ Buat safety backup database di VPS
4. ✅ Restore database otomatis

### Option 2: Upload Saja (Manual Restore)

```cmd
cd d:\project\rbmschedule\scripts
.\upload_to_vps.bat
```

Script akan upload file, lalu Anda restore manual via SSH.

---

## 📝 Cara Manual Step-by-Step

### Step 1: Buka PowerShell atau Command Prompt

```cmd
# Buka CMD atau PowerShell di Windows
# Tekan Windows + R, ketik: cmd atau powershell
```

### Step 2: Upload File dengan SCP

```powershell
# Sintaks dasar
scp "path\file\lokal" user@host:/path/tujuan/

# Contoh untuk project ini:
scp "d:\project\rbmschedule\backup\backup_20260605_062551.sql" root@145.79.8.148:/opt/label/backup/
```

**Penjelasan:**
- `d:\project\rbmschedule\backup\backup_20260605_062551.sql` = File backup di Windows
- `root` = User SSH VPS
- `145.79.8.148` = IP VPS
- `/opt/label/backup/` = Folder tujuan di VPS

Masukkan password SSH ketika diminta.

### Step 3: SSH ke VPS

```cmd
ssh root@145.79.8.148
```

Masukkan password SSH.

### Step 4: Verifikasi File

```bash
# Cek file sudah ada
ls -lh /opt/label/backup/backup_20260605_062551.sql

# Cek ukuran file
du -h /opt/label/backup/backup_20260605_062551.sql
```

### Step 5: Restore Database

```bash
# Restore ke database
mysql -u rbm_user -p rbm_schedule < /opt/label/backup/backup_20260605_062551.sql

# Masukkan password database (bukan password SSH!)
```

### Step 6: Verifikasi Restore

```bash
# Cek jumlah data
mysql -u rbm_user -p rbm_schedule -e "SELECT COUNT(*) FROM schedules;"

# Cek tabel
mysql -u rbm_user -p rbm_schedule -e "SHOW TABLES;"
```

---

## 🔧 Cara Alternatif

### Menggunakan WinSCP (GUI - User Friendly)

1. **Download WinSCP**: https://winscp.net/eng/download.php
2. **Install dan buka WinSCP**
3. **Buat koneksi baru:**
   - File Protocol: `SCP`
   - Host name: `145.79.8.148`
   - Port number: `22`
   - User name: `root`
   - Password: `[password SSH Anda]`
4. **Klik Login**
5. **Drag & Drop file** `backup_20260605_062551.sql` dari kiri (Windows) ke kanan (VPS folder `/opt/label/backup/`)
6. **SSH manual untuk restore** (lihat Step 5 di atas)

### Menggunakan FTP/SFTP Client (FileZilla)

1. **Download FileZilla Client**: https://filezilla-project.org/
2. **Install dan buka FileZilla**
3. **Buat koneksi:**
   - Host: `sftp://145.79.8.148`
   - Username: `root`
   - Password: `[password SSH Anda]`
   - Port: `22`
4. **Klik Quickconnect**
5. **Drag & Drop file** dari panel kiri (lokal) ke panel kanan (remote `/opt/label/backup/`)
6. **SSH manual untuk restore**

---

## ⚡ Tips & Tricks

### 1. Upload Multiple Backups

```powershell
# Upload semua file .sql di folder backup
cd d:\project\rbmschedule\backup
scp *.sql root@145.79.8.148:/opt/label/backup/
```

### 2. Upload dengan Progress Bar

```powershell
# SCP sudah menampilkan progress secara default
scp "d:\project\rbmschedule\backup\backup_20260605_062551.sql" root@145.79.8.148:/opt/label/backup/

# Output:
# backup_20260605_062551.sql    100%  1234KB   1.2MB/s   00:01
```

### 3. Upload dengan Kompresi (Lebih Cepat)

```powershell
# Compress dulu di Windows
# Install 7-Zip atau gunakan PowerShell
Compress-Archive -Path "d:\project\rbmschedule\backup\backup_20260605_062551.sql" -DestinationPath "d:\project\rbmschedule\backup\backup.zip"

# Upload file zip (lebih kecil, lebih cepat)
scp "d:\project\rbmschedule\backup\backup.zip" root@145.79.8.148:/opt/label/backup/

# Di VPS, extract dan restore
ssh root@145.79.8.148
cd /opt/label/backup
unzip backup.zip
mysql -u rbm_user -p rbm_schedule < backup_20260605_062551.sql
```

### 4. Check Koneksi Sebelum Upload

```cmd
# Test koneksi SSH
ssh root@145.79.8.148 "echo 'Connected!'"

# Test apakah folder tujuan ada
ssh root@145.79.8.148 "ls -la /opt/label/backup"
```

---

## 🐛 Troubleshooting

### Problem: "scp: command not found" di Windows

**Solusi:**

**Option 1: Install OpenSSH di Windows**
```powershell
# Buka PowerShell sebagai Administrator
Add-WindowsCapability -Online -Name OpenSSH.Client~~~~0.0.1.0

# Verifikasi
scp
```

**Option 2: Gunakan Git Bash**
- Download Git for Windows: https://git-scm.com/download/win
- Install dengan opsi "Use Git and optional Unix tools from Command Prompt"
- Buka Git Bash dan jalankan perintah SCP

**Option 3: Gunakan WinSCP atau FileZilla** (GUI - lebih mudah)

### Problem: "Permission denied (publickey,password)"

**Solusi:**
```powershell
# Pastikan username dan IP benar
ssh root@145.79.8.148

# Coba dengan user lain jika bukan root
ssh username@145.79.8.148

# Jika sudah setup SSH key, pastikan key sudah di-load
```

### Problem: "No such file or directory" di VPS

**Solusi:**
```bash
# SSH ke VPS dulu
ssh root@145.79.8.148

# Buat folder backup jika belum ada
mkdir -p /opt/label/backup

# Verifikasi folder
ls -la /opt/label/

# Set permission
chmod 755 /opt/label/backup

# Keluar dan coba upload lagi
exit
```

### Problem: Upload sangat lambat

**Kemungkinan:**
1. File terlalu besar
2. Koneksi internet lambat
3. VPS jauh (latency tinggi)

**Solusi:**
```powershell
# Compress file dulu
# Gunakan 7-Zip atau PowerShell Compress-Archive

# Atau gunakan gzip di Linux setelah upload
ssh root@145.79.8.148
cd /opt/label/backup
gzip backup_20260605_062551.sql
# File jadi backup_20260605_062551.sql.gz (lebih kecil)

# Restore dari gzip
gunzip < backup_20260605_062551.sql.gz | mysql -u rbm_user -p rbm_schedule
```

### Problem: "mysql: command not found" di VPS

**Solusi:**
```bash
# Install MySQL client jika belum ada
sudo apt update
sudo apt install mysql-client

# Atau jika pakai Docker
docker exec -i rbmschedule_db mysql -u rbm_user -p rbm_schedule < /opt/label/backup/backup_20260605_062551.sql
```

---

## 📋 Checklist Upload Database

- [ ] File backup sudah ada di `d:\project\rbmschedule\backup\`
- [ ] OpenSSH atau Git Bash sudah terinstall di Windows
- [ ] Koneksi SSH ke VPS berhasil (test dengan: `ssh root@145.79.8.148`)
- [ ] Folder `/opt/label/backup/` sudah ada di VPS
- [ ] Upload file berhasil (cek dengan: `ssh root@145.79.8.148 "ls -lh /opt/label/backup/"`)
- [ ] Database credentials sudah benar (user: `rbm_user`, database: `rbm_schedule`)
- [ ] Restore berhasil tanpa error
- [ ] Verifikasi data (jumlah schedules, users)
- [ ] Test login aplikasi

---

## 🎯 Quick Reference

### Upload File
```cmd
scp "d:\project\rbmschedule\backup\backup_20260605_062551.sql" root@145.79.8.148:/opt/label/backup/
```

### SSH ke VPS
```cmd
ssh root@145.79.8.148
```

### Restore Database
```bash
mysql -u rbm_user -p rbm_schedule < /opt/label/backup/backup_20260605_062551.sql
```

### Verifikasi
```bash
mysql -u rbm_user -p rbm_schedule -e "SHOW TABLES;"
```

---

## 📚 Referensi

- [WinSCP Download](https://winscp.net/eng/download.php)
- [FileZilla Download](https://filezilla-project.org/)
- [Git for Windows](https://git-scm.com/download/win)
- [OpenSSH for Windows](https://docs.microsoft.com/en-us/windows-server/administration/openssh/openssh_install_firstuse)

---

**Happy Uploading! 🚀**
