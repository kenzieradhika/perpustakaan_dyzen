<?php
require_once '../../config/data_base.php';
require_once '../../config/logger.php';
requireAdmin();

$action = isset($_GET['action']) ? $_GET['action'] : 'view';
$archive_listing = Logger::getArchiveListing();
$archive_stats = Logger::getArchiveStats();

// Handle restore action
if ($action == 'restore' && isset($_GET['file'])) {
    $file = basename($_GET['file']);
    if (Logger::restoreLog($file)) {
        $_SESSION['success'] = "Log berhasil direstore dari archive.";
    } else {
        $_SESSION['error'] = "Gagal merestore log.";
    }
    header('Location: logs_archive.php');
    exit();
}

// Handle delete archive
if ($action == 'delete' && isset($_GET['file']) && isset($_GET['csrf_token'])) {
    if (verifyCsrfToken($_GET['csrf_token'])) {
        $file = basename($_GET['file']);
        $filepath = __DIR__ . '/../../logs/archive/' . $file;
        
        // Need to find full path
        foreach ($archive_listing as $year => $months) {
            foreach ($months as $month => $files) {
                foreach ($files as $file_info) {
                    if ($file_info['name'] == $file) {
                        $full_path = __DIR__ . "/../../logs/archive/{$year}/{$month}/{$file}";
                        if (file_exists($full_path)) {
                            unlink($full_path);
                            Logger::backup("Deleted archive file", ['file' => $file]);
                            $_SESSION['success'] = "File archive berhasil dihapus.";
                        }
                        break;
                    }
                }
            }
        }
    }
    header('Location: logs_archive.php');
    exit();
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs Archive - PERPUSTAKAAN DYZEN</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .main-content {
            margin-left: 280px;
            padding: 1.5rem;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
        .archive-stats {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        }
        .file-size {
            font-family: monospace;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <header class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-playfair font-bold text-primary">Logs Archive</h1>
                    <p class="text-text-light">Kelola arsip log sistem perpustakaan</p>
                </div>
                <a href="logs.php" class="btn-outline">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Logs
                </a>
            </div>
        </header>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= e($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= e($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Archive Statistics -->
        <div class="grid md:grid-cols-4 gap-6 mb-8">
            <div class="archive-stats rounded-lg p-6 text-white">
                <p class="text-white text-opacity-90">Total Arsip</p>
                <?php 
                $total_files = 0;
                foreach ($archive_listing as $year => $months) {
                    foreach ($months as $month => $files) {
                        $total_files += count($files);
                    }
                }
                ?>
                <p class="text-3xl font-bold"><?= number_format($total_files) ?></p>
                <p class="text-sm mt-2">file log terarsip</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                <p class="text-text-light">Tahun Arsip</p>
                <p class="text-2xl font-bold text-primary"><?= count($archive_listing) ?></p>
                <p class="text-sm mt-2">tahun</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                <p class="text-text-light">Bulan Arsip</p>
                <p class="text-2xl font-bold text-primary">
                    <?php 
                    $total_months = 0;
                    foreach ($archive_listing as $year => $months) {
                        $total_months += count($months);
                    }
                    echo number_format($total_months);
                    ?>
                </p>
                <p class="text-sm mt-2">bulan</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                <p class="text-text-light">Retensi</p>
                <p class="text-2xl font-bold text-primary">365</p>
                <p class="text-sm mt-2">hari</p>
            </div>
        </div>
        
        <!-- Archive Browser -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-4 border-b bg-light">
                <div class="flex items-center gap-2">
                    <!-- SVG Icon - Folder/Archive -->
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                    <h3 class="font-bold text-lg">Arsip Logs</h3>
                </div>
                <p class="text-text-light text-sm mt-1">Logs dirotasi otomatis setiap 5MB dan diarsipkan per bulan</p>
            </div>
            
            <div class="p-4">
                <?php if (empty($archive_listing)): ?>
                    <div class="text-center py-12 text-text-light">
                        <svg class="w-20 h-20 mx-auto mb-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                        <p>Belum ada arsip logs.</p>
                        <p class="text-sm mt-1">Logs akan diarsipkan secara otomatis saat mencapai 5MB.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($archive_listing as $year => $months): ?>
                        <div class="mb-6">
                            <h4 class="font-bold text-lg text-primary mb-3 border-b pb-2">Tahun <?= htmlspecialchars($year) ?></h4>
                            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($months as $month => $files): ?>
                                    <div class="border rounded-lg overflow-hidden">
                                        <div class="bg-light px-4 py-2 font-semibold">
                                            <?= str_replace('-', ' ', htmlspecialchars($month)) ?>
                                            <span class="text-text-light text-sm ml-2">(<?= count($files) ?> files)</span>
                                        </div>
                                        <div class="divide-y max-h-64 overflow-y-auto">
                                            <?php foreach ($files as $file): ?>
                                                <div class="px-4 py-2 hover:bg-light flex justify-between items-center text-sm">
                                                    <div class="flex-1">
                                                        <div class="font-mono text-xs"><?= htmlspecialchars($file['name']) ?></div>
                                                        <div class="text-text-light text-xs">
                                                            <?= date('d/m/Y H:i', strtotime($file['modified'])) ?> | 
                                                            <span class="file-size"><?= number_format($file['size'] / 1024, 2) ?> KB</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex space-x-2">
                                                        <a href="?action=restore&file=<?= urlencode($year . '/' . $month . '/' . $file['name']) ?>" 
                                                           class="text-green-600 hover:text-green-700"
                                                           title="Restore to current log"
                                                           onclick="return confirm('Restore log ini ke log aktif?')">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                            </svg>
                                                        </a>
                                                        <a href="?action=delete&file=<?= urlencode($year . '/' . $month . '/' . $file['name']) ?>&csrf_token=<?= $csrf_token ?>" 
                                                           class="text-red-600 hover:text-red-700"
                                                           title="Delete archive"
                                                           onclick="return confirm('Hapus file archive ini? Tindakan tidak dapat dibatalkan.')">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="p-4 border-t bg-light text-text-light text-sm">
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-primary mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Logs dirotasi otomatis saat mencapai 5MB</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-primary mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                        <span>Arsip disimpan selama 365 hari</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-primary mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>File archive dikompres dengan GZIP</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-primary mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        <span>Bisa direstore ke log aktif</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Storage Info -->
        <div class="bg-light rounded-lg p-4 mt-6">
            <div class="flex justify-between items-center">
                <div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                        <h4 class="font-semibold">Storage Information</h4>
                    </div>
                    <p class="text-text-light text-sm mt-1">Lokasi arsip: <code class="text-xs"><?= __DIR__ . '/../../logs/archive/' ?></code></p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-text-light">Total size archive</p>
                    <?php
                    $total_size = 0;
                    foreach ($archive_listing as $year => $months) {
                        foreach ($months as $month => $files) {
                            foreach ($files as $file) {
                                $total_size += $file['size'];
                            }
                        }
                    }
                    ?>
                    <p class="font-bold"><?= number_format($total_size / 1048576, 2) ?> MB</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
</body>
</html>