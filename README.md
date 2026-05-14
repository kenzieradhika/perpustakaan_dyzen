# 📚 PERPUSTAKAAN DYZEN - Library Management System

Sistem manajemen perpustakaan digital lengkap dengan fitur admin dan user. Dibangun menggunakan PHP Native, MySQL, TailwindCSS, dan JavaScript.

---

## ⚙️ Persyaratan Sistem

| Software | Minimal Versi |
|----------|---------------|
| PHP | 8.0 atau lebih baru |
| MySQL | 5.7 atau lebih baru |
| XAMPP / Laragon | Versi terbaru |
| Web Browser | Chrome, Firefox, Edge |

---

## 🚀 Cara Instalasi

### Langkah 1: Install XAMPP

1. Download XAMPP dari https://www.apachefriends.org/
2. Install seperti biasa (klik Next terus)
3. Buka **XAMPP Control Panel**
4. Klik tombol **Start** pada **Apache** dan **MySQL**
5. Pastikan kedua layanan berwarna hijau

### Langkah 2: Letakkan File Project

1. Copy folder `perpustakaan_dyzen`
2. Paste ke: `C:\xampp\htdocs\`
3. Hasilnya: `C:\xampp\htdocs\perpustakaan_dyzen\`

### Langkah 3: Import Database

**CARA 1 - Via phpMyAdmin (Paling Mudah)**

1. Buka browser, ketik: `http://localhost/phpmyadmin`
2. Klik menu **New** di sidebar kiri
3. Isi nama database: `perpustakaan_dyzen`
4. Klik tombol **Create**
5. Klik database `perpustakaan_dyzen` yang baru dibuat
6. Klik tab **Import**
7. Klik **Choose File** → pilih `sql/dyzen.sql`
8. Klik tombol **Go** di bagian bawah

**CARA 2 - Via Command Prompt**

