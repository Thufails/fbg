<!DOCTYPE html>
<html lang="id">
<head>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SBS Monitoring System')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-bg: #f8f9fa;
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 100px;
            --transition-speed: 0.3s;
            --top-nav-height: 70px;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        /* Top Navigation Bar */
        #top-nav {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--top-nav-height);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all var(--transition-speed) ease;
        }
        
        #top-nav.expanded {
            left: var(--sidebar-collapsed-width);
        }
        
        .logo-title-section {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
            min-width: 0; /* Untuk mencegah overflow */
        }
        
        .system-title {
            color: white;
            font-weight: 400;
            font-size: 1.3rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-right: 10px;
        }
        
        .user-section {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-shrink: 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-dropdown {
            position: relative;
        }

        .user-menu-trigger {
            cursor: pointer;
            list-style: none;
            padding: 5px 8px;
            border-radius: 8px;
        }

        .user-menu-trigger::-webkit-details-marker {
            display: none;
        }

        .user-menu-trigger:hover,
        .user-dropdown[open] .user-menu-trigger {
            background: rgba(255, 255, 255, 0.12);
        }

        .dropdown-chevron {
            color: rgba(255, 255, 255, 0.9);
            transition: transform 0.2s ease;
        }

        .user-dropdown[open] .dropdown-chevron {
            transform: rotate(180deg);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-avatar i {
            font-size: 1.2rem;
            color: white;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .user-role {
            font-size: 0.75rem;
            opacity: 0.9;
        }
        
        .user-actions {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            z-index: 1100;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 2px;
            min-width: 180px;
            padding: 6px;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.1);
            border-radius: 10px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.2);
        }

        .user-dropdown[open] > .user-actions {
            display: flex !important;
        }
        
        .user-actions a {
            color: #334155 !important;
            text-decoration: none;
            transition: color 0.2s ease;
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 9px 10px;
            border-radius: 7px;
            font-size: 0.9rem;
            opacity: 1;
            visibility: visible;
            white-space: nowrap;
        }

        .user-actions a span,
        .user-actions a i {
            color: #334155 !important;
            opacity: 1;
            visibility: visible;
        }
        
        .user-actions a:hover {
            color: #3730a3;
            background: #eef2ff;
        }
        
        .login-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
        }
        
        .login-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        /* Hamburger Menu Button */
        #mobileMenuToggle {
            display: none;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 6px;
            width: 40px;
            height: 40px;
            color: white;
            font-size: 1.2rem;
            margin-right: 10px;
            flex-shrink: 0;
        }
        
        /* Sidebar Styles */
        #sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1001;
            transition: all var(--transition-speed) ease;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }
        
        #sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .sidebar-header {
            padding: 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: var(--top-nav-height);
            min-height: var(--top-nav-height);
            width: 100%;
        }
        
        /* Logo di Sidebar - Kotak Putih dengan Height Sama Top Nav */
        .sidebar-logo-container {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all var(--transition-speed) ease;
            position: relative;
            background: white;
            margin: 0;
            padding: 0;
        }
        
        .sidebar-logo-full {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: all var(--transition-speed) ease;
            padding: 15px;
            box-sizing: border-box;
        }
        
        .sidebar-logo-small {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: all var(--transition-speed) ease;
            display: none;
            padding: 10px;
            box-sizing: border-box;
        }
        
        .sidebar-logo-placeholder {
            width: 100%;
            height: 100%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-weight: bold;
            font-size: 1rem;
            text-align: center;
            padding: 15px;
            box-sizing: border-box;
        }
        
        .sidebar-logo-placeholder-small {
            width: 100%;
            height: 100%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-weight: bold;
            font-size: 0.8rem;
            text-align: center;
            padding: 10px;
            box-sizing: border-box;
            display: none;
        }
        
        /* Ketika sidebar collapsed */
        #sidebar.collapsed .sidebar-logo-full {
            display: none;
        }
        
        #sidebar.collapsed .sidebar-logo-small {
            display: block;
        }
        
        #sidebar.collapsed .sidebar-logo-placeholder {
            display: none;
        }
        
        #sidebar.collapsed .sidebar-logo-placeholder-small {
            display: flex;
        }
        
        /* Tombol Toggle - Dipindahkan ke bawah logo */
        .toggle-section {
            position: absolute;
            bottom: -45px; /* Posisi di bawah logo */
            left: 50%;
            transform: translateX(-50%);
            z-index: 1002;
            display: flex;
            justify-content: center;
            width: 100%;
        }
        
        .toggle-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 6px;
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .toggle-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        
        .sidebar-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            /* PERBAIKAN: Menambahkan margin top yang lebih besar untuk memberi ruang tombol toggle */
            margin-top: 60px; /* Diperbesar dari 20px menjadi 60px */
        }
        
        .sidebar-menu {
            list-style: none;
            margin: 0;
            padding: 0 15px;
            flex: 1;
            overflow-y: auto;
            min-height: 0;
        }
        
        .sidebar-menu::-webkit-scrollbar {
            width: 4px;
        }
        
        .sidebar-menu::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
        }
        
        .sidebar-menu li {
            position: relative;
        }
        
        .sidebar-menu a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
            white-space: nowrap;
            overflow: hidden;
            border-left: 3px solid transparent;
            font-size: 0.85rem;
            min-height: 40px;
            border-radius: 6px;
            margin: 2px 0;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-left-color: var(--accent-color);
        }
        
        .sidebar-menu i {
            margin-right: 12px;
            font-size: 1rem;
            min-width: 20px;
            text-align: center;
            flex-shrink: 0;
        }
        
        #sidebar.collapsed .sidebar-menu span {
            opacity: 0;
            width: 0;
            height: 0;
            overflow: hidden;
        }
        
        #sidebar.collapsed .sidebar-menu i {
            margin-right: 0;
        }
        
        #sidebar.collapsed .sidebar-menu a {
            justify-content: center;
            padding: 10px 5px;
        }
        
        .menu-section {
            padding: 8px 15px;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin: 10px 0 5px 0;
            white-space: nowrap;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }
        
        #sidebar.collapsed .menu-section {
            opacity: 0;
            height: 0;
            padding: 0;
            margin: 0;
            border: none;
        }
        
        .sidebar-footer {
            padding: 15px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Tooltip untuk menu yang diminimalkan */
        .menu-tooltip {
            position: relative;
        }
        
        #sidebar.collapsed .menu-tooltip::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            margin-left: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            pointer-events: none;
        }
        
        #sidebar.collapsed .menu-tooltip:hover::after {
            opacity: 1;
            visibility: visible;
            transform: translateY(-50%) translateX(5px);
        }
        
        #sidebar.collapsed .menu-tooltip::before {
            content: '';
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            border: 6px solid transparent;
            border-right-color: rgba(0, 0, 0, 0.9);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1001;
            margin-left: 3px;
            pointer-events: none;
        }
        
        #sidebar.collapsed .menu-tooltip:hover::before {
            opacity: 1;
            visibility: visible;
            transform: translateY(-50%) translateX(0);
        }
        
        /* Main Content Styles */
        #content {
            margin-left: var(--sidebar-width);
            margin-top: var(--top-nav-height);
            padding: 20px;
            transition: all var(--transition-speed) ease;
            min-height: calc(100vh - var(--top-nav-height));
        }
        
        #content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        /* Content Styling */
        .content-header {
            margin-bottom: 2rem;
        }

        .table-responsive {
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn {
            border-radius: 6px;
        }
        
        /* Mobile Responsive */
        @media (max-width: 991px) {
            #sidebar {
                width: var(--sidebar-width);
                transform: translateX(-100%);
                z-index: 1050;
                transition: transform var(--transition-speed) ease;
            }
            
            #sidebar.mobile-open {
                transform: translateX(0);
            }
            
            #top-nav {
                left: 0;
                z-index: 1060;
                padding: 0 15px;
            }
            
            #top-nav.expanded {
                left: 0; /* Tetap 0 di mobile */
            }
            
            #content {
                margin-left: 0;
                margin-top: var(--top-nav-height);
            }
            
            #content.expanded {
                margin-left: 0; /* Tetap 0 di mobile */
            }
            
            .sidebar-header {
                height: var(--top-nav-height);
                min-height: var(--top-nav-height);
            }
            
            .sidebar-logo-full {
                padding: 12px;
            }
            
            .sidebar-logo-small {
                padding: 8px;
            }
            
            .toggle-section {
                bottom: -40px;
            }
            
            /* PERBAIKAN: Menyesuaikan margin top untuk mobile */
            .sidebar-content {
                margin-top: 50px; /* Diperbesar dari 20px menjadi 50px untuk mobile */
            }
            
            /* Perbaikan layout top navigation untuk mobile */
            .logo-title-section {
                flex: 1;
                justify-content: flex-start;
                min-width: 0;
            }
            
            .system-title {
                font-size: 1.1rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                margin-right: 0;
            }
            
            .user-details {
                display: none;
            }
            
            .user-actions a span {
                display: none;
            }

            .user-dropdown .user-actions a span {
                display: inline;
            }
            
            .login-btn span {
                display: none;
            }
            
            /* Hamburger Menu */
            #mobileMenuToggle {
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 15px;
            }
            
            /* Nonaktifkan tooltip di mobile */
            .menu-tooltip::after,
            .menu-tooltip::before {
                display: none !important;
            }
            
            /* Overlay untuk menutup sidebar */
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1049;
                display: none;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
        }
        
        /* Responsivitas tambahan untuk layar yang lebih kecil */
        @media (max-width: 576px) {
            #top-nav {
                padding: 0 10px;
            }
            
            .system-title {
                font-size: 1rem;
            }
            
            .user-avatar {
                width: 35px;
                height: 35px;
            }
            
            .user-avatar i {
                font-size: 1rem;
            }
            
            .user-actions a, .login-btn {
                font-size: 0.8rem;
                padding: 6px 10px;
            }
            
            #mobileMenuToggle {
                width: 35px;
                height: 35px;
                font-size: 1rem;
                margin-right: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <!-- Sidebar -->
    <div id="sidebar">
        <div class="sidebar-header">
            <!-- Logo di Sidebar - Kotak Putih dengan Height Sama Top Nav -->
            <div class="sidebar-logo-container">
                <!-- Logo Besar (Normal) -->
                <img src="/images/logo-sbs.png" alt="SBS Logo" class="sidebar-logo-full" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <!-- Logo Kecil (Minimized) -->
                <img src="/images/logo-kecil.png" alt="SBS Logo Kecil" class="sidebar-logo-small" onerror="this.style.display='none'; this.nextElementSibling.nextElementSibling.style.display='flex';">
                
                <!-- Placeholder jika gambar tidak ditemukan -->
                <div class="sidebar-logo-placeholder" style="display: none;">
                    SBS LOGO
                </div>
                <div class="sidebar-logo-placeholder-small" style="display: none;">
                    SBS
                </div>
            </div>
            
            <!-- Tombol Toggle - Dipindahkan ke bawah logo -->
            <div class="toggle-section">
                <button class="toggle-btn menu-tooltip" id="sidebarToggle" data-tooltip="Toggle Menu">
                    <i class="bi bi-chevron-double-left"></i>
                </button>
            </div>
        </div>
        
        <div class="sidebar-content">
            <ul class="sidebar-menu">
                <!-- Menu Dashboard -->
                @if(Auth::check())
                <div class="menu-section">Utama</div>
                <li>
                    <a href="{{ route('dashboard.view') }}" class="{{ request()->routeIs('dashboard.*') ? 'active' : '' }} menu-tooltip" data-tooltip="Dashboard">
                        <i class="bi bi-house"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                @endif
                
                <!-- User Management Menu -->
                @if(Auth::check() && Auth::user()->level === 'admin')
                <div class="menu-section">Manajemen User</div>
                <li>
                    <a href="{{ route('user.view') }}" class="{{ request()->routeIs('user.view') ? 'active' : '' }} menu-tooltip" data-tooltip="Manajemen User">
                        <i class="bi bi-person-fill"></i>
                        <span>Manajemen User</span>
                    </a>
                </li>
                @endif

                <!-- Master Database Menu -->
                @if(Auth::check() && Auth::user()->level === 'admin')
                <div class="menu-section">Master Database</div>
                <!-- Menu Konversi Tanggal - DITAMBAHKAN DI SINI -->
                <li>
                    <a href="{{ route('konversiTanggal.view') }}" class="{{ request()->routeIs('konversi_tanggal.*') ? 'active' : '' }} menu-tooltip" data-tooltip="Konversi Tanggal">
                        <i class="bi bi-calendar-event"></i>
                        <span>Konversi Tanggal</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('entitas.view') }}" class="{{ request()->routeIs('entitas.*') ? 'active' : '' }} menu-tooltip" data-tooltip="Entitas">
                        <i class="bi bi-building"></i>
                        <span>Entitas</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('akun.view') }}" class="{{ request()->routeIs('akun.*') ? 'active' : '' }} menu-tooltip" data-tooltip="Akun">
                        <i class="bi bi-person-badge"></i>
                        <span>Akun</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('produk.view') }}" class="{{ request()->routeIs('produk.*') ? 'active' : '' }} menu-tooltip" data-tooltip="Produk">
                        <i class="bi bi-box-seam"></i>
                        <span>Produk</span>
                    </a>
                </li>
                @endif      
                
                <!-- Transaksi Menu -->
                @if(Auth::check() && (Auth::user()->level === 'admin' || Auth::user()->level === 'user'))
                <div class="menu-section">Transaksi</div>         
                <li>
                    <a href="{{ route('target.view') }}" class="{{ request()->routeIs('target.*') ? 'active' : '' }} menu-tooltip" data-tooltip="Target">
                        <i class="bi bi-bullseye"></i>
                        <span>Target</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('detailTarget.view') }}" class="{{ request()->routeIs('detailTarget.*') ? 'active' : '' }} menu-tooltip" data-tooltip="Target Detail">
                        <i class="bi bi-search"></i>
                        <span>Target Detail</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('penjualan.view') }}" class="{{ request()->routeIs('penjualan.*') ? 'active' : '' }} menu-tooltip" data-tooltip="Penjualan">
                        <i class="bi bi-cash-stack"></i>
                        <span>Penjualan</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('realisasi.view') }}" class="{{ request()->routeIs('realisasi.*') ? 'active' : '' }} menu-tooltip" data-tooltip="Realisasi">
                        <i class="bi bi-check-circle"></i>
                        <span>Realisasi</span>
                    </a>
                </li>                
                <li>
                    <a href="{{ route('detailRealisasi.view') }}" class="{{ request()->routeIs('detailRealisasi.*') ? 'active' : '' }} menu-tooltip" data-tooltip="Realisasi Detail">
                        <i class="bi bi-search"></i>
                        <span>Realisasi Detail</span>
                    </a>
                </li>
                @endif                
                
                <!-- Laporan Menu -->
                @if(Auth::check())
                <div class="menu-section">Laporan</div>                
                <li>
                    <a href="{{ route('laporan.view') }}" class="{{ request()->routeIs('laporan.*') ? 'active' : '' }} menu-tooltip" data-tooltip="Laporan Bulanan">
                        <i class="bi bi-calendar-month"></i>
                        <span>Laporan Bulanan</span>
                    </a>
                </li>
                @endif
                @if(Auth::check() && (Auth::user()->level === 'admin' || Auth::user()->level === 'user' || Auth::user()->level === 'analis'))
                <li>
                    <a href="{{ route('tugas.view') }}" class="{{ request()->routeIs('tugas.*') ? 'active' : '' }} menu-tooltip" data-tooltip="Laporan Tugas">
                        <i class="bi bi-file-earmark-check"></i>
                        <span>Laporan Tugas</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('issue.view') }}" class="{{ request()->routeIs('issue.*') ? 'active' : '' }} menu-tooltip" data-tooltip="Laporan Issue">
                        <i class="bi bi-file-earmark-excel"></i>
                        <span>Laporan Issue</span>
                    </a>
                </li>
                @endif                
                @if(Auth::check() && (Auth::user()->level === 'admin' || Auth::user()->level === 'user' || Auth::user()->level === 'analis'))
                <li>
                    <a href="{{ route('komplain.view') }}" class="{{ request()->routeIs('komplain.*') ? 'active' : '' }} menu-tooltip" data-tooltip="Laporan Komplain">
                        <i class="bi bi-file-earmark-lock"></i>
                        <span>Laporan Komplain</span>
                    </a>
                </li>
                @endif 
                @if(Auth::check() && (Auth::user()->level === 'admin' || Auth::user()->level === 'analis'))
                <li>
                    <a href="{{ route('analisis.view') }}" class="{{ request()->routeIs('analisis.*') ? 'active' : '' }} menu-tooltip" data-tooltip="Laporan Analisis">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        <span>Laporan Analisis</span>
                    </a>
                </li>
                @endif
            </ul>
        </div>
        
        <div class="sidebar-footer">
            <small class="text-white-50">SBS Monitoring System v1.0</small>
        </div>
    </div>

    <!-- Top Navigation Bar -->
    <div id="top-nav">
        <!-- Logo dan Nama Sistem di Kiri -->
        <div class="logo-title-section">
            <!-- Tombol Hamburger untuk Mobile -->
            <button class="toggle-btn d-lg-none" id="mobileMenuToggle">
                <i class="bi bi-list"></i>
            </button>
            
            <!-- Nama Sistem -->
            <span class="system-title">SBS Monitoring System</span>
        </div>
        
        <!-- User Profile di Kanan -->
        <div class="user-section">
            @if(Auth::check())
                <details class="user-dropdown">
                <summary class="user-info user-menu-trigger" aria-label="Buka menu akun">
                    <div class="user-avatar">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <div class="user-details">
                        <div class="user-name">{{ Auth::user()->name }}</div>
                        <div class="user-role">{{ Auth::user()->level }}</div>
                    </div>
                    <i class="bi bi-chevron-down dropdown-chevron" aria-hidden="true"></i>
                </summary>
                <div class="user-actions">
                    <a href="{{ route('user.updateData') }}">
                        <i class="bi bi-pencil-square"></i>
                        <span>Profil</span>
                    </a>
                    <a href="{{ route('logout') }}">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </a>
                </div>
                </details>
            @else
                <a href="{{ route('login') }}" class="login-btn">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Login</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Overlay untuk mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <div id="content">
        <!-- Konten Utama -->
        <div class="container-fluid">
            @yield('content')
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            const topNav = document.getElementById('top-nav');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            // Toggle sidebar pada desktop
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    // Di mobile, jangan toggle collapsed state
                    if (window.innerWidth > 991) {
                        sidebar.classList.toggle('collapsed');
                        content.classList.toggle('expanded');
                        topNav.classList.toggle('expanded');
                        
                        // Rotate icon
                        const icon = this.querySelector('i');
                        if (sidebar.classList.contains('collapsed')) {
                            icon.classList.remove('bi-chevron-double-left');
                            icon.classList.add('bi-chevron-double-right');
                        } else {
                            icon.classList.remove('bi-chevron-double-right');
                            icon.classList.add('bi-chevron-double-left');
                        }
                    }
                });
            }
            
            // Toggle sidebar pada mobile
            if (mobileMenuToggle && sidebarOverlay) {
                mobileMenuToggle.addEventListener('click', function() {
                    sidebar.classList.add('mobile-open');
                    sidebarOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden'; // Prevent scrolling
                });
                
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-open');
                    this.classList.remove('active');
                    document.body.style.overflow = ''; // Enable scrolling
                });
            }
            
            // Close sidebar when clicking on menu items in mobile
            const menuLinks = document.querySelectorAll('.sidebar-menu a');
            const sidebarMenu = document.querySelector('.sidebar-menu');
            const sidebarScrollKey = 'fbg-sidebar-scroll-top';

            // Restore the menu position after a normal page navigation.
            if (sidebarMenu) {
                try {
                    const savedScrollTop = Number(sessionStorage.getItem(sidebarScrollKey));
                    sessionStorage.removeItem(sidebarScrollKey);
                    if (Number.isFinite(savedScrollTop) && savedScrollTop >= 0) {
                        requestAnimationFrame(function() {
                            sidebarMenu.scrollTop = savedScrollTop;
                        });
                    }
                } catch (error) {
                    // Keep navigation working if browser storage is unavailable.
                }
            }

            menuLinks.forEach(link => {
                link.addEventListener('click', function(event) {
                    // Save only same-tab, same-site navigations (not Ctrl/Cmd-clicks or new tabs).
                    if (event.button === 0 && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey && (!link.target || link.target === '_self')) {
                        try {
                            const destination = new URL(link.href, window.location.href);
                            if (destination.origin === window.location.origin) {
                                sessionStorage.setItem(sidebarScrollKey, String(sidebarMenu ? sidebarMenu.scrollTop : 0));
                            }
                        } catch (error) {
                            // Keep navigation working if URL parsing or browser storage fails.
                        }
                    }

                    if (window.innerWidth <= 991) {
                        sidebar.classList.remove('mobile-open');
                        sidebarOverlay.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 991) {
                    // Reset mobile states when switching to desktop
                    sidebar.classList.remove('mobile-open');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.remove('active');
                    }
                    document.body.style.overflow = '';
                }
            });
            
            // Set active menu based on current route
            function setActiveMenu() {
                const currentPath = window.location.pathname;
                const menuLinks = document.querySelectorAll('.sidebar-menu a');
                
                menuLinks.forEach(link => {
                    if (link.getAttribute('href') === currentPath) {
                        link.classList.add('active');
                    }
                });
            }
            
            setActiveMenu();
        });
    </script>
</body>
</html>
