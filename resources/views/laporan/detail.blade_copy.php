@extends('layouts.app')
@section('title', 'Detail Laporan')
@section('content')
<div class="container">
    @include('layouts.notif')
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-file-earmark-text me-2"></i>Detail Laporan
                </h5>
                <span class="badge bg-light text-dark">
                    #{{ $laporan->kd_laporan }}
                </span>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Informasi Utama -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong><i class="bi bi-calendar-date me-2"></i>Tanggal Laporan:</strong>
                        <p class="mt-1">{{ \Carbon\Carbon::parse($laporan->tanggal)->translatedFormat('d F Y') }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-calendar-range me-2"></i>Periode Laporan:</strong>
                        <p class="mt-1">
                            {{ \Carbon\Carbon::parse($laporan->tanggal_awal)->translatedFormat('d F Y') }} - 
                            {{ \Carbon\Carbon::parse($laporan->tanggal_akhir)->translatedFormat('d F Y') }}
                        </p>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong><i class="bi bi-activity me-2"></i>Status:</strong>
                        <p class="mt-1">
                            @if($laporan->status == 0)
                                <span class="badge bg-secondary">
                                    <i class="bi bi-file-earmark-text"></i> Draft
                                </span>
                            @elseif($laporan->status == 1)
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-eye"></i> Dalam Review
                                </span>
                            @else
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> Disetujui
                                </span>
                            @endif
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-person me-2"></i>Dibuat Oleh:</strong>
                        <p class="mt-1">{{ $laporan->user->name ?? 'Tidak diketahui' }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Keterangan Laporan -->
            <div class="mb-4">
                <strong><i class="bi bi-card-text me-2"></i>Keterangan:</strong>
                <div class="card mt-2">
                    <div class="card-body bg-light">
                        {!! nl2br(e($laporan->keterangan)) !!}
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
                            <small class="text-muted">Dibuat pada: {{ $laporan->created_at->translatedFormat('d M Y H:i') }}</small>
                        </div>
                    </div>
                    @if($laporan->updated_at != $laporan->created_at)
                    <div class="timeline-item">
                        <div class="timeline-badge bg-info"></div>
                        <div class="timeline-content">
                            <small class="text-muted">Terakhir diupdate: {{ $laporan->updated_at->translatedFormat('d M Y H:i') }}</small>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Tampilkan data hasil analisis --}}
{{-- Tombol Generate Kesimpulan --}}
@if($hasilAnalisis->count() > 0)
<div class="row mt-4">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h5 class="card-title"><i class="fas fa-brain"></i> Generate Kesimpulan dan Saran</h5>
                <p class="card-text">Buat kesimpulan menyeluruh dari semua analisis yang telah dilakukan</p>
                
                <form action="{{ route('laporan.generate.conclusion') }}" method="POST">
                    @csrf
                    <input type="hidden" name="kd_laporan" value="{{ $laporan->kd_laporan }}">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-sync-alt"></i> Generate Kesimpulan
                    </button>
                </form>
                
                <small class="text-muted mt-2 d-block">
                    Sistem akan menganalisis semua data dan menghasilkan kesimpulan serta saran komprehensif
                </small>
            </div>
        </div>
    </div>
