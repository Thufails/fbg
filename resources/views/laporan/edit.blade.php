@extends('layouts.appform')
@section('content')
  <h3 class="mb-4"><i class="bi bi-file-earmark-edit"></i> Edit Laporan</h3>
  <form action="{{ route('laporan.update', $laporan->kd_laporan) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
    <div class="row mb-3">
      <div class="col-md-4">
        <label class="form-label"><i class="bi bi-calendar-date"></i> Tanggal Laporan</label>
        <input type="date" name="tanggal" class="form-control" 
               value="{{ old('tanggal', $laporan->tanggal) }}" required>
      </div>
      <div class="col-md-4">
        <label class="form-label"><i class="bi bi-calendar-range"></i> Tanggal Awal Periode</label>
        <input type="date" name="tanggal_awal" class="form-control" 
               value="{{ old('tanggal_awal', $laporan->tanggal_awal) }}" required>
      </div>
      <div class="col-md-4">
        <label class="form-label"><i class="bi bi-calendar-range-fill"></i> Tanggal Akhir Periode</label>
        <input type="date" name="tanggal_akhir" class="form-control" 
               value="{{ old('tanggal_akhir', $laporan->tanggal_akhir) }}" required>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-card-text"></i> Keterangan Laporan</label>
      <textarea name="keterangan" class="form-control" rows="5" required>
        {{ old('keterangan', $laporan->keterangan) }}
      </textarea>
    </div>

    <!-- Upload File PDF -->
    <div class="mb-3">
      <label class="form-label"><i class="bi bi-file-pdf"></i> Upload File PDF</label>
      <input type="file" name="pdf_file" class="form-control" accept=".pdf" id="pdf_file">
      <div class="form-text">Ukuran maksimal: 5MB. Format: PDF</div>
      
      @if($laporan->path_pdf)
      <div class="mt-2">
        <div class="d-flex align-items-center">
          <i class="bi bi-file-pdf text-danger me-2"></i>
          <a href="{{ Storage::url($laporan->path_pdf) }}" target="_blank" class="me-3">
            Lihat File PDF Saat Ini
          </a>
          <span class="badge bg-secondary">
            {{ \Carbon\Carbon::parse($laporan->pdf_updated_at ?? $laporan->updated_at)->format('d/m/Y H:i') }}
          </span>
        </div>
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" name="delete_pdf" id="delete_pdf" value="1">
          <label class="form-check-label text-danger" for="delete_pdf">
            <i class="bi bi-trash"></i> Hapus file PDF saat ini
          </label>
        </div>
      </div>
      @endif
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-person-check"></i> Status Laporan</label>
      <div class="form-radio-group">
        <div class="form-radio-item">
          <input class="form-check-input" type="radio" name="status" id="status-draft" 
                 value="0" {{ old('status', $laporan->status) == 0 ? 'checked' : '' }}>
          <label class="form-check-label ms-2" for="status-draft">
            <i class="bi bi-file-earmark-text text-secondary"></i> Draft
          </label>
        </div>
        <div class="form-radio-item">
          <input class="form-check-input" type="radio" name="status" id="status-review" 
                 value="1" {{ old('status', $laporan->status) == 1 ? 'checked' : '' }}>
          <label class="form-check-label ms-2" for="status-review">
            <i class="bi bi-eye text-primary"></i> Dalam Review
          </label>
        </div>
        <div class="form-radio-item">
          <input class="form-check-input" type="radio" name="status" id="status-approved" 
                 value="2" {{ old('status', $laporan->status) == 2 ? 'checked' : '' }}>
          <label class="form-check-label ms-2" for="status-approved">
            <i class="bi bi-check-circle-fill text-success"></i> Disetujui
          </label>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between">
      <a href="{{ route('laporan.view') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill"></i> Update Laporan</button>
    </div>
  </form>
@endsection

<style>
.form-radio-group {
  display: flex;
  gap: 1.5rem;
  margin-top: 0.5rem;
}
.form-radio-item {
  display: flex;
  align-items: center;
}
.form-check-input {
  margin-top: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Validasi ukuran file PDF
  document.getElementById('pdf_file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const fileSize = file.size / 1024 / 1024; // dalam MB
      const maxSize = 5; // 5MB
      
      if (fileSize > maxSize) {
        alert('Ukuran file terlalu besar! Maksimal 5MB.');
        e.target.value = '';
      }
      
      // Validasi ekstensi
      const fileName = file.name;
      const fileExt = fileName.split('.').pop().toLowerCase();
      if (fileExt !== 'pdf') {
        alert('Hanya file PDF yang diizinkan!');
        e.target.value = '';
      }
    }
  });
});
</script>