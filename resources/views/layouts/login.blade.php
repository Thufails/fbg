<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SBS Monitoring System</title>
    
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
        }
        
        body {
            background-color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        /* Top Navigation Bar - logo menempel di sudut kiri */
        #top-nav-login {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .logo-title-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        /* Container untuk logo dengan latar belakang putih penuh */
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 70px; /* Tinggi sama dengan header */
            background-color: white;
            position: relative;
            margin: 0;
            padding: 0;
        }
        
        /* Wrapper untuk gambar logo */
        .logo-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 10px 20px; /* Padding hanya di kanan, kiri tanpa padding */
            margin: 0;
        }
        
        /* Gambar logo - lebar sesuai gambar asli */
        .logo-img {
            height: 100%;
            width: auto;
            max-height: 50px; /* Maksimal tinggi 50px agar ada padding atas-bawah */
            object-fit: contain;
            display: block;
        }
        
        /* Placeholder untuk logo jika gambar tidak ada */
        .logo-placeholder {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            color: var(--primary-color);
            font-weight: bold;
            font-size: 0.8rem;
            text-align: center;
            line-height: 1.1;
            padding: 5px 20px;
            white-space: nowrap;
            border: 1px solid #e9ecef;
            margin: 0;
        }
        
        .system-title {
            color: white;
            font-weight: 600;
            font-size: 1.3rem;
            white-space: nowrap;
            margin-left: 10px; /* Sedikit margin setelah logo */
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-top: 50px;
            border: 1px solid #e9ecef;
        }
        
        .login-left {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-right {
            padding: 40px;
            background: white;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .feature-list i {
            font-size: 1.2rem;
            color: var(--accent-color);
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
        }
        
        .login-logo {
            max-width: 120px;
            margin-bottom: 20px;
        }

        /* Logo besar di dalam form login */
        .main-login-logo {
            max-width: 150px;
            margin-bottom: 25px;
            border-radius: 15px;
            background-color: white;
            padding: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* User section seperti di app.blade.php */
        .user-section {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-right: 20px; /* Padding hanya di kanan */
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

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .system-title {
                font-size: 1.1rem;
            }
            
            .logo-wrapper {
                padding: 10px 15px;
            }
            
            .logo-img {
                max-height: 45px;
            }
            
            .login-left, .login-right {
                padding: 30px 20px;
            }
        }
        
        @media (max-width: 576px) {
            .system-title {
                font-size: 1rem;
            }
            
            .logo-container {
                height: 60px;
            }
            
            .logo-img {
                max-height: 40px;
            }
            
            .logo-placeholder {
                font-size: 0.7rem;
                padding: 5px 15px;
            }
            
            .user-section {
                padding-right: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar seperti app.blade.php -->
    <div id="top-nav-login">
        <!-- Logo dan Nama Sistem di Kiri -->
        <div class="logo-title-section">
            <!-- Logo dengan latar belakang putih penuh -->
            <div class="logo-container">
                <div class="logo-wrapper">
                    <img src="{{ asset('images/logo-sbs.png') }}" alt="SBS Logo" class="logo-img" 
                         onerror="this.style.display='none'; this.parentNode.innerHTML='<div class=\'logo-placeholder\'>SBS<br>Logo</div>';">
                </div>
            </div>
            <!-- Nama Sistem -->
            <span class="system-title">SBS Monitoring System</span>
        </div>
        
        <!-- User Section di Kanan (untuk konsistensi dengan app.blade.php) -->
        <div class="user-section">
            <a href="#" class="login-btn">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Login</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        @yield('content')
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>