<?php
/**
 * ADMIN DASHBOARD COMPLETE - PERPUSTAKAAN DYZEN
 * Fitur: User Management (Edit, Ban, Unban, Hapus) + 5 Tools
 */

require_once '../../config/data_base.php';
requireAdmin();

// ============================================
// HANDLE ACTIONS
// ============================================

// Handle Ban User
if (isset($_POST['action']) && $_POST['action'] == 'ban' && isset($_POST['user_id'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Invalid security token";
    } else {
        $id = (int)$_POST['user_id'];
        if ($id == $_SESSION['user_id']) {
            $_SESSION['error'] = "Tidak bisa ban akun sendiri!";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET status = 'banned', updated_at = NOW() WHERE id = ? AND role = 'user'");
                $stmt->execute([$id]);
                if ($stmt->rowCount() > 0) {
                    $stmt = $pdo->prepare("SELECT nama FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $user = $stmt->fetch();
                    $stmt = $pdo->prepare("INSERT INTO peringatan (user_id, pesan, tipe) VALUES (?, 'Akun Anda telah dibanned oleh administrator.', 'danger')");
                    $stmt->execute([$id]);
                    $_SESSION['success'] = "User {$user['nama']} berhasil di-ban!";
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Gagal mem-ban user: " . $e->getMessage();
            }
        }
    }
    header('Location: admin_dashboard.php');
    exit();
}

// Handle Unban User
if (isset($_POST['action']) && $_POST['action'] == 'unban' && isset($_POST['user_id'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Invalid security token";
    } else {
        $id = (int)$_POST['user_id'];
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = 'aktif', updated_at = NOW() WHERE id = ? AND role = 'user'");
            $stmt->execute([$id]);
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("SELECT nama FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $user = $stmt->fetch();
                $stmt = $pdo->prepare("INSERT INTO peringatan (user_id, pesan, tipe) VALUES (?, 'Akun Anda telah diaktifkan kembali oleh administrator.', 'success')");
                $stmt->execute([$id]);
                $_SESSION['success'] = "User {$user['nama']} berhasil di-unban!";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Gagal mengaktifkan user: " . $e->getMessage();
        }
    }
    header('Location: admin_dashboard.php');
    exit();
}

// Handle Delete User
if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['user_id'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Invalid security token";
    } else {
        $id = (int)$_POST['user_id'];
        if ($id == $_SESSION['user_id']) {
            $_SESSION['error'] = "Tidak bisa hapus akun sendiri!";
        } else {
            // Cek peminjaman aktif
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE user_id = ? AND status = 'dipinjam'");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "User masih memiliki buku yang dipinjam!";
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT nama, foto FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $user = $stmt->fetch();
                    
                    if ($user['foto'] && file_exists(UPLOAD_PATH . $user['foto'])) {
                        unlink(UPLOAD_PATH . $user['foto']);
                    }
                    
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['success'] = "User {$user['nama']} berhasil dihapus!";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Gagal menghapus user: " . $e->getMessage();
                }
            }
        }
    }
    header('Location: admin_dashboard.php');
    exit();
}

// Handle Edit User
if (isset($_POST['action']) && $_POST['action'] == 'edit' && isset($_POST['user_id'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Invalid security token";
    } else {
        $id = (int)$_POST['user_id'];
        $nama = sanitize($_POST['nama']);
        $email = sanitize($_POST['email']);
        $kelas = sanitize($_POST['kelas']);
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, kelas = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$nama, $email, $kelas, $id]);
            $_SESSION['success'] = "User berhasil diupdate!";
        } catch (Exception $e) {
            $_SESSION['error'] = "Gagal update user: " . $e->getMessage();
        }
    }
    header('Location: admin_dashboard.php');
    exit();
}

