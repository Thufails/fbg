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
                        {{ $grafik->title }}
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
                        | Grafik: {{ $grafik->title }}
                        
                        @if($isExistingPrompt)
                        <br><small class="mt-1"><i class="fas fa-check-circle text-success me-1"></i>Menggunakan prompt yang sudah tersimpan dari database.</small>
                        @else
                        <br><small class="mt-1"><i class="fas fa-plus-circle text-primary me-1"></i>Menggunakan prompt baru yang digenerate otomatis.</small>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-10">
                    <!-- Tombol Tambah Issue, Komplain, Tugas, dan Analisis di atas card -->
                    <div class="d-flex justify-content-end mb-3 gap-2 flex-wrap">
                        <button type="button" class="btn btn-primary" onclick="showIssueModal()">
                            <i class="fas fa-plus-circle me-1"></i>Tambah Issue
                        </button>
                        <button type="button" class="btn btn-info text-white" onclick="showKomplainModal()">
                            <i class="fas fa-comment me-1"></i>Tambah Komplain
                        </button>
                        <button type="button" class="btn btn-success" onclick="showTugasModal()">
                            <i class="fas fa-tasks me-1"></i>Tambah Tugas
                        </button>
                        <button type="button" class="btn btn-purple text-white" onclick="showAnalisisModal()">
                            <i class="fas fa-chart-line me-1"></i>Tambah Analisis
                        </button>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-robot me-2"></i>Prompt Analisis AI</h6>
                            <div>
                                @if($isExistingPrompt)
                                <span class="badge bg-success me-2">Tersimpan</span>
                                @else
                                <span class="badge bg-secondary me-2">Baru</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('laporan.execute.analysis') }}" method="POST" id="promptForm">
                                @csrf
                                <input type="hidden" name="kd_laporan" value="{{ $kd_laporan }}">
                                <input type="hidden" name="title" value="{{ $title }}">
                                <input type="hidden" name="data" value="{{ json_encode($decodedData) }}">
                                
                                <!-- Selected Issues Display -->
                                <div class="mb-3" id="selectedIssuesContainer" style="display: none;">
                                    <label class="form-label"><strong>Issues Terpilih:</strong></label>
                                    <div id="selectedIssuesList" class="bg-light p-3 rounded mb-2"></div>
                                    <input type="hidden" name="selected_issues" id="selectedIssuesInput">
                                </div>

                                <!-- Selected Komplains Display -->
                                <div class="mb-3" id="selectedKomplainsContainer" style="display: none;">
                                    <label class="form-label"><strong>Komplains Terpilih:</strong></label>
                                    <div id="selectedKomplainsList" class="bg-light p-3 rounded mb-2"></div>
                                    <input type="hidden" name="selected_komplains" id="selectedKomplainsInput">
                                </div>

                                <!-- Selected Tugas Display -->
                                <div class="mb-3" id="selectedTugasContainer" style="display: none;">
                                    <label class="form-label"><strong>Tugas Terpilih:</strong></label>
                                    <div id="selectedTugasList" class="bg-light p-3 rounded mb-2"></div>
                                    <input type="hidden" name="selected_tugas" id="selectedTugasInput">
                                </div>

                                <!-- Selected Analisis Display -->
                                <div class="mb-3" id="selectedAnalisisContainer" style="display: none;">
                                    <label class="form-label"><strong>Analisis Terpilih:</strong></label>
                                    <div id="selectedAnalisisList" class="bg-light p-3 rounded mb-2"></div>
                                    <input type="hidden" name="selected_analisis" id="selectedAnalisisInput">
                                </div>
                                
                                <!-- Pengaturan Tambahan -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-header bg-secondary text-white">
                                                <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Pengaturan Analisis</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="max_words" class="form-label">
                                                        <strong>Maksimal Kata dalam Respon:</strong>
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="number" 
                                                               class="form-control" 
                                                               id="max_words" 
                                                               name="max_words" 
                                                               value="500" 
                                                               min="100" 
                                                               max="2000" 
                                                               step="50"
                                                               placeholder="Masukkan jumlah maksimal kata">
                                                        <span class="input-group-text">kata</span>
                                                    </div>
                                                    <div class="form-text">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        Tentukan batas maksimal kata untuk respon AI. Rentang: 100 - 2000 kata.
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="tone" class="form-label">
                                                        <strong>Gaya Bahasa:</strong>
                                                    </label>
                                                    <select class="form-select" id="tone" name="tone">
                                                        <option value="formal">Formal</option>
                                                        <option value="professional" selected>Profesional</option>
                                                        <option value="analytical">Analitis</option>
                                                        <option value="concise">Ringkas</option>
                                                        <option value="detailed">Detail</option>
                                                    </select>
                                                    <div class="form-text">
                                                        <i class="fas fa-comment me-1"></i>
                                                        Pilih gaya bahasa untuk respon analisis.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-header bg-secondary text-white">
                                                <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Estimasi Respon</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="text-center">
                                                    <div class="mb-3">
                                                        <i class="fas fa-clock fa-2x text-primary mb-2"></i>
                                                        <h5 id="estimatedTime">± 2-3 menit</h5>
                                                        <small class="text-muted">Estimasi waktu proses</small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <i class="fas fa-file-alt fa-2x text-success mb-2"></i>
                                                        <h5 id="wordCountInfo">500 kata</h5>
                                                        <small class="text-muted">Panjang respon</small>
                                                    </div>
                                                    <div class="progress mb-2" style="height: 8px;">
                                                        <div id="complexityBar" class="progress-bar bg-info" style="width: 60%;"></div>
                                                    </div>
                                                    <small class="text-muted" id="complexityText">Tingkat kompleksitas: Sedang</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
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
                                    <textarea class="form-control" id="prompt" name="prompt" rows="18" 
                                              placeholder="Masukkan prompt untuk analisis AI...">{{ $initialPrompt }}</textarea>
                                    <div class="form-text">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Prompt yang baik akan menghasilkan analisis yang lebih akurat dan relevan.
                                        <span id="wordCount" class="badge bg-info float-end mt-1">0 kata</span>
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
        </div>
    </div>
