<?php
require_once '../config/data_base.php';

// Get real-time stats for landing page
$totalBuku = $pdo->query("SELECT COUNT(*) as total FROM buku WHERE status = 'aktif'")->fetch()['total'];
$totalMember = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user' AND status = 'aktif'")->fetch()['total'];
$totalKategori = $pdo->query("SELECT COUNT(DISTINCT kategori) as total FROM buku WHERE kategori IS NOT NULL")->fetch()['total'];
$dipinjamHariIni = $pdo->query("SELECT COUNT(*) as total FROM peminjaman WHERE DATE(created_at) = CURDATE()")->fetch()['total'];

// Get featured books
$featuredBooks = $pdo->query("SELECT * FROM buku WHERE status = 'aktif' ORDER BY created_at DESC LIMIT 8")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dyzen Library | Perpustakaan Digital Premium</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #09637E;
            --primary-dark: #054A5E;
            --primary-light: #1A8BA8;
            --secondary: #088395;
            --accent: #60a5fa;
            --dark: #0F172A;
            --dark-light: #1E293B;
            --text-light: #94A3B8;
            --gradient-primary: linear-gradient(135deg, #09637E 0%, #088395 50%, #1A8BA8 100%);
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
            --shadow-xl: 0 25px 35px -12px rgba(0, 0, 0, 0.25);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--dark);
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--dark-light);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-light);
        }

        /* Navbar Styles */
        .navbar {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 1rem 0;
        }

        .navbar.scrolled {
            padding: 0.7rem 0;
            background: rgba(15, 23, 42, 0.98);
            box-shadow: var(--shadow-lg);
        }

        .navbar-brand {
            font-size: 1.6rem;
            font-weight: 800;
            font-family: 'Playfair Display', serif;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            transition: all 0.3s ease;
            font-weight: 500;
            margin: 0 0.25rem;
            position: relative;
        }

        .nav-link:hover {
            color: var(--accent) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: var(--accent);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 80%;
        }

        /* Hero Section */
        .hero-section {
            min-height: 100vh;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('../assets/images/lib.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, 
                rgba(15, 23, 42, 0.92) 0%,
                rgba(15, 23, 42, 0.85) 30%,
                rgba(9, 99, 126, 0.75) 70%,
                rgba(8, 131, 149, 0.65) 100%);
        }

        /* Glassmorphism Effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.5rem 1.2rem;
            background: rgba(96, 165, 250, 0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(96, 165, 250, 0.3);
            border-radius: 100px;
            color: var(--accent);
            font-size: 0.85rem;
            font-weight: 500;
            letter-spacing: 1px;
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.8s ease;
        }

        .hero-title {
            font-size: 4.5rem;
            font-weight: 800;
            font-family: 'Playfair Display', serif;
            color: white;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 0.8s ease 0.1s both;
        }

        .hero-title span {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            line-height: 1.6;
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Button Styles */
        .btn-primary-custom {
            background: var(--gradient-primary);
            border: none;
            padding: 0.9rem 2.2rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(9, 99, 126, 0.3);
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(9, 99, 126, 0.4);
            color: white;
        }

        .btn-outline-custom {
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(8px);
            padding: 0.9rem 2.2rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-outline-custom:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--accent);
            transform: translateY(-3px);
            color: white;
        }

        /* Scroll Indicator */
        .scroll-indicator {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 3;
            cursor: pointer;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); }
            40% { transform: translateX(-50%) translateY(-15px); }
            60% { transform: translateX(-50%) translateY(-7px); }
        }

        /* Stats Cards */
        .stats-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border-radius: 1.5rem;
            padding: 1.8rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }

        .stats-card:hover {
            transform: translateY(-8px);
            border-color: rgba(96, 165, 250, 0.3);
            box-shadow: var(--shadow-xl);
        }

        .stats-number {
            font-size: 3rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
        }

        /* Book Cards */
        .book-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border-radius: 1.2rem;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
        }

        .book-card:hover {
            transform: translateY(-10px) scale(1.02);
            border-color: rgba(96, 165, 250, 0.4);
            box-shadow: var(--shadow-xl);
        }

        .book-cover {
            height: 250px;
            background: linear-gradient(135deg, #1E293B, #0F172A);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .book-card:hover .book-cover img {
            transform: scale(1.1);
        }

        .book-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, transparent 60%, rgba(0,0,0,0.8) 100%);
        }

        /* Service Cards */
        .service-card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(12px);
            border-radius: 1.2rem;
            padding: 2rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.4s ease;
            height: 100%;
        }

        .service-card:hover {
            background: rgba(30, 41, 59, 0.8);
            transform: translateY(-8px);
            border-color: rgba(96, 165, 250, 0.3);
        }

        .service-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 1.2rem;
            background: linear-gradient(135deg, rgba(9, 99, 126, 0.2), rgba(8, 131, 149, 0.2));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .service-card:hover .service-icon {
            transform: scale(1.1);
            background: linear-gradient(135deg, rgba(9, 99, 126, 0.4), rgba(8, 131, 149, 0.4));
        }

        /* Mobile Menu */
        .mobile-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 80%;
            max-width: 320px;
            height: 100%;
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(20px);
            z-index: 1050;
            transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 2rem;
            box-shadow: -5px 0 30px rgba(0, 0, 0, 0.5);
        }

        .mobile-menu.active {
            right: 0;
        }

        .mobile-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 1040;
            display: none;
        }

        .mobile-overlay.active {
            display: block;
        }

        /* Category Badge */
        .category-badge {
            background: rgba(96, 165, 250, 0.2);
            color: var(--accent);
            font-size: 0.7rem;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            display: inline-block;
        }

        /* Footer */
        .footer {
            background: #0A0F1A;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding: 3rem 0 2rem;
        }

        /* Floating Shapes */
        .floating-shape {
            position: absolute;
            background: rgba(96, 165, 250, 0.08);
            border-radius: 50%;
            filter: blur(60px);
            z-index: 0;
        }

        /* SVG Icon Styles */
        .icon {
            width: 24px;
            height: 24px;
            stroke: currentColor;
            stroke-width: 1.5;
            fill: none;
        }

        .icon-lg {
            width: 40px;
            height: 40px;
        }

        .icon-xl {
            width: 64px;
            height: 64px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .stats-number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

<!-- Splash Screen -->
<div id="splashScreen" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #0F172A; z-index: 9999; display: flex; align-items: center; justify-content: center; transition: opacity 0.5s ease;">
    <div class="text-center">
        <!-- SVG Logo -->
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin: 0 auto 1rem;">
            <path d="M4 6H20V18H4V6Z" stroke="#60a5fa" stroke-width="1.5" fill="none"/>
            <path d="M8 4V8M16 4V8" stroke="#60a5fa" stroke-width="1.5" stroke-linecap="round"/>
            <path d="M8 12H16M8 16H13" stroke="#60a5fa" stroke-width="1.5" stroke-linecap="round"/>
            <circle cx="12" cy="12" r="2" fill="#60a5fa"/>
        </svg>
        <div style="font-size: 2rem; font-weight: 800; background: linear-gradient(135deg, #09637E, #088395); -webkit-background-clip: text; background-clip: text; color: transparent; font-family: 'Playfair Display', serif;">DYZEN</div>
        <div style="color: var(--text-light); margin-top: 0.5rem; letter-spacing: 3px;">LIBRARY</div>
        <div class="spinner-border text-primary mt-4" role="status" style="width: 2rem; height: 2rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="#">Dyzen Library</a>
        
        <button class="navbar-toggler border-0" type="button" id="mobileMenuToggle" style="background: transparent;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                <path d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="#beranda">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="#koleksi">Koleksi</a></li>
                <li class="nav-item"><a class="nav-link" href="#layanan">Layanan</a></li>
                <li class="nav-item"><a class="nav-link" href="#tentang">Tentang</a></li>
            </ul>
            <div class="d-flex gap-2">
                <a href="../auth/login.php" class="btn btn-primary-custom">Login</a>
                <a href="../auth/register.php" class="btn btn-outline-custom d-none d-sm-inline-block">Daftar</a>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Menu -->
<div class="mobile-overlay" id="mobileOverlay"></div>
<div class="mobile-menu" id="mobileMenu">
    <div class="d-flex justify-content-end mb-4">
        <button class="btn p-0" id="closeMobileMenu" style="background: transparent;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                <path d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    <div class="d-flex flex-column gap-3">
        <a href="#beranda" class="text-white text-decoration-none py-2 fs-5 mobile-nav-link">Beranda</a>
        <a href="#koleksi" class="text-white text-decoration-none py-2 fs-5 mobile-nav-link">Koleksi</a>
        <a href="#layanan" class="text-white text-decoration-none py-2 fs-5 mobile-nav-link">Layanan</a>
        <a href="#tentang" class="text-white text-decoration-none py-2 fs-5 mobile-nav-link">Tentang</a>
        <hr class="border-secondary">
        <a href="../auth/login.php" class="btn btn-primary-custom text-white py-2 rounded text-center">Login</a>
        <a href="../auth/register.php" class="btn btn-outline-custom text-white py-2 rounded text-center">Daftar</a>
    </div>
</div>

<!-- Hero Section -->
<section id="beranda" class="hero-section">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    
    <!-- Floating Shapes -->
    <div class="floating-shape" style="width: 300px; height: 300px; top: 10%; left: -100px;"></div>
    <div class="floating-shape" style="width: 200px; height: 200px; bottom: 20%; right: -50px;"></div>
    <div class="floating-shape" style="width: 150px; height: 150px; top: 50%; right: 20%;"></div>
    
    <div class="hero-content">
        <div class="hero-badge" data-aos="fade-up">
            <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
            Premium Digital Library
        </div>
        <h1 class="hero-title" data-aos="fade-up" data-aos-delay="100">
            Dyzen <span>Library</span>
        </h1>
        <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="200">
            Temukan jutaan pengetahuan, baca tanpa batas, dan jelajahi dunia 
            <br>melalui setiap lembar halaman.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap" data-aos="fade-up" data-aos-delay="300">
            <a href="#koleksi" class="btn btn-primary-custom">
                <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Mulai Membaca
            </a>
            <a href="#tentang" class="btn btn-outline-custom">
                <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 8v4M12 16h.01"/>
                </svg>
                Pelajari Lebih Lanjut
            </a>
        </div>
    </div>
    
    <div class="scroll-indicator">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
            <path d="M19 9l-7 7-7-7"/>
        </svg>
    </div>
</section>

<!-- Statistics Section -->
<section class="py-5" style="background: linear-gradient(180deg, #0F172A 0%, #1E293B 100%);">
    <div class="container">
        <div class="row g-4">
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="0">
                <div class="stats-card">
                    <div class="stats-number counter" data-target="<?= $totalBuku ?>">0</div>
                    <p class="mt-2 mb-0" style="color: var(--text-light);">
                        <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="display: inline; margin-right: 4px;">
                            <path d="M4 6H20V18H4V6Z" stroke="currentColor"/>
                            <path d="M8 4V8M16 4V8" stroke="currentColor"/>
                        </svg>
                        Total Buku
                    </p>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="100">
                <div class="stats-card">
                    <div class="stats-number counter" data-target="<?= $totalMember ?>">0</div>
                    <p class="mt-2 mb-0" style="color: var(--text-light);">
                        <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="display: inline; margin-right: 4px;">
                            <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Member Aktif
                    </p>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="200">
                <div class="stats-card">
                    <div class="stats-number counter" data-target="<?= $totalKategori ?>">0</div>
                    <p class="mt-2 mb-0" style="color: var(--text-light);">
                        <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="display: inline; margin-right: 4px;">
                            <path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l5 5a2 2 0 01.586 1.414V19a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                        </svg>
                        Kategori Buku
                    </p>
                </div>
            </div>
            <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="300">
                <div class="stats-card">
                    <div class="stats-number counter" data-target="<?= $dipinjamHariIni ?>">0</div>
                    <p class="mt-2 mb-0" style="color: var(--text-light);">
                        <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="display: inline; margin-right: 4px;">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        Dipinjam Hari Ini
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Koleksi Section -->
<section id="koleksi" class="py-5">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="display-5 fw-bold mb-3" style="color: white;">
                <svg class="icon" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                    <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>
                </svg>
                Koleksi Unggulan
            </h2>
            <p class="mx-auto" style="max-width: 600px; color: var(--text-light);">
                Dari fiksi hingga sains, tersedia ribuan judul yang siap menemani harimu.
            </p>
        </div>
        
        <div class="row g-4">
            <?php if (!empty($featuredBooks)): ?>
                <?php foreach($featuredBooks as $index => $book): ?>
                <div class="col-12 col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                    <div class="book-card">
                        <div class="book-cover">
                            <?php if(!empty($book['cover']) && file_exists('../uploads/covers/' . $book['cover'])): ?>
                                <img src="../uploads/covers/<?= htmlspecialchars($book['cover']) ?>" alt="<?= htmlspecialchars($book['judul']) ?>">
                            <?php else: ?>
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5">
                                    <path d="M4 6H20V18H4V6Z"/>
                                    <path d="M8 4V8M16 4V8"/>
                                    <path d="M8 12H16M8 16H13"/>
                                </svg>
                            <?php endif; ?>
                            <div class="book-overlay"></div>
                        </div>
                        <div class="p-4">
                            <h5 class="fw-bold text-white mb-1 text-truncate"><?= htmlspecialchars($book['judul']) ?></h5>
                            <p class="small mb-2" style="color: var(--text-light);">
                                <svg class="icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="display: inline; margin-right: 4px;">
                                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                <?= htmlspecialchars($book['pengarang']) ?>
                            </p>
                            <span class="category-badge mb-3">
                                <svg class="icon" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="display: inline; margin-right: 4px;">
                                    <path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l5 5a2 2 0 01.586 1.414V19a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                                </svg>
                                <?= htmlspecialchars($book['kategori']) ?>
                            </span>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="fw-semibold small" style="color: #4ade80;">
                                    <svg class="icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="display: inline; margin-right: 4px;">
                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                        <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>
                                    </svg>
                                    Stok: <?= $book['stok_tersedia'] ?>
                                </span>
                                <a href="../auth/login.php" class="text-decoration-none small fw-semibold" style="color: var(--accent);">
                                    Pinjam 
                                    <svg class="icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="display: inline; margin-left: 4px;">
                                        <path d="M5 12h14M12 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5" style="margin: 0 auto 1rem;">
                        <path d="M4 6H20V18H4V6Z"/>
                        <path d="M8 4V8M16 4V8"/>
                        <path d="M8 12H16M8 16H13"/>
                    </svg>
                    <p style="color: var(--text-light);">Belum ada buku tersedia.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="../auth/register.php" class="btn btn-primary-custom">
                <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
                Lihat Semua Koleksi
            </a>
        </div>
    </div>
</section>

<!-- Layanan Section -->
<section id="layanan" class="py-5" style="background: linear-gradient(180deg, #1E293B 0%, #0F172A 100%);">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="display-5 fw-bold mb-3" style="color: white;">
                <svg class="icon" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                Layanan Kami
            </h2>
            <p style="color: var(--text-light);">Fasilitas modern untuk mendukung literasi dan kenyamanan Anda.</p>
        </div>
        
        <div class="row g-4">
            <div class="col-12 col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="0">
                <div class="service-card">
                    <div class="service-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="2" y="2" width="20" height="20" rx="2.18"/>
                            <path d="M7 2v20M17 2v20M2 12h20M2 7h5M2 17h5M17 17h5M17 7h5"/>
                        </svg>
                    </div>
                    <h5 class="fw-bold mb-2 text-white">Akses Digital</h5>
                    <p class="small mb-0" style="color: var(--text-light);">eBook & audiobook 24/7</p>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                <div class="service-card">
                    <div class="service-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </div>
                    <h5 class="fw-bold mb-2 text-white">Ruang Baca</h5>
                    <p class="small mb-0" style="color: var(--text-light);">Nyaman & ber-AC</p>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                <div class="service-card">
                    <div class="service-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    </div>
                    <h5 class="fw-bold mb-2 text-white">Mentoring</h5>
                    <p class="small mb-0" style="color: var(--text-light);">Bimbingan riset & literasi</p>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                <div class="service-card">
                    <div class="service-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M12 2a10 10 0 1010 10c0-5.5-4.5-10-10-10z"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    </div>
                    <h5 class="fw-bold mb-2 text-white">Kafe Literasi</h5>
                    <p class="small mb-0" style="color: var(--text-light);">Diskusi santai & kopi</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Tentang Section -->
<section id="tentang" class="py-5">
    <div class="container">
        <div class="text-center mx-auto" style="max-width: 800px;" data-aos="fade-up">
            <h2 class="display-5 fw-bold mb-4" style="color: white;">
                <svg class="icon" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4M12 8h.01"/>
                </svg>
                Tentang Dyzen Library
            </h2>
            <p class="fs-5 mb-5" style="color: #cbd5e1;">
                Berdiri sejak 2020, Dyzen Library hadir sebagai ruang inklusif untuk semua kalangan. 
                Kami percaya bahwa akses terhadap pengetahuan adalah hak setiap orang.
            </p>
            
            <div class="row g-4">
                <div class="col-12 col-md-4" data-aos="fade-up" data-aos-delay="0">
                    <div class="stats-card">
                        <div class="stats-number">15.000+</div>
                        <p class="mt-2 mb-0" style="color: var(--text-light);">
                            <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="display: inline; margin-right: 4px;">
                                <path d="M4 6H20V18H4V6Z"/>
                            </svg>
                            Total Koleksi
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="stats-card">
                        <div class="stats-number">3.200+</div>
                        <p class="mt-2 mb-0" style="color: var(--text-light);">
                            <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="display: inline; margin-right: 4px;">
                                <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            Anggota Aktif
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="stats-card">
                        <div class="stats-number">24/7</div>
                        <p class="mt-2 mb-0" style="color: var(--text-light);">
                            <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="display: inline; margin-right: 4px;">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            Layanan Digital
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container text-center">
        <div class="mb-3">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.5">
                <path d="M4 6H20V18H4V6Z"/>
                <path d="M8 4V8M16 4V8"/>
                <path d="M8 12H16M8 16H13"/>
                <circle cx="12" cy="12" r="2"/>
            </svg>
        </div>
        <p class="mb-0 small" style="color: #64748b;">
            &copy; 2024-2026 Dyzen Library. All rights reserved.
        </p>
        <p class="mt-2 small" style="color: #475569;">
            Powered by KNXL
        </p>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
    // Initialize AOS
    AOS.init({
        duration: 800,
        once: true,
        offset: 100
    });

    // Splash Screen
    setTimeout(function() {
        var splash = document.getElementById('splashScreen');
        if (splash) {
            splash.style.opacity = '0';
            setTimeout(function() {
                splash.style.display = 'none';
            }, 500);
        }
    }, 1500);

    // Counter Animation
    var counters = document.querySelectorAll('.counter');

    function animateCounter(counter) {
        var target = parseInt(counter.getAttribute('data-target'));
        var current = 0;
        var increment = target / 60;
        
        function updateCounter() {
            current += increment;
            if (current >= target) {
                counter.textContent = target.toLocaleString('id-ID');
                return;
            }
            counter.textContent = Math.floor(current).toLocaleString('id-ID');
            requestAnimationFrame(updateCounter);
        }
        updateCounter();
    }

    // Intersection Observer for counters
    var observerOptions = { threshold: 0.3, rootMargin: '0px' };
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    counters.forEach(function(counter) {
        observer.observe(counter);
    });

    // Navbar Scroll Effect
    window.addEventListener('scroll', function() {
        var navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Mobile Menu
    var mobileToggle = document.getElementById('mobileMenuToggle');
    var mobileMenu = document.getElementById('mobileMenu');
    var mobileOverlay = document.getElementById('mobileOverlay');
    var closeMobileMenu = document.getElementById('closeMobileMenu');

    function openMobileMenu() {
        mobileMenu.classList.add('active');
        mobileOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileMenuFunc() {
        mobileMenu.classList.remove('active');
        mobileOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (mobileToggle) {
        mobileToggle.addEventListener('click', openMobileMenu);
    }

    if (closeMobileMenu) {
        closeMobileMenu.addEventListener('click', closeMobileMenuFunc);
    }

    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', closeMobileMenuFunc);
    }

    var mobileNavLinks = document.querySelectorAll('.mobile-nav-link');
    mobileNavLinks.forEach(function(link) {
        link.addEventListener('click', closeMobileMenuFunc);
    });

    // Smooth Scroll
    var allLinks = document.querySelectorAll('a[href^="#"]');
    allLinks.forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            var target = document.querySelector(targetId);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                closeMobileMenuFunc();
            }
        });
    });

    // Scroll indicator click
    var scrollIndicator = document.querySelector('.scroll-indicator');
    if (scrollIndicator) {
        scrollIndicator.addEventListener('click', function() {
            var koleksiSection = document.querySelector('#koleksi');
            if (koleksiSection) {
                koleksiSection.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }
</script>
</body>
</html>