@extends('layouts.appform')
@section('content')
  <h3 class="mb-4"><i class="bi bi-calendar-check"></i> Edit Konversi Tanggal</h3>
  <form action="{{ route('konversiTanggal.update', $konversi_tanggal->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="mb-3">
      <label class="form-label"><i class="bi bi-calendar-date"></i> Tanggal Masehi</label>
      <input type="date" name="tanggal" class="form-control @error('tanggal') is-invalid @enderror" 
             value="{{ old('tanggal', \Carbon\Carbon::parse($konversi_tanggal->tanggal)->format('Y-m-d')) }}" required>
      @error('tanggal')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-calendar-event"></i> Tanggal Hijriah Saat Ini</label>
      <input type="text" class="form-control bg-light" value="{{ $konversi_tanggal->tanggal_h }}" readonly>
      <small class="text-muted">Tanggal hijriah akan diperbarui otomatis setelah data disimpan</small>
    </div>

    <div class="alert alert-warning">
      <i class="bi bi-exclamation-triangle"></i> 
      <strong>Perhatian:</strong> Mengubah tanggal masehi akan mengubah hasil konversi hijriah.
    </div>

    <div class="d-flex justify-content-between">
      <a href="{{ route('konversiTanggal.view') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left-circle"></i> Kembali
      </a>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-save2"></i> Update
      </button>
    </div>

  </form>
</div>
@endsection