@extends('layouts.app')
@section('title', 'Koreksi Prompt Analisis')
@section('content')
<div class="container">
    @include('layouts.notif')
    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-edit me-2"></i>Koreksi Prompt Analisis
                </h5>
                <div class="text-end">
                    <span class="badge bg-light text-dark me-2">
                        Laporan: #{{ $kd_laporan }}
                    </span>
                    <span class="badge bg-info text-dark">
                        {{ $grafik->judul_grafik }}
                    </span>
                    @if($isExistingPrompt)
                    <span class="badge bg-success ms-2">
                        <i class="fas fa-database me-1"></i>Prompt Tersimpan
                    </span>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Informasi Laporan -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Informasi Laporan:</strong> 
                        @if(isset($laporan) && $laporan)
                            Periode {{ \Carbon\Carbon::parse($laporan->tanggal_awal)->translatedFormat('d F Y') }} - 
                            {{ \Carbon\Carbon::parse($laporan->tanggal_akhir)->translatedFormat('d F Y') }}
                        @else
                            Laporan #{{ $kd_laporan }}
                        @endif
                        | Grafik: {{ $grafik->judul_grafik }}
                        
                        @if($isExistingPrompt)
                        <br><small class="mt-1"><i class="fas fa-check-circle text-success me-1"></i>Menggunakan prompt yang sudah tersimpan dari database.</small>
                        @else
                        <br><small class="mt-1"><i class="fas fa-plus-circle text-primary me-1"></i>Menggunakan prompt baru yang digenerate otomatis.</small>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-database me-2"></i>Data yang akan Dianalisis</h6>
                            <span class="badge bg-light text-dark">{{ count($decodedData) }} records</span>
                        </div>
                        <div class="card-body">
                            <div style="max-height: 400px; overflow-y: auto;">
                                <pre class="bg-light p-3 rounded"><code>{{ json_encode($decodedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Data dalam format JSON. Pastikan struktur data sudah benar sebelum analisis.
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-robot me-2"></i>Prompt Analisis AI</h6>
                            @if($isExistingPrompt)
                            <span class="badge bg-success">Tersimpan</span>
                            @else
                            <span class="badge bg-secondary">Baru</span>
                            @endif
                        </div>
                        <div class="card-body">
                            <form action="{{ route('laporan.execute.analysis') }}" method="POST" id="promptForm">
                                @csrf
                                <input type="hidden" name="kd_laporan" value="{{ $kd_laporan }}">
                                <input type="hidden" name="kd_grafik" value="{{ $kd_grafik }}">
                                <input type="hidden" name="data" value="{{ json_encode($decodedData) }}">
                                
                                <div class="mb-3">
                                    <label for="prompt" class="form-label">
                                        <strong>Edit Prompt Analisis:</strong>
                                        <small class="text-muted d-block">
                                            @if($isExistingPrompt)
                                            <i class="fas fa-database text-success me-1"></i>Prompt ini sudah tersimpan di database. Anda dapat mengeditnya sesuai kebutuhan.
                                            @else
                                            <i class="fas fa-plus-circle text-primary me-1"></i>Prompt baru yang digenerate otomatis. Edit sesuai kebutuhan.
                                            @endif
                                        </small>
                                    </label>
                                    <textarea class="form-control" id="prompt" name="prompt" rows="15" 
                                              placeholder="Masukkan prompt untuk analisis AI...">{{ $initialPrompt }}</textarea>
                                    <div class="form-text">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Prompt yang baik akan menghasilkan analisis yang lebih akurat dan relevan.
                                    </div>
                                </div>
                                
                                @if($isExistingPrompt)
                                <div class="alert alert-success">
                                    <i class="fas fa-database me-2"></i> 
                                    <strong>Prompt Tersimpan:</strong> Anda sedang mengedit prompt yang sudah tersimpan di database. Perubahan akan disimpan ketika mengeksekusi analisis.
                                </div>
                                @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i> 
                                    <strong>Prompt Baru:</strong> Prompt ini akan disimpan ke database ketika Anda mengeksekusi analisis.
                                </div>
                                @endif
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('laporan.detail', $kd_laporan) }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Detail
                                    </a>
                                    
                                    <div>
                                        <button type="button" class="btn btn-outline-warning me-2" onclick="resetPrompt()">
                                            <i class="fas fa-undo me-1"></i> 
                                            @if($isExistingPrompt) Reset ke Tersimpan @else Reset ke Default @endif
                                        </button>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-play-circle me-1"></i> Execute Analysis
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tips Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Tips Membuat Prompt yang Efektif</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Do's:</h6>
                                    <ul>
                                        <li>Gunakan bahasa yang jelas dan spesifik</li>
                                        <li>Sebutkan format output yang diinginkan</li>
                                        <li>Berikan konteks tentang data yang dianalisis</li>
                                        <li>Minta insight yang actionable</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Don'ts:</h6>
                                    <ul>
                                        <li>Jangan terlalu umum atau ambigu</li>
                                        <li>Hindari instruksi yang bertentangan</li>
                                        <li>Jangan minta terlalu banyak hal sekaligus</li>
                                        <li>Hindari terminologi yang tidak jelas</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Simpan prompt awal untuk reset function
    const initialPrompt = `{!! addslashes($initialPrompt) !!}`;
    const isExistingPrompt = {{ $isExistingPrompt ? 'true' : 'false' }};

    function resetPrompt() {
        let confirmMessage = 'Apakah Anda yakin ingin mengembalikan prompt ke setting ';
        confirmMessage += isExistingPrompt ? 'yang tersimpan?' : 'default?';
        
        if (confirm(confirmMessage)) {
            document.getElementById('prompt').value = initialPrompt;
            // Trigger resize setelah reset
            const textarea = document.getElementById('prompt');
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight) + 'px';
        }
    }
    
    // Auto-resize textarea
    document.getElementById('prompt').addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Form submission handling
    document.getElementById('promptForm').addEventListener('submit', function(e) {
        const promptText = document.getElementById('prompt').value.trim();
        if (promptText === '') {
            e.preventDefault();
            alert('Prompt tidak boleh kosong!');
            return false;
        }
        
        // Show loading indicator
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
        submitBtn.disabled = true;
    });
    
    // Trigger initial resize
    document.addEventListener('DOMContentLoaded', function() {
        const textarea = document.getElementById('prompt');
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    });
</script>

<style>
    pre code {
        font-size: 0.8em;
        line-height: 1.3;
        font-family: 'Courier New', monospace;
    }
    
    #prompt {
        font-family: 'Courier New', monospace;
        font-size: 0.9em;
        line-height: 1.4;
    }
    
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .alert {
        border-left: 4px solid;
    }
    
    .badge {
        font-size: 0.75em;
    }
</style>
@endsection