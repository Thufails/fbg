@extends('layouts.appform')
@section('content')
  <h3 class="mb-4"><i class="bi bi-exclamation-triangle-fill"></i> Tambah Issue</h3>
  <form action="{{ route('issue.store') }}" method="POST">
    @csrf
    <div class="mb-3">
      <label class="form-label"><i class="bi bi-calendar-day"></i> Tanggal</label>
      <input type="date" name="tanggal" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label"><i class="bi bi-info-circle"></i> Deskripsi Masalah</label>
      <textarea name="uraian" class="form-control" rows="3" required></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label"><i class="bi bi-plus-slash-minus"></i> Status</label>
      <div class="form-radio-group">
        <div class="form-radio-item">
          <input class="form-check-input" type="radio" name="operasi" id="operasi-plus" value="0" checked>
          <label class="form-check-label ms-2" for="operasi-plus">
            <i class="bi bi-plus-circle text-success"></i> Plan
          </label>
        </div>
        <div class="form-radio-item">
          <input class="form-check-input" type="radio" name="operasi" id="operasi-plus" value="1">
          <label class="form-check-label ms-2" for="operasi-plus">
            <i class="bi bi-plus-circle text-success"></i> Proses
          </label>
        </div>
        <div class="form-radio-item">
          <input class="form-check-input" type="radio" name="operasi" id="operasi-minus" value="2">
          <label class="form-check-label ms-2" for="operasi-minus">
            <i class="bi bi-dash-circle text-danger"></i> Done
          </label>
        </div>
      </div>
    </div>
    <div class="d-flex justify-content-between">
      <a href="issue_view.html" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Kembali</a>
      <button type="submit" class="btn btn-primary"><i class="bi bi-bug-fill"></i> Laporkan</button>
    </div>
  </form>
</div>
@endsection
