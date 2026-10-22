@extends('layouts.app')
@section('title', 'Detail Akun')
@section('content')
<div class="container">
    @if(!isset($akun))
        <div class="alert alert-danger">
            Data akun tidak ditemukan
        </div>
        <a href="{{ route('akun.view') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Akun
        </a>
    @else
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-person-badge me-2"></i>Detail Akun
                </h5>
                <span class="badge bg-light text-dark">
                    {{ $akun->kd_akun ?? 'N/A' }}
                </span>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Informasi Utama -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong><i class="bi bi-card-text me-2"></i>Kode Akun:</strong>
                        <p class="mt-1">{{ $akun->kd_akun ?? '-' }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-person-lines-fill me-2"></i>Nama Akun:</strong>
                        <p class="mt-1">{{ $akun->nama_akun ?? '-' }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-diagram-3 me-2"></i>Induk:</strong>
                        <p class="mt-1">
                            @if(!empty($akun->induk))
                                {{ $akun->induk }}
                            @else
                                <span class="text-muted">Tidak ada induk</span>
                            @endif
                        </p>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong><i class="bi bi-list-ol me-2"></i>Urutan:</strong>
                        <p class="mt-1">{{ $akun->urutan ?? '0' }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-gear me-2"></i>Operasi:</strong>
                        <p class="mt-1">
                            @if(isset($akun->operasi) && $akun->operasi == '+')
                                <span class="badge bg-success">Penambahan (+)</span>
                            @else
                                <span class="badge bg-danger">Pengurangan (-)</span>
                            @endif
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-person me-2"></i>Dibuat Oleh:</strong>
                        <p class="mt-1">{{ $akun->created_by ?? 'System' }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Keterangan -->
            <div class="mb-4">
                <strong><i class="bi bi-chat-left-text me-2"></i>Keterangan:</strong>
                <div class="card mt-2">
                    <div class="card-body bg-light">
                        {{ $akun->keterangan ?? 'Tidak ada keterangan' }}
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
                            <small class="text-muted">Dibuat pada: {{ isset($akun->created_at) ? \Carbon\Carbon::parse($akun->created_at)->translatedFormat('d M Y H:i') : 'N/A' }}</small>
                        </div>
                    </div>
                    @if(isset($akun->updated_at) && $akun->updated_at != $akun->created_at)
                    <div class="timeline-item">
                        <div class="timeline-badge bg-info"></div>
                        <div class="timeline-content">
                            <small class="text-muted">Terakhir diupdate: {{ \Carbon\Carbon::parse($akun->updated_at)->translatedFormat('d M Y H:i') }}</small>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Tabel Detail Akun -->
            <div class="mt-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>
                        <i class="bi bi-list-check me-2"></i>Detail Akun
                    </h5>
                    <a href="{{ route('detailAkun.insert', ['kd_akun' => $akun->kd_akun]) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Detail
                    </a>
                </div>
                
                @if($detailAkun->isEmpty())
                    <div class="alert alert-info">
                        Tidak ada data detail akun
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-primary">
                                <tr>
                                    <th>No</th>
                                    <th>Kode Detail</th>
                                    <th>Nama Detail</th>
                                    <th>Keterangan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detailAkun as $index => $detail)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $detail->kd_detail_akun }}</td>
                                    <td>{{ $detail->nama_detail_akun }}</td>
                                    <td>{{ $detail->keterangan ?? '-' }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('detailAkun.edit', $detail->kd_detail_akun) }}" class="btn btn-warning">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <button class="btn btn-danger" onclick="confirmDetailDelete('{{ $detail->kd_detail_akun }}')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="card-footer bg-light">
            <div class="d-flex justify-content-between">
                <a href="{{ route('akun.view') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <div>
                    <a href="{{ route('akun.edit', $akun->kd_akun) }}" class="btn btn-warning me-2">
                        <i class="bi bi-pencil-square me-1"></i> Edit
                    </a>
                    <button class="btn btn-danger" onclick="confirmDelete()">
                        <i class="bi bi-trash me-1"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@if(isset($akun))
<!-- Delete Confirmation Modal for Akun -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus akun ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form action="{{ route('akun.destroy', $akun->kd_akun) }}" method="POST">
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

<!-- Delete Confirmation Modal for Detail Akun -->
<div class="modal fade" id="deleteDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus detail akun ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form id="deleteDetailForm" method="POST">
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
@endif

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
    .table-responsive {
        overflow-x: auto;
    }
</style>

@if(isset($akun))
<script>
    function confirmDelete() {
        var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }
    
    function confirmDetailDelete(detailId) {
        // Set form action URL
        document.getElementById('deleteDetailForm').action = '/detailAkun/' + detailId;
        var modal = new bootstrap.Modal(document.getElementById('deleteDetailModal'));
        modal.show();
    }
</script>
@endif
@endsection