@extends('layouts.app')
@section('title', 'Edit Realisasi')
@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-pencil me-2"></i>Edit Realisasi</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('realisasi.update', $realisasi->kd_realisasi) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- Informasi Akun -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Kode Akun</label>
                                <input type="text" class="form-control" value="{{ $realisasi->kd_akun }} - {{ $realisasi->nama_akun }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Entitas</label>
                                <input type="text" class="form-control" value="{{ $realisasi->kd_entitas }} - {{ $realisasi->nama_entitas }}" readonly>
                            </div>
                        </div>

                        <!-- Informasi Target dan Tanggal -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Target</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control" value="{{ number_format($realisasi->nilai_target, 0, ',', '.') }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tanggal</label>
                                <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($realisasi->tanggal)->format('d-m-Y') }}" readonly>
                            </div>
                        </div>

                        <!-- Form Input Realisasi -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nilai" class="form-label fw-bold">Nilai Realisasi <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('nilai') is-invalid @enderror" 
                                           id="nilai" name="nilai" 
                                           value="{{ old('nilai', $realisasi->nilai) }}" 
                                           min="0" step="1" required>
                                </div>
                                @error('nilai')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <!-- Keterangan -->
                        <div class="mb-3">
                            <label for="keterangan" class="form-label fw-bold">Keterangan</label>
                            <textarea class="form-control @error('keterangan') is-invalid @enderror" 
                                      id="keterangan" name="keterangan" 
                                      rows="4" placeholder="Masukkan keterangan realisasi...">{{ old('keterangan', $realisasi->keterangan) }}</textarea>
                            @error('keterangan')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Informasi Sistem -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="bi bi-calendar me-1"></i>
                                    Dibuat: {{ \Carbon\Carbon::parse($realisasi->created_at)->format('d-m-Y H:i') }}
                                </small>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="bi bi-arrow-clockwise me-1"></i>
                                    Diupdate: {{ \Carbon\Carbon::parse($realisasi->updated_at)->format('d-m-Y H:i') }}
                                </small>
                            </div>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('realisasi.view') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                            <div>
                                <button type="reset" class="btn btn-outline-danger me-2">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-1"></i> Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        border-radius: 0.5rem;
        border: 1px solid #dee2e6;
    }
    .card-header {
        border-radius: 0.5rem 0.5rem 0 0 !important;
    }
    .form-label {
        margin-bottom: 0.5rem;
    }
    .input-group-text {
        background-color: #f8f9fa;
        border: 1px solid #ced4da;
    }
</style>
@endsection