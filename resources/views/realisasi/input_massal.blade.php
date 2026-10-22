@extends('layouts.app')
@section('title', 'Input Realisasi Massal')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-collection"></i> Input Realisasi Massal</h4>
        <div>
            <a href="{{ route('realisasi.view') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form id="inputMassalForm" action="{{ route('realisasi.storeMassal') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <!-- Filter Section -->
                    <div class="col-md-12">
                        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-funnel"></i> Filter Data</h5>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="kd_akun" class="form-label">Pilih Akun</label>
                        <select class="form-select" id="kd_akun" name="kd_akun" required>
                            <option value="">-- Pilih Akun --</option>
                            @foreach($akunList ?? [] as $item)
                                <option value="{{ $item->kd_akun }}" {{ old('kd_akun') == $item->kd_akun ? 'selected' : '' }}>
                                    {{ $item->kd_akun }} - {{ $item->nama_akun }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="kd_entitas" class="form-label">Entitas</label>
                        <select class="form-select" id="kd_entitas" name="kd_entitas" required>
                            <option value="">-- Pilih Entitas --</option>
                            @foreach($entitasList ?? [] as $entitas)
                                <option value="{{ $entitas->kd_entitas }}" {{ old('kd_entitas') == $entitas->kd_entitas ? 'selected' : '' }}>
                                    {{ $entitas->kd_entitas }} - {{ $entitas->nama_entitas }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                        <input type="date" class="form-control" id="tanggal_awal" name="tanggal_awal" 
                               value="{{ old('tanggal_awal', date('Y-m-01')) }}" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" 
                               value="{{ old('tanggal_akhir', date('Y-m-t')) }}" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Jenis Input</label>
                        <div class="d-flex gap-4 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="jenis_input" id="massal_bulanan" value="bulanan" checked>
                                <label class="form-check-label" for="massal_bulanan">
                                    Input Bulanan (Dibagi Per Hari)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="jenis_input" id="massal_harian" value="harian">
                                <label class="form-check-label" for="massal_harian">
                                    Input Harian (Nilai Sama Setiap Hari)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Input Section -->
                    <div class="col-md-12">
                        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-input-cursor-text"></i> Input Nilai Realisasi</h5>
                    </div>

                    <!-- Input Bulanan - Nilai Total yang akan dibagi per hari -->
                    <div class="col-md-12" id="massalBulananContainer">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="nilai_total_bulanan" class="form-label">Nilai Total Realisasi (Bulanan)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nilai_total_bulanan" name="nilai_total_bulanan" 
                                           placeholder="Masukkan nilai total realisasi" min="0">
                                </div>
                                <div class="form-text">
                                    Nilai total ini akan dibagi rata untuk setiap hari dalam periode.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Perhitungan Otomatis</h6>
                                        <div id="perhitunganBulanan">
                                            <small class="text-muted">
                                                Pilih tanggal awal dan akhir untuk melihat perhitungan
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Input Harian - Nilai yang sama setiap hari -->
                    <div class="col-md-12 d-none" id="massalHarianContainer">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="nilai_per_hari" class="form-label">Nilai Realisasi Per Hari</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nilai_per_hari" name="nilai_per_hari" 
                                           placeholder="Masukkan nilai realisasi per hari" min="1">
                                </div>
                                <div class="form-text">
                                    Nilai ini akan diterapkan sama untuk setiap hari dalam periode.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Perhitungan Otomatis</h6>
                                        <div id="perhitunganHarian">
                                            <small class="text-muted">
                                                Pilih tanggal awal dan akhir untuk melihat perhitungan
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Preview nilai harian -->
                        <div class="mt-3 d-none" id="previewHarianContainer">
                            <label class="form-label">Preview Nilai Harian</label>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Nilai Realisasi</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="previewHarianBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div class="col-md-12">
                        <label for="keterangan_massal" class="form-label">Keterangan</label>
                        <textarea class="form-control" id="keterangan_massal" name="keterangan" rows="3" 
                                  placeholder="Masukkan keterangan (opsional)">{{ old('keterangan') }}</textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div id="infoMessage" class="text-muted"></div>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-save"></i> Simpan Realisasi Massal
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Section -->
    <div class="card mt-4 d-none" id="previewSection">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-eye"></i> Preview Data yang Akan Disimpan</h5>
        </div>
        <div class="card-body">
            <div id="previewContent"></div>
        </div>
    </div>
</div>

<style>
    .table th {
        font-weight: 600;
        font-size: 0.85rem;
    }
    .table td {
        font-size: 0.85rem;
    }
    .status-badge {
        font-size: 0.75rem;
    }
    .form-text {
        font-size: 0.8rem;
    }
    .date-range-info {
        background-color: #e9ecef;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 15px;
    }
    .perhitungan-item {
        display: flex;
        justify-content: between;
        margin-bottom: 5px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const kdAkun = document.getElementById('kd_akun');
    const kdEntitas = document.getElementById('kd_entitas');
    const tanggalAwal = document.getElementById('tanggal_awal');
    const tanggalAkhir = document.getElementById('tanggal_akhir');
    const radioBulanan = document.getElementById('massal_bulanan');
    const radioHarian = document.getElementById('massal_harian');
    const massalBulananContainer = document.getElementById('massalBulananContainer');
    const massalHarianContainer = document.getElementById('massalHarianContainer');
    const nilaiTotalBulanan = document.getElementById('nilai_total_bulanan');
    const nilaiPerHari = document.getElementById('nilai_per_hari');
    const perhitunganBulanan = document.getElementById('perhitunganBulanan');
    const perhitunganHarian = document.getElementById('perhitunganHarian');
    const previewHarianContainer = document.getElementById('previewHarianContainer');
    const previewHarianBody = document.getElementById('previewHarianBody');
    const infoMessage = document.getElementById('infoMessage');
    const previewSection = document.getElementById('previewSection');
    const previewContent = document.getElementById('previewContent');

    let targetData = [];
    let jumlahHari = 0;

    // Set default dates
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    if (!tanggalAwal.value) {
        tanggalAwal.valueAsDate = firstDayOfMonth;
    }
    if (!tanggalAkhir.value) {
        tanggalAkhir.valueAsDate = lastDayOfMonth;
    }

    // Hitung jumlah hari
    function hitungJumlahHari() {
        const startDate = new Date(tanggalAwal.value);
        const endDate = new Date(tanggalAkhir.value);
        const diffTime = Math.abs(endDate - startDate);
        jumlahHari = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        return jumlahHari;
    }

    // Toggle between monthly and daily input
    radioBulanan.addEventListener('change', function() {
        if (this.checked) {
            massalBulananContainer.classList.remove('d-none');
            massalHarianContainer.classList.add('d-none');
            updatePerhitunganBulanan();
            updateInfoMessage();
        }
    });
    
    radioHarian.addEventListener('change', function() {
        if (this.checked) {
            massalBulananContainer.classList.add('d-none');
            massalHarianContainer.classList.remove('d-none');
            updatePerhitunganHarian();
            updatePreviewHarian();
            updateInfoMessage();
        }
    });

    // Update perhitungan ketika nilai atau tanggal berubah
    [tanggalAwal, tanggalAkhir, nilaiTotalBulanan, nilaiPerHari].forEach(element => {
        element.addEventListener('input', function() {
            if (radioBulanan.checked) {
                updatePerhitunganBulanan();
            } else {
                updatePerhitunganHarian();
                updatePreviewHarian();
            }
            generatePreview();
        });
    });

    // Load data when filters change
    [kdAkun, kdEntitas, tanggalAwal, tanggalAkhir].forEach(element => {
        element.addEventListener('change', function() {
            hitungJumlahHari();
            
            if (radioBulanan.checked) {
                updatePerhitunganBulanan();
            } else {
                updatePerhitunganHarian();
                updatePreviewHarian();
            }

            if (kdAkun.value && kdEntitas.value && tanggalAwal.value && tanggalAkhir.value) {
                if (new Date(tanggalAwal.value) > new Date(tanggalAkhir.value)) {
                    alert('Tanggal awal tidak boleh lebih besar dari tanggal akhir');
                    clearData();
                    return;
                }
                loadTargetData();
            } else {
                clearData();
            }
        });
    });

    // Fungsi update perhitungan bulanan
    function updatePerhitunganBulanan() {
        const totalHari = hitungJumlahHari();
        const nilaiTotal = parseFloat(nilaiTotalBulanan.value) || 0;
        const nilaiPerHari = nilaiTotal > 0 ? Math.round(nilaiTotal / totalHari) : 0;

        perhitunganBulanan.innerHTML = `
            <div class="perhitungan-item">
                <span>Jumlah Hari:</span>
                <strong>${totalHari} hari</strong>
            </div>
            <div class="perhitungan-item">
                <span>Nilai Total:</span>
                <strong>Rp ${nilaiTotal.toLocaleString('id-ID')}</strong>
            </div>
            <div class="perhitungan-item">
                <span>Nilai Per Hari:</span>
                <strong class="text-primary">Rp ${nilaiPerHari.toLocaleString('id-ID')}</strong>
            </div>
            ${nilaiTotal > 0 ? `
            <div class="mt-2 p-2 bg-success bg-opacity-10 rounded">
                <small class="text-success">
                    <i class="bi bi-info-circle"></i> 
                    Nilai total akan dibagi rata menjadi <strong>Rp ${nilaiPerHari.toLocaleString('id-ID')}</strong> per hari
                </small>
            </div>
            ` : ''}
        `;
    }

    // Fungsi update perhitungan harian
    function updatePerhitunganHarian() {
        const totalHari = hitungJumlahHari();
        const nilaiPerHariInput = parseFloat(nilaiPerHari.value) || 0;
        const totalNilai = nilaiPerHariInput * totalHari;

        perhitunganHarian.innerHTML = `
            <div class="perhitungan-item">
                <span>Jumlah Hari:</span>
                <strong>${totalHari} hari</strong>
            </div>
            <div class="perhitungan-item">
                <span>Nilai Per Hari:</span>
                <strong>Rp ${nilaiPerHariInput.toLocaleString('id-ID')}</strong>
            </div>
            <div class="perhitungan-item">
                <span>Total Nilai:</span>
                <strong class="text-primary">Rp ${totalNilai.toLocaleString('id-ID')}</strong>
            </div>
            ${nilaiPerHariInput > 0 ? `
            <div class="mt-2 p-2 bg-success bg-opacity-10 rounded">
                <small class="text-success">
                    <i class="bi bi-info-circle"></i> 
                    Nilai <strong>Rp ${nilaiPerHariInput.toLocaleString('id-ID')}</strong> akan diterapkan untuk setiap hari
                </small>
            </div>
            ` : ''}
        `;
    }

    // Update preview nilai harian
    function updatePreviewHarian() {
        const nilaiPerHariInput = parseFloat(nilaiPerHari.value) || 0;
        const startDate = new Date(tanggalAwal.value);
        const endDate = new Date(tanggalAkhir.value);

        if (nilaiPerHariInput > 0) {
            previewHarianContainer.classList.remove('d-none');
            previewHarianBody.innerHTML = '';

            for (let i = 0; i < jumlahHari; i++) {
                const currentDate = new Date(startDate);
                currentDate.setDate(startDate.getDate() + i);
                
                const dateString = currentDate.toISOString().split('T')[0];
                const formattedDate = currentDate.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${formattedDate}</td>
                    <td class="text-end">Rp ${nilaiPerHariInput.toLocaleString('id-ID')}</td>
                    <td class="text-center">
                        <span class="badge bg-success status-badge">Akan Disimpan</span>
                    </td>
                `;
                previewHarianBody.appendChild(row);
            }
        } else {
            previewHarianContainer.classList.add('d-none');
        }
    }

    function loadTargetData() {
        const formData = new FormData();
        formData.append('kd_akun', kdAkun.value);
        formData.append('kd_entitas', kdEntitas.value);
        formData.append('tanggal_awal', tanggalAwal.value);
        formData.append('tanggal_akhir', tanggalAkhir.value);

        infoMessage.innerHTML = '<span class="text-info"><i class="bi bi-hourglass-split"></i> Memuat data target...</span>';

        fetch('{{ route("realisasi.getTargetData") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                targetData = data.data;
                updateInfoMessage();
                generatePreview();
            } else {
                alert('Gagal memuat data target: ' + data.message);
                clearData();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat memuat data');
            clearData();
        });
    }

    function updateInfoMessage() {
        if (targetData.length === 0) {
            infoMessage.textContent = 'Pilih akun, entitas, dan rentang tanggal untuk melihat data target.';
            return;
        }

        const totalTarget = targetData.reduce((sum, item) => sum + parseFloat(item.nilai), 0);
        const existingRealisasi = targetData.filter(item => item.has_realisasi).length;
        const newRealisasi = targetData.length - existingRealisasi;
        
        const startDate = new Date(tanggalAwal.value);
        const endDate = new Date(tanggalAkhir.value);
        
        infoMessage.innerHTML = `
            <div class="date-range-info">
                <strong>Periode:</strong> ${startDate.toLocaleDateString('id-ID')} - ${endDate.toLocaleDateString('id-ID')} 
                (${jumlahHari} hari)<br>
                <strong>Data Target:</strong> ${targetData.length} entri | 
                <strong>Total Target:</strong> Rp ${totalTarget.toLocaleString('id-ID')}<br>
                <strong>Status:</strong> 
                <span class="text-success">${newRealisasi} baru</span>
                ${existingRealisasi > 0 ? `• <span class="text-warning">${existingRealisasi} sudah ada</span>` : ''}
            </div>
        `;
    }

    function generatePreview() {
        if (targetData.length === 0) {
            previewSection.classList.add('d-none');
            return;
        }

        let previewHTML = '';
        const startDate = new Date(tanggalAwal.value);
        const endDate = new Date(tanggalAkhir.value);
        
        if (radioBulanan.checked) {
            const nilaiTotal = parseFloat(nilaiTotalBulanan.value) || 0;
            const nilaiPerHariCalc = nilaiTotal > 0 ? Math.round(nilaiTotal / jumlahHari) : 0;
            const existingCount = targetData.filter(item => item.has_realisasi).length;
            const newCount = targetData.length - existingCount;
            
            previewHTML = `
                <p><strong>Mode:</strong> Input Bulanan (Dibagi Per Hari)</p>
                <p><strong>Periode:</strong> ${startDate.toLocaleDateString('id-ID')} - ${endDate.toLocaleDateString('id-ID')} (${jumlahHari} hari)</p>
                <p><strong>Nilai Total:</strong> Rp ${nilaiTotal.toLocaleString('id-ID')}</p>
                <p><strong>Nilai Per Hari:</strong> Rp ${nilaiPerHariCalc.toLocaleString('id-ID')}</p>
                <p><strong>Jumlah Target:</strong> ${targetData.length} data</p>
                <p><strong>Yang akan disimpan:</strong> ${newCount} data baru</p>
                ${existingCount > 0 ? `<p class="text-warning"><strong>Perhatian:</strong> ${existingCount} data sudah memiliki realisasi dan akan dilewati.</p>` : ''}
                ${nilaiTotal <= 0 ? `<p class="text-danger"><strong>Peringatan:</strong> Nilai total harus lebih dari 0.</p>` : ''}
            `;
        } else {
            const nilaiPerHariInput = parseFloat(nilaiPerHari.value) || 0;
            const totalNilai = nilaiPerHariInput * jumlahHari;
            const existingCount = targetData.filter(item => item.has_realisasi).length;
            const newCount = targetData.length - existingCount;
            
            previewHTML = `
                <p><strong>Mode:</strong> Input Harian (Nilai Sama Setiap Hari)</p>
                <p><strong>Periode:</strong> ${startDate.toLocaleDateString('id-ID')} - ${endDate.toLocaleDateString('id-ID')} (${jumlahHari} hari)</p>
                <p><strong>Nilai Per Hari:</strong> Rp ${nilaiPerHariInput.toLocaleString('id-ID')}</p>
                <p><strong>Total Nilai:</strong> Rp ${totalNilai.toLocaleString('id-ID')}</p>
                <p><strong>Jumlah Target:</strong> ${targetData.length} data</p>
                <p><strong>Yang akan disimpan:</strong> ${newCount} data baru</p>
                ${existingCount > 0 ? `<p class="text-warning">${existingCount} data sudah memiliki realisasi dan akan dilewati.</p>` : ''}
                ${nilaiPerHariInput <= 0 ? `<p class="text-danger"><strong>Peringatan:</strong> Nilai per hari harus lebih dari 0.</p>` : ''}
            `;
        }

        previewContent.innerHTML = previewHTML;
        previewSection.classList.remove('d-none');
    }

    function clearData() {
        targetData = [];
        infoMessage.textContent = 'Pilih akun, entitas, dan rentang tanggal untuk melihat data target.';
        previewSection.classList.add('d-none');
    }

    // Form validation
    document.getElementById('inputMassalForm').addEventListener('submit', function(e) {
        console.log('Form submit dipanggil');
        
        if (!kdAkun.value || !kdEntitas.value || !tanggalAwal.value || !tanggalAkhir.value) {
            e.preventDefault();
            alert('Harap lengkapi semua filter data terlebih dahulu.');
            return;
        }

        if (new Date(tanggalAwal.value) > new Date(tanggalAkhir.value)) {
            e.preventDefault();
            alert('Tanggal awal tidak boleh lebih besar dari tanggal akhir.');
            return;
        }

        if (radioBulanan.checked) {
            const nilaiTotal = parseFloat(nilaiTotalBulanan.value);
            if (!nilaiTotal || nilaiTotal <= 0) {
                e.preventDefault();
                alert('Harap masukkan nilai total realisasi yang valid (lebih dari 0).');
                nilaiTotalBulanan.focus();
                return;
            }
        }

        if (radioHarian.checked) {
            const nilaiHarian = parseFloat(nilaiPerHari.value);
            if (!nilaiHarian || nilaiHarian <= 0) {
                e.preventDefault();
                alert('Harap masukkan nilai realisasi per hari yang valid (lebih dari 0).');
                nilaiPerHari.focus();
                return;
            }
        }

        console.log('Form validation passed, submitting...');

        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...';
    });

    // Auto-load data jika semua filter sudah terisi
    if (kdAkun.value && kdEntitas.value && tanggalAwal.value && tanggalAkhir.value) {
        if (new Date(tanggalAwal.value) <= new Date(tanggalAkhir.value)) {
            hitungJumlahHari();
            if (radioBulanan.checked) {
                updatePerhitunganBulanan();
            } else {
                updatePerhitunganHarian();
                updatePreviewHarian();
            }
            loadTargetData();
        }
    }
});
</script>
@endsection