</div>
@endif

            {{-- Tampilkan data hasil analisis --}}
            @if($hasilAnalisis->count() > 0)
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3><i class="fas fa-chart-bar"></i> Hasil Analisis</h3>
                        <span class="badge bg-info">
                            Total: {{ $hasilAnalisis->count() }} Analisis
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($hasilAnalisis as $analisis)
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100 border-{{ $analisis->status_analisis === 'Ada' ? 'success' : 'secondary' }}">
                                        <div class="card-header bg-{{ $analisis->status_analisis === 'Ada' ? 'success' : 'secondary' }} text-white d-flex justify-content-between align-items-center">
                                            <span>{{ Str::limit($analisis->title, 20) }}</span>
                                            @if($analisis->status_analisis === 'Ada')
                                                <span class="badge bg-{{ $analisis->jenis_analisis === 'AI' ? 'info' : 'warning' }} text-dark">
                                                    {{ $analisis->jenis_analisis === 'AI' ? 'AI' : 'Manual' }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Data:</span>
                                                <span class="badge bg-{{ $analisis->status_data === 'Ada' ? 'success' : 'secondary' }}">
                                                    {{ $analisis->status_data }}
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span>Analisis:</span>
                                                <span class="badge bg-{{ $analisis->status_analisis === 'Ada' ? 'success' : 'secondary' }}">
                                                    {{ $analisis->status_analisis }}
                                                </span>
                                            </div>
                                            
                                            {{-- Tampilkan preview analisis jika ada --}}
                                            @if($analisis->status_analisis === 'Ada')
                                                <div class="mb-3">
                                                    <small class="text-muted">Jenis: {{ $analisis->jenis_analisis === 'AI' ? 'Analisis AI' : 'Analisis Manual' }}</small>
                                                </div>
                                                <div class="analysis-preview mb-3">
                                                    <small class="text-muted">Preview:</small>
                                                    <div class="mt-1 p-2 bg-light rounded" style="max-height: 100px; overflow: hidden;">
                                                        {!! Str::limit(strip_tags($analisis->analisis), 100) !!}
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            {{-- Tombol Analyze dengan Form --}}
                                            <form action="{{ route('laporan.analyze') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="kd_laporan" value="{{ $laporan->kd_laporan }}">
                                                <input type="hidden" name="title" value="{{ $analisis->title }}">
                                                <input type="hidden" name="data" value="{{ json_encode($analisis->decoded_data) }}">
                                                @if($analisis->status_data === 'Ada')
                                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                                        <i class="fas fa-robot"></i> 
                                                        {{ $analisis->status_analisis === 'Ada' ? 'Reanalyze' : 'Analyze' }}
                                                    </button>
                                                @endif
                                            </form>
                                            
                                            {{-- Tombol View untuk melihat analisis lengkap --}}
                                            @if($analisis->status_analisis === 'Ada')
                                                <button type="button" class="btn btn-outline-info btn-sm w-100 mt-2" data-bs-toggle="modal" data-bs-target="#analysisModal{{ $loop->index }}">
                                                    <i class="fas fa-eye"></i> View Analysis
                                                </button>
                                            @endif
                                        </div>
                                        <div class="card-footer bg-light">
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i> 
                                                Diupdate: {{ \Carbon\Carbon::parse($analisis->updated_at)->format('d M Y H:i') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Modal untuk melihat analisis lengkap --}}
                                {{-- Modal untuk melihat dan mengedit analisis lengkap --}}
                                @if($analisis->status_analisis === 'Ada')
                                <div class="modal fade" id="analysisModal{{ $loop->index }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-xl">
                                        <div class="modal-content">
                                            <div class="modal-header bg-{{ $analisis->jenis_analisis === 'AI' ? 'info' : 'warning' }} text-white">
                                                <h5 class="modal-title">
                                                    {{ $analisis->title }} - {{ $analisis->jenis_analisis === 'AI' ? 'Analisis AI' : 'Analisis Manual' }}
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('laporan.update.analysis', $analisis->kd_hasil_analisis) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <textarea id="editor{{ $loop->index }}" name="analisis" class="form-control" rows="15">
                                                            {!! $analisis->analisis !!}
                                                        </textarea>
                                                    </div>
                                                    
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle"></i> Anda dapat mengedit analisis ini menggunakan editor di atas.
                                                        Klik "Simpan Perubahan" untuk menyimpan hasil edit.
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save"></i> Simpan Perubahan
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle"></i> Belum ada hasil analisis untuk laporan ini.
                </div>
            @endif
        </div>        
        <div class="card-footer bg-light">
            <div class="d-flex justify-content-between">
                <a href="{{ route('laporan.view') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <div>
                    {{-- Tombol Preview Laporan --}}
                    <a href="{{ route('laporan_bulanan.view', $laporan->kd_laporan) }}" class="btn btn-info me-2">
                        <i class="bi bi-eye me-1"></i> Preview Laporan
                    </a>
                    
                    <a href="{{ route('laporan.edit', $laporan->kd_laporan) }}" class="btn btn-warning me-2">
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
                Apakah Anda yakin ingin menghapus laporan ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form action="{{ route('laporan.destroy', $laporan->kd_laporan) }}" method="POST">
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

    document.addEventListener('DOMContentLoaded', function() {
        // Inisialisasi CKEditor untuk setiap modal analisis
        @foreach($hasilAnalisis as $index => $analisis)
            @if($analisis->status_analisis === 'Ada')
                ClassicEditor
                    .create(document.querySelector('#editor{{ $index }}'), {
                        toolbar: [
                            'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 
                            'numberedList', '|', 'undo', 'redo'
                        ]
                    })
                    .catch(error => {
                        console.error(error);
                    });
            @endif
        @endforeach

        // Inisialisasi CKEditor untuk modal kesimpulan
        @foreach($hasilAnalisis as $analisis)
            @if($analisis->title === 'Kesimpulan dan Saran')
                ClassicEditor
                    .create(document.querySelector('#conclusionContent'), {
                        toolbar: [
                            'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 
                            'numberedList', '|', 'undo', 'redo'
                        ]
                    })
                    .catch(error => {
                        console.error(error);
                    });
                @break
            @endif
        @endforeach
    });
</script>
@endsection