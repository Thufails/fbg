@extends('layouts.app')
@section('title', 'Detail Analisis')
@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i>Detail Analisis
                </h5>
                <span class="badge bg-light text-dark">
                    #{{ $analisis->kd_analisis }}
                </span>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Informasi Utama -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong><i class="bi bi-calendar-date me-2"></i>Tanggal Dibuat:</strong>
                        <p class="mt-1">{{ \Carbon\Carbon::parse($analisis->created_at)->translatedFormat('d F Y') }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-calendar-range me-2"></i>Periode:</strong>
                        <p class="mt-1">{{ $analisis->bulan }} {{ $analisis->tahun }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-person me-2"></i>Dibuat Oleh:</strong>
                        <p class="mt-1">{{ $analisis->name ?? 'Tidak diketahui' }}</p>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong><i class="bi bi-journal-text me-2"></i>Laporan Terkait:</strong>
                        <p class="mt-1">
                            @if(isset($analisis->kd_laporan))
                                {{ $analisis->kd_laporan }} - {{ $analisis->laporan_keterangan ?? 'Tidak ada keterangan' }}
                            @else
                                Tidak terkait laporan
                            @endif
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-clock me-2"></i>Durasi Analisis:</strong>
                        <p class="mt-1">
                            {{ \Carbon\Carbon::parse($analisis->created_at)->diffForHumans() }}
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Uraian Analisis -->
            <div class="mb-4">
                <strong><i class="bi bi-chat-left-text me-2"></i>Uraian Analisis:</strong>
                <div class="card mt-2">
                    <div class="card-body bg-light">
                        {!! nl2br(e($analisis->uraian)) !!}
                    </div>
                </div>
            </div>
            
            <!-- Timeline -->
            <div class="mb-4">
                <strong><i class="bi bi-clock-history me-2"></i>Timeline:</strong>
                <div class="timeline mt-3 ps-3">
                    <div class="timeline-item">
                        <div class="timeline-badge bg-primary"></div>
                        <div class="timeline-content">
                            <small class="text-muted">Dibuat pada: {{ \Carbon\Carbon::parse($analisis->created_at)->translatedFormat('d M Y H:i') }}</small>
                        </div>
                    </div>
                    @if($analisis->updated_at != $analisis->created_at)
                    <div class="timeline-item">
                        <div class="timeline-badge bg-info"></div>
                        <div class="timeline-content">
                            <small class="text-muted">Terakhir diupdate: {{ \Carbon\Carbon::parse($analisis->updated_at)->translatedFormat('d M Y H:i') }}</small>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="card-footer bg-light">
            <div class="d-flex justify-content-between">
                <a href="{{ route('analisis.view') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <div>
                    <a href="{{ route('analisis.edit', $analisis->kd_analisis) }}" class="btn btn-warning me-2">
                        <i class="bi bi-pencil-square me-1"></i> Edit
                    </a>
                    <button class="btn btn-danger" onclick="confirmDelete()">
                        <i class="bi bi-trash me-1"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus analisis ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form action="{{ route('analisis.destroy', $analisis->kd_analisis) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .timeline {
        position: relative;
        border-left: 2px solid #dee2e6;
    }
    .timeline-item {
        position: relative;
        padding-bottom: 1rem;
    }
    .timeline-badge {
        position: absolute;
        left: -8px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        top: 4px;
    }
    .timeline-content {
        margin-left: 15px;
    }
    .card-header {
        border-bottom: none;
    }
    .bg-light {
        background-color: #f8f9fa !important;
    }
</style>

<script>
    function confirmDelete() {
        var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }
</script>
@endsection