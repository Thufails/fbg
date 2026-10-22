@extends('layouts.app')
@section('title', 'Profil Perusahaan')
@section('nav')
@include('layouts.nav')
@endsection
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Header dengan Logo dan Nama Perusahaan -->
            <div class="text-center mb-5">
                @if($companyProfile && $companyProfile->logo)
                    {{-- <img src="{{ asset('storage/' . $companyProfile->logo) }}" 
                         alt="{{ $companyProfile->nama_perusahaan ?? 'Company Logo' }}" 
                         class="img-fluid mb-4" 
                         style="max-height: 120px;"> --}}
                @else
                    <!-- Fallback logo jika tidak ada -->
                    {{-- <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-4" 
                         style="width: 120px; height: 120px;">
                        <i class="bi bi-building display-6"></i>
                    </div> --}}
                @endif

                <h1 class="display-5 fw-bold text-primary mb-2">
                    {{ $companyProfile->nama_perusahaan ?? 'Nama Perusahaan' }}
                </h1>
                <p class="lead text-muted">
                    {{ $companyProfile->deskripsi ?? 'Deskripsi perusahaan belum tersedia.' }}
                </p>
            </div>

            <!-- Info Pengguna -->
            @auth
            <div class="card shadow-sm mb-5">
                <div class="card-body text-center">
                    <h5 class="card-title text-primary">Selamat Datang, {{ Auth::user()->name }}!</h5>
                    <p class="card-text">Anda login sebagai <span class="badge bg-primary">{{ Auth::user()->level }}</span></p>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-clock me-1"></i>
                        Terakhir login: {{ \Carbon\Carbon::now()->format('d M Y H:i') }}
                    </p>
                </div>
            </div>
            @endauth

            <!-- Informasi Perusahaan -->
            @if($companyProfile)
            <div class="card shadow-sm mb-5">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Informasi Perusahaan
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if($companyProfile->alamat)
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-geo-alt text-primary me-3 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Alamat</h6>
                                    <p class="text-muted mb-0">{{ $companyProfile->alamat }}</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($companyProfile->telepon)
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-telephone text-primary me-3 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Telepon</h6>
                                    <p class="text-muted mb-0">{{ $companyProfile->telepon }}</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($companyProfile->email)
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-envelope text-primary me-3 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Email</h6>
                                    <p class="text-muted mb-0">
                                        <a href="mailto:{{ $companyProfile->email }}" class="text-decoration-none">
                                            {{ $companyProfile->email }}
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($companyProfile->website)
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-globe text-primary me-3 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Website</h6>
                                    <p class="text-muted mb-0">
                                        <a href="{{ $companyProfile->website }}" target="_blank" class="text-decoration-none">
                                            Kunjungi Website
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @else
            <!-- Pesan jika data perusahaan belum ada -->
            <div class="alert alert-warning text-center">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Data profile perusahaan belum tersedia. 
                @auth
                    <a href="{{ route('profilPerusahaan.edit') }}" class="alert-link">
                        Klik di sini untuk mengisi data perusahaan.
                    </a>
                @endauth
            </div>
            @endif

            <!-- Fitur Aplikasi -->
            <div class="row mt-4">
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100 feature-card">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-primary text-white rounded-circle mx-auto mb-3">
                                <i class="bi bi-graph-up display-6"></i>
                            </div>
                            <h5 class="card-title fw-bold">Monitoring Real-time</h5>
                            <p class="card-text text-muted">Pemantauan data secara real-time dengan update otomatis</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100 feature-card">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-success text-white rounded-circle mx-auto mb-3">
                                <i class="bi bi-file-earmark-text display-6"></i>
                            </div>
                            <h5 class="card-title fw-bold">Laporan Lengkap</h5>
                            <p class="card-text text-muted">Generasi laporan otomatis dengan berbagai format</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100 feature-card">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-warning text-white rounded-circle mx-auto mb-3">
                                <i class="bi bi-shield-check display-6"></i>
                            </div>
                            <h5 class="card-title fw-bold">Keamanan Data</h5>
                            <p class="card-text text-muted">Proteksi data terenkripsi dengan sistem keamanan berlapis</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tombol Edit untuk Admin -->
            @auth
                @if(Auth::user()->level === 'admin' || Auth::user()->level === 'superadmin')
                <div class="text-center mt-5">
                    <a href="{{ route('profilPerusahaan.edit') }}" 
                       class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-pencil-square me-2"></i>
                        Edit Profile Perusahaan
                    </a>
                </div>
                @endif
            @endauth
        </div>
    </div>
</div>

<!-- Flash Message -->
@if(session('success'))
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1050">
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
@endif

@if(session('error'))
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1050">
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
@endif

<style>
    .feature-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid rgba(0,0,0,0.05);
    }
    
    .feature-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
    }
    
    .feature-icon {
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s ease;
    }
    
    .feature-card:hover .feature-icon {
        transform: scale(1.1);
    }
    
    .card {
        border-radius: 12px;
        overflow: hidden;
    }
    
    .badge {
        font-size: 0.75em;
        padding: 0.35em 0.65em;
    }
</style>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<script>
    // Auto-hide flash messages setelah 5 detik
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
</script>
@endsection