</div>

<!-- Modal untuk Daftar Issue -->
<div class="modal fade" id="issueModal" tabindex="-1" aria-labelledby="issueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="issueModalLabel">
                    <i class="fas fa-list me-2"></i>Pilih Issue
                </h5>
                <div class="ms-auto">
                    <small class="me-3">
                        Status: 
                        <span class="badge bg-warning">Open</span> 
                        <span class="badge bg-info">In Progress</span> 
                        <span class="badge bg-success">Resolved</span>
                    </small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Search Bar -->
                <div class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchIssue" placeholder="Cari issue...">
                        <button class="btn btn-outline-secondary" type="button" onclick="searchIssue()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Loading Indicator -->
                <div id="loadingIssues" class="text-center" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat issues...</p>
                </div>
                
                <!-- Issues List -->
                <div id="issuesList" class="list-group" style="max-height: 400px; overflow-y: auto;">
                    <!-- Issues akan dimuat di sini via AJAX -->
                </div>
            </div>
            <div class="modal-footer">
                <div class="me-auto">
                    <span class="badge bg-success" id="selectedCount">0 issue terpilih</span>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary confirm-btn" data-type="Issue">Konfirmasi Pilihan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Daftar Komplain -->
<div class="modal fade" id="komplainModal" tabindex="-1" aria-labelledby="komplainModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="komplainModalLabel">
                    <i class="fas fa-comments me-2"></i>Pilih Komplain
                </h5>
                <div class="ms-auto">
                    <small class="me-3">
                        Status: 
                        <span class="badge bg-danger">Baru</span> 
                        <span class="badge bg-warning">Diproses</span> 
                        <span class="badge bg-success">Selesai</span>
                    </small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Search Bar -->
                <div class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchKomplain" placeholder="Cari komplain...">
                        <button class="btn btn-outline-secondary" type="button" onclick="searchKomplain()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Loading Indicator -->
                <div id="loadingKomplains" class="text-center" style="display: none;">
                    <div class="spinner-border text-info" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat komplains...</p>
                </div>
                
                <!-- Komplains List -->
                <div id="komplainsList" class="list-group" style="max-height: 400px; overflow-y: auto;">
                    <!-- Komplains akan dimuat di sini via AJAX -->
                </div>
            </div>
            <div class="modal-footer">
                <div class="me-auto">
                    <span class="badge bg-success" id="selectedKomplainCount">0 komplain terpilih</span>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-info text-white confirm-btn" data-type="Komplain">Konfirmasi Pilihan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Daftar Tugas -->
<div class="modal fade" id="tugasModal" tabindex="-1" aria-labelledby="tugasModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="tugasModalLabel">
                    <i class="fas fa-tasks me-2"></i>Pilih Tugas
                </h5>
                <div class="ms-auto">
                    <small class="me-3">
                        Status: 
                        <span class="badge bg-warning">Plan</span> 
                        <span class="badge bg-info">Proses</span> 
                        <span class="badge bg-success">Done</span>
                    </small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Search Bar -->
                <div class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchTugas" placeholder="Cari tugas...">
                        <button class="btn btn-outline-secondary" type="button" onclick="searchTugas()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Loading Indicator -->
                <div id="loadingTugas" class="text-center" style="display: none;">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat tugas...</p>
                </div>
                
                <!-- Tugas List -->
                <div id="tugasList" class="list-group" style="max-height: 400px; overflow-y: auto;">
                    <!-- Tugas akan dimuat di sini via AJAX -->
                </div>
            </div>
            <div class="modal-footer">
                <div class="me-auto">
                    <span class="badge bg-success" id="selectedTugasCount">0 tugas terpilih</span>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-success confirm-btn" data-type="Tugas">Konfirmasi Pilihan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Daftar Analisis -->
