<?php
// Sidebar component for user
$current_page = basename($_SERVER['PHP_SELF']);

// Get unread notification count
global $pdo;
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peringatan WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unreadCount = $stmt->fetch()['total'];
?>
<aside class="sidebar" id="sidebar">
    <div class="p-6">
        <div class="flex items-center space-x-3 mb-8">
            <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="none">
                <path d="M4 6H20V18H4V6Z" stroke="currentColor" stroke-width="2"/>
                <path d="M8 4V8M16 4V8" stroke="currentColor" stroke-width="2"/>
                <path d="M12 11V16M9.5 13.5H14.5" stroke="currentColor" stroke-width="2"/>
            </svg>
            <span class="text-white font-playfair text-lg font-bold">PERPUSTAKAAN<br>DYZEN</span>
        </div>
        
        <nav class="space-y-2">
            <a href="index_user.php" class="sidebar-nav-item <?= $current_page == 'index_user.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Dashboard</span>
            </a>
            
            <a href="list_buku.php" class="sidebar-nav-item <?= $current_page == 'list_buku.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <span>Katalog Buku</span>
            </a>
            
            <a href="pinjam_buku.php" class="sidebar-nav-item <?= $current_page == 'pinjam_buku.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Pinjam Buku</span>
            </a>
            
            <a href="kembalikan_buku.php" class="sidebar-nav-item <?= $current_page == 'kembalikan_buku.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>Kembalikan Buku</span>
            </a>
            
            <a href="history_peminjaman.php" class="sidebar-nav-item <?= $current_page == 'history_peminjaman.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span>Riwayat</span>
            </a>
            
            <a href="cek_denda.php" class="sidebar-nav-item <?= $current_page == 'cek_denda.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Cek Denda</span>
            </a>
            
            <a href="stock_buku.php" class="sidebar-nav-item <?= $current_page == 'stock_buku.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span>Stok Buku</span>
            </a>
            
            <a href="peringatan_user.php" class="sidebar-nav-item <?= $current_page == 'peringatan_user.php' ? 'active' : '' ?>">
                <div class="relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <?php if ($unreadCount > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-danger text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                            <?= $unreadCount ?>
                        </span>
                    <?php endif; ?>
                </div>
                <span>Peringatan</span>
            </a>
            
            <a href="profile_user.php" class="sidebar-nav-item <?= $current_page == 'profile_user.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span>Profile</span>
            </a>
            
            <hr class="my-4 border-gray-700">
            
            <a href="../../auth/logout.php" class="sidebar-nav-item">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Logout</span>
            </a>
        </nav>
    </div>
</aside>

<style>
    .sidebar {
        transition: transform 0.3s ease;
    }
    
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            position: fixed;
            z-index: 1000;
        }
        .sidebar.mobile-open {
            transform: translateX(0);
        }
    }
</style>

<script>
    // Mobile sidebar toggle
    const toggleSidebar = () => {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('mobile-open');
    };
    
    if (window.innerWidth <= 768) {
        const header = document.querySelector('.main-content header');
        if (header && !document.getElementById('mobileToggleBtn')) {
            const btn = document.createElement('button');
            btn.id = 'mobileToggleBtn';
            btn.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>';
            btn.className = 'mr-4 text-primary';
            btn.onclick = toggleSidebar;
            header.insertBefore(btn, header.firstChild);
        }
    }
</script>