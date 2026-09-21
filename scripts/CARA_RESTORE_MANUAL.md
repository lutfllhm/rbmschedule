# 🔧 Cara Restore Manual (100% Berhasil)

Jika script otomatis gagal, gunakan cara manual ini:

---

## ✅ Method 1: Pipe dari Host (Paling Mudah)

```bash
# 1. Cek password di .env
cat /opt/label/.env | grep DB_ROOT_PASS

# 2. Restore langsung (ganti PASSWORD dengan password dari .env)
cat /opt/label/backup/backup_20260605_062551.sql | docker exec -i rbmschedule_db mysql -u root -pPASSWORD rbm_schedule

# Catatan: Tidak ada spasi antara -p dan PASSWORD
# Contoh: -pMyPassword123
```

**Selesai!** Cek hasilnya dengan:
```bash
docker exec rbmschedule_db mysql -u root -pPASSWORD rbm_schedule -e "SELECT COUNT(*) FROM users;"
```

---

## ✅ Method 2: Masuk ke Container (Paling Aman)

```bash
# 1. Masuk ke container
docker exec -it rbmschedule_db bash

# 2. Di dalam container, restore database
mysql -u root -p rbm_schedule < /tmp/backup_restore.sql
# Masukkan password ketika diminta

# 3. Jika file tidak ada di /tmp, cek lokasi file
ls -lh /tmp/*.sql
ls -lh /opt/label/backup/*.sql

# 4. Gunakan path yang benar
mysql -u root -p rbm_schedule < /path/yang/benar/backup_20260605_062551.sql

# 5. Verifikasi
mysql -u root -p rbm_schedule -e "SHOW TABLES;"
mysql -u root -p rbm_schedule -e "SELECT COUNT(*) FROM users;"

# 6. Keluar dari container
exit
```

---

## ✅ Method 3: Copy File Lagi ke Container

Kadang file tidak ter-copy dengan baik, copy ulang:

```bash
# 1. Copy file dari host ke container
docker cp /opt/label/backup/backup_20260605_062551.sql rbmschedule_db:/tmp/restore.sql

# 2. Verifikasi file ada di container
docker exec rbmschedule_db ls -lh /tmp/restore.sql

# 3. Restore dari host
docker exec -i rbmschedule_db mysql -u root -pPASSWORD rbm_schedule < /tmp/restore.sql

# 4. Atau masuk ke container dan restore
docker exec -it rbmschedule_db bash
mysql -u root -p rbm_schedule < /tmp/restore.sql
exit
```

---

## ✅ Method 4: Gunakan mysql client dari dalam container

```bash
# 1. Cek password
cat /opt/label/.env | grep DB_ROOT_PASS

# 2. Execute restore dengan mysql client
docker exec -i rbmschedule_db sh -c 'mysql -u root -p"$DB_ROOT_PASS" rbm_schedule' < /opt/label/backup/backup_20260605_062551.sql

# 3. Atau dengan heredoc
docker exec -i rbmschedule_db mysql -u root -pPASSWORD rbm_schedule <<< "$(cat /opt/label/backup/backup_20260605_062551.sql)"
```

---

## ✅ Method 5: Source dari MySQL Prompt (Paling Tradisional)

```bash
# 1. Copy file ke container
docker cp /opt/label/backup/backup_20260605_062551.sql rbmschedule_db:/tmp/backup.sql

# 2. Masuk ke MySQL prompt di container
docker exec -it rbmschedule_db mysql -u root -p

# 3. Di MySQL prompt, jalankan:
USE rbm_schedule;
SOURCE /tmp/backup.sql;
SHOW TABLES;
SELECT COUNT(*) FROM users;
EXIT;
```

---

## 🔍 Troubleshooting

### Password Salah?

Cek password yang benar:
```bash
# Lihat file .env
cat /opt/label/.env

# Atau cek docker-compose.yml
cat /opt/label/docker-compose.yml | grep MYSQL_ROOT_PASSWORD
```

### Container tidak ditemukan?

Cek nama container yang benar:
```bash
# Lihat semua container
docker ps -a

# Cari yang ada "mysql" atau "db"
docker ps | grep -i mysql
docker ps | grep -i db

# Gunakan nama yang benar, misal:
# - rbmschedule_db
# - label_db_1
# - rbmschedule-db-1
```

### File tidak ditemukan di container?

```bash
# Cari file di container
docker exec rbmschedule_db find / -name "*.sql" 2>/dev/null

# Atau cek folder tertentu
docker exec rbmschedule_db ls -la /tmp/
docker exec rbmschedule_db ls -la /opt/label/backup/
```

---

## 📝 Contoh Real (Lengkap)

```bash
# Asumsi:
# - Password root: MySecurePass123
# - Container name: rbmschedule_db
# - Backup file: /opt/label/backup/backup_20260605_062551.sql

# Step 1: Buat safety backup
docker exec rbmschedule_db mysqldump -u root -pMySecurePass123 rbm_schedule > /opt/label/backup/safety_$(date +%Y%m%d_%H%M%S).sql

# Step 2: Restore dengan pipe
cat /opt/label/backup/backup_20260605_062551.sql | docker exec -i rbmschedule_db mysql -u root -pMySecurePass123 rbm_schedule

# Step 3: Verifikasi
docker exec rbmschedule_db mysql -u root -pMySecurePass123 rbm_schedule -e "SHOW TABLES;"
docker exec rbmschedule_db mysql -u root -pMySecurePass123 rbm_schedule -e "SELECT COUNT(*) FROM users;"
docker exec rbmschedule_db mysql -u root -pMySecurePass123 rbm_schedule -e "SELECT COUNT(*) FROM schedules;"

# Step 4: Test login aplikasi
# Buka browser: http://IP-VPS:8090
```

---

## 🎯 Yang Paling Gampang (1 Command)

```bash
cat /opt/label/backup/backup_20260605_062551.sql | docker exec -i rbmschedule_db mysql -u root -p"$(grep DB_ROOT_PASS /opt/label/.env | cut -d '=' -f2)" rbm_schedule
```

Command ini akan:
1. Baca file backup
2. Ambil password dari .env otomatis
3. Pipe ke MySQL di container
4. Restore database

**Copy-paste command di atas dan Enter!**

---

## ✅ Verifikasi Restore Berhasil

```bash
# Cek jumlah data
docker exec rbmschedule_db mysql -u root -p"$(grep DB_ROOT_PASS /opt/label/.env | cut -d '=' -f2)" rbm_schedule -e "
SELECT 
  (SELECT COUNT(*) FROM users) as total_users,
  (SELECT COUNT(*) FROM schedules) as total_schedules;
"

# Cek user yang ada
docker exec rbmschedule_db mysql -u root -p"$(grep DB_ROOT_PASS /opt/label/.env | cut -d '=' -f2)" rbm_schedule -e "SELECT username, role FROM users;"

# Test login aplikasi
curl -I http://localhost:8090
```

---

**Pilih method mana saja yang paling mudah untuk Anda!** 🚀

Method 1 (pipe dari host) adalah yang paling mudah dan reliable.
