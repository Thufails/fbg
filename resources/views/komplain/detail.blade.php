@extends('layouts.app')
@section('title', 'Detail Komplain')
@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle-fill me-2"></i>Detail Komplain
                </h5>
                <span class="badge bg-light text-dark">
                    #{{ $komplain->kd_komplain }}
                </span>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Informasi Utama -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong><i class="bi bi-calendar-date me-2"></i>Tanggal:</strong>
                        <p class="mt-1">{{ \Carbon\Carbon::parse($komplain->tanggal)->translatedFormat('d F Y') }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-tag me-2"></i>Kategori:</strong>
                        <p class="mt-1">{{ $komplain->kategori }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-person me-2"></i>Pelapor:</strong>
                        <p class="mt-1">{{ $komplain->user->name ?? 'Tidak diketahui' }}</p>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong><i class="bi bi-activity me-2"></i>Status:</strong>
                        <p class="mt-1">
                            @if($komplain->status == 'Plan')
                                <span class="badge bg-danger">
                                    <i class="bi bi-exclamation-circle"></i> Plan
                                </span>
                            @elseif($komplain->status == 'Proses')
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-arrow-repeat"></i> Proses
                                </span>
                            @else
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> Selesai
                                </span>
                            @endif
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-lightning-charge me-2"></i>Prioritas:</strong>
                        <p class="mt-1">
                            @if($komplain->prioritas == 'tinggi')
                                <span class="text-danger">
                                    <i class="bi bi-exclamation-triangle-fill"></i> Tinggi
                                </span>
                            @elseif($komplain->prioritas == 'sedang')
                                <span class="text-warning">
                                    <i class="bi bi-dash-circle-fill"></i> Sedang
                                </span>
                            @else
                                <span class="text-success">
                                    <i class="bi bi-check-circle-fill"></i> Rendah
                                </span>
                            @endif
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-building me-2"></i>Sumber:</strong>
                        <p class="mt-1">{{ $komplain->sumber }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Deskripsi Komplain -->
            <div class="mb-4">
                <strong><i class="bi bi-chat-left-text me-2"></i>Deskripsi:</strong>
                <div class="card mt-2">
                    <div class="card-body bg-light">
                        {{ $komplain->uraian }}
                    </div>
                </div>
            </div>
            
            <!-- Timeline/Histori -->
            <div class="mb-4">
                <strong><i class="bi bi-clock-history me-2"></i>Timeline:</strong>
                <div class="timeline mt-3 ps-3">
                    <div class="timeline-item">
                        <div class="timeline-badge bg-primary"></div>
                        <div class="timeline-content">
                            <small class="text-muted">Dibuat pada: {{ $komplain->created_at->translatedFormat('d M Y H:i') }}</small>
                        </div>
                    </div>
                    @if($komplain->updated_at != $komplain->created_at)
                    <div class="timeline-item">
                        <div class="timeline-badge bg-info"></div>
                        <div class="timeline-content">
                            <small class="text-muted">Terakhir diupdate: {{ $komplain->updated_at->translatedFormat('d M Y H:i') }}</small>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="card-footer bg-light">
            <div class="d-flex justify-content-between">
                <a href="{{ route('komplain.view') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <div>
                    <a href="{{ route('komplain.edit', $komplain->kd_komplain) }}" class="btn btn-warning me-2">
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
                Apakah Anda yakin ingin menghapus komplain ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form action="{{ route('komplain.destroy', $komplain->kd_komplain) }}" method="POST">
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
</style>

<script>
    function confirmDelete() {
        var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }
</script>
@endsection