<div class="modal fade" id="analisisModal" tabindex="-1" aria-labelledby="analisisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-purple text-white">
                <h5 class="modal-title" id="analisisModalLabel">
                    <i class="fas fa-chart-line me-2"></i>Pilih Analisis
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Search Bar -->
                <div class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchAnalisis" placeholder="Cari analisis...">
                        <button class="btn btn-outline-secondary" type="button" onclick="searchAnalisis()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Loading Indicator -->
                <div id="loadingAnalisis" class="text-center" style="display: none;">
                    <div class="spinner-border text-purple" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat analisis...</p>
                </div>
                
                <!-- Analisis List -->
                <div id="analisisList" class="list-group" style="max-height: 400px; overflow-y: auto;">
                    <!-- Analisis akan dimuat di sini via AJAX -->
                </div>
            </div>
            <div class="modal-footer">
                <div class="me-auto">
                    <span class="badge bg-success" id="selectedAnalisisCount">0 analisis terpilih</span>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-purple text-white confirm-btn" data-type="Analisis">Konfirmasi Pilihan</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Simpan prompt awal untuk reset function
    const initialPrompt = {!! json_encode($initialPrompt) !!};
    
    // Variabel global untuk selected items
    let selectedIssues = [];
    let selectedKomplains = [];
    let selectedTugas = [];
    let selectedAnalisis = [];

    // ========== FUNGSI HELPER STATUS ==========
    // Fungsi untuk status issue
    function getIssueStatusText(status) {
        const statusMap = {
            0: 'Open',
            1: 'In Progress', 
            2: 'Resolved',
            3: 'Closed',
            4: 'Pending'
        };
        return statusMap[status] || 'Unknown';
    }

    function getIssueStatusClass(status) {
        const statusClassMap = {
            0: 'bg-warning text-dark',
            1: 'bg-info text-white',
            2: 'bg-success text-white',
            3: 'bg-secondary text-white',
            4: 'bg-light text-dark border'
        };
        return statusClassMap[status] || 'bg-secondary text-white';
    }

    // Fungsi untuk status komplain
    function getKomplainStatusText(status) {
        const statusMap = {
            0: 'Baru',
            1: 'Diproses',
            2: 'Selesai',
            3: 'Ditutup'
        };
        return statusMap[status] || 'Unknown';
    }

    function getKomplainStatusClass(status) {
        const statusClassMap = {
            0: 'bg-danger text-white',
            1: 'bg-warning text-dark',
            2: 'bg-success text-white',
            3: 'bg-secondary text-white'
        };
        return statusClassMap[status] || 'bg-secondary text-white';
    }

    // Fungsi untuk status tugas (existing)
    function getStatusText(status) {
        const statusMap = {
            0: 'Plan',
            1: 'Proses', 
            2: 'Done'
        };
        return statusMap[status] || 'Unknown';
    }

    function getStatusClass(status) {
        const statusClassMap = {
            0: 'bg-warning text-dark',
            1: 'bg-primary text-white',
            2: 'bg-success text-white'
        };
        return statusClassMap[status] || 'bg-secondary text-white';
    }

    // ========== FUNGSI MODAL ==========
    function showIssueModal() {
        loadIssues();
        const modal = new bootstrap.Modal(document.getElementById('issueModal'));
        modal.show();
    }

    function showKomplainModal() {
        loadKomplains();
        const modal = new bootstrap.Modal(document.getElementById('komplainModal'));
        modal.show();
    }

    function showTugasModal() {
        loadTugas();
        const modal = new bootstrap.Modal(document.getElementById('tugasModal'));
        modal.show();
    }

    function showAnalisisModal() {
        loadAnalisis();
        const modal = new bootstrap.Modal(document.getElementById('analisisModal'));
        modal.show();
    }

    // ========== FUNGSI LOAD DATA DARI API ==========
    function loadIssues(search = '') {
        const listElement = document.getElementById('issuesList');
        const loadingIndicator = document.getElementById('loadingIssues');
        
        listElement.innerHTML = '';
        loadingIndicator.style.display = 'block';

        fetch(`/issue/search?search=${encodeURIComponent(search)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            loadingIndicator.style.display = 'none';
            
            let issues = data.issues || data.data || [];
            
            if (issues.length === 0) {
                listElement.innerHTML = '<div class="text-center text-muted py-4">Tidak ada issue ditemukan</div>';
                return;
            }

            issues.forEach(item => {
                const isSelected = selectedIssues.some(selected => selected.kd_issue === item.kd_issue);
                const statusText = getIssueStatusText(item.status || 0);
                const statusClass = getIssueStatusClass(item.status || 0);
                
                const itemElement = document.createElement('div');
                itemElement.className = `list-group-item list-group-item-action ${isSelected ? 'active' : ''}`;
                
                const escapedUraian = escapeHtml(item.uraian || '');
                const escapedId = escapeHtml(item.kd_issue || '');
                const statusValue = item.status || 0;
                
                itemElement.innerHTML = `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                               value="${escapedId}" 
                               ${isSelected ? 'checked' : ''}
                               onchange="toggleIssue('${escapedId}', '${escapedUraian}', ${statusValue}, this.checked)">
                        <label class="form-check-label ${isSelected ? 'text-white' : ''}">
                            <strong>${escapedUraian}</strong>
                            <div class="mt-1">
                                <small class="${isSelected ? 'text-light' : 'text-muted'}">
                                    <i class="far fa-calendar me-1"></i>${formatDate(item.tanggal)}
                                </small>
                                <span class="badge ${statusClass} ms-2">${statusText}</span>
                                ${item.prioritas ? `<span class="badge bg-danger ms-1">Prioritas: ${item.prioritas}</span>` : ''}
                            </div>
                        </label>
                    </div>
                `;
                listElement.appendChild(itemElement);
            });

            updateSelectedCount('Issue');
        })
        .catch(error => {
            console.error('Error loading issues:', error);
            loadingIndicator.style.display = 'none';
            listElement.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Gagal memuat data issues. Silakan coba lagi.
                    <br><small>${error.message}</small>
                </div>
            `;
        });
    }

    function loadKomplains(search = '') {
        const listElement = document.getElementById('komplainsList');
        const loadingIndicator = document.getElementById('loadingKomplains');
        
        listElement.innerHTML = '';
        loadingIndicator.style.display = 'block';

        fetch(`/komplain/search?search=${encodeURIComponent(search)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            loadingIndicator.style.display = 'none';
            
            let komplains = data.komplains || data.data || [];
            
            if (komplains.length === 0) {
                listElement.innerHTML = '<div class="text-center text-muted py-4">Tidak ada komplain ditemukan</div>';
                return;
            }

            komplains.forEach(item => {
                const isSelected = selectedKomplains.some(selected => selected.kd_komplain === item.kd_komplain);
                const statusText = getKomplainStatusText(item.status || 0);
                const statusClass = getKomplainStatusClass(item.status || 0);
                
                const itemElement = document.createElement('div');
                itemElement.className = `list-group-item list-group-item-action ${isSelected ? 'active' : ''}`;
                
                const escapedUraian = escapeHtml(item.uraian || '');
                const escapedId = escapeHtml(item.kd_komplain || '');
                const statusValue = item.status || 0;
                
                itemElement.innerHTML = `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                               value="${escapedId}" 
                               ${isSelected ? 'checked' : ''}
                               onchange="toggleKomplain('${escapedId}', '${escapedUraian}', ${statusValue}, this.checked)">
                        <label class="form-check-label ${isSelected ? 'text-white' : ''}">
                            <strong>${escapedUraian}</strong>
                            <div class="mt-1">
                                <small class="${isSelected ? 'text-light' : 'text-muted'}">
                                    <i class="far fa-calendar me-1"></i>${formatDate(item.tanggal)}
                                </small>
                                <span class="badge ${statusClass} ms-2">${statusText}</span>
                                ${item.prioritas ? `<span class="badge bg-danger ms-1">Prioritas: ${item.prioritas}</span>` : ''}
                            </div>
                        </label>
                    </div>
                `;
                listElement.appendChild(itemElement);
            });

            updateSelectedCount('Komplain');
        })
        .catch(error => {
            console.error('Error loading komplains:', error);
            loadingIndicator.style.display = 'none';
            listElement.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Gagal memuat data komplains. Silakan coba lagi.
                    <br><small>${error.message}</small>
                </div>
            `;
        });
    }

    function loadTugas(search = '') {
        const listElement = document.getElementById('tugasList');
        const loadingIndicator = document.getElementById('loadingTugas');
        
        listElement.innerHTML = '';
        loadingIndicator.style.display = 'block';

        fetch(`/tugas/search?search=${encodeURIComponent(search)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            loadingIndicator.style.display = 'none';
            
            let tugas = data.tugas || data.data || [];
            
            if (tugas.length === 0) {
                listElement.innerHTML = '<div class="text-center text-muted py-4">Tidak ada tugas ditemukan</div>';
                return;
            }

            tugas.forEach(item => {
                const isSelected = selectedTugas.some(selected => selected.kd_tugas === item.kd_tugas);
                const statusText = getStatusText(item.status || 0);
                const statusClass = getStatusClass(item.status || 0);
                
                const itemElement = document.createElement('div');
                itemElement.className = `list-group-item list-group-item-action ${isSelected ? 'active' : ''}`;
                
                const escapedUraian = escapeHtml(item.uraian || '');
                const escapedId = escapeHtml(item.kd_tugas || '');
                const statusValue = item.status || 0;
                
                itemElement.innerHTML = `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                               value="${escapedId}" 
                               ${isSelected ? 'checked' : ''}
                               onchange="toggleTugas('${escapedId}', '${escapedUraian}', ${statusValue}, this.checked)">
                        <label class="form-check-label ${isSelected ? 'text-white' : ''}">
                            <strong>${escapedUraian}</strong>
                            <div class="mt-1">
                                <small class="${isSelected ? 'text-light' : 'text-muted'}">
                                    <i class="far fa-calendar me-1"></i>${formatDate(item.tanggal)}
                                </small>
                                <span class="badge ${statusClass} ms-2">${statusText}</span>
                            </div>
                        </label>
                    </div>
                `;
                listElement.appendChild(itemElement);
            });

            updateSelectedCount('Tugas');
        })
        .catch(error => {
            console.error('Error loading tugas:', error);
            loadingIndicator.style.display = 'none';
            listElement.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Gagal memuat data tugas. Silakan coba lagi.
                    <br><small>${error.message}</small>
                </div>
            `;
        });
    }

    function loadAnalisis(search = '') {
        const listElement = document.getElementById('analisisList');
        const loadingIndicator = document.getElementById('loadingAnalisis');
        
        listElement.innerHTML = '';
        loadingIndicator.style.display = 'block';

        fetch(`/analisis/search?search=${encodeURIComponent(search)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            loadingIndicator.style.display = 'none';
            
            let analyses = data.analyses || [];
            
            if (analyses.length === 0) {
                listElement.innerHTML = '<div class="text-center text-muted py-4">Tidak ada analisis ditemukan</div>';
                return;
            }

            analyses.forEach(item => {
                const isSelected = selectedAnalisis.some(selected => selected.kd_analisis === item.kd_analisis);
                const itemElement = document.createElement('div');
                itemElement.className = `list-group-item list-group-item-action ${isSelected ? 'active' : ''}`;
                
                const escapedUraian = escapeHtml(item.uraian || '');
                const escapedId = escapeHtml(item.kd_analisis || '');
                
                itemElement.innerHTML = `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                            value="${escapedId}" 
                            ${isSelected ? 'checked' : ''}
                            onchange="toggleAnalisis('${escapedId}', '${escapedUraian}', this.checked)">
                        <label class="form-check-label ${isSelected ? 'text-white' : ''}">
                            <strong>${escapedUraian}</strong>
                            <br>
                            <small class="${isSelected ? 'text-light' : 'text-muted'}">
                                <i class="far fa-calendar me-1"></i>${formatDate(item.created_at)} | ${item.bulan} ${item.tahun}
                            </small>
                        </label>
                    </div>
                `;
                listElement.appendChild(itemElement);
            });

            updateSelectedCount('Analisis');
        })
        .catch(error => {
            console.error('Error loading analisis:', error);
            loadingIndicator.style.display = 'none';
            listElement.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Gagal memuat data analisis. Silakan coba lagi.
                    <br><small>${error.message}</small>
                </div>
            `;
        });
    }

    // ========== FUNGSI TOGGLE SELECTION ==========
    function toggleIssue(id, uraian, status, isSelected) {
        console.log('Toggle issue:', id, uraian, status, isSelected);
        if (isSelected) {
            selectedIssues.push({ 
                kd_issue: id, 
                uraian: uraian,
                status: status,
                status_text: getIssueStatusText(status)
            });
        } else {
            selectedIssues = selectedIssues.filter(item => item.kd_issue !== id);
        }
        updateSelectedCount('Issue');
        updateUI('issuesList', id, isSelected);
    }

    function toggleKomplain(id, uraian, status, isSelected) {
        console.log('Toggle komplain:', id, uraian, status, isSelected);
        if (isSelected) {
            selectedKomplains.push({ 
                kd_komplain: id, 
                uraian: uraian,
                status: status,
                status_text: getKomplainStatusText(status)
            });
        } else {
            selectedKomplains = selectedKomplains.filter(item => item.kd_komplain !== id);
        }
        updateSelectedCount('Komplain');
        updateUI('komplainsList', id, isSelected);
    }

    function toggleTugas(id, uraian, status, isSelected) {
        console.log('Toggle tugas:', id, uraian, status, isSelected);
        if (isSelected) {
            selectedTugas.push({ 
                kd_tugas: id, 
                uraian: uraian,
                status: status,
                status_text: getStatusText(status)
            });
        } else {
            selectedTugas = selectedTugas.filter(item => item.kd_tugas !== id);
        }
        updateSelectedCount('Tugas');
        updateUI('tugasList', id, isSelected);
    }

    function toggleAnalisis(id, uraian, isSelected) {
        console.log('Toggle analisis:', id, uraian, isSelected);
        if (isSelected) {
            selectedAnalisis.push({ 
                kd_analisis: id, 
                uraian: uraian
            });
        } else {
            selectedAnalisis = selectedAnalisis.filter(item => item.kd_analisis !== id);
        }
        updateSelectedCount('Analisis');
        updateUI('analisisList', id, isSelected);
    }

    function updateUI(listId, id, isSelected) {
        const input = document.querySelector(`#${listId} input[value="${id}"]`);
        if (input) {
            const item = input.closest('.list-group-item');
            if (item) {
                item.classList.toggle('active', isSelected);
                const label = item.querySelector('.form-check-label');
                if (label) {
                    label.classList.toggle('text-white', isSelected);
                }
                const small = item.querySelector('small');
                if (small) {
                    small.classList.toggle('text-light', isSelected);
                    small.classList.toggle('text-muted', !isSelected);
                }
            }
        }
    }

    // ========== FUNGSI SEARCH ==========
    function searchIssue() {
        const searchTerm = document.getElementById('searchIssue').value;
        loadIssues(searchTerm);
    }

    function searchKomplain() {
        const searchTerm = document.getElementById('searchKomplain').value;
        loadKomplains(searchTerm);
    }

    function searchTugas() {
        const searchTerm = document.getElementById('searchTugas').value;
        loadTugas(searchTerm);
    }

    function searchAnalisis() {
        const searchTerm = document.getElementById('searchAnalisis').value;
        loadAnalisis(searchTerm);
    }

    // ========== FUNGSI HELPER ==========
    function updateSelectedCount(type) {
        let count, elementId;
        switch(type) {
            case 'Issue':
                count = selectedIssues.length;
                elementId = 'selectedCount';
                break;
            case 'Komplain':
                count = selectedKomplains.length;
                elementId = 'selectedKomplainCount';
                break;
            case 'Tugas':
                count = selectedTugas.length;
                elementId = 'selectedTugasCount';
                break;
            case 'Analisis':
                count = selectedAnalisis.length;
                elementId = 'selectedAnalisisCount';
                break;
        }
        if (document.getElementById(elementId)) {
            document.getElementById(elementId).textContent = `${count} ${type} terpilih`;
        }
    }

    function updateSelectedDisplay(containerId, listId, inputId, sectionType, items) {
        console.log(`Update display: ${containerId}, items:`, items);
        
        const container = document.getElementById(containerId);
        const list = document.getElementById(listId);
        const input = document.getElementById(inputId);
        
        if (!container || !list || !input) {
            console.error('Element tidak ditemukan:', {containerId, listId, inputId});
            return;
        }
        
        if (items.length > 0) {
            let html = '';
            items.forEach((item, index) => {
                const escapedUraian = escapeHtml(item.uraian || '');
                const statusText = item.status_text || 'Unknown';
                
                let statusClass = 'bg-secondary';
                if (sectionType === 'ISSUES') {
                    statusClass = getIssueStatusClass(item.status || 0).split(' ')[0];
                } else if (sectionType === 'COMPLAINTS') {
                    statusClass = getKomplainStatusClass(item.status || 0).split(' ')[0];
                } else if (sectionType === 'TASKS') {
                    statusClass = getStatusClass(item.status || 0).split(' ')[0];
                }
                
                html += `
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-white rounded border" 
                         data-index="${index}" 
                         data-type="${sectionType}">
                        <div>
                            <span class="me-2">${escapedUraian}</span>
                            <span class="badge ${statusClass}">${statusText}</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" data-index="${index}" data-type="${sectionType}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            });
            
            list.innerHTML = html;
            
            try {
                input.value = JSON.stringify(items);
            } catch (error) {
                console.error('Error stringifying items:', error);
                input.value = '[]';
            }
            
            container.style.display = 'block';
            addItemsToPrompt(sectionType, items);
        } else {
            container.style.display = 'none';
            input.value = '[]';
            removeSectionFromPrompt(sectionType);
        }
    }

    function removeSectionFromPrompt(sectionType) {
        const promptTextarea = document.getElementById('prompt');
        if (!promptTextarea) return;
        
        let currentPrompt = promptTextarea.value;
        
        const regex = new RegExp(`\\n?=== ${sectionType} ===[\\s\\S]*?=== END ${sectionType} ===\\n?`, 'g');
        currentPrompt = currentPrompt.replace(regex, '');
        
        promptTextarea.value = currentPrompt;
        promptTextarea.style.height = 'auto';
        promptTextarea.style.height = (promptTextarea.scrollHeight) + 'px';
    }

    function addItemsToPrompt(sectionType, items) {
        const promptTextarea = document.getElementById('prompt');
        if (!promptTextarea) return;
        
        let currentPrompt = promptTextarea.value;
        
        // Hapus section sebelumnya jika ada
        const regex = new RegExp(`\\n?=== ${sectionType} ===[\\s\\S]*?=== END ${sectionType} ===\\n?`, 'g');
        currentPrompt = currentPrompt.replace(regex, '');
        
        if (items.length > 0) {
            let itemsText = `\n\n=== ${sectionType} ===\n`;
            
            items.forEach(item => {
                const statusText = item.status_text || 'Unknown';
                itemsText += `- ${item.uraian} [Status: ${statusText}]\n`;
            });
            
            itemsText += `=== END ${sectionType} ===\n`;
            
            promptTextarea.value = currentPrompt + itemsText;
            promptTextarea.style.height = 'auto';
            promptTextarea.style.height = (promptTextarea.scrollHeight) + 'px';
        } else {
            promptTextarea.value = currentPrompt;
        }
        
        updateWordCount();
    }

    function formatDate(dateString) {
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        } catch (e) {
            return dateString || 'Tanggal tidak tersedia';
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#x27;')
            .replace(/`/g, '&#x60;')
            .replace(/{/g, '&#123;')
            .replace(/}/g, '&#125;')
            .replace(/\(/g, '&#40;')
            .replace(/\)/g, '&#41;');
    }

    function resetPrompt() {
        if (confirm('Apakah Anda yakin ingin mengembalikan prompt ke setting awal?')) {
            document.getElementById('prompt').value = initialPrompt;
            const textarea = document.getElementById('prompt');
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight) + 'px';
            updateWordCount();
            updateEstimation();
        }
    }

    // ========== FUNGSI UNTUK MAX WORDS & ESTIMASI ==========
    function countWords(text) {
        return text.trim() ? text.trim().split(/\s+/).length : 0;
    }

    function updateWordCount() {
        const promptText = document.getElementById('prompt').value;
        const wordCount = countWords(promptText);
        document.getElementById('wordCount').textContent = `${wordCount} kata`;
        
        updateEstimation();
    }

    function updateEstimation() {
        const maxWords = parseInt(document.getElementById('max_words').value) || 500;
        const promptWordCount = countWords(document.getElementById('prompt').value);
        const totalItems = selectedIssues.length + selectedKomplains.length + selectedTugas.length + selectedAnalisis.length;
        
        document.getElementById('wordCountInfo').textContent = `${maxWords} kata`;
        
        let estimatedMinutes = 2 + Math.floor(promptWordCount / 500) + Math.floor(totalItems / 5);
        estimatedMinutes = Math.min(estimatedMinutes, 10);
        document.getElementById('estimatedTime').textContent = `± ${estimatedMinutes} menit`;
        
        const complexity = Math.min(20 + (promptWordCount / 50) + (totalItems * 5), 100);
        document.getElementById('complexityBar').style.width = `${complexity}%`;
        
        let complexityText = 'Rendah';
        if (complexity > 60) complexityText = 'Tinggi';
        else if (complexity > 30) complexityText = 'Sedang';
        
        document.getElementById('complexityText').textContent = `Tingkat kompleksitas: ${complexityText}`;
    }

    function validateMaxWords() {
        const maxWordsInput = document.getElementById('max_words');
        let value = parseInt(maxWordsInput.value);
        
        if (isNaN(value) || value < 100) {
            maxWordsInput.value = 100;
        } else if (value > 2000) {
            maxWordsInput.value = 2000;
        }
        
        updateEstimation();
    }

    // ========== EVENT LISTENER UNTUK HAPUS ITEM ==========
    function setupRemoveItemListeners() {
        // Event delegation untuk tombol remove
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-item-btn') || e.target.closest('.remove-item-btn')) {
                const btn = e.target.classList.contains('remove-item-btn') ? e.target : e.target.closest('.remove-item-btn');
                const index = parseInt(btn.getAttribute('data-index'));
                const type = btn.getAttribute('data-type');
                
                console.log('Remove item clicked:', index, type);
                
                switch(type) {
                    case 'Issue':
                        selectedIssues.splice(index, 1);
                        updateSelectedDisplay('selectedIssuesContainer', 'selectedIssuesList', 'selectedIssuesInput', 'Issue', selectedIssues);
                        break;
                    case 'Komplain':
                        selectedKomplains.splice(index, 1);
                        updateSelectedDisplay('selectedKomplainsContainer', 'selectedKomplainsList', 'selectedKomplainsInput', 'Komplain', selectedKomplains);
                        break;
                    case 'Tugas':
                        selectedTugas.splice(index, 1);
                        updateSelectedDisplay('selectedTugasContainer', 'selectedTugasList', 'selectedTugasInput', 'Tugas', selectedTugas);
                        break;
                    case 'Analisis':
                        selectedAnalisis.splice(index, 1);
                        updateSelectedDisplay('selectedAnalisisContainer', 'selectedAnalisisList', 'selectedAnalisisInput', 'Analisis', selectedAnalisis);
                        break;
                }
                
                updateEstimation();
            }
        });
    }

    // ========== EVENT LISTENER UNTUK KONFIRMASI MODAL ==========
    function setupModalConfirmationListeners() {
        document.addEventListener('click', function(e) {
            // Handle tombol konfirmasi untuk semua modal
            if (e.target.classList.contains('confirm-btn') || e.target.closest('.confirm-btn')) {
                const btn = e.target.classList.contains('confirm-btn') ? e.target : e.target.closest('.confirm-btn');
                const type = btn.getAttribute('data-type');
                console.log('Tombol konfirmasi diklik untuk:', type);
                
                switch(type) {
                    case 'Issue':
                        updateSelectedDisplay('selectedIssuesContainer', 'selectedIssuesList', 'selectedIssuesInput', 'Issue', selectedIssues);
                        break;
                    case 'Komplain':
                        updateSelectedDisplay('selectedKomplainsContainer', 'selectedKomplainsList', 'selectedKomplainsInput', 'Komplain', selectedKomplains);
                        break;
                    case 'Tugas':
                        console.log('Selected Tugas before update:', selectedTugas);
                        updateSelectedDisplay('selectedTugasContainer', 'selectedTugasList', 'selectedTugasInput', 'Tugas', selectedTugas);
                        break;
                    case 'Analisis':
                        updateSelectedDisplay('selectedAnalisisContainer', 'selectedAnalisisList', 'selectedAnalisisInput', 'Analisis', selectedAnalisis);
                        break;
                }
                
                updateEstimation();
                
                // Tutup modal
                const modal = btn.closest('.modal');
                if (modal) {
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) {
                        bsModal.hide();
                    }
                }
            }
        });
    }

    // ========== INISIALISASI EVENT LISTENERS ==========
    function initializeEventListeners() {
        // Auto-resize textarea
        document.getElementById('prompt').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            updateWordCount();
        });

        // Event listeners untuk max words
        document.getElementById('max_words').addEventListener('change', validateMaxWords);
        document.getElementById('max_words').addEventListener('input', validateMaxWords);

        // Event listener untuk tone selection
        document.getElementById('tone').addEventListener('change', updateEstimation);

        // Setup event listeners untuk modal dan remove buttons
        setupModalConfirmationListeners();
        setupRemoveItemListeners();

        // Form submission handling
        document.getElementById('promptForm').addEventListener('submit', function(e) {
            const promptText = document.getElementById('prompt').value.trim();
            if (promptText === '') {
                e.preventDefault();
                alert('Prompt tidak boleh kosong!');
                return false;
            }
            
            const maxWords = parseInt(document.getElementById('max_words').value);
            if (isNaN(maxWords) || maxWords < 100 || maxWords > 2000) {
                e.preventDefault();
                alert('Maksimal kata harus antara 100-2000 kata!');
                return false;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
            submitBtn.disabled = true;
        });
    }

    // ========== INISIALISASI SETELAH DOM LOADED ==========
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM Content Loaded - Aplikasi siap');
        
        // Inisialisasi event listeners
        initializeEventListeners();
        
        // Trigger initial resize untuk textarea
        const textarea = document.getElementById('prompt');
        if (textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight) + 'px';
            updateWordCount();
        }
        
        // Initialize estimation
        updateEstimation();
        
        // Debug: Check if tugas elements exist
        console.log('Tugas elements check:');
        console.log('- selectedTugasContainer:', document.getElementById('selectedTugasContainer'));
        console.log('- selectedTugasList:', document.getElementById('selectedTugasList'));
        console.log('- selectedTugasInput:', document.getElementById('selectedTugasInput'));
    });
