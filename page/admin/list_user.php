<?php
require_once '../../config/data_base.php';
requireAdmin();

// Ambil pesan dari session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$query = "SELECT * FROM users WHERE role = 'user'";
$params = [];

if ($search) {
    $query .= " AND (nama LIKE ? OR email LIKE ? OR nisn LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status) {
    $query .= " AND status = ?";
    $params[] = $status;
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data User - PERPUSTAKAAN DYZEN</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .main-content {
            margin-left: 280px;
            padding: 1.5rem;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
        
        /* Alert Messages */
        .alert-success {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideInRight 0.3s ease;
        }
        
        .alert-error {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideInRight 0.3s ease;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Action Buttons */
        .action-btn {
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
        }
        
        .action-edit:hover {
            background: rgba(9, 99, 126, 0.1);
        }
        
        .action-ban:hover {
            background: rgba(245, 158, 11, 0.1);
        }
        
        .action-delete:hover {
            background: rgba(239, 68, 68, 0.1);
        }
        
        .action-unban:hover {
            background: rgba(16, 185, 129, 0.1);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 1rem;
            max-width: 450px;
            width: 90%;
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Button Styles */
        .btn-primary {
            background: linear-gradient(135deg, #09637E, #088395);
            color: white;
            padding: 10px 20px;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(9, 99, 126, 0.3);
        }
        
        /* DataTables override */
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #09637E, #088395) !important;
            color: white !important;
            border: none !important;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-playfair font-bold text-primary">Data User</h1>
                    <p class="text-text-light mt-1">Kelola data member perpustakaan</p>
                </div>
                <a href="tambah_user.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-primary to-secondary text-white px-5 py-2.5 rounded-lg font-semibold hover:shadow-md transition transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    Tambah User
                </a>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <?php if ($success): ?>
        <div class="alert-success">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><?= e($success) ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert-error">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><?= e($error) ?></span>
        </div>
        <?php endif; ?>
        
        <!-- Filter Section -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <form method="GET" action="" class="grid md:grid-cols-3 gap-4">
                <div>
                    <input type="text" name="search" placeholder="Cari nama, email, NISN..." 
                           value="<?= e($search) ?>"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition">
                </div>
                <div>
                    <select name="status" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition">
                        <option value="">Semua Status</option>
                        <option value="aktif" <?= $status == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="banned" <?= $status == 'banned' ? 'selected' : '' ?>>Banned</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-primary w-full">Filter</button>
                </div>
            </form>
        </div>
        
        <!-- Users Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table id="usersTable" class="w-full">
                    <thead class="bg-gradient-to-r from-primary to-secondary text-white">
                        <tr>
                            <th class="px-6 py-4 text-left">Nama</th>
                            <th class="px-6 py-4 text-left">Email</th>
                            <th class="px-6 py-4 text-left">NISN</th>
                            <th class="px-6 py-4 text-left">Kelas</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Terakhir Login</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <?php if (!empty($user['foto']) && file_exists('../../uploads/covers/' . $user['foto'])): ?>
                                        <img src="../../uploads/covers/<?= e($user['foto']) ?>" class="w-8 h-8 rounded-full object-cover">
                                    <?php else: ?>
                                        <div class="w-8 h-8 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center text-white text-sm font-bold">
                                            <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="font-semibold"><?= e($user['nama']) ?></div>
                                </div>
                             </td>
                            <td class="px-6 py-4"><?= e($user['email']) ?></td>
                            <td class="px-6 py-4"><?= e($user['nisn']) ?></td>
                            <td class="px-6 py-4"><?= e($user['kelas']) ?></td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold <?= $user['status'] == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                    <?= $user['status'] == 'aktif' ? 'Aktif' : 'Banned' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm">
                                <?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : '-' ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center items-center gap-2">
                                    <!-- Edit Button -->
                                    <a href="edit_user.php?id=<?= $user['id'] ?>" class="action-btn action-edit text-primary" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    
                                    <!-- Ban/Unban Button -->
                                    <?php if ($user['status'] == 'aktif'): ?>
                                        <a href="javascript:void(0)" onclick="confirmBan(<?= $user['id'] ?>, '<?= addslashes($user['nama']) ?>')" class="action-btn action-ban text-yellow-500" title="Ban User">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        </a>
                                    <?php else: ?>
                                        <a href="javascript:void(0)" onclick="confirmUnban(<?= $user['id'] ?>, '<?= addslashes($user['nama']) ?>')" class="action-btn action-unban text-green-500" title="Unban User">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <!-- Delete Button -->
                                    <a href="javascript:void(0)" onclick="confirmDelete(<?= $user['id'] ?>, '<?= addslashes($user['nama']) ?>')" class="action-btn action-delete text-red-500" title="Hapus">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-text-light">
                                <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <p>Tidak ada data user.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal Delete -->
    <div id="deleteModal" class="modal">
        <div class="modal-content p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold">Konfirmasi Hapus</h3>
            </div>
            <p id="deleteMessage" class="mb-6 text-gray-600"></p>
            <div class="flex justify-end gap-3">
                <button onclick="closeModal('deleteModal')" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Batal</button>
                <a href="#" id="deleteLink" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-700 transition">Hapus</a>
            </div>
        </div>
    </div>
    
    <!-- Modal Ban -->
    <div id="banModal" class="modal">
        <div class="modal-content p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold">Konfirmasi Ban</h3>
            </div>
            <p id="banMessage" class="mb-6 text-gray-600"></p>
            <div class="flex justify-end gap-3">
                <button onclick="closeModal('banModal')" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Batal</button>
                <a href="#" id="banLink" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition">Ban User</a>
            </div>
        </div>
    </div>
    
    <!-- Modal Unban -->
    <div id="unbanModal" class="modal">
        <div class="modal-content p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold">Konfirmasi Unban</h3>
            </div>
            <p id="unbanMessage" class="mb-6 text-gray-600"></p>
            <div class="flex justify-end gap-3">
                <button onclick="closeModal('unbanModal')" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Batal</button>
                <a href="#" id="unbanLink" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-700 transition">Unban User</a>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#usersTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                },
                pageLength: 10,
                responsive: true,
                order: [[0, 'asc']]
            });
        });
        
        const csrfToken = '<?= $csrf_token ?>';
        
        function confirmDelete(id, nama) {
            const modal = document.getElementById('deleteModal');
            const message = document.getElementById('deleteMessage');
            const deleteLink = document.getElementById('deleteLink');
            
            message.innerHTML = `Apakah Anda yakin ingin menghapus user "<strong>${nama}</strong>"?<br><br>Semua data peminjaman dan denda user ini akan terhapus.`;
            deleteLink.href = `hapus_user.php?id=${id}&csrf_token=${csrfToken}`;
            modal.classList.add('active');
        }
        
        function confirmBan(id, nama) {
            const modal = document.getElementById('banModal');
            const message = document.getElementById('banMessage');
            const banLink = document.getElementById('banLink');
            
            message.innerHTML = `Apakah Anda yakin ingin mem-ban user "<strong>${nama}</strong>"?<br><br>User tidak akan bisa login dan meminjam buku.`;
            banLink.href = `ban_user.php?id=${id}&csrf_token=${csrfToken}`;
            modal.classList.add('active');
        }
        
        function confirmUnban(id, nama) {
            const modal = document.getElementById('unbanModal');
            const message = document.getElementById('unbanMessage');
            const unbanLink = document.getElementById('unbanLink');
            
            message.innerHTML = `Apakah Anda yakin ingin mengaktifkan kembali user "<strong>${nama}</strong>"?`;
            unbanLink.href = `unban_user.php?id=${id}&csrf_token=${csrfToken}`;
            modal.classList.add('active');
        }
        
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('active');
        }
        
        window.onclick = function(event) {
            const modals = ['deleteModal', 'banModal', 'unbanModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    modal.classList.remove('active');
                }
            });
        }
        
        // Auto hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-success, .alert-error');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        }, 100);
    </script>
</body>
</html>