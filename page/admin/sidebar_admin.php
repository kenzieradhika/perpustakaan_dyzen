<?php
// Sidebar component for admin
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="p-6">
        <!-- Logo dengan SVG baru -->
        <div class="flex items-center space-x-3 mb-8">
            <svg class="w-10 h-10" role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <title>PerDzyn</title>
                <path d="M12 0 .784 3.984l1.711 14.772L12 24l9.506-5.244 1.71-14.772ZM8.354 4.212h1.674L9.19 6.124l-2.51-.24Zm2.032 0h1.315v6.812h-.717L5.843 9.112l-.717-2.988 4.308.35Zm1.794 0h1.314l.953 2.261 4.427-.349-.717 2.988-5.14 1.912h-.837Zm1.673 0h1.674L17.2 5.885l-2.51.239zM5.963 9.59l1.315.478 1.315 1.315 1.076-.24-.837 1.196v3.704l-2.87-2.39zm11.955 0v4.063l-2.87 2.39v-3.704l-.837-1.195 1.077.239 1.314-1.315zm-7.786 1.536.596.36h2.384l.597-.36.953 1.437v5.388l-.715 1.078-.835.838h-2.384l-.834-.838-.715-1.078v-5.388zm-2.854 4.08 1.554 1.315v1.793L7.278 16.76Zm9.324 0v1.554l-1.553 1.554V16.52z"/>
            </svg>
            <span class="text-white font-playfair text-lg font-bold">PERPUSTAKAAN<br>DYZEN</span>
        </div>
        
        <nav class="space-y-2">
            <a href="index_admin.php" class="sidebar-nav-item <?= $current_page == 'index_admin.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Dashboard</span>
            </a>
            
            <a href="list_buku.php" class="sidebar-nav-item <?= strpos($current_page, 'buku') !== false ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <span>Data Buku</span>
            </a>
            
            <a href="tambah_buku.php" class="sidebar-nav-item <?= $current_page == 'tambah_buku.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Buku</span>
            </a>
            
            <a href="list_user.php" class="sidebar-nav-item <?= strpos($current_page, 'user') !== false && $current_page != 'tambah_user.php' && $current_page != 'banned_users.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <span>Data User</span>
            </a>
            
            <a href="tambah_user.php" class="sidebar-nav-item <?= $current_page == 'tambah_user.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                <span>Tambah User</span>
            </a>
            
            <!-- MENU BANNED USERS - TAMBAHKAN INI -->
            <a href="banned_users.php" class="sidebar-nav-item <?= $current_page == 'banned_users.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                <span>Banned Users</span>
            </a>
            
            <a href="denda_siswa.php" class="sidebar-nav-item <?= $current_page == 'denda_siswa.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Denda Siswa</span>
            </a>
            
            <a href="laporan.php" class="sidebar-nav-item <?= $current_page == 'laporan.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Laporan</span>
            </a>
            
            <a href="profile_admin.php" class="sidebar-nav-item <?= $current_page == 'profile_admin.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span>Profile</span>
            </a>

            <a href="logs_archive.php" class="sidebar-nav-item <?= $current_page == 'logs_archive.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                <span>Logs Archive</span>
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
    
    // Add toggle button for mobile
    if (window.innerWidth <= 768) {
        const header = document.querySelector('.main-content header');
        if (header) {
            const btn = document.createElement('button');
            btn.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>';
            btn.className = 'mr-4 text-primary';
            btn.onclick = toggleSidebar;
            header.insertBefore(btn, header.firstChild);
        }
    }
</script>