</script>

<style>
    #prompt {
        font-family: 'Courier New', monospace;
        font-size: 0.9em;
        line-height: 1.4;
    }
    
    .list-group-item.active {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    #selectedIssuesList, #selectedKomplainsList, #selectedTugasList, #selectedAnalisisList {
        max-height: 200px;
        overflow-y: auto;
    }
    
    .confirm-btn {
        cursor: pointer;
    }
    
    .remove-item-btn {
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .remove-item-btn:hover {
        transform: scale(1.1);
    }
    
    .btn-purple {
        background-color: #6f42c1;
        border-color: #6f42c1;
    }
    
    .btn-purple:hover {
        background-color: #5a2d91;
        border-color: #5a2d91;
    }
    
    .bg-purple {
        background-color: #6f42c1 !important;
    }
    
    .text-purple {
        color: #6f42c1 !important;
    }
    
    .spinner-border.text-purple {
        color: #6f42c1 !important;
    }
    
    .card.bg-light {
        border: 1px solid #dee2e6;
    }
    
    .progress {
        background-color: #e9ecef;
    }
    
    #wordCount {
        font-size: 0.8em;
    }
    
    .badge {
        font-size: 0.75em;
        font-weight: 500;
    }
    
    .border {
        border: 1px solid #dee2e6 !important;
    }
</style>
@endsection