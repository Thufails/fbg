{{-- resources/views/realisasi/cart.blade.php --}}
@extends('layouts.app')
@section('title', 'Keranjang Realisasi')
@section('content')
<div class="container-fluid">
    <h4 class="mb-3"><i class="bi bi-cart"></i> Keranjang Realisasi</h4>
    <div class="card mb-4">
        <div class="card-body">
            @if(empty($cartItems))
                <div class="alert alert-warning">Keranjang realisasi Anda kosong.</div>
            @else
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <h5>Akun: {{ $akunInfo->kd_akun ?? '' }} - {{ $akunInfo->nama_akun ?? '' }}</h5>
                    </div>
                    <div>
                        <a href="{{ route('realisasi.emptyCart') }}" class="btn btn-danger me-2">
                            <i class="bi bi-trash me-1"></i> Kosongkan Keranjang
                        </a>
                        <a href="{{ route('realisasi.select') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Pilih Target
                        </a>
                    </div>
                </div>
                
                <form action="{{ route('realisasi.store') }}" method="POST" id="realisasiForm">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="persentase_global" class="form-label">Persentase Realisasi Global (%)</label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control" 
                                       id="persentase_global" 
                                       min="0" 
                                       max="100" 
                                       step="0.01"
                                       placeholder="Masukkan persentase">
                                <button type="button" class="btn btn-outline-primary" id="applyPersentase">
                                    Terapkan
                                </button>
                            </div>
                            <small class="text-muted">Isi persentase lalu klik terapkan</small>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th width="100">Tanggal</th>
                                    <th width="120">Tanggal Hijriah</th> {{-- Tambah kolom ini --}}
                                    <th>Kode Akun</th>
                                    <th>Nilai Target</th>
                                    <th width="150">Persentase (%)</th>
                                    <th width="200">Nilai Realisasi</th>
                                    <th width="250">Keterangan</th>
                                    <th width="50">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cartItems as $index => $item)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($item['tanggal'])->format('d-m-Y') }}</td>
                                    <td>{{ $item['tanggal_hijriah'] ?? '' }}</td> {{-- Tampilkan tanggal hijriah --}}
                                    <td>{{ $item['kd_akun'] }}</td>
                                    <td class="text-end" id="target_{{ $index }}">
                                        {{ number_format($item['nilai_target'], 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <input type="number" 
                                               class="form-control persentase-input" 
                                               data-index="{{ $index }}"
                                               id="persentase_{{ $index }}"
                                               min="0" 
                                               step="0.01"
                                               value="{{ $item['persentase'] ?? 0 }}">
                                    </td>
                                    <td>
                                        <input type="number" 
                                               name="realisasi[{{ $index }}][nilai]" 
                                               class="form-control realisasi-input" 
                                               id="realisasi_{{ $index }}"
                                               data-index="{{ $index }}"
                                               value="{{ $item['nilai_realisasi'] }}" 
                                               required>
                                        <input type="hidden" 
                                               name="realisasi[{{ $index }}][kd_target]" 
                                               value="{{ $item['kd_target'] }}">
                                        <input type="hidden" 
                                               name="realisasi[{{ $index }}][tanggal]" 
                                               value="{{ $item['tanggal'] }}">
                                    </td>
                                    <td>
                                        <input type="text" 
                                               name="realisasi[{{ $index }}][keterangan]" 
                                               class="form-control" 
                                               value="{{ $item['keterangan'] }}">
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('realisasi.removeFromCart', $index) }}" 
                                           class="btn btn-sm btn-danger"
                                           title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-3">
                        <div class="text-muted">
                            Total Item: {{ count($cartItems) }}
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Simpan Realisasi
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk menghitung realisasi berdasarkan persentase
    function hitungRealisasi(index) {
        const targetElement = document.getElementById(`target_${index}`);
        const persentaseInput = document.getElementById(`persentase_${index}`);
        const realisasiInput = document.getElementById(`realisasi_${index}`);
        
        // Ambil nilai target (hilangkan format angka)
        const targetText = targetElement.textContent.trim();
        const targetValue = parseFloat(targetText.replace(/\./g, '')) || 0;
        
        // Ambil nilai persentase
        const persentase = parseFloat(persentaseInput.value) || 0;
        
        // Hitung realisasi
        const realisasi = (targetValue * persentase) / 100;
        
        // Set nilai realisasi (bulatkan ke 2 desimal)
        realisasiInput.value = realisasi.toFixed(2);
    }
    
    // Event listener untuk input persentase individual
    document.querySelectorAll('.persentase-input').forEach(input => {
        input.addEventListener('input', function() {
            const index = this.getAttribute('data-index');
            hitungRealisasi(index);
        });
        
        // Hitung realisasi saat pertama kali load
        const index = input.getAttribute('data-index');
        hitungRealisasi(index);
    });
    
    // Event listener untuk input persentase global
    document.getElementById('applyPersentase').addEventListener('click', function() {
        const persentaseGlobal = parseFloat(document.getElementById('persentase_global').value);
        
        if (isNaN(persentaseGlobal) || persentaseGlobal < 0 || persentaseGlobal > 100) {
            alert('Masukkan persentase yang valid (0-100)');
            return;
        }
        
        // Terapkan persentase ke semua input
        document.querySelectorAll('.persentase-input').forEach(input => {
            input.value = persentaseGlobal;
            const index = input.getAttribute('data-index');
            hitungRealisasi(index);
        });
    });
    
    // Event listener untuk input realisasi manual
    document.querySelectorAll('.realisasi-input').forEach(input => {
        input.addEventListener('input', function() {
            const index = this.getAttribute('data-index');
            const targetElement = document.getElementById(`target_${index}`);
            const persentaseInput = document.getElementById(`persentase_${index}`);
            const realisasiValue = parseFloat(this.value) || 0;
            
            // Ambil nilai target
            const targetText = targetElement.textContent.trim();
            const targetValue = parseFloat(targetText.replace(/\./g, '')) || 0;
            
            // Hitung persentase berdasarkan input realisasi manual
            if (targetValue > 0) {
                const persentase = (realisasiValue / targetValue) * 100;
                persentaseInput.value = persentase.toFixed(2);
            }
        });
    });
});
</script>

<style>
.persentase-input:focus, .realisasi-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.realisasi-input[readonly] {
    background-color: #f8f9fa;
    cursor: not-allowed;
}
</style>
@endsection