```bash
cd C:\xampp\mysql\bin
mysql -u root -p
CREATE DATABASE perpustakaan_dyzen;
USE perpustakaan_dyzen;
SOURCE C:/xampp/htdocs/perpustakaan_dyzen/sql/dyzen.sql;
EXIT;
Langkah 4: Konfigurasi Database
Buka file config/data_base.php dan sesuaikan:

php
define('DB_HOST', 'localhost');
define('DB_NAME', 'perpustakaan_dyzen');
define('DB_USER', 'root');
define('DB_PASS', '');
Langkah 5: Jalankan Aplikasi
Buka browser dan ketik:

text
http://localhost/perpustakaan_dyzen/landing/halaman_awal.php
🔐 Login Aplikasi
Login sebagai ADMIN
Field	Isi
Email	admin@dyzen.com
Password	12345678
Login sebagai USER (Siswa)
Email	Password
budi@example.com	12345678
siti@example.com	12345678
ahmad@example.com	12345678
dewi@example.com	12345678
eko@example.com	12345678
fitri@example.com	12345678
Catatan: Semua user menggunakan password default 12345678

📂 Struktur Folder
text
perpustakaan_dyzen/
│
├── 📁 page/
│   ├── 📁 admin/          # Halaman admin (dashboard, user, buku, dll)
│   ├── 📁 user/           # Halaman user (katalog, pinjam, riwayat)
│   └── 📁 tools/          # Tools tambahan
│
├── 📁 assets/             # CSS, JS, gambar
│   ├── 📁 css/            # File CSS
│   └── 📁 js/             # File JavaScript
│
├── 📁 config/             # Konfigurasi database & keamanan
├── 📁 uploads/covers/     # Tempat foto profil & cover buku
├── 📁 logs/               # Log aktivitas sistem
├── 📁 auth/               # Login, register, logout
├── 📁 landing/            # Halaman awal (landing page)
├── 📁 sql/                # File database (dyzen.sql)
├── 📁 error/              # Halaman error (403, 404, 500)
├── 📁 scripts/            # Script maintenance & cron
├── 📁 vendor/             # Library Composer (PHPMailer)
│
├── 📄 .htaccess           # Konfigurasi keamanan Apache
├── 📄 composer.json       # Dependency Composer
├── 📄 README.md           # File ini
├── 📄 LICENCE             # Lisensi aplikasi
├── 📄 maintenance.html    # Halaman maintenance
└── 📄 kontak_pembuat.json # Kontak pembuat aplikasi

👑 Fitur untuk Admin
No	Fitur	Cara Mengakses
1	Dashboard dengan Grafik	Sidebar → Dashboard
2	Tambah Buku	Sidebar → Data Buku → Tambah Buku
3	Edit Buku	Data Buku → Klik ikon pensil
4	Hapus Buku	Data Buku → Klik ikon tong sampah
5	Tambah User	Sidebar → Data User → Tambah User
6	Edit User	Data User → Klik ikon pensil
7	Ban User	Data User → Klik tombol Ban
8	Unban User	Data User (status Banned) → Klik Unban
9	Hapus User	Data User → Klik Hapus
10	Lihat Denda	Sidebar → Denda Siswa
11	Tandai Bayar Denda	Denda Siswa → Klik Tandai Bayar
12	Lihat Laporan	Sidebar → Laporan
13	Export Laporan	Laporan → Klik Export Excel
14	Edit Profil Admin	Sidebar → Profile
15	Lihat Logs Sistem	Sidebar → Logs Archive

👤 Fitur untuk User (Siswa)
No	Fitur	Cara Mengakses
1	Dashboard Member	Setelah login langsung masuk dashboard
2	Katalog Buku	Sidebar → Katalog Buku
3	Cari Buku	Ketik judul/pengarang di form pencarian
4	Filter Buku	Pilih kategori dari dropdown
5	Pinjam Buku	Klik Pinjam → Konfirmasi
6	Kembalikan Buku	Sidebar → Kembalikan Buku
7	Riwayat Peminjaman	Sidebar → Riwayat
8	Cek Denda	Sidebar → Cek Denda
9	Stok Buku	Sidebar → Stok Buku
10	Notifikasi	Klik ikon lonceng di pojok kanan atas
11	Edit Profil	Sidebar → Profile

🛠️ Tools yang Tersedia
Tool	Lokasi File	Fungsi
Laporan	page/tools/laporan.php	Export data peminjaman ke Excel
Logs Archive	page/tools/logs_archive.php	Lihat riwayat aktivitas sistem
Backup Database	page/tools/backup_db.php	Backup database ke file SQL
Clear Cache	page/tools/clear_cache.php	Bersihkan cache sistem
System Info	page/tools/system_info.php	Lihat informasi server & PHP
Cara akses tools:
Login sebagai Admin
Buka sidebar
Klik menu Tools
Pilih tools yang ingin digunakan
Atau akses langsung via browser:

text
http://localhost/perpustakaan_dyzen/page/tools/laporan.php

📖 Aturan Sistem
Aturan	Nilai
Maksimal pinjam per user	3 buku
Lama pinjam	7 hari
Denda per hari keterlambatan	Rp 1.000
User yang di-ban	Tidak bisa login
User punya denda belum bayar	Tidak bisa pinjam

❌ Troubleshooting (Mengatasi Masalah)

Masalah	Solusi
Error Database connection failed	
1. Cek XAMPP: Apakah MySQL sudah Start?
2. Cek file config/data_base.php
3. Username: root, Password: kosong
Halaman tidak ditemukan (Error 404)	1. Pastikan folder di C:\xampp\htdocs\
2. Cek URL: localhost/perpustakaan_dyzen/
Gambar cover/foto tidak muncul	1. Buat folder uploads/covers
2. Beri permission Read & Write (755)
Login gagal terus	1. Cek email: admin@dyzen.com
2. Cek password: 12345678
3. Bersihkan cache browser (Ctrl+Shift+Delete)
Apache tidak bisa Start	Port 80 mungkin dipakai. Ganti ke port 8080:
XAMPP → Config → httpd.conf → ganti Listen 80 jadi Listen 8080
CSRF token invalid	Refresh halaman atau bersihkan cache browser
Class "Logger" not found	Pastikan file config/logger.php ada dan tidak error
Cara bersihkan cache browser:
Chrome / Edge: Tekan Ctrl + Shift + Delete → Pilih "Cached images" → Clear data

Atau: Tekan Ctrl + F5 untuk hard refresh

📞 Kontak Pembuat Aplikasi
Jika ada kendala atau saran untuk pengembangan:
json
{
  "instagram": "@kennnzz._",
  "whatsapp": "0812-2925-1088",
  "email": "maskengg94@gmail.com"
}
File lengkap ada di kontak_pembuat.json

📝 Catatan Penting
Password default semua user adalah 12345678
Admin punya akses penuh ke semua fitur termasuk ban/unban user
User biasa hanya bisa pinjam, kembali, dan lihat riwayat
User yang di-ban tidak bisa login sama sekali
User yang punya denda belum bayar tidak bisa pinjam
Backup database disarankan dilakukan secara rutin
File SQL ada di folder sql/dyzen.sql

🔄 Fitur yang Akan Datang
Export laporan ke PDF
Notifikasi via email
Barcode scanner untuk peminjaman
Versi mobile (Android)
Grafik interaktif di dashboard
Multi bahasa (Indonesia/Inggris)

⚖️ Lisensi
Aplikasi ini dibuat untuk tugas akhir / project sekolah.

Diperbolehkan:
Belajar dan mempelajari kode
Mengembangkan lebih lanjut
Menggunakan untuk project pribadi

Dilarang:
Menjual aplikasi ini tanpa izin
Mengaku sebagai pembuat asli

🎉 Selamat Menggunakan!

╔═══════════════════════════════════════════════════════════╗
║                                                           ║
║              PERPUSTAKAAN DYZEN - v2.0                    ║
║                                                           ║
║         Sistem Informasi Perpustakaan Digital             ║
║                                                           ║
║              Created with  by Kenzie                      ║
║                                                           ║
╚═══════════════════════════════════════════════════════════╝
© 2024-2026 Dyzen Library | Created by Kenzie