// ============================================
// GET DATA
// ============================================
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$query = "SELECT * FROM users WHERE role = 'user'";
$params = [];
if ($search) {
    $query .= " AND (nama LIKE ? OR email LIKE ? OR nisn LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status_filter) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}
$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Statistics
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalActive = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'aktif'")->fetchColumn();
$totalBanned = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'banned'")->fetchColumn();
$totalBooks = $pdo->query("SELECT COUNT(*) FROM buku WHERE status = 'aktif'")->fetchColumn();
$totalLoans = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'dipinjam'")->fetchColumn();
$totalFines = $pdo->query("SELECT COALESCE(SUM(jumlah_denda), 0) FROM denda WHERE status_bayar = 'belum'")->fetchColumn();

$csrf_token = generateCsrfToken();
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PERPUSTAKAAN DYZEN</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0F172A; font-family: 'Inter', sans-serif; }
        
        .sidebar {
            position: fixed; left: 0; top: 0; width: 260px; height: 100vh;
            background: linear-gradient(180deg, #09637E 0%, #054A5E 100%);
            color: white; overflow-y: auto; z-index: 1000;
        }
        .sidebar-nav-item {
            display: flex; align-items: center; gap: 12px; padding: 12px 20px;
            margin: 4px 12px; border-radius: 12px; color: rgba(255,255,255,0.8);
            text-decoration: none; transition: all 0.2s;
        }
        .sidebar-nav-item:hover, .sidebar-nav-item.active {
            background: rgba(255,255,255,0.15); color: white; transform: translateX(4px);
        }
        
        .main-content { margin-left: 260px; padding: 1.5rem; min-height: 100vh; }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); transition: 0.3s; } .sidebar.mobile-open { transform: translateX(0); } .main-content { margin-left: 0; } }
        
        .stat-card { background: rgba(30,41,59,0.6); backdrop-filter: blur(12px); border-radius: 1rem; padding: 1.25rem; border: 1px solid rgba(255,255,255,0.08); transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-4px); border-color: rgba(96,165,250,0.3); }
        
        .btn-primary { background: linear-gradient(135deg, #09637E, #088395); color: white; padding: 8px 20px; border-radius: 8px; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(9,99,126,0.4); }
        .btn-danger { background: #EF4444; color: white; padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-danger:hover { background: #DC2626; transform: translateY(-1px); }
        .btn-warning { background: #F59E0B; color: white; padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-warning:hover { background: #D97706; }
        .btn-success { background: #10B981; color: white; padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-success:hover { background: #059669; }
        .btn-outline { background: transparent; border: 1px solid #09637E; color: #09637E; padding: 6px 12px; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
        .btn-outline:hover { background: #09637E; color: white; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 1rem; max-width: 500px; width: 90%; padding: 1.5rem; animation: modalSlide 0.3s ease; }
        @keyframes modalSlide { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .alert-success { background: #D1FAE5; border: 1px solid #10B981; color: #065F46; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
        .alert-error { background: #FEE2E2; border: 1px solid #EF4444; color: #991B1B; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
        
        .badge-active { background: #D1FAE5; color: #065F46; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600; }
        .badge-banned { background: #FEE2E2; color: #991B1B; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600; }
        
        .tool-card { background: white; border-radius: 1rem; padding: 1.5rem; text-align: center; transition: all 0.3s; cursor: pointer; }
        .tool-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        
        @media (max-width: 768px) { .main-content { padding: 1rem; } .stat-card { padding: 1rem; } }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="p-6">
        <div class="flex items-center gap-3 mb-8">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5">
                <path d="M4 6H20V18H4V6Z"/>
                <path d="M8 4V8M16 4V8"/>
                <path d="M8 12H16M8 16H13"/>
                <circle cx="12" cy="12" r="2"/>
            </svg>
            <span class="text-white font-playfair text-lg font-bold">DYZEN<br>LIBRARY</span>
        </div>
        <nav class="space-y-1">
            <a href="#" class="sidebar-nav-item active"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg><span>Dashboard</span></a>
            <a href="#" class="sidebar-nav-item" onclick="showSection('users')"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg><span>Data User</span></a>
            <a href="#" class="sidebar-nav-item" onclick="showSection('books')"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg><span>Data Buku</span></a>
            <a href="#" class="sidebar-nav-item" onclick="showSection('loans')"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><span>Peminjaman</span></a>
            <a href="#" class="sidebar-nav-item" onclick="showSection('fines')"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span>Denda</span></a>
            <hr class="my-4 border-gray-700">
            <a href="../../auth/logout.php" class="sidebar-nav-item"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg><span>Logout</span></a>
        </nav>
    </div>
</aside>

<!-- Main Content -->
<div class="main-content">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex justify-between items-center flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-playfair font-bold text-primary">Admin Dashboard</h1>
                <p class="text-text-light mt-1">Selamat datang, <?= e($_SESSION['user_name']) ?>!</p>
            </div>
            <button onclick="toggleSidebar()" class="md:hidden btn-primary">☰ Menu</button>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($success): ?>
    <div class="alert-success"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span><?= e($success) ?></span></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert-error"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span><?= e($error) ?></span></div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div class="stat-card text-center"><div class="text-2xl font-bold text-primary"><?= $totalUsers ?></div><div class="text-sm text-text-light">Total User</div></div>
        <div class="stat-card text-center"><div class="text-2xl font-bold text-green-500"><?= $totalActive ?></div><div class="text-sm text-text-light">Aktif</div></div>
        <div class="stat-card text-center"><div class="text-2xl font-bold text-red-500"><?= $totalBanned ?></div><div class="text-sm text-text-light">Banned</div></div>
        <div class="stat-card text-center"><div class="text-2xl font-bold text-primary"><?= $totalBooks ?></div><div class="text-sm text-text-light">Total Buku</div></div>
        <div class="stat-card text-center"><div class="text-2xl font-bold text-yellow-500"><?= $totalLoans ?></div><div class="text-sm text-text-light">Dipinjam</div></div>
        <div class="stat-card text-center"><div class="text-2xl font-bold text-red-500">Rp<?= number_format($totalFines, 0, ',', '.') ?></div><div class="text-sm text-text-light">Denda</div></div>
    </div>

    <!-- Section: Users (Default Visible) -->
    <div id="section-users" class="section-content">
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center flex-wrap gap-4 mb-4">
                <h2 class="text-xl font-bold text-primary">📋 Manajemen User</h2>
                <a href="tambah_user.php" class="btn-primary">+ Tambah User</a>
            </div>
            <form method="GET" class="grid md:grid-cols-3 gap-4 mb-4">
                <input type="text" name="search" placeholder="Cari nama, email, NISN..." value="<?= e($search) ?>" class="px-4 py-2 border rounded-lg">
                <select name="status" class="px-4 py-2 border rounded-lg">
                    <option value="">Semua Status</option>
                    <option value="aktif" <?= $status_filter == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="banned" <?= $status_filter == 'banned' ? 'selected' : '' ?>>Banned</option>
                </select>
                <button type="submit" class="btn-primary">Filter</button>
            </form>
            
            <div class="overflow-x-auto">
                <table id="usersTable" class="w-full">
                    <thead class="bg-gradient-to-r from-primary to-secondary text-white">
                        <tr><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">NISN</th><th class="px-4 py-3">Kelas</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 font-semibold"><?= e($user['nama']) ?></td>
                            <td class="px-4 py-3"><?= e($user['email']) ?></td>
                            <td class="px-4 py-3"><?= e($user['nisn']) ?></td>
                            <td class="px-4 py-3"><?= e($user['kelas']) ?></td>
                            <td class="px-4 py-3"><span class="<?= $user['status'] == 'aktif' ? 'badge-active' : 'badge-banned' ?>"><?= $user['status'] == 'aktif' ? 'Aktif' : 'Banned' ?></span></td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <button onclick="openEditModal(<?= $user['id'] ?>, '<?= addslashes($user['nama']) ?>', '<?= addslashes($user['email']) ?>', '<?= addslashes($user['kelas']) ?>')" class="btn-outline text-sm py-1 px-3">Edit</button>
                                    <?php if ($user['status'] == 'aktif'): ?>
                                        <button onclick="confirmBan(<?= $user['id'] ?>, '<?= addslashes($user['nama']) ?>')" class="btn-warning text-sm py-1 px-3">Ban</button>
                                    <?php else: ?>
                                        <button onclick="confirmUnban(<?= $user['id'] ?>, '<?= addslashes($user['nama']) ?>')" class="btn-success text-sm py-1 px-3">Unban</button>
                                    <?php endif; ?>
                                    <button onclick="confirmDelete(<?= $user['id'] ?>, '<?= addslashes($user['nama']) ?>')" class="btn-danger text-sm py-1 px-3">Hapus</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section: Books (Hidden) -->
    <div id="section-books" class="section-content" style="display:none;">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-xl font-bold text-primary mb-4">📚 Manajemen Buku</h2>
            <a href="tambah_buku.php" class="btn-primary mb-4 inline-block">+ Tambah Buku</a>
            <div class="overflow-x-auto">
                <table id="booksTable" class="w-full">
                    <thead class="bg-gradient-to-r from-primary to-secondary text-white">
                        <tr><th>Judul</th><th>Pengarang</th><th>Kategori</th><th>Stok</th><th>Tersedia</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php $books = $pdo->query("SELECT * FROM buku ORDER BY created_at DESC")->fetchAll(); ?>
                        <?php foreach ($books as $book): ?>
                        <tr class="border-b">
                            <td class="px-4 py-3"><?= e($book['judul']) ?></td>
                            <td class="px-4 py-3"><?= e($book['pengarang']) ?></td>
                            <td class="px-4 py-3"><?= e($book['kategori']) ?></td>
                            <td class="px-4 py-3"><?= $book['stok'] ?></td>
                            <td class="px-4 py-3"><?= $book['stok_tersedia'] ?></td>
                            <td class="px-4 py-3"><a href="edit_buku.php?id=<?= $book['id'] ?>" class="btn-outline text-sm py-1 px-3 inline-block">Edit</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section: Loans (Hidden) -->
    <div id="section-loans" class="section-content" style="display:none;">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-xl font-bold text-primary mb-4">📖 Peminjaman Aktif</h2>
            <div class="overflow-x-auto">
                <table id="loansTable" class="w-full">
                    <thead class="bg-gradient-to-r from-primary to-secondary text-white">
                        <tr><th>Peminjam</th><th>Buku</th><th>Tgl Pinjam</th><th>Jatuh Tempo</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php $loans = $pdo->query("SELECT p.*, u.nama as user_name, b.judul as buku_judul FROM peminjaman p JOIN users u ON p.user_id = u.id JOIN buku b ON p.buku_id = b.id WHERE p.status = 'dipinjam' ORDER BY p.tgl_kembali ASC")->fetchAll(); ?>
                        <?php foreach ($loans as $loan): ?>
                        <tr class="border-b">
                            <td class="px-4 py-3"><?= e($loan['user_name']) ?></td>
                            <td class="px-4 py-3"><?= e($loan['buku_judul']) ?></td>
                            <td class="px-4 py-3"><?= date('d/m/Y', strtotime($loan['tgl_pinjam'])) ?></td>
                            <td class="px-4 py-3"><?= date('d/m/Y', strtotime($loan['tgl_kembali'])) ?></td>
                            <td class="px-4 py-3"><span class="badge-active">Dipinjam</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section: Fines (Hidden) -->
    <div id="section-fines" class="section-content" style="display:none;">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-xl font-bold text-primary mb-4">💰 Denda Belum Dibayar</h2>
            <div class="overflow-x-auto">
                <table id="finesTable" class="w-full">
                    <thead class="bg-gradient-to-r from-primary to-secondary text-white">
                        <tr><th>Nama</th><th>Buku</th><th>Hari Terlambat</th><th>Jumlah Denda</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php $fines = $pdo->query("SELECT d.*, u.nama as user_name, b.judul as buku_judul FROM denda d JOIN users u ON d.user_id = u.id JOIN peminjaman p ON d.peminjaman_id = p.id JOIN buku b ON p.buku_id = b.id WHERE d.status_bayar = 'belum'")->fetchAll(); ?>
                        <?php foreach ($fines as $fine): ?>
                        <tr class="border-b">
                            <td class="px-4 py-3"><?= e($fine['user_name']) ?></td>
                            <td class="px-4 py-3"><?= e($fine['buku_judul']) ?></td>
                            <td class="px-4 py-3"><?= $fine['hari_terlambat'] ?> hari</td>
                            <td class="px-4 py-3 text-red-500 font-bold">Rp <?= number_format($fine['jumlah_denda'], 0, ',', '.') ?></td>
                            <td class="px-4 py-3"><a href="denda_siswa.php" class="btn-primary text-sm py-1 px-3">Bayar</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 5 Tools Section -->
    <div class="grid md:grid-cols-5 gap-4 mt-8">
        <div class="tool-card" onclick="location.href='laporan.php'"><div class="text-3xl mb-2">📊</div><h3 class="font-bold">Laporan</h3><p class="text-sm text-text-light">Statistik & Grafik</p></div>
        <div class="tool-card" onclick="location.href='logs_archive.php'"><div class="text-3xl mb-2">📜</div><h3 class="font-bold">Logs Archive</h3><p class="text-sm text-text-light">Riwayat sistem</p></div>
        <div class="tool-card" onclick="location.href='profile_admin.php'"><div class="text-3xl mb-2">👤</div><h3 class="font-bold">Profile</h3><p class="text-sm text-text-light">Edit profil admin</p></div>
        <div class="tool-card" onclick="location.href='banned_users.php'"><div class="text-3xl mb-2">🚫</div><h3 class="font-bold">Banned Users</h3><p class="text-sm text-text-light">Lihat user banned</p></div>
        <div class="tool-card" onclick="location.href='denda_siswa.php'"><div class="text-3xl mb-2">💰</div><h3 class="font-bold">Kelola Denda</h3><p class="text-sm text-text-light">Manajemen denda</p></div>
    </div>
</div>

<!-- Modal Edit -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h3 class="text-xl font-bold mb-4">✏️ Edit User</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" id="edit_id">
            <div class="mb-3"><label class="block mb-1">Nama</label><input type="text" name="nama" id="edit_nama" class="w-full px-3 py-2 border rounded-lg" required></div>
            <div class="mb-3"><label class="block mb-1">Email</label><input type="email" name="email" id="edit_email" class="w-full px-3 py-2 border rounded-lg" required></div>
            <div class="mb-3"><label class="block mb-1">Kelas</label><input type="text" name="kelas" id="edit_kelas" class="w-full px-3 py-2 border rounded-lg"></div>
            <div class="flex justify-end gap-3"><button type="button" onclick="closeModal('editModal')" class="btn-outline">Batal</button><button type="submit" class="btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

<!-- Modal Ban -->
<div id="banModal" class="modal">
    <div class="modal-content"><h3 class="text-xl font-bold mb-4">⚠️ Konfirmasi Ban</h3><p id="banMessage" class="mb-6"></p>
        <form method="POST"><input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="action" value="ban"><input type="hidden" name="user_id" id="ban_id">
        <div class="flex justify-end gap-3"><button type="button" onclick="closeModal('banModal')" class="btn-outline">Batal</button><button type="submit" class="btn-warning">Ban User</button></div></form>
    </div>
</div>

<!-- Modal Unban -->
<div id="unbanModal" class="modal">
    <div class="modal-content"><h3 class="text-xl font-bold mb-4">✅ Konfirmasi Unban</h3><p id="unbanMessage" class="mb-6"></p>
        <form method="POST"><input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="action" value="unban"><input type="hidden" name="user_id" id="unban_id">
        <div class="flex justify-end gap-3"><button type="button" onclick="closeModal('unbanModal')" class="btn-outline">Batal</button><button type="submit" class="btn-success">Unban User</button></div></form>
    </div>
</div>

<!-- Modal Delete -->
<div id="deleteModal" class="modal">
    <div class="modal-content"><h3 class="text-xl font-bold mb-4">🗑️ Konfirmasi Hapus</h3><p id="deleteMessage" class="mb-6"></p>
        <form method="POST"><input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" id="delete_id">
        <div class="flex justify-end gap-3"><button type="button" onclick="closeModal('deleteModal')" class="btn-outline">Batal</button><button type="submit" class="btn-danger">Hapus</button></div></form>
    </div>
</div>

<script>
    // DataTables
    $(document).ready(function() {
        $('#usersTable').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }, pageLength: 10, responsive: true });
        $('#booksTable').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }, pageLength: 10 });
        $('#loansTable').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }, pageLength: 10 });
        $('#finesTable').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' }, pageLength: 10 });
    });

    function showSection(section) {
        document.querySelectorAll('.section-content').forEach(el => el.style.display = 'none');
        document.getElementById(`section-${section}`).style.display = 'block';
        document.querySelectorAll('.sidebar-nav-item').forEach(el => el.classList.remove('active'));
        event.target.closest('.sidebar-nav-item').classList.add('active');
    }

    function openEditModal(id, nama, email, kelas) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_kelas').value = kelas;
        document.getElementById('editModal').classList.add('active');
    }

    function confirmBan(id, nama) {
        document.getElementById('ban_id').value = id;
        document.getElementById('banMessage').innerHTML = `Apakah Anda yakin ingin mem-ban user "<strong>${nama}</strong>"?`;
        document.getElementById('banModal').classList.add('active');
    }

    function confirmUnban(id, nama) {
        document.getElementById('unban_id').value = id;
        document.getElementById('unbanMessage').innerHTML = `Apakah Anda yakin ingin mengaktifkan kembali user "<strong>${nama}</strong>"?`;
        document.getElementById('unbanModal').classList.add('active');
    }

    function confirmDelete(id, nama) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteMessage').innerHTML = `Apakah Anda yakin ingin menghapus user "<strong>${nama}</strong>"?<br><br>Semua data terkait akan terhapus.`;
        document.getElementById('deleteModal').classList.add('active');
    }

    function closeModal(modalId) { document.getElementById(modalId).classList.remove('active'); }
    function toggleSidebar() { document.getElementById('sidebar').classList.toggle('mobile-open'); }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) event.target.classList.remove('active');
    }

    setTimeout(() => { document.querySelectorAll('.alert-success, .alert-error').forEach(el => { setTimeout(() => el.remove(), 3000); }); }, 100);
</script>
</body>
</html>