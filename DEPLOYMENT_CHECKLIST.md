# ✅ Deployment Checklist - RBM Schedule

Gunakan checklist ini untuk memastikan deployment berjalan lancar dan aman.

## 📋 Pre-Deployment

### Server Setup
- [ ] Server sudah siap (Ubuntu 20.04+ / Debian 10+)
- [ ] RAM minimal 1GB tersedia
- [ ] Disk space minimal 10GB tersedia
- [ ] Akses SSH ke server tersedia
- [ ] Port yang diperlukan tersedia (cek: `sudo netstat -tulpn | grep LISTEN`)

### Tools Installation
- [ ] Docker sudah terinstall (`docker --version`)
- [ ] Docker Compose sudah terinstall (`docker-compose --version`)
- [ ] Git sudah terinstall (jika deploy via Git)

### Port Availability
- [ ] Port 8090 tersedia untuk web application (atau ganti di .env)
- [ ] Port 3308 tersedia untuk MySQL external (atau ganti di .env)
- [ ] Firewall dikonfigurasi untuk allow port yang digunakan

### Files Ready
- [ ] Source code sudah di-upload/clone ke server
- [ ] File `database.sql` ada di root folder
- [ ] File `.env.example` ada di root folder

## 🔧 Configuration

### Environment Setup
- [ ] Copy `.env.example` ke `.env` (`cp .env.example .env`)
- [ ] Edit file `.env` dengan konfigurasi yang benar
- [ ] Password `DB_ROOT_PASS` sudah diganti (minimal 12 karakter)
- [ ] Password `DB_PASS` sudah diganti (minimal 12 karakter)
- [ ] Port `WEB_PORT` disesuaikan jika perlu
- [ ] Port `DB_PORT_EXTERNAL` disesuaikan jika perlu
- [ ] `DEBUG_MODE=false` untuk production

### Security
- [ ] Password kuat (huruf besar, kecil, angka, simbol)
- [ ] File `.env` tidak ter-commit ke Git
- [ ] File `.gitignore` sudah benar
- [ ] `.dockerignore` sudah dikonfigurasi

## 🚀 Deployment

### Deploy Application
- [ ] Jalankan `chmod +x *.sh` untuk make scripts executable
- [ ] Jalankan `./deploy.sh` untuk deploy
- [ ] Tunggu hingga proses selesai (1-2 menit)
- [ ] Cek tidak ada error saat build

### Verify Deployment
- [ ] Container berjalan: `docker-compose ps`
- [ ] Status app container: "Up"
- [ ] Status db container: "Up"
- [ ] Web port listening: `netstat -tuln | grep 8090`
- [ ] DB port listening: `netstat -tuln | grep 3308`
- [ ] Web server responding: `curl http://localhost:8090`
- [ ] Database responding: `docker exec -it rbmschedule_db mysql -u rbm_user -p`

### Run Tests
- [ ] Jalankan `./test-deployment.sh`
- [ ] Semua test passed
- [ ] Tidak ada error di logs: `docker-compose logs`

## 🌐 Access & Testing

### Web Access
- [ ] Buka browser ke `http://IP-SERVER:8090`
- [ ] Halaman login tampil dengan benar
- [ ] Login dengan kredensial default berhasil
  - Username: `admin`
  - Password: `admin123`
- [ ] Dashboard tampil dengan benar
- [ ] Menu navigasi berfungsi
- [ ] Tidak ada error di browser console (F12)

### Functionality Testing
- [ ] Bisa create schedule baru
- [ ] Bisa edit schedule
- [ ] Bisa delete schedule
- [ ] Bisa view schedule
- [ ] Real-time sync berfungsi (test dengan 2 browser)
- [ ] Export report berfungsi
- [ ] Logout berfungsi

### Database Testing
- [ ] Koneksi database berhasil
- [ ] Semua tabel ter-import
- [ ] Data default ada (user admin)
- [ ] Query berjalan normal

## 🔐 Post-Deployment Security

