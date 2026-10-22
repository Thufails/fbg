@extends('layouts.appform')
@section('content')
  <h3 class="mb-4"><i class="bi bi-card-checklist"></i> Tambah Tugas</h3>
  <form action="{{ route('tugas.store') }}" method="POST">
    @csrf
    <div class="mb-3">
      <label class="form-label"><i class="bi bi-calendar-check"></i> Tanggal Jatuh Tempo</label>
      <input type="date" name="tanggal" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label"><i class="bi bi-card-text"></i> Uraian Tugas</label>
      <textarea name="uraian" class="form-control" rows="3" required></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label"><i class="bi bi-list-stars"></i> Status</label>
      <div class="form-radio-group">
        <div class="form-radio-item">
          <input class="form-check-input" type="radio" name="status" id="status-todo" value="0" checked>
          <label class="form-check-label ms-2" for="status-todo">
            <i class="bi bi-hourglass-top text-warning"></i> Todo
          </label>
        </div>
        <div class="form-radio-item">
          <input class="form-check-input" type="radio" name="status" id="status-in-progress" value="1">
          <label class="form-check-label ms-2" for="status-in-progress">
            <i class="bi bi-gear-wide-connected text-info"></i> In Progress
          </label>
        </div>
        <div class="form-radio-item">
          <input class="form-check-input" type="radio" name="status" id="status-done" value="2">
          <label class="form-check-label ms-2" for="status-done">
            <i class="bi bi-check-circle-fill text-success"></i> Done
          </label>
        </div>
      </div>
    </div>
    <div class="d-flex justify-content-between">
      <a href="{{ route('tugas.view') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
      <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill"></i> Simpan</button>
    </div>
  </form>
@endsection