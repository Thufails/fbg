@extends('layouts.appform')
@section('content')
  <h3 class="mb-4"><i class="bi bi-file-earmark-plus"></i> Tambah Laporan</h3>
  <form action="{{ route('laporan.store') }}" method="POST">
    @csrf
    <div class="row mb-3">
      <div class="col-md-4">
        <label class="form-label"><i class="bi bi-calendar-date"></i> Tanggal Laporan</label>
        <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', date('Y-m-d')) }}" required>
      </div>
      <div class="col-md-4">
        <label class="form-label"><i class="bi bi-calendar-range"></i> Tanggal Awal Periode</label>
        <input type="date" name="tanggal_awal" class="form-control" value="{{ old('tanggal_awal') }}" required>
      </div>
      <div class="col-md-4">
        <label class="form-label"><i class="bi bi-calendar-range-fill"></i> Tanggal Akhir Periode</label>
        <input type="date" name="tanggal_akhir" class="form-control" value="{{ old('tanggal_akhir') }}" required>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-card-text"></i> Keterangan Laporan</label>
      <textarea name="keterangan" class="form-control" rows="5" required>{{ old('keterangan') }}</textarea>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-person-check"></i> Status Laporan</label>
      <div class="form-radio-group">
        <div class="form-radio-item">
          <input class="form-check-input" type="radio" name="status" id="status-draft" value="0" checked>
          <label class="form-check-label ms-2" for="status-draft">
            <i class="bi bi-file-earmark-text text-secondary"></i> Draft
          </label>
        </div>
        <div class="form-radio-item">
          <input class="form-check-input" type="radio" name="status" id="status-review" value="1">
          <label class="form-check-label ms-2" for="status-review">
            <i class="bi bi-eye text-primary"></i> Dalam Review
          </label>
        </div>
        <div class="form-radio-item">
          <input class="form-check-input" type="radio" name="status" id="status-approved" value="2">
          <label class="form-check-label ms-2" for="status-approved">
            <i class="bi bi-check-circle-fill text-success"></i> Disetujui
          </label>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between">
      <a href="{{ route('laporan.view') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill"></i> Simpan Laporan</button>
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