# Panduan Kontribusi

Terima kasih atas minat Anda untuk berkontribusi pada RBM Schedule Management System!

## 🚀 Cara Berkontribusi

### 1. Fork Repository
Fork repository ini ke akun GitHub Anda.

### 2. Clone Repository
```bash
git clone https://github.com/your-username/rbmschedule.git
cd rbmschedule
```

### 3. Buat Branch
Buat branch baru untuk fitur atau perbaikan:
```bash
git checkout -b feature/nama-fitur
# atau
git checkout -b fix/nama-bug
```

### 4. Coding Standards

#### PHP
- Gunakan PSR-12 coding standard
- Tambahkan PHPDoc comments untuk semua fungsi dan class
- Gunakan type hints untuk parameter dan return types
- Nama variabel menggunakan camelCase
- Nama class menggunakan PascalCase

#### JavaScript
- Gunakan camelCase untuk variabel dan fungsi
- Gunakan const/let, hindari var
- Tambahkan comments untuk fungsi kompleks

#### CSS
- Gunakan BEM naming convention jika memungkinkan
- Organisir CSS dengan komentar section
- Gunakan CSS variables untuk konsistensi

### 5. Testing
Sebelum commit, pastikan:
- Kode berjalan tanpa error
- Fitur yang ditambahkan berfungsi dengan baik
- Tidak ada breaking changes (kecuali memang diperlukan)
- Error handling sudah diimplementasikan

### 6. Commit Messages
Gunakan format commit message yang jelas:
```
feat: Menambahkan fitur export Excel
fix: Memperbaiki bug real-time sync
docs: Update dokumentasi API
refactor: Refactor kode database connection
```

### 7. Push dan Pull Request
```bash
git push origin feature/nama-fitur
```
Kemudian buat Pull Request dengan deskripsi yang jelas.

## 📝 Checklist Pull Request

- [ ] Kode mengikuti coding standards
- [ ] PHPDoc comments sudah ditambahkan
- [ ] Error handling sudah diimplementasikan
- [ ] Tidak ada hardcoded credentials
- [ ] Logging sudah ditambahkan untuk operasi penting
- [ ] Dokumentasi sudah diupdate (jika diperlukan)
- [ ] Testing sudah dilakukan

## 🐛 Melaporkan Bug

Saat melaporkan bug, sertakan:
1. Deskripsi bug yang jelas
2. Langkah-langkah untuk reproduce
3. Expected behavior
4. Actual behavior
5. Screenshot (jika ada)
6. Environment (PHP version, MySQL version, browser, dll)

## 💡 Menyarankan Fitur

Saat menyarankan fitur baru:
1. Jelaskan use case dengan jelas
2. Berikan contoh skenario penggunaan
3. Jelaskan manfaat fitur tersebut
4. Pertimbangkan impact pada existing code

## 📞 Kontak

Jika ada pertanyaan, silakan buat issue di GitHub atau hubungi maintainer.

Terima kasih atas kontribusi Anda! 🎉