### Change Default Passwords
- [ ] Password admin di aplikasi sudah diubah
- [ ] Password tidak menggunakan default
- [ ] Password dicatat dengan aman

### Firewall Setup
- [ ] UFW atau firewalld sudah diaktifkan
- [ ] SSH port (22) di-allow
- [ ] Web port (8090) di-allow
- [ ] Port lain yang tidak perlu di-block
- [ ] Test akses dari luar masih bisa

### SSL Setup (Optional tapi Recommended)
- [ ] Domain sudah pointing ke server IP
- [ ] Nginx sudah diinstall (jika pakai nginx)
- [ ] SSL certificate sudah diinstall (Let's Encrypt)
- [ ] HTTPS berfungsi
- [ ] HTTP redirect ke HTTPS

## 💾 Backup Setup

### Initial Backup
- [ ] Backup database pertama kali: `./backup.sh`
- [ ] File backup tersimpan di folder `backup/`
- [ ] Test restore: `./restore.sh <backup-file>`

### Automated Backup
- [ ] Cron job untuk backup otomatis sudah dibuat
- [ ] Test cron job berjalan
- [ ] Backup retention dikonfigurasi (hapus backup > 7 hari)
- [ ] Backup location aman dan tidak ter-expose

### Remote Backup (Recommended)
- [ ] Setup rsync/scp ke remote server
- [ ] Test backup ke remote
- [ ] Schedule automated remote backup

## 📊 Monitoring Setup

### Health Check
- [ ] Status check script berfungsi: `./status.sh`
- [ ] Monitor container health: `docker ps`
- [ ] Monitor resource: `docker stats`

### Logging
- [ ] Application logs accessible: `logs/error.log`
- [ ] Docker logs accessible: `docker-compose logs`
- [ ] Log rotation dikonfigurasi
- [ ] Disk space untuk logs mencukupi

### Alerts (Optional)
- [ ] Email alert untuk error setup
- [ ] Disk space alert setup
- [ ] Uptime monitoring setup (external)

## 📝 Documentation

### Internal Documentation
- [ ] Kredensial disimpan dengan aman
- [ ] IP server dicatat
- [ ] Port yang digunakan dicatat
- [ ] Prosedur backup/restore didokumentasi
- [ ] Contact person untuk support dicatat

### Team Communication
- [ ] Tim diberitahu deployment selesai
- [ ] Kredensial dibagikan dengan aman
- [ ] Link akses aplikasi dibagikan
- [ ] Dokumentasi dibagikan

## 🔄 Update Plan

### Future Updates
- [ ] Git repository untuk version control
- [ ] Update procedure didokumentasi
- [ ] Rollback procedure didokumentasi
- [ ] Testing environment setup (staging)

## ✅ Final Checks

### Functionality
- [ ] Aplikasi bisa diakses dari internet
- [ ] Semua fitur berfungsi normal
- [ ] Performance acceptable (response time < 2s)
- [ ] No error di production logs

### Security
- [ ] Default password sudah diubah
- [ ] Firewall aktif
- [ ] SSL aktif (jika applicable)
- [ ] Backup berjalan otomatis
- [ ] File sensitive tidak ter-expose

### Documentation
- [ ] Semua kredensial dicatat
- [ ] Prosedur operasional didokumentasi
- [ ] Tim sudah trained
- [ ] Support contact tersedia

## 🎉 Deployment Complete!

Jika semua checklist sudah di-check, deployment Anda berhasil!

**Next Steps:**
1. Monitor aplikasi 24 jam pertama
2. Collect user feedback
3. Plan for improvements
4. Setup regular maintenance schedule

---

## 📞 Need Help?

- **Deployment Issues:** Lihat [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
- **General Questions:** Lihat [DEPLOYMENT.md](DEPLOYMENT.md)
- **Quick Start:** Lihat [QUICK_START.md](QUICK_START.md)

---

**Checklist ini dibuat untuk RBM Schedule v1.0.0**  
**Date:** June 2024
