# 🔄 Panduan Restore Database ke VPS

Panduan lengkap restore database RBM Schedule ke VPS di folder `/opt/label`

**VPS Details:**
- Domain: `lableschedule.iwareid.com`
- Host: `145.79.8.148`
- User: `root`
- App Directory: `/opt/label`
- Backup File: `backup_20260605_062551.sql`

---

## 🚀 Cara Cepat (Otomatis)

### Jalankan Script Otomatis:

```powershell
# Di Windows (PowerShell/CMD)
cd d:\project\rbmschedule\scripts
.\upload_restore_vps.bat
```

Script akan otomatis:
1. ✅ Upload backup ke VPS
2. ✅ Restore database
3. ✅ Verifikasi hasil restore

---

## 📝 Cara Manual (Step by Step)

### Step 1: Upload File Backup

```powershell
# Dari Windows
scp "d:\project\rbmschedule\backup\backup_20260605_062551.sql" root@145.79.8.148:/opt/label/backup/
```

### Step 2: SSH ke VPS

```bash
ssh root@145.79.8.148
```

### Step 3: Verifikasi File

```bash
# Cek file sudah ada
ls -lh /opt/label/backup/backup_20260605_062551.sql

# Cek ukuran file
du -h /opt/label/backup/backup_20260605_062551.sql
```

### Step 4: Backup Database Saat Ini (Safety)

```bash
# Buat safety backup sebelum restore
mysqldump -u rbm_user -p rbm_schedule > /opt/label/backup/safety_backup_$(date +%Y%m%d_%H%M%S).sql

# Verifikasi backup
ls -lh /opt/label/backup/safety_*.sql
```

### Step 5: Restore Database

```bash
# Restore database
mysql -u rbm_user -p rbm_schedule < /opt/label/backup/backup_20260605_062551.sql

# Masukkan password database ketika diminta
```

### Step 6: Verifikasi Restore

```bash
# Cek tabel
mysql -u rbm_user -p rbm_schedule -e "SHOW TABLES;"

# Cek jumlah users
mysql -u rbm_user -p rbm_schedule -e "SELECT COUNT(*) as total FROM users;"

# Cek jumlah schedules
mysql -u rbm_user -p rbm_schedule -e "SELECT COUNT(*) as total FROM schedules;"

# Cek data terbaru
mysql -u rbm_user -p rbm_schedule -e "SELECT * FROM schedules ORDER BY id DESC LIMIT 5;"
```

---

## 🔒 Cara Aman (Dengan Script Restore)

### Step 1: Upload File & Script

```powershell
# Upload backup
scp "d:\project\rbmschedule\backup\backup_20260605_062551.sql" root@145.79.8.148:/opt/label/backup/

# Upload script restore (jika belum ada)
scp "d:\project\rbmschedule\scripts\restore_db.sh" root@145.79.8.148:/opt/label/scripts/
```

### Step 2: SSH dan Jalankan Script

```bash
# SSH ke VPS
ssh root@145.79.8.148

# Masuk ke folder scripts
cd /opt/label/scripts

# Berikan permission
chmod +x restore_db.sh

# Edit kredensial jika perlu
nano restore_db.sh
# Pastikan:
# DB_USER="rbm_user"
# DB_PASS="password_anda"
# DB_NAME="rbm_schedule"
# DB_HOST="localhost"

# Jalankan restore
./restore_db.sh /opt/label/backup/backup_20260605_062551.sql
```

Script akan:
- ✅ Buat safety backup otomatis
- ✅ Drop dan create database
- ✅ Restore dari backup
- ✅ Verifikasi hasil
- ✅ Rollback otomatis jika gagal

---

## 🔧 Troubleshooting

### Problem: "Access denied for user"

```bash
# Cek user database yang benar
mysql -u root -p -e "SELECT User, Host FROM mysql.user;"

# Grant permission jika perlu
mysql -u root -p
```

```sql
GRANT ALL PRIVILEGES ON rbm_schedule.* TO 'rbm_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Problem: "Database does not exist"

```bash
# Buat database dulu
mysql -u root -p -e "CREATE DATABASE rbm_schedule CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Lalu restore
mysql -u rbm_user -p rbm_schedule < /opt/label/backup/backup_20260605_062551.sql
```

### Problem: File tidak ditemukan

```bash
# Cek lokasi file
find /opt -name "backup_20260605_062551.sql"

# Atau cek di home
ls -lh ~/backup_20260605_062551.sql

# Move ke folder yang benar
mv ~/backup_20260605_062551.sql /opt/label/backup/
```

### Problem: Permission denied

```bash
# Fix permission file
chmod 644 /opt/label/backup/backup_20260605_062551.sql

# Fix permission folder
chmod 755 /opt/label/backup
```

---

## 📋 Checklist Restore

- [ ] File backup sudah di-upload ke VPS
- [ ] Safety backup database saat ini sudah dibuat
- [ ] Database credentials sudah benar
- [ ] Restore berhasil tanpa error
- [ ] Verifikasi jumlah data (users, schedules)
- [ ] Test login ke aplikasi
- [ ] Test fitur CRUD schedule
- [ ] Cek audit logs

---

## 🔄 Restore dengan Docker (Jika Menggunakan Docker)

```bash
# SSH ke VPS
ssh root@145.79.8.148

# Upload backup ke VPS dulu (dari Windows)
# scp "d:\project\rbmschedule\backup\backup_20260605_062551.sql" root@145.79.8.148:/opt/label/backup/

# Copy ke dalam container
docker cp /opt/label/backup/backup_20260605_062551.sql rbmschedule_db:/tmp/

# Restore di dalam container
docker exec rbmschedule_db mysql -u rbm_user -prbm_password rbm_schedule < /tmp/backup_20260605_062551.sql

# Atau langsung dari host
docker exec -i rbmschedule_db mysql -u rbm_user -prbm_password rbm_schedule < /opt/label/backup/backup_20260605_062551.sql

# Verifikasi
docker exec rbmschedule_db mysql -u rbm_user -prbm_password rbm_schedule -e "SHOW TABLES;"
```

---

## 💡 Tips

1. **Selalu buat safety backup sebelum restore**
2. **Pastikan kredensial database sudah benar**
3. **Gunakan script restore untuk keamanan**
4. **Verifikasi data setelah restore**
5. **Test aplikasi setelah restore**
6. **Simpan safety backup untuk rollback**

---

## 🎯 Quick Reference

### Upload Backup
```bash
scp "d:\project\rbmschedule\backup\backup_20260605_062551.sql" root@145.79.8.148:/opt/label/backup/
```

### SSH ke VPS
```bash
ssh root@145.79.8.148
```

### Restore Database
```bash
mysql -u rbm_user -p rbm_schedule < /opt/label/backup/backup_20260605_062551.sql
```

### Verifikasi
```bash
mysql -u rbm_user -p rbm_schedule -e "SHOW TABLES; SELECT COUNT(*) FROM users; SELECT COUNT(*) FROM schedules;"
```

---

**Happy Restoring! 🎉**
