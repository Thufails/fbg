@extends('layouts.app')
@section('title', 'Edit Profil Perusahaan')
@section('nav')
@include('layouts.nav')
@endsection

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-building me-2"></i>
                        Edit Profil Perusahaan
                    </h5>
                </div>
                <div class="card-body">
                    
                    <!-- Flash Message -->
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    <form action="{{ route('profilPerusahaan.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <!-- Nama Perusahaan -->
                        <div class="mb-3">
                            <label for="nama_perusahaan" class="form-label fw-semibold">
                                Nama Perusahaan <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('nama_perusahaan') is-invalid @enderror" 
                                   id="nama_perusahaan" 
                                   name="nama_perusahaan" 
                                   value="{{ old('nama_perusahaan', $companyProfile->nama_perusahaan) }}"
                                   placeholder="Masukkan nama perusahaan"
                                   required>
                            @error('nama_perusahaan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Deskripsi -->
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label fw-semibold">Profil Perusahaan</label>
                            <textarea class="form-control @error('deskripsi') is-invalid @enderror" 
                                      id="deskripsi" 
                                      name="deskripsi" 
                                      rows="4" 
                                      placeholder="Masukkan deskripsi perusahaan">{{ old('deskripsi', $companyProfile->deskripsi) }}</textarea>
                            @error('deskripsi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Alamat -->
                        <div class="mb-3">
                            <label for="alamat" class="form-label fw-semibold">Alamat</label>
                            <textarea class="form-control @error('alamat') is-invalid @enderror" 
                                      id="alamat" 
                                      name="alamat" 
                                      rows="3" 
                                      placeholder="Masukkan alamat lengkap perusahaan">{{ old('alamat', $companyProfile->alamat) }}</textarea>
                            @error('alamat')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Telepon dan Email -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="telepon" class="form-label fw-semibold">Telepon</label>
                                    <input type="text" 
                                           class="form-control @error('telepon') is-invalid @enderror" 
                                           id="telepon" 
                                           name="telepon" 
                                           value="{{ old('telepon', $companyProfile->telepon) }}"
                                           placeholder="Contoh: +62 21 1234567">
                                    @error('telepon')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label fw-semibold">Email</label>
                                    <input type="email" 
                                           class="form-control @error('email') is-invalid @enderror" 
                                           id="email" 
                                           name="email" 
                                           value="{{ old('email', $companyProfile->email) }}"
                                           placeholder="Contoh: info@perusahaan.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Website -->
                        <div class="mb-4">
                            <label for="website" class="form-label fw-semibold">Website</label>
                            <input type="url" 
                                   class="form-control @error('website') is-invalid @enderror" 
                                   id="website" 
                                   name="website" 
                                   value="{{ old('website', $companyProfile->website) }}"
                                   placeholder="Contoh: https://www.perusahaan.com">
                            @error('website')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>
                                Kembali ke Home
                            </a>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Logo -->
<div class="modal fade" id="deleteLogoModal" tabindex="-1" aria-labelledby="deleteLogoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteLogoModalLabel">Konfirmasi Hapus Logo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus logo perusahaan?</p>
                <p class="text-muted small">Logo yang dihapus tidak dapat dikembalikan.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                {{-- <form action="{{ route('company-profile.remove-logo') }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>
                        Hapus Logo
                    </button>
                </form> --}}
            </div>
        </div>
    </div>
</div>

<style>
    .form-label {
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    
    .card {
        border: none;
        border-radius: 12px;
    }
    
    .card-header {
        border-radius: 12px 12px 0 0 !important;
        padding: 1rem 1.5rem;
    }
</style>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<script>
    function confirmDeleteLogo() {
        const deleteLogoModal = new bootstrap.Modal(document.getElementById('deleteLogoModal'));
        deleteLogoModal.show();
    }

    // Preview image sebelum upload
    document.getElementById('logo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Cek apakah sudah ada preview
                let preview = document.querySelector('.logo-preview');
                if (!preview) {
                    preview = document.createElement('div');
                    preview.className = 'logo-preview mt-2';
                    document.getElementById('logo').parentNode.appendChild(preview);
                }
                preview.innerHTML = `
                    <p class="text-muted small mb-1">Preview logo baru:</p>
                    <img src="${e.target.result}" class="img-thumbnail" style="max-height: 120px; max-width: 200px;">
                `;
            }
            reader.readAsDataURL(file);
        }
    });

    // Auto-hide alert setelah 5 detik
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