-- ============================================
-- DATABASE PERPUSTAKAAN DYZEN
-- Versi: 2.0 (Lengkap)
-- ============================================

-- Hapus database jika ada (HATI-HATI!)
-- DROP DATABASE IF EXISTS `perpustakaan_dyzen`;

-- Buat database baru
CREATE DATABASE IF NOT EXISTS `perpustakaan_dyzen` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `perpustakaan_dyzen`;

-- ============================================
-- TABEL 1: users (Data Pengguna)
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `nama` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `nisn` VARCHAR(20) DEFAULT NULL,
    `kelas` VARCHAR(20) DEFAULT NULL,
    `foto` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('aktif', 'banned') NOT NULL DEFAULT 'aktif',
    `token_reset` VARCHAR(255) DEFAULT NULL,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `nisn` (`nisn`),
    KEY `idx_status` (`status`),
    KEY `idx_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL 2: buku (Data Buku)
-- ============================================
CREATE TABLE IF NOT EXISTS `buku` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `judul` VARCHAR(200) NOT NULL,
    `pengarang` VARCHAR(100) NOT NULL,
    `penerbit` VARCHAR(100) DEFAULT NULL,
    `tahun_terbit` YEAR DEFAULT NULL,
    `isbn` VARCHAR(20) DEFAULT NULL,
    `kategori` VARCHAR(50) DEFAULT NULL,
    `deskripsi` TEXT,
    `cover` VARCHAR(255) DEFAULT NULL,
    `stok` INT(11) NOT NULL DEFAULT 0,
    `stok_tersedia` INT(11) NOT NULL DEFAULT 0,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `isbn` (`isbn`),
    KEY `idx_kategori` (`kategori`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL 3: peminjaman (Data Peminjaman)
-- ============================================
CREATE TABLE IF NOT EXISTS `peminjaman` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `buku_id` INT(11) NOT NULL,
    `tgl_pinjam` DATE NOT NULL,
    `tgl_kembali` DATE NOT NULL,
    `tgl_dikembalikan` DATE DEFAULT NULL,
    `status` ENUM('dipinjam', 'dikembalikan') NOT NULL DEFAULT 'dipinjam',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_buku_id` (`buku_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_peminjaman_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_peminjaman_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL 4: denda (Data Denda)
-- ============================================
CREATE TABLE IF NOT EXISTS `denda` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `peminjaman_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `hari_terlambat` INT(11) NOT NULL DEFAULT 0,
    `jumlah_denda` INT(11) NOT NULL DEFAULT 0,
    `status_bayar` ENUM('belum', 'sudah') NOT NULL DEFAULT 'belum',
    `tgl_bayar` DATE DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_peminjaman_id` (`peminjaman_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status_bayar` (`status_bayar`),
    CONSTRAINT `fk_denda_peminjaman` FOREIGN KEY (`peminjaman_id`) REFERENCES `peminjaman`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_denda_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL 5: peringatan (Notifikasi User)
-- ============================================
CREATE TABLE IF NOT EXISTS `peringatan` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `pesan` TEXT NOT NULL,
    `tipe` ENUM('info', 'warning', 'danger', 'success') NOT NULL DEFAULT 'info',
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_user_read` (`user_id`, `is_read`),
    CONSTRAINT `fk_peringatan_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL 6: laporan (Log Aktivitas)
-- ============================================
CREATE TABLE IF NOT EXISTS `laporan` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `tipe` VARCHAR(50) NOT NULL,
    `keterangan` TEXT NOT NULL,
    `generated_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tipe` (`tipe`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_laporan_user` FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABEL 7: login_logs (Log Login)
-- ============================================
CREATE TABLE IF NOT EXISTS `login_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) DEFAULT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT,
    `status` ENUM('success', 'failed') NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ip_status` (`ip_address`, `status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ============================================
-- DATA CONTOH (DUMMY DATA)
-- ============================================
-- ============================================

-- ============================================
-- 1. DATA USERS
-- ============================================

-- Password untuk semua user di bawah adalah: "12345678" (sudah di-hash)
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

-- Admin
INSERT INTO `users` (`nama`, `email`, `password`, `role`, `status`) VALUES
('Administrator', 'admin@dyzen.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'aktif');

-- User Aktif
INSERT INTO `users` (`nama`, `email`, `password`, `role`, `nisn`, `kelas`, `status`) VALUES
('Budi Santoso', 'budi@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '12345678901', 'X RPL 1', 'aktif'),
('Siti Aminah', 'siti@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '12345678902', 'X RPL 2', 'aktif'),
('Ahmad Fauzi', 'ahmad@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '12345678903', 'XI RPL 1', 'aktif'),
('Dewi Kartika', 'dewi@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '12345678904', 'XI RPL 2', 'aktif'),
('Eko Prasetyo', 'eko@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '12345678905', 'XII RPL 1', 'aktif'),
('Fitri Handayani', 'fitri@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '12345678906', 'XII RPL 2', 'aktif');

-- User Banned
INSERT INTO `users` (`nama`, `email`, `password`, `role`, `nisn`, `kelas`, `status`) VALUES
('Gilang Pratama', 'gilang@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '12345678907', 'X RPL 1', 'banned'),
('Hana Ramadhan', 'hana@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '12345678908', 'XI RPL 2', 'banned');

-- ============================================
-- 2. DATA BUKU
-- ============================================
INSERT INTO `buku` (`judul`, `pengarang`, `penerbit`, `tahun_terbit`, `isbn`, `kategori`, `deskripsi`, `stok`, `stok_tersedia`, `status`) VALUES
('Pemrograman Web dengan PHP', 'Andi Wijaya', 'Gramedia Pustaka', 2023, '978-602-04-1234-5', 'Teknologi', 'Buku panduan belajar PHP untuk pemula hingga mahir', 5, 4, 'aktif'),
('Matematika Dasar', 'Budi Raharjo', 'Erlangga', 2022, '978-602-04-5678-9', 'Sains', 'Matematika dasar untuk SMA/MA Kelas X', 3, 2, 'aktif'),
('Sejarah Indonesia', 'Siti Nurjanah', 'Kemendikbud', 2021, '978-602-04-9012-3', 'Sejarah', 'Buku sejarah Indonesia lengkap', 4, 4, 'aktif'),
('Fiksi Remaja', 'Dewi Lestari', 'Bentang Pustaka', 2023, '978-602-04-3456-7', 'Fiksi', 'Kumpulan cerita inspiratif untuk remaja', 6, 6, 'aktif'),
('Fisika Dasar', 'Dr. Ir. Bambang S', 'Grafindo', 2022, '978-602-04-7890-1', 'Sains', 'Fisika dasar untuk SMA Kelas XI', 3, 2, 'aktif'),
('Basis Data', 'Rosa AS', 'Informatika', 2023, '978-602-04-2345-6', 'Teknologi', 'Panduan lengkap basis data SQL', 4, 3, 'aktif'),
('Bahasa Inggris', 'Michael Swan', 'Oxford Press', 2021, '978-602-04-6789-0', 'Bahasa', 'Belajar bahasa Inggris untuk pemula', 5, 5, 'aktif'),
('Kimia Dasar', 'Raymond Chang', 'Erlangga', 2022, '978-602-04-3456-9', 'Sains', 'Kimia dasar untuk SMA', 3, 2, 'aktif'),
('Dunia Tanpa Batas', 'Tere Liye', 'Republika', 2023, '978-602-04-8901-2', 'Fiksi', 'Novel inspiratif tentang mimpi', 7, 7, 'aktif'),
('Pancasila', 'Prof. Dr. Kaelan', 'Paradigma', 2020, '978-602-04-4567-8', 'Pendidikan', 'Buku ajar pendidikan Pancasila', 4, 4, 'aktif'),
('Algoritma Pemrograman', 'Rinaldi Munir', 'Informatika', 2023, '978-602-04-5678-0', 'Teknologi', 'Algoritma dan struktur data', 4, 4, 'aktif'),
('Biologi Sel', 'Campbell', 'Erlangga', 2021, '978-602-04-6789-1', 'Sains', 'Biologi sel dan molekuler', 3, 3, 'aktif');

-- ============================================
-- 3. DATA PEMINJAMAN
-- ============================================
INSERT INTO `peminjaman` (`user_id`, `buku_id`, `tgl_pinjam`, `tgl_kembali`, `status`, `tgl_dikembalikan`) VALUES
-- Peminjaman yang sudah dikembalikan
(2, 1, '2024-01-10', '2024-01-17', 'dikembalikan', '2024-01-15'),
(2, 4, '2024-01-15', '2024-01-22', 'dikembalikan', '2024-01-20'),
(3, 2, '2024-01-20', '2024-01-27', 'dikembalikan', '2024-01-25'),
(4, 5, '2024-02-01', '2024-02-08', 'dikembalikan', '2024-02-07'),

-- Peminjaman yang masih dipinjam (aktif)
(2, 2, '2024-03-01', '2024-03-08', 'dipinjam', NULL),
(3, 5, '2024-03-05', '2024-03-12', 'dipinjam', NULL),
(4, 1, '2024-03-10', '2024-03-17', 'dipinjam', NULL),
(5, 6, '2024-03-12', '2024-03-19', 'dipinjam', NULL),

-- Peminjaman yang terlambat (sudah lewat jatuh tempo)
(6, 3, '2024-02-20', '2024-02-27', 'dipinjam', NULL),
(7, 7, '2024-02-25', '2024-03-03', 'dipinjam', NULL),
(8, 8, '2024-02-28', '2024-03-06', 'dipinjam', NULL);

-- ============================================
-- 4. DATA DENDA
-- ============================================
INSERT INTO `denda` (`peminjaman_id`, `user_id`, `hari_terlambat`, `jumlah_denda`, `status_bayar`, `tgl_bayar`) VALUES
-- Denda sudah dibayar
(1, 2, 0, 0, 'sudah', '2024-01-15'),
(3, 3, 0, 0, 'sudah', '2024-01-25'),
(4, 4, 0, 0, 'sudah', '2024-02-07'),

-- Denda belum dibayar (dari peminjaman terlambat)
(9, 6, 7, 7000, 'belum', NULL),
(10, 7, 5, 5000, 'belum', NULL),
(11, 8, 3, 3000, 'belum', NULL);

-- ============================================
-- 5. DATA PERINGATAN (Notifikasi)
-- ============================================
INSERT INTO `peringatan` (`user_id`, `pesan`, `tipe`, `is_read`) VALUES
(2, 'Selamat datang di Perpustakaan Dyzen!', 'info', 1),
(2, 'Buku "Pemrograman Web dengan PHP" akan jatuh tempo dalam 2 hari.', 'warning', 0),
(3, 'Selamat datang di Perpustakaan Dyzen!', 'info', 1),
(4, 'Selamat datang di Perpustakaan Dyzen!', 'info', 1),
(6, 'Buku "Sejarah Indonesia" telah melebihi batas waktu peminjaman. Segera kembalikan untuk menghindari denda.', 'danger', 0),
(7, 'Buku "Bahasa Inggris" akan jatuh tempo besok.', 'warning', 0),
(8, 'Akun Anda telah dibanned karena terlambat mengembalikan buku.', 'danger', 0);

-- ============================================
-- 6. DATA LAPORAN (Log Aktivitas)
-- ============================================
INSERT INTO `laporan` (`tipe`, `keterangan`, `generated_by`) VALUES
('login', 'Admin login ke sistem', 1),
('user_management', 'Menambahkan user baru: Budi Santoso', 1),
('user_management', 'Menambahkan user baru: Siti Aminah', 1),
('peminjaman', 'User Budi Santoso meminjam buku "Pemrograman Web"', NULL),
('pengembalian', 'User Budi Santoso mengembalikan buku "Pemrograman Web"', NULL),
('user_management', 'Mem-ban user Gilang Pratama karena overdue', 1),
('denda', 'Mencatat pembayaran denda dari user Siti Aminah', 1);

-- ============================================
-- 7. DATA LOGIN LOGS
-- ============================================
INSERT INTO `login_logs` (`user_id`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES
(1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'success', '2024-03-01 08:00:00'),
(2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'success', '2024-03-01 09:30:00'),
(3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'success', '2024-03-01 10:15:00'),
(1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'success', '2024-03-02 08:00:00'),
(NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'failed', '2024-03-02 09:00:00'),
(2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'success', '2024-03-02 10:00:00');

-- ============================================
-- ============================================
-- QUERY UNTUK UPDATE STOK TERSEDIA (Jalankan jika perlu sinkronisasi)
-- ============================================
-- ============================================

-- Update stok_tersedia berdasarkan peminjaman aktif
UPDATE buku b 
SET b.stok_tersedia = b.stok - (
    SELECT COUNT(*) 
    FROM peminjaman p 
    WHERE p.buku_id = b.id AND p.status = 'dipinjam'
);

-- ============================================
-- ============================================
-- TAMPILAN STATISTIK (Optional)
-- ============================================
-- ============================================

-- Melihat statistik keseluruhan
SELECT 
    (SELECT COUNT(*) FROM users WHERE role = 'user') AS total_user,
    (SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'aktif') AS user_aktif,
    (SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'banned') AS user_banned,
    (SELECT COUNT(*) FROM buku WHERE status = 'aktif') AS total_buku,
    (SELECT COUNT(*) FROM peminjaman WHERE status = 'dipinjam') AS buku_dipinjam,
    (SELECT COALESCE(SUM(jumlah_denda), 0) FROM denda WHERE status_bayar = 'belum') AS total_denda;

-- ============================================
-- SELESAI
-- ============================================