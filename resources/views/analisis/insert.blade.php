@extends('layouts.app')
@section('title','Tambah Analisis')
@section('content')
<div class="container-fluid">
  <h4 class="mb-3"><i class="bi bi-file-earmark-bar-graph"></i> Tambah Analisis</h4>
  
  <div class="card shadow-sm">
    <div class="card-body">
      <form action="{{ route('analisis.store') }}" method="POST">
        @csrf
        
        <div class="mb-3">
          <label for="tanggal" class="form-label">Tanggal Analisis</label>
          <input type="date" class="form-control" id="tanggal" name="tanggal" required>
        </div>
        
        <div class="mb-3">
          <label for="kd_laporan" class="form-label">Kode Laporan</label>
          <select class="form-select" id="kd_laporan" name="kd_laporan" required>
            <option value="" selected disabled>Pilih Laporan</option>
            @foreach($laporanList as $laporan)
              <option value="{{ $laporan->kd_laporan }}">{{ $laporan->kd_laporan }} - {{ $laporan->keterangan }}</option>
            @endforeach
          </select>
        </div>
        
        <div class="mb-3">
          <label for="uraian" class="form-label">Uraian Analisis</label>
          <textarea class="form-control" id="uraian" name="uraian" rows="3" required></textarea>
        </div>       
        
        <!-- Hidden fields for bulan and tahun -->
        <input type="hidden" id="bulan" name="bulan">
        <input type="hidden" id="tahun" name="tahun">
        
        <div class="d-flex justify-content-end">
          <a href="{{ route('analisis.view') }}" class="btn btn-secondary me-2">
            <i class="bi bi-arrow-left"></i> Kembali
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tanggalInput = document.getElementById('tanggal');
    
    // Set default date to today
    const today = new Date();
    const formattedDate = today.toISOString().substr(0, 10);
    tanggalInput.value = formattedDate;
    
    // Update bulan and tahun when date changes
    tanggalInput.addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        const bulan = selectedDate.toLocaleString('id-ID', { month: 'long' });
        const tahun = selectedDate.getFullYear();
        
        document.getElementById('bulan').value = bulan;
        document.getElementById('tahun').value = tahun;
    });
    
    // Trigger change event initially to set bulan and tahun
    tanggalInput.dispatchEvent(new Event('change'));
});
</script>

<style>
  .card {
    border-radius: 10px;
    border: 1px solid #dee2e6;
  }
  
  .form-label {
    font-weight: 500;
  }
  
  .form-select, .form-control {
    border-radius: 5px;
  }
</style>
@endsection